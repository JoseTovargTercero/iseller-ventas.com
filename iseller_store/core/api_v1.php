<?php
/**
 * Núcleo de la API v1 para las apps móviles.
 *
 * - Tokens propios (JWT HS256 de 15 min + refresh opaco rotativo en BD).
 * - Sin cookies ni CSRF: la app no es un navegador.
 * - Todo lo que llega del cliente se valida; precios/stock se recalculan.
 *
 * Requiere en el .env de la raíz: API_JWT_SECRET (32+ caracteres).
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/_calculadrora_precios.php';

// La API siempre responde JSON: un warning de PHP no debe romper el cuerpo.
ini_set('display_errors', '0');
error_reporting(E_ALL);

const API_ACCESS_TTL  = 900;              // 15 min
const API_REFRESH_TTL = 60 * 60 * 24 * 60; // 60 días
const API_MAX_BODY    = 1048576;           // 1 MB

// ── Respuestas ───────────────────────────────────────────────────────────────

function api_json(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_ok($data = null, array $extra = []): void
{
    api_json(200, array_merge(['ok' => true, 'data' => $data], $extra));
}

function api_error(int $status, string $mensaje, ?string $codigo = null): void
{
    $body = ['ok' => false, 'error' => $mensaje];
    if ($codigo !== null) {
        $body['code'] = $codigo;
    }
    api_json($status, $body);
}

function api_method(string ...$permitidos): void
{
    $metodo = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($metodo, $permitidos, true)) {
        header('Allow: ' . implode(', ', $permitidos));
        api_error(405, 'Método no permitido');
    }
}

// ── Entrada ──────────────────────────────────────────────────────────────────

function api_body(): array
{
    $largo = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($largo > API_MAX_BODY) {
        api_error(413, 'Solicitud demasiado grande');
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    if (strlen($raw) > API_MAX_BODY) {
        api_error(413, 'Solicitud demasiado grande');
    }
    $data = json_decode($raw, true);
    if (!is_array($data) || array_is_list($data)) {
        api_error(400, 'JSON inválido');
    }
    return $data;
}

function api_str(array $in, string $clave, int $max = 255): string
{
    $v = $in[$clave] ?? '';
    if (!is_string($v)) {
        return '';
    }
    $v = trim($v);
    return mb_substr($v, 0, $max, 'UTF-8');
}

// ── Rate limit (archivo por IP) ──────────────────────────────────────────────

function api_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function api_rate_limit(string $bucket, int $max, int $ventana, string $extra = ''): void
{
    $archivo = sys_get_temp_dir() . '/iseller_api_' . md5($bucket . '|' . api_ip() . '|' . $extra) . '.json';
    $ahora = time();
    $fh = @fopen($archivo, 'c+');
    if ($fh === false) {
        return; // Sin escritura no se bloquea el servicio.
    }
    try {
        if (!flock($fh, LOCK_EX)) {
            return;
        }
        $contenido = stream_get_contents($fh);
        $prev = json_decode((string)$contenido, true);
        $estado = ['inicio' => $ahora, 'n' => 0];
        if (is_array($prev) && isset($prev['inicio'], $prev['n']) && ($ahora - (int)$prev['inicio']) < $ventana) {
            $estado = ['inicio' => (int)$prev['inicio'], 'n' => (int)$prev['n']];
        }
        $estado['n']++;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($estado));
        fflush($fh);
        flock($fh, LOCK_UN);
    } finally {
        fclose($fh);
    }
    if ($estado['n'] > $max) {
        api_error(429, 'Demasiados intentos. Espera unos minutos.');
    }
}

// ── Tokens ───────────────────────────────────────────────────────────────────

function api_secret(): string
{
    $secret = trim((string)($_ENV['API_JWT_SECRET'] ?? ''));
    if (strlen($secret) < 32) {
        error_log('API v1: falta API_JWT_SECRET (32+ caracteres) en el .env');
        api_error(503, 'Servicio no configurado');
    }
    return $secret;
}

function api_b64u_encode(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function api_b64u_decode(string $txt): ?string
{
    $pad = strlen($txt) % 4;
    if ($pad > 0) {
        $txt .= str_repeat('=', 4 - $pad);
    }
    $bin = base64_decode(strtr($txt, '-_', '+/'), true);
    return $bin === false ? null : $bin;
}

function api_jwt_issue(int $usuarioId, string $deviceId): string
{
    $ahora = time();
    $header = api_b64u_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = api_b64u_encode(json_encode([
        'iss' => 'iseller-api',
        'sub' => $usuarioId,
        'iat' => $ahora,
        'exp' => $ahora + API_ACCESS_TTL,
        'jti' => bin2hex(random_bytes(8)),
        'dv'  => substr(hash('sha256', $deviceId), 0, 16),
    ]));
    $firma = api_b64u_encode(hash_hmac('sha256', $header . '.' . $payload, api_secret(), true));
    return $header . '.' . $payload . '.' . $firma;
}

function api_jwt_verify(string $jwt): ?array
{
    $partes = explode('.', $jwt);
    if (count($partes) !== 3) {
        return null;
    }
    [$h64, $p64, $f64] = $partes;
    $header = json_decode((string)api_b64u_decode($h64), true);
    if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
        return null;
    }
    $firmaEsperada = hash_hmac('sha256', $h64 . '.' . $p64, api_secret(), true);
    $firmaRecibida = api_b64u_decode($f64);
    if ($firmaRecibida === null || !hash_equals($firmaEsperada, $firmaRecibida)) {
        return null;
    }
    $payload = json_decode((string)api_b64u_decode($p64), true);
    if (!is_array($payload)) {
        return null;
    }
    if (($payload['iss'] ?? '') !== 'iseller-api') {
        return null;
    }
    if ((int)($payload['exp'] ?? 0) <= time()) {
        return null;
    }
    if ((int)($payload['sub'] ?? 0) <= 0) {
        return null;
    }
    return $payload;
}

function api_header(string $nombre): string
{
    $clave = 'HTTP_' . strtoupper(str_replace('-', '_', $nombre));
    $valor = $_SERVER[$clave] ?? $_SERVER['REDIRECT_' . $clave] ?? '';
    if ($valor !== '') {
        return (string)$valor;
    }
    // Apache/CGI en Windows no siempre expone Authorization en $_SERVER.
    foreach (['getallheaders', 'apache_request_headers'] as $fn) {
        if (function_exists($fn)) {
            $cabeceras = $fn();
            if (is_array($cabeceras)) {
                foreach ($cabeceras as $k => $v) {
                    if (strcasecmp((string)$k, $nombre) === 0) {
                        return (string)$v;
                    }
                }
            }
        }
    }
    return '';
}

function api_bearer(): string
{
    $cabecera = api_header('Authorization');
    if (stripos($cabecera, 'Bearer ') !== 0) {
        api_error(401, 'No autenticado', 'no_token');
    }
    return trim(substr($cabecera, 7));
}

function api_device_id(): string
{
    $id = trim(api_header('X-Device-Id'));
    if (!preg_match('/^[A-Za-z0-9._-]{8,64}$/', $id)) {
        api_error(400, 'Dispositivo no identificado', 'device_required');
    }
    return $id;
}

/** Cliente autenticado (o error 401). */
function api_auth(): array
{
    global $conexion_store;
    $payload = api_jwt_verify(api_bearer());
    if ($payload === null) {
        api_error(401, 'Sesión expirada', 'expired');
    }
    $deviceId = api_device_id();
    if (!hash_equals((string)($payload['dv'] ?? ''), substr(hash('sha256', $deviceId), 0, 16))) {
        api_error(401, 'Sesión no válida para este dispositivo', 'device_mismatch');
    }
    $stmt = $conexion_store->prepare(
        "SELECT id, nombre, email, telefono, foto, estado, puntos, nivel, referral_code, creado_en
         FROM tienda_usuarios WHERE id = ? LIMIT 1"
    );
    $id = (int)$payload['sub'];
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || (int)$user['estado'] !== 1) {
        api_error(401, 'Cuenta no disponible', 'inactive');
    }
    return $user;
}

