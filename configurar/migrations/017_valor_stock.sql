-- ============================================================
-- Migración 017: Módulo "Valor del stock"
-- ============================================================

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'stock', 'Valor del stock', 'valor_stock.php', 'calculator-outline', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'valor_stock.php'
);

-- Verificación:
-- SELECT id, categoria, nombre, dir FROM menu WHERE dir = 'valor_stock.php';
