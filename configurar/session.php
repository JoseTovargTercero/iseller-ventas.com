<?php
if (session_status() === PHP_SESSION_NONE) {

    $duracion = 10800;
    $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => $duracion, // Hasta que se cierre el navegador
        'path' => '/',
        'domain' => '',
        'secure' => $esHttps,       // Solo con HTTPS
        'httponly' => true,     // No accesible desde JS
        'samesite' => 'Strict'  // Previene CSRF
    ]);
    // Duración en segundos (3 horas = 10800)

    // Ajustar parámetros de la sesión
    ini_set('session.gc_maxlifetime', $duracion);
    ini_set('session.cookie_lifetime', $duracion);


    session_start();
}


if (empty($_SESSION["bss_id"]) || empty($_SESSION["id"])) {
    // Sin login real de panel no se entra. Se calcula la raíz del proyecto para
    // que el redirect funcione tanto desde /configurar/ como desde /publico/production/...
    $__scriptAuth = $_SERVER['SCRIPT_NAME'] ?? '';
    $__corteAuth  = strpos($__scriptAuth, '/publico/');
    if ($__corteAuth === false) {
        $__corteAuth = strpos($__scriptAuth, '/configurar/');
    }
    $__raizAuth = $__corteAuth === false ? '' : substr($__scriptAuth, 0, $__corteAuth);
    header('Location: ' . $__raizAuth . '/index.php');
    exit;
} else {
    $bss_id = $_SESSION["bss_id"];
}

// ── Interceptor de Membresía Vencida (centralizado para páginas y endpoints AJAX) ──
if (!empty($_SESSION['bss_id'])) {
    if (!isset($conexion)) {
        require_once(__DIR__ . '/configuracion.php');
    }
    require_once(__DIR__ . '/MembresiaHelper.php');
    $__estadoMembresia = MembresiaHelper::obtenerEstado($conexion, $_SESSION['bss_id']);
    $__paginasExentas = ['membresia_renovacion.php', 'salir.php'];
    if (!empty($__estadoMembresia['esta_vencida']) && !in_array(basename($_SERVER['PHP_SELF'] ?? ''), $__paginasExentas, true)) {
        // Destino absoluto desde la raíz del sitio, válido tanto desde /publico/production/ como desde /configurar/
        $__script = $_SERVER['SCRIPT_NAME'] ?? '';
        $__posProd = strpos($__script, 'publico/production/');
        if ($__posProd !== false) {
            $__destino = substr($__script, 0, $__posProd) . 'publico/production/membresia_renovacion.php';
        } else {
            $__posCfg = strpos($__script, '/configurar/');
            $__destino = ($__posCfg !== false ? substr($__script, 0, $__posCfg) : '') . '/publico/production/membresia_renovacion.php';
        }
        header('Location: ' . $__destino);
        exit;
    }
    unset($__estadoMembresia, $__paginasExentas, $__script, $__posProd, $__posCfg, $__destino);
}


$_SESSION['LAST_ACTIVITY'] = time();

// Warning: session_set_cookie_params(): Cannot change session cookie parameters when session is active in /home/gitcomco/bikerrockamazonas.com/is_p/configurar/session.php on line 3

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
