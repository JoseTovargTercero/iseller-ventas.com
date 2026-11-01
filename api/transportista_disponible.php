<?php
/**
 * API - Disponibilidad de cédula y correo  ->  GET /api/transportista_disponible.php
 *
 * Se usa en el registro para avisar antes de crear la cuenta.
 * Entrada: cedula (obligatoria), email (opcional)
 * Autenticación: X-Api-Key.
 */

require_once __DIR__ . '/transportista_comun.php';

exigirMetodo('GET');
limitar('disponible', 30, 300);
exigirApiKey();
asegurarTablas($conexion);

$cedula = normalizarCedula((string)($_GET['cedula'] ?? ''));
$email = strtolower(trim((string)($_GET['email'] ?? '')));

if (!preg_match('/^\d{5,10}$/', $cedula)) {
    salirJson(400, ['ok' => false, 'error' => 'La cédula no es válida']);
}
if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190)) {
    salirJson(400, ['ok' => false, 'error' => 'El correo no es válido']);
}

$stmt = $conexion->prepare("SELECT id FROM transportistas WHERE cedula = ? LIMIT 1");
$stmt->bind_param('s', $cedula);
$stmt->execute();
$existeCedula = (bool)$stmt->get_result()->fetch_row();
$stmt->close();

$existeEmail = false;
if ($email !== '') {
    $stmt = $conexion->prepare("SELECT id FROM transportistas WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $existeEmail = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
}

salirJson(200, [
    'ok' => true,
    'cedula_disponible' => !$existeCedula,
    'email_disponible' => !$existeEmail,
]);
