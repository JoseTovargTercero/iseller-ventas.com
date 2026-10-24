<?php
/**
 * API — Información general de la tienda  →  GET /api/transportista_tienda.php?bss_id=3
 *
 * Solo el transportista que tenga algún servicio de esa tienda puede consultarla.
 */

require_once __DIR__ . '/transportista_comun.php';

exigirMetodo('GET');
limitar('tienda', 60, 60);
exigirApiKey();
asegurarTablas($conexion);

$transportista = transportistaDesdeToken($conexion);
if (!$transportista) {
    salirJson(401, ['ok' => false, 'error' => 'Sesión no válida o expirada']);
}

$bssId = (int)($_GET['bss_id'] ?? 0);
if ($bssId <= 0) {
    salirJson(400, ['ok' => false, 'error' => 'Tienda inválida']);
}

$transportistaId = (int)$transportista['id'];
$stmt = $conexion->prepare(
    "SELECT 1 FROM transportista_servicios WHERE transportista_id = ? AND bss_id = ? LIMIT 1"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('ii', $transportistaId, $bssId);
$stmt->execute();
$tiene = $stmt->get_result()->num_rows > 0;
$stmt->close();
if (!$tiene) {
    salirJson(403, ['ok' => false, 'error' => 'No tienes servicios de esta tienda']);
}

$stmt = $conexion->prepare(
    "SELECT nombre, nombre_visible, eslogan, whatsapp, logo_url, comunidad,
            geo_estado, geo_municipio, horarios, reputacion_pct, categoria
       FROM negocio WHERE id = ? LIMIT 1"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('i', $bssId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    salirJson(404, ['ok' => false, 'error' => 'Tienda no encontrada']);
}

$nombre = trim((string)($row['nombre_visible'] ?: $row['nombre']));
$comunidad = trim((string)($row['comunidad'] ?? ''));
$municipio = trim((string)($row['geo_municipio'] ?? ''));
$estado = trim((string)($row['geo_estado'] ?? ''));
$direccion = $comunidad;
if ($municipio !== '' || $estado !== '') {
    $direccion .= ($direccion !== '' ? ' · ' : '') . trim($municipio . ($municipio !== '' && $estado !== '' ? ', ' : '') . $estado);
}

$logo = (string)($row['logo_url'] ?? '');
if ($logo !== '' && !preg_match('#^https?://#i', $logo)) {
    $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $appPath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/x.php'))), '/');
    if ($host !== '') {
        $logo = ($esHttps ? 'https' : 'http') . '://' . $host . $appPath . '/iseller_store/' . ltrim($logo, '/');
    }
}

salirJson(200, [
    'ok'             => true,
    'tienda'         => [
        'bss_id'         => $bssId,
        'nombre'         => $nombre,
        'eslogan'        => (string)($row['eslogan'] ?? ''),
        'whatsapp'       => (string)($row['whatsapp'] ?? ''),
        'direccion'      => $direccion,
        'comunidad'      => $comunidad,
        'municipio'      => $municipio,
        'estado'         => $estado,
        'categoria'      => (string)($row['categoria'] ?? ''),
        'logo_url'       => $logo,
        'horarios_texto' => formatearHorarios((string)($row['horarios'] ?? '')),
        'reputacion_pct' => $row['reputacion_pct'] !== null ? (float)$row['reputacion_pct'] : null,
    ],
]);

function formatearHorarios(string $json): string
{
    $datos = json_decode($json, true);
    if (!is_array($datos)) {
        return '';
    }
    $lineas = [];
    foreach (['tienda' => 'Tienda', 'delivery' => 'Delivery'] as $clave => $etiqueta) {
        if (empty($datos[$clave]) || !is_array($datos[$clave])) {
            continue;
        }
        $h = $datos[$clave];
        $dias = array_map('ucfirst', array_map('strval', (array)($h['dias'] ?? [])));
        $tipo = strtolower((string)($h['tipo'] ?? ''));
        $rango = '';
        if ($tipo === 'corrido') {
            $rango = trim((string)($h['h3'] ?? '') . '–' . (string)($h['h4'] ?? ''), '–');
        } else {
            $partes = [];
            if (!empty($h['h1']) || !empty($h['h2'])) {
                $partes[] = trim((string)($h['h1'] ?? '') . '–' . (string)($h['h2'] ?? ''), '–');
            }
            if (!empty($h['h3']) || !empty($h['h4'])) {
                $partes[] = trim((string)($h['h3'] ?? '') . '–' . (string)($h['h4'] ?? ''), '–');
            }
            $rango = implode(' y ', array_filter($partes));
        }
        $texto = $etiqueta . ': ';
        if ($dias) {
            $texto .= implode(', ', $dias);
        }
        if ($rango !== '') {
            $texto .= ($dias ? ' · ' : '') . $rango;
        }
        $lineas[] = $texto;
    }
    return implode("\n", $lineas);
}
