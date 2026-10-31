<?php
require_once __DIR__ . '/security.php';

AdminSecurity::requireLogin();
$conexion = AdminSecurity::getConexion();

$csrf_token = AdminSecurity::getCsrfToken();
$msg_success = '';
$msg_error   = '';

$dias   = max(1, (int)($_GET['dias'] ?? $_POST['dias'] ?? 180));
$gracia = max(0, (int)($_GET['gracia'] ?? $_POST['gracia'] ?? 30));
$vista  = $_GET['vista'] ?? 'todas';

// ── Utilidades de borrado ────────────────────────────────────────────────────
// La mayoría de tablas no tienen FK, así que el orden de borrado es manual.
function borrar_por_bss(mysqli $c, $tabla, $bss_id)
{
    $tabla = preg_replace('/[^a-z_]/', '', $tabla);
    if (!$c->query("DELETE FROM `$tabla` WHERE bss_id = " . (int)$bss_id)) {
        throw new Exception("Error al borrar $tabla: " . $c->error);
    }
}

function borrar_ids(mysqli $c, $tabla, $columna, array $ids)
{
    $tabla   = preg_replace('/[^a-z_]/', '', $tabla);
    $columna = preg_replace('/[^a-z_]/', '', $columna);
    $ids     = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) return;
    if (!$c->query("DELETE FROM `$tabla` WHERE `$columna` IN (" . implode(',', $ids) . ")")) {
        throw new Exception("Error al borrar $tabla: " . $c->error);
    }
}

function borrar_carpeta_tienda($bss_id)
{
    $dir = dirname(__DIR__) . '/iseller_store/assets/img/tiendas/' . (int)$bss_id;
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($dir);
}

/**
 * Métricas de actividad de un negocio. Se usa tanto para listar como para
 * revalidar en el servidor antes de borrar.
 */
