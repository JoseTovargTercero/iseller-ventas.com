<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ds_json(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$device = trim((string) ($_POST['device_id'] ?? ''));
if (!ds_dispositivo_valido($device)) {
    ds_json(['ok' => false, 'mensaje' => 'Dispositivo inválido']);
}

$codigo = ds_codigo_activo_para(ds_leer('codigos'), $device);

if ($codigo !== null) {
    ds_json([
        'ok' => true,
        'data' => [
            'estado' => 'aprobado',
            'expira' => (string) ($codigo['expira_en'] ?? ''),
        ],
    ]);
}

ds_json([
    'ok' => true,
    'data' => [
        'estado' => 'pendiente',
    ],
]);
