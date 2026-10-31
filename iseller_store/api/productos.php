<?php
require_once('../core/db.php');
require_once('../core/session.php');
// _tasas_cambio.php se incluye solo para la definición de la clase TasasCambio.
// Los precios se calculan POR PRODUCTO usando el bss_id de su propio registro en stock.
require_once('../core/_tasas_cambio.php');
require_once('../core/_calculadrora_precios.php');

header('Content-Type: application/json');

$tienda_slug = $_GET['tienda_slug'] ?? null;

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
// La tienda puede vivir en un subdirectorio (/iseller-tiendas.com/iseller_store/):
// se deduce de la ruta del script en vez de asumir que cuelga de la raíz del dominio.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/iseller_store/api'));
define('URL_BASE', $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($scriptDir) . '/');

// Devuelve la imagen real del producto. Si la foto personalizada que guarda
// la BD ya no existe, usa la genérica y evita un 404 en la cuadrícula.
function urlImagenProducto(array $row): string
{
    $foto = (string)($row['foto_url'] ?? '');
    if ($foto !== '' && is_file(__DIR__ . '/../' . ltrim($foto, '/'))) {
        return URL_BASE . $foto;
    }

    $id = (int)($row['id'] ?? 0);
    $imgPath = __DIR__ . "/../assets/img/stock/{$id}.png";
    $optimizedPath = __DIR__ . "/../assets/img/stock/optimized/{$id}.webp";
    if (!file_exists($optimizedPath) && file_exists($imgPath)) {
        optimizeImage($imgPath, $optimizedPath, 400, 75);
    }
    if (file_exists($optimizedPath)) {
        return URL_BASE . "assets/img/stock/optimized/{$id}.webp";
    }
    return file_exists($imgPath) ? URL_BASE . "assets/img/stock/{$id}.png" : '';
}

$mode = $_GET['mode'] ?? 'grid';
$user_id = getUserId();
$searchIndex = [];

// Función para optimizar imágenes y generar WebP
function optimizeImage($srcPath, $destPath, $maxWidth = 400, $quality = 75) {
    if (!file_exists($srcPath)) return false;
    $info = getimagesize($srcPath);
    if (!$info) return false;
    list($width, $height) = $info;
    $mime = $info['mime'];
    $ratio = $width / $height;
    $newWidth = $maxWidth;
    $newHeight = intval($maxWidth / $ratio);
    $image = null;
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($srcPath); break;
        case 'image/png':  $image = imagecreatefrompng($srcPath); break;
        case 'image/webp': $image = imagecreatefromwebp($srcPath); break;
        default: return false;
    }
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    if ($mime === 'image/png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagewebp($thumb, $destPath, $quality);
    return true;
}

function recompensas($user_id) {
    global $conexion_store;
    $recompensa = [];
    $sql = "SELECT porcentaje, monto, tipo FROM tienda_recompensas_usuario WHERE usuario_id = ? AND estado = 'disponible'";
    $stmt = $conexion_store->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $porcentaje_descuento = (float) $row['porcentaje'] ?? 0;
            $tipo_descuento = $row['tipo'] ?? 'porcentaje';
            $descuento = $tipo_descuento === 'porcentaje' ? $porcentaje_descuento : $row['monto'];
            $recompensa[] = [
                'porcentaje' => $porcentaje_descuento,
                'tipo' => $tipo_descuento,
                'descuento' => $descuento
            ];
        }
    }
    return $recompensa;
}

// ponytail: descuentos deshabilitados temporalmente
$recompensas = [];

function sumarPorcentaje($precio, $porcentaje) {
    return $precio + ($precio * $porcentaje / 100);
}

// ── Calculadora por bss_id (cada empresa usa sus propias tasas) ──────────
// Pre-cargamos las tasas de cambio de todos los bss_id de negocios activos
$_tasas_cache = [];

