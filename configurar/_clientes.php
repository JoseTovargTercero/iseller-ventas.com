<?php

/**
 * Registro único de clientes por (bss_id, cédula).
 *
 * La tabla `clientes` no puede llevar un índice único porque las bases ya
 * tienen duplicados históricos. `clientes_claves` actúa como candado atómico:
 * reclamar la clave evita que dos ventas simultáneas inserten el mismo cliente.
 */
function cl_asegurar_tabla($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $res = $conexion->query("SHOW TABLES LIKE 'clientes_claves'");
    $existe = $res && $res->num_rows > 0;
    if ($res) {
        $res->free();
    }

    if ($existe) {
        // `LIMIT 1` basta: si ya hay claves sembradas, la tabla está lista.
        $r = $conexion->query("SELECT 1 FROM clientes_claves LIMIT 1");
        if ($r && $r->num_rows > 0) {
            $r->free();
            return $listo = true;
        }
        if ($r) {
            $r->free();
        }
        // Tabla vacía (por ejemplo, creada por una petición concurrente):
        // se siembra con los clientes que ya existían.
    }

    // El DDL hace commit implícito: nunca debe ejecutarse dentro de una
    // transacción de venta.
    if (($conexion->server_status & MYSQLI_SERVER_STATUS_IN_TRANS) !== 0) {
        error_log('cl_asegurar_tabla: no se puede crear la tabla dentro de una transacción.');
        return false;
    }

    if (!$existe) {
        $sql = "CREATE TABLE IF NOT EXISTS `clientes_claves` (
            `bss_id`    INT(11)  NOT NULL,
            `cedula`    INT(11)  NOT NULL,
            `creado_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`bss_id`, `cedula`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$conexion->query($sql)) {
            error_log('cl_asegurar_tabla (crear): ' . $conexion->error);
            return $listo = false;
        }
    }

    // Los clientes que ya existían quedan marcados como registrados.
    if (!$conexion->query(
        "INSERT IGNORE INTO clientes_claves (bss_id, cedula)
         SELECT bss_id, cedula FROM clientes
         WHERE bss_id IS NOT NULL AND cedula IS NOT NULL AND cedula <> 0"
    )) {
        error_log('cl_asegurar_tabla (siembra): ' . $conexion->error);
    }

    return $listo = true;
}

/**
 * Registra al cliente solo una vez por (bss_id, cédula).
 * Devuelve true si insertó la fila en `clientes`.
 */
function cl_registrar($conexion, int $bssId, int $sucursalId, string $cedula, string $nombre, string $telefono): bool
{
    $cedula = trim($cedula);
    if ($cedula === '' || !ctype_digit($cedula)) {
        // Sin una cédula numérica no se puede identificar al cliente.
        return false;
    }

    if (!cl_asegurar_tabla($conexion)) {
        return false;
    }

    // Reclama la clave: la PK (bss_id, cedula) hace la comprobación atómica,
    // aunque otra caja esté registrando al mismo cliente al mismo tiempo.
    $stmt = $conexion->prepare("INSERT IGNORE INTO clientes_claves (bss_id, cedula) VALUES (?, ?)");
    if (!$stmt) {
        error_log('cl_registrar (prepare clave): ' . $conexion->error);
        return false;
    }
    $stmt->bind_param("is", $bssId, $cedula);
    if (!$stmt->execute()) {
        error_log('cl_registrar (execute clave): ' . $stmt->error);
        $stmt->close();
        return false;
    }
    $nuevo = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$nuevo) {
        return false;
    }

    $stmt = $conexion->prepare("INSERT INTO clientes (nombre, cedula, telefono, bss_id, sucursal_id) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log('cl_registrar (prepare cliente): ' . $conexion->error);
        return false;
    }
    $stmt->bind_param("ssssi", $nombre, $cedula, $telefono, $bssId, $sucursalId);
    $ok = $stmt->execute();
    if (!$ok) {
        error_log('cl_registrar (execute cliente): ' . $stmt->error);
        // No dejar la clave reclamada si el cliente no se guardó.
        $stmt->close();
        $conexion->query("DELETE FROM clientes_claves WHERE bss_id = " . (int) $bssId . " AND cedula = " . (int) $cedula);
        return false;
    }
    $stmt->close();

    return true;
}
