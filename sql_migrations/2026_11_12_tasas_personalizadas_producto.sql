-- Tasas de cambio personalizadas por par y asignación por producto.
-- Cada producto puede tener una tasa personalizada por cada par de conversión.
-- No hay tasas activas/inactivas: son definiciones reutilizables.

CREATE TABLE IF NOT EXISTS cambio_tasas_personalizadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bss_id INT NOT NULL,
    par ENUM('usd_bs','usd_cop','cop_bs','bs_cop') NOT NULL,
    nombre VARCHAR(60) NOT NULL,
    valor DECIMAL(18,6) NOT NULL,
    creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tasa (bss_id, par, nombre),
    KEY idx_bss_par (bss_id, par)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS producto_tasas_personalizadas (
    producto_id INT NOT NULL,
    par ENUM('usd_bs','usd_cop','cop_bs','bs_cop') NOT NULL,
    tasa_id INT NOT NULL,
    bss_id INT NOT NULL,
    PRIMARY KEY (producto_id, par),
    UNIQUE KEY uq_producto_tasa (producto_id, tasa_id),
    KEY idx_tasa (tasa_id),
    KEY idx_bss (bss_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
