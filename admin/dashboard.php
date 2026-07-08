<?php
session_start();

// ── Guard ────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// ── DB connection (reusing existing config pattern) ───────────────────────────
function cargarDotEnv($ruta)
{
    $archivoEnv = rtrim($ruta, '/') . '/.env';
    $archivoAlt = rtrim($ruta, '/') . '/env';
    if (file_exists($archivoEnv)) $archivo = $archivoEnv;
    elseif (file_exists($archivoAlt)) $archivo = $archivoAlt;
    else return;

    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        if (strpos(trim($linea), '#') === 0) continue;
        list($nombre, $valor) = explode('=', $linea, 2);
        $nombre = trim($nombre);
        $valor = trim($valor);
        if (!isset($_ENV[$nombre])) $_ENV[$nombre] = $valor;
    }
}

cargarDotEnv(dirname(__DIR__) . '/../../');
$usuario   = $_ENV['DB_USER']  ?? 'root';
$contrasena = $_ENV['DB_PASS'] ?? '';
$baseDatos  = $_ENV['DB_NAME'] ?? '';

$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
    die('<p style="color:red;font-family:monospace;padding:40px">DB Error: ' . $conexion->connect_error . '</p>');
}

// ── Queries ───────────────────────────────────────────────────────────────────

// 1. Todos los negocios (tabla negocio — todas las columnas)
$negocios = [];
$res = $conexion->query("SELECT * FROM negocio ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) $negocios[] = $row;
}

