-- ============================================================
-- Migración 002: Módulo de Empleados — iSeller POS
-- Fecha: 2026-09-02
-- Base de datos: iseller
-- ============================================================

-- ============================================================
-- 1. Tabla: empleados
-- ============================================================
CREATE TABLE IF NOT EXISTS `empleados` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `nombre`            VARCHAR(150)  NOT NULL,
  `rol`               VARCHAR(100)  NOT NULL,
  `tipo_pago`         ENUM('SEMANAL','MENSUAL') NOT NULL,
  `monto_pago`        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `moneda`            ENUM('USD','VES','COP') NOT NULL DEFAULT 'USD',
  `dia_ejecucion`     VARCHAR(50)   DEFAULT NULL COMMENT 'SEMANAL: 1-7 (lun-dom); MENSUAL: 1-31 (dia mes)',
  `recurrente_id`     INT(11)       DEFAULT NULL COMMENT 'FK a gastos_recurrentes',
  `activo`            TINYINT(1)    NOT NULL DEFAULT 1,
  `observacion`       TEXT          DEFAULT NULL,
  `usuario_id`        INT(11)       NOT NULL,
  `id_sucursal`       INT(11)       NOT NULL,
  `bss_id`            INT(11)       NOT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bss_suc` (`bss_id`, `id_sucursal`),
  FOREIGN KEY (`recurrente_id`) REFERENCES `gastos_recurrentes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Categoría "Personal" para cada negocio existente
-- ============================================================
INSERT IGNORE INTO `gastos_categorias` (`nombre`, `bss_id`)
SELECT 'Personal', n.id
FROM `negocio` n
WHERE NOT EXISTS (
  SELECT 1 FROM `gastos_categorias` gc
  WHERE gc.nombre = 'Personal' AND gc.bss_id = n.id
);

-- ============================================================
-- 3. Menú: agregar "Empleados" dentro de categoría "Gastos"
-- ============================================================
INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`)
SELECT 'Gastos', 'Empleados', 'empleados.php', 'people-outline'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'empleados.php'
);

-- ============================================================
-- 4. Verificación
-- ============================================================
-- SHOW TABLES LIKE 'empleado%';
-- DESCRIBE empleados;
-- SELECT m.id, m.categoria, m.nombre, m.dir, m.icono FROM menu m WHERE m.categoria = 'Gastos';
-- SELECT gc.id, gc.nombre, gc.bss_id FROM gastos_categorias gc WHERE gc.nombre = 'Personal';
