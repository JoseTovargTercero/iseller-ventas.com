<?php
require_once('includes/requires.php');

$topnav  = topnav();
$bss_id  = $_SESSION['bss_id'];

$stmtT = $conexion->prepare("SELECT slug, tiene_tienda_virtual FROM negocio WHERE id = ?");
$stmtT->bind_param("i", $bss_id);
$stmtT->execute();
$tienda = $stmtT->get_result()->fetch_assoc();
$stmtT->close();

if (!$tienda || !$tienda['tiene_tienda_virtual']) {
    header("Location: tienda_onboarding.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Tienda Virtual — Planes de Entrega</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css">
    <style>
        .planes-page {
            padding: 24px;
        }

        .planes-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .planes-header h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .planes-header h2 i {
            color: var(--dash-mint);
            font-size: 1.2rem;
        }

        .planes-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Stats row */
        .planes-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .stat-info .stat-num {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1;
        }

        .stat-info .stat-label {
            font-size: 0.72rem;
            color: var(--dash-text-muted);
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Active Plans Panel */
        .planes-panel {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .planes-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--dash-border);
            flex-wrap: wrap;
        }

        .planes-panel-header h3 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .planes-panel-header h3 i {
            color: var(--dash-mint);
        }

        /* Plans table */
        #tablaPlanes {
            width: 100%;
            border-collapse: collapse;
        }

        #tablaPlanes thead th {
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
            white-space: nowrap;
        }

        #tablaPlanes tbody td {
            padding: 14px 16px;
            color: var(--dash-text);
            font-size: 0.85rem;
            border-bottom: 1px solid var(--dash-border);
            vertical-align: middle;
        }

        #tablaPlanes tbody tr:hover {
            background: rgba(45, 212, 160, 0.03);
        }

        #tablaPlanes tbody tr:last-child td {
            border-bottom: none;
        }

        .plan-td-name {
            font-weight: 600;
            color: var(--dash-text);
        }

        .plan-td-date {
            font-size: 0.76rem;
            color: var(--dash-text-muted);
            margin-top: 2px;
        }

        .plan-progress-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 160px;
        }

        .plan-progress-wrap .plan-progress-track {
            flex: 1;
        }

        .plan-progress-label {
            font-size: 0.76rem;
            color: var(--dash-text-muted);
            white-space: nowrap;
        }

        .plan-code-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 11px;
            border-radius: 8px;
            background: rgba(45, 212, 160, 0.12);
            border: 1px solid rgba(45, 212, 160, 0.3);
            color: var(--dash-mint);
            font-family: monospace;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .plan-progress-track {
            width: 100%;
            height: 7px;
            background: var(--dash-border);
            border-radius: 99px;
            overflow: hidden;
        }

        .plan-progress-fill {
            height: 100%;
            background: var(--dash-mint);
            border-radius: 99px;
            transition: width 0.3s ease;
        }

        .planes-empty {
            padding: 56px 24px;
            text-align: center;
            color: var(--dash-text-muted);
        }

        .planes-empty i {
            font-size: 2.4rem;
            color: var(--dash-mint);
            opacity: 0.7;
        }

        /* Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 0.72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-status::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-1 {
            background: rgba(250, 204, 21, 0.1);
            color: #facc15;
        }

        .status-1::before {
            background: #facc15;
        }

        .status-2 {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .status-2::before {
            background: #3b82f6;
        }

        .status-3 {
            background: rgba(168, 85, 247, 0.1);
            color: #a855f7;
        }

        .status-3::before {
            background: #a855f7;
        }

        .status-4 {
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .status-4::before {
            background: var(--dash-mint);
        }

        .status-5 {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .status-5::before {
            background: #ef4444;
        }

        /* Modal & Stepper */
        .modal-content {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            color: var(--dash-text);
        }

        .modal-header {
            border-bottom: 1px solid var(--dash-border);
            padding: 20px 24px;
        }

        .modal-header .modal-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--dash-text);
        }

        .modal-order-meta {
            font-size: 0.75rem;
            color: var(--dash-text-muted);
            margin-top: 4px;
        }

        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.5;
        }

        .modal-body {
            padding: 0;
        }

        .status-steps {
            display: flex;
            align-items: flex-start;
        }

        .status-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            position: relative;
            text-align: center;
        }

        .status-step::before {
            content: '';
            position: absolute;
            top: 13px;
            left: -50%;
            width: 100%;
            height: 2px;
            background: var(--dash-border);
            z-index: 0;
        }

        .status-step:first-child::before {
            display: none;
        }

        .status-step.done::before,
        .status-step.active::before {
            background: var(--dash-mint);
        }

        .status-step .step-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--dash-bg);
            border: 2px solid var(--dash-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--dash-text-muted);
            z-index: 1;
        }

        .status-step.done .step-dot {
            background: var(--dash-mint);
            border-color: var(--dash-mint);
            color: #000;
        }

        .status-step.active .step-dot {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
            box-shadow: 0 0 0 4px rgba(45, 212, 160, 0.15);
        }

        .status-step .step-label {
            font-size: 0.68rem;
            color: var(--dash-text-muted);
            font-weight: 500;
        }

        .status-step.active .step-label {
            color: var(--dash-mint);
            font-weight: 600;
        }

        .status-step.done .step-label {
            color: var(--dash-text);
        }

        .plan-stepper-wrap {
            padding: 18px 24px;
            border-bottom: 1px solid var(--dash-border);
            background: rgba(255, 255, 255, 0.01);
        }

        .plan-step-pane {
            display: none;
            padding: 24px;
        }

        .plan-step-pane.active {
            display: block;
        }

        .plan-form-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--dash-text-muted);
            margin-bottom: 8px;
            display: block;
        }

        .plan-form-input {
            width: 100%;
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 0.88rem;
            transition: all 0.2s ease;
        }

        .plan-form-input:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.12);
            outline: none;
        }

        .plan-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .plan-orders-list {
            max-height: 320px;
            overflow-y: auto;
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            background: var(--dash-bg);
        }

        .plan-order-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--dash-border);
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .plan-order-item:last-child {
            border-bottom: none;
        }

        .plan-order-item:hover {
            background: rgba(45, 212, 160, 0.04);
        }

        .plan-order-item.selected {
            background: rgba(45, 212, 160, 0.08);
        }

        .plan-order-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 0;
        }

        .plan-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #2dd4a0;
            margin-top: 2px;
            cursor: pointer;
            flex-shrink: 0;
        }

        .plan-order-title {
            font-size: 0.86rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .plan-order-sub {
            font-size: 0.76rem;
            color: var(--dash-text-muted);
            margin-top: 3px;
        }

        .plan-gps-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 99px;
            font-weight: 600;
        }

        .plan-gps-tag.has-gps {
            background: rgba(45, 212, 160, 0.12);
            color: var(--dash-mint);
        }

        .plan-gps-tag.no-gps {
            background: rgba(250, 204, 21, 0.12);
            color: #facc15;
        }

        .plan-summary-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(45, 212, 160, 0.05);
            border: 1px solid rgba(45, 212, 160, 0.15);
            border-radius: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .plan-stops-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            margin-top: 14px;
            background: var(--dash-bg);
        }

        .plan-stop-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--dash-border);
            font-size: 0.82rem;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .plan-stop-row:last-child {
            border-bottom: none;
        }

        .plan-stop-row:hover {
            background: rgba(45, 212, 160, 0.05);
        }

        .plan-stop-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--dash-mint);
            color: #000;
            font-weight: 700;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .plan-leaflet-pin {
            background: #2dd4a0;
            color: #000;
            border: 2px solid #181b22;
            border-radius: 50%;
            width: 28px !important;
            height: 28px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.45);
        }

        .map-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 200px;
            border: 1px dashed var(--dash-border);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.02);
        }

        @media (max-width: 768px) {
            .planes-stats {
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
                <div class="planes-page">


                    <div class="planes-stats">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-map"></i></div>
                            <div class="stat-info">
                                <div class="stat-num" id="stat-planes-activos">0</div>
                                <div class="stat-label">Planes activos</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-truck"></i></div>
                            <div class="stat-info">
                                <div class="stat-num" id="stat-pedidos-pendientes">0</div>
                                <div class="stat-label">Pedidos por entregar</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                            <div class="stat-info">
                                <div class="stat-num" id="stat-pedidos-entregados">0</div>
                                <div class="stat-label">Entregas completadas (en curso)</div>
                            </div>
                        </div>
                    </div>

                    <div class="planes-panel">
                        <div class="planes-panel-header">
                            <h3>
                                <i class="bi bi-pin-map-fill"></i>
                                Lista de Planes Activos
                                <span class="badge-status status-4" id="badgeTotalPlanesActivos">0</span>
                            </h3>
                            <button class="btn btn-sm btn-success" onclick="abrirModalPlanEntrega()">
                                <i class="bi bi-plus-lg"></i> Crear plan de entrega
                            </button>
                        </div>
                        <div id="contenedorPlanesActivos"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPlanEntrega" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title" style="display:flex;align-items:center;gap:10px;">
                            <i class="bi bi-truck" style="color:var(--dash-mint);"></i>
                            <span id="plan-modal-title">Crear Plan de Entrega</span>
                            <span id="plan-header-code" class="plan-code-badge" style="display:none;"></span>
                        </h4>
                        <div class="modal-order-meta">
                            Organiza pedidos con modalidad delivery y visualiza sus ubicaciones en el mapa
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="plan-stepper-wrap">
                        <div class="status-steps" id="plan-wizard-steps">
                            <div class="status-step active" id="pstep-ind-1">
                                <div class="step-dot">1</div>
                                <div class="step-label">Nombre del plan</div>
                            </div>
                            <div class="status-step" id="pstep-ind-2">
                                <div class="step-dot">2</div>
                                <div class="step-label">Seleccionar pedidos</div>
                            </div>
                            <div class="status-step" id="pstep-ind-3">
                                <div class="step-dot">3</div>
                                <div class="step-label">Mapa de entregas</div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 1: Nombre del plan -->
                    <div class="plan-step-pane active" id="plan-step-1">
                        <div style="max-width:480px;margin:8px auto;">
                            <label class="plan-form-label" for="inputNombrePlan">Nombre del plan de entrega</label>
                            <input type="text" id="inputNombrePlan" class="plan-form-input" maxlength="120" placeholder="Ej. Ruta Centro — Turno Mañana" autocomplete="off">
                            <p style="font-size:0.78rem;color:var(--dash-text-muted);margin:10px 0 20px;">
                                <i class="bi bi-qr-code" style="color:var(--dash-mint);"></i>
                                Al guardar se generará automáticamente un código único con formato <strong>0000-aaaa</strong> asociado a tu usuario y tienda.
                            </p>
                            <div style="display:flex;justify-content:flex-end;gap:10px;">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-success" id="btnCrearPlanPaso1" onclick="crearPlanEntrega()">
                                    Continuar y seleccionar pedidos <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Selección de pedidos delivery -->
                    <div class="plan-step-pane" id="plan-step-2">
                        <div class="plan-toolbar">
                            <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:220px;">
                                <input type="text" id="inputBuscarPedidoPlan" class="plan-form-input" style="padding:7px 12px;font-size:0.82rem;" placeholder="Buscar por # orden, cliente o dirección..." oninput="filtrarPedidosPlan()">
                            </div>
                            <div style="display:flex;align-items:center;gap:14px;">
                                <label style="display:inline-flex;align-items:center;gap:6px;font-size:0.8rem;color:var(--dash-text);cursor:pointer;margin:0;text-transform:none;">
                                    <input type="checkbox" id="chkTodosDelivery" class="plan-checkbox" style="margin:0;" onchange="toggleTodosPedidosPlan(this.checked)">
                                    Seleccionar todos
                                </label>
                                <span class="badge-status status-4" id="planContadorSel">0 seleccionados</span>
                            </div>
                        </div>

                        <div id="listaPedidosDelivery" class="plan-orders-list"></div>

                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;gap:10px;flex-wrap:wrap;">
                            <span style="font-size:0.76rem;color:var(--dash-text-muted);">
                                <i class="bi bi-info-circle" style="color:var(--dash-mint);"></i>
                                Solo se muestran pedidos con modalidad <strong>Delivery</strong> pendientes de entrega.
                            </span>
                            <button type="button" class="btn btn-success" id="btnGuardarPedidosPlan" onclick="guardarPedidosEnPlan()">
                                Guardar pedidos y ver mapa <i class="bi bi-geo-alt-fill"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Paso 3: Mapa Leaflet con ubicaciones -->
                    <div class="plan-step-pane" id="plan-step-3">
                        <div class="plan-summary-bar">
                            <div>
                                <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--dash-text-muted);">Plan registrado</div>
                                <div style="font-size:0.98rem;font-weight:600;color:var(--dash-text);" id="planResumenNombre"></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span class="plan-code-badge" id="planResumenCodigo"></span>
                                <span class="badge-status status-4" id="planResumenTotal"></span>
                            </div>
                        </div>

                        <div id="plan-map" style="height:360px;border-radius:10px;border:1px solid var(--dash-border);background:var(--dash-bg);"></div>
                        <div id="plan-map-empty" class="map-empty-state" style="display:none;height:220px;">
                            <i class="bi bi-geo-alt" style="font-size:2rem;color:var(--dash-text-muted);"></i>
                            <p style="color:var(--dash-text-muted);margin:8px 0 0;font-size:0.86rem;">Los pedidos seleccionados no tienen coordenadas GPS registradas, pero quedaron guardados en el plan.</p>
                        </div>

                        <div class="plan-stops-list" id="planStopsList"></div>

                        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                            <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                                <i class="bi bi-check2-circle"></i> Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/jquery-3.7.1.min.js"></script>
    <script src="../vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/leaflet/leaflet.js"></script>
    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
    <script>
        var planActual = null;
        var pedidosDeliveryCache = [];
        var pedidosSeleccionados = {};
        var planMap = null;
        var planMarkersById = {};

        function trunc2(n) {
            return (Math.floor(Math.round(parseFloat(n) * 1e6) / 1e4) / 100).toFixed(2);
        }

        function parseJsonResponse(res) {
            var ct = res.headers.get('content-type') || '';
            if (ct.indexOf('application/json') === -1) {
                throw new Error('SESSION');
            }
            return res.json();
        }

        function getStatusText(status) {
            status = parseInt(status);
            var map = {
                1: 'Pendiente',
                2: 'En preparación',
                3: 'Enviado',
                4: 'Entregado',
                5: 'Rechazado'
            };
            return map[status] || 'Pendiente';
        }

        function getStatusBadge(status) {
            return '<span class="badge-status status-' + status + '">' + getStatusText(status) + '</span>';
        }

        $(document).ready(function() {
            $('#modalPlanEntrega').on('hidden.bs.modal', function() {
                if (planMap) {
                    planMap.remove();
                    planMap = null;
                }
                planMarkersById = {};
            });

            cargarPlanesEntrega();
        });

        function cargarPlanesEntrega() {
            fetch('db/tienda_pedidos_api.php?action=listar_planes_entrega')
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    var lista = (data && data.ok && data.data) ? data.data : [];
                    $('#badgeTotalPlanesActivos').text(lista.length);
                    $('#stat-planes-activos').text(lista.length);

                    var sumPend = 0;
                    var sumEntr = 0;
                    lista.forEach(function(p) {
                        sumPend += parseInt(p.pendientes) || 0;
                        sumEntr += parseInt(p.entregados) || 0;
                    });
                    $('#stat-pedidos-pendientes').text(sumPend);
                    $('#stat-pedidos-entregados').text(sumEntr);

                    if (lista.length === 0) {
                        $('#contenedorPlanesActivos').html(
                            '<div class="planes-empty">' +
                            '<i class="bi bi-truck"></i>' +
                            '<p style="margin:12px 0 4px;font-size:0.95rem;font-weight:600;color:var(--dash-text);">No hay planes de entrega activos</p>' +
                            '<p style="margin:0 0 18px;font-size:0.82rem;">Crea un nuevo plan para agrupar pedidos delivery y visualizar sus ubicaciones en el mapa.</p>' +
                            '<button class="btn btn-success" onclick="abrirModalPlanEntrega()"><i class="bi bi-plus-lg"></i> Crear plan de entrega</button>' +
                            '</div>'
                        );
                        return;
                    }

                    var html = '<table id="tablaPlanes">' +
                        '<thead><tr>' +
                        '<th>Plan</th>' +
                        '<th>Código</th>' +
                        '<th>Progreso</th>' +
                        '<th>Entregados</th>' +
                        '<th>Pendientes</th>' +
                        '<th style="text-align:right;">Acciones</th>' +
                        '</tr></thead><tbody>';
                    lista.forEach(function(p) {
                        var total = parseInt(p.total_pedidos) || 0;
                        var entregados = parseInt(p.entregados) || 0;
                        var pendientes = parseInt(p.pendientes) || 0;
                        var pct = total > 0 ? Math.round((entregados / total) * 100) : 0;

                        html += '<tr>' +
                            '<td>' +
                            '<div class="plan-td-name">' + p.nombre + '</div>' +
                            '<div class="plan-td-date"><i class="bi bi-clock"></i> ' + (p.created_at || '') + '</div>' +
                            '</td>' +
                            '<td><span class="plan-code-badge"><i class="bi bi-upc-scan"></i> ' + p.codigo + '</span></td>' +
                            '<td>' +
                            '<div class="plan-progress-wrap">' +
                            '<div class="plan-progress-track"><div class="plan-progress-fill" style="width:' + pct + '%;"></div></div>' +
                            '<span class="plan-progress-label">' + pct + '%</span>' +
                            '</div>' +
                            '</td>' +
                            '<td><strong>' + entregados + '</strong> / ' + total + '</td>' +
                            '<td><strong style="color:' + (pendientes > 0 ? '#facc15' : 'var(--dash-mint)') + ';">' + pendientes + '</strong></td>' +
                            '<td style="text-align:right;">' +
                            '<button class="btn btn-outline-success btn-sm" onclick="verPlanEntrega(' + p.id + ')"><i class="bi bi-geo-alt-fill"></i> Ver mapa</button>' +
                            '</td>' +
                            '</tr>';
                    });
                    html += '</tbody></table>';
                    $('#contenedorPlanesActivos').html(html);
                })
                .catch(function(err) {
                    console.error('Error al cargar planes:', err);
                });
        }

        function setPasoPlanUI(paso) {
            [1, 2, 3].forEach(function(s) {
                var ind = $('#pstep-ind-' + s);
                ind.removeClass('active done');
                var dot = ind.find('.step-dot');
                if (s < paso) {
                    ind.addClass('done');
                    dot.html('<i class="bi bi-check-lg"></i>');
                } else if (s === paso) {
                    ind.addClass('active');
                    dot.text(s);
                } else {
                    dot.text(s);
                }
            });
            $('.plan-step-pane').removeClass('active');
            $('#plan-step-' + paso).addClass('active');
        }

        function abrirModalPlanEntrega() {
            planActual = null;
            pedidosDeliveryCache = [];
            pedidosSeleccionados = {};
            $('#inputNombrePlan').val('');
            $('#inputBuscarPedidoPlan').val('');
            $('#chkTodosDelivery').prop('checked', false);
            $('#plan-modal-title').text('Crear Plan de Entrega');
            $('#plan-header-code').hide().text('');
            setPasoPlanUI(1);
            var modalEl = document.getElementById('modalPlanEntrega');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            setTimeout(function() {
                $('#inputNombrePlan').trigger('focus');
            }, 250);
        }

        function crearPlanEntrega() {
            var nombre = $.trim($('#inputNombrePlan').val());
            if (!nombre) {
                alert('Por favor ingresa un nombre para el plan de entrega.');
                $('#inputNombrePlan').trigger('focus');
                return;
            }

            var btn = $('#btnCrearPlanPaso1');
            btn.prop('disabled', true);

            var fd = new FormData();
            fd.append('action', 'crear_plan_entrega');
            fd.append('nombre', nombre);

            fetch('db/tienda_pedidos_api.php', {
                    method: 'POST',
                    body: fd
                })
                .then(parseJsonResponse)
                .then(function(data) {
                    btn.prop('disabled', false);
                    if (!data.ok) {
                        alert(data.msg || 'No se pudo crear el plan');
                        return;
                    }
                    planActual = {
                        id: data.plan_id,
                        nombre: data.nombre,
                        codigo: data.codigo
                    };
                    $('#plan-modal-title').text(data.nombre);
                    $('#plan-header-code').html('<i class="bi bi-upc-scan"></i> ' + data.codigo).show();
                    setPasoPlanUI(2);
                    cargarPedidosDeliveryDisponibles();
                })
                .catch(function(err) {
                    btn.prop('disabled', false);
                    if (err && err.message === 'SESSION') {
                        alert('Tu sesión expiró o la membresía requiere renovación. Recarga la página e inicia sesión nuevamente.');
                    } else {
                        alert('Error de conexión al crear el plan.');
                    }
                });
        }

        function cargarPedidosDeliveryDisponibles() {
            $('#listaPedidosDelivery').html('<div style="padding:32px;text-align:center;color:var(--dash-text-muted);font-size:0.85rem;">Cargando pedidos delivery...</div>');
            fetch('db/tienda_pedidos_api.php?action=listar_delivery_disponibles')
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) {
                        $('#listaPedidosDelivery').html('<div style="padding:32px;text-align:center;color:#ef4444;font-size:0.85rem;">Error al cargar pedidos</div>');
                        return;
                    }
                    pedidosDeliveryCache = data.data || [];
                    renderListaPedidosDelivery();
                });
        }

        function renderListaPedidosDelivery() {
            var q = $.trim($('#inputBuscarPedidoPlan').val()).toLowerCase();
            var filtrados = pedidosDeliveryCache.filter(function(o) {
                if (!q) return true;
                var texto = ('#' + o.id + ' ' + (o.cliente_nombre || o.cliente || '') + ' ' + (o.direccion || '') + ' ' + (o.comunidad || '')).toLowerCase();
                return texto.indexOf(q) !== -1;
            });

            if (filtrados.length === 0) {
                $('#listaPedidosDelivery').html('<div style="padding:36px 16px;text-align:center;color:var(--dash-text-muted);font-size:0.85rem;">No hay pedidos con modalidad Delivery disponibles.</div>');
                actualizarContadorSeleccionPlan();
                return;
            }

            var html = '';
            filtrados.forEach(function(o) {
                var checked = !!pedidosSeleccionados[o.id];
                var hasGps = o.lat && o.lng && parseFloat(o.lat) !== 0 && parseFloat(o.lng) !== 0;
                var gpsBadge = hasGps ?
                    '<span class="plan-gps-tag has-gps"><i class="bi bi-geo-alt-fill"></i> GPS</span>' :
                    '<span class="plan-gps-tag no-gps"><i class="bi bi-geo-alt"></i> Sin GPS</span>';
                var cliente = o.cliente_nombre || o.cliente || 'Cliente';
                var dir = o.direccion || 'Sin dirección especificada';
                if (o.comunidad) dir += ' · ' + o.comunidad;

                html += '<div class="plan-order-item ' + (checked ? 'selected' : '') + '" onclick="togglePedidoPlan(' + o.id + ')">' +
                    '<div class="plan-order-left">' +
                    '<input type="checkbox" class="plan-checkbox" ' + (checked ? 'checked' : '') + ' onclick="event.stopPropagation(); togglePedidoPlan(' + o.id + ')">' +
                    '<div>' +
                    '<div class="plan-order-title">' +
                    '<span>#' + o.id + ' — ' + cliente + '</span>' +
                    getStatusBadge(o.status) +
                    gpsBadge +
                    '</div>' +
                    '<div class="plan-order-sub"><i class="bi bi-pin-map"></i> ' + dir + (o.cliente_telefono ? ' · <i class="bi bi-telephone"></i> ' + o.cliente_telefono : '') + '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div style="font-weight:700;color:var(--dash-mint);font-size:0.9rem;white-space:nowrap;">$' + trunc2(o.total_price) + '</div>' +
                    '</div>';
            });

            $('#listaPedidosDelivery').html(html);
            actualizarContadorSeleccionPlan();
        }

        function togglePedidoPlan(id) {
            if (pedidosSeleccionados[id]) {
                delete pedidosSeleccionados[id];
            } else {
                pedidosSeleccionados[id] = true;
            }
            renderListaPedidosDelivery();
        }

        function toggleTodosPedidosPlan(marcar) {
            var q = $.trim($('#inputBuscarPedidoPlan').val()).toLowerCase();
            pedidosDeliveryCache.forEach(function(o) {
                var texto = ('#' + o.id + ' ' + (o.cliente_nombre || o.cliente || '') + ' ' + (o.direccion || '') + ' ' + (o.comunidad || '')).toLowerCase();
                if (!q || texto.indexOf(q) !== -1) {
                    if (marcar) pedidosSeleccionados[o.id] = true;
                    else delete pedidosSeleccionados[o.id];
                }
            });
            renderListaPedidosDelivery();
        }

        function filtrarPedidosPlan() {
            renderListaPedidosDelivery();
        }

        function actualizarContadorSeleccionPlan() {
            var ids = Object.keys(pedidosSeleccionados);
            $('#planContadorSel').text(ids.length + (ids.length === 1 ? ' seleccionado' : ' seleccionados'));
        }

        function guardarPedidosEnPlan() {
            if (!planActual || !planActual.id) return;
            var ids = Object.keys(pedidosSeleccionados);
            if (ids.length === 0) {
                alert('Selecciona al menos un pedido Delivery para continuar.');
                return;
            }

            var btn = $('#btnGuardarPedidosPlan');
            btn.prop('disabled', true);

            var fd = new FormData();
            fd.append('action', 'guardar_pedidos_plan');
            fd.append('plan_id', planActual.id);
            ids.forEach(function(id) {
                fd.append('ordenes[]', id);
            });

            fetch('db/tienda_pedidos_api.php', {
                    method: 'POST',
                    body: fd
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    btn.prop('disabled', false);
                    if (!data.ok) {
                        alert(data.msg || 'Error al guardar los pedidos en el plan');
                        return;
                    }
                    cargarPlanesEntrega();
                    setPasoPlanUI(3);
                    mostrarMapaPlanEntrega(data.plan, data.pedidos || []);
                })
                .catch(function() {
                    btn.prop('disabled', false);
                    alert('Error de conexión al guardar los pedidos.');
                });
        }

        function verPlanEntrega(planId) {
            fetch('db/tienda_pedidos_api.php?action=detalle_plan_entrega&id=' + planId)
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) {
                        alert(data.msg || 'No se pudo cargar el plan');
                        return;
                    }
                    planActual = data.plan;
                    $('#plan-modal-title').text(data.plan.nombre);
                    $('#plan-header-code').html('<i class="bi bi-upc-scan"></i> ' + data.plan.codigo).show();
                    setPasoPlanUI(3);
                    var modalEl = document.getElementById('modalPlanEntrega');
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    setTimeout(function() {
                        mostrarMapaPlanEntrega(data.plan, data.pedidos || []);
                    }, 180);
                });
        }

        function mostrarMapaPlanEntrega(plan, pedidos) {
            $('#planResumenNombre').text(plan.nombre);
            $('#planResumenCodigo').html('<i class="bi bi-upc-scan"></i> ' + plan.codigo);
            $('#planResumenTotal').text(pedidos.length + (pedidos.length === 1 ? ' pedido' : ' pedidos'));

            if (planMap) {
                planMap.remove();
                planMap = null;
            }
            planMarkersById = {};

            var puntos = [];
            var stopsHtml = '';

            pedidos.forEach(function(p, idx) {
                var num = idx + 1;
                var lat = parseFloat(p.lat);
                var lng = parseFloat(p.lng);
                var hasGps = !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0;
                var cliente = p.cliente_nombre || p.cliente || 'Cliente';
                var dir = p.direccion || 'Sin dirección registrada';

                if (hasGps) {
                    puntos.push({
                        num: num,
                        lat: lat,
                        lng: lng,
                        pedido: p
                    });
                }

                stopsHtml += '<div class="plan-stop-row" onclick="enfocarParadaMapa(' + p.id + ')">' +
                    '<div style="display:flex;align-items:center;gap:10px;min-width:0;">' +
                    '<span class="plan-stop-num">' + num + '</span>' +
                    '<div style="min-width:0;">' +
                    '<div style="font-weight:600;color:var(--dash-text);display:flex;align-items:center;gap:6px;flex-wrap:wrap;">' +
                    '<span>Orden #' + p.id + ' · ' + cliente + '</span>' +
                    getStatusBadge(p.status) +
                    '</div>' +
                    '<div style="font-size:0.75rem;color:var(--dash-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + dir + (p.referencia ? ' (' + p.referencia + ')' : '') + '</div>' +
                    '</div>' +
                    '</div>' +
                    '<div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">' +
                    (hasGps ? '<span class="plan-gps-tag has-gps"><i class="bi bi-geo-alt-fill"></i> En mapa</span>' : '<span class="plan-gps-tag no-gps">Sin GPS</span>') +
                    '<strong style="color:var(--dash-mint);">$' + trunc2(p.total_price) + '</strong>' +
                    '</div>' +
                    '</div>';
            });

            $('#planStopsList').html(stopsHtml);

            if (puntos.length > 0) {
                $('#plan-map').show();
                $('#plan-map-empty').hide();

                planMap = L.map('plan-map').setView([puntos[0].lat, puntos[0].lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(planMap);

                var bounds = [];
                puntos.forEach(function(item) {
                    var p = item.pedido;
                    var cliente = p.cliente_nombre || p.cliente || 'Cliente';
                    var icon = L.divIcon({
                        className: 'plan-leaflet-pin',
                        html: '<span>' + item.num + '</span>',
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                        popupAnchor: [0, -14]
                    });

                    var popupHtml = '<div style="font-size:12px;line-height:1.45;min-width:180px;">' +
                        '<strong>Parada #' + item.num + ' — Orden #' + p.id + '</strong><br>' +
                        '<span><strong>Cliente:</strong> ' + cliente + '</span><br>' +
                        (p.cliente_telefono ? '<span><strong>Tel:</strong> ' + p.cliente_telefono + '</span><br>' : '') +
                        '<span><strong>Dirección:</strong> ' + (p.direccion || 'N/A') + '</span>' +
                        (p.referencia ? '<br><span><strong>Ref:</strong> ' + p.referencia + '</span>' : '') +
                        '<br><span><strong>Total:</strong> $' + trunc2(p.total_price) + '</span>' +
                        '</div>';

                    var marker = L.marker([item.lat, item.lng], {
                        icon: icon
                    }).addTo(planMap).bindPopup(popupHtml);

                    planMarkersById[p.id] = marker;
                    bounds.push([item.lat, item.lng]);
                });

                setTimeout(function() {
                    if (!planMap) return;
                    planMap.invalidateSize();
                    if (bounds.length > 1) {
                        planMap.fitBounds(bounds, {
                            padding: [36, 36],
                            maxZoom: 16
                        });
                    } else if (bounds.length === 1) {
                        planMap.setView(bounds[0], 15);
                        var firstId = puntos[0].pedido.id;
                        if (planMarkersById[firstId]) planMarkersById[firstId].openPopup();
                    }
                }, 200);
            } else {
                $('#plan-map').hide();
                $('#plan-map-empty').show();
            }
        }

        function enfocarParadaMapa(ordenId) {
            var marker = planMarkersById[ordenId];
            if (planMap && marker) {
                planMap.setView(marker.getLatLng(), 16, {
                    animate: true
                });
                marker.openPopup();
            }
        }
    </script>
</body>

</html>