<?php
// api/get_categories_list.php
header('Content-Type: application/json');
require_once('../core/db.php');

$tienda_slug = isset($_GET['tienda_slug']) ? trim($_GET['tienda_slug']) : '';

if ($tienda_slug !== '') {
    // Categorías de los productos publicados por la tienda específica
    $sql = "
        SELECT DISTINCT c.id, c.nombre 
        FROM categorias_producto c
        JOIN productos p ON p.categoria = c.id
        JOIN negocio_productos np ON np.producto_id = p.id
        JOIN negocio n ON n.id = np.negocio_id
        WHERE n.slug = ? AND np.estado_publicacion = 1
        ORDER BY c.nombre ASC
    ";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $tienda_slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        $result = false;
    }
} else {
    // Categorías de TODOS los productos publicados en el marketplace
    $sql = "
        SELECT DISTINCT c.id, c.nombre 
        FROM categorias_producto c
        JOIN productos p ON p.categoria = c.id
        JOIN negocio_productos np ON np.producto_id = p.id
        WHERE np.estado_publicacion = 1
        ORDER BY c.nombre ASC
    ";
    $result = $conexion->query($sql);
}

$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

echo json_encode(['success' => true, 'categories' => $categories]);
?>
