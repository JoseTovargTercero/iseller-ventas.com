-- ============================================================
-- Migración 013: seguimiento de deliverys
-- Base de datos: iseller
-- ============================================================
--
-- Agrega el módulo a la sección "Tienda Online". La fila de `menu` es
-- también el permiso que se asigna a los empleados desde Permisos.

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'Tienda Online', 'Seguimiento de deliverys', 'tienda_seguimiento.php', 'bx bx-map-alt', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'tienda_seguimiento.php'
);
