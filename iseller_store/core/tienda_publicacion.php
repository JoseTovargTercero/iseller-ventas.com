<?php
/**
 * Método de publicación de productos de la tienda virtual.
 *
 * publicar_sin_stock = 0 (por defecto): el producto solo se muestra y se vende
 *   mientras tenga stock suficiente.
 * publicar_sin_stock = 1: el producto permanece publicado y se permite comprar
 *   aunque no haya stock (venta bajo pedido).
 */

if (!function_exists('tienda_publicacion_asegurar')) {
    /** Crea la columna si todavía no existe (el proyecto no usa un runner de migraciones). */
    function tienda_publicacion_asegurar($conexion): void
    {
        static $hecho = false;
        if ($hecho) {
            return;
        }
        $hecho = true;

        $res = $conexion->query("SHOW COLUMNS FROM negocio LIKE 'publicar_sin_stock'");
        if ($res && $res->num_rows === 0) {
            $conexion->query(
                "ALTER TABLE negocio
                 ADD COLUMN publicar_sin_stock TINYINT(1) NOT NULL DEFAULT 0
                 AFTER destacados_ids"
            );
        }
    }
}

if (!function_exists('tienda_publica_sin_stock')) {
    /** Indica si una tienda permite mantener productos publicados y vender sin stock. */
    function tienda_publica_sin_stock($conexion, int $negocioId): bool
    {
        if ($negocioId <= 0) {
            return false;
        }

        tienda_publicacion_asegurar($conexion);

        $stmt = $conexion->prepare("SELECT publicar_sin_stock FROM negocio WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $negocioId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['publicar_sin_stock'] ?? 0) === 1;
    }
}
