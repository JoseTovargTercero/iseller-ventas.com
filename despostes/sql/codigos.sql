-- Tabla de códigos de membresía por dispositivo.
CREATE TABLE IF NOT EXISTS codigos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(32) NOT NULL,
    dispositivo_id VARCHAR(64) NULL,
    nota VARCHAR(160) NULL,
    creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activado_en DATETIME NULL,
    expira_en DATE NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_codigos_codigo (codigo),
    KEY idx_codigos_dispositivo (dispositivo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
