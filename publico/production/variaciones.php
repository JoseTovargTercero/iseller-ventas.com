<?php
require_once('includes/requires.php');

$tipo_u = (int) ($_SESSION['nivel'] ?? 2);
$bss_id = (int) ($_SESSION['bss_id'] ?? 0);

if ($tipo_u !== 1 && $tipo_u !== 2) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();

$sucursales = [];
if ($tipo_u === 1) {
    $stmt = $conexion->prepare("SELECT id, nombre, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $sucursales[] = $row;
    }
    $stmt->close();
} else {
    $sid = (int) ($_SESSION['sucursal'] ?? 0);
    $stmt = $conexion->prepare("SELECT id, nombre, principal FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
    $stmt->bind_param('ii', $sid, $bss_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $sucursales[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Variaciones de productos | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
    <style>
        .right_col {
            background: #1a1e24;
            min-height: 100vh;
            padding: 26px 30px !important;
        }

        .var-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
        }

        .var-eyebrow {
            color: #2dd4a0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .var-head h3 {
            color: #e8edf2;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 0;
        }

        .var-head p {
            color: #8892a0;
            font-size: 13px;
            margin: 6px 0 0;
            max-width: 660px;
        }

        .var-head-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .var-card .d-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .var-card .d-grid .var-btn {
            width: 100%;
        }

        .var-check-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
        }

        .var-status {
            color: #8892a0;
            font-size: 12px;
            min-height: 16px;
        }

        .var-card {
            background: #181b22;
            border: 1px solid #2e353e;
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, .12);
        }

        .var-card label {
            color: #8892a0;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .var-input,
        .var-card .form-control,
        .var-card .form-select {
            background: #12151a;
            border: 1px solid #2e353e;
            color: #e8edf2;
            border-radius: 9px;
            min-height: 40px;
        }

        .var-input:focus,
        .var-card .form-control:focus,
        .var-card .form-select:focus {
            background: #12151a;
            color: #e8edf2;
            border-color: #2dd4a0;
            box-shadow: 0 0 0 3px rgba(45, 212, 160, .12);
        }

        .var-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border-radius: 9px;
            border: 1px solid #2e353e;
            background: #12151a;
            color: #e8edf2;
            font-size: 12.5px;
            font-weight: 700;
            padding: 10px 14px;
            transition: all .16s ease;
            text-decoration: none !important;
        }

        .var-btn:hover {
            color: #2dd4a0;
            border-color: rgba(45, 212, 160, .55);
            background: rgba(45, 212, 160, .06);
        }

        .var-btn.primary {
            background: linear-gradient(135deg, #2dd4a0, #1fb885);
            border-color: #2dd4a0;
            color: #0c1712;
        }

        .var-btn.primary:hover {
            color: #0c1712;
            filter: brightness(1.06);
            background: linear-gradient(135deg, #2dd4a0, #1fb885);
        }

        .var-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .var-btn ion-icon,
        .var-icon-btn ion-icon {
            font-size: 16px;
        }

        .var-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .var-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            border: 1px solid #2e353e;
            background: rgba(255, 255, 255, .025);
            color: #b7c0cc;
            font-size: 11.5px;
            font-weight: 600;
        }

        .var-chip b {
            color: #e8edf2;
            font-weight: 700;
        }

        .var-chip.warn {
            border-color: rgba(245, 158, 11, .35);
            background: rgba(245, 158, 11, .08);
            color: #f5b44a;
        }

        .var-stats {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .var-stat {
            background: #12151a;
            border: 1px solid #272d36;
            border-radius: 10px;
            padding: 10px 12px;
            min-width: 0;
        }

        .var-stat span {
            display: block;
            color: #7e8896;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .var-stat strong {
            display: block;
            color: #e8edf2;
            font-size: 15px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .var-stat.accent strong {
            color: #2dd4a0;
        }

        .var-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .var-card-head h5 {
            color: #e8edf2;
            font-size: 15px;
            font-weight: 700;
            margin: 0;
        }

        .var-card-head p {
            color: #7e8896;
            font-size: 11.5px;
            margin: 4px 0 0;
        }

        .var-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 28px;
            padding: 0 9px;
            border-radius: 999px;
            background: rgba(45, 212, 160, .12);
            color: #2dd4a0;
            font-size: 12px;
            font-weight: 800;
        }

        .var-table {
            width: 100%;
            color: #e8edf2;
            font-size: 12.5px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .var-table th {
            color: #7e8896;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            border-bottom: 1px solid #2e353e;
            padding: 9px 10px;
            white-space: nowrap;
        }

        .var-table td {
            border-bottom: 1px solid #232830;
            padding: 10px;
            vertical-align: middle;
        }

        .var-table tr:last-child td {
            border-bottom: none;
        }

        .var-table tbody tr:hover td {
            background: rgba(255, 255, 255, .018);
        }

        .var-name {
            font-weight: 700;
            color: #e8edf2;
            line-height: 1.25;
        }

        .var-sub {
            color: #7e8896;
            font-size: 10.5px;
            margin-top: 2px;
        }

        .var-money {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .var-money.sale {
            color: #2dd4a0;
            font-weight: 700;
        }

        .badge-var {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-var.on {
            background: rgba(45, 212, 160, .12);
            color: #2dd4a0;
        }

        .badge-var.off {
            background: rgba(239, 90, 111, .12);
            color: #ef5a6f;
        }

        .badge-var::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
        }

        .var-actions {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .var-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 7px;
            border: 1px solid #2e353e;
            background: #12151a;
            color: #b7c0cc;
            transition: all .15s ease;
        }

        .var-icon-btn:hover {
            color: #2dd4a0;
            border-color: rgba(45, 212, 160, .55);
        }

        .var-icon-btn.danger:hover {
            color: #ef5a6f;
            border-color: rgba(239, 90, 111, .55);
        }

        .var-icon-btn.warn:hover {
            color: #f5b44a;
            border-color: rgba(245, 158, 11, .55);
        }

        .var-empty {
            padding: 34px 16px;
            text-align: center;
            color: #7e8896;
        }

        .var-empty ion-icon {
            display: block;
            font-size: 34px;
            color: #3a424d;
            margin-bottom: 8px;
        }

        .var-empty strong {
            display: block;
            color: #b7c0cc;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .var-empty span {
            font-size: 12px;
        }

        .modal-content {
            background: #181b22;
            color: #e8edf2;
            border: 1px solid #2e353e;
            border-radius: 14px;
        }

        .modal-header,
        .modal-footer {
            border-color: #2e353e;
            padding: 15px 20px;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 700;
        }

        .modal-body {
            padding: 18px 20px 8px;
        }

        .modal-body label {
            color: #8892a0;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .modal-body .var-check {
            margin-bottom: 0;
        }

        .modal-body .form-control,
        .modal-body .form-select {
            background: #12151a;
            border: 1px solid #2e353e;
            color: #e8edf2;
            border-radius: 9px;
            min-height: 38px;
        }

        .modal-body .form-control:focus,
        .modal-body .form-select:focus {
            background: #12151a;
            color: #e8edf2;
            border-color: #2dd4a0;
            box-shadow: 0 0 0 3px rgba(45, 212, 160, .12);
        }

        .var-section-title {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #e8edf2;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin: 6px 0 10px;
            padding-bottom: 7px;
            border-bottom: 1px solid #2e353e;
        }

        .var-section-title ion-icon {
            color: #2dd4a0;
            font-size: 15px;
        }

        .var-preview {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            background: rgba(45, 212, 160, .05);
            border: 1px solid rgba(45, 212, 160, .2);
            border-radius: 10px;
            padding: 12px;
            margin: 2px 0 12px;
        }

        .var-preview span {
            display: block;
            color: #7e8896;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .var-preview strong {
            color: #e8edf2;
            font-size: 15px;
            font-weight: 700;
        }

        .var-preview strong.accent {
            color: #2dd4a0;
        }

        .var-stock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 8px;
            margin-bottom: 10px;
        }

        .var-stock-row {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #12151a;
            border: 1px solid #272d36;
            border-radius: 9px;
            padding: 8px 10px;
        }

        .var-stock-row .stock-name {
            flex: 1;
            min-width: 0;
            color: #d8dee6;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .var-stock-row input[type=number] {
            width: 76px;
            min-height: 30px;
            padding: 4px 7px;
            background: #0d1014;
            border: 1px solid #2e353e;
            border-radius: 6px;
            color: #e8edf2;
            font-size: 12px;
            text-align: right;
        }

        .var-stock-row label {
            display: flex;
            align-items: center;
            gap: 4px;
            margin: 0;
            color: #7e8896;
            font-size: 10.5px;
            white-space: nowrap;
            cursor: pointer;
        }

        .var-help {
            color: #7e8896;
            font-size: 11px;
            line-height: 1.5;
            margin: 0 0 10px;
        }

        .var-check {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #b7c0cc;
            font-size: 12px;
            font-weight: 600;
            margin: 0;
            cursor: pointer;
        }

        .select2-container--default .select2-selection--single {
            background: #12151a;
            border: 1px solid #2e353e;
            height: 40px;
            border-radius: 9px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #e8edf2;
            line-height: 38px;
            padding-left: 10px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px;
        }

        .select2-dropdown {
            background: #181b22;
            border: 1px solid #2e353e;
            color: #e8edf2;
            border-radius: 9px;
            overflow: hidden;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            background: #12151a;
            border: 1px solid #2e353e;
            color: #e8edf2;
            border-radius: 6px;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: #2dd4a0;
            color: #111;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background: #20252d;
            color: #e8edf2;
        }

        .select2-container--default .select2-selection--multiple {
            background: #12151a;
            border: 1px solid #2e353e;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6f7a88;
        }

        @media (max-width: 1200px) {
            .var-stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .right_col {
                padding: 18px 14px !important;
            }

            .var-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .var-head h3 {
                font-size: 20px;
            }

            .var-preview {
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
            <div class="var-head">
                <div>
                    <div class="var-eyebrow">Inventario</div>
                    <h3>Variaciones de productos</h3>
                    <p>Selecciona un producto base y crea subproductos con sus propios precios, empaque y stock. Cada variación queda anclada al producto padre.</p>
                </div>
                <div class="var-head-actions">
                    <span class="var-status" id="var-status"></span>
                    <button type="button" class="var-btn" id="btn-refresh">
                        <ion-icon name="refresh-outline"></ion-icon> Actualizar
                    </button>
                </div>
            </div>

            <div class="var-card">
                <div class="row g-2 align-items-end">
                    <div class="col-lg-3 col-md-4">
                        <label for="sucursal">Sucursal</label>
                        <select id="sucursal" class="form-select var-input">
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-6 col-md-5">
                        <label for="padre">Producto base</label>
                        <select id="padre" class="form-select var-input"></select>
                    </div>
                    <div class="col-lg-3 col-md-3 d-grid gap-2">
                        <button type="button" class="var-btn primary" id="btn-nueva">
                            <ion-icon name="add-outline"></ion-icon> Nueva variación
                        </button>
                        <a href="productos.php" class="var-btn" id="btn-ficha" target="_blank" rel="noopener">
                            <ion-icon name="open-outline"></ion-icon> Ir a productos
                        </a>
                    </div>
                </div>

                <div class="var-meta" id="padre-meta">
                    <span class="var-chip">Selecciona un producto base para comenzar</span>
                </div>

                <div class="var-stats" id="padre-stats" style="display:none;">
                    <div class="var-stat"><span>Unidad</span><strong id="stat-unidad">—</strong></div>
                    <div class="var-stat"><span>Stock sucursal</span><strong id="stat-stock">—</strong></div>
                    <div class="var-stat"><span>Precio compra</span><strong id="stat-compra">—</strong></div>
                    <div class="var-stat"><span>% ganancia</span><strong id="stat-porcentaje">—</strong></div>
                    <div class="var-stat"><span>Und. por bulto</span><strong id="stat-unidades">—</strong></div>
                    <div class="var-stat accent"><span>Variaciones</span><strong id="stat-variaciones">0</strong></div>
                </div>
            </div>

            <div class="var-card">
                <div class="var-card-head">
                    <div>
                        <h5>Variaciones del producto</h5>
                        <p id="variaciones-sub">Selecciona un producto base.</p>
                    </div>
                    <span class="var-count" id="variaciones-count">0</span>
                </div>

                <div class="table-responsive">
                    <table class="var-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Código</th>
                                <th>Compra</th>
                                <th>%</th>
                                <th>Und/bulto</th>
                                <th>Venta est.</th>
                                <th>Stock</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="variaciones-body">
                            <tr><td colspan="9"><div class="var-empty"><ion-icon name="git-branch-outline"></ion-icon><strong>Sin variaciones</strong><span>Selecciona un producto base y crea su primera variación.</span></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal crear / editar variación -->
<div class="modal fade" id="modalVariacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-titulo">Nueva variación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" style="color:#8892a0;opacity:1;text-shadow:none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-variacion" autocomplete="off">
                <input type="hidden" name="variacion_id" id="variacion-id" value="">
                <div class="modal-body">
                    <div class="var-section-title"><ion-icon name="pricetag-outline"></ion-icon> Identificación</div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-8">
                            <label>Nombre de la variación *</label>
                            <input type="text" name="nombre" id="var-nombre" class="form-control" maxlength="255" placeholder="Ej. Lomo fino" required>
                        </div>
                        <div class="col-md-4">
                            <label>Código de barras</label>
                            <input type="text" name="codigo_barras" id="var-codigo" class="form-control" maxlength="100" placeholder="Opcional">
                        </div>
                        <div class="col-md-6">
                            <label>Categoría</label>
                            <select name="categoria" id="var-categoria" class="form-select">
                                <option value="">Sin categoría</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Proveedor</label>
                            <input type="text" name="proveedor" id="var-proveedor" class="form-control" maxlength="150" placeholder="Opcional">
                        </div>
                        <div class="col-md-4">
                            <label>Origen</label>
                            <select name="origen" id="var-origen" class="form-select">
                                <option value="v">Venezolano</option>
                                <option value="c">Colombiano</option>
                                <option value="m">Mixto</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Unidad de medida</label>
                            <select name="unidad" id="var-unidad" class="form-select">
                                <option value="UND">Unidad (UND)</option>
                                <option value="KG">Kilo (KG)</option>
                                <option value="LT">Litro (LT)</option>
                                <option value="GR">Gramo (GR)</option>
                                <option value="ML">Mililitro (ML)</option>
                                <option value="CAJA">Caja</option>
                                <option value="PAQ">Paquete</option>
                                <option value="DOC">Docena</option>
                                <option value="BULTO">Bulto</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Rendimiento</label>
                            <input type="number" step="0.0001" min="0" max="1.5" name="rendimiento" id="var-rendimiento" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="var-section-title"><ion-icon name="cash-outline"></ion-icon> Precios y empaque</div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label>Precio de compra</label>
                            <input type="number" step="0.01" min="0" name="precio_compra" id="var-precio" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label>Unidades por bulto</label>
                            <input type="number" step="0.0001" min="0.0001" name="cantidad_unidades" id="var-cantidad" class="form-control" value="1">
                        </div>
                        <div class="col-md-4">
                            <label>% de ganancia</label>
                            <input type="number" step="0.01" min="-100" max="10000" name="porcentaje" id="var-porcentaje" class="form-control" value="0">
                        </div>
                    </div>

                    <div class="var-preview" aria-live="polite">
                        <div><span>Costo unitario</span><strong id="preview-costo">$ 0,00</strong></div>
                        <div><span>Ganancia por unidad</span><strong id="preview-ganancia">$ 0,00</strong></div>
                        <div><span>Precio de venta est.</span><strong class="accent" id="preview-venta">$ 0,00</strong></div>
                    </div>

                    <div id="bloque-stock">
                        <div class="var-section-title"><ion-icon name="cube-outline"></ion-icon> Stock inicial por sucursal</div>
                        <p class="var-help">Puedes iniciar con stock cero y cargarlo después desde ajustes de stock. La variación queda disponible en todas las sucursales.</p>
                        <div class="var-stock-grid" id="stock-grid"></div>
                    </div>

                    <div class="var-check-row mt-2 mb-2">
                        <label class="var-check">
                            <input type="checkbox" name="seguimiento_estricto" id="var-estricto" value="1">
                            Seguimiento estricto
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btn-guardar-variacion">Crear variación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const API = 'db/variaciones_api.php';
    const $ = (id) => document.getElementById(id);
    const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const num = (v, dec = 2) => Number(v || 0).toLocaleString('es-VE', {minimumFractionDigits: dec, maximumFractionDigits: dec});
    const entero = (v) => Number(v || 0).toLocaleString('es-VE', {maximumFractionDigits: 4});
    const money = (v) => '$ ' + num(v, 2);

    let productos = [];
    let categorias = [];
    let variaciones = [];
    let padreId = 0;
    let editando = false;

    const SUCURSALES = <?= json_encode($sucursales, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]' ?>;

    const sucursalSeleccionada = () => $('sucursal').value;
    const status = (txt, error) => {
        const el = $('var-status');
        el.textContent = txt || '';
        el.style.color = error ? '#ef5a6f' : '#2dd4a0';
    };

    function toast(icon, title) {
        if (window.Swal && Swal.fire) {
            Swal.fire({
                icon: icon || 'success',
                title: title,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2200,
                timerProgressBar: true,
                background: '#181b22',
                color: '#e8edf2'
            });
        } else {
            status(title, icon === 'error');
        }
    }

    async function api(accion, data) {
        const fd = new FormData();
        fd.append('accion', accion);
        fd.append('sucursal', sucursalSeleccionada());
        Object.keys(data || {}).forEach((k) => fd.append(k, data[k]));
        const res = await fetch(API, {method: 'POST', body: fd});
        let json;
        try {
            json = await res.json();
        } catch (e) {
            throw new Error('El servidor devolvió una respuesta no válida.');
        }
        if (!json.ok) throw new Error(json.message || 'No se pudo completar la operación.');
        return json;
    }

    function initSelect2(selector, options) {
        if (!window.jQuery || !jQuery.fn.select2) return;
        const el = jQuery(selector);
        if (el.data('select2')) el.select2('destroy');
        el.select2(options);
    }

    function refrescarSelect2() {
        initSelect2('#padre', {placeholder: 'Buscar producto base...', width: '100%', allowClear: true});
        initSelect2('#var-categoria', {placeholder: 'Sin categoría', width: '100%', allowClear: true, dropdownParent: jQuery('#modalVariacion')});
    }

    function setPadre(valor) {
        $('padre').value = String(valor || '');
        if (window.jQuery && jQuery('#padre').data('select2')) {
            jQuery('#padre').val(String(valor || '')).trigger('change.select2');
        }
    }

    function productoBase() {
        return productos.find((p) => Number(p.id) === Number(padreId)) || null;
    }

    function pintarPadre() {
        const p = productoBase();
        const meta = $('padre-meta');
        const stats = $('padre-stats');

        if (!p) {
            meta.innerHTML = '<span class="var-chip">Selecciona un producto base para comenzar</span>';
            stats.style.display = 'none';
            $('variaciones-sub').textContent = 'Selecciona un producto base.';
            return;
        }

        meta.innerHTML = [
            '<span class="var-chip"><ion-icon name="cube-outline"></ion-icon> ' + esc(p.nombre) + '</span>',
            '<span class="var-chip">Categoría: <b>' + esc(p.categoria_nombre || 'Sin categoría') + '</b></span>',
            '<span class="var-chip">Código: <b>' + esc(p.codigo_barras || '—') + '</b></span>',
            p.modo_control === 'CANAL'
                ? '<span class="var-chip warn"><ion-icon name="warning-outline"></ion-icon> Modo canal: al crear una variación se normalizará el inventario del producto base.</span>'
                : '<span class="var-chip">Inventario propio por producto</span>'
        ].join('');

        stats.style.display = 'grid';
        $('stat-unidad').textContent = p.unidad || 'UND';
        $('stat-stock').textContent = entero(p.stock) + ' ' + (p.unidad || 'UND');
        $('stat-compra').textContent = money(p.precio_compra);
        $('stat-porcentaje').textContent = num(p.porcentaje, 2) + '%';
        $('stat-unidades').textContent = entero(p.cantidad_unidades);
        $('stat-variaciones').textContent = entero(p.variaciones || 0);
        $('variaciones-sub').textContent = 'Subproductos anclados a «' + p.nombre + '».';
    }

    async function cargarCategorias() {
        const json = await api('categorias');
        categorias = json.data || [];
        const sel = $('var-categoria');
        sel.innerHTML = '<option value="">Sin categoría</option>';
        categorias.forEach((c) => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            sel.appendChild(opt);
        });
        refrescarSelect2();
    }

    async function cargarProductos() {
        const padreActual = padreId || $('padre').value;
        const sel = $('padre');
        sel.innerHTML = '<option value="">Cargando productos...</option>';
        const json = await api('productos');
        productos = json.data || [];

        sel.innerHTML = '<option value="">-- Selecciona un producto base --</option>';
        productos.forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.id;
            const etiqueta = p.nombre + (p.modo_control === 'CANAL' ? ' (canal)' : '') + (Number(p.variaciones) > 0 ? ' · ' + p.variaciones + ' var.' : '');
            opt.textContent = etiqueta;
            sel.appendChild(opt);
        });
        refrescarSelect2();

        if (padreActual && productos.some((p) => Number(p.id) === Number(padreActual))) {
            setPadre(padreActual);
        } else if (!padreId && productos.length) {
            const conVariaciones = productos.find((p) => Number(p.variaciones) > 0);
            setPadre((conVariaciones || productos[0]).id);
        }
        padreId = Number($('padre').value) || 0;
        pintarPadre();
    }

    function precioVenta(v) {
        const cantidad = Math.max(0.0001, Number(v.cantidad_unidades) || 1);
        const unitario = (Number(v.precio_compra) || 0) / cantidad;
        return unitario * (1 + ((Number(v.porcentaje) || 0) / 100));
    }

    function filaVacia(mensaje) {
        return '<tr><td colspan="9"><div class="var-empty"><ion-icon name="git-branch-outline"></ion-icon><strong>' + esc(mensaje) + '</strong><span>Crea una variación para verla aquí.</span></div></td></tr>';
    }

    async function cargarVariaciones() {
        const cuerpo = $('variaciones-body');
        if (!padreId) {
            variaciones = [];
            cuerpo.innerHTML = filaVacia('Sin producto base');
            $('variaciones-count').textContent = '0';
            return;
        }

        cuerpo.innerHTML = '<tr><td colspan="9"><div class="var-empty"><ion-icon name="hourglass-outline"></ion-icon><strong>Cargando variaciones…</strong></div></td></tr>';
        const json = await api('variaciones', {padre_id: padreId});
        variaciones = json.data || [];

        const p = json.padre || null;
        if (p) {
            const indice = productos.findIndex((item) => Number(item.id) === Number(p.id));
            if (indice >= 0) {
                productos[indice].modo_control = p.modo_control;
                productos[indice].nombre = p.nombre;
                productos[indice].unidad = p.unidad;
                productos[indice].precio_compra = p.precio_compra;
                productos[indice].cantidad_unidades = p.cantidad_unidades;
                productos[indice].porcentaje = p.porcentaje;
            }
            pintarPadre();
        }

        $('variaciones-count').textContent = String(variaciones.length);
        if (!variaciones.length) {
            cuerpo.innerHTML = filaVacia('Sin variaciones todavía');
            return;
        }

        cuerpo.innerHTML = variaciones.map((v) => {
            const visible = Number(v.visible_venta) === 1;
            const categoria = v.categoria_nombre || 'Sin categoría';
            return '<tr>' +
                '<td><div class="var-name">' + esc(v.nombre) + '</div><div class="var-sub">' + esc(categoria) + ' · ' + esc(v.unidad || 'UND') + '</div></td>' +
                '<td>' + (v.codigo_barras ? esc(v.codigo_barras) : '<span class="var-sub">—</span>') + '</td>' +
                '<td class="var-money">' + money(v.precio_compra) + '</td>' +
                '<td class="var-money">' + num(v.porcentaje, 2) + '%</td>' +
                '<td class="var-money">' + entero(v.cantidad_unidades) + '</td>' +
                '<td class="var-money sale">' + money(precioVenta(v)) + '</td>' +
                '<td class="var-money">' + entero(v.stock) + ' ' + esc(v.unidad || 'UND') + '</td>' +
                '<td><span class="badge-var ' + (visible ? 'on' : 'off') + '">' + (visible ? 'En venta' : 'Pausado') + '</span></td>' +
                '<td><div class="var-actions">' +
                    '<button type="button" class="var-icon-btn" title="Editar" data-editar="' + v.id + '"><ion-icon name="create-outline"></ion-icon></button>' +
                    '<button type="button" class="var-icon-btn ' + (visible ? 'warn' : '') + '" title="' + (visible ? 'Pausar' : 'Activar') + '" data-estado="' + v.id + '" data-visible="' + (visible ? 0 : 1) + '"><ion-icon name="' + (visible ? 'pause-outline' : 'play-outline') + '"></ion-icon></button>' +
                    '<button type="button" class="var-icon-btn warn" title="Desanclar" data-desanclar="' + v.id + '"><ion-icon name="unlink-outline"></ion-icon></button>' +
                    '<button type="button" class="var-icon-btn danger" title="Eliminar" data-eliminar="' + v.id + '"><ion-icon name="trash-outline"></ion-icon></button>' +
                '</div></td>' +
            '</tr>';
        }).join('');
    }

    async function recargarTodo() {
        await cargarVariaciones();
    }

    function pintarStockSucursales(stocks) {
        const grid = $('stock-grid');
        const lista = stocks || [];
        if (!lista.length) {
            grid.innerHTML = '<div class="var-help">No hay sucursales disponibles.</div>';
            return;
        }
        grid.innerHTML = lista.map((s, i) => {
            const id = 'stock-suc-' + s.id;
            return '<div class="var-stock-row">' +
                '<span class="stock-name" title="' + esc(s.nombre) + '">' + esc(s.nombre) + (Number(s.principal) === 1 ? ' · Principal' : '') + '</span>' +
                '<input type="number" step="0.0001" min="0" id="' + id + '" data-sucursal="' + s.id + '" value="0" aria-label="Stock en ' + esc(s.nombre) + '">' +
                '<label><input type="checkbox" data-visible-sucursal="' + s.id + '" checked> Visible</label>' +
            '</div>';
        }).join('');
    }

    function leerStocks() {
        const filas = Array.from(document.querySelectorAll('#stock-grid [data-sucursal]'));
        return filas.map((input) => {
            const sid = input.dataset.sucursal;
            const visible = document.querySelector('#stock-grid [data-visible-sucursal="' + sid + '"]');
            return {
                sucursal: Number(sid),
                stock: Number(input.value || 0),
                visible: visible && visible.checked ? 1 : 0
            };
        });
    }

    function actualizarPreview() {
        const precio = Number($('var-precio').value || 0);
        const cantidad = Math.max(0.0001, Number($('var-cantidad').value) || 1);
        const porcentaje = Number($('var-porcentaje').value || 0);
        const unitario = precio / cantidad;
        const ganancia = unitario * (porcentaje / 100);
        const venta = unitario + ganancia;
        $('preview-costo').textContent = money(unitario);
        $('preview-ganancia').textContent = money(ganancia);
        $('preview-venta').textContent = money(venta);
    }

    function limpiarFormulario() {
        $('form-variacion').reset();
        $('variacion-id').value = '';
        $('var-cantidad').value = '1';
        $('var-precio').value = '0';
        $('var-porcentaje').value = '0';
        $('var-rendimiento').value = '0';
        $('var-estricto').checked = false;
        if (window.jQuery && jQuery('#var-categoria').data('select2')) {
            jQuery('#var-categoria').val('').trigger('change.select2');
        }
        actualizarPreview();
    }

    function prepararNueva() {
        const p = productoBase();
        if (!p) {
            status('Selecciona un producto base.', true);
            return;
        }
        editando = false;
        limpiarFormulario();
        $('modal-titulo').textContent = 'Nueva variación de ' + p.nombre;
        $('btn-guardar-variacion').textContent = 'Crear variación';
        $('bloque-stock').style.display = '';
        $('var-nombre').value = '';
        $('var-nombre').placeholder = 'Ej. Corte de ' + p.nombre;
        $('var-precio').value = p.precio_compra || 0;
        $('var-cantidad').value = p.cantidad_unidades || 1;
        $('var-porcentaje').value = p.porcentaje || 0;
        $('var-proveedor').value = p.proveedor || '';
        $('var-origen').value = p.origen || 'v';
        $('var-unidad').value = p.unidad || 'UND';
        $('var-rendimiento').value = 0;
        $('var-codigo').value = '';
        pintarStockSucursales(SUCURSALES);
        if (window.jQuery && jQuery('#var-categoria').data('select2')) {
            jQuery('#var-categoria').val(String(p.categoria || '')).trigger('change.select2');
        } else {
            $('var-categoria').value = String(p.categoria || '');
        }
        actualizarPreview();
        jQuery('#modalVariacion').modal('show');
        setTimeout(() => $('var-nombre').focus(), 250);
    }

    function prepararEdicion(v) {
        if (!v) return;
        editando = true;
        limpiarFormulario();
        $('modal-titulo').textContent = 'Editar ' + v.nombre;
        $('btn-guardar-variacion').textContent = 'Guardar cambios';
        $('bloque-stock').style.display = 'none';
        $('variacion-id').value = v.id;
        $('var-nombre').value = v.nombre || '';
        $('var-codigo').value = v.codigo_barras || '';
        $('var-precio').value = v.precio_compra || 0;
        $('var-cantidad').value = v.cantidad_unidades || 1;
        $('var-porcentaje').value = v.porcentaje || 0;
        $('var-proveedor').value = v.proveedor || '';
        $('var-origen').value = v.origen || 'v';
        $('var-unidad').value = v.unidad || 'UND';
        $('var-rendimiento').value = v.rendimiento || 0;
        $('var-estricto').checked = Number(v.seguimiento_estricto) === 1;
        if (window.jQuery && jQuery('#var-categoria').data('select2')) {
            jQuery('#var-categoria').val(String(v.categoria || '')).trigger('change.select2');
        } else {
            $('var-categoria').value = String(v.categoria || '');
        }
        actualizarPreview();
        jQuery('#modalVariacion').modal('show');
    }

    async function refrescarProductoBase() {
        const padreActual = padreId;
        await cargarProductos();
        setPadre(padreActual);
        padreId = Number($('padre').value) || 0;
        pintarPadre();
    }

    $('sucursal').addEventListener('change', async () => {
        try {
            status('');
            await cargarProductos();
            await recargarTodo();
        } catch (e) {
            status(e.message, true);
        }
    });

    $('padre').addEventListener('change', () => {
        padreId = Number($('padre').value) || 0;
        pintarPadre();
        recargarTodo().catch((e) => status(e.message, true));
    });

    $('btn-refresh').addEventListener('click', async () => {
        try {
            status('Actualizando…');
            await cargarProductos();
            await recargarTodo();
            status('Datos actualizados.');
        } catch (e) {
            status(e.message, true);
        }
    });

    $('btn-nueva').addEventListener('click', prepararNueva);

    $('btn-ficha').addEventListener('click', (e) => {
        if (!padreId) {
            e.preventDefault();
            status('Selecciona un producto base.', true);
        }
    });

    ['var-precio', 'var-cantidad', 'var-porcentaje'].forEach((id) => {
        $(id).addEventListener('input', actualizarPreview);
        $(id).addEventListener('change', actualizarPreview);
    });

    $('form-variacion').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const fd = new FormData(form);
        const data = {};
        fd.forEach((v, k) => { data[k] = v; });
        data.seguimiento_estricto = form.querySelector('[name=seguimiento_estricto]').checked ? 1 : 0;

        try {
            $('btn-guardar-variacion').disabled = true;
            if (editando) {
                if (!data.variacion_id) throw new Error('No se identificó la variación a editar.');
                const json = await api('actualizar', data);
                jQuery('#modalVariacion').modal('hide');
                await refrescarProductoBase();
                await cargarVariaciones();
                toast('success', json.message || 'Variación actualizada.');
            } else {
                if (!padreId) throw new Error('Selecciona un producto base.');
                data.padre_id = padreId;
                data.stocks = JSON.stringify(leerStocks());
                const json = await api('crear', data);
                jQuery('#modalVariacion').modal('hide');
                await cargarProductos();
                setPadre(padreId);
                await cargarVariaciones();
                toast('success', json.message || 'Variación creada.');
                if (json.padre_normalizado) {
                    status('El producto base se normalizó para usar inventario por producto.');
                }
            }
        } catch (err) {
            status(err.message, true);
            if (window.Swal && Swal.fire) {
                Swal.fire({icon: 'error', title: 'No se pudo guardar', text: err.message, background: '#181b22', color: '#e8edf2', confirmButtonColor: '#2dd4a0'});
            }
        } finally {
            $('btn-guardar-variacion').disabled = false;
        }
    });

    $('variaciones-body').addEventListener('click', async (e) => {
        const editar = e.target.closest('[data-editar]');
        const estadoBtn = e.target.closest('[data-estado]');
        const desanclar = e.target.closest('[data-desanclar]');
        const eliminar = e.target.closest('[data-eliminar]');

        if (editar) {
            const v = variaciones.find((item) => Number(item.id) === Number(editar.dataset.editar));
            prepararEdicion(v);
            return;
        }

        if (estadoBtn) {
            try {
                const visible = Number(estadoBtn.dataset.visible) === 1 ? 1 : 0;
                const json = await api('estado', {variacion_id: estadoBtn.dataset.estado, visible: visible});
                await cargarVariaciones();
                toast('success', json.message);
            } catch (err) {
                status(err.message, true);
            }
            return;
        }

        if (desanclar) {
            const v = variaciones.find((item) => Number(item.id) === Number(desanclar.dataset.desanclar));
            const r = await Swal.fire({
                title: '¿Desanclar variación?',
                html: '«<strong>' + esc(v ? v.nombre : 'esta variación') + '</strong>» dejará de pertenecer al producto base, pero seguirá existiendo como producto independiente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, desanclar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#f5b44a',
                cancelButtonColor: '#3a424d',
                background: '#181b22',
                color: '#e8edf2'
            });
            if (!r.isConfirmed) return;
            try {
                const json = await api('desanclar', {variacion_id: desanclar.dataset.desanclar});
                await cargarProductos();
                setPadre(padreId);
                await cargarVariaciones();
                toast('success', json.message);
            } catch (err) {
                status(err.message, true);
            }
            return;
        }

        if (eliminar) {
            const v = variaciones.find((item) => Number(item.id) === Number(eliminar.dataset.eliminar));
            const r = await Swal.fire({
                title: '¿Eliminar variación?',
                html: 'Se eliminará «<strong>' + esc(v ? v.nombre : 'esta variación') + '</strong>» y su stock. Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef5a6f',
                cancelButtonColor: '#3a424d',
                background: '#181b22',
                color: '#e8edf2'
            });
            if (!r.isConfirmed) return;
            try {
                const json = await api('eliminar', {variacion_id: eliminar.dataset.eliminar});
                await cargarProductos();
                setPadre(padreId);
                await cargarVariaciones();
                toast('success', json.message);
            } catch (err) {
                status(err.message, true);
                Swal.fire({icon: 'error', title: 'No se pudo eliminar', text: err.message, background: '#181b22', color: '#e8edf2', confirmButtonColor: '#2dd4a0'});
            }
        }
    });

    function limpiarAlCerrarModal() {
        editando = false;
        limpiarFormulario();
    }

    if (window.jQuery) {
        jQuery('#modalVariacion').on('hidden.bs.modal', limpiarAlCerrarModal);
    } else {
        $('modalVariacion').addEventListener('hidden.bs.modal', limpiarAlCerrarModal);
    }

    (async function iniciar() {
        try {
            await cargarCategorias();
            await cargarProductos();
            await cargarVariaciones();
        } catch (e) {
            status(e.message, true);
        }
    })();
})();
</script>
</body>
</html>
