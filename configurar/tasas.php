<?php
require_once("configuracion.php");
require_once('session.php');

$tipoTasa = $_POST['tipoTasa'];
$margen = $_POST['margen'];
$redondeo = $_POST['redondeo'];
$bolivar = $_POST['bolivar'];
$peso = $_POST['peso'];
$bolivarPeso = $_POST['bolivarPeso'];
$peso_bolivar = $_POST['peso_bolivar'];
$bss_id = $_SESSION['bss_id'];

if ($tipoTasa == 3) {
    if ($margen != 0 && $margen != '') {
        $bolivar += $margen;
    }
    if ($redondeo == 1) {
        $bolivar = round($bolivar, 0, PHP_ROUND_HALF_UP);
    } elseif ($redondeo == 2) {
        $bolivar = round($bolivar, 0, PHP_ROUND_HALF_DOWN);
    }
}

// Preparar la consulta
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

// Vincular parámetros (asumiendo que todos son tipo string, cambia según el tipo: 'd' para double, 'i' para entero)
$stmt->bind_param("dddddddi", $peso, $bolivarPeso, $bolivar, $tipoTasa, $margen, $redondeo, $peso_bolivar, $bss_id);

// Ejecutar la consulta
if ($stmt->execute()) {
    // Éxito
} else {
    echo "Error al actualizar los datos: " . $stmt->error;
}

$stmt->close();
header("Location: " . $_SERVER['HTTP_REFERER']);
