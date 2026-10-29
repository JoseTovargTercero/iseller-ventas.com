<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');
require_once('../../../iseller_store/core/imagenes.php');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin sesión']);
    exit;
}

$bss_id = $_SESSION['bss_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

/* ─── 1. TOGGLE publicación ─────────────────────────────────────────── */
if ($action === 'toggle') {
    $producto_id     = (int)($_POST['producto_id'] ?? 0);
    $publicado       = (int)($_POST['publicado']   ?? 0);
    $sucursal_id     = (int)($_POST['sucursal_id'] ?? 0);

    if (!$producto_id) { echo json_encode(['ok' => false, 'msg' => 'ID inválido']); exit; }

    // Verificar que el producto pertenece a este negocio (mismo bss_id en stock)
    $check = $conexion->prepare("SELECT id FROM stock WHERE id_producto = ? AND bss_id = ? LIMIT 1");
    $check->bind_param("ii", $producto_id, $bss_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        echo json_encode(['ok' => false, 'msg' => 'Producto no pertenece a este negocio']);
        exit;
    }
    $check->close();

    if ($publicado === 1) {
        // Upsert en negocio_productos con estado 1 y la sucursal seleccionada
        $stmt = $conexion->prepare(
            "INSERT INTO negocio_productos (negocio_id, producto_id, estado_publicacion, sucursal_id)
             VALUES (?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE estado_publicacion = 1, sucursal_id = VALUES(sucursal_id)"
        );
        $stmt->bind_param("iii", $bss_id, $producto_id, $sucursal_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Upsert en negocio_productos con estado 0 (se limpia la sucursal o se mantiene)
        $stmt = $conexion->prepare(
            "INSERT INTO negocio_productos (negocio_id, producto_id, estado_publicacion)
             VALUES (?, ?, 0)
             ON DUPLICATE KEY UPDATE estado_publicacion = 0"
        );
        $stmt->bind_param("ii", $bss_id, $producto_id);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['ok' => true]);
    exit;
}

/* ─── 2. UPLOAD de foto ──────────────────────────────────────────────── */
if ($action === 'upload_foto') {
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    if (!$producto_id || empty($_FILES['foto'])) {
        echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']); exit;
    }

    $file   = $_FILES['foto'];

    // Valida contenido real y recodifica a WebP (no confía en la extensión).
    $dir = __DIR__ . '/../../../iseller_store/assets/img/tiendas/' . $bss_id . '/';
    $archivoFoto = imagen_guardar_webp($file, $dir, (string)$producto_id, 800, 5 * 1024 * 1024);
    if (!$archivoFoto) {
        echo json_encode(['ok' => false, 'msg' => 'La imagen no es válida. Usa JPG, PNG o WEBP de hasta 5 MB.']);
        exit;
    }

    $foto_url = "assets/img/tiendas/{$bss_id}/{$archivoFoto}";

    // Upsert: solo guarda foto, NO toca estado_publicacion
    $stmt = $conexion->prepare(
        "INSERT INTO negocio_productos (negocio_id, producto_id, foto_url, estado_publicacion)
         VALUES (?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE foto_url = VALUES(foto_url)"
    );
    $stmt->bind_param("iis", $bss_id, $producto_id, $foto_url);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'foto_url' => $foto_url]);
    exit;
}

/* ─── 2b. FOTO descargada desde una URL (arrastrar desde Google Imágenes) ── */
if ($action === 'descargar_foto') {
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $url = trim((string)($_POST['url'] ?? ''));

    if (!$producto_id || $url === '') {
        echo json_encode(['ok' => false, 'msg' => 'Datos incompletos']); exit;
    }
    if (!function_exists('curl_init')) {
        echo json_encode(['ok' => false, 'msg' => 'El servidor no tiene cURL']); exit;
    }

    $partes = parse_url($url);
    $host = strtolower($partes['host'] ?? '');
    // Solo HTTPS y dominios de miniaturas de buscadores: evita SSRF hacia la red interna.
    $dominios = ['gstatic.com', 'googleusercontent.com', 'ggpht.com', 'bing.com', 'bing.net'];
    $hostValido = false;
    foreach ($dominios as $d) {
        if ($host === $d || substr($host, -strlen($d) - 1) === '.' . $d) {
            $hostValido = true;
            break;
        }
    }
    if (!$hostValido || ($partes['scheme'] ?? '') !== 'https') {
        echo json_encode(['ok' => false, 'msg' => 'Solo se pueden descargar imágenes desde Google o Bing']); exit;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,          // sin redirecciones: la whitelist no se puede saltar
        CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_MAXFILESIZE    => 5 * 1024 * 1024,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $bytes = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    if (!is_string($bytes) || $status !== 200 || strlen($bytes) < 100 || strlen($bytes) > 5 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'msg' => 'No se pudo descargar la imagen']); exit;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'foto');
    if (!$tmp || file_put_contents($tmp, $bytes) === false) {
        echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar la imagen']); exit;
    }

    // Valida contenido real y recodifica a WebP, igual que una subida normal.
    $dir = __DIR__ . '/../../../iseller_store/assets/img/tiendas/' . $bss_id . '/';
    $archivoFoto = imagen_guardar_webp(
        ['tmp_name' => $tmp, 'size' => strlen($bytes), 'error' => UPLOAD_ERR_OK],
        $dir,
        (string)$producto_id,
        800,
        5 * 1024 * 1024,
        25000000,
        85,
        false
    );
    @unlink($tmp);

    if (!$archivoFoto) {
        echo json_encode(['ok' => false, 'msg' => 'La imagen descargada no es válida. Usa JPG, PNG o WEBP de hasta 5 MB.']);
        exit;
    }

    $foto_url = "assets/img/tiendas/{$bss_id}/{$archivoFoto}";
    $stmt = $conexion->prepare(
        "INSERT INTO negocio_productos (negocio_id, producto_id, foto_url, estado_publicacion)
         VALUES (?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE foto_url = VALUES(foto_url)"
    );
    $stmt->bind_param("iis", $bss_id, $producto_id, $foto_url);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'foto_url' => $foto_url]);
    exit;
}

