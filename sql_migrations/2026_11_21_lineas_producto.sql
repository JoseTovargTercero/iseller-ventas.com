-- Ingresos por Línea de Producto.
-- Líneas de seguimiento configurables por negocio: agrupan productos para
-- consultar cuánto dinero generaron en un período y cuánto representan del
-- total vendido.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lineas_producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bss_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_linea_nombre (bss_id, nombre),
    KEY idx_bss (bss_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS linea_producto_items (
    linea_id INT NOT NULL,
    producto_id INT NOT NULL,
    bss_id INT NOT NULL,
    PRIMARY KEY (linea_id, producto_id),
    KEY idx_linea (linea_id),
    KEY idx_producto (producto_id),
    KEY idx_bss (bss_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO menu (categoria, nombre, dir, icono, admin)
SELECT 'Estadísticas', 'Líneas de Producto', 'ingresos_linea_producto.php', 'layers-outline', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu WHERE dir = 'ingresos_linea_producto.php');
