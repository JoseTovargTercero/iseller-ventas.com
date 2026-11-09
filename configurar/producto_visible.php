<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit;
}

$bssId = (int) $_SESSION['bss_id'];
$nivel = (int) ($_SESSION['nivel'] ?? 2);
$productoId = (int) ($_POST['producto_id'] ?? 0);
$visible = (int) ($_POST['visible'] ?? 1) === 1 ? 1 : 0;
$sucursal = (int) ($_POST['sucursal'] ?? 0);

if ($productoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no válido.']);
    exit;
}
if ($nivel === 2) {
    $sucursal = (int) ($_SESSION['sucursal'] ?? 0);
}

$stmt = $conexion->prepare("SELECT id FROM productos WHERE id = ? AND bss_id = ? LIMIT 1");
$stmt->bind_param('ii', $productoId, $bssId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
    exit;
}
$stmt->close();

if ($sucursal > 0) {
    $stmt = $conexion->prepare("SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
    $stmt->bind_param('ii', $sucursal, $bssId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Sucursal no válida.']);
        exit;
    }
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE stock SET visible_venta = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
    $stmt->bind_param('iiii', $visible, $productoId, $sucursal, $bssId);
} else {
    $stmt = $conexion->prepare("UPDATE stock SET visible_venta = ? WHERE id_producto = ? AND bss_id = ?");
    $stmt->bind_param('iii', $visible, $productoId, $bssId);
}
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => $visible ? 'Producto en venta.' : 'Producto pausado.', 'visible' => $visible]);
