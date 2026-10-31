<?php
/**
 * Relación transportista ↔ negocio para la asignación de deliveries.
 *
 * La tabla se crea sola para no depender de una migración manual en cada
 * hosting, igual que hace api/transportista_comun.php con las suyas.
 */

function transportista_negocio_asegurar($conexion): void
{
    static $hecho = false;
    if ($hecho) {
        return;
    }
    $hecho = true;

    $conexion->query(
        "CREATE TABLE IF NOT EXISTS transportista_por_negocio (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            transportista_id INT UNSIGNED NOT NULL,
            bss_id INT UNSIGNED NOT NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_transportista_negocio (bss_id, transportista_id),
            KEY idx_tpn_transportista (transportista_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function transportista_negocio_tabla_existe($conexion): bool
{
    $res = $conexion->query("SHOW TABLES LIKE 'transportistas'");
    return $res && $res->num_rows > 0;
}

/** Transportistas asociados al negocio, con sus datos de la tabla global. */
function transportista_negocio_listar($conexion, int $bssId): array
{
    if (!transportista_negocio_tabla_existe($conexion)) {
        return [];
    }
    transportista_negocio_asegurar($conexion);

    $stmt = $conexion->prepare(
        "SELECT t.id, t.nombre, t.cedula, t.telefono, t.activo, t.deliverys, t.carreras, r.creado_en
           FROM transportista_por_negocio r
           INNER JOIN transportistas t ON t.id = r.transportista_id
          WHERE r.bss_id = ?
          ORDER BY t.nombre ASC"
    );
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Transportista actualmente asignado a un pedido delivery, o null si no hay. */
function transportista_servicio_actual($conexion, int $bssId, int $ordenId): ?array
{
    $res = $conexion->query("SHOW TABLES LIKE 'transportista_servicios'");
    if (!$res || $res->num_rows === 0) {
        return null;
    }

    $stmt = $conexion->prepare(
        "SELECT s.transportista_id, s.estado, t.nombre
           FROM transportista_servicios s
           LEFT JOIN transportistas t ON t.id = s.transportista_id
          WHERE s.tipo = 'delivery' AND s.numero_pedido = ? AND s.bss_id = ?
          LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    $numeroPedido = (string)$ordenId;
    $stmt->bind_param('si', $numeroPedido, $bssId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

/**
 * Crea o reasigna el servicio delivery del pedido. El upsert usa la clave
 * única (tipo, numero_pedido), así que sirve tanto para asignar como para cambiar.
 */
function transportista_asignar_delivery($conexion, int $bssId, int $ordenId, int $transportistaId, string $notas = 'Asignado desde el panel'): bool
{
    $stmt = $conexion->prepare(
        "SELECT o.cliente,
                u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
                d.direccion, d.telefono AS dir_telefono, d.lat, d.lng
           FROM tienda_ordenes o
           LEFT JOIN tienda_usuarios u ON u.id = o.customer_id
           LEFT JOIN tienda_usuarios_direcciones d ON d.id = o.direccion_id
          WHERE o.id = ? AND o.bss_id = ?
          LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ii', $ordenId, $bssId);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$detalle) {
        return false;
    }

    $cliente = trim((string)($detalle['cliente_nombre'] ?? ''));
    if ($cliente === '') {
        $cliente = (string)($detalle['cliente'] ?? '');
    }
    $direccion = (string)($detalle['direccion'] ?? '');
    $telefono = (string)($detalle['cliente_telefono'] ?? '');
    if ($telefono === '') {
        $telefono = (string)($detalle['dir_telefono'] ?? '');
    }
    $lat = ($detalle['lat'] !== null && $detalle['lat'] !== '') ? (float)$detalle['lat'] : null;
    $lng = ($detalle['lng'] !== null && $detalle['lng'] !== '') ? (float)$detalle['lng'] : null;
    $numeroPedido = (string)$ordenId;

    $stmt = $conexion->prepare(
        "INSERT INTO transportista_servicios
            (transportista_id, tipo, bss_id, numero_pedido, cliente, direccion, telefono,
             lat, lng, estado, notas)
         VALUES (?, 'delivery', ?, ?, ?, ?, ?, ?, ?, 'asignado', ?)
         ON DUPLICATE KEY UPDATE
            transportista_id = VALUES(transportista_id),
            bss_id = VALUES(bss_id),
            cliente = VALUES(cliente),
            direccion = VALUES(direccion),
            telefono = VALUES(telefono),
            lat = VALUES(lat),
            lng = VALUES(lng),
            estado = 'asignado',
            notas = VALUES(notas),
            actualizado_en = NOW()"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iissssdds', $transportistaId, $bssId, $numeroPedido, $cliente, $direccion, $telefono, $lat, $lng, $notas);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Rendimiento por transportista entre dos fechas (Y-m-d).
 *
 * Entregas: pedidos con status 4 cuya fecha de entrega cae en el rango.
 * Tiempo: desde el historial status 3 → status 4; sin historial se usa la
 * asignación → actualización del servicio completado. La evaluación usa las
 * últimas 30 entregas, sin importar el filtro.
 */
function transportista_negocio_rendimiento($conexion, int $bssId, string $desde, string $hasta): array
{
    $bssId = (int)$bssId;
    if (!transportista_negocio_tabla_existe($conexion)) {
        return [];
    }
    transportista_negocio_asegurar($conexion);

    $datos = [];
    foreach (transportista_negocio_listar($conexion, $bssId) as $t) {
        $datos[(int)$t['id']] = [
            'nombre'   => (string)$t['nombre'],
            'cedula'   => (string)$t['cedula'],
            'entregas' => 0,
            'minutos'  => [],
            'ultimas'  => [],
        ];
    }
    if (!$datos) {
        return [];
    }

    $tabla = $conexion->query("SHOW TABLES LIKE 'transportista_servicios'");
    if (!$tabla || $tabla->num_rows === 0) {
        return array_values($datos);
    }

    $historial = $conexion->query("SHOW TABLES LIKE 'tienda_ordenes_estados'");
    $tieneHistorial = $historial && $historial->num_rows > 0;
    $joinHistorial = '';
    $colsHistorial = "NULL AS enviado_en, NULL AS entregado_en";
    $ordenFecha = 's.actualizado_en';
    if ($tieneHistorial) {
        $joinHistorial = "
            LEFT JOIN (
                SELECT orden_id,
                       MAX(CASE WHEN status = 3 THEN created END) AS enviado_en,
                       MAX(CASE WHEN status = 4 THEN created END) AS entregado_en
                  FROM tienda_ordenes_estados
                 WHERE bss_id = $bssId
                 GROUP BY orden_id
            ) e ON e.orden_id = o.id";
        $colsHistorial = 'e.enviado_en, e.entregado_en';
        $ordenFecha = 'COALESCE(e.entregado_en, s.actualizado_en)';
    }

    $sql = "SELECT s.transportista_id, s.estado, s.creado_en, s.actualizado_en, $colsHistorial
              FROM transportista_servicios s
              INNER JOIN tienda_ordenes o
                      ON o.id = CAST(s.numero_pedido AS UNSIGNED) AND o.bss_id = s.bss_id
              $joinHistorial
             WHERE s.bss_id = $bssId
               AND s.tipo = 'delivery'
               AND o.status = 4
             ORDER BY $ordenFecha DESC";
    $res = $conexion->query($sql);
    if (!$res) {
        return array_values($datos);
    }

    while ($fila = $res->fetch_assoc()) {
        $id = (int)$fila['transportista_id'];
        if (!isset($datos[$id])) {
            continue;
        }
        $fin = $fila['entregado_en'];
        if (!$fin && $fila['estado'] === 'completado') {
            $fin = $fila['actualizado_en'];
        }
        $inicio = $fila['enviado_en'] ?: $fila['creado_en'];
        if (!$fin || !$inicio) {
            continue;
        }
        $minutos = (strtotime($fin) - strtotime($inicio)) / 60;
        if ($minutos < 0) {
            continue;
        }

        // Las filas vienen de la más reciente a la más antigua.
        if (count($datos[$id]['ultimas']) < 30) {
            $datos[$id]['ultimas'][] = $minutos;
        }
        $dia = substr($fin, 0, 10);
        if ($dia >= $desde && $dia <= $hasta) {
            $datos[$id]['entregas']++;
            $datos[$id]['minutos'][] = $minutos;
        }
    }
    $res->free();

    foreach ($datos as &$d) {
        $cantidad = count($d['minutos']);
        $d['promedio_min'] = $cantidad ? round(array_sum($d['minutos']) / $cantidad, 1) : null;

        $n = count($d['ultimas']);
        $promedio = $n ? round(array_sum($d['ultimas']) / $n, 1) : null;
        $d['ultimas_n'] = $n;
        $d['ultimas_min'] = $promedio;
        $d['estrellas'] = 0;
        $d['etiqueta'] = 'Sin datos';
        if ($n >= 3 && $promedio !== null) {
            if ($promedio <= 30) {
                $d['estrellas'] = 5;
                $d['etiqueta'] = 'Excelente';
            } elseif ($promedio <= 45) {
                $d['estrellas'] = 4;
                $d['etiqueta'] = 'Muy bueno';
            } elseif ($promedio <= 60) {
                $d['estrellas'] = 3;
                $d['etiqueta'] = 'Bueno';
            } elseif ($promedio <= 90) {
                $d['estrellas'] = 2;
                $d['etiqueta'] = 'Regular';
            } else {
                $d['estrellas'] = 1;
                $d['etiqueta'] = 'Lento';
            }
        }
        unset($d['minutos'], $d['ultimas']);
    }
    unset($d);

    usort($datos, function ($a, $b) {
        if ($a['entregas'] !== $b['entregas']) {
            return $b['entregas'] - $a['entregas'];
        }
        if ($a['ultimas_min'] !== $b['ultimas_min']) {
            if ($a['ultimas_min'] === null) {
                return 1;
            }
            if ($b['ultimas_min'] === null) {
                return -1;
            }
            return $a['ultimas_min'] <=> $b['ultimas_min'];
        }
        return strcmp($a['nombre'], $b['nombre']);
    });

    return array_values($datos);
}

/** Push al transportista cuando se le asigna (o reasigna) un delivery. */
function transportista_notificar_asignacion($conexion, int $bssId, int $ordenId, int $transportistaId, string $titulo = 'Nuevo delivery asignado'): array
{
    if (!function_exists('enviar_push_transportista')) {
        return ['ok' => false, 'sent' => 0, 'msg' => 'El módulo de notificaciones no está cargado.'];
    }

    $nombreTienda = '';
    try {
        $stmt = $conexion->prepare(
            "SELECT COALESCE(NULLIF(nombre_visible, ''), nombre) AS nombre FROM negocio WHERE id = ? LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('i', $bssId);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $nombreTienda = trim((string)($fila['nombre'] ?? ''));
        }
    } catch (Throwable $e) {
        // El nombre de la tienda es solo decorativo: nunca debe impedir el push.
        error_log('transportista_notificar_asignacion: ' . $e->getMessage());
    }

    return enviar_push_transportista(
        $conexion,
        $transportistaId,
        $titulo,
        'Pedido #' . $ordenId . ($nombreTienda !== '' ? ' · ' . $nombreTienda : ''),
        ['tipo' => 'delivery', 'pedido' => (string)$ordenId, 'bss_id' => (string)$bssId]
    );
}
