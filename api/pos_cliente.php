<?php
/**
 * API — Nombre de un cliente por cédula  →  GET /api/pos_cliente.php?cedula=...
 *
 * Misma prioridad que el POS web:
 *   1. base_nombres
 *   2. clientes del negocio
 *   3. vacío (la app deja que el cajero escriba el nombre)
 *
 * Autenticación: X-Api-Key + Bearer, igual que el resto de api/pos_*.php.
 */

require_once __DIR__ . '/_pos_comun.php';
require_once __DIR__ . '/../configurar/_nombres.php';

exigirMetodo('GET');
exigirApiKey();
limitar('cliente', 120, 60);

$sesion = exigirSesion($conexion);
$bssId = (int)$sesion['bss_id'];

$cedula = trim((string)($_GET['cedula'] ?? ''));
if ($cedula === '') {
    salir(400, ['ok' => false, 'error' => 'Falta la cédula']);
}

$datos = nb_buscar($conexion, $cedula, $bssId);

salir(200, [
    'ok' => true,
    'cedula' => $cedula,
    'nombre' => $datos['nombre'],
    'telefono' => $datos['telefono'],
    'origen' => $datos['origen'],
]);
