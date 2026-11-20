-- ============================================================
-- Migración 034: Auditoría de movimientos de stock
-- Base de datos: iseller
-- Fecha: 2026-11-20
-- ============================================================
-- Registra el stock anterior y el stock nuevo de cada producto
-- afectado por compras clásicas, compras con factura y
-- transferencias entre sucursales.
--
-- La aplicación también crea esta tabla automáticamente desde
-- configurar/_stock_auditoria.php si la migración no se ejecutó.
-- ============================================================

CREATE TABLE IF NOT EXISTS `stock_auditoria` (
  `id`                  BIGINT(20)    NOT NULL AUTO_INCREMENT,
  `bss_id`              INT(11)       NOT NULL,
  `id_sucursal`         INT(11)       NOT NULL,
  `id_producto`         INT(11)       NOT NULL,
  `producto_nombre`     VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
  `tipo_movimiento`     VARCHAR(40)   NOT NULL COMMENT 'COMPRA, COMPRA_FACTURA, TRANSFERENCIA_SALIDA, TRANSFERENCIA_ENTRADA',
  `origen`              VARCHAR(60)   NOT NULL DEFAULT '' COMMENT 'nueva_compra, compra_factura, transferencia',
  `referencia_tipo`     VARCHAR(40)   DEFAULT NULL COMMENT 'compra, factura_compra, stock_traslado',
  `referencia_id`       INT(11)       DEFAULT NULL,
  `cantidad`            DECIMAL(15,4) NOT NULL DEFAULT 0 COMMENT 'Cantidad movida, siempre positiva',
  `stock_anterior`      DECIMAL(15,4) NOT NULL DEFAULT 0,
  `stock_nuevo`         DECIMAL(15,4) NOT NULL DEFAULT 0,
  `diferencia`          DECIMAL(15,4) NOT NULL DEFAULT 0 COMMENT 'stock_nuevo - stock_anterior',
  `id_sucursal_origen`  INT(11)       DEFAULT NULL,
  `id_sucursal_destino` INT(11)       DEFAULT NULL,
  `detalle`             TEXT          DEFAULT NULL COMMENT 'Datos adicionales en JSON',
  `usuario_id`          INT(11)       NOT NULL DEFAULT 0,
  `usuario_nombre`      VARCHAR(150)  NOT NULL DEFAULT '' COMMENT 'Copia histórica',
  `ip`                  VARCHAR(45)   DEFAULT NULL,
  `user_agent`          VARCHAR(255)  DEFAULT NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sa_bss_fecha`       (`bss_id`, `created_at`),
  KEY `idx_sa_bss_suc_fecha`   (`bss_id`, `id_sucursal`, `created_at`),
  KEY `idx_sa_bss_prod_fecha`  (`bss_id`, `id_producto`, `created_at`),
  KEY `idx_sa_bss_tipo_fecha`  (`bss_id`, `tipo_movimiento`, `created_at`),
  KEY `idx_sa_referencia`      (`referencia_tipo`, `referencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Verificación
-- ============================================================
-- SHOW TABLES LIKE 'stock_auditoria';
-- DESCRIBE stock_auditoria;
-- SELECT * FROM stock_auditoria ORDER BY id DESC LIMIT 20;
