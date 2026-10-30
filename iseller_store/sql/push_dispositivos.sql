-- ============================================================
--  Tokens de dispositivos para notificaciones push (FCM)
--  Un registro por token. `tipo` distingue a quién pertenece:
--    'panel'   -> usuarios del panel del negocio (tabla usuarios)
--    'cliente' -> usuarios de la tienda (tabla tienda_usuarios)
--    'transp'  -> transportistas (tabla transportistas)
--  Los ids de ambas tablas son independientes, por eso `tipo`.
--  El índice es por (token, tipo, usuario_id): un mismo teléfono puede estar
--  logueado a la vez en el panel y en la app del transportista.
-- ============================================================

CREATE TABLE IF NOT EXISTS `push_dispositivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(10) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `bss_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `plataforma` varchar(20) NOT NULL DEFAULT 'android',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_push_token_tipo_usuario` (`token`(191), `tipo`, `usuario_id`),
  KEY `idx_push_usuario` (`tipo`, `usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
