<?php

require_once('../configuracion.php');
require_once '../session.php';
require_once 'DatabaseHandler.php';
$db = new DatabaseHandler($conexion);

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

$tabla = $data['table'];
$bss_id = $_SESSION["bss_id"];
$configFunction = $data['config'] ?? '_default';

// Verificar que la función de configuración existe y es callable
if (!function_exists($configFunction)) {
    echo json_encode(['error' => "Configuración '$configFunction' no válida o no encontrada"]);
    exit;
}

// Llamar a la función de configuración y obtener los parámetros
$config = $configFunction($tabla);

// Llamar al método select con la configuración seleccionada
try {
    $resultado = $db->select(
        $config['columnas'],
        $config['tabla'],
        $config['where'],
        $config['order_by'],
        $config['join']
    );
    echo $resultado;
} catch (Exception $e) {
    throw new Exception("Error al ejecutar la consulta: " . $e->getMessage());
}



/*
    * Configuraciones:
*/

function _default($tabla)
{
    return [
        'columnas' => null,
        'tabla' => $tabla,
        'where' => null,
        'order_by' => null,
        'join' => null
    ];
} // Tabla por defecto






/*
function _sucursales($tabla)
{
    global $data;
    $id_ejercicio = $data['id_ejercicio'];

    return [
        'columnas' => ["$tabla.*", "pl_programas.programa AS programa_n, pl_programas.denominacion, pl_sectores.sector AS sector_n, pl_sectores.id AS sector_id"],
        'tabla' => $tabla,
        'where' => "id_ejercicio='$id_ejercicio'",
        'order_by' => ['pl_programas.programa'],
        'join' => [
            'pl_programas' => "$tabla.programa = pl_programas.id",
            'pl_sectores' => "pl_programas.sector = pl_sectores.id"
        ]
    ];
} // carga el programa por join y filtra por id_ejercicio
*/