/* ─── 3. DELETE foto ─────────────────────────────────────────────────── */
if ($action === 'delete_foto') {
    $producto_id = (int)($_POST['producto_id'] ?? 0);

    $stmt = $conexion->prepare("UPDATE negocio_productos SET foto_url = NULL WHERE negocio_id = ? AND producto_id = ?");
    $stmt->bind_param("ii", $bss_id, $producto_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true]);
    exit;
}

/* ─── 4. LISTAR productos (paginación + búsqueda) ─────────────────────── */
if ($action === 'listar' || ($action === '' && $_SERVER['REQUEST_METHOD'] === 'GET')) {
    $busqueda = '%' . ($conexion->real_escape_string($_GET['q'] ?? '')) . '%';
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $limit    = 20;
    $offset   = ($page - 1) * $limit;

    // Una sola pasada sobre stock con GROUP BY, en vez de una subconsulta
    // correlacionada por cada fila (que re-escaneaba stock ~20 veces).
    $sql = "SELECT p.id, p.nombre, p.codigo_barras,
                   np.estado_publicacion, np.foto_url, np.sucursal_id,
                   IFNULL(SUM(s.stock), 0) AS stock_cantidad
            FROM stock s
            INNER JOIN sucursales su ON su.id = s.id_sucursal AND su.bss_id = s.bss_id
            INNER JOIN productos p ON p.id = s.id_producto
            LEFT JOIN negocio_productos np
                   ON np.producto_id = p.id AND np.negocio_id = ?
            WHERE s.bss_id = ? AND p.activo = 0 AND p.nombre LIKE ?
            GROUP BY p.id, p.nombre, p.codigo_barras, np.estado_publicacion, np.foto_url, np.sucursal_id
            ORDER BY np.estado_publicacion DESC, p.nombre ASC
            LIMIT ? OFFSET ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iisii", $bss_id, $bss_id, $busqueda, $limit, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $countStmt = $conexion->prepare(
        "SELECT COUNT(DISTINCT s.id_producto) total
         FROM stock s
         INNER JOIN sucursales su ON su.id = s.id_sucursal AND su.bss_id = s.bss_id
         INNER JOIN productos p ON p.id = s.id_producto
         WHERE s.bss_id = ? AND p.activo = 0 AND p.nombre LIKE ?"
    );
    $countStmt->bind_param("is", $bss_id, $busqueda);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    echo json_encode([
        'ok'    => true,
        'data'  => $rows,
        'total' => (int)$total,
        'page'  => $page,
        'pages' => (int)ceil($total / $limit),
    ]);
    exit;
}

