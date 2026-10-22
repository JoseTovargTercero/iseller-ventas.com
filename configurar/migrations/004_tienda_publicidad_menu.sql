-- ============================================================
-- Migración 004: Agregar "Publicidad" a la sección Tienda Online
-- ============================================================

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`)
SELECT 'Tienda Online', 'Publicidad', 'tienda_publicidad.php', 'bx bx-megaphone'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'tienda_publicidad.php'
);
