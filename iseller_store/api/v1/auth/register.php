<?php
/**
 * POST /iseller_store/api/v1/auth/register.php
 * Body JSON: { name, email, password, password_confirm, phone, device_name? }
 * Cabecera: X-Device-Id
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../core/api_v1.php';

api_method('POST');
api_rate_limit('register_ip', 5, 3600);
$in = api_body();

$nombre = api_str($in, 'name', 100);
$email = strtolower(api_str($in, 'email', 150));
$telefono = api_str($in, 'phone', 20);
$password = is_string($in['password'] ?? null) ? (string)$in['password'] : '';
$confirm = is_string($in['password_confirm'] ?? null) ? (string)$in['password_confirm'] : '';
$deviceId = api_device_id();
$deviceName = api_str($in, 'device_name', 120) ?: 'Android';

if (mb_strlen($nombre) < 2) {
    api_error(400, 'Escribe tu nombre', 'invalid_name');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error(400, 'Correo inválido', 'invalid_email');
}
if (!preg_match('/^[0-9+()\-.\s]{5,20}$/', $telefono)) {
    api_error(400, 'Teléfono inválido', 'invalid_phone');
}
if (strlen($password) < 6) {
    api_error(400, 'La contraseña debe tener al menos 6 caracteres', 'weak_password');
}
if ($password !== $confirm) {
    api_error(400, 'Las contraseñas no coinciden', 'password_mismatch');
}

global $conexion_store;
$stmt = $conexion_store->prepare("SELECT id FROM tienda_usuarios WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$existe = $stmt->get_result()->num_rows > 0;
$stmt->close();
if ($existe) {
    api_error(409, 'Ese correo ya está registrado. Inicia sesión.', 'email_exists');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conexion_store->prepare(
    "INSERT INTO tienda_usuarios (nombre, email, password, telefono, estado) VALUES (?, ?, ?, ?, 1)"
);
$stmt->bind_param('ssss', $nombre, $email, $hash, $telefono);
if (!$stmt->execute()) {
    $stmt->close();
    api_error(500, 'No se pudo crear la cuenta');
}
$userId = (int)$conexion_store->insert_id;
$stmt->close();

$codigo = strtoupper(substr(md5($userId . uniqid('', true)), 0, 8));
$stmt = $conexion_store->prepare("UPDATE tienda_usuarios SET referral_code = ? WHERE id = ?");
$stmt->bind_param('si', $codigo, $userId);
$stmt->execute();
$stmt->close();

$stmt = $conexion_store->prepare(
    "SELECT id, nombre, email, telefono, foto, estado, puntos, nivel, referral_code, creado_en
     FROM tienda_usuarios WHERE id = ? LIMIT 1"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

api_ok(api_session_payload($user, $deviceId, $deviceName, 'android'), ['message' => 'Cuenta creada']);
