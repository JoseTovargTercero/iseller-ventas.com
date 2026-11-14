<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_factura_compra.php');
require_once('_canal.php');
require_once('_productos_precio_compra.php');

header('Content-Type: application/json; charset=utf-8');

function fcResponder($ok, $mensaje, array $extra = [])
{
    echo json_encode(array_merge(['success' => (bool) $ok, 'message' => $mensaje], $extra));
    exit;
}

function fcConfigActiva($conexion, $bssId)
{
    $stmt = $conexion->prepare("SELECT factura FROM configuracion WHERE bss_id = ? ORDER BY id DESC LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $bssId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $fila && (int) $fila['factura'] === 1;
}

function fcStmt($conexion, $sql)
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error de base de datos.');
    }
    return $stmt;
}

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    fcResponder(false, 'No autorizado.');
}

$bssId = (int) $_SESSION['bss_id'];
if (!fcConfigActiva($conexion, $bssId)) {
    fcResponder(false, 'El registro de compras con factura no está activo.');
}

$contenido = file_get_contents('php://input');
$input = json_decode($contenido, true);
if (!is_array($input)) {
    $input = $_POST;
}

$accion = $input['accion'] ?? ($_GET['accion'] ?? '');
fc_asegurar_esquema($conexion);
$usuario = $_SESSION['nombre'] ?? 'desconocido';
$usuarioId = (int) ($_SESSION['id'] ?? 0);

