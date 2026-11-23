<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ds_json(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$in = ds_input();
$nombre = trim((string) ($in['nombre'] ?? ''));
$email = strtolower(trim((string) ($in['email'] ?? '')));
$password = (string) ($in['password'] ?? '');

if (ds_largo($nombre) < 2 || ds_largo($nombre) > 120) {
    ds_json(['ok' => false, 'mensaje' => 'Escribe tu nombre.']);
}
if (!ds_email_valido($email) || ds_largo($email) > 190) {
    ds_json(['ok' => false, 'mensaje' => 'Ese correo no es válido.']);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    ds_json(['ok' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres.']);
}

try {
    $pdo = ds_db();

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        ds_json(['ok' => false, 'mensaje' => 'Ese correo ya está registrado.']);
    }

    $id = ds_uuid();
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (id, nombre, email, password_hash)
         VALUES (:id, :nombre, :email, :hash)'
    );
    $stmt->execute([
        ':id' => $id,
        ':nombre' => $nombre,
        ':email' => $email,
        ':hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $token = ds_token_crear($pdo, $id);

    ds_json([
        'ok' => true,
        'data' => [
            'token' => $token,
            'usuario' => ['id' => $id, 'nombre' => $nombre, 'email' => $email],
        ],
    ]);
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        ds_json(['ok' => false, 'mensaje' => 'Ese correo ya está registrado.']);
    }
    ds_json(['ok' => false, 'mensaje' => 'No se pudo crear la cuenta. Intenta de nuevo.'], 500);
} catch (Throwable $e) {
    ds_json(['ok' => false, 'mensaje' => 'No se pudo crear la cuenta. Intenta de nuevo.'], 500);
}