function metricas_negocio(mysqli $c, $bss_id, $dias, $gracia)
{
    $bss_id = (int)$bss_id;
    $row = $c->query("
        SELECT n.id, n.nombre, n.estado, n.membresia_estado, n.fecha_regitro,
               (SELECT u.usuario FROM usuarios u WHERE u.bss_id = n.id ORDER BY u.id LIMIT 1) AS login,
               (SELECT u.nombre  FROM usuarios u WHERE u.bss_id = n.id ORDER BY u.id LIMIT 1) AS dueno,
               (SELECT COUNT(*) FROM productos p WHERE p.bss_id = n.id) AS productos,
               (SELECT COUNT(*) FROM orden o WHERE o.bss_id = n.id AND o.status IN (1,4)) AS ventas_pos,
               (SELECT COUNT(*) FROM tienda_ordenes t WHERE t.bss_id = n.id AND t.status IN (1,2,3,4,6)) AS ventas_web,
               (SELECT MAX(o.created) FROM orden o WHERE o.bss_id = n.id AND o.status IN (1,4)) AS ult_pos,
               (SELECT MAX(t.created) FROM tienda_ordenes t WHERE t.bss_id = n.id AND t.status IN (1,2,3,4,6)) AS ult_web
        FROM negocio n
        WHERE n.id = $bss_id
    ")->fetch_assoc();

    if (!$row) return null;

    $row['productos']   = (int)$row['productos'];
    $row['ventas_pos']  = (int)$row['ventas_pos'];
    $row['ventas_web']  = (int)$row['ventas_web'];
    $row['ventas']      = $row['ventas_pos'] + $row['ventas_web'];
    $row['ultima_venta'] = $row['ult_pos'] && $row['ult_web']
        ? max($row['ult_pos'], $row['ult_web'])
        : ($row['ult_pos'] ?: $row['ult_web']);

    $row['antiguedad']    = (int)floor((time() - strtotime($row['fecha_regitro'])) / 86400);
    $row['dias_sin_venta'] = $row['ultima_venta']
        ? (int)floor((time() - strtotime($row['ultima_venta'])) / 86400)
        : null;

    if ($row['ventas'] === 0) {
        if ($row['antiguedad'] < $gracia) {
            $row['motivo'] = 'reciente';
        } elseif ($row['productos'] === 0) {
            $row['motivo'] = 'sin_actividad';
        } else {
            $row['motivo'] = 'nunca_vendio';
        }
    } else {
        $row['motivo'] = $row['dias_sin_venta'] >= $dias ? 'sin_venta' : 'activo';
    }

    $row['elegible'] = in_array($row['motivo'], ['sin_actividad', 'nunca_vendio', 'sin_venta'], true);

    return $row;
}

// ── Borrado definitivo de un negocio ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AdminSecurity::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: inactivos.php?error=csrf');
        exit;
    }

    $bss_id = (int)($_POST['bss_id'] ?? 0);

    try {
        $conexion->begin_transaction();

        $conexion->query("SELECT id FROM negocio WHERE id = $bss_id FOR UPDATE");
        $neg = metricas_negocio($conexion, $bss_id, $dias, $gracia);
        if (!$neg) {
            throw new Exception('El negocio no existe.');
        }
        if (!$neg['elegible']) {
            throw new Exception('La cuenta ya no cumple los criterios de inactividad (puede tener ventas recientes). Recarga la página.');
        }
        if (trim($_POST['confirmar'] ?? '') !== $neg['nombre']) {
            throw new Exception('El nombre escrito no coincide con el del negocio. No se borró nada.');
        }

        $ids = function ($sql) use ($conexion) {
            $out = [];
            $res = $conexion->query($sql);
            while ($r = $res->fetch_row()) $out[] = (int)$r[0];
            return $out;
        };

        $web_ids     = $ids("SELECT id FROM tienda_ordenes WHERE bss_id = $bss_id");
        $plan_ids    = $ids("SELECT id FROM tienda_planes_entrega WHERE bss_id = $bss_id");
        $conv_ids    = $ids("SELECT id FROM tienda_chat_conversaciones WHERE bss_id = $bss_id");
        $usuario_ids = $ids("SELECT id FROM usuarios WHERE bss_id = $bss_id");

        // Historial de compradores ligado a pedidos online del negocio
        borrar_ids($conexion, 'tienda_historial_puntos', 'compra_id', $web_ids);
        borrar_ids($conexion, 'tienda_recompensas_usuario', 'compra_id', $web_ids);
        borrar_ids($conexion, 'tienda_compras_por_usuarios', 'compra_id', $web_ids);
        borrar_ids($conexion, 'tienda_planes_entrega_pedidos', 'orden_id', $web_ids);
        borrar_ids($conexion, 'tienda_planes_entrega_pedidos', 'plan_id', $plan_ids);
        borrar_por_bss($conexion, 'tienda_orden_articulos', $bss_id);
        borrar_por_bss($conexion, 'tienda_ordenes', $bss_id);
        borrar_ids($conexion, 'tienda_reputacion_votos', 'negocio_id', [$bss_id]);
        borrar_ids($conexion, 'tienda_chat_mensajes', 'conversacion_id', $conv_ids);
        borrar_por_bss($conexion, 'tienda_chat_conversaciones', $bss_id);
        borrar_por_bss($conexion, 'tienda_chat_mensaje_compra', $bss_id);

        // Ventas POS, fiados y compras
        borrar_por_bss($conexion, 'fracciones', $bss_id);
        borrar_por_bss($conexion, 'orden_articulos', $bss_id);
        borrar_por_bss($conexion, 'creditos', $bss_id);
        borrar_por_bss($conexion, 'abonos', $bss_id);
        borrar_por_bss($conexion, 'orden', $bss_id);
        borrar_por_bss($conexion, 'compras', $bss_id);

        // Catálogo, inventario y gastos (empleados antes que sus gastos recurrentes: hay FK)
        borrar_por_bss($conexion, 'combo_items', $bss_id);
        borrar_por_bss($conexion, 'stock', $bss_id);
        borrar_ids($conexion, 'negocio_productos', 'negocio_id', [$bss_id]);
        borrar_por_bss($conexion, 'productos', $bss_id);
        borrar_por_bss($conexion, 'empleados', $bss_id);
        borrar_por_bss($conexion, 'gastos', $bss_id);
        borrar_por_bss($conexion, 'gastos_recurrentes', $bss_id);
        borrar_por_bss($conexion, 'gastos_categorias', $bss_id);

        // Operación diaria y cuentas
        borrar_por_bss($conexion, 'cortes_de_caja', $bss_id);
        borrar_por_bss($conexion, 'errores_ventas', $bss_id);
        borrar_por_bss($conexion, 'pos_tokens', $bss_id);
        borrar_por_bss($conexion, 'push_dispositivos', $bss_id);
        borrar_por_bss($conexion, 'notificaciones', $bss_id);
        borrar_por_bss($conexion, 'membresias_pagos', $bss_id);
        borrar_ids($conexion, 'users_permisos', 'id_user', $usuario_ids);
        borrar_por_bss($conexion, 'usuarios', $bss_id);

        // Configuración del negocio
        borrar_por_bss($conexion, 'clientes', $bss_id);
        borrar_por_bss($conexion, 'configuracion', $bss_id);
        borrar_por_bss($conexion, 'cambio', $bss_id);
        borrar_por_bss($conexion, 'cambios_mostrados', $bss_id);
        borrar_por_bss($conexion, 'categorias_producto', $bss_id);
        borrar_por_bss($conexion, 'sucursales', $bss_id);
        borrar_por_bss($conexion, 'tienda_planes_entrega', $bss_id);
        borrar_ids($conexion, 'negocio', 'id', [$bss_id]);

        $conexion->commit();
        try { borrar_carpeta_tienda($bss_id); } catch (Throwable $e) { /* los archivos no bloquean el borrado */ }

        header('Location: inactivos.php?msg=borrado&nombre=' . urlencode($neg['nombre']) . "&dias=$dias&gracia=$gracia");
        exit;
    } catch (Throwable $e) {
        $conexion->rollback();
        $msg_error = $e->getMessage();
    }
}

