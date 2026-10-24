<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/session.php';
require_once __DIR__ . '/../core/venezuela_geo.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$estado = trim((string)($input['geo_estado'] ?? ''));
$municipio = trim((string)($input['geo_municipio'] ?? ''));
$geo = venezuela_estados_municipios();

if (!isset($geo[$estado]) || !in_array($municipio, $geo[$estado], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Selecciona un estado y municipio válidos.']);
    exit;
}

$conexion_store->set_charset('utf8mb4');
$uid = (int)getUserId();
$stmt = $conexion_store->prepare("UPDATE tienda_usuarios SET geo_estado = ?, geo_municipio = ? WHERE id = ? LIMIT 1");
$stmt->bind_param('ssi', $estado, $municipio, $uid);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudieron guardar los datos.']);
    exit;
}

$stmt->close();
echo json_encode(['success' => true]);
