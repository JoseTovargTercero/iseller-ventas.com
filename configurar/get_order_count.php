<?php
header('Content-Type: application/json');
require_once 'configuracion.php';

try {
    $sql = "SELECT COUNT(*) as total_sucursales FROM `sucursales`";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $sucursales = (int)$row['total_sucursales'] * 3;


    // Preparar la consulta para contar las órdenes de forma segura
    $sql = "SELECT COUNT(*) as total FROM `orden`";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conexion->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'status' => 'success',
        'count' => (int)$row['total'],
        'count_sucursales' => $sucursales
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conexion->close();
}
?>
