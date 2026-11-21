<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once('../../configurar/_stock_traslados.php');

header('Content-Type: application/json; charset=utf-8');

if (!in_array((int) ($_SESSION['nivel'] ?? 0), [1, 2], true)) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$productoId = (int) ($_GET['id'] ?? 0);
$dias = (int) ($_GET['dias'] ?? 30);
$sucursalId = (int) ($_GET['sucursal'] ?? 0);
if (!in_array($dias, [7, 30, 90, 365], true)) {
    $dias = 30;
}

if ($productoId <= 0 || $bssId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Producto inválido']);
    exit;
}

$stmt = $conexion->prepare("SELECT id, nombre, unidad, cantidad_unidades, mayor, id_producto_relacionado FROM productos WHERE id = ? AND bss_id = ? LIMIT 1");
$stmt->bind_param('ii', $productoId, $bssId);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo json_encode(['ok' => false, 'error' => 'Producto no encontrado']);
    exit;
}

$baseId = (int) $producto['id'];

/* ── Producto base y presentaciones al mayor enlazadas ────────────────
   El movimiento de stock de una presentación impacta la existencia del
   producto base, por eso el gráfico trabaja con toda la familia.        */
$stmt = $conexion->prepare(
    "SELECT id, nombre, unidad, cantidad_unidades, mayor, id_producto_relacionado
     FROM productos
     WHERE bss_id = ? AND (id = ? OR (id_producto_relacionado = ? AND mayor = '1'))"
);
$stmt->bind_param('iii', $bssId, $baseId, $baseId);
$stmt->execute();
$resGrupo = $stmt->get_result();
$stmt->close();

$metaProducto = [];
$productoSalida = $producto;
while ($fila = $resGrupo->fetch_assoc()) {
    $pid = (int) $fila['id'];
    $esMayor = ((string) ($fila['mayor'] ?? '0')) === '1';
    $metaProducto[$pid] = [
        'factor' => $esMayor ? max(1.0, (float) ($fila['cantidad_unidades'] ?? 1)) : 1.0,
        'mayor' => $esMayor,
        'relacionado' => (int) ($fila['id_producto_relacionado'] ?? 0),
    ];
    if ($pid === $baseId) {
        $productoSalida = $fila;
    }
}

if (!$metaProducto) {
    $metaProducto[$baseId] = [
        'factor' => ((string) ($producto['mayor'] ?? '0')) === '1'
            ? max(1.0, (float) ($producto['cantidad_unidades'] ?? 1))
            : 1.0,
        'mayor' => ((string) ($producto['mayor'] ?? '0')) === '1',
        'relacionado' => (int) ($producto['id_producto_relacionado'] ?? 0),
    ];
}

$unidad = trim((string) ($productoSalida['unidad'] ?? 'UND')) ?: 'UND';
$ids = array_map('intval', array_keys($metaProducto));
$idsSql = implode(',', $ids);

/* ── Sucursales y sucursal seleccionada ───────────────────────────────
   El gráfico nunca es general: si no llega una sucursal, se usa la
   principal (o la primera) por defecto.                                */
