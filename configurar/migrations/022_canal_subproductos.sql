-- ============================================================
-- Migración 022: subproductos por canal y seguimiento estricto
-- Base de datos: iseller
-- ============================================================

-- Opción global del negocio.
ALTER TABLE `negocio`
  ADD COLUMN `seguimiento_estricto` TINYINT(1) NOT NULL DEFAULT 0;

-- Producto: unidad, rendimiento, modo y bandera de seguimiento estricto.
ALTER TABLE `productos`
  ADD COLUMN `unidad` VARCHAR(8) NOT NULL DEFAULT 'UND' AFTER `nombre`,
  ADD COLUMN `rendimiento` DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER `id_producto_relacionado`,
  ADD COLUMN `modo_control` ENUM('NORMAL','CANAL','DETALLADO') NOT NULL DEFAULT 'NORMAL' AFTER `rendimiento`,
  ADD COLUMN `seguimiento_estricto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `modo_control`,
  ADD KEY `idx_prod_relacionado` (`bss_id`, `id_producto_relacionado`),
  ADD KEY `idx_prod_estricto` (`bss_id`, `seguimiento_estricto`);

-- Interruptor de venta y datos de la última compra.
ALTER TABLE `stock`
  ADD COLUMN `visible_venta` TINYINT(1) NOT NULL DEFAULT 1 AFTER `stock`,
  ADD COLUMN `ultima_compra_at` DATETIME NULL AFTER `visible_venta`,
  ADD COLUMN `ultima_compra_kg` DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER `ultima_compra_at`,
  ADD KEY `idx_stock_visible` (`bss_id`, `id_sucursal`, `visible_venta`),
  ADD KEY `idx_stock_compra` (`bss_id`, `id_sucursal`, `ultima_compra_at`);

-- Lotes de canal/res.
CREATE TABLE IF NOT EXISTS `lotes` (
  `id`                    INT AUTO_INCREMENT PRIMARY KEY,
  `bss_id`                INT NOT NULL,
  `id_sucursal`           INT NOT NULL,
  `padre_id`              INT NOT NULL,
  `modo`                  ENUM('CANAL','DETALLADO') NOT NULL DEFAULT 'CANAL',
  `kg_inicial`            DECIMAL(15,4) NOT NULL,
  `kg_restante`           DECIMAL(15,4) NOT NULL,
  `diferencia_pendiente`  DECIMAL(15,4) NOT NULL DEFAULT 0,
  `conciliado`            TINYINT(1) NOT NULL DEFAULT 0,
  `costo_total`           DECIMAL(15,4) NOT NULL DEFAULT 0,
  `proveedor`             VARCHAR(150) NULL,
  `notas`                 TEXT NULL,
  `estado`                ENUM('ABIERTO','CERRADO','ANULADO') NOT NULL DEFAULT 'ABIERTO',
  `fecha_apertura`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre`          DATETIME NULL,
  `usuario_id`            INT NOT NULL,
  KEY `idx_lote_bss_suc` (`bss_id`, `id_sucursal`, `estado`),
  KEY `idx_lote_padre` (`bss_id`, `padre_id`, `estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Movimientos del lote.
CREATE TABLE IF NOT EXISTS `lotes_detalle` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `lote_id`         INT NOT NULL,
  `producto_id`     INT NULL,
  `cantidad`        DECIMAL(15,4) NOT NULL,
  `costo_unitario`  DECIMAL(15,4) NOT NULL DEFAULT 0,
  `origen`          ENUM('DESPIECE','VENTA','MERMA','AJUSTE') NOT NULL,
  `orden_id`        INT NULL,
  `usuario_id`      INT NOT NULL,
  `fecha`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_lote_det` (`lote_id`, `origen`),
  KEY `idx_lote_prod` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cantidades de venta como número, no texto. Necesario para kg y reportes.
ALTER TABLE `orden_articulos`
  MODIFY `quantity` DECIMAL(15,3) NOT NULL DEFAULT 0;

-- Entrada de menú.
INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
SELECT 'stock', 'Res en canal / Subproductos', 'canal.php', 'layers-outline', 0
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `menu` m WHERE m.dir = 'canal.php'
);
