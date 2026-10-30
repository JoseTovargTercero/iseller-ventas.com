<?php
/**
 * POST /iseller_store/api/v1/push/token.php
 * Body JSON: { token, action?: "register"|"unregister", platform?: "android" }
 * Cabecera: Authorization: Bearer ... + X-Device-Id
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../core/api_v1.php';
require_once __DIR__ . '/../../../../publico/production/includes/push_fcm.php';

api_method('POST');
$user = api_auth();
api_rate_limit('push_token', 60, 300);
$in = api_body();

$token = api_str($in, 'token', 255);
$accion = api_str($in, 'action', 20) ?: 'register';
$plataforma = api_str($in, 'platform', 20) ?: 'android';

if (strlen($token) < 20) {
    api_error(400, 'Token inválido', 'invalid_token');
}
if (!in_array($plataforma, ['android', 'ios'], true)) {
    $plataforma = 'android';
}

global $conexion;
push_asegurar_tabla($conexion);
push_limpiar_token_ajeno($conexion, 'cliente', (int)$user['id'], $token);
if ($accion === 'unregister') {
    $stmt = $conexion->prepare(
        "DELETE FROM push_dispositivos WHERE token = ? AND tipo = 'cliente' AND usuario_id = ?"
    );
    $stmt->bind_param('si', $token, $user['id']);
    $stmt->execute();
    $stmt->close();
    api_ok(null, ['message' => 'Dispositivo dado de baja']);
}

$stmt = $conexion->prepare(
    "INSERT INTO push_dispositivos (tipo, usuario_id, bss_id, token, plataforma)
     VALUES ('cliente', ?, NULL, ?, ?)
     ON DUPLICATE KEY UPDATE tipo = 'cliente', usuario_id = VALUES(usuario_id),
                             plataforma = VALUES(plataforma), actualizado_en = NOW()"
);
$stmt->bind_param('iss', $user['id'], $token, $plataforma);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    api_error(500, 'No se pudo registrar el dispositivo');
}
api_ok(null, ['message' => 'Dispositivo registrado']);
