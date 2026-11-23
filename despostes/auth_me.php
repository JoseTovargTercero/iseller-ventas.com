<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/db.php';

try {
    $usuario = ds_requiere_sesion();
    ds_json([
        'ok' => true,
        'data' => [
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'premium_hasta' => $usuario['premium_hasta'] ?? null,
            ],
        ],
    ]);
} catch (Throwable $e) {
    ds_json(['ok' => false, 'mensaje' => 'No se pudo validar la sesión.'], 500);
}
