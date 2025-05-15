<?php
require_once("configuracion.php");
require_once('session.php');
if (isset($_GET['accion'])) {

    if ($_GET['accion'] == "activarCOR") {
        $accion = "1";
    } else {
        $accion = "0";
    }
    $stmt = $conexion->prepare("UPDATE mail SET cortes='$accion' WHERE id='1'");
    $stmt->execute();
    $stmt->close();

    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=notificacion' . $accion . '');
    header('Location: ' . PAGINA_INICIO);
}




if (isset($_GET['accion2'])) {

    if ($_GET['accion2'] == "activar") {
        $accion = "1";
    } else {
        $accion = "0";
    }
    $stmt = $conexion->prepare("UPDATE mail SET cierre='$accion' WHERE id='1'");
    $stmt->execute();
    $stmt->close();

    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=notificacion' . $accion . '');
    header('Location: ' . PAGINA_INICIO);
}
