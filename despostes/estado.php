<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ds_json(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$device = trim((string) ($_POST['device_id'] ?? ''));
if (!ds_dispositivo_valido($device)) {
    ds_json(['ok' => false, 'mensaje' => 'Dispositivo inválido']);
}

try {
    $stmt = ds_db()->prepare(
        'SELECT expira_en FROM codigos
         WHERE dispositivo_id = :device AND expira_en >= CURDATE()
         ORDER BY expira_en DESC LIMIT 1'
    );
    $stmt->execute([':device' => $device]);
    $fila = $stmt->fetch();

    if ($fila) {
        ds_json([
            'ok' => true,
            'data' => [
                'estado' => 'aprobado',
                'expira' => (string) $fila['expira_en'],
            ],
        ]);
    }

    ds_json(['ok' => true, 'data' => ['estado' => 'pendiente']]);
} catch (Throwable $e) {
    ds_json(['ok' => false, 'mensaje' => 'No se pudo consultar el estado.'], 500);
}
