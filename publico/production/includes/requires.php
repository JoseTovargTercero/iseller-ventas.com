<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once('../../configurar/_tasas_cambio.php');
require_once('includes/header.php');
require_once('includes/menu.php');

// recupera el nombre de la pagina actual
$nombrePagina = basename($_SERVER['PHP_SELF']);



if ($_SESSION["nivel"] == 2) {
    $archivoActual = basename($_SERVER['PHP_SELF']);
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
    if ($coincidencia == false && $nombrePagina != 'detallesVenta.php' ) {
        header("Location: ../../login/salir.php");
        exit;
    }
}
