<?php
/**
 * Reglas de cantidad por producto.
 *
 * Sin fila en producto_reglas el producto usa mínimo 1 y paso 1.
 * Los valores se manejan en décimas enteras para evitar errores de flotantes.
 */

if (!function_exists('reglas_producto_asegurar')) {
    function reglas_producto_asegurar($conexion): void
    {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;

        $conexion->query(
            "CREATE TABLE IF NOT EXISTS producto_reglas (
                producto_id INT NOT NULL,
                bss_id INT NOT NULL,
                minimo_cantidad DECIMAL(6,2) NOT NULL DEFAULT 1.00,
                paso_cantidad DECIMAL(6,2) NOT NULL DEFAULT 1.00,
                actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (producto_id),
                KEY idx_producto_reglas_bss (bss_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}

if (!function_exists('reglas_producto_normalizar')) {
    /** Valor válido con un decimal; si viene vacío o fuera de rango usa el defecto. */
    function reglas_producto_normalizar($valor, float $defecto = 1.0): float
    {
        $numero = is_numeric($valor) ? (float)$valor : $defecto;
        if (!is_finite($numero) || $numero < 0.1) {
            return $defecto;
        }
        return min(9999.9, round($numero, 1));
    }
}

if (!function_exists('reglas_producto_cantidad_valida')) {
    /** La cantidad debe ser el mínimo más un múltiplo exacto del paso. */
    function reglas_producto_cantidad_valida($cantidad, $minimo, $paso): bool
    {
        $cantidadT = (int)round((float)$cantidad * 10);
        $minimoT   = (int)round((float)$minimo * 10);
        $pasoT     = (int)round((float)$paso * 10);

        if ($pasoT <= 0 || $cantidadT < $minimoT) {
            return false;
        }

        return (($cantidadT - $minimoT) % $pasoT) === 0;
    }
}
