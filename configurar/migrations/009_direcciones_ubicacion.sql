-- ============================================================
-- Migración 009: estado y municipio de las direcciones de entrega
-- Base de datos: iseller
-- ============================================================

ALTER TABLE `tienda_usuarios_direcciones`
  ADD COLUMN `geo_estado` VARCHAR(100) NULL DEFAULT NULL AFTER `comunidad`,
  ADD COLUMN `geo_municipio` VARCHAR(150) NULL DEFAULT NULL AFTER `geo_estado`;
