<?php
/**
 * API - Ubicación del transportista  ->  POST /api/transportista_ubicacion.php
 *
 * La app la envía cada 2 minutos mientras el transportista tiene un servicio activo.
 * Cada envío queda como una fila del historial con hora, pedido y coordenadas.
 * Entrada: lat, lng, precision (m, opcional), rumbo (grados, opcional), servicio_id (opcional)
 * Autenticación: Bearer del transportista + X-Api-Key.
 */

require_once __DIR__ . '/transportista_comun.php';

exigirMetodo('POST');
limitar('ubicacion', 20, 300);
exigirApiKey();
asegurarTablas($conexion);

$transportista = transportistaDesdeToken($conexion);
if (!$transportista) {
    salirJson(401, ['ok' => false, 'error' => 'Sesión no válida o expirada']);
}

$in = entrada();
$lat = (float)($in['lat'] ?? 0);
$lng = (float)($in['lng'] ?? 0);
$precision = isset($in['precision']) ? (float)$in['precision'] : null;
$rumbo = isset($in['rumbo']) && $in['rumbo'] !== '' ? (float)$in['rumbo'] : null;
$servicioId = (int)($in['servicio_id'] ?? 0);
$transportistaId = (int)$transportista['id'];

if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat === 0.0 && $lng === 0.0)) {
    salirJson(400, ['ok' => false, 'error' => 'Ubicación inválida']);
}

// Pedido que está entregando: el servicio activo del transportista.
$numeroPedido = null;
if ($servicioId > 0) {
    $stmt = $conexion->prepare(
        "SELECT numero_pedido FROM transportista_servicios WHERE id = ? AND transportista_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $servicioId, $transportistaId);
} else {
    $stmt = $conexion->prepare(
        "SELECT id, numero_pedido FROM transportista_servicios
          WHERE transportista_id = ? AND estado IN ('asignado','aceptado','en_camino')
          ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param('i', $transportistaId);
}
$stmt->execute();
$servicio = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($servicio) {
    if (isset($servicio['id'])) {
        $servicioId = (int)$servicio['id'];
    }
    $numeroPedido = $servicio['numero_pedido'] !== null ? (string)$servicio['numero_pedido'] : null;
}

$stmt = $conexion->prepare(
    "INSERT INTO transportista_ubicaciones
        (transportista_id, servicio_id, numero_pedido, lat, lng, precision_m, rumbo, creado_en)
     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$servicioParam = $servicioId > 0 ? $servicioId : null;
$stmt->bind_param('iisdddd', $transportistaId, $servicioParam, $numeroPedido, $lat, $lng, $precision, $rumbo);
$stmt->execute();
$stmt->close();

// Historial acotado: se descartan las posiciones de más de 30 días.
$conexion->query("DELETE FROM transportista_ubicaciones WHERE creado_en < NOW() - INTERVAL 30 DAY");

salirJson(200, [
    'ok' => true,
    'servicio_id' => $servicioId,
    'numero_pedido' => $numeroPedido,
    'creado_en' => date('Y-m-d H:i:s'),
]);
