<?php

/**
 * Asegura las columnas de seguimiento y fecha límite en la tabla creditos.
 * Se llama fuera de la transacción de venta porque el DDL hace commit implícito.
 */
function credito_asegurar_columnas($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $columnas = [
        'seguimiento_estricto' => "ALTER TABLE `creditos` ADD COLUMN `seguimiento_estricto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cedula`",
        'fecha_limite' => "ALTER TABLE `creditos` ADD COLUMN `fecha_limite` DATE NULL DEFAULT NULL AFTER `seguimiento_estricto`",
    ];

    $ok = true;
    foreach ($columnas as $columna => $sql) {
        $res = @$conexion->query("SHOW COLUMNS FROM `creditos` WHERE Field = '$columna'");
        $existe = $res && $res->num_rows > 0;
        if ($res) {
            $res->free();
        }

        if (!$existe && !@$conexion->query($sql)) {
            $ok = false;
        }
    }

    return $listo = $ok;
}
