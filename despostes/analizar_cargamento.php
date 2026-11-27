<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/GeminiCargamento.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ds_json(['error' => 'Método no permitido'], 405);
}

$raw = file_get_contents('php://input');
if ($raw === false || trim($raw) === '') {
    ds_json(['error' => 'Cuerpo de la solicitud vacío'], 400);
}
if (strlen($raw) > 200000) {
    ds_json(['error' => 'La solicitud es demasiado grande'], 413);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    ds_json(['error' => 'JSON inválido'], 400);
}

$device = trim((string) ($data['device_id'] ?? ''));
if (strlen($device) < 8) {
    ds_json(['error' => 'device_id requerido'], 400);
}

$cargamento = $data['cargamento'] ?? null;
if (!is_array($cargamento)) {
    ds_json(['error' => 'Falta el cargamento'], 400);
}

$resumen = $cargamento['resumen'] ?? null;
if (!is_array($resumen) || (int) ($resumen['despostadas'] ?? 0) <= 0) {
    ds_json(['error' => 'No hay despostes registrados para analizar'], 422);
}

// Límite simple por dispositivo: 60 solicitudes por hora.
$dir = ds_config()['data_dir'] . '/analisis';
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}
$limiteFile = $dir . '/limite_' . md5($device) . '.json';
$fp = @fopen($limiteFile, 'c+');
if ($fp && flock($fp, LOCK_EX)) {
    $marcas = json_decode((string) stream_get_contents($fp), true);
    if (!is_array($marcas)) {
        $marcas = [];
    }
    $ahora = time();
    $marcas = array_values(array_filter(
        $marcas,
        static fn($t) => is_int($t) && $t > $ahora - 3600
    ));
    if (count($marcas) >= 60) {
        flock($fp, LOCK_UN);
        fclose($fp);
        ds_json(['error' => 'Alcanzaste el límite de análisis por hora. Intenta más tarde.'], 429);
    }
    $marcas[] = $ahora;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($marcas));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
} elseif ($fp) {
    fclose($fp);
}

$forzar = isset($_GET['refresh']) && $_GET['refresh'] === '1';
$servicio = new GeminiCargamento();
$resultado = $servicio->analizar($cargamento, $forzar);
ds_json($resultado);
