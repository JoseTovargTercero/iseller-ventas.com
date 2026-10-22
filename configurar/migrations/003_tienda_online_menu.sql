-- ============================================================
-- Migración 003: Agregar "Tienda Online" al menú principal
-- ============================================================

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`)
SELECT NULL, 'Tienda Online', '../iseller_store/admin/index.php', 'storefront-outline'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = '../iseller_store/admin/index.php'
);
