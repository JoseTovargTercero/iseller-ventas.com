<?php
require_once('includes/requires.php');

if (!isset($_SESSION["validate"]) || $_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
}

$tipo_u = (int) $_SESSION['nivel'];
$bss_id = (int) $_SESSION['bss_id'];
$hoy    = date('Y-m-d');

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

$topnav = topnav();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Ingresos por Línea de Producto | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <script src="../vendors/apexcharts/apexcharts.min.js"></script>

    <style>
        .right_col {
            background: var(--dash-bg) !important;
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .dash-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
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

        .btn-lp {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .2s ease;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
            cursor: pointer;
        }

        .btn-lp:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
            color: #fff;
        }

        /* Filtros */
        .lp-filters {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 22px;
        }

        .lp-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1 1 150px;
            min-width: 130px;
        }

        .lp-field.lp-field-wide {
            flex: 2 1 290px;
        }

        .lp-field label {
            color: var(--dash-text-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .lp-input {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            color: var(--dash-text);
            font-size: 13px;
            height: 36px;
            padding: 8px 10px;
            width: 100%;
            color-scheme: dark;
        }

        .lp-input:focus {
            border-color: var(--dash-mint);
            outline: none;
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .lp-group-row {
            display: flex;
            gap: 6px;
        }

        .lp-group-row select {
            flex: 1;
            min-width: 0;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 8px;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
            font-size: 15px;
        }

        .btn-icon:hover:not(:disabled) {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
        }

        .btn-icon.danger:hover:not(:disabled) {
            border-color: #ef5a6f;
            color: #ef5a6f;
        }

        .btn-icon:disabled {
            opacity: .35;
            cursor: not-allowed;
        }

        .btn-refresh-dash {
            background: rgba(255, 255, 255, .05);
            border: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            width: 38px;
            height: 38px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
        }

        .btn-refresh-dash:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, .4);
            background: rgba(45, 212, 160, .05);
        }

        .btn-refresh-dash.spinning svg {
            animation: lp-spin .8s linear infinite;
        }

        @keyframes lp-spin {
            100% { transform: rotate(360deg); }
        }

        /* Secciones y KPIs */
        .section-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin: 0 0 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .section-label .badge-cat {
            color: var(--dash-mint);
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            background: rgba(45, 212, 160, .08);
            border: 1px solid rgba(45, 212, 160, .2);
            text-transform: none;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .kpi-row-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 26px;
        }

        .kpi-card {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 20px 22px 16px;
            transition: border-color .25s ease, box-shadow .25s ease;
            position: relative;
            overflow: hidden;
            background: transparent;
        }

        .kpi-card:hover {
            border-color: rgba(45, 212, 160, .35);
            box-shadow: 0 0 0 1px rgba(45, 212, 160, .08), 0 8px 30px rgba(0, 0, 0, .25);
        }

        .kpi-info h6 {
            font-size: 12px;
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

        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.15;
            margin: 10px 0;
            letter-spacing: -.5px;
        }

        .kpi-metrics {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 12px;
            padding-top: 10px;
            border-top: 1px solid rgba(46, 53, 62, .5);
            color: var(--dash-text-muted);
        }

        .metric-up {
            color: var(--dash-mint);
            font-weight: 600;
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

        .panel-badge {
            font-size: 11px;
            font-weight: 500;
            color: var(--dash-text-muted);
            background: rgba(255, 255, 255, .04);
            border: 1px solid var(--dash-border);
            padding: 2px 8px;
            border-radius: 4px;
        }

        .dash-panel .panel-body {
            padding: 16px 20px;
        }

        /* Tabla */
        .lp-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lp-table thead th {
            padding: 11px 18px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--dash-text-muted);
            text-align: left;
            border-bottom: 1px solid var(--dash-border);
            white-space: nowrap;
        }

        .lp-table tbody td {
            padding: 12px 18px;
            font-size: 13px;
            color: var(--dash-text);
            border-bottom: 1px solid rgba(46, 53, 62, .5);
        }

        .lp-table tbody tr:last-child td {
            border-bottom: none;
        }

        .lp-table .lp-num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .lp-table th.lp-num {
            text-align: right;
        }

        .lp-pos {
            color: var(--dash-mint);
            font-weight: 600;
        }

        .lp-neg {
            color: #ef5a6f;
            font-weight: 600;
        }

        .lp-empty {
            text-align: center;
            color: var(--dash-text-muted);
            padding: 34px 18px !important;
        }

        /* Modal de línea */
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

        .modal .form-control {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            height: auto;
        }

        .modal .form-control:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .modal .col-form-label {
            color: var(--dash-text-muted);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding-top: 8px;
            padding-bottom: 4px;
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
            cursor: pointer;
        }

        .lp-prod-list {
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            max-height: 320px;
            overflow-y: auto;
        }

        .lp-prod-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            margin: 0;
            border-bottom: 1px solid rgba(46, 53, 62, .5);
            font-size: 13px;
            font-weight: 400;
            cursor: pointer;
        }

        .lp-prod-item:last-child {
            border-bottom: none;
        }

        .lp-prod-item:hover {
            background: rgba(45, 212, 160, .04);
        }

        .lp-prod-item input {
            accent-color: #2dd4a0;
            width: 15px;
            height: 15px;
            flex: 0 0 15px;
            margin: 0;
        }

        .lp-prod-item .lp-prod-name {
            flex: 1;
            min-width: 0;
            color: var(--dash-text);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .lp-prod-item small {
            color: var(--dash-text-muted);
            font-size: 11px;
            white-space: nowrap;
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

        @media (max-width: 1200px) {
            .kpi-row-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .kpi-row-4 {
                grid-template-columns: 1fr;
            }

            .right_col {
                padding: 16px 14px !important;
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
                <div class="dash-header">
                    <div>
                        <h3>Ingresos por Línea de Producto</h3>
                        <p>Consulta cuánto generó un grupo de productos y qué parte representa del total vendido.</p>
                    </div>
                    <?php if ($tipo_u === 1): ?>
                        <div class="dash-controls">
                            <button type="button" class="btn-lp" id="btnNuevaLinea">+ Nueva línea</button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="lp-filters">
                    <div class="lp-field lp-field-wide">
                        <label for="selLinea">Línea de producto</label>
                        <div class="lp-group-row">
                            <select id="selLinea" class="lp-input">
                                <option value="">Seleccione una línea…</option>
                            </select>
                            <?php if ($tipo_u === 1): ?>
                                <button type="button" class="btn-icon" id="btnEditarLinea" title="Editar línea" disabled>
                                    <ion-icon name="create-outline"></ion-icon>
                                </button>
                                <button type="button" class="btn-icon danger" id="btnEliminarLinea" title="Eliminar línea" disabled>
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="lp-field">
                        <label for="fechaDesde">Desde</label>
                        <input type="date" id="fechaDesde" class="lp-input" value="<?= $hoy ?>" max="<?= $hoy ?>">
                    </div>

                    <div class="lp-field">
                        <label for="fechaHasta">Hasta</label>
                        <input type="date" id="fechaHasta" class="lp-input" value="<?= $hoy ?>" max="<?= $hoy ?>">
                    </div>

                    <?php if ($tipo_u === 1 && count($sucursales) > 1): ?>
                        <div class="lp-field">
                            <label for="selSucursal">Sucursal</label>
                            <select id="selSucursal" class="lp-input">
                                <option value="0">Todas las sucursales</option>
                                <?php foreach ($sucursales as $suc): ?>
                                    <option value="<?= (int) $suc['id'] ?>">
                                        <?= htmlspecialchars($suc['nombre'], ENT_QUOTES, 'UTF-8') ?><?= $suc['principal'] == 1 ? ' (Principal)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="button" class="btn-refresh-dash" id="btnRefresh" title="Actualizar datos">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67" />
                        </svg>
                    </button>
                </div>

                <div class="section-label">
                    <span>Resumen del período</span>
                    <span class="badge-cat" id="badgeLinea">Sin línea</span>
                </div>

                <div class="kpi-row-4">
                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h6>Ingresos de la línea</h6>
                            <small>Ventas del período seleccionado</small>
                        </div>
                        <div class="kpi-value" id="kpiIngreso">$0.00</div>
                        <div class="kpi-metrics">
                            <span>Total del negocio: <span id="kpiTotalPeriodo" style="color:var(--dash-text);font-weight:600;">$0.00</span></span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h6>Participación</h6>
                            <small>Parte del dinero generado</small>
                        </div>
                        <div class="kpi-value" id="kpiPorcentaje">0%</div>
                        <div class="kpi-metrics">
                            <span class="metric-up">Del total vendido en el período</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h6>Unidades vendidas</h6>
                            <small>Productos de la línea</small>
                        </div>
                        <div class="kpi-value" id="kpiUnidades">0</div>
                        <div class="kpi-metrics">
                            <span id="kpiProductosVendidos">0 productos con ventas</span>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="kpi-info">
                            <h6>Costo de lo vendido</h6>
                            <small>Base para abonar al proveedor</small>
                        </div>
                        <div class="kpi-value" id="kpiCosto">$0.00</div>
                        <div class="kpi-metrics">
                            <span>Utilidad: <span id="kpiUtilidad" class="metric-up">$0.00</span></span>
                            <span id="kpiTickets">0 tickets</span>
                        </div>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="panel-header">
                        <h6>Ingresos por día</h6>
                        <span class="panel-badge" id="badgePeriodo">Hoy</span>
                    </div>
                    <div class="panel-body">
                        <div id="chartIngresos" style="min-height: 300px;"></div>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="panel-header">
                        <h6>Productos de la línea</h6>
                        <span class="panel-badge" id="badgeProductos">0 productos</span>
                    </div>
                    <div class="panel-body p-0" style="overflow-x:auto;">
                        <table class="lp-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="lp-num">Unidades</th>
                                    <th class="lp-num">Ingreso</th>
                                    <th class="lp-num">% de la línea</th>
                                    <th class="lp-num">Costo</th>
                                    <th class="lp-num">Utilidad</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyProductos">
                                <tr>
                                    <td colspan="6" class="lp-empty">Seleccione una línea para consultar sus ingresos.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($tipo_u === 1): ?>
        <div class="modal fade" id="modalLinea" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLineaTitle">Nueva línea de producto</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formLinea" autocomplete="off">
                        <input type="hidden" id="lineaId" value="">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="col-form-label">Nombre *</label>
                                <input class="form-control" id="lineaNombre" placeholder="Ej: Grupo Ganado" required maxlength="150">
                            </div>
                            <div class="form-group">
                                <label class="col-form-label">Descripción (opcional)</label>
                                <input class="form-control" id="lineaDescripcion" placeholder="Ej: Productos del proveedor de res" maxlength="255">
                            </div>
                            <div class="form-group">
                                <label class="col-form-label">
                                    Productos * <span id="contadorProductos" style="text-transform:none;letter-spacing:0;">0 seleccionados</span>
                                </label>
                                <input class="form-control" id="buscarProducto" placeholder="Buscar por nombre, código o categoría…" style="margin-bottom:8px;">
                                <div class="lp-prod-list" id="listaProductos">
                                    <div class="lp-empty">Cargando productos…</div>
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
    <?php endif; ?>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>

    <script>
        const API = 'db/ingresos_linea_producto_api.php';
        const PUEDE_EDITAR = <?= $tipo_u === 1 ? 'true' : 'false' ?>;
        const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });

        const $id = (id) => document.getElementById(id);
        let lineas = [];
        let catalogo = [];
        let chart = null;

        const esc = (valor) => String(valor ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));

        const fmtMoney = (valor) => '$' + (Number(valor) || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        const fmtNum = (valor) => (Number(valor) || 0).toLocaleString('en-US', {
            maximumFractionDigits: 2
        });

        const fechaCorta = (iso) => iso ? iso.slice(8, 10) + '/' + iso.slice(5, 7) + '/' + iso.slice(0, 4) : '';

        function lineaSeleccionada() {
            const sel = $id('selLinea');
            return lineas.find((l) => String(l.id) === String(sel.value)) || null;
        }

        function actualizarBotones() {
            const btnEditar = $id('btnEditarLinea');
            const btnEliminar = $id('btnEliminarLinea');
            const hay = !!lineaSeleccionada();
            if (btnEditar) btnEditar.disabled = !hay || !PUEDE_EDITAR;
            if (btnEliminar) btnEliminar.disabled = !hay || !PUEDE_EDITAR;
        }

        /* ---------- Catálogo de productos ---------- */
        async function cargarCatalogo() {
            try {
                const resp = await fetch(API + '?accion=catalogo');
                const data = await resp.json();
                catalogo = data.data || [];
                renderCatalogo();
            } catch (e) {
                console.error('Error al cargar catálogo:', e);
            }
        }

        function renderCatalogo() {
            const cont = $id('listaProductos');
            if (!cont) return;

            if (!catalogo.length) {
                cont.innerHTML = '<div class="lp-empty">No hay productos activos.</div>';
                return;
            }

            cont.innerHTML = catalogo.map((p) => {
                const buscar = [p.nombre, p.codigo, p.categoria_nombre].filter(Boolean).join(' ').toLowerCase();
                return '<label class="lp-prod-item" data-buscar="' + esc(buscar) + '">'
                    + '<input type="checkbox" value="' + p.id + '">'
                    + '<span class="lp-prod-name">' + esc(p.nombre) + '</span>'
                    + (p.categoria_nombre ? '<small>' + esc(p.categoria_nombre) + '</small>' : '')
                    + '</label>';
            }).join('');
        }

        function filtrarProductos() {
            const cont = $id('listaProductos');
            if (!cont) return;
            const q = $id('buscarProducto').value.trim().toLowerCase();
            cont.querySelectorAll('.lp-prod-item').forEach((el) => {
                el.style.display = !q || (el.dataset.buscar || '').includes(q) ? '' : 'none';
            });
        }

        function actualizarContador() {
            const cont = $id('listaProductos');
            if (!cont) return;
            const total = cont.querySelectorAll('input[type="checkbox"]:checked').length;
            $id('contadorProductos').textContent = total + (total === 1 ? ' seleccionado' : ' seleccionados');
        }

        /* ---------- Líneas de producto ---------- */
        async function cargarLineas(preferida) {
            const sel = $id('selLinea');
            const actual = preferida != null ? String(preferida) : sel.value;

            try {
                const data = await fetch(API + '?accion=listar').then((r) => r.json());
                lineas = data.data || [];
            } catch (e) {
                console.error('Error al cargar líneas:', e);
                lineas = [];
            }

            sel.innerHTML = '<option value="">Seleccione una línea…</option>' + lineas.map((l) =>
                '<option value="' + l.id + '">' + esc(l.nombre) + ' (' + l.total_productos + ')</option>'
            ).join('');

            if (actual && lineas.some((l) => String(l.id) === actual)) {
                sel.value = actual;
            } else if (lineas.length) {
                sel.value = String(lineas[0].id);
            }

            actualizarBotones();
        }

        function resetResumen() {
            $id('badgeLinea').textContent = 'Sin línea';
            $id('badgeProductos').textContent = '0 productos';
            $id('kpiIngreso').textContent = '$0.00';
            $id('kpiTotalPeriodo').textContent = '$0.00';
            $id('kpiPorcentaje').textContent = '0%';
            $id('kpiUnidades').textContent = '0';
            $id('kpiProductosVendidos').textContent = '0 productos con ventas';
            $id('kpiCosto').textContent = '$0.00';
            $id('kpiUtilidad').textContent = '$0.00';
            $id('kpiTickets').textContent = '0 tickets';
            $id('tbodyProductos').innerHTML = '<tr><td colspan="6" class="lp-empty">Seleccione una línea para consultar sus ingresos.</td></tr>';
            if (chart) {
                chart.updateOptions({ xaxis: { categories: [] } });
                chart.updateSeries([{ name: 'Ingresos', data: [] }]);
            }
        }

        function actualizarGrafico(grafico) {
            if (!chart) return;
            const labels = (grafico.labels || []).map(fechaCorta);
            chart.updateOptions({ xaxis: { categories: labels } });
            chart.updateSeries([{ name: 'Ingresos', data: grafico.valores || [] }]);
        }

        function renderProductos(productos) {
            const tbody = $id('tbodyProductos');
            if (!productos || !productos.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="lp-empty">Sin ventas de esta línea en el período seleccionado.</td></tr>';
                return;
            }

            tbody.innerHTML = productos.map((p) => {
                const utilidad = Number(p.utilidad) || 0;
                return '<tr>'
                    + '<td>' + esc(p.nombre) + '</td>'
                    + '<td class="lp-num">' + fmtNum(p.unidades) + '</td>'
                    + '<td class="lp-num">' + fmtMoney(p.ingreso) + '</td>'
                    + '<td class="lp-num">' + (Number(p.porcentaje) || 0).toFixed(1) + '%</td>'
                    + '<td class="lp-num">' + fmtMoney(p.costo) + '</td>'
                    + '<td class="lp-num ' + (utilidad >= 0 ? 'lp-pos' : 'lp-neg') + '">' + fmtMoney(utilidad) + '</td>'
                    + '</tr>';
            }).join('');
        }

        async function cargarResumen() {
            const linea = lineaSeleccionada();
            const btnRef = $id('btnRefresh');

            if (!linea) {
                resetResumen();
                return;
            }

            const desde = $id('fechaDesde').value;
            const hasta = $id('fechaHasta').value;
            if (!desde || !hasta) {
                Toast.fire({ icon: 'error', title: 'Seleccione el período' });
                return;
            }
            if (desde > hasta) {
                Toast.fire({ icon: 'error', title: 'La fecha inicial no puede ser mayor que la final' });
                return;
            }

            const params = new URLSearchParams({
                accion: 'resumen',
                linea_id: linea.id,
                desde: desde,
                hasta: hasta
            });

            const selSuc = $id('selSucursal');
            if (selSuc && selSuc.value && selSuc.value !== '0') {
                params.append('sucursal', selSuc.value);
            }

            if (btnRef) btnRef.classList.add('spinning');

            try {
                const data = await fetch(API + '?' + params.toString()).then((r) => r.json());
                if (!data.success) {
                    Toast.fire({ icon: 'error', title: data.msg || data.error || 'No se pudo consultar la línea' });
                    return;
                }

                const k = data.kpis;
                $id('badgeLinea').textContent = data.linea.nombre;
                $id('badgePeriodo').textContent = desde === hasta
                    ? fechaCorta(desde)
                    : fechaCorta(desde) + ' → ' + fechaCorta(hasta);

                $id('kpiIngreso').textContent = fmtMoney(k.ingreso);
                $id('kpiTotalPeriodo').textContent = fmtMoney(k.total_periodo);
                $id('kpiPorcentaje').textContent = (Number(k.porcentaje) || 0).toFixed(1) + '%';
                $id('kpiUnidades').textContent = fmtNum(k.unidades);
                $id('kpiProductosVendidos').textContent = data.productos.length + (data.productos.length === 1 ? ' producto con ventas' : ' productos con ventas');
                $id('kpiCosto').textContent = fmtMoney(k.costo);
                $id('kpiUtilidad').textContent = fmtMoney(k.utilidad);
                $id('kpiTickets').textContent = fmtNum(k.tickets) + (Number(k.tickets) === 1 ? ' ticket' : ' tickets');
                $id('badgeProductos').textContent = linea.total_productos + (linea.total_productos === 1 ? ' producto en la línea' : ' productos en la línea');

                actualizarGrafico(data.grafico || {});
                renderProductos(data.productos || []);
            } catch (e) {
                console.error('Error al consultar la línea:', e);
                Toast.fire({ icon: 'error', title: 'Error de conexión' });
            } finally {
                if (btnRef) btnRef.classList.remove('spinning');
            }
        }

        /* ---------- Modal crear / editar ---------- */
        function abrirModal(linea) {
            if (!PUEDE_EDITAR) return;

            $id('lineaId').value = linea ? linea.id : '';
            $id('lineaNombre').value = linea ? linea.nombre : '';
            $id('lineaDescripcion').value = linea ? (linea.descripcion || '') : '';
            $id('modalLineaTitle').textContent = linea ? 'Editar línea de producto' : 'Nueva línea de producto';
            $id('buscarProducto').value = '';

            const cont = $id('listaProductos');
            const seleccionados = new Set((linea ? linea.productos : []).map(Number));
            cont.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
                cb.checked = seleccionados.has(Number(cb.value));
            });
            filtrarProductos();
            actualizarContador();

            $('#modalLinea').modal('show');
            $('#modalLinea').on('shown.bs.modal', function () {
                $id('lineaNombre').focus();
            });
        }

        /* ---------- Gráfico ---------- */
        function initGrafico() {
            if (!window.ApexCharts) return;

            chart = new ApexCharts($id('chartIngresos'), {
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: { show: false },
                    background: 'transparent',
                    fontFamily: 'inherit'
                },
                series: [{ name: 'Ingresos', data: [] }],
                colors: ['#2dd4a0'],
                plotOptions: {
                    bar: { columnWidth: '55%', borderRadius: 4 }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: [],
                    labels: {
                        style: { colors: '#8892a0', fontSize: '11px' }
                    },
                    axisBorder: { color: 'rgba(255,255,255,0.08)' },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#8892a0', fontSize: '11px' },
                        formatter: (val) => '$' + Number(val).toLocaleString('en-US', { maximumFractionDigits: 0 })
                    }
                },
                grid: {
                    borderColor: 'rgba(255,255,255,0.05)',
                    strokeDashArray: 3,
                    xaxis: { lines: { show: false } }
                },
                tooltip: {
                    theme: 'dark',
                    y: { formatter: (val) => fmtMoney(val) }
                }
            });
            chart.render();
        }

        /* ---------- Eventos ---------- */
        $id('selLinea').addEventListener('change', function () {
            actualizarBotones();
            cargarResumen();
        });

        $id('fechaDesde').addEventListener('change', cargarResumen);
        $id('fechaHasta').addEventListener('change', cargarResumen);

        const selSucursal = $id('selSucursal');
        if (selSucursal) selSucursal.addEventListener('change', cargarResumen);

        $id('btnRefresh').addEventListener('click', async function () {
            await cargarLineas();
            await cargarResumen();
        });

        if (PUEDE_EDITAR) {
            $id('btnNuevaLinea').addEventListener('click', () => abrirModal(null));

            $id('btnEditarLinea').addEventListener('click', () => {
                const linea = lineaSeleccionada();
                if (linea) abrirModal(linea);
            });

            $id('btnEliminarLinea').addEventListener('click', () => {
                const linea = lineaSeleccionada();
                if (!linea) return;

                Swal.fire({
                    title: 'Eliminar línea',
                    text: 'Se eliminará la línea "' + linea.nombre + '" y su configuración de productos. Las ventas no se modifican.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({ url: API, type: 'POST', dataType: 'json', data: { accion: 'eliminar', id: linea.id } })
                        .done(async function (resp) {
                            Toast.fire({ icon: resp.status === 'ok' ? 'success' : 'error', title: resp.msg });
                            if (resp.status === 'ok') {
                                $id('selLinea').value = '';
                                await cargarLineas();
                                await cargarResumen();
                            }
                        })
                        .fail(function () { Toast.fire({ icon: 'error', title: 'Error de conexión' }); });
                });
            });

            $id('buscarProducto').addEventListener('input', filtrarProductos);
            $id('listaProductos').addEventListener('change', actualizarContador);

            $('#formLinea').on('submit', function (e) {
                e.preventDefault();

                const id = $id('lineaId').value;
                const nombre = $id('lineaNombre').value.trim();
                const descripcion = $id('lineaDescripcion').value.trim();
                const productos = Array.from($id('listaProductos').querySelectorAll('input[type="checkbox"]:checked')).map((cb) => cb.value);

                if (!nombre) {
                    Toast.fire({ icon: 'error', title: 'Ingrese un nombre para la línea' });
                    return;
                }
                if (!productos.length) {
                    Toast.fire({ icon: 'error', title: 'Seleccione al menos un producto' });
                    return;
                }

                const data = {
                    accion: id ? 'editar' : 'crear',
                    nombre: nombre,
                    descripcion: descripcion,
                    productos: productos.join(',')
                };
                if (id) data.id = id;

                $.ajax({ url: API, type: 'POST', dataType: 'json', data: data })
                    .done(async function (resp) {
                        if (resp.status !== 'ok') {
                            Toast.fire({ icon: 'error', title: resp.msg || 'No se pudo guardar la línea' });
                            return;
                        }
                        $('#modalLinea').modal('hide');
                        Toast.fire({ icon: 'success', title: resp.msg });
                        await cargarLineas(resp.id);
                        await cargarResumen();
                    })
                    .fail(function () { Toast.fire({ icon: 'error', title: 'Error de conexión' }); });
            });
        }

        (async function init() {
            await Promise.all([cargarCatalogo(), cargarLineas()]);
            initGrafico();
            if ($id('selLinea').value) {
                await cargarResumen();
            } else {
                resetResumen();
            }
        })();
    </script>
</body>

</html>
