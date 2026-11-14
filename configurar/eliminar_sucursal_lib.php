<?php
/**
 * Borra los datos de una sucursal: stock y sus movimientos, ventas, compras,
 * caja, gastos, asignaciones entre sucursales, planillas y tokens.
 *
 * Se conservan los clientes (quedan sin sucursal) y los usuarios nivel 1.
 * Los usuarios de la sucursal se borran y sus pagos de membresía se reasignan
 * al usuario que ejecuta la acción para no perder ese historial.
 *
 * Debe llamarse dentro de una transacción y con los permisos ya validados.
 * No borra la fila de `sucursales` ni el logo: eso lo hace quien la llama.
 *
 * @return int total de filas borradas
 */
function eliminar_sucursal_datos(mysqli $conexion, int $bssId, int $sucursalId, int $usuarioActualId): int
{
    $borrar = function (string $sql, string $tipos, array $params) use ($conexion): int {
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Error al preparar la consulta: ' . $conexion->error);
        }
        $stmt->bind_param($tipos, ...$params);
        if (!$stmt->execute()) {
            throw new RuntimeException('Error al borrar datos de la sucursal: ' . $stmt->error);
        }
        $filas = $stmt->affected_rows;
        $stmt->close();
        return $filas;
    };

    $total = 0;

    // Tesorería entre sucursales: los pagos son RESTRICT, van antes que las cuentas.
    $total += $borrar(
        "DELETE p FROM pagos_sucursales p
           JOIN cuentas_sucursales c ON c.id = p.cuenta_sucursal_id
          WHERE p.bss_id = ? AND c.bss_id = ?
            AND (c.sucursal_deudora = ? OR c.sucursal_acreedora = ?)",
        "iiii",
        [$bssId, $bssId, $sucursalId, $sucursalId]
    );
    $total += $borrar(
        "DELETE FROM cuentas_sucursales
          WHERE bss_id = ? AND (sucursal_deudora = ? OR sucursal_acreedora = ?)",
        "iii",
        [$bssId, $sucursalId, $sucursalId]
    );
    // Sus bitácoras, conceptos, detalles y movimientos caen por CASCADE.
    $total += $borrar(
        "DELETE FROM asignaciones_sucursales
          WHERE bss_id = ? AND (sucursal_origen = ? OR sucursal_destino = ?)",
        "iii",
        [$bssId, $sucursalId, $sucursalId]
    );

    $total += $borrar(
        "DELETE ld FROM lotes_detalle ld
           JOIN lotes l ON l.id = ld.lote_id
          WHERE l.bss_id = ? AND l.id_sucursal = ?",
        "ii",
        [$bssId, $sucursalId]
    );
    $total += $borrar("DELETE FROM lotes WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM fracciones WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM stock_ajustes WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM stock_modificaciones WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM stock WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);

    $total += $borrar("DELETE FROM creditos WHERE bss_id = ? AND sucursal_id = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM abonos WHERE bss_id = ? AND sucursal_id = ?", "ii", [$bssId, $sucursalId]);
    // Los artículos y créditos de la orden caen por CASCADE.
    $total += $borrar("DELETE FROM orden WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM compras_facturas WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM compras WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM cortes_de_caja WHERE bss_id = ? AND sucursal_id = ?", "ii", [$bssId, $sucursalId]);

    $total += $borrar("DELETE FROM gastos WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM gastos_recurrentes WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM gastos_resumen_mensual WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);

    $total += $borrar("DELETE FROM negocio_productos WHERE sucursal_id = ?", "i", [$sucursalId]);
    $total += $borrar("DELETE FROM configuracion WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM pos_tokens WHERE bss_id = ? AND sucursal_id = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM errores_ventas WHERE bss_id = ? AND sucursal_id = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM tienda_orden_articulos WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM tienda_ordenes WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);
    $total += $borrar("DELETE FROM empleados WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);

    // Los clientes son del negocio: solo se desvinculan de la sucursal.
    $total += $borrar("UPDATE clientes SET sucursal_id = NULL WHERE bss_id = ? AND sucursal_id = ?", "ii", [$bssId, $sucursalId]);

    // Usuarios de la sucursal (los nivel 1 son dueños y no se borran).
    $usuariosSucursal = "SELECT id FROM usuarios
                          WHERE bss_id = ? AND id_sucursal = ? AND nivel <> 1 AND id <> ?";
    $total += $borrar(
        "UPDATE membresias_pagos SET usuario_id = ?
          WHERE usuario_id IN ($usuariosSucursal)",
        "iiii",
        [$usuarioActualId, $bssId, $sucursalId, $usuarioActualId]
    );
    $total += $borrar(
        "DELETE FROM push_dispositivos WHERE usuario_id IN ($usuariosSucursal)",
        "iii",
        [$bssId, $sucursalId, $usuarioActualId]
    );
    $total += $borrar(
        "DELETE FROM users_permisos WHERE id_user IN ($usuariosSucursal)",
        "iii",
        [$bssId, $sucursalId, $usuarioActualId]
    );
    $total += $borrar(
        "DELETE FROM usuarios WHERE bss_id = ? AND id_sucursal = ? AND nivel <> 1 AND id <> ?",
        "iii",
        [$bssId, $sucursalId, $usuarioActualId]
    );
    // Dueños (y el usuario actual) quedan sin sucursal asignada.
    $borrar("UPDATE usuarios SET id_sucursal = NULL WHERE bss_id = ? AND id_sucursal = ?", "ii", [$bssId, $sucursalId]);

    return $total;
}
