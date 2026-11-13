<?php
require_once('includes/requires.php');
require_once('../../configurar/_ajustes_stock.php');

$tipo_u = (int) ($_SESSION['nivel'] ?? 2);
$bss_id = (int) ($_SESSION['bss_id'] ?? 0);

if ($tipo_u !== 1 && $tipo_u !== 2) {
    header('Location: ../../index.php');
    exit;
}

// Permiso explícito para empleados, igual que valor_stock.php y rendimiento_categoria.php.
if ($tipo_u === 2) {
    $tienePermiso = false;
    if (!empty($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        foreach ($_SESSION['permisos'] as $item) {
            if (is_array($item) && in_array('ajustes_stock.php', $item, true)) {
                $tienePermiso = true;
                break;
            }
            if ($item === 'ajustes_stock.php') {
                $tienePermiso = true;
                break;
            }
        }
    }
    if (!$tienePermiso) {
        header('Location: index.php');
        exit;
    }
}

$topnav = topnav();

// Sucursales: el admin ve todas; el empleado queda atado a la suya.
$sucursales = [];
if ($tipo_u === 1) {
    $stmtSuc = mysqli_prepare($conexion, "SELECT id, nombre, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC");
    if ($stmtSuc) {
        $stmtSuc->bind_param('i', $bss_id);
        $stmtSuc->execute();
        $resSuc = $stmtSuc->get_result();
        while ($rowSuc = $resSuc->fetch_assoc()) {
            $sucursales[] = $rowSuc;
        }
        $stmtSuc->close();
    }
} else {
    $sucursalFija = (int) ($_SESSION['sucursal'] ?? 0);
    $stmtSuc = mysqli_prepare($conexion, "SELECT id, nombre, principal FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
    if ($stmtSuc) {
        $stmtSuc->bind_param('ii', $sucursalFija, $bss_id);
        $stmtSuc->execute();
        $resSuc = $stmtSuc->get_result();
        if ($rowSuc = $resSuc->fetch_assoc()) {
            $sucursales[] = $rowSuc;
        }
        $stmtSuc->close();
    }
}

$sucursalFija = $tipo_u === 2 ? (int) ($_SESSION['sucursal'] ?? 0) : 0;
$sucursalNombre = $sucursales[0]['nombre'] ?? 'Sucursal asignada';

// Categorías que tienen productos en el negocio.
$categorias = [];
$stmtCats = mysqli_prepare(
    $conexion,
    "SELECT DISTINCT cp.id, cp.nombre, cp.grupo
     FROM categorias_producto cp
     INNER JOIN productos p ON p.categoria = cp.id
     WHERE p.bss_id = ? AND p.activo = 0
     ORDER BY cp.nombre ASC"
);
if ($stmtCats) {
    $stmtCats->bind_param('i', $bss_id);
    $stmtCats->execute();
    $resCats = $stmtCats->get_result();
    while ($rowCat = $resCats->fetch_assoc()) {
        $categorias[] = $rowCat;
    }
    $stmtCats->close();
}

// Grupos del catálogo para el filtro previo a categoría.
$grupos = [];
foreach ($categorias as $cat) {
    $grupoNombre = trim((string) ($cat['grupo'] ?? ''));
    if ($grupoNombre !== '') {
        $grupos[$grupoNombre] = $grupoNombre;
    }
}

$motivosMerma  = ajustes_motivos_merma();
$motivosAjuste = ajustes_motivos_ajuste();
$hoy           = date('Y-m-d');
$mesInicio     = date('Y-m-01');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Ajustes de stock y mermas | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <script src="../vendors/apexcharts/apexcharts.min.js"></script>
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .aj-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 20px;
        }

        .aj-header h3 {
            color: var(--dash-text);
            font-size: 21px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -.3px;
        }

        .aj-header p {
            color: var(--dash-text-muted);
            font-size: 13px;
            margin: 3px 0 0;
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
            transition: all .2s ease;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
            cursor: pointer;
        }

        .btn-mint:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
            color: #fff;
        }

        .btn-mint:disabled {
            opacity: .6;
            cursor: wait;
            transform: none;
        }

        .btn-ghost {
            background: rgba(255, 255, 255, .06);
            border: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            font-size: 12px;
            font-weight: 600;
            padding: 8px 13px;
            border-radius: 8px;
            cursor: pointer;
            transition: all .2s ease;
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, .1);
            color: var(--dash-text);
        }

        .btn-ghost:disabled {
            opacity: .35;
            cursor: default;
        }

        .aj-filters {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 18px;
        }

        .aj-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1 1 135px;
            min-width: 120px;
        }

        .aj-field.aj-field-wide {
            flex: 1 1 190px;
        }

        .aj-product-search {
            display: flex;
            gap: 6px;
        }

        .aj-product-search input {
            flex: 0 0 42%;
            min-width: 0;
        }

        .aj-product-search select {
            flex: 1 1 58%;
            min-width: 0;
        }

        .aj-field label {
            color: var(--dash-text-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .aj-input {
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

        textarea.aj-input {
            height: auto;
            min-height: 82px;
            resize: vertical;
        }

        .aj-input:focus {
            border-color: var(--dash-mint);
            outline: none;
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .aj-help {
            color: var(--dash-text-muted);
            display: block;
            font-size: 10.5px;
            line-height: 1.35;
            margin-top: 4px;
        }

        .aj-actions {
            display: flex;
            gap: 8px;
            padding-bottom: 1px;
        }

        .aj-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .aj-kpi {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 16px 18px;
            min-height: 116px;
        }

        .aj-kpi-label {
            color: var(--dash-text-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .aj-kpi-value {
            color: var(--dash-text);
            font-size: 25px;
            font-weight: 700;
            line-height: 1.2;
            margin-top: 8px;
            letter-spacing: -.5px;
        }

        .aj-kpi-value.danger {
            color: #ef5a6f;
        }

        .aj-kpi-value.mint {
            color: #2dd4a0;
        }

        .aj-kpi-sub {
            color: var(--dash-text-muted);
            font-size: 11.5px;
            margin-top: 6px;
            line-height: 1.45;
        }

        .aj-kpi-sub .up {
            color: #ef5a6f;
            font-weight: 600;
        }

        .aj-kpi-sub .down {
            color: #2dd4a0;
            font-weight: 600;
        }

        .aj-grid {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        .aj-panel {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            overflow: hidden;
        }

        .aj-panel-header {
            align-items: center;
            border-bottom: 1px solid var(--dash-border);
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 13px 16px;
        }

        .aj-panel-header h6 {
            color: var(--dash-text);
            font-size: 13px;
            font-weight: 600;
            margin: 0;
        }

        .aj-badge {
            background: rgba(255, 255, 255, .07);
            border-radius: 20px;
            color: var(--dash-text-muted);
            display: inline-block;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .2px;
            padding: 3px 9px;
            white-space: nowrap;
        }

        .aj-badge.salida {
            background: rgba(239, 90, 111, .14);
            color: #ef5a6f;
        }

        .aj-badge.entrada {
            background: rgba(45, 212, 160, .14);
            color: #2dd4a0;
        }

        .aj-badge.ok {
            background: rgba(45, 212, 160, .12);
            color: #2dd4a0;
        }

        .aj-badge.anulado {
            background: rgba(136, 146, 160, .15);
            color: var(--dash-text-muted);
        }

        .aj-panel-body {
            padding: 12px 14px;
        }

        .aj-chart {
            min-height: 300px;
        }

        .aj-toggle {
            display: inline-flex;
            background: rgba(255, 255, 255, .05);
            border-radius: 8px;
            padding: 3px;
            gap: 3px;
        }

        .aj-toggle button {
            background: transparent;
            border: none;
            border-radius: 6px;
            color: var(--dash-text-muted);
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 10px;
        }

        .aj-toggle button.active {
            background: rgba(45, 212, 160, .16);
            color: #2dd4a0;
        }

        .aj-table-wrap {
            overflow-x: auto;
        }

        .aj-table {
            border-collapse: collapse;
            font-size: 12.5px;
            width: 100%;
        }

        .aj-table thead th {
            background: transparent;
            border-bottom: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .4px;
            padding: 10px 12px;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .aj-table tbody td {
            border-bottom: 1px solid rgba(46, 53, 62, .45);
            color: var(--dash-text);
            padding: 10px 12px;
            vertical-align: middle;
        }

        .aj-table tbody tr:last-child td {
            border-bottom: none;
        }

        .aj-table tbody tr:hover {
            background: rgba(45, 212, 160, .03);
        }

        .aj-table small {
            color: var(--dash-text-muted);
            font-size: 10.5px;
        }

        .aj-table .money {
            color: #ef5a6f;
            font-weight: 700;
            white-space: nowrap;
        }

        .aj-table .money.entrada {
            color: #2dd4a0;
        }

        .aj-action {
            background: transparent;
            border: 1px solid var(--dash-border);
            border-radius: 6px;
            color: var(--dash-text-muted);
            cursor: pointer;
            font-size: 11px;
            padding: 4px 8px;
            transition: all .2s ease;
        }

        .aj-action:hover {
            border-color: #ef5a6f;
            color: #ef5a6f;
        }

        .aj-empty {
            color: var(--dash-text-muted);
            padding: 28px 14px;
            text-align: center;
        }

        .aj-pagination {
            align-items: center;
            border-top: 1px solid var(--dash-border);
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 16px;
            flex-wrap: wrap;
        }

        .aj-pagination span {
            color: var(--dash-text-muted);
            font-size: 11.5px;
        }

        .aj-rank {
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .aj-rank-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .aj-rank-pos {
            align-items: center;
            background: rgba(255, 255, 255, .06);
            border-radius: 6px;
            color: var(--dash-text-muted);
            display: inline-flex;
            font-size: 10px;
            font-weight: 700;
            height: 22px;
            justify-content: center;
            min-width: 22px;
        }

        .aj-rank-main {
            flex: 1;
            min-width: 0;
        }

        .aj-rank-name {
            color: var(--dash-text);
            display: block;
            font-size: 12px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .aj-rank-bar {
            background: rgba(255, 255, 255, .06);
            border-radius: 4px;
            height: 4px;
            margin-top: 5px;
            overflow: hidden;
        }

        .aj-rank-bar span {
            background: #ef5a6f;
            border-radius: 4px;
            display: block;
            height: 100%;
        }

        .aj-rank-value {
            color: #ef5a6f;
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .aj-product-box {
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            color: var(--dash-text-muted);
            font-size: 12px;
            line-height: 1.6;
            padding: 10px 12px;
        }

        .aj-product-box strong {
            color: var(--dash-text);
        }

        .aj-preview {
            align-items: baseline;
            background: rgba(239, 90, 111, .07);
            border: 1px solid rgba(239, 90, 111, .2);
            border-radius: 8px;
            color: var(--dash-text-muted);
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 12px;
            font-size: 12px;
        }

        .aj-preview strong {
            color: #ef5a6f;
            font-size: 18px;
        }

        .aj-preview small {
            width: 100%;
        }

        .aj-modal {
            background: var(--dash-card) !important;
            border: 1px solid var(--dash-border) !important;
            border-radius: 14px !important;
            color: var(--dash-text);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
        }

        .aj-modal .modal-header,
        .aj-modal .modal-footer {
            border-color: var(--dash-border);
            padding: 14px 20px;
        }

        .aj-modal .modal-body {
            padding: 16px 20px 20px;
        }

        .aj-modal .modal-title {
            color: var(--dash-text);
            font-size: 15px;
            font-weight: 600;
        }

        .aj-modal .close {
            color: var(--dash-text-muted);
            opacity: .7;
            text-shadow: none;
        }

        .aj-modal label {
            color: var(--dash-text-muted);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .3px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .aj-modal .row>[class*="col-"] {
            margin-bottom: 4px;
        }

        @media (max-width: 1100px) {
            .aj-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .aj-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .right_col {
                padding: 16px !important;
            }

            .aj-kpis {
                grid-template-columns: 1fr;
            }

            .aj-filters {
                flex-direction: column;
                align-items: stretch;
            }

            .aj-actions {
                justify-content: stretch;
            }

            .aj-actions button {
                flex: 1;
            }

            .aj-table thead {
                display: none;
            }

            .aj-table,
            .aj-table tbody,
            .aj-table tr,
            .aj-table td {
                display: block;
                width: 100%;
            }

            .aj-table tr {
                border-bottom: 1px solid var(--dash-border);
                padding: 6px 0;
            }

            .aj-table tbody td {
                border: none;
                display: flex;
                justify-content: space-between;
                gap: 12px;
                padding: 4px 12px;
                text-align: right;
            }

            .aj-table tbody td::before {
                color: var(--dash-text-muted);
                content: attr(data-label);
                font-size: 10.5px;
                font-weight: 600;
                letter-spacing: .3px;
                text-align: left;
                text-transform: uppercase;
            }

            .aj-table tbody td.aj-td-empty {
                display: block;
                text-align: center;
            }

            .aj-table tbody td.aj-td-empty::before {
                content: none;
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
                <div class="aj-header">
                    <div>
                    </div>
                    <button type="button" class="btn-mint" id="btnNuevoRegistro">
                        <ion-icon name="add-outline" style="font-size:16px;"></ion-icon>
                        Registrar merma
                    </button>
                </div>

                <div class="aj-filters">
                    <?php if ($tipo_u === 1): ?>
                        <div class="aj-field">
                            <label for="filtroSucursal">Sucursal</label>
                            <select id="filtroSucursal" class="aj-input">
                                <option value="0">Todas las sucursales</option>
                                <?php foreach ($sucursales as $suc): ?>
                                    <option value="<?= (int) $suc['id'] ?>">
                                        <?= htmlspecialchars($suc['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="aj-field">
                            <label>Sucursal</label>
                            <input type="text" class="aj-input" value="<?= htmlspecialchars($sucursalNombre, ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                    <?php endif; ?>

                    <div class="aj-field">
                        <label for="filtroDesde">Desde</label>
                        <input type="date" id="filtroDesde" class="aj-input" value="<?= $mesInicio ?>" max="<?= $hoy ?>">
                    </div>
                    <div class="aj-field">
                        <label for="filtroHasta">Hasta</label>
                        <input type="date" id="filtroHasta" class="aj-input" value="<?= $hoy ?>" max="<?= $hoy ?>">
                    </div>
                    <div class="aj-field aj-field-wide">
                        <label for="filtroGrupo">Grupo</label>
                        <select id="filtroGrupo" class="aj-input">
                            <option value="">Todos los grupos</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <option value="<?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="aj-field aj-field-wide">
                        <label for="filtroCategoria">Categoría</label>
                        <select id="filtroCategoria" class="aj-input">
                            <option value="0">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="aj-field aj-field-wide">
                        <label for="filtroProducto">Producto</label>
                        <div class="aj-product-search">
                            <input type="text" id="filtroProductoBuscar" class="aj-input" placeholder="Buscar...">
                            <select id="filtroProducto" class="aj-input">
                                <option value="0">Todos los productos</option>
                            </select>
                        </div>
                    </div>
                    <div class="aj-field">
                        <label for="filtroTipo">Tipo</label>
                        <select id="filtroTipo" class="aj-input">
                            <option value="TODAS">Todas</option>
                            <option value="MERMA">Mermas</option>
                            <option value="AJUSTE">Ajustes</option>
                        </select>
                    </div>
                    <div class="aj-actions">
                        <button type="button" class="btn-ghost" id="btnSemana">Semana</button>
                        <button type="button" class="btn-ghost" id="btnMes">Mes</button>
                    </div>
                </div>

                <div class="aj-kpis">
                    <div class="aj-kpi">
                        <div class="aj-kpi-label">Pérdida esta semana</div>
                        <div class="aj-kpi-value danger" id="kpiSemana">$0.00</div>
                        <div class="aj-kpi-sub" id="kpiSemanaDetalle">Mermas y ajustes negativos</div>
                    </div>
                    <div class="aj-kpi">
                        <div class="aj-kpi-label">Pérdida este mes</div>
                        <div class="aj-kpi-value danger" id="kpiMes">$0.00</div>
                        <div class="aj-kpi-sub" id="kpiMesDetalle">Sin datos</div>
                    </div>
                    <div class="aj-kpi">
                        <div class="aj-kpi-label">Unidades perdidas este mes</div>
                        <div class="aj-kpi-value" id="kpiUnidades">0</div>
                        <div class="aj-kpi-sub" id="kpiUnidadesDetalle">Sin registros</div>
                    </div>
                    <div class="aj-kpi">
                        <div class="aj-kpi-label">Categoría con mayor pérdida</div>
                        <div class="aj-kpi-value" id="kpiCategoria" style="font-size:18px;">—</div>
                        <div class="aj-kpi-sub" id="kpiCategoriaDetalle">En el periodo seleccionado</div>
                    </div>
                </div>

                <div class="aj-grid">
                    <div class="aj-panel">
                        <div class="aj-panel-header">
                            <h6>Evolución de la pérdida</h6>
                            <div class="aj-toggle">
                                <button type="button" class="active" data-vista="semanal">Semanal</button>
                                <button type="button" data-vista="mensual">Mensual</button>
                            </div>
                        </div>
                        <div class="aj-panel-body">
                            <div id="chartPeriodo" class="aj-chart"></div>
                        </div>
                    </div>

                    <div class="aj-panel">
                        <div class="aj-panel-header">
                            <h6>Pérdida por categoría</h6>
                            <span class="aj-badge">Periodo consultado</span>
                        </div>
                        <div class="aj-panel-body">
                            <div id="chartCategoria" class="aj-chart"></div>
                        </div>
                    </div>
                </div>

                <div class="aj-grid">
                    <div class="aj-panel">
                        <div class="aj-panel-header">
                            <h6>Pérdida por sucursal</h6>
                            <span class="aj-badge">Periodo consultado</span>
                        </div>
                        <div class="aj-panel-body">
                            <div id="chartSucursal" class="aj-chart"></div>
                        </div>
                    </div>

                    <div class="aj-panel">
                        <div class="aj-panel-header">
                            <h6>Top productos con pérdida</h6>
                            <span class="aj-badge">Periodo consultado</span>
                        </div>
                        <div class="aj-panel-body">
                            <div class="aj-rank" id="listaTopProductos">
                                <div class="aj-empty" style="padding:16px 0;">Sin datos</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="aj-panel">
                    <div class="aj-panel-header">
                        <h6>Movimientos registrados</h6>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <span class="aj-badge" id="badgeTotal">0 registros</span>
                            <span class="aj-badge" id="badgePeriodo"></span>
                        </div>
                    </div>
                    <div class="aj-table-wrap">
                        <table class="aj-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Sucursal</th>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Motivo</th>
                                    <th>Razón</th>
                                    <th>Cantidad</th>
                                    <th>Costo</th>
                                    <th>Valor ($)</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaMovimientos">
                                <tr>
                                    <td colspan="12" class="aj-empty aj-td-empty">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="aj-pagination">
                        <span id="paginaInfo">Mostrando 0-0 de 0</span>
                        <div style="display:flex;gap:8px;">
                            <button type="button" class="btn-ghost" id="paginaAnterior" disabled>Anterior</button>
                            <button type="button" class="btn-ghost" id="paginaSiguiente" disabled>Siguiente</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal registrar -->
    <div class="modal fade" id="modalRegistro" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content aj-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar merma o ajuste</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formRegistro" autocomplete="off">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="regTipo">Tipo *</label>
                                <select id="regTipo" class="aj-input" required>
                                    <option value="MERMA">Merma (pérdida)</option>
                                    <option value="AJUSTE">Ajuste por conteo</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="regSucursal">Sucursal *</label>
                                <?php if ($tipo_u === 1): ?>
                                    <select id="regSucursal" class="aj-input" required>
                                        <?php foreach ($sucursales as $suc): ?>
                                            <option value="<?= (int) $suc['id'] ?>">
                                                <?= htmlspecialchars($suc['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" class="aj-input" value="<?= htmlspecialchars($sucursalNombre, ENT_QUOTES, 'UTF-8') ?>" readonly>
                                    <input type="hidden" id="regSucursal" value="<?= (int) $sucursalFija ?>">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="regFecha">Fecha *</label>
                                <input type="date" id="regFecha" class="aj-input" value="<?= $hoy ?>" max="<?= $hoy ?>" required>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="regProductoFiltro">Filtrar producto</label>
                                <input type="text" id="regProductoFiltro" class="aj-input" placeholder="Escriba el nombre del producto...">
                            </div>
                            <div class="col-md-6">
                                <label for="regProducto">Producto *</label>
                                <select id="regProducto" class="aj-input" required>
                                    <option value="">-- Escriba para filtrar --</option>
                                </select>
                            </div>
                        </div>

                        <div class="aj-product-box mt-3" id="regProductoInfo">
                            Seleccione un producto para ver su stock actual y su costo unitario.
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4" id="regCampoCantidad">
                                <label for="regCantidad">Cantidad *</label>
                                <input type="number" id="regCantidad" class="aj-input" min="0.0001" step="any" placeholder="Unidades o kg perdidos">
                                <small class="aj-help">En productos por peso, registre los kg perdidos. Se admiten hasta 4 decimales.</small>
                            </div>
                            <div class="col-md-4" id="regCampoStockReal" style="display:none;">
                                <label for="regStockReal">Stock real *</label>
                                <input type="number" id="regStockReal" class="aj-input" min="0" step="any" placeholder="Resultado del conteo">
                                <small class="aj-help">Use la misma unidad del inventario (kg o unidades).</small>
                            </div>
                            <div class="col-md-4">
                                <label for="regMotivo">Motivo *</label>
                                <select id="regMotivo" class="aj-input" required></select>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label for="regRazon">Razón / explicación *</label>
                                <textarea id="regRazon" class="aj-input" rows="3" maxlength="500" required placeholder="¿Por qué se produjo la merma o el descuadre?"></textarea>
                            </div>
                        </div>

                        <div class="aj-preview mt-3">
                            <span>Valor estimado:</span>
                            <strong id="regPreview">$0.00</strong>
                            <small id="regPreviewDetalle">Seleccione un producto e indique la cantidad.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-mint" id="regGuardar">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal anular -->
    <div class="modal fade" id="modalAnular" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content aj-modal">
                <div class="modal-header">
                    <h5 class="modal-title">Anular registro</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formAnular" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" id="anularId" value="">
                        <div class="aj-product-box mb-3" id="anularInfo"></div>
                        <label for="anularMotivo">Motivo de anulación *</label>
                        <textarea id="anularMotivo" class="aj-input" rows="3" maxlength="500" required placeholder="Explique por qué se anula este registro"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ghost" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-mint" id="anularGuardar">Anular y revertir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/fuse/fuse.min.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        const API_URL = 'db/ajustes_stock_api.php';
        const ES_ADMIN = <?= $tipo_u === 1 ? 'true' : 'false' ?>;
        const SUCURSAL_FIJA = <?= (int) $sucursalFija ?>;
        const HOY = '<?= $hoy ?>';
        const MOTIVOS = <?= json_encode(['MERMA' => $motivosMerma, 'AJUSTE' => $motivosAjuste], JSON_UNESCAPED_UNICODE) ?>;
        const MOTIVO_LABEL = Object.assign({}, MOTIVOS.MERMA, MOTIVOS.AJUSTE);
        const SUCURSALES = <?= json_encode($sucursales, JSON_UNESCAPED_UNICODE) ?>;
        const CATEGORIAS = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;

        // El stock se guarda con 4 decimales, pero antes se mostraba con 2.
        // Si el usuario escribía el valor redondeado (10.47 sobre 10.465),
        // el sistema lo rechazaba. Este margen absorbe esa diferencia.
        const TOLERANCIA_STOCK = 0.0051;

        let PRODUCTOS = [];
        let PRODUCTOS_REG = [];
        let FUSE_FILTRO = null;
        let FUSE_REG = null;
        let PRODUCTO_REG = null;
        let ULTIMO_RESUMEN = null;
        let PAGINA_ACTUAL = 1;
        let TOTAL_REGISTROS = 0;
        let VISTA_PERIODO = 'semanal';
        let chartPeriodo = null;
        let chartCategoria = null;
        let chartSucursal = null;

        const $id = (id) => document.getElementById(id);

        function money(valor) {
            return '$' + (Number(valor) || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function numero(valor, decimales) {
            return (Number(valor) || 0).toLocaleString('en-US', {
                maximumFractionDigits: decimales === undefined ? 2 : decimales
            });
        }

        function esc(texto) {
            return String(texto == null ? '' : texto).replace(/[&<>"']/g, function(c) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c];
            });
        }

        function truncar(texto, largo) {
            const valor = String(texto == null ? '' : texto);
            return valor.length > largo ? valor.slice(0, largo - 1) + '…' : valor;
        }

        function toast(icono, titulo) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: icono,
                title: titulo,
                showConfirmButton: false,
                timer: 2800,
                timerProgressBar: true
            });
        }

        async function api(datos) {
            const cuerpo = new URLSearchParams();
            Object.keys(datos).forEach(function(clave) {
                cuerpo.append(clave, datos[clave] == null ? '' : datos[clave]);
            });
            const respuesta = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: cuerpo.toString()
            });
            let json;
            try {
                json = await respuesta.json();
            } catch (e) {
                throw new Error('El servidor devolvió una respuesta no válida.');
            }
            if (!json.ok) {
                throw new Error(json.message || 'Error del servidor.');
            }
            return json;
        }

        function sucursalActual() {
            if (!ES_ADMIN) return SUCURSAL_FIJA;
            const select = $id('filtroSucursal');
            return select ? parseInt(select.value, 10) || 0 : 0;
        }

        function sucursalRegistro() {
            if (!ES_ADMIN) return SUCURSAL_FIJA;
            return parseInt($id('regSucursal').value, 10) || 0;
        }

        function filtrosActuales() {
            return {
                sucursal: sucursalActual(),
                desde: $id('filtroDesde').value,
                hasta: $id('filtroHasta').value,
                grupo: $id('filtroGrupo').value,
                categoria_id: parseInt($id('filtroCategoria').value, 10) || 0,
                producto_id: parseInt($id('filtroProducto').value, 10) || 0,
                tipo: $id('filtroTipo').value
            };
        }

        function poblarCategorias(grupo) {
            const select = $id('filtroCategoria');
            const valorActual = select.value;
            const filtradas = CATEGORIAS.filter(function(categoria) {
                return !grupo || String(categoria.grupo || '') === grupo;
            });

            select.innerHTML = '<option value="0">Todas las categorías</option>' +
                filtradas.map(function(categoria) {
                    return '<option value="' + categoria.id + '">' + esc(categoria.nombre) + '</option>';
                }).join('');

            if (filtradas.some(function(categoria) {
                    return String(categoria.id) === String(valorActual);
                })) {
                select.value = valorActual;
            }
        }

        function formatoPeriodo(periodo) {
            if (VISTA_PERIODO === 'semanal') {
                const partes = String(periodo).split('-W');
                return partes.length === 2 ? 'Sem ' + partes[1] : periodo;
            }
            const partes = String(periodo).split('-');
            if (partes.length !== 2) return periodo;
            const fecha = new Date(parseInt(partes[0], 10), parseInt(partes[1], 10) - 1, 1);
            return fecha.toLocaleDateString('es-ES', {
                month: 'short',
                year: 'numeric'
            });
        }

        function opcionesBaseChart() {
            return {
                chart: {
                    background: 'transparent',
                    foreColor: '#8892a0',
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit'
                },
                theme: {
                    mode: 'dark'
                },
                dataLabels: {
                    enabled: false
                },
                grid: {
                    borderColor: 'rgba(46,53,62,.7)',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: 'dark'
                }
            };
        }

        function renderPeriodo() {
            const contenedor = $id('chartPeriodo');
            if (!ULTIMO_RESUMEN) return;
            const serieOriginal = VISTA_PERIODO === 'semanal' ? ULTIMO_RESUMEN.serie_semanal : ULTIMO_RESUMEN.serie_mensual;

            // Orden cronológico ascendente: la semana/mes más antiguo a la
            // izquierda y el más reciente a la derecha. Así no depende del
            // orden en que el backend devuelva la serie.
            const serie = (serieOriginal || []).slice().sort(function(a, b) {
                return String(a.periodo).localeCompare(String(b.periodo));
            });

            const categorias = serie.map(function(item) {
                return formatoPeriodo(item.periodo);
            });
            const valores = serie.map(function(item) {
                return Number(item.monto_usd) || 0;
            });

            if (chartPeriodo) {
                chartPeriodo.destroy();
                chartPeriodo = null;
            }
            contenedor.innerHTML = '';
            chartPeriodo = new ApexCharts(contenedor, Object.assign(opcionesBaseChart(), {
                chart: Object.assign(opcionesBaseChart().chart, {
                    type: 'bar',
                    height: 300
                }),
                series: [{
                    name: 'Pérdida',
                    data: valores
                }],
                colors: ['#ef5a6f'],
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '46%'
                    }
                },
                xaxis: {
                    categories: categorias,
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function(valor) {
                            return '$' + Number(valor).toFixed(0);
                        },
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(valor) {
                            return money(valor);
                        }
                    }
                }
            }));
            chartPeriodo.render();
        }

        function renderCategoria() {
            const contenedor = $id('chartCategoria');
            if (chartCategoria) {
                chartCategoria.destroy();
                chartCategoria = null;
            }
            contenedor.innerHTML = '';
            const datos = (ULTIMO_RESUMEN && ULTIMO_RESUMEN.por_categoria) || [];
            if (!datos.length) {
                contenedor.innerHTML = '<div class="aj-empty">Sin pérdidas en el periodo</div>';
                return;
            }
            chartCategoria = new ApexCharts(contenedor, Object.assign(opcionesBaseChart(), {
                chart: Object.assign(opcionesBaseChart().chart, {
                    type: 'donut',
                    height: 300
                }),
                series: datos.map(function(item) {
                    return Number(item.monto_usd) || 0;
                }),
                labels: datos.map(function(item) {
                    return item.categoria;
                }),
                colors: ['#ef5a6f', '#f5803e', '#f5c26b', '#2dd4a0', '#27bcfd', '#727cf5', '#ff679b', '#8892a0'],
                legend: {
                    position: 'bottom',
                    fontSize: '11px',
                    labels: {
                        colors: '#8892a0'
                    }
                },
                stroke: {
                    colors: ['#181b22']
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(valor) {
                            return money(valor);
                        }
                    }
                }
            }));
            chartCategoria.render();
        }

        function renderSucursal() {
            const contenedor = $id('chartSucursal');
            if (chartSucursal) {
                chartSucursal.destroy();
                chartSucursal = null;
            }
            contenedor.innerHTML = '';
            const datos = (ULTIMO_RESUMEN && ULTIMO_RESUMEN.por_sucursal) || [];
            if (!datos.length) {
                contenedor.innerHTML = '<div class="aj-empty">Sin pérdidas en el periodo</div>';
                return;
            }
            if (!ES_ADMIN || datos.length < 2) {
                contenedor.innerHTML = '<div class="aj-empty">No hay varias sucursales con pérdidas en el periodo.</div>';
                return;
            }
            chartSucursal = new ApexCharts(contenedor, Object.assign(opcionesBaseChart(), {
                chart: Object.assign(opcionesBaseChart().chart, {
                    type: 'bar',
                    height: 300
                }),
                series: [{
                    name: 'Pérdida',
                    data: datos.map(function(item) {
                        return Number(item.monto_usd) || 0;
                    })
                }],
                colors: ['#f5803e'],
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%'
                    }
                },
                xaxis: {
                    categories: datos.map(function(item) {
                        return item.sucursal;
                    }),
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#8892a0',
                            fontSize: '11px'
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function(valor) {
                            return money(valor);
                        }
                    }
                }
            }));
            chartSucursal.render();
        }

        function renderTopProductos() {
            const contenedor = $id('listaTopProductos');
            const datos = (ULTIMO_RESUMEN && ULTIMO_RESUMEN.top_productos) || [];
            if (!datos.length) {
                contenedor.innerHTML = '<div class="aj-empty" style="padding:16px 0;">Sin datos</div>';
                return;
            }
            const maximo = Math.max.apply(null, datos.map(function(item) {
                return Number(item.monto_usd) || 0;
            })) || 1;

            contenedor.innerHTML = datos.map(function(item, indice) {
                const ancho = (Number(item.monto_usd) || 0) / maximo * 100;
                return '<div class="aj-rank-item">' +
                    '<span class="aj-rank-pos">' + (indice + 1) + '</span>' +
                    '<div class="aj-rank-main">' +
                    '<span class="aj-rank-name" title="' + esc(item.producto) + '">' + esc(item.producto) + '</span>' +
                    '<div class="aj-rank-bar"><span style="width:' + ancho + '%"></span></div>' +
                    '</div>' +
                    '<span class="aj-rank-value">' + money(item.monto_usd) + '</span>' +
                    '</div>';
            }).join('');
        }

        function renderKpis() {
            if (!ULTIMO_RESUMEN) return;
            const kpis = ULTIMO_RESUMEN.kpis || {};
            const variacion = Number(kpis.variacion_mes_pct) || 0;
            const claseVariacion = variacion > 0 ? 'up' : (variacion < 0 ? 'down' : '');
            const signo = variacion > 0 ? '+' : '';

            $id('kpiSemana').textContent = money(kpis.perdida_semana_usd);
            $id('kpiSemanaDetalle').innerHTML = 'Mermas y ajustes negativos acumulados';

            $id('kpiMes').textContent = money(kpis.perdida_mes_usd);
            $id('kpiMesDetalle').innerHTML =
                'Mermas: ' + money(kpis.merma_mes_usd) +
                ' · Ajustes: ' + money(kpis.ajuste_negativo_mes_usd) +
                ' · <span class="' + claseVariacion + '">' + signo + variacion.toFixed(1) + '% vs mes anterior</span>';

            $id('kpiUnidades').textContent = numero(kpis.unidades_mes, 2);
            $id('kpiUnidadesDetalle').textContent = (Number(kpis.registros_mes) || 0) + ' registros de pérdida';

            const categorias = ULTIMO_RESUMEN.por_categoria || [];
            if (categorias.length) {
                $id('kpiCategoria').textContent = categorias[0].categoria;
                $id('kpiCategoriaDetalle').textContent = money(categorias[0].monto_usd) + ' en el periodo';
            } else {
                $id('kpiCategoria').textContent = '—';
                $id('kpiCategoriaDetalle').textContent = 'Sin pérdidas en el periodo';
            }
        }

        function renderPaginacion(total) {
            TOTAL_REGISTROS = total;
            const desde = total === 0 ? 0 : ((PAGINA_ACTUAL - 1) * 50) + 1;
            const hasta = Math.min(PAGINA_ACTUAL * 50, total);
            $id('paginaInfo').textContent = 'Mostrando ' + desde + '-' + hasta + ' de ' + total;
            $id('paginaAnterior').disabled = PAGINA_ACTUAL <= 1;
            $id('paginaSiguiente').disabled = hasta >= total;
        }

        function filaMovimiento(fila) {
            const esSalida = fila.direccion === 'SALIDA';
            const badgeDireccion = '<span class="aj-badge ' + (esSalida ? 'salida' : 'entrada') + '">' +
                (esSalida ? 'Pérdida' : 'Sobrante') + '</span>';
            const estado = fila.estado === 'ACTIVO' ?
                '<span class="aj-badge ok">Activo</span>' :
                '<span class="aj-badge anulado">Anulado</span>';
            const accion = fila.estado === 'ACTIVO' ?
                '<button type="button" class="aj-action" data-id="' + fila.id + '" data-producto="' + esc(fila.producto) + '" data-fecha="' + fila.fecha + '" data-monto="' + fila.monto_usd + '" data-usuario="' + esc(fila.usuario) + '" data-razon="' + esc(fila.razon) + '">Anular</button>' :
                '—';

            return '<tr>' +
                '<td data-label="Fecha">' + esc(fila.fecha) + '</td>' +
                '<td data-label="Sucursal">' + esc(fila.sucursal) + '</td>' +
                '<td data-label="Producto"><strong>' + esc(fila.producto) + '</strong><br><small>' + esc(fila.categoria) + '</small></td>' +
                '<td data-label="Tipo">' + (fila.tipo === 'MERMA' ? 'Merma' : 'Ajuste') + '</td>' +
                '<td data-label="Motivo">' + esc(MOTIVO_LABEL[fila.motivo] || fila.motivo) + '</td>' +
                '<td data-label="Razón"><span title="' + esc(fila.razon) + '">' + esc(truncar(fila.razon, 52)) + '</span></td>' +
                '<td data-label="Cantidad">' + numero(fila.cantidad, 2) + ' ' + badgeDireccion + '</td>' +
                '<td data-label="Costo">' + money(fila.costo_unitario) + '</td>' +
                '<td data-label="Valor pérdida" class="money ' + (esSalida ? '' : 'entrada') + '">' + money(fila.monto_usd) + '</td>' +
                '<td data-label="Usuario">' + esc(fila.usuario) + '</td>' +
                '<td data-label="Estado" title="' + (fila.estado === 'ANULADO' ? esc(fila.motivo_anulacion || '') : '') + '">' + estado + '</td>' +
                '<td data-label="Acciones">' + accion + '</td>' +
                '</tr>';
        }

        async function cargarResumen() {
            const datos = Object.assign({
                accion: 'resumen'
            }, filtrosActuales());
            ULTIMO_RESUMEN = await api(datos);
            renderKpis();
            renderPeriodo();
            renderCategoria();
            renderSucursal();
            renderTopProductos();
        }

        async function cargarTabla(pagina) {
            PAGINA_ACTUAL = pagina || 1;
            const datos = Object.assign({
                accion: 'listar',
                pagina: PAGINA_ACTUAL
            }, filtrosActuales());
            const respuesta = await api(datos);
            const tbody = $id('tablaMovimientos');
            const filas = respuesta.filas || [];

            if (!filas.length) {
                tbody.innerHTML = '<tr><td colspan="12" class="aj-empty aj-td-empty">No hay movimientos con los filtros seleccionados.</td></tr>';
            } else {
                tbody.innerHTML = filas.map(filaMovimiento).join('');
            }

            $id('badgeTotal').textContent = (respuesta.total || 0) + ' registros';
            $id('badgePeriodo').textContent = $id('filtroDesde').value + ' a ' + $id('filtroHasta').value;
            renderPaginacion(respuesta.total || 0);
        }

        async function cargarTodo() {
            try {
                await Promise.all([cargarResumen(), cargarTabla(1)]);
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudieron cargar los datos',
                    text: error.message
                });
            }
        }

        function pintarProductosFiltro(lista, valorActual) {
            const select = $id('filtroProducto');
            select.innerHTML = '<option value="0">Todos los productos</option>' +
                lista.map(function(producto) {
                    return '<option value="' + producto.id + '">' + esc(producto.nombre) + '</option>';
                }).join('');

            if (valorActual && lista.some(function(producto) {
                    return String(producto.id) === String(valorActual);
                })) {
                select.value = valorActual;
            }
        }

        async function cargarProductosFiltro() {
            const sucursal = sucursalActual();
            const respuesta = await api({
                accion: 'productos',
                sucursal: sucursal
            });
            PRODUCTOS = respuesta.data || [];
            FUSE_FILTRO = new Fuse(PRODUCTOS, {
                keys: ['nombre'],
                threshold: 0.3,
                ignoreLocation: true,
                ignoreDiacritics: true,
                useExtendedSearch: true
            });

            const buscador = $id('filtroProductoBuscar');
            const valorActual = $id('filtroProducto').value;
            if (buscador) buscador.value = '';
            pintarProductosFiltro(PRODUCTOS, valorActual);
        }

        function buscarProductoFiltro(texto) {
            const busqueda = String(texto || '').trim();
            const teniaProducto = $id('filtroProducto').value !== '0';

            let lista = PRODUCTOS;
            if (busqueda !== '') {
                if (FUSE_FILTRO) {
                    lista = FUSE_FILTRO.search(busqueda).slice(0, 200).map(function(resultado) {
                        return resultado.item;
                    });
                } else {
                    lista = PRODUCTOS.filter(function(producto) {
                        return producto.nombre.toLowerCase().indexOf(busqueda.toLowerCase()) !== -1;
                    }).slice(0, 200);
                }
            }

            pintarProductosFiltro(lista, '0');
            $id('filtroProducto').value = '0';

            if (teniaProducto) {
                cargarTodo();
            }
        }

        async function cargarProductosRegistro() {
            const sucursal = sucursalRegistro();
            if (!sucursal) {
                PRODUCTOS_REG = [];
                FUSE_REG = null;
                return;
            }
            const respuesta = await api({
                accion: 'productos',
                sucursal: sucursal
            });
            PRODUCTOS_REG = respuesta.data || [];
            FUSE_REG = new Fuse(PRODUCTOS_REG, {
                keys: ['nombre'],
                threshold: 0.3,
                ignoreLocation: true,
                ignoreDiacritics: true,
                useExtendedSearch: true
            });
            filtrarProductosRegistro($id('regProductoFiltro').value);
        }

        function filtrarProductosRegistro(texto) {
            const select = $id('regProducto');
            select.innerHTML = '';
            PRODUCTO_REG = null;

            const busqueda = String(texto || '').trim();
            if (busqueda === '') {
                select.add(new Option('-- Escriba para filtrar --', ''));
                actualizarInfoProducto();
                return;
            }

            let resultados = [];
            if (FUSE_REG) {
                resultados = FUSE_REG.search(busqueda).slice(0, 80).map(function(item) {
                    return item.item;
                });
            } else {
                resultados = PRODUCTOS_REG.filter(function(producto) {
                    return producto.nombre.toLowerCase().indexOf(busqueda.toLowerCase()) !== -1;
                }).slice(0, 80);
            }

            if (!resultados.length) {
                select.add(new Option('-- Sin coincidencias --', ''));
                actualizarInfoProducto();
                return;
            }

            select.add(new Option('-- Seleccione --', ''));
            resultados.forEach(function(producto) {
                select.add(new Option(producto.nombre, producto.id));
            });
            actualizarInfoProducto();
        }

        function actualizarInfoProducto() {
            const caja = $id('regProductoInfo');
            if (!PRODUCTO_REG) {
                caja.innerHTML = 'Seleccione un producto para ver su stock actual y su costo unitario.';
                actualizarPreview();
                return;
            }
            caja.innerHTML = '<strong>' + esc(PRODUCTO_REG.nombre) + '</strong>' +
                ' · ' + esc(PRODUCTO_REG.categoria) +
                ' · Stock actual: <strong>' + numero(PRODUCTO_REG.stock, 4) + '</strong>' +
                ' · Costo unitario: <strong>' + money(PRODUCTO_REG.costo_unitario) + '</strong>';
            actualizarPreview();
        }

        function actualizarTipoRegistro() {
            const tipo = $id('regTipo').value;
            $id('regCampoCantidad').style.display = tipo === 'MERMA' ? '' : 'none';
            $id('regCampoStockReal').style.display = tipo === 'AJUSTE' ? '' : 'none';

            const select = $id('regMotivo');
            select.innerHTML = Object.keys(MOTIVOS[tipo]).map(function(clave) {
                return '<option value="' + clave + '">' + esc(MOTIVOS[tipo][clave]) + '</option>';
            }).join('');

            if (tipo === 'AJUSTE' && PRODUCTO_REG) {
                $id('regStockReal').value = PRODUCTO_REG.stock;
            }
            actualizarPreview();
        }

        function actualizarPreview() {
            const valor = $id('regPreview');
            const detalle = $id('regPreviewDetalle');
            if (!PRODUCTO_REG) {
                valor.textContent = '$0.00';
                detalle.textContent = 'Seleccione un producto e indique la cantidad.';
                return;
            }

            const tipo = $id('regTipo').value;
            const costo = Number(PRODUCTO_REG.costo_unitario) || 0;
            let cantidad = 0;
            let etiqueta = 'Pérdida estimada';

            if (tipo === 'MERMA') {
                cantidad = Math.max(0, parseFloat($id('regCantidad').value) || 0);
                const stock = Number(PRODUCTO_REG.stock) || 0;
                if (cantidad > stock + TOLERANCIA_STOCK) {
                    detalle.textContent = 'La cantidad excede el stock actual (' + numero(stock, 4) + ').';
                    valor.textContent = money(stock * costo);
                    return;
                }
                // Si escribió el stock redondeado, se toma todo el disponible.
                if (cantidad > stock) {
                    cantidad = stock;
                }
            } else {
                const real = parseFloat($id('regStockReal').value);
                if (!isNaN(real)) {
                    const diferencia = real - (Number(PRODUCTO_REG.stock) || 0);
                    cantidad = Math.abs(diferencia);
                    etiqueta = diferencia >= 0 ? 'Valor del sobrante' : 'Pérdida estimada';
                }
            }

            valor.textContent = money(cantidad * costo);
            detalle.textContent = cantidad > 0 ?
                numero(cantidad, 4) + ' × ' + money(costo) + ' · ' + etiqueta :
                'Indique una cantidad para calcular el valor.';
        }

        function abrirRegistro() {
            const formulario = $id('formRegistro');
            formulario.reset();
            $id('regFecha').value = HOY;
            $id('regTipo').value = 'MERMA';
            PRODUCTO_REG = null;
            $id('regCantidad').value = '';
            $id('regStockReal').value = '';
            $id('regProductoFiltro').value = '';
            $id('regProducto').innerHTML = '<option value="">-- Escriba para filtrar --</option>';
            actualizarInfoProducto();

            if (ES_ADMIN) {
                const filtro = parseInt($id('filtroSucursal').value, 10) || 0;
                const porDefecto = filtro > 0 ? filtro : (SUCURSALES.length ? SUCURSALES[0].id : 0);
                $id('regSucursal').value = porDefecto;
            }

            actualizarTipoRegistro();
            cargarProductosRegistro().catch(function(error) {
                toast('error', error.message);
            });
            $('#modalRegistro').modal('show');
        }

        async function guardarRegistro(evento) {
            evento.preventDefault();
            if (!PRODUCTO_REG) {
                toast('error', 'Seleccione un producto.');
                return;
            }

            const tipo = $id('regTipo').value;
            const razon = $id('regRazon').value.trim();
            if (!razon) {
                toast('error', 'Escriba la razón o explicación.');
                return;
            }

            const datos = {
                accion: 'registrar',
                tipo: tipo,
                sucursal: sucursalRegistro(),
                producto_id: PRODUCTO_REG.id,
                motivo: $id('regMotivo').value,
                razon: razon,
                fecha: $id('regFecha').value
            };

            if (tipo === 'MERMA') {
                let cantidad = parseFloat($id('regCantidad').value);
                const stock = Number(PRODUCTO_REG.stock) || 0;
                if (!(cantidad > 0)) {
                    toast('error', 'Indique una cantidad mayor que cero.');
                    return;
                }
                if (cantidad > stock + TOLERANCIA_STOCK) {
                    toast('error', 'La cantidad excede el stock actual (' + numero(stock, 4) + ').');
                    return;
                }
                // Se ajusta al stock real para no dejar residuos por redondeo.
                if (cantidad > stock) {
                    cantidad = stock;
                }
                if (!(cantidad > 0)) {
                    toast('error', 'No hay stock disponible para registrar la merma.');
                    return;
                }
                datos.cantidad = cantidad;
            } else {
                const stockReal = parseFloat($id('regStockReal').value);
                if (isNaN(stockReal) || stockReal < 0) {
                    toast('error', 'Indique un stock real válido.');
                    return;
                }
                if (Math.abs(stockReal - (Number(PRODUCTO_REG.stock) || 0)) < 0.0001) {
                    toast('error', 'El stock real coincide con el sistema.');
                    return;
                }
                datos.stock_real = stockReal;
            }

            const boton = $id('regGuardar');
            boton.disabled = true;
            boton.textContent = 'Guardando...';

            try {
                const respuesta = await api(datos);
                $('#modalRegistro').modal('hide');
                toast('success', respuesta.message || 'Registro guardado.');
                await cargarProductosFiltro();
                await cargarTodo();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo guardar',
                    text: error.message
                });
            } finally {
                boton.disabled = false;
                boton.textContent = 'Guardar';
            }
        }

        function abrirAnular(boton) {
            $id('anularId').value = boton.dataset.id;
            $id('anularMotivo').value = '';
            $id('anularInfo').innerHTML =
                '<strong>' + esc(boton.dataset.producto) + '</strong><br>' +
                'Fecha: ' + esc(boton.dataset.fecha) +
                ' · Valor: <strong>' + money(boton.dataset.monto) + '</strong>' +
                ' · Registrado por: ' + esc(boton.dataset.usuario) + '<br>' +
                'Razón: ' + esc(boton.dataset.razon);
            $('#modalAnular').modal('show');
        }

        async function guardarAnulacion(evento) {
            evento.preventDefault();
            const id = parseInt($id('anularId').value, 10) || 0;
            const motivo = $id('anularMotivo').value.trim();
            if (!id || !motivo) {
                toast('error', 'Indique el motivo de anulación.');
                return;
            }

            const boton = $id('anularGuardar');
            boton.disabled = true;
            boton.textContent = 'Anulando...';

            try {
                const respuesta = await api({
                    accion: 'anular',
                    id: id,
                    motivo: motivo
                });
                $('#modalAnular').modal('hide');
                toast('success', respuesta.message || 'Registro anulado.');
                await cargarProductosFiltro();
                await cargarTodo();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo anular',
                    text: error.message
                });
            } finally {
                boton.disabled = false;
                boton.textContent = 'Anular y revertir';
            }
        }

        function fechaISO(fecha) {
            const anio = fecha.getFullYear();
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const dia = String(fecha.getDate()).padStart(2, '0');
            return anio + '-' + mes + '-' + dia;
        }

        function aplicarSemanaActual() {
            const hoy = new Date(HOY + 'T12:00:00');
            const diaSemana = (hoy.getDay() + 6) % 7;
            const lunes = new Date(hoy);
            lunes.setDate(hoy.getDate() - diaSemana);
            $id('filtroDesde').value = fechaISO(lunes);
            $id('filtroHasta').value = HOY;
            cargarTodo();
        }

        function aplicarMesActual() {
            $id('filtroDesde').value = HOY.slice(0, 8) + '01';
            $id('filtroHasta').value = HOY;
            cargarTodo();
        }

        document.addEventListener('DOMContentLoaded', function() {
            $id('btnNuevoRegistro').addEventListener('click', abrirRegistro);
            $id('btnSemana').addEventListener('click', aplicarSemanaActual);
            $id('btnMes').addEventListener('click', aplicarMesActual);

            $id('filtroTipo').addEventListener('change', cargarTodo);
            $id('filtroGrupo').addEventListener('change', function() {
                poblarCategorias(this.value);
                $id('filtroCategoria').value = '0';
                cargarTodo();
            });
            $id('filtroCategoria').addEventListener('change', cargarTodo);
            $id('filtroProducto').addEventListener('change', cargarTodo);
            $id('filtroProductoBuscar').addEventListener('input', function() {
                buscarProductoFiltro(this.value);
            });
            $id('filtroDesde').addEventListener('change', cargarTodo);
            $id('filtroHasta').addEventListener('change', cargarTodo);

            if (ES_ADMIN) {
                $id('filtroSucursal').addEventListener('change', async function() {
                    try {
                        await cargarProductosFiltro();
                        await cargarTodo();
                    } catch (error) {
                        toast('error', error.message);
                    }
                });
            }

            document.querySelectorAll('.aj-toggle button').forEach(function(boton) {
                boton.addEventListener('click', function() {
                    document.querySelectorAll('.aj-toggle button').forEach(function(otro) {
                        otro.classList.remove('active');
                    });
                    boton.classList.add('active');
                    VISTA_PERIODO = boton.dataset.vista;
                    renderPeriodo();
                });
            });

            $id('regTipo').addEventListener('change', actualizarTipoRegistro);
            $id('regProductoFiltro').addEventListener('input', function() {
                filtrarProductosRegistro(this.value);
            });
            $id('regProducto').addEventListener('change', function() {
                const id = parseInt(this.value, 10) || 0;
                PRODUCTO_REG = PRODUCTOS_REG.find(function(producto) {
                    return producto.id === id;
                }) || null;
                if (PRODUCTO_REG && $id('regTipo').value === 'AJUSTE') {
                    $id('regStockReal').value = PRODUCTO_REG.stock;
                }
                actualizarInfoProducto();
            });
            $id('regCantidad').addEventListener('input', actualizarPreview);
            $id('regStockReal').addEventListener('input', actualizarPreview);

            if (ES_ADMIN) {
                $id('regSucursal').addEventListener('change', function() {
                    $id('regProductoFiltro').value = '';
                    $id('regProducto').innerHTML = '<option value="">-- Escriba para filtrar --</option>';
                    PRODUCTO_REG = null;
                    actualizarInfoProducto();
                    cargarProductosRegistro().catch(function(error) {
                        toast('error', error.message);
                    });
                });
            }

            $id('formRegistro').addEventListener('submit', guardarRegistro);
            $id('formAnular').addEventListener('submit', guardarAnulacion);

            $id('tablaMovimientos').addEventListener('click', function(evento) {
                const boton = evento.target.closest('.aj-action');
                if (boton) {
                    abrirAnular(boton);
                }
            });

            $id('paginaAnterior').addEventListener('click', function() {
                if (PAGINA_ACTUAL > 1) {
                    cargarTabla(PAGINA_ACTUAL - 1).catch(function(error) {
                        toast('error', error.message);
                    });
                }
            });
            $id('paginaSiguiente').addEventListener('click', function() {
                if (PAGINA_ACTUAL * 50 < TOTAL_REGISTROS) {
                    cargarTabla(PAGINA_ACTUAL + 1).catch(function(error) {
                        toast('error', error.message);
                    });
                }
            });

            cargarProductosFiltro()
                .then(function() {
                    return cargarTodo();
                })
                .catch(function(error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo iniciar el módulo',
                        text: error.message
                    });
                });
        });
    </script>
</body>

</html>