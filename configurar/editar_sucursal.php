<?php
require_once("configuracion.php");
require_once('session.php');


header('Content-Type: application/json');

$response = ['success' => false, 'mensaje' => ''];

// Validar datos esperados
if (!isset($_POST['edit_nombre'], $_POST['edit_stock_critico'], $_POST['id_editar'])) {
	$response['mensaje'] = 'Faltan datos requeridos.';
	echo json_encode($response);
	exit;
}

$nombre = trim($_POST['edit_nombre']);
$stock_critico = trim($_POST['edit_stock_critico']);
$id = trim($_POST['id_editar']);

// Validar que no estén vacíos
if ($nombre === '' || $stock_critico === '' || $id === '') {
	$response['mensaje'] = 'Todos los campos son obligatorios.';
	echo json_encode($response);
	exit;
}

// Preparar y ejecutar la consulta
$stmt = $conexion->prepare("UPDATE sucursales SET nombre = ?, stockCritico = ? WHERE id = ? AND bss_id=$bss_id");
if ($stmt) {
	$stmt->bind_param("sii", $nombre, $stock_critico, $id);

	if ($stmt->execute()) {
		$response['success'] = true;
		$response['mensaje'] = 'Sucursal actualizada correctamente.';
	} else {
		$response['mensaje'] = 'Error al ejecutar la consulta: ' . $stmt->error;
	}

	$stmt->close();
} else {
	$response['mensaje'] = 'Error en la preparación de la consulta.';
}

$conexion->close();
echo json_encode($response);