$sucursales = [];
$stmt = $conexion->prepare(
    "SELECT id, nombre, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, id ASC"
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

if (!$sucursales) {
    echo json_encode(['ok' => false, 'error' => 'No hay sucursales registradas']);
    exit;
}

$sucursalSeleccionada = null;
foreach ($sucursales as $suc) {
    if ((int) $suc['id'] === $sucursalId) {
        $sucursalSeleccionada = $suc;
        break;
    }
}
if (!$sucursalSeleccionada) {
    $sucursalSeleccionada = $sucursales[0];
}
$sucursalId = (int) $sucursalSeleccionada['id'];

/* ── Stock actual de la sucursal seleccionada ─────────────────────────
   En las presentaciones al mayor la existencia real vive en el producto
   base enlazado; se suman los IDs sin duplicarlos.                     */
$stockIds = [];
foreach ($metaProducto as $pid => $meta) {
    $stockId = !empty($meta['mayor']) ? (int) ($meta['relacionado'] ?? 0) : 0;
    if ($stockId <= 0) {
        $stockId = (int) $pid;
    }
    if ($stockId > 0) {
        $stockIds[$stockId] = true;
    }
}
$stockIdsSql = implode(',', array_keys($stockIds));

$stockActual = 0.0;
if ($stockIdsSql !== '') {
    $stmt = $conexion->prepare(
        "SELECT COALESCE(SUM(CAST(stock AS DECIMAL(15,4))), 0) AS total
         FROM stock
         WHERE bss_id = ? AND id_sucursal = ? AND id_producto IN ($stockIdsSql)"
    );
    if ($stmt) {
        $stmt->bind_param('ii', $bssId, $sucursalId);
        $stmt->execute();
        $stockActual = (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
    }
}

$hoy = date('Y-m-d');
$desde = date('Y-m-d', strtotime('-' . ($dias - 1) . ' days'));
$eventos = [];

/* ── Compras (incluye compras con factura) ────────────────────────────
   Las compras con factura también se guardan en la tabla compras con
   factura_id, así que se cuentan una sola vez.                         */
$tieneCreatedAtCompras = false;
$resColCompras = @$conexion->query("SHOW COLUMNS FROM compras LIKE 'created_at'");
if ($resColCompras && $resColCompras->num_rows > 0) {
    $tieneCreatedAtCompras = true;
}
if ($resColCompras) {
    $resColCompras->free();
}

$tieneFacturaId = false;
$resColFactura = @$conexion->query("SHOW COLUMNS FROM compras LIKE 'factura_id'");
if ($resColFactura && $resColFactura->num_rows > 0) {
    $tieneFacturaId = true;
}
if ($resColFactura) {
    $resColFactura->free();
}

$fechaCompraExpr = "COALESCE(STR_TO_DATE(c.fecha, '%d-%m-%Y %h:%i %p'), CONCAT(c.dia, ' 00:00:00'))";
if ($tieneCreatedAtCompras) {
    $fechaCompraExpr = "COALESCE(STR_TO_DATE(c.fecha, '%d-%m-%Y %h:%i %p'), c.created_at, CONCAT(c.dia, ' 00:00:00'))";
}
$facturaExpr = $tieneFacturaId
    ? "CASE WHEN c.factura_id IS NOT NULL AND c.factura_id > 0 THEN 1 ELSE 0 END"
    : "0";

$stmt = $conexion->prepare(
    "SELECT $fechaCompraExpr AS fecha_evento,
            CAST(c.cantidad AS DECIMAL(15,4)) AS cantidad,
            $facturaExpr AS con_factura
     FROM compras c
     WHERE c.bss_id = ?
       AND c.id_sucursal = ?
       AND c.cod IN ($idsSql)
       AND $fechaCompraExpr >= ?
       AND $fechaCompraExpr < DATE_ADD(?, INTERVAL 1 DAY)
     ORDER BY fecha_evento ASC"
);
if ($stmt) {
    $stmt->bind_param('iiss', $bssId, $sucursalId, $desde, $hoy);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $fecha = trim((string) $fila['fecha_evento']);
        $cantidad = (float) $fila['cantidad'];
        if ($fecha === '' || $cantidad <= 0) {
            continue;
        }
        $eventos[] = [
            'fecha_evento' => $fecha,
            'tipo' => 'ingreso',
            'cantidad' => round($cantidad, 4),
            'detalle' => ((int) $fila['con_factura'] === 1) ? 'Compra con factura' : 'Compra',
        ];
    }
    $stmt->close();
}

/* ── Ventas ───────────────────────────────────────────────────────────
   Se agrupan por día para que el gráfico no se sature de puntos.       */
$stmt = $conexion->prepare(
    "SELECT MAX(o.created) AS fecha_evento,
            SUM(oa.quantity * CASE WHEN COALESCE(p.mayor, '0') = '1'
                    THEN GREATEST(COALESCE(p.cantidad_unidades, 1), 1)
                    ELSE 1 END) AS cantidad
     FROM orden o
     INNER JOIN orden_articulos oa ON oa.order_id = o.id
     LEFT JOIN productos p ON p.id = oa.product_id
     WHERE o.bss_id = ?
       AND o.id_sucursal = ?
       AND oa.product_id IN ($idsSql)
       AND o.status IN (1, 2, 3, 4)
       AND o.created >= ?
       AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
     GROUP BY DATE(o.created)
     ORDER BY fecha_evento ASC"
);
if ($stmt) {
    $stmt->bind_param('iiss', $bssId, $sucursalId, $desde, $hoy);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $fecha = trim((string) $fila['fecha_evento']);
        $cantidad = (float) $fila['cantidad'];
        if ($fecha === '' || $cantidad <= 0) {
            continue;
        }
        $eventos[] = [
            'fecha_evento' => $fecha,
            'tipo' => 'venta',
            'cantidad' => round($cantidad, 4),
            'detalle' => 'Ventas del día',
        ];
    }
    $stmt->close();
}

