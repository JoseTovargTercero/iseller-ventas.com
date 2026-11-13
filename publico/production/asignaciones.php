<?php
require_once('includes/requires.php');
require_once('../../configurar/_asignaciones_sucursales.php');

$esAdmin = (int) $_SESSION['nivel'] === 1;
$sucursalSesion = (int) ($_SESSION['sucursal'] ?? 0);

if (!$esAdmin && !in_array('asignaciones.php', $_SESSION['permisos'] ?? [], true)) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();
$sucursalesTodas = asig_listar_sucursales($conexion, (int) $bss_id);
$sucursalesFormulario = $esAdmin
    ? $sucursalesTodas
    : array_values(array_filter($sucursalesTodas, static fn($s) => (int) $s['id'] === $sucursalSesion));
$hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Asignación de mercancía</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .a-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid transparent;
            border-radius: 9px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            transition: all .18s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .a-btn ion-icon {
            font-size: 16px;
        }

        .a-btn-primary {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #07130f;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
        }

        .a-btn-primary:hover {
            color: #07130f;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        }

        .a-btn-ghost {
            background: rgba(255, 255, 255, .04);
            border-color: var(--dash-border);
            color: var(--dash-text-muted);
        }

        .a-btn-ghost:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, .45);
            background: rgba(45, 212, 160, .06);
        }

        .a-btn-danger {
            background: rgba(239, 90, 111, .1);
            border-color: rgba(239, 90, 111, .3);
            color: #ef5a6f;
        }

        .a-btn-danger:hover {
            background: rgba(239, 90, 111, .18);
            color: #ff7b8c;
        }

        .a-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .a-icon-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .18s ease;
            text-decoration: none;
        }

        .a-icon-btn:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, .5);
            background: rgba(45, 212, 160, .06);
            text-decoration: none;
        }

        .a-icon-btn.danger:hover {
            color: #ef5a6f;
            border-color: rgba(239, 90, 111, .5);
            background: rgba(239, 90, 111, .06);
        }

        .a-icon-btn ion-icon {
            font-size: 16px;
        }

        .a-filters {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            padding: 16px 20px;
            border-bottom: 1px solid var(--dash-border);
        }

        .a-filter {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 145px;
        }

        .a-filter label,
        .a-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--dash-text-muted);
            margin: 0;
        }

        .a-input,
        .a-select,
        .a-textarea {
            width: 100%;
            background: rgba(255, 255, 255, .035);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            color: var(--dash-text);
            padding: 9px 12px;
            font-size: 13px;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .a-input:focus,
        .a-select:focus,
        .a-textarea:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .a-select option {
            background: #181b22;
            color: var(--dash-text);
        }

        .a-textarea {
            min-height: 76px;
            resize: vertical;
        }

        .a-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .a-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .a-table thead th {
            padding: 11px 14px;
            text-align: left;
            font-weight: 700;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--dash-text-muted);
            border-bottom: 1px solid var(--dash-border);
            background: rgba(255, 255, 255, .015);
            white-space: nowrap;
        }

        .a-table tbody td {
            padding: 11px 14px;
            color: var(--dash-text);
            border-bottom: 1px solid rgba(46, 53, 62, .55);
            vertical-align: middle;
        }

        .a-table tbody tr:last-child td {
            border-bottom: none;
        }

        .a-table tbody tr:hover {
            background: rgba(45, 212, 160, .025);
        }

        .a-right {
            text-align: right !important;
        }

        .a-center {
            text-align: center !important;
        }

        .a-muted {
            color: var(--dash-text-muted) !important;
        }

        .a-money {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .a-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .25px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .a-badge-borrador {
            background: rgba(245, 180, 91, .12);
            color: #f5b45b;
        }

        .a-badge-aplicado {
            background: rgba(45, 212, 160, .12);
            color: var(--dash-mint);
        }

        .a-badge-mayor {
            background: rgba(91, 156, 245, .12);
            color: #5b9cf5;
            text-transform: none;
            font-size: 10px;
        }

        .a-empty {
            padding: 48px 20px;
            text-align: center;
            color: var(--dash-text-muted);
        }

        .a-empty ion-icon {
            font-size: 40px;
            opacity: .35;
        }

        .a-empty h5 {
            color: var(--dash-text);
            font-size: 15px;
            margin: 12px 0 4px;
        }

        .a-empty p {
            margin: 0;
            font-size: 12.5px;
        }

        .a-form-body {
            padding: 20px 22px 24px;
        }

        .a-grid {
            display: grid;
            gap: 14px;
            margin-bottom: 14px;
        }

        .a-grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .a-grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .a-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .a-switch-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 14px;
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            background: rgba(255, 255, 255, .02);
        }

        .a-switch {
            position: relative;
            width: 40px;
            height: 22px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .a-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .a-switch span {
            position: absolute;
            inset: 0;
            border-radius: 20px;
            background: #2e353e;
            cursor: pointer;
            transition: background .18s ease;
        }

        .a-switch span::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            left: 3px;
            top: 3px;
            border-radius: 50%;
            background: #fff;
            transition: transform .18s ease;
        }

        .a-switch input:checked+span {
            background: var(--dash-mint);
        }

        .a-switch input:checked+span::before {
            transform: translateX(18px);
        }

        .a-switch-copy strong {
            display: block;
            font-size: 12.5px;
            color: var(--dash-text);
            margin-bottom: 2px;
        }

        .a-switch-copy small {
            display: block;
            font-size: 11.5px;
            color: var(--dash-text-muted);
            line-height: 1.45;
        }

        .a-note {
            display: flex;
            gap: 9px;
            align-items: flex-start;
            padding: 11px 14px;
            border: 1px solid rgba(91, 156, 245, .25);
            background: rgba(91, 156, 245, .06);
            border-radius: 9px;
            color: #b9d2f8;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .a-note ion-icon {
            color: #5b9cf5;
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .a-search {
            display: grid;
            grid-template-columns: 1fr 190px auto;
            gap: 10px;
            margin-bottom: 10px;
        }

        .a-results {
            display: none;
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            background: #181b22;
            max-height: 280px;
            overflow-y: auto;
            margin-bottom: 14px;
        }

        .a-results.show {
            display: block;
        }

        .a-result {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 14px;
            border-bottom: 1px solid rgba(46, 53, 62, .55);
        }

        .a-result:last-child {
            border-bottom: none;
        }

        .a-result strong {
            display: block;
            font-size: 13px;
            color: var(--dash-text);
        }

        .a-result small {
            display: block;
            color: var(--dash-text-muted);
            font-size: 11.5px;
            margin-top: 2px;
        }

        .a-result-add {
            border: 1px solid rgba(45, 212, 160, .35);
            background: rgba(45, 212, 160, .08);
            color: var(--dash-mint);
            border-radius: 7px;
            padding: 6px 11px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }

        .a-result-add:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .a-qty {
            width: 100px;
            background: rgba(255, 255, 255, .035);
            border: 1px solid var(--dash-border);
            border-radius: 7px;
            color: var(--dash-text);
            padding: 6px 9px;
            font-size: 12.5px;
            text-align: right;
        }

        .a-qty.invalid {
            border-color: rgba(239, 90, 111, .7);
            box-shadow: 0 0 0 2px rgba(239, 90, 111, .1);
        }

        .a-total-row {
            display: flex;
            justify-content: flex-end;
            align-items: baseline;
            gap: 12px;
            padding: 16px 4px 4px;
            font-size: 13px;
            color: var(--dash-text-muted);
            border-top: 1px solid var(--dash-border);
            margin-top: 8px;
        }

        .a-total-row strong {
            color: var(--dash-mint);
            font-size: 23px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .a-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .a-warning {
            border: 1px solid rgba(245, 180, 91, .35);
            background: rgba(245, 180, 91, .07);
            color: #f8d9a3;
            border-radius: 9px;
            padding: 11px 14px;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .a-modal .modal-content {
            background: #181b22;
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            color: var(--dash-text);
        }

        .a-modal .modal-header,
        .a-modal .modal-footer {
            border-color: var(--dash-border);
            padding: 15px 20px;
        }

        .a-modal .modal-body {
            padding: 18px 20px;
            max-height: 72vh;
            overflow-y: auto;
        }

        .a-modal .modal-title {
            font-size: 16px;
            font-weight: 700;
        }

        .a-modal .close {
            color: var(--dash-text-muted);
            opacity: .8;
            text-shadow: none;
        }

        .a-detail-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 16px;
        }

        .a-detail-cell {
            border: 1px solid var(--dash-border);
            border-radius: 9px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, .02);
        }

        .a-detail-cell span {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--dash-text-muted);
            margin-bottom: 3px;
        }

        .a-detail-cell strong {
            font-size: 13px;
            color: var(--dash-text);
        }

        .a-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--dash-text-muted);
            margin: 18px 0 8px;
        }

        .a-summary-line {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 7px 0;
            border-bottom: 1px solid rgba(46, 53, 62, .45);
            font-size: 12.5px;
        }

        .a-summary-line span {
            color: var(--dash-text-muted);
        }

        .a-summary-line b {
            color: var(--dash-text);
            font-variant-numeric: tabular-nums;
        }

        .a-summary-line.total b {
            color: var(--dash-mint);
            font-size: 16px;
        }

        @media (max-width: 900px) {
            .a-grid-3 {
                grid-template-columns: 1fr;
            }

            .a-detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .right_col {
                padding: 14px 12px !important;
            }

            .a-grid-2 {
                grid-template-columns: 1fr;
            }

            .a-filters {
                padding: 14px;
            }

            .a-filter {
                width: 100%;
            }

            .a-search {
                grid-template-columns: 1fr;
            }

            .a-form-body {
                padding: 16px 14px 20px;
            }

            .a-actions {
                flex-direction: column-reverse;
            }

            .a-actions .a-btn {
                width: 100%;
            }

            .a-table thead {
                display: none;
            }

            .a-table tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px 10px;
                padding: 12px;
                border-bottom: 1px solid var(--dash-border);
            }

            .a-table tbody td {
                padding: 2px 0;
                border: none;
            }

            .a-table tbody td[data-label]::before {
                content: attr(data-label) ': ';
                color: var(--dash-text-muted);
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: .3px;
            }

            .a-detail-grid {
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
                <div id="vista-lista">
                    <div class="dash-panel">
                        <div class="panel-header">
                            <h6><ion-icon name="swap-horizontal-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Asignación de mercancía</h6>
                            <?php if ($esAdmin): ?>
                                <button type="button" class="a-btn a-btn-primary" id="btn-nueva">
                                    <ion-icon name="add-outline"></ion-icon> Nueva asignación
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="panel-body p-0">
                            <div class="a-filters">
                                <div class="a-filter">
                                    <label for="filtro-estado">Estado</label>
                                    <select id="filtro-estado" class="a-select">
                                        <option value="">Todos</option>
                                        <option value="BORRADOR">Borrador</option>
                                        <option value="APLICADO">Aplicado</option>
                                    </select>
                                </div>
                                <div class="a-filter">
                                    <label for="filtro-sucursal">Sucursal</label>
                                    <select id="filtro-sucursal" class="a-select">
                                        <option value="">Todas</option>
                                        <?php foreach ($sucursalesFormulario as $sucursal): ?>
                                            <option value="<?= (int) $sucursal['id'] ?>"><?= htmlspecialchars($sucursal['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="a-filter">
                                    <label for="filtro-desde">Desde</label>
                                    <input type="date" id="filtro-desde" class="a-input">
                                </div>
                                <div class="a-filter">
                                    <label for="filtro-hasta">Hasta</label>
                                    <input type="date" id="filtro-hasta" class="a-input">
                                </div>
                                <button type="button" class="a-btn a-btn-ghost" id="btn-filtrar">
                                    <ion-icon name="funnel-outline"></ion-icon> Filtrar
                                </button>
                            </div>
                            <div class="a-table-wrap">
                                <table class="a-table" id="tabla-asignaciones">
                                    <thead>
                                        <tr>
                                            <th>Documento</th>
                                            <th>Fecha</th>
                                            <th>Origen</th>
                                            <th>Destino</th>
                                            <th class="a-right">Total USD</th>
                                            <th class="a-center">Estado</th>
                                            <th class="a-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="a-empty d-none" id="lista-vacia">
                                <ion-icon name="swap-horizontal-outline"></ion-icon>
                                <h5>No hay asignaciones registradas</h5>
                                <p>Las asignaciones que cree aparecerán en este listado.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="vista-form" class="d-none">
                    <div class="dash-panel">
                        <div class="panel-header">
                            <h6 id="form-titulo"><ion-icon name="add-circle-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Nueva asignación</h6>
                            <button type="button" class="a-btn a-btn-ghost" id="btn-volver">
                                <ion-icon name="arrow-back-outline"></ion-icon> Volver
                            </button>
                        </div>
                        <div class="panel-body a-form-body">
                            <div id="aviso-rectificacion" class="a-warning d-none">
                                Está rectificando una asignación aplicada. El inventario y la obligación económica se ajustarán automáticamente. Indique la razón del cambio.
                            </div>

                            <div class="a-grid a-grid-3">
                                <div class="a-field">
                                    <label class="a-label" for="sucursal-origen">Sucursal de origen *</label>
                                    <select id="sucursal-origen" class="a-select">
                                        <option value="">-- Seleccione --</option>
                                        <?php foreach ($sucursalesFormulario as $sucursal): ?>
                                            <option value="<?= (int) $sucursal['id'] ?>"><?= htmlspecialchars($sucursal['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="a-field">
                                    <label class="a-label" for="sucursal-destino">Sucursal de destino *</label>
                                    <select id="sucursal-destino" class="a-select">
                                        <option value="">-- Seleccione --</option>
                                        <?php foreach ($sucursalesFormulario as $sucursal): ?>
                                            <option value="<?= (int) $sucursal['id'] ?>"><?= htmlspecialchars($sucursal['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="a-field">
                                    <label class="a-label" for="fecha-asignacion">Fecha *</label>
                                    <input type="date" id="fecha-asignacion" class="a-input" value="<?= $hoy ?>">
                                </div>
                            </div>

                            <div class="a-grid a-grid-2">
                                <div class="a-switch-row">
                                    <label class="a-switch">
                                        <input type="checkbox" id="descuenta-origen" checked>
                                        <span></span>
                                    </label>
                                    <div class="a-switch-copy">
                                        <strong>Descontar inventario de origen</strong>
                                        <small>Si lo desactiva, la mercancía no necesita estar registrada en la sucursal de origen.</small>
                                    </div>
                                </div>
                                <div class="a-switch-row">
                                    <label class="a-switch">
                                        <input type="checkbox" id="suma-destino" checked>
                                        <span></span>
                                    </label>
                                    <div class="a-switch-copy">
                                        <strong>Sumar inventario en destino</strong>
                                        <small>Si lo desactiva, solo se genera la obligación económica sin mover existencias.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="a-note">
                                <ion-icon name="information-circle-outline"></ion-icon>
                                <span>El monto de la deuda se calcula exclusivamente con el costo unitario de los productos, sin porcentajes ni recargos. El costo queda congelado al aplicar la asignación.</span>
                            </div>

                            <div class="a-field" style="margin-bottom:14px;">
                                <label class="a-label" for="observaciones">Observaciones</label>
                                <textarea id="observaciones" class="a-textarea" placeholder="Notas internas de la asignación (opcional)"></textarea>
                            </div>

                            <div class="a-search">
                                <input type="text" id="buscar-producto" class="a-input" placeholder="Buscar producto por nombre o código">
                                <select id="filtro-tipo" class="a-select">
                                    <option value="">Detal y mayor</option>
                                    <option value="detal">Solo detal</option>
                                    <option value="mayor">Solo mayor</option>
                                </select>
                                <button type="button" class="a-btn a-btn-ghost" id="btn-buscar-producto">
                                    <ion-icon name="search-outline"></ion-icon> Buscar
                                </button>
                            </div>
                            <div class="a-results" id="resultados-productos"></div>

                            <div class="a-table-wrap">
                                <table class="a-table" id="tabla-lineas">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th class="a-right">Existencia origen</th>
                                            <th class="a-right">Cantidad</th>
                                            <th class="a-right">Costo unitario</th>
                                            <th class="a-right">Subtotal</th>
                                            <th class="a-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="a-empty d-none" id="lineas-vacias">
                                <ion-icon name="cube-outline"></ion-icon>
                                <h5>Sin productos agregados</h5>
                                <p>Use el buscador o agregue un concepto sin inventario más abajo.</p>
                            </div>

                            <div class="a-section-title" style="margin-top:18px;">Conceptos y servicios sin inventario</div>
                            <div class="a-note">
                                <ion-icon name="receipt-outline"></ion-icon>
                                <span>Agregue deudas especiales o de logística, por ejemplo “costo de transporte”. No modifican inventario y se suman al total de la obligación.</span>
                            </div>
                            <div class="a-search" style="grid-template-columns:1fr 160px auto;">
                                <input type="text" id="concepto-descripcion" class="a-input" placeholder="Ej: Costo de transporte">
                                <input type="number" id="concepto-monto" class="a-input" placeholder="Monto USD" min="0.01" step="0.01">
                                <button type="button" class="a-btn a-btn-ghost" id="btn-agregar-concepto">
                                    <ion-icon name="add-outline"></ion-icon> Agregar
                                </button>
                            </div>
                            <div class="a-table-wrap">
                                <table class="a-table" id="tabla-conceptos">
                                    <thead>
                                        <tr>
                                            <th>Concepto</th>
                                            <th class="a-right">Monto USD</th>
                                            <th class="a-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div class="a-empty d-none" id="conceptos-vacios" style="padding:18px;">
                                <p>Sin conceptos agregados.</p>
                            </div>

                            <div class="a-total-row">Total asignado <strong id="total-asignacion">$0.00</strong></div>

                            <div id="motivo-wrap" class="a-field d-none" style="margin-top:14px;">
                                <label class="a-label" for="motivo-rectificacion">Razón de la rectificación *</label>
                                <textarea id="motivo-rectificacion" class="a-textarea" placeholder="Explique qué error se está corrigiendo"></textarea>
                            </div>

                            <div class="a-actions">
                                <button type="button" class="a-btn a-btn-ghost" id="btn-cancelar-form">Cancelar</button>
                                <button type="button" class="a-btn a-btn-ghost" id="btn-guardar-borrador">
                                    <ion-icon name="save-outline"></ion-icon> Guardar borrador
                                </button>
                                <button type="button" class="a-btn a-btn-primary" id="btn-guardar-aplicar">
                                    <ion-icon name="checkmark-done-outline"></ion-icon> Guardar y aplicar
                                </button>
                                <button type="button" class="a-btn a-btn-primary d-none" id="btn-rectificar">
                                    <ion-icon name="create-outline"></ion-icon> Guardar rectificación
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade a-modal" id="modalDetalle" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalle-titulo">Detalle de la asignación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="detalle-body"></div>
                <div class="modal-footer">
                    <a href="#" class="a-btn a-btn-ghost" id="btn-detalle-pdf" target="_blank" rel="noopener">
                        <ion-icon name="download-outline"></ion-icon> PDF
                    </a>
                    <button type="button" class="a-btn a-btn-ghost" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <script src="../vendors/nprogress/nprogress.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="../build/js/modal.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        const CFG = <?= json_encode([
                    'esAdmin' => $esAdmin,
                    'usuarioId' => (int) $_SESSION['id'],
                    'hoy' => $hoy,
                    'endpoint' => '../../configurar/asignaciones_sucursales_back.php',
                ], JSON_UNESCAPED_UNICODE) ?>;

        const $lista = document.getElementById('vista-lista');
        const $form = document.getElementById('vista-form');
        const $tbody = document.querySelector('#tabla-asignaciones tbody');
        const $lineasBody = document.querySelector('#tabla-lineas tbody');
        const $resultados = document.getElementById('resultados-productos');
        const $total = document.getElementById('total-asignacion');

        let asignaciones = {};
        let lineas = [];
        let conceptos = [];
        let editando = null;
        let rectificando = false;
        let resultadosProductos = [];

        const esc = (valor) => String(valor ?? '').replace(/[&<>"']/g, (c) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[c]));

        const money = (valor) => '$' + Number(valor || 0).toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        const qty = (valor) => {
            const numero = Number(valor || 0);
            return Number.isInteger(numero) ? String(numero) : String(numero).replace(/\.?0+$/, '');
        };

        const estadoBadge = (estado) => estado === 'APLICADO'
            ? '<span class="a-badge a-badge-aplicado">Aplicado</span>'
            : '<span class="a-badge a-badge-borrador">Borrador</span>';

        async function post(accion, datos = {}) {
            const body = new URLSearchParams();
            body.set('accion', accion);
            Object.entries(datos).forEach(([clave, valor]) => body.set(clave, valor ?? ''));
            const res = await fetch(CFG.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body
            });
            const texto = await res.text();
            try {
                return JSON.parse(texto);
            } catch (error) {
                console.error('Respuesta no válida:', texto);
                return {
                    status: 'error',
                    msg: 'El servidor devolvió una respuesta no válida.'
                };
            }
        }

        function mostrarLista() {
            $form.classList.add('d-none');
            $lista.classList.remove('d-none');
            editando = null;
            rectificando = false;
        }

        function mostrarFormulario() {
            $lista.classList.add('d-none');
            $form.classList.remove('d-none');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function cargarLista() {
            $tbody.innerHTML = '<tr><td colspan="7" class="a-muted a-center" style="padding:26px;">Cargando…</td></tr>';
            const data = await post('listar', {
                estado: document.getElementById('filtro-estado').value,
                sucursal: document.getElementById('filtro-sucursal').value,
                desde: document.getElementById('filtro-desde').value,
                hasta: document.getElementById('filtro-hasta').value
            });

            if (data.status !== 'ok') {
                $tbody.innerHTML = '<tr><td colspan="7" class="a-center" style="padding:26px;color:#ef5a6f;">' + esc(data.msg) + '</td></tr>';
                return;
            }

            const items = data.data.items || [];
            asignaciones = {};
            items.forEach((item) => {
                asignaciones[item.id] = item;
            });

            if (items.length === 0) {
                $tbody.innerHTML = '';
                document.getElementById('lista-vacia').classList.remove('d-none');
                return;
            }
            document.getElementById('lista-vacia').classList.add('d-none');

            $tbody.innerHTML = items.map((item) => {
                const acciones = [];
                acciones.push('<button type="button" class="a-icon-btn" data-accion="ver" data-id="' + item.id + '" title="Ver detalle"><ion-icon name="eye-outline"></ion-icon></button>');

                if (item.estado === 'APLICADO') {
                    acciones.push('<a class="a-icon-btn" href="asignacion_pdf.php?id=' + item.id + '" target="_blank" rel="noopener" title="PDF"><ion-icon name="download-outline"></ion-icon></a>');
                }
                if (item.puede_editar) {
                    acciones.push('<button type="button" class="a-icon-btn" data-accion="editar" data-id="' + item.id + '" title="Editar borrador"><ion-icon name="create-outline"></ion-icon></button>');
                }
                if (item.puede_rectificar) {
                    acciones.push('<button type="button" class="a-icon-btn" data-accion="rectificar" data-id="' + item.id + '" title="Rectificar aplicación"><ion-icon name="create-outline"></ion-icon></button>');
                }
                if (item.puede_aplicar) {
                    acciones.push('<button type="button" class="a-icon-btn" data-accion="aplicar" data-id="' + item.id + '" title="Aplicar asignación"><ion-icon name="checkmark-done-outline"></ion-icon></button>');
                }
                if (item.puede_eliminar) {
                    acciones.push('<button type="button" class="a-icon-btn danger" data-accion="eliminar" data-id="' + item.id + '" title="Eliminar borrador"><ion-icon name="trash-outline"></ion-icon></button>');
                }

                return `<tr>
                    <td data-label="Documento"><strong>${esc(item.numero_documento)}</strong></td>
                    <td data-label="Fecha">${esc(item.fecha)}</td>
                    <td data-label="Origen">${esc(item.origen_nombre)}</td>
                    <td data-label="Destino">${esc(item.destino_nombre)}</td>
                    <td data-label="Total" class="a-right a-money">${money(item.total)}</td>
                    <td data-label="Estado" class="a-center">${estadoBadge(item.estado)}</td>
                    <td data-label="Acciones" class="a-center" style="white-space:nowrap;">${acciones.join(' ')}</td>
                </tr>`;
            }).join('');
        }

        function resetFormulario() {
            editando = null;
            rectificando = false;
            lineas = [];
            conceptos = [];
            resultadosProductos = [];
            document.getElementById('sucursal-origen').value = '';
            document.getElementById('sucursal-destino').value = '';
            document.getElementById('fecha-asignacion').value = CFG.hoy;
            document.getElementById('observaciones').value = '';
            document.getElementById('descuenta-origen').checked = true;
            document.getElementById('suma-destino').checked = true;
            document.getElementById('buscar-producto').value = '';
            document.getElementById('filtro-tipo').value = '';
            document.getElementById('concepto-descripcion').value = '';
            document.getElementById('concepto-monto').value = '';
            document.getElementById('motivo-rectificacion').value = '';
            $resultados.classList.remove('show');
            $resultados.innerHTML = '';
            document.getElementById('motivo-wrap').classList.add('d-none');
            document.getElementById('aviso-rectificacion').classList.add('d-none');
            document.getElementById('btn-guardar-borrador').classList.remove('d-none');
            document.getElementById('btn-guardar-aplicar').classList.remove('d-none');
            document.getElementById('btn-rectificar').classList.add('d-none');
            document.getElementById('form-titulo').innerHTML = '<ion-icon name="add-circle-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Nueva asignación';
            renderConceptos();
            renderLineas();
        }

        async function abrirFormulario(item, esRectificacion = false) {
            if (!item) {
                resetFormulario();
                mostrarFormulario();
                return;
            }

            const data = await post('detalle', {
                id: item.id
            });
            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }

            const detalle = data.data;
            const asignacion = detalle.asignacion;
            editando = asignacion;
            rectificando = esRectificacion;
            lineas = (detalle.detalles || []).map((d) => ({
                producto_id: Number(d.producto_id),
                nombre: d.producto_nombre,
                stock: d.stock_origen,
                stock_registrado: d.stock_origen !== null,
                costo_unitario: Number(d.costo_unitario),
                cantidad: Number(d.cantidad),
                mayor: !!d.es_mayor
            }));
            conceptos = (detalle.conceptos || []).map((c) => ({
                concepto: c.concepto,
                monto: Number(c.monto)
            }));

            document.getElementById('sucursal-origen').value = String(asignacion.sucursal_origen);
            document.getElementById('sucursal-destino').value = String(asignacion.sucursal_destino);
            document.getElementById('fecha-asignacion').value = asignacion.fecha;
            document.getElementById('observaciones').value = asignacion.observaciones || '';
            document.getElementById('descuenta-origen').checked = Number(asignacion.descuenta_origen) === 1;
            document.getElementById('suma-destino').checked = Number(asignacion.suma_destino) === 1;
            document.getElementById('motivo-rectificacion').value = '';
            $resultados.classList.remove('show');
            $resultados.innerHTML = '';

            if (rectificando) {
                document.getElementById('form-titulo').innerHTML = '<ion-icon name="create-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Rectificar ' + esc(asignacion.numero_documento);
                document.getElementById('motivo-wrap').classList.remove('d-none');
                document.getElementById('aviso-rectificacion').classList.remove('d-none');
                document.getElementById('btn-guardar-borrador').classList.add('d-none');
                document.getElementById('btn-guardar-aplicar').classList.add('d-none');
                document.getElementById('btn-rectificar').classList.remove('d-none');
            } else {
                document.getElementById('form-titulo').innerHTML = '<ion-icon name="create-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Editar borrador ' + esc(asignacion.numero_documento);
                document.getElementById('motivo-wrap').classList.add('d-none');
                document.getElementById('aviso-rectificacion').classList.add('d-none');
                document.getElementById('btn-guardar-borrador').classList.remove('d-none');
                document.getElementById('btn-guardar-aplicar').classList.remove('d-none');
                document.getElementById('btn-rectificar').classList.add('d-none');
            }

            renderConceptos();
            renderLineas();
            mostrarFormulario();
        }

        function totalAsignacion() {
            const totalLineas = lineas.reduce((suma, linea) => suma + (Number(linea.cantidad || 0) * Number(linea.costo_unitario || 0)), 0);
            const totalConceptos = conceptos.reduce((suma, concepto) => suma + Number(concepto.monto || 0), 0);
            return totalLineas + totalConceptos;
        }

        function actualizarTotal() {
            $total.textContent = money(totalAsignacion());
        }

        function actualizarBotonera() {
            const origen = document.getElementById('sucursal-origen').value;
            const destino = document.getElementById('sucursal-destino').value;
            const mismo = origen && destino && origen === destino;
            const sinDocumento = lineas.length === 0 && conceptos.length === 0;

            ['btn-guardar-aplicar', 'btn-guardar-borrador', 'btn-rectificar'].forEach((id) => {
                const boton = document.getElementById(id);
                if (boton) boton.disabled = sinDocumento || mismo;
            });
        }

        function renderConceptos() {
            const tbody = document.querySelector('#tabla-conceptos tbody');
            if (!tbody) return;

            if (conceptos.length === 0) {
                tbody.innerHTML = '';
                document.getElementById('conceptos-vacios').classList.remove('d-none');
            } else {
                document.getElementById('conceptos-vacios').classList.add('d-none');
                tbody.innerHTML = conceptos.map((concepto, indice) => `<tr>
                    <td data-label="Concepto"><strong>${esc(concepto.concepto)}</strong> <span class="a-badge a-badge-borrador" style="text-transform:none;">Sin inventario</span></td>
                    <td data-label="Monto" class="a-right a-money">${money(concepto.monto)}</td>
                    <td data-label="" class="a-center">
                        <button type="button" class="a-icon-btn danger" data-quitar-concepto="${indice}" title="Quitar concepto"><ion-icon name="trash-outline"></ion-icon></button>
                    </td>
                </tr>`).join('');
            }

            actualizarTotal();
            actualizarBotonera();
        }

        function renderLineas() {
            const descuenta = document.getElementById('descuenta-origen').checked;
            const destino = document.getElementById('sucursal-destino').value;
            const origen = document.getElementById('sucursal-origen').value;
            const mismo = origen && destino && origen === destino;

            if (lineas.length === 0) {
                $lineasBody.innerHTML = '';
                document.getElementById('lineas-vacias').classList.remove('d-none');
            } else {
                document.getElementById('lineas-vacias').classList.add('d-none');
                const filas = lineas.map((linea, indice) => {
                    const subtotal = Number(linea.cantidad || 0) * Number(linea.costo_unitario || 0);
                    const stock = descuenta
                        ? (linea.stock_registrado
                            ? qty(linea.stock) + (linea.mayor ? ' <span class="a-muted" style="font-size:10px;">pacas</span>' : '')
                            : '<span class="a-muted">Sin registro</span>')
                        : '<span class="a-muted">—</span>';
                    const invalido = descuenta && linea.stock_registrado && Number(linea.cantidad) > Number(linea.stock) + 0.0001;
                    return `<tr>
                        <td data-label="Producto"><strong>${esc(linea.nombre)}</strong> ${linea.mayor ? '<span class="a-badge a-badge-mayor">Mayor</span>' : ''}</td>
                        <td data-label="Existencia" class="a-right">${stock}</td>
                        <td data-label="Cantidad" class="a-right">
                            <input type="number" class="a-qty ${invalido ? 'invalid' : ''}" min="0.0001" step="0.0001" value="${Number(linea.cantidad)}" data-index="${indice}">
                        </td>
                        <td data-label="Costo unitario" class="a-right a-money">${money(linea.costo_unitario)}</td>
                        <td data-label="Subtotal" class="a-right a-money" data-subtotal>${money(subtotal)}</td>
                        <td data-label="" class="a-center">
                            <button type="button" class="a-icon-btn danger" data-quitar="${indice}" title="Quitar producto"><ion-icon name="trash-outline"></ion-icon></button>
                        </td>
                    </tr>`;
                }).join('');
                $lineasBody.innerHTML = filas;
            }

            actualizarTotal();
            actualizarBotonera();
        }

        function actualizarCantidadLinea(input) {
            const indice = Number(input.dataset.index);
            const linea = lineas[indice];
            if (!linea) return;

            linea.cantidad = Number(input.value || 0);

            const fila = input.closest('tr');
            const celdaSubtotal = fila ? fila.querySelector('[data-subtotal]') : null;
            if (celdaSubtotal) {
                celdaSubtotal.textContent = money(Number(linea.cantidad || 0) * Number(linea.costo_unitario || 0));
            }

            const descuenta = document.getElementById('descuenta-origen').checked;
            const invalido = descuenta
                && linea.stock_registrado
                && Number(linea.cantidad) > Number(linea.stock) + 0.0001;
            input.classList.toggle('invalid', invalido);

            actualizarTotal();
        }

        async function buscarProductos() {
            const origen = document.getElementById('sucursal-origen').value;
            const destino = document.getElementById('sucursal-destino').value;
            const q = document.getElementById('buscar-producto').value.trim();
            if (!origen) {
                Alerta.toast('error', 'Seleccione primero la sucursal de origen.');
                return;
            }
            if (!destino) {
                Alerta.toast('error', 'Seleccione primero la sucursal de destino.');
                return;
            }
            if (origen === destino) {
                Alerta.toast('error', 'La sucursal de origen y destino no pueden ser la misma.');
                return;
            }
            if (q.length < 2) {
                Alerta.toast('error', 'Escriba al menos dos caracteres para buscar.');
                return;
            }

            $resultados.innerHTML = '<div class="a-muted" style="padding:12px 14px;font-size:12.5px;">Buscando…</div>';
            $resultados.classList.add('show');

            const data = await post('buscar_productos', {
                sucursal: origen,
                q,
                tipo: document.getElementById('filtro-tipo').value
            });
            if (data.status !== 'ok') {
                $resultados.innerHTML = '<div style="padding:12px 14px;color:#ef5a6f;font-size:12.5px;">' + esc(data.msg) + '</div>';
                return;
            }

            resultadosProductos = data.data.productos || [];
            if (resultadosProductos.length === 0) {
                $resultados.innerHTML = '<div class="a-muted" style="padding:12px 14px;font-size:12.5px;">No se encontraron productos.</div>';
                return;
            }

            const descuenta = document.getElementById('descuenta-origen').checked;
            $resultados.innerHTML = resultadosProductos.map((producto, indice) => {
                const sinExistencia = descuenta && (!producto.stock_registrado || Number(producto.stock) <= 0);
                return `<div class="a-result">
                    <div>
                        <strong>${esc(producto.nombre)} ${producto.mayor ? '<span class="a-badge a-badge-mayor">Mayor</span>' : ''}</strong>
                        <small>Existencia: ${producto.stock_registrado ? qty(producto.stock) + (producto.mayor ? ' pacas' : '') : 'sin registro'} · Costo unitario: ${money(producto.costo_unitario)}</small>
                    </div>
                    <button type="button" class="a-result-add" data-resultado="${indice}" ${sinExistencia ? 'disabled' : ''}>
                        ${sinExistencia ? 'Sin existencia' : 'Agregar'}
                    </button>
                </div>`;
            }).join('');
        }

        function agregarProducto(producto) {
            const existente = lineas.find((linea) => Number(linea.producto_id) === Number(producto.id));
            if (existente) {
                existente.cantidad = Number(existente.cantidad) + 1;
            } else {
                lineas.push({
                    producto_id: Number(producto.id),
                    nombre: producto.nombre,
                    stock: producto.stock,
                    stock_registrado: producto.stock_registrado,
                    costo_unitario: Number(producto.costo_unitario),
                    cantidad: 1,
                    mayor: producto.mayor
                });
            }
            renderLineas();
        }

        function agregarConcepto() {
            const descripcion = document.getElementById('concepto-descripcion').value.trim();
            const monto = Number(document.getElementById('concepto-monto').value || 0);

            if (descripcion === '') {
                Alerta.toast('error', 'Escriba el nombre del concepto.');
                return;
            }
            if (!monto || monto <= 0) {
                Alerta.toast('error', 'El monto del concepto debe ser mayor que cero.');
                return;
            }

            conceptos.push({
                concepto: descripcion,
                monto: Math.round(monto * 100) / 100
            });
            document.getElementById('concepto-descripcion').value = '';
            document.getElementById('concepto-monto').value = '';
            renderConceptos();
        }

        function datosFormulario() {
            return {
                id: editando ? editando.id : 0,
                sucursal_origen: document.getElementById('sucursal-origen').value,
                sucursal_destino: document.getElementById('sucursal-destino').value,
                fecha: document.getElementById('fecha-asignacion').value,
                observaciones: document.getElementById('observaciones').value,
                descuenta_origen: document.getElementById('descuenta-origen').checked ? 1 : 0,
                suma_destino: document.getElementById('suma-destino').checked ? 1 : 0,
                detalles: JSON.stringify(lineas.map((linea) => ({
                    producto_id: linea.producto_id,
                    cantidad: linea.cantidad
                }))),
                conceptos: JSON.stringify(conceptos.map((concepto) => ({
                    concepto: concepto.concepto,
                    monto: concepto.monto
                })))
            };
        }

        function validarFormulario() {
            const datos = datosFormulario();
            if (!datos.sucursal_origen || !datos.sucursal_destino) {
                Alerta.toast('error', 'Seleccione la sucursal de origen y destino.');
                return false;
            }
            if (datos.sucursal_origen === datos.sucursal_destino) {
                Alerta.toast('error', 'La sucursal de origen y destino no pueden ser la misma.');
                return false;
            }
            if (!datos.fecha) {
                Alerta.toast('error', 'Seleccione la fecha de la asignación.');
                return false;
            }
            if (lineas.length === 0 && conceptos.length === 0) {
                Alerta.toast('error', 'Agregue al menos un producto o un concepto.');
                return false;
            }
            for (const concepto of conceptos) {
                if (!concepto.concepto || Number(concepto.monto) <= 0) {
                    Alerta.toast('error', 'Todos los conceptos deben tener descripción y monto mayor que cero.');
                    return false;
                }
            }
            for (const linea of lineas) {
                if (!linea.cantidad || Number(linea.cantidad) <= 0) {
                    Alerta.toast('error', 'Todas las cantidades deben ser mayores que cero.');
                    return false;
                }
                if (document.getElementById('descuenta-origen').checked && Number(linea.cantidad) > Number(linea.stock) + 0.0001 && linea.stock_registrado) {
                    Alerta.toast('error', 'La cantidad de "' + linea.nombre + '" supera la existencia de origen.');
                    return false;
                }
            }
            if (rectificando && document.getElementById('motivo-rectificacion').value.trim() === '') {
                Alerta.toast('error', 'Indique la razón de la rectificación.');
                return false;
            }
            return true;
        }

        async function guardarBorrador() {
            if (!validarFormulario()) return;
            const data = await post('guardar', datosFormulario());
            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }
            Alerta.toast('success', data.msg);
            mostrarLista();
            cargarLista();
        }

        async function guardarYAplicar() {
            if (!validarFormulario()) return;

            const confirmacion = await Swal.fire({
                title: '¿Aplicar la asignación?',
                html: 'Se descontará el inventario de origen (si está activado), se sumará en destino y se generará la deuda por <strong>' + money(totalAsignacion()) + '</strong>.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, aplicar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2dd4a0'
            });
            if (!confirmacion.isConfirmed) return;

            const guardado = await post('guardar', datosFormulario());
            if (guardado.status !== 'ok') {
                Alerta.toast('error', guardado.msg);
                return;
            }

            const aplicado = await post('aplicar', {
                id: guardado.data.id
            });
            if (aplicado.status !== 'ok') {
                // El borrador ya existe: si el usuario reintenta, se actualiza
                // el mismo documento en lugar de crear otro.
                editando = {
                    id: guardado.data.id,
                    numero_documento: guardado.data.numero_documento
                };
                Alerta.toast('error', 'Borrador guardado, pero no se pudo aplicar: ' + aplicado.msg);
                cargarLista();
                return;
            }

            Alerta.toast('success', aplicado.msg);
            mostrarLista();
            cargarLista();
        }

        async function rectificar() {
            if (!validarFormulario()) return;

            const confirmacion = await Swal.fire({
                title: '¿Guardar la rectificación?',
                text: 'Se ajustará el inventario y la obligación económica de la asignación.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, rectificar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f5b45b'
            });
            if (!confirmacion.isConfirmed) return;

            const motivo = document.getElementById('motivo-rectificacion').value.trim();
            const datos = datosFormulario();
            datos.motivo = motivo;

            const data = await post('rectificar', datos);
            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }
            Alerta.toast('success', data.msg);
            mostrarLista();
            cargarLista();
        }

        async function aplicarItem(id) {
            const item = asignaciones[id];
            if (!item) return;
            const confirmacion = await Swal.fire({
                title: '¿Aplicar ' + esc(item.numero_documento) + '?',
                text: 'Se moverá el inventario y se generará la obligación económica. Esta acción no se puede repetir.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2dd4a0'
            });
            if (!confirmacion.isConfirmed) return;

            const data = await post('aplicar', {
                id
            });
            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }
            Alerta.toast('success', data.msg);
            cargarLista();
        }

        async function eliminarItem(id) {
            const item = asignaciones[id];
            if (!item) return;
            const esAplicada = item.estado === 'APLICADO';
            const confirmacion = await Swal.fire({
                title: esAplicada ? '¿Eliminar la asignación aplicada?' : '¿Eliminar el borrador?',
                html: esAplicada
                    ? 'Se eliminará la asignación <strong>' + esc(item.numero_documento) + '</strong>, su obligación económica, los movimientos y la bitácora. Se revertirá el inventario.'
                        + (Number(item.pagos_count) > 0 ? ' También se borrarán <strong>' + Number(item.pagos_count) + '</strong> abono(s) registrado(s).' : '')
                        + ' <strong>Esta acción no se puede deshacer.</strong>'
                    : 'No se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar todo',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef5a6f'
            });
            if (!confirmacion.isConfirmed) return;

            const data = await post('eliminar', {
                id
            });
            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }
            Alerta.toast('success', data.msg);
            cargarLista();
        }

        async function verDetalle(id) {
            const data = await post('detalle', {
                id
            });
            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }
            const d = data.data;
            const a = d.asignacion;
            const cuenta = d.cuenta;
            const pagos = d.pagos || [];
            const abonado = pagos.reduce((s, p) => s + Number(p.monto_usd || 0), 0);
            const original = cuenta ? Number(cuenta.monto_original) : Number(a.total);
            const saldo = Math.max(0, original - abonado);
            const excedente = Math.max(0, abonado - original);
            const estadoDeuda = abonado <= 0 ? 'PENDIENTE' : (abonado < original ? 'PARCIAL' : 'PAGADA');

            const filas = (d.detalles || []).map((detalle) => `<tr>
                <td>${esc(detalle.producto_nombre)}</td>
                <td class="a-right">${qty(detalle.cantidad)}</td>
                <td class="a-right a-money">${money(detalle.costo_unitario)}</td>
                <td class="a-right a-money">${money(detalle.subtotal)}</td>
            </tr>`).join('');

            const conceptosDetalle = d.conceptos || [];
            const filasConceptos = conceptosDetalle.map((concepto) => `<tr>
                <td>${esc(concepto.concepto)}</td>
                <td class="a-right a-money">${money(concepto.monto)}</td>
            </tr>`).join('');
            const conceptosHtml = conceptosDetalle.length
                ? `<div class="a-section-title">Conceptos y servicios sin inventario</div>
                   <div class="a-table-wrap">
                       <table class="a-table">
                           <thead><tr><th>Concepto</th><th class="a-right">Monto USD</th></tr></thead>
                           <tbody>${filasConceptos}</tbody>
                       </table>
                   </div>`
                : '';

            const filasPagos = pagos.length
                ? pagos.map((pago) => `<tr>
                    <td>${esc(pago.fecha)}</td>
                    <td class="a-right a-money">${money(pago.monto_usd)}</td>
                    <td class="a-right">${pago.tasa_bcv !== null ? Number(pago.tasa_bcv) : '—'}</td>
                    <td class="a-right">${pago.tasa_peso_dolar !== null ? Number(pago.tasa_peso_dolar) : '—'}</td>
                    <td>${esc(pago.metodo_pago || '—')}</td>
                    <td>${pago.comprobante ? '<a href="../../' + encodeURI(pago.comprobante) + '" target="_blank" rel="noopener">Ver</a>' : '—'}</td>
                    <td>${esc(pago.usuario_nombre || '—')}</td>
                </tr>`).join('')
                : '<tr><td colspan="7" class="a-muted a-center">Sin abonos registrados.</td></tr>';

            const productosPorId = {};
            (d.detalles || []).forEach((detalle) => {
                productosPorId[detalle.producto_id] = detalle.producto_nombre;
            });
            const sucursalesPorId = {
                [a.sucursal_origen]: a.origen_nombre,
                [a.sucursal_destino]: a.destino_nombre
            };
            const filasMovimientos = (d.movimientos || []).map((mov) => `<tr>
                <td>${esc(productosPorId[mov.producto_id] || ('Producto #' + mov.producto_id))}</td>
                <td>${esc(sucursalesPorId[mov.sucursal_id] || ('Sucursal #' + mov.sucursal_id))}</td>
                <td>${esc(mov.tipo)}</td>
                <td class="a-right">${qty(mov.cantidad)}</td>
                <td class="a-right">${qty(mov.stock_antes)} → ${qty(mov.stock_despues)}</td>
            </tr>`).join('');

            const filasBitacora = (d.bitacora || []).map((b) => `<tr>
                <td>${esc(b.created_at)}</td>
                <td>${esc(b.accion)}</td>
                <td>${esc(b.usuario_nombre || '—')}</td>
                <td>${esc(b.motivo || '—')}</td>
            </tr>`).join('');

            const deudaHtml = cuenta
                ? `<div class="a-section-title">Obligación económica</div>
                   <div class="a-summary-line"><span>Monto original</span><b>${money(original)}</b></div>
                   <div class="a-summary-line"><span>Total abonado</span><b>${money(abonado)}</b></div>
                   <div class="a-summary-line"><span>Saldo pendiente</span><b>${money(saldo)}</b></div>
                   <div class="a-summary-line"><span>Excedente</span><b>${money(excedente)}</b></div>
                   <div class="a-summary-line"><span>Estado de la deuda</span><b>${estadoDeuda}</b></div>`
                : `<div class="a-section-title">Obligación económica</div>
                   <div class="a-muted" style="font-size:12.5px;margin-bottom:8px;">${a.estado === 'APLICADO' ? 'Información financiera reservada a la sucursal deudora.' : 'Se generará automáticamente al aplicar la asignación.'}</div>`;

            const abonosHtml = cuenta
                ? `<div class="a-section-title">Abonos</div>
                   <div class="a-table-wrap">
                       <table class="a-table">
                           <thead><tr><th>Fecha</th><th class="a-right">Monto</th><th class="a-right">BCV</th><th class="a-right">Dólar/peso</th><th>Método</th><th>Comprobante</th><th>Usuario</th></tr></thead>
                           <tbody>${filasPagos}</tbody>
                       </table>
                   </div>`
                : '';

            document.getElementById('detalle-titulo').textContent = 'Asignación ' + (a.numero_documento || '');
            document.getElementById('detalle-body').innerHTML = `
                <div class="a-detail-grid">
                    <div class="a-detail-cell"><span>Fecha</span><strong>${esc(a.fecha)}</strong></div>
                    <div class="a-detail-cell"><span>Estado</span><strong>${estadoBadge(a.estado)}</strong></div>
                    <div class="a-detail-cell"><span>Origen</span><strong>${esc(a.origen_nombre)}</strong></div>
                    <div class="a-detail-cell"><span>Destino</span><strong>${esc(a.destino_nombre)}</strong></div>
                    <div class="a-detail-cell"><span>Usuario</span><strong>${esc(a.usuario_nombre)}</strong></div>
                    <div class="a-detail-cell"><span>Inventario origen</span><strong>${Number(a.descuenta_origen) === 1 ? 'Descontado' : 'No descontado'}</strong></div>
                    <div class="a-detail-cell"><span>Inventario destino</span><strong>${Number(a.suma_destino) === 1 ? 'Sumado' : 'No sumado'}</strong></div>
                    <div class="a-detail-cell"><span>Total</span><strong>${money(a.total)}</strong></div>
                </div>
                ${a.observaciones ? '<div class="a-muted" style="font-size:12.5px;margin-bottom:12px;"><strong style="color:var(--dash-text);">Observaciones:</strong> ' + esc(a.observaciones) + '</div>' : ''}

                <div class="a-section-title">Productos</div>
                <div class="a-table-wrap">
                    <table class="a-table">
                        <thead><tr><th>Producto</th><th class="a-right">Cantidad</th><th class="a-right">Costo unitario</th><th class="a-right">Subtotal</th></tr></thead>
                        <tbody>${filas || '<tr><td colspan="4" class="a-muted a-center">Sin productos.</td></tr>'}</tbody>
                    </table>
                </div>
                ${conceptosHtml}
                <div class="a-summary-line total"><span>Total asignado</span><b>${money(a.total)}</b></div>

                ${deudaHtml}
                ${abonosHtml}

                <div class="a-section-title">Movimientos de inventario</div>
                <div class="a-table-wrap">
                    <table class="a-table">
                        <thead><tr><th>Producto</th><th>Sucursal</th><th>Movimiento</th><th class="a-right">Cantidad inventario</th><th class="a-right">Stock</th></tr></thead>
                        <tbody>${filasMovimientos || '<tr><td colspan="5" class="a-muted a-center">Sin movimientos de inventario.</td></tr>'}</tbody>
                    </table>
                </div>

                <div class="a-section-title">Bitácora</div>
                <div class="a-table-wrap">
                    <table class="a-table">
                        <thead><tr><th>Fecha</th><th>Acción</th><th>Usuario</th><th>Motivo</th></tr></thead>
                        <tbody>${filasBitacora || '<tr><td colspan="4" class="a-muted a-center">Sin registros.</td></tr>'}</tbody>
                    </table>
                </div>`;

            document.getElementById('btn-detalle-pdf').href = 'asignacion_pdf.php?id=' + a.id;
            $('#modalDetalle').modal('show');
        }

        const btnNueva = document.getElementById('btn-nueva');
        if (btnNueva) {
            btnNueva.addEventListener('click', () => {
                resetFormulario();
                mostrarFormulario();
            });
        }
        document.getElementById('btn-volver').addEventListener('click', mostrarLista);
        document.getElementById('btn-cancelar-form').addEventListener('click', mostrarLista);
        document.getElementById('btn-filtrar').addEventListener('click', cargarLista);
        document.getElementById('btn-buscar-producto').addEventListener('click', buscarProductos);
        document.getElementById('buscar-producto').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProductos();
            }
        });
        document.getElementById('btn-guardar-borrador').addEventListener('click', guardarBorrador);
        document.getElementById('btn-guardar-aplicar').addEventListener('click', guardarYAplicar);
        document.getElementById('btn-rectificar').addEventListener('click', rectificar);
        document.getElementById('btn-agregar-concepto').addEventListener('click', agregarConcepto);
        document.getElementById('concepto-monto').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                agregarConcepto();
            }
        });

        document.getElementById('tabla-conceptos').addEventListener('click', (e) => {
            const boton = e.target.closest('[data-quitar-concepto]');
            if (!boton) return;
            conceptos.splice(Number(boton.dataset.quitarConcepto), 1);
            renderConceptos();
        });

        document.getElementById('resultados-productos').addEventListener('click', (e) => {
            const boton = e.target.closest('[data-resultado]');
            if (!boton) return;
            const producto = resultadosProductos[Number(boton.dataset.resultado)];
            if (producto) agregarProducto(producto);
        });

        document.getElementById('tabla-lineas').addEventListener('input', (e) => {
            if (!e.target.classList.contains('a-qty')) return;
            actualizarCantidadLinea(e.target);
        });

        document.getElementById('tabla-lineas').addEventListener('click', (e) => {
            const boton = e.target.closest('[data-quitar]');
            if (!boton) return;
            lineas.splice(Number(boton.dataset.quitar), 1);
            renderLineas();
        });

        document.getElementById('sucursal-origen').addEventListener('change', () => {
            if (lineas.length > 0) {
                lineas = [];
                resultadosProductos = [];
                $resultados.classList.remove('show');
                $resultados.innerHTML = '';
                Alerta.toast('info', 'Cambió la sucursal de origen. Se limpiaron los productos agregados.');
            }
            renderLineas();
        });

        document.getElementById('sucursal-destino').addEventListener('change', () => {
            const origen = document.getElementById('sucursal-origen').value;
            const destino = document.getElementById('sucursal-destino').value;
            if (origen && destino && origen === destino) {
                Alerta.toast('error', 'La sucursal de origen y destino no pueden ser la misma.');
            }
            renderLineas();
        });

        document.getElementById('descuenta-origen').addEventListener('change', () => {
            $resultados.classList.remove('show');
            $resultados.innerHTML = '';
            renderLineas();
        });
        document.getElementById('suma-destino').addEventListener('change', renderLineas);

        document.getElementById('tabla-asignaciones').addEventListener('click', (e) => {
            const boton = e.target.closest('[data-accion]');
            if (!boton) return;
            const id = Number(boton.dataset.id);
            const accion = boton.dataset.accion;
            if (accion === 'ver') verDetalle(id);
            if (accion === 'editar') abrirFormulario(asignaciones[id], false);
            if (accion === 'rectificar') abrirFormulario(asignaciones[id], true);
            if (accion === 'aplicar') aplicarItem(id);
            if (accion === 'eliminar') eliminarItem(id);
        });

        renderConceptos();
        renderLineas();
        cargarLista();
    </script>
</body>

</html>
