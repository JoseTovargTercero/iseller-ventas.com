<?php
/**
 * API — Login de la app Android "iSeller Códigos"  →  POST /api/codigos_login.php
 *
 * Valida usuario/contraseña y devuelve un token Bearer de larga duración
 * (PosAuth), igual que el POS de Windows. No pide X-Api-Key: esa llave no se
 * puede incrustar en un APK; la autenticación real es la contraseña del usuario.
 *
 * Contrato: docs/APP_CODIGOS_BARRAS.md
 */

require_once __DIR__ . '/_pos_comun.php';
require_once __DIR__ . '/../configurar/MembresiaHelper.php';

exigirMetodo('POST');
limitar('codigos_login', 15, 300);

$in = entrada();
$usuario = trim((string)($in['usuario'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($usuario === '' || $password === '') {
    salir(400, ['ok' => false, 'error' => 'Faltan datos']);
}

$stmt = $conexion->prepare(
    "SELECT id, status, nombre, bss_id, nivel, contrasena
     FROM usuarios WHERE usuario = ? LIMIT 1"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('s', $usuario);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Mensaje genérico: no se revela si el usuario existe.
if (!$u || !password_verify($password, (string)$u['contrasena'])) {
    salir(401, ['ok' => false, 'error' => 'Credenciales incorrectas']);
}
if ((int)$u['status'] === 1) {
    salir(403, ['ok' => false, 'error' => 'Usuario inactivo por incumplimiento de pago']);
}

$bssId = (int)$u['bss_id'];
$membresia = MembresiaHelper::obtenerEstado($conexion, $bssId);
if (!empty($membresia['esta_vencida'])) {
    salir(403, ['ok' => false, 'error' => 'Membresía vencida']);
}

$negocio = '';
$stmt = $conexion->prepare("SELECT nombre FROM negocio WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $negocio = (string)($stmt->get_result()->fetch_assoc()['nombre'] ?? '');
    $stmt->close();
}

$token = PosAuth::emitir($conexion, (int)$u['id'], $bssId, null);

salir(200, [
    'ok' => true,
    'token' => $token,
    'expira_en' => PosAuth::TTL,
    'bss_id' => $bssId,
    'negocio' => $negocio,
    'usuario' => [
        'id' => (int)$u['id'],
        'nombre' => (string)$u['nombre'],
        'usuario' => $usuario,
    ],
]);
