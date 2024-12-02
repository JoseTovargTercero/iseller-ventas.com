<?php 
require_once("configuracion.php");
$id = $_GET['id'];


$stmt = $conexion->prepare("UPDATE usuarios SET status='1' WHERE id='$id'");
$stmt->execute();
$stmt->close();


	
	define('PAGINA_INICIO','../publico/production/users.php?accion=borrado');
	header('Location: '.PAGINA_INICIO);

?>