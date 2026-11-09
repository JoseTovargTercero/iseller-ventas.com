-- ============================================================
-- iSeller - Migración para hosting
-- Cambios no comiteados: compras con factura + res en canal
-- Base de datos: iseller
-- Motor: MariaDB 10.4+ / MySQL 8+
--
-- Es idempotente: se puede ejecutar varias veces sin duplicar
-- columnas, índices, tablas ni el menú.
-- ============================================================

-- ------------------------------------------------------------
-- Utilidades temporales
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS `_mig_add_column`;
DROP PROCEDURE IF EXISTS `_mig_add_index`;

DELIMITER $$

CREATE PROCEDURE `_mig_add_column`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @mig_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE mig_stmt FROM @mig_sql;
        EXECUTE mig_stmt;
        DEALLOCATE PREPARE mig_stmt;
    END IF;
END$$

CREATE PROCEDURE `_mig_add_index`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @mig_sql = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_definition);
        PREPARE mig_stmt FROM @mig_sql;
        EXECUTE mig_stmt;
        DEALLOCATE PREPARE mig_stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- 021. Configuración: registro de compras con factura
-- ============================================================
CALL `_mig_add_column`(
    'configuracion',
    'factura',
    'TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = registro de compras con factura'' AFTER `registro_clientes`'
);

