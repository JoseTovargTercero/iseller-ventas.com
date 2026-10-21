<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../iseller_store/core/reputacion.php';

AdminSecurity::requireLogin();
$conexion = AdminSecurity::getConexion();

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminSecurity::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $mensaje = 'Token inválido. Recarga la página.';
    } else {
        $voto_id = (int)($_POST['voto_id'] ?? 0);
        $oculto  = (int)($_POST['oculto'] ?? 0) === 1 ? 0 : 1;

        $stmt = $conexion->prepare("UPDATE tienda_reputacion_votos SET oculto = ? WHERE id = ?");
        $stmt->bind_param("ii", $oculto, $voto_id);
        $stmt->execute();
        $stmt->close();

        $mensaje = $oculto === 1 ? 'Comentario oculto.' : 'Comentario visible de nuevo.';
    }
}

$votos = $conexion->query("
    SELECT v.id, v.recomienda, v.comentario, v.oculto, v.created_at,
           n.nombre AS tienda, n.slug,
           u.nombre AS comprador
    FROM tienda_reputacion_votos v
    JOIN negocio n ON n.id = v.negocio_id
    LEFT JOIN tienda_usuarios u ON u.id = v.usuario_id
    WHERE v.comentario IS NOT NULL AND v.comentario <> ''
    ORDER BY v.id DESC
    LIMIT 200
")->fetch_all(MYSQLI_ASSOC);

$token = AdminSecurity::getCsrfToken();
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? ($_SESSION['admin_user'] ?? 'Admin'));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iSeller Admin — Reputación</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #080910;
            color: #f1f5f9;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            padding: 32px;
        }
        .wrap { max-width: 1100px; margin: 0 auto; }
        .top {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; flex-wrap: wrap; margin-bottom: 24px;
        }
        h1 { font-size: 1.35rem; font-weight: 700; }
        h1 span { color: #8b5cf6; }
        .back {
            color: #a5b4fc; text-decoration: none; font-size: .85rem;
            border: 1px solid rgba(255,255,255,.12); padding: 9px 14px; border-radius: 9px;
        }
        .back:hover { background: rgba(255,255,255,.06); }
        .msg {
            background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.35);
            color: #86efac; padding: 12px 14px; border-radius: 9px; margin-bottom: 18px; font-size: .85rem;
        }
        .empty {
            border: 1px dashed rgba(255,255,255,.15); border-radius: 12px;
            padding: 48px 24px; text-align: center; color: #94a3b8;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            text-align: left; padding: 12px 14px; font-size: .84rem;
            border-bottom: 1px solid rgba(255,255,255,.08); vertical-align: top;
        }
        th {
            font-size: .7rem; text-transform: uppercase; letter-spacing: .05em;
            color: #94a3b8; font-weight: 600;
        }
        tr:hover td { background: rgba(255,255,255,.025); }
        .up { color: #22c55e; }
        .down { color: #ef4444; }
        .comment { max-width: 420px; color: #cbd5e1; line-height: 1.45; overflow-wrap: anywhere; }
        .muted { color: #94a3b8; font-size: .75rem; }
        .tag {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: .68rem; font-weight: 700;
            background: rgba(245,158,11,.15); color: #fbbf24;
        }
        button {
            background: rgba(255,255,255,.06); color: #e2e8f0;
            border: 1px solid rgba(255,255,255,.14); border-radius: 8px;
            padding: 7px 12px; font-size: .76rem; cursor: pointer;
        }
        button:hover { background: rgba(255,255,255,.12); }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="top">
            <div>
                <h1>Moderación de <span>reputación</span></h1>
                <div class="muted">Ocultar un comentario no cambia el porcentaje de recomendaciones.</div>
            </div>
            <a class="back" href="dashboard.php">← Volver al dashboard</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="msg"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <?php if (count($votos) === 0): ?>
            <div class="empty">Todavía no hay comentarios de compradores.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Tienda</th>
                        <th>Comprador</th>
                        <th>Voto</th>
                        <th>Comentario</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($votos as $v): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($v['tienda']) ?></strong><br>
                                <span class="muted"><?= htmlspecialchars($v['slug'] ?? '') ?></span>
                            </td>
                            <td><?= htmlspecialchars(reputacion_nombre_publico($v['comprador'])) ?></td>
                            <td>
                                <span class="<?= (int)$v['recomienda'] === 1 ? 'up' : 'down' ?>"><?= (int)$v['recomienda'] === 1 ? '▲' : '▼' ?></span>
                                <?= (int)$v['recomienda'] === 1 ? 'Recomienda' : 'No recomienda' ?>
                            </td>
                            <td>
                                <div class="comment"><?= nl2br(htmlspecialchars($v['comentario'])) ?></div>
                                <?php if ((int)$v['oculto'] === 1): ?>
                                    <span class="tag">Oculto</span>
                                <?php endif; ?>
                            </td>
                            <td class="muted"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                                    <input type="hidden" name="voto_id" value="<?= (int)$v['id'] ?>">
                                    <input type="hidden" name="oculto" value="<?= (int)$v['oculto'] ?>">
                                    <button type="submit"><?= (int)$v['oculto'] === 1 ? 'Mostrar' : 'Ocultar' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>
