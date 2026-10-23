<?php
require_once('includes/requires.php');

if (!isset($_SESSION["validate"]) || $_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
}

$tipo_u = (int)$_SESSION['nivel'];
$bss_id = (int)$_SESSION['bss_id'];

// Verificacion de permisos para empleados (nivel 2)
if ($tipo_u === 2) {
    $has_perm = false;
    if (isset($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        foreach ($_SESSION['permisos'] as $p_item) {
            if (is_array($p_item) && in_array('rendimiento_categoria.php', $p_item)) {
                $has_perm = true;
                break;
            } elseif ($p_item === 'rendimiento_categoria.php') {
                $has_perm = true;
                break;
            }
        }
    }
    
    // Consulta directa a la base de datos por seguridad
    if (!$has_perm && isset($_SESSION['id'])) {
        $u_id = (int)$_SESSION['id'];
        $stmtP = $conexion->prepare("
            SELECT up.id 
            FROM users_permisos up
            INNER JOIN menu m ON m.id = up.id_item_menu
            WHERE up.id_user = ? AND m.dir = 'rendimiento_categoria.php'
            LIMIT 1
        ");
        if ($stmtP) {
            $stmtP->bind_param('i', $u_id);
            $stmtP->execute();
            if ($stmtP->get_result()->num_rows > 0) {
                $has_perm = true;
            }
            $stmtP->close();
        }
    }

    if (!$has_perm) {
        header('Location: index.php');
        exit;
    }
}

$topnav = topnav();

// 1. Obtener categorias asociadas a productos existentes
$stmtCats = $conexion->prepare("
    SELECT 
        cp.id, 
        cp.nombre, 
        COUNT(p.id) AS total_productos
    FROM categorias_producto cp
    INNER JOIN productos p ON p.categoria = cp.id
    WHERE p.bss_id = ? AND p.activo = 0
    GROUP BY cp.id, cp.nombre
    HAVING total_productos > 0
    ORDER BY cp.nombre ASC
");
$stmtCats->bind_param('i', $bss_id);
$stmtCats->execute();
$resCats = $stmtCats->get_result();
$categorias = [];
while ($rowC = $resCats->fetch_assoc()) {
    $categorias[] = $rowC;
}
$stmtCats->close();

// Verificar si existen productos sin categoria asignada
$stmtSinCat = $conexion->prepare("
    SELECT COUNT(id) AS total_sin_cat 
    FROM productos 
    WHERE bss_id = ? AND activo = 0 AND (categoria IS NULL OR categoria = 0)
");
$stmtSinCat->bind_param('i', $bss_id);
$stmtSinCat->execute();
$resSinCat = $stmtSinCat->get_result()->fetch_assoc();
$total_sin_cat = (int)($resSinCat['total_sin_cat'] ?? 0);
$stmtSinCat->close();

// 2. Obtener sucursales (solo para administradores)
$sucursales = [];
if ($tipo_u === 1) {
    $stmtSuc = $conexion->prepare("SELECT id, nombre, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC");
    $stmtSuc->bind_param('i', $bss_id);
    $stmtSuc->execute();
    $resSuc = $stmtSuc->get_result();
    while ($rowS = $resSuc->fetch_assoc()) {
        $sucursales[] = $rowS;
    }
    $stmtSuc->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Rendimiento por Categoría | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <script src="../vendors/apexcharts/apexcharts.min.js"></script>

    <style>
        .right_col {
            background: var(--dash-bg) !important;
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        /* Encabezado */
        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--dash-border);
        }

        .dash-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0;
            letter-spacing: -0.3px;
        }

        .dash-header p {
            color: var(--dash-text-muted);
            margin: 4px 0 0;
            font-size: 13px;
        }

        .dash-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Selectores integrados al estilo del dashboard */
        .select-filter-wrap {
            position: relative;
        }

        .select-filter {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 34px 8px 14px;
            border-radius: 8px;
            outline: none;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%238892a0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            min-width: 250px;
        }

        .select-filter:hover,
        .select-filter:focus {
            border-color: rgba(45, 212, 160, 0.4);
            box-shadow: 0 0 0 1px rgba(45, 212, 160, 0.1);
        }

        .select-filter option {
            background: #1a1e24;
            color: #f3f4f6;
            padding: 8px;
        }

        .btn-refresh-dash {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            outline: none;
        }

        .btn-refresh-dash:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, 0.4);
            background: rgba(45, 212, 160, 0.05);
        }

        .btn-refresh-dash.spinning svg {
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        /* Subtitulos de Seccion */
        .section-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-label .badge-cat {
            color: var(--dash-mint);
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(45, 212, 160, 0.08);
            border: 1px solid rgba(45, 212, 160, 0.2);
            text-transform: none;
            letter-spacing: 0;
        }

        /* KPI Cards: SIN background, solo borde, tal como en index.php */
        .kpi-row-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .kpi-row-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .kpi-card {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 22px 24px 18px;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            overflow: hidden;
            background: transparent;
        }

        .kpi-card:hover {
            border-color: rgba(45, 212, 160, 0.35);
            box-shadow: 0 0 0 1px rgba(45, 212, 160, 0.08), 0 8px 30px rgba(0, 0, 0, 0.25);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--dash-mint), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .kpi-card:hover::before {
            opacity: 0.6;
        }

        .kpi-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .kpi-info h6 {
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text-muted);
            margin: 0 0 2px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .kpi-info small {
            font-size: 11px;
            color: rgba(136, 146, 160, 0.6);
        }

        .kpi-value {
            font-size: 30px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.1;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .kpi-metrics {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            padding-top: 10px;
            border-top: 1px solid rgba(46, 53, 62, 0.5);
        }

        .metric-up {
            color: var(--dash-mint);
            font-weight: 600;
        }

        .metric-badge {
            font-size: 11px;
            font-weight: 600;
            color: var(--dash-mint);
            background: rgba(45, 212, 160, 0.08);
            border: 1px solid rgba(45, 212, 160, 0.2);
            padding: 2px 7px;
            border-radius: 4px;
        }

        /* Paneles de Analisis: SIN background, solo borde */
        .dash-panel {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            overflow: hidden;
            background: transparent;
            margin-bottom: 20px;
        }

        .dash-panel .panel-header {
            padding: 18px 22px 14px;
            border-bottom: 1px solid var(--dash-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .dash-panel .panel-header h6 {
            font-size: 14px;
            font-weight: 600;
            color: var(--dash-text);
            margin: 0;
        }

        .panel-badge {
            font-size: 11px;
            font-weight: 500;
            color: var(--dash-text-muted);
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--dash-border);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .dash-panel .panel-body {
            padding: 16px 20px;
        }

        /* Lista de Ranking: SIN background individual, solo separadores de linea */
        .rank-list {
            display: flex;
            flex-direction: column;
        }

        .rank-item {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid rgba(46, 53, 62, 0.5);
            transition: background 0.15s ease;
            gap: 14px;
            background: transparent;
        }

        .rank-item:last-child {
            border-bottom: none;
        }

        .rank-item:hover {
            background: rgba(45, 212, 160, 0.03);
        }

        .rank-index {
            width: 26px;
            font-size: 12px;
            font-weight: 700;
            color: var(--dash-text-muted);
            text-align: center;
            flex-shrink: 0;
        }

        .rank-item:first-child .rank-index {
            color: var(--dash-mint);
        }

        .rank-info {
            flex: 1;
            min-width: 0;
        }

        .rank-info h6 {
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text);
            margin: 0 0 2px;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .rank-info small {
            font-size: 11px;
            color: var(--dash-text-muted);
            display: flex;
            gap: 10px;
        }

        .rank-info small span {
            color: var(--dash-text);
            font-weight: 500;
        }

        .rank-financials {
            text-align: right;
            white-space: nowrap;
        }

        .rank-total {
            font-size: 15px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .rank-profit {
            font-size: 11px;
            font-weight: 600;
            color: var(--dash-mint);
        }

        .rank-empty {
            padding: 48px 22px;
            text-align: center;
            color: var(--dash-text-muted);
            font-size: 13px;
        }

        /* Layout de dos columnas para grafico y ranking */
        .analytics-row {
            display: grid;
            grid-template-columns: 7fr 5fr;
            gap: 20px;
        }

        .analytics-col {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 0;
        }

        .analytics-col .dash-panel {
            margin-bottom: 0;
        }

        @media (max-width: 1024px) {
            .kpi-row-3 {
                grid-template-columns: 1fr;
            }
            .kpi-row-2 {
                grid-template-columns: 1fr;
            }
            .analytics-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu; ?>
            <?php echo $topnav; ?>

            <div class="right_col" role="main">
                <!-- Barra de Controles superior -->
                <div class="d-flex justify-content-end dash-header">
                    <div class="dash-controls">
                        <!-- Selector de Categorias -->
                        <div class="select-filter-wrap">
                            <select id="selectCategoria" class="select-filter" title="Seleccionar categoría de productos">
                                <?php if (!empty($categorias)): ?>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['nombre']); ?> (<?php echo $cat['total_productos']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if ($total_sin_cat > 0): ?>
                                    <option value="0" <?php echo empty($categorias) ? 'selected' : ''; ?>>
                                        Sin categoría asignada (<?php echo $total_sin_cat; ?>)
                                    </option>
                                <?php endif; ?>

                                <?php if (empty($categorias) && $total_sin_cat === 0): ?>
                                    <option value="" disabled selected>Catálogo sin existencias</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Selector de Sucursal (solo admin) -->
                        <?php if ($tipo_u === 1 && count($sucursales) > 1): ?>
                            <div class="select-filter-wrap" style="min-width: 170px;">
                                <select id="selectSucursal" class="select-filter" style="min-width: 170px;" title="Filtrar por sucursal">
                                    <option value="todas">Todas las sucursales</option>
                                    <?php foreach ($sucursales as $suc): ?>
                                        <option value="<?php echo $suc['id']; ?>">
                                            <?php echo htmlspecialchars($suc['nombre']); ?><?php echo $suc['principal'] == 1 ? ' (Principal)' : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <!-- Boton Refrescar -->
                        <button type="button" class="btn-refresh-dash" id="btnRefresh" title="Actualizar datos">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- 1. Tarjetas de Ventas y Ganancias (Hoy, Semana, Mes) -->
                <div class="section-label">
                    <span>Ventas y Ganancias de la Categoría</span>
                    <span class="badge-cat" id="badgeCategoriaActual">Categoría</span>
                </div>

                <div class="kpi-row-3">
                    <!-- Hoy -->
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Ventas de Hoy</h6>
                                <small>Facturación de las últimas 24h</small>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi_ventas_hoy">$0.00</div>
                        <div class="kpi-metrics">
                            <span class="metric-up">
                                Ganancia: <span id="kpi_ganancia_hoy">$0.00</span>
                            </span>
                            <span class="metric-badge" id="kpi_margen_hoy">0.0% margen</span>
                        </div>
                    </div>

                    <!-- Semana -->
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Ventas de la Semana</h6>
                                <small>Lunes al momento actual</small>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi_ventas_semana">$0.00</div>
                        <div class="kpi-metrics">
                            <span class="metric-up">
                                Ganancia: <span id="kpi_ganancia_semana">$0.00</span>
                            </span>
                            <span class="metric-badge" id="kpi_margen_semana">0.0% margen</span>
                        </div>
                    </div>

                    <!-- Mes -->
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Ventas del Mes</h6>
                                <small>Acumulado del mes actual</small>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi_ventas_mes">$0.00</div>
                        <div class="kpi-metrics">
                            <span class="metric-up">
                                Ganancia: <span id="kpi_ganancia_mes">$0.00</span>
                            </span>
                            <span class="metric-badge" id="kpi_margen_mes">0.0% margen</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Indicadores de Inventario (Existencias y Valorizacion) -->
                <div class="section-label">
                    <span>Estado del Inventario</span>
                </div>

                <div class="kpi-row-2">
                    <!-- Cantidad Total en Stock -->
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Cantidad Total en Stock</h6>
                                <small id="kpi_total_productos_cat">0 referencias registradas</small>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi_stock_unidades">0</div>
                        <div class="kpi-metrics">
                            <span style="color:var(--dash-text-muted);">Unidades físicas en sucursal activa</span>
                        </div>
                    </div>

                    <!-- Valor Total del Stock al Costo -->
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Valor Total del Stock (Costo)</h6>
                                <small>Valoración contable de inventario</small>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi_stock_valor">$0.00</div>
                        <div class="kpi-metrics">
                            <span style="color:var(--dash-text-muted);">
                                Proyección comercial: <span id="kpi_stock_valor_venta" style="color:var(--dash-text);font-weight:600;">$0.00</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. Grafico Semanal y Clasificacion Top 5 -->
                <div class="analytics-row">
                    <!-- Grafico Semanal -->
                    <div class="dash-panel">
                        <div class="panel-header">
                            <h6>Ventas Semanales por Día</h6>
                            <span class="panel-badge">Semana actual</span>
                        </div>
                        <div class="panel-body p-0" id="chartVentasCategoria" style="min-height: 330px;">
                        </div>
                    </div>

                    <!-- Clasificacion Top 5 -->
                    <div class="analytics-col">
                        <div class="dash-panel">
                            <div class="panel-header">
                                <h6>Top 5 Productos Más Vendidos</h6>
                                <span class="panel-badge">Esta semana</span>
                            </div>
                            <div class="panel-body p-0">
                                <div class="rank-list" id="contenedorTopProductos">
                                    <!-- Filas cargadas dinamicamente -->
                                </div>
                            </div>
                        </div>

                        <div class="dash-panel">
                            <div class="panel-header">
                                <h6>Top 5 Productos Más Rentables</h6>
                                <span class="panel-badge">Por ganancia · Esta semana</span>
                            </div>
                            <div class="panel-body p-0">
                                <div class="rank-list" id="contenedorTopRentables">
                                    <!-- Filas cargadas dinamicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts requeridos -->
    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let weeklyChart = null;

            function formatMoney(amount) {
                const val = Number(amount) || 0;
                return '$' + val.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function initWeeklyChart() {
                const options = {
                    series: [{
                        name: 'Ventas',
                        data: [0, 0, 0, 0, 0, 0, 0]
                    }],
                    chart: {
                        type: 'area',
                        height: 330,
                        toolbar: { show: false },
                        zoom: { enabled: false },
                        background: 'transparent',
                        fontFamily: 'inherit'
                    },
                    colors: ['#2dd4a0'],
                    dataLabels: { enabled: false },
                    stroke: {
                        curve: 'smooth',
                        width: 2.5
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.35,
                            opacityTo: 0.02,
                            stops: [0, 95, 100]
                        }
                    },
                    xaxis: {
                        categories: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                        labels: {
                            style: {
                                colors: '#8892a0',
                                fontSize: '11px',
                                fontWeight: 500
                            }
                        },
                        axisBorder: { color: 'rgba(255, 255, 255, 0.08)' },
                        axisTicks: { color: 'rgba(255, 255, 255, 0.08)' }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#8892a0',
                                fontSize: '11px'
                            },
                            formatter: function(val) {
                                return '$' + Number(val).toFixed(2);
                            }
                        }
                    },
                    grid: {
                        borderColor: 'rgba(255, 255, 255, 0.05)',
                        strokeDashArray: 3,
                        xaxis: { lines: { show: false } },
                        yaxis: { lines: { show: true } }
                    },
                    tooltip: {
                        theme: 'dark',
                        y: {
                            formatter: function(val) {
                                return '$' + Number(val).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    },
                    markers: {
                        size: 3.5,
                        colors: ['#2dd4a0'],
                        strokeColors: '#1a1e24',
                        strokeWidth: 2,
                        hover: { size: 5.5 }
                    }
                };

                const el = document.querySelector('#chartVentasCategoria');
                if (el) {
                    weeklyChart = new ApexCharts(el, options);
                    weeklyChart.render();
                }
            }

            function renderTopProductos(topList) {
                const container = document.getElementById('contenedorTopProductos');
                if (!container) return;

                if (!topList || topList.length === 0) {
                    container.innerHTML = `
                        <div class="rank-empty">
                            No se registran ventas para esta categoría durante la semana actual.
                        </div>
                    `;
                    return;
                }

                let html = '';
                topList.forEach((item, index) => {
                    const posNumber = String(index + 1).padStart(2, '0');
                    const unidadesLabel = Number(item.unidades) === 1 ? '1 unidad' : `${item.unidades} unidades`;

                    html += `
                        <div class="rank-item">
                            <div class="rank-index">${posNumber}</div>
                            <div class="rank-info">
                                <h6 title="${item.nombre}">${item.nombre}</h6>
                                <small>
                                    Vendido: <span>${unidadesLabel}</span>
                                    Stock: <span>${item.stock}</span>
                                </small>
                            </div>
                            <div class="rank-financials">
                                <div class="rank-total">${formatMoney(item.ingreso)}</div>
                                <div class="rank-profit">+${formatMoney(item.ganancia)}</div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            }

            function renderTopRentables(topList) {
                const container = document.getElementById('contenedorTopRentables');
                if (!container) return;

                if (!topList || topList.length === 0) {
                    container.innerHTML = `
                        <div class="rank-empty">
                            No se registran ventas para esta categoría durante la semana actual.
                        </div>
                    `;
                    return;
                }

                let html = '';
                topList.forEach((item, index) => {
                    const posNumber = String(index + 1).padStart(2, '0');
                    const unidadesLabel = Number(item.unidades) === 1 ? '1 unidad' : `${item.unidades} unidades`;
                    const ganancia = Number(item.ganancia) || 0;

                    html += `
                        <div class="rank-item">
                            <div class="rank-index">${posNumber}</div>
                            <div class="rank-info">
                                <h6 title="${item.nombre}">${item.nombre}</h6>
                                <small>
                                    Vendido: <span>${unidadesLabel}</span>
                                    Stock: <span>${item.stock}</span>
                                </small>
                            </div>
                            <div class="rank-financials">
                                <div class="rank-total" style="color:${ganancia >= 0 ? 'var(--dash-mint)' : 'var(--dash-danger)'};">${ganancia >= 0 ? '+' : '-'}${formatMoney(Math.abs(ganancia))}</div>
                                <div class="rank-profit" style="color:var(--dash-text-muted);">${formatMoney(item.ingreso)} venta</div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            }

            function cargarDatos() {
                const selectCat = document.getElementById('selectCategoria');
                const selectSuc = document.getElementById('selectSucursal');
                const btnRef = document.getElementById('btnRefresh');

                if (!selectCat || selectCat.value === "") return;

                const catVal = selectCat.value;
                const sucVal = selectSuc ? selectSuc.value : '';

                if (btnRef) btnRef.classList.add('spinning');

                const params = new URLSearchParams();
                params.append('categoria', catVal);
                if (sucVal && sucVal !== 'todas') {
                    params.append('sucursal', sucVal);
                }

                fetch('db/rendimiento_categoria_api.php?' + params.toString())
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Respuesta inválida del servidor: ' + response.statusText);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (btnRef) btnRef.classList.remove('spinning');

                        if (!data || !data.success) {
                            console.error('Error en la API:', data ? data.error : 'Sin datos');
                            return;
                        }

                        // 1. Etiqueta de Categoria
                        const badgeCat = document.getElementById('badgeCategoriaActual');
                        if (badgeCat && data.categoria) {
                            badgeCat.textContent = data.categoria.nombre;
                        }

                        // 2. Indicadores de Ventas y Ganancias
                        const kpis = data.kpis || {};
                        document.getElementById('kpi_ventas_hoy').textContent = formatMoney(kpis.ventas_hoy);
                        document.getElementById('kpi_ganancia_hoy').textContent = formatMoney(kpis.ganancia_hoy);
                        document.getElementById('kpi_margen_hoy').textContent = (kpis.margen_hoy || 0) + '% margen';

                        document.getElementById('kpi_ventas_semana').textContent = formatMoney(kpis.ventas_semana);
                        document.getElementById('kpi_ganancia_semana').textContent = formatMoney(kpis.ganancia_semana);
                        document.getElementById('kpi_margen_semana').textContent = (kpis.margen_semana || 0) + '% margen';

                        document.getElementById('kpi_ventas_mes').textContent = formatMoney(kpis.ventas_mes);
                        document.getElementById('kpi_ganancia_mes').textContent = formatMoney(kpis.ganancia_mes);
                        document.getElementById('kpi_margen_mes').textContent = (kpis.margen_mes || 0) + '% margen';

                        // 3. Telemetria de Stock
                        const unidades = Number(kpis.stock_unidades) || 0;
                        document.getElementById('kpi_stock_unidades').textContent = unidades.toLocaleString('en-US');
                        
                        const prodsCount = data.categoria.total_productos || 0;
                        document.getElementById('kpi_total_productos_cat').textContent = prodsCount + (prodsCount === 1 ? ' referencia registrada' : ' referencias registradas');

                        document.getElementById('kpi_stock_valor').textContent = formatMoney(kpis.stock_valor_costo);
                        document.getElementById('kpi_stock_valor_venta').textContent = formatMoney(kpis.stock_valor_venta);

                        // 4. Actualizar Grafico
                        if (weeklyChart && data.grafico_semanal) {
                            weeklyChart.updateSeries([{
                                name: data.categoria.nombre,
                                data: data.grafico_semanal.ventas
                            }]);
                        }

                        // 5. Actualizar Clasificacion de Productos
                        renderTopProductos(data.top_productos);
                        renderTopRentables(data.top_productos_rentables);
                    })
                    .catch(err => {
                        if (btnRef) btnRef.classList.remove('spinning');
                        console.error('Error al cargar datos:', err);
                    });
            }

            initWeeklyChart();
            cargarDatos();

            const selectCategoria = document.getElementById('selectCategoria');
            if (selectCategoria) {
                selectCategoria.addEventListener('change', cargarDatos);
            }

            const selectSucursal = document.getElementById('selectSucursal');
            if (selectSucursal) {
                selectSucursal.addEventListener('change', cargarDatos);
            }

            const btnRefresh = document.getElementById('btnRefresh');
            if (btnRefresh) {
                btnRefresh.addEventListener('click', cargarDatos);
            }
        });
    </script>
</body>

</html>
