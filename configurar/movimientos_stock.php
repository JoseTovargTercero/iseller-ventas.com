<?php
/**
 * Listado de traslados agrupados por cargamento.
 *
 * Cada cargamento incluye sus artículos con las cantidades transferidas.
 * Las filas antiguas sin cargamento se muestran individualmente.
 */
require_once(__DIR__ . '/configuracion.php');
require_once(__DIR__ . '/session.php');
require_once(__DIR__ . '/_stock_traslados.php');
require_once(__DIR__ . '/_stock_cargamentos.php');

header('Content-Type: application/json; charset=utf-8');

if (!in_array((int) ($_SESSION['nivel'] ?? 0), [1, 2], true)) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$bssId         = (int) ($_SESSION['bss_id'] ?? 0);
$productoId    = (int) ($_GET['producto'] ?? 0);
$origen        = (int) ($_GET['origen'] ?? 0);
$destino       = (int) ($_GET['destino'] ?? 0);
$cargamentoId  = (int) preg_replace('/\D+/', '', (string) ($_GET['cargamento'] ?? ''));

if ($bssId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Negocio inválido']);
    exit;
}

if (!st_asegurar_tabla($conexion)) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo preparar la tabla de traslados.']);
    exit;
}
if (!sc_asegurar_tabla($conexion)) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo preparar la tabla de cargamentos.']);
    exit;
}

$where = ['T.bss_id = ?'];
$params = [$bssId];
$types = 'i';

if ($productoId > 0) {
    $where[] = 'T.id_producto = ?';
    $params[] = $productoId;
    $types .= 'i';
}
if ($origen > 0) {
    $where[] = 'T.sucursal_origen = ?';
    $params[] = $origen;
    $types .= 'i';
}
if ($destino > 0) {
    $where[] = 'T.sucursal_destino = ?';
    $params[] = $destino;
    $types .= 'i';
}
if ($cargamentoId > 0) {
    $where[] = 'T.cargamento_id = ?';
    $params[] = $cargamentoId;
    $types .= 'i';
}

$sql = "SELECT
            T.id,
            T.cargamento_id,
            T.created_at,
            T.cantidad,
            T.id_producto,
            T.producto_nombre,
            T.usuario_nombre,
            C.motivo,
            C.created_at AS cargamento_fecha,
            C.usuario_nombre AS cargamento_usuario,
            COALESCE(so.nombre, '—') AS origen,
            COALESCE(sd.nombre, '—') AS destino,
            COALESCE(NULLIF(p.unidad, ''), 'UND') AS unidad
        FROM stock_traslados T
        LEFT JOIN stock_cargamentos C ON C.id = T.cargamento_id AND C.bss_id = T.bss_id
        LEFT JOIN sucursales so ON so.id = T.sucursal_origen AND so.bss_id = T.bss_id
        LEFT JOIN sucursales sd ON sd.id = T.sucursal_destino AND sd.bss_id = T.bss_id
        LEFT JOIN productos p ON p.id = T.id_producto AND p.bss_id = T.bss_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY COALESCE(C.created_at, T.created_at) DESC, T.id DESC
        LIMIT 1000";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'No se pudieron consultar los traslados.']);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$grupos = [];
while ($fila = $res->fetch_assoc()) {
    $cargoId = $fila['cargamento_id'] !== null ? (int) $fila['cargamento_id'] : null;
    $grupoId = $cargoId !== null ? 'c' . $cargoId : 't' . (int) $fila['id'];

    if (!isset($grupos[$grupoId])) {
        $grupos[$grupoId] = [
            'grupo_id'        => $grupoId,
            'cargamento_id'    => $cargoId,
            'fecha'            => (string) ($cargoId !== null && !empty($fila['cargamento_fecha'])
                ? $fila['cargamento_fecha']
                : $fila['created_at']),
            'origen'           => (string) $fila['origen'],
            'destino'          => (string) $fila['destino'],
            'usuario'          => (string) ($cargoId !== null && !empty($fila['cargamento_usuario'])
                ? $fila['cargamento_usuario']
                : $fila['usuario_nombre']),
            'motivo'           => (string) ($cargoId !== null ? ($fila['motivo'] ?? '') : ''),
            'total_productos'  => 0,
            'total_unidades'   => 0.0,
            'items'            => [],
        ];
    }

    $productoIdFila = (int) $fila['id_producto'];
    if (!isset($grupos[$grupoId]['items'][$productoIdFila])) {
        $grupos[$grupoId]['items'][$productoIdFila] = [
            'producto_id' => $productoIdFila,
            'producto'    => (string) $fila['producto_nombre'],
            'unidad'      => (string) $fila['unidad'],
            'cantidad'    => 0.0,
        ];
    }

    $cantidad = (float) $fila['cantidad'];
    $grupos[$grupoId]['items'][$productoIdFila]['cantidad'] += $cantidad;
    $grupos[$grupoId]['total_unidades'] += $cantidad;
}
$stmt->close();

$cargamentos = [];
foreach ($grupos as $grupo) {
    $items = array_values($grupo['items']);
    usort($items, static function ($a, $b) {
        return strcmp((string) $a['producto'], (string) $b['producto']);
    });

    foreach ($items as &$item) {
        $item['cantidad'] = round((float) $item['cantidad'], 4);
    }
    unset($item);

    $grupo['items'] = $items;
    $grupo['total_productos'] = count($items);
    $grupo['total_unidades'] = round((float) $grupo['total_unidades'], 4);
    $cargamentos[] = $grupo;
}

echo json_encode([
    'ok' => true,
    'cargamentos' => $cargamentos,
]);
