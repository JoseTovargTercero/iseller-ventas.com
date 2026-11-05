<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$usuarioId = (int)($_SESSION['id'] ?? 0);
$nivel = (int)($_SESSION['nivel'] ?? 0);
$puede = $nivel === 1;

if (!$puede) {
    $stmt = $conexion->prepare("
        SELECT 1 FROM users_permisos up
        INNER JOIN menu m ON m.id = up.id_item_menu
        WHERE up.id_user = ? AND m.nombre = 'Venta de productos al mayor'
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        $puede = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    }
}

if (!$puede) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Sin permiso']);
    exit;
}

$_SESSION['pos_modo_mayor'] = (($_POST['modo'] ?? '') === '1');

echo json_encode(['ok' => true, 'modo_mayor' => $_SESSION['pos_modo_mayor']]);
