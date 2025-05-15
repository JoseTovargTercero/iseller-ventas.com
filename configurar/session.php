<?php
session_set_cookie_params([
    'lifetime' => 0, // Hasta que se cierre el navegador
    'path' => '/',
    'domain' => '',
    'secure' => true,       // Solo con HTTPS
    'httponly' => true,     // No accesible desde JS
    'samesite' => 'Strict'  // Previene CSRF
]);

session_start();

if (empty($_SESSION["bss_id"])) {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();
