<?php
ob_start();
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();
  $cliente = $_GET['cliente'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <title>Control de Créditos — <?= htmlspecialchars($cliente) ?></title>
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
    .dash-header { margin-bottom: 28px; }
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
      background: rgba(45,212,160,0.04);
    }
    .nav-tabs.dash-tabs .nav-link.active {
      color: var(--dash-mint);
      background: transparent;
      box-shadow: inset 0 -2px 0 var(--dash-mint);
    }

    .dash-table-wrap { overflow-x: auto; padding: 0 16px 16px; }
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
      border-bottom: 1px solid rgba(46,53,62,.4);
    }
    .dash-table tbody tr:last-child { border-bottom: none; }
    .dash-table tbody tr:hover { background: rgba(45,212,160,.03); }
    .dash-table tbody td {
      padding: 12px 14px;
      color: var(--dash-text);
      vertical-align: middle;
    }
    .dash-table .text-center { text-align: center; }
    .dash-table .text-end { text-align: right; }

    .dash-toggle {
      position: relative;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      font-size: 13px;
      color: var(--dash-text-muted);
      user-select: none;
    }
    .dash-toggle input {
      position: absolute;
      opacity: 0;
      width: 0;
      height: 0;
    }
    .dash-toggle .slider {
      width: 36px;
      height: 20px;
      background: rgba(255,255,255,.1);
      border-radius: 10px;
      position: relative;
      transition: background .2s ease;
      flex-shrink: 0;
    }
    .dash-toggle .slider::after {
      content: '';
      position: absolute;
      width: 16px; height: 16px;
      border-radius: 50%;
      background: var(--dash-text-muted);
      top: 2px; left: 2px;
      transition: transform .2s ease, background .2s ease;
    }
    .dash-toggle input:checked + .slider {
      background: var(--dash-mint);
    }
    .dash-toggle input:checked + .slider::after {
      transform: translateX(16px);
      background: #fff;
    }

    .dash-btn-pagar {
      background: linear-gradient(135deg, #2dd4a0, #25b88a);
      border: none;
      color: #fff;
      font-size: 12px;
      font-weight: 600;
      padding: 6px 16px;
      border-radius: 8px;
      transition: all .2s ease;
      box-shadow: 0 3px 12px rgba(45,212,160,0.25);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .dash-btn-pagar:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(45,212,160,0.35);
    }
    .dash-btn-pagar-sm {
      font-size: 11px;
      padding: 4px 12px;
    }

    .dash-input {
      background: var(--dash-bg);
      border: 1px solid var(--dash-border);
      color: var(--dash-text);
      border-radius: 8px;
      padding: 9px 14px;
      font-size: 13px;
      width: 100%;
      transition: border-color .2s ease;
    }
    .dash-input:focus {
      border-color: var(--dash-mint);
      outline: none;
      box-shadow: 0 0 0 2px rgba(45,212,160,.12);
    }
    .dash-select-xs {
      background: var(--dash-bg);
      border: 1px solid var(--dash-border);
      color: var(--dash-text);
      border-radius: 8px;
      padding: 9px 14px;
      font-size: 13px;
      transition: border-color .2s ease;
      width: fit-content;
    }
    .dash-select-xs:focus {
      border-color: var(--dash-mint);
      outline: none;
      box-shadow: 0 0 0 2px rgba(45,212,160,.12);
    }

    /* Abonos list */
    .abono-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      border-bottom: 1px solid rgba(46,53,62,.5);
      transition: background .15s ease;
    }
    .abono-item:hover { background: rgba(45,212,160,.03); }
    .abono-item:last-child { border-bottom: none; }
    .abono-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .abono-badge {
      width: 36px; height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 14px;
      flex-shrink: 0;
    }
    .abono-badge.usd { background: rgba(45,212,160,.15); color: #2dd4a0; }
    .abono-badge.cop { background: rgba(91,156,245,.15); color: #5b9cf5; }
    .abono-badge.bs  { background: rgba(239,90,111,.15); color: #ef5a6f; }
    .abono-info h6 {
      font-size: 13px;
      font-weight: 600;
      color: var(--dash-text);
      margin: 0;
    }
    .abono-info small {
      font-size: 11px;
      color: var(--dash-text-muted);
    }
    .abono-delete {
      background: none;
      border: none;
      color: var(--dash-text-muted);
      cursor: pointer;
      padding: 4px;
      border-radius: 6px;
      transition: all .2s ease;
      display: inline-flex;
    }
    .abono-delete:hover { color: #ef5a6f; background: rgba(239,90,111,.1); }
    .abono-delete ion-icon { font-size: 16px; }

    .tab-pane.fade {
      background: transparent;
    }
    .tab-pane.fade:not(.show) {
      display: none;
    }

    .loader-overlay {
      position: fixed;
      inset: 0;
      z-index: 99999;
      background: rgba(0,0,0,.4);
      display: grid;
      place-items: center;
    }
    .loader {
      width: 48px;
      height: 6px;
      display: block;
      position: relative;
      border-radius: 4px;
      color: var(--dash-mint);
      box-sizing: border-box;
      animation: animloader 0.6s linear infinite;
    }
    @keyframes animloader {
      0%   { box-shadow: -10px 20px, 10px 35px, 0px 50px }
      25%  { box-shadow: 0px 20px, 0px 35px, 10px 50px }
      50%  { box-shadow: 10px 20px, -10px 35px, 0px 50px }
      75%  { box-shadow: 0px 20px, 0px 35px, -10px 50px }
      100% { box-shadow: -10px 20px, 10px 35px, 0px 50px }
    }

    .swal2-popup {
      background: var(--dash-card) !important;
      border: 1px solid var(--dash-border) !important;
      border-radius: 14px !important;
      padding: 0 !important;
    }
    .swal2-modal { font-family: inherit; }
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
    }
    .swal2-close {
      color: var(--dash-text-muted) !important;
      font-size: 28px !important;
    }
    .swal2-confirm {
      background: linear-gradient(135deg, #2dd4a0, #25b88a) !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 9px 24px !important;
      font-size: 13px !important;
      font-weight: 600 !important;
      box-shadow: 0 3px 12px rgba(45,212,160,.25) !important;
    }
    .swal2-cancel {
      background: rgba(255,255,255,.06) !important;
      border: 1px solid var(--dash-border) !important;
      color: var(--dash-text-muted) !important;
      border-radius: 8px !important;
      padding: 9px 24px !important;
      font-size: 13px !important;
    }
  </style>
</head>
<body class="nav-md">
<div class="loader-overlay" id="cargando" style="display:none;">
  <span class="loader"></span>
</div>

<div class="container body">
  <div class="main_container">
    <?php echo $menu ?>
    <?php echo $topnav ?>

    <div class="right_col">
      <div class="d-flex justify-content-between dash-header">
        <div>
          <h3>Créditos</h3>
          <p>Cliente: <?= htmlspecialchars($cliente) ?></p>
        </div>
      </div>

      <!-- NAV TABS -->
      <ul class="nav nav-tabs dash-tabs mb-3" id="creditosTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <a class="nav-link active" id="tab-otorgados" data-toggle="tab" href="#pane-otorgados" role="tab">Créditos otorgados</a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link" id="tab-abonos" data-toggle="tab" href="#pane-abonos" role="tab">Deuda total y abonos</a>
        </li>
      </ul>

      <div class="tab-content">
        <!-- TAB 1: CRÉDITOS OTORGADOS -->
        <div class="tab-pane fade show active" id="pane-otorgados" role="tabpanel">
          <div class="dash-panel">
            <div class="panel-header">
              <h6><ion-icon name="receipt-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Créditos otorgados</h6>
              <label class="dash-toggle">
                <input type="checkbox" id="mostrarValorInicial">
                <span class="slider"></span>
                Mostrar valor inicial
              </label>
            </div>
            <div class="panel-body p-0">
              <div class="dash-table-wrap">
                <table id="tabla-creditos" class="dash-table" style="width:100%">
                  <thead>
                    <tr>
                      <th>Cant</th>
                      <th>Producto</th>
                      <th class="text-center">Valor ($)</th>
                      <th class="text-center">Valor (COP)</th>
                      <th class="text-center">Valor (BS)</th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: DEUDA TOTAL Y ABONOS -->
        <div class="tab-pane fade" id="pane-abonos" role="tabpanel">
          <div class="row">
            <div class="col-lg-8 mb-4">
              <div class="dash-panel">
                <div class="panel-header">
                  <h6><ion-icon name="receipt-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Deuda total y abonos</h6>
                  <button class="dash-btn-pagar dash-btn-pagar-sm" onclick="procesarPagosSecuencial()">Pagar Todo</button>
                </div>
                <div class="panel-body p-0">
                  <div class="dash-table-wrap">
                    <table id="tabla-abonos" class="dash-table" style="width:100%">
                      <thead>
                        <tr>
                          <th>Cant</th>
                          <th>Producto</th>
                          <th class="text-center">Valor ($)</th>
                          <th class="text-center">Valor (COP)</th>
                          <th class="text-center">Valor (BS)</th>
                        </tr>
                      </thead>
                      <tbody></tbody>
                    </table>
                    <div id="alert-pagado" style="padding:0 16px 16px;"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4 mb-4">
              <div class="dash-panel">
                <div class="panel-header">
                  <h6><ion-icon name="wallet-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Abonos</h6>
                </div>
                <div class="panel-body p-0">
                  <div id="abonos-list"></div>
                  <form id="data-form" class="d-flex align-items-center" style="padding:14px 18px;border-top:1px solid var(--dash-border);gap:6px;">
                    <input type="text" id="monto" class="dash-input" placeholder="Monto" style="width:auto;flex:1;">
                    <select id="moneda" class="dash-select-xs">
                      <option value="USD">USD</option>
                      <option value="Bs">Bs</option>
                      <option value="COP">COP</option>
                    </select>
                    <button type="submit" class="dash-btn-pagar dash-btn-pagar-sm" style="padding:9px 14px;">
                      <ion-icon name="save-outline"></ion-icon>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .hide { display: none !important; }
</style>

<script src="../vendors/jquery/dist/jquery.min.js"></script>
<script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../build/js/custom.js"></script>
<script src="../build/js/global-loader.js"></script>

<script>
const cliente = <?php echo json_encode($cliente); ?>;

async function cargarCreditos() {
  const tbody = document.querySelector('#tabla-creditos tbody');
  if (!tbody) return;
  try {
    const res = await fetch('../../configurar/creditos_por_cliente.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `cliente=${encodeURIComponent(cliente)}`
    });
    if (!res.ok) throw new Error('Error al obtener datos');
    const data = await res.json();
    tbody.innerHTML = '';
    if (data.ordenes.length === 0) { window.location.href = 'creditos.php'; }

    data.ordenes.forEach(ord => {
      ord.productos.forEach(sub => {
        const { nombre, precio_dolar_visible, precio_peso_visible, precio_bs_visible } = sub.datos;
        const cantidad = sub.cantidad;
        const usd = (precio_dolar_visible * cantidad).toFixed(2);
        const cop = (precio_peso_visible * cantidad).toLocaleString('es-CO');
        const bs = (precio_bs_visible * cantidad).toLocaleString('es-VE', { minimumFractionDigits: 2 });
        tbody.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${sub.cantidad}</td>
            <td>${nombre}</td>
            <td class="text-center">${usd} <small>$</small></td>
            <td class="text-center">${cop} <small>Cop</small></td>
            <td class="text-center">${bs} <small>Bs</small></td>
          </tr>`);
      });
      const ts = ord.totales;
      const ts_inicial = ord.totales_iniciales;
      const ts_inicial_u = ts_inicial.usd.toFixed(2);
      const ts_inicial_c = ts_inicial.cop > 0 ? ts_inicial.cop.toLocaleString('es-CO') : '0,00';
      const ts_inicial_b = ts_inicial.bs > 0 ? ts_inicial.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 }) : '0,00';
      tbody.insertAdjacentHTML('beforeend', `
        <tr style="background:rgba(255,255,255,.03);">
          <td>COMPRA: <b>${ord.id}</b></td>
          <td>Fecha: ${ord.fecha}</td>
          <td class="text-center"><b>TOTAL:
            <span class="total_final">${ts.usd.toFixed(2)}</span>
            <span class="total_inicial hide">${ts_inicial_u}</span>
            <small>$</small></b>
          </td>
          <td class="text-center"><b>TOTAL:
            <span class="total_final">${ts.cop.toLocaleString('es-CO')}</span>
            <span class="total_inicial hide">${ts_inicial_c}</span>
            <small>Cop</small></b>
          </td>
          <td class="text-center"><b>TOTAL:
            <span class="total_final">${ts.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</span>
            <span class="total_inicial hide">${ts_inicial_b}</span>
            <small>Bs</small></b>
            <button data-tipoCompra="${ord.tipoCompra}" data-precioPesoVenta="${ts.cop}" data-precioBsVenta="${ts.bs}" data-id_credito="${ord.id_credito}" data-id="${ord.id}" class="dash-btn-pagar dash-btn-pagar-sm btn-pagar hide" style="margin-left:8px;">Pagar</button>
          </td>
        </tr>`);
    });

    const tg = data.totales_global;
    const tg_inicial_u = tg.total_inicial_dolares.toFixed(2);
    const tg_inicial_c = tg.total_inicial_cop > 0 ? tg.total_inicial_cop.toLocaleString('es-CO') : '0,00';
    const tg_inicial_b = tg.total_inicial_bs > 0 ? tg.total_inicial_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 }) : '0,00';
    tbody.insertAdjacentHTML('beforeend', `
      <tr style="background:rgba(255,255,255,.06);">
        <td colspan="2">DEUDA TOTAL:</td>
        <td class="text-center"><b>
          <span class="total_final">${tg.usd.toFixed(2)}</span>
          <span class="total_inicial hide">${tg_inicial_u}</span>
          <small>$</small></b>
        </td>
        <td class="text-center"><b>
          <span class="total_final">${tg.cop.toLocaleString('es-CO')}</span>
          <span class="total_inicial hide">${tg_inicial_c}</span>
          <small>Cop</small></b>
        </td>
        <td class="text-center"><b>
          <span class="total_final">${tg.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</span>
          <span class="total_inicial hide">${tg_inicial_b}</span>
          <small>Bs</small></b>
        </td>
      </tr>`);
  } catch (error) {
    console.error(error);
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">No fue posible cargar los créditos.</td></tr>';
  }
}

document.getElementById('mostrarValorInicial').addEventListener('change', function() {
  const checked = this.checked;
  document.querySelectorAll('.total_final').forEach(s => s.classList.toggle('hide', checked));
  document.querySelectorAll('.total_inicial').forEach(s => s.classList.toggle('hide', !checked));
});

document.getElementById('data-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const monto = document.getElementById('monto').value.trim();
  const moneda = document.getElementById('moneda').value.trim();
  if (!monto || !moneda) { Alerta.toast('error', 'Completa todos los campos'); return; }
  try {
    const res = await fetch('../../configurar/registrar_abono.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ cliente, monto, moneda })
    });
    const result = await res.json();
    if (result.success) {
      cargarDeuda();
      Alerta.toast('success', 'Abono registrado correctamente');
      document.getElementById('monto').value = '';
    } else {
      Alerta.toast('error', 'Error: ' + result.message);
    }
  } catch (error) {
    console.error(error);
    Alerta.toast('error', 'Error al registrar el abono');
  }
});

const colores_monedas = { usd: 'usd', cop: 'cop', bs: 'bs' };

async function cargarDeuda() {
  const tbody = document.querySelector('#tabla-abonos tbody');
  if (!tbody) return;
  try {
    const res = await fetch('../../configurar/creditos_por_cliente_total_abonado.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `cliente=${encodeURIComponent(cliente)}`
    });
    if (!res.ok) throw new Error('Error al obtener datos');
    const data = await res.json();
    tbody.innerHTML = '';

    const abonos_div = document.querySelector('#abonos-list');
    const abonos = data.abonos || [];
    abonos_div.innerHTML = '';
    abonos.forEach(abns => {
      abonos_div.insertAdjacentHTML('beforeend', `
        <div class="abono-item">
          <div class="abono-left">
            <div class="abono-badge ${colores_monedas[abns.moneda]}">${abns.moneda.toUpperCase().substring(0,1)}</div>
            <div class="abono-info">
              <h6>${formatearMiles(abns.monto)} <small>${abns.moneda.toUpperCase()}</small></h6>
              <small>${abns.fecha}</small>
            </div>
          </div>
          <button class="abono-delete" data-id="${abns.id}"><ion-icon name="close-outline"></ion-icon></button>
        </div>`);
    });

    document.querySelectorAll('.abono-delete').forEach(btn => {
      btn.addEventListener('click', function() {
        Swal.fire({
          title: '¿Estás seguro?',
          text: 'Esta acción eliminará el abono.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar'
        }).then(result => {
          if (result.isConfirmed) eliminarAbono(this.getAttribute('data-id'));
        });
      });
    });

    function eliminarAbono(id) {
      fetch('../../configurar/eliminar_abono.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(id)}`
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) { Alerta.toast('success', 'Abono eliminado correctamente'); cargarDeuda(); }
        else { Alerta.toast('error', 'Error: ' + data.message); }
      })
      .catch(err => { console.error(err); Alerta.toast('error', 'Error al eliminar el abono'); });
    }

    const productos = data.productos || [];
    if (productos.length === 0) { window.location.href = 'creditos.php'; return; }

    productos.forEach(prod => {
      const clase = prod.total_usd.toFixed(2) === '0.00' ? 'text-muted' : '';
      tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${prod.cantidad}</td>
          <td>${prod.nombre}</td>
          <td class="text-center ${clase}">${prod.total_usd.toFixed(2)} <small>$</small></td>
          <td class="text-center ${clase}">${prod.total_cop.toLocaleString('es-CO')} <small>Cop</small></td>
          <td class="text-center ${clase}">${prod.total_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })} <small>Bs</small></td>
        </tr>`);
    });

    const tg2 = data.deuda_restante;
    tbody.insertAdjacentHTML('beforeend', `
      <tr style="background:rgba(255,255,255,.06);">
        <td colspan="2"><b>DEUDA TOTAL:</b></td>
        <td class="text-center"><b>${tg2.usd.toFixed(2)} <small>$</small></b></td>
        <td class="text-center"><b>${tg2.cop.toLocaleString('es-CO')} <small>Cop</small></b></td>
        <td class="text-center"><b>${tg2.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })} <small>Bs</small></b></td>
      </tr>`);

    document.querySelector('#alert-pagado').innerHTML = tg2.usd.toFixed(2) === '0.00'
      ? `<div style="padding:16px;text-align:center;color:var(--dash-mint);font-size:14px;font-weight:600;"><ion-icon name="checkmark-circle"></ion-icon> ¡Todo pagado! No hay deuda pendiente. <button class="dash-btn-pagar dash-btn-pagar-sm" style="margin-left:12px;" onclick="procesarPagosSecuencial()">Pagar Todo</button></div>`
      : '';

  } catch (error) {
    console.error(error);
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">No fue posible cargar los créditos.</td></tr>';
  }
}

function formatearMiles(n) {
  const num = parseFloat(n);
  if (isNaN(num)) return n;
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener('DOMContentLoaded', cargarCreditos);
document.addEventListener('DOMContentLoaded', cargarDeuda);

function procesarPagosSecuencial() {
  const botones = Array.from(document.querySelectorAll('.btn-pagar'));
  if (botones.length === 0) { Alerta.toast('info', 'No hay pagos pendientes'); return; }
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'Esta acción cambiará el estatus de todos los créditos a pagado.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, procesar',
    cancelButtonText: 'Cancelar'
  }).then(result => { if (result.isConfirmed) procesarPagos(); });
}

async function procesarPagos() {
  const botones = Array.from(document.querySelectorAll('.btn-pagar'));
  if (botones.length === 0) { Alerta.toast('info', 'No hay pagos pendientes'); return; }
  document.getElementById('cargando').style.display = 'grid';
  for (let boton of botones) {
    try {
      await pagar(
        boton.getAttribute('data-id_credito'),
        boton.getAttribute('data-id'),
        boton.getAttribute('data-precioPesoVenta'),
        boton.getAttribute('data-precioBsVenta'),
        boton.getAttribute('data-tipoCompra')
      );
    } catch (error) {
      console.error('Error procesando el pago:', error);
      Alerta.toast('error', 'Error en un pago, se detiene el proceso.');
      break;
    }
  }
  document.getElementById('cargando').style.display = 'none';
  window.location.href = 'creditos.php';
}

async function pagar(credito, compra, precioPesoVenta, precioBsVenta, tipoCompra) {
  const res = await fetch('../../configurar/pagar.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      pagoTipo: 1,
      order_id: compra,
      precioPesoVenta: precioPesoVenta,
      precioBsVenta: precioBsVenta,
    })
  });
  const data = await res.json();
  if (data.success) return data;
  throw new Error(data.message || 'Error en el backend');
}

$(document).ready(function() {
  document.getElementById('cargando').style.display = 'none';
});
</script>
</body>
</html>
<?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}
ob_end_flush();
?>