<?php
/**
 * GET /iseller_store/api/v1/store.php?slug=yola
 * Branding, hero, categorías, entrega y datos de pago de una tienda virtual.
 *
 * Sin `slug` devuelve el marketplace (como la portada web): branding iSeller,
 * hero global, categorías de todas las tiendas y sin datos de entrega/pago.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';

api_method('GET');
api_rate_limit('store_ip', 120, 60);

global $conexion;

$slug = trim((string)($_GET['slug'] ?? ''));

/** Categorías con productos publicados. Si $bssId > 0, solo esa tienda. */
function api_categorias(?int $bssId): array
{
    global $conexion;
    $filtro = $bssId !== null ? 'AND np.negocio_id = ?' : '';
    $sql = "SELECT DISTINCT c.id, c.nombre
            FROM categorias_producto c
            INNER JOIN productos p ON p.categoria = c.id AND p.activo = 0 AND p.origen != 'c'
            INNER JOIN negocio_productos np ON np.producto_id = p.id AND np.estado_publicacion = 1
            INNER JOIN negocio n ON np.negocio_id = n.id
                 AND n.tiene_tienda_virtual = 1 AND n.estado = 'activo'
            INNER JOIN stock s ON s.id_producto = p.id AND s.bss_id = n.id AND s.stock > 0
                 AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
            WHERE 1 = 1 $filtro
            ORDER BY c.nombre ASC";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($bssId !== null) {
        $stmt->bind_param('i', $bssId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($fila = $res->fetch_assoc()) {
        $items[] = ['id' => (int)$fila['id'], 'name' => $fila['nombre']];
    }
    $stmt->close();
    return $items;
}

$themeBase = [
    'primary'      => '#36A97C',
    'primary_dark' => '#2A8562',
    'accent'       => '#2DD4A0',
    'background'   => '#F5F7F6',
    'surface'      => '#FFFFFF',
    'text'         => '#1F2933',
    'muted'        => '#6B7280',
    'border'       => '#E5E7EB',
    'danger'       => '#EF4444',
    'radius_dp'    => 16,
];

// ── Marketplace (sin tienda) ────────────────────────────────────────────────
if ($slug === '') {
    $imagenHero = 'assets/img/bg-hero-a.webp';
    if (api_local_path($imagenHero) === null) {
        $imagenHero = '';
    }
    $ratio = 2.2;
    $localHero = api_local_path($imagenHero);
    if ($localHero !== null) {
        $info = @getimagesize($localHero);
        if (is_array($info) && (int)$info[1] > 0) {
            $ratio = max(1.35, min(2.2, (float)$info[0] / (float)$info[1]));
        }
    }

    api_ok([
        'marketplace' => true,
        'store' => null,
        'hero' => [
            'image_url'    => api_abs($imagenHero),
            'logo_url'     => api_abs('assets/img/logo-tienda.png'),
            'title'        => 'Descubre productos de tiendas de tu localidad',
            'subtitle'     => 'Encuentra lo que necesitas desde la comodidad de tu hogar y con los mejores precios.',
            'badge'        => 'Tu mercado en un solo lugar',
            'aspect_ratio' => round($ratio, 3),
            'focus'        => 'center',
            'overlay'      => 'left',
        ],
        'theme' => $themeBase,
        'delivery' => null,
        'payment' => null,
        'categories' => api_categorias(null),
        'rates' => ['bcv' => 0, 'usd_cop' => 0, 'usd_bs' => 0],
        'server_time' => date('c'),
    ]);
    exit;
}

// ── Tienda individual ───────────────────────────────────────────────────────
$tienda = api_store_by_slug($slug);
$bssId = (int)$tienda['id'];

$portada = trim((string)($tienda['portada_url'] ?? ''));
$logo = trim((string)($tienda['logo_url'] ?? ''));

// La app recorta el hero: se acota la proporción real para que en móvil no quede
// una franja de 4:1 ni una imagen gigante en tablets.
$ratio = 1.78;
$local = api_local_path($portada);
if ($local !== null) {
    $info = @getimagesize($local);
    if (is_array($info) && (int)$info[1] > 0) {
        $ratio = (float)$info[0] / (float)$info[1];
    }
}
$ratio = max(1.35, min(2.2, $ratio));

$configDelivery = json_decode((string)($tienda['config_delivery'] ?? '{}'), true) ?: [];
$tipoDelivery = (string)($configDelivery['tipo'] ?? '');
$costoDelivery = (float)($configDelivery['costo'] ?? 0);
$minimoGratis = (float)($configDelivery['minimo'] ?? 0);

$delivery = [
    'mode'            => $tipoDelivery === 'gratis' ? 'free' : ($tipoDelivery === 'pago' ? 'paid' : 'none'),
    'cost_usd'        => $costoDelivery,
    'free_from_usd'   => $tipoDelivery === 'gratis' ? $minimoGratis : 0,
    'zone'            => (string)($configDelivery['zonas'] ?? 'municipio'),
    'requires_address'=> true,
];

$pago = json_decode((string)($tienda['datos_bancarios'] ?? '{}'), true) ?: [];
$calc = api_calc($bssId);

api_ok([
    'marketplace' => false,
    'store' => [
        'id'               => $bssId,
        'slug'             => $tienda['slug'],
        'name'             => $tienda['nombre_visible'] !== '' ? $tienda['nombre_visible'] : $tienda['nombre'],
        'tagline'          => (string)($tienda['eslogan'] ?? ''),
        'category'         => (string)($tienda['categoria'] ?? ''),
        'logo_url'         => api_abs($logo),
        'cover_url'        => api_abs($portada),
        'verified'         => true,
        'reputation_pct'   => (float)($tienda['reputacion_pct'] ?? 0),
        'reputation_total' => (int)($tienda['reputacion_total'] ?? 0),
        'whatsapp'         => (string)($tienda['whatsapp'] ?? ''),
        'location'         => [
            'lat'      => $tienda['lat'] !== null ? (float)$tienda['lat'] : null,
            'lng'      => $tienda['lng'] !== null ? (float)$tienda['lng'] : null,
            'state'    => (string)($tienda['geo_estado'] ?? ''),
            'municipality' => (string)($tienda['geo_municipio'] ?? ''),
        ],
        'hours'            => json_decode((string)($tienda['horarios'] ?? '{}'), true) ?: new stdClass(),
    ],
    'hero' => [
        'image_url'    => api_abs($portada),
        'logo_url'     => api_abs($logo),
        'title'        => $tienda['nombre_visible'] !== '' ? $tienda['nombre_visible'] : $tienda['nombre'],
        'subtitle'     => (string)($tienda['eslogan'] ?? ''),
        'badge'        => 'Tienda verificada',
        'aspect_ratio' => round($ratio, 3),
        'focus'        => 'center',
        'overlay'      => 'left',
    ],
    'theme' => $themeBase,
    'delivery'       => $delivery,
    'payment'        => $pago,
    'categories'     => api_categorias($bssId),
    'rates'          => [
        'bcv'       => $calc->getTasa('bcv'),
        'usd_cop'   => $calc->getTasa('pesoDolar'),
        'usd_bs'    => $calc->getTasa('bcv'),
    ],
    'server_time' => date('c'),
]);
