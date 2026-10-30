<?php
/**
 * API — Registro del token de push (iSeller POS)  →  POST /api/push_registrar.php
 *
 * La app Android manda aquí el token de FCM tras un login correcto (y al renovarlo),
 * para que el servidor pueda notificarle con push_fcm.php (tipo 'panel').
 *
 * Autenticación del cliente: cabecera `X-Api-Key` (POS_API_KEY del .env), igual que
 * auth_login.php. El usuario se identifica por su login (`usuario`), no por contraseña:
 * la app acaba de validarla contra auth_login.php.
 *
 * Entrada (JSON o formulario):
 *   usuario    string  login del usuario del panel (obligatorio)
 *   token      string  token FCM del dispositivo (obligatorio)
 *   plataforma string  'android' por defecto
 *   action     string  'registrar' por defecto | 'baja'
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
require_once __DIR__ . '/../publico/production/includes/push_fcm.php';

function salir(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Solo POST ──────────────────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    salir(405, ['ok' => false, 'error' => 'Método no permitido']);
}

// ── Rate limit por IP: 60 peticiones / 5 min ───────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlFile = sys_get_temp_dir() . '/iseller_pushreg_' . md5($ip) . '.json';
$rlMax = 60;
$rlWindow = 300;
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
    salir(429, ['ok' => false, 'error' => 'Demasiadas peticiones']);
}

// ── API key (fail-closed) ──────────────────────────────────────────────────────
$apiKeyEsperada = trim($_ENV['POS_API_KEY'] ?? '');
$apiKeyRecibida = trim($_SERVER['HTTP_X_API_KEY'] ?? '');
if ($apiKeyEsperada === '') {
    if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
        salir(503, ['ok' => false, 'error' => 'API no configurada']);
    }
} elseif ($apiKeyRecibida === '' || !hash_equals($apiKeyEsperada, $apiKeyRecibida)) {
    salir(401, ['ok' => false, 'error' => 'No autorizado']);
}

// ── Entrada: JSON o formulario ─────────────────────────────────────────────────
$raw = file_get_contents('php://input');
$json = json_decode((string)$raw, true);
$in = is_array($json) ? $json : $_POST;

$usuario = trim((string)($in['usuario'] ?? $in['login'] ?? ''));
$token = trim((string)($in['token'] ?? ''));
$plataforma = trim((string)($in['plataforma'] ?? 'android'));
$action = trim((string)($in['action'] ?? 'registrar'));

if ($usuario === '') {
    salir(400, ['ok' => false, 'error' => 'Faltan datos']);
}
if ($token === '' || strlen($token) > 255) {
    salir(400, ['ok' => false, 'error' => 'Token inválido']);
}
if ($plataforma === '') {
    $plataforma = 'android';
}

// ── Usuario del panel ──────────────────────────────────────────────────────────
$stmt = $conexion->prepare("SELECT id, status, bss_id FROM usuarios WHERE usuario = ? LIMIT 1");
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('s', $usuario);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) {
    salir(401, ['ok' => false, 'error' => 'Credenciales incorrectas']);
}
if ((int)$u['status'] === 1) {
    salir(403, ['ok' => false, 'error' => 'Usuario inactivo por incumplimiento de pago']);
}

$usuarioId = (int)$u['id'];
$bssId = (int)$u['bss_id'];

push_asegurar_tabla($conexion);

// ── Alta o baja del dispositivo ────────────────────────────────────────────────
if ($action === 'baja') {
    $stmt = $conexion->prepare("DELETE FROM push_dispositivos WHERE token = ? AND tipo = 'panel' AND usuario_id = ?");
    $stmt->bind_param('si', $token, $usuarioId);
    $stmt->execute();
    $stmt->close();
    salir(200, ['ok' => true]);
}

push_limpiar_token_ajeno($conexion, 'panel', $usuarioId, $token);

$stmt = $conexion->prepare(
    "INSERT INTO push_dispositivos (tipo, usuario_id, bss_id, token, plataforma)
     VALUES ('panel', ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), bss_id = VALUES(bss_id),
                             plataforma = VALUES(plataforma), actualizado_en = NOW()"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('iiss', $usuarioId, $bssId, $token, $plataforma);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}

salir(200, ['ok' => true, 'usuario_id' => $usuarioId, 'bss_id' => $bssId]);
