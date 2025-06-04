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
} else {
    $bss_id = $_SESSION["bss_id"];
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();



// Verificar acceso de los usuarios nivel 2
/*
if ($_SESSION["nivel"] == 2) {


    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $url .= "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";


    // Obtener la ruta relativa del script actual
    $script_path = $_SERVER['SCRIPT_NAME']; // Ej: /miweb/configurar/index.php
    // Verificar si está dentro de "configurar" o sus subdirectorios
    if (preg_match('#/configurar(/|$)#', $script_path)) {
        return;
    }


    $archivoActual = basename($_SERVER['PHP_SELF']);
    // verificar si la pagina que se esta cargando esta en el nivel de acceso del user
    $coincidencia = false;
    $archivo = false;
    foreach ($_SESSION["permisos"] as $key => $value) {
        $url_acceso = $value;
        $archivo = $value;

        if ($url_acceso == $archivoActual) {
            $coincidencia = true;
            $archivo = false;
        }
    }

    // print_r($_SESSION["permisos"]);

    if ($coincidencia == false) {

        if ($archivo) {
            header("Location: " . $archivo);
            exit;
        }

        // Si no coincide, redirigir al usuario a la página principal
        header("Location: " . '../../index.php');
    }
}*/
