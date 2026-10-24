<?php
require_once('../core/db.php');
require_once('../core/session.php');
require_once('../core/favoritos.php');

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'login' => true, 'msg' => 'Inicia sesión para guardar favoritos.']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido.']);
    exit;
}

$negocioId = (int)($_POST['negocio_id'] ?? 0);
if ($negocioId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'msg' => 'Tienda inválida.']);
    exit;
}

$stmt = $conexion->prepare(
    "SELECT id FROM negocio WHERE id = ? AND tiene_tienda_virtual = 1 AND estado = 'activo' LIMIT 1"
);
$stmt->bind_param('i', $negocioId);
$stmt->execute();
$negocio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$negocio) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Tienda no encontrada.']);
    exit;
}

$usuarioId = (int)getUserId();
$conexion_store->set_charset('utf8mb4');

if (!favoritos_asegurar($conexion_store)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'No se pudo preparar la tabla de favoritos.']);
    exit;
}

$esFavorito = es_favorito($conexion_store, $usuarioId, $negocioId);

if ($esFavorito) {
    $stmt = $conexion_store->prepare(
        "DELETE FROM tienda_favoritos WHERE usuario_id = ? AND negocio_id = ?"
    );
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'No se pudo quitar el favorito.']);
        exit;
    }
    $stmt->bind_param('ii', $usuarioId, $negocioId);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['ok' => true, 'favorito' => false]);
    exit;
}

$stmt = $conexion_store->prepare(
    "INSERT INTO tienda_favoritos (usuario_id, negocio_id)
     VALUES (?, ?)
     ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id)"
);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar el favorito.']);
    exit;
}
$stmt->bind_param('ii', $usuarioId, $negocioId);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true, 'favorito' => true]);
