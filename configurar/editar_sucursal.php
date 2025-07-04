<?php
require_once("configuracion.php");
require_once('session.php');


header('Content-Type: application/json');

$response = ['success' => false, 'mensaje' => ''];



function cargarArchivo($archivo, $folder, $id)
{
	// Verifica si el archivo existe y no hubo error en la subida
	if (isset($_FILES[$archivo]) && $_FILES[$archivo]['error'] === UPLOAD_ERR_OK) {

		$source = $_FILES[$archivo]['tmp_name']; // Archivo temporal
		$extension = pathinfo($_FILES[$archivo]['name'], PATHINFO_EXTENSION); // Obtener extensión real

		// Validar extensión permitida
		$ext_permitidas = ['jpg', 'jpeg', 'png'];
		if (!in_array(strtolower($extension), $ext_permitidas)) {
			echo "Extensión no permitida";
			return;
		}

		// Crear el directorio si no existe
		if (!file_exists($folder)) {
			if (!mkdir($folder, 0777, true)) {
				echo "No se pudo crear el directorio";
				return;
			}
		}

		$target_path = $folder . '/' . $id . '.png'; // Guardar como PNG
		if (move_uploaded_file($source, $target_path)) {
			return true;
		} else {
			return false;
		}
	}
}




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
	cargarArchivo("foto2", '../publico/production/images/sucursal_logo/', $id);

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