function api_refresh_create(int $usuarioId, string $deviceId, string $deviceName, string $plataforma): string
{
    global $conexion_store;
    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    $expira = date('Y-m-d H:i:s', time() + API_REFRESH_TTL);
    $nombre = mb_substr($deviceName, 0, 120, 'UTF-8');
    $plataforma = mb_substr($plataforma, 0, 20, 'UTF-8');
    $stmt = $conexion_store->prepare(
        "INSERT INTO api_refresh_tokens (usuario_id, token_hash, device_id, device_name, plataforma, expires_at)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssss', $usuarioId, $hash, $deviceId, $nombre, $plataforma, $expira);
    if (!$stmt->execute()) {
        $stmt->close();
        api_error(500, 'No se pudo iniciar la sesión');
    }
    $stmt->close();
    return $raw;
}

/** Devuelve [usuario, refresh_nuevo] o error. Rota el token usado. */
function api_refresh_rotate(string $raw, string $deviceId): array
{
    global $conexion_store;
    if (!preg_match('/^[a-f0-9]{64}$/', $raw)) {
        api_error(401, 'Sesión no válida', 'bad_refresh');
    }
    $hash = hash('sha256', $raw);
    $stmt = $conexion_store->prepare(
        "SELECT id, usuario_id, device_id, device_name, plataforma, expires_at, revoked_at
         FROM api_refresh_tokens WHERE token_hash = ? LIMIT 1"
    );
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$fila || $fila['revoked_at'] !== null || strtotime((string)$fila['expires_at']) <= time()) {
        api_error(401, 'Sesión expirada. Vuelve a iniciar sesión.', 'expired_refresh');
    }
    if (!hash_equals((string)$fila['device_id'], $deviceId)) {
        api_error(401, 'Sesión no válida para este dispositivo', 'device_mismatch');
    }
    $stmt = $conexion_store->prepare("UPDATE api_refresh_tokens SET revoked_at = NOW(), last_used_at = NOW() WHERE id = ? AND revoked_at IS NULL");
    $stmt->bind_param('i', $fila['id']);
    $stmt->execute();
    $rotado = $stmt->affected_rows;
    $stmt->close();
    if ($rotado !== 1) {
        api_error(401, 'Sesión ya utilizada', 'refresh_reused');
    }

    $stmt = $conexion_store->prepare(
        "SELECT id, nombre, email, telefono, foto, estado, puntos, nivel, referral_code, creado_en
         FROM tienda_usuarios WHERE id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $fila['usuario_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || (int)$user['estado'] !== 1) {
        api_error(401, 'Cuenta no disponible', 'inactive');
    }

    $nuevo = api_refresh_create(
        (int)$fila['usuario_id'],
        $deviceId,
        (string)($fila['device_name'] ?? ''),
        (string)($fila['plataforma'] ?? 'android')
    );
    return [$user, $nuevo];
}

// ── Utilidades de tienda ─────────────────────────────────────────────────────

/** Raíz pública de la tienda (donde viven assets/ y api/). */
function api_store_root(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $pos = strpos($script, '/api/v1/');
    $base = $pos === false ? rtrim(dirname($script), '/') : substr($script, 0, $pos);
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-]+(:[0-9]{1,5})?$/', $host)) {
        $host = 'localhost';
    }
    $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $esquema . '://' . $host . $base;
}

