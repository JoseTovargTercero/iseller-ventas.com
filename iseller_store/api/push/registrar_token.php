<?php
/**
 * Registro del token de push del dispositivo (Google FCM).
 *
 * Lo llama la app Android después del login (y al renovar el token de FCM).
 * Detecta solo de quién es el token según la sesión:
 *   - sesión de la tienda (tienda_usuarios) -> tipo 'cliente'
 *   - sesión del panel (usuarios)           -> tipo 'panel'
 *
 * POST JSON o formulario:
 *   token      string  token de FCM (obligatorio)
 *   plataforma string  'android' (por defecto)
 *   action     string  'registrar' (por defecto) | 'baja'
 */

require_once('../../core/db.php');
require_once('../../core/session.php');

header('Content-Type: application/json');

$tipo = '';
$usuario_id = 0;
$bss_id = null;

if (isLoggedIn()) {
    $tipo = 'cliente';
    $usuario_id = (int)getUserId();
} elseif (!empty($_SESSION['validate']) && !empty($_SESSION['id'])) {
    $tipo = 'panel';
    $usuario_id = (int)$_SESSION['id'];
    $bss_id = isset($_SESSION['bss_id']) ? (int)$_SESSION['bss_id'] : null;
}

if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

$action = $input['action'] ?? 'registrar';
$token = trim($input['token'] ?? '');
$plataforma = trim($input['plataforma'] ?? 'android');

if ($token === '' || strlen($token) > 255) {
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

if ($action === 'baja') {
    $stmt = $conexion->prepare("DELETE FROM push_dispositivos WHERE token = ? AND tipo = ? AND usuario_id = ?");
    $stmt->bind_param('ssi', $token, $tipo, $usuario_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

$stmt = $conexion->prepare(
    "INSERT INTO push_dispositivos (tipo, usuario_id, bss_id, token, plataforma)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), usuario_id = VALUES(usuario_id),
                             bss_id = VALUES(bss_id), plataforma = VALUES(plataforma),
                             actualizado_en = NOW()"
);
$stmt->bind_param('siiss', $tipo, $usuario_id, $bss_id, $token, $plataforma);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok]);
