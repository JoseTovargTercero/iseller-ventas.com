-- ============================================================
-- Migración 014: reglas de cantidad por producto
-- Base de datos: iseller
-- ============================================================
--
-- Si un producto no tiene fila aquí, la tienda usa mínimo 1 y paso 1.
-- Los valores se guardan con un decimal: 0.1, 0.2, 0.5, 1, etc.

CREATE TABLE IF NOT EXISTS `producto_reglas` (
  `producto_id` INT NOT NULL,
  `bss_id` INT NOT NULL,
  `minimo_cantidad` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
  `paso_cantidad` DECIMAL(6,2) NOT NULL DEFAULT 1.00,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`producto_id`),
  KEY `idx_producto_reglas_bss` (`bss_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
