<?php
/**
 * POST /iseller_store/api/v1/auth/refresh.php
 * Body JSON: { refresh_token }
 * Cabecera: X-Device-Id
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../core/api_v1.php';

api_method('POST');
api_rate_limit('refresh_ip', 120, 900);
$in = api_body();
$refresh = api_str($in, 'refresh_token', 128);
$deviceId = api_device_id();

[$user, $nuevoRefresh] = api_refresh_rotate($refresh, $deviceId);

api_ok([
    'access_token'  => api_jwt_issue((int)$user['id'], $deviceId),
    'refresh_token' => $nuevoRefresh,
    'token_type'    => 'Bearer',
    'expires_in'    => API_ACCESS_TTL,
    'user'          => api_user_public($user),
]);
