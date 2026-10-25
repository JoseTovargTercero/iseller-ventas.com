<?php
/**
 * Autochequeo de la API v1 (solo CLI):
 *   php iseller_store/tests/api_v1_selftest.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../core/api_v1.php';

function check(bool $condicion, string $nombre): void
{
    if (!$condicion) {
        fwrite(STDERR, "FALLÓ: $nombre\n");
        exit(1);
    }
    echo "ok - $nombre\n";
}

$jwt = api_jwt_issue(6, 'dispositivo-de-prueba');
$payload = api_jwt_verify($jwt);
check(is_array($payload) && (int)$payload['sub'] === 6, 'JWT válido y con subject');
check(api_jwt_verify($jwt . 'x') === null, 'JWT alterado rechazado');
check(api_trunc2(10.567) === 10.56, 'precio truncado a 2 decimales');
check(api_trunc2(0.999) === 0.99, 'precio pequeño truncado');
check(str_ends_with(api_abs('assets/img/x.webp'), '/assets/img/x.webp'), 'URL absoluta de recursos');
check(api_abs('https://cdn.test/x.png') === 'https://cdn.test/x.png', 'URL externa respetada');

echo "API v1: autochequeo correcto\n";
