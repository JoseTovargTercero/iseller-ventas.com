<?php
/**
 * API — Registrar el token FCM del transportista  →  POST /api/transportista_push.php
 *
 * Entrada: token, plataforma ('android'), action ('registrar'|'baja')
 * Autenticación: Bearer del transportista + X-Api-Key.
 */

require_once __DIR__ . '/transportista_comun.php';
require_once __DIR__ . '/../publico/production/includes/push_fcm.php';

exigirMetodo('POST');
limitar('push', 30, 300);
exigirApiKey();
asegurarTablas($conexion);
push_asegurar_tabla($conexion);

$transportista = transportistaDesdeToken($conexion);
if (!$transportista) {
    salirJson(401, ['ok' => false, 'error' => 'Sesión no válida o expirada']);
}

$in = entrada();
$token = trim((string)($in['token'] ?? ''));
$plataforma = trim((string)($in['plataforma'] ?? 'android'));
$accion = trim((string)($in['action'] ?? 'registrar'));

if ($token === '' || strlen($token) > 255) {
    salirJson(400, ['ok' => false, 'error' => 'Token inválido']);
}
if ($plataforma === '') {
    $plataforma = 'android';
}

$transportistaId = (int)$transportista['id'];

if ($accion === 'baja') {
    $stmt = $conexion->prepare(
        "DELETE FROM push_dispositivos WHERE token = ? AND tipo = 'transp' AND usuario_id = ?"
    );
    if ($stmt) {
        $stmt->bind_param('si', $token, $transportistaId);
        $stmt->execute();
        $stmt->close();
    }
    salirJson(200, ['ok' => true]);
}

// tipo 'transp' (cabe en varchar(10)); el token es único por dispositivo.
push_limpiar_token_ajeno($conexion, 'transp', $transportistaId, $token);
$stmt = $conexion->prepare(
    "INSERT INTO push_dispositivos (tipo, usuario_id, bss_id, token, plataforma)
     VALUES ('transp', ?, 0, ?, ?)
     ON DUPLICATE KEY UPDATE tipo = 'transp', usuario_id = VALUES(usuario_id),
                             plataforma = VALUES(plataforma), actualizado_en = NOW()"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('iss', $transportistaId, $token, $plataforma);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}

salirJson(200, ['ok' => true]);
