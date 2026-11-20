<?php
/**
 * Movimiento general de un producto: compras, ventas, traslados y mermas.
 * Devuelve eventos individuales con fecha/hora y sucursal para pintarlos en
 * una línea de tiempo tipo git (una columna por sucursal).
 *
 * GET: id, desde (Y-m-d), hasta (Y-m-d)
 */
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once('../../configurar/_stock_traslados.php');

header('Content-Type: application/json; charset=utf-8');

if (!in_array((int) ($_SESSION['nivel'] ?? 0), [1, 2], true)) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$bssId      = (int) ($_SESSION['bss_id'] ?? 0);
$productoId = (int) ($_GET['id'] ?? 0);
$desde      = trim((string) ($_GET['desde'] ?? ''));
$hasta      = trim((string) ($_GET['hasta'] ?? ''));

if ($productoId <= 0 || $bssId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Producto inválido']);
    exit;
}

$tsDesde = $desde !== '' ? strtotime($desde) : strtotime('-29 days');
$tsHasta = $hasta !== '' ? strtotime($hasta) : time();
if ($tsDesde === false || $tsHasta === false) {
    echo json_encode(['ok' => false, 'error' => 'Fechas inválidas']);
    exit;
}
$desde = date('Y-m-d', $tsDesde);
$hasta = date('Y-m-d', $tsHasta);
if ($desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

/* ── Producto y su familia (base + presentaciones al mayor) ─────────── */
$stmt = $conexion->prepare(
    "SELECT id, nombre, unidad, cantidad_unidades, mayor, id_producto_relacionado
     FROM productos WHERE id = ? AND bss_id = ? LIMIT 1"
);
$stmt->bind_param('ii', $productoId, $bssId);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo json_encode(['ok' => false, 'error' => 'Producto no encontrado']);
    exit;
}

// Misma agrupación que la ficha: el producto consultado y sus presentaciones
// al mayor enlazadas (no se mezclan variaciones/despiece de otros productos).
$baseId = (int) $producto['id'];

$stmt = $conexion->prepare(
    "SELECT id, nombre, unidad, cantidad_unidades, mayor
     FROM productos
     WHERE bss_id = ? AND (id = ? OR (id_producto_relacionado = ? AND mayor = '1'))"
);
$stmt->bind_param('iii', $bssId, $baseId, $baseId);
$stmt->execute();
$resGrupo = $stmt->get_result();
$stmt->close();

$metaProducto = [];
$baseProducto = null;
while ($fila = $resGrupo->fetch_assoc()) {
    $pid = (int) $fila['id'];
    $esMayor = ((string) ($fila['mayor'] ?? '0')) === '1';
    $unidades = max(1.0, (float) ($fila['cantidad_unidades'] ?? 1));
    $metaProducto[$pid] = [
        'factor' => $esMayor ? $unidades : 1.0,
        'mayor' => $esMayor,
    ];
    if ($pid === $baseId) {
        $baseProducto = $fila;
    }
}

if (!$metaProducto) {
    $metaProducto[(int) $producto['id']] = [
        'factor' => ((string) ($producto['mayor'] ?? '0')) === '1'
            ? max(1.0, (float) ($producto['cantidad_unidades'] ?? 1))
            : 1.0,
        'mayor' => ((string) ($producto['mayor'] ?? '0')) === '1',
    ];
}

$productoSalida = $baseProducto ?: $producto;
$unidad = trim((string) ($productoSalida['unidad'] ?? 'UND')) ?: 'UND';
$ids = array_map('intval', array_keys($metaProducto));
$idsSql = implode(',', $ids);

/* ── Sucursales del negocio (todas, aunque no tengan movimientos) ───── */
$sucursales = [];
$stmt = $conexion->prepare(
    "SELECT id, nombre, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC"
);
if ($stmt) {
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $resSuc = $stmt->get_result();
    while ($fila = $resSuc->fetch_assoc()) {
        $sucursales[] = [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'principal' => (int) ($fila['principal'] ?? 0) === 1,
        ];
    }
    $stmt->close();
}
$sucursalNombre = [];
foreach ($sucursales as $suc) {
    $sucursalNombre[$suc['id']] = $suc['nombre'];
}

