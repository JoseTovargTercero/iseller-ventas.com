<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != 1 && $_SESSION['nivel'] != 2) {
    header('Location: ../../index.php');
    exit;
}

$topnav       = topnav();
$nivelUsuario = $_SESSION['nivel'];
$nombreUsuario = $_SESSION['nombre'];

$semana = date('Y-W');
$mes    = date('Y-m');

// Obtener sucursales (solo nivel 1)
$sucursales = [];
if ($nivelUsuario == 1) {
    $suc_stmt = $conexion->prepare("SELECT id, nombre FROM sucursales WHERE bss_id=? ORDER BY nombre");
    if ($suc_stmt) {
        $suc_stmt->bind_param('i', $bss_id);
        $suc_stmt->execute();
        $suc_result = $suc_stmt->get_result();
        if ($suc_result) {
            while ($row = $suc_result->fetch_assoc()) {
                $sucursales[] = $row;
            }
        }
        $suc_stmt->close();
    }
}

// Obtener categorías del negocio
$cat_stmt = $conexion->prepare("SELECT id, nombre FROM gastos_categorias WHERE bss_id=? AND activo=1 ORDER BY nombre");
if ($cat_stmt) {
    $cat_stmt->bind_param('i', $bss_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $categorias = [];
    if ($cat_result) {
        while ($row = $cat_result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    $cat_stmt->close();
} else {
    $categorias = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Gastos</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .dash-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0;
            letter-spacing: -.3px;
        }

        .dash-header p {
            color: var(--dash-text-muted);
            margin: 2px 0 0;
            font-size: 13px;
        }

        .dash-header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
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
            transition: all .2s ease;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
            cursor: pointer;
        }

        .btn-dash-filter:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
            color: #fff;
        }

        .btn-dash-filter ion-icon {
            font-size: 16px;
        }

        .btn-dash-new {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .2s ease;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
            cursor: pointer;
        }

        .btn-dash-new:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
            color: #fff;
        }

        .btn-dash-new ion-icon {
            font-size: 16px;
        }

        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .kpi-card {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 22px 24px 18px;
            transition: border-color .25s ease, box-shadow .25s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            border-color: rgba(45, 212, 160, .35);
            box-shadow: 0 0 0 1px rgba(45, 212, 160, .08), 0 8px 30px rgba(0, 0, 0, .25);
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
            transition: opacity .3s ease;
        }

        .kpi-card:hover::before {
            opacity: .6;
        }

        .kpi-card.negative::before {
            background: linear-gradient(90deg, transparent, #ef5a6f, transparent);
        }

        .kpi-card.negative:hover {
            border-color: rgba(239, 90, 111, .35);
            box-shadow: 0 0 0 1px rgba(239, 90, 111, .08), 0 8px 30px rgba(0, 0, 0, .25);
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
            letter-spacing: .4px;
        }

        .kpi-info small {
            font-size: 11px;
            color: rgba(136, 146, 160, .6);
        }

        .kpi-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(45, 212, 160, .1);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(45, 212, 160, .15);
            flex-shrink: 0;
        }

        .kpi-icon-circle ion-icon {
            font-size: 22px;
            color: var(--dash-mint);
        }

        .kpi-card.negative .kpi-icon-circle {
            background: rgba(239, 90, 111, .1);
            border-color: rgba(239, 90, 111, .15);
        }

        .kpi-card.negative .kpi-icon-circle ion-icon {
            color: var(--dash-danger);
        }

        .kpi-value {
            font-size: 30px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.1;
            margin-bottom: 6px;
            letter-spacing: -.5px;
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

        .dash-panel {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            overflow: hidden;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px 14px;
            border-bottom: 1px solid var(--dash-border);
        }

        .panel-header h6 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-header h6 ion-icon {
            font-size: 16px;
            color: var(--dash-mint);
        }

        .panel-body {
            padding: 18px 22px;
        }

        .filter-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-bar .filter-label {
            font-size: 12px;
            color: var(--dash-text-muted);
            font-weight: 500;
        }

        .btn-period {
            background: rgba(255, 255, 255, .06);
            border: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            padding: 5px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
        }

        .btn-period:hover {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
        }

        .btn-period.active {
            background: var(--dash-mint);
            border-color: var(--dash-mint);
            color: #fff;
        }

        .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .dash-table thead th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--dash-text-muted);
            border-bottom: 1px solid var(--dash-border);
            background: transparent;
        }

        .dash-table tbody tr {
            transition: background .15s ease;
            border-bottom: 1px solid rgba(46, 53, 62, .4);
        }

        .dash-table tbody tr:last-child {
            border-bottom: none;
        }

        .dash-table tbody tr:hover {
            background: rgba(45, 212, 160, .03);
        }

        .dash-table tbody td {
            padding: 12px 14px;
            color: var(--dash-text);
            vertical-align: middle;
        }

        .badge-activo {
            background: #32d7c0;
            color: #fff;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-anulado {
            background: #ef5a6f;
            color: #fff;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-action {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
            padding: 0;
            font-size: 14px;
            margin-right: 4px;
        }

        .btn-action:last-child {
            margin-right: 0;
        }

        .btn-action:hover {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
            background: rgba(45, 212, 160, .06);
        }

        .btn-action.btn-danger:hover {
            border-color: #ef5a6f;
            color: #ef5a6f;
            background: rgba(239, 90, 111, .06);
        }

        .modal-content {
            background: var(--dash-card) !important;
            border: 1px solid var(--dash-border) !important;
            border-radius: 14px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
        }

        .modal-header {
            border-bottom: 1px solid var(--dash-border);
            padding: 18px 22px 14px;
        }

        .modal-header .close {
            color: var(--dash-text-muted);
            opacity: .7;
            font-size: 24px;
            transition: opacity .15s ease;
        }

        .modal-header .close:hover {
            opacity: 1;
            color: var(--dash-text);
        }

        .modal-title {
            color: var(--dash-text);
            font-size: 16px;
            font-weight: 600;
        }

        .modal-body {
            padding: 18px 22px;
        }

        .modal-footer {
            border-top: 1px solid var(--dash-border);
            padding: 14px 22px 18px;
        }

        .modal .form-control,
        .modal select.form-control {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .modal .form-control:focus,
        .modal select.form-control:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .modal .form-group {
            margin-bottom: 6px;
        }

        .modal .col-form-label,
        .modal label.form-label {
            color: var(--dash-text-muted);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .btn-dash-submit {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 24px;
            border-radius: 8px;
            transition: all .2s ease;
            cursor: pointer;
        }

        .btn-dash-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        }

        .btn-dash-close {
            background: rgba(255, 255, 255, .06);
            border: none;
            color: var(--dash-text-muted);
            font-size: 13px;
            font-weight: 600;
            padding: 9px 24px;
            border-radius: 8px;
            transition: all .2s ease;
            cursor: pointer;
        }

        .btn-dash-close:hover {
            background: rgba(255, 255, 255, .1);
        }

        .swal2-popup {
            background: var(--dash-card) !important;
            border: 1px solid var(--dash-border) !important;
            border-radius: 14px !important;
        }

        .swal2-title {
            color: var(--dash-text) !important;
        }

        .swal2-html-container {
            color: var(--dash-text-muted) !important;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, #2dd4a0, #25b88a) !important;
            border: none !important;
            border-radius: 8px !important;
        }

        .swal2-cancel {
            background: rgba(255, 255, 255, .06) !important;
            border: none !important;
            border-radius: 8px !important;
            color: var(--dash-text-muted) !important;
        }

        .swal2-input,
        .swal2-textarea {
            background: var(--dash-bg) !important;
            border: 1px solid var(--dash-border) !important;
            color: var(--dash-text) !important;
            border-radius: 8px !important;
        }

        #modal-anular-gasto textarea.is-invalid {
            border-color: #ef5a6f !important;
            box-shadow: 0 0 0 2px rgba(239, 90, 111, .15) !important;
        }

        @media (max-width: 991px) {
            .kpi-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575px) {
            .kpi-row {
                grid-template-columns: 1fr;
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

                <div class="dash-header">
                    <div>
                        <h3>Gastos</h3>
                        <p>Consulta y registro de gastos</p>
                    </div>
                    <div class="dash-header-actions">
                        <select id="filterSucursal" onchange="cambiarSucursal()" style="background:var(--dash-bg);border:1px solid var(--dash-border);color:var(--dash-text);border-radius:8px;padding:8px 12px;font-size:12px;display:none;">
                            <option value="0">Todas las sucursales</option>
                        </select>
                        <?php if ($nivelUsuario == 1 && count($sucursales) > 0): ?>
                            <script>
                                (function() {
                                    var sel = document.getElementById('filterSucursal');
                                    var data = <?= json_encode($sucursales) ?>;
                                    data.forEach(function(s) {
                                        var o = document.createElement('option');
                                        o.value = s.id;
                                        o.textContent = s.nombre;
                                        sel.appendChild(o);
                                    });
                                    sel.style.display = '';
                                })();
                            </script>
                        <?php endif; ?>
                        <button class="btn-dash-filter" onclick="mostrarModal()">
                            <ion-icon name="funnel-outline"></ion-icon> Filtros
                        </button>
                        <button class="btn-dash-new" style="background:linear-gradient(135deg,#5b9cf5,#4a8ae0);box-shadow:0 3px 12px rgba(91,156,245,.25);text-decoration:none;" onclick="abrirModalNuevo()">
                            <ion-icon name="add-outline"></ion-icon> Nuevo gasto
                        </button>
                    </div>
                </div>

                <div class="kpi-row">
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Ganancias semana</h6>
                                <small>Ventas - Costo de la semana</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="trending-up-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="gananciaBrutaSemana">$0.00</div>
                        <div class="kpi-metrics">
                            <span class="metric-down">
                                <ion-icon name="arrow-down-outline"></ion-icon>
                                <span id="gastosSemanaCount">0</span> <small>gastos</small>
                            </span>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Ganancias mes</h6>
                                <small>Ventas - Costo del mes</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="calendar-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="gananciaBrutaMes">$0.00</div>
                        <div class="kpi-metrics">
                            <span class="metric-down">
                                <ion-icon name="arrow-down-outline"></ion-icon>
                                <span id="gastosMesCount">0</span> <small>gastos</small>
                            </span>
                        </div>
                    </div>
                    <div class="kpi-card negative">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Beneficio neto semana</h6>
                                <small>Ganancias - Gastos semana</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="wallet-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value"><span id="gananciaNetaSemana">0.00</span>$</div>
                    </div>
                    <div class="kpi-card negative">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Beneficio neto mes</h6>
                                <small>Ganancias - Gastos mes</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="stats-chart-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value"><span id="gananciaNetaMes">0.00</span>$</div>
                    </div>
                </div>

                <div id="proyeccion-box" style="background:rgba(91,156,245,.08);border:1px solid rgba(91,156,245,.2);border-radius:14px;padding:18px 24px;margin-bottom:24px;display:none;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="width:44px;height:44px;border-radius:50%;background:rgba(91,156,245,.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <ion-icon name="calculator-outline" style="font-size:22px;color:#5b9cf5;"></ion-icon>
                        </div>
                        <div>
                            <div style="font-size:12px;font-weight:600;color:var(--dash-text-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;">Proyección gastos recurrentes (mes)</div>
                            <div style="font-size:13px;color:var(--dash-text-muted);">Monto estimado de gastos fijos que se aplicarán este mes</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:24px;">
                        <div style="text-align:right;">
                            <div style="font-size:28px;font-weight:700;color:#5b9cf5;" id="proyeccionMonto">$0.00</div>
                            <div style="font-size:11px;color:var(--dash-text-muted);">Gastos recurrentes</div>
                        </div>
                        <div style="width:1px;height:40px;background:rgba(91,156,245,.2);"></div>
                        <div style="text-align:right;">
                            <div style="font-size:28px;font-weight:700;" id="proyeccionBalance">$0.00</div>
                            <div style="font-size:11px;color:var(--dash-text-muted);" id="proyeccionBalanceLabel">Balance neto vs recurrentes</div>
                        </div>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="panel-header">
                        <h6><ion-icon name="receipt-outline"></ion-icon>Gastos registrados</h6>
                        <div class="filter-bar">
                            <span class="filter-label">Período:</span>
                            <button class="btn-period" onclick="filtroRapido('hoy', this)">Hoy</button>
                            <button class="btn-period" onclick="filtroRapido('semana', this)">Esta semana</button>
                            <button class="btn-period active" onclick="filtroRapido('mes', this)">Este mes</button>
                            <button class="btn-period" onclick="filtroRapido('anio', this)">Este año</button>
                        </div>
                    </div>
                    <div class="panel-body" style="padding:0 16px 16px;">
                        <div class="table-responsive" id="tablaContenidoPagos">
                            <div style="text-align:center;padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Cargando tabla de gastos <ion-icon name="sync-outline" style="animation:spin 1s linear infinite;"></ion-icon></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Filtros -->
    <div class="modal fade" id="modal-filtros" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><ion-icon name="funnel-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);vertical-align:middle;"></ion-icon>Filtrar gastos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Fecha desde</label>
                        <input type="date" id="filterFechaDesde" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha hasta</label>
                        <input type="date" id="filterFechaHasta" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select id="filterCategoria" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select id="filterTipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="FIJO">Fijo</option>
                            <option value="VARIABLE">Variable</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select id="filterEstado" class="form-control">
                            <option value="">Todos</option>
                            <option value="ACTIVO">Activo</option>
                            <option value="ANULADO">Anulado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-dash-close" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn-dash-submit" onclick="aplicarFiltros()">Aplicar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Gasto -->
    <div class="modal fade" id="modal-gasto" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-gasto-title">Nuevo gasto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="form-gasto" autocomplete="off">
                    <input type="hidden" id="gastoId" value="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">Fecha *</label>
                                    <input type="date" id="gastoFecha" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">Categoría</label>
                                    <select id="gastoCategoria" class="form-control">
                                        <option value="">Sin categoría</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php if ($nivelUsuario == 1 && count($sucursales) > 0): ?>
                            <div class="form-group">
                                <label class="form-label">Sucursal *</label>
                                <select id="gastoSucursal" class="form-control">
                                    <?php foreach ($sucursales as $suc): ?>
                                        <option value="<?= $suc['id'] ?>"><?= htmlspecialchars($suc['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label">Concepto *</label>
                            <input type="text" id="gastoConcepto" class="form-control" placeholder="Descripción del gasto" maxlength="255">
                        </div>
                        <input type="hidden" id="gastoTipo" value="VARIABLE">
                        <input type="hidden" id="gastoFrecuencia" value="UNICO">
                        <input type="hidden" id="gastoMoneda" value="USD">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="form-label">Monto *</label>
                                    <input type="number" id="gastoMonto" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label class="form-label">Observación</label>
                                    <input type="text" id="gastoObservacion" class="form-control" maxlength="500">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-dash-close" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-dash-submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Anular Gasto -->
    <div class="modal fade" id="modal-anular-gasto" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" style="border-bottom-color:rgba(239,90,111,.2);">
                    <h5 class="modal-title" style="display:flex;align-items:center;gap:10px;">
                        <span style="width:34px;height:34px;border-radius:50%;background:rgba(239,90,111,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <ion-icon name="ban-outline" style="font-size:18px;color:#ef5a6f;"></ion-icon>
                        </span>
                        Anular gasto
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div style="background:rgba(239,90,111,.08);border:1px solid rgba(239,90,111,.18);border-radius:10px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;">
                        <ion-icon name="warning-outline" style="font-size:20px;color:#ef5a6f;margin-top:1px;flex-shrink:0;"></ion-icon>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--dash-text);margin-bottom:2px;">¿Está seguro de anular este gasto?</div>
                            <div style="font-size:12px;color:var(--dash-text-muted);">El gasto <strong style="color:var(--dash-text);"><span id="anular-gasto-codigo"></span></strong> quedará marcado como anulado y no se podrá revertir.</div>
                        </div>
                    </div>
                    <input type="hidden" id="anular-gasto-id" value="">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" style="display:flex;align-items:center;gap:6px;">
                            <ion-icon name="pencil-outline" style="font-size:13px;color:var(--dash-text-muted);"></ion-icon>
                            Motivo de anulación *
                        </label>
                        <textarea id="anular-gasto-motivo" class="form-control" rows="3" placeholder="Describa el motivo de la anulación..." style="resize:vertical;"></textarea>
                        <div class="invalid-feedback" style="font-size:11px;">El motivo es obligatorio</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-dash-close" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-dash-submit" style="background:linear-gradient(135deg,#ef5a6f,#d94460);box-shadow:0 3px 12px rgba(239,90,111,.25);" onclick="confirmarAnulacion()">
                        <ion-icon name="ban-outline" style="font-size:14px;margin-right:4px;vertical-align:middle;"></ion-icon> Anular gasto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>

    <style>
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        function getSucursalId() {
            const sel = document.getElementById('filterSucursal');
            return sel ? parseInt(sel.value) || 0 : 0;
        }

        function getSucursalData() {
            return {
                id_sucursal: getSucursalId()
            };
        }

        function cambiarSucursal() {
            obtener_registros();
        }

        function abrirModalNuevo() {
            $('#gastoId').val('');
            $('#form-gasto')[0].reset();
            const d = new Date();
            const ds = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            $('#gastoFecha').val(ds);
            $('#modal-gasto-title').text('Nuevo gasto');
            $('#modal-gasto').modal('show');
        }

        function obtener_registros() {
            const data = {
                id_sucursal: getSucursalId()
            };
            $.ajax({
                    url: 'consulta_tablaPagos.php',
                    type: 'POST',
                    dataType: 'html',
                    data: data
                })
                .done(function(resultado) {
                    $('#tablaContenidoPagos').html(resultado);
                });

            actualizarCounts();
        }

        function filtroRapido(periodo, el) {
            document.querySelectorAll('.btn-period').forEach(function(b) {
                b.classList.remove('active');
            });
            el.classList.add('active');

            let data = {
                id_sucursal: getSucursalId()
            };

            if (periodo === 'hoy') {
                const hoy = new Date();
                const hoyStr = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
                data.fecha_desde = hoyStr;
                data.fecha_hasta = hoyStr;
            } else if (periodo === 'semana') {
                const hoy = new Date();
                const hoyStr = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0') + '-' + String(hoy.getDate()).padStart(2, '0');
                const d = new Date(hoy);
                d.setDate(hoy.getDate() - hoy.getDay() + 1);
                data.fecha_desde = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                data.fecha_hasta = hoyStr;
            } else if (periodo === 'mes') {
                const hoy = new Date();
                const yyyy = hoy.getFullYear();
                const mm = hoy.getMonth();
                const ultimoDia = new Date(yyyy, mm + 1, 0).getDate();
                data.fecha_desde = yyyy + '-' + String(mm + 1).padStart(2, '0') + '-01';
                data.fecha_hasta = yyyy + '-' + String(mm + 1).padStart(2, '0') + '-' + ultimoDia;
            } else if (periodo === 'anio') {
                const hoy = new Date();
                data.fecha_desde = hoy.getFullYear() + '-01-01';
                data.fecha_hasta = '9999-12-31';
            }

            $.ajax({
                    url: 'consulta_tablaPagos.php',
                    type: 'POST',
                    dataType: 'html',
                    cache: false,
                    data: data
                })
                .done(function(resultado) {
                    $('#tablaContenidoPagos').html(resultado);
                })
                .fail(function(xhr, status, error) {
                    $('#tablaContenidoPagos').html('<div style="text-align:center;padding:40px 0;color:#ef5a6f;font-size:13px;">Error al cargar: ' + status + '</div>');
                });
        }

        function mostrarModal() {
            $('#modal-filtros').modal('show');
        }

        function aplicarFiltros() {
            let data = {
                id_sucursal: getSucursalId()
            };
            const fd = $('#filterFechaDesde').val();
            const fh = $('#filterFechaHasta').val();
            const cat = $('#filterCategoria').val();
            const tipo = $('#filterTipo').val();
            const estado = $('#filterEstado').val();

            if (fd && fh) {
                data.fecha_desde = fd;
                data.fecha_hasta = fh;
            }
            if (cat) data.categoria_id = cat;
            if (tipo) data.tipo = tipo;
            if (estado) data.estado = estado;

            $.ajax({
                    url: 'consulta_tablaPagos.php',
                    type: 'POST',
                    dataType: 'html',
                    data: data
                })
                .done(function(resultado) {
                    $('#tablaContenidoPagos').html(resultado);
                    $('#modal-filtros').modal('hide');
                });
        }

        function actualizarCounts() {
            const semana = "<?= $semana ?>";
            const mes = "<?= $mes ?>";
            const data = {
                semana: semana,
                mes: mes,
                id_sucursal: getSucursalId()
            };
            let netoMes = 0;

            $.ajax({
                    url: 'consulta_gastosCount.php',
                    type: 'POST',
                    dataType: 'html',
                    data: data
                })
                .done(function(resultado1) {
                    const r = resultado1.split('*');
                    $('#gananciaBrutaSemana').html('$' + r[0]);
                    $('#gananciaBrutaMes').html('$' + r[1]);
                    $('#gastosSemanaCount').html(r[2]);
                    $('#gastosMesCount').html(r[3]);
                    $('#gananciaNetaSemana').html(r[4]);
                    $('#gananciaNetaMes').html(r[5]);
                    netoMes = parseFloat(r[5]) || 0;
                    cargarProyeccion(netoMes);
                });
        }

        function cargarProyeccion(netoMes) {
            $.ajax({
                    url: 'consulta_proyeccionRecurrentes.php',
                    type: 'POST',
                    dataType: 'html',
                    data: {
                        id_sucursal: getSucursalId()
                    }
                })
                .done(function(resp) {
                    const proyeccion = parseFloat(resp) || 0;
                    if (proyeccion > 0) {
                        $('#proyeccionMonto').text('$' + proyeccion.toFixed(2));
                        const balance = netoMes - proyeccion;
                        const balanceEl = $('#proyeccionBalance');
                        const labelEl = $('#proyeccionBalanceLabel');
                        balanceEl.text((balance >= 0 ? '+' : '-') + '$' + Math.abs(balance).toFixed(2));
                        if (balance >= 0) {
                            balanceEl.css('color', '#2dd4a0');
                            labelEl.text('Ya cubres los recurrentes ✓');
                        } else {
                            balanceEl.css('color', '#ef5a6f');
                            labelEl.text('Falta para cubrir recurrentes');
                        }
                        $('#proyeccion-box').css('display', 'flex');
                    } else {
                        $('#proyeccion-box').hide();
                    }
                });
        }

        $('#form-gasto').on('submit', function(e) {
            e.preventDefault();
            if ($('#gastoConcepto').val().trim() === '') {
                Toast.fire({
                    icon: 'error',
                    title: 'Ingrese el concepto'
                });
                return;
            }
            if (!$('#gastoMonto').val() || parseFloat($('#gastoMonto').val()) <= 0) {
                Toast.fire({
                    icon: 'error',
                    title: 'El monto debe ser mayor a 0'
                });
                return;
            }

            $.ajax({
                    url: '../../configurar/aggregarGasto.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        concepto: $('#gastoConcepto').val(),
                        categoria_id: $('#gastoCategoria').val(),
                        tipo: $('#gastoTipo').val(),
                        frecuencia: $('#gastoFrecuencia').val(),
                        moneda: 'USD',
                        monto: $('#gastoMonto').val(),
                        fecha: $('#gastoFecha').val(),
                        observacion: $('#gastoObservacion').val(),
                        id_sucursal: $('#gastoSucursal').length ? $('#gastoSucursal').val() : <?= intval($_SESSION['sucursal'] ?? 0) ?>
                    }
                })
                .done(function(resp) {
                    if (resp.status === 'ok') {
                        Toast.fire({
                            icon: 'success',
                            title: 'Gasto registrado: ' + resp.codigo
                        });
                        $('#modal-gasto').modal('hide');
                        obtener_registros();
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: resp.msg || 'Error al registrar'
                        });
                    }
                })
                .fail(function() {
                    Toast.fire({
                        icon: 'error',
                        title: 'Error de conexión'
                    });
                });
        });

        function verDetalle(el) {
            const d = JSON.parse(el.closest('tr').getAttribute('data-detalle'));
            const estadoBadge = d.estado === 'ACTIVO' ?
                '<span style="background:#32d7c0;color:#fff;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;">Activo</span>' :
                '<span style="background:#ef5a6f;color:#fff;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;">Anulado</span>';
            const tipoBadge = d.tipo === 'Fijo' ?
                '<span style="background:rgba(91,156,245,.15);color:#5b9cf5;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;">Fijo</span>' :
                '<span style="background:rgba(245,180,91,.15);color:#f5b45b;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;">Variable</span>';

            function row(icon, label, value) {
                return '<div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid rgba(46,53,62,.4);">' +
                    '<div style="width:32px;height:32px;border-radius:8px;background:rgba(45,212,160,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
                    '<ion-icon name="' + icon + '" style="font-size:16px;color:var(--dash-mint);"></ion-icon></div>' +
                    '<div style="flex:1;min-width:0;">' +
                    '<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--dash-text-muted);margin-bottom:2px;">' + label + '</div>' +
                    '<div style="font-size:14px;font-weight:500;color:var(--dash-text);">' + value + '</div>' +
                    '</div></div>';
            }

            let html = '<div style="text-align:left;">';

            html += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--dash-border);">' +
                '<div><div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--dash-text-muted);margin-bottom:2px;">Concepto</div>' +
                '<div style="font-size:16px;font-weight:700;color:var(--dash-text);">' + d.concepto + '</div></div>' +
                '<div style="display:flex;gap:8px;">' + tipoBadge + estadoBadge + '</div></div>';

            html += row('calendar-outline', 'Fecha', d.fecha);
            html += row('pricetag-outline', 'Categoría', d.categoria);
            html += row('repeat-outline', 'Frecuencia', d.frecuencia);
            html += row('cash-outline', 'Monto', d.simbolo + ' ' + d.monto + ' <span style="color:var(--dash-text-muted);font-size:12px;">( $ ' + d.monto_usd + ' USD )</span>');
            html += row('person-outline', 'Registrado por', d.usuario);

            if (d.observacion) {
                html += row('chatbubble-outline', 'Observación', d.observacion);
            }

            html += '</div>';

            Swal.fire({
                title: '<span style="display:flex;align-items:center;gap:10px;">' +
                    '<span style="width:36px;height:36px;border-radius:50%;background:rgba(45,212,160,.1);display:flex;align-items:center;justify-content:center;">' +
                    '<ion-icon name="receipt-outline" style="font-size:20px;color:var(--dash-mint);"></ion-icon></span>' +
                    d.codigo + '</span>',
                html: html,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#2dd4a0',
                width: 520,
                customClass: {
                    popup: 'swal-detail-popup'
                }
            });
        }

        function anularGasto(id, codigo) {
            $('#anular-gasto-id').val(id);
            $('#anular-gasto-codigo').text(codigo);
            $('#anular-gasto-motivo').val('');
            $('#anular-gasto-motivo').removeClass('is-invalid');
            $('#modal-anular-gasto').modal('show');
        }

        function confirmarAnulacion() {
            const motivo = $('#anular-gasto-motivo').val().trim();
            if (!motivo) {
                $('#anular-gasto-motivo').addClass('is-invalid');
                return;
            }
            $('#anular-gasto-motivo').removeClass('is-invalid');
            const id = $('#anular-gasto-id').val();

            $.ajax({
                    url: '../../configurar/anular_gasto.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: id,
                        motivo: motivo
                    }
                })
                .done(function(resp) {
                    $('#modal-anular-gasto').modal('hide');
                    if (resp.status === 'ok') {
                        Toast.fire({
                            icon: 'success',
                            title: 'Gasto anulado'
                        });
                        obtener_registros();
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: resp.msg || 'Error al anular'
                        });
                    }
                })
                .fail(function() {
                    $('#modal-anular-gasto').modal('hide');
                    Toast.fire({
                        icon: 'error',
                        title: 'Error de conexión'
                    });
                });
        }

        obtener_registros();
    </script>
</body>

</html>