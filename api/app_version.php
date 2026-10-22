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

// ── Descarga del APK con soporte de reanudación (Range) ───────────────────────
if (($_GET['descargar'] ?? '') === '1') {
    $tamano = (int)filesize($apkPath);
    @set_time_limit(0);
    ignore_user_abort(true);

    $inicio = 0;
    $fin = $tamano - 1;
    $parcial = false;

    $rango = trim($_SERVER['HTTP_RANGE'] ?? '');
    if ($rango !== '' && preg_match('/^bytes=(\d*)-(\d*)$/', $rango, $m)) {
        if ($m[1] === '' && $m[2] !== '') {
            $inicio = max(0, $tamano - (int)$m[2]);   // bytes=-N (últimos N)
        } else {
            $inicio = (int)$m[1];
            if ($m[2] !== '') {
                $fin = min((int)$m[2], $tamano - 1);
            }
        }
        if ($inicio > $fin || $inicio >= $tamano) {
            header('Content-Range: bytes */' . $tamano);
            http_response_code(416);
            exit;
        }
        $parcial = true;
    }

    $longitud = $fin - $inicio + 1;

    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="iseller_pos.apk"');
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . $longitud);
    header('Cache-Control: no-store');
    if ($parcial) {
        http_response_code(206);
        header('Content-Range: bytes ' . $inicio . '-' . $fin . '/' . $tamano);
    }

    if (ob_get_level()) {
        ob_end_clean();
    }

    $fp = @fopen($apkPath, 'rb');
    if ($fp === false) {
        exit;
    }
    fseek($fp, $inicio);
    $restante = $longitud;
    while ($restante > 0 && !feof($fp) && !connection_aborted()) {
        $bloque = fread($fp, min(65536, $restante));
        if ($bloque === false || $bloque === '') {
            break;
        }
        echo $bloque;
        flush();
        $restante -= strlen($bloque);
    }
    fclose($fp);
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
