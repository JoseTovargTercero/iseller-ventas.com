<?php
require_once('includes/requires.php');



/////////////////////////// CONTADOR //////////////////////////////


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {

    $topnav = topnav();

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
    $tipo_u =  $_SESSION['nivel'];



    $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";

    if ($tipo_u == 2) {
        $id_sucursal = $_SESSION['sucursal'];

        // Solo para los usuarios tipo 2
        $query .= " AND id='$id_sucursal'";
    }

    $query .= "  ORDER BY principal DESC";

    $stmt = mysqli_prepare($conexion, $query);
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sucursales = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sucursales[] = $row;
        }
    }
    $stmt->close();




    $stmt = mysqli_prepare($conexion, "SELECT id, nombre, id_sucursal FROM `usuarios` WHERE bss_id = ?");
    $stmt->bind_param('s', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    $stmt->close();




?>

    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Inicio </title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>

                <style>
                    .right_col {
                        background: var(--dash-bg);
                        min-height: 100vh;
                        padding: 24px 28px !important;
                    }

                    .dash-header {
                        margin-bottom: 28px;
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
                        margin: 2px 0 0;
                        font-size: 13px;
                    }

                    .dash-header .last-updated {
                        font-size: 12px;
                        color: var(--dash-text-muted);
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    }

                    .dash-header .last-updated ion-icon {
                        font-size: 14px;
                    }

                    .btn-dash-filter {
                        background: linear-gradient(135deg, #2dd4a0, #25b88a);
                        border: none;
                        color: #fff;
                        font-size: 13px;
                        font-weight: 600;
                        padding: 8px 18px;
                        border-radius: 8px;
                        display: inline-flex;
                        align-items: center;
                        gap: 7px;
                        transition: all 0.2s ease;
                        height: min-content;
                        box-shadow: 0 3px 12px rgba(45, 212, 160, 0.25);
                    }

                    .btn-dash-filter:hover {
                        transform: translateY(-1px);
                        box-shadow: 0 6px 20px rgba(45, 212, 160, 0.35);
                        color: #fff;
                    }

                    .btn-dash-filter ion-icon {
                        font-size: 16px;
                    }

                    /* KPI Cards row */
                    .kpi-row {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 20px;
                        margin-bottom: 28px;
                    }

                    .kpi-card {
                        background: var(--dash-card);
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

                    .kpi-metrics {
                        display: flex;
                        gap: 18px;
                        font-size: 12px;
                    }

                    .kpi-metrics .metric-up {
                        color: var(--dash-mint);
                        display: flex;
                        align-items: center;
                        gap: 3px;
                    }

                    .kpi-metrics .metric-down {
                        color: var(--dash-danger);
                        display: flex;
                        align-items: center;
                        gap: 3px;
                    }

                    .kpi-sparkline {
                        margin-top: 8px;
                        height: 36px;
                    }

                    /* Panel cards (branch info + chart) */
                    .dash-panel {
                        background: var(--dash-card);
                        border: 1px solid var(--dash-border);
                        border-radius: 14px;
                        overflow: hidden;
                    }

                    .dash-panel .panel-header {
                        padding: 18px 22px 14px;
                        border-bottom: 1px solid var(--dash-border);
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                    }

                    .dash-panel .panel-header h6 {
                        font-size: 14px;
                        font-weight: 600;
                        color: var(--dash-text);
                        margin: 0;
                    }

                    .dash-panel .panel-body {
                        padding: 6px 0;
                    }

                    /* Branch info list items */
                    .info-item {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 13px 22px;
                        border-bottom: 1px solid rgba(46, 53, 62, 0.6);
                        transition: background 0.15s ease;
                    }

                    .info-item:last-child {
                        border-bottom: none;
                    }

                    .info-item:hover {
                        background: rgba(45, 212, 160, 0.03);
                    }

                    .info-item-left {
                        display: flex;
                        align-items: center;
                        gap: 14px;
                    }

                    .info-icon {
                        width: 38px;
                        height: 38px;
                        border-radius: 10px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background: rgba(45, 212, 160, 0.08);
                        flex-shrink: 0;
                    }

                    .info-icon ion-icon {
                        font-size: 18px;
                        color: var(--dash-mint);
                    }

                    .info-text h6 {
                        font-size: 13px;
                        font-weight: 600;
                        color: var(--dash-text);
                        margin: 0;
                        line-height: 1.2;
                    }

                    .info-text small {
                        font-size: 11px;
                        color: var(--dash-text-muted);
                    }

                    .info-value {
                        font-size: 16px;
                        font-weight: 700;
                        color: var(--dash-text);
                    }

                    /* Chart controls */
                    .chart-controls {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }

                    .chart-controls .date-badge {
                        font-size: 12px;
                        color: var(--dash-text-muted);
                        background: rgba(255, 255, 255, 0.04);
                        padding: 4px 12px;
                        border-radius: 6px;
                        border: 1px solid var(--dash-border);
                    }

                    .chart-controls .btn-refresh {
                        width: 32px;
                        height: 32px;
                        border-radius: 8px;
                        border: 1px solid var(--dash-border);
                        background: transparent;
                        color: var(--dash-text-muted);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    }

                    .chart-controls .btn-refresh:hover {
                        border-color: var(--dash-mint);
                        color: var(--dash-mint);
                    }

                    .chart-controls .btn-refresh ion-icon {
                        font-size: 16px;
                    }

                    /* ─── Top clientes ranking ─── */
                    .rank-item {
                        display: flex;
                        align-items: center;
                        padding: 14px 22px;
                        border-bottom: 1px solid rgba(46, 53, 62, 0.5);
                        transition: background 0.15s ease;
                        gap: 14px;
                    }

                    .rank-item:last-child {
                        border-bottom: none;
                    }

                    .rank-item:hover {
                        background: rgba(45, 212, 160, 0.03);
                    }

                    .rank-badge {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 13px;
                        font-weight: 700;
                        flex-shrink: 0;
                        background: rgba(255, 255, 255, 0.05);
                        color: var(--dash-text-muted);
                    }

                    .rank-badge.gold {
                        background: rgba(245, 180, 91, 0.15);
                        color: #f5b45b;
                    }

                    .rank-badge.silver {
                        background: rgba(200, 200, 210, 0.1);
                        color: #c8c8d2;
                    }

                    .rank-badge.bronze {
                        background: rgba(205, 127, 50, 0.12);
                        color: #cd7f32;
                    }

                    .rank-info {
                        flex: 1;
                        min-width: 0;
                    }

                    .rank-info h6 {
                        font-size: 13px;
                        font-weight: 600;
                        color: var(--dash-text);
                        margin: 0;
                        line-height: 1.3;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .rank-info small {
                        font-size: 11px;
                        color: var(--dash-text-muted);
                    }

                    .rank-total {
                        font-size: 16px;
                        font-weight: 700;
                        color: var(--dash-text);
                        white-space: nowrap;
                    }

                    .rank-empty {
                        padding: 50px 22px;
                        text-align: center;
                        color: var(--dash-text-muted);
                        font-size: 13px;
                    }
                </style>
                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->

                <!-- page content -->
                <div class='right_col'>




                    <div class="d-flex justify-content-between dash-header">
                        <div>
                            <h3>Dashboard</h3>
                            <p>Resumen y estadísticas</p>
                            <div class="last-updated">
                                <ion-icon name="time-outline"></ion-icon>
                                <span id="lastUpdatedLabel">Actualizado ahora</span>
                            </div>
                        </div>
                        <?php if ($_SESSION["nivel"] == 1): ?>
                            <div style="align-self: anchor-center;">
                                <button type="button" class="btn-dash-filter" data-toggle="modal" data-target="#exampleModalCenter">
                                    <ion-icon name="funnel-outline"></ion-icon>
                                    Aplicar Filtro
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>




                    <div class='kpi-row'>
                        <div class="kpi-card">
                            <div class="kpi-top">
                                <div class="kpi-info">
                                    <h6>Ventas del día</h6>
                                    <small>Ventas realizadas hoy</small>
                                </div>
                                <div class="kpi-icon-circle">
                                    <ion-icon name="trending-up-outline"></ion-icon>
                                </div>
                            </div>
                            <div class="kpi-value" id="venta_dia">$0</div>
                            <div class="kpi-metrics">
                                <span class="metric-up">
                                    <ion-icon name="arrow-up-outline"></ion-icon>
                                    <span id="ganancias_dia">0</span> <small>Margen</small>
                                </span>
                                <span class="metric-down">
                                    <ion-icon name="arrow-down-outline"></ion-icon>
                                    <span id="gastos_dia">0</span> <small>Gastos</small>
                                </span>
                            </div>
                            <div class="kpi-sparkline" id="sparklineDia"></div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-top">
                                <div class="kpi-info">
                                    <h6>Ventas de la semana</h6>
                                    <small>Ventas de los últimos 7 días</small>
                                </div>
                                <div class="kpi-icon-circle">
                                    <ion-icon name="calendar-outline"></ion-icon>
                                </div>
                            </div>
                            <div class="kpi-value" id="venta_semana">$0</div>
                            <div class="kpi-metrics">
                                <span class="metric-up">
                                    <ion-icon name="arrow-up-outline"></ion-icon>
                                    <span id="ganancias_semana">0</span> <small>Margen</small>
                                </span>
                                <span class="metric-down">
                                    <ion-icon name="arrow-down-outline"></ion-icon>
                                    <span id="gastos_semana">0</span> <small>Gastos</small>
                                </span>
                            </div>
                            <div class="kpi-sparkline" id="sparklineSemana"></div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-top">
                                <div class="kpi-info">
                                    <h6>Ventas del mes</h6>
                                    <small>Ventas del mes actual</small>
                                </div>
                                <div class="kpi-icon-circle">
                                    <ion-icon name="stats-chart-outline"></ion-icon>
                                </div>
                            </div>
                            <div class="kpi-value" id="venta_mes">$0</div>
                            <div class="kpi-metrics">
                                <span class="metric-up">
                                    <ion-icon name="arrow-up-outline"></ion-icon>
                                    <span id="ganancias_mes">0</span> <small>Margen</small>
                                </span>
                                <span class="metric-down">
                                    <ion-icon name="arrow-down-outline"></ion-icon>
                                    <span id="gastos_mes">0</span> <small>Gastos</small>
                                </span>
                            </div>
                            <div class="kpi-sparkline" id="sparklineMes"></div>
                        </div>
                    </div>

                    <?php if ($_SESSION["nivel"] == 1): ?>

                        <!-- Modal -->
                        <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <div class="modal-content" style="background:var(--dash-card) !important;border:1px solid var(--dash-border);color:var(--dash-text);border-radius:14px;">
                                    <div class="modal-header" style="border-bottom:1px solid var(--dash-border);padding:16px 22px;">
                                        <h5 class="modal-title" style="color:var(--dash-text);font-weight:600;font-size:16px;" id="exampleModalLongTitle">Aplicar filtro</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:var(--dash-text-muted);font-size:24px;">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body" style="padding:20px 22px;">

                                        <div class="mb-3">
                                            <label for="sucursal" class="form-label" style="color:var(--dash-text-muted);font-size:13px;font-weight:500;">Sucursal</label>
                                            <select id="sucursal" class="me-2" style="background:var(--dash-bg);border:1px solid var(--dash-border);color:var(--dash-text);border-radius:8px;padding:8px 12px;font-size:13px;width:100%;">
                                                <?php if (count($sucursales) > 1): ?>
                                                    <option value="todas">-- Seleccione --</option>
                                                <?php endif; ?>

                                                <?php foreach ($sucursales as $row): ?>
                                                    <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($row['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="usuario" class="form-label" style="color:var(--dash-text-muted);font-size:13px;font-weight:500;">Usuario</label>
                                            <select id="usuario" class="me-2" style="background:var(--dash-bg);border:1px solid var(--dash-border);color:var(--dash-text);border-radius:8px;padding:8px 12px;font-size:13px;width:100%;">
                                                <option value="todos">-- Seleccione --</option>
                                            </select>
                                        </div>

                                    </div>
                                    <div class="modal-footer text-end" style="border-top:1px solid var(--dash-border);padding:14px 22px;">
                                        <button type="button" class="btn btn-secondary" style="background:var(--dash-border);border:none;color:var(--dash-text-muted);border-radius:8px;padding:7px 18px;font-size:13px;" data-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>



                    <script>
                        document.getElementById('sucursal').addEventListener('change', function() {
                            const sucursalId = this.value;
                            const usuarioSelect = document.getElementById('usuario');
                            usuarioSelect.innerHTML = '<option value="todos">-- Seleccione --</option>'; // Limpiar opciones anteriores

                            if (sucursalId === 'todas') {
                                return;
                            }

                            // Filtrar usuarios por sucursal
                            const usuariosFiltrados = <?php echo json_encode($usuarios); ?>.filter(usuario => usuario.id_sucursal == sucursalId);

                            if (usuariosFiltrados.length > 0) {
                                usuariosFiltrados.forEach(usuario => {
                                    const option = document.createElement('option');
                                    option.value = usuario.id;
                                    option.textContent = usuario.nombre;
                                    usuarioSelect.appendChild(option);
                                });
                            } else {
                                usuarioSelect.innerHTML = '<option value="">No hay usuarios disponibles</option>';
                            }
                        });
                    </script>


                    <div class='row g-4'>

                        <div class="col-lg-6 mb-3">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h6><ion-icon name="storefront-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Información de la sucursal</h6>
                                </div>
                                <div class="panel-body" id="informacion_interes" style="min-height:380px;">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h6><ion-icon name="bar-chart-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Ventas de la semana</h6>
                                    <div class="chart-controls">
                                        <span class="date-badge">
                                            <ion-icon name="calendar-outline" style="font-size:12px;margin-right:4px;"></ion-icon>
                                            Last 7 Days
                                        </span>
                                        <button class="btn-refresh" onclick="refreshChart()" title="Actualizar">
                                            <ion-icon name="refresh-outline"></ion-icon>
                                        </button>
                                    </div>
                                </div>
                                <div class="panel-body p-0" id="apex_chart_1" style="min-height:380px;">
                                </div>
                            </div>
                        </div>

                        <div class='col-lg-12 hide'>
                            <div class='x_panel tile '>
                                <div class='x_title' style="border-bottom: none">
                                    <h5 style="font-weight: 400;">Ventas de las ultimas semanas</h5>
                                </div>
                                <div class='x_content' style="margin-top: -20px;">
                                    <div id="chartdiv"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h6><ion-icon name="cash-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Créditos por cliente</h6>
                                    <div class="chart-controls">
                                        <span class="date-badge">
                                            <ion-icon name="alert-circle-outline" style="font-size:12px;margin-right:4px;"></ion-icon>
                                            Monto total
                                        </span>
                                    </div>
                                </div>
                                <div class="panel-body p-0" id="apex_chart_creditos" style="min-height:340px;">
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h6><ion-icon name="trophy-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Top compradores</h6>
                                    <div class="chart-controls">
                                        <div class="btn-group btn-group-toggle" role="group" id="periodoToggle">
                                            <button type="button" class="btn btn-sm period-btn" data-periodo="semana" style="background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);color:var(--dash-text-muted);border-radius:6px 0 0 6px;font-size:11px;font-weight:600;padding:4px 12px;transition:all 0.2s;">Semana</button>
                                            <button type="button" class="btn btn-sm period-btn active" data-periodo="mes" style="background:var(--dash-mint);border:1px solid var(--dash-mint);color:#fff;border-radius:0 6px 6px 0;font-size:11px;font-weight:600;padding:4px 12px;transition:all 0.2s;">Mes</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-body p-0" id="topClientesContainer" style="min-height:340px;max-height:380px;overflow-y:auto;">
                                    <div style="padding:30px 22px;text-align:center;color:var(--dash-text-muted);font-size:13px;">Cargando...</div>
                                </div>
                            </div>
                        </div>


                        <div class="col-lg-4 mb-3">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h6><ion-icon name="pie-chart-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Ventas por sucursal</h6>
                                    <div class="chart-controls">
                                        <div class="btn-group btn-group-toggle" role="group" id="periodoPieToggle">
                                            <button type="button" class="btn btn-sm period-pie-btn" data-periodo="dia" style="background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);color:var(--dash-text-muted);border-radius:6px 0 0 6px;font-size:11px;font-weight:600;padding:4px 10px;transition:all 0.2s;">Día</button>
                                            <button type="button" class="btn btn-sm period-pie-btn" data-periodo="semana" style="background:rgba(255,255,255,0.06);border:1px solid var(--dash-border);color:var(--dash-text-muted);border-radius:0;font-size:11px;font-weight:600;padding:4px 10px;transition:all 0.2s;">Semana</button>
                                            <button type="button" class="btn btn-sm period-pie-btn active" data-periodo="mes" style="background:var(--dash-mint);border:1px solid var(--dash-mint);color:#fff;border-radius:0 6px 6px 0;font-size:11px;font-weight:600;padding:4px 10px;transition:all 0.2s;">Mes</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="panel-body p-0" id="apex_chart_ventas_sucursal" style="min-height:340px;">
                                </div>

                                <div class="panel-body p-0" id="resumenSucursalContainer">
                                    <div style="padding:30px 22px;text-align:center;color:var(--dash-text-muted);font-size:13px;">Cargando...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h6><ion-icon name="flame-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Top 5 productos más vendidos</h6>
                                    <div class="chart-controls">
                                        <span class="date-badge">
                                            <ion-icon name="cube-outline" style="font-size:12px;margin-right:4px;"></ion-icon>
                                            Volumen e ingresos
                                        </span>
                                    </div>
                                </div>
                                <div class="panel-body p-0" id="topProductosContainer" style="min-height:320px;max-height:380px;overflow-y:auto;">
                                    <div style="padding:30px 22px;text-align:center;color:var(--dash-text-muted);font-size:13px;">Cargando...</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12  mb-3">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h6><ion-icon name="time-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Ventas promedio por hora</h6>
                                    <div class="chart-controls">
                                        <span class="date-badge">
                                            <ion-icon name="trending-up-outline" style="font-size:12px;margin-right:4px;"></ion-icon>
                                            Promedio últimos 7 días
                                        </span>
                                    </div>
                                </div>
                                <div class="panel-body p-0" id="apex_chart_horas" style="min-height:320px;">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- /footer content -->
            </div>
        </div>

        <script src="../build/js/global-loader.js"></script>

        <script>
            // Mapeo de iconos para indicadores
            const iconMap = {
                creditosHoy: 'card-outline',
                despachadosHoy: 'cube-outline',
                ventasMesDescontado: 'receipt-outline',
                almacenProductos: 'business-outline',
                valorStockSinGanancia: 'cash-outline',
                gananciasEsperadas: 'trending-up-outline'
            };

            // Indicadores
            const indicadores = [{
                    id: "creditosHoy",
                    titulo: "Créditos",
                    subtitulo: "Créditos otorgados hoy.",
                    prefijo: "$"
                },
                {
                    id: "despachadosHoy",
                    titulo: "Despachos",
                    subtitulo: "Productos despachados hoy.",
                    prefijo: ""
                }, {
                    id: "ventasMesDescontado",
                    titulo: "Descontado",
                    subtitulo: "Dinero descontado del mes.",
                    prefijo: "$"
                },
                {
                    id: "almacenProductos",
                    titulo: "Almacén",
                    subtitulo: "Productos en el almacén.",
                    prefijo: ""
                },
                {
                    id: "valorStockSinGanancia",
                    titulo: "Valor del stock",
                    subtitulo: "Valor base sin ganancias",
                    prefijo: "$"
                },
                {
                    id: "gananciasEsperadas",
                    titulo: "Ganancias",
                    subtitulo: "Ganancias esperadas",
                    prefijo: "$"
                }
            ];




            // ─── Sparkline helper ───
            function crearSparkline(elId, data) {
                const el = document.getElementById(elId);
                if (!el) return;
                el.innerHTML = '';
                const opts = {
                    series: [{
                        data: data.length ? data : [0]
                    }],
                    chart: {
                        type: 'line',
                        height: 36,
                        width: '100%',
                        sparkline: {
                            enabled: true
                        },
                        background: 'transparent',
                        animations: {
                            enabled: false
                        }
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 1.8,
                        colors: ['#2dd4a0']
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 0,
                            opacityFrom: 0.3,
                            opacityTo: 0
                        }
                    },
                    markers: {
                        size: 0
                    },
                    tooltip: {
                        enabled: false
                    },
                    grid: {
                        show: false
                    },
                    yaxis: {
                        show: false
                    },
                    xaxis: {
                        show: false
                    }
                };
                const chart = new ApexCharts(el, opts);
                chart.render();
                return chart;
            }

            // ─── Weekly Chart (main) ───
            var options = {
                series: [{
                    name: "Ventas",
                    data: []
                }],
                chart: {
                    height: 340,
                    type: 'line',
                    background: 'transparent',
                    zoom: {
                        enabled: false
                    },
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
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2.5,
                    colors: ['#2dd4a0']
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 0,
                        opacityFrom: 0.35,
                        opacityTo: 0.02,
                        colorStops: [{
                            offset: 0,
                            color: '#2dd4a0',
                            opacity: 0.35
                        }, {
                            offset: 100,
                            color: '#2dd4a0',
                            opacity: 0.02
                        }]
                    }
                },
                markers: {
                    size: 4,
                    colors: ['#2dd4a0'],
                    strokeColors: '#1a1e24',
                    strokeWidth: 2
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.04)',
                    row: {
                        opacity: 0.3
                    }
                },
                xaxis: {
                    categories: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        }
                    }
                }
            };

            var apex_chart = new ApexCharts(document.querySelector("#apex_chart_1"), options);
            apex_chart.render();

            // ─── Creditos por cliente bar chart ───
            var creditosOptions = {
                series: [{
                    name: "Monto total",
                    data: []
                }],
                chart: {
                    height: 300,
                    type: 'bar',
                    background: 'transparent',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: false
                    }
                },
                theme: {
                    mode: 'dark'
                },
                tooltip: {
                    theme: 'dark'
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 0
                },
                colors: ['#2dd4a0'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '70%',
                        distributed: false
                    }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.04)',
                    row: {
                        opacity: 0.3
                    }
                },
                xaxis: {
                    categories: [],
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        },
                        formatter: function(v) {
                            return '$' + v.toFixed(2);
                        }
                    }
                }
            };

            var apex_creditos = new ApexCharts(document.querySelector("#apex_chart_creditos"), creditosOptions);
            apex_creditos.render();

            // ─── Ventas por sucursal pie chart ───
            var sucursalPieOptions = {
                series: [],
                chart: {
                    height: 300,
                    type: 'pie',
                    background: 'transparent',
                    toolbar: {
                        show: false
                    }
                },
                theme: {
                    mode: 'dark'
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(v) {
                            return '$' + Number(v).toLocaleString('en-US', {
                                minimumFractionDigits: 2
                            });
                        }
                    }
                },
                labels: [],
                colors: ['#2dd4a0', '#5b9cf5', '#f5b45b', '#ef5a6f', '#a78bfa', '#f472b6', '#34d399'],
                dataLabels: {
                    style: {
                        colors: ['#e8edf2'],
                        fontSize: '12px',
                        fontWeight: 600
                    },
                    dropShadow: {
                        enabled: false
                    }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        colors: '#8892a0'
                    },
                    fontSize: '12px',
                    itemMargin: {
                        horizontal: 12
                    }
                },
                stroke: {
                    width: 0
                },
                plotOptions: {
                    pie: {
                        expandOnClick: true,
                        donut: {
                            size: '55%'
                        }
                    }
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        chart: {
                            height: 260
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            var apex_ventas_sucursal = new ApexCharts(document.querySelector("#apex_chart_ventas_sucursal"), sucursalPieOptions);
            apex_ventas_sucursal.render();

            // ─── Ventas promedio por hora ───
            var horasLabels = Array.from({
                length: 24
            }, (_, i) => String(i).padStart(2, '0') + ':00');
            var horasOptions = {
                series: [{
                    name: 'Promedio ventas',
                    data: []
                }],
                chart: {
                    height: 280,
                    type: 'bar',
                    background: 'transparent',
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: false
                    }
                },
                theme: {
                    mode: 'dark'
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(v) {
                            return '$' + Number(v).toLocaleString('en-US', {
                                minimumFractionDigits: 2
                            });
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 0
                },
                colors: ['#2dd4a0'],
                plotOptions: {
                    bar: {
                        borderRadius: 2,
                        columnWidth: '75%',
                        distributed: false
                    }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.04)',
                    row: {
                        opacity: 0.3
                    }
                },
                xaxis: {
                    categories: horasLabels,
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '10px'
                        },
                        rotate: -45
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        },
                        formatter: function(v) {
                            return '$' + v.toFixed(2);
                        }
                    }
                },
                annotations: {
                    yaxis: [{
                        y: 0,
                        strokeDashArray: 0,
                        borderColor: 'rgba(255,255,255,0.06)'
                    }]
                }
            };

            var apex_horas = new ApexCharts(document.querySelector("#apex_chart_horas"), horasOptions);
            apex_horas.render();

            function renderResumenSucursal(data) {
                const container = document.getElementById('resumenSucursalContainer');
                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="rank-empty">Sin datos para este período</div>';
                    return;
                }
                const total = data.reduce((sum, s) => sum + s.total, 0);
                let html = '';
                data.forEach((item, i) => {
                    const pct = total > 0 ? ((item.total / total) * 100).toFixed(1) : 0;
                    const icons = ['storefront-outline', 'business-outline', 'cash-outline', 'card-outline', 'cube-outline'];
                    html += `
                        <div class="info-item">
                            <div class="info-item-left">
                                <div class="info-icon">
                                    <ion-icon name="${icons[i % icons.length]}"></ion-icon>
                                </div>
                                <div class="info-text">
                                    <h6>${item.sucursal}</h6>
                                    <small>${pct}% del total</small>
                                </div>
                            </div>
                            <div class="info-value">$${Number(item.total).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }

            // ─── Top clientes ranking ───
            let currentPeriodo = 'mes';
            let currentPeriodoPie = 'mes';

            function renderTopProductos(data) {
                const container = document.getElementById('topProductosContainer');
                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="rank-empty">Sin datos</div>';
                    return;
                }
                let html = '';
                data.forEach((item, i) => {
                    const badgeClass = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
                    const icon = i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : (i + 1);
                    html += `
                        <div class="rank-item">
                            <div class="rank-badge ${badgeClass}" style="width:28px;height:28px;font-size:11px;">${icon}</div>
                            <div class="rank-info">
                                <h6>${item.producto}</h6>
                                <small>${item.cantidad} vendidos</small>
                            </div>
                            <div class="rank-total" style="font-size:14px;">$${Number(item.ingreso).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }

            function renderTopClientes(data) {
                const container = document.getElementById('topClientesContainer');
                if (!data || data.length === 0) {
                    container.innerHTML = '<div class="rank-empty">Sin datos para este período</div>';
                    return;
                }
                let html = '';
                data.forEach((item, i) => {
                    const badgeClass = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : '';
                    const icon = i === 0 ? '🥇' : i === 1 ? '🥈' : i === 2 ? '🥉' : (i + 1);
                    html += `
                        <div class="rank-item">
                            <div class="rank-badge ${badgeClass}">${icon}</div>
                            <div class="rank-info">
                                <h6>${item.nombre || item.cedula}</h6>
                                <small>${item.cedula || ''}</small>
                            </div>
                            <div class="rank-total">$${Number(item.total).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }

            // Period toggle for top clientes ranking
            document.getElementById('periodoToggle').addEventListener('click', function(e) {
                const btn = e.target.closest('.period-btn');
                if (!btn) return;
                const periodo = btn.dataset.periodo;
                if (periodo === currentPeriodo) return;
                currentPeriodo = periodo;
                this.querySelectorAll('.period-btn').forEach(b => {
                    b.style.background = 'rgba(255,255,255,0.06)';
                    b.style.borderColor = 'var(--dash-border)';
                    b.style.color = 'var(--dash-text-muted)';
                });
                btn.style.background = 'var(--dash-mint)';
                btn.style.borderColor = 'var(--dash-mint)';
                btn.style.color = '#fff';
                refreshChart();
            });

            // Period toggle for ventas por sucursal pie chart
            document.getElementById('periodoPieToggle').addEventListener('click', function(e) {
                const btn = e.target.closest('.period-pie-btn');
                if (!btn) return;
                const periodo = btn.dataset.periodo;
                if (periodo === currentPeriodoPie) return;
                currentPeriodoPie = periodo;
                this.querySelectorAll('.period-pie-btn').forEach(b => {
                    b.style.background = 'rgba(255,255,255,0.06)';
                    b.style.borderColor = 'var(--dash-border)';
                    b.style.color = 'var(--dash-text-muted)';
                });
                btn.style.background = 'var(--dash-mint)';
                btn.style.borderColor = 'var(--dash-mint)';
                btn.style.color = '#fff';
                refreshChart();
            });

            // ─── Sparkline instances ───
            let sparklineDia = null;
            let sparklineSemana = null;
            let sparklineMes = null;

            function cargar_tabla(sucursal = null, usuario = null, periodo = 'mes', periodoPie = 'mes') {
                fetch('../../configurar/index_back.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            sucursal: sucursal,
                            usuario: usuario,
                            periodo: periodo,
                            periodoPie: periodoPie
                        })
                    })
                    .then(response => response.json())
                    .then(json => {
                        if (!json || json.length === 0) return;

                        // Update KPI values
                        document.getElementById('venta_dia').innerText = `$${json.totalVentasDiarias}`;
                        document.getElementById('venta_semana').innerText = `$${json.totalVentasSemana}`;
                        document.getElementById('venta_mes').innerText = `$${json.totalVentasMes}`;

                        document.getElementById('ganancias_dia').innerText = `$${json.gananciasDia}`;
                        document.getElementById('ganancias_semana').innerText = `$${json.gananciasSemana}`;
                        document.getElementById('ganancias_mes').innerText = `$${json.gananciasMes}`;
                        document.getElementById('gastos_dia').innerText = json.gastosDia || '0';
                        document.getElementById('gastos_semana').innerText = `$${json.gastosSemana}`;
                        document.getElementById('gastos_mes').innerText = `$${json.gastosMes}`;

                        // Build weekly data for main chart
                        const ventas_semana = json.ventasSemana;
                        const weeklyData = [
                            recortarADosDecimales(ventas_semana.Lunes),
                            recortarADosDecimales(ventas_semana.Martes),
                            recortarADosDecimales(ventas_semana.Miercoles),
                            recortarADosDecimales(ventas_semana.Jueves),
                            recortarADosDecimales(ventas_semana.Viernes),
                            recortarADosDecimales(ventas_semana.Sabado),
                            recortarADosDecimales(ventas_semana.Domingo)
                        ];
                        apex_chart.updateSeries([{
                            name: 'Ventas',
                            data: weeklyData
                        }]);

                        // Sparkline data from daily records (if available)
                        if (json.ventasDiarias && Array.isArray(json.ventasDiarias)) {
                            if (sparklineDia) sparklineDia.destroy();
                            sparklineDia = crearSparkline('sparklineDia', json.ventasDiarias.slice(-7));
                        }
                        if (json.ventasSemanas) {
                            const semArr = Object.values(json.ventasSemanas).map(v => v[0] || 0);
                            if (sparklineSemana) sparklineSemana.destroy();
                            sparklineSemana = crearSparkline('sparklineSemana', semArr.slice(-7));
                            const mesArr = Object.values(json.ventasSemanas).map(v => v[0] || 0);
                            if (sparklineMes) sparklineMes.destroy();
                            sparklineMes = crearSparkline('sparklineMes', mesArr.slice(-12));
                        }

                        // Update creditos bar chart
                        if (json.creditosPorCliente && Array.isArray(json.creditosPorCliente)) {
                            const clientes = json.creditosPorCliente.map(c => c.cliente);
                            const montos = json.creditosPorCliente.map(c => c.total);
                            apex_creditos.updateOptions({
                                xaxis: {
                                    categories: clientes
                                }
                            });
                            apex_creditos.updateSeries([{
                                name: 'Monto total',
                                data: montos
                            }]);
                        }

                        // Update ventas por sucursal pie chart
                        if (json.ventasPorSucursal && Array.isArray(json.ventasPorSucursal)) {
                            const labels = json.ventasPorSucursal.map(s => s.sucursal);
                            const data = json.ventasPorSucursal.map(s => s.total);
                            apex_ventas_sucursal.updateOptions({
                                labels: labels
                            });
                            apex_ventas_sucursal.updateSeries(data);
                            renderResumenSucursal(json.ventasPorSucursal);
                        } else {
                            apex_ventas_sucursal.updateSeries([]);
                            document.getElementById('resumenSucursalContainer').innerHTML = '<div class="rank-empty">Sin datos</div>';
                        }

                        // Update ventas por hora chart
                        if (json.ventasPorHora && Array.isArray(json.ventasPorHora)) {
                            apex_horas.updateSeries([{
                                name: 'Promedio ventas',
                                data: json.ventasPorHora
                            }]);
                        }

                        renderTopProductos(json.topProductos);
                        renderTopClientes(json.topClientes);
                        renderInformacionInteres(json);
                        document.getElementById('lastUpdatedLabel').innerText =
                            'Última actualización: ' + new Date().toLocaleTimeString('es-VE', {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                    })
                    .catch(error => console.error("Error en la solicitud:", error));
            }

            function refreshChart() {
                cargar_tabla(
                    document.getElementById('sucursal')?.value || null,
                    document.getElementById('usuario')?.value || null,
                    currentPeriodo,
                    currentPeriodoPie
                );
            }

            // Render dinámico con iconos vectoriales
            function renderInformacionInteres(json) {
                const contenedor = document.getElementById('informacion_interes');
                contenedor.innerHTML = '';

                indicadores.forEach(item => {
                    if (json.hasOwnProperty(item.id)) {
                        const valor = json[item.id];
                        const icon = iconMap[item.id] || 'ellipse-outline';
                        const bloque = `
                            <div class="info-item">
                                <div class="info-item-left">
                                    <div class="info-icon">
                                        <ion-icon name="${icon}"></ion-icon>
                                    </div>
                                    <div class="info-text">
                                        <h6>${item.titulo}</h6>
                                        <small>${item.subtitulo}</small>
                                    </div>
                                </div>
                                <div class="info-value">${item.prefijo}${valor}</div>
                            </div>
                        `;
                        contenedor.insertAdjacentHTML('beforeend', bloque);
                    }
                });
            }

            // Llamada inicial
            cargar_tabla(null, null, 'mes', 'mes');

            document.getElementById('sucursal').addEventListener('change', function() {
                cargar_tabla(this.value, null, currentPeriodo, currentPeriodoPie);
            });
            document.getElementById('usuario').addEventListener('change', function() {
                const sucursal = document.getElementById('sucursal').value;
                cargar_tabla(sucursal, this.value, currentPeriodo, currentPeriodoPie);
            });
        </script>



        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../build/js/custom.js"></script>

    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
    ?>