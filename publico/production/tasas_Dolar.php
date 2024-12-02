<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");

///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////
if(isset($_POST['rep2'])){
	$q2=$conexion->real_escape_string($_POST['rep2']);
	$query="SELECT * FROM tasas_dolar WHERE id='$q2'";
}

$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0){
	while($filaAlumnos= $buscarAlumnos->fetch_assoc()){ 
		$recepciondolar = $filaAlumnos['recepcion'];
		echo "<input type='text'  hidden value='".$recepciondolar."' id='cambioDolar' name='cambioDolar'>
		<p style='text-align:center'>Dólar: <strong>".number_format($recepciondolar,'0', '.','.')."</strong></p>";
	}
}else{

    if(isset($_GET['idDolar'])){
	$elIdDolar = $_GET['idDolar'];
	$query="SELECT * FROM tasas_dolar WHERE id='$elIdDolar'";
	}else{
	$query="SELECT * FROM tasas_dolar WHERE id='0'";
	}

$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0){
	while($filaAlumnos= $buscarAlumnos->fetch_assoc()){ 
		$recepciondolar = $filaAlumnos['recepcion'];
		echo "<input type='text' hidden  value='".$recepciondolar."' id='cambioDolar' name='cambioDolar'>
	
		
		<p style='text-align:center'>Dólar: <strong>".number_format($recepciondolar,'0', '.','.')."</strong></p>";
	}
}

}


?>
