<?php
require_once 'configuracion.php';
require_once 'session.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$producto_id = intval($input['producto_id'] ?? 0);
$sucursal = $input['sucursal'] ?? '';

if ($producto_id <= 0 || empty($sucursal)) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

// Aquí iría tu lógica real de actualización en la base de datos

$stmt = $conexion->prepare("SELECT porcentaje FROM stock WHERE id_producto = ? AND id_sucursal = ?");
$stmt->bind_param('ii', $producto_id, $sucursal);
$stmt->execute();
$stmt->bind_result($porcentaje);
$stmt->fetch();
$stmt->close();

// Supongamos que obtienes un ID de actualización como resultado:

echo json_encode(['success' => true, 'data' => $porcentaje]);
