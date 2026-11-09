-- ============================================================
-- Migración 021: Opción de registro de compras con factura
-- Base de datos: iseller
-- Fecha: 2026-11-07
-- ============================================================

ALTER TABLE `configuracion`
  ADD COLUMN IF NOT EXISTS `factura` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = registro de compras con factura' AFTER `registro_clientes`;

-- ============================================================
-- Verificación
-- ============================================================
-- DESCRIBE configuracion;
-- SELECT bss_id, factura FROM configuracion;