function getCalculadora(int $bss_id, $recompensas = []) {
    global $_tasas_cache, $conexion;
    if (!isset($_tasas_cache[$bss_id])) {
        // Cargar tasas de cambio del negocio
        $stmt = $conexion->prepare("SELECT * FROM cambio WHERE bss_id = ? LIMIT 1");
        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $cambioRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Cargar monedas mostradas
        $stmt2 = $conexion->prepare("SELECT tasas FROM cambios_mostrados WHERE bss_id = ? LIMIT 1");
        $stmt2->bind_param('i', $bss_id);
        $stmt2->execute();
        $mostradosRow = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        $data_monedas_neg = $mostradosRow ? json_decode($mostradosRow['tasas'], true) : [];

        // BCV (global, no cambia por empresa)
        $bcvRes = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC LIMIT 1");
        $bcvRow = $bcvRes ? $bcvRes->fetch_assoc() : null;
        $bcv_val = $bcvRow ? (float)$bcvRow['valor'] : 0;

        $_tasas_cache[$bss_id] = [
            'pesoDolar'    => (float)($cambioRow['pesoDolar'] ?? 0),
            'peso_bolivar' => (float)($cambioRow['peso_bolivar'] ?? 0),
            'bolivar_peso' => (float)($cambioRow['bolivar_peso'] ?? 0),
            'dolarBolivar' => (float)($cambioRow['DolarBolivar'] ?? 0),
            'bcv'          => $bcv_val,
            'data_monedas' => $data_monedas_neg,
        ];
    }

    $t = $_tasas_cache[$bss_id];
    return new CalculadoraPrecios(
        $t['pesoDolar'], $t['peso_bolivar'], $t['dolarBolivar'],
        $t['bolivar_peso'], $t['bcv'], $t['data_monedas'], $recompensas
    );
}
// ─────────────────────────────────────────────────────────────────────────

// Formato común de producto: cuadrícula y cintillo de recomendados
function formatearProducto(array $row, array $recompensas = []): array
{
    $precios = getCalculadora((int)$row['bss_id'], $recompensas)->calcularPrecios($row);

    return [
        'id' => $row['id'],
        'nombre' => ucfirst(mb_strtolower(trim((string)$row['nombre']), 'UTF-8')),
        // Al mayor se muestran las pacas completas que cubre el stock base.
        'stock' => (int)($row['stock_visible'] ?? $row['stock']),
        'mayor' => $row['mayor'],
        'codigo' => trim((string)$row['codigo_barras']),
        'categorias' => $row['categorias_nombres'] ?? '',
        'precio_dolar_visible' => $precios['precio_venta_dolar'],
        'precio_peso_visible' => $precios['precio_venta_peso'],
        'precio_bs_visible' => $precios['precio_venta_bs'],
        'precio_costo' => $precios['precio_dolar_compra'],
        'precio_costo_bs' => $precios['precio_bs_compra'],
        'sucursal' => $row['slug'],
        'tienda_nombre' => $row['nombre_tienda'],
        'cantidadPaca' => $row['cantidad_unidades'],
        'img' => urlImagenProducto($row),
        'bss_id' => $row['bss_id'],
        'id_sucursal' => $row['id_sucursal']
    ];
}

// Construcción de la consulta basada en si hay un slug de tienda o es el marketplace global.
// Al mayor solo se muestra si el stock del producto base cubre al menos una paca.
$where = "p.activo = 0 AND n.tiene_tienda_virtual = 1 AND n.estado = 'activo'
          AND (
              (p.mayor = '1' AND p.cantidad_unidades > 0 AND sb.stock >= p.cantidad_unidades)
              OR
              ((p.mayor IS NULL OR p.mayor <> '1') AND s.stock > 0)
          )";
$params = [];
$types = "";

if ($tienda_slug) {
    $where .= " AND n.slug = ?";
    $params[] = $tienda_slug;
    $types .= "s";
}

// Marketplace: solo productos de tiendas del estado del visitante. El estado
// se calcula en el servidor al renderizar index.php y se envía en la consulta.
$estadoFiltro = trim((string)($_GET['estado'] ?? ''));
if (!$tienda_slug && $estadoFiltro !== '') {
    $where .= " AND n.geo_estado = ?";
    $params[] = $estadoFiltro;
    $types .= "s";
}

