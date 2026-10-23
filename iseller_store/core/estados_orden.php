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
    function registrar_estado_orden(mysqli $conexion, int $ordenId, int $bssId, int $status, string $origen = 'panel'): void
    {
        $stmt = $conexion->prepare(
            "INSERT INTO tienda_ordenes_estados (orden_id, bss_id, status, origen, created) VALUES (?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo registrar el estado del pedido');
        }

        $fecha = date('Y-m-d H:i:s');
        $stmt->bind_param('iiiss', $ordenId, $bssId, $status, $origen, $fecha);
        $stmt->execute();
        $stmt->close();
    }
}
