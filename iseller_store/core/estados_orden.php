<?php
/**
 * Historial de estados de los pedidos web (tienda_ordenes).
 */
if (!function_exists('registrar_estado_orden')) {
    /**
     * Inserta una fila con la hora exacta del cambio de estado.
     * Debe llamarse dentro de la misma transacción que actualiza
     * tienda_ordenes.status: si falla, el cambio completo se revierte.
     */
    function registrar_estado_orden(mysqli $conexion, int $ordenId, int $bssId, int $status, string $origen = 'panel', ?string $fecha = null): void
    {
        $stmt = $conexion->prepare(
            "INSERT INTO tienda_ordenes_estados (orden_id, bss_id, status, origen, created) VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo registrar el estado del pedido');
        }

        $fechaRegistro = $fecha !== null ? $fecha : date('Y-m-d H:i:s');
        $stmt->bind_param('iiiss', $ordenId, $bssId, $status, $origen, $fechaRegistro);
        $stmt->execute();
        $stmt->close();

        // 3 = enviado: guardar el punto de partida (ubicación de la tienda).
        if ($status === 3) {
            guardar_origen_pedido($conexion, $ordenId, $bssId);
        }
    }
}

if (!function_exists('guardar_origen_pedido')) {
    /**
     * Punto de inicio del reparto: la ubicación de la tienda al pasar el pedido a enviado.
     * Si la tienda no tiene coordenadas configuradas, no se guarda nada.
     */
    function guardar_origen_pedido(mysqli $conexion, int $ordenId, int $bssId): void
    {
        static $tablaLista = false;
        try {
            if (!$tablaLista) {
                $conexion->query(
                    "CREATE TABLE IF NOT EXISTS tienda_orden_origen (
                        orden_id INT NOT NULL,
                        bss_id INT NOT NULL,
                        lat DECIMAL(10,7) NOT NULL,
                        lng DECIMAL(10,7) NOT NULL,
                        creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (orden_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
                );
                $tablaLista = true;
            }

            $stmt = $conexion->prepare("SELECT lat, lng FROM negocio WHERE id = ? AND lat IS NOT NULL AND lng IS NOT NULL LIMIT 1");
            if (!$stmt) {
                return;
            }
            $stmt->bind_param('i', $bssId);
            $stmt->execute();
            $tienda = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$tienda) {
                return;
            }

            $stmt = $conexion->prepare(
                "INSERT INTO tienda_orden_origen (orden_id, bss_id, lat, lng, creado_en)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE bss_id = VALUES(bss_id), lat = VALUES(lat),
                                         lng = VALUES(lng), creado_en = NOW()"
            );
            if (!$stmt) {
                return;
            }
            $lat = (float)$tienda['lat'];
            $lng = (float)$tienda['lng'];
            $stmt->bind_param('iidd', $ordenId, $bssId, $lat, $lng);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) {
            // No bloquear el cambio de estado si falla el guardado del origen.
            error_log('origen pedido ' . $ordenId . ': ' . $e->getMessage());
        }
    }
}
