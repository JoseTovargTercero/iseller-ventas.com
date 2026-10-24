<?php
/**
 * API — Servicios asignados al transportista  →  GET /api/transportista_servicios.php
 *
 * Autenticación: Authorization: Bearer <token> + X-Api-Key.
 * Parámetro opcional: tipo=delivery|carrera (por defecto delivery).
 */

require_once __DIR__ . '/transportista_comun.php';

exigirMetodo('GET');
limitar('servicios', 120, 60);
exigirApiKey();
asegurarTablas($conexion);

$transportista = transportistaDesdeToken($conexion);
if (!$transportista) {
    salirJson(401, ['ok' => false, 'error' => 'Sesión no válida o expirada']);
}

$tipo = (($_GET['tipo'] ?? 'delivery') === 'carrera') ? 'carrera' : 'delivery';

$stmt = $conexion->prepare(
    "SELECT s.id, s.tipo, s.bss_id, s.codigo_ruta, s.numero_pedido, s.cliente,
            s.direccion, s.telefono, s.lat, s.lng, s.estado, s.notas, s.creado_en,
            COALESCE(NULLIF(n.nombre_visible, ''), n.nombre, '') AS tienda_nombre
       FROM transportista_servicios s
       LEFT JOIN negocio n ON n.id = s.bss_id
      WHERE s.transportista_id = ? AND s.tipo = ?
      ORDER BY FIELD(s.estado, 'asignado', 'aceptado', 'en_camino', 'completado', 'cancelado', 'rechazado'),
               s.creado_en DESC
      LIMIT 200"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$id = (int)$transportista['id'];
$stmt->bind_param('is', $id, $tipo);
$stmt->execute();
$res = $stmt->get_result();
$servicios = [];
while ($row = $res->fetch_assoc()) {
    $servicios[] = [
        'id'            => (int)$row['id'],
        'tipo'          => (string)$row['tipo'],
        'bss_id'        => (int)($row['bss_id'] ?? 0),
        'tienda_nombre' => (string)($row['tienda_nombre'] ?? ''),
        'codigo_ruta'   => (string)($row['codigo_ruta'] ?? ''),
        'numero_pedido' => (string)($row['numero_pedido'] ?? ''),
        'cliente'       => (string)($row['cliente'] ?? ''),
        'direccion'     => (string)($row['direccion'] ?? ''),
        'telefono'      => (string)($row['telefono'] ?? ''),
        'lat'           => $row['lat'] !== null ? (float)$row['lat'] : null,
        'lng'           => $row['lng'] !== null ? (float)$row['lng'] : null,
        'estado'        => (string)$row['estado'],
        'notas'         => (string)($row['notas'] ?? ''),
        'creado_en'     => (string)$row['creado_en'],
    ];
}
$stmt->close();

salirJson(200, [
    'ok'        => true,
    'servicios' => $servicios,
    'total'     => count($servicios),
]);
