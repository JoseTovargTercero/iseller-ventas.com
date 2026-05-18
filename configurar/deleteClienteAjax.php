<?php
require_once('configuracion.php');
require_once('session.php');

$clienteId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($clienteId === 0) {
    echo json_encode(['status' => false, 'msg' => 'ID de cliente inválido.']);
    exit;
}

try {
    // Validar que el cliente pertenece al negocio
    $stmt = $conexion->prepare("SELECT id FROM clientes WHERE id = ? AND bss_id = ?");
    $stmt->bind_param("ii", $clienteId, $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => false, 'msg' => 'No tiene permisos para eliminar este cliente o el cliente no existe.']);
        exit;
    }
    $stmt->close();

    $stmtDelete = $conexion->prepare("DELETE FROM clientes WHERE id = ? AND bss_id = ?");
    $stmtDelete->bind_param("ii", $clienteId, $bss_id);
    
    if ($stmtDelete->execute()) {
        echo json_encode(['status' => true, 'msg' => 'Cliente eliminado correctamente.']);
    } else {
        echo json_encode(['status' => false, 'msg' => 'Error al eliminar el cliente. Es posible que tenga ventas asociadas.']);
    }
    $stmtDelete->close();

} catch (Exception $e) {
    echo json_encode(['status' => false, 'msg' => 'Error del servidor: ' . $e->getMessage()]);
}
