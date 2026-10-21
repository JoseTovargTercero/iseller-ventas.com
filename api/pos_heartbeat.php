<?php
/**
 * API — Latido del POS nativo  →  GET /api/pos_heartbeat.php
 *
 * Sirve para dos cosas:
 *  1. Que la caja detecte que hay internet (y no solo que la red responde).
 *  2. Que la app compruebe que su token sigue vivo: si un administrador lo
 *     revocó, aquí recibe 401 y cierra la sesión local.
 *
 * Autenticación: X-Api-Key + Bearer.
 */

require_once __DIR__ . '/_pos_comun.php';

exigirMetodo('GET');
exigirApiKey();
limitar('heartbeat', 240, 60);

$sesion = exigirSesion($conexion);

salir(200, [
    'ok' => true,
    'servidor_en' => servidorEn(),
    'usuario_id' => $sesion['id_user'],
    'bss_id' => $sesion['bss_id'],
]);
