<?php
require_once('configuracion.php');

if($_SESSION['nivel']==1){
	

$nombre = $_POST['name'];
$user = $_POST['user'];
$contrasena = $_POST['password'];
$conf_contrasena = $_POST['password2'];
$nivel = $_POST['nivel'];

	
    if ($nombre != '' && $user != '' && $contrasena != '' && $nivel != '') {
      if ($contrasena == $conf_contrasena) {



      $contrasena = md5($contrasena);
      $clave = md5($clave);


      $stmt_o = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, nivel) VALUES ('$nombre','$user', '$contrasena', '$nivel')");
      $stmt_o->execute();
		  
		  
		  
	
			
		define('PAGINA_INICIO','../publico/production/users.php?accion=exito');
		header('Location: '.PAGINA_INICIO);

      }else{ 
          define('PAGINA_INICIO','../publico/production/users.php?accion=contra');
		header('Location: '.PAGINA_INICIO);
      }
    }else{
       define('PAGINA_INICIO','../publico/production/users.php?accion=vacio');
		header('Location: '.PAGINA_INICIO);
    }

	
}else {
	
	define('PAGINA_INICIO','../../index.php?mensaje=sin_permiso');
	header('Location: '.PAGINA_INICIO);
}

?>
