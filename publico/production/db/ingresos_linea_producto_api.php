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

/** Todos los ids deben pertenecer al negocio. */
function lp_productos_validos(mysqli $conexion, array $ids, int $bss_id): bool
{
    if (!$ids) {
        return false;
    }
    $in  = implode(',', $ids);
    $res = $conexion->query("SELECT COUNT(*) AS total FROM productos WHERE bss_id = $bss_id AND id IN ($in)");
    if (!$res) {
        return false;
    }
    $total = (int) ($res->fetch_assoc()['total'] ?? 0);
    $res->free();
    return $total === count($ids);
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
        $stmt = $conexion->prepare("
            SELECT p.id, p.nombre, p.codigo, p.categoria, cp.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias_producto cp ON cp.id = p.categoria
            WHERE p.bss_id = ? AND p.activo = 0
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

        $resItems = $conexion->query("SELECT linea_id, producto_id FROM linea_producto_items WHERE bss_id = $bss_id");
        if ($resItems) {
            while ($item = $resItems->fetch_assoc()) {
                $linea_id = (int) $item['linea_id'];
                if (isset($lineas[$linea_id])) {
                    $lineas[$linea_id]['productos'][] = (int) $item['producto_id'];
                }
            }
            $resItems->free();
        }

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
        $sucCond     = $sucursal_id > 0 ? " AND o.id_sucursal = $sucursal_id" : '';

        // Indicadores de la línea en el período.
        $stmt = $conexion->prepare("
            SELECT
                COALESCE(SUM(oa.quantity * CAST(oa.precio_venta_dolar AS DECIMAL(15,2))), 0) AS ingreso,
                COALESCE(SUM(oa.quantity * CAST(oa.precio AS DECIMAL(15,2))), 0) AS costo,
                COALESCE(SUM(oa.quantity), 0) AS unidades,
                COUNT(DISTINCT o.id) AS tickets
            FROM orden_articulos oa
            INNER JOIN orden o ON o.id = oa.order_id
            INNER JOIN linea_producto_items li ON li.producto_id = oa.product_id AND li.bss_id = oa.bss_id
            WHERE li.linea_id = ?
              AND o.bss_id = ?
              AND o.status IN (1, 4)
              AND o.created >= ?
              AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
              $sucCond
        ");
        $stmt->bind_param('iiss', $linea_id, $bss_id, $desde, $hasta);
        $stmt->execute();
        $kpi = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $ingreso  = round((float) ($kpi['ingreso'] ?? 0), 2);
        $costo    = round((float) ($kpi['costo'] ?? 0), 2);
        $unidades = round((float) ($kpi['unidades'] ?? 0), 3);
        $tickets  = (int) ($kpi['tickets'] ?? 0);

        // Total vendido por el negocio en el mismo período y sucursal.
        $stmtTotal = $conexion->prepare("
            SELECT COALESCE(SUM(oa.quantity * CAST(oa.precio_venta_dolar AS DECIMAL(15,2))), 0) AS total
            FROM orden_articulos oa
            INNER JOIN orden o ON o.id = oa.order_id
            WHERE o.bss_id = ?
              AND o.status IN (1, 4)
              AND o.created >= ?
              AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
              $sucCond
        ");
        $stmtTotal->bind_param('iss', $bss_id, $desde, $hasta);
        $stmtTotal->execute();
        $totalPeriodo = round((float) ($stmtTotal->get_result()->fetch_assoc()['total'] ?? 0), 2);
        $stmtTotal->close();

        // Desglose por producto de la línea.
        $stmtProd = $conexion->prepare("
            SELECT p.id, p.nombre,
                   COALESCE(SUM(oa.quantity), 0) AS unidades,
                   COALESCE(SUM(oa.quantity * CAST(oa.precio_venta_dolar AS DECIMAL(15,2))), 0) AS ingreso,
                   COALESCE(SUM(oa.quantity * CAST(oa.precio AS DECIMAL(15,2))), 0) AS costo
            FROM orden_articulos oa
            INNER JOIN orden o ON o.id = oa.order_id
            INNER JOIN linea_producto_items li ON li.producto_id = oa.product_id AND li.bss_id = oa.bss_id
            INNER JOIN productos p ON p.id = oa.product_id
            WHERE li.linea_id = ?
              AND o.bss_id = ?
              AND o.status IN (1, 4)
              AND o.created >= ?
              AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
              $sucCond
            GROUP BY p.id, p.nombre
            ORDER BY ingreso DESC, p.nombre ASC
        ");
        $stmtProd->bind_param('iiss', $linea_id, $bss_id, $desde, $hasta);
        $stmtProd->execute();
        $resProd = $stmtProd->get_result();
        $productos = [];
        while ($row = $resProd->fetch_assoc()) {
            $ingresoProd = round((float) $row['ingreso'], 2);
            $productos[] = [
                'id'         => (int) $row['id'],
                'nombre'     => $row['nombre'],
                'unidades'   => round((float) $row['unidades'], 3),
                'ingreso'    => $ingresoProd,
                'porcentaje' => $ingreso > 0 ? round($ingresoProd / $ingreso * 100, 2) : 0,
                'costo'      => round((float) $row['costo'], 2),
                'utilidad'   => round($ingresoProd - (float) $row['costo'], 2),
            ];
        }
        $stmtProd->close();

        // Ingresos por día dentro del rango (se completan los días sin ventas).
        $stmtDia = $conexion->prepare("
            SELECT DATE(o.created) AS fecha,
                   COALESCE(SUM(oa.quantity * CAST(oa.precio_venta_dolar AS DECIMAL(15,2))), 0) AS ingreso
            FROM orden_articulos oa
            INNER JOIN orden o ON o.id = oa.order_id
            INNER JOIN linea_producto_items li ON li.producto_id = oa.product_id AND li.bss_id = oa.bss_id
            WHERE li.linea_id = ?
              AND o.bss_id = ?
              AND o.status IN (1, 4)
              AND o.created >= ?
              AND o.created < DATE_ADD(?, INTERVAL 1 DAY)
              $sucCond
            GROUP BY DATE(o.created)
            ORDER BY fecha ASC
        ");
        $stmtDia->bind_param('iiss', $linea_id, $bss_id, $desde, $hasta);
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
        $productos   = lp_ids((string) ($_POST['productos'] ?? ''));

        if ($nombre === '') {
            lp_error('El nombre de la línea es obligatorio');
        }
        if (mb_strlen($nombre, 'UTF-8') > 150) {
            lp_error('El nombre no puede superar 150 caracteres');
        }
        if (mb_strlen($descripcion, 'UTF-8') > 255) {
            lp_error('La descripción no puede superar 255 caracteres');
        }
        if (!$productos) {
            lp_error('Seleccione al menos un producto');
        }
        if (!lp_productos_validos($conexion, $productos, $bss_id)) {
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
