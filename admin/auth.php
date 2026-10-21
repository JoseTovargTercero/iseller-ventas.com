<?php
require_once __DIR__ . '/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// 1. Verificación de Rate Limiting (Protección contra fuerza bruta)
$rateLimit = AdminSecurity::checkRateLimit();
if ($rateLimit['blocked']) {
    header('Location: index.php?error=locked&wait=' . (int)$rateLimit['remaining_wait']);
    exit;
}

// 2. Verificación de token CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (!AdminSecurity::verifyCsrfToken($csrfToken)) {
    header('Location: index.php?error=csrf');
    exit;
}

// 3. Obtener y sanitizar datos
$user = trim($_POST['admin_user'] ?? '');
$pass = trim($_POST['admin_pass'] ?? '');

if (empty($user) || empty($pass)) {
    header('Location: index.php?error=empty');
    exit;
}

// 4. Validación segura de credenciales contra la base de datos
$authResult = AdminSecurity::validarCredenciales($user, $pass);

if ($authResult && is_array($authResult)) {
    // Éxito: limpiar intentos y regenerar ID de sesión contra fijación
    AdminSecurity::resetRateLimit();
    session_regenerate_id(true);

    $_SESSION['admin_logged_in']     = true;
    $_SESSION['admin_id']            = $authResult['id'];
    $_SESSION['admin_user']          = $authResult['usuario'];
    $_SESSION['admin_name']          = $authResult['nombre'];
    $_SESSION['admin_rol']           = $authResult['rol'];
    $_SESSION['admin_login_at']      = time();
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_ip']            = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['admin_ua']            = $_SERVER['HTTP_USER_AGENT'] ?? '';

    header('Location: dashboard.php');
    exit;
} else {
    // Fallo: registrar intento fallido
    $intentos = AdminSecurity::recordFailedLogin();
    if ($intentos >= AdminSecurity::MAX_LOGIN_ATTEMPTS) {
        header('Location: index.php?error=locked&wait=' . AdminSecurity::LOCKOUT_MINUTES);
    } else {
        header('Location: index.php?error=1');
    }
    exit;
}
