<?php
header('Content-Type: application/json');
require_once("configuracion.php");
require_once('session.php');
require_once('_tasas_cambio.php');

$tipoTasa = $_POST['tipoTasa'];
$margen = $_POST['margen'] ?? 0;
$redondeo = $_POST['redondeo'] ?? 0;
$peso = $_POST['peso'];
$bolivarPeso = $_POST['bolivarPeso'];
$peso_bolivar = $_POST['peso_bolivar'];
$bss_id = $_SESSION['bss_id'];

$tasasCambio = new TasasCambio($conexion);

if ($tipoTasa == 2) {
    $bcv = $tasasCambio->obtenerBcv();
    $bolivar = $bcv;
} elseif ($tipoTasa == 3) {
    $bcv = $tasasCambio->obtenerBcv();
    $bolivar = $bcv;
    if ($margen != 0 && $margen != '') {
        $bolivar += $margen;
    }
    if ($redondeo == 1) {
        $bolivar = round($bolivar, 0, PHP_ROUND_HALF_UP);
    } elseif ($redondeo == 2) {
        $bolivar = round($bolivar, 0, PHP_ROUND_HALF_DOWN);
    }
} else {
    $bolivar = $_POST['bolivar'];
}

$stmt = $conexion->prepare("
    UPDATE cambio 
    SET 
        pesoDolar = ?, 
        bolivar_peso = ?, 
        DolarBolivar = ?, 
        tipo_tasa_bs = ?, 
        margen_neto = ?, 
        redondeo = ?, 
        peso_bolivar = ?
    WHERE id = ?
");

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conexion->error]);
    exit;
}

$stmt->bind_param("dddddddi", $peso, $bolivarPeso, $bolivar, $tipoTasa, $margen, $redondeo, $peso_bolivar, $bss_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Tasas actualizadas correctamente',
        'peso_bolivar' => $peso_bolivar
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar los datos: ' . $stmt->error]);
}

$stmt->close();
