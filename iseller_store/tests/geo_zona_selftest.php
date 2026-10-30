<?php
/**
 * Autochequeo de la zona tienda/comprador (solo CLI):
 *   php iseller_store/tests/geo_zona_selftest.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../core/venezuela_geo.php';

function check(bool $condicion, string $nombre): void
{
    if (!$condicion) {
        fwrite(STDERR, "FALLÓ: $nombre\n");
        exit(1);
    }
    echo "ok - $nombre\n";
}

$tienda = ['geo_estado' => 'Aragua', 'geo_municipio' => 'Girardot'];

check(zona_tienda_respecto_usuario($tienda, 'Aragua', 'Girardot') === 'ok', 'mismo estado y municipio');
check(zona_tienda_respecto_usuario($tienda, 'Aragua', 'Valencia') === 'otro_municipio', 'mismo estado, otro municipio');
check(zona_tienda_respecto_usuario($tienda, 'Carabobo', 'Valencia') === 'otro_estado', 'otro estado');
check(zona_tienda_respecto_usuario($tienda, '', '') === 'ok', 'sin ubicación de usuario no bloquea');
check(zona_tienda_respecto_usuario(['geo_estado' => ''], 'Aragua', 'Girardot') === 'ok', 'tienda sin ubicación no bloquea');

echo "Zona tienda/comprador: autochequeo correcto\n";
