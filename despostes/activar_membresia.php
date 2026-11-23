<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ds_json(['ok' => false, 'mensaje' => 'Método no permitido'], 405);
}

$device = trim((string) ($_POST['device_id'] ?? ''));
$codigo = ds_codigo_normalizar((string) ($_POST['codigo'] ?? ''));

if (!ds_dispositivo_valido($device)) {
    ds_json(['ok' => false, 'mensaje' => 'Dispositivo inválido']);
}
if (!ds_codigo_valido($codigo)) {
    ds_json(['ok' => false, 'mensaje' => 'Código inválido']);
}

$config = ds_config();

try {
    $pdo = ds_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, dispositivo_id, expira_en
         FROM codigos WHERE codigo = :codigo LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([':codigo' => $codigo]);
    $fila = $stmt->fetch();

    if (!$fila) {
        $pdo->rollBack();
        ds_json(['ok' => false, 'mensaje' => 'Código no encontrado']);
    }

    $dispositivoActual = (string) ($fila['dispositivo_id'] ?? '');
    if ($dispositivoActual !== '' && $dispositivoActual !== $device) {
        $pdo->rollBack();
        ds_json(['ok' => false, 'mensaje' => 'Este código ya fue usado en otro dispositivo']);
    }

    $expira = (string) ($fila['expira_en'] ?? '');
    if ($expira !== '' && $expira < ds_hoy()) {
        $pdo->rollBack();
        ds_json(['ok' => false, 'mensaje' => 'El código está vencido']);
    }

    if ($dispositivoActual === '') {
        $expira = ds_sumar_dias((int) $config['membership_days']);
        $stmt = $pdo->prepare(
            'UPDATE codigos
             SET dispositivo_id = :device, activado_en = NOW(), expira_en = :expira
             WHERE id = :id'
        );
        $stmt->execute([
            ':device' => $device,
            ':expira' => $expira,
            ':id' => $fila['id'],
        ]);
    }

    $pdo->commit();
    ds_json([
        'ok' => true,
        'mensaje' => 'Membresía activada',
        'expira' => $expira,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    ds_json(['ok' => false, 'mensaje' => 'No se pudo validar el código. Intenta de nuevo.'], 500);
}
