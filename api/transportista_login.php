<?php
/**
 * API — Login de transportista  →  POST /api/transportista_login.php
 *
 * Entrada: cedula, password
 * Devuelve el perfil y un token Bearer para consultar sus servicios.
 */

require_once __DIR__ . '/transportista_comun.php';

exigirMetodo('POST');
limitar('login', 20, 300);
exigirApiKey();
asegurarTablas($conexion);

$in = entrada();
$cedula   = normalizarCedula((string)($in['cedula'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($cedula === '' || $password === '') {
    salirJson(400, ['ok' => false, 'error' => 'Faltan datos']);
}

$stmt = $conexion->prepare("SELECT * FROM transportistas WHERE cedula = ? LIMIT 1");
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('s', $cedula);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !password_verify($password, $row['password_hash'])) {
    salirJson(401, ['ok' => false, 'error' => 'Cédula o contraseña incorrectas']);
}
if ((int)$row['activo'] !== 1) {
    salirJson(403, ['ok' => false, 'error' => 'Cuenta inactiva']);
}

$token = emitirToken($conexion, (int)$row['id']);

salirJson(200, [
    'ok'            => true,
    'token'         => $token,
    'transportista' => perfilTransportista($row),
]);