/** URL absoluta de una ruta relativa guardada en la BD (relativa a iseller_store/). */
function api_abs(?string $ruta): string
{
    $ruta = trim((string)$ruta);
    if ($ruta === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }
    return api_store_root() . '/' . ltrim($ruta, '/');
}

/** Ruta local (para getimagesize) de una ruta relativa de la BD. */
function api_local_path(?string $ruta): ?string
{
    $ruta = trim((string)$ruta);
    if ($ruta === '' || preg_match('#^https?://#i', $ruta)) {
        return null;
    }
    $local = dirname(__DIR__) . '/' . ltrim($ruta, '/');
    return is_file($local) ? $local : null;
}

/**
 * URL de imagen comprimida (WebP redimensionado y cacheado por el servidor).
 * Solo las rutas bajo assets/img pasan por el endpoint; el resto conserva api_abs.
 */
function api_image(?string $ruta, int $ancho = 600, int $calidad = 72): string
{
    $ruta = trim((string)$ruta);
    if ($ruta === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }
    if (!preg_match('#^assets/img/[A-Za-z0-9._/-]+$#', $ruta) || strpos($ruta, '..') !== false) {
        return api_abs($ruta);
    }
    $ancho = max(64, min(1600, $ancho));
    $calidad = max(40, min(85, $calidad));
    $version = '';
    $local = api_local_path($ruta);
    if ($local !== null) {
        $mtime = @filemtime($local);
        if ($mtime) {
            $version = '&v=' . $mtime;
        }
    }
    return api_store_root() . '/api/v1/image.php?src=' . rawurlencode($ruta)
        . '&w=' . $ancho . '&q=' . $calidad . $version;
}