-- ============================================================
-- 022-A. Facturas de compra y abonos
-- ============================================================
CREATE TABLE IF NOT EXISTS `compras_facturas` (
  `id`              INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`          INT(11)       NOT NULL,
  `id_sucursal`     INT(11)       NOT NULL,
  `proveedor`       VARCHAR(255)  NOT NULL,
  `numero_factura`  VARCHAR(100)  NOT NULL,
  `fecha_factura`   DATE          NOT NULL,
  `total`           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `estado`          ENUM('pendiente','pagada') NOT NULL DEFAULT 'pendiente',
  `usuario`         VARCHAR(150)  DEFAULT NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cf_bss_estado` (`bss_id`, `estado`),
  KEY `idx_cf_bss_fecha`  (`bss_id`, `fecha_factura`),
  KEY `idx_cf_bss_num`    (`bss_id`, `numero_factura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `compras_factura_pagos` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `factura_id`  INT(11)       NOT NULL,
  `bss_id`      INT(11)       NOT NULL,
  `monto`       DECIMAL(15,2) NOT NULL,
  `fecha`       DATE          NOT NULL,
  `usuario`     VARCHAR(150)  DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cfp_factura` (`factura_id`),
  KEY `idx_cfp_bss`     (`bss_id`),
  CONSTRAINT `fk_cfp_factura` FOREIGN KEY (`factura_id`) REFERENCES `compras_facturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL `_mig_add_column`(
    'compras',
    'factura_id',
    'INT(11) DEFAULT NULL AFTER `factura`'
);
CALL `_mig_add_index`(
    'compras',
    'idx_compras_factura_id',
    'KEY `idx_compras_factura_id` (`factura_id`)'
);

-- ============================================================
-- 022-B. Res en canal, subproductos y seguimiento estricto
-- ============================================================
CALL `_mig_add_column`(
    'negocio',
    'seguimiento_estricto',
    'TINYINT(1) NOT NULL DEFAULT 0'
);

CALL `_mig_add_column`(
    'productos',
    'unidad',
    'VARCHAR(8) NOT NULL DEFAULT ''UND'' AFTER `nombre`'
);
CALL `_mig_add_column`(
    'productos',
    'rendimiento',
    'DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER `id_producto_relacionado`'
);
CALL `_mig_add_column`(
    'productos',
    'modo_control',
    'ENUM(''NORMAL'',''CANAL'',''DETALLADO'') NOT NULL DEFAULT ''NORMAL'' AFTER `rendimiento`'
);
CALL `_mig_add_column`(
    'productos',
    'seguimiento_estricto',
    'TINYINT(1) NOT NULL DEFAULT 0 AFTER `modo_control`'
);
CALL `_mig_add_index`(
    'productos',
    'idx_prod_relacionado',
    'KEY `idx_prod_relacionado` (`bss_id`, `id_producto_relacionado`)'
);
CALL `_mig_add_index`(
    'productos',
    'idx_prod_estricto',
    'KEY `idx_prod_estricto` (`bss_id`, `seguimiento_estricto`)'
);

CALL `_mig_add_column`(
    'stock',
    'visible_venta',
    'TINYINT(1) NOT NULL DEFAULT 1 AFTER `stock`'
);
CALL `_mig_add_column`(
    'stock',
    'ultima_compra_at',
    'DATETIME NULL AFTER `visible_venta`'
);
CALL `_mig_add_column`(
    'stock',
    'ultima_compra_kg',
    'DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER `ultima_compra_at`'
);
CALL `_mig_add_index`(
    'stock',
    'idx_stock_visible',
    'KEY `idx_stock_visible` (`bss_id`, `id_sucursal`, `visible_venta`)'
);
CALL `_mig_add_index`(
    'stock',
    'idx_stock_compra',
    'KEY `idx_stock_compra` (`bss_id`, `id_sucursal`, `ultima_compra_at`)'
);

CREATE TABLE IF NOT EXISTS `lotes` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `bss_id`                INT NOT NULL,
  `id_sucursal`           INT NOT NULL,
  `padre_id`              INT NOT NULL,
  `modo`                  ENUM('CANAL','DETALLADO') NOT NULL DEFAULT 'CANAL',
  `kg_inicial`            DECIMAL(15,4) NOT NULL,
  `kg_restante`           DECIMAL(15,4) NOT NULL,
  `diferencia_pendiente`  DECIMAL(15,4) NOT NULL DEFAULT 0,
  `conciliado`            TINYINT(1) NOT NULL DEFAULT 0,
  `costo_total`           DECIMAL(15,4) NOT NULL DEFAULT 0,
  `proveedor`             VARCHAR(150) NULL,
  `notas`                 TEXT NULL,
  `estado`                ENUM('ABIERTO','CERRADO','ANULADO') NOT NULL DEFAULT 'ABIERTO',
  `fecha_apertura`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre`          DATETIME NULL,
  `usuario_id`            INT NOT NULL,
  KEY `idx_lote_bss_suc` (`bss_id`, `id_sucursal`, `estado`),
  KEY `idx_lote_padre` (`bss_id`, `padre_id`, `estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `lotes_detalle` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `lote_id`         INT NOT NULL,
  `producto_id`     INT NULL,
  `cantidad`        DECIMAL(15,4) NOT NULL,
  `costo_unitario`  DECIMAL(15,4) NOT NULL DEFAULT 0,
  `origen`          ENUM('DESPIECE','VENTA','MERMA','AJUSTE') NOT NULL,
  `orden_id`        INT NULL,
  `usuario_id`      INT NOT NULL,
  `fecha`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_lote_det` (`lote_id`, `origen`),
  KEY `idx_lote_prod` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cantidades de venta como número (kg y reportes)
UPDATE `orden_articulos` SET `quantity` = 0 WHERE `quantity` IS NULL;
ALTER TABLE `orden_articulos`
  MODIFY `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0;

-- Menú de Res en canal / Subproductos
INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'stock', 'Res en canal / Subproductos', 'canal.php', 'layers-outline', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'canal.php'
);

-- ------------------------------------------------------------
-- Limpieza de utilidades temporales
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS `_mig_add_column`;
DROP PROCEDURE IF EXISTS `_mig_add_index`;

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW COLUMNS FROM configuracion LIKE 'factura';
-- SHOW COLUMNS FROM compras LIKE 'factura_id';
-- SHOW COLUMNS FROM negocio LIKE 'seguimiento_estricto';
-- SHOW COLUMNS FROM productos LIKE 'unidad';
-- SHOW COLUMNS FROM productos LIKE 'modo_control';
-- SHOW COLUMNS FROM productos LIKE 'rendimiento';
-- SHOW COLUMNS FROM productos LIKE 'seguimiento_estricto';
-- SHOW COLUMNS FROM stock LIKE 'visible_venta';
-- SHOW COLUMNS FROM stock LIKE 'ultima_compra_at';
-- SHOW COLUMNS FROM stock LIKE 'ultima_compra_kg';
-- SHOW COLUMNS FROM orden_articulos LIKE 'quantity';
-- SHOW TABLES LIKE 'compras_facturas';
-- SHOW TABLES LIKE 'compras_factura_pagos';
-- SHOW TABLES LIKE 'lotes';
-- SHOW TABLES LIKE 'lotes_detalle';
-- SELECT id, nombre, dir FROM menu WHERE dir = 'canal.php';
