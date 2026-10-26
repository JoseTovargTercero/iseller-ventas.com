-- ============================================================
-- Migración 012: sección de productos principales (recomendados)
-- Base de datos: iseller
-- ============================================================
--
-- destacados_activo: 1 = mostrar el cintillo en el perfil de la tienda.
-- destacados_ids: JSON con hasta 10 ids de productos (orden de visualización).

ALTER TABLE `negocio`
  ADD COLUMN `destacados_activo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `horarios`,
  ADD COLUMN `destacados_ids` TEXT NULL DEFAULT NULL AFTER `destacados_activo`;
