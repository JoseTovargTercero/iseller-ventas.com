<?php

require_once 'configuracion.php';
require_once 'session.php';
require_once '_tasas_cambio.php';
require("_calculadrora_precios.php");


header("Content-Type: application/json");

// Variables globales (ejemplo, deben estar definidas en tu sesión o config)

// Validar globals
if (!$bss_id) {
    echo json_encode(["success" => false, "message" => "Acceso no autorizado"]);
    exit;
}

// Validar ID
if (!isset($_POST["id"]) || empty($_POST["id"])) {
    echo json_encode(["success" => false, "message" => "ID no recibido"]);
    exit;
}

$id = intval($_POST["id"]);

// 1. Verificar que el abono pertenezca al usuario
$stmt = $conexion->prepare("
    SELECT id FROM abonos 
    WHERE id = ? AND bss_id = ?
");
$stmt->bind_param("ii", $id, $bss_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No autorizado para eliminar este abono"]);
    exit;
}
$stmt->close();

// 2. Eliminar si pasa la validación
$stmt = $conexion->prepare("DELETE FROM abonos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Error al eliminar el abono"]);
}

$stmt->close();
$conexion->close();
