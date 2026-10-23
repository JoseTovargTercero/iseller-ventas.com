-- ============================================================
-- Migración 006: historial de estados de los pedidos web
-- Base de datos: iseller
-- ============================================================
--
-- Guarda una fila por cada cambio de estado de tienda_ordenes, con la hora
-- exacta del cambio, para medir cuánto dura un pedido en cada estado.
--
-- El primer estado (1 = Pendiente) no se duplica aquí: su inicio es
-- tienda_ordenes.created. Cada fila marca el fin del estado anterior y el
-- inicio del nuevo.
--
-- `origen` indica quién hizo el cambio: 'panel' (tienda_pedidos.php) o
-- 'app' (api/pedido_entregado.php, app de entrega).
--
-- Los cambios anteriores a esta migración no se pueden reconstruir: el
-- seguimiento empieza a partir de su ejecución.

CREATE TABLE IF NOT EXISTS `tienda_ordenes_estados` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `orden_id` INT(11)      NOT NULL,
  `bss_id`   INT(11)      NOT NULL,
  `status`   TINYINT(4)   NOT NULL,
  `origen`   VARCHAR(20)  NOT NULL DEFAULT 'panel',
  `created`  DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_orden_estados_orden` (`orden_id`, `created`),
  KEY `idx_orden_estados_bss` (`bss_id`, `status`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
