-- Fase 1: Unificación de Base de Datos y Nuevas Columnas/Tablas

-- 1. Agregar columnas a la tabla de negocios en la BD principal
ALTER TABLE `iseller`.`negocio` 
ADD COLUMN `tiene_tienda_virtual` TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN `slug` VARCHAR(150) NULL UNIQUE,
ADD COLUMN `logo_url` VARCHAR(255) NULL,
ADD COLUMN `config_delivery` JSON NULL,
ADD COLUMN `datos_bancarios` JSON NULL;

-- 2. Crear tabla para relacionar productos con la tienda de cada negocio y guardar sus fotos personalizadas
CREATE TABLE `iseller`.`negocio_productos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `negocio_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `foto_url` VARCHAR(255) NULL,
  `estado_publicacion` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_negocio_producto` (`negocio_id`, `producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fase 7.1: Directorio de fotos de tiendas (crear manualmente en el servidor)
-- Ruta: iseller_store/assets/img/tiendas/{negocio_id}/{producto_id}_{timestamp}.jpg
-- La API tienda_productos_api.php crea el subdirectorio por negocio automáticamente.

-- 3. Unificación: Mover tablas de iseller_store a iseller (Renombrando para evitar colisiones)
-- Asegurar que existan y copiarlas a iseller con el prefijo tienda_
CREATE TABLE `iseller`.`tienda_usuarios` LIKE `iseller_store`.`usuarios`;
INSERT INTO `iseller`.`tienda_usuarios` SELECT * FROM `iseller_store`.`usuarios`;

CREATE TABLE `iseller`.`tienda_usuarios_direcciones` LIKE `iseller_store`.`usuarios_direcciones`;
INSERT INTO `iseller`.`tienda_usuarios_direcciones` SELECT * FROM `iseller_store`.`usuarios_direcciones`;

CREATE TABLE `iseller`.`tienda_ordenes` LIKE `iseller_store`.`orden`;
INSERT INTO `iseller`.`tienda_ordenes` SELECT * FROM `iseller_store`.`orden`;

CREATE TABLE `iseller`.`tienda_orden_articulos` LIKE `iseller_store`.`orden_articulos`;
INSERT INTO `iseller`.`tienda_orden_articulos` SELECT * FROM `iseller_store`.`orden_articulos`;

CREATE TABLE `iseller`.`tienda_compras_por_usuarios` LIKE `iseller_store`.`compras_por_usuarios`;
INSERT INTO `iseller`.`tienda_compras_por_usuarios` SELECT * FROM `iseller_store`.`compras_por_usuarios`;

CREATE TABLE `iseller`.`tienda_historial_puntos` LIKE `iseller_store`.`historial_puntos`;
INSERT INTO `iseller`.`tienda_historial_puntos` SELECT * FROM `iseller_store`.`historial_puntos`;

CREATE TABLE `iseller`.`tienda_recompensas_usuario` LIKE `iseller_store`.`recompensas_usuario`;
INSERT INTO `iseller`.`tienda_recompensas_usuario` SELECT * FROM `iseller_store`.`recompensas_usuario`;

CREATE TABLE `iseller`.`tienda_chat_categorias` LIKE `iseller_store`.`chat_categorias`;
INSERT INTO `iseller`.`tienda_chat_categorias` SELECT * FROM `iseller_store`.`chat_categorias`;

CREATE TABLE `iseller`.`tienda_chat_conversaciones` LIKE `iseller_store`.`chat_conversaciones`;
INSERT INTO `iseller`.`tienda_chat_conversaciones` SELECT * FROM `iseller_store`.`chat_conversaciones`;

CREATE TABLE `iseller`.`tienda_chat_mensajes` LIKE `iseller_store`.`chat_mensajes`;
INSERT INTO `iseller`.`tienda_chat_mensajes` SELECT * FROM `iseller_store`.`chat_mensajes`;

CREATE TABLE `iseller`.`tienda_chat_mensajes_automaticos` LIKE `iseller_store`.`chat_mensajes_automaticos`;
INSERT INTO `iseller`.`tienda_chat_mensajes_automaticos` SELECT * FROM `iseller_store`.`chat_mensajes_automaticos`;

-- 4. Adaptar tienda_ordenes para relacionarse con negocio
ALTER TABLE `iseller`.`tienda_ordenes`
ADD COLUMN `negocio_id` INT NULL AFTER `id`;
