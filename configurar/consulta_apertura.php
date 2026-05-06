<?php
require_once("configuracion.php");
require_once('session.php');

$fecha = date('Y-m-d');
$sucursal = $_SESSION['sucursal'];
$bss_id = $_SESSION['bss_id'];
$tipo_corte = $_GET['tipo_corte'];
$usuario_id = $_SESSION['id'];


$sql = "SELECT id FROM cortes_de_caja WHERE fecha = ? AND sucursal_id = ? AND bss_id = ? AND usuario_id = ? AND tipo_corte = ?";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Error al preparar la consulta: " . $conexion->error]);
    exit;
}
$stmt->bind_param("siiis", $fecha, $sucursal, $bss_id, $usuario_id, $tipo_corte);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["status" => "success", "corte" => true]);
} else {
    echo json_encode(["status" => "success", "corte" => false]);
}
$stmt->close();
