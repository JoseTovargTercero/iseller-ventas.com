-- ============================================================
-- Migración 010: Mis transportistas (relación por negocio)
-- Base de datos: iseller
-- ============================================================
--
-- Los transportistas se registran en la app (tabla `transportistas`).
-- Cada negocio agrega por cédula a los que quiere ver en el select de
-- "Asignar transportista" de Pedidos Web.

CREATE TABLE IF NOT EXISTS `transportista_por_negocio` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transportista_id` INT UNSIGNED NOT NULL,
  `bss_id` INT UNSIGNED NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transportista_negocio` (`bss_id`, `transportista_id`),
  KEY `idx_tpn_transportista` (`transportista_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`)
SELECT 'Tienda Online', 'Mis transportistas', 'tienda_transportistas.php', 'bx bx-id-card'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'tienda_transportistas.php'
);
