-- Módulo Transportistas (iSeller POS)
-- La API crea estas tablas automáticamente (CREATE TABLE IF NOT EXISTS).
-- Este archivo sirve para crear/verificar la estructura manualmente.

CREATE TABLE IF NOT EXISTS transportistas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    cedula VARCHAR(12) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    deliverys TINYINT(1) NOT NULL DEFAULT 1,
    carreras TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_transportista_cedula (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS transportista_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    transportista_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expira_en DATETIME NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_transportista_token (token_hash),
    KEY idx_transportista_tokens (transportista_id),
    CONSTRAINT fk_transportista_tokens FOREIGN KEY (transportista_id)
        REFERENCES transportistas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS transportista_servicios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    transportista_id INT UNSIGNED NOT NULL,
    tipo ENUM('delivery','carrera') NOT NULL DEFAULT 'delivery',
    bss_id INT UNSIGNED DEFAULT NULL,
    codigo_ruta VARCHAR(20) DEFAULT NULL,
    numero_pedido VARCHAR(40) DEFAULT NULL,
    cliente VARCHAR(150) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    lat DECIMAL(10,7) DEFAULT NULL,
    lng DECIMAL(10,7) DEFAULT NULL,
    estado ENUM('asignado','aceptado','en_camino','completado','cancelado','rechazado') NOT NULL DEFAULT 'asignado',
    notas VARCHAR(255) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_transportista_servicio_pedido (tipo, numero_pedido),
    KEY idx_transportista_servicios (transportista_id, tipo, estado),
    CONSTRAINT fk_transportista_servicios FOREIGN KEY (transportista_id)
        REFERENCES transportistas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