// ── Detalle de una factura (solo lectura) ────────────────────────────────────
if ($accion === 'detalle') {
    $facturaId = (int) ($_GET['id'] ?? 0);
    if ($facturaId <= 0) {
        fcResponder(false, 'Factura inválida.');
    }

    $stmt = $conexion->prepare("SELECT id, id_sucursal, proveedor, numero_factura, fecha_factura, fecha_vencimiento, total, estado FROM compras_facturas WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        fcResponder(false, 'Error de base de datos.');
    }
    $stmt->bind_param("ii", $facturaId, $bssId);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$factura || ($nivel === 2 && (int) $factura['id_sucursal'] !== (int) $_SESSION['sucursal'])) {
        fcResponder(false, 'Factura no encontrada.');
    }

    $lineas = [];
    $stmt = $conexion->prepare("SELECT producto, precio, cantidad, CporPrecio, Porcentaje FROM compras WHERE factura_id = ? AND bss_id = ? ORDER BY id ASC");
    $stmt->bind_param("ii", $facturaId, $bssId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $lineas[] = [
            'producto' => $fila['producto'],
            'cantidad' => round((float) $fila['cantidad'], 3),
            'unidades_bulto' => (int) $fila['CporPrecio'],
            'porcentaje' => (float) $fila['Porcentaje'],
            'subtotal' => fc_linea_total($fila['precio'], $fila['CporPrecio'], $fila['cantidad']),
        ];
    }
    $stmt->close();

    $pagos = [];
    $abonado = 0.0;
    $stmt = $conexion->prepare("SELECT monto, fecha, usuario FROM compras_factura_pagos WHERE factura_id = ? AND bss_id = ? ORDER BY id ASC");
    $stmt->bind_param("ii", $facturaId, $bssId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $monto = (float) $fila['monto'];
        $abonado += $monto;
        $pagos[] = ['monto' => $monto, 'fecha' => $fila['fecha'], 'usuario' => $fila['usuario']];
    }
    $stmt->close();

    fcResponder(true, 'OK', [
        'factura' => $factura,
        'lineas' => $lineas,
        'pagos' => $pagos,
        'abonado' => round($abonado, 2),
        'saldo' => round((float) $factura['total'] - $abonado, 2),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fcResponder(false, 'Método no permitido.');
}

// ── Registrar una factura completa ───────────────────────────────────────────
if ($accion === 'guardar') {
    $proveedor = trim((string) ($input['proveedor'] ?? ''));
    $numeroFactura = trim((string) ($input['numero_factura'] ?? ''));
    $fechaFactura = trim((string) ($input['fecha_factura'] ?? ''));
    $fechaVencimiento = trim((string) ($input['fecha_vencimiento'] ?? ''));
    $abonoInicial = round((float) ($input['abono_inicial'] ?? 0), 2);
    $lineas = $input['lineas'] ?? null;

    if ($proveedor === '' || strlen($proveedor) > 255) {
        fcResponder(false, 'Indique un proveedor válido.');
    }
    if ($numeroFactura === '' || strlen($numeroFactura) > 100) {
        fcResponder(false, 'Indique un número de factura válido.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFactura) || !checkdate((int) substr($fechaFactura, 5, 2), (int) substr($fechaFactura, 8, 2), (int) substr($fechaFactura, 0, 4))) {
        fcResponder(false, 'Indique una fecha de factura válida.');
    }
    if ($fechaVencimiento !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimiento) || !checkdate((int) substr($fechaVencimiento, 5, 2), (int) substr($fechaVencimiento, 8, 2), (int) substr($fechaVencimiento, 0, 4)))) {
        fcResponder(false, 'Indique una fecha de vencimiento válida.');
    }
    if ($fechaVencimiento !== '' && $fechaVencimiento < $fechaFactura) {
        fcResponder(false, 'El vencimiento no puede ser anterior a la fecha de la factura.');
    }
    if (!is_array($lineas) || count($lineas) === 0 || count($lineas) > 200) {
        fcResponder(false, 'Agregue al menos un producto a la factura.');
    }
    if ($abonoInicial < 0) {
        fcResponder(false, 'El abono inicial no puede ser negativo.');
    }

    $sucursal = $nivel === 2 ? (int) $_SESSION['sucursal'] : (int) ($input['sucursal'] ?? 0);
    if ($sucursal <= 0) {
        fcResponder(false, 'Seleccione una sucursal.');
    }

    $stmt = $conexion->prepare("SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        fcResponder(false, 'Error de base de datos.');
    }
    $stmt->bind_param("ii", $sucursal, $bssId);
    $stmt->execute();
    $sucursalValida = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$sucursalValida) {
        fcResponder(false, 'La sucursal indicada no pertenece al negocio.');
    }

    $fechaCompra = date('d-m-Y h:i a');
    $semana = date('Y-W');
    $mes = date('Y-m');
    $dia = date('Y-m-d');
    $hoy = date('Y-m-d');

    producto_asegurar_historial_precio($conexion);

    $conexion->begin_transaction();

    try {
        $total = 0.0;
        $productos = [];
        $conciliaciones = is_array($input['conciliaciones'] ?? null) ? $input['conciliaciones'] : [];
        $conciliacionesPendientes = [];

        foreach ($lineas as $linea) {
            $productoId = (int) ($linea['producto_id'] ?? 0);
            $cantidad = round((float) ($linea['cantidad'] ?? 0), 3); // admite fracciones (kg, litros, etc.)
            $precio = round((float) ($linea['precio'] ?? 0), 2);
            $unidadesBulto = (int) ($linea['unidades_bulto'] ?? 0);
            $porcentaje = round((float) ($linea['porcentaje'] ?? 0), 2);

            if ($productoId <= 0 || $cantidad <= 0 || $precio <= 0 || $unidadesBulto <= 0 || $porcentaje < 0) {
                throw new Exception('Hay una línea con datos inválidos.');
            }

            $stmt = fcStmt($conexion, "SELECT nombre FROM productos WHERE id = ? AND bss_id = ? LIMIT 1");
            $stmt->bind_param("ii", $productoId, $bssId);
            $stmt->execute();
            $producto = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$producto) {
                throw new Exception('Uno de los productos no pertenece al negocio.');
            }

            $stmt = fcStmt($conexion, "SELECT id FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
            $stmt->bind_param("iii", $productoId, $sucursal, $bssId);
            $stmt->execute();
            $stock = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$stock) {
                throw new Exception('El producto "' . $producto['nombre'] . '" no está asignado a la sucursal.');
            }

            if (canal_producto_estricto($conexion, $productoId, $bssId)) {
                $contexto = canal_contexto_estricto($conexion, $productoId, $sucursal, $bssId);
                $conc = $conciliaciones[$productoId] ?? $conciliaciones[(string) $productoId] ?? null;
                $stockVisto = $conc ? (float) ($conc['stock_visto'] ?? -1) : -1;
                $confirmado = $conc && !empty($conc['confirmado']);
                if (!$confirmado || abs($stockVisto - (float) $contexto['stock_actual']) > 0.0001) {
                    $conciliacionesPendientes[$productoId] = $contexto;
                }
            }

            $subtotal = fc_linea_total($precio, $unidadesBulto, $cantidad);
            if ($subtotal <= 0) {
                throw new Exception('El subtotal de una línea debe ser mayor que cero.');
            }

            $productos[] = [
                'id' => $productoId,
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'precio' => $precio,
                'unidades_bulto' => $unidadesBulto,
                'porcentaje' => $porcentaje,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        if ($conciliacionesPendientes) {
            $conexion->rollback();
            fcResponder(false, 'Confirme la cantidad anterior de los productos con seguimiento estricto.', [
                'requiere_conciliacion' => true,
                'productos' => array_values($conciliacionesPendientes),
            ]);
        }

        $total = round($total, 2);
        if ($abonoInicial > $total + 0.001) {
            throw new Exception('El abono inicial no puede superar el total de la factura.');
        }

        $estado = fc_estado($total, $abonoInicial);
        $statusLinea = $estado === 'pagada' ? 1 : 2;

        // El crédito solo tiene vencimiento mientras quede saldo. Si no se indicó
        // fecha, se asume un crédito a 30 días desde la fecha de la factura.
        if ($estado === 'pendiente') {
            if ($fechaVencimiento === '') {
                $fechaVencimiento = date('Y-m-d', strtotime($fechaFactura . ' +30 days'));
            }
        } else {
            $fechaVencimiento = null;
        }

        $stmt = fcStmt($conexion, "INSERT INTO compras_facturas (bss_id, id_sucursal, proveedor, numero_factura, fecha_factura, fecha_vencimiento, total, estado, usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssdss", $bssId, $sucursal, $proveedor, $numeroFactura, $fechaFactura, $fechaVencimiento, $total, $estado, $usuario);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo crear la factura.');
        }
        $facturaId = (int) $conexion->insert_id;
        $stmt->close();
        if ($facturaId <= 0) {
            throw new Exception('No se pudo crear la factura.');
        }

        $stmtLinea = fcStmt($conexion, "INSERT INTO compras (proveedor, factura, fechaFactura, status, cod, producto, precio, cantidad, CporPrecio, Porcentaje, fecha, user, semana, dia, mes, id_sucursal, bss_id, factura_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtProducto = fcStmt($conexion, "UPDATE productos SET cantidad_unidades = ?, proveedor = ? WHERE id = ? AND bss_id = ?");

        foreach ($productos as $producto) {
            $productoId = $producto['id'];
            $nombreProducto = $producto['nombre'];
            $cantidad = $producto['cantidad'];
            $precio = $producto['precio'];
            $unidadesBulto = $producto['unidades_bulto'];
            $porcentaje = $producto['porcentaje'];
            $cod = (string) $productoId;

            $cantidadTexto = canal_numero($cantidad);
            $stmtLinea->bind_param(
                "sssissdsidsssssiii",
                $proveedor,
                $numeroFactura,
                $fechaFactura,
                $statusLinea,
                $cod,
                $nombreProducto,
                $precio,
                $cantidadTexto,
                $unidadesBulto,
                $porcentaje,
                $fechaCompra,
                $usuario,
                $semana,
                $dia,
                $mes,
                $sucursal,
                $bssId,
                $facturaId
            );
            if (!$stmtLinea->execute()) {
                throw new Exception('No se pudo registrar una línea de la factura.');
            }

            $stmtProducto->bind_param("isii", $unidadesBulto, $proveedor, $productoId, $bssId);
            if (!$stmtProducto->execute()) {
                throw new Exception('No se pudo actualizar el producto "' . $nombreProducto . '".');
            }

            producto_actualizar_precio_compra(
                $conexion,
                $bssId,
                $productoId,
                $precio,
                'factura',
                $usuarioId,
                $usuario,
                $sucursal
            );

            canal_sumar_compra($conexion, $productoId, $sucursal, $bssId, $cantidad, $usuarioId);
            $stmtPct = $conexion->prepare("UPDATE stock SET porcentaje = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
            if ($stmtPct) {
                $stmtPct->bind_param('diii', $porcentaje, $productoId, $sucursal, $bssId);
                $stmtPct->execute();
                $stmtPct->close();
            }
        }

        $stmtLinea->close();
        $stmtProducto->close();

        if ($abonoInicial > 0) {
            $stmt = fcStmt($conexion, "INSERT INTO compras_factura_pagos (factura_id, bss_id, monto, fecha, usuario) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iidss", $facturaId, $bssId, $abonoInicial, $hoy, $usuario);
            if (!$stmt->execute()) {
                throw new Exception('No se pudo registrar el abono inicial.');
            }
            $stmt->close();
        }

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        fcResponder(false, $e->getMessage());
    }

    fcResponder(true, 'Factura registrada correctamente.', [
        'factura_id' => $facturaId,
        'total' => $total,
        'abonado' => $abonoInicial,
        'saldo' => round($total - $abonoInicial, 2),
        'estado' => $estado,
        'fecha_vencimiento' => $fechaVencimiento,
    ]);
}

// ── Registrar un abono ───────────────────────────────────────────────────────
if ($accion === 'abonar') {
    $facturaId = (int) ($input['factura_id'] ?? 0);
    $monto = round((float) ($input['monto'] ?? 0), 2);

    if ($facturaId <= 0 || $monto <= 0) {
        fcResponder(false, 'Indique un monto de abono válido.');
    }

    $conexion->begin_transaction();

    try {
        $stmt = fcStmt($conexion, "SELECT id_sucursal, total FROM compras_facturas WHERE id = ? AND bss_id = ? FOR UPDATE");
        $stmt->bind_param("ii", $facturaId, $bssId);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$factura || ($nivel === 2 && (int) $factura['id_sucursal'] !== (int) $_SESSION['sucursal'])) {
            throw new Exception('Factura no encontrada.');
        }

        $stmt = fcStmt($conexion, "SELECT COALESCE(SUM(monto), 0) AS abonado FROM compras_factura_pagos WHERE factura_id = ? AND bss_id = ?");
        $stmt->bind_param("ii", $facturaId, $bssId);
        $stmt->execute();
        $abonado = (float) ($stmt->get_result()->fetch_assoc()['abonado'] ?? 0);
        $stmt->close();

        $total = (float) $factura['total'];
        $saldo = round($total - $abonado, 2);
        if ($monto > $saldo + 0.001) {
            throw new Exception('El abono supera el saldo pendiente de la factura.');
        }

        $hoy = date('Y-m-d');
        $stmt = fcStmt($conexion, "INSERT INTO compras_factura_pagos (factura_id, bss_id, monto, fecha, usuario) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $facturaId, $bssId, $monto, $hoy, $usuario);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo registrar el abono.');
        }
        $stmt->close();

        $nuevoAbonado = round($abonado + $monto, 2);
        $estado = fc_estado($total, $nuevoAbonado);
        $statusLinea = $estado === 'pagada' ? 1 : 2;

        $stmt = fcStmt($conexion, "UPDATE compras_facturas SET estado = ? WHERE id = ? AND bss_id = ?");
        $stmt->bind_param("sii", $estado, $facturaId, $bssId);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo actualizar el estado de la factura.');
        }
        $stmt->close();

        $stmt = fcStmt($conexion, "UPDATE compras SET status = ? WHERE factura_id = ? AND bss_id = ?");
        $stmt->bind_param("iii", $statusLinea, $facturaId, $bssId);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo actualizar el estado de las líneas.');
        }
        $stmt->close();

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        fcResponder(false, $e->getMessage());
    }

    fcResponder(true, 'Abono registrado correctamente.', [
        'total' => $total,
        'abonado' => $nuevoAbonado,
        'saldo' => round($total - $nuevoAbonado, 2),
        'estado' => $estado,
    ]);
}

