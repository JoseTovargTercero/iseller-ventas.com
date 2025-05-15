<?php

require_once('configuracion.php');
require_once('session.php');

require '_tablas.php';

$consulta = new Tablas($conexion);
$bss_id = $_SESSION["bss_id"];

// PROCESAR SOLICITUDES
$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data["tabla"])) {
    echo json_encode(["error" => "Acción no especificada."]);
    exit;
}


// configuraciones



// Con condiciones
$datos = $tablas->obtenerDatosTablas("sucursales", ["bss_id" => $bss_id]);
