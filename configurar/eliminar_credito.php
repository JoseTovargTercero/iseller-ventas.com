<?php
require_once 'configuracion.php';
require_once 'session.php';

header('Content-Type: application/json');

function responderCredito($ok, $mensaje = '', $extra = [])
{
    echo json_encode(array_merge(['success' => $ok, 'message' => $mensaje], $extra));
    exit;
}

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    responderCredito(false, 'No autorizado');
}

$orderId = (int) ($_POST['order_id'] ?? 0);
$devolverStock = (int) ($_POST['devolver_stock'] ?? 0) === 1;

if ($orderId <= 0) {
    responderCredito(false, 'Crédito no válido');
}

// Crédito activo + orden del mismo negocio
$stmt = $conexion->prepare(
    "SELECT c.negocio, c.estado, c.tipoCompra, o.id_sucursal
       FROM creditos c
       INNER JOIN orden o ON o.id = c.order_id
      WHERE c.order_id = ? AND o.bss_id = ?
      LIMIT 1"
);
$stmt->bind_param('ii', $orderId, $bss_id);
$stmt->execute();
$credito = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$credito || (int) $credito['estado'] !== 2) {
    responderCredito(false, 'Crédito no encontrado o ya pagado');
}
if ($nivel === 2 && (int) $_SESSION['sucursal'] !== (int) $credito['id_sucursal']) {
    responderCredito(false, 'No tiene permisos para eliminar este crédito');
}

$negocio = $credito['negocio'];
$esMayor = (int) $credito['tipoCompra'] === 4;
$abonosEliminados = 0;

try {
    $conexion->begin_transaction();

    if ($devolverStock) {
        devolverStockCredito($conexion, $orderId, $bss_id, $esMayor);
    }

    $stmt = $conexion->prepare("DELETE FROM fracciones WHERE id_order = ? AND bss_id = ?");
    $stmt->bind_param('ii', $orderId, $bss_id);
    $stmt->execute();
    $stmt->close();

    // La FK de creditos y orden_articulos borra en cascada
    $stmt = $conexion->prepare("DELETE FROM orden WHERE id = ? AND bss_id = ?");
    $stmt->bind_param('ii', $orderId, $bss_id);
    $stmt->execute();
    $stmt->close();

    // ¿Quedan créditos pendientes del cliente?
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM creditos WHERE negocio = ? AND bss_id = ? AND estado != 1");
    $stmt->bind_param('si', $negocio, $bss_id);
    $stmt->execute();
    $stmt->bind_result($pendientes);
    $stmt->fetch();
    $stmt->close();

    // Cuenta cerrada: sin deuda pendiente, los abonos ya no tienen respaldo
    if ((int) $pendientes === 0) {
        $stmt = $conexion->prepare("DELETE FROM abonos WHERE cliente = ? AND bss_id = ?");
        $stmt->bind_param('si', $negocio, $bss_id);
        $stmt->execute();
        $abonosEliminados = $stmt->affected_rows;
        $stmt->close();
    }

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    responderCredito(false, 'No se pudo eliminar el crédito: ' . $e->getMessage());
}

responderCredito(true, 'Crédito y venta eliminados', [
    'cuenta_cerrada' => (int) $pendientes === 0,
    'abonos_eliminados' => (int) $abonosEliminados,
]);

/**
 * Devuelve al stock lo que descontó la venta. Los combos devuelven a sus hijos
 * y las ventas al mayor por cantidad_unidades, igual que al registrarlas.
 */
function devolverStockCredito($conexion, $orderId, $bssId, $esMayor)
{
    $stmt = $conexion->prepare("SELECT product_id, quantity, id_sucursal FROM orden_articulos WHERE order_id = ?");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    $lineas = [];
    while ($fila = $res->fetch_assoc()) {
        $lineas[] = $fila;
    }
    $stmt->close();

    $stmtCombo = $conexion->prepare("SELECT producto_id, cantidad FROM combo_items WHERE combo_producto_id = ? AND bss_id = ?");
    $stmtUnidades = $conexion->prepare("SELECT cantidad_unidades FROM productos WHERE id = ? LIMIT 1");
    $stmtSumar = $conexion->prepare("UPDATE stock SET stock = stock + ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
    $stmtIdStock = $conexion->prepare("SELECT id_stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
    $stmtSumarMayor = $conexion->prepare("UPDATE stock SET stock = stock + ? WHERE id = ? AND id_sucursal = ? AND bss_id = ?");
    if (!$stmtSumar || !$stmtIdStock || !$stmtSumarMayor) {
        throw new Exception('No se pudo preparar el stock');
    }

    foreach ($lineas as $linea) {
        $productoId = (int) $linea['product_id'];
        $cantidad = (float) $linea['quantity'];
        $sucursalId = (int) $linea['id_sucursal'];
        if ($cantidad <= 0) {
            continue;
        }

        $esCombo = false;
        $stmtCombo->bind_param('ii', $productoId, $bssId);
        $stmtCombo->execute();
        $resCombo = $stmtCombo->get_result();
        while ($hijo = $resCombo->fetch_assoc()) {
            $esCombo = true;
            sumarStockCredito($stmtSumar, (int) $hijo['producto_id'], $cantidad * (float) $hijo['cantidad'], $sucursalId, $bssId);
        }
        if ($esCombo) {
            continue;
        }

        if ($esMayor) {
            // shortcut: usa cantidad_unidades actual; si cambió el tamaño de la paca, el stock devuelto no coincide con el histórico.
            $stmtUnidades->bind_param('i', $productoId);
            $stmtUnidades->execute();
            $filaU = $stmtUnidades->get_result()->fetch_assoc();
            $unidades = $filaU ? (float) $filaU['cantidad_unidades'] : 0.0;
            if ($unidades <= 0) {
                continue;
            }

            // La venta al mayor descuenta del stock detallado enlazado por id_stock
            $stmtIdStock->bind_param('iii', $productoId, $sucursalId, $bssId);
            $stmtIdStock->execute();
            $filaS = $stmtIdStock->get_result()->fetch_assoc();
            $idStock = $filaS ? (int) $filaS['id_stock'] : 0;
            if ($idStock > 0) {
                $cantidadMayor = $cantidad * $unidades;
                $stmtSumarMayor->bind_param('diii', $cantidadMayor, $idStock, $sucursalId, $bssId);
                if (!$stmtSumarMayor->execute()) {
                    throw new Exception('No se pudo devolver el stock al mayor del producto ' . $productoId);
                }
            }
            continue;
        }

        sumarStockCredito($stmtSumar, $productoId, $cantidad, $sucursalId, $bssId);
    }

    $stmtCombo->close();
    $stmtUnidades->close();
    $stmtSumar->close();
    $stmtIdStock->close();
    $stmtSumarMayor->close();
}

function sumarStockCredito($stmt, $productoId, $cantidad, $sucursalId, $bssId)
{
    $stmt->bind_param('diii', $cantidad, $productoId, $sucursalId, $bssId);
    if (!$stmt->execute()) {
        throw new Exception('No se pudo devolver el stock del producto ' . $productoId);
    }
}
