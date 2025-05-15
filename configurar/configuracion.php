<?php
if ($_SERVER['SERVER_NAME'] == 'localhost') {
	$usuario = 'root';
	$contrasena = '';
	$baseDeDatos = 'iseller';
} elseif ($_SERVER['SERVER_NAME'] == 'iseller-tiendas.com') {
	$usuario = 'userseller';
	$contrasena = 'B9f(FbTR=sMd';
	$baseDeDatos = 'iseller';
}

$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
	die('Error de conexion: ' . $conexion->connect_error);
}

date_default_timezone_set('America/Manaus');
//error_reporting(0);


if (@$_SESSION["bss_id"]) {
	$bss_id = $_SESSION["bss_id"];
}

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
	$amount = (int) $amount;
	// Redondear a la centena más cercana
	$roundedAmount = round($amount / 100) * 100;

	// Convertir el número a un formato con separadores de miles
	return $roundedAmount;
}


function formatPesoVista($amount)
{
	// convierte $amount a un entero
	$amount = (int)$amount;

	// Eliminar comas y espacios, convertir a float
	$amount = floatval(str_replace(',', '', trim($amount)));

	// Redondear a la centena más cercana
	$roundedAmount = round($amount / 100) * 100;

	// Formatear con separadores de miles

	return $roundedAmount;
}
