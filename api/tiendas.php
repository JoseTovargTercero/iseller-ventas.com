<?php
/**
 * API — Tiendas registradas para el cintillo del inicio (Android)
 *      GET /api/tiendas.php
 *
 * Devuelve nombre y logo en miniatura comprimida (WebP 96x96, cacheada en
 * iseller_store/assets/img/tiendas/thumbs/). Autenticación: X-Api-Key.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

set_exception_handler(function () {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../configurar/configuracion.php';
require_once __DIR__ . '/../iseller_store/core/tienda_logo.php';

function salirJson(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    salirJson(405, ['ok' => false, 'error' => 'Método no permitido']);
}

// Rate limit por IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlFile = sys_get_temp_dir() . '/iseller_tiendas_' . md5($ip) . '.json';
$rlMax = 60;
$rlWindow = 60;
$ahora = time();
$rl = ['inicio' => $ahora, 'n' => 0];
if (is_file($rlFile)) {
    $prev = json_decode((string)@file_get_contents($rlFile), true);
    if (is_array($prev) && isset($prev['inicio'], $prev['n']) && ($ahora - (int)$prev['inicio']) < $rlWindow) {
        $rl = ['inicio' => (int)$prev['inicio'], 'n' => (int)$prev['n']];
    }
}
$rl['n']++;
@file_put_contents($rlFile, json_encode($rl));
if ($rl['n'] > $rlMax) {
    salirJson(429, ['ok' => false, 'error' => 'Demasiadas peticiones']);
}

// API key (fail-closed)
$esperada = trim($_ENV['POS_API_KEY'] ?? '');
$recibida = trim($_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? ''));
if ($esperada === '') {
    if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
        salirJson(503, ['ok' => false, 'error' => 'API no configurada']);
    }
} elseif ($recibida === '' || !hash_equals($esperada, $recibida)) {
    salirJson(401, ['ok' => false, 'error' => 'No autorizado']);
}

$res = $conexion->query(
    "SELECT id, nombre, nombre_visible, logo_url, slug, categoria
       FROM negocio
      WHERE tiene_tienda_virtual = 1
        AND estado = 'activo'
        AND slug IS NOT NULL AND slug <> ''
      ORDER BY COALESCE(NULLIF(nombre_visible, ''), nombre) ASC
      LIMIT 50"
);
if (!$res) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}

$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$host = $_SERVER['HTTP_HOST'] ?? '';
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$appPath = rtrim(str_replace('\\', '/', dirname(dirname($uriPath))), '/');
$baseTienda = ($host !== '' ? (($esHttps ? 'https' : 'http') . '://' . $host . $appPath) : '') . '/iseller_store/';

$tiendas = [];
while ($row = $res->fetch_assoc()) {
    $nombre = trim((string)($row['nombre_visible'] ?: $row['nombre']));
    $thumb = tienda_logo_thumb((string)($row['logo_url'] ?? ''), 96);
    $logo = '';
    // Solo se devuelve la miniatura comprimida; si no se pudo generar, se usa la inicial.
    if ($thumb !== '' && strpos($thumb, 'assets/img/tiendas/thumbs/') !== false) {
        $logo = $baseTienda . ltrim($thumb, '/');
    }
    $tiendas[] = [
        'id'        => (int)$row['id'],
        'nombre'    => $nombre,
        'slug'      => (string)$row['slug'],
        'categoria' => (string)($row['categoria'] ?? ''),
        'logo'      => $logo,
        'inicial'   => mb_strtoupper(mb_substr($nombre, 0, 1)),
    ];
}

salirJson(200, ['ok' => true, 'tiendas' => $tiendas, 'total' => count($tiendas)]);
