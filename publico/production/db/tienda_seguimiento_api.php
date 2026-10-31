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

$stmt = $conexion->prepare(
    "SELECT o.id, o.status, o.created, o.cliente,
            u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
            d.direccion, d.referencia
       FROM tienda_ordenes o
       LEFT JOIN tienda_usuarios u ON u.id = o.customer_id
       LEFT JOIN tienda_usuarios_direcciones d ON d.id = o.direccion_id
      WHERE o.bss_id = ?
        AND o.tipo_entrega = 'delivery'
        AND o.status IN (2, 6, 3)
      ORDER BY o.id DESC
      LIMIT 200"
);
if (!$stmt) {
    error_log('seguimiento pedidos: ' . $conexion->error);
    echo json_encode(['ok' => false, 'msg' => 'No se pudo consultar el seguimiento']);
    exit;
}
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$tieneServicios = tabla_existe($conexion, 'transportista_servicios');
$tieneUbicaciones = tabla_existe($conexion, 'transportista_ubicaciones');
$tieneOrigen = tabla_existe($conexion, 'tienda_orden_origen');

$stmtServicio = null;
if ($tieneServicios) {
    $stmtServicio = $conexion->prepare(
        "SELECT s.id, s.estado, s.transportista_id,
                t.nombre AS transportista_nombre, t.telefono AS transportista_telefono
           FROM transportista_servicios s
           LEFT JOIN transportistas t ON t.id = s.transportista_id
          WHERE s.bss_id = ? AND s.tipo = 'delivery' AND s.numero_pedido = ?
          LIMIT 1"
    );
    if (!$stmtServicio) {
        error_log('seguimiento servicios: ' . $conexion->error);
    }
}

$stmtUbicacion = null;
if ($tieneUbicaciones) {
    $stmtUbicacion = $conexion->prepare(
        "SELECT lat, lng, precision_m, creado_en
           FROM transportista_ubicaciones
          WHERE transportista_id = ?
            AND (servicio_id = ? OR numero_pedido = ?)
          ORDER BY id DESC
          LIMIT 1"
    );
    if (!$stmtUbicacion) {
        error_log('seguimiento ubicaciones: ' . $conexion->error);
    }
}

$stmtOrigen = null;
if ($tieneOrigen) {
    $stmtOrigen = $conexion->prepare(
        "SELECT lat, lng, creado_en FROM tienda_orden_origen WHERE orden_id = ? LIMIT 1"
    );
    if (!$stmtOrigen) {
        error_log('seguimiento origen: ' . $conexion->error);
    }
}

$data = [];
foreach ($filas as $fila) {
    $ordenId = (int)$fila['id'];
    $numeroPedido = (string)$ordenId;
    $servicio = null;

    if ($stmtServicio) {
        $stmtServicio->bind_param('iis', $bss_id, $ordenId, $numeroPedido);
        $stmtServicio->execute();
        $resServicio = $stmtServicio->get_result();
        if ($resServicio) {
            $servicio = $resServicio->fetch_assoc();
            $resServicio->free();
        }
    }

    $ubicacion = null;
    if ($stmtUbicacion && $servicio && (int)$servicio['transportista_id'] > 0) {
        $transportistaId = (int)$servicio['transportista_id'];
        $servicioId = (int)$servicio['id'];
        $stmtUbicacion->bind_param('iis', $transportistaId, $servicioId, $numeroPedido);
        $stmtUbicacion->execute();
        $resUbicacion = $stmtUbicacion->get_result();
        $ubic = $resUbicacion ? $resUbicacion->fetch_assoc() : null;
        if ($resUbicacion) {
            $resUbicacion->free();
        }
        if ($ubic && $ubic['lat'] !== null && $ubic['lng'] !== null) {
            $ubicacion = [
                'lat'         => (float)$ubic['lat'],
                'lng'         => (float)$ubic['lng'],
                'precision_m' => $ubic['precision_m'] !== null ? (float)$ubic['precision_m'] : null,
                'creado_en'   => (string)$ubic['creado_en'],
            ];
        }
    }

    $origen = null;
    if ($stmtOrigen) {
        $stmtOrigen->bind_param('i', $ordenId);
        $stmtOrigen->execute();
        $resOrigen = $stmtOrigen->get_result();
        $ori = $resOrigen ? $resOrigen->fetch_assoc() : null;
        if ($resOrigen) {
            $resOrigen->free();
        }
        if ($ori && $ori['lat'] !== null && $ori['lng'] !== null) {
            $origenLat = (float)$ori['lat'];
            $origenLng = (float)$ori['lng'];
            if ($origenLat !== 0.0 || $origenLng !== 0.0) {
                $origen = [
                    'lat'       => $origenLat,
                    'lng'       => $origenLng,
                    'creado_en' => $ori['creado_en'] !== null ? (string)$ori['creado_en'] : null,
                ];
            }
        }
    }

    $data[] = [
        'id'                     => $ordenId,
        'status'                 => (int)$fila['status'],
        'created'                => (string)$fila['created'],
        'cliente'                => (string)($fila['cliente_nombre'] ?: ($fila['cliente'] ?? '')),
        'cliente_telefono'       => (string)($fila['cliente_telefono'] ?? ''),
        'direccion'              => (string)($fila['direccion'] ?? ''),
        'referencia'             => (string)($fila['referencia'] ?? ''),
        'transportista_id'       => $servicio ? (int)$servicio['transportista_id'] : null,
        'transportista_nombre'   => (string)($servicio['transportista_nombre'] ?? ''),
        'transportista_telefono' => (string)($servicio['transportista_telefono'] ?? ''),
        'servicio_estado'        => (string)($servicio['estado'] ?? ''),
        'ubicacion'              => $ubicacion,
        'origen'                 => $origen,
    ];
}

if ($stmtServicio) {
    $stmtServicio->close();
}
if ($stmtUbicacion) {
    $stmtUbicacion->close();
}
if ($stmtOrigen) {
    $stmtOrigen->close();
}

echo json_encode(['ok' => true, 'data' => $data]);