$eventos = [];
$factorDe = static function (int $pid) use ($metaProducto): float {
    return (float) ($metaProducto[$pid]['factor'] ?? 1.0);
};
$nombreSucursal = static function ($id) use ($sucursalNombre): string {
    $id = (int) $id;
    return $sucursalNombre[$id] ?? ('Sucursal #' . $id);
};
$normalizarFecha = static function ($fecha): string {
    $fecha = trim((string) $fecha);
    if ($fecha === '' || strpos($fecha, '0000-00-00') === 0) {
        return '';
    }
    if (strlen($fecha) <= 10) {
        return $fecha . ' 00:00:00';
    }
    return $fecha;
};

/* ── Compras ────────────────────────────────────────────────────────── */
$stmt = $conexion->prepare(
    "SELECT COALESCE(c.created_at, STR_TO_DATE(c.fecha, '%d-%m-%Y %h:%i %p')) AS fecha_evento,
            c.cod, c.cantidad, c.id_sucursal, c.user, c.status
     FROM compras c
     WHERE c.bss_id = ?
       AND c.cod IN ($idsSql)
       AND (
            (c.created_at IS NOT NULL AND c.created_at >= ? AND c.created_at < DATE_ADD(?, INTERVAL 1 DAY))
            OR (c.created_at IS NULL AND c.dia BETWEEN ? AND ?)
       )
     ORDER BY fecha_evento DESC"
);
if ($stmt) {
    $stmt->bind_param('issss', $bssId, $desde, $hasta, $desde, $hasta);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $fecha = $normalizarFecha($fila['fecha_evento']);
        $cantidad = (float) $fila['cantidad'];
        if ($fecha === '' || $cantidad <= 0) {
            continue;
        }
        $pid = (int) $fila['cod'];
        $cantidad *= $factorDe($pid);
        $eventos[] = [
            'id' => 'compra-' . count($eventos) . '-' . md5($fecha . $pid . $cantidad),
            'fecha' => $fecha,
            'tipo' => 'compra',
            'subtipo' => 'Compra',
            'sucursal_id' => (int) $fila['id_sucursal'],
            'sucursal' => $nombreSucursal($fila['id_sucursal']),
            'cantidad' => round($cantidad, 4),
            'signo' => 1,
            'usuario' => (string) ($fila['user'] ?? ''),
            'detalle' => 'Entrada por compra',
            'referencia' => '',
        ];
    }
    $stmt->close();
}

/* ── Ventas ─────────────────────────────────────────────────────────── */
$stmt = $conexion->prepare(
    "SELECT o.id AS orden_id, o.created AS fecha_evento, o.id_sucursal, o.status,
            COALESCE(o.usuario, 0) AS usuario_id,
            COALESCE(NULLIF(TRIM(u.nombre), ''), 'Sin usuario') AS usuario,
            SUM(oa.quantity * CASE WHEN COALESCE(p.mayor, '0') = '1'
                    THEN GREATEST(COALESCE(p.cantidad_unidades, 1), 1)
                    ELSE 1 END) AS cantidad,
            SUM(CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - COALESCE(o.descontado, 0) / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity END) AS venta_usd,
            COUNT(*) AS lineas
     FROM orden o
     INNER JOIN orden_articulos oa ON oa.order_id = o.id
     LEFT JOIN productos p ON p.id = oa.product_id
     LEFT JOIN usuarios u ON u.id = o.usuario
     WHERE o.bss_id = ?
       AND oa.product_id IN ($idsSql)
       AND o.status IN (1, 2, 4)
       AND o.created >= ?
       AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
     GROUP BY o.id, o.created, o.id_sucursal, o.status, o.usuario, u.nombre
     ORDER BY o.created DESC"
);
if ($stmt) {
    $stmt->bind_param('iss', $bssId, $desde, $hasta);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $fecha = $normalizarFecha($fila['fecha_evento']);
        $cantidad = (float) $fila['cantidad'];
        if ($fecha === '' || $cantidad <= 0) {
            continue;
        }
        $eventos[] = [
            'id' => 'venta-' . (int) $fila['orden_id'],
            'orden_id' => (int) $fila['orden_id'],
            'fecha' => $fecha,
            'tipo' => 'venta',
            'subtipo' => 'Venta',
            'sucursal_id' => (int) $fila['id_sucursal'],
            'sucursal' => $nombreSucursal($fila['id_sucursal']),
            'cantidad' => round($cantidad, 4),
            'monto' => round((float) $fila['venta_usd'], 2),
            'signo' => -1,
            'usuario_id' => (int) $fila['usuario_id'],
            'usuario' => (string) $fila['usuario'],
            'detalle' => 'Venta #' . (int) $fila['orden_id'],
            'referencia' => 'detallesVenta.php?id=' . (int) $fila['orden_id'],
        ];
    }
    $stmt->close();
}

