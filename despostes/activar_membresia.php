<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';

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
    $resultado = ds_actualizar('codigos', function (array &$codigos) use ($device, $codigo, $config) {
        foreach ($codigos as $indice => $item) {
            if (!is_array($item)) {
                continue;
            }
            if (strcasecmp((string) ($item['codigo'] ?? ''), $codigo) !== 0) {
                continue;
            }

            $dispositivoActual = (string) ($item['dispositivo'] ?? '');
            if ($dispositivoActual !== '' && $dispositivoActual !== $device) {
                return ['ok' => false, 'mensaje' => 'Este código ya fue usado en otro dispositivo'];
            }

            $expira = (string) ($item['expira_en'] ?? '');
            if ($expira !== '' && $expira < ds_hoy()) {
                return ['ok' => false, 'mensaje' => 'El código está vencido'];
            }

            if ($dispositivoActual === '') {
                $codigos[$indice]['dispositivo'] = $device;
                $codigos[$indice]['activado_en'] = ds_hoy();
                $codigos[$indice]['expira_en'] = ds_sumar_dias((int) $config['membership_days']);
            }

            return [
                'ok' => true,
                'mensaje' => 'Membresía activada',
                'expira' => (string) ($codigos[$indice]['expira_en'] ?? ''),
            ];
        }

        return ['ok' => false, 'mensaje' => 'Código no encontrado'];
    });
} catch (Throwable $e) {
    ds_json(['ok' => false, 'mensaje' => 'No se pudo validar el código. Intenta de nuevo.'], 500);
}

ds_json($resultado);
