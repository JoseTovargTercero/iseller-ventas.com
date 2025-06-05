<?php
require_once('../configurar/configuracion.php');
header('Content-Type: application/json');

// Validación básica
if (!isset($_POST['login'], $_POST['password'])) {
    echo json_encode(['status' => false, 'msg' => 'Faltan datos']);
    exit();
}

$doc = trim($_POST['login']);
$contrasena = $_POST['password'];

// Evitar ataques con espacios extra o datos vacíos
if (empty($doc) || empty($contrasena)) {
    echo json_encode(['status' => false, 'msg' => 'Faltan datos']);
    exit();
}

// Consulta segura
$stmt = mysqli_prepare($conexion, "SELECT id, nombre, bss_id, nivel, contrasena, id_sucursal FROM usuarios WHERE usuario = ? AND status = 0");
$stmt->bind_param("s", $doc);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();

    if (password_verify($contrasena, $usuario['contrasena'])) {
        // Regenerar ID de sesión por seguridad
        session_set_cookie_params([
            'lifetime' => 0, // Hasta que se cierre el navegador
            'path' => '/',
            'domain' => '',
            'secure' => true,       // Solo con HTTPS
            'httponly' => true,     // No accesible desde JS
            'samesite' => 'Strict'  // Previene CSRF
        ]);


        session_start();
        session_regenerate_id(true);

        $_SESSION['id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['nivel'] = $usuario['nivel'];
        if ($usuario['nivel'] == 2) {
            $_SESSION['sucursal'] = $usuario['id_sucursal'];
        }
        $_SESSION['bss_id'] = $usuario['bss_id'];
        $_SESSION["validate"] = "ok";

        $id = $usuario['id'];

        if ($usuario['nivel'] != 1) {
            $permisos = [];

            $stmt_2 = mysqli_prepare($conexion, "SELECT sup.id_item_menu, menu.dir FROM `users_permisos` AS sup 
				LEFT JOIN menu ON menu.id = sup.id_item_menu
				WHERE id_user = ?");
            $stmt_2->bind_param('i', $id);
            $stmt_2->execute();
            $result = $stmt_2->get_result();
            if ($result->num_rows > 0) {
                while ($row_p = $result->fetch_assoc()) {
                    $permisos[$row_p['id_item_menu']] = $row_p['dir'];
                }
            }
            $stmt_2->close();
            $_SESSION['permisos'] = $permisos;
        }

        // Puedes registrar el login exitoso en logs si deseas
        registrarIntentoLogin($conexion, $doc, 1, $usuario['nivel'], 'Login exitoso');
        // Redireccionar según nivel
        echo json_encode(['status' => true, 'msg' => 'ok']);
        exit();
    } else {
        // Contraseña incorrecta
        registrarIntentoLogin($conexion, $doc, 0, null, 'Contraseña incorrecta');
        echo json_encode(['status' => false, 'msg' => 'Contraseña o usuario incorrecto']);
        exit();
    }
} else {
    // Usuario no encontrado
    echo json_encode(['status' => false, 'msg' => 'Contraseña o usuario incorrecto']);
    exit();
}


function registrarIntentoLogin($conexion, $usuario, $exito, $nivel = null, $mensaje = '')
{
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $conexion->prepare("
        INSERT INTO user_log (usuario, exito, nivel, ip_usuario, user_agent, mensaje)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sissss", $usuario, $exito, $nivel, $ip, $user_agent, $mensaje);
    $stmt->execute();
}








/*
require_once('../configurar/configuracion.php');
//recuperamos usuario y contraseña y lo filtramos

$doc = mysqli_real_escape_string($conexion, $_POST['login']);
$contrasena = mysqli_real_escape_string($conexion, $_POST['password']);



$stmt = mysqli_prepare($conexion, "SELECT * FROM usuarios WHERE usuario=?");
$stmt->bind_param("s", $doc);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
while ($row = $result->fetch_assoc()) {
if (password_verify($contrasena, $row['contrasena'])) {

$_SESSION['nombre'] = $row['nombre'];
$_SESSION['nivel'] = $row['nivel'];
$_SESSION['id'] = $row['id'];
$_SESSION["validate"] = "ok";

$id = $_SESSION['id'];
$nombre = $_SESSION['nombre'];
$fecha = date('d/m/Y');
$hora = date('h:i A');
$nivel = $_SESSION['nivel'];


if ($_SESSION['nivel'] == '1') {
define('PAGINA_INICIO', '../publico/production/ventas.php');
header('Location: ' . PAGINA_INICIO);
} else {
define('PAGINA_INICIO', '../publico/production/ventas.php');
header('Location: ' . PAGINA_INICIO);
}
} else {
define('PAGINA_INICIO', '../index.php?error=error');
header('Location: ' . PAGINA_INICIO);
}
}
} else {
define('PAGINA_INICIO', '../index.php?error=error');
header('Location: ' . PAGINA_INICIO);
}


*/
/*

$numberCode = rand(15000, 15000000);
$letras = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

function ramdomAlphaCode()
{
global $letras;
$alphaCode = '';

for ($i = 0; $i < 10; $i++) {
    $alphaCode .=$letras[rand(1, 26)];
    }
    return $alphaCode;
    }
    $_SESSION['qrcode']=$id . '-' . $numberCode . ramdomAlphaCode();

    */