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

// Solicitudes abiertas: visibles para los transportistas vinculados al negocio.
$resTabla = $conexion->query("SHOW TABLES LIKE 'transportista_por_negocio'");
$conVinculo = $resTabla && $resTabla->num_rows > 0;
$condicion = $conVinculo
    ? "(s.transportista_id = ? OR (s.transportista_id IS NULL AND s.estado = 'esperando'
        AND EXISTS (SELECT 1 FROM transportista_por_negocio r
                     WHERE r.bss_id = s.bss_id AND r.transportista_id = ?)))"
    : "s.transportista_id = ?";

$stmt = $conexion->prepare(
    "SELECT s.id, s.tipo, s.bss_id, s.codigo_ruta, s.numero_pedido, s.cliente,
            s.direccion, s.telefono, s.lat, s.lng, s.estado, s.entregado_en, s.notas, s.creado_en,
            COALESCE(NULLIF(n.nombre_visible, ''), n.nombre, '') AS tienda_nombre
       FROM transportista_servicios s
       LEFT JOIN negocio n ON n.id = s.bss_id
      WHERE $condicion AND s.tipo = ?
      ORDER BY FIELD(s.estado, 'esperando', 'asignado', 'aceptado', 'en_camino', 'completado', 'cancelado', 'rechazado'),
               s.creado_en DESC
      LIMIT 200"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$id = (int)$transportista['id'];
if ($conVinculo) {
    $stmt->bind_param('iis', $id, $id, $tipo);
} else {
    $stmt->bind_param('is', $id, $tipo);
}
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
        'entregado_en'  => (string)($row['entregado_en'] ?? ''),
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
