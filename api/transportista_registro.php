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
$foto      = (string)($in['foto'] ?? '');
$motoMarca = trim((string)($in['moto_marca'] ?? ''));
$motoModelo = trim((string)($in['moto_modelo'] ?? ''));
$motoColor = trim((string)($in['moto_color'] ?? ''));

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
if ($motoMarca === '' || $motoModelo === '' || $motoColor === '') {
    salirJson(400, ['ok' => false, 'error' => 'Completa la marca, el modelo y el color de la moto']);
}
if (mb_strlen($motoMarca) > 60 || mb_strlen($motoModelo) > 60 || mb_strlen($motoColor) > 40) {
    salirJson(400, ['ok' => false, 'error' => 'Los datos de la moto son demasiado largos']);
}
if ($foto === '') {
    salirJson(400, ['ok' => false, 'error' => 'Falta la foto del transportista']);
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

// ── Foto del transportista (base64; se guarda como archivo) ───────────────────
$foto = preg_replace('/\s+/', '', $foto);
if (preg_match('#^data:image/(jpeg|png);base64,#i', $foto, $m)) {
    $foto = substr($foto, strlen($m[0]));
}
$datosFoto = base64_decode($foto, true);
if ($datosFoto === false || $datosFoto === '') {
    salirJson(400, ['ok' => false, 'error' => 'La foto no es válida']);
}
if (strlen($datosFoto) > 2 * 1024 * 1024) {
    salirJson(400, ['ok' => false, 'error' => 'La foto es demasiado grande (máx. 2 MB)']);
}
$infoFoto = @getimagesizefromstring($datosFoto);
if (!$infoFoto || !in_array($infoFoto[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
    salirJson(400, ['ok' => false, 'error' => 'La foto debe ser JPG o PNG']);
}

$directorioFotos = __DIR__ . '/../uploads/transportistas';
if (!is_dir($directorioFotos) && !@mkdir($directorioFotos, 0775, true)) {
    salirJson(500, ['ok' => false, 'error' => 'No se pudo guardar la foto']);
}
$extension = $infoFoto[2] === IMAGETYPE_PNG ? 'png' : 'jpg';
$nombreFoto = 'transportista_' . bin2hex(random_bytes(8)) . '.' . $extension;
$rutaFoto = $directorioFotos . '/' . $nombreFoto;
if (@file_put_contents($rutaFoto, $datosFoto) === false) {
    salirJson(500, ['ok' => false, 'error' => 'No se pudo guardar la foto']);
}

$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$host = $_SERVER['HTTP_HOST'] ?? '';
$appPath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$fotoUrl = $host !== ''
    ? ($esHttps ? 'https' : 'http') . '://' . $host . $appPath . '/uploads/transportistas/' . $nombreFoto
    : '';

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conexion->prepare(
    "INSERT INTO transportistas
        (nombre, cedula, telefono, password_hash, deliverys, carreras,
         foto_url, moto_marca, moto_modelo, moto_color)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    @unlink($rutaFoto);
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('ssssiissss', $nombre, $cedula, $telefono, $hash, $deliverys, $carreras,
        $fotoUrl, $motoMarca, $motoModelo, $motoColor);
$ok = $stmt->execute();
$nuevoId = $conexion->insert_id;
$errno = $conexion->errno;
$stmt->close();

if (!$ok) {
    @unlink($rutaFoto);
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
        'foto_url'  => $fotoUrl,
        'moto_marca' => $motoMarca,
        'moto_modelo' => $motoModelo,
        'moto_color' => $motoColor,
    ]),
]);
