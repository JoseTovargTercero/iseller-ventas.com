<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");

///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////
if (isset($_POST['rep'])) {
	$q = $conexion->real_escape_string($_POST['rep']);
	$query = "SELECT * FROM cambio WHERE id='1'";
}

$buscarAlumnos = $conexion->query($query);
if ($buscarAlumnos->num_rows > 0) {
	while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {


		$tabla = $q . " COP = <strong>" . $q / $filaAlumnos['pesoDolar'] . "</strong> USD";
	}
} else {

	$tabla = 'Sin valor de entrada';
}


echo $tabla;
