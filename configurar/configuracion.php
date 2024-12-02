<?php
/*

	$usuario = 'root';
	$contrasena = '';
	$baseDeDatos = 'inventario';


	$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos); 
	$conexion->set_charset('utf8'); 

	if ($conexion->connect_error) {
	die('Error de conexion: ' . $conexion->connect_error);
	}

	date_default_timezone_set('America/Manaus');
	session_start();
	error_reporting(0);



	function contar($condicion){
		global $conexion;

		//$condicion = "SELECT count(*) FROM $table WHERE $condicion";
		$stmt = $conexion->prepare($condicion);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_row();
		$galTotal = $row[0];

		return $galTotal;
	}
*/


/*
	$usuario = 'userseller';
	$contrasena = 'B9f(FbTR=sMd';
	$baseDeDatos = 'iseller';
*/

$usuario = 'root';
$contrasena = '';
$baseDeDatos = 'iseller';



$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
	die('Error de conexion: ' . $conexion->connect_error);
}

date_default_timezone_set('America/Manaus');
session_start();
error_reporting(0);



function contar($condicion)
{
	global $conexion;

	//$condicion = "SELECT count(*) FROM $table WHERE $condicion";
	$stmt = $conexion->prepare($condicion);
	$stmt->execute();
	$row = $stmt->get_result()->fetch_row();
	$galTotal = $row[0];

	return $galTotal;
}


function formatPeso($amount)
{
	// Redondear a la centena más cercana
	$roundedAmount = round($amount / 100) * 100;

	// Convertir el número a un formato con separadores de miles
	return number_format($roundedAmount);
}
