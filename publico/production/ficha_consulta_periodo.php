<?php
require_once('includes/requires.php');

header('Content-Type: application/json');

if ($_SESSION['nivel'] != 1 && $_SESSION['nivel'] != 2) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$idProducto = intval($_GET['id'] ?? 0);
$desde      = $_GET['desde'] ?? date('Y-m-01');
$hasta      = $_GET['hasta'] ?? date('Y-m-d');
$bss_id     = $_SESSION['bss_id'];

if (!$idProducto) {
    echo json_encode(['error' => 'Producto inválido']);
    exit;
}

// Sanitize & validate dates
$desde = date('Y-m-d', strtotime($desde));
$hasta = date('Y-m-d', strtotime($hasta));

if ($desde === '1970-01-01' || $hasta === '1970-01-01') {
    echo json_encode(['error' => 'Fechas inválidas']);
    exit;
}

/* ─── Una sola query ─────────────────────────────────────────────────
   JOIN entre sucursales → orden → orden_articulos.
   GROUP BY sucursal; el motor calcula ventas, ganancia y cantidad
   directamente sin traer filas al PHP ni iterar en bucles anidados.

   El descuento (status = 4 = venta al mayor con porcentaje) se
   aplica con CASE WHEN dentro del SUM.
   ─────────────────────────────────────────────────────────────────── */
$stmt = $conexion->prepare(
    "SELECT
        s.nombre                                    AS sucursal,
        SUM(oa.quantity)                            AS cantidad,
        SUM(
            CASE WHEN o.status = '4'
                THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                ELSE oa.precio_venta_dolar * oa.quantity
            END
        )                                           AS total_venta,
        SUM(
            CASE WHEN o.status = '4'
                THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                ELSE oa.precio_venta_dolar * oa.quantity
            END - oa.precio * oa.quantity
        )                                           AS total_ganancia
     FROM sucursales s
     JOIN orden       o  ON  o.id_sucursal = s.id
     JOIN orden_articulos oa ON oa.order_id = o.id
     WHERE s.bss_id       = ?
       AND oa.product_id  = ?
       AND o.modified    >= ?
       AND o.modified    <= ?
       AND o.status      IN (1, 4)
     GROUP BY s.id, s.nombre
     ORDER BY s.nombre"
);
$stmt->bind_param('siss', $bss_id, $idProducto, $desde, $hasta);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

$resultados = [];
while ($row = $res->fetch_assoc()) {
    $resultados[] = [
        'sucursal' => $row['sucursal'],
        'cantidad' => intval($row['cantidad']),
        'total'    => round(floatval($row['total_venta']),    2),
        'ganancia' => round(floatval($row['total_ganancia']), 2),
    ];
}

echo json_encode([
    'desde'      => $desde,
    'hasta'      => $hasta,
    'resultados' => $resultados,
]);
