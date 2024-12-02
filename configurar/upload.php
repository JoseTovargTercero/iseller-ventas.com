<?php
require_once('configuracion.php');

if($_SESSION['nivel']==1 || $_SESSION['nivel']==2){
	
	if((isset($_FILES['photo']['name'])&&($_FILES['photo']['error']==UPLOAD_ERR_OK))){
		
		
		
		
        $nombre = $_GET['codigo'].'.jpg';
        $codigo = $_GET['codigo'];
        
		$ruta_destino = '../publico/production/images/stock/'.$nombre;
		
		move_uploaded_file($_FILES['photo']['tmp_name'], $ruta_destino);
		


$foto = "SI";
        
$query="SELECT * FROM productos WHERE codigo='$codigo'";
$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0)
{
	
	$stmt = $conexion->prepare("UPDATE productos SET foto='$foto' WHERE codigo='$codigo'");
	$stmt->execute();
$stmt->close();
   
    
    
}

    
    
    }
	
	define('PAGINA_INICIO','../publico/production/nuevoProducto.php?codigo='.$codigo.'&foto=actualizada');
	header('Location: '.PAGINA_INICIO);



	
}else {
	
	define('PAGINA_INICIO','../../index.php?mensaje=sin_permiso');
	header('Location: '.PAGINA_INICIO);
}




?>





