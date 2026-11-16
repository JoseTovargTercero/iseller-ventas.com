<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_cuentas_pagar.php');

header('Content-Type: application/json; charset=utf-8');

function cpResponder($ok, $mensaje, array $extra = [])
{
    echo json_encode(array_merge(['success' => (bool) $ok, 'message' => $mensaje], $extra));
    exit;
}

function cpStmt($conexion, $sql)
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error de base de datos.');
    }
    return $stmt;
}

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    cpResponder(false, 'No autorizado.');
}

$bssId = (int) $_SESSION['bss_id'];

if (!cp_asegurar_esquema($conexion)) {
    cpResponder(false, 'No se pudo preparar la tabla de cuentas por pagar.');
}

$contenido = file_get_contents('php://input');
$input = json_decode($contenido, true);
if (!is_array($input)) {
    $input = $_POST;
}

$accion = $input['accion'] ?? ($_GET['accion'] ?? '');
$usuario = $_SESSION['nombre'] ?? 'desconocido';

// ── Detalle de una factura por conceptos (solo lectura) ─────────────────────
if ($accion === 'detalle') {
    $facturaId = (int) ($_GET['id'] ?? 0);
    if ($facturaId <= 0) {
        cpResponder(false, 'Factura inválida.');
    }

    $stmt = $conexion->prepare("SELECT id, id_sucursal, proveedor, numero_factura, fecha_factura, fecha_vencimiento, total, estado FROM cuentas_pagar_facturas WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        cpResponder(false, 'Error de base de datos.');
    }
    $stmt->bind_param("ii", $facturaId, $bssId);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$factura || ($nivel === 2 && (int) $factura['id_sucursal'] !== (int) $_SESSION['sucursal'])) {
        cpResponder(false, 'Factura no encontrada.');
    }

    $conceptos = [];
    $stmt = $conexion->prepare("SELECT concepto, monto FROM cuentas_pagar_conceptos WHERE factura_id = ? AND bss_id = ? ORDER BY id ASC");
    $stmt->bind_param("ii", $facturaId, $bssId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $conceptos[] = [
            'concepto' => $fila['concepto'],
            'monto' => round((float) $fila['monto'], 2),
        ];
    }
    $stmt->close();

    $pagos = [];
    $abonado = 0.0;
    $stmt = $conexion->prepare("SELECT monto, fecha, usuario FROM cuentas_pagar_pagos WHERE factura_id = ? AND bss_id = ? ORDER BY id ASC");
    $stmt->bind_param("ii", $facturaId, $bssId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $monto = (float) $fila['monto'];
        $abonado += $monto;
        $pagos[] = ['monto' => $monto, 'fecha' => $fila['fecha'], 'usuario' => $fila['usuario']];
    }
    $stmt->close();

    cpResponder(true, 'OK', [
        'origen' => 'concepto',
        'factura' => $factura,
        'conceptos' => $conceptos,
        'pagos' => $pagos,
        'abonado' => round($abonado, 2),
        'saldo' => round((float) $factura['total'] - $abonado, 2),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cpResponder(false, 'Método no permitido.');
}

// ── Registrar una factura por conceptos ─────────────────────────────────────
if ($accion === 'guardar') {
    $proveedor = trim((string) ($input['proveedor'] ?? ''));
    $numeroFactura = trim((string) ($input['numero_factura'] ?? ''));
    $fechaFactura = trim((string) ($input['fecha_factura'] ?? ''));
    $fechaVencimiento = trim((string) ($input['fecha_vencimiento'] ?? ''));
    $abonoInicial = round((float) ($input['abono_inicial'] ?? 0), 2);
    $conceptosRaw = $input['conceptos'] ?? null;

    if ($proveedor === '' || strlen($proveedor) > 255) {
        cpResponder(false, 'Indique un proveedor válido.');
    }
    if ($numeroFactura === '' || strlen($numeroFactura) > 100) {
        cpResponder(false, 'Indique un número de factura válido.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFactura) || !checkdate((int) substr($fechaFactura, 5, 2), (int) substr($fechaFactura, 8, 2), (int) substr($fechaFactura, 0, 4))) {
        cpResponder(false, 'Indique una fecha de factura válida.');
    }
    if ($fechaVencimiento !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimiento) || !checkdate((int) substr($fechaVencimiento, 5, 2), (int) substr($fechaVencimiento, 8, 2), (int) substr($fechaVencimiento, 0, 4)))) {
        cpResponder(false, 'Indique una fecha de vencimiento válida.');
    }
    if ($fechaVencimiento !== '' && $fechaVencimiento < $fechaFactura) {
        cpResponder(false, 'El vencimiento no puede ser anterior a la fecha de la factura.');
    }
    if ($abonoInicial < 0) {
        cpResponder(false, 'El abono inicial no puede ser negativo.');
    }
    if (!is_array($conceptosRaw) || count($conceptosRaw) === 0 || count($conceptosRaw) > 100) {
        cpResponder(false, 'Agregue al menos un concepto a la factura.');
    }

    $conceptos = [];
    $total = 0.0;

    foreach ($conceptosRaw as $fila) {
        if (!is_array($fila)) {
            continue;
        }

        $texto = trim((string) ($fila['concepto'] ?? ''));
        $montoRaw = $fila['monto'] ?? '';

        // Las filas completamente vacías se ignoran (el usuario pudo dejar
        // una fila nueva sin usar antes de guardar).
        if ($texto === '' && ($montoRaw === '' || $montoRaw === null)) {
            continue;
        }
        if ($texto === '' || strlen($texto) > 255) {
            cpResponder(false, 'Cada concepto debe tener una descripción de hasta 255 caracteres.');
        }
        if (!is_numeric($montoRaw)) {
            cpResponder(false, 'Indique un monto válido en cada concepto.');
        }

        $monto = round((float) $montoRaw, 2);
        if ($monto <= 0) {
            cpResponder(false, 'El monto de cada concepto debe ser mayor que cero.');
        }

        $total += $monto;
        $conceptos[] = ['concepto' => $texto, 'monto' => $monto];
    }

    if (count($conceptos) === 0) {
        cpResponder(false, 'Agregue al menos un concepto a la factura.');
    }

    $total = round($total, 2);
    if ($total <= 0) {
        cpResponder(false, 'El total de la factura debe ser mayor que cero.');
    }
    if ($total > 9999999999999.99) {
        cpResponder(false, 'El total de la factura es demasiado alto.');
    }
    if ($abonoInicial > $total + 0.001) {
        cpResponder(false, 'El abono inicial no puede superar el total de la factura.');
    }

    $sucursal = $nivel === 2 ? (int) $_SESSION['sucursal'] : (int) ($input['sucursal'] ?? 0);
    if ($sucursal <= 0) {
        cpResponder(false, 'Seleccione una sucursal.');
    }

    $stmt = $conexion->prepare("SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        cpResponder(false, 'Error de base de datos.');
    }
    $stmt->bind_param("ii", $sucursal, $bssId);
    $stmt->execute();
    $sucursalValida = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$sucursalValida) {
        cpResponder(false, 'La sucursal indicada no pertenece al negocio.');
    }

    $estado = cp_estado($total, $abonoInicial);

    // Si queda saldo se usa la fecha indicada o, por defecto, 30 días.
    if ($estado === 'pendiente') {
        if ($fechaVencimiento === '') {
            $fechaVencimiento = date('Y-m-d', strtotime($fechaFactura . ' +30 days'));
        }
    } else {
        $fechaVencimiento = null;
    }

    $hoy = date('Y-m-d');

    $conexion->begin_transaction();

    try {
        $stmt = cpStmt($conexion, "INSERT INTO cuentas_pagar_facturas (bss_id, id_sucursal, proveedor, numero_factura, fecha_factura, fecha_vencimiento, total, estado, usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssdss", $bssId, $sucursal, $proveedor, $numeroFactura, $fechaFactura, $fechaVencimiento, $total, $estado, $usuario);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo crear la factura.');
        }
        $facturaId = (int) $conexion->insert_id;
        $stmt->close();
        if ($facturaId <= 0) {
            throw new Exception('No se pudo crear la factura.');
        }

        $stmtConcepto = cpStmt($conexion, "INSERT INTO cuentas_pagar_conceptos (factura_id, bss_id, concepto, monto) VALUES (?, ?, ?, ?)");
        foreach ($conceptos as $concepto) {
            $stmtConcepto->bind_param("iisd", $facturaId, $bssId, $concepto['concepto'], $concepto['monto']);
            if (!$stmtConcepto->execute()) {
                throw new Exception('No se pudo registrar un concepto de la factura.');
            }
        }
        $stmtConcepto->close();

        if ($abonoInicial > 0) {
            $stmt = cpStmt($conexion, "INSERT INTO cuentas_pagar_pagos (factura_id, bss_id, monto, fecha, usuario) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iidss", $facturaId, $bssId, $abonoInicial, $hoy, $usuario);
            if (!$stmt->execute()) {
                throw new Exception('No se pudo registrar el abono inicial.');
            }
            $stmt->close();
        }

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        cpResponder(false, $e->getMessage());
    }

    cpResponder(true, 'Factura registrada correctamente.', [
        'factura_id' => $facturaId,
        'total' => $total,
        'abonado' => $abonoInicial,
        'saldo' => round($total - $abonoInicial, 2),
        'estado' => $estado,
        'fecha_vencimiento' => $fechaVencimiento,
    ]);
}

