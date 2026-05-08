<?php
require_once('configuracion.php');
require_once('session.php');

$clienteId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';

if ($clienteId === 0 || empty($cedula) || empty($nombre)) {
    echo json_encode(['status' => false, 'msg' => 'Datos incompletos para actualizar el cliente.']);
    exit;
}

try {
    // Validar que el cliente pertenece al negocio
    $stmt = $conexion->prepare("SELECT id FROM clientes WHERE id = ? AND bss_id = ?");
    $stmt->bind_param("ii", $clienteId, $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => false, 'msg' => 'No tiene permisos para editar este cliente o el cliente no existe.']);
        exit;
    }
    $stmt->close();

    $stmtUpdate = $conexion->prepare("UPDATE clientes SET cedula = ?, nombre = ?, telefono = ? WHERE id = ? AND bss_id = ?");
    $stmtUpdate->bind_param("sssii", $cedula, $nombre, $telefono, $clienteId, $bss_id);
    
    if ($stmtUpdate->execute()) {
        echo json_encode(['status' => true, 'msg' => 'Cliente actualizado correctamente.']);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Error al actualizar el cliente.']);
    }
    $stmtUpdate->close();

} catch (Exception $e) {
    echo json_encode(['status' => false, 'msg' => 'Error del servidor: ' . $e->getMessage()]);
}
