-- ============================================================
-- Migración 019: resumen mensual de ganancias y gastos (caché)
-- Fecha: 2026-11-07
-- Base de datos: iseller
-- ============================================================

-- Guarda el cálculo de meses ya cerrados para no recalcularlos
-- en cada visita al módulo de Gastos. El mes en curso no se guarda.
CREATE TABLE IF NOT EXISTS `gastos_resumen_mensual` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`        INT(11)       NOT NULL,
  `id_sucursal`   INT(11)       NOT NULL DEFAULT 0 COMMENT '0 = todas las sucursales del negocio',
  `mes`           VARCHAR(7)    NOT NULL COMMENT 'Y-m',
  `ganancias`     DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Ventas - costo de artículos',
  `gastos`        DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Gastos activos del mes',
  `calculado_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resumen` (`bss_id`, `id_sucursal`, `mes`),
  KEY `idx_bss_mes` (`bss_id`, `mes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
