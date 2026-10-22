<?php
/**
 * API: Obtener Categorías de Chat
 * Retorna todas las categorías activas para iniciar conversaciones
 */

require_once('../../core/db.php');

header('Content-Type: application/json');

try {
    // Obtener categorías activas
    $sql = "SELECT id, nombre, descripcion, icono, orden 
            FROM tienda_chat_categorias 
            WHERE activo = 1 
            ORDER BY orden ASC";
    
    $stmt = $conexion_store->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'icono' => $row['icono'],
            'orden' => (int)$row['orden']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $categorias
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener categorías'
    ]);
}
?>
