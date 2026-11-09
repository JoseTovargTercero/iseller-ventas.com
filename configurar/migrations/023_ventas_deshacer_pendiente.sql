-- ============================================================
-- Migración 023: Solicitud de eliminación de ventas
-- Base de datos: iseller
-- Fecha: 2026-11-08
-- ============================================================

ALTER TABLE `orden`
  ADD COLUMN IF NOT EXISTS `deshacer_pendiente` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `deshacer_solicitado_por` INT(11) DEFAULT NULL AFTER `deshacer_pendiente`,
  ADD COLUMN IF NOT EXISTS `deshacer_solicitado_en` DATETIME DEFAULT NULL AFTER `deshacer_solicitado_por`;

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW COLUMNS FROM orden LIKE 'deshacer%';
