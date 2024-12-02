<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");

///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////
if(isset($_POST['rep1'])){
	$q4=$conexion->real_escape_string($_POST['rep1']);
	$query="SELECT * FROM tasas_pesos WHERE id_peso='$q4'";
}

$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0)
{
	while($filaAlumnos= $buscarAlumnos->fetch_assoc())
	{ 
		$recepcionPesos = $filaAlumnos['recepcion_peso'];
		$publicacionPesos = $filaAlumnos['publicacion_peso'];

		echo "
		<input type='text' hidden value='".$recepcionPesos."' id='cambioPesoRecepcion' name='cambioPesoRecepcion'>
		<input type='text' hidden  value='".$publicacionPesos."' id='cambioPesoPublicacion' name='cambioPesoPublicacion'>
	
			";
	}
}else{
	if(isset($_GET['idPeso'])){
		$idPeso = $_GET['idPeso'];
		$query="SELECT * FROM tasas_pesos WHERE id_peso='$idPeso'";
			}else{
		
				$query="SELECT * FROM tasas_pesos WHERE id_peso='0'";
		}

$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0){
	while($filaAlumnos= $buscarAlumnos->fetch_assoc()){ 
		$recepcionPesos = $filaAlumnos['recepcion_peso'];
		$publicacionPesos = $filaAlumnos['publicacion_peso'];

		echo " 
		<input type='text' hidden value='".$recepcionPesos."' id='cambioPesoRecepcion' name='cambioPesoRecepcion'>
		<input type='text' hidden value='".$publicacionPesos."' id='cambioPesoPublicacion' name='cambioPesoPublicacion'>


		";
	}
}

}


?>
