-- ============================================================
-- Migración 029: subproducto de respaldo para variaciones
-- Base de datos: iseller
-- ============================================================
--
-- Cada variación puede apuntar a otra variación del mismo padre.
-- Al vender, si su stock no alcanza, se descuenta del respaldo y
-- luego del producto padre. 0 = sin respaldo.

ALTER TABLE `productos`
  ADD COLUMN `id_producto_respaldo` INT NOT NULL DEFAULT 0 AFTER `id_producto_relacionado`,
  ADD KEY `idx_prod_respaldo` (`bss_id`, `id_producto_respaldo`);
