<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');

header('Content-Type: application/json; charset=utf-8');

if (!in_array((int) ($_SESSION['nivel'] ?? 0), [1, 2], true)) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$productoId = (int) ($_GET['id'] ?? 0);
$dias = (int) ($_GET['dias'] ?? 30);
if (!in_array($dias, [7, 30, 90, 365], true)) {
    $dias = 30;
}

if ($productoId <= 0 || $bssId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Producto inválido']);
    exit;
}

$stmt = $conexion->prepare("SELECT id, nombre, unidad FROM productos WHERE id = ? AND bss_id = ? LIMIT 1");
$stmt->bind_param('ii', $productoId, $bssId);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo json_encode(['ok' => false, 'error' => 'Producto no encontrado']);
    exit;
}

$unidad = trim((string) ($producto['unidad'] ?? 'UND')) ?: 'UND';
$hoy = date('Y-m-d');
$desde = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
$productoIdTexto = (string) $productoId;

/* Stock actual total del producto en todas las sucursales. */
$stmt = $conexion->prepare("SELECT COALESCE(SUM(stock), 0) AS total FROM stock WHERE id_producto = ? AND bss_id = ?");
$stmt->bind_param('ii', $productoId, $bssId);
$stmt->execute();
$stockActual = (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$eventos = [];

/* Entradas: cada compra con su hora exacta. */
$stmt = $conexion->prepare(
    "SELECT COALESCE(created_at, STR_TO_DATE(fecha, '%d-%m-%Y %h:%i %p')) AS fecha_evento,
            CAST(cantidad AS DECIMAL(15,4)) AS cantidad
     FROM compras
     WHERE bss_id = ? AND cod = ? AND dia BETWEEN ? AND ?
       AND (created_at IS NOT NULL OR fecha IS NOT NULL)"
);
if ($stmt) {
    $stmt->bind_param('isss', $bssId, $productoIdTexto, $desde, $hoy);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $cantidad = (float) $fila['cantidad'];
        if ($fila['fecha_evento'] && $cantidad > 0) {
            $eventos[] = [
                'fecha_evento' => $fila['fecha_evento'],
                'tipo' => 'compras',
                'cantidad' => round($cantidad, 2),
            ];
        }
    }
    $stmt->close();
}

/* Salidas por ventas: todas las ventas del mismo día forman un solo evento. */
$stmt = $conexion->prepare(
    "SELECT MAX(o.created) AS fecha_evento, SUM(oa.quantity) AS cantidad
     FROM orden o
     INNER JOIN orden_articulos oa ON oa.order_id = o.id
     WHERE oa.product_id = ? AND o.bss_id = ? AND oa.bss_id = ?
       AND o.status IN (1, 2, 3, 4)
       AND o.created >= ? AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
     GROUP BY DATE(o.created)"
);
if ($stmt) {
    $stmt->bind_param('iiiss', $productoId, $bssId, $bssId, $desde, $hoy);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $cantidad = (float) $fila['cantidad'];
        if ($fila['fecha_evento'] && $cantidad > 0) {
            $eventos[] = [
                'fecha_evento' => $fila['fecha_evento'],
                'tipo' => 'ventas',
                'cantidad' => round($cantidad, 2),
            ];
        }
    }
    $stmt->close();
}

/* Salidas por mermas: cada ajuste con su hora de registro. */
$stmt = $conexion->prepare(
    "SELECT created_at AS fecha_evento, cantidad
     FROM stock_ajustes
     WHERE producto_id = ? AND bss_id = ?
       AND tipo = 'MERMA' AND direccion = 'SALIDA' AND estado = 'ACTIVO'
       AND fecha BETWEEN ? AND ?"
);
if ($stmt) {
    $stmt->bind_param('iiss', $productoId, $bssId, $desde, $hoy);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $cantidad = (float) $fila['cantidad'];
        if ($fila['fecha_evento'] && $cantidad > 0) {
            $eventos[] = [
                'fecha_evento' => $fila['fecha_evento'],
                'tipo' => 'mermas',
                'cantidad' => round($cantidad, 2),
            ];
        }
    }
    $stmt->close();
}

$ordenTipo = ['compras' => 0, 'ventas' => 1, 'mermas' => 2];
usort($eventos, function ($a, $b) use ($ordenTipo) {
    $cmp = strcmp((string) $a['fecha_evento'], (string) $b['fecha_evento']);
    if ($cmp !== 0) {
        return $cmp;
    }
    return ($ordenTipo[$a['tipo']] ?? 9) <=> ($ordenTipo[$b['tipo']] ?? 9);
});

$neto = 0.0;
foreach ($eventos as $evento) {
    $neto += $evento['tipo'] === 'compras' ? $evento['cantidad'] : -$evento['cantidad'];
}
$stockInicial = round($stockActual - $neto, 2);

echo json_encode([
    'ok' => true,
    'stock_actual' => round($stockActual, 2),
    'stock_inicial' => $stockInicial,
    'eventos' => $eventos,
    'unidad' => $unidad,
    'desde' => $desde,
    'hasta' => $hoy,
    'dias' => $dias,
]);
