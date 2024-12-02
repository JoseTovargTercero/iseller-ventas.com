<?php 
require_once('configuracion.php');

if(isset($_POST['nombreK'])){
    $nombreK  = strip_tags( addslashes( $_POST['nombreK'] ) );
    $descripcion  = strip_tags( addslashes( $_POST['desc'] ) );

    $stmt_o = $conexion->prepare("INSERT INTO `categorias` (`nombre_categoria`, `descripcion`) VALUES ('$nombreK', '$descripcion');");
    $stmt_o->execute();

    define('PAGINA_INICIO','../publico/production/Categorias.php?accion=agregada');
	header('Location: '.PAGINA_INICIO);   
}
