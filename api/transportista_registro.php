<?php
/**
 * API — Registro de transportista  →  POST /api/transportista_registro.php
 *
 * Entrada (JSON o formulario):
 *   nombre, cedula, telefono, password, deliverys (0/1), carreras (0/1)
 *
 * Crea la cuenta (contraseña hasheada) y devuelve un token Bearer.
 */

require_once __DIR__ . '/transportista_comun.php';

exigirMetodo('POST');
limitar('registro', 10, 600);
exigirApiKey();
asegurarTablas($conexion);

$in = entrada();
$nombre   = trim((string)($in['nombre'] ?? ''));
$cedula   = normalizarCedula((string)($in['cedula'] ?? ''));
$telefono = normalizarTelefono((string)($in['telefono'] ?? ''));
$password = (string)($in['password'] ?? '');
$deliverys = !empty($in['deliverys']) ? 1 : 0;
$carreras  = !empty($in['carreras']) ? 1 : 0;

if ($nombre === '' || $cedula === '' || $telefono === '' || $password === '') {
    salirJson(400, ['ok' => false, 'error' => 'Faltan datos']);
}
if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 120) {
    salirJson(400, ['ok' => false, 'error' => 'El nombre no es válido']);
}
if (!preg_match('/^\d{5,10}$/', $cedula)) {
    salirJson(400, ['ok' => false, 'error' => 'La cédula no es válida']);
}
if (!preg_match('/^\d{7,15}$/', $telefono)) {
    salirJson(400, ['ok' => false, 'error' => 'El teléfono no es válido']);
}
if (strlen($password) < 6 || strlen($password) > 72) {
    salirJson(400, ['ok' => false, 'error' => 'La contraseña debe tener entre 6 y 72 caracteres']);
}
if ($deliverys + $carreras < 1) {
    salirJson(400, ['ok' => false, 'error' => 'Selecciona al menos un servicio']);
}

$stmt = $conexion->prepare(
    "SELECT id FROM transportistas WHERE cedula = ? LIMIT 1"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('s', $cedula);
$stmt->execute();
$existe = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existe) {
    salirJson(409, ['ok' => false, 'error' => 'Ya existe una cuenta con esa cédula']);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conexion->prepare(
    "INSERT INTO transportistas (nombre, cedula, telefono, password_hash, deliverys, carreras)
     VALUES (?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('ssssii', $nombre, $cedula, $telefono, $hash, $deliverys, $carreras);
$ok = $stmt->execute();
$nuevoId = $conexion->insert_id;
$errno = $conexion->errno;
$stmt->close();

if (!$ok) {
    if ($errno === 1062) {
        salirJson(409, ['ok' => false, 'error' => 'Ya existe una cuenta con esa cédula']);
    }
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}

$token = emitirToken($conexion, (int)$nuevoId);

salirJson(201, [
    'ok'            => true,
    'token'         => $token,
    'transportista' => perfilTransportista([
        'id'        => $nuevoId,
        'nombre'    => $nombre,
        'cedula'    => $cedula,
        'telefono'  => $telefono,
        'deliverys' => $deliverys,
        'carreras'  => $carreras,
    ]),
]);