// ── Actualizar la fecha de vencimiento del crédito ───────────────────────────
if ($accion === 'vencimiento') {
    $facturaId = (int) ($input['factura_id'] ?? 0);
    $fechaVencimiento = trim((string) ($input['fecha_vencimiento'] ?? ''));

    if ($facturaId <= 0) {
        fcResponder(false, 'Factura inválida.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimiento) || !checkdate((int) substr($fechaVencimiento, 5, 2), (int) substr($fechaVencimiento, 8, 2), (int) substr($fechaVencimiento, 0, 4))) {
        fcResponder(false, 'Indique una fecha de vencimiento válida.');
    }

    try {
        $stmt = fcStmt($conexion, "SELECT id_sucursal, total, fecha_factura FROM compras_facturas WHERE id = ? AND bss_id = ? LIMIT 1");
        $stmt->bind_param("ii", $facturaId, $bssId);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$factura || ($nivel === 2 && (int) $factura['id_sucursal'] !== (int) $_SESSION['sucursal'])) {
            throw new Exception('Factura no encontrada.');
        }
        if ($fechaVencimiento < $factura['fecha_factura']) {
            throw new Exception('El vencimiento no puede ser anterior a la fecha de la factura.');
        }

        $stmt = fcStmt($conexion, "SELECT COALESCE(SUM(monto), 0) AS abonado FROM compras_factura_pagos WHERE factura_id = ? AND bss_id = ?");
        $stmt->bind_param("ii", $facturaId, $bssId);
        $stmt->execute();
        $abonado = (float) ($stmt->get_result()->fetch_assoc()['abonado'] ?? 0);
        $stmt->close();

        if (round((float) $factura['total'] - $abonado, 2) <= 0.005) {
            throw new Exception('La factura ya está pagada; no requiere vencimiento.');
        }

        $stmt = fcStmt($conexion, "UPDATE compras_facturas SET fecha_vencimiento = ? WHERE id = ? AND bss_id = ?");
        $stmt->bind_param("sii", $fechaVencimiento, $facturaId, $bssId);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo actualizar el vencimiento.');
        }
        $stmt->close();
    } catch (Throwable $e) {
        fcResponder(false, $e->getMessage());
    }

    fcResponder(true, 'Vencimiento actualizado correctamente.', [
        'fecha_vencimiento' => $fechaVencimiento,
    ]);
}

fcResponder(false, 'Acción inválida.');
