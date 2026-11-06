-- ============================================================
-- Migración 017: conceptos y servicios sin inventario
-- Asignaciones entre sucursales (deudas especiales / logística)
-- ============================================================

CREATE TABLE IF NOT EXISTS `asignaciones_sucursales_conceptos` (
  `id`             INT(11)        NOT NULL AUTO_INCREMENT,
  `asignacion_id`  INT(11)        NOT NULL,
  `concepto`       VARCHAR(255)   NOT NULL,
  `monto`          DECIMAL(15,2)  NOT NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_concepto_asignacion` (`asignacion_id`),
  CONSTRAINT `fk_concepto_asignacion`
    FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones_sucursales` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
