<?php
require_once('includes/requires.php');

$usuario_id = $_SESSION['id'];
$efectivo_bs_fondo = $_POST['efectivo_bs_fondo'] ?? 0;
$efectivo_usd_fondo = $_POST['efectivo_usd_fondo'] ?? 0;
$pesos_fondo = $_POST['pesos_fondo'] ?? 0;
$observaciones = $_POST['observaciones'] ?? '';

$fecha = date('Y-m-d');
$sucursal = $_SESSION['sucursal'];
$bss_id = $_SESSION['bss_id'];

$sql = "INSERT INTO cortes_de_caja (
    usuario_id,
    bss_id,
    sucursal_id,
    tipo_corte,
    fecha,

    efectivo_bs_fondo,
    efectivo_usd_fondo,
    pesos_fondo,
    observaciones
) VALUES (?, ?, ?, 'apertura', ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "iiisddds",
    $usuario_id,
    $bss_id,
    $sucursal,
    $fecha,
    $efectivo_bs_fondo,
    $efectivo_usd_fondo,
    $pesos_fondo,
    $observaciones
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Caja aperturada exitosamente"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al aperturar caja: " . $stmt->error
    ]);
}
?>