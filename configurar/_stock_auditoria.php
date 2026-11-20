<?php

/**
 * Auditoría de movimientos de stock.
 *
 * Guarda, para cada producto afectado, el stock anterior, el stock nuevo,
 * la diferencia, el usuario, la IP, la referencia del documento y datos
 * adicionales del movimiento (compra, factura o transferencia).
 *
 * La tabla se asegura en el primer uso para que el hosting siga funcionando
 * aunque no se haya ejecutado la migración 034_stock_auditoria.sql. Debe
 * llamarse antes de abrir una transacción.
 */
function sa_asegurar_tabla($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $res = $conexion->query("SHOW TABLES LIKE 'stock_auditoria'");
    if ($res && $res->num_rows > 0) {
        $res->free();
        return $listo = true;
    }
    if ($res) {
        $res->free();
    }

    $sql = "CREATE TABLE IF NOT EXISTS `stock_auditoria` (
        `id`                  BIGINT(20)    NOT NULL AUTO_INCREMENT,
        `bss_id`              INT(11)       NOT NULL,
        `id_sucursal`         INT(11)       NOT NULL,
        `id_producto`         INT(11)       NOT NULL,
        `producto_nombre`     VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
        `tipo_movimiento`     VARCHAR(40)   NOT NULL COMMENT 'COMPRA, COMPRA_FACTURA, TRANSFERENCIA_SALIDA, TRANSFERENCIA_ENTRADA',
        `origen`              VARCHAR(60)   NOT NULL DEFAULT '' COMMENT 'nueva_compra, compra_factura, transferencia',
        `referencia_tipo`     VARCHAR(40)   DEFAULT NULL COMMENT 'compra, factura_compra, stock_traslado',
        `referencia_id`       INT(11)       DEFAULT NULL,
        `cantidad`            DECIMAL(15,4) NOT NULL DEFAULT 0 COMMENT 'Cantidad movida, siempre positiva',
        `stock_anterior`      DECIMAL(15,4) NOT NULL DEFAULT 0,
        `stock_nuevo`         DECIMAL(15,4) NOT NULL DEFAULT 0,
        `diferencia`          DECIMAL(15,4) NOT NULL DEFAULT 0 COMMENT 'stock_nuevo - stock_anterior',
        `id_sucursal_origen`  INT(11)       DEFAULT NULL,
        `id_sucursal_destino` INT(11)       DEFAULT NULL,
        `detalle`             TEXT          DEFAULT NULL COMMENT 'Datos adicionales en JSON',
        `usuario_id`          INT(11)       NOT NULL DEFAULT 0,
        `usuario_nombre`      VARCHAR(150)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
        `ip`                  VARCHAR(45)   DEFAULT NULL,
        `user_agent`          VARCHAR(255)  DEFAULT NULL,
        `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_sa_bss_fecha`       (`bss_id`, `created_at`),
        KEY `idx_sa_bss_suc_fecha`   (`bss_id`, `id_sucursal`, `created_at`),
        KEY `idx_sa_bss_prod_fecha`  (`bss_id`, `id_producto`, `created_at`),
        KEY `idx_sa_bss_tipo_fecha`  (`bss_id`, `tipo_movimiento`, `created_at`),
        KEY `idx_sa_referencia`      (`referencia_tipo`, `referencia_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $listo = (bool) @$conexion->query($sql);
}

/**
 * Inserta una fila de auditoría. Devuelve false si no se pudo registrar.
 *
 * Datos mínimos esperados:
 * bss_id, id_sucursal, id_producto, producto_nombre, tipo_movimiento,
 * cantidad, stock_anterior, stock_nuevo.
 */
function sa_registrar($conexion, array $datos): bool
{
    if (!sa_asegurar_tabla($conexion)) {
        return false;
    }

    $bssId          = (int) ($datos['bss_id'] ?? 0);
    $sucursalId     = (int) ($datos['id_sucursal'] ?? 0);
    $productoId     = (int) ($datos['id_producto'] ?? 0);
    $productoNombre = (string) ($datos['producto_nombre'] ?? '');
    $tipo           = (string) ($datos['tipo_movimiento'] ?? '');
    $origen         = (string) ($datos['origen'] ?? '');
    $referenciaTipo = isset($datos['referencia_tipo']) ? (string) $datos['referencia_tipo'] : null;
    $referenciaId   = isset($datos['referencia_id']) && $datos['referencia_id'] !== null
        ? (int) $datos['referencia_id']
        : null;
    $cantidad       = (float) ($datos['cantidad'] ?? 0);
    $stockAnterior  = (float) ($datos['stock_anterior'] ?? 0);
    $stockNuevo     = (float) ($datos['stock_nuevo'] ?? 0);
    $diferencia     = array_key_exists('diferencia', $datos)
        ? (float) $datos['diferencia']
        : ($stockNuevo - $stockAnterior);
    $sucursalOrigen = isset($datos['id_sucursal_origen']) && $datos['id_sucursal_origen'] !== null
        ? (int) $datos['id_sucursal_origen']
        : null;
    $sucursalDestino = isset($datos['id_sucursal_destino']) && $datos['id_sucursal_destino'] !== null
        ? (int) $datos['id_sucursal_destino']
        : null;

    $detalle = $datos['detalle'] ?? null;
    if (is_array($detalle) || is_object($detalle)) {
        $detalle = json_encode($detalle, JSON_UNESCAPED_UNICODE);
    }
    $detalle = $detalle !== null ? (string) $detalle : null;

    $usuarioId     = (int) ($datos['usuario_id'] ?? ($_SESSION['id'] ?? 0));
    $usuarioNombre = (string) ($datos['usuario_nombre'] ?? ($_SESSION['nombre'] ?? ''));
    $ip            = $datos['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
    $userAgent     = $datos['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
    $ip            = $ip !== null ? substr((string) $ip, 0, 45) : null;
    $userAgent     = $userAgent !== null ? substr((string) $userAgent, 0, 255) : null;

    $stmt = $conexion->prepare(
        "INSERT INTO stock_auditoria
            (bss_id, id_sucursal, id_producto, producto_nombre, tipo_movimiento,
             origen, referencia_tipo, referencia_id, cantidad, stock_anterior,
             stock_nuevo, diferencia, id_sucursal_origen, id_sucursal_destino,
             detalle, usuario_id, usuario_nombre, ip, user_agent)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        'iiissssiddddiisisss',
        $bssId,
        $sucursalId,
        $productoId,
        $productoNombre,
        $tipo,
        $origen,
        $referenciaTipo,
        $referenciaId,
        $cantidad,
        $stockAnterior,
        $stockNuevo,
        $diferencia,
        $sucursalOrigen,
        $sucursalDestino,
        $detalle,
        $usuarioId,
        $usuarioNombre,
        $ip,
        $userAgent
    );

    $ok = $stmt->execute();
    $stmt->close();

    return (bool) $ok;
}
