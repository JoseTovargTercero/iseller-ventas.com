<?php
require_once("configuracion.php");

header('Content-Type: application/json');

// Verifica que el usuario tiene permiso (nivel 1)
if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] != 1) {
	echo json_encode(['tipo' => 'error', 'mensaje' => 'No tienes permisos para realizar esta acción.']);
	exit;
}

// Obtener el ID desde JSON
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

// Validar que el ID es un número entero positivo
if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
	echo json_encode(['tipo' => 'error', 'mensaje' => 'ID inválido.']);
	exit;
}

// Usar consulta preparada
$stmt = $conexion->prepare("UPDATE usuarios SET status = '1', usuario = '0' WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
	echo json_encode(['tipo' => 'success', 'mensaje' => 'Usuario eliminado.']);
} else {
	echo json_encode(['tipo' => 'error', 'mensaje' => 'Error al eliminar el usuario.']);
}

$stmt->close();
$conexion->close();
