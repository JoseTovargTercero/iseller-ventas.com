<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ds_json(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$in = ds_input();
$email = strtolower(trim((string) ($in['email'] ?? '')));
$password = (string) ($in['password'] ?? '');

if (!ds_email_valido($email) || $password === '') {
    ds_json(['ok' => false, 'mensaje' => 'Correo o contraseña incorrectos.'], 401);
}

try {
    $pdo = ds_db();
    $stmt = $pdo->prepare(
        'SELECT id, nombre, email, password_hash, premium_hasta
         FROM usuarios WHERE email = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, (string) $usuario['password_hash'])) {
        usleep(250000);
        ds_json(['ok' => false, 'mensaje' => 'Correo o contraseña incorrectos.'], 401);
    }

    $token = ds_token_crear($pdo, (string) $usuario['id']);

    ds_json([
        'ok' => true,
        'data' => [
            'token' => $token,
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'premium_hasta' => $usuario['premium_hasta'],
            ],
        ],
    ]);
} catch (Throwable $e) {
    ds_json(['ok' => false, 'mensaje' => 'No se pudo iniciar sesión. Intenta de nuevo.'], 500);
}
