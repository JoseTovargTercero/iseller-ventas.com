<?php
function cargarDotEnv($ruta)
{
	// Construir rutas posibles
	$archivoEnv = rtrim($ruta, '/') . '/.env';
	$archivoAlt = rtrim($ruta, '/') . '/env';

	// Verificar cuál existe
	if (file_exists($archivoEnv)) {
		$archivo = $archivoEnv;
	} elseif (file_exists($archivoAlt)) {
		$archivo = $archivoAlt;
	} else {
		echo "Archivo .env o env no encontrado en $ruta";
		return;
	}

	$lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lineas as $linea) {
		if (strpos(trim($linea), '#') === 0) continue; // Ignorar comentarios

		list($nombre, $valor) = explode('=', $linea, 2);
		$nombre = trim($nombre);
		$valor = trim($valor);

		// No sobrescribe variables ya definidas
		if (!isset($_ENV[$nombre])) {
			$_ENV[$nombre] = $valor;
		}
	}
}

cargarDotEnv(dirname(__DIR__) . '/../../');
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$baseDeDatos = $_ENV['DB_NAME'];


$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
	die('Error de conexion: ' . $conexion->connect_error);
}

date_default_timezone_set('America/Manaus');
//error_reporting(0);



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);




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
