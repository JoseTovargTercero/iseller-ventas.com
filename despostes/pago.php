<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';

$config = ds_config();
$device = trim((string) ($_GET['id'] ?? $_POST['device_id'] ?? ''));
$deviceValido = ds_dispositivo_valido($device);

$enviado = false;
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $deviceValido) {
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $contacto = trim((string) ($_POST['contacto'] ?? ''));
    $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

    if ($nombre === '' || $contacto === '') {
        $error = 'Indica tu nombre y un teléfono o correo para contactarte.';
    } elseif (ds_largo($nombre) > 120 || ds_largo($contacto) > 160 || ds_largo($mensaje) > 600) {
        $error = 'Los datos son demasiado largos.';
    } else {
        try {
            $enviado = ds_actualizar('solicitudes', function (array &$solicitudes) use ($device, $nombre, $contacto, $mensaje) {
                $haceUnMinuto = date('Y-m-d H:i:s', time() - 60);
                foreach ($solicitudes as $solicitud) {
                    if (!is_array($solicitud)) {
                        continue;
                    }
                    if (($solicitud['dispositivo'] ?? '') === $device
                        && ($solicitud['fecha'] ?? '') >= $haceUnMinuto) {
                        return true;
                    }
                }

                $solicitudes[] = [
                    'id' => bin2hex(random_bytes(8)),
                    'dispositivo' => $device,
                    'nombre' => $nombre,
                    'contacto' => $contacto,
                    'mensaje' => $mensaje,
                    'fecha' => ds_ahora(),
                    'estado' => 'nueva',
                ];
                return true;
            });
        } catch (Throwable $e) {
            $error = 'No se pudo registrar la solicitud. Intenta de nuevo.';
        }
    }
}