// 2. Para cada negocio: última venta, cantidad de ventas y cantidad de sucursales
$negocios_stats = [];
foreach ($negocios as $neg) {
    $bid = (int)$neg['id'];

    // Última venta
    $ultima_venta = null;
    $resUV = $conexion->query("
        SELECT * FROM orden
        WHERE bss_id = $bid AND status IN ('1','4')
        ORDER BY id DESC LIMIT 1
    ");
    if ($resUV && $resUV->num_rows > 0) {
        $ultima_venta = $resUV->fetch_assoc();
    }

    // Cantidad total de ventas completadas
    $cant_ventas = 0;
    $resCant = $conexion->query("
        SELECT COUNT(*) AS total FROM orden
        WHERE bss_id = $bid AND status IN ('1','4')
    ");
    if ($resCant) {
        $r = $resCant->fetch_assoc();
        $cant_ventas = (int)($r['total'] ?? 0);
    }

    // Cantidad de sucursales
    $cant_sucursales = 0;
    $resSuc = $conexion->query("
        SELECT COUNT(*) AS total FROM sucursales
        WHERE bss_id = $bid
    ");
    if ($resSuc) {
        $r = $resSuc->fetch_assoc();
        $cant_sucursales = (int)($r['total'] ?? 0);
    }

    $negocios_stats[$bid] = [
        'ultima_venta'    => $ultima_venta,
        'cant_ventas'     => $cant_ventas,
        'cant_sucursales' => $cant_sucursales,
    ];
}

// 3. Columnas de la tabla negocio (dinámica)
$columnas_negocio = [];
if (!empty($negocios)) {
    $columnas_negocio = array_keys($negocios[0]);
}

// 4. Totales globales
$total_negocios   = count($negocios);
$total_ventas_global = 0;
$total_sucursales_global = 0;
foreach ($negocios_stats as $s) {
    $total_ventas_global    += $s['cant_ventas'];
    $total_sucursales_global += $s['cant_sucursales'];
}

$admin_name = htmlspecialchars($_SESSION['admin_user'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iSeller Admin — Dashboard</title>
    <meta name="description" content="Dashboard de administración iSeller. Vista de negocios, ventas y sucursales.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #080910;
            --bg-surface: #0f1117;
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
            --radius-lg: 20px;
            --sidebar-w: 240px;
            --header-h: 68px;
        }

        html,
        body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-1);
            overflow-x: hidden;
        }

        /* ── Background effects ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.018) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.12;
            pointer-events: none;
            z-index: 0;
        }

        .orb-1 {
            width: 600px;
            height: 600px;
            background: var(--accent);
            top: -200px;
            left: -200px;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: var(--accent-2);
            bottom: -150px;
            right: -150px;
        }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: rgba(15, 17, 23, 0.9);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            padding: 0;
        }

        .sidebar-logo {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            box-shadow: 0 4px 12px var(--accent-glow);
        }

        .sidebar-logo-text {
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo-text .brand {
            font-size: 15px;
            font-weight: 700;
        }

        .sidebar-logo-text .tag {
            font-size: 10px;
            color: var(--text-2);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            color: var(--text-2);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
        }

        .nav-item:hover {
            background: var(--bg-card-h);
            color: var(--text-1);
        }

        .nav-item.active {
            background: rgba(99, 102, 241, 0.15);
            color: var(--accent);
        }

        .nav-item .icon {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .nav-section {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-3);
            padding: 12px 12px 4px;
        }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid var(--border);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-sm);
            background: var(--bg-card);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: 50% !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-info {
            flex: 1;
            min-width: 0;
        }

        .user-info .name {
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-info .role {
            font-size: 10px;
            color: var(--text-2);
        }

        .btn-logout {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-2);
            font-size: 14px;
            padding: 4px;
            transition: color 0.2s;
            flex-shrink: 0;
        }

        .btn-logout:hover {
            color: var(--red);
        }

        /* ── Main layout ── */
        .main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 1;
        }

        /* ── Header ── */
        .header {
            position: sticky;
            top: 0;
            z-index: 50;
            height: var(--header-h);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            background: rgba(8, 9, 16, 0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
        }

        .header-left h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .header-left p {
            font-size: 12px;
            color: var(--text-2);
            margin-top: 1px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge-live {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.25);
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #86efac;
        }

        .badge-live .dot {
            width: 6px;
            height: 6px;
            background: var(--green);
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(0.8);
            }
        }

        .btn-refresh {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-2);
            padding: 7px 10px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.15s;
        }

        .btn-refresh:hover {
            background: var(--bg-card-h);
            color: var(--text-1);
            border-color: var(--border-light);
        }

        /* ── Content ── */
        .content {
            flex: 1;
            padding: 32px;
        }

        /* ── KPI Cards ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .kpi-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
            animation: fadeIn 0.5s ease both;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            border-color: var(--border-light);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--kpi-color, linear-gradient(90deg, var(--accent), var(--accent-2)));
        }

        .kpi-card:nth-child(1) {
            --kpi-color: linear-gradient(90deg, #6366f1, #8b5cf6);
            animation-delay: 0.05s;
        }

        .kpi-card:nth-child(2) {
            --kpi-color: linear-gradient(90deg, #06b6d4, #6366f1);
            animation-delay: 0.10s;
        }

        .kpi-card:nth-child(3) {
            --kpi-color: linear-gradient(90deg, #22c55e, #06b6d4);
            animation-delay: 0.15s;
        }

        .kpi-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-2);
            margin-bottom: 12px;
        }

        .kpi-value {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -1.5px;
            color: var(--text-1);
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 12px;
            color: var(--text-2);
        }

        .kpi-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 28px;
            opacity: 0.2;
        }

        /* ── Section headers ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .section-sub {
            font-size: 12px;
            color: var(--text-2);
            margin-top: 2px;
        }

        .section-badge {
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.25);
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 11px;
            color: #a5b4fc;
            font-weight: 600;
        }

        /* ── Table wrapper ── */
        .table-wrap {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: 32px;
            animation: fadeIn 0.5s ease 0.2s both;
        }

        .table-scroll {
            overflow-x: auto;
            max-height: 500px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: rgba(15, 17, 23, 0.95);
            backdrop-filter: blur(8px);
        }

        th {
            padding: 12px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-2);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text-1);
            vertical-align: middle;
            white-space: nowrap;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr {
            transition: background 0.15s;
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, 0.025);
        }

        /* ── Status badges ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .badge-green {
            background: rgba(34, 197, 94, 0.12);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.25);
        }

        .badge-red {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .badge-amber {
            background: rgba(245, 158, 11, 0.12);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .badge-cyan {
            background: rgba(6, 182, 212, 0.12);
            color: #67e8f9;
            border: 1px solid rgba(6, 182, 212, 0.25);
        }

        .badge-purple {
            background: rgba(99, 102, 241, 0.12);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.25);
        }

        /* Number cells */
        .cell-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .cell-id {
            color: var(--text-3);
            font-size: 11px;
            font-weight: 600;
        }

        /* ── Stats row within table ── */
        .stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── Empty state ── */
        .empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-2);
        }

        .empty-icon {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.4;
        }

        .empty p {
            font-size: 13px;
        }

        /* ── Venta info cell ── */
        .venta-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .venta-id {
            font-size: 10px;
            color: var(--text-3);
        }

        .venta-total {
            font-size: 13px;
            font-weight: 700;
            color: var(--green);
        }

        .venta-date {
            font-size: 10px;
            color: var(--text-2);
        }

        /* ── Animations ── */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            :root {
                --sidebar-w: 0px;
            }

            .sidebar {
                display: none;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
            }
        }

        /* ── Tooltip ── */
        [title] {
            cursor: help;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- ── Sidebar ── -->
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
            <a class="nav-item active" href="dashboard.php">
                <span class="icon">🏠</span> Dashboard
            </a>
            <a class="nav-item" href="dashboard.php">
                <span class="icon">🏢</span> Negocios
            </a>
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

    <!-- ── Main ── -->
    <div class="main">

        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1>Panel de Negocios</h1>
                <p>Resumen general del sistema · <?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="header-right">
                <div class="badge-live">
                    <span class="dot"></span>
                    En línea
                </div>
                <button class="btn-refresh" onclick="window.location.reload()" title="Actualizar datos">🔄</button>
                <a href="logout.php" style="text-decoration:none">
                    <button class="btn-refresh" title="Cerrar sesión">⏻</button>
                </a>
            </div>
        </header>

        <!-- Content -->
        <div class="content">

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Negocios registrados</div>
                    <div class="kpi-value"><?= $total_negocios ?></div>
                    <div class="kpi-sub">Total en base de datos</div>
                    <div class="kpi-icon">🏢</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Ventas totales</div>
                    <div class="kpi-value"><?= number_format($total_ventas_global) ?></div>
                    <div class="kpi-sub">Órdenes completadas (todos los negocios)</div>
                    <div class="kpi-icon">💳</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Sucursales activas</div>
                    <div class="kpi-value"><?= number_format($total_sucursales_global) ?></div>
                    <div class="kpi-sub">Suma total de sucursales</div>
                    <div class="kpi-icon">📍</div>
                </div>
            </div>

            <!-- Negocios Table -->
            <div class="section-header">
                <div>
                    <div class="section-title">Negocios registrados</div>
                    <div class="section-sub">Todas las columnas de la tabla <code style="background:rgba(255,255,255,0.06);padding:1px 6px;border-radius:4px;font-size:11px;">negocio</code></div>
                </div>
                <span class="section-badge"><?= $total_negocios ?> registros</span>
            </div>

            <div class="table-wrap">
                <div class="table-scroll">
                    <?php if (empty($negocios)): ?>
                        <div class="empty">
                            <div class="empty-icon">🏢</div>
                            <p>No hay negocios registrados aún.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach ($columnas_negocio as $col): ?>
                                        <th><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $col))) ?></th>
                                    <?php endforeach; ?>
                                    <!-- Extra computed columns -->
                                    <th>Ventas totales</th>
                                    <th>Última venta</th>
                                    <th>Sucursales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($negocios as $neg):
                                    $bid   = (int)$neg['id'];
                                    $stats = $negocios_stats[$bid];
                                    $uv    = $stats['ultima_venta'];
                                ?>
                                    <tr>
                                        <?php foreach ($columnas_negocio as $col):
                                            $val = $neg[$col];
                                        ?>
                                            <td>
                                                <?php
                                                // Smart cell rendering
                                                if ($col === 'id') {
                                                    echo '<span class="cell-id">#' . htmlspecialchars($val) . '</span>';
                                                } elseif ($col === 'estado') {
                                                    $e_val = strtolower($val ?? '');
                                                    if ($e_val === 'activo')      $cls = 'badge-green';
                                                    elseif ($e_val === 'inactivo') $cls = 'badge-red';
                                                    elseif ($e_val === 'suspendido') $cls = 'badge-amber';
                                                    else                           $cls = 'badge-cyan';
                                                    echo '<span class="badge ' . $cls . '">' . htmlspecialchars($val ?? '—') . '</span>';
                                                } elseif ($col === 'plan') {
                                                    echo $val ? '<span class="badge badge-purple">' . htmlspecialchars($val) . '</span>' : '<span style="color:var(--text-3)">—</span>';
                                                } elseif (is_null($val) || $val === '') {
                                                    echo '<span style="color:var(--text-3)">—</span>';
                                                } else {
                                                    echo '<span title="' . htmlspecialchars($val) . '">' . htmlspecialchars(mb_strimwidth($val, 0, 40, '…')) . '</span>';
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>

                                        <!-- Ventas totales -->
                                        <td class="cell-num">
                                            <span class="stat-pill">
                                                💳 <?= number_format($stats['cant_ventas']) ?>
                                            </span>
                                        </td>

                                        <!-- Última venta -->
                                        <td>
                                            <?php if ($uv): ?>
                                                <div class="venta-info">
                                                    <span class="venta-id">Orden #<?= htmlspecialchars($uv['id']) ?></span>
                                                    <span class="venta-total">$<?= number_format((float)($uv['total_price'] ?? 0), 2) ?></span>
                                                    <span class="venta-date"><?= htmlspecialchars($uv['created'] ?? '—') ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:var(--text-3);font-size:11px;">Sin ventas</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Sucursales -->
                                        <td class="cell-num">
                                            <span class="stat-pill">
                                                📍 <?= $stats['cant_sucursales'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Per-business stats table -->
            <div class="section-header">
                <div>
                    <div class="section-title">Métricas por negocio</div>
                    <div class="section-sub">Última venta, cantidad de ventas y sucursales</div>
                </div>
                <span class="section-badge">Resumen</span>
            </div>

            <div class="table-wrap">
                <div class="table-scroll">
                    <?php if (empty($negocios)): ?>
                        <div class="empty">
                            <div class="empty-icon">📊</div>
                            <p>No hay datos disponibles.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Negocio</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Plan</th>
                                    <th>Cantidad de ventas</th>
                                    <th>Última venta — Orden ID</th>
                                    <th>Última venta — Total $</th>
                                    <th>Última venta — Total Bs</th>
                                    <th>Última venta — Fecha</th>
                                    <th>Sucursales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($negocios as $neg):
                                    $bid   = (int)$neg['id'];
                                    $stats = $negocios_stats[$bid];
                                    $uv    = $stats['ultima_venta'];
                                ?>
                                    <tr>
                                        <td class="cell-id">#<?= $bid ?></td>
                                        <td><strong><?= htmlspecialchars($neg['nombre'] ?? '—') ?></strong></td>
                                        <td><?= htmlspecialchars($neg['tipo'] ?? '—') ?></td>
                                        <td>
                                            <?php $e = strtolower($neg['estado'] ?? ''); ?>
                                            <?php
                                            if ($e === 'activo')          $badge_cls = 'badge-green';
                                            elseif ($e === 'inactivo')    $badge_cls = 'badge-red';
                                            elseif ($e === 'suspendido')  $badge_cls = 'badge-amber';
                                            else                          $badge_cls = 'badge-cyan';
                                            ?>
                                            <span class="badge <?= $badge_cls ?>">
                                                <?= htmlspecialchars($neg['estado'] ?? '—') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $neg['plan'] ? '<span class="badge badge-purple">' . htmlspecialchars($neg['plan']) . '</span>' : '<span style="color:var(--text-3)">—</span>' ?>
                                        </td>
                                        <td class="cell-num">
                                            <strong><?= number_format($stats['cant_ventas']) ?></strong>
                                        </td>
                                        <?php if ($uv): ?>
                                            <td class="cell-id">#<?= htmlspecialchars($uv['id']) ?></td>
                                            <td class="cell-num" style="color:var(--green);font-weight:700">
                                                $<?= number_format((float)($uv['total_price'] ?? 0), 2) ?>
                                            </td>
                                            <td class="cell-num">
                                                Bs <?= number_format((float)($uv['total_price_bs'] ?? 0), 2) ?>
                                            </td>
                                            <td><?= htmlspecialchars($uv['created'] ?? '—') ?></td>
                                        <?php else: ?>
                                            <td colspan="4" style="color:var(--text-3);font-size:11px;text-align:center">Sin ventas registradas</td>
                                        <?php endif; ?>
                                        <td class="cell-num">
                                            <span class="badge badge-cyan"><?= $stats['cant_sucursales'] ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /content -->
    </div><!-- /main -->

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(() => window.location.reload(), 5 * 60 * 1000);
    </script>
</body>

</html>