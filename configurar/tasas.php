<?php
header('Content-Type: application/json');
require_once("configuracion.php");
require_once('session.php');
require_once('_tasas_cambio.php');

$tipoTasa = isset($_POST['tipoTasa']) ? (int)$_POST['tipoTasa'] : 1;
$margen = isset($_POST['margen']) && $_POST['margen'] !== '' ? $_POST['margen'] : '0';
$redondeo = isset($_POST['redondeo']) ? (int)$_POST['redondeo'] : 0;
$peso = isset($_POST['peso']) ? (string)$_POST['peso'] : '0';
$bolivarPeso = isset($_POST['bolivarPeso']) ? (string)$_POST['bolivarPeso'] : '0';
$peso_bolivar = isset($_POST['peso_bolivar']) ? (string)$_POST['peso_bolivar'] : '0';
$bss_id = (int)($_SESSION['bss_id'] ?? 0);

if ($bss_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida o identificador de negocio no encontrado.']);
    exit;
}

$tasasCambio = new TasasCambio($conexion);

if ($tipoTasa == 2) {
    $bcv = $tasasCambio->obtenerBcv();
    $bolivar = (string)$bcv;
} elseif ($tipoTasa == 3) {
    $bcv = (float)$tasasCambio->obtenerBcv();
    $bolivarNum = $bcv;
    $margenNum = (float)$margen;
    if ($margenNum != 0) {
        $bolivarNum += $margenNum;
    }
    if ($redondeo == 1) {
        $bolivarNum = round($bolivarNum, 0, PHP_ROUND_HALF_UP);
    } elseif ($redondeo == 2) {
        $bolivarNum = round($bolivarNum, 0, PHP_ROUND_HALF_DOWN);
    }
    $bolivar = (string)$bolivarNum;
} else {
    $bolivar = isset($_POST['bolivar']) ? (string)$_POST['bolivar'] : '0';
}

// Verificar si ya existe registro en cambio para este negocio (bss_id)
$check = $conexion->prepare("SELECT id FROM cambio WHERE bss_id = ? LIMIT 1");
if ($check === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar la base de datos: ' . $conexion->error]);
    exit;
}
$check->bind_param("i", $bss_id);
$check->execute();
$checkRes = $check->get_result();
$existe = ($checkRes && $checkRes->num_rows > 0);
$check->close();

if ($existe) {
    // Actualizar por bss_id (NO por id autoincremental)
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
        WHERE bss_id = ?
    ");
} else {
    // Si es un usuario nuevo y no tenía fila en cambio, se inserta
    $stmt = $conexion->prepare("
        INSERT INTO cambio (
            pesoDolar, 
            bolivar_peso, 
            DolarBolivar, 
            tipo_tasa_bs, 
            margen_neto, 
            redondeo, 
            peso_bolivar, 
            bss_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
}

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $conexion->error]);
    exit;
}

$stmt->bind_param("sssisisi", $peso, $bolivarPeso, $bolivar, $tipoTasa, $margen, $redondeo, $peso_bolivar, $bss_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Tasas actualizadas correctamente',
        'peso_bolivar' => $peso_bolivar,
        'bolivar' => $bolivar
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar los datos: ' . $stmt->error]);
}

$stmt->close();
