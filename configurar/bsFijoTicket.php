<?php
  require_once("configuracion.php");
  require_once('session.php');
  if (isset($_GET['accion'])) {

    if ($_GET['accion'] == "activar") {
      $accion = "1";
    } else {
      $accion = "0";
    }
    $stmt = $conexion->prepare("UPDATE sistem SET bsFijoTicket='$accion'");
    $stmt->execute();
$stmt->close();

    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=notificacion' . $accion . '');
    header('Location: ' . PAGINA_INICIO);
  }


?>