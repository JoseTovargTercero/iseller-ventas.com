<?php
/**
 * API — Tasas de cambio del POS nativo  →  GET /api/pos_tasas.php
 *
 * Permite refrescar solo las tasas sin volver a bajar el catálogo.
 * La app sella la fecha de descarga con `servidor_en` (no con su propio reloj)
 * para saber cuándo la tasa queda vieja.
 *
 * Autenticación: X-Api-Key + Bearer.
 */

require_once __DIR__ . '/_pos_comun.php';

exigirMetodo('GET');
exigirApiKey();
limitar('tasas', 60, 60);

$sesion = exigirSesion($conexion);

salir(200, [
    'ok' => true,
    'servidor_en' => servidorEn(),
    'tasas' => tasasDe($conexion, $sesion['bss_id']),
]);