/* ─── 5. DETALLE de producto ──────────────────────────────────────────── */
if ($action === 'detalle') {
    $producto_id = (int)($_GET['id'] ?? 0);
    if (!$producto_id) { echo json_encode(['ok' => false, 'msg' => 'ID requerido']); exit; }

    // Primero: obtener datos del negocio_productos (foto, publicación, sucursal)
    $stmtNP = $conexion->prepare(
        "SELECT np.foto_url, np.estado_publicacion, np.sucursal_id,
                su.nombre AS sucursal_nombre
         FROM negocio_productos np
         LEFT JOIN sucursales su ON su.id = np.sucursal_id
         WHERE np.producto_id = ? AND np.negocio_id = ?
         LIMIT 1"
    );
    $stmtNP->bind_param("ii", $producto_id, $bss_id);
    $stmtNP->execute();
    $np = $stmtNP->get_result()->fetch_assoc();
    $stmtNP->close();

    // Segundo: obtener datos del producto + stock
    $sucursal_filter = "";
    $types = "ii";
    $params = [$bss_id, $producto_id];

    if ($np && !empty($np['sucursal_id'])) {
        $sucursal_filter = " AND s.id_sucursal = ?";
        $types = "iii";
        $params = [$bss_id, $np['sucursal_id'], $producto_id];
    }

    $stmt = $conexion->prepare(
        "SELECT p.id, p.nombre, p.codigo_barras, p.precio_compra, p.mayor,
                p.cantidad_unidades, p.origen, p.proveedor,
                s.stock, s.porcentaje
         FROM productos p
         INNER JOIN stock s ON s.id_producto = p.id AND s.bss_id = ?{$sucursal_filter}
         WHERE p.id = ? AND p.activo = 0
         LIMIT 1"
    );
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) { echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado']); exit; }

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $base = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/iseller_store/';

    $foto = '';
    if ($np && !empty($np['foto_url'])) {
        $foto = $base . $np['foto_url'];
    } else {
        $rootDir = realpath(__DIR__ . '/../../..');
        $imgPath = $rootDir . '/iseller_store/assets/img/stock/' . $row['id'] . '.png';
        $optPath = $rootDir . '/iseller_store/assets/img/stock/optimized/' . $row['id'] . '.webp';
        if (file_exists($optPath)) {
            $foto = $base . "assets/img/stock/optimized/{$row['id']}.webp";
        } elseif (file_exists($imgPath)) {
            $foto = $base . "assets/img/stock/{$row['id']}.png";
        }
    }

    echo json_encode([
        'ok'   => true,
        'data' => [
            'id'            => (int)$row['id'],
            'nombre'        => ucfirst(mb_strtolower(trim($row['nombre']), 'UTF-8')),
            'codigo'        => trim($row['codigo_barras'] ?? ''),
            'precio_compra' => (float)$row['precio_compra'],
            'mayor'         => (int)$row['mayor'],
            'unidades'      => (int)$row['cantidad_unidades'],
            'stock'         => (int)$row['stock'],
            'porcentaje'    => (float)$row['porcentaje'],
            'origen'        => $row['origen'] ?? '',
            'proveedor'     => $row['proveedor'] ?? '',
            'foto_url'      => $foto,
            'publicado'     => ($np && $np['estado_publicacion'] == 1),
            'sucursal'      => ($np['sucursal_nombre'] ?? '—'),
        ]
    ]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);

