<?php
require_once('includes/requires.php');

if (!isset($_SESSION["validate"]) || $_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
}

$tipo_u = (int)$_SESSION['nivel'];
$bss_id = (int)$_SESSION['bss_id'];

// Verificación de permisos para empleados (nivel 2)
if ($tipo_u === 2) {
    $has_perm = false;
    if (isset($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        foreach ($_SESSION['permisos'] as $p_item) {
            if (is_array($p_item) && in_array('rendimiento_tienda.php', $p_item)) {
                $has_perm = true;
                break;
            } elseif ($p_item === 'rendimiento_tienda.php') {
                $has_perm = true;
                break;
            }
        }
    }

    if (!$has_perm && isset($_SESSION['id'])) {
        $u_id = (int)$_SESSION['id'];
        $stmtP = $conexion->prepare("
            SELECT up.id 
            FROM users_permisos up
            INNER JOIN menu m ON m.id = up.id_item_menu
            WHERE up.id_user = ? AND m.dir = 'rendimiento_tienda.php'
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Rendimiento Tienda Online | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <script src="../vendors/apexcharts/apexcharts.min.js"></script>

    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

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

        .chart-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* KPI Cards row */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        @media (max-width: 1200px) {
            .kpi-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .kpi-row {
                grid-template-columns: 1fr;
            }
        }

        .kpi-card {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 22px 24px 18px;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            overflow: hidden;
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
            background: linear-gradient(90deg, transparent, #2dd4a0, transparent);
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
            margin-bottom: 14px;
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

        .kpi-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(45, 212, 160, 0.1);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(45, 212, 160, 0.15);
            flex-shrink: 0;
        }

        .kpi-icon-circle ion-icon {
            font-size: 22px;
            color: var(--dash-mint);
        }

        .kpi-value {
            font-size: 30px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.1;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .status-card .kpi-icon-circle {
            width: 42px;
            height: 42px;
        }

        .status-card .kpi-icon-circle ion-icon {
            font-size: 20px;
        }

        .status-card .kpi-value {
            font-size: 24px;
        }

        /* Paneles */
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

        .panel-body {
            padding: 18px 22px 22px;
        }

        .panel-body.p-0 {
            padding: 0;
        }

        .section-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
        }

        /* Ranking online */
        .ol-rank-item {
            display: flex;
            align-items: center;
            padding: 12px 22px;
            border-bottom: 1px solid rgba(46, 53, 62, 0.4);
            gap: 12px;
            transition: background 0.15s;
        }

        .ol-rank-item:last-child {
            border-bottom: none;
        }

        .ol-rank-item:hover {
            background: rgba(45, 212, 160, 0.03);
        }

        .ol-rank-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--dash-text-muted);
            flex-shrink: 0;
        }

        .ol-rank-item:nth-child(1) .ol-rank-num {
            background: rgba(245, 180, 91, 0.15);
            color: #f5b45b;
        }

        .ol-rank-item:nth-child(2) .ol-rank-num {
            background: rgba(200, 200, 210, 0.1);
            color: #c8c8d2;
        }

        .ol-rank-item:nth-child(3) .ol-rank-num {
            background: rgba(205, 127, 50, 0.12);
            color: #cd7f32;
        }

        .ol-rank-name {
            flex: 1;
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .ol-rank-val {
            font-size: 13px;
            font-weight: 700;
            color: var(--dash-text);
            white-space: nowrap;
        }

        .rank-empty {
            padding: 30px 22px;
            text-align: center;
            color: var(--dash-text-muted);
            font-size: 13px;
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu; ?>
            <?php echo $topnav; ?>

            <div class="right_col" role="main">
                <!-- Header -->
                <div class="dash-header">
                    <div>
                        <h3>Rendimiento Tienda Online</h3>
                        <p>Métricas de órdenes, ingresos y productos de tu tienda web</p>
                    </div>
                    <div class="chart-controls">
                        <div class="btn-group" id="onlinePeriodoToggle">
                            <button type="button" class="btn btn-sm online-period-btn" data-periodo="dia" style="background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);color:var(--dash-text-muted);border-radius:6px 0 0 6px;font-size:11px;font-weight:600;padding:4px 12px;transition:all 0.2s;">Día</button>
                            <button type="button" class="btn btn-sm online-period-btn" data-periodo="semana" style="background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);color:var(--dash-text-muted);border-radius:0;font-size:11px;font-weight:600;padding:4px 12px;transition:all 0.2s;">Semana</button>
                            <button type="button" class="btn btn-sm online-period-btn active" data-periodo="mes" style="background:var(--dash-mint);border:1px solid var(--dash-mint);color:#fff;border-radius:0 6px 6px 0;font-size:11px;font-weight:600;padding:4px 12px;transition:all 0.2s;">Mes</button>
                        </div>
                    </div>
                </div>

                <!-- KPIs principales -->
                <div class="kpi-row">
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Órdenes</h6>
                                <small>Recibidas en el período</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="bag-check-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_ordenes">0</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Ingresos</h6>
                                <small>Ventas online</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="cash-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_ingreso">$0</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Pendientes</h6>
                                <small>Por procesar</small>
                            </div>
                            <div class="kpi-icon-circle" style="background:rgba(245,180,91,0.12);border-color:rgba(245,180,91,0.15);">
                                <ion-icon name="time-outline" style="color:var(--dash-warning);"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_pendientes_kpi">0</div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Publicados</h6>
                                <small>En tu tienda</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="cube-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_publicados">0</div>
                    </div>
                </div>

                <!-- KPIs de estado -->
                <div class="kpi-row">
                    <div class="kpi-card status-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Pendientes</h6>
                                <small>Órdenes por confirmar</small>
                            </div>
                            <div class="kpi-icon-circle" style="background:rgba(245,180,91,0.12);border-color:rgba(245,180,91,0.15);">
                                <ion-icon name="ellipse" style="color:var(--dash-warning);font-size:10px;"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_pendientes">0</div>
                    </div>

                    <div class="kpi-card status-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Completadas</h6>
                                <small>Órdenes finalizadas</small>
                            </div>
                            <div class="kpi-icon-circle" style="background:rgba(45,212,160,0.12);border-color:rgba(45,212,160,0.15);">
                                <ion-icon name="ellipse" style="color:var(--dash-mint);font-size:10px;"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_completadas">0</div>
                    </div>

                    <div class="kpi-card status-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Delivery</h6>
                                <small>Entregas a domicilio</small>
                            </div>
                            <div class="kpi-icon-circle" style="background:rgba(91,156,245,0.12);border-color:rgba(91,156,245,0.15);">
                                <ion-icon name="ellipse" style="color:var(--dash-info);font-size:10px;"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_delivery">0</div>
                    </div>

                    <div class="kpi-card status-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Retiro</h6>
                                <small>Retiro en tienda</small>
                            </div>
                            <div class="kpi-icon-circle" style="background:rgba(167,139,250,0.12);border-color:rgba(167,139,250,0.15);">
                                <ion-icon name="ellipse" style="color:#a78bfa;font-size:10px;"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="ol_retiro">0</div>
                    </div>
                </div>

                <!-- Chart + Rankings -->
                <div class="row g-4">
                    <div class="col-lg-8 mb-3">
                        <div class="dash-panel">
                            <div class="panel-header">
                                <h6><ion-icon name="bar-chart-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Órdenes últimos 7 días</h6>
                            </div>
                            <div class="panel-body p-0" id="ol_chart_dias" style="min-height:320px;"></div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-3">
                        <div class="dash-panel">
                            <div class="panel-header">
                                <h6><ion-icon name="flame-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Top productos online</h6>
                            </div>
                            <div class="panel-body p-0" id="ol_topProductos" style="max-height:145px;overflow-y:auto;"></div>
                        </div>

                        <div class="dash-panel" style="margin-top:20px;">
                            <div class="panel-header">
                                <h6><ion-icon name="trophy-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Mejores clientes online</h6>
                            </div>
                            <div class="panel-body p-0" id="ol_topClientes" style="max-height:145px;overflow-y:auto;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../build/js/global-loader.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        let olChartDias = null;
        let currentOnlinePeriodo = 'mes';

        function cargarRendimientoOnline(periodo) {
            fetch('db/tienda_rendimiento_api.php?periodo=' + periodo + '&_=' + Date.now())
                .then(r => r.json())
                .then(d => {
                    if (d.error) {
                        console.error(d.error);
                        return;
                    }
                    const k = d.kpis;
                    document.getElementById('ol_ordenes').textContent = k.total_ordenes || 0;
                    document.getElementById('ol_ingreso').textContent = '$' + Number(k.total_ingreso || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    document.getElementById('ol_pendientes_kpi').textContent = k.pendientes || 0;
                    document.getElementById('ol_publicados').textContent = d.productosPublicados || 0;
                    document.getElementById('ol_pendientes').textContent = k.pendientes || 0;
                    document.getElementById('ol_completadas').textContent = k.completadas || 0;
                    document.getElementById('ol_delivery').textContent = k.delivery || 0;
                    document.getElementById('ol_retiro').textContent = k.retiro || 0;

                    // Chart dias
                    const dias = d.chartDias.map(r => {
                        const f = new Date(r.dia);
                        return f.toLocaleDateString('es', {
                            weekday: 'short',
                            day: 'numeric'
                        });
                    });
                    const ordenesData = d.chartDias.map(r => r.ordenes);
                    const ingresoData = d.chartDias.map(r => Number(r.ingreso));

                    if (olChartDias) olChartDias.destroy();
                    olChartDias = new ApexCharts(document.querySelector('#ol_chart_dias'), {
                        series: [{
                                name: 'Órdenes',
                                data: ordenesData
                            },
                            {
                                name: 'Ingreso $',
                                data: ingresoData
                            }
                        ],
                        chart: {
                            type: 'bar',
                            height: 320,
                            background: 'transparent',
                            toolbar: {
                                show: false
                            }
                        },
                        theme: {
                            mode: 'dark'
                        },
                        tooltip: {
                            theme: 'dark'
                        },
                        colors: ['#2dd4a0', '#5b9cf5'],
                        plotOptions: {
                            bar: {
                                borderRadius: 4,
                                columnWidth: '60%'
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            width: 0
                        },
                        grid: {
                            borderColor: 'rgba(255,255,255,0.04)'
                        },
                        xaxis: {
                            categories: dias,
                            labels: {
                                style: {
                                    colors: '#8892a0',
                                    fontSize: '11px'
                                }
                            }
                        },
                        yaxis: [{
                                labels: {
                                    style: {
                                        colors: '#8892a0',
                                        fontSize: '11px'
                                    }
                                }
                            },
                            {
                                opposite: true,
                                labels: {
                                    style: {
                                        colors: '#8892a0',
                                        fontSize: '11px'
                                    },
                                    formatter: v => '$' + v
                                }
                            }
                        ],
                        legend: {
                            position: 'bottom',
                            labels: {
                                colors: '#8892a0'
                            }
                        }
                    });
                    olChartDias.render();

                    // Top productos
                    const tpC = document.getElementById('ol_topProductos');
                    if (d.topProductos.length === 0) {
                        tpC.innerHTML = '<div class="rank-empty">Sin datos</div>';
                    } else {
                        tpC.innerHTML = d.topProductos.map((p, i) =>
                            `<div class="ol-rank-item"><div class="ol-rank-num">${i+1}</div><span class="ol-rank-name">${p.producto}</span><span class="ol-rank-val">${p.cantidad} uds</span></div>`
                        ).join('');
                    }

                    // Top clientes
                    const tcC = document.getElementById('ol_topClientes');
                    if (d.topClientes.length === 0) {
                        tcC.innerHTML = '<div class="rank-empty">Sin datos</div>';
                    } else {
                        tcC.innerHTML = d.topClientes.map((c, i) =>
                            `<div class="ol-rank-item"><div class="ol-rank-num">${i+1}</div><span class="ol-rank-name">${c.nombre}</span><span class="ol-rank-val">$${Number(c.total).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}</span></div>`
                        ).join('');
                    }
                })
                .catch(e => console.error('Error rendimiento online:', e));
        }

        document.getElementById('onlinePeriodoToggle').addEventListener('click', function(e) {
            const btn = e.target.closest('.online-period-btn');
            if (!btn) return;
            const periodo = btn.dataset.periodo;
            if (periodo === currentOnlinePeriodo) return;
            currentOnlinePeriodo = periodo;
            this.querySelectorAll('.online-period-btn').forEach(b => {
                b.style.background = 'rgba(255,255,255,0.06)';
                b.style.borderColor = 'var(--dash-border)';
                b.style.color = 'var(--dash-text-muted)';
            });
            btn.style.background = 'var(--dash-mint)';
            btn.style.borderColor = 'var(--dash-mint)';
            btn.style.color = '#fff';
            cargarRendimientoOnline(periodo);
        });

        cargarRendimientoOnline('mes');
    </script>

    <!-- jQuery -->
    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>
</body>

</html>
