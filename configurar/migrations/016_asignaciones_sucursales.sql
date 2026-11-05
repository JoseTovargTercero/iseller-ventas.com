-- ============================================================
-- Migración 016: Asignación de mercancía y deudas entre sucursales
-- Base de datos: iseller
-- Fecha: 2026-11-04
-- ============================================================

-- ============================================================
-- 1. Cabecera de asignaciones
-- ============================================================
CREATE TABLE IF NOT EXISTS `asignaciones_sucursales` (
  `id`                INT(11)        NOT NULL AUTO_INCREMENT,
  `bss_id`            INT(11)        NOT NULL,
  `numero_documento`  VARCHAR(30)    DEFAULT NULL COMMENT 'ASG-000001. NULL mientras se crea el borrador',
  `sucursal_origen`   INT(11)        NOT NULL,
  `sucursal_destino`  INT(11)        NOT NULL,
  `fecha`             DATE           NOT NULL,
  `usuario_id`        INT(11)        NOT NULL,
  `estado`            ENUM('BORRADOR','APLICADO') NOT NULL DEFAULT 'BORRADOR',
  `descuenta_origen`  TINYINT(1)     NOT NULL DEFAULT 1 COMMENT '1 = descuenta inventario de origen',
  `suma_destino`      TINYINT(1)     NOT NULL DEFAULT 1 COMMENT '1 = suma inventario en destino',
  `total`             DECIMAL(15,2)  NOT NULL DEFAULT 0.00 COMMENT 'Costo histórico total en USD',
  `observaciones`     TEXT           DEFAULT NULL,
  `aplicado_at`       DATETIME       DEFAULT NULL,
  `created_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_asig_numero` (`bss_id`, `numero_documento`),
  KEY `idx_asig_bss_estado` (`bss_id`, `estado`),
  KEY `idx_asig_origen` (`bss_id`, `sucursal_origen`),
  KEY `idx_asig_destino` (`bss_id`, `sucursal_destino`),
  KEY `idx_asig_fecha` (`bss_id`, `fecha`),
  KEY `idx_asig_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Detalle de productos de la asignación
-- ============================================================
CREATE TABLE IF NOT EXISTS `asignaciones_sucursales_detalles` (
  `id`                INT(11)        NOT NULL AUTO_INCREMENT,
  `asignacion_id`     INT(11)        NOT NULL,
  `producto_id`       INT(11)        NOT NULL,
  `producto_nombre`   VARCHAR(255)   NOT NULL COMMENT 'Copia histórica del nombre',
  `cantidad`          DECIMAL(15,4)  NOT NULL,
  `costo_unitario`    DECIMAL(15,4)  NOT NULL COMMENT 'Costo histórico por unidad',
  `subtotal`          DECIMAL(15,2)  NOT NULL,
  `created_at`        DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_detalle_producto` (`asignacion_id`, `producto_id`),
  KEY `idx_detalle_producto` (`producto_id`),
  CONSTRAINT `fk_asig_detalle_asignacion`
    FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones_sucursales` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Obligación económica (cuenta por cobrar entre sucursales)
-- ============================================================
CREATE TABLE IF NOT EXISTS `cuentas_sucursales` (
  `id`                  INT(11)        NOT NULL AUTO_INCREMENT,
  `bss_id`              INT(11)        NOT NULL,
  `asignacion_id`       INT(11)        NOT NULL,
  `sucursal_acreedora`  INT(11)        NOT NULL COMMENT 'Sucursal que entrega la mercancía',
  `sucursal_deudora`    INT(11)        NOT NULL COMMENT 'Sucursal que recibe la mercancía',
  `monto_original`      DECIMAL(15,2)  NOT NULL COMMENT 'Costo histórico de la asignación',
  `created_at`          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME       DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cuenta_asignacion` (`asignacion_id`),
  KEY `idx_cuenta_deudora` (`bss_id`, `sucursal_deudora`),
  KEY `idx_cuenta_acreedora` (`bss_id`, `sucursal_acreedora`),
  CONSTRAINT `fk_cuenta_asignacion`
    FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones_sucursales` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Historial de abonos y pagos
-- ============================================================
CREATE TABLE IF NOT EXISTS `pagos_sucursales` (
  `id`                  INT(11)        NOT NULL AUTO_INCREMENT,
  `bss_id`              INT(11)        NOT NULL,
  `cuenta_sucursal_id`  INT(11)        NOT NULL,
  `monto_usd`           DECIMAL(15,2)  NOT NULL,
  `fecha`               DATE           NOT NULL,
  `tasa_bcv`            DECIMAL(15,4)  DEFAULT NULL,
  `tasa_peso_dolar`     DECIMAL(15,4)  DEFAULT NULL COMMENT 'Dólar / peso al momento del abono',
  `metodo_pago`         VARCHAR(50)    DEFAULT NULL,
  `referencia`          VARCHAR(100)   DEFAULT NULL,
  `observaciones`       TEXT           DEFAULT NULL,
  `usuario_id`          INT(11)        NOT NULL,
  `created_at`          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pago_cuenta` (`cuenta_sucursal_id`),
  KEY `idx_pago_bss_fecha` (`bss_id`, `fecha`),
  CONSTRAINT `chk_pago_monto_positivo` CHECK (`monto_usd` > 0),
  CONSTRAINT `fk_pago_cuenta`
    FOREIGN KEY (`cuenta_sucursal_id`) REFERENCES `cuentas_sucursales` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. Movimientos de inventario generados por la asignación
-- ============================================================
CREATE TABLE IF NOT EXISTS `asignaciones_sucursales_movimientos` (
  `id`              INT(11)        NOT NULL AUTO_INCREMENT,
  `bss_id`          INT(11)        NOT NULL,
  `asignacion_id`   INT(11)        NOT NULL,
  `producto_id`     INT(11)        NOT NULL,
  `sucursal_id`     INT(11)        NOT NULL,
  `tipo`            ENUM('ORIGEN_DESCUENTO','DESTINO_INCREMENTO','REVERSION_ORIGEN','REVERSION_DESTINO') NOT NULL,
  `cantidad`        DECIMAL(15,4)  NOT NULL,
  `stock_antes`     DECIMAL(15,4)  NOT NULL DEFAULT 0,
  `stock_despues`   DECIMAL(15,4)  NOT NULL DEFAULT 0,
  `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mov_asignacion` (`asignacion_id`),
  KEY `idx_mov_sucursal` (`bss_id`, `sucursal_id`, `producto_id`),
  CONSTRAINT `fk_mov_asignacion`
    FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones_sucursales` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. Bitácora de creación, edición y aplicación
-- ============================================================
CREATE TABLE IF NOT EXISTS `asignaciones_sucursales_bitacora` (
  `id`                  INT(11)      NOT NULL AUTO_INCREMENT,
  `asignacion_id`       INT(11)      NOT NULL,
  `usuario_id`          INT(11)      NOT NULL,
  `accion`              ENUM('CREADA','EDITADA','APLICADA','RECTIFICADA') NOT NULL,
  `motivo`              TEXT         DEFAULT NULL,
  `datos_anteriores`    LONGTEXT     DEFAULT NULL,
  `datos_nuevos`        LONGTEXT     DEFAULT NULL,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bitacora_asignacion` (`asignacion_id`),
  CONSTRAINT `fk_bitacora_asignacion`
    FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones_sucursales` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. Menú: módulos dentro de Finanzas
-- ============================================================
INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'Finanzas', 'Asignación de mercancía', 'asignaciones.php', 'swap-horizontal-outline', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `dir` = 'asignaciones.php');

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'Finanzas', 'Deudas entre sucursales', 'deudas_sucursales.php', 'wallet-outline', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `menu` WHERE `dir` = 'deudas_sucursales.php');

-- ============================================================
-- 8. Verificación
-- ============================================================
-- SHOW TABLES LIKE 'asignaciones%';
-- SHOW TABLES LIKE 'cuentas_sucursales';
-- SHOW TABLES LIKE 'pagos_sucursales';
-- SELECT id, categoria, nombre, dir FROM menu WHERE dir IN ('asignaciones.php','deudas_sucursales.php');
