<?php
require_once('includes/requires.php');

$usuario_id = $_SESSION['id'];
$sucursal = $_SESSION['sucursal'];
$bss_id = $_SESSION['bss_id'];
$fecha = date('Y-m-d');

// Contado (Real)
$efectivo_bs_contado = $_POST['efectivo_bs_contado'] ?? 0;
$efectivo_usd_contado = $_POST['efectivo_usd_contado'] ?? 0;
$pesos_contado = $_POST['pesos_contado'] ?? 0;
$punto_contado = $_POST['punto_contado'] ?? 0;
$biopago_contado = $_POST['biopago_contado'] ?? 0;
$pago_movil_contado = $_POST['pago_movil_contado'] ?? 0;
$transferencia_contado = $_POST['transferencia_contado'] ?? 0;

// Fondo
$efectivo_bs_fondo = $_POST['efectivo_bs_fondo'] ?? 0;
$efectivo_usd_fondo = $_POST['efectivo_usd_fondo'] ?? 0;
$pesos_fondo = $_POST['pesos_fondo'] ?? 0;

$observaciones = $_POST['observaciones'] ?? '';

// Opcional: Aquí podrías calcular las sumas de las ventas del sistema 
// y calcular las diferencias. Por ahora guardamos lo que el usuario contó.
// ... 

$sql = "INSERT INTO cortes_de_caja (
    usuario_id,
    bss_id,
    sucursal_id,
    tipo_corte,
    fecha,

    efectivo_bs_contado,
    efectivo_usd_contado,
    pesos_contado,
    punto_contado,
    biopago_contado,
    pago_movil_contado,
    transferencia_contado,

    efectivo_bs_fondo,
    efectivo_usd_fondo,
    pesos_fondo,

    observaciones
) VALUES (?, ?, ?, 'cierre', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "iiisdddddddddds",
    $usuario_id,
    $bss_id,
    $sucursal,
    $fecha,
    
    $efectivo_bs_contado,
    $efectivo_usd_contado,
    $pesos_contado,
    $punto_contado,
    $biopago_contado,
    $pago_movil_contado,
    $transferencia_contado,

    $efectivo_bs_fondo,
    $efectivo_usd_fondo,
    $pesos_fondo,
    
    $observaciones
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Caja cerrada exitosamente"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al cerrar caja: " . $stmt->error
    ]);
}
?>
