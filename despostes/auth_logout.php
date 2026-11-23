<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ds_json(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$token = ds_bearer_token();
if ($token !== '') {
    try {
        $stmt = ds_db()->prepare('DELETE FROM auth_tokens WHERE token_hash = :hash');
        $stmt->execute([':hash' => hash('sha256', $token)]);
    } catch (Throwable $e) {
        // Cerrar sesión localmente no debe fallar por la red.
    }
}

ds_json(['ok' => true]);