if ($mode === 'search_index') {
    $sql = "SELECT p.id, p.nombre, p.codigo_barras, p.precio_compra, p.cantidad_unidades, p.origen, s.stock, s.porcentaje, p.mayor,
                   MAX(CASE WHEN p.mayor = '1' AND p.cantidad_unidades > 0
                            THEN FLOOR(IFNULL(sb.stock, 0) / p.cantidad_unidades)
                            ELSE s.stock END) AS stock_visible,
                   GROUP_CONCAT(DISTINCT cat.nombre SEPARATOR ', ') AS categorias_nombres,
                   np.foto_url, n.slug, COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) AS tienda_nombre, s.bss_id, s.id_sucursal
            FROM productos p
            INNER JOIN negocio_productos np ON p.id = np.producto_id AND np.estado_publicacion = 1
            INNER JOIN negocio n ON np.negocio_id = n.id
            INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
            LEFT JOIN stock sb ON sb.id = s.id_stock AND sb.id_sucursal = s.id_sucursal AND sb.bss_id = s.bss_id
            LEFT JOIN categorias_producto cat ON p.categoria = cat.id
            WHERE {$where}
            GROUP BY p.id, n.id
            ORDER BY p.nombre ASC";
            
    $stmt = $conexion->prepare($sql);
    if (!$stmt) { echo json_encode(['error' => $conexion->error]); exit; }
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $precios = getCalculadora((int)$row['bss_id'], $recompensas)->calcularPrecios($row);
        $nombre = ucfirst(mb_strtolower(trim((string)$row['nombre']), 'UTF-8'));
        $img = urlImagenProducto($row);

        $searchIndex[] = [
            'id' => $row['id'],
            'n' => $nombre,
            'c' => trim((string)$row['codigo_barras']),
            's' => (int)$row['stock_visible'],
            'm' => $row['mayor'],
            'paca' => (int)$row['cantidad_unidades'],
            'ca' => $row['categorias_nombres'] ?? '',
            'pd' => $precios['precio_venta_dolar'],
            'pp' => $precios['precio_venta_peso'],
            'pb' => $precios['precio_venta_bs'],
            'cd' => $precios['dolar_con_recompensa'] ?? $precios['precio_venta_dolar'],
            'cp' => $precios['bs_con_recompensa'] ?? $precios['precio_venta_bs'],
            'pc' => $precios['precio_dolar_compra'],
            'pc_bs' => $precios['precio_bs_compra'],
            'foto_url' => $img,
            'sucursal' => $row['slug'],
            'tn' => $row['tienda_nombre'] ?? '',
            'bss_id' => $row['bss_id'],
            'id_sucursal' => $row['id_sucursal']
        ];
    }
    echo json_encode(['recompensas' => $recompensas, 'searchIndex' => $searchIndex]);
    exit;
}

// Mode Destacados: productos principales elegidos por la tienda (cintillo)
if ($mode === 'destacados') {
    if (!$tienda_slug) {
        echo json_encode(['data' => []]);
        exit;
    }

    $stmtNeg = $conexion->prepare("SELECT id, destacados_activo, destacados_ids FROM negocio WHERE slug = ? LIMIT 1");
    $stmtNeg->bind_param('s', $tienda_slug);
    $stmtNeg->execute();
    $neg = $stmtNeg->get_result()->fetch_assoc();
    $stmtNeg->close();

    $idsDest = [];
    if ($neg && (int)$neg['destacados_activo'] === 1) {
        $tmpDest = json_decode((string)$neg['destacados_ids'], true);
        if (is_array($tmpDest)) {
            foreach (array_slice($tmpDest, 0, 10) as $pidDest) {
                $pidDest = (int)$pidDest;
                if ($pidDest > 0 && !in_array($pidDest, $idsDest, true)) $idsDest[] = $pidDest;
            }
        }
    }
    if (!$idsDest) {
        echo json_encode(['data' => []]);
        exit;
    }

    $listaDest = implode(',', $idsDest);
    $sql = "SELECT p.*, s.stock, s.porcentaje, s.id_sucursal, s.bss_id, n.slug, COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) as nombre_tienda, np.foto_url,
                   MAX(CASE WHEN p.mayor = '1' AND p.cantidad_unidades > 0
                            THEN FLOOR(IFNULL(sb.stock, 0) / p.cantidad_unidades)
                            ELSE s.stock END) AS stock_visible,
                   GROUP_CONCAT(DISTINCT cat.nombre SEPARATOR ', ') AS categorias_nombres
            FROM productos p
            INNER JOIN negocio_productos np ON p.id = np.producto_id AND np.estado_publicacion = 1
            INNER JOIN negocio n ON np.negocio_id = n.id
            INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
            LEFT JOIN stock sb ON sb.id = s.id_stock AND sb.id_sucursal = s.id_sucursal AND sb.bss_id = s.bss_id
            LEFT JOIN categorias_producto cat ON p.categoria = cat.id
            WHERE {$where} AND p.id IN ($listaDest)
            GROUP BY p.id, n.id
            ORDER BY FIELD(p.id, $listaDest)
            LIMIT 10";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) { echo json_encode(['error' => $conexion->error]); exit; }
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $destacados = [];
    while ($row = $result->fetch_assoc()) {
        $destacados[] = formatearProducto($row, $recompensas);
    }

    echo json_encode(['data' => $destacados]);
    exit;
}