/* ── Mermas y ajustes de salida ─────────────────────────────────────── */
$stmt = $conexion->prepare(
    "SELECT id, COALESCE(created_at, CONCAT(fecha, ' 00:00:00')) AS fecha_evento,
            producto_id, cantidad, id_sucursal, tipo, motivo, razon, usuario_nombre
     FROM stock_ajustes
     WHERE bss_id = ?
       AND producto_id IN ($idsSql)
       AND estado = 'ACTIVO'
       AND direccion = 'SALIDA'
       AND fecha BETWEEN ? AND ?
     ORDER BY fecha_evento DESC"
);
if ($stmt) {
    $stmt->bind_param('iss', $bssId, $desde, $hasta);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $fecha = $normalizarFecha($fila['fecha_evento']);
        $cantidad = (float) $fila['cantidad'];
        if ($fecha === '' || $cantidad <= 0) {
            continue;
        }
        $pid = (int) $fila['producto_id'];
        $cantidad *= $factorDe($pid);
        $esAjuste = (string) ($fila['tipo'] ?? '') === 'AJUSTE';
        $eventos[] = [
            'id' => 'merma-' . (int) $fila['id'],
            'fecha' => $fecha,
            'tipo' => 'merma',
            'subtipo' => $esAjuste ? 'Ajuste' : 'Merma',
            'sucursal_id' => (int) $fila['id_sucursal'],
            'sucursal' => $nombreSucursal($fila['id_sucursal']),
            'cantidad' => round($cantidad, 4),
            'signo' => -1,
            'usuario' => (string) ($fila['usuario_nombre'] ?? ''),
            'detalle' => (string) ($fila['motivo'] ?? ''),
            'razon' => (string) ($fila['razon'] ?? ''),
            'referencia' => '',
        ];
    }
    $stmt->close();
}

/* ── Asignaciones aplicadas (también mueven stock entre sucursales) ──── */
$stmt = $conexion->prepare(
    "SELECT a.id AS asignacion_id, a.numero_documento,
            COALESCE(a.aplicado_at, a.created_at, CONCAT(a.fecha, ' 00:00:00')) AS fecha_evento,
            d.producto_id, d.cantidad,
            a.sucursal_origen, a.sucursal_destino,
            COALESCE(NULLIF(TRIM(u.nombre), ''), 'Sin usuario') AS usuario
     FROM asignaciones_sucursales a
     INNER JOIN asignaciones_sucursales_detalles d ON d.asignacion_id = a.id
     LEFT JOIN usuarios u ON u.id = a.usuario_id
     WHERE a.bss_id = ?
       AND a.estado = 'APLICADO'
       AND a.descuenta_origen = 1
       AND a.suma_destino = 1
       AND d.producto_id IN ($idsSql)
       AND COALESCE(a.aplicado_at, a.created_at, CONCAT(a.fecha, ' 00:00:00')) >= ?
       AND COALESCE(a.aplicado_at, a.created_at, CONCAT(a.fecha, ' 00:00:00')) < DATE_ADD(?, INTERVAL 1 DAY)
     ORDER BY fecha_evento DESC"
);
if ($stmt) {
    $stmt->bind_param('iss', $bssId, $desde, $hasta);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $fecha = $normalizarFecha($fila['fecha_evento']);
        $cantidad = (float) $fila['cantidad'];
        if ($fecha === '' || $cantidad <= 0) {
            continue;
        }
        $pid = (int) $fila['producto_id'];
        $cantidad *= $factorDe($pid);
        $origenId = (int) $fila['sucursal_origen'];
        $destinoId = (int) $fila['sucursal_destino'];
        $documento = trim((string) ($fila['numero_documento'] ?? ''));
        $eventos[] = [
            'id' => 'asignacion-' . (int) $fila['asignacion_id'] . '-' . $pid,
            'fecha' => $fecha,
            'tipo' => 'traslado',
            'subtipo' => 'Asignación',
            'sucursal_id' => null,
            'sucursal' => '',
            'cantidad' => round($cantidad, 4),
            'signo' => 0,
            'usuario' => (string) $fila['usuario'],
            'detalle' => 'Asignación' . ($documento !== '' ? ' ' . $documento : ''),
            'referencia' => '',
            'origen_id' => $origenId,
            'destino_id' => $destinoId,
            'origen' => $nombreSucursal($origenId),
            'destino' => $nombreSucursal($destinoId),
        ];
    }
    $stmt->close();
}

