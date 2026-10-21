<?php
/**
 * API — Sincronización de ventas del POS nativo  →  POST /api/pos_ventas.php
 *
 * Recibe un lote de ventas hechas en la caja (posiblemente sin internet) y las
 * inserta de forma idempotente: `orden.id_pedido` es único, así que reintentar
 * el mismo lote nunca duplica.
 *
 * Autenticación: X-Api-Key + Bearer. La sucursal y el usuario salen del token,
 * no del cuerpo (un cliente no puede escribir ventas en otra caja).
 */

require_once __DIR__ . '/_pos_comun.php';

exigirMetodo('POST');
exigirApiKey();
limitar('ventas', 60, 60);

$sesion = exigirSesion($conexion);
$bssId = $sesion['bss_id'];
$sucursalId = (int)($sesion['sucursal_id'] ?? 0);
$usuarioId = $sesion['id_user'];

if ($sucursalId <= 0) {
    salir(409, ['ok' => false, 'error' => 'La sesión no tiene sucursal']);
}

$entrada = entrada();
$ventas = $entrada['ventas'] ?? null;
if (!is_array($ventas) || count($ventas) === 0) {
    salir(400, ['ok' => false, 'error' => 'No hay ventas para procesar']);
}
if (count($ventas) > 100) {
    salir(400, ['ok' => false, 'error' => 'Máximo 100 ventas por lote']);
}

$registroClientes = 0;
$stmt = $conexion->prepare("SELECT registro_clientes FROM configuracion WHERE bss_id = ? ORDER BY id DESC LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($fila) {
        $registroClientes = (int)$fila['registro_clientes'];
    }
}

$resultados = [];

foreach ($ventas as $venta) {
    $resultados[] = procesarVenta($conexion, $venta, $bssId, $sucursalId, $usuarioId, $registroClientes);
}

salir(200, [
    'ok' => true,
    'servidor_en' => servidorEn(),
    'resultados' => $resultados,
]);

// ───────────────────────────────────────────────────────────────────────────────