// ── Registrar un abono ──────────────────────────────────────────────────────
if ($accion === 'abonar') {
    $facturaId = (int) ($input['factura_id'] ?? 0);
    $monto = round((float) ($input['monto'] ?? 0), 2);

    if ($facturaId <= 0 || $monto <= 0) {
        cpResponder(false, 'Indique un monto de abono válido.');
    }

    $conexion->begin_transaction();

    try {
        $stmt = cpStmt($conexion, "SELECT id_sucursal, total FROM cuentas_pagar_facturas WHERE id = ? AND bss_id = ? FOR UPDATE");
        $stmt->bind_param("ii", $facturaId, $bssId);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$factura || ($nivel === 2 && (int) $factura['id_sucursal'] !== (int) $_SESSION['sucursal'])) {
            throw new Exception('Factura no encontrada.');
        }

        $stmt = cpStmt($conexion, "SELECT COALESCE(SUM(monto), 0) AS abonado FROM cuentas_pagar_pagos WHERE factura_id = ? AND bss_id = ?");
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
        $stmt = cpStmt($conexion, "INSERT INTO cuentas_pagar_pagos (factura_id, bss_id, monto, fecha, usuario) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iidss", $facturaId, $bssId, $monto, $hoy, $usuario);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo registrar el abono.');
        }
        $stmt->close();

        $nuevoAbonado = round($abonado + $monto, 2);
        $estado = cp_estado($total, $nuevoAbonado);

        $stmt = cpStmt($conexion, "UPDATE cuentas_pagar_facturas SET estado = ? WHERE id = ? AND bss_id = ?");
        $stmt->bind_param("sii", $estado, $facturaId, $bssId);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo actualizar el estado de la factura.');
        }
        $stmt->close();

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        cpResponder(false, $e->getMessage());
    }

    cpResponder(true, 'Abono registrado correctamente.', [
        'total' => $total,
        'abonado' => $nuevoAbonado,
        'saldo' => round($total - $nuevoAbonado, 2),
        'estado' => $estado,
    ]);
}

