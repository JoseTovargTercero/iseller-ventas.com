-- ============================================================
-- Migración 028: Historial de precios de compra de productos
-- Base de datos: iseller
-- Fecha: 2026-11-13
-- ============================================================
--
-- Guarda cada cambio de precio_compra. Si el producto es base, también
-- se registra el cambio aplicado a cada presentación al mayor.

CREATE TABLE IF NOT EXISTS `productos_precio_historial` (
  `id`                      INT(11)       NOT NULL AUTO_INCREMENT,
  `bss_id`                  INT(11)       NOT NULL,
  `producto_id`             INT(11)       NOT NULL,
  `producto_nombre`         VARCHAR(255)  NOT NULL DEFAULT '',
  `es_mayor`                TINYINT(1)    NOT NULL DEFAULT 0,
  `producto_relacionado_id` INT(11)       DEFAULT NULL COMMENT 'Producto base que originó el cambio',
  `precio_anterior`         DECIMAL(15,4) NOT NULL DEFAULT 0,
  `precio_nuevo`            DECIMAL(15,4) NOT NULL DEFAULT 0,
  `origen`                  VARCHAR(30)   NOT NULL DEFAULT '' COMMENT 'compra, edicion, factura, variacion',
  `id_sucursal`             INT(11)       DEFAULT NULL,
  `usuario_id`              INT(11)       NOT NULL DEFAULT 0,
  `usuario_nombre`          VARCHAR(150)  NOT NULL DEFAULT '',
  `created_at`              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pph_bss_producto` (`bss_id`, `producto_id`, `created_at`),
  KEY `idx_pph_bss_fecha` (`bss_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificación:
-- SHOW TABLES LIKE 'productos_precio_historial';
-- SELECT * FROM productos_precio_historial ORDER BY id DESC LIMIT 10;
