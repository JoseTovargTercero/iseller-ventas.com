-- ============================================================
-- Migración 027: variaciones de productos
-- Base de datos: iseller
-- ============================================================
--
-- Reemplaza la entrada de menú del antiguo módulo de canal por el
-- módulo de variaciones. La relación padre/hijo usa la columna
-- productos.id_producto_relacionado, por lo que no se requieren
-- cambios adicionales de esquema.

UPDATE `menu`
SET `nombre` = 'Variaciones de productos',
    `dir` = 'variaciones.php',
    `icono` = 'git-branch-outline'
WHERE `dir` = 'canal.php';

INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'stock', 'Variaciones de productos', 'variaciones.php', 'git-branch-outline', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'variaciones.php'
);
