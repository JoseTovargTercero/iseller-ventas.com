<?php
/*
require_once('../configurar/configuracion.php');
$fecha = date ('d/m/Y');
$hora =  date ('h:i A');
$id = $_SESSION['id'];

	$stmt = $conexion->prepare("UPDATE `log_usuarios` SET `hora_fin`= '$hora' WHERE `id_user`='$id' ORDER BY id DESC LIMIT 1");
	$stmt->execute();
$stmt->close();
	$stmt = $conexion->prepare("UPDATE `log_usuarios` SET `fecha_fin`= '$fecha' WHERE `id_user`='$id' ORDER BY id DESC LIMIT 1");
	$stmt->execute();
	$stmt->close();




*/


session_start();

unset($_SESSION);

session_destroy();

define('PAGINA_INICIO','../index.php');
header('Location: '.PAGINA_INICIO);
    
?>