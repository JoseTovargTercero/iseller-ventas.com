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

cargarDotEnv(dirname(__DIR__, 2));
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$baseDeDatos = $_ENV['DB_NAME'];
$baseDeDatos_store = isset($_ENV['DB_NAME_STORE']) ? $_ENV['DB_NAME_STORE'] : $_ENV['DB_NAME'];


mysqli_report(MYSQLI_REPORT_OFF);
$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
	die('Error de conexion');
}



$conexion_store = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion_store->set_charset('utf8');

if ($conexion_store->connect_error) {
	die('Error de conexion_store');
}

date_default_timezone_set('America/Manaus');
//error_reporting(0);


	$bss_id = 3;
	$enable_rewards = false; // Desactivado por defecto (Multitienda)


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
