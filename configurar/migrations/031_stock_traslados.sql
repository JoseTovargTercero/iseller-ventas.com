-- ============================================================
-- Migración 031: Historial de traslados de stock entre sucursales
-- Base de datos: iseller
-- Fecha: 2026-11-16
-- ============================================================

-- ============================================================
-- 1. Bitácora de traslados: cada fila guarda origen y destino
--    para alimentar el listado de movimientos de stock.
-- ============================================================
CREATE TABLE IF NOT EXISTS `stock_traslados` (
  `id`               INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`           INT(11)       NOT NULL,
  `id_producto`      INT(11)       NOT NULL,
  `producto_nombre`  VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
  `cantidad`         DECIMAL(15,4) NOT NULL COMMENT 'Siempre positiva',
  `sucursal_origen`  INT(11)       NOT NULL,
  `sucursal_destino` INT(11)       NOT NULL,
  `usuario_id`       INT(11)       NOT NULL COMMENT 'Quién traslada ($_SESSION[id])',
  `usuario_nombre`   VARCHAR(150)  NOT NULL DEFAULT '' COMMENT 'Copia histórica del usuario',
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_st_bss_fecha`     (`bss_id`, `created_at`),
  KEY `idx_st_bss_prod`      (`bss_id`, `id_producto`, `created_at`),
  KEY `idx_st_bss_origen`    (`bss_id`, `sucursal_origen`),
  KEY `idx_st_bss_destino`   (`bss_id`, `sucursal_destino`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Verificación
-- ============================================================
-- SHOW TABLES LIKE 'stock_traslados';
-- DESCRIBE stock_traslados;