/* ── Mermas ─────────────────────────────────────────────────────────── */
$stmt = $conexion->prepare(
    "SELECT COALESCE(created_at, CONCAT(fecha, ' 00:00:00')) AS fecha_evento,
            CAST(cantidad AS DECIMAL(15,4)) AS cantidad
     FROM stock_ajustes
     WHERE bss_id = ?
       AND id_sucursal = ?
       AND producto_id IN ($idsSql)
       AND tipo = 'MERMA'
       AND direccion = 'SALIDA'
       AND estado = 'ACTIVO'
       AND fecha BETWEEN ? AND ?
     ORDER BY fecha_evento ASC"
);
if ($stmt) {
    $stmt->bind_param('iiss', $bssId, $sucursalId, $desde, $hoy);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $fecha = trim((string) $fila['fecha_evento']);
        $cantidad = (float) $fila['cantidad'];
        if ($fecha === '' || $cantidad <= 0) {
            continue;
        }
        $eventos[] = [
            'fecha_evento' => $fecha,
            'tipo' => 'merma',
            'cantidad' => round($cantidad, 4),
            'detalle' => 'Merma',
        ];
    }
    $stmt->close();
}

/* ── Traslados recibidos y enviados ─────────────────────────────────── */
if (st_asegurar_tabla($conexion)) {
    $stmt = $conexion->prepare(
        "SELECT id, created_at AS fecha_evento, cantidad, sucursal_origen, sucursal_destino
         FROM stock_traslados
         WHERE bss_id = ?
           AND id_producto IN ($idsSql)
           AND (sucursal_origen = ? OR sucursal_destino = ?)
           AND created_at >= ?
           AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
         ORDER BY created_at ASC"
    );
    if ($stmt) {
        $stmt->bind_param('iiiss', $bssId, $sucursalId, $sucursalId, $desde, $hoy);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $fecha = trim((string) $fila['fecha_evento']);
            $cantidad = (float) $fila['cantidad'];
            $origen = (int) $fila['sucursal_origen'];
            $destino = (int) $fila['sucursal_destino'];
            if ($fecha === '' || $cantidad <= 0 || $origen === $destino) {
                continue;
            }

            if ($destino === $sucursalId) {
                $tipo = 'ingreso';
                $detalle = 'Traslado recibido';
            } elseif ($origen === $sucursalId) {
                $tipo = 'salida';
                $detalle = 'Traslado enviado';
            } else {
                continue;
            }

            $eventos[] = [
                'fecha_evento' => $fecha,
                'tipo' => $tipo,
                'cantidad' => round($cantidad, 4),
                'detalle' => $detalle,
            ];
        }
        $stmt->close();
    }
}

/* ── Orden cronológico y cálculo del stock inicial ──────────────────── */
usort($eventos, static function ($a, $b) {
    $cmp = strcmp((string) $a['fecha_evento'], (string) $b['fecha_evento']);
    if ($cmp !== 0) {
        return $cmp;
    }
    $ordenTipo = ['ingreso' => 0, 'venta' => 1, 'salida' => 2, 'merma' => 3];
    return ($ordenTipo[$a['tipo']] ?? 9) <=> ($ordenTipo[$b['tipo']] ?? 9);
});

$neto = 0.0;
foreach ($eventos as $evento) {
    $neto += $evento['tipo'] === 'ingreso' ? $evento['cantidad'] : -$evento['cantidad'];
}
$stockInicial = round($stockActual - $neto, 4);

echo json_encode([
    'ok' => true,
    'stock_actual' => round($stockActual, 4),
    'stock_inicial' => $stockInicial,
    'eventos' => $eventos,
    'unidad' => $unidad,
    'desde' => $desde,
    'hasta' => $hoy,
    'dias' => $dias,
    'sucursal' => $sucursalSeleccionada,
    'sucursales' => $sucursales,
], JSON_UNESCAPED_UNICODE);