// ── Actualizar la fecha de vencimiento ──────────────────────────────────────
if ($accion === 'vencimiento') {
    $facturaId = (int) ($input['factura_id'] ?? 0);
    $fechaVencimiento = trim((string) ($input['fecha_vencimiento'] ?? ''));

    if ($facturaId <= 0) {
        cpResponder(false, 'Factura inválida.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVencimiento) || !checkdate((int) substr($fechaVencimiento, 5, 2), (int) substr($fechaVencimiento, 8, 2), (int) substr($fechaVencimiento, 0, 4))) {
        cpResponder(false, 'Indique una fecha de vencimiento válida.');
    }

    try {
        $stmt = cpStmt($conexion, "SELECT id_sucursal, total, fecha_factura FROM cuentas_pagar_facturas WHERE id = ? AND bss_id = ? LIMIT 1");
        $stmt->bind_param("ii", $facturaId, $bssId);
        $stmt->execute();
        $factura = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$factura || ($nivel === 2 && (int) $factura['id_sucursal'] !== (int) $_SESSION['sucursal'])) {
            throw new Exception('Factura no encontrada.');
        }

        $fechaFactura = date('Y-m-d', strtotime((string) $factura['fecha_factura']));
        if ($fechaVencimiento < $fechaFactura) {
            throw new Exception('El vencimiento no puede ser anterior a la fecha de la factura.');
        }

        $stmt = cpStmt($conexion, "SELECT COALESCE(SUM(monto), 0) AS abonado FROM cuentas_pagar_pagos WHERE factura_id = ? AND bss_id = ?");
        $stmt->bind_param("ii", $facturaId, $bssId);
        $stmt->execute();
        $abonado = (float) ($stmt->get_result()->fetch_assoc()['abonado'] ?? 0);
        $stmt->close();

        if (round((float) $factura['total'] - $abonado, 2) <= 0.005) {
            throw new Exception('La factura ya está pagada; no requiere vencimiento.');
        }

        $stmt = cpStmt($conexion, "UPDATE cuentas_pagar_facturas SET fecha_vencimiento = ? WHERE id = ? AND bss_id = ?");
        $stmt->bind_param("sii", $fechaVencimiento, $facturaId, $bssId);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo actualizar el vencimiento.');
        }
        $stmt->close();
    } catch (Throwable $e) {
        cpResponder(false, $e->getMessage());
    }

    cpResponder(true, 'Vencimiento actualizado correctamente.', [
        'fecha_vencimiento' => $fechaVencimiento,
    ]);
}

cpResponder(false, 'Acción inválida.');