/* ── Traslados manuales entre sucursales ────────────────────────────── */
if (st_asegurar_tabla($conexion)) {
    $stmt = $conexion->prepare(
        "SELECT id, created_at AS fecha_evento, id_producto, cantidad,
                sucursal_origen, sucursal_destino, usuario_nombre
         FROM stock_traslados
         WHERE bss_id = ?
           AND id_producto IN ($idsSql)
           AND created_at >= ?
           AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
         ORDER BY created_at DESC"
    );
    if ($stmt) {
        $stmt->bind_param('iss', $bssId, $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $fecha = $normalizarFecha($fila['fecha_evento']);
            $cantidad = (float) $fila['cantidad'];
            if ($fecha === '' || $cantidad <= 0) {
                continue;
            }
            $pid = (int) $fila['id_producto'];
            $cantidad *= $factorDe($pid);
            $origenId = (int) $fila['sucursal_origen'];
            $destinoId = (int) $fila['sucursal_destino'];
            $eventos[] = [
                'id' => 'traslado-' . (int) $fila['id'],
                'fecha' => $fecha,
                'tipo' => 'traslado',
                'subtipo' => 'Traslado',
                'sucursal_id' => null,
                'sucursal' => '',
                'cantidad' => round($cantidad, 4),
                'signo' => 0,
                'usuario' => (string) ($fila['usuario_nombre'] ?? ''),
                'detalle' => 'Traslado entre sucursales',
                'referencia' => '',
                'origen_id' => $origenId,
                'destino_id' => $destinoId,
                'origen' => $nombreSucursal($origenId),
                'destino' => $nombreSucursal($destinoId),
            ];
        }
        $stmt->close();
    }
}

/* ── Orden final: fecha/hora descendente y tipo como desempate ──────── */
$ordenTipo = ['compra' => 0, 'traslado' => 1, 'venta' => 2, 'merma' => 3];
usort($eventos, static function ($a, $b) use ($ordenTipo) {
    $cmp = strcmp((string) $b['fecha'], (string) $a['fecha']);
    if ($cmp !== 0) {
        return $cmp;
    }
    $ta = $ordenTipo[$a['tipo']] ?? 9;
    $tb = $ordenTipo[$b['tipo']] ?? 9;
    if ($ta !== $tb) {
        return $ta <=> $tb;
    }
    return strcmp((string) $b['id'], (string) $a['id']);
});

$totalEventos = count($eventos);
$truncado = $totalEventos > 5000;
if ($truncado) {
    $eventos = array_slice($eventos, 0, 5000);
}

echo json_encode([
    'ok' => true,
    'producto' => [
        'id' => $baseId,
        'nombre' => (string) ($productoSalida['nombre'] ?? $producto['nombre']),
        'unidad' => $unidad,
        'cantidad_unidades' => (float) ($productoSalida['cantidad_unidades'] ?? 1),
        'mayor' => ((string) ($productoSalida['mayor'] ?? '0')) === '1',
    ],
    'desde' => $desde,
    'hasta' => $hasta,
    'sucursales' => $sucursales,
    'eventos' => $eventos,
    'total_eventos' => $totalEventos,
    'truncado' => $truncado,
], JSON_UNESCAPED_UNICODE);
