<?php
/**
 * API — Anular una venta ya sincronizada  →  POST /api/pos_venta_anular.php
 *
 * Deshace una venta que ya está en el servidor: devuelve el stock (incluidos los
 * hijos de los combos) y borra la orden con sus líneas, en una transacción.
 *
 * Idempotente: si la orden ya no existe, responde ok con `ya_anulada`.
 * Solo la puede anular el cajero que la hizo, o un administrador (nivel 1).
 * Las ventas a crédito se anulan desde el módulo de créditos, no desde aquí.
 *
 * Autenticación: X-Api-Key + Bearer. Contrato: PLAN.md §5 y api/README.md.
 */

require_once __DIR__ . '/_pos_comun.php';

exigirMetodo('POST');
exigirApiKey();
limitar('anular', 60, 60);

$sesion = exigirSesion($conexion);
$bssId = (int)$sesion['bss_id'];
$sucursalId = (int)($sesion['sucursal_id'] ?? 0);
$usuarioId = (int)$sesion['id_user'];
$nivel = (int)($sesion['nivel'] ?? 2);

$in = entrada();
$idPedido = trim((string)($in['id_pedido'] ?? ''));
if ($idPedido === '') {
    salir(400, ['ok' => false, 'error' => 'Falta id_pedido']);
}

$stmt = $conexion->prepare(
    "SELECT id, usuario, id_sucursal, status, tipoPago
     FROM orden WHERE id_pedido = ? AND bss_id = ? LIMIT 1"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('si', $idPedido, $bssId);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orden) {
    salir(200, ['ok' => true, 'ya_anulada' => true]);
}

$ordenId = (int)$orden['id'];

if ((int)$orden['usuario'] !== $usuarioId && $nivel !== 1) {
    salir(403, ['ok' => false, 'error' => 'Esa venta la hizo otro cajero']);
}
if ((int)$orden['id_sucursal'] !== $sucursalId) {
    salir(403, ['ok' => false, 'error' => 'Esa venta es de otra sucursal']);
}
if ((int)$orden['status'] === 2) {
    salir(409, ['ok' => false, 'error' => 'Las ventas a crédito se anulan desde el módulo de créditos']);
}

// El POS guarda en `status` el tipo de venta: 4 = al mayor (1 = normal, 2 = crédito).
// Ojo: tipoPago es el método de pago (4 = efectivo), no el tipo de venta.

