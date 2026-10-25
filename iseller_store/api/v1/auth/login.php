<?php
/**
 * POST /iseller_store/api/v1/auth/login.php
 * Body JSON: { email, password, device_name? }
 * Cabecera: X-Device-Id
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../core/api_v1.php';

api_method('POST');
$in = api_body();

$email = strtolower(api_str($in, 'email', 150));
$password = is_string($in['password'] ?? null) ? (string)$in['password'] : '';
$deviceId = api_device_id();
$deviceName = api_str($in, 'device_name', 120) ?: 'Android';

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 200) {
    api_error(400, 'Correo o contraseña inválidos', 'bad_credentials');
}

// Dos límites: por IP y por IP+correo (evita fuerza bruta distribuida por cuenta).
api_rate_limit('login_ip', 40, 900);
api_rate_limit('login_email', 10, 900, $email);

global $conexion_store;
$stmt = $conexion_store->prepare(
    "SELECT id, nombre, email, telefono, foto, password, estado, puntos, nivel, referral_code, creado_en
     FROM tienda_usuarios WHERE email = ? LIMIT 1"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Mismo mensaje para correo inexistente, contraseña mala y cuenta desactivada.
// password_verify corre siempre (contra un hash falso si no hay usuario) para no
// delatar por tiempo qué correos existen.
$hashGuardado = (string)($user['password'] ?? '$2y$12$hBkhcZ.1EFkIYnCboyC0e.tWd/atQghH8mQxP8Gy5wrhCvGiCLqLe');
$passwordOk = password_verify($password, $hashGuardado);
if (!$user || (int)$user['estado'] !== 1 || !$passwordOk) {
    api_error(401, 'Correo o contraseña incorrectos', 'invalid_login');
}

unset($user['password']);
api_ok(api_session_payload($user, $deviceId, $deviceName, 'android'), ['message' => 'Sesión iniciada']);
