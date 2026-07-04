<?php
require_once 'configuracion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$stmt = $conexion->prepare("SELECT valor, time FROM cambios_bcv_historico ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'status'  => 'success',
        'valor'   => (float) $row['valor'],
        'time'    => $row['time']
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'No hay registros BCV disponibles.'
    ]);
}
$stmt->close();
?>
