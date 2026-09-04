<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');

$bss_id      = (int) $_SESSION['bss_id'];
$usuario_id  = (int) $_SESSION['id'];
$id_sucursal = (int) $_SESSION['sucursal'];
$nivel       = (int) $_SESSION['nivel'];

if (!$bss_id || !$usuario_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Sesión no válida']);
    exit;
}

$id_gasto = intval($_POST['id'] ?? 0);
$motivo   = trim(strip_tags($_POST['motivo'] ?? ''));

if ($id_gasto <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'ID de gasto inválido']);
    exit;
}

if (empty($motivo)) {
    echo json_encode(['status' => 'error', 'msg' => 'El motivo de anulación es obligatorio']);
    exit;
}

$filtroSucursal = $nivel == 1 ? '' : ' AND id_sucursal=?';

$stmt = $conexion->prepare(
    "SELECT id, estado FROM gastos WHERE id=? AND bss_id=?$filtroSucursal"
);
if ($nivel == 1) {
    $stmt->bind_param('ii', $id_gasto, $bss_id);
} else {
    $stmt->bind_param('iii', $id_gasto, $bss_id, $id_sucursal);
}
$stmt->execute();
$gasto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$gasto) {
    echo json_encode(['status' => 'error', 'msg' => 'Gasto no encontrado']);
    exit;
}

if ($gasto['estado'] === 'ANULADO') {
    echo json_encode(['status' => 'error', 'msg' => 'Este gasto ya fue anulado']);
    exit;
}

$stmt = $conexion->prepare(
    "UPDATE gastos
     SET estado='ANULADO', usuario_anulacion=?, fecha_anulacion=NOW(), motivo_anulacion=?
     WHERE id=? AND bss_id=?$filtroSucursal"
);
if ($nivel == 1) {
    $stmt->bind_param('isii', $usuario_id, $motivo, $id_gasto, $bss_id);
} else {
    $stmt->bind_param('isiii', $usuario_id, $motivo, $id_gasto, $bss_id, $id_sucursal);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'ok', 'msg' => 'Gasto anulado correctamente']);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Error al anular gasto']);
}

$stmt->close();
