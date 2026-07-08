<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {

  $topnav = topnav();

  $stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ? ORDER BY principal DESC");
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
  <html lang="es">

  <head>
    <title>Lista de Ventas</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
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

      .btn-dash-action {
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
        cursor: pointer;
      }

      .btn-dash-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(45, 212, 160, 0.35);
        color: #fff;
      }

      .btn-dash-action ion-icon {
        font-size: 16px;
      }

      /* ─── Nav tabs ─── */
      .nav-tabs.dash-tabs {
        border-bottom: 1px solid var(--dash-border);
        gap: 4px;
      }

      .nav-tabs.dash-tabs .nav-link {
        border: none;
        color: var(--dash-text-muted);
        font-size: 13px;
        font-weight: 500;
        padding: 10px 20px;
        border-radius: 8px 8px 0 0;
        transition: all 0.2s ease;
        background: transparent;
      }

      .nav-tabs.dash-tabs .nav-link:hover {
        color: var(--dash-text);
        background: rgba(45, 212, 160, 0.04);
      }

      .nav-tabs.dash-tabs .nav-link.active {
        color: var(--dash-mint);
        background: transparent;
        box-shadow: inset 0 -2px 0 var(--dash-mint);
      }

      /* ─── Table ─── */
      .dash-table-wrap {
        overflow-x: auto;
        padding: 0 16px 16px;
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

      .dash-table .text-center {
        text-align: center;
      }

      .dash-table .text-end {
        text-align: right;
      }

      /* info-item (payment methods / currency totals) */
      .info-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 22px;
        border-bottom: 1px solid rgba(46, 53, 62, .5);
        transition: background .15s ease, padding-left .2s ease;
        position: relative;
      }

      .info-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 6px;
        bottom: 6px;
        width: 3px;
        border-radius: 0 3px 3px 0;
        opacity: 0;
        background: var(--accent, var(--dash-mint));
        transition: opacity .2s ease;
      }

      .info-item:hover::before {
        opacity: 1;
      }

      .info-item:last-child {
        border-bottom: none;
      }

      .info-item:hover {
        background: rgba(45, 212, 160, .03);
        padding-left: 26px;
      }

      .info-item-left {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
      }

      .info-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--accent, var(--dash-mint)) 15%, transparent);
        flex-shrink: 0;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
      }

      .info-item:hover .info-icon {
        transform: scale(1.05);
        box-shadow: 0 0 20px color-mix(in srgb, var(--accent, var(--dash-mint)) 25%, transparent);
      }

      .info-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 12px;
        transition: transform .3s ease;
      }

      .info-item:hover .info-icon img {
        transform: scale(1.08);
      }

      .info-icon .avatar-letter {
        font-size: 18px;
        font-weight: 700;
        transition: transform .2s ease;
      }

      .info-item:hover .info-icon .avatar-letter {
        transform: scale(1.1);
      }

      .info-text {
        min-width: 0;
      }

      .info-text h6 {
        font-size: 13px;
        font-weight: 600;
        color: var(--dash-text);
        margin: 0;
        line-height: 1.3;
      }

      .info-text small {
        font-size: 11px;
        color: var(--dash-text-muted);
        display: block;
      }

      .info-text p {
        margin: 0;
      }

      .info-value {
        font-size: 17px;
        font-weight: 700;
        color: var(--dash-text);
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        transition: color .2s ease;
      }

      .info-item:hover .info-value {
        color: var(--dash-mint);
      }

      .info-value small {
        font-size: 11px;
        color: var(--dash-text-muted);
        font-weight: 400;
        margin-left: 2px;
      }

      /* Date input */
      .dash-date-input {
        background: var(--dash-bg);
        border: 1px solid var(--dash-border);
        color: var(--dash-text);
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 13px;
        transition: border-color .2s ease;
      }

      .dash-date-input:focus {
        border-color: var(--dash-mint);
        outline: none;
        box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
      }

      /* Modal */
      .modal-dash .modal-content {
        background: var(--dash-card);
        border: 1px solid var(--dash-border);
        border-radius: 14px;
      }

      .modal-dash .modal-header {
        border-bottom: 1px solid var(--dash-border);
        padding: 16px 22px;
      }

      .modal-dash .modal-title {
        color: var(--dash-text);
        font-weight: 600;
        font-size: 16px;
      }

      .modal-dash .close {
        color: var(--dash-text-muted);
        font-size: 24px;
        opacity: .7;
        transition: opacity .2s;
        background: none;
        border: none;
      }

      .modal-dash .close:hover {
        opacity: 1;
      }

      .modal-dash .modal-body {
        padding: 20px 22px;
      }

      .modal-dash .modal-footer {
        border-top: 1px solid var(--dash-border);
        padding: 14px 22px;
      }

      .modal-dash .form-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--dash-text-muted);
        margin-bottom: 6px;
        display: block;
      }

      .modal-dash .form-control {
        background: var(--dash-bg);
        border: 1px solid var(--dash-border);
        color: var(--dash-text);
        border-radius: 8px;
        padding: 9px 14px;
        font-size: 13px;
        width: 100%;
        transition: border-color .2s ease;
      }

      .modal-dash .form-control:focus {
        border-color: var(--dash-mint);
        outline: none;
        box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
      }

      .btn-dash-secondary {
        background: rgba(255, 255, 255, .06);
        border: 1px solid var(--dash-border);
        color: var(--dash-text-muted);
        border-radius: 8px;
        padding: 9px 20px;
        font-size: 13px;
        font-weight: 500;
        transition: all .2s ease;
        cursor: pointer;
      }

      .btn-dash-secondary:hover {
        border-color: var(--dash-mint);
        color: var(--dash-mint);
      }

      /* DataTables overrides for dark theme */
      .dataTables_wrapper .dataTables_filter input,
      .dataTables_wrapper .dataTables_length select {
        background: var(--dash-bg) !important;
        border: 1px solid var(--dash-border) !important;
        color: var(--dash-text) !important;
        border-radius: 8px !important;
        padding: 6px 12px !important;
      }

      .dataTables_wrapper .dataTables_info,
      .dataTables_wrapper .dataTables_length,
      .dataTables_wrapper .dataTables_filter label {
        color: var(--dash-text-muted) !important;
        font-size: 13px !important;
      }

      .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: var(--dash-text-muted) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 6px !important;
        margin: 0 2px !important;
        padding: 4px 12px !important;
        background: transparent !important;
      }

      .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--dash-mint) !important;
        border-color: var(--dash-mint) !important;
        color: #fff !important;
      }

      .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        border-color: var(--dash-mint) !important;
        color: var(--dash-mint) !important;
      }

      .tab-pane.fade {
        background: transparent;
      }

      .tab-pane.fade:not(.show) {
        display: none;
      }

      /* ─── Table action buttons ─── */
      .btn-detalles,
      .btn-eliminar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid var(--dash-border);
        background: transparent;
        color: var(--dash-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s ease;
        padding: 0;
      }

      .btn-detalles:hover {
        border-color: var(--dash-mint);
        color: var(--dash-mint);
      }

      .btn-eliminar:hover {
        border-color: #ef5a6f;
        color: #ef5a6f;
      }

      .btn-detalles ion-icon,
      .btn-eliminar ion-icon {
        font-size: 16px;
      }

      /* ─── SweetAlert2 dark theme overrides ─── */
      .swal2-popup {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 14px !important;
        padding: 0 !important;
      }

      .swal2-modal {
        font-family: inherit;
      }

      .swal2-title {
        color: var(--dash-text) !important;
        font-size: 18px !important;
        font-weight: 600 !important;
        padding: 20px 22px 10px !important;
      }

      .swal2-html-container {
        color: var(--dash-text-muted) !important;
        font-size: 13px !important;
        padding: 0 22px 16px !important;
        text-align: left !important;
      }

      .swal2-html-container h1 {
        font-size: 16px;
        color: var(--dash-text);
        margin-bottom: 12px;
      }

      .swal2-html-container h1 i {
        color: var(--dash-mint);
        margin-right: 6px;
      }

      .swal2-close {
        color: var(--dash-text-muted) !important;
        font-size: 28px !important;
        transition: opacity .2s !important;
      }

      .swal2-close:hover {
        opacity: .8 !important;
      }

      .swal2-confirm {
        background: linear-gradient(135deg, #2dd4a0, #25b88a) !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 9px 24px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        box-shadow: 0 3px 12px rgba(45, 212, 160, .25) !important;
        color: #fff !important;
      }

      .swal2-deny {
        border-radius: 8px !important;
        padding: 9px 24px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
      }

      .swal2-cancel {
        background: rgba(255, 255, 255, .06) !important;
        border: 1px solid var(--dash-border) !important;
        color: var(--dash-text-muted) !important;
        border-radius: 8px !important;
        padding: 9px 24px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
      }

      .swal2-icon {
        margin-top: 16px;
      }

      .swal2-actions {
        padding: 0 22px 20px !important;
      }

      /* Table inside Swal */
      .swal2-html-container table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-top: 8px;
      }

      .swal2-html-container table thead th {
        padding: 10px 12px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .3px;
        color: var(--dash-text-muted);
        border-bottom: 1px solid var(--dash-border);
      }

      .swal2-html-container table tbody td {
        padding: 10px 12px;
        color: var(--dash-text);
        border-bottom: 1px solid rgba(46, 53, 62, .4);
      }

      .swal2-html-container table tbody tr:hover {
        background: rgba(45, 212, 160, .03);
      }

      .swal2-html-container table tbody tr:last-child td {
        border-bottom: none;
      }

      .swal2-loader {
        border-color: var(--dash-mint) transparent var(--dash-mint) transparent !important;
      }

      .swal2-show {
        animation: swal2-show-custom .25s ease !important;
      }

      @keyframes swal2-show-custom {
        0% {
          transform: scale(.92);
          opacity: 0;
        }

        100% {
          transform: scale(1);
          opacity: 1;
        }
      }

      .table-info-cell {
        background: rgba(45, 212, 160, .04);
      }

      .table-warning-cell {
        background: rgba(245, 180, 91, .08);
      }
    </style>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php echo $menu ?>
        <?php echo $topnav ?>

        <div class="right_col">
          <div class="d-flex justify-content-end dash-header">

            <?php if ($_SESSION["nivel"] == 1): ?>
              <div style="align-self:anchor-center;">
                <button type="button" class="btn-dash-action" data-toggle="modal" data-target="#filterModal">
                  <ion-icon name="funnel-outline"></ion-icon> Aplicar Filtro
                </button>
              </div>
            <?php endif; ?>
          </div>

          <div class="row">
            <div class="col-lg-12 mb-4">
              <div class="dash-panel">
                <div class="panel-header">
                  <div>
                    <h6><ion-icon name="receipt-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Listado de ventas</h6>
                    <small style="color:var(--dash-text-muted);font-size:11px;">Los créditos otorgados se visualizarán en la lista; sin embargo, no serán considerados en la totalización hasta su cancelación.</small>
                  </div>
                  <input type="date" class="dash-date-input" name="fechaSolic" id="fechaSolic">
                </div>
                <div class="panel-body p-0">
                  <div class="dash-table-wrap">
                    <table id="datatable" class="dash-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>T</th>
                          <th>Pago por</th>
                          <th>Usuario</th>
                          <th>Fecha</th>
                          <th>Monto</th>
                          <th>COP</th>
                          <th>Bs</th>
                          <th>Cliente</th>
                          <th>Detalles</th>
                        </tr>
                      </thead>
                      <tbody id="datos-tabla"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-6 mb-4">
              <div class="dash-panel">
                <div class="panel-header">
                  <h6><ion-icon name="card-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Métodos de pago</h6>
                </div>
                <div class="panel-body p-0" id="paymentMethodsList">
                  <div class="info-item" style="--accent:#2dd4a0;">
                    <div class="info-item-left">
                      <div class="info-icon"><img src="images/PUNTO-DE-VENTA.png" alt="PUNTO DE VENTA"></div>
                      <div class="info-text">
                        <h6>PUNTO DE VENTA</h6><small>Pago con punto</small>
                      </div>
                    </div>
                    <div class="info-value"><span id="total_Punto"></span> <small>Bs</small></div>
                  </div>
                  <div class="info-item" style="--accent:#5b9cf5;">
                    <div class="info-item-left">
                      <div class="info-icon"><img src="images/PAGO-MOVIL.png" alt="PAGO MOVIL"></div>
                      <div class="info-text">
                        <h6>PAGO MÓVIL</h6><small>Transferencia telefónica</small>
                      </div>
                    </div>
                    <div class="info-value"><span id="total_Pmovil"></span> <small>Bs</small></div>
                  </div>
                  <div class="info-item" style="--accent:#a78bfa;">
                    <div class="info-item-left">
                      <div class="info-icon"><img src="images/TRANSFERENCIA.png" alt="TRANSFERENCIA"></div>
                      <div class="info-text">
                        <h6>TRANSFERENCIA</h6><small>Pago bancario</small>
                      </div>
                    </div>
                    <div class="info-value"><span id="total_Transferencia"></span> <small>Bs</small></div>
                  </div>
                  <div class="info-item" style="--accent:#f472b6;">
                    <div class="info-item-left">
                      <div class="info-icon"><img src="images/BIOPAGO.png" alt="BIOPAGO"></div>
                      <div class="info-text">
                        <h6>BIOPAGO</h6><small>Pago biométrico</small>
                      </div>
                    </div>
                    <div class="info-value"><span id="total_Biopago"></span> <small>Bs</small></div>
                  </div>
                  <div class="info-item" style="--accent:#f5b45b;">
                    <div class="info-item-left">
                      <div class="info-icon"><img src="images/EFECTIVO-BOLIVAR.png" alt="EFECTIVO"></div>
                      <div class="info-text">
                        <h6>EFECTIVO BS</h6><small>Pago en efectivo</small>
                      </div>
                    </div>
                    <div class="info-value"><span id="total_Efectivo"></span> <small>Bs</small></div>
                  </div>
                  <div class="info-item" style="--accent:#34d399;">
                    <div class="info-item-left">
                      <div class="info-icon"><img src="images/EFECTIVO-DOLAR.png" alt="DOLARES"></div>
                      <div class="info-text">
                        <h6>DÓLARES</h6><small>Pago en USD</small>
                      </div>
                    </div>
                    <div class="info-value"><span id="total_Dolares"></span> <small>$</small></div>
                  </div>
                  <div class="info-item" style="--accent:#f97316;">
                    <div class="info-item-left">
                      <div class="info-icon"><img src="images/EFECTIVO-PESOS.png" alt="PESOS"></div>
                      <div class="info-text">
                        <h6>PESOS</h6><small>Pago en COP</small>
                      </div>
                    </div>
                    <div class="info-value"><span id="total_pesos"></span> <small>Cop</small></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-6 mb-4">
              <div class="dash-panel">
                <div class="panel-header">
                  <h6><ion-icon name="stats-chart-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Totales por moneda</h6>
                </div>
                <div class="panel-body p-0">
                  <?php
                  $items = [
                    ['titulo' => 'Bolivares', 'subtitulo' => 'ganacias_Bolivares', 'valor' => 'valor_Bolivares', 'accent' => '#f5b45b', 'letra' => 'B'],
                    ['titulo' => 'Dolares',   'subtitulo' => 'ganacias_Dolares',   'valor' => 'valor_Dolares',   'accent' => '#2dd4a0', 'letra' => '$'],
                    ['titulo' => 'Pesos',     'subtitulo' => 'ganacias_Pesos',     'valor' => 'valor_Pesos',     'accent' => '#8892a0', 'letra' => 'P'],
                    ['titulo' => 'Mayor',     'subtitulo' => 'ganacias_Mayor',     'valor' => 'valor_Mayor',     'accent' => '#a78bfa', 'letra' => 'M'],
                    ['titulo' => 'Detal',     'subtitulo' => 'ganacias_Detal',     'valor' => 'valor_Detal',     'accent' => '#f472b6', 'letra' => 'D'],
                  ];
                  foreach ($items as $item):
                  ?>
                    <div class="info-item" style="--accent:<?= $item['accent'] ?>;">
                      <div class="info-item-left">
                        <div class="info-icon" style="--accent:<?= $item['accent'] ?>;">
                          <span class="avatar-letter" style="color:<?= $item['accent'] ?>;"><?= $item['letra'] ?></span>
                        </div>
                        <div class="info-text">
                          <h6><?= $item['titulo'] ?></h6><small class="d-none" id="<?= $item['subtitulo'] ?>"></small>
                        </div>
                      </div>
                      <div class="info-value"><span id="<?= $item['valor'] ?>"></span></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Modal -->
        <div class="modal fade modal-dash" id="filterModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Aplicar filtro</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label" for="sucursal_selector">Sucursal</label>
                  <select class="form-control" id="sucursal_selector" name="sucursal_a_editar">
                    <?php if (count($sucursales) > 1): ?>
                      <option value="">Todas las sucursales</option>
                    <?php endif; ?>
                    <?php foreach ($sucursales as $row): ?>
                      <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="usuario">Usuario</label>
                  <select id="usuario" class="form-control">
                    <option value="todos">-- Seleccione --</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn-dash-secondary" data-dismiss="modal">Cerrar</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="../build/js/global-loader.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
      let table = new DataTable('#datatable');
      const periodo = 'dia';
    </script>
    <script src="../build/js/info_ventas.js"></script>



    <script>
      document.getElementById('sucursal_selector').addEventListener('change', function() {
        const sucursalId = this.value;
        const usuarioSelect = document.getElementById('usuario');
        usuarioSelect.innerHTML = '<option value="todos">-- Seleccione --</option>';

        if (sucursalId === 'todas') return;

        const usuariosFiltrados = <?php echo json_encode($usuarios); ?>.filter(u => u.id_sucursal == sucursalId);
        if (usuariosFiltrados.length > 0) {
          usuariosFiltrados.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.nombre;
            usuarioSelect.appendChild(opt);
          });
        } else {
          usuarioSelect.innerHTML = '<option value="">No hay usuarios disponibles</option>';
        }
      });

      function confirmarEliminar(id) {
        Swal.fire({
          title: 'Eliminar Venta',
          text: '¿Está seguro de eliminar esta venta? Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          cancelButtonText: 'Cancelar',
          confirmButtonText: 'Eliminar',
          confirmButtonColor: '#ef5a6f',
        }).then(result => {
          if (result.isConfirmed) {
            eliminarVenta(id);
          }
        });
      }

      function eliminarVenta(id) {
        $.ajax({
            url: '../../configurar/deleteVentaAjax.php',
            type: 'POST',
            dataType: 'html',
            data: {
              id: id
            },
          })
          .done(function(resultado) {
            const respuesta = JSON.parse(resultado);
            if (respuesta.status == true) {
              Swal.fire('Eliminado', 'La venta se eliminó correctamente.', 'success');
              cargarInfo();
            } else {
              Swal.fire('Error', respuesta.message || 'No se pudo eliminar la venta.', 'error');
            }
          })
          .fail(function() {
            Swal.fire('Error', 'No se pudo contactar con el servidor.', 'error');
          });
      }
    </script>
  </body>

  </html>
<?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}
?>