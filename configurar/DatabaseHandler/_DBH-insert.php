<?php

require_once 'conexion.php';
require_once 'session.php';
require_once 'errores.php';
require_once 'DatabaseHandler.php';
$db = new DatabaseHandler($conexion);

function insertarRegistro($tabla, $data)
{
    global $db;

    $campos_valores = [];
    foreach ($data as $campo => $valor) {
        $campos_valores[] = [$campo, $valor];
    }

    try {
        $resultado = $db->insert($tabla, $campos_valores);
        return $resultado;
    } catch (Exception $e) {
        throw new Exception("Error: " . $e->getMessage());
    }
}

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data["t_name"]) || !isset($data["data"])) {
    echo json_encode(["error" => "Se esperaban mas datos"]);
    exit;
}

$response = insertarRegistro($data["t_name"], $data["data"]);

echo json_encode($response);