function procesarVenta($conexion, $venta, int $bssId, int $sucursalId, int $usuarioId, int $registroClientes): array
{
    $idPedido = trim((string)($venta['id_pedido'] ?? ''));

    $error = validarVenta($venta, $idPedido);
    if ($error !== null) {
        return ['id_pedido' => $idPedido, 'ok' => false, 'error' => $error];
    }

    // Ya sincronizada antes (reintento): se responde OK sin volver a insertar.
    $existente = buscarOrden($conexion, $idPedido, $bssId);
    if ($existente !== null) {
        return ['id_pedido' => $idPedido, 'ok' => true, 'orden_id' => $existente, 'duplicada' => true];
    }

    $metodoPago = (string)$venta['metodo_pago'];
    $despacho = (int)$venta['despacho'];
    $productos = $venta['productos'];
    $pagosFraccionados = $venta['pagos_fraccionados'] ?? null;
    $cliente = $venta['cliente'] ?? [];
    $cedula = trim((string)($cliente['cedula'] ?? ''));
    $nombreCliente = trim((string)($cliente['nombre'] ?? ''));
    $telefonoCliente = trim((string)($cliente['telefono'] ?? ''));

    // La fecha de la venta es la de la caja, no la de sincronización.
    $fechaLocal = (string)($venta['fecha_local'] ?? '');
    $timestamp = strtotime($fechaLocal) ?: time();
    $fechaVenta = date('Y-m-d', $timestamp);
    $mes = date('Y-m', $timestamp);
    $ano = date('Y', $timestamp);
    $semana = date('Y-W', $timestamp);
    $dia = date('N', $timestamp);
    $created = date('Y-m-d H:i:s', $timestamp);

    $pagoTipo = (int)str_replace('option', '', $metodoPago);
    $tipoVenta = ($despacho === 2) ? 2 : 1;
    $compraTipo = 1;

    $esMayor = false;
    foreach ($productos as $p) {
        if ((string)($p['mayor'] ?? '0') === '1') {
            $esMayor = true;
            break;
        }
    }
    if ($esMayor) {
        $compraTipo = 4;
        if ($despacho === 2) {
            $pagoTipo = 4;
        } else {
            $tipoVenta = 4;
        }
    }

    $totalUsd = (float)$venta['total_usd'];
    $totalBs = (float)$venta['total_bs'];
    $totalCop = formatPeso((float)$venta['total_cop']);

    $advertencias = [];
    $calculado = calcularTotales($productos);
    if (abs($calculado['usd'] - $totalUsd) > 0.01 || abs($calculado['bs'] - $totalBs) > 0.5) {
        $advertencias[] = 'Los totales enviados no coinciden con la suma de las líneas.';
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare(
            "INSERT INTO orden (
                status, usuario, total_price, created, modified, fecha,
                semana, ano, total_price_bs, total_price_cop, tipoPago,
                dia, id_sucursal, bss_id, id_pedido, cliente
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception('No se pudo preparar la orden: ' . $conexion->error);
        }
        $stmt->bind_param(
            'iissssssddsiiiss',
            $tipoVenta,
            $usuarioId,
            $totalUsd,
            $created,
            $fechaVenta,
            $mes,
            $semana,
            $ano,
            $totalBs,
            $totalCop,
            $pagoTipo,
            $dia,
            $sucursalId,
            $bssId,
            $idPedido,
            $cedula
        );

        if (!$stmt->execute()) {
            if ($conexion->errno === 1062) {
                // Otra petición la insertó en paralelo: se trata como duplicada.
                $stmt->close();
                $conexion->rollback();
                $ordenId = buscarOrden($conexion, $idPedido, $bssId);
                return ['id_pedido' => $idPedido, 'ok' => true, 'orden_id' => $ordenId, 'duplicada' => true];
            }
            $mensaje = $stmt->error;
            $stmt->close();
            throw new Exception('Error al insertar la orden: ' . $mensaje);
        }
        $ordenId = (int)$stmt->insert_id;
        $stmt->close();

        if ($registroClientes === 1 || $despacho === 2) {
            guardarCliente($conexion, $bssId, $sucursalId, $cedula, $nombreCliente, $telefonoCliente);
        }

        $faltantes = guardarArticulosYStock($conexion, $ordenId, $productos, $bssId, $sucursalId);

        // Pago fraccionado (tipoPago 8): mismo desglose que guarda el POS web en `fracciones`.
        if ($pagoTipo === 8) {
            guardarFracciones($conexion, $ordenId, $pagosFraccionados, $bssId, $sucursalId);
        }

        if ($despacho === 2) {
            $stmtC = $conexion->prepare(
                "INSERT INTO creditos (order_id, total_price, total_price_bs, total_price_cop, negocio, tipoCompra, bss_id, sucursal_id, cedula)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmtC) {
                $stmtC->bind_param('idssssiii', $ordenId, $totalUsd, $totalBs, $totalCop, $nombreCliente, $compraTipo, $bssId, $sucursalId, $cedula);
                $stmtC->execute();
                $stmtC->close();
            }
        }

        $conexion->commit();

        $respuesta = ['id_pedido' => $idPedido, 'ok' => true, 'orden_id' => $ordenId, 'duplicada' => false];
        if (count($faltantes) > 0) {
            $respuesta['faltantes'] = $faltantes;
        }
        if (count($advertencias) > 0) {
            $respuesta['advertencias'] = $advertencias;
        }
        return $respuesta;
    } catch (Exception $e) {
        $conexion->rollback();
        return ['id_pedido' => $idPedido, 'ok' => false, 'error' => $e->getMessage()];
    }
}

function validarVenta($venta, string $idPedido): ?string
{
    if ($idPedido === '') {
        return 'Falta id_pedido.';
    }
    if (empty($venta['metodo_pago']) || !is_string($venta['metodo_pago'])) {
        return 'Falta metodo_pago.';
    }
    if (empty($venta['despacho'])) {
        return 'Falta despacho.';
    }
    if (!isset($venta['productos']) || !is_array($venta['productos']) || count($venta['productos']) === 0) {
        return 'La venta no tiene productos.';
    }
    foreach ($venta['productos'] as $p) {
        if (empty($p['id']) || empty($p['name'])) {
            return 'Hay un producto sin id o sin nombre.';
        }
        if (!isset($p['qty']) || (float)$p['qty'] <= 0) {
            return 'Hay un producto con cantidad inválida.';
        }
    }
    if ((int)$venta['despacho'] === 2) {
        $cliente = $venta['cliente'] ?? [];
        if (trim((string)($cliente['cedula'] ?? '')) === '' || trim((string)($cliente['nombre'] ?? '')) === '') {
            return 'La venta a crédito exige cédula y nombre del cliente.';
        }
    }

    if ((string)$venta['metodo_pago'] === 'option8') {
        $pf = $venta['pagos_fraccionados'] ?? null;
        if (!is_array($pf)) {
            return 'Falta el desglose del pago fraccionado.';
        }
        $claves = ['punto', 'bioPago', 'pagoMovil', 'transferencia', 'efectivo', 'dolares', 'pesos'];
        $positivo = false;
        foreach ($claves as $clave) {
            $valor = $pf[$clave] ?? 0;
            if (!is_numeric($valor) || (float)$valor < 0) {
                return "Monto fraccionado inválido en '$clave'.";
            }
            if ((float)$valor > 0) {
                $positivo = true;
            }
        }
        if (!$positivo) {
            return 'El pago fraccionado no tiene montos.';
        }
    }

    return null;
}

