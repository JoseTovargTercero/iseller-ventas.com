<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

$nombre = trim($_POST['nombre'] ?? '');
if (empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
    exit;
}

$slug = strtolower(trim($nombre));
$slug = preg_replace('/[áéíóúñ]/u', ['a','e','i','o','u','n'], $slug);
$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');

$stmt = $conexion->prepare("SELECT id FROM categorias_producto WHERE slug = ? AND bss_id = ?");
$stmt->bind_param("si", $slug, $bss_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'id' => (int)$row['id']]);
    $stmt->close();
    exit;
}
$stmt->close();

$stmt = $conexion->prepare("INSERT INTO categorias_producto (bss_id, nombre, slug) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $bss_id, $nombre, $slug);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => (int)$conexion->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar']);
}
$stmt->close();
