<?php
/**
 * Sirve imágenes de la tienda comprimidas (WebP) y redimensionadas.
 *
 *   /iseller_store/api/v1/image.php?src=assets/img/tiendas/3/logo.webp&w=160&q=78
 *
 * Solo acepta rutas dentro de iseller_store/assets/img. El tipo real se detecta
 * con getimagesize (hay archivos guardados con extensión equivocada). El
 * resultado se cachea en el directorio temporal con una clave que incluye la
 * fecha del archivo, así que una imagen reemplazada se regenera sola.
 */
declare(strict_types=1);

$src = trim((string)($_GET['src'] ?? ''));
$ancho = max(64, min(1600, (int)($_GET['w'] ?? 600)));
$calidad = max(40, min(85, (int)($_GET['q'] ?? 72)));

function imagen_404(): void
{
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

function servir_original(string $archivo, string $mime): void
{
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($archivo));
    readfile($archivo);
    exit;
}

if ($src === '' || strpos($src, '..') !== false
    || !preg_match('#^assets/img/[A-Za-z0-9._/-]+$#', $src)) {
    imagen_404();
}

$baseAssets = realpath(__DIR__ . '/../../assets/img');
$archivo = realpath(__DIR__ . '/../../' . $src);
if ($baseAssets === false || $archivo === false || !is_file($archivo)
    || strpos($archivo, $baseAssets . DIRECTORY_SEPARATOR) !== 0) {
    imagen_404();
}

$info = @getimagesize($archivo);
$tipos = [
    IMAGETYPE_WEBP => ['image/webp', 'imagecreatefromwebp'],
    IMAGETYPE_JPEG => ['image/jpeg', 'imagecreatefromjpeg'],
    IMAGETYPE_PNG  => ['image/png', 'imagecreatefrompng'],
];
$tipo = is_array($info) ? (int)($info[2] ?? 0) : 0;
if (!isset($tipos[$tipo]) || (int)$info[0] <= 0 || (int)$info[1] <= 0) {
    imagen_404();
}
[$mime, $cargar] = $tipos[$tipo];
$origenAncho = (int)$info[0];
$origenAlto = (int)$info[1];

$mtime = @filemtime($archivo) ?: 0;
$cacheDir = rtrim(sys_get_temp_dir(), '/\\') . '/iseller_app_img';
if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
}

$clave = hash('sha256', $archivo . '|' . $mtime . '|' . $ancho . '|' . $calidad);
$cache = $cacheDir . '/' . $clave . '.webp';
$etag = '"' . substr($clave, 0, 32) . '"';

header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=2592000, immutable');
header('ETag: ' . $etag);
if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

if (is_file($cache)) {
    header('Content-Type: image/webp');
    header('Content-Length: ' . (string)filesize($cache));
    readfile($cache);
    exit;
}

// Ya es WebP y no hay que ampliarlo: se sirve tal cual.
if ($tipo === IMAGETYPE_WEBP && $origenAncho <= $ancho) {
    servir_original($archivo, $mime);
}

// Imágenes absurdamente grandes: mejor servir el original que agotar memoria.
if ($origenAncho * $origenAlto > 40000000) {
    servir_original($archivo, $mime);
}

if (!function_exists('imagewebp') || !function_exists($cargar)) {
    servir_original($archivo, $mime);
}

$destinoAncho = min($ancho, $origenAncho);
$destinoAlto = max(1, (int)round($origenAlto * ($destinoAncho / $origenAncho)));

$origen = @$cargar($archivo);
if (!$origen) {
    servir_original($archivo, $mime);
}

$destino = imagecreatetruecolor($destinoAncho, $destinoAlto);
if (!$destino) {
    imagedestroy($origen);
    servir_original($archivo, $mime);
}
imagealphablending($destino, false);
imagesavealpha($destino, true);
imagecopyresampled($destino, $origen, 0, 0, 0, 0, $destinoAncho, $destinoAlto, $origenAncho, $origenAlto);
$generado = @imagewebp($destino, $cache, $calidad);
imagedestroy($destino);
imagedestroy($origen);

if (!$generado || !is_file($cache)) {
    servir_original($archivo, $mime);
}

header('Content-Type: image/webp');
header('Content-Length: ' . (string)filesize($cache));
readfile($cache);
