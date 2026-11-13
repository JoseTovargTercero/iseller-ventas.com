-- ============================================================
-- Migración 026: Historial de modificaciones de stock
-- Base de datos: iseller
-- Fecha: 2026-11-13
-- ============================================================

-- ============================================================
-- 1. Bitácora de cambios manuales de stock
--    Cada fila guarda el stock antes y después del cambio y el
--    usuario que lo hizo (tomado de la sesión, nunca del POST).
-- ============================================================
CREATE TABLE IF NOT EXISTS `stock_modificaciones` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`          INT(11)       NOT NULL,
  `id_sucursal`     INT(11)       NOT NULL,
  `id_producto`     INT(11)       NOT NULL,
  `producto_nombre` VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
  `stock_anterior`  DECIMAL(15,4) NOT NULL DEFAULT 0,
  `stock_nuevo`     DECIMAL(15,4) NOT NULL DEFAULT 0,
  `diferencia`      DECIMAL(15,4) NOT NULL DEFAULT 0 COMMENT 'stock_nuevo - stock_anterior',
  `usuario_id`      INT(11)       NOT NULL COMMENT 'Quién modifica el stock ($_SESSION[id])',
  `usuario_nombre`  VARCHAR(150)  NOT NULL COMMENT 'Copia histórica del usuario',
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sm_bss_fecha`      (`bss_id`, `created_at`),
  KEY `idx_sm_bss_suc_fecha`  (`bss_id`, `id_sucursal`, `created_at`),
  KEY `idx_sm_bss_prod_fecha` (`bss_id`, `id_producto`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Verificación
-- ============================================================
-- SHOW TABLES LIKE 'stock_modificaciones';
-- DESCRIBE stock_modificaciones;
-- SELECT * FROM stock_modificaciones ORDER BY id DESC LIMIT 10;
