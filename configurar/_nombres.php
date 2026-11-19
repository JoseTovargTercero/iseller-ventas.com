<?php

/**
 * Búsqueda del nombre de un cliente por cédula.
 *
 * Prioridad:
 *   1. `base_nombres` (registro general del hosting).
 *   2. `clientes` del negocio (fallback).
 *   3. Vacío, para que el cajero escriba el nombre.
 */
function nb_tabla_existe($conexion): bool
{
    static $existe = null;
    if ($existe !== null) {
        return $existe;
    }

    $res = $conexion->query("SHOW TABLES LIKE 'base_nombres'");
    $existe = $res && $res->num_rows > 0;
    if ($res) {
        $res->free();
    }

    return $existe;
}

/**
 * Devuelve ['nombre' => string, 'telefono' => string, 'origen' => string].
 * `origen` es 'base_nombres', 'clientes' o '' cuando no se encontró.
 */
function nb_buscar($conexion, $cedula, int $bssId): array
{
    $vacio = ['nombre' => '', 'telefono' => '', 'origen' => ''];
    $cedula = trim((string) $cedula);
    if ($cedula === '') {
        return $vacio;
    }

    $nombreBase = '';
    $nombreCliente = '';
    $telefono = '';

    if (nb_tabla_existe($conexion)) {
        // Una sola ida al servidor: nombre de base_nombres (prioridad),
        // nombre y teléfono de clientes (fallback).
        $sql = "SELECT
                    (SELECT nombre FROM base_nombres WHERE cedula = ? ORDER BY id DESC LIMIT 1) AS nombre_base,
                    (SELECT nombre FROM clientes WHERE cedula = ? AND bss_id = ? LIMIT 1) AS nombre_cliente,
                    (SELECT telefono FROM clientes WHERE cedula = ? AND bss_id = ? LIMIT 1) AS telefono";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssisi', $cedula, $cedula, $bssId, $cedula, $bssId);
            if ($stmt->execute()) {
                $fila = $stmt->get_result()->fetch_assoc();
                if ($fila) {
                    $nombreBase = trim((string) ($fila['nombre_base'] ?? ''));
                    $nombreCliente = trim((string) ($fila['nombre_cliente'] ?? ''));
                    $telefono = trim((string) ($fila['telefono'] ?? ''));
                }
            }
            $stmt->close();
        }
    } else {
        // Instalaciones sin base_nombres: solo la tabla del negocio.
        $stmt = $conexion->prepare("SELECT nombre, telefono FROM clientes WHERE cedula = ? AND bss_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('si', $cedula, $bssId);
            if ($stmt->execute()) {
                $fila = $stmt->get_result()->fetch_assoc();
                if ($fila) {
                    $nombreCliente = trim((string) ($fila['nombre'] ?? ''));
                    $telefono = trim((string) ($fila['telefono'] ?? ''));
                }
            }
            $stmt->close();
        }
    }

    if ($nombreBase !== '') {
        return ['nombre' => $nombreBase, 'telefono' => $telefono, 'origen' => 'base_nombres'];
    }
    if ($nombreCliente !== '') {
        return ['nombre' => $nombreCliente, 'telefono' => $telefono, 'origen' => 'clientes'];
    }

    return $vacio;
}
