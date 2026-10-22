<?php
if (!function_exists('cargarDotEnv')) {
	function cargarDotEnv($ruta)
	{
		// Busca .env (prioritario) y luego env en la raíz del proyecto, su carpeta
		// padre y la abuela. Así funciona igual en local (C:\xampp\.env) y en
		// hosting (/home/gitcomco/.env) sin depender del nivel de despliegue.
		$base = rtrim($ruta, '/\\');
		$dirs = [
			$base,
			dirname($base),
			dirname($base, 2),
		];

		$archivo = null;
		foreach (['.env', 'env'] as $nombre) {
			foreach ($dirs as $dir) {
				$candidato = $dir . DIRECTORY_SEPARATOR . $nombre;
				if (is_file($candidato) && is_readable($candidato)) {
					$archivo = $candidato;
					break 2;
				}
			}
		}

		if ($archivo === null) {
			error_log('cargarDotEnv: no se encontró .env/env en ' . implode(', ', $dirs));
			return false;
		}

		$lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!is_array($lineas)) {
			return false;
		}
		foreach ($lineas as $linea) {
			$linea = trim($linea);
			if ($linea === '' || strpos($linea, '#') === 0 || strpos($linea, '=') === false) {
				continue;
			}

			list($nombre, $valor) = explode('=', $linea, 2);
			$nombre = trim($nombre);
			$valor = trim($valor);

			// No sobrescribe variables ya definidas
			if ($nombre !== '' && !isset($_ENV[$nombre])) {
				$_ENV[$nombre] = $valor;
			}
		}

		return true;
	}
}

cargarDotEnv(dirname(__DIR__));
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$baseDeDatos = $_ENV['DB_NAME'];


$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
	die('Error de conexion: ' . $conexion->connect_error);
}

date_default_timezone_set('America/Manaus');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);




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
