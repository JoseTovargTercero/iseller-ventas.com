-- ============================================================
-- Migración 007: estado y municipio de la tienda
-- Base de datos: iseller
-- ============================================================
--
-- El estado del negocio ya existe como enum('activo','inactivo'), por eso
-- las columnas de ubicación usan el prefijo geo_.

ALTER TABLE `negocio`
  ADD COLUMN `geo_estado` VARCHAR(100) NULL DEFAULT NULL AFTER `comunidad`,
  ADD COLUMN `geo_municipio` VARCHAR(150) NULL DEFAULT NULL AFTER `geo_estado`;