/** Tienda virtual activa por slug o 404. */
function api_store_by_slug(string $slug): array
{
    global $conexion;
    if (!preg_match('/^[a-zA-Z0-9._-]{1,150}$/', $slug)) {
        api_error(404, 'Tienda no encontrada');
    }
    $stmt = $conexion->prepare(
        "SELECT id, nombre, nombre_visible, eslogan, slug, logo_url, portada_url, categoria,
                config_delivery, datos_bancarios, lat, lng, geo_estado, geo_municipio,
                reputacion_total, reputacion_pct, whatsapp, horarios
         FROM negocio
         WHERE slug = ? AND tiene_tienda_virtual = 1 AND estado = 'activo'
         LIMIT 1"
    );
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $tienda = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$tienda) {
        api_error(404, 'Tienda no encontrada o inactiva');
    }
    return $tienda;
}

/** Calculadora de precios cacheada por tienda. */
function api_calc(int $bssId): CalculadoraPrecios
{
    global $conexion;
    static $cache = [];
    if (isset($cache[$bssId])) {
        return $cache[$bssId];
    }
    $stmt = $conexion->prepare("SELECT * FROM cambio WHERE bss_id = ? LIMIT 1");
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $cambio = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $stmt = $conexion->prepare("SELECT tasas FROM cambios_mostrados WHERE bss_id = ? LIMIT 1");
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $mostrados = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    $bcv = 0.0;
    $res = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC LIMIT 1");
    if ($res && $fila = $res->fetch_assoc()) {
        $bcv = (float)$fila['valor'];
    }

    $dolarBolivar = (float)($cambio['DolarBolivar'] ?? 0);
    if ($dolarBolivar <= 0) {
        $dolarBolivar = $bcv > 0 ? $bcv : 40.0;
    }

    $cache[$bssId] = new CalculadoraPrecios(
        (float)($cambio['pesoDolar'] ?? 0),
        (float)($cambio['peso_bolivar'] ?? 0),
        $dolarBolivar,
        (float)($cambio['bolivar_peso'] ?? 0),
        $bcv,
        json_decode($mostrados['tasas'] ?? '[]', true) ?: []
    );
    return $cache[$bssId];
}

/** Precio truncado a 2 decimales, igual que el checkout web. */
function api_trunc2(float $valor): float
{
    return floor(round($valor * 1e6) / 1e4) / 100;
}

function api_user_public(array $u): array
{
    return [
        'id'            => (int)$u['id'],
        'name'          => $u['nombre'] ?? '',
        'email'         => $u['email'] ?? '',
        'phone'         => $u['telefono'] ?? '',
        'photo_url'     => api_image($u['foto'] ?? '', 200, 75),
        'level'         => (int)($u['nivel'] ?? 1),
        'points'        => (float)($u['puntos'] ?? 0),
        'referral_code' => $u['referral_code'] ?? '',
        'created_at'    => $u['creado_en'] ?? null,
    ];
}

/** Respuesta estándar de sesión. */
function api_session_payload(array $user, string $deviceId, string $deviceName, string $plataforma): array
{
    return [
        'access_token'  => api_jwt_issue((int)$user['id'], $deviceId),
        'refresh_token' => api_refresh_create((int)$user['id'], $deviceId, $deviceName, $plataforma),
        'token_type'    => 'Bearer',
        'expires_in'    => API_ACCESS_TTL,
        'user'          => api_user_public($user),
    ];
}
