<?php
/**
 * Invalida el resumen mensual cacheado de un mes cuando cambian sus gastos.
 * Si la migración 019 todavía no está aplicada, no hace nada.
 */
function resumen_mensual_invalidar($conexion, $bss_id, $id_sucursal, $mes)
{
    if (!$mes || $mes >= date('Y-m')) {
        return;
    }

    $stmt = $conexion->prepare(
        "DELETE FROM gastos_resumen_mensual
         WHERE bss_id=? AND mes=? AND id_sucursal IN (0, ?)"
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('isi', $bss_id, $mes, $id_sucursal);
    $stmt->execute();
    $stmt->close();
}
