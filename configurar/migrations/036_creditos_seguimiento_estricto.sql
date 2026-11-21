-- ============================================================
-- Migración 036: Seguimiento estricto y fecha límite de créditos
-- Base de datos: iseller
-- Fecha: 2026-11-21
-- ============================================================

-- MySQL no admite ADD COLUMN IF NOT EXISTS. Se comprueba primero.

SET @existe_seguimiento := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'creditos'
    AND COLUMN_NAME = 'seguimiento_estricto'
);

SET @sql_seguimiento := IF(
  @existe_seguimiento = 0,
  'ALTER TABLE `creditos` ADD COLUMN `seguimiento_estricto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cedula`',
  'DO 0'
);

PREPARE stmt FROM @sql_seguimiento;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @existe_fecha_limite := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'creditos'
    AND COLUMN_NAME = 'fecha_limite'
);

SET @sql_fecha_limite := IF(
  @existe_fecha_limite = 0,
  'ALTER TABLE `creditos` ADD COLUMN `fecha_limite` DATE NULL DEFAULT NULL AFTER `seguimiento_estricto`',
  'DO 0'
);

PREPARE stmt FROM @sql_fecha_limite;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- Verificación
-- ============================================================
-- DESCRIBE creditos;
-- SELECT id, negocio, seguimiento_estricto, fecha_limite FROM creditos ORDER BY id DESC LIMIT 10;
