<?php
declare(strict_types=1);

require __DIR__ . '/private/lib.php';
require __DIR__ . '/private/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

$config = ds_config();
$error = '';
$aviso = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $accion = (string) ($_POST['action'] ?? '');

    if ($accion === 'logout') {
        $_SESSION = [];
        session_destroy();
        header('Location: admin.php');
        exit;
    }

    if (!ds_csrf_valido()) {
        $error = 'La sesión expiró. Vuelve a intentarlo.';
    } elseif ($accion === 'login') {
        if ((string) $config['admin_password'] === 'CAMBIA-ESTA-CLAVE') {
            $error = 'Cambia la clave de admin en private/config.php antes de entrar.';
        } elseif (hash_equals((string) $config['admin_password'], (string) ($_POST['password'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $aviso = 'Sesión iniciada.';
        } else {
            $error = 'Clave incorrecta.';
        }
    } elseif (!empty($_SESSION['admin']) && $accion === 'generar') {
        $cantidad = max(1, min(20, (int) ($_POST['cantidad'] ?? 1)));
        $nota = trim((string) ($_POST['nota'] ?? ''));
        if (ds_largo($nota) > 160) {
            $nota = ds_recortar($nota, 160);
        }

        try {
            $stmt = ds_db()->prepare('INSERT INTO codigos (codigo, nota) VALUES (:codigo, :nota)');
            $generados = [];
            for ($i = 0; $i < $cantidad; $i++) {
                for ($intento = 0; $intento < 10; $intento++) {
                    $codigo = ds_generar_codigo((string) $config['code_prefix']);
                    try {
                        $stmt->execute([':codigo' => $codigo, ':nota' => $nota]);
                        $generados[] = $codigo;
                        break;
                    } catch (PDOException $repetido) {
                        if ((int) ($repetido->errorInfo[1] ?? 0) !== 1062) {
                            throw $repetido;
                        }
                    }
                }
            }
            $aviso = 'Códigos generados: ' . implode(', ', $generados);
        } catch (Throwable $e) {
            $error = 'No se pudieron guardar los códigos. Revisa la base de datos.';
        }
    } elseif (!empty($_SESSION['admin']) && $accion === 'atender') {
        $id = (string) ($_POST['id'] ?? '');
        if ($id !== '') {
            try {
                ds_actualizar('solicitudes', function (array &$solicitudes) use ($id) {
                    foreach ($solicitudes as $indice => $solicitud) {
                        if (is_array($solicitud) && ($solicitud['id'] ?? '') === $id) {
                            $solicitudes[$indice]['estado'] = 'atendida';
                        }
                    }
                });
                $aviso = 'Solicitud marcada como atendida.';
            } catch (Throwable $e) {
                $error = 'No se pudo actualizar la solicitud.';
            }
        }
    }
}

$logueado = !empty($_SESSION['admin']);
$csrf = ds_csrf();

if (!$logueado) {
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin · Despostes</title>
        <style>
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #FAF7F2;
                color: #1F1B18; font-family: system-ui, "Segoe UI", Roboto, sans-serif; padding: 20px; }
            .card { width: min(420px, 100%); background: #fff; border: 1px solid #E7E0D8;
                border-radius: 18px; padding: 24px; }
            .logo { width: 38px; height: 38px; border-radius: 50%; background: #8E1B1B; color: #fff;
                display: grid; place-items: center; font-weight: 700; margin-bottom: 16px; }
            h1 { font-size: 22px; margin: 0 0 6px; }
            p { color: #6B625C; font-size: 14px; margin: 0 0 18px; }
            label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
            input { width: 100%; box-sizing: border-box; border: 1px solid #B8AEA5; border-radius: 12px;
                padding: 12px; font: inherit; }
            button { width: 100%; margin-top: 14px; border: 0; border-radius: 14px; padding: 13px;
                background: #8E1B1B; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
            .error { background: #FBE9E7; color: #B3261E; border-radius: 10px; padding: 10px;
                font-size: 13px; margin-bottom: 12px; }
        </style>
    </head>
    <body>
    <div class="card">
        <div class="logo">D</div>
        <h1>Admin Despostes</h1>
        <p>Genera códigos y revisa solicitudes de membresía.</p>
        <?php if ($error !== ''): ?><div class="error"><?= ds_escape($error) ?></div><?php endif; ?>
        <?php if ((string) $config['admin_password'] === 'CAMBIA-ESTA-CLAVE'): ?>
            <div class="error">Antes de usar el panel, edita <code>private/config.php</code> y cambia <code>admin_password</code>.</div>
        <?php else: ?>
            <form method="post" action="admin.php">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf" value="<?= ds_escape($csrf) ?>">
                <label for="password">Clave de administrador</label>
                <input id="password" type="password" name="password" required autofocus>
                <button type="submit">Entrar</button>
            </form>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$codigos = [];
$solicitudes = ds_leer('solicitudes');
$hoy = ds_hoy();

try {
    $codigos = ds_db()->query(
        'SELECT codigo, dispositivo_id, nota, creado, activado_en, expira_en
         FROM codigos ORDER BY creado DESC, id DESC'
    )->fetchAll();
} catch (Throwable $e) {
    $error = 'No se pudieron leer los códigos. Revisa la base de datos.';
}

$disponibles = 0;
$activos = 0;
$vencidos = 0;
foreach ($codigos as $item) {
    $dispositivo = (string) ($item['dispositivo_id'] ?? '');
    $expira = (string) ($item['expira_en'] ?? '');
    if ($dispositivo === '') {
        $disponibles++;
    } elseif ($expira !== '' && $expira < $hoy) {
        $vencidos++;
    } else {
        $activos++;
    }
}

$nuevas = 0;
foreach ($solicitudes as $solicitud) {
    if (is_array($solicitud) && ($solicitud['estado'] ?? 'nueva') === 'nueva') {
        $nuevas++;
    }
}

usort($solicitudes, static function ($a, $b) {
    return strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? ''));
});
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin · Despostes</title>
    <style>
        :root { --surface:#FAF7F2; --card:#fff; --ink:#1F1B18; --muted:#6B625C; --primary:#8E1B1B;
            --hair:#E7E0D8; --ok:#2E7D32; --warn:#9A5B00; --bad:#B3261E; }
        * { box-sizing: border-box; }
        body { margin:0; background:var(--surface); color:var(--ink);
            font-family: system-ui, "Segoe UI", Roboto, sans-serif; font-size:15px; line-height:1.45; }
        header { background:var(--card); border-bottom:1px solid var(--hair); padding:14px 20px;
            display:flex; align-items:center; justify-content:space-between; gap:12px; }
        header strong { font-size:16px; }
        main { max-width:1040px; margin:0 auto; padding:20px; }
        h1 { font-size:24px; margin:0 0 4px; }
        p.lead { color:var(--muted); margin:0 0 18px; font-size:14px; }
        .grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
        .stat { flex:1 1 130px; background:var(--card); border:1px solid var(--hair);
            border-radius:14px; padding:12px 14px; }
        .stat span { display:block; font-size:12px; color:var(--muted); }
        .stat strong { font-size:22px; }
        .card { background:var(--card); border:1px solid var(--hair); border-radius:16px;
            padding:16px; margin-bottom:16px; }
        h2 { font-size:17px; margin:0 0 10px; }
        label { font-size:12px; font-weight:600; display:block; margin-bottom:5px; }
        input, textarea { border:1px solid #B8AEA5; border-radius:10px; padding:10px;
            font:inherit; width:100%; }
        .row { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
        .row > div { flex:1 1 160px; }
        button { border:0; border-radius:12px; padding:11px 14px; font:inherit; font-weight:600;
            cursor:pointer; }
        .primary { background:var(--primary); color:#fff; }
        .ghost { background:transparent; border:1px solid #B8AEA5; color:var(--ink); }
        .link { background:transparent; color:var(--primary); padding:6px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { text-align:left; padding:9px 6px; border-bottom:1px solid var(--hair);
            vertical-align:top; }
        th { color:var(--muted); font-size:12px; font-weight:600; }
        code { font-size:12px; overflow-wrap:anywhere; }
        .badge { display:inline-block; border-radius:999px; padding:2px 8px; font-size:11px;
            font-weight:600; }
        .b-ok { background:#E4F0E3; color:var(--ok); }
        .b-warn { background:#FBF0DE; color:var(--warn); }
        .b-bad { background:#FBE9E7; color:var(--bad); }
        .b-neutral { background:#F3EEE7; color:var(--muted); }
        .msg { border-radius:10px; padding:10px 12px; font-size:14px; margin-bottom:14px; }
        .msg-ok { background:#E4F0E3; color:#1E5222; }
        .msg-err { background:#FBE9E7; color:var(--bad); }
        .empty { color:var(--muted); font-size:13px; padding:8px 0; }
        .muted { color:var(--muted); font-size:12px; }
        .copy { border:0; background:transparent; color:var(--primary); font-size:12px;
            padding:2px 4px; cursor:pointer; }
        .logout { font-size:13px; }
        @media (max-width:640px) {
            table { display:block; overflow-x:auto; white-space:nowrap; }
        }
    </style>
</head>
<body>
<header>
    <strong>Despostes · Administración</strong>
    <form method="post" action="admin.php" class="logout">
        <input type="hidden" name="action" value="logout">
        <input type="hidden" name="csrf" value="<?= ds_escape($csrf) ?>">
        <button type="submit" class="link">Salir</button>
    </form>
</header>

<main>
    <h1>Membresías</h1>
    <p class="lead">Genera códigos después de confirmar el pago y revisa las solicitudes recibidas.</p>

    <?php if ($error !== ''): ?><div class="msg msg-err"><?= ds_escape($error) ?></div><?php endif; ?>
    <?php if ($aviso !== ''): ?><div class="msg msg-ok"><?= ds_escape($aviso) ?></div><?php endif; ?>

    <div class="grid">
        <div class="stat"><span>Disponibles</span><strong><?= $disponibles ?></strong></div>
        <div class="stat"><span>Activas</span><strong><?= $activos ?></strong></div>
        <div class="stat"><span>Vencidas</span><strong><?= $vencidos ?></strong></div>
        <div class="stat"><span>Solicitudes nuevas</span><strong><?= $nuevas ?></strong></div>
    </div>

    <div class="card">
        <h2>Generar códigos</h2>
        <form method="post" action="admin.php">
            <input type="hidden" name="action" value="generar">
            <input type="hidden" name="csrf" value="<?= ds_escape($csrf) ?>">
            <div class="row">
                <div>
                    <label for="cantidad">Cantidad (1–20)</label>
                    <input id="cantidad" type="number" name="cantidad" min="1" max="20" value="1" required>
                </div>
                <div style="flex:2 1 240px;">
                    <label for="nota">Nota (comprador, referencia)</label>
                    <input id="nota" name="nota" maxlength="160" placeholder="Ej. Juan Pérez · pago 1234">
                </div>
                <div style="flex:0 0 auto;">
                    <button type="submit" class="primary">Generar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Solicitudes</h2>
        <?php if (count($solicitudes) === 0): ?>
            <div class="empty">No hay solicitudes todavía.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Fecha</th><th>Persona</th><th>Contacto</th><th>Dispositivo</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($solicitudes as $solicitud): if (!is_array($solicitud)) continue; ?>
                    <tr>
                        <td><?= ds_escape((string) ($solicitud['fecha'] ?? '')) ?></td>
                        <td>
                            <?= ds_escape((string) ($solicitud['nombre'] ?? '')) ?>
                            <?php if (!empty($solicitud['mensaje'])): ?>
                                <div class="muted"><?= ds_escape((string) $solicitud['mensaje']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= ds_escape((string) ($solicitud['contacto'] ?? '')) ?></td>
                        <td>
                            <code><?= ds_escape((string) ($solicitud['dispositivo'] ?? '')) ?></code>
                            <button type="button" class="copy" data-copy="<?= ds_escape((string) ($solicitud['dispositivo'] ?? '')) ?>">Copiar</button>
                        </td>
                        <td>
                            <?php $estado = (string) ($solicitud['estado'] ?? 'nueva'); ?>
                            <span class="badge <?= $estado === 'nueva' ? 'b-warn' : 'b-ok' ?>"><?= ds_escape($estado) ?></span>
                        </td>
                        <td>
                            <?php if ($estado === 'nueva'): ?>
                                <form method="post" action="admin.php">
                                    <input type="hidden" name="action" value="atender">
                                    <input type="hidden" name="csrf" value="<?= ds_escape($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= ds_escape((string) ($solicitud['id'] ?? '')) ?>">
                                    <button type="submit" class="ghost">Atender</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Códigos</h2>
        <?php if (count($codigos) === 0): ?>
            <div class="empty">Aún no hay códigos generados.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Código</th><th>Estado</th><th>ID del dispositivo</th><th>Activado</th><th>Vence</th><th>Nota</th></tr></thead>
                <tbody>
                <?php foreach ($codigos as $item):
                    $dispositivo = (string) ($item['dispositivo_id'] ?? '');
                    $expira = (string) ($item['expira_en'] ?? '');
                    if ($dispositivo === '') {
                        $clase = 'b-neutral'; $texto = 'Disponible';
                    } elseif ($expira !== '' && $expira < $hoy) {
                        $clase = 'b-bad'; $texto = 'Vencido';
                    } else {
                        $clase = 'b-ok'; $texto = 'Activo';
                    }
                ?>
                    <tr>
                        <td>
                            <code><?= ds_escape((string) ($item['codigo'] ?? '')) ?></code>
                            <button type="button" class="copy" data-copy="<?= ds_escape((string) ($item['codigo'] ?? '')) ?>">Copiar</button>
                        </td>
                        <td><span class="badge <?= $clase ?>"><?= $texto ?></span></td>
                        <td><code><?= ds_escape($dispositivo) ?></code></td>
                        <td><?= ds_escape((string) ($item['activado_en'] ?? '—')) ?></td>
                        <td><?= ds_escape($expira !== '' ? $expira : '—') ?></td>
                        <td><?= ds_escape((string) ($item['nota'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<script>
document.querySelectorAll('[data-copy]').forEach(function (boton) {
    boton.addEventListener('click', function () {
        var texto = boton.getAttribute('data-copy') || '';
        if (navigator.clipboard) {
            navigator.clipboard.writeText(texto).then(function () {
                boton.innerText = 'Copiado';
            });
        }
    });
});
</script>
</body>
</html>
