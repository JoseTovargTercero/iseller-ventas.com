<?php
/**
 * API — Versión del POS nativo  →  GET /api/pos_version.php
 *
 * Lee api/pos_version.json y devuelve la versión publicada, su SHA-256 y la URL
 * absoluta del ZIP desde donde se publica el hosting actual.
 */

require_once __DIR__ . '/_pos_comun.php';

exigirMetodo('GET');
exigirApiKey();
limitar('version', 30, 60);

$archivo = __DIR__ . '/pos_version.json';
// Se tolera el BOM: editores como Notepad lo agregan y json_decode lo rechaza.
$texto = is_file($archivo) ? (string)file_get_contents($archivo) : '';
$data = json_decode(ltrim($texto, "\xEF\xBB\xBF \t\n\r"), true);

if (!is_array($data) || empty($data['version'])) {
    salir(404, ['ok' => false, 'error' => 'Versión no configurada']);
}

$zip = basename((string)($data['archivo'] ?? 'pos_update.zip'));
$esHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$base = ($esHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
// Ruta pública real (funciona igual en la raíz o bajo /inventario/ con rewrite)
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$directorio = rtrim(str_replace('\\', '/', dirname($uriPath)), '/');

salir(200, [
    'ok' => true,
    'version' => (string)$data['version'],
    'sha256' => (string)($data['sha256'] ?? ''),
    'notas' => (string)($data['notas'] ?? ''),
    'url' => $base . $directorio . '/' . $zip,
    'servidor_en' => servidorEn(),
]);
