-- Búsqueda de clientes por cédula en `base_nombres`.
--
-- La usan configurar/consulta_datoscliente.php (POS web),
-- api/pos_catalogo.php y api/pos_ventas.php (POS nativo).
-- Sin este índice cada consulta recorre las ~123.000 filas de la tabla.

SET @tabla := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'base_nombres'
);

SET @existe := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'base_nombres'
      AND INDEX_NAME = 'idx_base_nombres_cedula'
);

SET @sql := IF(
    @tabla = 0 OR @existe > 0,
    'SELECT 1',
    'ALTER TABLE `base_nombres` ADD INDEX `idx_base_nombres_cedula` (`cedula`)'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
