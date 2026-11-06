-- ============================================================
-- Migración 018: comprobante de abonos entre sucursales
-- ============================================================

ALTER TABLE `pagos_sucursales`
  ADD COLUMN IF NOT EXISTS `comprobante` VARCHAR(255) DEFAULT NULL
  AFTER `observaciones`;
