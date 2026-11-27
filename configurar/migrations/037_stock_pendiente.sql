-- ============================================================
-- 037: Comportamiento del stock al llegar a 0
--   0 = detener el stock en 0 (comportamiento anterior)
--   1 = registrar el faltante como stock pendiente
-- ============================================================

-- MySQL no soporta ADD COLUMN IF NOT EXISTS en todas sus versiones.
-- Se comprueba INFORMATION_SCHEMA y se ejecuta el ALTER solo si falta.
SET @columna_stock_pendiente := (
  SELECT COUNT(*)
    FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'negocio'
     AND COLUMN_NAME = 'stock_pendiente'
);

SET @sql_stock_pendiente := IF(
  @columna_stock_pendiente = 0,
  'ALTER TABLE `negocio` ADD COLUMN `stock_pendiente` TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);

PREPARE stmt_stock_pendiente FROM @sql_stock_pendiente;
EXECUTE stmt_stock_pendiente;
DEALLOCATE PREPARE stmt_stock_pendiente;

CREATE TABLE IF NOT EXISTS `stock_pendiente` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Los triggers son opcionales. La aplicación descuenta el pendiente en PHP
-- (sp_sincronizar) antes de vender/listar y al cargar mercancía, por lo que
-- esta migración no requiere el privilegio CREATE TRIGGER del hosting.
-- Si la base ya tiene los triggers trg_stock_pendiente_*, se conservan.
