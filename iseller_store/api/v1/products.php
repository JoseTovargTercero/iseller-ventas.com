<?php
/**
 * GET /iseller_store/api/v1/products.php
 *   ?slug=yola&page=1&limit=20&category=12&q=arroz&sort=name_asc
 *   ?slug=  (vacío) → marketplace: productos de todas las tiendas.
 * Precios SIEMPRE recalculados por el servidor (la app nunca los envía).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';
require_once __DIR__ . '/../../core/reglas_producto.php';
require_once __DIR__ . '/../../core/combos.php';

global $conexion;
reglas_producto_asegurar($conexion);

api_method('GET');
api_rate_limit('products_ip', 180, 60);

$slug = trim((string)($_GET['slug'] ?? ''));
$tienda = null;
$bssId = 0;
if ($slug !== '') {
    $tienda = api_store_by_slug($slug);
    $bssId = (int)$tienda['id'];
}

$page = max(1, min(1000, (int)($_GET['page'] ?? 1)));
$limit = max(1, min(40, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;
$category = (int)($_GET['category'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 60, 'UTF-8');
$sort = ($_GET['sort'] ?? '') === 'name_desc' ? 'DESC' : 'ASC';

$where = "np.estado_publicacion = 1
          AND p.activo = 0
          AND (
              p.es_combo = 1
              OR (p.mayor = '1' AND p.cantidad_unidades > 0
                  AND COALESCE(sb.stock, 0) >= p.cantidad_unidades * COALESCE(pr.minimo_cantidad, 1))
              OR ((p.mayor IS NULL OR p.mayor <> '1')
                  AND s.stock >= COALESCE(pr.minimo_cantidad, 1))
          )
          AND n.tiene_tienda_virtual = 1
          AND n.estado = 'activo'";
$types = '';
$params = [];

if ($bssId > 0) {
    $where .= " AND np.negocio_id = ?";
    $types .= 'i';
    $params[] = $bssId;
}
if ($category > 0) {
    $where .= " AND p.categoria = ?";
    $types .= 'i';
    $params[] = $category;
}
if ($q !== '') {
    $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
    $where .= " AND (p.nombre LIKE ? OR p.codigo_barras = ? OR p.codigo_barras LIKE ?)";
    $types .= 'sss';
    $params[] = $like;
    $params[] = $q;
    $params[] = $like;
}

$filter = trim((string)($_GET['filter'] ?? ''));
if ($filter === 'al_mayor') {
    $where .= " AND p.mayor = '1'";
} elseif ($filter === 'combos') {
    $where .= " AND p.es_combo = 1";
} elseif (str_starts_with($filter, 'grupo:')) {
    $grupo = mb_substr(trim(substr($filter, 6)), 0, 100, 'UTF-8');
    if ($grupo !== '') {
        $where .= " AND c.grupo = ?";
        $types .= 's';
        $params[] = $grupo;
    }
}

$state = trim((string)($_GET['state'] ?? ''));
if ($state !== '') {
    $where .= " AND n.geo_estado = ?";
    $types .= 's';
    $params[] = $state;
}

global $conexion;

$sql = "SELECT p.id, p.nombre, p.precio_compra, p.cantidad_unidades, p.origen, p.mayor,
               p.codigo_barras, p.es_combo,
               s.stock, s.porcentaje, s.bss_id, s.id_sucursal, np.foto_url,
               c.id AS categoria_id, c.nombre AS categoria_nombre,
               n.slug AS store_slug,
               COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) AS store_name,
               COALESCE(pr.minimo_cantidad, 1.00) AS minimo_cantidad,
               COALESCE(pr.paso_cantidad, 1.00) AS paso_cantidad
        FROM productos p
        INNER JOIN negocio_productos np ON p.id = np.producto_id
        INNER JOIN negocio n ON np.negocio_id = n.id
        INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id
             AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
        LEFT JOIN stock sb ON sb.id = s.id_stock AND sb.id_sucursal = s.id_sucursal AND sb.bss_id = s.bss_id
        LEFT JOIN categorias_producto c ON c.id = p.categoria
        LEFT JOIN producto_reglas pr ON pr.producto_id = p.id
        WHERE $where
        GROUP BY p.id, s.id, pr.minimo_cantidad, pr.paso_cantidad
        ORDER BY p.nombre $sort, p.id ASC
        LIMIT ? OFFSET ?";
$paramsQuery = array_merge($params, [$limit, $offset]);
$typesQuery = $types . 'ii';
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    api_error(500, 'No se pudo consultar el catálogo');
}
$stmt->bind_param($typesQuery, ...$paramsQuery);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($fila = $res->fetch_assoc()) {
    $bssProducto = (int)$fila['bss_id'];
    $idSucursal = (int)$fila['id_sucursal'];
    $precios = api_calc($bssProducto)->calcularPrecios($fila);

    $esCombo = (int)($fila['es_combo'] ?? 0) === 1;
    $comboItems = [];
    $stockProducto = (float)$fila['stock'];
    if ($esCombo) {
        $comboItems = combo_items($conexion, (int)$fila['id'], $bssProducto);
        $stockProducto = (float)combo_stock(
            $conexion,
            (int)$fila['id'],
            $bssProducto,
            $idSucursal
        );
    } elseif ((string)$fila['mayor'] === '1') {
        $infoStock = producto_stock_info($conexion, (int)$fila['id'], $idSucursal, $bssProducto);
        if ($infoStock) {
            $stockProducto = (float)$infoStock['stock'];
        }
    }

    $minProducto = (float)($fila['minimo_cantidad'] ?? 1);
    // Los combos se incluyen en el SQL aunque no tengan piezas suficientes.
    if ($stockProducto < ($minProducto > 0 ? $minProducto : 1)) {
        continue;
    }

    $imagen = '';
    if (!empty($fila['foto_url'])) {
        $imagen = api_image($fila['foto_url'], 600, 72);
    } else {
        $optimizada = 'assets/img/stock/optimized/' . (int)$fila['id'] . '.webp';
        $original   = 'assets/img/stock/' . (int)$fila['id'] . '.png';
        if (api_local_path($optimizada) !== null) {
            $imagen = api_image($optimizada, 400, 75);
        } elseif (api_local_path($original) !== null) {
            $imagen = api_image($original, 400, 75);
        }
    }

    $items[] = [
        'id'            => (int)$fila['id'],
        'name'          => ucfirst(mb_strtolower(trim((string)$fila['nombre']), 'UTF-8')),
        'image_url'     => $imagen,
        'price_usd'     => api_trunc2((float)$precios['precio_venta_dolar']),
        'price_bs'      => api_trunc2((float)$precios['precio_venta_bs']),
        'price_cop'     => api_trunc2((float)$precios['precio_venta_peso']),
        'stock'         => $stockProducto,
        'in_stock'      => $stockProducto >= $minProducto,
        'pack_size'     => (int)$fila['cantidad_unidades'],
        'bulk'          => (string)$fila['mayor'] === '1',
        'min_quantity'  => (float)$fila['minimo_cantidad'],
        'step_quantity' => (float)$fila['paso_cantidad'],
        'category_id'   => $fila['categoria_id'] !== null ? (int)$fila['categoria_id'] : null,
        'category_name' => (string)($fila['categoria_nombre'] ?? ''),
        'store_id'      => $bssProducto,
        'store_slug'    => (string)$fila['store_slug'],
        'store_name'    => (string)$fila['store_name'],
        'branch_id'     => (int)$fila['id_sucursal'],
        'codigo_barras' => (string)($fila['codigo_barras'] ?? ''),
        'es_combo'      => $esCombo ? 1 : 0,
        'combo_items'   => $comboItems,
    ];
}
$stmt->close();

$countSql = "SELECT COUNT(DISTINCT p.id) AS total
             FROM productos p
             INNER JOIN negocio_productos np ON p.id = np.producto_id
             INNER JOIN negocio n ON np.negocio_id = n.id
             INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id
                  AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
             LEFT JOIN stock sb ON sb.id = s.id_stock AND sb.id_sucursal = s.id_sucursal AND sb.bss_id = s.bss_id
             LEFT JOIN categorias_producto c ON c.id = p.categoria
             LEFT JOIN producto_reglas pr ON pr.producto_id = p.id
             WHERE $where";
$stmtCount = $conexion->prepare($countSql);
if (!$stmtCount) {
    api_error(500, 'No se pudo contar el catálogo');
}
if ($types !== '') {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$total = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
$stmtCount->close();

api_ok([
    'items'    => $items,
    'page'     => $page,
    'limit'    => $limit,
    'total'    => $total,
    'has_more' => ($offset + $limit) < $total,
    'query'    => $q,
    'marketplace' => $slug === '',
]);
