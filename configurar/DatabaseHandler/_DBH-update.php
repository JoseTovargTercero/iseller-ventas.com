<?php

require_once 'conexion.php';
require_once 'session.php';
require_once 'errores.php';
require_once 'DatabaseHandler.php';
$db = new DatabaseHandler($conexion);

function actualizarRegistro($tabla, $data)
{
    global $db;
    $valores = [];
    foreach ($data as $campo => $valor) {
        if ($campo !== 'id') {
            $valores[] = [$campo, $valor, 's'];
        }
    }
    try {
        $where = "id = " . intval($data['id']);
        $resultado = $db->update($tabla, $valores, $where);
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

$response = actualizarRegistro($data["t_name"], $data["data"]);
echo json_encode($response);
