<?php
/**
 * POST /iseller_store/api/v1/auth/logout.php
 * Revoca el refresh del dispositivo. Body JSON: { refresh_token }
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../core/api_v1.php';

api_method('POST');
$user = api_auth();
api_device_id();
$in = api_body();
$refresh = api_str($in, 'refresh_token', 128);

if (preg_match('/^[a-f0-9]{64}$/', $refresh)) {
    global $conexion_store;
    $hash = hash('sha256', $refresh);
    $stmt = $conexion_store->prepare(
        "UPDATE api_refresh_tokens SET revoked_at = NOW()
         WHERE token_hash = ? AND usuario_id = ? AND revoked_at IS NULL"
    );
    $stmt->bind_param('si', $hash, $user['id']);
    $stmt->execute();
    $stmt->close();
}

api_ok(null, ['message' => 'Sesión cerrada']);
