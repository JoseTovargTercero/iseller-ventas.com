<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_tasas_cambio.php');

header('Content-Type: application/json');

$bss_id      = (int) $_SESSION['bss_id'];
$usuario_id  = (int) $_SESSION['id'];
$id_sucursal = (int) ($_POST['id_sucursal'] ?: $_SESSION['sucursal']);

if (!$bss_id || !$usuario_id || !$id_sucursal) {
    echo json_encode(['status' => 'error', 'msg' => 'Sesión no válida']);
    exit;
}

$concepto     = trim(strip_tags($_POST['concepto'] ?? ''));
$categoria_id = intval($_POST['categoria_id'] ?? 0) ?: null;
$tipo         = in_array($_POST['tipo'] ?? '', ['FIJO', 'VARIABLE']) ? $_POST['tipo'] : null;
$frecuencia   = in_array($_POST['frecuencia'] ?? '', [
    'UNICO', 'DIARIO', 'SEMANAL', 'QUINCENAL',
    'MENSUAL', 'BIMESTRAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL'
]) ? $_POST['frecuencia'] : null;
$moneda       = in_array($_POST['moneda'] ?? '', ['USD', 'VES', 'COP']) ? $_POST['moneda'] : 'USD';
$monto        = floatval($_POST['monto'] ?? 0);
$fecha        = trim($_POST['fecha'] ?? '');
$observacion  = trim(strip_tags($_POST['observacion'] ?? ''));

if (!$concepto || !$tipo || !$frecuencia || $monto <= 0 || !$fecha) {
    echo json_encode(['status' => 'error', 'msg' => 'Campos obligatorios incompletos']);
    exit;
}

$dt = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$dt || $dt->format('Y-m-d') !== $fecha) {
    echo json_encode(['status' => 'error', 'msg' => 'Fecha inválida']);
    exit;
}

switch ($moneda) {
    case 'VES':
        $tasa_cambio = floatval($dolarBolivar);
        $monto_usd   = ($tasa_cambio > 0) ? round($monto / $tasa_cambio, 2) : 0;
        break;
    case 'COP':
        $tasa_cambio = floatval($pesoDolar);
        $monto_usd   = ($tasa_cambio > 0) ? round($monto / $tasa_cambio, 2) : 0;
        break;
    default:
        $tasa_cambio = 1.0;
        $monto_usd   = $monto;
}

$semana = $dt->format('Y-W');
$mes    = $dt->format('Y-m');

$stmt = $conexion->prepare("SELECT codigo FROM gastos WHERE bss_id=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$row    = $stmt->get_result()->fetch_assoc();
$numero = $row ? (int) substr($row['codigo'], 2) + 1 : 1;
$codigo = 'G-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
$stmt->close();

if ($categoria_id) {
    $stmt = $conexion->prepare(
        "INSERT INTO gastos
         (codigo, fecha, semana, mes, concepto, categoria_id, tipo, frecuencia,
          moneda, monto, tasa_cambio, monto_usd, observacion, usuario_id, id_sucursal, bss_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param(
        'sssssisssddsiiii',
        $codigo, $fecha, $semana, $mes, $concepto, $categoria_id, $tipo, $frecuencia,
        $moneda, $monto, $tasa_cambio, $monto_usd, $observacion, $usuario_id, $id_sucursal, $bss_id
    );
} else {
    $stmt = $conexion->prepare(
        "INSERT INTO gastos
         (codigo, fecha, semana, mes, concepto, tipo, frecuencia,
          moneda, monto, tasa_cambio, monto_usd, observacion, usuario_id, id_sucursal, bss_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->bind_param(
        'ssssssssssddsii',
        $codigo, $fecha, $semana, $mes, $concepto, $tipo, $frecuencia,
        $moneda, $monto, $tasa_cambio, $monto_usd, $observacion, $usuario_id, $id_sucursal, $bss_id
    );
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'ok', 'codigo' => $codigo]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Error al registrar gasto']);
}

$stmt->close();
