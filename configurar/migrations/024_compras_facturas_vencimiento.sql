-- ============================================================
-- Migración 024: Vencimiento del crédito en facturas de compra
-- Base de datos: iseller
-- Fecha: 2026-11-08
-- ============================================================

ALTER TABLE `compras_facturas`
  ADD COLUMN IF NOT EXISTS `fecha_vencimiento` DATE DEFAULT NULL AFTER `fecha_factura`;

-- Para facturas pendientes creadas antes de esta migración, se puede
-- definir el vencimiento desde el módulo con el botón de calendario.

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW COLUMNS FROM compras_facturas LIKE 'fecha_vencimiento';
