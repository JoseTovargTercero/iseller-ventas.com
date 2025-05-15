<?php
/*
$usuario = 'userseller';
$contrasena = 'B9f(FbTR=sMd';
$baseDeDatos = 'iseller';

DB_USER=userseller
DB_PASS=B9f(FbTR=sMd
DB_NAME=iseller
API_KEY=afa5859e067e3a9f96886ebc

*/

//$usuario = 'root';
$contrasena = '';
//$baseDeDatos = 'iseller';


function cargarDotEnv($ruta)
{
    if (!file_exists($ruta)) {
        echo error_log("Archivo .env no encontrado en $ruta");
        return;
    }

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

cargarDotEnv(dirname(__DIR__) . '/env');
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$baseDeDatos = $_ENV['DB_NAME'];
$apiKey = $_ENV['API_KEY'];

$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
    die('Error de conexion: ' . $conexion->connect_error);
}

$internetError = false;

$stmt = $conexion->prepare("SELECT time FROM cambios_bcv_historico ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$ultima_actualizacion = new DateTime(
    $row['time'] ?? '2022-01-01 05:54:54'
);

$stmt->close();

// Obtener el valor
function obtenerTasaDeApi(&$internetError)
{
    // $api_key = "afa5859e067e3a9f96886ebc";
    global $apiKey;
    $url = "https://v6.exchangerate-api.com/v6/$apiKey/pair/USD/VES";

    try {
        $response = @file_get_contents($url);
        if ($response === false) {
            $internetError = true;
            return 0;
        }
        $data = json_decode($response, true);
        return $data['conversion_rate'];
    } catch (Exception $e) {
        $internetError = true;
        return 0;
    }
}

$tasa = obtenerTasaDeApi($internetError);

if ($tasa <= 0 || $internetError) {
    error_log("No se pudo obtener la tasa desde la API.");
    exit;
}

// Actualiza el Historico
$stmt2 = $conexion->prepare("INSERT INTO cambios_bcv_historico (valor) VALUES (?)");
$stmt2->bind_param("s", $tasa);
$stmt2->execute();
$stmt2->close();


// aolicar margen y redondeo
function aplicarMargenYRedondeo(float $tasa_base, int $tipo_tasa_bs, ?float $margen_neto, int $redondeo): float
{
    if ($tipo_tasa_bs === 3) {
        if (!empty($margen_neto)) {
            $tasa_base += $margen_neto;
        }
        if ($redondeo === 1) {
            $tasa_base = round($tasa_base, 0, PHP_ROUND_HALF_UP);
        } elseif ($redondeo === 2) {
            $tasa_base = round($tasa_base, 0, PHP_ROUND_HALF_DOWN);
        }
    }
    return $tasa_base;
}



$stmt2 = $conexion->prepare("UPDATE `cambio` SET `DolarBolivar`= ? WHERE id= ? ");

// consultar cambios de los usuarios
$stmt = mysqli_prepare($conexion, "SELECT * FROM `cambio` WHERE tipo_tasa_bs = '3' OR tipo_tasa_bs = '2' ");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id_r = $row['id'];
        $bs_tasa = aplicarMargenYRedondeo($tasa, $row['tipo_tasa_bs'], $row['margen_neto'], $row['redondeo']);

        $stmt2->bind_param("di", $bs_tasa, $id_r);
        if (!$stmt2->execute()) {
            error_log("Error al insertar en cambios_bcv_historico: $id_r" . $stmt2->error);
        }
    }
}
$stmt->close();
$stmt2->close();