if (($_GET['msg'] ?? '') === 'borrado') {
    $msg_success = 'La cuenta "' . htmlspecialchars($_GET['nombre'] ?? '') . '" y todos sus datos asociados fueron eliminados.';
}
if (($_GET['error'] ?? '') === 'csrf') {
    $msg_error = 'Token de seguridad inválido. Intenta nuevamente.';
}

// ── Listado ──────────────────────────────────────────────────────────────────
$negocios = [];
// shortcut: tope de 500 cuentas; paginar si la base crece mucho.
$res = $conexion->query("SELECT id FROM negocio ORDER BY fecha_regitro ASC LIMIT 500");
while ($r = $res->fetch_row()) {
    $m = metricas_negocio($conexion, $r[0], $dias, $gracia);
    if ($m) $negocios[] = $m;
}

$stats = ['todas' => 0, 'nunca' => 0, 'sin_venta' => 0, 'candidatas' => 0];
foreach ($negocios as $n) {
    if ($n['motivo'] === 'activo' || $n['motivo'] === 'reciente') continue;
    $stats['todas']++;
    if (in_array($n['motivo'], ['sin_actividad', 'nunca_vendio'], true)) $stats['nunca']++;
    if ($n['motivo'] === 'sin_venta') $stats['sin_venta']++;
    if ($n['elegible']) $stats['candidatas']++;
}

$negocios = array_values(array_filter($negocios, function ($n) use ($vista) {
    if ($vista === 'nunca')    return in_array($n['motivo'], ['sin_actividad', 'nunca_vendio'], true);
    if ($vista === 'sin_venta') return $n['motivo'] === 'sin_venta';
    return $n['motivo'] !== 'activo' && $n['motivo'] !== 'reciente';
}));

$motivos = [
    'sin_actividad' => ['Sin actividad', 'badge-red'],
    'nunca_vendio'  => ['Nunca vendió', 'badge-amber'],
    'sin_venta'     => ['Sin ventas recientes', 'badge-cyan'],
];

