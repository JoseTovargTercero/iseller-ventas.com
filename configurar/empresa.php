<?php
require_once('configuracion.php');
require_once('session.php');

if (isset($_POST['empresa'])) {
    $nombreEmpresa  = strip_tags(addslashes($_POST['empresa']));


    $stmt_o = $conexion->prepare("UPDATE empresa SET emp='$nombreEmpresa' WHERE id='1'");
    $stmt_o->execute();
    $stmt_o->close();


    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=empresa');
    header('Location: ' . PAGINA_INICIO);
}


if (isset($_POST['stockCritico'])) {
    $stockCritico  = strip_tags(addslashes($_POST['stockCritico']));


    $stmt = $conexion->prepare("UPDATE empresa SET stockCritico='$stockCritico' WHERE id='1'");
    $stmt->execute();
    $stmt->close();



    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=stockCritico');
    header('Location: ' . PAGINA_INICIO);
}

if (isset($_GET['accion'])) {

    if ($_GET['accion'] == "activar") {
        $accion = "1";
    } else {
        $accion = "0";
    }
    $stmt = $conexion->prepare("UPDATE empresa SET notificacionStockCritico='$accion' WHERE id='1'");
    $stmt->execute();
    $stmt->close();
    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=notificacion');
    header('Location: ' . PAGINA_INICIO);
}
