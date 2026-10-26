<?php
require_once('iseller_store/core/check_maintenance.php');
require_once('iseller_store/core/db.php');
require_once('iseller_store/core/session.php');
require_once('iseller_store/core/_tasas_cambio.php');
require_once('iseller_store/core/reputacion.php');
require_once('iseller_store/core/eslogan.php');

// El texto de la tienda (eslogan, nombre, comunidad) es utf8mb4: con la conexión
// en utf8 (3 bytes) los emojis se mostrarían como "????".
$conexion->set_charset('utf8mb4');

$tienda_slug = $_GET['tienda_slug'] ?? null;
$es_tienda_individual = false;
$negocio_data = null;
$bss_id = null;
$sucursal = null;

// Lógica de enrutamiento basada en la URL (si no se usa .htaccess para pasar $_GET)
$request_uri = $_SERVER['REQUEST_URI'];
$path = trim((string) parse_url($request_uri, PHP_URL_PATH), '/');
// Quitar el subdirectorio del proyecto (ej: /iseller-tiendas.com en local) para quedarnos con el slug
$base_dir = trim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_dir !== '' && ($path === $base_dir || strpos($path, $base_dir . '/') === 0)) {
    $path = ltrim(substr($path, strlen($base_dir)), '/');
}
// El nombre de la tienda vive en el directorio raíz: el primer segmento de la URL
$path = explode('/', $path)[0];
// Palabras clave que nunca son una tienda
$reservados = ['index.php', 'index', 'inventario'];
if (in_array(strtolower((string)$tienda_slug), $reservados, true)) {
    $tienda_slug = null;
}
// Evitar que lea archivos/directorios reales ('iseller_store', 'admin', etc.) o palabras clave
if (empty($tienda_slug) && $path !== '' && !in_array(strtolower($path), $reservados, true) && !is_dir(__DIR__ . '/' . $path) && !file_exists(__DIR__ . '/' . $path)) {
    $tienda_slug = $path;
}

if ($tienda_slug) {
    // Buscar el negocio en la base de datos unificada
    $stmtNegocio = $conexion->prepare("SELECT id, nombre, logo_url, portada_url, config_delivery, lat, lng, nombre_visible, eslogan, categoria, reputacion_total, reputacion_recomiendan, reputacion_pct, geo_estado, geo_municipio FROM negocio WHERE slug = ? AND tiene_tienda_virtual = 1 AND estado = 'activo'");
    if ($stmtNegocio) {
        $stmtNegocio->bind_param("s", $tienda_slug);
        $stmtNegocio->execute();
        $resNegocio = $stmtNegocio->get_result();
        if ($negocio_data = $resNegocio->fetch_assoc()) {
            $es_tienda_individual = true;
            $bss_id = $negocio_data['id']; // Usamos el ID del negocio como bss_id

            // Si la BD conserva una imagen que ya no existe en disco, no se
            // imprime su URL: evitamos el 404 y usamos el placeholder.
            foreach (['logo_url', 'portada_url'] as $imgKey) {
                if (!empty($negocio_data[$imgKey]) && !is_file(__DIR__ . '/iseller_store/' . ltrim($negocio_data[$imgKey], '/'))) {
                    $negocio_data[$imgKey] = '';
                }
            }
        }
        $stmtNegocio->close();
    }

    // Si se paso un slug pero no se encontro la tienda (o no esta activa/no tiene tienda virtual)
    if (!$es_tienda_individual) {
        http_response_code(404);
        $protocol404 = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host404 = $_SERVER['HTTP_HOST'];
        $script_dir404 = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $base_url_404 = $protocol404 . '://' . $host404 . $script_dir404 . '/iseller_store/';
?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Tienda no encontrada | iSeller</title>
            <base href="<?= $base_url_404 ?>">
            <link href="../publico/vendors/bootstrap5/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">
            <link rel="stylesheet" href="assets/css/global-styles.css">
            <style>
                body {
                    background: #f8f9fa;
                    min-height: 100vh;
                    display: flex;
                    flex-direction: column;
                }

                .not-found-hero {
                    flex: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 120px 24px 80px;
                    text-align: center;
                }

                .not-found-icon {
                    width: 72px;
                    height: 72px;
                    margin: 0 auto 24px;
                    background: #fff;
                    border: 1px solid #e5e7eb;
                    border-radius: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                }

                .not-found-icon i {
                    font-size: 30px;
                    color: #9ca3af;
                }

                .not-found-hero h1 {
                    font-size: 20px;
                    font-weight: 700;
                    color: #111827;
                    margin-bottom: 8px;
                }

                .not-found-hero p {
                    font-size: 14px;
                    color: #6b7280;
                    max-width: 320px;
                    margin: 0 auto 28px;
                    line-height: 1.6;
                }

                .not-found-actions {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                    flex-wrap: wrap;
                }

                .not-found-actions .btn-primary {
                    background: linear-gradient(135deg, #2dd4a0, #25b88a);
                    border: none;
                    border-radius: 10px;
                    padding: 10px 24px;
                    font-weight: 600;
                    font-size: 13px;
                    box-shadow: 0 2px 8px rgba(45, 212, 160, 0.25);
                    transition: all 0.3s cubic-bezier(0.22, 1, 0.36, 1);
                }

                .not-found-actions .btn-primary:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 4px 14px rgba(45, 212, 160, 0.35);
                }

                .not-found-actions .btn-outline-secondary {
                    border-radius: 10px;
                    padding: 10px 24px;
                    font-weight: 600;
                    font-size: 13px;
                    transition: all 0.2s;
                }
            </style>
        </head>

        <body>
            <?php include 'iseller_store/includes/navbar.php'; ?>
            <div class="not-found-hero">
                <div>
                    <div class="not-found-icon">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h1>Tienda no encontrada</h1>
                    <p>Lo sentimos, la tienda que estás buscando no existe, ha sido desactivada o su enlace es incorrecto.</p>
                    <div class="not-found-actions">
                        <a href="/" class="btn btn-primary">
                            <i class="bi bi-house-door me-1"></i> Ir al inicio
                        </a>
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
            <script src="../publico/vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
            <script type="module" src="../publico/vendors/ionicons/ionicons.esm.js"></script>
            <script nomodule src="../publico/vendors/ionicons/ionicons.js"></script>
        </body>

        </html>
<?php
        exit;
    }
}

// Si no es tienda individual, usamos el bss_id predeterminado temporalmente o null para indicar global
if (!$es_tienda_individual) {
    $bss_id = 3; // Fallback for backwards compatibility with calculations if needed
}

// Última página pública visitada: registro.php vuelve aquí al terminar
$_SESSION['return_after_auth'] = $request_uri;

// OJO: no guardar $bss_id en la sesión desde la portada pública. El panel de la
// tienda (configurar/session.php) usa $_SESSION['bss_id'] y un visitante anónimo
// quedaría autenticado como dueño de la tienda.

require("iseller_store/core/_calculadrora_precios.php");
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);

include 'iseller_store/core/la-carta.php';
$cart = new Cart;

// Datos básicos del usuario (para la foto del navbar y direcciones por defecto)
$loyaltyData = [];
if (isLoggedIn()) {
    $uid = getUserId();
    $stmtLoyalty = $conexion_store->prepare("SELECT foto, nombre, telefono FROM tienda_usuarios WHERE id = ?");
    $stmtLoyalty->bind_param("i", $uid);
    $stmtLoyalty->execute();
    if ($rowLoyalty = $stmtLoyalty->get_result()->fetch_assoc()) {
        $loyaltyData = [
            'foto' => $rowLoyalty['foto'],
            'nombre' => $rowLoyalty['nombre'],
            'telefono' => $rowLoyalty['telefono']
        ];
    }
    $stmtLoyalty->close();
}

// --- FETCH STORE CONFIGURATION ---
$store_config = [
    'horario' => 'Lunes a Sábado: 8:00 AM - 12:30 PM / 3:00 PM - 9:00 PM<br>Domingos: 9:00 AM - 12:30 PM',
    'horario_delivery' => '8:00 AM - 11:30 AM / 2:00 PM - 6:00 PM',
    'direccion' => 'YolaMarket'
];

if ($es_tienda_individual && !empty($negocio_data['config_delivery'])) {
    $deliveryConf = json_decode($negocio_data['config_delivery'], true);
    if (isset($deliveryConf['tipo'])) {
        $store_config['horario_delivery'] = "Delivery: " . ucfirst($deliveryConf['tipo']) . " - Zonas: " . ucfirst($deliveryConf['zonas'] ?? 'municipio');
    }
    $store_config['direccion'] = $negocio_data['nombre_visible'] ?: $negocio_data['nombre'];
}

// Eslogan bajo el nombre: el que configuró la tienda o el de su categoría
$tienda_slogan = '';
if ($es_tienda_individual) {
    $tienda_slogan = eslogan_mostrar($negocio_data['eslogan'] ?? '', $negocio_data['categoria'] ?? '');
}

// Favorito del cliente para esta tienda
$es_favorito = false;
if ($es_tienda_individual && isLoggedIn()) {
    require_once('iseller_store/core/favoritos.php');
    $es_favorito = es_favorito($conexion_store, (int)getUserId(), (int)$negocio_data['id']);
}

// Registro de métricas
require_once('iseller_store/core/metrics.php');
// registrarVisita($conexion_store);

