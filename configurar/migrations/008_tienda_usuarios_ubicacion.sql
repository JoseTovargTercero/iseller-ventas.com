-- ============================================================
-- Migración 008: estado y municipio de los clientes de la tienda
-- Base de datos: iseller
-- ============================================================
--
-- El registro web ya no pide teléfono; en su lugar pide estado y municipio.

ALTER TABLE `tienda_usuarios`
  ADD COLUMN `geo_estado` VARCHAR(100) NULL DEFAULT NULL AFTER `telefono`,
  ADD COLUMN `geo_municipio` VARCHAR(150) NULL DEFAULT NULL AFTER `geo_estado`;