try {
    $conexion->begin_transaction();

    devolverStock($conexion, $ordenId, $bssId, $sucursalId);

    $stmt = $conexion->prepare("DELETE FROM orden_articulos WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $ordenId);
        $stmt->execute();
        $stmt->close();
    }

    // Desglose del pago fraccionado (si la venta lo tenía).
    $stmt = $conexion->prepare("DELETE FROM fracciones WHERE id_order = ? AND bss_id = ?");
    if ($stmt) {
        $stmt->bind_param('ii', $ordenId, $bssId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conexion->prepare("DELETE FROM orden WHERE id = ? AND bss_id = ?");
    if (!$stmt) {
        throw new Exception('No se pudo borrar la orden');
    }
    $stmt->bind_param('ii', $ordenId, $bssId);
    $stmt->execute();
    $stmt->close();

    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    salir(500, ['ok' => false, 'error' => $e->getMessage()]);
}

salir(200, ['ok' => true, 'ya_anulada' => false, 'orden_id' => $ordenId]);

// ───────────────────────────────────────────────────────────────────────────────

/**
 * Devuelve al stock lo que descontó la venta. Los combos devuelven a sus hijos.
 * En las ventas al mayor se devuelve `cantidad * cantidad_unidades` al producto base
 * (stock.id_stock), igual que como se descontó al registrar la venta. Se decide por
 * producto, no por la orden: una orden al mayor puede traer líneas de detal.
 */
function devolverStock($conexion, int $ordenId, int $bssId, int $sucursalId): void
{
    $stmtLineas = $conexion->prepare("SELECT product_id, quantity FROM orden_articulos WHERE order_id = ?");
    if (!$stmtLineas) {
        throw new Exception('No se pudieron leer las líneas de la venta');
    }
    $stmtLineas->bind_param('i', $ordenId);
    $stmtLineas->execute();
    $resLineas = $stmtLineas->get_result();

    $lineas = [];
    while ($fila = $resLineas->fetch_assoc()) {
        $lineas[] = ['id' => (int)$fila['product_id'], 'cantidad' => (float)$fila['quantity']];
    }
    $stmtLineas->close();

    $stmtCombo = $conexion->prepare(
        "SELECT producto_id, cantidad FROM combo_items WHERE combo_producto_id = ? AND bss_id = ?"
    );
    $stmtUnidades = $conexion->prepare("SELECT mayor, cantidad_unidades FROM productos WHERE id = ? LIMIT 1");
    $stmtIdStock = $conexion->prepare("SELECT id_stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
    $stmtSumarMayor = $conexion->prepare(
        "UPDATE stock SET stock = stock + ? WHERE id = ? AND id_sucursal = ? AND bss_id = ?"
    );
    $stmtSumar = $conexion->prepare(
        "UPDATE stock SET stock = stock + ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
    );
    if (!$stmtSumar) {
        throw new Exception('No se pudo preparar el stock');
    }

    foreach ($lineas as $linea) {
        $productoId = $linea['id'];
        $cantidad = $linea['cantidad'];
        if ($cantidad <= 0) {
            continue;
        }

        // ¿Combo? Se devuelve a cada producto hijo.
        $esCombo = false;
        if ($stmtCombo) {
            $stmtCombo->bind_param('ii', $productoId, $bssId);
            $stmtCombo->execute();
            $resCombo = $stmtCombo->get_result();
            if ($resCombo && $resCombo->num_rows > 0) {
                $esCombo = true;
                while ($hijo = $resCombo->fetch_assoc()) {
                    sumarStock($stmtSumar, (int)$hijo['producto_id'],
                        $cantidad * (float)$hijo['cantidad'], $sucursalId, $bssId);
                }
            }
        }
        if ($esCombo) {
            continue;
        }

        $filaU = null;
        if ($stmtUnidades) {
            $stmtUnidades->bind_param('i', $productoId);
            $stmtUnidades->execute();
            $filaU = $stmtUnidades->get_result()->fetch_assoc();
        }

        if ($filaU && (string)$filaU['mayor'] === '1') {
            $unidades = (float)$filaU['cantidad_unidades'];
            $stmtIdStock->bind_param('iii', $productoId, $sucursalId, $bssId);
            $stmtIdStock->execute();
            $filaS = $stmtIdStock->get_result()->fetch_assoc();
            $idStock = $filaS ? (int)$filaS['id_stock'] : 0;
            if ($idStock > 0 && $unidades > 0) {
                $cantidadMayor = $cantidad * $unidades;
                $stmtSumarMayor->bind_param('diii', $cantidadMayor, $idStock, $sucursalId, $bssId);
                if (!$stmtSumarMayor->execute()) {
                    throw new Exception('No se pudo devolver el stock al mayor del producto ' . $productoId);
                }
            }
            continue;
        }

        sumarStock($stmtSumar, $productoId, $cantidad, $sucursalId, $bssId);
    }

    $stmtCombo->close();
    $stmtUnidades->close();
    $stmtIdStock->close();
    $stmtSumarMayor->close();
    $stmtSumar->close();
}

function sumarStock($stmt, int $productoId, float $cantidad, int $sucursalId, int $bssId): void
{
    $stmt->bind_param('diii', $cantidad, $productoId, $sucursalId, $bssId);
    if (!$stmt->execute()) {
        throw new Exception('No se pudo devolver el stock del producto ' . $productoId);
    }
}
