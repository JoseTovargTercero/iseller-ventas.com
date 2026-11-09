-- ============================================================
-- Migración 024: Vencimiento del crédito en facturas de compra
-- Base de datos: iseller
-- Fecha: 2026-11-08
-- ============================================================

-- MySQL no admite ADD COLUMN IF NOT EXISTS. Se comprueba primero.
SET @existe := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'compras_facturas'
    AND COLUMN_NAME = 'fecha_vencimiento'
);

SET @sql := IF(
  @existe = 0,
  'ALTER TABLE `compras_facturas` ADD COLUMN `fecha_vencimiento` DATE NULL AFTER `fecha_factura`',
  'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Para facturas pendientes creadas antes de esta migración, se puede
-- definir el vencimiento desde el módulo con el botón de calendario.

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW COLUMNS FROM compras_facturas LIKE 'fecha_vencimiento';
-- ============================================================
