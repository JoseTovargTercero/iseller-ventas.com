-- ============================================================
-- Migración 001: Módulo de Gastos — iSeller POS
-- Fecha: 2026-08-28
-- Base de datos: iseller
-- ============================================================

-- ============================================================
-- 1. Tabla: gastos_categorias
-- ============================================================
CREATE TABLE IF NOT EXISTS `gastos_categorias` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL,
  `padre_id`    INT(11)      DEFAULT NULL COMMENT 'NULL = raíz; ID = subcategoría',
  `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
  `bss_id`      INT(11)      NOT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bss`   (`bss_id`),
  INDEX `idx_padre` (`padre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Tabla: gastos_recurrentes
-- ============================================================
CREATE TABLE IF NOT EXISTS `gastos_recurrentes` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `concepto`        VARCHAR(255) NOT NULL,
  `categoria_id`    INT(11)      DEFAULT NULL,
  `tipo`            ENUM('FIJO','VARIABLE') NOT NULL DEFAULT 'FIJO',
  `frecuencia`      ENUM('DIARIO','SEMANAL','QUINCENAL','MENSUAL',
                         'BIMESTRAL','TRIMESTRAL','SEMESTRAL','ANUAL') NOT NULL,
  `monto_estimado`  DECIMAL(15,2) DEFAULT NULL COMMENT 'Referencial, no obligatorio',
  `moneda`          ENUM('USD','VES','COP') NOT NULL DEFAULT 'USD',
  `dia_ejecucion`   VARCHAR(50)  DEFAULT NULL COMMENT 'SEMANAL: 1-7 (dia semana); MENSUAL: 1-31 (dia mes, ajuste automatico)',
  `fecha_inicio`    DATE         NOT NULL,
  `fecha_fin`       DATE         DEFAULT NULL COMMENT 'NULL = sin vencimiento',
  `activo`          TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `observacion`     TEXT         DEFAULT NULL,
  `usuario_id`      INT(11)      NOT NULL,
  `bss_id`          INT(11)      NOT NULL,
  `id_sucursal`     INT(11)      NOT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activo_bss` (`activo`, `bss_id`, `id_sucursal`),
  FOREIGN KEY (`categoria_id`) REFERENCES `gastos_categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Tabla: gastos
-- ============================================================
CREATE TABLE IF NOT EXISTS `gastos` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `codigo`            VARCHAR(20)   NOT NULL COMMENT 'G-000001, secuencial por bss_id',
  `fecha`             DATE          NOT NULL,
  `semana`            VARCHAR(10)   NOT NULL COMMENT 'Y-W — para cálculos de ganancias',
  `mes`               VARCHAR(7)    NOT NULL COMMENT 'Y-m — para cálculos de ganancias',
  `concepto`          VARCHAR(255)  NOT NULL,
  `categoria_id`      INT(11)       DEFAULT NULL,
  `tipo`              ENUM('FIJO','VARIABLE') NOT NULL,
  `frecuencia`        ENUM('UNICO','DIARIO','SEMANAL','QUINCENAL','MENSUAL',
                           'BIMESTRAL','TRIMESTRAL','SEMESTRAL','ANUAL') NOT NULL,
  `moneda`            ENUM('USD','VES','COP') NOT NULL DEFAULT 'USD',
  `monto`             DECIMAL(15,2) NOT NULL COMMENT 'Monto en la moneda original registrada',
  `tasa_cambio`       DECIMAL(15,4) NOT NULL DEFAULT 1.0000 COMMENT 'Tasa vigente al registrar',
  `monto_usd`         DECIMAL(15,2) NOT NULL COMMENT 'Equivalente en USD — afecta cálculo de ganancias',
  `estado`            ENUM('ACTIVO','ANULADO') NOT NULL DEFAULT 'ACTIVO',
  `observacion`       TEXT          DEFAULT NULL,
  `recurrente_id`     INT(11)       DEFAULT NULL COMMENT 'FK a gastos_recurrentes si fue generado desde allí',
  `usuario_id`        INT(11)       NOT NULL COMMENT '$_SESSION[id]',
  `usuario_anulacion` INT(11)       DEFAULT NULL,
  `fecha_anulacion`   DATETIME      DEFAULT NULL,
  `motivo_anulacion`  TEXT          DEFAULT NULL,
  `id_sucursal`       INT(11)       NOT NULL COMMENT '$_SESSION[sucursal]',
  `bss_id`            INT(11)       NOT NULL COMMENT '$_SESSION[bss_id]',
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_semana`  (`semana`, `bss_id`, `id_sucursal`),
  INDEX `idx_mes`     (`mes`, `bss_id`, `id_sucursal`),
  INDEX `idx_fecha`   (`fecha`, `bss_id`, `id_sucursal`),
  INDEX `idx_estado`  (`estado`),
  FOREIGN KEY (`categoria_id`)  REFERENCES `gastos_categorias`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`recurrente_id`) REFERENCES `gastos_recurrentes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Categorías iniciales para cada negocio existente
-- ============================================================
INSERT IGNORE INTO `gastos_categorias` (`nombre`, `bss_id`)
SELECT c.nombre, n.id
FROM (
  SELECT 'Personal'               AS nombre UNION ALL
  SELECT 'Alquiler'               UNION ALL
  SELECT 'Servicios públicos'     UNION ALL
  SELECT 'Mantenimiento'          UNION ALL
  SELECT 'Reparaciones'           UNION ALL
  SELECT 'Transporte'             UNION ALL
  SELECT 'Publicidad'             UNION ALL
  SELECT 'Papelería'              UNION ALL
  SELECT 'Limpieza'               UNION ALL
  SELECT 'Tecnología'             UNION ALL
  SELECT 'Comisiones'             UNION ALL
  SELECT 'Impuestos'              UNION ALL
  SELECT 'Gastos administrativos' UNION ALL
  SELECT 'Otros'
) c
CROSS JOIN `negocio` n;

-- ============================================================
-- 5. Verificación
-- ============================================================
-- Ejecutar después de la migración para confirmar:
-- SHOW TABLES LIKE 'gasto%';
-- DESCRIBE gastos_categorias;
-- DESCRIBE gastos_recurrentes;
-- DESCRIBE gastos;
-- SELECT COUNT(*) AS total_categorias FROM gastos_categorias;
