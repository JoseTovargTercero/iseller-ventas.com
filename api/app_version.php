<?php
/**
 * API — Actualización de la app Android (iSeller POS)
 *
 * GET /api/app_version.php              -> metadatos JSON de la última versión
 * GET /api/app_version.php?descargar=1  -> binario APK (application/vnd.android.package-archive)
 *
 * Autenticación: cabecera `X-Api-Key` (o `?key=`), leída de POS_API_KEY en .env.
 */

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

set_exception_handler(function () {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../configurar/configuracion.php';

function salirJson(int $code, array $body): void
{
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    salirJson(405, ['ok' => false, 'error' => 'Método no permitido']);
}

// ── Autenticación por API key (fail-closed) ────────────────────────────────────
$apiKeyEsperada = trim($_ENV['POS_API_KEY'] ?? '');
$apiKeyRecibida = trim($_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? ''));

if ($apiKeyEsperada === '') {
    if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
        salirJson(503, ['ok' => false, 'error' => 'API no configurada']);
    }
} elseif ($apiKeyRecibida === '' || !hash_equals($apiKeyEsperada, $apiKeyRecibida)) {
    salirJson(401, ['ok' => false, 'error' => 'No autorizado']);
}

// ── Leer configuración de versión ──────────────────────────────────────────────
$metaPath = __DIR__ . '/app_version.json';
if (!is_file($metaPath)) {
    salirJson(404, ['ok' => false, 'error' => 'Versión no configurada']);
}

$meta = json_decode((string)file_get_contents($metaPath), true);
if (!is_array($meta) || empty($meta['version_code'])) {
    salirJson(500, ['ok' => false, 'error' => 'Configuración de versión inválida']);
}

$relPath = ltrim(str_replace('\\', '/', (string)($meta['archivo'] ?? 'apk/iseller_pos.apk')), '/');
$rootDir = realpath(__DIR__ . '/..');
$apkPath = $rootDir ? realpath($rootDir . DIRECTORY_SEPARATOR . $relPath) : false;

// Evitar path traversal fuera del proyecto
if (!$apkPath || !$rootDir || strpos($apkPath, $rootDir) !== 0 || !is_file($apkPath)) {
    salirJson(404, ['ok' => false, 'error' => 'APK no disponible en el servidor']);
}

// ── Descarga directa del APK ───────────────────────────────────────────────────
if (($_GET['descargar'] ?? '') === '1') {
    $tamano = filesize($apkPath);
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="iseller_pos.apk"');
    if ($tamano !== false) {
        header('Content-Length: ' . $tamano);
    }
    header('Cache-Control: no-store');
    if (ob_get_level()) {
        ob_end_clean();
    }
    readfile($apkPath);
    exit;
}

// ── Metadatos de la versión ────────────────────────────────────────────────────
$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$base = ($esHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
// Ruta pública real (funciona igual en la raíz o bajo /inventario/ con rewrite)
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$appPath = rtrim(str_replace('\\', '/', dirname($uriPath)), '/');

salirJson(200, [
    'ok'           => true,
    'version_code' => (int)$meta['version_code'],
    'version_name' => (string)($meta['version_name'] ?? ''),
    'notas'        => (string)($meta['notas'] ?? ''),
    'sha256'       => (string)hash_file('sha256', $apkPath),
    'tamano'       => (int)filesize($apkPath),
    'apk_url'      => $base . $appPath . '/app_version.php?descargar=1',
]);
