<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin sesión']);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$action = $_GET['action'] ?? '';
$conexion->set_charset('utf8mb4');
header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($action !== 'listar') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Acción no válida']);
    exit;
}

function tabla_existe($conexion, string $tabla): bool
{
    $res = $conexion->query("SHOW TABLES LIKE '" . $conexion->real_escape_string($tabla) . "'");
    return $res && $res->num_rows > 0;
}

if (!tabla_existe($conexion, 'transportista_servicios')) {
    echo json_encode(['ok' => true, 'data' => []]);
    exit;
}

$tieneUbicaciones = tabla_existe($conexion, 'transportista_ubicaciones');
$tieneOrigen = tabla_existe($conexion, 'tienda_orden_origen');

$joinUbicacion = '';
$colsUbicacion = "NULL AS ubic_lat, NULL AS ubic_lng, NULL AS ubic_precision, NULL AS ubic_creado_en";
if ($tieneUbicaciones) {
    $joinUbicacion = "
        LEFT JOIN transportista_ubicaciones ub ON ub.id = (
            SELECT ub2.id
              FROM transportista_ubicaciones ub2
             WHERE ub2.transportista_id = s.transportista_id
               AND (ub2.servicio_id = s.id OR ub2.numero_pedido = CAST(o.id AS CHAR))
             ORDER BY ub2.id DESC
             LIMIT 1
        )";
    $colsUbicacion = "ub.lat AS ubic_lat, ub.lng AS ubic_lng, ub.precision_m AS ubic_precision, ub.creado_en AS ubic_creado_en";
}

$joinOrigen = '';
$colsOrigen = "n.lat AS origen_lat, n.lng AS origen_lng, NULL AS origen_creado_en";
if ($tieneOrigen) {
    $joinOrigen = "LEFT JOIN tienda_orden_origen ori ON ori.orden_id = o.id";
    $colsOrigen = "COALESCE(ori.lat, n.lat) AS origen_lat,
                   COALESCE(ori.lng, n.lng) AS origen_lng,
                   ori.creado_en AS origen_creado_en";
}

$sql = "SELECT o.id, o.status, o.created, o.cliente,
               u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
               d.direccion, d.referencia,
               s.id AS servicio_id, s.estado AS servicio_estado,
               s.transportista_id,
               t.nombre AS transportista_nombre, t.telefono AS transportista_telefono,
               $colsUbicacion, $colsOrigen
          FROM tienda_ordenes o
          LEFT JOIN tienda_usuarios u ON u.id = o.customer_id
          LEFT JOIN tienda_usuarios_direcciones d ON d.id = o.direccion_id
          LEFT JOIN negocio n ON n.id = o.bss_id
          LEFT JOIN transportista_servicios s
                 ON s.tipo = 'delivery' AND s.bss_id = o.bss_id
                AND s.numero_pedido = CAST(o.id AS CHAR)
          LEFT JOIN transportistas t ON t.id = s.transportista_id
          $joinOrigen
          $joinUbicacion
         WHERE o.bss_id = ?
           AND o.tipo_entrega = 'delivery'
           AND o.status IN (2, 6, 3)
         ORDER BY o.id DESC
         LIMIT 200";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'msg' => 'No se pudo consultar el seguimiento']);
    exit;
}
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$data = [];
foreach ($filas as $fila) {
    $ubicacion = null;
    if ($fila['ubic_lat'] !== null && $fila['ubic_lng'] !== null) {
        $ubicacion = [
            'lat'         => (float)$fila['ubic_lat'],
            'lng'         => (float)$fila['ubic_lng'],
            'precision_m' => $fila['ubic_precision'] !== null ? (float)$fila['ubic_precision'] : null,
            'creado_en'   => (string)$fila['ubic_creado_en'],
        ];
    }

    $origen = null;
    if ($fila['origen_lat'] !== null && $fila['origen_lng'] !== null) {
        $origenLat = (float)$fila['origen_lat'];
        $origenLng = (float)$fila['origen_lng'];
        if ($origenLat !== 0.0 || $origenLng !== 0.0) {
            $origen = [
                'lat'       => $origenLat,
                'lng'       => $origenLng,
                'creado_en' => $fila['origen_creado_en'] !== null ? (string)$fila['origen_creado_en'] : null,
            ];
        }
    }

    $data[] = [
        'id'                     => (int)$fila['id'],
        'status'                 => (int)$fila['status'],
        'created'                => (string)$fila['created'],
        'cliente'                => (string)($fila['cliente_nombre'] ?: ($fila['cliente'] ?? '')),
        'cliente_telefono'       => (string)($fila['cliente_telefono'] ?? ''),
        'direccion'              => (string)($fila['direccion'] ?? ''),
        'referencia'             => (string)($fila['referencia'] ?? ''),
        'transportista_id'       => $fila['transportista_id'] !== null ? (int)$fila['transportista_id'] : null,
        'transportista_nombre'   => (string)($fila['transportista_nombre'] ?? ''),
        'transportista_telefono' => (string)($fila['transportista_telefono'] ?? ''),
        'servicio_estado'        => (string)($fila['servicio_estado'] ?? ''),
        'ubicacion'              => $ubicacion,
        'origen'                 => $origen,
    ];
}

echo json_encode(['ok' => true, 'data' => $data]);
