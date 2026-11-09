-- ============================================================
-- Migración 025: Hora exacta de las compras
-- Base de datos: iseller
-- Fecha: 2026-11-08
-- ============================================================

-- MySQL no admite ADD COLUMN IF NOT EXISTS. Se comprueba primero.
SET @existe := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'compras'
    AND COLUMN_NAME = 'created_at'
);

SET @sql := IF(
  @existe = 0,
  'ALTER TABLE `compras` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL AFTER `fecha`',
  'DO 0'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Rellena las compras existentes desde la fecha visible (d-m-Y h:i a).
UPDATE `compras`
SET `created_at` = STR_TO_DATE(`fecha`, '%d-%m-%Y %h:%i %p')
WHERE `created_at` IS NULL
  AND `fecha` IS NOT NULL
  AND `fecha` <> '';

-- La última compra de cada producto/sucursal toma la hora exacta que guardó
-- el stock (ultima_compra_at). Así una merma registrada segundos antes de la
-- compra conserva el orden correcto.
UPDATE `compras` c
JOIN (
  SELECT MAX(`id`) AS `id`
  FROM `compras`
  GROUP BY `cod`, `id_sucursal`, `bss_id`
) ult ON ult.`id` = c.`id`
JOIN `stock` s
  ON s.`id_producto` = CAST(c.`cod` AS UNSIGNED)
 AND s.`id_sucursal` = c.`id_sucursal`
 AND s.`bss_id` = c.`bss_id`
SET c.`created_at` = s.`ultima_compra_at`
WHERE s.`ultima_compra_at` IS NOT NULL
  AND (c.`created_at` IS NULL OR s.`ultima_compra_at` > c.`created_at`);

-- Las compras nuevas guardan la hora exacta automáticamente.
ALTER TABLE `compras`
  MODIFY COLUMN `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

-- ============================================================
-- Verificación
-- ============================================================
-- SELECT id, cod, fecha, created_at FROM compras ORDER BY id DESC LIMIT 10;
