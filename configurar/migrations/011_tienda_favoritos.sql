-- ============================================================
-- Migración 011: tiendas favoritas de los clientes
-- Base de datos: iseller
-- ============================================================

CREATE TABLE IF NOT EXISTS `tienda_favoritos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `negocio_id` INT UNSIGNED NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_favorito` (`usuario_id`, `negocio_id`),
  KEY `idx_favorito_negocio` (`negocio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
