<?php

/**
 * Stock pendiente cuando el negocio permite vender sin existencia.
 *
 * El stock físico nunca se guarda en negativo: al llegar a 0 se detiene. Las
 * unidades que no se pudieron descontar se acumulan en `stock_pendiente` y los
 * triggers de la tabla `stock` las consumen automáticamente cuando ingresa
 * mercancía (compra, traslado, asignación, devolución, etc.).
 */

function sp_asegurar_columna_negocio($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $res = $conexion->query("SHOW COLUMNS FROM negocio LIKE 'stock_pendiente'");
    if ($res && $res->num_rows > 0) {
        $res->free();
        return $listo = true;
    }
    if ($res) {
        $res->free();
    }

    @$conexion->query("ALTER TABLE negocio ADD COLUMN stock_pendiente TINYINT(1) NOT NULL DEFAULT 0");

    $res = $conexion->query("SHOW COLUMNS FROM negocio LIKE 'stock_pendiente'");
    $ok = $res && $res->num_rows > 0;
    if ($res) {
        $res->free();
    }

    return $listo = $ok;
}

function sp_asegurar_tabla($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `stock_pendiente` (
        `id`           INT(11)        NOT NULL AUTO_INCREMENT,
        `bss_id`       INT(11)        NOT NULL,
        `id_producto`  INT(11)        NOT NULL,
        `id_sucursal`  INT(11)        NOT NULL,
        `cantidad`     DECIMAL(15,4)  NOT NULL DEFAULT 0.0000,
        `created_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`   DATETIME       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_stock_pendiente` (`bss_id`, `id_producto`, `id_sucursal`),
        KEY `idx_stock_pendiente_bss` (`bss_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $listo = (bool) @$conexion->query($sql);
}

function sp_asegurar_triggers($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $existentes = [];
    $res = $conexion->query("SHOW TRIGGERS LIKE 'trg_stock_pendiente_%'");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $existentes[] = (string) ($row['Trigger'] ?? '');
        }
        $res->free();
    }

    if (!in_array('trg_stock_pendiente_update', $existentes, true)) {
        $sql = "CREATE TRIGGER trg_stock_pendiente_update
                BEFORE UPDATE ON stock
                FOR EACH ROW
                BEGIN
                    DECLARE v_pendiente DECIMAL(15,4) DEFAULT 0;
                    DECLARE v_aumento DECIMAL(15,4) DEFAULT 0;
                    DECLARE v_consumo DECIMAL(15,4) DEFAULT 0;

                    IF NEW.stock > OLD.stock THEN
                        SET v_aumento = NEW.stock - OLD.stock;

                        SET v_pendiente = COALESCE((
                            SELECT cantidad
                              FROM stock_pendiente
                             WHERE bss_id = NEW.bss_id
                               AND id_producto = NEW.id_producto
                               AND id_sucursal = NEW.id_sucursal
                             LIMIT 1
                        ), 0);

                        IF v_pendiente > 0 THEN
                            SET v_consumo = LEAST(v_aumento, v_pendiente);
                            SET NEW.stock = OLD.stock + (v_aumento - v_consumo);

                            UPDATE stock_pendiente
                               SET cantidad = GREATEST(0, cantidad - v_consumo)
                             WHERE bss_id = NEW.bss_id
                               AND id_producto = NEW.id_producto
                               AND id_sucursal = NEW.id_sucursal;

                            DELETE FROM stock_pendiente
                             WHERE bss_id = NEW.bss_id
                               AND id_producto = NEW.id_producto
                               AND id_sucursal = NEW.id_sucursal
                               AND cantidad <= 0.0001;
                        END IF;
                    END IF;
                END";

        if (!@$conexion->query($sql)) {
            return $listo = false;
        }
    }

    if (!in_array('trg_stock_pendiente_insert', $existentes, true)) {
        $sql = "CREATE TRIGGER trg_stock_pendiente_insert
                BEFORE INSERT ON stock
                FOR EACH ROW
                BEGIN
                    DECLARE v_pendiente DECIMAL(15,4) DEFAULT 0;
                    DECLARE v_consumo DECIMAL(15,4) DEFAULT 0;

                    IF COALESCE(NEW.stock, 0) > 0 THEN
                        SET v_pendiente = COALESCE((
                            SELECT cantidad
                              FROM stock_pendiente
                             WHERE bss_id = NEW.bss_id
                               AND id_producto = NEW.id_producto
                               AND id_sucursal = NEW.id_sucursal
                             LIMIT 1
                        ), 0);

                        IF v_pendiente > 0 THEN
                            SET v_consumo = LEAST(NEW.stock, v_pendiente);
                            SET NEW.stock = NEW.stock - v_consumo;

                            UPDATE stock_pendiente
                               SET cantidad = GREATEST(0, cantidad - v_consumo)
                             WHERE bss_id = NEW.bss_id
                               AND id_producto = NEW.id_producto
                               AND id_sucursal = NEW.id_sucursal;

                            DELETE FROM stock_pendiente
                             WHERE bss_id = NEW.bss_id
                               AND id_producto = NEW.id_producto
                               AND id_sucursal = NEW.id_sucursal
                               AND cantidad <= 0.0001;
                        END IF;
                    END IF;
                END";

        if (!@$conexion->query($sql)) {
            return $listo = false;
        }
    }

    return $listo = true;
}

function sp_asegurar_todo($conexion): bool
{
    return sp_asegurar_columna_negocio($conexion)
        && sp_asegurar_tabla($conexion)
        && sp_asegurar_triggers($conexion);
}

/**
 * Prepara todo lo necesario antes de abrir la transacción de una venta.
 * Devuelve true si el negocio tiene activo el stock pendiente.
 */
function sp_preparar_venta($conexion, int $bssId): bool
{
    static $cache = [];
    if (array_key_exists($bssId, $cache)) {
        return $cache[$bssId];
    }
    if ($bssId <= 0 || !sp_asegurar_columna_negocio($conexion)) {
        return $cache[$bssId] = false;
    }

    $stmt = $conexion->prepare("SELECT stock_pendiente FROM negocio WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return $cache[$bssId] = false;
    }
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $activo = (int) ($row['stock_pendiente'] ?? 0) === 1;
    if ($activo) {
        $activo = sp_asegurar_tabla($conexion) && sp_asegurar_triggers($conexion);
    }

    return $cache[$bssId] = $activo;
}

/**
 * Registra unidades faltantes. No hace DDL: debe llamarse después de
 * sp_preparar_venta(), dentro de la misma transacción de la venta.
 */
function sp_registrar($conexion, int $bssId, int $productoId, int $sucursalId, float $cantidad): bool
{
    $cantidad = round($cantidad, 4);
    if ($cantidad <= 0 || $productoId <= 0 || $sucursalId <= 0) {
        return false;
    }
    if (!sp_preparar_venta($conexion, $bssId)) {
        return false;
    }

    $stmt = $conexion->prepare(
        "INSERT INTO stock_pendiente (bss_id, id_producto, id_sucursal, cantidad)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('iiid', $bssId, $productoId, $sucursalId, $cantidad);
    $ok = $stmt->execute();
    $stmt->close();

    return (bool) $ok;
}
