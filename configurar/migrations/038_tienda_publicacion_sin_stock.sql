-- Método de publicación de productos de la tienda virtual.
-- 0 = solo se publican/venden productos con stock positivo (comportamiento anterior).
-- 1 = los productos permanecen publicados y se permite comprar aunque no haya stock.
ALTER TABLE negocio
    ADD COLUMN publicar_sin_stock TINYINT(1) NOT NULL DEFAULT 0 AFTER destacados_ids;
