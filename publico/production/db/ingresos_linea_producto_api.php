<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../configurar/configuracion.php');
require_once(__DIR__ . '/../../../configurar/session.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'msg' => 'No autorizado. Sesión no válida.']);
    exit;
}

$bss_id = (int) $_SESSION['bss_id'];
$nivel  = (int) ($_SESSION['nivel'] ?? 2);
$accion = trim((string) ($_REQUEST['accion'] ?? ''));

function lp_json(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Los errores de negocio se devuelven con HTTP 200 para que el AJAX de la
 * página pueda mostrar el mensaje concreto (jQuery trata 4xx como fallo).
 */
function lp_error(string $msg): void
{
    lp_json(['status' => 'error', 'msg' => $msg]);
}

function lp_fecha(string $valor): ?string
{
    $d = DateTime::createFromFormat('!Y-m-d', $valor);
    return ($d && $d->format('Y-m-d') === $valor) ? $valor : null;
}

function lp_sucursal(int $nivel): int
{
    if ($nivel === 2) {
        return (int) ($_SESSION['sucursal'] ?? 0);
    }
    $valor = $_REQUEST['sucursal'] ?? '';
    return ($valor === '' || $valor === 'todas') ? 0 : (int) $valor;
}

/** Normaliza una lista de ids enviada como "1,2,3". */
function lp_ids(string $raw): array
{
    $ids = [];
    foreach (explode(',', $raw) as $parte) {
        $id = (int) $parte;
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }
    return array_slice(array_values($ids), 0, 5000);
}

/**
 * Convierte las presentaciones enviadas a su producto principal.
 * - Variación:      id_producto_relacionado -> principal.
 * - Al mayor:       id_producto_relacionado -> principal (o a su variación).
 * - Producto normal: se queda igual.
 * Devuelve null si algún id no pertenece al negocio.
 */
function lp_productos_canonicos(mysqli $conexion, array $ids, int $bss_id): ?array
{
    if (!$ids) {
        return null;
    }
    $in  = implode(',', $ids);
    $res = $conexion->query("
        SELECT p.id,
               COALESCE(NULLIF(parent.id_producto_relacionado, 0), NULLIF(p.id_producto_relacionado, 0), p.id) AS base_id
        FROM productos p
        LEFT JOIN productos parent ON parent.id = p.id_producto_relacionado AND parent.bss_id = p.bss_id
        WHERE p.bss_id = $bss_id
          AND p.id IN ($in)
    ");
    if (!$res) {
        return null;
    }

    $encontrados = 0;
    $bases = [];
    while ($row = $res->fetch_assoc()) {
        $encontrados++;
        $base_id = (int) $row['base_id'];
        if ($base_id > 0) {
            $bases[$base_id] = $base_id;
        }
    }
    $res->free();

    if ($encontrados !== count($ids) || !$bases) {
        return null;
    }
    return array_values($bases);
}

function lp_guardar_items(mysqli $conexion, int $linea_id, array $ids, int $bss_id): bool
{
    $stmt = $conexion->prepare("INSERT IGNORE INTO linea_producto_items (linea_id, producto_id, bss_id) VALUES (?, ?, ?)");
    if (!$stmt) {
        return false;
    }
    foreach ($ids as $producto_id) {
        $stmt->bind_param('iii', $linea_id, $producto_id, $bss_id);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
    }
    $stmt->close();
    return true;
}

if (in_array($accion, ['crear', 'editar', 'eliminar'], true) && $nivel !== 1) {
    lp_error('No autorizado');
}

switch ($accion) {

    case 'catalogo':
        // Solo productos principales activos. Variaciones, presentaciones al
        // mayor y combos no se eligen: sus ventas se suman solas al principal.
        $stmt = $conexion->prepare("
            SELECT p.id, p.nombre, p.codigo, p.categoria, cp.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias_producto cp ON cp.id = p.categoria
            WHERE p.bss_id = ?
              AND p.activo = 0
              AND COALESCE(p.es_combo, 0) = 0
              AND (p.mayor IS NULL OR p.mayor = 0)
              AND (p.id_producto_relacionado IS NULL OR p.id_producto_relacionado = 0)
            ORDER BY p.nombre ASC
        ");
        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $productos = [];
        while ($row = $res->fetch_assoc()) {
            $productos[] = [
                'id'               => (int) $row['id'],
                'nombre'           => $row['nombre'],
                'codigo'           => (string) ($row['codigo'] ?? ''),
                'categoria_nombre' => (string) ($row['categoria_nombre'] ?? ''),
            ];
        }
        $stmt->close();
        lp_json(['success' => true, 'data' => $productos]);
        break;

    case 'listar':
        $lineas = [];
        $stmt = $conexion->prepare("
            SELECT l.id, l.nombre, l.descripcion,
                   COUNT(i.producto_id) AS total_productos
            FROM lineas_producto l
            LEFT JOIN linea_producto_items i ON i.linea_id = l.id
            WHERE l.bss_id = ?
            GROUP BY l.id, l.nombre, l.descripcion
            ORDER BY l.nombre ASC
        ");
        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $lineas[(int) $row['id']] = [
                'id'              => (int) $row['id'],
                'nombre'          => $row['nombre'],
                'descripcion'     => (string) ($row['descripcion'] ?? ''),
                'total_productos' => (int) $row['total_productos'],
                'productos'       => [],
            ];
        }
        $stmt->close();

        // Se devuelve el principal aunque el item guardado sea una variación
        // o una presentación al mayor (datos creados antes de esta regla).
        $resItems = $conexion->query("
            SELECT li.linea_id,
                   COALESCE(NULLIF(parent.id_producto_relacionado, 0), NULLIF(p.id_producto_relacionado, 0), p.id) AS producto_id
            FROM linea_producto_items li
            INNER JOIN productos p ON p.id = li.producto_id AND p.bss_id = li.bss_id
            LEFT JOIN productos parent ON parent.id = p.id_producto_relacionado AND parent.bss_id = p.bss_id
            WHERE li.bss_id = $bss_id
        ");
        if ($resItems) {
            while ($item = $resItems->fetch_assoc()) {
                $linea_id = (int) $item['linea_id'];
                $producto_id = (int) $item['producto_id'];
                if (isset($lineas[$linea_id]) && $producto_id > 0 && !in_array($producto_id, $lineas[$linea_id]['productos'], true)) {
                    $lineas[$linea_id]['productos'][] = $producto_id;
                }
            }
            $resItems->free();
        }

        foreach ($lineas as &$linea) {
            $linea['total_productos'] = count($linea['productos']);
        }
        unset($linea);

        lp_json(['success' => true, 'data' => array_values($lineas)]);
        break;

    case 'resumen':
        $linea_id = (int) ($_REQUEST['linea_id'] ?? 0);
        $hoy      = date('Y-m-d');
        $desde    = lp_fecha((string) ($_REQUEST['desde'] ?? '')) ?? $hoy;
        $hasta    = lp_fecha((string) ($_REQUEST['hasta'] ?? '')) ?? $hoy;

        if ($desde > $hasta) {
            lp_error('La fecha inicial no puede ser mayor que la final');
        }

        $stmtLinea = $conexion->prepare("SELECT id, nombre FROM lineas_producto WHERE id = ? AND bss_id = ? LIMIT 1");
        $stmtLinea->bind_param('ii', $linea_id, $bss_id);
        $stmtLinea->execute();
        $linea = $stmtLinea->get_result()->fetch_assoc();
        $stmtLinea->close();
        if (!$linea) {
            lp_error('La línea de producto no existe');
        }

        $sucursal_id = lp_sucursal($nivel);
        $sucCondDir   = $sucursal_id > 0 ? " AND o.id_sucursal = $sucursal_id" : '';
        $sucCondCombo = $sucursal_id > 0 ? " AND o2.id_sucursal = $sucursal_id" : '';

        /*
         * Ventas directas de la línea. Cada presentación (producto principal,
         * variación o al mayor) se resuelve a su principal; el ingreso y el
         * costo se suman con el margen real de cada presentación.
         */
        $sqlDirecta = "
            SELECT
                o.id AS order_id,
                o.created AS fecha,
                oa.product_id AS presentacion_id,
                base.id AS producto_id,
                base.nombre AS nombre,
                oa.quantity AS unidades,
                oa.quantity * CAST(oa.precio_venta_dolar AS DECIMAL(15,2)) AS ingreso,
                oa.quantity * CAST(oa.precio AS DECIMAL(15,2)) AS costo
            FROM orden_articulos oa
            INNER JOIN orden o ON o.id = oa.order_id
            INNER JOIN productos pv ON pv.id = oa.product_id
            LEFT JOIN productos parent ON parent.id = pv.id_producto_relacionado AND parent.bss_id = oa.bss_id
            INNER JOIN productos base ON base.id = COALESCE(NULLIF(parent.id_producto_relacionado, 0), NULLIF(pv.id_producto_relacionado, 0), pv.id)
            INNER JOIN linea_producto_items li ON li.linea_id = ? AND li.producto_id = base.id AND li.bss_id = oa.bss_id
            WHERE o.bss_id = ?
              AND o.status IN (1, 4)
              AND o.created >= ?
              AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
              AND COALESCE(pv.es_combo, 0) = 0
              $sucCondDir
        ";

        /*
         * Combos: la venta del combo se reparte entre sus componentes según el
         * valor de referencia de cada uno. Así la línea recibe solo la parte
         * que corresponde a sus productos y no se duplica entre líneas.
         */
        $sqlCombo = "
            SELECT
                t.order_id AS order_id,
                t.fecha AS fecha,
                comp.id AS presentacion_id,
                base.id AS producto_id,
                base.nombre AS nombre,
                ci.cantidad * t.quantity AS unidades,
                COALESCE(t.quantity * CAST(t.precio_venta_dolar AS DECIMAL(15,2))
                    * (ci.cantidad * ci.venta_unitario_ref) / NULLIF(t.ref_venta_total, 0), 0) AS ingreso,
                COALESCE(t.quantity * CAST(t.precio AS DECIMAL(15,2))
                    * (ci.cantidad * ci.costo_unitario_ref) / NULLIF(t.ref_costo_total, 0), 0) AS costo
            FROM (
                SELECT
                    oa2.id AS oa_id,
                    oa2.order_id AS order_id,
                    o2.created AS fecha,
                    oa2.product_id AS combo_id,
                    oa2.quantity AS quantity,
                    oa2.precio_venta_dolar AS precio_venta_dolar,
                    oa2.precio AS precio,
                    oa2.bss_id AS bss_id,
                    SUM(ci2.cantidad * ci2.venta_unitario_ref) AS ref_venta_total,
                    SUM(ci2.cantidad * ci2.costo_unitario_ref) AS ref_costo_total
                FROM orden_articulos oa2
                INNER JOIN orden o2 ON o2.id = oa2.order_id
                INNER JOIN productos combo2 ON combo2.id = oa2.product_id AND COALESCE(combo2.es_combo, 0) = 1
                INNER JOIN combo_items ci2 ON ci2.combo_producto_id = oa2.product_id AND ci2.bss_id = oa2.bss_id
                WHERE o2.bss_id = ?
                  AND o2.status IN (1, 4)
                  AND o2.created >= ?
                  AND o2.created < DATE_ADD(?, INTERVAL 1 DAY)
                  $sucCondCombo
                GROUP BY oa2.id, oa2.order_id, o2.created, oa2.product_id, oa2.quantity, oa2.precio_venta_dolar, oa2.precio, oa2.bss_id
            ) t
            INNER JOIN combo_items ci ON ci.combo_producto_id = t.combo_id AND ci.bss_id = t.bss_id
            INNER JOIN productos comp ON comp.id = ci.producto_id
            LEFT JOIN productos parent ON parent.id = comp.id_producto_relacionado AND parent.bss_id = t.bss_id
            INNER JOIN productos base ON base.id = COALESCE(NULLIF(parent.id_producto_relacionado, 0), NULLIF(comp.id_producto_relacionado, 0), comp.id)
            INNER JOIN linea_producto_items li ON li.linea_id = ? AND li.producto_id = base.id AND li.bss_id = t.bss_id
        ";

        $unionVentas = "($sqlDirecta) UNION ALL ($sqlCombo)";
        $tiposUnion  = 'iiss' . 'issi';
        $paramsUnion = [$linea_id, $bss_id, $desde, $hasta, $bss_id, $desde, $hasta, $linea_id];

        // Desglose por producto principal.
        $stmtProd = $conexion->prepare("
            SELECT v.producto_id, v.nombre,
                   COALESCE(SUM(v.unidades), 0) AS unidades,
                   COALESCE(SUM(v.ingreso), 0) AS ingreso,
                   COALESCE(SUM(v.costo), 0) AS costo,
                   COUNT(DISTINCT v.presentacion_id) AS presentaciones
            FROM ($unionVentas) v
            GROUP BY v.producto_id, v.nombre
            ORDER BY ingreso DESC, v.nombre ASC
        ");
        $stmtProd->bind_param($tiposUnion, ...$paramsUnion);
        $stmtProd->execute();
        $resProd = $stmtProd->get_result();

        $productos = [];
        $ingreso = 0.0;
        $costo = 0.0;
        $unidades = 0.0;
        while ($row = $resProd->fetch_assoc()) {
            $ingresoProd = round((float) $row['ingreso'], 2);
            $costoProd = round((float) $row['costo'], 2);
            $unidadesProd = round((float) $row['unidades'], 3);
            $ingreso += $ingresoProd;
            $costo += $costoProd;
            $unidades += $unidadesProd;
            $productos[] = [
                'id'            => (int) $row['producto_id'],
                'nombre'        => $row['nombre'],
                'unidades'      => $unidadesProd,
                'ingreso'       => $ingresoProd,
                'porcentaje'    => 0, // se calcula al final sobre el total de la línea
                'costo'         => $costoProd,
                'utilidad'      => round($ingresoProd - $costoProd, 2),
                'presentaciones' => (int) $row['presentaciones'],
            ];
        }
        $stmtProd->close();

        $ingreso = round($ingreso, 2);
        $costo = round($costo, 2);
        $unidades = round($unidades, 3);
        foreach ($productos as &$producto) {
            $producto['porcentaje'] = $ingreso > 0 ? round($producto['ingreso'] / $ingreso * 100, 2) : 0;
        }
        unset($producto);

        // Tickets con al menos un producto de la línea (directo o en combo).
        $stmtTickets = $conexion->prepare("SELECT COUNT(DISTINCT v.order_id) AS tickets FROM ($unionVentas) v");
        $stmtTickets->bind_param($tiposUnion, ...$paramsUnion);
        $stmtTickets->execute();
        $tickets = (int) ($stmtTickets->get_result()->fetch_assoc()['tickets'] ?? 0);
        $stmtTickets->close();

        // Total vendido por el negocio en el mismo período y sucursal.
        $stmtTotal = $conexion->prepare("
            SELECT COALESCE(SUM(oa.quantity * CAST(oa.precio_venta_dolar AS DECIMAL(15,2))), 0) AS total
            FROM orden_articulos oa
            INNER JOIN orden o ON o.id = oa.order_id
            WHERE o.bss_id = ?
              AND o.status IN (1, 4)
              AND o.created >= ?
              AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
              $sucCondDir
        ");
        $stmtTotal->bind_param('iss', $bss_id, $desde, $hasta);
        $stmtTotal->execute();
        $totalPeriodo = round((float) ($stmtTotal->get_result()->fetch_assoc()['total'] ?? 0), 2);
        $stmtTotal->close();

        // Ingresos de la línea por día (se completan los días sin ventas).
        $stmtDia = $conexion->prepare("
            SELECT DATE(v.fecha) AS fecha, COALESCE(SUM(v.ingreso), 0) AS ingreso
            FROM ($unionVentas) v
            GROUP BY DATE(v.fecha)
            ORDER BY fecha ASC
        ");
        $stmtDia->bind_param($tiposUnion, ...$paramsUnion);
        $stmtDia->execute();
        $resDia = $stmtDia->get_result();
        $porDia = [];
        while ($row = $resDia->fetch_assoc()) {
            $porDia[$row['fecha']] = round((float) $row['ingreso'], 2);
        }
        $stmtDia->close();

        $labels = [];
        $valores = [];
        $cursor = new DateTime($desde);
        $fin = (new DateTime($hasta))->modify('+1 day');
        while ($cursor < $fin) {
            $fecha = $cursor->format('Y-m-d');
            $labels[] = $fecha;
            $valores[] = $porDia[$fecha] ?? 0;
            $cursor->modify('+1 day');
        }

        lp_json([
            'success' => true,
            'linea'   => ['id' => (int) $linea['id'], 'nombre' => $linea['nombre']],
            'periodo' => ['desde' => $desde, 'hasta' => $hasta],
            'kpis'    => [
                'ingreso'       => $ingreso,
                'costo'         => $costo,
                'utilidad'      => round($ingreso - $costo, 2),
                'unidades'      => $unidades,
                'tickets'       => $tickets,
                'total_periodo' => $totalPeriodo,
                'porcentaje'    => $totalPeriodo > 0 ? round($ingreso / $totalPeriodo * 100, 2) : 0,
            ],
            'productos' => $productos,
            'grafico'   => ['labels' => $labels, 'valores' => $valores],
        ]);
        break;

    case 'crear':
    case 'editar':
        $id          = (int) ($_POST['id'] ?? 0);
        $nombre      = trim(strip_tags((string) ($_POST['nombre'] ?? '')));
        $descripcion = trim(strip_tags((string) ($_POST['descripcion'] ?? '')));
        $enviados    = lp_ids((string) ($_POST['productos'] ?? ''));

        if ($nombre === '') {
            lp_error('El nombre de la línea es obligatorio');
        }
        if (mb_strlen($nombre, 'UTF-8') > 150) {
            lp_error('El nombre no puede superar 150 caracteres');
        }
        if (mb_strlen($descripcion, 'UTF-8') > 255) {
            lp_error('La descripción no puede superar 255 caracteres');
        }
        if (!$enviados) {
            lp_error('Seleccione al menos un producto');
        }

        $productos = lp_productos_canonicos($conexion, $enviados, $bss_id);
        if (!$productos) {
            lp_error('Hay productos que no pertenecen a este negocio');
        }

        $sqlDup = "SELECT id FROM lineas_producto WHERE bss_id = ? AND nombre = ?";
        if ($accion === 'editar') {
            $sqlDup .= " AND id <> ?";
        }
        $sqlDup .= " LIMIT 1";
        $stmtDup = $conexion->prepare($sqlDup);
        if ($accion === 'editar') {
            $stmtDup->bind_param('isi', $bss_id, $nombre, $id);
        } else {
            $stmtDup->bind_param('is', $bss_id, $nombre);
        }
        $stmtDup->execute();
        $duplicada = $stmtDup->get_result()->num_rows > 0;
        $stmtDup->close();
        if ($duplicada) {
            lp_error('Ya existe una línea con ese nombre');
        }

        if ($accion === 'editar') {
            $stmtCheck = $conexion->prepare("SELECT id FROM lineas_producto WHERE id = ? AND bss_id = ? LIMIT 1");
            $stmtCheck->bind_param('ii', $id, $bss_id);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                $stmtCheck->close();
                lp_error('La línea de producto no existe');
            }
            $stmtCheck->close();
        }

        $conexion->begin_transaction();

        if ($accion === 'crear') {
            $stmt = $conexion->prepare("INSERT INTO lineas_producto (bss_id, nombre, descripcion) VALUES (?, ?, ?)");
            $ok = $stmt && $stmt->bind_param('iss', $bss_id, $nombre, $descripcion) && $stmt->execute();
            $linea_id = $ok ? (int) $conexion->insert_id : 0;
            if ($stmt) {
                $stmt->close();
            }
        } else {
            $stmt = $conexion->prepare("UPDATE lineas_producto SET nombre = ?, descripcion = ? WHERE id = ? AND bss_id = ?");
            $ok = $stmt && $stmt->bind_param('ssii', $nombre, $descripcion, $id, $bss_id) && $stmt->execute();
            $linea_id = $id;
            if ($stmt) {
                $stmt->close();
            }
            if ($ok) {
                $stmtDel = $conexion->prepare("DELETE FROM linea_producto_items WHERE linea_id = ? AND bss_id = ?");
                $ok = $stmtDel && $stmtDel->bind_param('ii', $id, $bss_id) && $stmtDel->execute();
                if ($stmtDel) {
                    $stmtDel->close();
                }
            }
        }

        if (!$ok || !lp_guardar_items($conexion, $linea_id, $productos, $bss_id)) {
            $conexion->rollback();
            lp_error('No se pudo guardar la línea de producto');
        }

        $conexion->commit();
        lp_json([
            'status' => 'ok',
            'msg'    => $accion === 'crear' ? 'Línea creada' : 'Línea actualizada',
            'id'     => $linea_id,
        ]);
        break;

    case 'eliminar':
        $id = (int) ($_POST['id'] ?? 0);

        $stmtCheck = $conexion->prepare("SELECT id FROM lineas_producto WHERE id = ? AND bss_id = ? LIMIT 1");
        $stmtCheck->bind_param('ii', $id, $bss_id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows === 0) {
            $stmtCheck->close();
            lp_error('La línea de producto no existe');
        }
        $stmtCheck->close();

        $conexion->begin_transaction();
        $stmtItems = $conexion->prepare("DELETE FROM linea_producto_items WHERE linea_id = ? AND bss_id = ?");
        $ok = $stmtItems && $stmtItems->bind_param('ii', $id, $bss_id) && $stmtItems->execute();
        if ($stmtItems) {
            $stmtItems->close();
        }
        if ($ok) {
            $stmtLinea = $conexion->prepare("DELETE FROM lineas_producto WHERE id = ? AND bss_id = ?");
            $ok = $stmtLinea && $stmtLinea->bind_param('ii', $id, $bss_id) && $stmtLinea->execute();
            if ($stmtLinea) {
                $stmtLinea->close();
            }
        }

        if (!$ok) {
            $conexion->rollback();
            lp_error('No se pudo eliminar la línea de producto');
        }

        $conexion->commit();
        lp_json(['status' => 'ok', 'msg' => 'Línea eliminada']);
        break;

    default:
        lp_error('Acción no válida');
}
