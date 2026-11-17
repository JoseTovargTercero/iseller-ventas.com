-- ============================================================
-- Migración 030: Entrada de menú para compras con factura
-- Base de datos: iseller
-- Fecha: 2026-11-16
-- ============================================================
-- "Nueva compra" y "Nueva compra con factura" conviven en el menú
-- y ya no dependen de la configuración `factura`.
-- ============================================================

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'stock', 'Nueva compra con factura', 'compras_facturas.php', 'receipt-outline', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'compras_facturas.php'
);

-- ============================================================
-- Verificación
-- ============================================================
-- SELECT id, categoria, nombre, dir, icono FROM menu WHERE dir IN ('nuevaCompra.php','compras_facturas.php');
