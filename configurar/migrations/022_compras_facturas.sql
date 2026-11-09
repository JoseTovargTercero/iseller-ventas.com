-- ============================================================
-- Migración 022: Facturas de compra con estado de pago y abonos
-- Base de datos: iseller
-- Fecha: 2026-11-07
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

ALTER TABLE `compras`
  ADD COLUMN IF NOT EXISTS `factura_id` INT(11) DEFAULT NULL AFTER `factura`,
  ADD KEY `idx_compras_factura_id` (`factura_id`);

-- ============================================================
-- Verificación
-- ============================================================
-- DESCRIBE compras_facturas;
-- DESCRIBE compras_factura_pagos;
-- SHOW COLUMNS FROM compras LIKE 'factura_id';
