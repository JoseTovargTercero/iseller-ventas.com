<?php
require_once('includes/requires.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SESSION['nivel'] != 1 && $_SESSION['nivel'] != 2) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$idProducto  = intval($_GET['id'] ?? 0);
$desde       = $_GET['desde'] ?? date('Y-m-01');
$hasta       = $_GET['hasta'] ?? date('Y-m-d');
$sucursalId  = max(0, intval($_GET['sucursal'] ?? 0));
$usuarioId   = max(0, intval($_GET['usuario'] ?? 0));
$bss_id      = $_SESSION['bss_id'];

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

if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

/* Productos al mayor enlazados: sus ventas también cuentan para el
   producto base. La cantidad se lleva a unidades y a cajas. */
$idsVenta = [(int) $idProducto];
$stmtMayor = $conexion->prepare(
    "SELECT id FROM productos WHERE bss_id = ? AND id_producto_relacionado = ? AND mayor = '1'"
);
$stmtMayor->bind_param('si', $bss_id, $idProducto);
$stmtMayor->execute();
$resMayor = $stmtMayor->get_result();
while ($m = $resMayor->fetch_assoc()) {
    $idsVenta[] = (int) $m['id'];
}
$stmtMayor->close();
$idsVentaSql = implode(',', $idsVenta);

/* ─── Filtros comunes ────────────────────────────────────────────────
   Sucursal y usuario son opcionales (0 = todos).                      */
$filtros = "s.bss_id = ?
            AND oa.product_id IN ($idsVentaSql)
            AND o.created >= ?
            AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
            AND o.status IN (1, 4)";
$tipos   = 'iss';
$params  = [$bss_id, $desde, $hasta];

if ($sucursalId > 0) {
    $filtros .= ' AND o.id_sucursal = ?';
    $tipos   .= 'i';
    $params[] = $sucursalId;
}
if ($usuarioId > 0) {
    $filtros .= ' AND o.usuario = ?';
    $tipos   .= 'i';
    $params[] = $usuarioId;
}

/* ─── Líneas de venta del producto ───────────────────────────────────
   Cada fila es una línea de una orden. Se calculan:
   - unidades: presentación base en unidades.
   - cajas:    bultos/cajas vendidos. Para productos al mayor la
               cantidad ya está en cajas; para el producto base se
               divide entre las unidades que trae cada caja.
   - venta/ganancia: con el descuento al mayor (status = 4).          */
$lineasSql = "
    SELECT
        o.id                    AS orden_id,
        o.created               AS fecha,
        o.status                AS status,
        o.id_sucursal           AS sucursal_id,
        s.nombre                AS sucursal,
        COALESCE(u.id, 0)       AS usuario_id,
        COALESCE(NULLIF(TRIM(u.nombre), ''), 'Sin usuario') AS usuario,
        CASE WHEN COALESCE(p.mayor, '0') = '1'
            THEN oa.quantity * COALESCE(NULLIF(p.cantidad_unidades, 0), 1)
            ELSE oa.quantity
        END AS unidades,
        CASE WHEN COALESCE(p.mayor, '0') = '1'
            THEN oa.quantity
            ELSE oa.quantity / COALESCE(NULLIF(p.cantidad_unidades, 0), 1)
        END AS cajas,
        CASE WHEN o.status = '4'
            THEN oa.precio_venta_dolar * oa.quantity * (1 - COALESCE(o.descontado, 0) / 100)
            ELSE oa.precio_venta_dolar * oa.quantity
        END AS venta,
        CASE WHEN o.status = '4'
            THEN oa.precio_venta_dolar * oa.quantity * (1 - COALESCE(o.descontado, 0) / 100) - oa.precio * oa.quantity
            ELSE oa.precio_venta_dolar * oa.quantity - oa.precio * oa.quantity
        END AS ganancia
    FROM orden o
    JOIN orden_articulos oa ON oa.order_id = o.id
    JOIN sucursales s ON s.id = o.id_sucursal
    LEFT JOIN usuarios u ON u.id = o.usuario
    LEFT JOIN productos p ON p.id = oa.product_id
    WHERE $filtros
";

/* ─── Resumen por sucursal y por usuario ─────────────────────────────
   Una sola pasada: el motor agrupa y suma.                            */
$stmt = $conexion->prepare("
    SELECT
        sucursal_id,
        sucursal,
        usuario_id,
        usuario,
        SUM(cajas)              AS cajas,
        SUM(unidades)           AS unidades,
        SUM(venta)              AS total_venta,
        SUM(ganancia)           AS total_ganancia,
        COUNT(DISTINCT orden_id) AS ventas
    FROM ($lineasSql) l
    GROUP BY sucursal_id, sucursal, usuario_id, usuario
    ORDER BY sucursal ASC, total_venta DESC
");
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

$sucursales = [];
$resultados = [];

while ($row = $res->fetch_assoc()) {
    $sid    = (int) $row['sucursal_id'];
    $cajas  = round((float) $row['cajas'], 2);
    $unid   = round((float) $row['unidades'], 2);
    $total  = round((float) $row['total_venta'], 2);
    $gain   = round((float) $row['total_ganancia'], 2);
    $ventas = (int) $row['ventas'];

    if (!isset($sucursales[$sid])) {
        $sucursales[$sid] = [
            'id'              => $sid,
            'sucursal'        => $row['sucursal'],
            'cajas'           => 0,
            'unidades'        => 0,
            'total'           => 0,
            'ganancia'        => 0,
            'ventas'          => 0,
            'usuarios'        => [],
            'ventas_detalle'  => [],
        ];
    }

    $sucursales[$sid]['cajas']    += $cajas;
    $sucursales[$sid]['unidades'] += $unid;
    $sucursales[$sid]['total']    += $total;
    $sucursales[$sid]['ganancia'] += $gain;
    $sucursales[$sid]['ventas']   += $ventas;

    $usuarioFila = [
        'usuario_id' => (int) $row['usuario_id'],
        'usuario'    => $row['usuario'],
        'cajas'      => $cajas,
        'unidades'   => $unid,
        'total'      => $total,
        'ganancia'   => $gain,
        'ventas'     => $ventas,
    ];
    $sucursales[$sid]['usuarios'][] = $usuarioFila;

    $resultados[] = [
        'sucursal' => $row['sucursal'],
        'usuario'  => $row['usuario'],
        'cantidad' => $unid,
        'cajas'    => $cajas,
        'total'    => $total,
        'ganancia' => $gain,
        'ventas'   => $ventas,
    ];
}

/* ─── Detalle de ventas que incluyeron el producto ───────────────────
   Se agrupa por orden para que cada venta aparezca una sola vez.      */
$stmtV = $conexion->prepare("
    SELECT
        orden_id,
        fecha,
        status,
        sucursal_id,
        sucursal,
        usuario_id,
        usuario,
        SUM(cajas)      AS cajas,
        SUM(unidades)   AS unidades,
        SUM(venta)      AS total_venta,
        SUM(ganancia)   AS total_ganancia
    FROM ($lineasSql) l
    GROUP BY orden_id, fecha, status, sucursal_id, sucursal, usuario_id, usuario
    ORDER BY fecha DESC, orden_id DESC
    LIMIT 500
");
$stmtV->bind_param($tipos, ...$params);
$stmtV->execute();
$resV = $stmtV->get_result();
$stmtV->close();

$ventasLista = [];
while ($row = $resV->fetch_assoc()) {
    $sid = (int) $row['sucursal_id'];
    $venta = [
        'id'       => (int) $row['orden_id'],
        'fecha'    => $row['fecha'],
        'status'   => (int) $row['status'],
        'usuario'  => $row['usuario'],
        'cajas'    => round((float) $row['cajas'], 2),
        'unidades' => round((float) $row['unidades'], 2),
        'total'    => round((float) $row['total_venta'], 2),
        'ganancia' => round((float) $row['total_ganancia'], 2),
    ];

    if (isset($sucursales[$sid])) {
        $sucursales[$sid]['ventas_detalle'][] = $venta;
    }
    $ventasLista[] = $venta + ['sucursal' => $row['sucursal'], 'sucursal_id' => $sid];
}

foreach ($sucursales as &$suc) {
    $suc['cajas']    = round($suc['cajas'], 2);
    $suc['unidades'] = round($suc['unidades'], 2);
    $suc['total']    = round($suc['total'], 2);
    $suc['ganancia'] = round($suc['ganancia'], 2);
    $suc['ventas_total']     = $suc['ventas'];
    $suc['ventas_mostradas'] = count($suc['ventas_detalle']);
}
unset($suc);

echo json_encode([
    'desde'        => $desde,
    'hasta'        => $hasta,
    'sucursal'     => $sucursalId,
    'usuario'      => $usuarioId,
    'sucursales'   => array_values($sucursales),
    'ventas'       => $ventasLista,
    'ventas_total' => count($ventasLista),
    'resultados'   => $resultados,
]);
