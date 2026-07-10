<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav        = topnav();
    $bss_id        = $_SESSION['bss_id'];
    $idProducto    = intval($_GET['id'] ?? 0);
    $nivelUsuario  = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];
    $id            = $idProducto;

    /* ─── Product info ─────────────────────────────────────────────── */
    $stmtP = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmtP->bind_param('i', $idProducto);
    $stmtP->execute();
    $rowP      = $stmtP->get_result()->fetch_assoc();
    $stmtP->close();
    $nombreP   = $rowP['nombre']   ?? 'Producto';
    $categoria = $rowP['categoria'] ?? '';
    $codigo    = $rowP['codigo']    ?? '';

    /* ─── Helpers ──────────────────────────────────────────────────── */
    function ventaProducto($conexion, $idProducto, $campo, $valor, $tipo = null)
    {
        $tipoClause = $tipo ? "AND o.status = '$tipo'" : "AND o.status IN (1,4)";
        $sql = "SELECT oa.precio_venta_dolar, oa.precio, oa.quantity, o.descontado, o.status
                FROM orden o
                JOIN orden_articulos oa ON oa.order_id = o.id
                WHERE oa.product_id = '$idProducto'
                  AND o.$campo = '$valor'
                  $tipoClause";
        $res     = $conexion->query($sql);
        $ventas  = 0;
        $costo   = 0;
        while ($r = $res->fetch_assoc()) {
            $sub = $r['precio_venta_dolar'] * $r['quantity'];
            if ($r['status'] == '4') {
                $sub -= $sub * floatval($r['descontado']) / 100;
            }
            $ventas += $sub;
            $costo  += $r['precio'] * $r['quantity'];
        }
        return ['ventas' => round($ventas, 2), 'ganancia' => round($ventas - $costo, 2)];
    }

    $dia    = date('Y-m-d');
    $semana = date('Y-W');
    $mes    = date('Y-m');

    /* ─── KPI data ─────────────────────────────────────────────────── */
    $kpiDia    = ventaProducto($conexion, $idProducto, 'modified', $dia);
    $kpiSemana = ventaProducto($conexion, $idProducto, 'semana',   $semana);
    $kpiMes    = ventaProducto($conexion, $idProducto, 'fecha',    $mes);

    /* ─── Monthly chart data (current year) ────────────────────────── */
    $anio       = date('Y');
    $mesesLabel = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $chartVentas    = [];
    $chartGanancias = [];
    $chartCantidad  = [];

    for ($m = 1; $m <= 12; $m++) {
        $mesKey = $anio . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
        $d = ventaProducto($conexion, $idProducto, 'fecha', $mesKey);
        $chartVentas[]    = $d['ventas'];
        $chartGanancias[] = $d['ganancia'];

        // Quantity for the month
        $stmtQty = $conexion->prepare(
            "SELECT SUM(oa.quantity) as qty
             FROM orden o
             JOIN orden_articulos oa ON oa.order_id = o.id
             WHERE oa.product_id = ?
               AND o.fecha = ?
               AND o.status IN (1,4)"
        );
        $stmtQty->bind_param('is', $idProducto, $mesKey);
        $stmtQty->execute();
        $qtyRow = $stmtQty->get_result()->fetch_assoc();
        $stmtQty->close();
        $chartCantidad[] = intval($qtyRow['qty'] ?? 0);
    }

    /* ─── Sucursales for period table ──────────────────────────────── */
    $stmtS = $conexion->prepare("SELECT id, nombre FROM sucursales WHERE bss_id = ?");
    $stmtS->bind_param('s', $bss_id);
    $stmtS->execute();
    $sucursalesRows = $stmtS->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtS->close();
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($nombreP) ?> — Ficha de Producto</title>
        <?php require_once('includes/headers.php'); ?>

        <!-- Flatpickr date picker -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

        <style>
            /* ── Google Font ─────────────────────────────────────────── */
            @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;700&display=swap');

            /* ── Reset / base ────────────────────────────────────────── */
            .ficha-wrap * {
                box-sizing: border-box;
            }

            .ficha-wrap {
                font-family: 'DM Sans', sans-serif;
                color: #e2e8f0;
                padding: 24px 20px 40px;
            }

            /* ── Page Header ─────────────────────────────────────────── */
            .ficha-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                margin-bottom: 32px;
                animation: fadeSlideDown .5s ease both;
            }

            .ficha-header-left h1 {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 26px;
                font-weight: 700;
                color: #f8fafc;
                margin: 0 0 4px;
                line-height: 1.2;
            }

            .ficha-header-left .sub {
                font-size: 13px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .badge-cat {
                display: inline-block;
                border: 1px solid rgba(99, 102, 241, .3);
                border-radius: 20px;
                padding: 2px 10px;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .04em;
            }

            .badge-code {
                display: inline-block;
                background: rgba(20, 184, 166, .12);
                color: #2dd4bf;
                border: 1px solid rgba(20, 184, 166, .25);
                border-radius: 20px;
                padding: 2px 10px;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .04em;
            }

            /* ── KPI Grid ────────────────────────────────────────────── */
            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
                margin-bottom: 28px;
            }

            .kpi-card {
                border: 1px solid rgba(255, 255, 255, .07);
                border-radius: 16px;
                padding: 20px 22px;
                position: relative;
                overflow: hidden;
                cursor: default;
                transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
                animation: fadeSlideUp .5s ease both;
            }

            .kpi-card:nth-child(1) {
                animation-delay: .05s;
            }

            .kpi-card:nth-child(2) {
                animation-delay: .10s;
            }

            .kpi-card:nth-child(3) {
                animation-delay: .15s;
            }

            .kpi-card:nth-child(4) {
                animation-delay: .20s;
            }

            .kpi-card:nth-child(5) {
                animation-delay: .25s;
            }

            .kpi-card:nth-child(6) {
                animation-delay: .30s;
            }

            .kpi-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 16px 40px rgba(0, 0, 0, .4);
                border-color: rgba(255, 255, 255, .14);
            }

            .kpi-card::before {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: inherit;
                z-index: -1;
            }

            .kpi-glow {
                position: absolute;
                width: 90px;
                height: 90px;
                border-radius: 50%;
                filter: blur(30px);
                opacity: .35;
                top: -20px;
                right: -20px;
                pointer-events: none;
            }

            .kpi-card-label {
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: #94a3b8;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .kpi-icon {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
            }

            .kpi-value {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 28px;
                font-weight: 700;
                color: #f1f5f9;
                line-height: 1;
                margin-bottom: 6px;
            }

            .kpi-sub {
                font-size: 12px;
                color: #94a3b8;
            }

            .kpi-sub span {
                font-weight: 600;
            }


            /* ── Panel ───────────────────────────────────────────────── */
            .ficha-panel {
                border: 1px solid rgba(255, 255, 255, .07);
                border-radius: 18px;
                overflow: hidden;
                animation: fadeSlideUp .5s ease .3s both;
            }

            .ficha-panel+.ficha-panel {
                margin-top: 20px;
            }

            .ficha-panel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                padding: 18px 22px;
                border-bottom: 1px solid rgba(255, 255, 255, .06);
            }

            .ficha-panel-title {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 15px;
                font-weight: 600;
                color: #f1f5f9;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .ficha-panel-title i {
                font-size: 16px;
            }

            .ficha-panel-body {
                padding: 22px;
            }

            /* ── Date Filter ─────────────────────────────────────────── */
            .filter-row {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .filter-field {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .filter-field label {
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .07em;
                color: #64748b;
            }

            .filter-input {
                background: rgba(30, 41, 59, .8);
                border: 1px solid rgba(255, 255, 255, .1);
                border-radius: 10px;
                color: #e2e8f0;
                padding: 8px 14px;
                font-family: 'DM Sans', sans-serif;
                font-size: 14px;
                outline: none;
                cursor: pointer;
                transition: border-color .2s ease;
            }

            .filter-input:focus {
                border-color: #6366f1;
            }

            .filter-input::placeholder {
                color: #475569;
            }

            .btn-filter {
                background: linear-gradient(135deg, #2dd4a0, #25b88a);
                border: none;
                border-radius: 10px;
                color: #fff;
                font-family: 'DM Sans', sans-serif;
                font-size: 14px;
                font-weight: 600;
                padding: 9px 22px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: opacity .2s ease, transform .15s ease;
                align-self: flex-end;
            }

            .btn-filter:hover {
                opacity: .9;
                transform: translateY(-1px);
            }

            .btn-filter:active {
                transform: translateY(0);
            }

            .btn-filter.loading {
                opacity: .7;
                pointer-events: none;
            }

            /* Preset chips */
            .preset-chips {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
                align-self: flex-end;
            }

            .preset-chip {
                background: rgba(30, 41, 59, .7);
                border: 1px solid rgba(255, 255, 255, .09);
                border-radius: 20px;
                color: #94a3b8;
                font-size: 12px;
                font-weight: 500;
                padding: 5px 12px;
                cursor: pointer;
                transition: background .2s ease, color .2s ease, border-color .2s ease;
            }

            .preset-chip:hover,
            .preset-chip.active {
                background: #2dd49f50;
                color: #2dd49ff5;
                border-color: #2dd49f7a;
            }

            /* ── Period Results Table ────────────────────────────────── */
            #period-results {
                margin-top: 18px;
            }

            .branch-block {
                margin-bottom: 24px;
            }

            .branch-block:last-child {
                margin-bottom: 0;
            }

            .branch-title {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 13px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .1em;
                color: #64748b;
                margin: 0 0 10px;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .branch-title::after {
                content: '';
                flex: 1;
                height: 1px;
                background: rgba(255, 255, 255, .06);
            }

            .ficha-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }

            .ficha-table th {
                text-align: left;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: #475569;
                padding: 10px 14px;
                border-bottom: 1px solid rgba(255, 255, 255, .06);
            }

            .ficha-table td {
                padding: 12px 14px;
                border-bottom: 1px solid rgba(255, 255, 255, .04);
                color: #cbd5e1;
                vertical-align: middle;
            }

            .ficha-table tr:last-child td {
                border-bottom: none;
            }

            .ficha-table tr:hover td {
                background: rgba(255, 255, 255, .03);
            }

            .num-big {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 20px;
                font-weight: 700;
                color: #f1f5f9;
            }

            .num-sub {
                font-size: 12px;
                color: #64748b;
            }

            .gain-pos {
                color: #34d399;
                font-weight: 600;
            }

            .gain-neg {
                color: #f87171;
                font-weight: 600;
            }

            /* loading / empty states */
            .state-loading,
            .state-empty {
                text-align: center;
                padding: 32px 0;
                color: #475569;
                font-size: 14px;
            }

            .state-loading i,
            .state-empty i {
                display: block;
                font-size: 28px;
                margin-bottom: 8px;
            }

            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }

            .spin {
                animation: spin .8s linear infinite;
                display: inline-block;
            }

            /* ── Chart Panel ─────────────────────────────────────────── */
            #ficha-chart {
                min-height: 280px;
            }

            /* ── Animations ──────────────────────────────────────────── */
            @keyframes fadeSlideDown {
                from {
                    opacity: 0;
                    transform: translateY(-14px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeSlideUp {
                from {
                    opacity: 0;
                    transform: translateY(18px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* ── Flatpickr overrides ──────────────────────────────────── */
            .flatpickr-calendar {
                background: #0f172a !important;
                border: 1px solid rgba(255, 255, 255, .1) !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, .5) !important;
                border-radius: 12px !important;
            }

            .flatpickr-day.selected,
            .flatpickr-day.startRange,
            .flatpickr-day.endRange {
                background: #6366f1 !important;
                border-color: #6366f1 !important;
            }

            .flatpickr-day.inRange {
                background: rgba(99, 102, 241, .2) !important;
                border-color: transparent !important;
            }

            .flatpickr-day {
                color: #94a3b8 !important;
            }

            .flatpickr-day:hover {
                background: rgba(99, 102, 241, .15) !important;
            }

            .flatpickr-months .flatpickr-month,
            .flatpickr-weekday,
            .numInputWrapper {
                color: #94a3b8 !important;
            }

            /* ── Responsive ──────────────────────────────────────────── */
            @media (max-width: 640px) {
                .kpi-grid {
                    grid-template-columns: 1fr 1fr;
                }

                .ficha-header {
                    flex-direction: column;
                }
            }
        </style>
    </head>

    <body class="nav-md">
        <div class="container body">
            <div class="main_container">

                <?php echo $menu ?>
                <?php echo $topnav ?>

                <div class="right_col" role="main">
                    <div class="ficha-wrap">

                        <!-- ── Page Header ─────────────────────────────────── -->
                        <div class="ficha-header">
                            <div class="ficha-header-left">
                                <h1><?= htmlspecialchars($nombreP) ?></h1>
                                <div class="sub">
                                    <?php if ($codigo): ?>
                                        <span class="badge-code"><i class="fa fa-barcode"></i> <?= htmlspecialchars($codigo) ?></span>
                                    <?php endif; ?>
                                    <?php if ($categoria): ?>
                                        <span class="badge-cat"><?= htmlspecialchars($categoria) ?></span>
                                    <?php endif; ?>
                                    <span>Ficha de producto</span>
                                </div>
                            </div>
                        </div>

                        <!-- ── KPI Cards ───────────────────────────────────── -->
                        <div class="kpi-grid">
                            <?php
                            $kpis = [
                                ['label' => 'Ventas Hoy',          'icon' => 'fa fa-sun-o',      'color' => 'amber',  'ventas' => $kpiDia['ventas'],    'ganancia' => $kpiDia['ganancia']],
                                ['label' => 'Ventas Esta Semana',  'icon' => 'fa fa-calendar',   'color' => 'blue',   'ventas' => $kpiSemana['ventas'], 'ganancia' => $kpiSemana['ganancia']],
                                ['label' => 'Ventas Este Mes',     'icon' => 'fa fa-chart-bar',  'color' => 'indigo', 'ventas' => $kpiMes['ventas'],    'ganancia' => $kpiMes['ganancia']],
                            ];
                            foreach ($kpis as $k): ?>
                                <div class="kpi-card <?= $k['color'] ?>">
                                    <div class="kpi-glow"></div>
                                    <div class="kpi-card-label">
                                        <span class="kpi-icon"><i class="<?= $k['icon'] ?>"></i></span>
                                        <?= $k['label'] ?>
                                    </div>
                                    <div class="kpi-value">$<?= number_format($k['ventas'], 2, '.', ',') ?></div>
                                    <div class="kpi-sub">
                                        Ganancia: <span class="<?= $k['ganancia'] >= 0 ? 'gain-pos' : 'gain-neg' ?>">
                                            $<?= number_format($k['ganancia'], 2, '.', ',') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- ── Chart Panel ─────────────────────────────────── -->
                        <div class="ficha-panel">
                            <div class="ficha-panel-header">
                                <span class="ficha-panel-title">
                                    <i class="fa fa-area-chart" style="color:#6366f1;"></i>
                                    Evolución Mensual <?= $anio ?>
                                </span>
                            </div>
                            <div class="ficha-panel-body" style="padding-bottom: 10px;">
                                <div id="ficha-chart"></div>
                            </div>
                        </div>

                        <!-- ── Period Filter Panel ─────────────────────────── -->
                        <div class="ficha-panel">
                            <div class="ficha-panel-header">
                                <span class="ficha-panel-title">
                                    <i class="fa fa-filter" style="color:#14b8a6;"></i>
                                    Ventas por Período
                                </span>
                            </div>
                            <div class="ficha-panel-body">
                                <div class="filter-row">
                                    <div class="filter-field">
                                        <label>Desde</label>
                                        <input id="fp-desde" class="filter-input" type="text"
                                            placeholder="<?= date('Y-m-01') ?>" value="<?= date('Y-m-01') ?>">
                                    </div>
                                    <div class="filter-field">
                                        <label>Hasta</label>
                                        <input id="fp-hasta" class="filter-input" type="text"
                                            placeholder="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="preset-chips">
                                        <span class="preset-chip" data-preset="today">Hoy</span>
                                        <span class="preset-chip" data-preset="week">Esta semana</span>
                                        <span class="preset-chip active" data-preset="month">Este mes</span>
                                        <span class="preset-chip" data-preset="quarter">Trimestre</span>
                                        <span class="preset-chip" data-preset="year">Este año</span>
                                    </div>
                                    <button class="btn-filter" id="btn-filtrar">
                                        <i class="fa fa-search"></i> Consultar
                                    </button>
                                </div>

                                <div id="period-results">
                                    <div class="state-loading">
                                        <i class="fa fa-circle-o-notch spin"></i>
                                        Cargando datos…
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /ficha-wrap -->
                </div><!-- /right_col -->

            </div><!-- /main_container -->
        </div><!-- /container body -->

        <!-- Scripts -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <script src="../vendors/nprogress/nprogress.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <script>
            /* ── ApexCharts ─────────────────────────────────────────────────── */
            (function() {
                const ventasSeries = <?= json_encode($chartVentas) ?>;
                const gananciasSeries = <?= json_encode($chartGanancias) ?>;
                const cantidadSeries = <?= json_encode($chartCantidad) ?>;
                const meses = <?= json_encode($mesesLabel) ?>;
                const mesActual = <?= date('n') ?> - 1; // 0-indexed

                // Highlight current month
                const barColors = meses.map((_, i) =>
                    i === mesActual ? '#6366f1' : 'rgba(99,102,241,.35)'
                );

                const opts = {
                    series: [{
                            name: 'Ventas ($)',
                            type: 'bar',
                            data: ventasSeries
                        },
                        {
                            name: 'Ganancia ($)',
                            type: 'bar',
                            data: gananciasSeries
                        },
                        {
                            name: 'Unidades',
                            type: 'line',
                            data: cantidadSeries
                        },
                    ],
                    chart: {
                        type: 'bar',
                        height: 270,
                        background: 'transparent',
                        toolbar: {
                            show: false
                        },
                        fontFamily: "'DM Sans', sans-serif",
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        },
                    },
                    stroke: {
                        width: [0, 0, 2.5],
                        curve: 'smooth'
                    },
                    markers: {
                        size: [0, 0, 4],
                        colors: ['#2dd4bf'],
                        strokeColors: '#0f172a',
                        strokeWidth: 2
                    },
                    colors: ['#6366f1', '#34d399', '#2dd4bf'],
                    dataLabels: {
                        enabled: false
                    },
                    xaxis: {
                        categories: meses,
                        labels: {
                            style: {
                                colors: '#64748b',
                                fontSize: '12px'
                            }
                        },
                        axisBorder: {
                            color: 'rgba(255,255,255,.06)'
                        },
                        axisTicks: {
                            color: 'rgba(255,255,255,.06)'
                        },
                    },
                    yaxis: [{
                            labels: {
                                style: {
                                    colors: '#64748b'
                                },
                                formatter: v => '$' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v.toFixed(0))
                            },
                            axisBorder: {
                                show: false
                            },
                        },
                        {
                            show: false
                        },
                        {
                            opposite: true,
                            labels: {
                                style: {
                                    colors: '#64748b'
                                },
                                formatter: v => v.toFixed(0) + ' u'
                            },
                        },
                    ],
                    grid: {
                        borderColor: 'rgba(255,255,255,.04)',
                        strokeDashArray: 4
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            colors: '#94a3b8'
                        },
                        markers: {
                            radius: 4
                        },
                    },
                    tooltip: {
                        theme: 'dark',
                        y: [{
                                formatter: v => '$' + Number(v).toFixed(2)
                            },
                            {
                                formatter: v => '$' + Number(v).toFixed(2)
                            },
                            {
                                formatter: v => v + ' unidades'
                            },
                        ],
                    },
                    theme: {
                        mode: 'dark'
                    },
                };

                const chart = new ApexCharts(document.querySelector('#ficha-chart'), opts);
                chart.render();
            })();

            /* ── Flatpickr ──────────────────────────────────────────────────── */
            flatpickr('#fp-desde', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                defaultDate: '<?= date('Y-m-01') ?>',
            });
            flatpickr('#fp-hasta', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                defaultDate: '<?= date('Y-m-d') ?>',
            });

            /* ── Preset chips ───────────────────────────────────────────────── */
            document.querySelectorAll('.preset-chip').forEach(chip => {
                chip.addEventListener('click', function() {
                    document.querySelectorAll('.preset-chip').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');

                    const today = new Date();
                    const fmt = d => d.toISOString().slice(0, 10);
                    let desde, hasta = fmt(today);

                    switch (this.dataset.preset) {
                        case 'today':
                            desde = fmt(today);
                            break;
                        case 'week': {
                            const d = new Date(today);
                            d.setDate(d.getDate() - d.getDay() + 1);
                            desde = fmt(d);
                            break;
                        }
                        case 'month':
                            desde = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
                            break;
                        case 'quarter':
                            desde = fmt(new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1));
                            break;
                        case 'year':
                            desde = fmt(new Date(today.getFullYear(), 0, 1));
                            break;
                    }
                    document.querySelector('#fp-desde')._flatpickr.setDate(desde);
                    document.querySelector('#fp-hasta')._flatpickr.setDate(hasta);
                    consultarPeriodo();
                });
            });

            /* ── Period query ───────────────────────────────────────────────── */
            function consultarPeriodo() {
                const desde = document.getElementById('fp-desde').value;
                const hasta = document.getElementById('fp-hasta').value;
                const pid = <?= $idProducto ?>;
                const btn = document.getElementById('btn-filtrar');
                const results = document.getElementById('period-results');

                btn.classList.add('loading');
                btn.innerHTML = '<i class="fa fa-circle-o-notch" style="animation:spin .8s linear infinite;"></i> Consultando…';

                results.innerHTML = `
        <div class="state-loading">
            <i class="fa fa-circle-o-notch spin"></i>
            Procesando período…
        </div>`;

                fetch(`ficha_consulta_periodo.php?id=${pid}&desde=${desde}&hasta=${hasta}`)
                    .then(r => r.json())
                    .then(data => {
                        btn.classList.remove('loading');
                        btn.innerHTML = '<i class="fa fa-search"></i> Consultar';

                        if (data.error) {
                            results.innerHTML = `<div class="state-empty"><i class="fa fa-exclamation-circle"></i>${data.error}</div>`;
                            return;
                        }

                        if (!data.resultados || data.resultados.length === 0) {
                            results.innerHTML = `<div class="state-empty"><i class="fa fa-inbox"></i>Sin movimientos en este período</div>`;
                            return;
                        }

                        let html = '';
                        data.resultados.forEach(r => {
                            const gainClass = r.ganancia >= 0 ? 'gain-pos' : 'gain-neg';
                            html += `
                <div class="branch-block">
                    <p class="branch-title"><i class="fa fa-map-marker"></i> ${r.sucursal}</p>
                    <table class="ficha-table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Unidades despachadas</th>
                                <th>Valor Vendido</th>
                                <th>Ganancia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>${data.desde} <span style="color:#475569">→</span> ${data.hasta}</td>
                                <td><span class="num-big">${r.cantidad.toFixed(2)}</span> <span class="num-sub">unid.</span></td>
                                <td><span class="num-big">$${parseFloat(r.total).toLocaleString('es-VE', {minimumFractionDigits:2})}</span></td>
                                <td><span class="${gainClass}">$${parseFloat(r.ganancia).toLocaleString('es-VE', {minimumFractionDigits:2})}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>`;
                        });

                        results.innerHTML = html;
                    })
                    .catch(() => {
                        btn.classList.remove('loading');
                        btn.innerHTML = '<i class="fa fa-search"></i> Consultar';
                        results.innerHTML = `<div class="state-empty"><i class="fa fa-exclamation-triangle"></i>Error al conectar con el servidor</div>`;
                    });
            }

            document.getElementById('btn-filtrar').addEventListener('click', consultarPeriodo);

            // Auto-load on page open
            consultarPeriodo();
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>