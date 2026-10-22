-- ============================================================
-- Migración 005: índices para los reportes de ventas
-- (listaVentas día / semana / mes y dashboard)
-- Base de datos: iseller
-- ============================================================
--
-- Las consultas filtran por bss_id + status IN (1,4) + rango de created.
-- Sin este índice, cada reporte recorre la tabla `orden` completa.
-- Ejecutar una sola vez (en producción puede tardar y bloquear escrituras
-- mientras se construye; hacerlo en horario de baja actividad).

ALTER TABLE `orden` ADD INDEX `idx_orden_reporte` (`bss_id`, `status`, `created`);