// Mode Grid Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24;
$sort = $_GET['order'] ?? '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;

// Order logic
if (!$tienda_slug) {
    // Si estamos en el marketplace global, priorizar los que tienen fotos personalizadas (PRO TIP)
    $order = "(np.foto_url IS NOT NULL AND np.foto_url != '') DESC, RAND()";
} else {
    switch ($sort) {
        case 'name_asc': $order = 'p.nombre ASC'; break;
        case 'name_desc': $order = 'p.nombre DESC'; break;
        default: $order = 'p.id ASC'; break;
    }
}

if ($categoryId) {
    $where .= " AND p.categoria = ?";
    $params[] = $categoryId;
    $types .= "i";
    // Listado por categoría del marketplace: orden estable para que la paginación no repita productos.
    if (!$tienda_slug) $order = 'p.nombre ASC';
}

$grupoFiltro = trim((string)($_GET['grupo'] ?? ''));
if ($grupoFiltro !== '') {
    $where .= " AND p.categoria IN (SELECT id FROM categorias_producto WHERE grupo = ?)";
    $params[] = $grupoFiltro;
    $types .= "s";
    if (!$tienda_slug) $order = 'p.nombre ASC';
}

$offset = ($page - 1) * $limit;

$sql = "SELECT p.*, s.stock, s.porcentaje, s.id_sucursal, s.bss_id, n.slug, COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) as nombre_tienda, np.foto_url,
               MAX(CASE WHEN p.mayor = '1' AND p.cantidad_unidades > 0
                        THEN FLOOR(IFNULL(sb.stock, 0) / p.cantidad_unidades)
                        ELSE s.stock END) AS stock_visible,
               GROUP_CONCAT(DISTINCT cat.nombre SEPARATOR ', ') AS categorias_nombres
        FROM productos p
        INNER JOIN negocio_productos np ON p.id = np.producto_id AND np.estado_publicacion = 1
        INNER JOIN negocio n ON np.negocio_id = n.id
        INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
        LEFT JOIN stock sb ON sb.id = s.id_stock AND sb.id_sucursal = s.id_sucursal AND sb.bss_id = s.bss_id
        LEFT JOIN categorias_producto cat ON p.categoria = cat.id
        WHERE {$where}
        GROUP BY p.id, n.id
        ORDER BY {$order}
        LIMIT ? OFFSET ?";

$paramsGrid = $params;
$typesGrid = $types . "ii";
$paramsGrid[] = $limit;
$paramsGrid[] = $offset;

$stmt = $conexion->prepare($sql);
if (!$stmt) { echo json_encode(['error' => $conexion->error]); exit; }
if (!empty($paramsGrid)) $stmt->bind_param($typesGrid, ...$paramsGrid);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = formatearProducto($row, $recompensas);
}


$countSql = "SELECT COUNT(DISTINCT p.id) total
             FROM productos p
             INNER JOIN negocio_productos np ON p.id = np.producto_id AND np.estado_publicacion = 1
             INNER JOIN negocio n ON np.negocio_id = n.id
             INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id
                         AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
             LEFT JOIN stock sb ON sb.id = s.id_stock AND sb.id_sucursal = s.id_sucursal AND sb.bss_id = s.bss_id
             WHERE {$where}";
$countStmt = $conexion->prepare($countSql);
if (!empty($params)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];

echo json_encode([
    'recompensas' => $recompensas,
    'page' => $page,
    'limit' => $limit,
    'total' => (int)$total,
    'hasMore' => ($offset + $limit) < $total,
    'data' => $products
]);