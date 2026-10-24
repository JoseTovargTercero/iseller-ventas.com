<?php
/**
 * Tiendas favoritas de los clientes de la tienda virtual.
 * La tabla se crea sola para no depender de una migración manual.
 */

function favoritos_asegurar($conexion): bool
{
    static $ok = null;
    if ($ok !== null) {
        return $ok;
    }

    $ok = (bool)$conexion->query(
        "CREATE TABLE IF NOT EXISTS tienda_favoritos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id INT UNSIGNED NOT NULL,
            negocio_id INT UNSIGNED NOT NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_favorito (usuario_id, negocio_id),
            KEY idx_favorito_negocio (negocio_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
    return $ok;
}

function es_favorito($conexion, int $usuarioId, int $negocioId): bool
{
    if (!favoritos_asegurar($conexion)) {
        return false;
    }

    $stmt = $conexion->prepare(
        "SELECT 1 FROM tienda_favoritos WHERE usuario_id = ? AND negocio_id = ? LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ii', $usuarioId, $negocioId);
    $stmt->execute();
    $es = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $es;
}
