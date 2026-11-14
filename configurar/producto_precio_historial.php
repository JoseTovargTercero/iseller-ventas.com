<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_productos_precio_compra.php');

header('Content-Type: application/json; charset=utf-8');

$bss_id = (int) ($_SESSION['bss_id'] ?? 0);
$nivel  = (int) ($_SESSION['nivel'] ?? 0);
$productoId = (int) ($_GET['producto_id'] ?? 0);

if (!$bss_id || $productoId <= 0 || ($nivel !== 1 && $nivel !== 2)) {
    echo json_encode(['ok' => false, 'message' => 'Solicitud no válida.']);
    exit;
}

$stmt = $conexion->prepare(
    "SELECT id, nombre, mayor, id_producto_relacionado
     FROM productos WHERE id = ? AND bss_id = ? LIMIT 1"
);
$stmt->bind_param('ii', $productoId, $bss_id);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo json_encode(['ok' => false, 'message' => 'Producto no encontrado.']);
    exit;
}

producto_asegurar_historial_precio($conexion);

$ids = [(int) $producto['id']];
if (!empty($producto['id_producto_relacionado'])) {
    $ids[] = (int) $producto['id_producto_relacionado'];
}
$ids = array_values(array_unique($ids));
$in = implode(',', $ids);

$items = [];
$stmt = $conexion->prepare(
    "SELECT h.*, u.nombre AS usuario_actual
     FROM productos_precio_historial h
     LEFT JOIN usuarios u ON u.id = h.usuario_id
     WHERE h.bss_id = ? AND h.producto_id IN ($in)
     ORDER BY h.created_at DESC, h.id DESC
     LIMIT 100"
);
if ($stmt) {
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'fecha' => $row['created_at'],
            'producto' => $row['producto_nombre'],
            'es_mayor' => (int) $row['es_mayor'] === 1,
            'precio_anterior' => (float) $row['precio_anterior'],
            'precio_nuevo' => (float) $row['precio_nuevo'],
            'origen' => $row['origen'],
            'usuario' => $row['usuario_actual'] ?: $row['usuario_nombre'],
        ];
    }
    $stmt->close();
}

echo json_encode([
    'ok' => true,
    'producto' => $producto['nombre'],
    'items' => $items,
], JSON_UNESCAPED_UNICODE);
