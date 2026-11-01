<?php
/**
 * Seguimiento de un pedido delivery (Bearer).
 *
 * GET /iseller_store/api/v1/tracking.php?order_id=42
 *
 * Devuelve el origen (tienda), el destino (dirección), el transportista
 * asignado, su última ubicación y el recorrido reciente. Solo el dueño del
 * pedido puede consultarlo.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';

$user = api_auth();
$userId = (int)$user['id'];
global $conexion, $conexion_store;

api_method('GET');
api_rate_limit('tracking_get', 120, 60);

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    api_error(400, 'Pedido inválido', 'invalid_order');
}

$stmt = $conexion_store->prepare(
    "SELECT o.id, o.status, o.tipo_entrega, o.negocio_id, o.direccion_id,
            (SELECT c.estatus FROM tienda_compras_por_usuarios c
              WHERE c.compra_id = o.id AND c.usuario_id = o.customer_id
              ORDER BY c.id DESC LIMIT 1) AS estatus,
            COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) AS store_name,
            n.lat AS store_lat, n.lng AS store_lng, n.logo_url AS store_logo
       FROM tienda_ordenes o
       LEFT JOIN negocio n ON n.id = o.negocio_id
      WHERE o.id = ? AND o.customer_id = ?
      LIMIT 1"
);
$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$orden) {
    api_error(404, 'Pedido no encontrado', 'not_found');
}
if ((string)$orden['tipo_entrega'] !== 'delivery') {
    api_error(400, 'Este pedido no tiene entrega a domicilio', 'not_delivery');
}

$status = ((int)$orden['status'] === 6)
    ? 'transportista_asignado'
    : (string)($orden['estatus'] ?? 'pendiente');

function api_tabla_existe(mysqli $conexion, string $tabla): bool
{
    $res = $conexion->query("SHOW TABLES LIKE '" . $conexion->real_escape_string($tabla) . "'");
    return $res && $res->num_rows > 0;
}

$store = [
    'name' => (string)($orden['store_name'] ?? ''),
    'lat'  => $orden['store_lat'] !== null ? (float)$orden['store_lat'] : null,
    'lng'  => $orden['store_lng'] !== null ? (float)$orden['store_lng'] : null,
    'logo_url' => api_image(
        trim((string)($orden['store_logo'] ?? '')) !== ''
            ? (string)$orden['store_logo']
            : 'assets/img/logo-tienda.png',
        160,
        78
    ),
];

// Origen: punto guardado al pasar el pedido a enviado; si no, la tienda.
$origin = null;
if (api_tabla_existe($conexion_store, 'tienda_orden_origen')) {
    $stmt = $conexion_store->prepare(
        "SELECT lat, lng, creado_en FROM tienda_orden_origen WHERE orden_id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($fila && $fila['lat'] !== null && $fila['lng'] !== null
        && ((float)$fila['lat'] !== 0.0 || (float)$fila['lng'] !== 0.0)) {
        $origin = [
            'lat'        => (float)$fila['lat'],
            'lng'        => (float)$fila['lng'],
            'created_at' => (string)($fila['creado_en'] ?? ''),
        ];
    }
}
if ($origin === null && $store['lat'] !== null && $store['lng'] !== null
    && ($store['lat'] !== 0.0 || $store['lng'] !== 0.0)) {
    $origin = [
        'lat'        => $store['lat'],
        'lng'        => $store['lng'],
        'created_at' => '',
    ];
}

$destination = null;
$direccionId = (int)($orden['direccion_id'] ?? 0);
if ($direccionId > 0) {
    $stmt = $conexion_store->prepare(
        "SELECT direccion, referencia, lat, lng FROM tienda_usuarios_direcciones
          WHERE id = ? AND usuario_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $direccionId, $userId);
    $stmt->execute();
    $dir = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($dir) {
        $destination = [
            'address'   => (string)($dir['direccion'] ?? ''),
            'reference' => (string)($dir['referencia'] ?? ''),
            'lat'       => $dir['lat'] !== null ? (float)$dir['lat'] : null,
            'lng'       => $dir['lng'] !== null ? (float)$dir['lng'] : null,
        ];
    }
}

$carrier = null;
$serviceId = 0;
$location = null;
$history = [];
if (api_tabla_existe($conexion_store, 'transportista_servicios')) {
    $stmt = $conexion_store->prepare(
        "SELECT s.id, s.estado, s.transportista_id, t.nombre, t.telefono
           FROM transportista_servicios s
           LEFT JOIN transportistas t ON t.id = s.transportista_id
          WHERE s.bss_id = ? AND s.tipo = 'delivery' AND s.numero_pedido = ?
          ORDER BY s.id DESC LIMIT 1"
    );
    $bssId = (int)$orden['negocio_id'];
    $numero = (string)$orderId;
    $stmt->bind_param('is', $bssId, $numero);
    $stmt->execute();
    $servicio = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($servicio) {
        $serviceId = (int)$servicio['id'];
        $carrier = [
            'id'             => (int)$servicio['transportista_id'],
            'name'           => (string)($servicio['nombre'] ?? ''),
            'phone'          => (string)($servicio['telefono'] ?? ''),
            'service_status' => (string)($servicio['estado'] ?? ''),
        ];
    }
}

if ($carrier !== null && $carrier['id'] > 0
    && api_tabla_existe($conexion_store, 'transportista_ubicaciones')) {
    $stmt = $conexion_store->prepare(
        "SELECT lat, lng, precision_m, creado_en
           FROM transportista_ubicaciones
          WHERE transportista_id = ?
            AND (servicio_id = ? OR numero_pedido = ?)
          ORDER BY id DESC LIMIT 100"
    );
    $transportistaId = (int)$carrier['id'];
    $stmt->bind_param('iis', $transportistaId, $serviceId, $numero);
    $stmt->execute();
    $res = $stmt->get_result();
    $puntos = [];
    while ($fila = $res->fetch_assoc()) {
        if ($fila['lat'] === null || $fila['lng'] === null) continue;
        $punto = [
            'lat'        => (float)$fila['lat'],
            'lng'        => (float)$fila['lng'],
            'accuracy'   => $fila['precision_m'] !== null ? (float)$fila['precision_m'] : null,
            'created_at' => (string)($fila['creado_en'] ?? ''),
        ];
        $puntos[] = $punto;
    }
    $stmt->close();
    if ($puntos) {
        $location = $puntos[0];
        $history = array_reverse($puntos);
    }
}

api_ok([
    'order_id'      => $orderId,
    'status'        => $status,
    'delivery_type' => 'delivery',
    'store'         => $store,
    'origin'        => $origin,
    'destination'   => $destination,
    'carrier'       => $carrier,
    'location'      => $location,
    'history'       => $history,
    'updated_at'    => date('c'),
]);
