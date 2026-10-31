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
        JOIN stock s ON s.id_producto = p.id AND s.bss_id = n.id AND s.stock > 0
        WHERE n.slug = ? AND np.estado_publicacion = 1 AND p.activo = 0 AND n.estado = 'activo' AND n.tiene_tienda_virtual = 1
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
        JOIN negocio n ON n.id = np.negocio_id
        JOIN stock s ON s.id_producto = p.id AND s.bss_id = n.id AND s.stock > 0
        WHERE np.estado_publicacion = 1 AND p.activo = 0 AND n.estado = 'activo' AND n.tiene_tienda_virtual = 1
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

// Categoría especial para los productos al mayor con al menos una paca disponible.
$sqlMayor = "
    SELECT COUNT(DISTINCT p.id) AS total
    FROM productos p
    INNER JOIN negocio_productos np ON np.producto_id = p.id AND np.estado_publicacion = 1
    INNER JOIN negocio n ON n.id = np.negocio_id
    INNER JOIN stock s ON s.id_producto = p.id AND s.bss_id = n.id
         AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
    LEFT JOIN stock sb ON sb.id = s.id_stock AND sb.id_sucursal = s.id_sucursal AND sb.bss_id = s.bss_id
    WHERE p.activo = 0 AND p.mayor = '1' AND p.cantidad_unidades > 0
      AND sb.stock >= p.cantidad_unidades
      AND n.estado = 'activo' AND n.tiene_tienda_virtual = 1
";
if ($tienda_slug !== '') {
    $sqlMayor .= " AND n.slug = ?";
    $stmtMayor = $conexion->prepare($sqlMayor);
    if ($stmtMayor) {
        $stmtMayor->bind_param("s", $tienda_slug);
        $stmtMayor->execute();
        $resMayor = $stmtMayor->get_result()->fetch_assoc();
        $stmtMayor->close();
        if ((int)($resMayor['total'] ?? 0) > 0) {
            $categories[] = ['id' => 'mayor', 'nombre' => 'Al mayor'];
        }
    }
} else {
    $resMayor = $conexion->query($sqlMayor);
    if ($resMayor && (int)($resMayor->fetch_assoc()['total'] ?? 0) > 0) {
        $categories[] = ['id' => 'mayor', 'nombre' => 'Al mayor'];
    }
}

echo json_encode(['success' => true, 'categories' => $categories]);
?>
