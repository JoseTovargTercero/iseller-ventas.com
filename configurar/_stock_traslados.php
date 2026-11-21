<?php

/**
 * Bitácora de traslados entre sucursales. Se asegura en el primer uso para
 * que el hosting funcione aunque no se haya ejecutado la migración 031.
 *
 * cada fila representa un artículo trasladado. Si el traslado pertenece a un
 * cargamento, cargamento_id apunta a stock_cargamentos.
 */
function st_asegurar_tabla($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $res = $conexion->query("SHOW TABLES LIKE 'stock_traslados'");
    if ($res && $res->num_rows > 0) {
        $res->free();

        // Compatibilidad con instalaciones que ya tienen la tabla sin la
        // columna de cargamento.
        $col = $conexion->query("SHOW COLUMNS FROM stock_traslados LIKE 'cargamento_id'");
        $tieneColumna = $col && $col->num_rows > 0;
        if ($col) {
            $col->free();
        }
        if (!$tieneColumna) {
            @$conexion->query("ALTER TABLE stock_traslados ADD COLUMN cargamento_id INT(11) NULL AFTER id");
            $col = $conexion->query("SHOW COLUMNS FROM stock_traslados LIKE 'cargamento_id'");
            $tieneColumna = $col && $col->num_rows > 0;
            if ($col) {
                $col->free();
            }
        }
        if (!$tieneColumna) {
            return $listo = false;
        }

        $idx = $conexion->query("SHOW INDEX FROM stock_traslados WHERE Key_name = 'idx_st_cargamento'");
        $tieneIndice = $idx && $idx->num_rows > 0;
        if ($idx) {
            $idx->free();
        }
        if (!$tieneIndice) {
            @$conexion->query("ALTER TABLE stock_traslados ADD KEY idx_st_cargamento (cargamento_id)");
        }

        return $listo = true;
    }
    if ($res) {
        $res->free();
    }

    $sql = "CREATE TABLE IF NOT EXISTS `stock_traslados` (
        `id`               INT(11)       NOT NULL AUTO_INCREMENT,
        `cargamento_id`    INT(11)       DEFAULT NULL,
        `bss_id`           INT(11)       NOT NULL,
        `id_producto`      INT(11)       NOT NULL,
        `producto_nombre`  VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
        `cantidad`         DECIMAL(15,4) NOT NULL COMMENT 'Siempre positiva',
        `sucursal_origen`  INT(11)       NOT NULL,
        `sucursal_destino` INT(11)       NOT NULL,
        `usuario_id`       INT(11)       NOT NULL COMMENT 'Quién traslada (\$_SESSION[id])',
        `usuario_nombre`   VARCHAR(150)  NOT NULL DEFAULT '' COMMENT 'Copia histórica del usuario',
        `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_st_bss_fecha`     (`bss_id`, `created_at`),
        KEY `idx_st_bss_prod`      (`bss_id`, `id_producto`, `created_at`),
        KEY `idx_st_bss_origen`    (`bss_id`, `sucursal_origen`),
        KEY `idx_st_bss_destino`   (`bss_id`, `sucursal_destino`),
        KEY `idx_st_cargamento`    (`cargamento_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $listo = (bool) @$conexion->query($sql);
}
