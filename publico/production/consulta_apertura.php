<?php
require_once('includes/requires.php');

$fecha = date('Y-m-d');
$sucursal = $_SESSION['sucursal'];
$bss_id = $_SESSION['bss_id'];


$sql = "SELECT id FROM cortes_de_caja WHERE fecha = ? AND sucursal_id = ? AND bss_id = ?";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Error al preparar la consulta: " . $conexion->error]);
    exit;
}
$stmt->bind_param("sii", $fecha, $sucursal, $bss_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "success", "abierta" => true]);
} else {
    echo json_encode(["status" => "success", "abierta" => false]);
}
$stmt->close();
