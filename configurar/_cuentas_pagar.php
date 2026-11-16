<?php

require_once __DIR__ . '/_factura_compra.php';

/**
 * Facturas por pagar registradas manualmente (por conceptos). No tocan
 * inventario: solo guardan la obligación y su explicación.
 */
function cp_asegurar_esquema($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $sqls = [
        "CREATE TABLE IF NOT EXISTS `cuentas_pagar_facturas` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `bss_id` INT(11) NOT NULL,
            `id_sucursal` INT(11) NOT NULL,
            `proveedor` VARCHAR(255) NOT NULL,
            `numero_factura` VARCHAR(100) NOT NULL,
            `fecha_factura` DATE NOT NULL,
            `fecha_vencimiento` DATE NULL,
            `total` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `estado` ENUM('pendiente','pagada') NOT NULL DEFAULT 'pendiente',
            `usuario` VARCHAR(150) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_cpf_bss_estado` (`bss_id`, `estado`),
            KEY `idx_cpf_bss_fecha` (`bss_id`, `fecha_factura`),
            KEY `idx_cpf_bss_num` (`bss_id`, `numero_factura`),
            KEY `idx_cpf_sucursal` (`id_sucursal`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `cuentas_pagar_conceptos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `factura_id` INT(11) NOT NULL,
            `bss_id` INT(11) NOT NULL,
            `concepto` VARCHAR(255) NOT NULL,
            `monto` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_cpc_factura` (`factura_id`),
            KEY `idx_cpc_bss` (`bss_id`),
            CONSTRAINT `fk_cpc_factura` FOREIGN KEY (`factura_id`) REFERENCES `cuentas_pagar_facturas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `cuentas_pagar_pagos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `factura_id` INT(11) NOT NULL,
            `bss_id` INT(11) NOT NULL,
            `monto` DECIMAL(15,2) NOT NULL,
            `fecha` DATE NOT NULL,
            `usuario` VARCHAR(150) DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_cpp_factura` (`factura_id`),
            KEY `idx_cpp_bss` (`bss_id`),
            CONSTRAINT `fk_cpp_factura` FOREIGN KEY (`factura_id`) REFERENCES `cuentas_pagar_facturas` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($sqls as $sql) {
        if (!@$conexion->query($sql)) {
            return $listo = false;
        }
    }

    return $listo = true;
}

/**
 * Asegura la entrada del módulo en el menú. Se ejecuta también desde
 * includes/menu.php para que quede visible sin depender de la migración.
 */
function cp_asegurar_menu($conexion): void
{
    static $hecho = false;
    if ($hecho) {
        return;
    }
    $hecho = true;

    @$conexion->query(
        "INSERT INTO `menu` (`categoria`, `nombre`, `dir`, `icono`, `admin`)
         SELECT COALESCE(
                    (SELECT m.categoria FROM `menu` m WHERE m.dir = 'nuevaCompra.php' LIMIT 1),
                    (SELECT m2.categoria FROM `menu` m2 WHERE m2.dir = 'compras_facturas.php' LIMIT 1),
                    'Compras'
                ),
                'Cuentas por pagar', 'cuentas_pagar.php', 'wallet-outline', 0
         FROM DUAL
         WHERE NOT EXISTS (SELECT 1 FROM `menu` m3 WHERE m3.dir = 'cuentas_pagar.php')"
    );
}

function cp_estado($total, $abonado): string
{
    return ((float) $abonado + 0.005 >= (float) $total) ? 'pagada' : 'pendiente';
}

/**
 * Devuelve las tablas de facturas/pagos según el origen. Los nombres se
 * usan solo desde un mapa fijo, nunca desde datos del usuario.
 */
function cp_origenes(): array
{
    return [
        'concepto' => [
            'facturas' => 'cuentas_pagar_facturas',
            'pagos' => 'cuentas_pagar_pagos',
        ],
        'compra' => [
            'facturas' => 'compras_facturas',
            'pagos' => 'compras_factura_pagos',
        ],
    ];
}

function cp_listar_facturas($conexion, int $bssId, int $sucursalId, string $origen): array
{
    $mapa = cp_origenes();
    if (!isset($mapa[$origen])) {
        return [];
    }

    $tabla = $mapa[$origen]['facturas'];
    $pagos = $mapa[$origen]['pagos'];

    $sql = "SELECT f.*, COALESCE(p.abonado, 0) AS abonado
        FROM `$tabla` f
        LEFT JOIN (
            SELECT factura_id, SUM(monto) AS abonado
            FROM `$pagos`
            WHERE bss_id = ?
            GROUP BY factura_id
        ) p ON p.factura_id = f.id
        WHERE f.bss_id = ?";

    if ($sucursalId > 0) {
        $sql .= " AND f.id_sucursal = ?";
    }

    $sql .= " ORDER BY (f.estado = 'pendiente') DESC, (f.fecha_vencimiento IS NULL) ASC, f.fecha_vencimiento ASC, f.id DESC LIMIT 100";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($sucursalId > 0) {
        $stmt->bind_param("iii", $bssId, $bssId, $sucursalId);
    } else {
        $stmt->bind_param("ii", $bssId, $bssId);
    }
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $filas;
}

function cp_estadisticas($conexion, int $bssId, int $sucursalId, string $origen): array
{
    $mapa = cp_origenes();
    if (!isset($mapa[$origen])) {
        return [];
    }

    $tabla = $mapa[$origen]['facturas'];
    $pagos = $mapa[$origen]['pagos'];

    $sql = "SELECT
            COUNT(*) AS total_facturas,
            COALESCE(SUM(f.total), 0) AS total_comprado,
            COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 THEN f.total - COALESCE(p.abonado, 0) ELSE 0 END), 0) AS pendiente_saldo,
            COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 THEN 1 ELSE 0 END), 0) AS pendientes,
            COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 AND f.fecha_vencimiento IS NOT NULL AND f.fecha_vencimiento < CURDATE() THEN f.total - COALESCE(p.abonado, 0) ELSE 0 END), 0) AS vencida_saldo,
            COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 AND f.fecha_vencimiento IS NOT NULL AND f.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) AS vencidas,
            COALESCE(SUM(CASE WHEN f.estado = 'pagada' THEN 1 ELSE 0 END), 0) AS pagadas,
            COALESCE(SUM(CASE WHEN f.estado = 'pagada' THEN f.total ELSE 0 END), 0) AS pagada_total
        FROM `$tabla` f
        LEFT JOIN (
            SELECT factura_id, SUM(monto) AS abonado
            FROM `$pagos`
            WHERE bss_id = ?
            GROUP BY factura_id
        ) p ON p.factura_id = f.id
        WHERE f.bss_id = ?";

    if ($sucursalId > 0) {
        $sql .= " AND f.id_sucursal = ?";
    }

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($sucursalId > 0) {
        $stmt->bind_param("iii", $bssId, $bssId, $sucursalId);
    } else {
        $stmt->bind_param("ii", $bssId, $bssId);
    }
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();

    return $fila;
}
