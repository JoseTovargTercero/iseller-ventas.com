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
$stmt = mysqli_prepare($conexion, "SELECT id, status, nombre, bss_id, nivel, contrasena, id_sucursal, darkMode FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $doc);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();

    if ($usuario['status'] == 1) {
        echo json_encode(['status' => false, 'msg' => 'Usuario inactivo por incumplimiento de pago']);
        exit();
    }
    if (password_verify($contrasena, $usuario['contrasena'])) {
        // Regenerar ID de sesión por seguridad
        if (session_status() === PHP_SESSION_NONE) {
            $duracion = 10800;

            session_set_cookie_params([
                'lifetime' => $duracion, // Hasta que se cierre el navegador
                'path' => '/',
                'domain' => '',
                'secure' => true,       // Solo con HTTPS
                'httponly' => true,     // No accesible desde JS
                'samesite' => 'Strict'  // Previene CSRF
            ]);
            // Duración en segundos (3 horas = 10800)


            ini_set('session.gc_maxlifetime', $duracion);
            ini_set('session.cookie_lifetime', $duracion);
            session_set_cookie_params($duracion);


            session_start();
        }

        session_regenerate_id(true);

        $_SESSION['id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['nivel'] = $usuario['nivel'];
        if ($usuario['nivel'] == 2) {
            $_SESSION['sucursal'] = $usuario['id_sucursal'];
        } else {
            unset($_SESSION['sucursal']);
        }

        if ($usuario['darkMode'] == 1) {
            $_SESSION["darkMode"] = "SI";
        } else {
            $_SESSION["darkMode"] = "NO";
        }
        $_SESSION['bss_id'] = $usuario['bss_id'];
        $_SESSION["validate"] = "ok";

        $id = $usuario['id'];



        $subdirectorios = [
            '11' => [
                'creditos_cliente.php'
            ],
            '8' => [
                'ficha.php'
            ]
        ];




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

                    if (isset($subdirectorios[$row_p['id_item_menu']])) {
                        foreach ($subdirectorios[$row_p['id_item_menu']] as $sub) {
                            $permisos[] = $sub;
                        }
                    }
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


