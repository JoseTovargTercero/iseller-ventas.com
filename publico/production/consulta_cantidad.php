<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");

///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////


if(isset($_POST['rep_precio']))
{
	$q=$conexion->real_escape_string($_POST['rep_precio']);
/*	$query="SELECT * FROM cambio WHERE id='1'";

}

$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0)
{
	while($filaAlumnos= $buscarAlumnos->fetch_assoc())
	{     
  */      
        $tabla= '
        <input type="text" value="'.$_POST['rep_precio'].'$" class="form-control has-feedback-left" placeholder="First Name">
        <span class="fa fa-user form-control-feedback left" aria-hidden="true"></span>
        ';
    /*
    
	}*/
}else{
    
		$tabla='Sin valor de entrada';
	}


echo $tabla;
?>


