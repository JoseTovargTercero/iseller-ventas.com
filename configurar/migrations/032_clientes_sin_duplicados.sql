-- Clientes sin duplicados por (bss_id, cédula).
--
-- 1) Índice de apoyo para buscar por cédula dentro de un negocio.
-- 2) Tabla-candado que hace atómico el registro de clientes.
--
-- configurar/accion_carta.php (POS web) y api/pos_ventas.php (POS nativo)
-- usan cl_registrar(): reclaman (bss_id, cedula) en `clientes_claves` y solo
-- insertan en `clientes` quien logra reclamarla. Así dos ventas simultáneas
-- no pueden crear el mismo cliente.
--
-- No se puede poner UNIQUE en clientes(bss_id, cedula) porque las bases ya
-- tienen duplicados históricos; `clientes_claves` sí es único y se siembra
-- con las claves existentes.

SET @existe := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'clientes'
      AND INDEX_NAME = 'idx_clientes_bss_cedula'
);

SET @sql := IF(
    @existe = 0,
    'ALTER TABLE `clientes` ADD INDEX `idx_clientes_bss_cedula` (`bss_id`, `cedula`)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `clientes_claves` (
    `bss_id`    INT(11)  NOT NULL,
    `cedula`    INT(11)  NOT NULL,
    `creado_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`bss_id`, `cedula`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `clientes_claves` (`bss_id`, `cedula`)
SELECT `bss_id`, `cedula`
FROM `clientes`
WHERE `bss_id` IS NOT NULL
  AND `cedula` IS NOT NULL
  AND `cedula` <> 0;
