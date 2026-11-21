-- ============================================================
-- Migración 035: Cargamentos de traslado entre sucursales
-- Base de datos: iseller
-- Fecha: 2026-11-21
-- ============================================================
-- Un cargamento agrupa varios artículos trasladados. Cada artículo
-- sigue guardándose en stock_traslados, con cargamento_id.
--
-- La aplicación también crea la tabla y la columna automáticamente
-- desde configurar/_stock_cargamentos.php y _stock_traslados.php.
-- ============================================================

CREATE TABLE IF NOT EXISTS `stock_cargamentos` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agrega cargamento_id a stock_traslados sin fallar si ya existe.
SET @sa_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stock_traslados'
    AND COLUMN_NAME = 'cargamento_id'
);
SET @sa_sql := IF(
  @sa_col = 0,
  'ALTER TABLE `stock_traslados` ADD COLUMN `cargamento_id` INT(11) DEFAULT NULL AFTER `id`',
  'SELECT 1'
);
PREPARE sa_stmt FROM @sa_sql;
EXECUTE sa_stmt;
DEALLOCATE PREPARE sa_stmt;

-- Agrega el índice sin fallar si ya existe.
SET @sa_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'stock_traslados'
    AND INDEX_NAME = 'idx_st_cargamento'
);
SET @sa_sql := IF(
  @sa_idx = 0,
  'ALTER TABLE `stock_traslados` ADD KEY `idx_st_cargamento` (`cargamento_id`)',
  'SELECT 1'
);
PREPARE sa_stmt FROM @sa_sql;
EXECUTE sa_stmt;
DEALLOCATE PREPARE sa_stmt;

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW TABLES LIKE 'stock_cargamentos';
-- DESCRIBE stock_traslados;
-- SELECT * FROM stock_cargamentos ORDER BY id DESC LIMIT 10;