function buscarOrden($conexion, string $idPedido, int $bssId): ?int
{
    $stmt = $conexion->prepare("SELECT id FROM orden WHERE id_pedido = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('si', $idPedido, $bssId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ? (int)$fila['id'] : null;
}

function calcularTotales(array $productos): array
{
    $usd = 0.0;
    $bs = 0.0;
    foreach ($productos as $p) {
        $qty = (float)$p['qty'];
        $usd += (float)$p['price'] * $qty;
        $bs += (float)$p['priceBolivar'] * $qty;
    }
    return ['usd' => $usd, 'bs' => $bs];
}

function guardarCliente($conexion, int $bssId, int $sucursalId, string $cedula, string $nombre, string $telefono): void
{
    if ($cedula === '') {
        return;
    }
    $stmt = $conexion->prepare("SELECT id FROM clientes WHERE cedula = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('si', $cedula, $bssId);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($existe) {
        return;
    }

    $stmt = $conexion->prepare(
        "INSERT INTO clientes (nombre, cedula, telefono, bss_id, sucursal_id) VALUES (?, ?, ?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param('ssssi', $nombre, $cedula, $telefono, $bssId, $sucursalId);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Inserta las líneas y descuenta stock. Devuelve los productos que quedaron
 * por debajo de cero (el stock se guarda en 0, como hace el POS web).
 */
function guardarArticulosYStock($conexion, int $ordenId, array $productos, int $bssId, int $sucursalId): array
{
        $insertStmt = $conexion->prepare(
            "INSERT INTO orden_articulos (
                order_id, product_id, quantity, precio, bolivar, peso,
                precio_venta_dolar, precio_venta_bs, precio_venta_cop, id_sucursal, bss_id
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$insertStmt) {
            throw new Exception('No se pudo preparar el artículo: ' . $conexion->error);
        }

    $stmtStock = $conexion->prepare(
        "SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1"
    );
    $updateStmt = $conexion->prepare(
        "UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
    );
    $stmtComboItems = $conexion->prepare(
        "SELECT producto_id, cantidad FROM combo_items WHERE combo_producto_id = ? AND bss_id = ?"
    );
    // Venta al mayor: la paca descuenta del producto base enlazado por stock.id_stock.
    $stmtMayorStock = $conexion->prepare(
        "SELECT p.cantidad_unidades, sp.id AS base_id, sp.stock AS stock_base
           FROM stock s
     INNER JOIN productos p ON p.id = s.id_producto
      LEFT JOIN stock sp ON sp.id = s.id_stock AND sp.id_sucursal = s.id_sucursal AND sp.bss_id = s.bss_id
          WHERE s.id_producto = ? AND s.id_sucursal = ? AND s.bss_id = ? AND p.mayor = '1'
          LIMIT 1"
    );
    $updateStmtMayor = $conexion->prepare(
        "UPDATE stock SET stock = ? WHERE id = ? AND id_sucursal = ? AND bss_id = ?"
    );

    $faltantes = [];

    foreach ($productos as $item) {
        $productoId = (int)$item['id'];
        $qty = (float)$item['qty'];
        $priceC = (float)$item['price_C'];
        $priceCBs = (float)$item['price_C_Bs'];
        $priceCCop = (float)$item['price_C_Cop'];
        $precio = (float)$item['price'];
        $precioBolivar = (float)$item['priceBolivar'];
        $precioPeso = (float)$item['pricePeso'];

        $insertStmt->bind_param(
            'iiddddddddi',
            $ordenId,
            $productoId,
            $qty,
            $priceC,
            $priceCBs,
            $priceCCop,
            $precio,
            $precioBolivar,
            $precioPeso,
            $sucursalId,
            $bssId
        );
        if (!$insertStmt->execute()) {
            throw new Exception('Error al insertar el artículo: ' . $insertStmt->error);
        }

        // ¿Es combo? Se descuentan los hijos y no el combo.
        $esCombo = false;
        if ($stmtComboItems) {
            $stmtComboItems->bind_param('ii', $productoId, $bssId);
            $stmtComboItems->execute();
            $resCombo = $stmtComboItems->get_result();
            if ($resCombo && $resCombo->num_rows > 0) {
                $esCombo = true;
                while ($hijo = $resCombo->fetch_assoc()) {
                    $idHijo = (int)$hijo['producto_id'];
                    $cantHijo = (float)$hijo['cantidad'] * $qty;
                    $disponible = stockActual($stmtStock, $idHijo, $sucursalId, $bssId);
                    if ($disponible - $cantHijo < 0) {
                        $faltantes[] = ['id' => $idHijo, 'faltante' => abs($disponible - $cantHijo)];
                    }
                    actualizarStock($updateStmt, $idHijo, $sucursalId, $bssId, max(0, $disponible - $cantHijo));
                }
            }
        }
        if ($esCombo) {
            continue;
        }

        // Venta al mayor: se descuenta la cantidad de paquetes por las unidades que
        // trae la paca/caja/saco, y del producto base (id_stock), no de la fila de la paca.
        if ((string)($item['mayor'] ?? '0') === '1') {
            $mayor = null;
            if ($stmtMayorStock) {
                $stmtMayorStock->bind_param('iii', $productoId, $sucursalId, $bssId);
                $stmtMayorStock->execute();
                $mayor = $stmtMayorStock->get_result()->fetch_assoc();
            }
            $idStock = $mayor ? (int)($mayor['base_id'] ?? 0) : 0;
            $unidades = $mayor ? (float)$mayor['cantidad_unidades'] : 0.0;
            if ($idStock > 0 && $unidades > 0) {
                $descontar = $qty * $unidades;
                $disponible = (float)$mayor['stock_base'];
                if ($disponible - $descontar < 0) {
                    $faltantes[] = ['id' => $productoId, 'faltante' => abs($disponible - $descontar)];
                }
                $updateStmtMayor->bind_param('diii', max(0, $disponible - $descontar), $idStock, $sucursalId, $bssId);
                if (!$updateStmtMayor->execute()) {
                    throw new Exception('Error al actualizar el stock al mayor: ' . $updateStmtMayor->error);
                }
            }
            continue;
        }

        $disponible = stockActual($stmtStock, $productoId, $sucursalId, $bssId);
        $descontar = $qty;

        if ($disponible - $descontar < 0) {
            $faltantes[] = ['id' => $productoId, 'faltante' => abs($disponible - $descontar)];
        }
        actualizarStock($updateStmt, $productoId, $sucursalId, $bssId, max(0, $disponible - $descontar));
    }

    $insertStmt->close();
    $stmtStock->close();
    $updateStmt->close();
    if ($stmtComboItems) {
        $stmtComboItems->close();
    }
    if ($stmtMayorStock) {
        $stmtMayorStock->close();
    }
    if ($updateStmtMayor) {
        $updateStmtMayor->close();
    }

    return $faltantes;
}

/**
 * Guarda el desglose del pago fraccionado (tipoPago 8), igual que accion_carta.php.
 */
function guardarFracciones($conexion, int $ordenId, $pagos, int $bssId, int $sucursalId): void
{
    $pf = is_array($pagos) ? $pagos : [];

    $stmt = $conexion->prepare(
        "INSERT INTO fracciones (id_order, punto, pagoMovil, transferencia, bioPago, efectivo, pesos, dolares, id_sucursal, bss_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        throw new Exception('No se pudo preparar el desglose fraccionado: ' . $conexion->error);
    }

    $punto = (float)($pf['punto'] ?? 0);
    $pagoMovil = (float)($pf['pagoMovil'] ?? 0);
    $transferencia = (float)($pf['transferencia'] ?? 0);
    $bioPago = (float)($pf['bioPago'] ?? 0);
    $efectivo = (float)($pf['efectivo'] ?? 0);
    $pesos = (float)($pf['pesos'] ?? 0);
    $dolares = (float)($pf['dolares'] ?? 0);

    $stmt->bind_param(
        'idddddddii',
        $ordenId,
        $punto,
        $pagoMovil,
        $transferencia,
        $bioPago,
        $efectivo,
        $pesos,
        $dolares,
        $sucursalId,
        $bssId
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Error al guardar el desglose fraccionado: ' . $error);
    }
    $stmt->close();
}

function stockActual($stmtStock, int $productoId, int $sucursalId, int $bssId): float{
    $stmtStock->bind_param('iii', $productoId, $sucursalId, $bssId);
    $stmtStock->execute();
    $fila = $stmtStock->get_result()->fetch_assoc();
    return $fila ? (float)$fila['stock'] : 0.0;
}

function actualizarStock($updateStmt, int $productoId, int $sucursalId, int $bssId, float $nuevo): void
{
    $updateStmt->bind_param('diii', $nuevo, $productoId, $sucursalId, $bssId);
    if (!$updateStmt->execute()) {
        throw new Exception('Error al actualizar el stock: ' . $updateStmt->error);
    }
}
