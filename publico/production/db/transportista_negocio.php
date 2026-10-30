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