// Tiendas destacadas para el marketplace (solo cuando no hay tienda individual)
$tiendas_destacadas = [];
if (!$es_tienda_individual) {
    $resTiendas = $conexion->query("
        SELECT n.id, n.nombre, n.nombre_visible, n.logo_url, n.portada_url, n.categoria, n.slug,
               (SELECT COUNT(*) FROM negocio_productos np
                WHERE np.negocio_id = n.id AND np.estado_publicacion = 1) AS total_productos
        FROM negocio n
        WHERE n.tiene_tienda_virtual = 1
          AND n.estado = 'activo'
          AND EXISTS (
              SELECT 1 FROM negocio_productos np
              WHERE np.negocio_id = n.id AND np.estado_publicacion = 1
          )
        ORDER BY total_productos DESC
    ");
    if ($resTiendas) {
        $tiendas_destacadas = $resTiendas->fetch_all(MYSQLI_ASSOC);
    }
}

// Logos comprimidos para el listado de tiendas destacadas
require_once(__DIR__ . '/iseller_store/core/tienda_logo.php');

$repNivel = null;
$repReviews = [];
if ($es_tienda_individual && $negocio_data) {
    $repNivel = reputacion_nivel($negocio_data['reputacion_total'], $negocio_data['reputacion_pct']);

    $stmtRep = $conexion->prepare("
        SELECT v.recomienda, v.comentario, v.created_at, u.nombre
        FROM tienda_reputacion_votos v
        LEFT JOIN tienda_usuarios u ON u.id = v.usuario_id
        WHERE v.negocio_id = ? AND v.oculto = 0
          AND v.comentario IS NOT NULL AND v.comentario <> ''
        ORDER BY v.id DESC
        LIMIT 10
    ");
    $stmtRep->bind_param("i", $negocio_data['id']);
    $stmtRep->execute();
    $repReviews = $stmtRep->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtRep->close();
}

// Tiendas cercanas: primero las del municipio del usuario (o el de su tienda),
// luego el resto de su estado. Sin ubicación de referencia no hay listado.
$tiendas_cercanas = ['municipio' => [], 'estado' => []];
$geo_cerca_estado = '';
$geo_cerca_municipio = '';

// Cliente logueado en la tienda: su ubicación manda.
if (isLoggedIn()) {
    $stmtGeoCerca = $conexion_store->prepare("SELECT geo_estado, geo_municipio FROM tienda_usuarios WHERE id = ? LIMIT 1");
    $uidCerca = (int)getUserId();
    $stmtGeoCerca->bind_param("i", $uidCerca);
    $stmtGeoCerca->execute();
    $geoUsuario = $stmtGeoCerca->get_result()->fetch_assoc();
    $stmtGeoCerca->close();
    if ($geoUsuario && !empty($geoUsuario['geo_estado']) && !empty($geoUsuario['geo_municipio'])) {
        $geo_cerca_estado = (string)$geoUsuario['geo_estado'];
        $geo_cerca_municipio = (string)$geoUsuario['geo_municipio'];
    }
}

// Dueño logueado en el panel (misma sesión PHP): usar la ubicación de su tienda.
if (($geo_cerca_estado === '' || $geo_cerca_municipio === '') && !empty($_SESSION['bss_id'])) {
    $stmtGeoTienda = $conexion->prepare("SELECT geo_estado, geo_municipio FROM negocio WHERE id = ? LIMIT 1");
    $bssCerca = (int)$_SESSION['bss_id'];
    $stmtGeoTienda->bind_param("i", $bssCerca);
    $stmtGeoTienda->execute();
    $geoTienda = $stmtGeoTienda->get_result()->fetch_assoc();
    $stmtGeoTienda->close();
    if ($geoTienda && !empty($geoTienda['geo_estado']) && !empty($geoTienda['geo_municipio'])) {
        $geo_cerca_estado = (string)$geoTienda['geo_estado'];
        $geo_cerca_municipio = (string)$geoTienda['geo_municipio'];
    }
}

if ($es_tienda_individual) {
    // Si nadie tiene ubicación, usar la de la tienda que se está viendo.
    if ($geo_cerca_estado === '' || $geo_cerca_municipio === '') {
        $geo_cerca_estado = (string)($negocio_data['geo_estado'] ?? '');
        $geo_cerca_municipio = (string)($negocio_data['geo_municipio'] ?? '');
    }
}

if ($geo_cerca_estado !== '' && $geo_cerca_municipio !== '') {
    $stmtCerca = $conexion->prepare("
        SELECT n.id, n.nombre, n.nombre_visible, n.logo_url, n.categoria, n.slug, n.geo_municipio
        FROM negocio n
        WHERE n.tiene_tienda_virtual = 1
          AND n.estado = 'activo'
          AND n.slug IS NOT NULL AND n.slug <> ''
          AND n.geo_estado = ?
          AND EXISTS (
              SELECT 1 FROM negocio_productos np
              WHERE np.negocio_id = n.id AND np.estado_publicacion = 1
          )
        ORDER BY (n.geo_municipio = ?) DESC, COALESCE(NULLIF(n.nombre_visible, ''), n.nombre)
    ");
    $stmtCerca->bind_param("ss", $geo_cerca_estado, $geo_cerca_municipio);
    $stmtCerca->execute();
    $resCerca = $stmtCerca->get_result();
    while ($filaCerca = $resCerca->fetch_assoc()) {
        if ($filaCerca['geo_municipio'] === $geo_cerca_municipio) {
            $tiendas_cercanas['municipio'][] = $filaCerca;
        } else {
            $tiendas_cercanas['estado'][] = $filaCerca;
        }
    }
    $stmtCerca->close();
}

$grupos_cercanos = [];
if ($tiendas_cercanas['municipio']) {
    $grupos_cercanos['Mismo municipio · ' . $geo_cerca_municipio] = $tiendas_cercanas['municipio'];
}
if ($tiendas_cercanas['estado']) {
    $grupos_cercanos['Mismo estado · ' . $geo_cerca_estado] = $tiendas_cercanas['estado'];
}

?>

<!DOCTYPE html>
<html lang='es'>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $base_url = $protocol . '://' . $host . $script_dir . '/iseller_store/';
    ?>
    <base href="<?= $base_url ?>">
    <script>
        // La ruta de fotos y logos vive en la BD: si el archivo no existe, usamos el placeholder
        function imgFallback(img) { img.onerror = null; img.src = 'assets/img/no-images.png'; }

        // En el grid, una foto rota se pinta igual que un producto sin imagen.
        function productImgError(img) {
            img.onerror = null;
            const wrapper = img.parentElement;
            if (!wrapper) return;
            wrapper.classList.add('no-img');
            wrapper.innerHTML = '<div class="no-img-inner"><i class="bi bi-box-seam"></i></div>';
        }
    </script>

    <title>iSeller Store | Ecommerce para comprar en línea fácil y seguro</title>

    <!-- SEO Básico -->
    <meta name="description" content="iSeller Store es un ecommerce donde puedes comprar productos en línea de forma segura, rápida y confiable. Descubre ofertas y paga fácilmente.">
    <meta name="keywords" content="ecommerce, tienda online, comprar en línea, iseller store, compras online, marketplace">
    <meta name="author" content="iSeller Store">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="iSeller Store | Compra en línea fácil y seguro">
    <meta property="og:description" content="Compra fácil y seguro en iSeller Store. Regístrate y obtén $5 de regalo.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://iseller-tiendas.com/">
    <meta property="og:image" content="https://iseller-tiendas.com/iseller_store/assets/img/og-image.jpg">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="iSeller Store | Compra en línea fácil y seguro">
    <meta name="twitter:description" content="Ecommerce confiable para comprar productos en línea de forma rápida y segura.">
    <meta name="twitter:image" content="https://iseller-tiendas.com/iseller_store/assets/img/og-image.jpg">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo-tienda.png">
    <link rel="apple-touch-icon" href="assets/img/logo-tienda.png">

    <!-- Bootstrap CSS -->
    <link href="../publico/vendors/bootstrap5/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">

    <!-- Merchant CTA fonts -->
    <link href="../publico/vendors/fonts/fonts.css" rel="stylesheet">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">

    <!-- Chat System CSS -->
    <link rel="stylesheet" href="assets/css/chat.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="../publico/vendors/leaflet/leaflet.css" crossorigin="">
    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "vkl5wdgfvq");
    </script>
    <style>
        .imagen-cuadrada {
            width: 100%;
            /* O el ancho que necesites */
            aspect-ratio: 1 / 1;
            /* Mantiene proporción 1:1 */
            object-fit: cover;
            /* Ajusta la imagen sin aplastarla */
        }

        /* Custom Modal Styles */
        .modal-content {
            border: none;
        }

        .modal-xl-custom {
            max-width: 1100px;
        }

        .list-group-item-action {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .list-group-item-action:hover {
            background-color: #f8fafc;
            border-left-color: var(--primary-color);
            padding-left: 2rem !important;
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem 1.75rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .empty-state {
            text-align: center;
            padding: 3.5rem 1.5rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.4;
        }

        /* ---- Premium Cart Modal ---- */
        #modalCarrito .modal-content {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(0, 0, 0, 0.03);
        }

        #modalCarrito .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            background: linear-gradient(135deg, #fafffe, #f0faf4);
        }

        #modalCarrito .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        #modalCarrito .modal-title .cart-icon-badge {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: var(--primary-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 4px 12px rgba(111, 175, 122, 0.25);
        }

        #modalCarrito .btn-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.08);
            opacity: 1;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            filter: none;
        }

        #modalCarrito .btn-close::before {
            content: '\2715';
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            line-height: 1;
        }

        #modalCarrito .btn-close:hover {
            background: rgba(0, 0, 0, 0.12);
            transform: rotate(90deg);
        }

        #modalCarrito .btn-close:hover::before {
            color: var(--text-primary);
        }

        #modalCarrito .modal-body {
            padding: 0;
        }

        #cart-items {
            padding: 1rem 1.25rem !important;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.12) transparent;
        }

        #cart-items::-webkit-scrollbar {
            width: 5px;
        }

        #cart-items::-webkit-scrollbar-track {
            background: transparent;
        }

        #cart-items::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.12);
            border-radius: 10px;
        }

        .store-cart-group {
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
            border-radius: 14px !important;
            padding: 0.85rem !important;
            margin-bottom: 0.75rem !important;
            background: #fafcfb !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .store-cart-group h6 {
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-secondary) !important;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.06) !important;
            padding-bottom: 0.6rem !important;
            margin-bottom: 0.6rem !important;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .store-cart-group h6 i {
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .cart-item {
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            background: #fff;
            border-radius: 12px !important;
            margin-bottom: 0.5rem !important;
            padding: 0.75rem 0.85rem !important;
            border: 1px solid rgba(0, 0, 0, 0.04) !important;
            position: relative;
        }

        .cart-item:last-child {
            margin-bottom: 0 !important;
        }

        .cart-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            border-color: rgba(111, 175, 122, 0.2) !important;
        }

        .cart-item-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.88rem;
            line-height: 1.3;
            letter-spacing: -0.01em;
        }

        .cart-item-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 0.9rem;
            letter-spacing: -0.02em;
        }

        .cart-item .qty-badge {
            font-size: 0.7rem;
            font-weight: 600;
            background: rgba(111, 175, 122, 0.1);
            color: var(--primary-dark);
            padding: 0.25rem 0.55rem;
            border-radius: 6px;
            letter-spacing: 0.02em;
        }

        .cart-item .btn-remove-item {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            border: none;
            background: rgba(239, 68, 68, 0.06);
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
            opacity: 0.6;
        }

        .cart-item .btn-remove-item:hover {
            background: rgba(239, 68, 68, 0.12);
            opacity: 1;
            transform: scale(1.05);
        }

        /* Cart Footer */
        #cart-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.06) !important;
            padding: 1.25rem 1.5rem !important;
            background: #fafcfb !important;
        }

        .cart-total-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .cart-total-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-secondary);
        }

        .cart-total-amount {
            text-align: right;
            line-height: 1.2;
        }

        .cart-total-amount .total-dolar {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.03em;
        }

        .cart-total-amount .total-bs {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .cart-total-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.06), transparent);
            margin: 0.75rem 0;
        }

        .btn-checkout {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            font-weight: 700;
            padding: 0.85rem;
            border-radius: 14px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            font-size: 0.95rem;
            letter-spacing: -0.01em;
            position: relative;
            overflow: hidden;
        }

        .btn-checkout::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(111, 175, 122, 0.35);
            color: white;
        }

        .btn-checkout:hover::before {
            opacity: 1;
        }

        .btn-checkout:active {
            transform: translateY(0);
        }

        .cart-actions-row {
            display: flex;
            gap: 0.5rem;
        }

        .cart-actions-row .btn {
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.6rem;
            transition: all 0.2s ease;
        }

        .btn-save-cart {
            border: 1px solid rgba(111, 175, 122, 0.25);
            color: var(--primary-dark);
            background: rgba(111, 175, 122, 0.06);
        }

        .btn-save-cart:hover {
            background: rgba(111, 175, 122, 0.12);
            border-color: rgba(111, 175, 122, 0.35);
        }

        .btn-save-cart-hero {
            background: linear-gradient(135deg, rgba(111, 175, 122, 0.12) 0%, rgba(78, 143, 99, 0.18) 100%);
            border: 1.5px solid rgba(111, 175, 122, 0.35);
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.65rem 0.8rem;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .btn-save-cart-hero:hover {
            background: linear-gradient(135deg, rgba(111, 175, 122, 0.2) 0%, rgba(78, 143, 99, 0.28) 100%);
            border-color: rgba(111, 175, 122, 0.55);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(111, 175, 122, 0.15);
        }

        .btn-load-cart-hero {
            background: linear-gradient(135deg, rgba(136, 152, 180, 0.1) 0%, rgba(100, 120, 160, 0.15) 100%);
            border: 1.5px solid rgba(136, 152, 180, 0.3);
            color: #4a5a76;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.65rem 0.8rem;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .btn-load-cart-hero:hover {
            background: linear-gradient(135deg, rgba(136, 152, 180, 0.18) 0%, rgba(100, 120, 160, 0.22) 100%);
            border-color: rgba(136, 152, 180, 0.5);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(136, 152, 180, 0.12);
        }

        .cart-quick-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-clear-cart {
            border: 1px solid rgba(0, 0, 0, 0.08);
            color: var(--text-secondary);
            background: transparent;
        }

        .btn-clear-cart:hover {
            background: rgba(239, 68, 68, 0.06);
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.2);
        }

        /* Empty cart state */
        .cart-empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .cart-empty-state .empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: rgba(111, 175, 122, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .cart-empty-state .empty-icon i {
            font-size: 2rem;
            color: var(--primary-color);
            opacity: 0.6;
        }

        .cart-empty-state h6 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.35rem;
            font-size: 1rem;
        }

        .cart-empty-state p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin: 0;
        }

        /* Cart Flying Animation */
        .btn-add-to-car {
            position: relative;
        }

        .btn-add-to-car .cart-item-anim {
            position: absolute;
            height: 24px;
            width: 24px;
            top: -10px;
            right: -10px;
            display: none;
            z-index: 1000;
        }

        .btn-add-to-car .cart-item-anim:before {
            content: '1';
            display: block;
            line-height: 24px;
            height: 24px;
            width: 24px;
            font-size: 12px;
            font-weight: 600;
            background: #2bd156;
            color: white;
            border-radius: 20px;
            text-align: center;
        }

        .btn-add-to-car.sendtocart .cart-item-anim {
            display: block;
            animation: xAxis 1s forwards cubic-bezier(1.000, 0.440, 0.840, 0.165);
        }

        .btn-add-to-car.sendtocart .cart-item-anim:before {
            animation: yAxis 1s alternate forwards cubic-bezier(0.165, 0.840, 0.440, 1.000);
        }

        .btn-cart.shake {
            animation: shakeCart .4s ease-in-out forwards;
        }

        @keyframes xAxis {
            100% {
                transform: translateX(calc(100vw - 150px));
                /* Rough estimate to top-right */
            }
        }

        @keyframes yAxis {
            100% {
                transform: translateY(calc(-100vh + 100px));
                /* Rough estimate to top-right */
            }
        }

        @keyframes shakeCart {
            25% {
                transform: translateX(6px);
            }

            50% {
                transform: translateX(-4px);
            }

            75% {
                transform: translateX(2px);
            }

            100% {
                transform: translateX(0);
            }
        }

        /* Skeleton Loader Styles */
        .skeleton-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            height: 100%;
        }

        .skeleton-img {
            width: 100%;
            aspect-ratio: 1/1;
            background: #f3f4f6;
        }

        .skeleton-body {
            padding: 1.25rem;
        }

        .skeleton-text {
            height: 1rem;
            background: #f3f4f6;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }

        .skeleton-price {
            height: 1.5rem;
            width: 60%;
            background: #f3f4f6;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .skeleton-button {
            height: 32px;
            background: #f3f4f6;
            border-radius: var(--radius-md);
        }

        .shimmer {
            position: relative;
            overflow: hidden;
        }

        .shimmer::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }


        .text-sm {
            font-size: 70% !important;
        }

        .text-xs {
            font-size: 60% !important;
        }

        .badge-aliado {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 99px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            margin-bottom: 0.5rem;
        }

        .badge-aliado i {
            font-size: 0.8rem;
        }

        /* Badge de tienda origen — aparece en modo Marketplace */
        .badge-tienda-origen {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 0.18rem 0.55rem;
            border-radius: 99px;
            text-decoration: none;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            white-space: nowrap;
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge-tienda-origen:hover {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            box-shadow: 0 2px 6px rgba(29, 78, 216, 0.15);
            color: #1e3a8a;
        }

        .badge-tienda-origen i {
            font-size: 0.75rem;
            flex-shrink: 0;
        }

        /* ===== CARD ACTIONS - Add to Cart row ===== */
        .card-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem 0.75rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            border: 1.5px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            height: 36px;
        }

        .btn-qty {
            width: 30px;
            height: 100%;
            background: #ffffff;
            border: none;
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
            padding: 0;
            line-height: 1;
        }

        .btn-qty:hover {
            background: #f1f3f5;
        }

        .qty-input {
            width: 28px;
            height: 100%;
            border: none;
            border-left: 1.5px solid #dee2e6;
            border-right: 1.5px solid #dee2e6;
            text-align: center;
            font-size: 0.88rem;
            font-weight: 600;
            color: #212529;
            background: #ffffff;
            padding: 0;
            -moz-appearance: textfield;
        }

        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* The green Agregar button */
        .btn-add {
            flex: 1;
            height: 36px;
            background: #1a7f4b;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            white-space: nowrap;
        }

        .btn-add:hover {
            background: #155f38;
        }

        .btn-add:active {
            transform: scale(0.97);
        }

        .btn-add:disabled,
        .btn-add.disabled {
            background: #adb5bd;
            cursor: not-allowed;
            transform: none;
        }

        .btn-add i {
            font-size: 0.95rem;
        }

        /* Tiendas ribbon (marketplace homepage) */
        .tiendas-section {
            padding: 28px 0 0;
        }

        .tiendas-section-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .tiendas-section-header h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1a1a1a;
            margin: 0 0 2px;
        }

        .tiendas-section-header p {
            font-size: 0.82rem;
            color: #666;
            margin: 0;
        }


        .tiendas-ribbon {
            position: relative;
        }

        .tiendas-ribbon-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #333;
            font-size: 1rem;
            transition: box-shadow 0.2s, background 0.2s;
        }

        .tiendas-ribbon-nav:hover {
            background: #f0faf4;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16);
        }

        .tiendas-ribbon-nav.left {
            left: -18px;
        }

        .tiendas-ribbon-nav.right {
            right: -18px;
        }

        .tiendas-ribbon-nav.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .tiendas-ribbon-scroll {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            padding: 6px 4px 12px;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .tiendas-ribbon-scroll::-webkit-scrollbar {
            display: none;
        }

        .tienda-chip {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            text-align: left;
            background: #fff;
            border: 1.5px solid #e9ecef;
            border-radius: 14px;
            padding: 0;
            overflow: hidden;
            text-decoration: none;
            flex-shrink: 0;
            scroll-snap-align: start;
            transition: border-color 0.2s, box-shadow 0.2s, transform 0.18s;
            width: 250px;
            cursor: pointer;
            position: relative;
        }

        .tienda-chip:hover {
            border-color: #1a7f4b;
            box-shadow: 0 4px 16px rgba(26, 127, 75, 0.12);
            transform: translateY(-2px);
            text-decoration: none;
        }

        .tienda-chip.active-tienda {
            border-color: #1a7f4b;
            box-shadow: 0 0 0 2px rgba(26, 127, 75, 0.15);
        }

        .tienda-chip-cover {
            height: 96px;
            flex-shrink: 0;
            background: linear-gradient(145deg, #f8faf9 0%, #eef6f1 50%, #e8f5ec 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .tienda-chip-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .tienda-chip-cover i {
            font-size: 28px;
            color: #b3c9bc;
        }

        .tienda-chip-body {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 0 14px 14px;
            margin-top: -24px;
            position: relative;
        }

        .tienda-chip-logo {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            overflow: hidden;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.14);
            flex-shrink: 0;
        }

        .tienda-chip-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tienda-chip-logo i {
            font-size: 22px;
            color: #adb5bd;
        }

        .tienda-chip-info {
            flex: 1;
            min-width: 0;
            padding-top: 26px;
        }

        .tienda-chip-name {
            font-size: 13px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.3;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 4px;
        }

        .tienda-chip-name .verified-dot {
            color: #1a7f4b;
            font-size: 12px;
            flex-shrink: 0;
        }

        .tienda-chip-cat {
            display: block;
            font-size: 11px;
            color: #888;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }

        .tienda-chip-count {
            font-size: 11px;
            color: #555;
            font-weight: 500;
        }

        .tienda-chip-arrow {
            font-size: 15px;
            color: #333;
            margin-top: 32px;
            flex-shrink: 0;
        }

        .tienda-chip-more {
            background: #f6fbf8;
            border-color: #d9ebe2;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px 18px;
            gap: 4px;
        }

        .tienda-chip-more:hover {
            background: #eef8f2;
            border-color: #1a7f4b;
        }

        .tienda-chip-more-icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            border: 1.5px solid #bcdccb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #1a7f4b;
            margin-bottom: 4px;
        }

        .tienda-chip-more-title {
            font-size: 15px;
            font-weight: 800;
            color: #1a7f4b;
        }

        .tienda-chip-more-sub {
            font-size: 11px;
            color: #6b8f7d;
        }

        .tienda-chip-more-arrow {
            font-size: 16px;
            color: #1a7f4b;
            margin-top: 4px;
        }

        /* Tiendas favoritas del cliente */
        .favoritas-section {
            padding-top: 28px;
            padding-bottom: 4px;
        }

        .favoritas-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .favoritas-head h2 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.25rem;
            font-weight: 800;
            color: #1a1a1a;
            margin: 0 0 2px;
        }

        .favoritas-head h2 i {
            color: #ef5a6f;
            font-size: 1rem;
        }

        .favoritas-head p {
            font-size: 0.82rem;
            color: #666;
            margin: 0;
        }

        .favoritas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 14px;
        }

        .favoritas-grid .tienda-chip {
            width: auto;
        }

        @media (max-width: 576px) {
            .favoritas-grid {
                grid-template-columns: 1fr;
            }
        }

        /* No-image product placeholder */
        .product-img-wrapper.no-img {
            background: linear-gradient(145deg, #f8faf9 0%, #eef6f1 50%, #e8f5ec 100%);
        }

        .product-img-wrapper.no-img::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 70% 30%, rgba(45, 212, 160, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .product-img-wrapper.no-img .no-img-inner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .no-img-inner i {
            font-size: 32px;
            color: #2dd4a0;
            opacity: 0.4;
            transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.3s;
        }

        .product-card:hover .no-img-inner i {
            transform: scale(1.08);
            opacity: 0.6;
        }

        /* Modal no-image */
        .modal-no-img {
            width: 100%;
            height: 100%;
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, #f8faf9 0%, #eef6f1 50%, #e8f5ec 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px 0 0 16px;
        }

        .modal-no-img i {
            font-size: 72px;
            color: #2dd4a0;
            opacity: 0.3;
        }
    </style>

    <!-- Scripts: fuse/dexie se cargan al final, junto al script que los usa -->

</head>


<div id="toast-cart" class="toast-cart">
    <div class="toast-icon">
        <i class="bi bi-check-lg"></i>
    </div>
    <div class="toast-text">Agregado al carrito</div>
</div>

<body class="bg-light" data-user-logged-in="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">
    <!-- Navbar -->
    <?php include 'iseller_store/includes/navbar.php'; ?>

    <!-- Hero Banner (Dynamic) -->
    <?php if ($es_tienda_individual): ?>
        <!-- BANNER DE TIENDA INDIVIDUAL -->
        <!-- BANNER DE TIENDA INDIVIDUAL -->
        <style>
            /* ===== STORE PROFILE HEADER ===== */
            .store-profile-header {
                position: relative;
                width: 100%;
                padding-top: 0;
            }

            .store-cover {
                position: relative;
                width: 100%;
                height: 360px;
                background-size: cover;
                background-position: top;
                display: flex;
                align-items: center;
            }

            /* Left gradient overlay for text legibility */
            .store-cover::before {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(to right,
                        rgba(0, 0, 0, 0.72) 0%,
                        rgba(0, 0, 0, 0.55) 45%,
                        rgba(0, 0, 0, 0.10) 75%,
                        rgba(0, 0, 0, 0.00) 100%);
                z-index: 1;
            }

            .store-cover-content {
                position: relative;
                z-index: 2;
                display: flex;
                align-items: center;
                gap: 1.25rem;
                padding: 0 2rem;
                max-width: 680px;
            }

            /* Circular logo */
            .store-logo-img {
                width: 100px;
                height: 100px;
                object-fit: cover;
                border-radius: 50%;
                border: 3px solid rgba(255, 255, 255, 0.85);
                flex-shrink: 0;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
            }

            .store-logo-placeholder {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.15);
                border: 3px solid rgba(255, 255, 255, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 2.5rem;
                color: rgba(255, 255, 255, 0.7);
            }

            .store-text-info {
                color: #ffffff;
            }

            .store-title {
                font-size: 1.9rem;
                font-weight: 800;
                color: #ffffff;
                margin-bottom: 0.15rem;
                line-height: 1.1;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .store-verified-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 26px;
                height: 24px;
                background: #1a7f4b;
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
                flex-shrink: 0;
                animation: badge-morph 4s ease-in-out infinite;
            }

            @keyframes badge-morph {

                0%,
                100% {
                    border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
                }

                25% {
                    border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%;
                }

                50% {
                    border-radius: 50% 60% 30% 60% / 30% 50% 70% 50%;
                }

                75% {
                    border-radius: 40% 60% 50% 70% / 60% 40% 60% 30%;
                }
            }

            .store-verified-badge svg {
                width: 14px;
                height: 14px;
                fill: #fff;
            }

            .store-tagline {
                font-size: 0.9rem;
                color: rgba(255, 255, 255, 0.85);
                margin-bottom: 0.9rem;
                line-height: 1.45;
            }

            /* Badges on the image */
            .store-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .store-badge {
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(6px);
                border: 1px solid rgba(255, 255, 255, 0.3);
                padding: 0.35rem 0.9rem;
                border-radius: 50px;
                font-size: 0.82rem;
                font-weight: 500;
                font-family: inherit;
                color: #ffffff;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                transition: background 0.2s;
                white-space: nowrap;
            }

            .store-badge.clickable:hover {
                background: rgba(255, 255, 255, 0.28);
                cursor: pointer;
            }

            /* Segundo topbar del perfil de la tienda */
            :root {
                --navbar-h: 60px;
            }

            @media (max-width: 991px) {
                :root {
                    --navbar-h: 104px;
                }
            }

            .store-subnav {
                position: sticky;
                top: var(--navbar-h, 60px);
                z-index: 1020;
                margin-top: var(--navbar-h, 60px);
                background: rgba(255, 255, 255, 0.94);
                -webkit-backdrop-filter: blur(14px);
                backdrop-filter: blur(14px);
                border-bottom: 1px solid rgba(17, 24, 39, 0.08);
                box-shadow: 0 6px 20px rgba(17, 24, 39, 0.06);
            }

            .store-subnav-inner {
                max-width: 1280px;
                margin: 0 auto;
                padding: 10px 32px;
                display: flex;
                align-items: center;
                gap: 18px;
            }

            .store-subnav-brand {
                display: flex;
                align-items: center;
                gap: 0;
                min-width: 0;
                min-height: 38px;
                flex: 1;
            }

            .store-subnav-logo-wrap {
                display: flex;
                align-items: center;
                width: 0;
                opacity: 0;
                overflow: hidden;
                flex-shrink: 0;
                transition: width 0.25s ease, opacity 0.2s ease, margin-right 0.25s ease;
            }

            .store-subnav.show-logo .store-subnav-logo-wrap {
                width: 38px;
                opacity: 1;
                margin-right: 10px;
            }

            .store-subnav-logo,
            .store-subnav-logo-placeholder {
                width: 38px;
                height: 38px;
                border-radius: 50%;
                flex-shrink: 0;
                border: 2px solid #fff;
                box-shadow: 0 0 0 1px rgba(17, 24, 39, 0.08), 0 2px 6px rgba(17, 24, 39, 0.08);
            }

            .store-subnav-logo {
                object-fit: cover;
                background: #f3f4f6;
            }

            .store-subnav-logo-placeholder {
                background: #ecfdf5;
                color: #1a7f4b;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1rem;
            }

            .store-subnav-text {
                min-width: 0;
            }

            .store-subnav-name {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 0.92rem;
                font-weight: 700;
                color: #111827;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .store-subnav-name i {
                color: #1a7f4b;
                font-size: 0.78rem;
                flex-shrink: 0;
            }

            .store-subnav-meta {
                display: flex;
                align-items: center;
                gap: 7px;
                margin-top: 2px;
                font-size: 0.72rem;
                color: #6b7280;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .store-subnav-meta .dot {
                width: 3px;
                height: 3px;
                border-radius: 50%;
                background: #d1d5db;
                flex-shrink: 0;
            }

            .store-subnav-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-shrink: 0;
            }

            .store-subnav-fav {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                height: 40px;
                padding: 0 18px;
                border: 1px solid transparent;
                border-radius: 10px;
                background: #1a7f4b;
                color: #fff;
                font-size: 0.82rem;
                font-weight: 700;
                font-family: inherit;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(26, 127, 75, 0.22);
                transition: transform 0.15s ease, background 0.2s ease, box-shadow 0.2s ease, color 0.2s ease;
            }

            .store-subnav-fav:hover {
                background: #166a3f;
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(26, 127, 75, 0.28);
            }

            .store-subnav-fav:active {
                transform: translateY(0);
            }

            .store-subnav-fav.is-fav {
                background: #fff;
                color: #e11d48;
                border-color: #fecdd3;
                box-shadow: 0 2px 8px rgba(225, 29, 72, 0.08);
            }

            .store-subnav-fav.is-fav:hover {
                background: #fff1f2;
            }

            .store-subnav-fav.is-fav i {
                color: #e11d48;
            }

            .store-subnav-fav:disabled {
                opacity: 0.7;
                cursor: wait;
            }

            .store-subnav-fav i {
                font-size: 1rem;
            }

            .store-subnav-btn {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                height: 40px;
                padding: 0 14px;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                background: #fff;
                color: #374151;
                font-size: 0.8rem;
                font-weight: 600;
                font-family: inherit;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .store-subnav-btn:hover {
                border-color: #1a7f4b;
                color: #1a7f4b;
                background: #f0fdf4;
            }

            .store-subnav-divider {
                width: 1px;
                height: 24px;
                background: #e5e7eb;
                margin: 0 4px;
                flex-shrink: 0;
            }

            .store-subnav-delivery {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .store-subnav-meta-item {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.76rem;
                font-weight: 500;
                color: #4b5563;
                white-space: nowrap;
            }

            .store-subnav-meta-item i {
                color: #1a7f4b;
                font-size: 0.9rem;
            }

            @media (max-width: 991px) {
                .store-subnav-inner {
                    padding: 10px 16px;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .store-subnav-brand {
                    flex: 1 1 100%;
                }

                .store-subnav-actions {
                    width: 100%;
                    flex-wrap: wrap;
                }

                .store-subnav-fav {
                    flex: 1;
                }

                .store-subnav-meta-item {
                    font-size: 0.72rem;
                }

                .store-subnav-delivery {
                    flex: 1 1 100%;
                    gap: 12px;
                }
            }

            @media (max-width: 640px) {
                .store-subnav-meta {
                    display: none;
                }

                .store-subnav-delivery {
                    display: none;
                }

                .store-subnav-actions {
                    gap: 6px;
                }

                .store-subnav-fav {
                    font-size: 0.78rem;
                    padding: 0 12px;
                    white-space: nowrap;
                }

                .store-subnav-btn {
                    padding: 0 12px;
                }

                .store-subnav-btn span {
                    display: none;
                }

                .store-subnav-divider {
                    display: none;
                }
            }

            .store-rep-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                background: rgba(255, 255, 255, 0.95);
                color: #1f2933;
                border-radius: 50px;
                padding: 0.35rem 0.9rem;
                font-size: 0.82rem;
                font-weight: 600;
                white-space: nowrap;
            }

            .store-rep-badge .rep-dot {
                width: 9px;
                height: 9px;
                border-radius: 50%;
                background: var(--rep-color, #9e9e9e);
                flex-shrink: 0;
            }

            /* El nivel se muestra en el punto y las barras: el color puro (amarillo, verde
               claro) no contrasta como texto sobre el badge blanco. */
            .store-rep-badge .rep-pct {
                font-weight: 800;
            }

            .store-rep-badge .rep-count {
                color: #6b7280;
                font-weight: 500;
            }

            .store-rep-bars {
                display: inline-flex;
                gap: 2px;
                margin-left: 2px;
            }

            .store-rep-bars span {
                width: 8px;
                height: 4px;
                border-radius: 2px;
                background: rgba(31, 41, 51, 0.15);
            }

            .store-rep-bars span.on {
                background: var(--rep-color, #9e9e9e);
            }

            .rep-reviews-wrap {
                margin-top: 1.1rem;
            }

            .rep-reviews {
                background: #fff;
                border: 1px solid #e9ecef;
                border-radius: 20px;
                padding: 22px 24px;
            }

            .rep-reviews-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
                flex-wrap: wrap;
            }

            .rep-reviews-head h2 {
                font-size: 1.15rem;
                font-weight: 800;
                margin: 0;
                color: #1f2933;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .rep-reviews-head .rep-summary {
                font-size: 0.82rem;
                color: #6b7280;
                font-weight: 600;
            }

            .rep-reviews-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 14px;
            }

            .rep-review {
                border: 1px solid #eef0f2;
                border-radius: 14px;
                padding: 14px 16px;
                background: #fbfcfc;
            }

            .rep-review-head {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 8px;
                font-weight: 700;
                font-size: 0.85rem;
                color: #1f2933;
            }

            .rep-review-head i.up {
                color: #00a650;
            }

            .rep-review-head i.down {
                color: #f23d4f;
            }

            .rep-review p {
                margin: 0 0 8px;
                font-size: 0.88rem;
                color: #374151;
                line-height: 1.45;
                overflow-wrap: anywhere;
            }

            .rep-review-meta {
                font-size: 0.75rem;
                color: #9ca3af;
            }

            /* Decorative slogan on the right */
            .store-slogan-right {
                position: absolute;
                right: 2.5rem;
                bottom: 1.5rem;
                z-index: 2;
                font-family: 'Georgia', 'Times New Roman', serif;
                font-style: italic;
                font-size: 1.55rem;
                font-weight: 700;
                color: rgba(255, 255, 255, 0.88);
                text-align: right;
                line-height: 1.2;
                pointer-events: none;
                filter: drop-shadow(2px 5px 9px black);
            }

            /* ===== CATEGORY NAVIGATION ===== */
            .category-nav-wrapper {
                position: sticky;
                top: 60px;
                z-index: 99;
                background: rgba(248, 249, 250, 0.97);
                backdrop-filter: blur(10px);
                border-bottom: 1px solid #e9ecef;
                padding: 0.6rem 1rem;
                margin-bottom: 2rem;
            }

            .category-nav-inner {
                display: flex;
                align-items: center;
                gap: 1rem;
                max-width: 1200px;
                margin: 0 auto;
            }

            .category-pills-zone {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: nowrap;
                overflow: hidden;
                flex: 0 0 70%;
                max-width: 70%;
                min-width: 0;
            }

            .category-pills-list {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: nowrap;
                overflow: hidden;
                min-width: 0;
                flex: 1;
            }

            .category-sort-zone {
                flex: 0 0 30%;
                max-width: 30%;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                justify-content: flex-end;
            }

            .category-pill {
                white-space: nowrap;
                background: #ffffff;
                color: #495057;
                border: 1.5px solid #e9ecef;
                padding: 0.45rem 1.1rem;
                border-radius: 99px;
                font-size: 0.9rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
                flex-shrink: 0;
                display: flex;
                align-items: center;
                gap: 0.4rem;
            }

            .category-pill:hover {
                border-color: #1a7f4b;
                color: #1a7f4b;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(26, 127, 75, 0.12);
            }

            .category-pill.active {
                background: #1a7f4b;
                color: #ffffff;
                border-color: #1a7f4b;
                box-shadow: 0 6px 16px rgba(26, 127, 75, 0.25);
            }

            #sort-products-store {
                border: 1.5px solid #e9ecef !important;
                font-weight: 600 !important;
                color: #495057;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03) !important;
                transition: border-color 0.3s;
            }

            #sort-products-store:hover {
                border-color: #1a7f4b !important;
            }


            .category-more-btn {
                white-space: nowrap;
                background: #ffffff;
                color: #495057;
                border: 1px solid #dee2e6;
                padding: 0.4rem 0.85rem;
                border-radius: 50px;
                font-size: 0.88rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                flex-shrink: 0;
                display: none;
            }

            .category-more-btn:hover {
                background: #f8f9fa;
                border-color: #ced4da;
            }

            .category-more-dropdown {
                position: absolute;
                top: calc(100% + 6px);
                right: 0;
                background: #fff;
                border: 1px solid #dee2e6;
                border-radius: 10px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
                z-index: 200;
                min-width: 200px;
                padding: 0.4rem 0;
                display: none;
            }

            .category-more-dropdown .cat-drop-item {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
                color: #495057;
                cursor: pointer;
                transition: background 0.15s;
            }

            .category-more-dropdown .cat-drop-item:hover {
                background: #f8f9fa;
            }

            .category-more-dropdown .cat-drop-item.active {
                color: #1a7f4b;
                font-weight: 600;
            }

            .category-more-wrapper {
                position: relative;
                flex-shrink: 0;
            }

            @media (max-width: 576px) {
                .store-cover {
                    height: 220px;
                }

                .store-cover-content {
                    padding: 0 1rem;
                    gap: 0.85rem;
                }

                .store-logo-img,
                .store-logo-placeholder {
                    width: 76px;
                    height: 76px;
                }

                .store-title {
                    font-size: 1.15rem;
                }

                .store-slogan-right {

                    display: none;
                }

                .store-tagline {
                    font-size: 0.8rem;
                    margin-bottom: 0.6rem;
                }

                .category-pills-zone {
                    flex: 0 0 60%;
                    max-width: 60%;
                }

                .category-sort-zone {
                    flex: 0 0 40%;
                    max-width: 40%;
                }
            }

            .logo-apoyando {
                width: 180px;
                height: auto;
            }

            .logo-apoyando svg {
                display: block;
                width: 100%;
                height: auto;
            }

            .logo-texto {
                fill: #ffffff;
            }

            .logo-linea {
                fill: #00a83b;
            }
        </style>
        <?php
        $portada = !empty($negocio_data['portada_url']) ? $base_url . htmlspecialchars($negocio_data['portada_url']) : 'assets/img/bg-hero-0.png';
        $deliveryInfo = "Tienda Virtual";
        $tipo_delivery = "No especificado";
        if (!empty($negocio_data['config_delivery'])) {
            $dc = json_decode($negocio_data['config_delivery'], true);
            $tipo_delivery = ($dc['tipo'] ?? '') === 'gratis' ? 'Delivery Gratis' : 'Delivery Pagado ($' . number_format($dc['costo'] ?? 0, 2) . ')';
            $zona = ucfirst($dc['zonas'] ?? 'municipio');
            $deliveryInfo = "Zonas: $zona";
        }
        ?>
        <div class="store-subnav">
            <div class="store-subnav-inner">
                <div class="store-subnav-brand">
                    <span class="store-subnav-logo-wrap">
                        <?php if (!empty($negocio_data['logo_url'])): ?>
                            <img class="store-subnav-logo" src="<?= htmlspecialchars($negocio_data['logo_url']) ?>" onerror="imgFallback(this)" alt="">
                        <?php else: ?>
                            <span class="store-subnav-logo-placeholder"><i class="bi bi-shop"></i></span>
                        <?php endif; ?>
                    </span>
                    <div class="store-subnav-text">
                        <span class="store-subnav-name">
                            <?= htmlspecialchars($negocio_data['nombre_visible'] ?: $negocio_data['nombre']) ?>
                            <i class="bi bi-patch-check-fill" title="Tienda verificada"></i>
                        </span>
                        <?php if (!empty($negocio_data['categoria']) || ($repNivel && $repNivel['visible'])): ?>
                            <span class="store-subnav-meta">
                                <?php if (!empty($negocio_data['categoria'])): ?>
                                    <span><?= htmlspecialchars($negocio_data['categoria']) ?></span>
                                <?php endif; ?>
                                <?php if ($repNivel && $repNivel['visible']): ?>
                                    <?php if (!empty($negocio_data['categoria'])): ?><span class="dot"></span><?php endif; ?>
                                    <span><?= round((float)$negocio_data['reputacion_pct']) ?>% recomendado</span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="store-subnav-actions">
                    <button type="button" class="store-subnav-fav <?= $es_favorito ? 'is-fav' : '' ?>" id="btn-favorito" data-negocio="<?= (int)$negocio_data['id'] ?>" aria-pressed="<?= $es_favorito ? 'true' : 'false' ?>" title="<?= $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                        <i class="bi <?= $es_favorito ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                        <span><?= $es_favorito ? 'En favoritos' : 'Agregar a favoritos' ?></span>
                    </button>
                    <button type="button" class="store-subnav-btn" data-bs-toggle="modal" data-bs-target="#modalUbicacion" title="Consultar ubicación" aria-label="Consultar ubicación">
                        <i class="bi bi-geo-alt"></i> <span>Ubicación</span>
                    </button>
                    <?php if (!empty($negocio_data['config_delivery'])): ?>
                        <span class="store-subnav-delivery">
                            <span class="store-subnav-divider"></span>
                            <span class="store-subnav-meta-item"><i class="bi bi-truck"></i> <?= $tipo_delivery ?></span>
                            <span class="store-subnav-meta-item"><i class="bi bi-map"></i> <?= htmlspecialchars($deliveryInfo, ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <section class="store-profile-header">
            <div class="store-cover" style="background-image: url('<?= $portada ?>');">
                <!-- Content overlay on the left -->
                <div class="store-cover-content">
                    <?php if (!empty($negocio_data['logo_url'])): ?>
                        <img class="store-logo-img" src="<?= htmlspecialchars($negocio_data['logo_url']) ?>" onerror="imgFallback(this)" alt="Logo <?= htmlspecialchars($negocio_data['nombre_visible'] ?: $negocio_data['nombre']) ?>">
                    <?php else: ?>
                        <div class="store-logo-placeholder"><i class="bi bi-shop"></i></div>
                    <?php endif; ?>
                    <div class="store-text-info">
                        <h1 class="store-title">
                            <?= htmlspecialchars($negocio_data['nombre_visible'] ?: $negocio_data['nombre']) ?>
                            <span class="store-verified-badge" title="Tienda verificada">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                                </svg>
                            </span>
                        </h1>
                            <p class="store-tagline"><?= nl2br(htmlspecialchars($tienda_slogan)) ?></p>
                        <?php if ($repNivel && $repNivel['visible']): ?>
                            <div class="store-badges">
                                <div class="store-rep-badge" style="--rep-color: <?= $repNivel['color'] ?>" title="Reputación basada en <?= (int)$negocio_data['reputacion_total'] ?> calificaciones de compradores">
                                    <span class="rep-dot"></span>
                                    <span class="rep-pct"><?= round((float)$negocio_data['reputacion_pct']) ?>%</span> recomendado
                                    <span class="rep-count">· <?= (int)$negocio_data['reputacion_total'] ?> calificaciones</span>
                                    <span class="store-rep-bars">
                                        <?php for ($i = 0; $i < 5; $i++): ?>
                                            <span class="<?= $i < $repNivel['bars'] ? 'on' : '' ?>"></span>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Decorative slogan right -->
                <div class="store-slogan-right">
                    <div class="logo-apoyando">
                        <svg viewBox="0 0 1386 795" aria-label="Apoyando lo nuestro">
                            <use href="apoyando_lo_nuestro_2colores.svg#texto"
                                class="logo-texto"></use>

                            <use href="apoyando_lo_nuestro_2colores.svg#linea"
                                class="logo-linea"></use>
                        </svg>
                    </div>


                </div>
            </div>
        </section>

        <?php include 'iseller_store/includes/tiendas_favoritas.php'; ?>

        <?php if ($es_tienda_individual && count($repReviews) > 0): ?>
            <section class="container-fluid px-3 px-md-5 rep-reviews-wrap" id="opiniones-tienda">
                <div class="rep-reviews">
                    <div class="rep-reviews-head">
                        <h2><i class="bi bi-chat-quote"></i> Opiniones de compradores</h2>
                        <?php if ($repNivel && $repNivel['visible']): ?>
                            <span class="rep-summary"><?= round((float)$negocio_data['reputacion_pct']) ?>% recomendado · <?= (int)$negocio_data['reputacion_total'] ?> calificaciones</span>
                        <?php endif; ?>
                    </div>
                    <div class="rep-reviews-grid">
                        <?php foreach ($repReviews as $rv): ?>
                            <article class="rep-review">
                                <div class="rep-review-head">
                                    <i class="bi <?= (int)$rv['recomienda'] === 1 ? 'bi-hand-thumbs-up-fill up' : 'bi-hand-thumbs-down-fill down' ?>"></i>
                                    <span><?= htmlspecialchars(reputacion_nombre_publico($rv['nombre'])) ?></span>
                                </div>
                                <p><?= nl2br(htmlspecialchars($rv['comentario'])) ?></p>
                                <div class="rep-review-meta"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Contenedor del Menú de Categorías + Ordenar -->
        <div class="category-nav-wrapper d-none" id="store-category-wrapper">
            <div class="category-nav-inner">
                <!-- 70%: Pills + More dropdown -->
                <div class="category-pills-zone">
                    <div class="category-pills-list" id="category-pill-container"></div>
                    <div class="category-more-wrapper" id="category-more-wrapper">
                        <button class="category-more-btn" id="category-more-btn"><i class="bi bi-chevron-down me-1"></i>Más</button>
                        <div class="category-more-dropdown" id="category-more-dropdown"></div>
                    </div>
                </div>
                <!-- 30%: Ordenar por -->
                <div class="category-sort-zone">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small fw-medium text-nowrap"><i class="bi bi-filter me-1"></i> Ordenar por:</span>
                        <select class="form-select form-select-sm border-0 shadow-sm" id="sort-products" style="border-radius: var(--radius-md); min-width: 160px; height: 32px; cursor: pointer; background-color: white;">
                            <option value="" selected>Recomendados</option>
                            <option value="name_asc">Nombre (A-Z)</option>
                            <option value="name_desc">Nombre (Z-A)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- BANNER DEL MARKETPLACE (Genérico) - Nuevo diseño -->
        <style>
            .mkt-hero {
                background-image: url('assets/img/bg-hero-a.webp');
                border-bottom: 1px solid #e8f5e9;
                padding-top: 68px;
                background-position: center;
                background-size: auto;
                overflow: hidden;
            }

            .mkt-hero-inner {
                display: flex;
                align-items: center;
                max-width: 1280px;
                margin: 0 auto;
                padding: 28px 32px 0;
                gap: 24px;
            }

            .mkt-hero-text {
                flex: 1 1 0;
                min-width: 0;
                padding-bottom: 28px;
                background-size: cover;
                background-position: center;
                border-radius: 16px;
            }

            .mkt-hero-eyebrow {
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #1a7f4b;
                margin-bottom: 10px;
            }

            .mkt-hero-title {
                font-size: clamp(1.6rem, 3.5vw, 2.6rem);
                font-weight: 800;
                color: #1a1a1a;
                line-height: 1.2;
                margin-bottom: 10px;
            }

            .mkt-hero-title .accent {
                color: #1a7f4b;
            }

            .mkt-hero-sub {
                font-size: 0.92rem;
                color: #555;
                line-height: 1.6;
                margin-bottom: 22px;
                max-width: 420px;
            }

            .mkt-hero-search {
                display: flex;
                align-items: center;
                background: #fff;
                border: 1.5px solid #d4edda;
                border-radius: 50px;
                padding: 6px 8px 6px 18px;
                box-shadow: 0 2px 16px rgba(26, 127, 75, 0.1);
                max-width: 480px;
                position: relative;
            }

            .mkt-hero-search input {
                flex: 1;
                border: none;
                outline: none;
                font-size: 0.9rem;
                color: #333;
                background: transparent;
            }

            .mkt-hero-search input::placeholder {
                color: #aaa;
            }

            .mkt-hero-results {
                position: fixed;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
                max-height: 420px;
                overflow-y: auto;
                z-index: 9999;
            }

            .mkt-hero-results-section {
                padding: 10px 14px 4px;
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #6b7280;
            }

            .mkt-hero-results-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 14px;
                text-decoration: none;
                color: #1a1a1a;
                transition: background 0.15s;
                cursor: pointer;
            }

            .mkt-hero-results-item:hover {
                background: #f0fdf4;
            }

            .mkt-hero-results-item .item-img {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                object-fit: cover;
                background: #f1f5f9;
                flex-shrink: 0;
            }

            .mkt-hero-results-item .item-info {
                min-width: 0;
                flex: 1;
            }

            .mkt-hero-results-item .item-name {
                font-size: 0.85rem;
                font-weight: 600;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .mkt-hero-results-item .item-meta {
                font-size: 0.75rem;
                color: #6b7280;
            }

            .mkt-hero-results-item .item-price {
                font-size: 0.85rem;
                font-weight: 700;
                color: #1a7f4b;
                flex-shrink: 0;
            }

            .mkt-hero-img-col {
                flex-shrink: 0;
                position: relative;
                width: clamp(200px, 30vw, 445px);
                align-self: flex-end;
            }

            .mkt-hero-img-col img {
                display: block;
                width: 100%;
                height: auto;
                border-radius: 12px 12px 0 0;
            }

            .mkt-hero-callout {
                position: absolute;
                top: 18%;
                left: -12%;
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                padding: 10px 14px;
                font-size: 0.82rem;
                color: #333;
                line-height: 1.35;
                font-weight: 600;
                max-width: 130px;
                text-align: center;
                pointer-events: none;
            }

            .mkt-hero-callout::after {
                content: "↘";
                font-size: 1.2rem;
                color: #1a7f4b;
                display: block;
                margin-top: 2px;
            }

            @media (max-width: 768px) {
                .mkt-hero-inner {
                    flex-direction: column;
                    padding: 20px 16px 0;
                    gap: 12px;
                }

                .mkt-hero-img-col {
                    width: 80%;
                    align-self: center;
                }

                .mkt-hero-callout {
                    display: none;
                }

                .mkt-hero-text {
                    padding-bottom: 0;
                }
            }

            </style>
        <div class="mkt-hero">
            <div class="mkt-hero-inner">
                <div class="mkt-hero-text">
                    <p class="mkt-hero-eyebrow mt-3">Tu mercado en un solo lugar</p>
                    <h1 class="mkt-hero-title">Descubre productos<br>de tiendas de <span class="accent">tu localidad</span></h1>
                    <p class="mkt-hero-sub">Encuentra lo que necesitas desde la comodidad de tu hogar y con los mejores precios.</p>
                    <div class="mkt-hero-search">
                        <i class="bi bi-search me-2" style="color:#aaa; font-size:0.95rem;"></i>
                        <input type="text" id="hero-search-input" placeholder="Buscar productos o tiendas..." oninput="heroSearch(this.value)">
                    </div>
                    <button type="button" class="btn rounded-pill px-4 py-2 fw-semibold mt-3" data-bs-toggle="modal" data-bs-target="#modalTiendasCercanas" style="background:#1a7f4b;color:#fff;border:none;box-shadow:0 2px 12px rgba(26,127,75,0.25);">
                        <i class="bi bi-shop-window me-2"></i> Encontrar tiendas cercanas
                    </button>
                </div>
                <div class="mkt-hero-img-col">
                    <img src="assets/img/bat-1.webp" alt="iSeller Store - Mercado local" loading="eager">
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$es_tienda_individual): ?>
        <?php include 'iseller_store/includes/tiendas_favoritas.php'; ?>
    <?php endif; ?>

    <!-- Registration CTA -->
    <?php if (false && !isLoggedIn()): ?>
        <section class="py-2 border-bottom position-relative" style="background: linear-gradient(180deg, #f8fff9 0%, #ffffff 100%); z-index: 10;">
            <div class="container text-center">
                <h3 class="fw-bold mb-3" style="color: #1a7f4b;">¡Empieza con el pie derecho!</h2>
                    <p class="lead mb-4 text-muted mx-auto" style="max-width: 700px;">
                        Regístrate ahora y comienza tu camino hacia el ahorro inteligente.
                        <br>
                        <span>¡Solo con registrarte ya recibirás un <strong style="color: #1a7f4b; font-size: 1.1em; font-weight: 800;">BONO DE $5</strong> para tus compras!</span>
                    </p>
                    <a href="registro.php" class="btn btn-lg rounded-pill px-5 mb-3 shadow text-white" style="background-color: #1a7f4b; border: none;">
                        Crear mi cuenta gratis <i class="bi bi-arrow-right ms-2"></i>
                    </a>
            </div>
        </section>
    <?php endif; ?>

    <!-- Product Grid Section -->
    <div class="container-fluid products-container px-3 px-md-5 pt-2" id="products-container">
        <?php if (!$es_tienda_individual && count($tiendas_destacadas) > 0): ?>
            <div class="tiendas-section mb-4">
                <div class="tiendas-section-header">
                    <div>
                        <h2>Explora tiendas</h2>
                        <p>Conoce nuestras tiendas aliadas y encuentra todo lo que necesitas.</p>
                    </div>
                </div>
                <div class="tiendas-ribbon">
                    <button class="tiendas-ribbon-nav left hidden" id="tiendas-nav-left" onclick="scrollTiendas(-1)" aria-label="Anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <div class="tiendas-ribbon-scroll" id="tiendas-scroll">
                        <?php foreach ($tiendas_destacadas as $t):
                            // La portada se muestra con su versión ligera (480px) si existe
                            $portada_t = $t['portada_url'] ?? '';
                            if ($portada_t && is_file(__DIR__ . '/iseller_store/' . substr($portada_t, 0, -5) . '_min.webp')) {
                                $portada_t = substr($portada_t, 0, -5) . '_min.webp';
                            }
                            $logo_chip = tienda_logo_thumb((string)($t['logo_url'] ?? ''), 96);
                        ?>
                            <a href="/<?= htmlspecialchars($t['slug']) ?>" class="tienda-chip">
                                <div class="tienda-chip-cover">
                                    <?php if ($portada_t !== ''): ?>
                                        <img src="<?= htmlspecialchars($portada_t) ?>" onerror="imgFallback(this)" alt="" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <i class="bi bi-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="tienda-chip-body">
                                    <div class="tienda-chip-logo">
                                        <?php if ($logo_chip !== ''): ?>
                                            <img src="<?= htmlspecialchars($logo_chip) ?>" onerror="imgFallback(this)" alt="<?= htmlspecialchars($t['nombre_visible'] ?: $t['nombre']) ?>" loading="lazy" decoding="async">
                                        <?php else: ?>
                                            <i class="bi bi-shop"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tienda-chip-info">
                                        <span class="tienda-chip-name">
                                            <?= htmlspecialchars($t['nombre_visible'] ?: $t['nombre']) ?>
                                            <i class="bi bi-patch-check-fill verified-dot"></i>
                                        </span>
                                        <span class="tienda-chip-cat"><?= htmlspecialchars($t['categoria']) ?></span>
                                        <span class="tienda-chip-count"><?= number_format((int)$t['total_productos'], 0, ',', '.') ?> productos</span>
                                    </div>
                                    <i class="bi bi-chevron-right tienda-chip-arrow"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        <a href="#" class="tienda-chip tienda-chip-more" onclick="openTiendasModal(); return false;">
                            <span class="tienda-chip-more-icon"><i class="bi bi-shop"></i></span>
                            <span class="tienda-chip-more-title">Ver más tiendas</span>
                            <span class="tienda-chip-more-sub">Explora todas nuestras tiendas aliadas</span>
                            <i class="bi bi-arrow-right tienda-chip-more-arrow"></i>
                        </a>
                    </div>
                    <button class="tiendas-ribbon-nav right" id="tiendas-nav-right" onclick="scrollTiendas(1)" aria-label="Siguiente">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
            <script>
                (function() {
                    const scroll = document.getElementById('tiendas-scroll');
                    const btnL = document.getElementById('tiendas-nav-left');
                    const btnR = document.getElementById('tiendas-nav-right');
                    if (!scroll) return;

                    function updateNav() {
                        btnL.classList.toggle('hidden', scroll.scrollLeft <= 10);
                        btnR.classList.toggle('hidden', scroll.scrollLeft + scroll.clientWidth >= scroll.scrollWidth - 10);
                    }
                    scroll.addEventListener('scroll', updateNav, {
                        passive: true
                    });
                    updateNav();
                    window.scrollTiendas = function(dir) {
                        scroll.scrollBy({
                            left: dir * 480,
                            behavior: 'smooth'
                        });
                    };
                })();
            </script>
        <?php endif; ?>

        <?php if ($es_tienda_individual): ?>
        <style>
            .destacados-ribbon-wrap {
                margin: 0 0 26px;
            }

            .destacados-ribbon {
                display: flex;
                gap: 14px;
                overflow-x: auto;
                padding: 4px 2px 14px;
                scroll-snap-type: x proximity;
                -webkit-overflow-scrolling: touch;
            }

            .destacados-ribbon::-webkit-scrollbar {
                height: 8px;
            }

            .destacados-ribbon::-webkit-scrollbar-thumb {
                background: #d6dde6;
                border-radius: 8px;
            }

            .destacado-card {
                flex: 0 0 170px;
                scroll-snap-align: start;
                cursor: pointer;
            }

            .destacado-card .product-body {
                padding: 0.85rem;
            }

            .destacado-card .product-title {
                font-size: 0.85rem;
            }

            .destacado-card .price-container {
                margin-left: 0.85rem;
                margin-right: 0.85rem;
            }

            .destacado-card .price-main {
                font-size: 1.1rem;
            }

            @media (max-width: 575.98px) {
                .destacado-card {
                    flex: 0 0 150px;
                }
            }
        </style>

        <section class="destacados-ribbon-wrap" id="destacados-section" style="display:none;">
            <div class="tiendas-section-header">
                <div>
                    <h2>Recomendados</h2>
                    <p>Una selección de <?= htmlspecialchars($negocio_data['nombre_visible'] ?: $negocio_data['nombre']) ?>.</p>
                </div>
            </div>
            <div class="destacados-ribbon" id="destacados-ribbon"></div>
        </section>
        <?php endif; ?>

        <!-- Grid Header: Sorting -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
            </div>
        </div>

        <div class="tiendas-section-header">
            <div>
                <h2>Productos destacados</h2>
                <p>Conoce algunos de los productos disponibles en <span class="text-primary">iseller store</span>.</p>
            </div>
        </div>


        <div id="products-grid">
            <!-- Products will be loaded here by JavaScript -->
            <!-- Skeleton placeholders will be shown here initially -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const grid = document.getElementById('products-grid');
                    if (grid && grid.children.length <= 1) { // Only if empty or has sentinel logic
                        let skeletons = '';
                        for (let i = 0; i < 24; i++) {
                            skeletons += `
                                 <div>
                                     <div class="skeleton-card">
                                         <div class="skeleton-img shimmer"></div>
                                         <div class="skeleton-body">
                                             <div class="skeleton-text shimmer" style="width: 80%;"></div>
                                             <div class="skeleton-price shimmer"></div>
                                             <div class="skeleton-button shimmer"></div>
                                         </div>
                                     </div>
                                 </div>
                             `;
                        }
                        grid.innerHTML = skeletons;
                    }
                });
            </script>
        </div>
    </div>

    <!-- Beneficios: eliminado (puntos/niveles desactivados) -->

    <!-- Modal Ubicación -->
    <style>
        .map-marker-logo {
            border-radius: 50%;
            border: 3px solid #198754;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            object-fit: cover;
        }
    </style>
    <div class="modal fade" id="modalUbicacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-geo-alt-fill me-2"></i> <?= htmlspecialchars(($negocio_data['nombre_visible'] ?? '') ?: ($negocio_data['nombre'] ?? ''), ENT_QUOTES) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-4 bg-light shadow-sm">
                        <div class="row align-items-center">
                            <div class="col-lg-7">
                                <h6 class="fw-bold mb-1">Visítanos directamente</h6>
                                <p class="text-muted small mb-3">Estamos ubicados en una zona accesible y segura para tu comodidad.</p>
                                <?php if (!empty($store_config['direccion'])): ?>
                                    <div class="d-flex align-items-start gap-3 mb-2">
                                        <i class="bi bi-pin-map text-success fs-5"></i>
                                        <div>
                                            <p class="mb-0 fw-semibold">Dirección</p>
                                            <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($store_config['direccion'], ENT_QUOTES, 'UTF-8')) ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($store_config['horario'])): ?>
                                    <div class="d-flex align-items-start gap-3 mb-2">
                                        <i class="bi bi-clock text-success fs-5"></i>
                                        <div>
                                            <p class="mb-0 fw-semibold">Horario de Atención (Tienda Física)</p>
                                            <p class="text-muted small mb-0"><?= $store_config['horario'] ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($store_config['horario_delivery'])): ?>
                                    <div class="d-flex align-items-start gap-3">
                                        <i class="bi bi-truck text-success fs-5"></i>
                                        <div>
                                            <p class="mb-0 fw-semibold">Horario de Atención (Delivery)</p>
                                            <p class="text-muted small mb-0"><?= htmlspecialchars($store_config['horario_delivery'], ENT_QUOTES, 'UTF-8') ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-lg-5 d-none d-lg-block text-center">
                                <i class="bi bi-shop text-success" style="font-size: 5rem; opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                    <div id="map-tienda" style="height: 400px; width: 100%;"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                    <?php if (!empty($negocio_data['lat']) && !empty($negocio_data['lng'])): ?>
                        <a href="https://www.google.com/maps?q=<?= $negocio_data['lat'] ?>,<?= $negocio_data['lng'] ?>" target="_blank" class="btn btn-success px-4">
                            <i class="bi bi-map me-2"></i> Abrir en Google Maps
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Tiendas cercanas -->
        <div class="modal fade" id="modalTiendasCercanas" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white fw-bold"><i class="bi bi-shop-window me-2"></i> Tiendas cercanas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (!$grupos_cercanos): ?>
                            <?php if ($geo_cerca_estado === '' && !isLoggedIn() && empty($_SESSION['bss_id'])): ?>
                                <p class="text-muted small mb-2">Inicia sesión para ver las tiendas cercanas a tu ubicación.</p>
                                <a href="login.php" class="btn btn-sm btn-success rounded-pill px-3">Iniciar sesión</a>
                            <?php else: ?>
                                <p class="text-muted small mb-0">No hay tiendas cercanas registradas todavía.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php foreach ($grupos_cercanos as $tituloGrupo => $listaCercanos): ?>
                            <h6 class="fw-bold text-muted text-uppercase small mb-2 mt-3"><?= htmlspecialchars($tituloGrupo) ?></h6>
                            <div class="list-group list-group-flush mb-2">
                                <?php foreach ($listaCercanos as $t): ?>
                                    <a href="<?= htmlspecialchars($script_dir . '/' . $t['slug']) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-0">
                                        <?php if (!empty($t['logo_url'])): ?>
                                            <img src="<?= htmlspecialchars($t['logo_url']) ?>" alt="" style="width:44px;height:44px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                                        <?php else: ?>
                                            <span style="width:44px;height:44px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-shop text-muted"></i></span>
                                        <?php endif; ?>
                                        <span class="flex-grow-1">
                                            <span class="d-block fw-semibold"><?= htmlspecialchars($t['nombre_visible'] ?: $t['nombre']) ?></span>
                                            <span class="d-block small text-muted"><?= htmlspecialchars($t['categoria'] ?: 'Tienda') ?><?= !empty($t['geo_municipio']) ? ' · ' . htmlspecialchars($t['geo_municipio']) : '' ?></span>
                                        </span>
                                        <i class="bi bi-chevron-right text-muted"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <!-- Modal Cuenta -->
    <div class="modal fade" id="modalCuenta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-fill me-2"></i> Mi Cuenta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom bg-light">
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars(getUserName() ?? ''); ?></h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="perfil.php" class="list-group-item list-group-item-action py-3">
                            <i class="bi bi-person-circle me-3 text-primary"></i> Mi Perfil
                        </a>
                        <a href="checkout.php" class="list-group-item list-group-item-action py-3">
                            <i class="bi bi-cart-check me-3 text-primary"></i> Mi Ordenes / Checkout
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action py-3 text-danger">
                            <i class="bi bi-box-arrow-right me-3"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Carrito -->
    <div class="modal fade" id="modalCarrito" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="cart-icon-badge"><i class="bi bi-bag-fill"></i></span>
                        Mi Carrito
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cart-items" style="max-height: 380px; overflow-y: auto;">
                        <div class="cart-empty-state" id="cart-empty-default">
                            <div class="empty-icon">
                                <i class="bi bi-bag-x"></i>
                            </div>
                            <h6>Tu carrito está vacío</h6>
                            <p>Agrega productos para comenzar</p>
                        </div>
                    </div>
                    <div id="cart-footer" style="display: none;">
                        <div class="cart-total-section" style="padding: 0 1.25rem;">
                            <span class="cart-total-label">Total</span>
                            <div class="cart-total-amount">
                                <div class="total-dolar"><span id="cart-total-dolar">$0.00</span></div>
                                <div class="total-bs"><span id="cart-total-bs">Bs 0.00</span></div>
                            </div>
                        </div>
                        <div class="cart-total-divider" style="margin: 0 1.25rem;"></div>
                        <div style="padding: 1rem 1.25rem 1.25rem;">
                            <div class="cart-quick-actions mb-2">
                                <button class="btn btn-save-cart-hero flex-grow-1" onclick="saveCartPrompt()">
                                    <i class="bi bi-bookmark-plus-fill me-1"></i> Guardar
                                </button>
                                <button class="btn btn-load-cart-hero flex-grow-1" onclick="loadSavedCarts()">
                                    <i class="bi bi-clock-history me-1"></i> Cargar
                                </button>
                            </div>
                            <div class="d-grid gap-2 mb-2">
                                <a href="checkout.php" class="btn btn-checkout">
                                    <i class="bi bi-credit-card-2-front-fill me-2"></i>Finalizar Compra
                                </a>
                            </div>
                            <div class="cart-actions-row">
                                <button class="btn btn-clear-cart flex-grow-1" onclick="vaciarCarritoJs()">
                                    <i class="bi bi-trash3 me-1"></i> Vaciar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Guardar Carrito -->
    <div class="modal fade" id="modalGuardarCarrito" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-bookmark-plus-fill me-2"></i> Guardar Carrito</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Guarda este carrito para hacer mercado en segundos la próxima vez.</p>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control rounded-3" id="saved_cart_name" placeholder="Ej: Compras del mes">
                        <label for="saved_cart_name">Nombre del carrito</label>
                    </div>
                    <button type="button" class="btn btn-success w-100 py-3 fw-bold rounded-3 shadow-sm" onclick="confirmSaveCart()">
                        <i class="bi bi-lightning-charge-fill me-2"></i> Guardar Mercado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Listar Carritos -->
    <div class="modal fade" id="modalListarCarts" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(5px);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-folder-check me-2"></i> Mis Mercados Guardados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="saved-carts-list" class="list-group list-group-flush overflow-auto" style="max-height: 450px;">
                        <!-- List via JS -->
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-link text-muted text-decoration-none w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div class="modal fade" id="modalProductoDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered product-modal-dialog">
            <div class="modal-content product-modal-content">
                <button type="button" class="btn-close btn-close-white product-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="row g-0">
                    <!-- Image Side (Protruding) -->
                    <div class="col-md-5">
                        <div class="product-modal-img-container">
                            <img src="" id="modal-product-img" class="product-modal-img" alt="Product">
                            <div id="modal-no-img" class="modal-no-img" style="display:none;">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                    <!-- Details Side -->
                    <div class="col-md-7">
                        <div class="product-modal-body">
                            <div class="mb-4">
                                <h2 class="modal-product-title" id="modal-product-name">Product Name</h2>
                                <div class="modal-product-code">COD: <span id="modal-product-code">12345</span></div>
                                <div id="modal-tienda-wrapper" class="mt-1" style="display:none;">
                                    <a id="modal-tienda-link" href="#" class="badge-tienda-origen">
                                        <i class="bi bi-shop"></i> <span id="modal-tienda-nombre"></span>
                                    </a>
                                </div>
                            </div>

                            <div class="pricing-section mb-4">
                                <div class="modal-product-price-main">
                                    <span class="modal-product-currency">$</span><span id="modal-product-price-d">0.00</span>
                                </div>
                                <div class="modal-product-price-secondary">
                                    <i class="bi bi-wallet2"></i> Bs <span id="modal-product-price-bs">0.00</span>
                                </div>
                            </div>

                            <div class="modal-section-title">DETALLES Y BENEFICIOS</div>
                            <ul id="modal-product-description" class="modal-benefits-list">
                                <!-- Bullet points inserted via JS -->
                            </ul>

                            <div id="modal-product-category" class="mb-3" style="display: none;">
                                <span class="badge bg-light text-muted fw-normal p-1" style="font-size: 0.8rem;">
                                    <i class="bi bi-tag small"></i> <span id="modal-category-text"></span>
                                </span>
                            </div>

                            <div class="modal-actions-container">
                                <div class="modal-qty-wrapper">
                                    <button class="btn-qty" onclick="changeModalQty(-1)"><i class="bi bi-dash"></i></button>
                                    <input type="number" class="qty-input" id="modal-product-qty" value="1" min="1" readonly>
                                    <button class="btn-qty" onclick="changeModalQty(1)"><i class="bi bi-plus"></i></button>
                                </div>

                                <button class="btn modal-btn-add" id="modal-btn-add"
                                    data-add-id="" data-codigo="" data-P_D="" data-P_P="" data-P_B="">
                                    <i class="bi bi-cart-plus-fill"></i> AGREGAR AL CARRITO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($es_tienda_individual): ?>
        <!-- Chat Button -->
        <button class="chat-floating-button" id="chat-fab" title="Iniciar chat" style="background: #1a7f4b; box-shadow: 0 4px 16px rgba(26,127,75,0.4);">
            <i class="bi bi-chat-dots"></i>
        </button>

        <!-- Chat Start Modal -->
        <div class="modal fade" id="modalChat" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-lg, 1rem); overflow: hidden;">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title fw-bold text-white"><i class="bi bi-chat-dots me-2"></i> Iniciar Chat</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <?php if (!isLoggedIn()): ?>
                            <p class="text-muted small mb-3">Inicia sesión para escribirle a la tienda y seguir la conversación en tus chats.</p>
                            <a href="login.php" class="btn btn-success w-100 fw-semibold">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar sesión
                            </a>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Categoría</label>
                                <select class="form-select" id="chat-categoria" required style="border-radius: var(--radius-md, 0.5rem);">
                                    <option value="">Selecciona una categoría</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Asunto</label>
                                <input type="text" class="form-control" id="chat-asunto" placeholder="¿En qué podemos ayudarte?" maxlength="200" required style="border-radius: var(--radius-md, 0.5rem);">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-secondary">Mensaje</label>
                                <textarea class="form-control" id="chat-mensaje" rows="4" placeholder="Describe tu consulta..." maxlength="5000" required style="border-radius: var(--radius-md, 0.5rem);"></textarea>
                            </div>
                            <div id="chat-error" class="alert alert-danger d-none" role="alert" style="border-radius: var(--radius-md, 0.5rem);"></div>
                            <button class="btn btn-success w-100 fw-semibold" id="chat-enviar">
                                <i class="bi bi-send-fill me-2"></i> Enviar mensaje
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- jQuery -->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="../publico/vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Scripts -->
    <script src="assets/js/fastclick.js"></script>
    <script src="assets/js/nprogress.js"></script>
    <script src="assets/js/custom.js"></script>
    <!-- Leaflet JS -->
    <script src="../publico/vendors/leaflet/leaflet.js" crossorigin=""></script>
    <script src="../publico/vendors/leaflet/leaflet-pip.js"></script>
    <!-- Notiflix Library -->
    <script src="assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-aio-3.2.8.min.js"></script>

    <!-- Búsqueda y caché local: se usan en el script de abajo, no antes -->
    <script src="../publico/vendors/fuse/fuse.min.js"></script>
    <script src="../publico/vendors/dexie/dexie.min.js"></script>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const ref = urlParams.get('ref');

        if (ref) {
            document.cookie = `referral_code=${ref}; path=/; max-age=2592000`;
        }



        var productos = []; // Se cargará dinámicamente
        var productos_por_id = {}; // Se llenará a medida que se haga scroll o busqueda
        var codigos = [];

        const base_url = 'core/';
        let userRewards = [];

        // Defaults from user profile for address form
        const DEFAULT_RECEPTOR = "<?php echo $loyaltyData['nombre'] ?? ''; ?>";
        const DEFAULT_TELEFONO = "<?php echo $loyaltyData['telefono'] ?? ''; ?>";

        // Initialize UI Elements
        document.addEventListener('DOMContentLoaded', () => {
            // Init Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            // --- MOBILE SEARCH OVERLAY LOGIC ---
            const searchToggle = document.getElementById('btn-search-toggle');
            const searchOverlay = document.getElementById('mobile-search-overlay');
            const searchClose = document.getElementById('btn-search-close');
            const searchInputMobile = document.getElementById('search-mobile');

            if (searchToggle && searchOverlay) {
                searchToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    searchOverlay.classList.add('active');
                    setTimeout(() => searchInputMobile.focus(), 100);
                });

                if (searchClose) {
                    searchClose.addEventListener('click', () => {
                        searchOverlay.classList.remove('active');
                        $('#search-results-mobile').removeClass('show');
                    });
                }

                // Close on click outside the overlay content
                document.addEventListener('click', (e) => {
                    if (searchOverlay.classList.contains('active') &&
                        !searchOverlay.contains(e.target) &&
                        !searchToggle.contains(e.target)) {
                        searchOverlay.classList.remove('active');
                        $('#search-results-mobile').removeClass('show');
                    }
                });
            }

            // --- INIT STORE MAP ON MODAL SHOW ---
            const modalUbicacion = document.getElementById('modalUbicacion');
            let mapTienda = null;

            modalUbicacion?.addEventListener('shown.bs.modal', function() {
                if (!mapTienda) {
                    <?php if ($es_tienda_individual && !empty($negocio_data['lat']) && !empty($negocio_data['lng'])): ?>
                        const lat = <?= $negocio_data['lat'] ?>;
                        const lng = <?= $negocio_data['lng'] ?>;
                        const popTitle = "<b><?= htmlspecialchars($negocio_data['nombre_visible'] ?: $negocio_data['nombre'], ENT_QUOTES) ?></b><br>Ubicación de la tienda.";
                    <?php else: ?>
                        const lat = 5.642498;
                        const lng = -67.602170;
                        const popTitle = '<b>iSeller Store</b><br>Nuestra Tienda Principal.';
                    <?php endif; ?>

                    mapTienda = L.map('map-tienda').setView([lat, lng], 16);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(mapTienda);

                    <?php if (!empty($negocio_data['logo_url'])): ?>
                        const markerIcon = L.icon({
                            iconUrl: '<?= addslashes($negocio_data['logo_url']) ?>',
                            iconSize: [40, 40],
                            iconAnchor: [20, 40],
                            popupAnchor: [0, -40],
                            className: 'map-marker-logo'
                        });
                        L.marker([lat, lng], {
                                icon: markerIcon
                            }).addTo(mapTienda)
                            .bindPopup(popTitle)
                            .openPopup();
                    <?php else: ?>
                        L.marker([lat, lng]).addTo(mapTienda)
                            .bindPopup(popTitle)
                            .openPopup();
                    <?php endif; ?>
                }

                // Refrescar tamaño para corregir problemas de renderizado en modales
                // Se añade un pequeño delay para asegurar que el modal haya terminado su transición
                setTimeout(() => {
                    if (mapTienda) mapTienda.invalidateSize();
                }, 250);
            });
        });

        /* buscador de productos */
        let productos_indexados = [];
        let searchItems = [];
        let fuse;

        const tiendaSlugParam = urlParams.has('tienda_slug') ? '&tienda_slug=' + urlParams.get('tienda_slug') : '<?= !empty($tienda_slug) ? '&tienda_slug=' . urlencode($tienda_slug) : '' ?>';
        fetch('api/productos.php?mode=search_index' + tiendaSlugParam)
            .then(res => res.json())
            .then(data => {
                productos_indexados = data.searchIndex;
                // Mapear claves para Fuse (revertir minificación)
                searchItems = data.searchIndex.map(item => ({
                    id: item.id,
                    nombre: item.n,
                    codigo: item.c,
                    stock: item.s,
                    mayor: item.m,
                    precio_dolar_visible: item.pd,
                    precio_weight_visible: item.pp,
                    precio_bs_visible: item.pb,
                    precio_costo: item.pc,
                    precio_costo_bs: item.pc_bs,
                    categorias: item.ca || '',
                    tienda_nombre: item.tn || '',
                    sucursal: item.sucursal || '',
                    id_sucursal: item.id_sucursal,
                    bss_id: item.bss_id,
                    img: item.foto_url || ''
                }));

                fuse = new Fuse(searchItems, {
                    keys: ['nombre', 'categorias'],
                    threshold: 0.35,
                    ignoreLocation: true,
                    ignoreDiacritics: true,
                    includeScore: false,
                    useExtendedSearch: false
                });
            })
            .catch(err => console.error("Error cargando índice de búsqueda", err));

        // Cintillo de productos recomendados (solo en el perfil de una tienda)
        const destacadosRibbon = document.getElementById('destacados-ribbon');
        if (destacadosRibbon) {
            fetch('api/productos.php?mode=destacados' + tiendaSlugParam)
                .then(res => res.json())
                .then(data => {
                    const lista = Array.isArray(data.data) ? data.data : [];
                    if (!lista.length) return;

                    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

                    destacadosRibbon.innerHTML = lista.map(p => {
                        productos_por_id[p.id] = p;
                        const nombre = esc(p.nombre);
                        const img = p.img
                            ? `<img src="${esc(p.img)}" loading="lazy" class="product-img" alt="${nombre}" onerror="productImgError(this)">`
                            : `<div class="no-img-inner"><i class="bi bi-box-seam"></i></div>`;

                        return `<div class="destacado-card" onclick="openProductModal('${esc(p.id)}')">
                            <div class="product-card">
                                <div class="product-img-wrapper ${p.img ? '' : 'no-img'}">${img}</div>
                                <div class="product-body"><h3 class="product-title" title="${nombre}">${nombre}</h3></div>
                                <div class="price-container">
                                    <div class="price-main"><span class="price-dolar">$${formatNumber(recortarADosDecimales(p.precio_dolar_visible))}</span></div>
                                    <div class="price-sub"><span class="price-bs">Bs ${formatNumber(recortarADosDecimales(p.precio_bs_visible))}</span></div>
                                </div>
                            </div>
                        </div>`;
                    }).join('');

                    document.getElementById('destacados-section').style.display = '';
                })
                .catch(() => {});
        }




        if (window.innerWidth > 768) {
            window.addEventListener("scroll", () => {
                const scrolled = window.scrollY;
                document.querySelectorAll('.hero-bg').forEach(bg => {
                    bg.style.transform = `translateY(${scrolled * 0.4}px)`;
                });
            });
        }
        /**
         * ==========================================
         *  HERO BANNER LOGIC & SCROLL ANIMATION
         * ==========================================
         */
        const heroSlides = [{
                text: "Actualiza tu ubicación y Verifica si aplica para delivery GRATIS. <?php if (isLoggedIn()): ?><a id='btn-actualizar-ubicacion' class='btn btn-success'>Verificar Ubicación</a><?php endif; ?>",
                img: "assets/img/bg-hero-0.png"
            },
            {
                text: "¡Hazle el mercado a un familiar! Agrega su dirección y nosotros nos encargamos del resto.",
                img: "assets/img/bg-hero-2.png"
            },
        ];

        // Ya no se precargan bg-hero-0/2: eran 4.3 MB y el slider no tiene destino (#hero-bg no existe)

        let heroSlideIndex = 0;
        let isAnimating = false;

        const heroTextElement = document.getElementById('hero-text');
        const heroBgElement = document.getElementById('hero-bg');

        const FADE_TIME = 600;
        const SLIDE_TIME = 5000;

        function rotateHero() {
            if (isAnimating || !heroTextElement || !heroBgElement) return;

            isAnimating = true;

            // Fade out
            heroTextElement.style.opacity = '0';
            heroTextElement.style.transform = 'translateY(20px)';

            setTimeout(() => {
                heroSlideIndex = (heroSlideIndex + 1) % heroSlides.length;
                const slide = heroSlides[heroSlideIndex];

                // Cambio EXACTAMENTE sincronizado
                heroTextElement.innerHTML = slide.text;
                heroBgElement.style.backgroundImage = `url('${slide.img}')`;

                // Fade in
                heroTextElement.style.opacity = '1';
                heroTextElement.style.transform = 'translateY(0)';

                setTimeout(() => {
                    isAnimating = false;
                }, FADE_TIME);

            }, FADE_TIME);
        }

        function initHero() {
            const slide = heroSlides[0];
            if (heroTextElement) heroTextElement.innerHTML = slide.text;
            if (heroBgElement) heroBgElement.style.backgroundImage = `url('${slide.img}')`;
        }




        // Arranque estable con timeout recursivo (NO setInterval)
        function startHeroSlider() {
            setTimeout(function loop() {
                rotateHero();
                setTimeout(loop, SLIDE_TIME);
            }, SLIDE_TIME);
        }

        // Inicializar primer slide inmediatamente
        initHero();

        if (heroSlides.length > 1) {
            startHeroSlider();
        }
        // Scroll Animation
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            const heroBg = document.getElementById('hero-bg');
            const heroContent = document.querySelector('.hero-content');

            // Parallax & Fade Effect
            if (scrollY < 600) {
                // Fade out content
                const opacity = 1 - (scrollY / 400);
                if (heroContent) heroContent.style.opacity = Math.max(opacity, 0);

                // Transform BG to white/fade
                if (heroBg) {
                    // heroBg.style.filter = `blur(${scrollY / 50}px) grayscale(${scrollY / 10}%)`;
                    // Optional: Fade overlay to white
                }
            }
        });

        // Función de búsqueda rápida y limpia
        /*   const buscarConFuse = termino => {
            if(!fuse) return [];
            return fuse.search(`=${termino}`).map(r => r.item);
        };
*/

        // Búsqueda tolerante: tildes, errores de tipeo, orden de palabras, sinónimos y código de barras
        const SINONIMOS_BUSQUEDA = {
            gaseosa: 'refresco',
            celular: 'telefono',
            telefono: 'celular',
            computadora: 'laptop',
            laptop: 'computadora',
            nevera: 'refrigerador',
            refrigerador: 'nevera',
            zapatilla: 'zapato',
            zapato: 'zapatilla',
            lapicero: 'boligrafo',
            boligrafo: 'lapicero',
            cuaderno: 'libreta',
            libreta: 'cuaderno'
        };

        const normalizarBusqueda = texto => (texto || '').toString()
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();

        const buscarTolerante = termino => {
            if (typeof fuse === 'undefined' || !fuse) return [];

            const consulta = normalizarBusqueda(termino);
            if (!consulta) return [];

            const vistos = new Set();
            const salida = [];
            const agregar = item => {
                if (item && !vistos.has(item.id)) {
                    vistos.add(item.id);
                    salida.push(item);
                }
            };

            // Código de barras: coincidencia directa (evita que Fuse asocie números al azar)
            if (/^\d{4,}$/.test(consulta)) {
                const porCodigo = searchItems.filter(it => String(it.codigo || '').includes(consulta));
                if (porCodigo.length) return porCodigo;
            }

            // Coincidencia normal (tolera errores de tipeo y tildes)
            fuse.search('=' + consulta).forEach(r => agregar(r.item));
            if (salida.length) return salida;

            const palabras = consulta.split(' ').filter(p => p.length > 2);

            // Sinónimos, solo si el nombre contiene la palabra equivalente
            palabras.forEach(palabra => {
                const equivalente = SINONIMOS_BUSQUEDA[palabra];
                if (!equivalente) return;
                fuse.search(equivalente).forEach(r => {
                    if (normalizarBusqueda(r.item.nombre).includes(equivalente)) agregar(r.item);
                });
            });
            if (salida.length) return salida;

            // Todas las palabras, en cualquier orden
            if (palabras.length > 1) {
                const coincidencias = palabras.map(p => new Map(fuse.search(p).map(r => [r.item.id, r.item])));
                const comunes = [...coincidencias[0].keys()].filter(id => coincidencias.every(m => m.has(id)));
                if (comunes.length) {
                    comunes.forEach(id => agregar(coincidencias[0].get(id)));
                    return salida;
                }
                // Al menos una palabra
                coincidencias.forEach(m => [...m.values()].slice(0, 5).forEach(agregar));
            }

            return salida;
        };

        const buscarConFuse = termino => buscarTolerante(termino);
        /* buscador de productos */

        // Search functionality for both desktop and mobile
        $(document).on('keyup', '#search, #search-mobile', function() {
            var nombreProducto = $(this).val();
            var isMobile = $(this).attr('id') === 'search-mobile';
            var resultsContainer = isMobile ? '#search-results-mobile' : '#search-results';

            if (nombreProducto.length > 2) {
                let resultados = buscarConFuse(nombreProducto)
                mostrarResultadosBusqueda(resultados, resultsContainer, userRewards[0])
            } else {
                $(resultsContainer).html('').removeClass('show');
            }
        });

        // Close search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#search, #search-results, #search-mobile, #search-results-mobile').length) {
                $("#search-results, #search-results-mobile").removeClass('show');
            }
        });

        function mostrarResultadosBusqueda(resultados, containerSelector = '#search-results', recompensas = null) {
            const searchResults = $(containerSelector);
            searchResults.html('');

            if (!Array.isArray(resultados) || resultados.length === 0) {
                searchResults.html('<div class="p-3 text-muted text-center"><i class="bi bi-search me-2"></i>No se encontraron productos</div>');
                searchResults.addClass('show');
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            resultados.slice(0, 8).forEach(item => {

                productos_por_id[item.id] = item;

                let precio_dolar_visible = item.precio_dolar_visible;
                let precio_bs_visible = item.precio_bs_visible;

                let precio_dolar_html = `$${formatNumber(precio_dolar_visible)}`;
                let precio_bs_html = `${formatNumber(recortarADosDecimales(precio_bs_visible))} Bs`;

                if (recompensas) {
                    const tipo_recompensa = recompensas.tipo;
                    if (tipo_recompensa === 'descuento_ganancia') {
                        let old_pd = precio_dolar_html;
                        let old_pb = precio_bs_html;

                        precio_dolar_visible = item.precio_costo;
                        precio_bs_visible = item.precio_costo_bs;

                        precio_dolar_html = `$${formatNumber(recortarADosDecimales(precio_dolar_visible))} <span class="text-decoration-line-through text-danger text-xs" style="font-size: 0.7rem;">${old_pd}</span> `;
                        precio_bs_html = `${formatNumber(recortarADosDecimales(precio_bs_visible))} Bs <span class="text-decoration-line-through text-danger text-xs" style="font-size: 0.7rem;">${old_pb}</span> `;
                    }
                }

                html += `
                    <div class="list-group-item list-group-item-action border-0 border-bottom py-2" style="cursor: pointer;" onclick="seleccionarProductoBusqueda('${item.id}')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold text-primary-dark">${item.nombre}</span>
                                    <span class="text-muted" style="font-size: 0.72rem;">
                                        <i class="bi bi-tag small"></i> ${item.ca || item.categorias || 'Sin categoría'}
                                    </span>
                                </div>
                                ${item.tienda_nombre ? `
                                <div class="text-success" style="font-size: 0.72rem;">
                                    ${item.sucursal
                                        ? `<a href="/${item.sucursal}" class="text-success text-decoration-none" onclick="event.stopPropagation()" title="Ver tienda: ${item.tienda_nombre}"><i class="bi bi-shop"></i> ${item.tienda_nombre}</a>`
                                        : `<span><i class="bi bi-shop"></i> ${item.tienda_nombre}</span>`}
                                </div>` : ''}
                                <div class="d-flex gap-3 small">
                                    <span class="fw-bold text-success">
                                        ${precio_dolar_html}
                                    </span>
                                    <span class="text-muted border-start ps-3">
                                        ${precio_bs_html}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2" >
                                <div class="quantity-control form-control-sm border px-1">
                                    ${(()=>{
                                        const _isD = DECIMAL_QTY_IDS.has(String(item.id));
                                        const _step = _isD ? '0.5' : '1';
                                        const _min  = _isD ? '0.5' : '1';
                                        const _val  = _isD ? '0.5' : '1';
                                        return `
                                            <button class="btn-qty" onclick="changeQty(this, -1)" style="border:none;background:none;font-weight:bold;cursor:pointer;padding:0 4px;">-</button>
                                            <input type="number" class="qty-input form-control-plaintext p-0 text-center" 
                                                data-cantidad-id="${item.id}"
                                                value="${_val}" min="${_min}"
                                                data-step="${_step}" data-min="${_min}"
                                                step="${_step}" readonly
                                                style="width: 40px; height: 30px;">
                                            <button class="btn-qty" onclick="changeQty(this, 1, null)" style="border:none;background:none;font-weight:bold;cursor:pointer;padding:0 4px;">+</button>
                                        `;
                                    })()}
                                </div>
                                <button class="btn btn-sm btn-action-primary d-flex align-items-center justify-content-center btn-add-to-car" 
                                    style="width: 38px; height: 32px; border-radius: 50%; background: var(--primary-color); color: white; border: none;"
                                    data-add-id="${item.id}"
                                    data-codigo="${item.codigo || ''}"
                                    data-P_D="${precio_dolar_visible}"
                                    data-P_P="${item.precio_weight_visible}"
                                    data-P_B="${precio_bs_visible}"
                                    data-P_C="${item.precio_costo}"
                                    data-P_C_BS="${item.precio_costo_bs}">
                                    <i class="bi bi-plus-lg"></i>
                                    <span class="cart-item-anim"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                /*

  data-add-id="${producto.id}"
                                        data-codigo="${producto.codigo || ''}"
                                        data-P_D="${precio_dolar_visible}"
                                        data-P_B="${precio_bs_visible}"
                                        data-P_C="${producto.precio_costo}"
                                        data-P_C_BS="${producto.precio_costo_bs}">

                */
            });
            html += '</div>';
            searchResults.html(html);
            searchResults.addClass('show');
        }

        function seleccionarProductoBusqueda(id) {
            // Optional: Navigate to product detail or highlight it
        }

        // Infinite Scroll & Sorting Logic
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let currentSort = ''; // Sorting parameter
        let currentCategory = ''; // Category parameter
        const productsGrid = document.getElementById("products-grid");

        // Load Categories into filter & pill menu
        (async function loadCategoriesFilter() {
            try {
                const res = await fetch('api/get_categories_list.php' + (typeof tiendaSlugParam !== 'undefined' && tiendaSlugParam ? tiendaSlugParam.replace('&', '?') : ''));
                const data = await res.json();
                if (data.success) {
                    const pillContainer = document.getElementById('category-pill-container');
                    if (pillContainer) {
                        // Build all pills (Todas + categories)
                        const allCats = [{
                            id: '',
                            nombre: 'Todas'
                        }, ...data.categories];
                        pillContainer.innerHTML = '';

                        const getIcon = (name) => {
                            const n = name.toLowerCase();
                            if (n === 'todas') return '<i class="bi bi-grid-fill"></i>';
                            if (n.includes('harina') || n.includes('alimento') || n.includes('arroz')) return '<i class="bi bi-egg-fried"></i>';
                            if (n.includes('bebida') || n.includes('jugo') || n.includes('agua')) return '<i class="bi bi-cup-straw"></i>';
                            if (n.includes('higiene') || n.includes('limpieza')) return '<i class="bi bi-droplet-half"></i>';
                            if (n.includes('hogar')) return '<i class="bi bi-house-door"></i>';
                            return '<i class="bi bi-tag-fill"></i>';
                        };

                        allCats.forEach(cat => {
                            const pill = document.createElement('div');
                            pill.className = 'category-pill' + (cat.id === '' ? ' active' : '');
                            pill.dataset.categoryId = cat.id;
                            pill.innerHTML = getIcon(cat.nombre) + ' <span>' + cat.nombre + '</span>';
                            pillContainer.appendChild(pill);
                        });

                        document.getElementById('store-category-wrapper').classList.remove('d-none');

                        // After render, check overflow and move excess pills to dropdown
                        requestAnimationFrame(() => {
                            buildMoreDropdown(pillContainer);
                        });

                        // Click handler for pills (delegated)
                        pillContainer.addEventListener('click', function(e) {
                            const pill = e.target.closest('.category-pill');
                            if (!pill) return;
                            setActiveCategory(pill.dataset.categoryId);
                        });

                        // Sort select inside store nav
                        const sortStore = document.getElementById('sort-products-store');
                        if (sortStore) {
                            sortStore.addEventListener('change', function() {
                                currentSort = this.value;
                                currentPage = 1;
                                hasMore = true;
                                showSkeletons();
                                cargarMasProductos();
                            });
                        }
                    }

                    // Dropdown toggle
                    const moreBtn = document.getElementById('category-more-btn');
                    const moreDropdown = document.getElementById('category-more-dropdown');
                    if (moreBtn && moreDropdown) {
                        moreBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const open = moreDropdown.style.display === 'block';
                            moreDropdown.style.display = open ? 'none' : 'block';
                        });
                        document.addEventListener('click', () => {
                            moreDropdown.style.display = 'none';
                        });
                        moreDropdown.addEventListener('click', function(e) {
                            const item = e.target.closest('.cat-drop-item');
                            if (!item) return;
                            setActiveCategory(item.dataset.categoryId);
                            moreDropdown.style.display = 'none';
                        });
                    }
                }
            } catch (e) {
                console.error("Error cargando categorías para filtro", e);
            }
        })();


        // -- Category helper functions --
        function setActiveCategory(catId) {
            currentCategory = catId;
            const pillContainer = document.getElementById('category-pill-container');
            if (pillContainer) {
                pillContainer.querySelectorAll('.category-pill').forEach(p => {
                    p.classList.toggle('active', p.dataset.categoryId == catId);
                });
            }
            const moreDropdown = document.getElementById('category-more-dropdown');
            if (moreDropdown) {
                moreDropdown.querySelectorAll('.cat-drop-item').forEach(p => {
                    p.classList.toggle('active', p.dataset.categoryId == catId);
                });
                const isInDropdown = Array.from(moreDropdown.querySelectorAll('.cat-drop-item'))
                    .some(p => p.dataset.categoryId == catId && catId !== '');
                const moreBtn = document.getElementById('category-more-btn');
                if (moreBtn) moreBtn.classList.toggle('active', isInDropdown);
            }
            currentPage = 1;
            hasMore = true;
            showSkeletons();
            cargarMasProductos();
        }

        function buildMoreDropdown(pillContainer) {
            const moreWrapper = document.getElementById('category-more-wrapper');
            const moreBtn = document.getElementById('category-more-btn');
            const moreDropdown = document.getElementById('category-more-dropdown');
            if (!moreWrapper || !moreBtn || !moreDropdown) return;

            const pills = Array.from(pillContainer.querySelectorAll('.category-pill'));
            const availableWidth = pillContainer.offsetWidth - 90;
            let usedWidth = 0;
            let overflowStart = -1;

            // Reset
            pills.forEach(p => {
                p.style.display = '';
            });

            pills.forEach((pill, i) => {
                usedWidth += pill.offsetWidth + 8;
                if (overflowStart === -1 && usedWidth > availableWidth) {
                    overflowStart = i;
                }
            });

            if (overflowStart !== -1 && overflowStart < pills.length) {
                const dropItems = [];
                pills.forEach((pill, i) => {
                    if (i >= overflowStart) {
                        pill.style.display = 'none';
                        const item = document.createElement('div');
                        item.className = 'cat-drop-item' + (pill.classList.contains('active') ? ' active' : '');
                        item.dataset.categoryId = pill.dataset.categoryId;
                        item.textContent = pill.textContent;
                        dropItems.push(item);
                    }
                });
                moreDropdown.innerHTML = '';
                dropItems.forEach(item => moreDropdown.appendChild(item));
                moreBtn.style.display = '';
            } else {
                moreBtn.style.display = 'none';
                moreDropdown.innerHTML = '';
            }
        }

        // Event listener for sorting dropdown
        const _sortEl = document.getElementById('sort-products');
        if (_sortEl) _sortEl.addEventListener('change', function() {
            currentSort = this.value;
            currentPage = 1;
            hasMore = true;

            // Show skeletons to preserve vertical space and prevent scroll jump
            showSkeletons();

            cargarMasProductos();
        });

        function showSkeletons() {
            if (productsGrid.children.length > 0 && !isLoading && currentPage > 1) return;
            if (currentPage === 1 && productsGrid.querySelector('.product-item')) return;

            let skeletons = '';
            for (let i = 0; i < 24; i++) {
                skeletons += `
                    <div>
                        <div class="skeleton-card">
                            <div class="skeleton-img shimmer"></div>
                            <div class="skeleton-body">
                                <div class="skeleton-text shimmer" style="width: 80%;"></div>
                                <div class="skeleton-price shimmer"></div>
                                <div class="skeleton-button shimmer"></div>
                            </div>
                        </div>
                    </div>
                `;
            }
            productsGrid.innerHTML = skeletons;
        }
        const sentinel = document.createElement("div"); // Elemento invisible para detectar el final
        sentinel.id = "sentinel";
        document.getElementById("products-container").appendChild(sentinel);

        async function cargarMasProductos() {
            if (isLoading || !hasMore) return;
            isLoading = true;

            let loaderShown = false;

            // Programar loader después de 1s
            const loaderTimer = setTimeout(() => {
                Notiflix.Loading.standard('Cargando productos...');
                loaderShown = true;
            }, 1000);
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const tiendaSlugParam = urlParams.has('tienda_slug') ? '&tienda_slug=' + urlParams.get('tienda_slug') : '<?= !empty($tienda_slug) ? '&tienda_slug=' . urlencode($tienda_slug) : '' ?>';
                const res = await fetch(`api/productos.php?mode=grid&page=${currentPage}&limit=18&order=${currentSort}&category=${currentCategory}${tiendaSlugParam}`);
                const data = await res.json();

                const recompensas = data.recompensas[0];

                if (data.data && data.data.length > 0) {
                    renderProductos(data.data, recompensas);

                    data.data.forEach(p => {
                        productos_por_id[p.id] = p;
                    });

                    currentPage++;
                    hasMore = data.hasMore;
                } else {
                    hasMore = false;
                }

            } catch (err) {
                console.error("Error cargando productos", err);

            } finally {
                // Cancelar aparición del loader si aún no salió
                clearTimeout(loaderTimer);

                // Quitar loader solo si llegó a mostrarse
                if (loaderShown) {
                    Notiflix.Loading.remove();
                }

                isLoading = false;
            }
        }


        function renderProductos(listaProductos, recompensas) {
            // Remove spinner temporarily to append before it, or just append to grid

            // Limpiar skeletons o spinner si es la primera carga
            if (currentPage === 1) {
                productsGrid.innerHTML = '';
            }

            listaProductos.forEach(producto => {

                const isSocio = producto.sucursal === 'loreamny';
                const isOutOfStock = !isSocio && producto.stock <= 0 && producto.mayor !== '1';
                const opacityClass = isOutOfStock ? 'opacity-50 grayscale' : '';
                const btnDisabled = isOutOfStock ? 'disabled btn-disabled' : '';
                const btnText = isOutOfStock ? 'Agotado' : '';

                // Badge de tienda — solo en modo Marketplace global
                const esModoMarketplace = <?= $es_tienda_individual ? 'false' : 'true' ?>;
                let badgeTienda = '';
                if (esModoMarketplace && producto.tienda_nombre) {
                    badgeTienda = producto.sucursal ?
                        `<small><a href="/${producto.sucursal}" style="font-size: 12px;text-decoration: none;" title="Ver tienda: ${producto.tienda_nombre}" onclick="event.stopPropagation()">
                        <i class="bi bi-shop"></i> ${producto.tienda_nombre}
                    </a></small>` :
                        `<small><span style="font-size: 12px;" title="${producto.tienda_nombre}">
                        <i class="bi bi-shop"></i> ${producto.tienda_nombre}
                    </span></small>`;
                }

                let precio_dolar_visible = producto.precio_dolar_visible;
                let precio_bs_visible = producto.precio_bs_visible;

                // Calcula precio final
                let precio_dolar = '$' + formatNumber(recortarADosDecimales(precio_dolar_visible));
                let precio_bs = 'Bs' + formatNumber(recortarADosDecimales(precio_bs_visible));

                if (recompensas) {
                    const tipo_recompensa = recompensas.tipo;
                    if (tipo_recompensa === 'descuento_ganancia') {
                        precio_dolar_visible = producto.precio_costo;
                        precio_bs_visible = producto.precio_costo_bs;
                        precio_dolar = `$${formatNumber(recortarADosDecimales(producto.precio_costo))} <span class="text-decoration-line-through text-danger text-xs">${precio_dolar}</span> `;
                        precio_bs = `Bs ${formatNumber(recortarADosDecimales(producto.precio_costo_bs))} <span class="text-decoration-line-through text-danger text-sm">${precio_bs}</span> `;
                    }
                }

                // Preparing Image source for modal interaction
                const hasImg = producto.img != '';
                const imgSrc = hasImg ? producto.img : '';
                // Randomize a review count/stars based on product id to simulate the image visually
                const pId = parseInt(producto.id) || 1;
                const starCount = 4 + (pId % 2 === 0 ? 0.5 : 1.0);
                const reviewCount = 40 + (pId * 7 % 100);
                let starsHtml = '';
                for (let s = 1; s <= 5; s++) {
                    if (s <= Math.floor(starCount)) starsHtml += '<i class="bi bi-star-fill"></i>';
                    else if (s === Math.ceil(starCount)) starsHtml += '<i class="bi bi-star-half"></i>';
                    else starsHtml += '<i class="bi bi-star"></i>';
                }

                // Random badge
                let badgeHtml = '';
                if (pId % 5 === 0) badgeHtml = '<div class="product-badge-top badge-oferta">Oferta</div>';
                else if (pId % 3 === 0) badgeHtml = '<div class="product-badge-top badge-mas-vendido">Más vendido</div>';

                let card = `
                    <div class="product-item">
                        <div class="product-card ${opacityClass}">
                            <div class="product-img-wrapper ${hasImg ? '' : 'no-img'}" style="cursor: pointer;" onclick="openProductModal('${producto.id}')">
                                ${hasImg
                                    ? `<img src="${imgSrc}" loading="lazy" class="product-img" alt="${producto.nombre}" onerror="productImgError(this)">`
                                    : `<div class="no-img-inner"><i class="bi bi-box-seam"></i></div>`
                                }
                            </div>
                            <div class="product-body" style="cursor: pointer;" onclick="openProductModal('${producto.id}')">
                                <h3 class="product-title" title="${producto.nombre}">${producto.nombre}</h3>
                                ${badgeTienda}
                            </div>
                            <div class="price-container">
                                <div class="price-main"><span class="price-dolar">${precio_dolar}</span></div>
                                <div class="price-sub"><span class="price-bs">${precio_bs}</span></div>
                                <div class="stock-text">${producto.stock > 0 ? 'Disponible: ' + producto.stock : 'Agotado'}</div>
                            </div>
                            <div class="card-actions">
                                <div class="quantity-control">
                                    <button class="btn-qty" onclick="changeQty(this, -1)">-</button>
                                    <input type="number" class="qty-input" 
                                        data-cantidad-id="${producto.id}"
                                        value="${DECIMAL_QTY_IDS.has(String(producto.id)) ? '0.5' : '1'}"
                                        min="${DECIMAL_QTY_IDS.has(String(producto.id)) ? '0.5' : '1'}"
                                        data-step="${DECIMAL_QTY_IDS.has(String(producto.id)) ? '0.5' : '1'}"
                                        data-min="${DECIMAL_QTY_IDS.has(String(producto.id)) ? '0.5' : '1'}"
                                        step="${DECIMAL_QTY_IDS.has(String(producto.id)) ? '0.5' : '1'}" readonly>
                                    <button class="btn-qty" onclick="changeQty(this, 1, ${isSocio ? null : producto.stock})">+</button>
                                </div>
                                <button class="btn btn-add btn-add-to-car ${btnDisabled}" 
                                    data-add-id="${producto.id}"
                                    data-codigo="${producto.codigo || ''}"
                                    data-P_D="${precio_dolar_visible}"
                                    data-P_B="${precio_bs_visible}"
                                    data-P_C="${producto.precio_costo}"
                                    data-P_C_BS="${producto.precio_costo_bs}">
                                    <i class="bi bi-cart2"></i>
                                    <span class="cart-item-anim"></span>
                                </button>
                            </div>
                        </div>
                    </div>`;
                $("#products-grid").append(card);
            });
        }
        // --- NEW MODAL LOGIC ---

        function openProductModal(productId) {
            const producto = productos_por_id[productId];
            if (!producto) return;

            // Populate Elements
            document.getElementById('modal-product-name').textContent = producto.nombre;
            document.getElementById('modal-product-code').textContent = producto.codigo || 'N/A';

            // Mostrar tienda en el modal (solo en modo Marketplace)
            const esModoMarketplace = <?= $es_tienda_individual ? 'false' : 'true' ?>;
            const tiendaWrapper = document.getElementById('modal-tienda-wrapper');
            if (esModoMarketplace && producto.tienda_nombre) {
                document.getElementById('modal-tienda-nombre').textContent = producto.tienda_nombre;
                const link = document.getElementById('modal-tienda-link');
                if (producto.sucursal) {
                    link.setAttribute('href', '/' + producto.sucursal);
                } else {
                    link.removeAttribute('href');
                }
                tiendaWrapper.style.display = 'block';
            } else {
                tiendaWrapper.style.display = 'none';
            }

            document.getElementById('modal-product-price-d').textContent = formatNumber(producto.precio_dolar_visible);
            document.getElementById('modal-product-price-bs').textContent = formatNumber(producto.precio_bs_visible);

            // Image
            const modalImg = document.getElementById('modal-product-img');
            const modalNoImg = document.getElementById('modal-no-img');
            modalImg.onerror = function () {
                modalImg.onerror = null;
                modalImg.style.display = 'none';
                if (modalNoImg) modalNoImg.style.display = 'flex';
            };
            if (producto.img) {
                modalImg.src = producto.img;
                modalImg.style.display = 'block';
                if (modalNoImg) modalNoImg.style.display = 'none';
            } else {
                modalImg.style.display = 'none';
                if (modalNoImg) modalNoImg.style.display = 'flex';
            }

            // Reset Qty
            document.getElementById('modal-product-qty').value = 1;

            // Update Add to Cart Button
            const btn = document.getElementById('modal-btn-add');
            btn.dataset.addId = producto.id;
            btn.dataset.codigo = producto.codigo || '';
            btn.dataset.P_D = producto.precio_dolar_visible;
            btn.dataset.P_P = producto.precio_peso_visible;
            btn.dataset.P_B = producto.precio_bs_visible;

            // Benefits / Description Logic
            const descList = document.getElementById('modal-product-description');
            descList.innerHTML = '';

            // Try to use description if available, otherwise generic benefits or nothing
            if (producto.descripcion) {
                // Split by newlines or bullets if structured
                const lines = producto.descripcion.split(/\r?\n|•|- /).filter(line => line.trim() !== '');
                if (lines.length > 0) {
                    lines.forEach(line => {
                        descList.innerHTML += `<li>${line.trim()}</li>`;
                    });
                } else {
                    descList.innerHTML += `<li>${producto.descripcion}</li>`;
                }
            } else {
                // Default generic benefits if empty
                descList.innerHTML = `
                    <li>Alta calidad garantizada</li>
                    <li>Disponibilidad inmediata</li>
                    <li>Mejor relación precio-valor</li>
                `;
            }

            // Category
            const categoryWrapper = document.getElementById('modal-product-category');
            const categoryText = document.getElementById('modal-category-text');
            if (producto.categorias) {
                categoryText.textContent = producto.categorias;
                categoryWrapper.style.display = 'block';
            } else {
                categoryWrapper.style.display = 'none';
            }

            // Show Modal
            const modal = new bootstrap.Modal(document.getElementById('modalProductoDetalle'));
            modal.show();
        }

        function changeModalQty(delta) {
            const input = document.getElementById('modal-product-qty');
            let val = parseInt(input.value) || 1;
            val += delta;
            if (val < 1) val = 1;

            input.value = val;
        }


        // Intersection Observer
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {

                cargarMasProductos();
            }
        }, {
            rootMargin: "200px"
        });

        observer.observe(sentinel);







        // Helper for quantity buttons
        const DECIMAL_QTY_IDS = new Set([4584, 4789, 5330, 4087, 4362, 4796, 4798, 4660, 4102, 1658, 2874, 1628, 2872, 1659, 3803, 3805, 1652, 4097, 1652, 3806, 1684, 1627].map(String));

        window.changeQty = function(btn, delta, max = null) {
            const input = btn.parentNode.querySelector('input');
            const step = parseFloat(input.dataset.step) || 1;
            const minVal = parseFloat(input.dataset.min) || step;
            let val = parseFloat(input.value);
            if (isNaN(val)) val = minVal;
            val = Math.round((val + delta * step) * 100) / 100;
            if (val < minVal) val = minVal;
            if (max != null && max > 0 && val > max) val = max;
            input.value = val;
        };

        var total_pesos = 0;
        var total_dolares = 0;
        var total_bolivares = 0;

        function confirmarVenta(tipo = 'venta') {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                return false;
            }
            $('button').blur();
            procesarPedido(6, 1)
        }

        document.addEventListener('click', function(event) {
            const btn = event.target.closest('.btn-add-to-car');
            if (btn) {
                let id_p = btn.getAttribute('data-add-id');
                let dolarventa_p = btn.getAttribute('data-P_D')
                let pesoventa_p = btn.getAttribute('data-P_P')
                let bolivarventa_p = btn.getAttribute('data-P_B')

                $('#search, #search-mobile').val('')
                $("#search-results, #search-results-mobile").removeClass('show');
                addtocarJS(id_p, dolarventa_p, pesoventa_p, bolivarventa_p, null, null);
            }
        });

        // * IndexedBD //
        const db = new Dexie("POS_DB");

        db.version(2).stores({
            carritoActivo: 'id',
            carritosVenta: 'id',
            carritosReservados: 'id',
            cart_meta: 'id'
        });

        // Si el navegador ya tiene un POS_DB en una versión mayor (otra app u otra
        // build del mismo origen), toda consulta falla con VersionError: se recrea
        // el carrito local una vez en vez de dejar la tienda sin carrito.
        async function abrirDbCarrito() {
            try {
                await db.open();
            } catch (err) {
                if (err.name !== 'VersionError') throw err;
                await Dexie.delete('POS_DB');
                await db.open();
            }
        }
        // * IndexedBD //

        var carritoActivo = {};

        // Listen for Modal Add to Cart
        document.getElementById('modal-btn-add').addEventListener('click', function() {
            const btnMod = this;
            const id = btnMod.dataset.addId;
            const qty = document.getElementById('modal-product-qty').value;

            if (!id) return;

            // 1. Sincronizar cantidad en todos los inputs visibles de este producto
            document.querySelectorAll(`input[data-cantidad-id="${id}"]`).forEach(input => {
                input.value = qty;
            });

            // 2. Buscar el botón de agregar original (en grilla o buscador)
            // Priorizamos el de la grilla si existe, sino cualquier otro
            const originalBtn = document.querySelector(`.btn-add-to-car[data-add-id="${id}"]`);

            if (originalBtn) {
                // Simular click en el botón original
                originalBtn.click();
                bootstrap.Modal.getInstance(document.getElementById('modalProductoDetalle')).hide();
            } else {
                // Fallback: Si no se encuentra el botón físico (ej: producto no renderizado aún), 
                // llamar directamente a la función
                addtocarJS(
                    id,
                    btnMod.dataset.p_d,
                    btnMod.dataset.p_p,
                    btnMod.dataset.p_b,
                    null,
                    qty,
                    btnMod,
                    btnMod.dataset.p_c,
                    btnMod.dataset.p_c_bs
                );
            }
        });

        async function updateCartTimestamp() {
            await db.cart_meta.put({
                id: 'last_updated',
                timestamp: Date.now()
            });
        }

        async function checkCartExpiration() {
            const meta = await db.cart_meta.get('last_updated');
            if (meta) {
                const now = Date.now();
                const diff = now - meta.timestamp;
                const limit = 20 * 60 * 1000; // 20 minutes in ms

                if (diff > limit) {
                    await db.carritoActivo.clear();
                    await db.cart_meta.delete('last_updated');
                    return true; // Expired
                }
            }
            return false;
        }

        // Cargar carrito activo desde IndexedDB al iniciar
        (async function cargarCarritoInicial() {
            await abrirDbCarrito();
            await checkCartExpiration();

            // Show skeletons ONLY if grid is empty to avoid race condition
            if (productsGrid.children.length === 0) {
                showSkeletons();
            }

            const items = await db.carritoActivo.toArray();
            carritoActivo = items.reduce((obj, item) => {
                obj[item.id] = item;
                return obj;
            }, {});
            actualizarCarritoJs();

            // Trigger load if not already loading
            if (!isLoading) {
                cargarMasProductos();
            }
        })();

        async function actualizarCarritoActivo() {
            const items = await db.carritoActivo.toArray();
            carritoActivo = items.reduce((obj, item) => {
                obj[item.id] = item;
                return obj;
            }, {});
            actualizarCarritoJs();
        }




        /* Actualizar ubicacion */
        let mapUbicacion, markerUbicacion, geojsonLayer;
        const freeDeliveryComs = ["JOSE ANTONIO PAEZ", "LA FLORIDA", "CHAPARRALITO", "EL POLIGONO", "URB EL CAICET", "URB AV DEL EJERCITO", "SIMON BOLIVAR", "CARINAGUITA", "JOSE MARIA VARGAS", "LA PAILA", "LOS CAJONES DE SAN ENRIQUE", "SAN ENRIQUE CENTRO", "EL BOSQUE SAN ENRIQUE", "EL BAJO DE SAN ENRIQUE"];
        // El GeoJSON muestra "Comunidad - Puerto Ayacucho Edo Amazonas"; para
        // comparar las zonas de delivery gratis se usa solo el nombre base.
        const comunidadBase = (nombre) => String(nombre || '').replace(/\s*-\s*Puerto Ayacucho Edo Amazonas$/i, '').toUpperCase();

        // --- CONFIGURACIÓN DE GEOCERCA ---
        const GEOFENCE_CENTER = [5.653802, -67.605304];
        const GEOFENCE_RADIUS = 6500; // 6.5 kilómetros en metros
        let geofenceCircle = null;

        function isWithinPerimeter(lat, lng) {
            if (!lat || !lng) return false;
            const center = L.latLng(GEOFENCE_CENTER);
            const target = L.latLng(lat, lng);
            return center.distanceTo(target) <= GEOFENCE_RADIUS;
        }

        async function actualizarUbicacion() {
            const el = document.getElementById('modalGestionUbicacion');
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            modal.show();

            // Siempre mostrar la lista al abrir
            regresarALista();

            // Cargar direcciones
            try {
                const res = await fetch('api/perfil_data.php');
                const data = await res.json();
                if (data.success) {
                    renderListaUbicaciones(data.addresses);
                }
            } catch (e) {
                console.error("Error cargando direcciones:", e);
            }

            // Inicializar mapa si no existe
            if (!mapUbicacion) {
                setTimeout(() => {
                    mapUbicacion = L.map('map-gestion').setView(GEOFENCE_CENTER, 13);
                    L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                        maxZoom: 20
                    }).addTo(mapUbicacion);

                    // Dibujar Círculo de Perímetro (Geocerca)
                    geofenceCircle = L.circle(GEOFENCE_CENTER, {
                        color: '#198754', // Verde Bootstrap
                        fillColor: '#25ca7dff',
                        fillOpacity: 0.1,
                        radius: GEOFENCE_RADIUS,
                        weight: 2,
                        dashArray: '5, 10',
                        interactive: false
                    }).addTo(mapUbicacion);

                    let allCommunitiesNames = [];
                    fetch('assets/js/comunidades.geojson')
                        .then(r => r.json())
                        .then(geoData => {
                            allCommunitiesNames = geoData.features.map(f => f.properties.NAME).sort();

                            geojsonLayer = L.geoJSON(geoData, {
                                style: function(feature) {
                                    const isFree = freeDeliveryComs.includes(comunidadBase(feature.properties.NAME));
                                    return {
                                        color: isFree ? "#198754" : "#bfd8ffff", // Verde si es gratis, Azul si es pago
                                        weight: 2,
                                        fillOpacity: 0.1
                                    };
                                }
                            }).addTo(mapUbicacion);
                        });

                    // Intentar obtener geolocalización del navegador
                    if ("geolocation" in navigator) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;

                                if (isWithinPerimeter(lat, lng)) {
                                    // Actualizar mapa y marcador si está dentro
                                    mapUbicacion.setView([lat, lng], 18);

                                    if (markerUbicacion) mapUbicacion.removeLayer(markerUbicacion);
                                    markerUbicacion = L.marker([lat, lng]).addTo(mapUbicacion);

                                    document.getElementById('loc-lat').value = lat;
                                    document.getElementById('loc-lng').value = lng;

                                    Notiflix.Notify.success('Ubicación detectada exitosamente');
                                } else {
                                    mapUbicacion.setView(GEOFENCE_CENTER, 14);
                                }
                            },
                            (error) => {
                                console.warn('Error de geolocalización:', error.message);
                            }, {
                                enableHighAccuracy: true,
                                timeout: 5000,
                                maximumAge: 0
                            }
                        );
                    }

                    mapUbicacion.on('click', function(e) {
                        if (!isWithinPerimeter(e.latlng.lat, e.latlng.lng)) {
                            Notiflix.Notify.failure('La ubicación seleccionada está fuera de nuestra zona de entrega.');
                            mapUbicacion.panTo(GEOFENCE_CENTER);
                            return;
                        }

                        const results = leafletPip.pointInLayer(e.latlng, geojsonLayer);
                        if (results.length > 0) {
                            const comName = results[0].feature.properties.NAME;
                            const isFree = freeDeliveryComs.includes(comunidadBase(comName));

                            document.getElementById('loc-comunidad').value = comName;
                            document.getElementById('loc-comunidad-input').value = comName;
                            document.getElementById('loc-delivery-gratis').value = isFree ? 1 : 0;

                            if (isFree) {
                                Notiflix.Notify.success('¡Excelente! Esta zona cuenta con delivery GRATIS.');
                            } else {
                                Notiflix.Notify.info('Zona seleccionada: Entrega disponible con cargo de $2.');
                            }

                            if (markerUbicacion) mapUbicacion.removeLayer(markerUbicacion);
                            markerUbicacion = L.marker(e.latlng).addTo(mapUbicacion);
                            document.getElementById('loc-lat').value = e.latlng.lat;
                            document.getElementById('loc-lng').value = e.latlng.lng;
                        } else {
                            Notiflix.Notify.warning('Por favor selecciona una ubicación sobre una de las comunidades marcadas.');
                        }
                    });

                    // --- LOGICA BUSQUEDA DE COMUNIDADES ---
                    const inputCom = document.getElementById('loc-comunidad-input');
                    const resultsDiv = document.getElementById('loc-community-results');

                    inputCom.addEventListener('input', (e) => {
                        const query = e.target.value.toLowerCase();
                        if (query.length < 2) {
                            resultsDiv.style.display = 'none';
                            return;
                        }

                        // Filtrar todas las comunidades
                        const matches = allCommunitiesNames.filter(c => c.toLowerCase().includes(query));

                        if (matches.length > 0) {
                            let html = '';
                            matches.forEach(m => {
                                html += `<div class="community-item" onclick="seleccionarComunidadGestion('${m}')">${m}</div>`;
                            });
                            resultsDiv.innerHTML = html;
                            resultsDiv.style.display = 'block';
                        } else {
                            resultsDiv.style.display = 'none';
                        }
                    });

                    document.addEventListener('click', (e) => {
                        if (inputCom && resultsDiv && !inputCom.contains(e.target) && !resultsDiv.contains(e.target)) {
                            resultsDiv.style.display = 'none';
                        }
                    });

                    window.seleccionarComunidadGestion = function(name) {
                        inputCom.value = name;
                        document.getElementById('loc-comunidad').value = name;
                        const isFree = freeDeliveryComs.includes(comunidadBase(name));
                        document.getElementById('loc-delivery-gratis').value = isFree ? 1 : 0;
                        resultsDiv.style.display = 'none';

                        if (isFree) {
                            Notiflix.Notify.success('¡Excelente! Esta zona cuenta con delivery GRATIS.');
                        } else {
                            Notiflix.Notify.info('Zona seleccionada: Entrega disponible con cargo de $2.');
                        }

                        // Zoom al polígono si existe
                        if (geojsonLayer) {
                            geojsonLayer.eachLayer(layer => {
                                if (layer.feature.properties.NAME.toUpperCase() === name.toUpperCase()) {
                                    mapUbicacion.fitBounds(layer.getBounds(), {
                                        padding: [20, 20]
                                    });
                                }
                            });
                        }
                    };
                }, 500);
            }
        }

        function renderListaUbicaciones(addresses) {
            const container = document.getElementById('lista-ubicaciones-user');
            container.innerHTML = '';

            if (addresses.length === 0) {
                container.innerHTML = '<div class="p-3 text-center text-muted">No tienes ubicaciones guardadas.</div>';
                return;
            }

            addresses.forEach(addr => {
                const div = document.createElement('div');
                div.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                div.innerHTML = `
                    <div class="flex-grow-1">
                        <div class="fw-bold text-uppercase small text-success">${addr.nombre_receptor || 'Sin nombre'}</div>
                        <div class="small fw-semibold text-dark">${addr.direccion}</div>
                        <small class="text-muted d-block">${addr.comunidad || 'Sin comunidad'}</small>
                        <small class="fw-bold d-block ${addr.delivery_gratis_confirmado == 1 ? 'text-success' : 'text-primary'}" style="font-size: 0.75rem;">
                            <i class="bi bi-truck me-1"></i>${addr.delivery_gratis_confirmado == 1 ? 'DELIVERY GRATIS' : 'DELIVERY $2'}
                        </small>
                    </div>
                    <button class="btn btn-sm btn-success rounded-pill px-3" onclick='editarUbicacion(${JSON.stringify(addr)})'>Actualizar</button>
                `;
                container.appendChild(div);
            });
        }

        window.editarUbicacion = function(addr) {
            document.getElementById('loc-form').reset();
            document.getElementById('loc-form-title').innerText = 'Actualizar Ubicación';
            document.getElementById('loc-id').value = addr.id;
            document.getElementById('loc-nombre').value = addr.nombre_receptor || DEFAULT_RECEPTOR;
            document.getElementById('loc-telefono').value = addr.telefono || DEFAULT_TELEFONO;
            document.getElementById('loc-comunidad-input').value = addr.comunidad || '';
            document.getElementById('loc-direccion').value = addr.direccion;
            document.getElementById('loc-referencia').value = addr.referencia || '';
            document.getElementById('loc-comunidad').value = addr.comunidad || '';
            document.getElementById('loc-lat').value = addr.lat;
            document.getElementById('loc-lng').value = addr.lng;
            document.getElementById('loc-delivery-gratis').value = addr.delivery_gratis_confirmado || 0;

            // Ocultar sección de datos exactos si es actualización
            const soloNuevo = document.getElementById('solo_nuevo_registro');
            if (soloNuevo) soloNuevo.classList.add('d-none');

            // --- VALIDACION AUTOMATICA PARA DELIVERY GRATIS ---
            const notifGratis = document.getElementById('notif-delivery-gratis');
            if (notifGratis) notifGratis.classList.add('d-none');

            if (addr.lat && addr.lng && geojsonLayer) {
                const results = leafletPip.pointInLayer([addr.lng, addr.lat], geojsonLayer); // Nota: LeafletPip usa [lng, lat]
                if (results.length > 0) {
                    const comName = results[0].feature.properties.NAME;
                    const isFree = freeDeliveryComs.includes(comunidadBase(comName));
                    document.getElementById('loc-comunidad').value = comName;
                    document.getElementById('loc-comunidad-input').value = comName;
                    document.getElementById('loc-delivery-gratis').value = isFree ? 1 : 0;
                    if (notifGratis && isFree) notifGratis.classList.remove('d-none');
                } else {
                    document.getElementById('loc-delivery-gratis').value = 0; // Asegurar 0 si está fuera
                }
            }

            if (markerUbicacion) mapUbicacion.removeLayer(markerUbicacion);
            const latlng = [addr.lat, addr.lng];
            markerUbicacion = L.marker(latlng).addTo(mapUbicacion);
            mapUbicacion.setView(latlng, 16);

            document.getElementById('view-lista-ubicaciones').classList.add('d-none');
            document.getElementById('view-form-ubicacion').classList.remove('d-none');

            // Fix gray map issue
            setTimeout(() => {

                mapUbicacion.invalidateSize();
                mapUbicacion.setView(latlng, 16);
            }, 200);
        };

        window.mostrarFormNuevaUbicacion = function() {
            document.getElementById('loc-form').reset();
            document.getElementById('loc-comunidad-input').value = '';
            document.getElementById('loc-form-title').innerText = 'Agregar Nueva Ubicación';
            document.getElementById('loc-id').value = '';
            document.getElementById('loc-nombre').value = DEFAULT_RECEPTOR;
            document.getElementById('loc-telefono').value = DEFAULT_TELEFONO;

            // Mostrar sección de datos exactos si es nuevo registro
            const soloNuevo = document.getElementById('solo_nuevo_registro');
            if (soloNuevo) soloNuevo.classList.remove('d-none');

            // Resetear notificación de delivery gratis
            const notifGratis = document.getElementById('notif-delivery-gratis');
            if (notifGratis) notifGratis.classList.add('d-none');
            if (markerUbicacion) mapUbicacion.removeLayer(markerUbicacion);

            document.getElementById('view-lista-ubicaciones').classList.add('d-none');
            document.getElementById('view-form-ubicacion').classList.remove('d-none');

            // Fix gray map issue
            setTimeout(() => {
                mapUbicacion.invalidateSize();
            }, 200);
        };

        window.regresarALista = function() {
            document.getElementById('view-form-ubicacion').classList.add('d-none');
            document.getElementById('view-lista-ubicaciones').classList.remove('d-none');

            const notifGratis = document.getElementById('notif-delivery-gratis');
            if (notifGratis) notifGratis.classList.add('d-none');
        };

        window.guardarGestionUbicacion = async function() {
            const payload = {
                id: document.getElementById('loc-id').value,
                nombre_receptor: document.getElementById('loc-nombre').value,
                telefono: document.getElementById('loc-telefono').value,
                direccion: document.getElementById('loc-direccion').value,
                referencia: document.getElementById('loc-referencia').value,
                lat: document.getElementById('loc-lat').value,
                lng: document.getElementById('loc-lng').value,
                comunidad: document.getElementById('loc-comunidad').value,
                delivery_gratis_confirmado: document.getElementById('loc-delivery-gratis').value
            };

            if (!payload.direccion || !payload.lat) {
                Notiflix.Notify.failure('Por favor completa todos los datos y selecciona en el mapa.');
                return;
            }

            Notiflix.Loading.pulse('Guardando...');


            const res = await fetch('api/update_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            let data = await res.text();


            try {
                data = JSON.parse(data);

                Notiflix.Loading.remove();

                if (data.success) {
                    Notiflix.Notify.success(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('modalGestionUbicacion')).hide();
                    // Opcional: recargar vista o datos si es necesario
                } else {
                    Notiflix.Notify.failure(data.message);
                }
            } catch (e) {
                Notiflix.Loading.remove();
                Notiflix.Notify.failure('Error en el servidor');
            }
        };

        $(document).ready(function() {
            $(document).on('click', '#btn-actualizar-ubicacion', function() {
                actualizarUbicacion();
            });
        });




        let toastTimeout;

        function showToastCart(message = 'Agregado al carrito') {
            const toast = document.getElementById('toast-cart');
            const text = toast.querySelector('.toast-text');

            text.textContent = message;

            clearTimeout(toastTimeout);

            // Reset
            toast.classList.remove('expand', 'show');

            // Paso 1: mostrar icono
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Paso 2: expandir
            setTimeout(() => {
                toast.classList.add('expand');
            }, 300);

            // Paso 3: ocultar
            toastTimeout = setTimeout(() => {
                toast.classList.remove('expand');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 300);
            }, 2800);
        }

        async function verificarStock(id, cantidad) {
            // Mostrar Loader global o especifico
            Notiflix.Loading.standard('Verificando disponibilidad...');

            try {
                const t_slug = (typeof tienda_slug_js !== "undefined") ? tienda_slug_js : "";
                const res = await fetch(`api/check_stock.php?id=${id}&cantidad=${cantidad}&tienda_slug=${t_slug}`);
                const data = await res.json();

                Notiflix.Loading.remove();

                if (data.success) {
                    return {
                        'result': true,
                        'message': 'Disponible'
                    };
                } else {
                    return {
                        'result': false,
                        'message': 'No hay stock suficiente'
                    };
                }
            } catch (e) {
                Notiflix.Loading.remove();
                return {
                    'result': false,
                    'message': 'Problemas de internet, intente nuevamente'
                }; // Asumir no disponible si hay error de red para seguridad
            }
        }

        async function addtocarJS(id, dolarventa_p, pesoventa_p, bolivarventa_p, mayor, cantidad_scann = null, btnElement = null) {
            const inputCantidad = document.querySelector(`input[data-cantidad-id="${id}"]`);
            // Determine quantity to check (New + Existing in Cart)
            let newQty = inputCantidad ? parseFloat(inputCantidad.value) : 1;
            if (cantidad_scann != null) newQty = parseFloat(cantidad_scann);

            const idStr = id.toString();
            let currentInCart = 0;
            if (carritoActivo[idStr]) {
                currentInCart = carritoActivo[idStr].qty;
            }

            const totalQtyToCheck = newQty + currentInCart;

            const disponible = await verificarStock(id, totalQtyToCheck);
            if (!disponible['result']) {
                Notiflix.Notify.failure(disponible['message']);
                return;

            }

            if (btnElement) {
                const $btn = $(btnElement);
                const $cart = $('#cartDropdown');

                $btn.addClass('sendtocart');
                setTimeout(function() {
                    $btn.removeClass('sendtocart');
                    $cart.addClass('shake');
                    setTimeout(function() {
                        $cart.removeClass('shake');
                    }, 500);
                }, 1000);
            }

            let cant = inputCantidad ? parseFloat(inputCantidad.value) : 1;
            if (cantidad_scann != null) cant = parseFloat(cantidad_scann);

            if (isNaN(cant) || cant <= 0) {
                Notiflix.Notify.warning('Cantidad inválida. Debe ser un número mayor a 0.');
                return;
            }

            if (inputCantidad) inputCantidad.value = 1;

            if (!productos_por_id[id]) {
                console.error(`Producto con ID ${id} no encontrado.`);
                return;
            }
            const idPedido = id.toString();
            const producto = productos_por_id[idPedido];

            if (carritoActivo[idPedido]) {
                carritoActivo[idPedido].qty += cant;
                // Actualizar por si acaso (items viejos o búsqueda incompleta)
                carritoActivo[idPedido].id_sucursal = producto.id_sucursal;
                carritoActivo[idPedido].bss_id = producto.bss_id;
            } else {
                carritoActivo[idPedido] = {
                    id: idPedido,
                    name: producto.nombre,
                    price_C: parseFloat(producto.precio_costo),
                    price_C_Bs: parseFloat(producto.precio_costo_bs),
                    price: parseFloat(dolarventa_p),
                    pricePeso: parseFloat(pesoventa_p),
                    priceBolivar: parseFloat(bolivarventa_p),
                    qty: cant,
                    mayor: mayor,
                    cantidadPaca: 1,
                    priceCosto: parseFloat(producto.precio_costo),
                    bss_id: producto.bss_id,
                    id_sucursal: producto.id_sucursal,
                    tienda_nombre: producto.tienda_nombre || producto.sucursal || 'Tienda'
                };
            }


            if (carritoActivo[idPedido].qty == 0) {
                await db.carritoActivo.delete(idPedido);
            } else {
                await db.carritoActivo.put(carritoActivo[idPedido]);
            }
            await updateCartTimestamp();
            showToastCart()
            actualizarCarritoJs();
        }

        async function actualizarCarritoJs() {
            total_pesos = total_dolares = total_bolivares = 0;
            const items = Object.values(carritoActivo);

            const cartItems = $("#cart-items");
            const cartFooter = $("#cart-footer");
            const cartCount = $("#cart-count");
            const cartItemsMobile = $(".cart-items-mobile");
            const cartFooterMobile = $(".cart-footer-mobile");
            const cartCountMobile = $(".cart-count-mobile");

            if (items.length > 0) {
                let html = '';
                // Agrupar por tienda
                let grouped = {};
                items.forEach(element => {
                    let key = element.tienda_nombre || 'Tienda';
                    if (!grouped[key]) grouped[key] = [];
                    grouped[key].push(element);
                });

                for (let tienda in grouped) {
                    html += `
                        <div class="store-cart-group">
                            <h6>
                                <i class="bi bi-shop"></i> ${tienda}
                            </h6>
                            <div class="store-items">
                    `;
                    grouped[tienda].forEach(element => {
                        let subtotalBolivar = recortarADosDecimales(element.priceBolivar * element.qty);
                        let subtotalDolar = recortarADosDecimales(element.price * element.qty);

                        html += `
                            <div class="cart-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1 pe-2">
                                        <div class="cart-item-title">${element.name}</div>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="qty-badge">x${element.qty}</span>
                                            <span class="cart-item-price">$${formatNumber(subtotalDolar)}</span>
                                        </div>
                                    </div>
                                    <button class="btn-remove-item" onclick="quitarProductoJs('${element.id}')" title="Eliminar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        total_dolares = recortarADosDecimales(total_dolares + subtotalDolar);
                        total_bolivares = recortarADosDecimales(total_bolivares + subtotalBolivar);
                    });
                    html += `</div></div>`;
                }

                cartItems.html(html);
                cartItemsMobile.html(html);
                total_pesos = Math.round(total_pesos);

                $("#cart-total-dolar, .cart-total-dolar-mobile").text(`$${formatNumber(recortarADosDecimales(total_dolares))}`);
                $("#cart-total-bs, .cart-total-bs-mobile").text(`${formatNumber(recortarADosDecimales(total_bolivares))} Bs`);

                cartFooter.show();
                cartFooterMobile.show();
                cartCount.text(items.length);
                cartCountMobile.text(items.length);
            } else {
                const emptyHtml = `
                    <div class="cart-empty-state animate-fade-in">
                        <div class="empty-icon">
                            <i class="bi bi-bag-x"></i>
                        </div>
                        <h6>Tu carrito está vacío</h6>
                        <p>Agrega productos para comenzar</p>
                    </div>
                `;
                cartItems.html(emptyHtml);
                cartItemsMobile.html(emptyHtml);
                cartFooter.hide();
                cartFooterMobile.hide();
                cartCount.text('0');
                cartCountMobile.text('0');
            }
        }

        async function quitarProductoJs(id) {
            await deleteFromIndexedDB('carritoActivo', id);
            await updateCartTimestamp();
            await actualizarCarritoActivo()
        }

        async function vaciarCarritoJs() {
            Notiflix.Confirm.show(
                'Vaciar Carrito',
                '¿Estás seguro de que deseas vaciar el carrito?',
                'Sí, vaciar',
                'Cancelar',
                async () => {
                    carritoActivo = {};
                    await db.carritoActivo.clear();
                    await db.cart_meta.delete('last_updated');
                    actualizarCarritoJs();
                    Notiflix.Notify.success('Carrito vaciado');
                }
            );
        }

        // ======================
        // FORMATEO DE NÚMEROS
        // ======================
        function formatNumber(num) {
            return parseFloat(num).toLocaleString('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatPeso(num) {
            return Math.round(parseFloat(num));
        }

        function formatearMiles(num) {
            return parseFloat(num).toLocaleString('es-VE');
        }

        function recortarADosDecimales(num) {
            return Math.floor(Math.round(parseFloat(num) * 1e6) / 1e4) / 100;
        }

        // ======================
        // PROCESAR PEDIDO (VENTA)
        // ======================
        async function procesarPedido(metodoPago, despacho, nombreC = null) {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                console.warn("No hay carrito activo para procesar.");
                return false;
            }

            const idPedido = String(Date.now());;
            const datosCliente = {
                nombre: nombreC || "",
                cedula: "",
                telefono: ""
            };

            try {
                // Separar el carrito en multiples carritos (uno por bss_id)
                let carritosPorEmpresa = {};
                for (let k in carritoActivo) {
                    if (carritoActivo.hasOwnProperty(k)) {
                        let prod = carritoActivo[k];
                        let bss = prod.bss_id || 'default';
                        if (!carritosPorEmpresa[bss]) {
                            carritosPorEmpresa[bss] = {
                                id: idPedido + '-' + bss,
                                metodoPago,
                                despacho,
                                valorFinalVenta: 0,
                                valorFinalBs: 0,
                                valorFinalCop: 0,
                                datosCliente,
                                productos: {}
                            };
                        }
                        carritosPorEmpresa[bss].productos[k] = prod;
                        carritosPorEmpresa[bss].valorFinalVenta += prod.price * (prod.qty ?? 1);
                        carritosPorEmpresa[bss].valorFinalBs += prod.priceBolivar * (prod.qty ?? 1);
                        carritosPorEmpresa[bss].valorFinalCop += prod.pricePeso * (prod.qty ?? 1);
                    }
                }

                // Guardar cada sub-carrito como un pedido independiente en IndexedDB
                for (let bss in carritosPorEmpresa) {
                    await db.carritosVenta.put(carritosPorEmpresa[bss]);
                }

                carritoActivo = {};
                await db.carritoActivo.clear();
                enviarPedidosProcesados();
                return true;
            } catch (e) {
                console.error("Error guardando en IndexedDB, ", e);
            }
        }

        // ======================
        // ENVIAR PEDIDOS PROCESADOS
        // ======================
        async function enviarPedidosProcesados() {
            let pedidosIndexedDB = await db.carritosVenta.toArray();

            if (pedidosIndexedDB.length === 0) {
                console.warn("No hay pedidos procesados para enviar.");
                return;
            }

            total_dolares = 0;
            total_bolivares = 0;
            total_pesos = 0;

            fetch(base_url + 'accion_carta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'enviarPedidos',
                        pedidos: JSON.stringify(pedidosIndexedDB),
                    }),
                })
                .then((res) => res.text())
                .then(async (text) => {

                    let response;
                    try {
                        response = JSON.parse(text);
                    } catch (e) {
                        console.error('Error al parsear JSON:', e);
                        Notiflix.Notify.failure('Respuesta no válida del servidor.');
                        return;
                    }

                    if (response.status) {
                        Notiflix.Notify.success('Venta procesada correctamente.');
                        await db.carritosVenta.clear();
                        actualizarCarritoJs();
                    } else {
                        Notiflix.Notify.failure(response.data || 'Error en la respuesta del servidor.');
                    }
                })
                .catch((error) => {
                    console.error('Error en fetch:', error);
                    Notiflix.Notify.failure('Error al enviar los pedidos. Intente nuevamente.');
                });
        }

        async function deleteFromIndexedDB(storeName, id) {
            try {
                await db.table(storeName).delete(String(id));
                console.log(`Registro con ID ${id} eliminado de ${storeName}`);
            } catch (err) {
                console.error(`Error eliminando de ${storeName}`, err);
            }
        }

        async function getFromIndexedDB(storeName, id) {
            try {
                return await db.table(storeName).get(String(id));
            } catch (error) {
                console.error(`Error obteniendo registro de ${storeName}`, error);
                return null;
            }
        }

        async function getAllFromIndexedDB(storeName) {
            try {
                return await db.table(storeName).toArray();
            } catch (error) {
                console.error(`Error obteniendo todos los registros de ${storeName}`, error);
                return [];
            }
        }

        // --- FUNCIONES CARRITO GUARDADO ---
        function saveCartPrompt() {
            if (Object.keys(carritoActivo).length === 0) {
                Notiflix.Notify.warning('El carrito está vacío');
                return;
            }
            // Ocultar modal carrito antes de mostrar el de guardar
            bootstrap.Modal.getInstance(document.getElementById('modalCarrito')).hide();

            document.getElementById('saved_cart_name').value = '';
            const modal = new bootstrap.Modal(document.getElementById('modalGuardarCarrito'));
            modal.show();
        }

        async function confirmSaveCart() {
            const nameInput = document.getElementById('saved_cart_name');
            const name = nameInput.value;
            if (!name) {
                Notiflix.Notify.warning('Por favor ingresa un nombre');
                return;
            }

            Notiflix.Loading.pulse('Guardando carrito...');

            try {
                const response = await fetch('api/save_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        name: name,
                        content: JSON.stringify(carritoActivo)
                    })
                });

                const data = await response.json();
                Notiflix.Loading.remove();

                if (data.success) {
                    Notiflix.Notify.success(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('modalGuardarCarrito')).hide();
                } else {
                    Notiflix.Notify.failure(data.message);
                }
            } catch (error) {
                Notiflix.Loading.remove();
                Notiflix.Notify.failure('Error de comunicación');
            }
        }

        async function loadSavedCarts() {
            if (document.body.dataset.userLoggedIn !== 'true') {
                Notiflix.Notify.warning('Debes iniciar sesión para ver tus carritos guardados');
                return;
            }

            Notiflix.Loading.standard('Cargando tus carritos...');

            try {
                const response = await fetch('api/get_saved_carts.php');
                const data = await response.json();
                Notiflix.Loading.remove();

                if (data.success) {
                    const list = document.getElementById('saved-carts-list');
                    list.innerHTML = '';

                    if (data.carts.length === 0) {
                        list.innerHTML = '<div class="p-5 text-center text-muted"><i class="bi bi-folder2-open d-block fs-1 mb-2 opacity-25"></i> No tienes carritos guardados</div>';
                    } else {
                        data.carts.forEach(cart => {
                            list.innerHTML += `
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 border-0 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold text-dark">${cart.name}</h6>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-muted fw-normal"><i class="bi bi-calendar3 me-1"></i> ${new Date(cart.created_at).toLocaleDateString()}</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-success btn-sm rounded-pill px-4 shadow-sm" onclick="selectSavedCart(${cart.id})">
                                        Cargar
                                    </button>
                                </div>
                            `;
                        });
                    }

                    const modal = new bootstrap.Modal(document.getElementById('modalListarCarts'));
                    modal.show();
                } else {
                    Notiflix.Notify.failure(data.message);
                }
            } catch (error) {
                Notiflix.Loading.remove();
                Notiflix.Notify.failure('Error de comunicación');
            }
        }

        async function selectSavedCart(id) {
            Notiflix.Confirm.show(
                'Cargar Carrito',
                '¿Deseas cargar este carrito? El carrito actual se vaciará.',
                'Sí, cargar',
                'Cancelar',
                async () => {
                    Notiflix.Loading.standard('Verificando disponibilidad...');
                    try {
                        const response = await fetch(`api/load_saved_cart.php?id=${id}`);
                        const data = await response.json();

                        if (data.success) {
                            // Vaciar carrito actual
                            carritoActivo = {};
                            await db.carritoActivo.clear();

                            const items = data.content;
                            let loadedCount = 0;
                            let outOfStockCount = 0;

                            for (const pid in items) {
                                const item = items[pid];
                                const disponible = await verificarStock(item.id, item.qty);

                                if (disponible.result) {
                                    // Agregar a carrito activo
                                    carritoActivo[item.id] = item;
                                    await db.carritoActivo.put(item);
                                    loadedCount++;
                                } else {
                                    outOfStockCount++;
                                }
                            }

                            actualizarCarritoJs();
                            bootstrap.Modal.getInstance(document.getElementById('modalListarCarts')).hide();
                            Notiflix.Loading.remove();

                            if (outOfStockCount > 0) {
                                Notiflix.Report.info(
                                    'Carga Completada',
                                    `Se cargaron ${loadedCount} productos. ${outOfStockCount} productos no estaban disponibles y no se agregaron.`,
                                    'Entendido'
                                );
                            } else {
                                Notiflix.Notify.success('Carrito cargado correctamente');
                            }
                        } else {
                            Notiflix.Loading.remove();
                            Notiflix.Notify.failure(data.message);
                        }
                    } catch (error) {
                        Notiflix.Loading.remove();
                        Notiflix.Notify.failure('Error de comunicación');
                    }
                }
            );
        }

        async function shareReferralJS() {
            try {
                Notiflix.Loading.standard('Generando enlace...');
                const res = await fetch('api/perfil_data.php');
                const data = await res.json();
                Notiflix.Loading.remove();

                if (!data.success) {
                    Notiflix.Notify.failure('Error: ' + data.message);
                    return;
                }

                const refLink = `${window.location.origin}/?ref=${data.user.referral_code}`;
                const text = `Hola, te recomiendo comprar en iSeller Store 🛒 Encuentra productos de tiendas de tu localidad y recibe tu pedido en casa: 👉 ${refLink}`;

                if (navigator.share) {
                    navigator.share({
                        title: 'Únete a iSeller Store',
                        text: text,
                        url: refLink
                    }).catch(err => {
                        if (err.name !== 'AbortError') {
                            console.error('Share failed:', err);
                        }
                    });
                } else {
                    navigator.clipboard.writeText(text).then(() => {
                        Notiflix.Notify.success('Enlace de referido copiado al portapapeles');
                    });
                }
            } catch (err) {
                Notiflix.Loading.remove();
                console.error('Error sharing referral:', err);
                Notiflix.Notify.failure('No se pudo compartir el código');
            }
        }

        $(document).ready(function() {

            $('#loc-comunidad-input').on('click', function() {
                Notiflix.Notify.failure('Indica tu ubicación en el mapa');
            });

        });
    </script>

    <!-- Modal Gestión de Ubicación -->
    <div class="modal fade" id="modalGestionUbicacion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Gestionar mis Ubicaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <!-- Vista de Lista -->
                    <div id="view-lista-ubicaciones">
                        <p>
                            <i class="bi bi-info-circle"></i>
                            Actualiza tu ubicación para acceder al delivery gratis en las zonas indicadas.
                        </p>
                        <!-- Zonas con Envío Gratis -->
                        <div class="alert alert-success border-0 rounded-4 mb-4">
                            <small class="fw-bold d-block mb-1"><i class="bi bi-truck me-1"></i> ENVÍO GRATIS (Compras > $3)</small>
                            <ul class="mb-0 small row row-cols-2 g-1">
                                <li>JOSE ANTONIO PAEZ</li>
                                <li>LA FLORIDA</li>
                                <li>CHAPARRALITO</li>
                                <li>EL POLIGONO</li>
                                <li>URB EL CAICET</li>
                                <li>URB AV DEL EJERCITO</li>
                                <li>SIMON BOLIVAR</li>
                                <li>CARINAGUITA</li>
                                <li>JOSE MARIA VARGAS</li>
                                <li>LA PAILA</li>
                                <li>LOS CAJONES DE SAN ENRIQUE</li>
                                <li>SAN ENRIQUE CENTRO</li>
                                <li>EL BOSQUE SAN ENRIQUE</li>
                                <li>EL BAJO DE SAN ENRIQUE</li>
                            </ul>
                        </div>

                        <!-- Lista de Ubicaciones Actuales -->
                        <h6 class="fw-bold mb-3">Mis Ubicaciones</h6>
                        <div id="lista-ubicaciones-user" class="list-group list-group-flush mb-4 rounded-4 border">
                            <div class="p-3 text-center text-muted">Cargando ubicaciones...</div>
                        </div>

                        <div class="d-grid mb-4">
                            <button class="btn btn-outline-success rounded-pill" onclick="mostrarFormNuevaUbicacion()">
                                <i class="bi bi-plus-lg me-2"></i>Agregar Nueva Ubicación
                            </button>
                        </div>
                    </div>

                    <!-- Vista de Formulario (Edición/Nueva) -->
                    <div id="view-form-ubicacion" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0" id="loc-form-title">Datos de Ubicación</h6>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="regresarALista()">
                                <i class="bi bi-arrow-left me-1"></i>Regresar
                            </button>
                        </div>

                        <div id="notif-delivery-gratis" class="alert alert-success border-0 rounded-4 mb-3 d-none">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    ¡Excelente! Tienes <strong>Delivery Gratis</strong> en esta ubicación.
                                </div>
                                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" onclick="guardarGestionUbicacion()">
                                    Continuar <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <form id="loc-form">
                            <input type="hidden" id="loc-id">
                            <input type="hidden" id="loc-lat">
                            <input type="hidden" id="loc-lng">
                            <input type="hidden" id="loc-comunidad">
                            <input type="hidden" id="loc-delivery-gratis">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">NOMBRE RECEPTOR *</label>
                                    <input type="text" class="form-control" id="loc-nombre" required placeholder="Quién recibe">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">TELÉFONO *</label>
                                    <input type="number" class="form-control" id="loc-telefono" required placeholder="0414-XXXXXXX">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">COMUNIDAD / BARRIO *</label>
                                <div class="search-container">
                                    <input type="text" readonly placeholder="Indica tu ubicacion en el mapa" class="form-control" id="loc-comunidad-input" required placeholder="Selecciona una zona habitable" autocomplete="off">
                                    <div id="loc-community-results" class="community-results"></div>
                                </div>
                            </div>

                            <section id="solo_nuevo_registro">
                                <div class="mb-3" id>
                                    <label class="form-label small fw-bold">DIRECCIÓN EXACTA *</label>
                                    <input type="text" class="form-control" id="loc-direccion" required placeholder="Calle, Carrera, Edificio/Casa">
                                </div>
                                <div class="mb-3" id>
                                    <label class="form-label small fw-bold">REFERENCIA</label>
                                    <input type="text" class="form-control" id="loc-referencia" placeholder="Gris con porton blanco, etc.">
                                </div>

                            </section>


                            <label class="form-label small fw-bold text-success"><i class="bi bi-geo-alt-fill me-1"></i> MARCA TU CASA EN EL MAPA *</label>
                            <div id="map-gestion" style="height: 300px; border-radius: 1rem; margin-bottom: 1rem; background: #eee;"></div>

                            <div class="d-grid">
                                <button type="button" class="btn btn-success px-4 py-2 fw-bold" onclick="guardarGestionUbicacion()">
                                    Guardar Ubicación
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        #map-gestion {
            width: 100%;
            border: 1px solid #ddd;
            position: relative;
        }

        .list-group-item-action {
            cursor: pointer;
            transition: background 0.2s;
        }

        .list-group-item-action:hover {
            background: #f8f9fa;
        }
    </style>

    <!-- ===== PREMUM MERCHANT CTA & MINIMAL FOOTER ===== -->
    <style>
        /* ===== Merchant CTA — minimal ===== */
        .merchant-cta {
            --mc-ink: #14241c;
            --mc-muted: #6b7a73;
            --mc-green: #1a7f4b;
            --mc-green-dark: #13663a;
            --mc-line: #e9ecef;
            width: 100%;
            margin: 64px auto 48px;
            padding: 0 24px;
        }

        .mc-card {
            background: #fff;
            border: 1px solid var(--mc-line);
            border-radius: 18px;
            overflow: hidden;
        }

        .mc-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 40px;
            padding: 44px 48px;
            flex-wrap: wrap;
        }

        .mc-title {
            font-weight: 600;
            font-size: clamp(1.5rem, 2.4vw, 2.1rem);
            line-height: 1.15;
            letter-spacing: -0.01em;
            color: var(--mc-ink);
            margin: 0 0 10px;
        }

        .mc-sub {
            color: var(--mc-muted);
            font-size: 0.98rem;
            line-height: 1.6;
            max-width: 44ch;
            margin: 0;
        }

        .mc-btn {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff !important;
            text-decoration: none !important;
            background: var(--mc-green);
            padding: 14px 26px;
            border-radius: 100px;
            transition: background 0.2s ease, transform 0.2s ease;
            white-space: nowrap;
        }

        .mc-btn:hover {
            background: var(--mc-green-dark);
            transform: translateY(-1px);
            color: #fff !important;
        }

        .mc-btn i {
            transition: transform 0.2s ease;
        }

        .mc-btn:hover i {
            transform: translateX(3px);
        }

        /* Cinta de beneficios */
        .mc-ribbon {
            border-top: 1px solid var(--mc-line);
            background: #fbfcfc;
            overflow: hidden;
            -webkit-mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent);
            mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent);
        }

        .mc-ribbon-track {
            display: flex;
            align-items: center;
            gap: 40px;
            width: max-content;
            padding: 14px 24px;
            animation: mc-marquee 40s linear infinite;
        }

        .mc-ribbon-track span {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: var(--mc-muted);
            font-size: 0.82rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .mc-ribbon-track i {
            color: var(--mc-green);
            font-size: 0.95rem;
        }

        @keyframes mc-marquee {
            to {
                transform: translateX(-50%);
            }
        }

        @media (max-width: 760px) {
            .mc-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 24px;
                padding: 34px 26px;
            }

            .mc-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .mc-ribbon-track {
                animation: none;
            }
        }

        .minimal-footer {
            border-top: 1px solid #f0f0f0;
            padding: 32px 0;
            margin-top: 20px;
            background: #fff;
        }

        .minimal-footer-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .minimal-footer-text {
            font-size: 0.85rem;
            color: #777;
            font-weight: 500;
        }

        .minimal-footer-text strong {
            color: #333;
            font-weight: 700;
        }

        .minimal-footer-love {
            font-size: 0.85rem;
            color: #777;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .minimal-footer-love i {
            color: #1a7f4b;
            font-size: 0.9rem;
        }
    </style>

    <section class="merchant-cta" aria-labelledby="merchant-cta-title">
        <div class="mc-card">
            <div class="mc-top">
                <div class="mc-copy">
                    <h2 class="mc-title" id="merchant-cta-title">¿Tienes una tienda?</h2>
                    <p class="mc-sub">Vende en línea, recibe pedidos y cobra en dólares y bolívares. Sin comisiones por venta.</p>
                </div>
                <a class="mc-btn" href="https://iseller-tiendas.com/inventario/registro.php" target="_blank" rel="noopener">
                    Crear mi tienda <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="mc-ribbon" aria-hidden="true">
                <div class="mc-ribbon-track">
                    <span><i class="bi bi-shop"></i> Vende en línea</span>
                    <span><i class="bi bi-truck"></i> Gestion de Delivery</span>
                    <span><i class="bi bi-currency-dollar"></i> Cobra en $, Bs y Cop</span>
                    <span><i class="bi bi-box-seam"></i> Control de inventario</span>
                    <span><i class="bi bi-bar-chart-line"></i> Reportes de ventas</span>
                    <span><i class="bi bi-globe2"></i> Tu propia dirección web</span>
                    <span><i class="bi bi-shop"></i> Multisucursal</span>
                </div>
            </div>
        </div>
    </section>

    <footer class="minimal-footer">
        <div class="minimal-footer-inner">
            <div class="minimal-footer-text">
                &copy; <?php echo date('Y'); ?> <strong>iSeller Store</strong>. Todos los derechos reservados.
            </div>
            <div class="minimal-footer-love">
                Hecho con <i class="bi bi-heart-fill"></i> en Venezuela
            </div>
        </div>
    </footer>

    <!-- Chat Component (Simplified) -->
    <?php include 'iseller_store/assets/components/chat-button.php'; ?>
    <script src="assets/js/chat-simple.js"></script>

    <!-- Modal: Todas las tiendas (outside stacking contexts) -->
    <?php if (!$es_tienda_individual && count($tiendas_destacadas) > 0): ?>
        <div class="modal fade" id="modalTiendas" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title fw-bold"><i class="bi bi-shop text-success me-2"></i>Todas las tiendas</h6>
                        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body px-4 pb-4">
                        <input type="text" id="modal-tiendas-search" class="form-control mb-3" placeholder="Buscar tienda..." style="border-radius:10px; border-color:#e9ecef;">
                        <div id="modal-tiendas-list" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:12px; max-height:60vh; overflow-y:auto;"></div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            const ALL_TIENDAS = <?= json_encode($tiendas_destacadas, JSON_UNESCAPED_UNICODE) ?>;
            let _modalTiendas = null;

            function openTiendasModal() {
                document.getElementById('modal-tiendas-search').value = '';
                renderTiendasModal(ALL_TIENDAS);
                if (!_modalTiendas) {
                    _modalTiendas = new bootstrap.Modal(document.getElementById('modalTiendas'));
                }
                _modalTiendas.show();
                setTimeout(() => document.getElementById('modal-tiendas-search').focus(), 200);
            }

            function renderTiendasModal(tiendas) {
                const list = document.getElementById('modal-tiendas-list');
                if (!tiendas.length) {
                    list.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#94a3b8;padding:2rem;">No hay tiendas disponibles</p>';
                    return;
                }
                list.innerHTML = tiendas.map(t => {
                    const name = t.nombre_visible || t.nombre;
                    const logo = t.logo_url ?
                        `<img src="${t.logo_url}" alt="${name}" onerror="imgFallback(this)" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">` :
                        `<i class="bi bi-shop" style="font-size:1.2rem;color:#adb5bd;"></i>`;
                    return `<a href="/${t.slug}" class="tienda-chip" style="width:auto;flex-direction:row;align-items:center;text-align:left;padding:12px 14px;gap:10px;">
                    <div class="tienda-chip-logo" style="width:44px;height:44px;border-radius:50%;margin-bottom:0;border:2px solid #e9ecef;">${logo}</div>
                    <div style="min-width:0;flex:1;">
                        <span class="tienda-chip-name" style="justify-content:flex-start;margin-bottom:2px;">
                            ${name}
                            <i class="bi bi-patch-check-fill verified-dot"></i>
                        </span>
                        <span class="tienda-chip-cat" style="margin-bottom:2px;">${t.categoria||''}</span>
                        <span class="tienda-chip-count">${t.total_productos||0} productos</span>
                    </div>
                </a>`;
                }).join('');
            }

            document.getElementById('modal-tiendas-search')?.addEventListener('input', function() {
                const q = this.value.toLowerCase();
                renderTiendasModal(ALL_TIENDAS.filter(t => {
                    const name = (t.nombre_visible || t.nombre).toLowerCase();
                    const cat = (t.categoria || '').toLowerCase();
                    return name.includes(q) || cat.includes(q);
                }));
            });
        </script>
    <?php endif; ?>
    <script>
        let _heroLastProds = {};
        let _heroSearchBox = null;

        function _getHeroBox() {
            if (!_heroSearchBox) {
                _heroSearchBox = document.createElement('div');
                _heroSearchBox.className = 'mkt-hero-results';
                _heroSearchBox.id = 'hero-search-results';
                document.body.appendChild(_heroSearchBox);
            }
            return _heroSearchBox;
        }

        function heroSearch(q) {
            const box = _getHeroBox();
            q = q.trim();
            if (q.length < 2) {
                box.style.display = 'none';
                box.innerHTML = '';
                return;
            }

            // Posicionar debajo del input
            const inp = document.getElementById('hero-search-input');
            if (inp) {
                const r = inp.getBoundingClientRect();
                box.style.left = r.left + 'px';
                box.style.top = (r.bottom + 6) + 'px';
                box.style.width = r.width + 'px';
            }

            let html = '';

            // Productos via Fuse
            if (typeof fuse !== 'undefined') {
                const prods = buscarConFuse(q).slice(0, 5);
                _heroLastProds = {};
                if (prods.length) {
                    html += '<div class="mkt-hero-results-section">Productos</div>';
                    prods.forEach(r => {
                        const it = r.item;
                        _heroLastProds[it.id] = it;
                        const img = it.img ? '<img class="item-img" src="' + it.img + '" alt="" onerror="imgFallback(this)">' : '<div class="item-img" style="display:flex;align-items:center;justify-content:center;"><i class="bi bi-box-seam" style="color:#94a3b8;"></i></div>';
                        const tiendaLabel = it.tienda_nombre ? '<div class="item-meta" style="color:#6b7a8d;font-size:0.72rem;"><i class="bi bi-shop" style="font-size:0.65rem;"></i> ' + it.tienda_nombre + '</div>' : '';
                        html += '<div class="mkt-hero-results-item" role="button" onclick="heroSearchPick(' + it.id + ')">' + img + '<div class="item-info"><div class="item-name">' + it.nombre + '</div><div class="item-meta">' + (it.categorias || '') + '</div>' + tiendaLabel + '</div><div class="item-price">$' + parseFloat(it.precio_dolar_visible || 0).toFixed(2) + '</div></div>';
                    });
                }
            }

            // Tiendas
            if (typeof ALL_TIENDAS !== 'undefined') {
                const ql = q.toLowerCase();
                const tias = ALL_TIENDAS.filter(t => {
                    const n = (t.nombre_visible || t.nombre || '').toLowerCase();
                    const c = (t.categoria || '').toLowerCase();
                    return n.includes(ql) || c.includes(ql);
                }).slice(0, 4);
                if (tias.length) {
                    html += '<div class="mkt-hero-results-section">Tiendas</div>';
                    tias.forEach(t => {
                        const name = t.nombre_visible || t.nombre;
                        const logo = t.logo_url ? '<img class="item-img" src="' + t.logo_url + '" alt="" onerror="imgFallback(this)" style="border-radius:50%;">' : '<div class="item-img" style="display:flex;align-items:center;justify-content:center;border-radius:50%;"><i class="bi bi-shop" style="color:#94a3b8;"></i></div>';
                        html += '<a href="/' + t.slug + '" class="mkt-hero-results-item">' + logo + '<div class="item-info"><div class="item-name">' + name + ' <i class="bi bi-patch-check-fill" style="color:#1a7f4b;font-size:0.7rem;"></i></div><div class="item-meta">' + (t.categoria || '') + ' &middot; ' + (t.total_productos || 0) + ' productos</div></div></a>';
                    });
                }
            }

            if (html) {
                box.innerHTML = html;
                box.style.display = 'block';
            } else {
                box.innerHTML = '<div style="padding:16px;text-align:center;color:#94a3b8;font-size:0.85rem;">Sin resultados para "' + q + '"</div>';
                box.style.display = 'block';
            }
        }

        function heroSearchPick(id) {
            _getHeroBox().style.display = 'none';
            document.getElementById('hero-search-input').blur();
            if (!productos_por_id[id] && _heroLastProds[id]) {
                productos_por_id[id] = _heroLastProds[id];
            }
            openProductModal(id);
        }
        document.addEventListener('click', function(e) {
            if (_heroSearchBox && !_heroSearchBox.contains(e.target) && e.target.id !== 'hero-search-input') {
                _heroSearchBox.style.display = 'none';
            }
        });
        document.getElementById('hero-search-input')?.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) heroSearch(this.value);
        });

        /* ========== Chat ========== */
        (function() {
            const fab = document.getElementById('chat-fab');
            const modal = document.getElementById('modalChat');
            if (!fab || !modal) return;
            const bsModal = new bootstrap.Modal(modal);

            // Load categories
            fetch('api/chat/obtener_categorias.php')
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.data) {
                        const sel = document.getElementById('chat-categoria');
                        if (!sel) return;
                        d.data.forEach(c => {
                            const o = document.createElement('option');
                            o.value = c.id;
                            o.textContent = c.nombre;
                            sel.appendChild(o);
                        });
                    }
                }).catch(() => {});

            fab.addEventListener('click', () => bsModal.show());

            document.getElementById('chat-enviar')?.addEventListener('click', async function() {
                const btn = this;
                const errEl = document.getElementById('chat-error');
                errEl.classList.add('d-none');

                const payload = {
                    categoria_id: parseInt(document.getElementById('chat-categoria').value),
                    asunto: document.getElementById('chat-asunto').value.trim(),
                    mensaje: document.getElementById('chat-mensaje').value.trim(),
                    bss_id: <?= (int)($bss_id ?? 0) ?>
                };

                if (!payload.categoria_id || !payload.asunto || payload.asunto.length < 3 || !payload.mensaje || payload.mensaje.length < 5) {
                    errEl.textContent = 'Por favor completa todos los campos.';
                    errEl.classList.remove('d-none');
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Enviando...';
                try {
                    const res = await fetch('api/chat/start.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.href = 'chat.php?conv=' + encodeURIComponent(data.conversacion_id);
                        return;
                    } else {
                        errEl.textContent = data.message || 'Error al enviar';
                        errEl.classList.remove('d-none');
                    }
                } catch (e) {
                    errEl.textContent = 'Error de conexión';
                    errEl.classList.remove('d-none');
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill me-2"></i> Enviar mensaje';
            });
        })();
    </script>
    <script>
        (function() {
            var nav = document.getElementById('navbar');
            if (!nav) return;

            function syncNavbarHeight() {
                document.documentElement.style.setProperty('--navbar-h', nav.offsetHeight + 'px');
            }

            syncNavbarHeight();
            window.addEventListener('resize', syncNavbarHeight);
            window.addEventListener('load', syncNavbarHeight);
        })();
    </script>
    <script>
        (function() {
            var heroLogo = document.querySelector('.store-logo-img, .store-logo-placeholder');
            var subnav = document.querySelector('.store-subnav');
            if (!heroLogo || !subnav) return;

            var ticking = false;

            function update() {
                var logoRect = heroLogo.getBoundingClientRect();
                var barRect = subnav.getBoundingClientRect();
                subnav.classList.toggle('show-logo', logoRect.bottom <= barRect.bottom);
            }

            function onScroll() {
                if (ticking) return;
                ticking = true;
                window.requestAnimationFrame(function() {
                    update();
                    ticking = false;
                });
            }

            window.addEventListener('scroll', onScroll, {
                passive: true
            });
            window.addEventListener('resize', update);
            update();
        })();
    </script>
    <script>
        (function() {
            var btn = document.getElementById('btn-favorito');
            if (!btn) return;

            btn.addEventListener('click', function() {
                if (document.body.dataset.userLoggedIn !== 'true') {
                    window.location.href = 'login.php';
                    return;
                }

                var negocioId = btn.dataset.negocio;
                btn.disabled = true;

                var fd = new FormData();
                fd.append('negocio_id', negocioId);

                fetch('api/favorito.php', {
                        method: 'POST',
                        body: fd,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        btn.disabled = false;
                        if (!data.ok) {
                            if (data.login) {
                                window.location.href = 'login.php';
                            }
                            return;
                        }

                        var fav = !!data.favorito;
                        btn.classList.toggle('is-fav', fav);
                        btn.setAttribute('aria-pressed', fav ? 'true' : 'false');
                        btn.title = fav ? 'Quitar de favoritos' : 'Agregar a favoritos';

                        var icon = btn.querySelector('i');
                        if (icon) icon.className = 'bi ' + (fav ? 'bi-heart-fill' : 'bi-heart');

                        var text = btn.querySelector('span');
                        if (text) text.textContent = fav ? 'En favoritos' : 'Agregar a favoritos';
                    })
                    .catch(function() {
                        btn.disabled = false;
                    });
            });
        })();
    </script>
</body>

</html>