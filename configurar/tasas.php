<?php
require_once("configuracion.php");
require_once('session.php');

$tipoTasa = $_POST['tipoTasa'];
$margen = $_POST['margen'] ?? 0;
$redondeo = $_POST['redondeo'] ?? 0;
$peso = $_POST['peso'];
$bolivarPeso = $_POST['bolivarPeso'];
$peso_bolivar = $_POST['peso_bolivar'];
$bss_id = $_SESSION['bss_id'];

require_once('_tasas_cambio.php');
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
    echo "Error al preparar la consulta: " . $conexion->error;
    exit;
}

$stmt->bind_param("dddddddi", $peso, $bolivarPeso, $bolivar, $tipoTasa, $margen, $redondeo, $peso_bolivar, $bss_id);

if ($stmt->execute()) {
} else {
    echo "Error al actualizar los datos: " . $stmt->error;
}

$stmt->close();
header("Location: " . $_SERVER['HTTP_REFERER']);
