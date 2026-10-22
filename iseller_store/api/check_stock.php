<?php
// api/check_stock.php
header('Content-Type: application/json');
require_once('../core/db.php');
require_once('../core/session.php');

if (!isset($_GET['id']) || !isset($_GET['cantidad'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
    exit;
}

$id = (int)$_GET['id'];
$cantidad = (float)$_GET['cantidad'];
$tienda_slug = $_GET['tienda_slug'] ?? null;

// Buscamos dinámicamente el stock en la sucursal donde está publicado el producto
$sql = "SELECT s.stock 
        FROM productos p
        INNER JOIN negocio_productos np ON p.id = np.producto_id AND np.estado_publicacion = 1
        INNER JOIN stock s ON p.id = s.id_producto AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
        INNER JOIN negocio n ON np.negocio_id = n.id AND s.bss_id = n.id
        WHERE p.id = ?";

if ($tienda_slug) {
    $sql .= " AND n.slug = ?";
}
$sql .= " LIMIT 1";

$stmt = $conexion->prepare($sql);
if ($tienda_slug) {
    $stmt->bind_param("is", $id, $tienda_slug);
} else {
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $stockActual = (float)$row['stock'];
    
    if ($stockActual >= $cantidad) {
        echo json_encode(['success' => true, 'stock' => $stockActual]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Stock insuficiente', 'current_stock' => $stockActual]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado o no publicado']);
}
?>
