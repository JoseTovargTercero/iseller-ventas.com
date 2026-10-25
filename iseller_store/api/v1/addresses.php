<?php
/**
 * GET  /iseller_store/api/v1/addresses.php        → direcciones del cliente
 * POST /iseller_store/api/v1/addresses.php        → crea una dirección
 *   { name, phone, address, reference?, state, municipality, community?,
 *     lat?, lng?, is_primary? }
 */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';
require_once __DIR__ . '/../../core/venezuela_geo.php';

$user = api_auth();
global $conexion_store;

function api_address_public(array $d): array
{
    return [
        'id'          => (int)$d['id'],
        'name'        => (string)($d['nombre_receptor'] ?? ''),
        'phone'       => (string)($d['telefono'] ?? ''),
        'address'     => (string)($d['direccion'] ?? ''),
        'reference'   => (string)($d['referencia'] ?? ''),
        'state'       => (string)($d['geo_estado'] ?? ''),
        'municipality'=> (string)($d['geo_municipio'] ?? ''),
        'community'   => (string)($d['comunidad'] ?? ''),
        'lat'         => $d['lat'] !== null ? (float)$d['lat'] : null,
        'lng'         => $d['lng'] !== null ? (float)$d['lng'] : null,
        'is_primary'  => (int)($d['es_principal'] ?? 0) === 1,
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    api_method('GET');
    api_rate_limit('addresses_get', 120, 60);
    $stmt = $conexion_store->prepare(
        "SELECT * FROM tienda_usuarios_direcciones
         WHERE usuario_id = ? ORDER BY es_principal DESC, creado_en DESC"
    );
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($fila = $res->fetch_assoc()) {
        $items[] = api_address_public($fila);
    }
    $stmt->close();
    api_ok(['items' => $items]);
}

api_method('POST');
api_rate_limit('addresses_post', 30, 300);
$in = api_body();

$nombre = api_str($in, 'name', 100);
$telefono = api_str($in, 'phone', 20);
$direccion = api_str($in, 'address', 500);
$referencia = api_str($in, 'reference', 300);
$comunidad = api_str($in, 'community', 100);
$estado = api_str($in, 'state', 100);
$municipio = api_str($in, 'municipality', 150);
$esPrincipal = !empty($in['is_primary']);

if (mb_strlen($nombre) < 2) {
    api_error(400, 'Escribe el nombre de quien recibe', 'invalid_name');
}
if (!preg_match('/^[0-9+()\-.\s]{5,20}$/', $telefono)) {
    api_error(400, 'Teléfono inválido', 'invalid_phone');
}
if (mb_strlen($direccion) < 5) {
    api_error(400, 'Escribe la dirección', 'invalid_address');
}

$geo = venezuela_estados_municipios();
if (!isset($geo[$estado]) || !in_array($municipio, $geo[$estado], true)) {
    api_error(400, 'Selecciona estado y municipio', 'invalid_geo');
}

$lat = $in['lat'] ?? null;
$lng = $in['lng'] ?? null;
if ($lat !== null || $lng !== null) {
    if (!is_numeric($lat) || !is_numeric($lng)) {
        api_error(400, 'Ubicación inválida', 'invalid_location');
    }
    $lat = (float)$lat;
    $lng = (float)$lng;
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        api_error(400, 'Ubicación fuera de rango', 'invalid_location');
    }
} else {
    $lat = null;
    $lng = null;
}

$conexion_store->begin_transaction();
try {
    if ($esPrincipal) {
        $stmt = $conexion_store->prepare("UPDATE tienda_usuarios_direcciones SET es_principal = 0 WHERE usuario_id = ?");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conexion_store->prepare("SELECT COUNT(*) AS n FROM tienda_usuarios_direcciones WHERE usuario_id = ?");
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $cantidad = (int)($stmt->get_result()->fetch_assoc()['n'] ?? 0);
        $stmt->close();
        $esPrincipal = $cantidad === 0;
    }

    $principalInt = $esPrincipal ? 1 : 0;
    $stmt = $conexion_store->prepare(
        "INSERT INTO tienda_usuarios_direcciones
            (usuario_id, nombre_receptor, telefono, direccion, referencia, geo_estado, geo_municipio,
             lat, lng, comunidad, delivery_gratis_confirmado, es_principal)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)"
    );
    $stmt->bind_param(
        'issssssddsi',
        $user['id'], $nombre, $telefono, $direccion, $referencia, $estado, $municipio,
        $lat, $lng, $comunidad, $principalInt
    );
    if (!$stmt->execute()) {
        throw new RuntimeException('insert');
    }
    $id = (int)$stmt->insert_id;
    $stmt->close();
    $conexion_store->commit();
} catch (Throwable $e) {
    $conexion_store->rollback();
    error_log('API addresses: ' . $e->getMessage());
    api_error(500, 'No se pudo guardar la dirección');
}

$stmt = $conexion_store->prepare("SELECT * FROM tienda_usuarios_direcciones WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$fila) {
    api_error(500, 'No se pudo leer la dirección guardada');
}
api_ok(api_address_public($fila), ['message' => 'Dirección guardada']);