$paymentUrl = trim((string) ($config['payment_url'] ?? ''));
$whatsapp = preg_replace('/\D+/', '', (string) ($config['whatsapp'] ?? ''));
$email = trim((string) ($config['support_email'] ?? ''));
$whatsappTexto = rawurlencode('Hola, quiero solicitar mi código de ' . $config['price_label']
    . '. Mi ID de dispositivo es ' . $device);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#8E1B1B">
    <title>Solicitar código · Despostes</title>
    <style>
        :root {
            --surface: #FAF7F2; --card: #FFFFFF; --ink: #1F1B18; --muted: #6B625C;
            --primary: #8E1B1B; --positive: #2E7D32; --hair: #E7E0D8; --warning: #9A5B00;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--surface); color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.45; font-size: 16px; }
        .wrap { max-width: 520px; margin: 0 auto; padding: 24px 20px 40px; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
        .logo { width: 38px; height: 38px; border-radius: 50%; background: var(--primary);
            color: #fff; display: grid; place-items: center; font-weight: 700; }
        .brand strong { font-size: 17px; }
        h1 { font-size: 27px; line-height: 1.15; letter-spacing: -0.02em; margin: 0 0 8px; }
        p.lead { color: var(--muted); margin: 0 0 20px; }
        .card { background: var(--card); border: 1px solid var(--hair); border-radius: 16px;
            padding: 18px; margin-top: 14px; }
        .label { font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        .device { display: flex; align-items: center; gap: 8px; background: var(--surface);
            border: 1px solid var(--hair); border-radius: 12px; padding: 10px 12px; }
        .device code { font-size: 12px; overflow-wrap: anywhere; flex: 1; }
        button, .btn { border: 0; border-radius: 14px; padding: 13px 16px; font: inherit;
            font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex;
            align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: #fff; width: 100%; }
        .btn-outline { background: transparent; color: var(--ink); border: 1px solid #B8AEA5;
            width: 100%; }
        .btn-whatsapp { background: #E7F5EA; color: #1E5222; border: 1px solid #B7DDBB;
            width: 100%; }
        .btn-copy { background: transparent; color: var(--primary); padding: 6px 8px;
            border-radius: 8px; font-size: 13px; }
        form { margin-top: 14px; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; }
        input, textarea { width: 100%; border: 1px solid #B8AEA5; border-radius: 12px;
            background: #fff; padding: 12px; font: inherit; color: var(--ink); }
        textarea { min-height: 84px; resize: vertical; }
        input:focus, textarea:focus { outline: 2px solid var(--primary); border-color: var(--primary); }
        .hint { font-size: 12px; color: var(--muted); margin-top: 6px; }
        .error { background: #FBE9E7; color: #B3261E; border-radius: 12px; padding: 10px 12px;
            margin-top: 12px; font-size: 14px; }
        .ok { background: #E4F0E3; color: #1E5222; border-radius: 12px; padding: 12px;
            margin-top: 12px; font-size: 14px; }
        .steps { margin: 14px 0 0; padding-left: 20px; color: var(--muted); font-size: 14px; }
        .steps li { margin-bottom: 6px; }
        .foot { color: var(--muted); font-size: 12px; margin-top: 22px; text-align: center; }
        .warn { background: #FBF0DE; color: var(--warning); border-radius: 12px;
            padding: 10px 12px; font-size: 14px; }
        .space { height: 10px; }
    </style>
</head>
<body>
<main class="wrap">
    <div class="brand"><div class="logo">D</div><strong>Despostes</strong></div>

    <h1>Solicitar código</h1>
    <p class="lead"><?= ds_escape($config['price_label']) ?> · pago único por 12 meses.</p>

    <?php if (!$deviceValido): ?>
        <div class="card">
            <div class="warn">Abre esta página desde la app para identificar tu dispositivo.</div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="label">ID de este dispositivo</div>
            <div class="device">
                <code id="deviceId"><?= ds_escape($device) ?></code>
                <button type="button" class="btn-copy" onclick="copiarId()">Copiar</button>
            </div>
            <p class="hint">Envíanos este ID junto con tu pago para asignarte el código.</p>

            <?php if ($paymentUrl !== ''): ?>
                <div class="space"></div>
                <a class="btn btn-primary" href="<?= ds_escape($paymentUrl) ?>">Ir a pagar</a>
            <?php endif; ?>

            <?php if ($whatsapp !== ''): ?>
                <div class="space"></div>
                <a class="btn btn-whatsapp" href="https://wa.me/<?= ds_escape($whatsapp) ?>?text=<?= $whatsappTexto ?>">Escribir por WhatsApp</a>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="font-size:18px;margin:0 0 4px;">Solicitar mi código</h2>
            <p class="hint" style="margin:0;">Déjanos tus datos y te contactamos para coordinar el pago y entregarte el código.</p>

            <?php if ($error !== ''): ?>
                <div class="error"><?= ds_escape($error) ?></div>
            <?php endif; ?>

            <?php if ($enviado): ?>
                <div class="ok">Solicitud enviada. Te contactaremos con el código de activación.</div>
                <ol class="steps">
                    <li>Guardamos tu solicitud con tu ID de dispositivo.</li>
                    <li>Te escribimos para coordinar el pago.</li>
                    <li>Recibes un código y lo ingresas en Membresía.</li>
                </ol>
            <?php else: ?>
                <form method="post" action="">
                    <input type="hidden" name="device_id" value="<?= ds_escape($device) ?>">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" name="nombre" maxlength="120" required autocomplete="name">
                    <label for="contacto">Teléfono o correo</label>
                    <input id="contacto" name="contacto" maxlength="160" required autocomplete="tel">
                    <label for="mensaje">Mensaje (opcional)</label>
                    <textarea id="mensaje" name="mensaje" maxlength="600"></textarea>
                    <div class="space"></div>
                    <button type="submit" class="btn-primary">Enviar solicitud</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($email !== ''): ?>
            <p class="foot">Soporte: <a href="mailto:<?= ds_escape($email) ?>"><?= ds_escape($email) ?></a></p>
        <?php endif; ?>
    <?php endif; ?>

    <p class="foot">Despostes · activación anual por dispositivo</p>
</main>
<script>
function copiarId() {
    var texto = document.getElementById('deviceId').innerText;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(texto).then(function () {
            document.querySelector('.btn-copy').innerText = 'Copiado';
        });
    }
}
</script>
</body>
</html>
