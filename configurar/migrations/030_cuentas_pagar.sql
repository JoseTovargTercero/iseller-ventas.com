-- ============================================================
-- Migración 030: Cuentas por pagar (facturas por conceptos)
-- No afecta inventario: solo registra obligaciones y sus conceptos.
-- ============================================================

CREATE TABLE IF NOT EXISTS `cuentas_pagar_facturas` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`            INT(11)       NOT NULL,
  `id_sucursal`       INT(11)       NOT NULL,
  `proveedor`         VARCHAR(255)  NOT NULL,
  `numero_factura`    VARCHAR(100)  NOT NULL,
  `fecha_factura`     DATE          NOT NULL,
  `fecha_vencimiento` DATE          NULL,
  `total`             DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `estado`            ENUM('pendiente','pagada') NOT NULL DEFAULT 'pendiente',
  `usuario`           VARCHAR(150)  DEFAULT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpf_bss_estado` (`bss_id`, `estado`),
  KEY `idx_cpf_bss_fecha`  (`bss_id`, `fecha_factura`),
  KEY `idx_cpf_bss_num`    (`bss_id`, `numero_factura`),
  KEY `idx_cpf_sucursal`   (`id_sucursal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cuentas_pagar_conceptos` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `factura_id` INT(11)       NOT NULL,
  `bss_id`     INT(11)       NOT NULL,
  `concepto`   VARCHAR(255)  NOT NULL,
  `monto`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpc_factura` (`factura_id`),
  KEY `idx_cpc_bss`     (`bss_id`),
  CONSTRAINT `fk_cpc_factura` FOREIGN KEY (`factura_id`) REFERENCES `cuentas_pagar_facturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cuentas_pagar_pagos` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `factura_id` INT(11)       NOT NULL,
  `bss_id`     INT(11)       NOT NULL,
  `monto`      DECIMAL(15,2) NOT NULL,
  `fecha`      DATE          NOT NULL,
  `usuario`    VARCHAR(150)  DEFAULT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cpp_factura` (`factura_id`),
  KEY `idx_cpp_bss`     (`bss_id`),
  CONSTRAINT `fk_cpp_factura` FOREIGN KEY (`factura_id`) REFERENCES `cuentas_pagar_facturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entrada de menú. Se reutiliza la categoría de compras si existe.
INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT COALESCE(
           (SELECT m.categoria FROM `menu` m WHERE m.dir = 'nuevaCompra.php' LIMIT 1),
           (SELECT m2.categoria FROM `menu` m2 WHERE m2.dir = 'compras_facturas.php' LIMIT 1),
           'Compras'
       ),
       'Cuentas por pagar', 'cuentas_pagar.php', 'wallet-outline', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `menu` m3 WHERE m3.dir = 'cuentas_pagar.php');

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW TABLES LIKE 'cuentas_pagar_%';
-- SELECT id, categoria, nombre, dir FROM `menu` WHERE dir = 'cuentas_pagar.php';
