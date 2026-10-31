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
$debug = isset($_GET['debug']) && (int)($_SESSION['nivel'] ?? 0) === 1;
$conexion->set_charset('utf8mb4');
header('Content-Type: application/json');
header('Cache-Control: no-store');

set_exception_handler(function (Throwable $e) use ($debug) {
    error_log('seguimiento fatal: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error interno en el seguimiento',
        'debug' => $debug ? $e->getMessage() : null,
    ]);
});

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

$res = $conexion->query(
    "SELECT o.id, o.status, o.created, o.cliente,
            u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
            d.direccion, d.referencia, d.lat AS destino_lat, d.lng AS destino_lng
       FROM tienda_ordenes o
       LEFT JOIN tienda_usuarios u ON u.id = o.customer_id
       LEFT JOIN tienda_usuarios_direcciones d ON d.id = o.direccion_id
      WHERE o.bss_id = $bss_id
        AND o.tipo_entrega = 'delivery'
        AND o.status IN (2, 6, 3)
      ORDER BY o.id DESC
      LIMIT 200"
);
if (!$res) {
    error_log('seguimiento pedidos: ' . $conexion->error);
    echo json_encode([
        'ok' => false,
        'msg' => 'No se pudo consultar el seguimiento',
        'debug' => $debug ? $conexion->error : null,
    ]);
    exit;
}

$filas = [];
while ($fila = $res->fetch_assoc()) {
    $filas[] = $fila;
}
$res->free();

$tieneServicios = tabla_existe($conexion, 'transportista_servicios');
$tieneUbicaciones = tabla_existe($conexion, 'transportista_ubicaciones');
$tieneOrigen = tabla_existe($conexion, 'tienda_orden_origen');

$data = [];
$errores = [];

foreach ($filas as $fila) {
    $ordenId = (int)$fila['id'];
    $servicio = null;

    if ($tieneServicios) {
        $resServicio = $conexion->query(
            "SELECT s.id, s.estado, s.transportista_id,
                    t.nombre AS transportista_nombre, t.telefono AS transportista_telefono
               FROM transportista_servicios s
               LEFT JOIN transportistas t ON t.id = s.transportista_id
              WHERE s.bss_id = $bss_id AND s.tipo = 'delivery' AND s.numero_pedido = '$ordenId'
              LIMIT 1"
        );
        if ($resServicio) {
            $servicio = $resServicio->fetch_assoc();
            $resServicio->free();
        } else {
            $errores[] = 'servicios: ' . $conexion->error;
        }
    }

    $ubicacion = null;
    if ($tieneUbicaciones && $servicio && (int)$servicio['transportista_id'] > 0) {
        $transportistaId = (int)$servicio['transportista_id'];
        $servicioId = (int)$servicio['id'];
        $resUbicacion = $conexion->query(
            "SELECT lat, lng, precision_m, creado_en
               FROM transportista_ubicaciones
              WHERE transportista_id = $transportistaId
                AND (servicio_id = $servicioId OR numero_pedido = '$ordenId')
              ORDER BY id DESC
              LIMIT 1"
        );
        if ($resUbicacion) {
            $ubic = $resUbicacion->fetch_assoc();
            $resUbicacion->free();
            if ($ubic && $ubic['lat'] !== null && $ubic['lng'] !== null) {
                $ubicacion = [
                    'lat'         => (float)$ubic['lat'],
                    'lng'         => (float)$ubic['lng'],
                    'precision_m' => $ubic['precision_m'] !== null ? (float)$ubic['precision_m'] : null,
                    'creado_en'   => (string)$ubic['creado_en'],
                ];
            }
        } else {
            $errores[] = 'ubicaciones: ' . $conexion->error;
        }
    }

    $origen = null;
    if ($tieneOrigen) {
        $resOrigen = $conexion->query(
            "SELECT lat, lng, creado_en FROM tienda_orden_origen WHERE orden_id = $ordenId LIMIT 1"
        );
        if ($resOrigen) {
            $ori = $resOrigen->fetch_assoc();
            $resOrigen->free();
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
        } else {
            $errores[] = 'origen: ' . $conexion->error;
        }
    }

    $destino = null;
    if ($fila['destino_lat'] !== null && $fila['destino_lng'] !== null) {
        $destinoLat = (float)$fila['destino_lat'];
        $destinoLng = (float)$fila['destino_lng'];
        if ($destinoLat !== 0.0 || $destinoLng !== 0.0) {
            $destino = ['lat' => $destinoLat, 'lng' => $destinoLng];
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
        'destino'                => $destino,
        'transportista_id'       => $servicio ? (int)$servicio['transportista_id'] : null,
        'transportista_nombre'   => (string)($servicio['transportista_nombre'] ?? ''),
        'transportista_telefono' => (string)($servicio['transportista_telefono'] ?? ''),
        'servicio_estado'        => (string)($servicio['estado'] ?? ''),
        'ubicacion'              => $ubicacion,
        'origen'                 => $origen,
    ];
}

if ($errores) {
    error_log('seguimiento: ' . implode(' | ', $errores));
}

$salida = ['ok' => true, 'data' => $data];
if ($debug && $errores) {
    $salida['debug'] = $errores;
}
echo json_encode($salida);
