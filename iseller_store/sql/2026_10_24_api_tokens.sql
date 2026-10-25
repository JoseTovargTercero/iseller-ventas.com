-- Tokens de sesión para las apps móviles (API v1).
-- El refresh se guarda hasheado (SHA-256); el valor en claro solo lo tiene la app.
CREATE TABLE IF NOT EXISTS `api_refresh_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `device_id` VARCHAR(64) NOT NULL,
  `device_name` VARCHAR(120) DEFAULT NULL,
  `plataforma` VARCHAR(20) NOT NULL DEFAULT 'android',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_refresh_hash` (`token_hash`),
  KEY `idx_refresh_usuario` (`usuario_id`),
  KEY `idx_refresh_device` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
