<?php
/**
 * Busca productos del negocio para copiar sus datos en nuevoProducto.php.
 * Devuelve solo los campos que se copian: precio, origen, bulto, porcentaje,
 * proveedor y categoría.
 */
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin sesión']);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$q = mb_substr(trim((string)($_GET['q'] ?? '')), 0, 80, 'UTF-8');

$sql = "SELECT p.id, p.nombre, p.codigo_barras, p.precio_compra,
               p.cantidad_unidades, p.porcentaje, p.origen, p.proveedor,
               p.categoria, c.nombre AS categoria_nombre
          FROM productos p
          LEFT JOIN categorias_producto c ON c.id = p.categoria
         WHERE p.bss_id = ? AND p.activo = 0";

$types = 'i';
$params = [$bss_id];

if ($q !== '') {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo_barras LIKE ?)";
    $like = '%' . $q . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY p.nombre ASC LIMIT 30";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'msg' => 'No se pudo buscar productos']);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id'               => (int)$row['id'],
        'nombre'           => (string)$row['nombre'],
        'codigo'           => (string)($row['codigo_barras'] ?? ''),
        'precio_compra'    => (string)($row['precio_compra'] ?? ''),
        'cantidad_unidades'=> (int)($row['cantidad_unidades'] ?? 1),
        'porcentaje'       => (string)($row['porcentaje'] ?? ''),
        'origen'           => (string)($row['origen'] ?? ''),
        'proveedor'        => (string)($row['proveedor'] ?? ''),
        'categoria_id'     => $row['categoria'] !== null ? (int)$row['categoria'] : null,
        'categoria_nombre' => (string)($row['categoria_nombre'] ?? ''),
    ];
}
$stmt->close();

echo json_encode(['ok' => true, 'data' => $data]);
