<?php

require_once 'conexion.php';
require_once 'session.php';
require_once 'errores.php';
require_once 'DatabaseHandler.php';
$db = new DatabaseHandler($conexion);

function eliminarRegistro($tabla, $id)
{
    global $db;

    try {
        $resultado = $db->delete($tabla, "id = " . intval($id));
        return $resultado;
    } catch (Exception $e) {
        throw new Exception("Error: " . $e->getMessage());
    }
}

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data["t_name"]) || !isset($data["id"])) {
    echo json_encode(["error" => "Se esperaban mas datos"]);
    exit;
}

$response = eliminarRegistro($data["t_name"], $data["id"]);
echo json_encode($response);
