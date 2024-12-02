<?php 

require_once("configuracion.php");

if(isset($_POST['tipoTasa'])){
    
    $tipo  = strip_tags( addslashes( $_POST['tipoTasa'] ) );
    $nombre  = strip_tags( addslashes( $_POST['nombre'] ) );
    $recepcion  = strip_tags( addslashes( $_POST['recepcion'] ) );
    $publicacion = strip_tags( addslashes( $_POST['publicacion'] ) );
    


if($tipo == "dolares"){


    $stmt_o = $conexion->prepare("INSERT INTO `tasas_dolar` (`tasa`, `recepcion`, `publicacion`) VALUES ('$nombre', '$recepcion', '$recepcion');");
    $stmt_o->execute();
    $stmt_o -> close();
}else{


    $stmt_o = $conexion->prepare("INSERT INTO `tasas_pesos` (`tasa_peso`, `recepcion_peso`, `publicacion_peso`) VALUES ('$nombre', '$recepcion', '$publicacion');");
    $stmt_o->execute();
    $stmt_o -> close();
}

    
    define('PAGINA_INICIO','../publico/production/tasas.php?accion=tasas');
	header('Location: '.PAGINA_INICIO);

}

if(isset($_GET['idBorrar'])){

    $tipo = $_GET['tipo'];
    $id = $_GET['idBorrar'];
    if($tipo == "peso"){
        $stmt_o = $conexion->prepare("DELETE FROM tasas_pesos WHERE id_peso='$id'");
        $stmt_o->execute();
        $stmt_o -> close();
    }else{

        $stmt_o = $conexion->prepare("DELETE FROM tasas_dolar WHERE id='$id'");
        $stmt_o->execute();
        $stmt_o -> close();
    }
    define('PAGINA_INICIO','../publico/production/tasas.php?accion=borrado');
	header('Location: '.PAGINA_INICIO);
    }



?>