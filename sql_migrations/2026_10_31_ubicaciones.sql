-- Ubicaciones del transportista y punto de origen de cada pedido.
-- Las API también crean estas tablas solas (asegurarTablas / registrar_estado_orden);
-- este archivo sirve para aplicarlas a mano en producción.

CREATE TABLE IF NOT EXISTS transportista_ubicaciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    transportista_id INT UNSIGNED NOT NULL,
    servicio_id INT UNSIGNED DEFAULT NULL,
    numero_pedido VARCHAR(40) DEFAULT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    precision_m DECIMAL(8,2) DEFAULT NULL,
    rumbo DECIMAL(5,1) DEFAULT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ubicaciones_transportista (transportista_id, creado_en),
    KEY idx_ubicaciones_pedido (numero_pedido, creado_en),
    KEY idx_ubicaciones_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Punto de partida del pedido: ubicación de la tienda al pasar a "enviado" (status 3).
CREATE TABLE IF NOT EXISTS tienda_orden_origen (
    orden_id INT NOT NULL,
    bss_id INT NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (orden_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
