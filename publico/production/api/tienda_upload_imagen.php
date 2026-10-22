<?php
/**
 * API: Subida segura de imagen de tienda (logo o portada).
 * POST campo "tipo" (logo|portada) + campo file "imagen"
 */

// Prevenir cualquier output antes del JSON
ob_start();

function optimizeImage($srcPath, $destPath, $maxWidth = 400, $quality = 75) {
    if (!file_exists($srcPath)) return false;
    $info = getimagesize($srcPath);
    if (!$info) return false;
    list($width, $height) = $info;
    $mime = $info['mime'];
    $ratio = $width / $height;
    
    $newWidth = $width > $maxWidth ? $maxWidth : $width;
    $newHeight = $width > $maxWidth ? intval($maxWidth / $ratio) : $height;
    
    $image = null;
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($srcPath); break;
        case 'image/png':  $image = imagecreatefrompng($srcPath); break;
        case 'image/webp': $image = imagecreatefromwebp($srcPath); break;
        default: return false;
    }
    
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    $result = imagewebp($thumb, $destPath, $quality);
    imagedestroy($image);
    imagedestroy($thumb);
    return $result;
}

// Cargar solo lo necesario (sin headers HTML)
// Desde publico/production/api/ -> subir 3 niveles hasta la raiz del proyecto
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

// Conectar a la BD directamente (igual que db.php pero sin depender de rutas complejas)
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'iseller';

$conexion = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conexion->connect_error) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de base de datos']);
    exit;
}
$conexion->set_charset('utf8mb4');

// Limpiar cualquier output previo (warnings, notices)
ob_end_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
    exit;
}

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] != '1') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

$bss_id = (int)($_SESSION['bss_id'] ?? 0);
if (!$bss_id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Sesion invalida: bss_id=' . $bss_id]);
    exit;
}

$tipo = $_POST['tipo'] ?? '';
if (!in_array($tipo, ['logo', 'portada'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tipo invalido. Use logo o portada.']);
    exit;
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    $code_err = $_FILES['imagen']['error'] ?? -1;
    if ($code_err === UPLOAD_ERR_INI_SIZE || $code_err === UPLOAD_ERR_FORM_SIZE) {
        $msg = 'El archivo es demasiado grande.';
    } elseif ($code_err === UPLOAD_ERR_NO_FILE) {
        $msg = 'No se recibio ningun archivo.';
    } else {
        $msg = 'Error al subir el archivo. Codigo: ' . $code_err;
    }
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$file = $_FILES['imagen'];

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
if (!in_array($ext, $allowed_ext)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Formato no permitido. Use JPG, PNG o WEBP.']);
    exit;
}

$max_size = $tipo === 'portada' ? 5 * 1024 * 1024 : 2 * 1024 * 1024;
if ($file['size'] > $max_size) {
    $mb = $tipo === 'portada' ? '5' : '2';
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => "El archivo supera el limite de {$mb} MB."]);
    exit;
}

// Validar MIME real
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowed_mimes)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo no es una imagen valida. MIME: ' . $mime]);
    exit;
}

// Directorio de destino: /iseller_store/assets/img/tiendas/{bss_id}/
$dir = realpath(__DIR__ . '/../../../iseller_store/assets/img/tiendas/');
if (!$dir) {
    // Intentar crear la ruta base
    $dir = __DIR__ . '/../../../iseller_store/assets/img/tiendas/';
}
$dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $bss_id . DIRECTORY_SEPARATOR;
if (!is_dir($dir)) mkdir($dir, 0775, true);

$filename = $tipo . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
$dest = $dir . $filename;

$maxWidth = $tipo === 'portada' ? 1200 : 400;

if (!optimizeImage($file['tmp_name'], $dest, $maxWidth, 85)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo optimizar ni guardar la imagen. Verifica que la imagen sea válida.']);
    exit;
}

// Borrar imagen anterior
$col = $tipo . '_url';
$old_res = $conexion->query("SELECT `{$col}` FROM negocio WHERE id = {$bss_id}");
if ($old_res) {
    $old_row = $old_res->fetch_assoc();
    if (!empty($old_row[$col])) {
        $old_path = realpath(__DIR__ . '/../../../iseller_store/') . DIRECTORY_SEPARATOR . $old_row[$col];
        if (file_exists($old_path)) @unlink($old_path);
    }
}

$url = "assets/img/tiendas/{$bss_id}/{$filename}";
$stmt = $conexion->prepare("UPDATE negocio SET `{$col}` = ? WHERE id = ?");
$stmt->bind_param('si', $url, $bss_id);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true, 'url' => $url]);
