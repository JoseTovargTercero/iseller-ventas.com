<?php

/**
 * Registro de cargamentos de traslado entre sucursales.
 *
 * Un cargamento agrupa varios artículos. Cada artículo se sigue guardando
 * como una fila en stock_traslados, pero con el cargamento_id que los une.
 */
function sc_asegurar_tabla($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $res = $conexion->query("SHOW TABLES LIKE 'stock_cargamentos'");
    if ($res && $res->num_rows > 0) {
        $res->free();
        return $listo = true;
    }
    if ($res) {
        $res->free();
    }

    $sql = "CREATE TABLE IF NOT EXISTS `stock_cargamentos` (
        `id`                   INT(11)       NOT NULL AUTO_INCREMENT,
        `bss_id`               INT(11)       NOT NULL,
        `sucursal_origen`      INT(11)       NOT NULL,
        `sucursal_destino`     INT(11)       NOT NULL,
        `motivo`               TEXT          NOT NULL,
        `usuario_id`           INT(11)       NOT NULL DEFAULT 0,
        `usuario_nombre`       VARCHAR(150)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
        `total_productos`      INT(11)       NOT NULL DEFAULT 0,
        `total_unidades`       DECIMAL(15,4) NOT NULL DEFAULT 0,
        `created_at`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sc_bss_fecha`   (`bss_id`, `created_at`),
        KEY `idx_sc_bss_ruta`    (`bss_id`, `sucursal_origen`, `sucursal_destino`),
        KEY `idx_sc_bss_usuario` (`bss_id`, `usuario_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $listo = (bool) @$conexion->query($sql);
}

/**
 * Crea el registro del cargamento y devuelve su id, o 0 si falla.
 */
function sc_crear($conexion, array $datos): int
{
    if (!sc_asegurar_tabla($conexion)) {
        return 0;
    }

    $bssId          = (int) ($datos['bss_id'] ?? 0);
    $sucursalOrigen = (int) ($datos['sucursal_origen'] ?? 0);
    $sucursalDestino = (int) ($datos['sucursal_destino'] ?? 0);
    $motivo         = (string) ($datos['motivo'] ?? '');
    $usuarioId      = (int) ($datos['usuario_id'] ?? ($_SESSION['id'] ?? 0));
    $usuarioNombre  = (string) ($datos['usuario_nombre'] ?? ($_SESSION['nombre'] ?? ''));
    $totalProductos = (int) ($datos['total_productos'] ?? 0);
    $totalUnidades  = (float) ($datos['total_unidades'] ?? 0);

    $stmt = $conexion->prepare(
        "INSERT INTO stock_cargamentos
            (bss_id, sucursal_origen, sucursal_destino, motivo, usuario_id,
             usuario_nombre, total_productos, total_unidades)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param(
        'iiisisid',
        $bssId,
        $sucursalOrigen,
        $sucursalDestino,
        $motivo,
        $usuarioId,
        $usuarioNombre,
        $totalProductos,
        $totalUnidades
    );

    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }

    $id = (int) $conexion->insert_id;
    $stmt->close();

    return $id;
}
