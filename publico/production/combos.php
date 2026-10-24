<?php
require_once('includes/requires.php');

if (!isset($_SESSION["validate"]) || $_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
}

$tipo_u = (int)$_SESSION['nivel'];
$bss_id = (int)$_SESSION['bss_id'];
$sucursal_actual = (int)($_SESSION['sucursal'] ?? 0);

$topnav = topnav();

$sucursales = [];
if ($tipo_u === 1) {
    $stmtSuc = $conexion->prepare("SELECT id, nombre, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC");
    $stmtSuc->bind_param('i', $bss_id);
    $stmtSuc->execute();
    $resSuc = $stmtSuc->get_result();
    while ($rowS = $resSuc->fetch_assoc()) {
        $sucursales[] = $rowS;
        if ($sucursal_actual <= 0) {
            $sucursal_actual = (int)$rowS['id'];
        }
    }
    $stmtSuc->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Packs y Combos | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <script src="../assets/sweetalert2.all.min.js"></script>

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
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .dash-panel {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            background: var(--dash-card);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .dash-panel .panel-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--dash-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .dash-panel .panel-header h6 {
            font-size: 14px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dash-panel .panel-body {
            padding: 20px 22px;
        }

        .form-label-dash {
            font-size: 12px;
            font-weight: 600;
            color: var(--dash-text-muted);
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .input-dash {
            background: var(--dash-bg) !important;
            border: 1px solid var(--dash-border) !important;
            color: var(--dash-text) !important;
            border-radius: 8px !important;
            padding: 9px 12px !important;
            font-size: 13px !important;
            width: 100%;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-dash:focus {
            border-color: var(--dash-mint) !important;
            box-shadow: 0 0 0 2px rgba(45, 212, 160, 0.15) !important;
        }

        .btn-mint {
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
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-mint:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 16px rgba(45, 212, 160, 0.3);
            color: #fff;
        }

        .btn-ghost {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            font-size: 12px;
            font-weight: 600;
            padding: 7px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-ghost:hover {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
        }

        /* Buscador de productos para agregar al combo */
        .prod-search-list {
            max-height: 230px;
            overflow-y: auto;
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            background: var(--dash-bg);
            margin-top: 8px;
        }

        .prod-search-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            border-bottom: 1px solid rgba(46, 53, 62, 0.5);
            gap: 10px;
            transition: background 0.15s;
        }

        .prod-search-item:last-child {
            border-bottom: none;
        }

        .prod-search-item:hover {
            background: rgba(45, 212, 160, 0.05);
        }

        .prod-search-item.disabled-origin {
            opacity: 0.42;
        }

        .badge-origin {
            font-size: 10px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .badge-origin.ven {
            background: rgba(45, 212, 160, 0.14);
            color: var(--dash-mint);
            border: 1px solid rgba(45, 212, 160, 0.25);
        }

        .badge-origin.col {
            background: rgba(91, 156, 245, 0.14);
            color: var(--dash-info);
            border: 1px solid rgba(91, 156, 245, 0.25);
        }

        .badge-origin.mix {
            background: rgba(245, 180, 91, 0.15);
            color: #f5b45b;
            border: 1px solid rgba(245, 180, 91, 0.3);
        }

        /* Resumen KPI dentro del creador */
        .combo-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 16px 0;
        }

        .combo-summary-box {
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            padding: 12px 14px;
            background: var(--dash-bg);
        }

        .combo-summary-box small {
            font-size: 11px;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            font-weight: 600;
            display: block;
        }

        .combo-summary-box .val {
            font-size: 20px;
            font-weight: 700;
            color: var(--dash-text);
            margin-top: 2px;
        }

        .alert-cost-warning {
            background: rgba(239, 90, 111, 0.12);
            border: 1px solid rgba(239, 90, 111, 0.35);
            color: #ef5a6f;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12.5px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        /* Tabla de seguimiento de combos */
        .table-dash {
            width: 100%;
            border-collapse: collapse;
        }

        .table-dash th {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--dash-text-muted);
            padding: 12px 14px;
            border-bottom: 1px solid var(--dash-border);
            text-align: left;
        }

        .table-dash td {
            padding: 13px 14px;
            border-bottom: 1px solid rgba(46, 53, 62, 0.5);
            color: var(--dash-text);
            font-size: 13px;
            vertical-align: middle;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .status-pill.ok {
            background: rgba(45, 212, 160, 0.12);
            color: var(--dash-mint);
        }

        .status-pill.warn {
            background: rgba(245, 180, 91, 0.15);
            color: #f5b45b;
        }

        .status-pill.danger {
            background: rgba(239, 90, 111, 0.15);
            color: #ef5a6f;
        }

        .diff-up {
            color: #f5b45b;
            font-weight: 700;
            font-size: 11px;
        }

        .diff-down {
            color: var(--dash-mint);
            font-weight: 700;
            font-size: 11px;
        }

        .combo-detail-row {
            background: rgba(0, 0, 0, 0.18);
        }

        .combo-detail-box {
            padding: 14px 18px;
            border-left: 3px solid var(--dash-mint);
        }

        @media (max-width: 992px) {
            .combo-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ═══ Responsive: móvil / WebView ═══ */
        @media (max-width: 768px) {
            .right_col {
                padding: 14px 12px !important;
            }

            .dash-header {
                margin-bottom: 16px;
                gap: 10px;
            }

            .dash-header > div:last-child {
                width: 100%;
                min-width: 0 !important;
            }

            .dash-panel {
                border-radius: 12px;
                margin-bottom: 16px;
            }

            .dash-panel .panel-header {
                padding: 14px;
                gap: 10px;
            }

            .dash-panel .panel-body {
                padding: 14px;
            }

            .panel-header > .d-flex {
                width: 100%;
                flex-wrap: wrap;
                gap: 8px;
            }

            .panel-header .btn-ghost,
            .panel-header .btn-mint {
                flex: 1 1 auto;
                justify-content: center;
            }

            /* Etiqueta larga + botón pequeño: el botón pasa a su propia línea. */
            .combo-field-head {
                flex-wrap: wrap;
                gap: 6px;
            }

            .combo-field-head .form-label-dash {
                flex: 1 1 100%;
                margin-bottom: 0;
            }

            .combo-field-head .btn-ghost {
                white-space: nowrap;
                font-size: 10.5px;
                padding: 4px 8px;
            }

            .combo-col-items {
                margin-top: 18px;
            }

            /* En vez de aplastar las columnas, la tabla se desliza en horizontal. */
            .panel-body.p-0 {
                -webkit-overflow-scrolling: touch;
            }

            .table-dash {
                min-width: 760px;
            }

            .table-dash th,
            .table-dash td {
                padding: 10px 12px;
            }

            /* Tabla de productos incluidos en el combo */
            .combo-items-wrap {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            #tablaItemsCombo {
                min-width: 520px;
            }

            /* Detalle desplegable de cada combo */
            .combo-detail-box {
                padding: 12px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .combo-detail-box .table-dash {
                min-width: 560px;
            }

            .prod-search-list {
                max-height: 200px;
            }

            .prod-search-item {
                padding: 10px 12px;
                gap: 8px;
            }

            .prod-search-item .btn-ghost {
                flex-shrink: 0;
                padding: 5px 10px;
            }

            .combo-summary-grid {
                grid-template-columns: 1fr;
                gap: 10px;
                margin: 14px 0;
            }

            .combo-summary-box {
                padding: 10px 12px;
            }

            .alert-cost-warning {
                padding: 10px 12px;
                font-size: 12px;
            }
        }

        @media (max-width: 767.98px) {
            /* Separación entre la columna de datos y la de productos del combo. */
            .combo-col-items {
                margin-top: 18px;
            }
        }

        @media (max-width: 480px) {
            .right_col {
                padding: 12px 10px !important;
            }

            .dash-panel .panel-header {
                flex-direction: column;
                align-items: stretch;
            }

            .panel-header h6 {
                font-size: 13px;
                line-height: 1.35;
            }

            .combo-detail-box {
                padding: 10px;
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
                    <div id="nombre_pagina"></div>
                    <?php if ($tipo_u === 1 && count($sucursales) > 1): ?>
                        <div style="min-width: 220px;">
                            <select id="selectSucursal" class="input-dash">
                                <?php foreach ($sucursales as $suc): ?>
                                    <option value="<?= (int)$suc['id'] ?>" <?= (int)$suc['id'] === $sucursal_actual ? 'selected' : '' ?>>
                                        Sucursal: <?= htmlspecialchars($suc['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECCIÓN: TABLA DE COMBOS ACTIVOS (ANCHO COMPLETO) -->
                <div id="seccionTablaCombos" class="row g-4">
                    <div class="col-12">
                        <div class="dash-panel">
                            <div class="panel-header">
                                <h6>Combos Activos y Seguimiento de Precios en $</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn-ghost" onclick="cargarCombos()">
                                        <ion-icon name="refresh-outline"></ion-icon> Actualizar
                                    </button>
                                    <button type="button" class="btn-mint" onclick="mostrarFormularioCombo()">
                                        <ion-icon name="add-outline"></ion-icon> Agregar
                                    </button>
                                </div>
                            </div>
                            <div class="panel-body p-0" style="overflow-x:auto;">
                                <div id="combosVacio" style="display:none;text-align:center;padding:34px 16px;color:var(--dash-text-muted);">
                                    No hay combos registrados en esta sucursal.
                                </div>
                                <table class="table-dash" id="tablaCombos">
                                    <thead>
                                        <tr>
                                            <th>Combo</th>
                                            <th>Costo Reg. vs Actual ($)</th>
                                            <th>Suma Detal ($)</th>
                                            <th>Precio Combo ($)</th>
                                            <th>Estado Precios</th>
                                            <th style="text-align:right;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyCombos">
                                        <tr>
                                            <td colspan="6" style="text-align:center;padding:30px;color:var(--dash-text-muted);">
                                                Cargando combos...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: FORMULARIO DE REGISTRO (OCULTO POR DEFECTO) -->
                <div id="seccionFormularioCombo" class="row g-4" style="display:none;">
                    <div class="col-12">
                        <div class="dash-panel">
                            <div class="panel-header">
                                <h6 id="tituloFormulario">Crear Nuevo Combo</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span id="badgeOrigenCombo" class="badge-origin" style="display:none;"></span>
                                    <button type="button" id="btnCancelarEdicion" class="btn-ghost" style="display:none;padding:4px 10px;font-size:11px;" onclick="mostrarTablaCombos()">
                                        Cancelar
                                    </button>
                                    <button type="button" class="btn-ghost" onclick="mostrarTablaCombos()">
                                        Volver al listado
                                    </button>
                                </div>
                            </div>
                            <div class="panel-body">
                                <input type="hidden" id="comboIdInput" value="0">

                                <div class="row g-4">
                                    <!-- COLUMNA IZQUIERDA: DATOS Y BÚSQUEDA -->
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label-dash">Nombre del Combo *</label>
                                            <input type="text" id="nombreCombo" class="input-dash" placeholder="Ej: Combo Desayuno Familiar">
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1 combo-field-head">
                                                <label class="form-label-dash mb-0">Seleccionar productos del combo</label>
                                                <small style="color:var(--dash-text-muted);font-size:11px;">Puedes combinar productos de ambos orígenes</small>
                                            </div>
                                            <input type="text" id="buscadorProductos" class="input-dash" placeholder="Buscar producto por nombre o código...">
                                            <div class="prod-search-list" id="listaResultadosProductos"></div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1 combo-field-head">
                                                <label class="form-label-dash mb-0">Precio Final de Venta del Combo ($) *</label>
                                                <button type="button" class="btn-ghost" style="padding:2px 8px;font-size:11px;" onclick="usarPrecioSugerido()">
                                                    Usar suma sugerida
                                                </button>
                                            </div>
                                            <input type="number" step="0.01" min="0" id="precioFinalCombo" class="input-dash" placeholder="0.00" style="font-size:16px !important;font-weight:700;">
                                        </div>

                                        <div class="alert-cost-warning" id="alertaBajoCosto">
                                            <ion-icon name="warning-outline" style="font-size:22px;flex-shrink:0;"></ion-icon>
                                            <div>
                                                ¡Atención! El precio final del combo está por debajo del costo de compra sumado de los productos (<span id="textoCostoAlerta">$0.00</span>). Estarías vendiendo a pérdida.
                                            </div>
                                        </div>

                                        <button type="button" class="btn-mint w-100 justify-content-center" id="btnGuardarCombo" onclick="guardarCombo()">
                                            <ion-icon name="save-outline" style="font-size:17px;"></ion-icon>
                                            <span id="textoBtnGuardar">Guardar Combo</span>
                                        </button>
                                    </div>

                                    <!-- COLUMNA DERECHA: PRODUCTOS INCLUIDOS EN EL COMBO -->
                                    <div class="col-md-6 combo-col-items">
                                        <label class="form-label-dash">Productos incluidos en el combo</label>
                                        <div class="combo-items-wrap" style="border:1px solid var(--dash-border);border-radius:10px;overflow:hidden;margin-bottom:14px;">
                                            <div id="itemsComboVacio" style="display:none;text-align:center;color:var(--dash-text-muted);padding:20px 14px;font-size:13px;">
                                                Selecciona productos arriba para armar el combo
                                            </div>
                                            <table class="table-dash" id="tablaItemsCombo">
                                                <thead>
                                                    <tr>
                                                        <th>Producto</th>
                                                        <th style="width:75px;text-align:center;">Cant.</th>
                                                        <th style="text-align:right;">Costo $</th>
                                                        <th style="text-align:right;">Venta $</th>
                                                        <th style="width:36px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyItemsCombo">
                                                    <tr>
                                                        <td colspan="5" style="text-align:center;color:var(--dash-text-muted);padding:20px;">
                                                            Selecciona productos arriba para armar el combo
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="combo-summary-grid">
                                            <div class="combo-summary-box">
                                                <small>Suma Costo Compra</small>
                                                <div class="val" id="resumenCostoTotal">$0.00</div>
                                            </div>
                                            <div class="combo-summary-box">
                                                <small>Suma Venta Normal</small>
                                                <div class="val" id="resumenVentaSugerida" style="color:var(--dash-info);">$0.00</div>
                                            </div>
                                            <div class="combo-summary-box">
                                                <small>Ganancia Estimada</small>
                                                <div class="val" id="resumenGanancia" style="color:var(--dash-mint);">$0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        let catalogoProductos = [];
        let listaCombos = [];
        let itemsSeleccionados = []; // [{producto_id, nombre, origen, costo_unitario, venta_unitario, cantidad}]
        let precioEditadoManualmente = false;

        function getSucursalId() {
            const sel = document.getElementById('selectSucursal');
            return sel ? sel.value : '<?= (int)$sucursal_actual ?>';
        }

        function fmtUsd(val) {
            return '$' + (Number(val) || 0).toFixed(2);
        }

        function obtenerOrigenActualCombo() {
            if (itemsSeleccionados.length === 0) return null;
            const primero = itemsSeleccionados[0].origen;
            return itemsSeleccionados.every(i => i.origen === primero) ? primero : 'm';
        }

        function valorItem(it, campo) {
            const bcv = campo + '_bcv';
            return (obtenerOrigenActualCombo() === 'm' && it[bcv] != null)
                ? Number(it[bcv])
                : Number(it[campo]);
        }

        function mostrarFormularioCombo() {
            document.getElementById('seccionTablaCombos').style.display = 'none';
            document.getElementById('seccionFormularioCombo').style.display = 'flex';
            resetFormulario();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function mostrarTablaCombos() {
            document.getElementById('seccionFormularioCombo').style.display = 'none';
            document.getElementById('seccionTablaCombos').style.display = 'flex';
            resetFormulario();
        }

        function cargarCatalogoProductos() {
            fetch('../../configurar/combos_back.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: 'listar_productos',
                    sucursal_id: getSucursalId()
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    catalogoProductos = data.productos || [];
                    renderResultadosBusqueda();
                }
            });
        }

        function renderResultadosBusqueda() {
            const cont = document.getElementById('listaResultadosProductos');
            const q = (document.getElementById('buscadorProductos').value || '').trim().toLowerCase();

            const filtrados = catalogoProductos.filter(p => {
                if (!q) return true;
                return p.nombre.toLowerCase().includes(q) || (p.codigo_barras && p.codigo_barras.toLowerCase().includes(q));
            }).slice(0, 40);

            if (filtrados.length === 0) {
                cont.innerHTML = '<div style="padding:14px;text-align:center;color:var(--dash-text-muted);font-size:12px;">No se encontraron productos</div>';
                return;
            }

            cont.innerHTML = filtrados.map(p => {
                const badgeClass = p.origen === 'c' ? 'col' : 'ven';
                const badgeText = p.origen === 'c' ? 'COL' : 'VEN';
                const mixto = obtenerOrigenActualCombo() === 'm';
                const costo = mixto ? p.costo_unitario_bcv : p.costo_unitario;
                const venta = mixto ? p.venta_unitario_bcv : p.venta_unitario;
                return `
                    <div class="prod-search-item">
                        <div style="min-width:0;flex:1;">
                            <div style="font-size:13px;font-weight:600;color:var(--dash-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <span class="badge-origin ${badgeClass}" style="margin-right:6px;">${badgeText}</span>
                                ${p.nombre}
                            </div>
                            <small style="color:var(--dash-text-muted);font-size:11px;">
                                Costo: ${fmtUsd(costo)} | Venta: ${fmtUsd(venta)} | Stock: ${p.stock}
                            </small>
                        </div>
                        <button type="button" class="btn-ghost" style="padding:4px 10px;" onclick="agregarProductoAlCombo(${p.id})">
                            + Agregar
                        </button>
                    </div>
                `;
            }).join('');
        }

        function agregarProductoAlCombo(prodId) {
            const prod = catalogoProductos.find(p => p.id === Number(prodId));
            if (!prod) return;

            const existente = itemsSeleccionados.find(i => i.producto_id === prod.id);
            if (existente) {
                existente.cantidad = Number(existente.cantidad) + 1;
            } else {
                itemsSeleccionados.push({
                    producto_id: prod.id,
                    nombre: prod.nombre,
                    origen: prod.origen,
                    costo_unitario: Number(prod.costo_unitario),
                    venta_unitario: Number(prod.venta_unitario),
                    costo_unitario_bcv: Number(prod.costo_unitario_bcv ?? prod.costo_unitario),
                    venta_unitario_bcv: Number(prod.venta_unitario_bcv ?? prod.venta_unitario),
                    cantidad: 1
                });
            }

            renderItemsSeleccionados();
            renderResultadosBusqueda();
        }

        function cambiarCantidadItem(prodId, valor) {
            const cant = parseFloat(valor);
            const item = itemsSeleccionados.find(i => i.producto_id === Number(prodId));
            if (!item) return;
            item.cantidad = (isNaN(cant) || cant <= 0) ? 0 : cant;
            recalcularTotalesCombo();
        }

        function quitarItemDelCombo(prodId) {
            itemsSeleccionados = itemsSeleccionados.filter(i => i.producto_id !== Number(prodId));
            renderItemsSeleccionados();
            renderResultadosBusqueda();
        }

        function renderItemsSeleccionados() {
            const tbody = document.getElementById('tbodyItemsCombo');
            const tablaItems = document.getElementById('tablaItemsCombo');
            const vacioItems = document.getElementById('itemsComboVacio');
            const badgeOrigen = document.getElementById('badgeOrigenCombo');
            const origenCombo = obtenerOrigenActualCombo();

            if (origenCombo) {
                badgeOrigen.style.display = 'inline-block';
                badgeOrigen.className = 'badge-origin ' + (origenCombo === 'c' ? 'col' : (origenCombo === 'm' ? 'mix' : 'ven'));
                badgeOrigen.textContent = origenCombo === 'c' ? 'Origen Colombiano' : (origenCombo === 'm' ? 'Origen Mixto' : 'Origen Venezolano');
            } else {
                badgeOrigen.style.display = 'none';
            }

            if (itemsSeleccionados.length === 0) {
                tablaItems.style.display = 'none';
                vacioItems.style.display = 'block';
                tbody.innerHTML = '';
                recalcularTotalesCombo();
                return;
            }

            tablaItems.style.display = '';
            vacioItems.style.display = 'none';

            tbody.innerHTML = itemsSeleccionados.map(it => {
                const costoUnit = valorItem(it, 'costo_unitario');
                const ventaUnit = valorItem(it, 'venta_unitario');
                const subCosto = costoUnit * it.cantidad;
                const subVenta = ventaUnit * it.cantidad;
                return `
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:12.5px;">${it.nombre}</div>
                            <small style="color:var(--dash-text-muted);font-size:11px;">
                                Unit: C ${fmtUsd(costoUnit)} / V ${fmtUsd(ventaUnit)}
                            </small>
                        </td>
                        <td style="text-align:center;">
                            <input type="number" step="0.01" min="0.01" class="input-dash" style="padding:4px 6px !important;text-align:center;"
                                value="${it.cantidad}" oninput="cambiarCantidadItem(${it.producto_id}, this.value)">
                        </td>
                        <td style="text-align:right;font-weight:600;">${fmtUsd(subCosto)}</td>
                        <td style="text-align:right;font-weight:600;color:var(--dash-info);">${fmtUsd(subVenta)}</td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-ghost" style="padding:3px 7px;color:#ef5a6f;border-color:rgba(239,90,111,0.3);" onclick="quitarItemDelCombo(${it.producto_id})">
                                &times;
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            recalcularTotalesCombo();
        }

        function recalcularTotalesCombo() {
            let sumaCosto = 0;
            let sumaVenta = 0;

            itemsSeleccionados.forEach(it => {
                const q = Number(it.cantidad) || 0;
                sumaCosto += valorItem(it, 'costo_unitario') * q;
                sumaVenta += valorItem(it, 'venta_unitario') * q;
            });

            sumaCosto = Number(sumaCosto.toFixed(2));
            sumaVenta = Number(sumaVenta.toFixed(2));

            document.getElementById('resumenCostoTotal').textContent = fmtUsd(sumaCosto);
            document.getElementById('resumenVentaSugerida').textContent = fmtUsd(sumaVenta);

            const inputPrecio = document.getElementById('precioFinalCombo');
            if (!precioEditadoManualmente) {
                inputPrecio.value = sumaVenta > 0 ? sumaVenta.toFixed(2) : '';
            }

            evaluarAlertaPrecio();
        }

        function usarPrecioSugerido() {
            precioEditadoManualmente = false;
            recalcularTotalesCombo();
        }

        function evaluarAlertaPrecio() {
            let sumaCosto = 0;
            itemsSeleccionados.forEach(it => {
                sumaCosto += valorItem(it, 'costo_unitario') * (Number(it.cantidad) || 0);
            });
            sumaCosto = Number(sumaCosto.toFixed(2));

            const precioFinal = parseFloat(document.getElementById('precioFinalCombo').value) || 0;
            const ganancia = precioFinal - sumaCosto;

            const elGanancia = document.getElementById('resumenGanancia');
            elGanancia.textContent = (ganancia >= 0 ? '+' : '') + fmtUsd(ganancia);
            elGanancia.style.color = ganancia < 0 ? '#ef5a6f' : 'var(--dash-mint)';

            const alerta = document.getElementById('alertaBajoCosto');
            if (itemsSeleccionados.length > 0 && precioFinal > 0 && precioFinal < sumaCosto) {
                document.getElementById('textoCostoAlerta').textContent = fmtUsd(sumaCosto);
                alerta.style.display = 'flex';
            } else {
                alerta.style.display = 'none';
            }
        }

        document.getElementById('precioFinalCombo').addEventListener('input', function() {
            precioEditadoManualmente = true;
            evaluarAlertaPrecio();
        });

        document.getElementById('buscadorProductos').addEventListener('input', renderResultadosBusqueda);

        async function guardarCombo() {
            const comboId = Number(document.getElementById('comboIdInput').value) || 0;
            const nombre = document.getElementById('nombreCombo').value.trim();
            const precioVenta = parseFloat(document.getElementById('precioFinalCombo').value) || 0;

            if (!nombre) {
                Swal.fire('Atención', 'Ingresa el nombre del combo.', 'warning');
                return;
            }
            if (itemsSeleccionados.length === 0) {
                Swal.fire('Atención', 'Selecciona al menos un producto para el combo.', 'warning');
                return;
            }
            if (precioVenta <= 0) {
                Swal.fire('Atención', 'Ingresa un precio final válido mayor a $0.', 'warning');
                return;
            }

            let sumaCosto = 0;
            itemsSeleccionados.forEach(it => {
                sumaCosto += valorItem(it, 'costo_unitario') * it.cantidad;
            });
            sumaCosto = Number(sumaCosto.toFixed(2));

            if (precioVenta < sumaCosto) {
                const conf = await Swal.fire({
                    icon: 'warning',
                    title: 'Precio menor al costo de compra',
                    html: `El precio final (<b>${fmtUsd(precioVenta)}</b>) es menor a la suma del costo de compra de los productos (<b>${fmtUsd(sumaCosto)}</b>).<br><br>¿Deseas guardar el combo de todas formas?`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar igual',
                    cancelButtonText: 'Corregir precio',
                    confirmButtonColor: '#ef5a6f'
                });
                if (!conf.isConfirmed) return;
            }

            fetch('../../configurar/combos_back.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: 'guardar_combo',
                    combo_id: comboId,
                    nombre: nombre,
                    precio_venta: precioVenta,
                    sucursal_id: getSucursalId(),
                    items: itemsSeleccionados.map(i => ({
                        producto_id: i.producto_id,
                        cantidad: i.cantidad
                    }))
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: res.message,
                        timer: 1600,
                        showConfirmButton: false
                    });
                    mostrarTablaCombos();
                    cargarCombos();
                } else {
                    Swal.fire('Error', res.message || 'No se pudo guardar el combo', 'error');
                }
            });
        }

        function resetFormulario() {
            document.getElementById('comboIdInput').value = '0';
            document.getElementById('nombreCombo').value = '';
            document.getElementById('precioFinalCombo').value = '';
            document.getElementById('tituloFormulario').textContent = 'Crear Nuevo Combo';
            document.getElementById('textoBtnGuardar').textContent = 'Guardar Combo';
            document.getElementById('btnCancelarEdicion').style.display = 'none';
            itemsSeleccionados = [];
            precioEditadoManualmente = false;
            renderItemsSeleccionados();
            renderResultadosBusqueda();
        }

        function cargarCombos() {
            fetch('../../configurar/combos_back.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: 'listar_combos',
                    sucursal_id: getSucursalId()
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    listaCombos = data.combos || [];
                    renderTablaCombos();
                }
            });
        }

        function renderTablaCombos() {
            const tbody = document.getElementById('tbodyCombos');
            const tabla = document.getElementById('tablaCombos');
            const vacio = document.getElementById('combosVacio');

            if (listaCombos.length === 0) {
                tabla.style.display = 'none';
                vacio.style.display = 'block';
                tbody.innerHTML = '';
                return;
            }

            tabla.style.display = '';
            vacio.style.display = 'none';

            let html = '';
            listaCombos.forEach(c => {
                const badgeClass = c.origen === 'c' ? 'col' : (c.origen === 'm' ? 'mix' : 'ven');
                const badgeText = c.origen === 'c' ? 'COL' : (c.origen === 'm' ? 'MIX' : 'VEN');

                let statusHtml = '<span class="status-pill ok"><ion-icon name="checkmark-circle-outline"></ion-icon> Al día</span>';
                if (c.alerta_perdida) {
                    statusHtml = '<span class="status-pill danger"><ion-icon name="alert-circle-outline"></ion-icon> Costo supera venta</span>';
                } else if (c.tiene_variacion) {
                    statusHtml = `<span class="status-pill warn"><ion-icon name="trending-up-outline"></ion-icon> ${c.items_con_cambio} prod. varió $</span>`;
                }

                const diffCostoStr = c.diff_costo_total !== 0
                    ? `<span class="${c.diff_costo_total > 0 ? 'diff-up' : 'diff-down'}">(${c.diff_costo_total > 0 ? '+' : ''}${fmtUsd(c.diff_costo_total)})</span>`
                    : '';

                html += `
                    <tr>
                        <td>
                            <div style="font-weight:700;">
                                <span class="badge-origin ${badgeClass}" style="margin-right:5px;">${badgeText}</span>
                                ${c.nombre}
                            </div>
                            <small style="color:var(--dash-text-muted);font-size:11px;">
                                Stock armable: <b>${c.stock_disponible}</b> | ${c.items.length} productos
                            </small>
                        </td>
                        <td>
                            <div style="font-size:12px;color:var(--dash-text-muted);">Reg: ${fmtUsd(c.costo_registrado)}</div>
                            <div style="font-weight:700;">Hoy: ${fmtUsd(c.costo_actual_hijos)} ${diffCostoStr}</div>
                        </td>
                        <td style="font-weight:600;color:var(--dash-info);">${fmtUsd(c.venta_actual_hijos)}</td>
                        <td>
                            <div style="font-weight:700;font-size:14px;color:var(--dash-mint);">${fmtUsd(c.precio_venta_combo)}</div>
                            <small style="color:${c.margen_actual_usd < 0 ? '#ef5a6f' : 'var(--dash-text-muted)'};font-size:11px;">
                                Margen hoy: ${fmtUsd(c.margen_actual_usd)}
                            </small>
                        </td>
                        <td>${statusHtml}</td>
                        <td style="text-align:right;white-space:nowrap;">
                            <button type="button" class="btn-ghost" style="padding:5px 9px;" title="Ver productos y seguimiento de precios" onclick="toggleDetalleCombo(${c.id})">
                                <ion-icon name="eye-outline"></ion-icon>
                            </button>
                            <button type="button" class="btn-ghost" style="padding:5px 9px;" title="Editar / Sincronizar costos actuales" onclick="editarCombo(${c.id})">
                                <ion-icon name="sync-outline"></ion-icon> Ajustar
                            </button>
                            <button type="button" class="btn-ghost" style="padding:5px 9px;color:#ef5a6f;border-color:rgba(239,90,111,0.3);" title="Eliminar combo" onclick="eliminarCombo(${c.id})">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        </td>
                    </tr>
                    <tr id="detalleCombo_${c.id}" class="combo-detail-row" style="display:none;">
                        <td colspan="6">
                            <div class="combo-detail-box">
                                <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--dash-text-muted);margin-bottom:8px;">
                                    Seguimiento de precios en $ de los productos que componen "${c.nombre}"
                                </div>
                                <table class="table-dash" style="background:transparent;">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Cant.</th>
                                            <th>Costo Unit. (Al crear → Hoy)</th>
                                            <th>Venta Unit. (Al crear → Hoy)</th>
                                            <th>Stock Actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${c.items.map(it => {
                                            const cCambio = it.diff_costo !== 0
                                                ? `<span class="${it.diff_costo > 0 ? 'diff-up' : 'diff-down'}">(${it.diff_costo > 0 ? '+' : ''}${fmtUsd(it.diff_costo)})</span>`
                                                : '<span style="color:var(--dash-text-muted);font-size:11px;">(Sin cambio)</span>';
                                            const vCambio = it.diff_venta !== 0
                                                ? `<span class="${it.diff_venta > 0 ? 'diff-up' : 'diff-down'}">(${it.diff_venta > 0 ? '+' : ''}${fmtUsd(it.diff_venta)})</span>`
                                                : '<span style="color:var(--dash-text-muted);font-size:11px;">(Sin cambio)</span>';
                                            return `
                                                <tr>
                                                    <td style="font-weight:600;">${it.nombre}</td>
                                                    <td>${it.cantidad}</td>
                                                    <td>${fmtUsd(it.costo_ref)} → <b>${fmtUsd(it.costo_actual)}</b> ${cCambio}</td>
                                                    <td>${fmtUsd(it.venta_ref)} → <b>${fmtUsd(it.venta_actual)}</b> ${vCambio}</td>
                                                    <td>${it.stock_actual}</td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        function toggleDetalleCombo(id) {
            const row = document.getElementById('detalleCombo_' + id);
            if (!row) return;
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }

        function editarCombo(id) {
            const combo = listaCombos.find(c => c.id === Number(id));
            if (!combo) return;

            mostrarFormularioCombo();

            document.getElementById('comboIdInput').value = combo.id;
            document.getElementById('nombreCombo').value = combo.nombre;
            document.getElementById('precioFinalCombo').value = Number(combo.precio_venta_combo).toFixed(2);
            precioEditadoManualmente = true;

            document.getElementById('tituloFormulario').textContent = `Editando / Sincronizando: ${combo.nombre}`;
            document.getElementById('textoBtnGuardar').textContent = 'Actualizar Combo y Sincronizar Costos';
            document.getElementById('btnCancelarEdicion').style.display = 'inline-block';

            itemsSeleccionados = combo.items.map(it => {
                const cat = catalogoProductos.find(p => p.id === Number(it.producto_id)) || {};
                return {
                    producto_id: it.producto_id,
                    nombre: it.nombre,
                    origen: cat.origen || combo.origen,
                    costo_unitario: Number(cat.costo_unitario ?? it.costo_actual),
                    venta_unitario: Number(cat.venta_unitario ?? it.venta_actual),
                    costo_unitario_bcv: Number(cat.costo_unitario_bcv ?? it.costo_actual),
                    venta_unitario_bcv: Number(cat.venta_unitario_bcv ?? it.venta_actual),
                    cantidad: Number(it.cantidad)
                };
            });

            renderItemsSeleccionados();
            renderResultadosBusqueda();
        }

        function eliminarCombo(id) {
            Swal.fire({
                title: '¿Eliminar este combo?',
                text: 'Los productos individuales que lo conforman no serán afectados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef5a6f'
            }).then(res => {
                if (!res.isConfirmed) return;
                fetch('../../configurar/combos_back.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        accion: 'eliminar_combo',
                        combo_id: id,
                        sucursal_id: getSucursalId()
                    })
                })
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'success') {
                        cargarCombos();
                    } else {
                        Swal.fire('Error', d.message, 'error');
                    }
                });
            });
        }

        const selSuc = document.getElementById('selectSucursal');
        if (selSuc) {
            selSuc.addEventListener('change', () => {
                resetFormulario();
                cargarCatalogoProductos();
                cargarCombos();
            });
        }

        cargarCatalogoProductos();
        cargarCombos();
    </script>
</body>

</html>