$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? ($_SESSION['admin_user'] ?? 'Admin'));
$base_qs    = 'dias=' . $dias . '&gracia=' . $gracia . '&vista=';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iSeller Admin — Cuentas inactivas</title>
    <link href="../publico/vendors/fonts/fonts.css" rel="stylesheet">
    <link href='../publico/vendors/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #080910;
            --bg-card: rgba(255, 255, 255, 0.035);
            --bg-card-h: rgba(255, 255, 255, 0.06);
            --border: rgba(255, 255, 255, 0.08);
            --border-light: rgba(255, 255, 255, 0.12);
            --accent: #6366f1;
            --accent-2: #8b5cf6;
            --accent-glow: rgba(99, 102, 241, 0.3);
            --cyan: #06b6d4;
            --green: #22c55e;
            --amber: #f59e0b;
            --red: #ef4444;
            --text-1: #f1f5f9;
            --text-2: #94a3b8;
            --text-3: #475569;
            --radius-sm: 8px;
            --radius: 14px;
            --sidebar-w: 240px;
            --header-h: 68px;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-1);
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.018) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none; z-index: 0;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: rgba(10, 11, 18, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column; z-index: 100;
        }
        .sidebar-logo {
            height: var(--header-h); padding: 0 20px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; box-shadow: 0 4px 14px var(--accent-glow);
        }
        .sidebar-logo-text .brand { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; }
        .sidebar-logo-text .tag {
            font-size: 9px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--accent);
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 4px; padding: 1px 5px; margin-left: 6px;
        }
        .sidebar-nav { flex: 1; padding: 16px 12px; display: flex; flex-direction: column; gap: 4px; }
        .nav-section {
            font-size: 10px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--text-3); padding: 12px 12px 4px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: var(--radius-sm);
            color: var(--text-2); text-decoration: none;
            font-size: 13px; font-weight: 500; transition: all 0.15s;
        }
        .nav-item:hover { color: var(--text-1); background: var(--bg-card); }
        .nav-item.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(99, 102, 241, 0.35); font-weight: 600;
        }
        .sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); }
        .user-card {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: var(--radius-sm); background: var(--bg-card);
        }
        .user-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
        }
        .user-info { flex: 1; min-width: 0; }
        .user-info .name { font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-info .role { font-size: 10px; color: var(--text-2); }
        .btn-logout { background: none; border: none; cursor: pointer; color: var(--text-2); font-size: 14px; }
        .btn-logout:hover { color: var(--red); }

        /* ── Layout ── */
        .main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; position: relative; z-index: 1; }
        .header {
            position: sticky; top: 0; z-index: 50; height: var(--header-h);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px; background: rgba(8, 9, 16, 0.85);
            backdrop-filter: blur(16px); border-bottom: 1px solid var(--border);
        }
        .header-left h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; }
        .header-left p { font-size: 12px; color: var(--text-2); margin-top: 1px; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .badge-live {
            display: flex; align-items: center; gap: 6px;
            background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.25);
            border-radius: 20px; padding: 5px 12px; font-size: 11px; font-weight: 600; color: #86efac;
        }
        .badge-live .dot { width: 6px; height: 6px; background: var(--green); border-radius: 50%; animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .5; transform: scale(.8); } }
        .btn-refresh {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-sm); color: var(--text-2);
            padding: 7px 11px; cursor: pointer; font-size: 13px; transition: all .15s;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .btn-refresh:hover { background: var(--bg-card-h); color: var(--text-1); }
        .content { padding: 28px 32px; flex: 1; }

        /* ── Alerts ── */
        .alert-box {
            padding: 14px 18px; border-radius: var(--radius-sm);
            margin-bottom: 22px; font-size: 13px; display: flex; align-items: center; gap: 10px;
        }
        .alert-box.success { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.3); color: #86efac; }
        .alert-box.danger  { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }

        /* ── KPIs ── */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
        .kpi-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 18px 20px; transition: all .2s;
        }
        .kpi-card:hover { border-color: var(--border-light); transform: translateY(-2px); }
        .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--text-2); }
        .kpi-val { font-size: 24px; font-weight: 800; margin-top: 6px; letter-spacing: -.5px; }
        .kpi-card.red .kpi-val { color: var(--red); }
        .kpi-card.amber .kpi-val { color: var(--amber); }
        .kpi-card.cyan .kpi-val { color: var(--cyan); }

        /* ── Toolbar ── */
        .toolbar {
            display: flex; align-items: flex-end; justify-content: space-between;
            gap: 16px; margin-bottom: 18px; flex-wrap: wrap;
        }
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .tab-btn {
            background: var(--bg-card); border: 1px solid var(--border);
            color: var(--text-2); padding: 7px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 600; text-decoration: none;
            transition: all .15s; display: inline-flex; align-items: center; gap: 6px;
        }
        .tab-btn:hover { background: var(--bg-card-h); color: var(--text-1); }
        .tab-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); box-shadow: 0 2px 10px var(--accent-glow); }
        .tab-count { background: rgba(255,255,255,.15); border-radius: 10px; padding: 1px 6px; font-size: 10px; }

        .params { display: flex; align-items: flex-end; gap: 10px; }
        .params label { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: var(--text-2); margin-bottom: 5px; }
        .params input {
            width: 88px; background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 8px; padding: 8px 10px; color: var(--text-1); font-size: 12.5px; outline: none;
        }
        .params input:focus { border-color: var(--accent); }
        .params button {
            background: var(--bg-card); border: 1px solid var(--border); color: var(--text-2);
            padding: 9px 13px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;
        }
        .params button:hover { background: var(--bg-card-h); color: var(--text-1); }

        /* ── Table ── */
        .table-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th {
            background: rgba(15,17,23,.95); padding: 12px 16px; text-align: left;
            font-size: 10.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: var(--text-2); border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        td { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: middle; }
        tbody tr:hover td { background: rgba(255,255,255,.025); }
        .muted { font-size: 11px; color: var(--text-2); }
        .dim { color: var(--text-3); }
        .num { text-align: right; font-variant-numeric: tabular-nums; }

        .badge {
            display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px;
            border-radius: 20px; font-size: 10.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px; white-space: nowrap;
        }
        .badge-amber { background: rgba(245,158,11,.15); color: #fbbf24; border: 1px solid rgba(245,158,11,.3); }
        .badge-red   { background: rgba(239,68,68,.15);  color: #fca5a5; border: 1px solid rgba(239,68,68,.3); }
        .badge-cyan  { background: rgba(6,182,212,.15);  color: #67e8f9; border: 1px solid rgba(6,182,212,.3); }

        .btn-delete {
            background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.35);
            color: #fca5a5; border-radius: 6px; padding: 6px 11px;
            font-size: 11.5px; font-weight: 700; cursor: pointer; transition: all .15s;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-delete:hover { background: rgba(239,68,68,.25); transform: translateY(-1px); }

        .empty { text-align: center; padding: 44px; color: var(--text-2); font-size: 13px; }
        .empty i { font-size: 32px; display: block; margin-bottom: 8px; opacity: .5; }

        /* ── Modal ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.75);
            backdrop-filter: blur(6px); display: none; align-items: center;
            justify-content: center; z-index: 1000; padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal-card {
            background: #10121a; border: 1px solid var(--border-light);
            border-radius: 14px; max-width: 480px; width: 100%; padding: 26px;
            box-shadow: 0 24px 60px rgba(0,0,0,.8);
        }
        .modal-title { font-size: 16px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .modal-desc { font-size: 13px; color: var(--text-2); margin-bottom: 16px; line-height: 1.55; }
        .modal-desc strong { color: #fca5a5; }
        .modal-form label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
        .modal-form input {
            width: 100%; background: #080910; border: 1px solid var(--border);
            border-radius: 8px; color: var(--text-1); padding: 10px 12px;
            font-size: 13px; font-family: inherit; margin-bottom: 14px; outline: none;
        }
        .modal-form input:focus { border-color: var(--accent); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
        .btn-cancel {
            background: var(--bg-card); border: 1px solid var(--border); color: var(--text-2);
            padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn-confirm {
            background: linear-gradient(135deg, #ef4444, #dc2626); border: none; color: #fff;
            padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer;
        }
        .btn-confirm:disabled { opacity: .45; cursor: not-allowed; }

        @media (max-width: 1024px) {
            :root { --sidebar-w: 0px; }
            .sidebar { display: none; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .content { padding: 20px; }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">⚡</div>
            <div class="sidebar-logo-text">
                <span class="brand">iSeller</span>
                <span class="tag">Admin</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <span class="nav-section">Menú</span>
            <a class="nav-item" href="dashboard.php"><i class="bx bx-home-alt"></i> Dashboard Negocios</a>
            <a class="nav-item" href="pagos.php"><i class="bx bx-credit-card"></i> Pagos Membresía</a>
            <a class="nav-item" href="reputacion.php"><i class="bx bx-star"></i> Reputación</a>
            <a class="nav-item active" href="inactivos.php"><i class="bx bx-user-x"></i> Cuentas inactivas</a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div class="user-info">
                    <div class="name"><?= $admin_name ?></div>
                    <div class="role">Super Admin</div>
                </div>
                <a href="logout.php" class="btn-logout" title="Cerrar sesión">⏻</a>
            </div>
        </div>
    </aside>

    <div class="main">
        <header class="header">
            <div class="header-left">
                <h1>Cuentas inactivas</h1>
                <p>Negocios sin actividad o sin ventas recientes · <?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="header-right">
                <div class="badge-live"><span class="dot"></span> En línea</div>
                <a href="inactivos.php?<?= $base_qs ?>todas" class="btn-refresh" title="Recargar"><i class="bx bx-refresh"></i> Actualizar</a>
                <a href="logout.php" class="btn-refresh" title="Cerrar sesión">⏻</a>
            </div>
        </header>

        <div class="content">
            <?php if ($msg_success): ?>
                <div class="alert-box success"><i class="bx bx-check-circle" style="font-size:18px;"></i><div><?= $msg_success ?></div></div>
            <?php endif; ?>
            <?php if ($msg_error): ?>
                <div class="alert-box danger"><i class="bx bx-error-circle" style="font-size:18px;"></i><div><?= htmlspecialchars($msg_error) ?></div></div>
            <?php endif; ?>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Cuentas inactivas</div>
                    <div class="kpi-val"><?= $stats['todas'] ?></div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">Nunca vendieron</div>
                    <div class="kpi-val"><?= $stats['nunca'] ?></div>
                </div>
                <div class="kpi-card amber">
                    <div class="kpi-label">Sin vender +<?= $dias ?> días</div>
                    <div class="kpi-val"><?= $stats['sin_venta'] ?></div>
                </div>
                <div class="kpi-card cyan">
                    <div class="kpi-label">Se pueden borrar</div>
                    <div class="kpi-val"><?= $stats['candidatas'] ?></div>
                </div>
            </div>

            <div class="toolbar">
                <div class="filter-tabs">
                    <a href="inactivos.php?dias=<?= $dias ?>&gracia=<?= $gracia ?>&vista=todas" class="tab-btn <?= $vista === 'todas' ? 'active' : '' ?>">
                        <i class="bx bx-list-ul"></i> Todas <span class="tab-count"><?= $stats['todas'] ?></span>
                    </a>
                    <a href="inactivos.php?dias=<?= $dias ?>&gracia=<?= $gracia ?>&vista=nunca" class="tab-btn <?= $vista === 'nunca' ? 'active' : '' ?>">
                        <i class="bx bx-user-x"></i> Nunca vendieron <span class="tab-count"><?= $stats['nunca'] ?></span>
                    </a>
                    <a href="inactivos.php?dias=<?= $dias ?>&gracia=<?= $gracia ?>&vista=sin_venta" class="tab-btn <?= $vista === 'sin_venta' ? 'active' : '' ?>">
                        <i class="bx bx-time"></i> Sin ventas recientes <span class="tab-count"><?= $stats['sin_venta'] ?></span>
                    </a>
                </div>

                <form class="params" method="get" action="inactivos.php">
                    <input type="hidden" name="vista" value="<?= htmlspecialchars($vista) ?>">
                    <div>
                        <label for="gracia">Gracia (días)</label>
                        <input type="number" id="gracia" name="gracia" min="0" max="365" value="<?= $gracia ?>">
                    </div>
                    <div>
                        <label for="dias">Sin vender (días)</label>
                        <input type="number" id="dias" name="dias" min="1" max="3650" value="<?= $dias ?>">
                    </div>
                    <button type="submit" title="Aplicar umbrales"><i class="bx bx-filter-alt"></i> Aplicar</button>
                </form>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Negocio</th>
                                <th>Cuenta</th>
                                <th>Registro</th>
                                <th>Productos</th>
                                <th>Ventas</th>
                                <th>Última venta</th>
                                <th>Motivo</th>
                                <th style="text-align:right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($negocios)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty">
                                            <i class="bx bx-inbox"></i>
                                            No hay cuentas que cumplan estos criterios.
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($negocios as $n):
                                    $motivo = $motivos[$n['motivo']];
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($n['nombre']) ?></strong>
                                            <div class="muted">#<?= (int)$n['id'] ?><?= $n['membresia_estado'] ? ' · ' . htmlspecialchars($n['membresia_estado']) : '' ?></div>
                                        </td>
                                        <td>
                                            <?php if ($n['login']): ?>
                                                <?= htmlspecialchars($n['dueno'] ?: '—') ?>
                                                <div class="muted"><?= htmlspecialchars($n['login']) ?></div>
                                            <?php else: ?>
                                                <span class="dim">Sin usuario</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($n['fecha_regitro'])) ?>
                                            <div class="muted">hace <?= (int)$n['antiguedad'] ?> días</div>
                                        </td>
                                        <td class="num"><?= number_format($n['productos']) ?></td>
                                        <td class="num"><?= number_format($n['ventas']) ?></td>
                                        <td>
                                            <?php if ($n['ultima_venta']): ?>
                                                <?= date('d/m/Y', strtotime($n['ultima_venta'])) ?>
                                                <div class="muted">hace <?= (int)$n['dias_sin_venta'] ?> días</div>
                                            <?php else: ?>
                                                <span class="dim">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?= $motivo[1] ?>"><?= $motivo[0] ?></span></td>
                                        <td style="text-align:right;">
                                            <?php if ($n['elegible']): ?>
                                                <button type="button" class="btn-delete"
                                                    onclick="abrirBorrado(<?= (int)$n['id'] ?>, <?= htmlspecialchars(json_encode($n['nombre']), ENT_QUOTES) ?>, <?= (int)$n['productos'] ?>, <?= (int)$n['ventas'] ?>)">
                                                    <i class="bx bx-trash"></i> Borrar
                                                </button>
                                            <?php else: ?>
                                                <span class="dim" style="font-size:11px;">En gracia</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Modal borrado ── -->
    <div class="modal-overlay" id="modalBorrar">
        <div class="modal-card">
            <h3 class="modal-title" style="color: var(--red);">
                <i class="bx bx-error-circle"></i> Borrar cuenta definitivamente
            </h3>
            <p class="modal-desc">
                Se eliminará <strong id="mNombre"></strong> y todos sus datos: productos, ventas,
                pedidos online, clientes, sucursales, pagos de membresía, chats y archivos de la tienda.
                <strong>Esta acción no se puede deshacer.</strong>
            </p>
            <p class="modal-desc" id="mResumen" style="margin-top:-8px;"></p>

            <form method="post" action="inactivos.php" class="modal-form" id="formBorrar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="bss_id" id="mBssId" value="">
                <input type="hidden" name="dias" value="<?= $dias ?>">
                <input type="hidden" name="gracia" value="<?= $gracia ?>">

                <label for="confirmar">Escribe el nombre exacto del negocio para confirmar</label>
                <input type="text" name="confirmar" id="confirmar" autocomplete="off" required>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-confirm" id="btnConfirmar" disabled>
                        <i class="bx bx-trash"></i> Borrar para siempre
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let nombreEsperado = '';

        function abrirBorrado(id, nombre, productos, ventas) {
            nombreEsperado = nombre;
            document.getElementById('mBssId').value = id;
            document.getElementById('mNombre').textContent = nombre;
            document.getElementById('mResumen').innerHTML =
                'Se borrarán <strong>' + productos + '</strong> producto(s) y <strong>' + ventas + '</strong> venta(s).';
            document.getElementById('confirmar').value = '';
            document.getElementById('btnConfirmar').disabled = true;
            document.getElementById('modalBorrar').classList.add('open');
            document.getElementById('confirmar').focus();
        }

        function cerrarModal() {
            document.getElementById('modalBorrar').classList.remove('open');
        }

        document.getElementById('confirmar').addEventListener('input', function () {
            document.getElementById('btnConfirmar').disabled = this.value.trim() !== nombreEsperado;
        });

        document.getElementById('modalBorrar').addEventListener('click', function (e) {
            if (e.target === this) cerrarModal();
        });
    </script>
</body>

</html>
