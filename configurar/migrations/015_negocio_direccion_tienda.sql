-- ============================================================
-- Migración 015: dirección / descripción de la ubicación de la tienda
-- Base de datos: iseller
-- ============================================================
--
-- Texto libre que el dueño escribe para describir dónde está el local.
-- Se muestra en el perfil de la tienda y cuando el cliente elige
-- "Retiro en tienda" en el checkout.

ALTER TABLE `negocio`
  ADD COLUMN `direccion_tienda` VARCHAR(255) NULL DEFAULT NULL AFTER `comunidad`;
