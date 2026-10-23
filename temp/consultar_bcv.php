<?php

function cargarDotEnv($ruta)
{
    if (!file_exists($ruta)) {
        error_log("Archivo .env no encontrado en $ruta");
        return;
    }

    $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue; // Ignorar comentarios
        if (strpos($linea, '=') === false) continue;

        list($nombre, $valor) = explode('=', $linea, 2);
        $nombre = trim($nombre);
        $valor = trim($valor);

        // No sobrescribe variables ya definidas
        if (!isset($_ENV[$nombre])) {
            $_ENV[$nombre] = $valor;
        }
    }
}

// Busca .env en la carpeta del script, la del proyecto o dos niveles arriba.
$envFile = null;
foreach ([__DIR__, dirname(__DIR__), dirname(__DIR__, 2), dirname(__DIR__, 3)] as $dir) {
    foreach (['.env', 'env'] as $nombre) {
        $candidato = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $nombre;
        if (is_file($candidato) && is_readable($candidato)) {
            $envFile = $candidato;
            break 2;
        }
    }
}
if ($envFile === null) {
    fwrite(STDERR, "No se encontró el archivo .env" . PHP_EOL);
    exit(1);
}
cargarDotEnv($envFile);

if (!isset($_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME'])) {
    fwrite(STDERR, "Faltan DB_USER, DB_PASS o DB_NAME en el .env" . PHP_EOL);
    exit(1);
}

$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$baseDeDatos = $_ENV['DB_NAME'];
$apiKey = $_ENV['API_KEY'] ?? '';

mysqli_report(MYSQLI_REPORT_OFF);
$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
    fwrite(STDERR, 'Error de conexión: ' . $conexion->connect_error . PHP_EOL);
    exit(1);
}

// Modo de verificación: no consulta el BCV ni modifica datos.
if (in_array('--check', $argv ?? [], true)) {
    $check = $conexion->query("SELECT DATABASE() AS db, COUNT(*) AS historico FROM cambios_bcv_historico");
    $data = $check ? $check->fetch_assoc() : ['db' => null, 'historico' => null];
    echo 'DB OK: ' . json_encode($data) . PHP_EOL;
    exit(0);
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

// 1. Desactivar temporalmente la verificación estricta de SSL para esta petición
// Recuerda que el BCV suele fallar con sus certificados.
$contexto = stream_context_create([
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ],
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n"
    ]
]);

$url = "https://www.bcv.org.ve/";

// 2. Descargar el HTML de la página
$html = @file_get_contents($url, false, $contexto);

if ($html === false) {
   $internetError = true;

   return false;
}

// 3. Cargar el HTML en el parser de PHP
$doc = new DOMDocument();
// El @ evita que PHP lance advertencias por errores de HTML interno del BCV
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

// 4. Buscar los contenedores por su ID usando XPath
// En el HTML del BCV, el valor está dentro de un <strong> dentro del div #dolar o #euro
$query_usd = '//div[@id="dolar"]//strong';
$query_eur = '//div[@id="euro"]//strong';

$node_usd = $xpath->query($query_usd);
$node_eur = $xpath->query($query_eur);

$tasas = ['USD' => null, 'EUR' => null];

if ($node_usd->length > 0) {
    $valor_usd = trim($node_usd->item(0)->nodeValue);
    // Convertir formato coma (45,50) a punto (45.50)
    $tasas['USD'] = (float)str_replace(',', '.', $valor_usd);
}

if ($node_eur->length > 0) {
    $valor_eur = trim($node_eur->item(0)->nodeValue);
    $tasas['EUR'] = (float)str_replace(',', '.', $valor_eur);
}

// 5. PROCESAR LOS DATOS
if ($tasas['USD']) {
   $internetError = false;

  return $tasas['USD'];
} else {
   $internetError = true;
   return false;
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

