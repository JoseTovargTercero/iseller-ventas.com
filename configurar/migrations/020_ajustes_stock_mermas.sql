-- ============================================================
-- Migración 020: Ajustes de stock y mermas
-- Base de datos: iseller
-- Fecha: 2026-11-07
-- ============================================================

-- ============================================================
-- 1. Libro de movimientos de inventario (mermas y ajustes)
-- ============================================================
CREATE TABLE IF NOT EXISTS `stock_ajustes` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`            INT(11)       NOT NULL,
  `id_sucursal`       INT(11)       NOT NULL,
  `producto_id`       INT(11)       NOT NULL,
  `producto_nombre`   VARCHAR(255)  NOT NULL COMMENT 'Copia histórica',
  `categoria_id`      INT(11)       DEFAULT NULL,
  `categoria_nombre`  VARCHAR(150)  NOT NULL DEFAULT 'Sin categoría' COMMENT 'Copia histórica',
  `tipo`              ENUM('MERMA','AJUSTE') NOT NULL,
  `direccion`         ENUM('SALIDA','ENTRADA') NOT NULL,
  `motivo`            VARCHAR(40)   NOT NULL COMMENT 'Clasificación fija: VENCIMIENTO, ROTURA, etc.',
  `razon`             TEXT          NOT NULL COMMENT 'Explicación escrita por el usuario',
  `cantidad`          DECIMAL(15,4) NOT NULL COMMENT 'Siempre positiva; la dirección indica el signo',
  `costo_unitario`    DECIMAL(15,4) NOT NULL DEFAULT 0,
  `monto_usd`         DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Valor en $ de la pérdida = cantidad * costo_unitario',
  `stock_antes`       DECIMAL(15,4) NOT NULL DEFAULT 0,
  `stock_despues`     DECIMAL(15,4) NOT NULL DEFAULT 0,
  `fecha`             DATE          NOT NULL,
  `estado`            ENUM('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `usuario_id`        INT(11)       NOT NULL COMMENT 'Quién registra el ajuste ($_SESSION[id])',
  `usuario_nombre`    VARCHAR(150)  NOT NULL COMMENT 'Copia histórica del usuario',
  `usuario_anulacion` INT(11)       DEFAULT NULL,
  `fecha_anulacion`   DATETIME      DEFAULT NULL,
  `motivo_anulacion`  TEXT          DEFAULT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aj_bss_fecha`      (`bss_id`, `fecha`),
  KEY `idx_aj_bss_suc_fecha`  (`bss_id`, `id_sucursal`, `fecha`),
  KEY `idx_aj_bss_prod_fecha` (`bss_id`, `producto_id`, `fecha`),
  KEY `idx_aj_bss_cat_fecha`  (`bss_id`, `categoria_id`, `fecha`),
  KEY `idx_aj_bss_tipo`       (`bss_id`, `tipo`, `direccion`),
  CONSTRAINT `chk_aj_cantidad` CHECK (`cantidad` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Entrada de menú
-- ============================================================
INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'stock', 'Ajustes de stock y mermas', 'ajustes_stock.php',
       'trending-down-outline', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'ajustes_stock.php'
);

-- ============================================================
-- 3. Verificación
-- ============================================================
-- SHOW TABLES LIKE 'stock_ajustes';
-- DESCRIBE stock_ajustes;
-- SELECT id, categoria, nombre, dir FROM menu WHERE dir = 'ajustes_stock.php';
