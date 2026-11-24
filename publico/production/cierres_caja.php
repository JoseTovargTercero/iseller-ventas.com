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

    $usuarios = [];
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
  <title>Cierres de Caja</title>
  <?php require_once('includes/headers.php'); ?>
  <link rel="stylesheet" href="theme.css">
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
    .dash-table .text-end { text-align: right; }

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
      box-shadow: 0 0 0 2px rgba(45,212,160,.12);
    }

    .dash-select {
      background: var(--dash-bg);
      border: 1px solid var(--dash-border);
      color: var(--dash-text);
      border-radius: 8px;
      padding: 7px 14px;
      font-size: 13px;
      transition: border-color .2s ease;
    }
    .dash-select:focus {
      border-color: var(--dash-mint);
      outline: none;
      box-shadow: 0 0 0 2px rgba(45,212,160,.12);
    }

    .filter-bar {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    .filter-bar label {
      font-size: 12px;
      font-weight: 600;
      color: var(--dash-text-muted);
      text-transform: uppercase;
      letter-spacing: .3px;
    }

    .table-info-cell { background: rgba(45,212,160,.04); }
    .table-warning-cell { background: rgba(245,180,91,.08); }
    .text-danger { color: #ef5a6f !important; }
    .text-success { color: #2dd4a0 !important; }

    thead .group-label {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .6px;
      color: var(--dash-text-muted);
      text-align: center;
      border-bottom: 1px solid var(--dash-border);
      padding: 8px 14px;
      background: rgba(255,255,255,.015);
    }

    /* ── Móvil (WebView de la app) ──────────────────────────────────── */
    @media (max-width: 768px) {
      .right_col {
        padding: 14px 12px !important;
      }

      .dash-panel .panel-header {
        padding: 14px 16px 12px;
      }

      /* Fecha y sucursal a lo ancho */
      .filter-bar {
        width: 100%;
      }

      .filter-bar > div {
        flex: 1 1 100%;
      }

      .filter-bar .dash-date-input,
      .filter-bar .dash-select {
        width: 100%;
      }

      .dash-table-wrap {
        padding: 0 10px 12px;
      }

      /* La tabla de 9 columnas no cabe: cada usuario pasa a ser una tarjeta.
         El nombre de la columna viaja en cada celda (data-label). */
      #tabla-cortes thead {
        display: none;
      }

      #tabla-cortes,
      #tabla-cortes tbody {
        display: block;
        width: 100%;
      }

      #tabla-cortes tbody.datos {
        padding: 12px;
        margin-bottom: 10px;
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        background: rgba(255, 255, 255, .02);
      }

      #tabla-cortes tbody:empty,
      #tabla-cortes tfoot:empty {
        display: none;
      }

      #tabla-cortes tr {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 4px 6px;
        padding-bottom: 8px;
        margin-bottom: 8px;
        border-bottom: 1px solid rgba(46, 53, 62, .5);
      }

      #tabla-cortes tr:last-child {
        padding-bottom: 0;
        margin-bottom: 0;
        border-bottom: none;
      }

      #tabla-cortes td {
        display: block;
        border-radius: 8px;
      }

      #tabla-cortes td.usuario,
      #tabla-cortes td.concepto {
        grid-column: 1 / -1;
      }

      #tabla-cortes td.usuario {
        padding: 0 0 6px;
        min-width: 0 !important;
        text-align: left !important;
        background: transparent;
        border-bottom: none !important;
      }

      /* El título de cada bloque (Apertura, Cierre, Sistema...) */
      #tabla-cortes td.concepto {
        padding: 6px 8px;
        font-size: 12.5px;
        text-align: left !important;
        background: rgba(255, 255, 255, .03);
      }

      /* Los números: nombre de la columna encima del valor */
      #tabla-cortes td.valor {
        padding: 5px 6px;
        font-size: 12.5px;
        text-align: left !important;
      }

      #tabla-cortes td.valor::before {
        content: attr(data-label);
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--dash-text-muted);
      }

      /* Las celdas que en escritorio van con "-" no aportan en el teléfono */
      #tabla-cortes td.vacio {
        display: none;
      }

      /* El tinte va en el título del bloque, no en cada número */
      #tabla-cortes td.table-info-cell,
      #tabla-cortes td.table-warning-cell {
        background: transparent;
      }
    }

    /* ── Cierre pendiente / cierre por administrador ─────────────────── */
    .user-sucursal {
      display: block;
      margin-top: 2px;
      color: var(--dash-text-muted);
      font-size: 11px;
    }

    .badge-sin-cierre {
      display: inline-block;
      margin-top: 6px;
      padding: 2px 8px;
      border-radius: 20px;
      background: rgba(245, 180, 91, .12);
      color: #f5b45b;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .3px;
      text-transform: uppercase;
    }

    .btn-cerrar-admin {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      margin-top: 7px;
      padding: 5px 10px;
      border: 1px solid rgba(45, 212, 160, .35);
      border-radius: 7px;
      background: rgba(45, 212, 160, .08);
      color: var(--dash-mint);
      font-size: 11px;
      font-weight: 600;
      line-height: 1.2;
      cursor: pointer;
      transition: background .18s ease, border-color .18s ease;
    }

    .btn-cerrar-admin:hover {
      background: rgba(45, 212, 160, .16);
      border-color: var(--dash-mint);
    }

    /* Formulario del cierre que registra el administrador */
    .swal2-popup.admin-cierre-popup {
      background: #181b22;
      color: #e8edf2;
    }

    .swal2-popup.admin-cierre-popup .swal2-title {
      color: #e8edf2;
    }

    .admin-cierre {
      text-align: left;
      color: #e8edf2;
    }

    .admin-cierre-head {
      display: flex;
      flex-direction: column;
      gap: 2px;
      margin-bottom: 8px;
    }

    .admin-cierre-head strong {
      font-size: 15px;
      color: #e8edf2;
    }

    .admin-cierre-head span {
      font-size: 12px;
      color: #8892a0;
    }

    .admin-cierre-nota {
      margin: 0 0 10px;
      font-size: 12px;
      line-height: 1.45;
      color: #8892a0;
    }

    .admin-cierre-resumen {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 5px;
      margin-bottom: 12px;
      padding: 8px 10px;
      border: 1px solid #2e353e;
      border-radius: 8px;
      background: rgba(255, 255, 255, .025);
      font-size: 11px;
      color: #cbd5e1;
    }

    .admin-cierre-resumen b {
      color: #8892a0;
      font-weight: 600;
    }

    .admin-cierre-section {
      margin: 10px 0 5px;
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      color: #8892a0;
    }

    .admin-cierre-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 8px;
    }

    .admin-cierre-field {
      display: flex;
      flex-direction: column;
      gap: 3px;
      min-width: 0;
    }

    .admin-cierre-field label {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: .03em;
      text-transform: uppercase;
      color: #8892a0;
    }

    .admin-cierre-field input,
    .admin-cierre-field textarea {
      width: 100%;
      padding: 7px 9px;
      border: 1px solid #2e353e;
      border-radius: 7px;
      background: #12151a;
      color: #e8edf2;
      font-size: 12px;
    }

    .admin-cierre-field input:focus,
    .admin-cierre-field textarea:focus {
      outline: none;
      border-color: #2dd4a0;
      box-shadow: 0 0 0 2px rgba(45, 212, 160, .1);
    }

    .admin-cierre-field textarea {
      min-height: 54px;
      resize: vertical;
    }

    .admin-cierre-obs {
      margin-top: 10px;
    }

    @media (max-width: 560px) {
      .admin-cierre-grid,
      .admin-cierre-resumen {
        grid-template-columns: 1fr 1fr;
      }
    }
  </style>
</head>
<body class="nav-md">
<div class="container body">
  <div class="main_container">
    <?php echo $menu ?>
    <?php echo $topnav ?>

    <div class="right_col">
     

      <div class="dash-panel">
        <div class="panel-header">
          <h6><ion-icon name="people-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Totales por Usuario vs Corte de Caja</h6>
          <div class="filter-bar">
            <div>
              <label>Fecha</label>
              <input type="date" class="dash-date-input" id="fechaFiltro" value="<?php echo date('Y-m-d') ?>" max="<?php echo date('Y-m-d') ?>">
            </div>
            <?php if ($_SESSION["nivel"] == 1 && count($sucursales) > 1): ?>
            <div>
              <label>Sucursal</label>
              <select class="dash-select" id="sucursalFiltro">
                <option value="">Todas</option>
                <?php foreach ($sucursales as $row): ?>
                  <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="panel-body p-0">
          <div class="dash-table-wrap">
            <table class="dash-table" id="tabla-cortes">
              <thead>
                <tr>
                  <th rowspan="2">Usuario</th>
                  <th rowspan="2">Concepto</th>
                  <th colspan="3" class="text-center" style="text-align:center;border-bottom:1px solid var(--dash-border);">Efectivo</th>
                  <th colspan="4" class="text-center" style="text-align:center;border-bottom:1px solid var(--dash-border);">Digital</th>
                </tr>
                <tr>
                  <th class="text-end">Bs</th>
                  <th class="text-end">USD</th>
                  <th class="text-end">Pesos</th>
                  <th class="text-end">Punto</th>
                  <th class="text-end">P.Móvil</th>
                  <th class="text-end">Transfer.</th>
                  <th class="text-end">Biopago</th>
                </tr>
              </thead>
              <tbody id="tbody-cortes">
                <tr><td colspan="9" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Cargando...</td></tr>
              </tbody>
              <tfoot id="tfoot-cortes"></tfoot>
            </table>
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
const esAdminCaja = <?= (int) ($_SESSION['nivel'] ?? 0) === 1 ? 'true' : 'false' ?>;
const datosCortes = new Map();

const escCaja = (valor) => String(valor ?? '').replace(/[&<>"']/g, (c) => ({
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#39;'
}[c]));

function totalesCero() {
  return {
    efectivo_bs: 0,
    dolares: 0,
    pesos: 0,
    punto: 0,
    pago_movil: 0,
    transferencia: 0,
    biopago: 0
  };
}

function cargarCortes() {
  const fecha = document.getElementById('fechaFiltro').value;
  const sucursal = document.getElementById('sucursalFiltro')?.value || '';

  document.getElementById('tbody-cortes').innerHTML = '<tr><td colspan="9" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Cargando...</td></tr>';
  document.getElementById('tfoot-cortes').innerHTML = '';

  fetch('../../configurar/listaVentas_back.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'totales_por_usuario', fechaSolic: fecha, sucursal: sucursal })
  })
  .then(r => r.json())
  .then(data => {
    if (data.status !== 'success') {
      document.getElementById('tbody-cortes').innerHTML = '<tr><td colspan="9" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Error al cargar datos.</td></tr>';
      return;
    }

    const tbody = document.getElementById('tbody-cortes');
    const tabla = document.getElementById('tabla-cortes');
    const tfoot = document.getElementById('tfoot-cortes');
    tbody.innerHTML = '';
    tfoot.innerHTML = '';
    datosCortes.clear();
    // Tarjetas de la carga anterior (en móvil cada usuario es una tarjeta)
    tabla.querySelectorAll('tbody.datos').forEach(tb => tb.remove());

    if (!data.cortes || data.cortes.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">No hay cortes de caja registrados para esta fecha.</td></tr>';
      return;
    }

    const fmt = (n, dec = 2) => (Number(n) || 0).toLocaleString('es-VE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    const colorDif = val => val < 0 ? 'text-success' : (val > 0 ? 'text-danger' : '');

    data.cortes.forEach(u => {
      datosCortes.set(String(u.usuario_id), u);

      const sinCierre = !u.cierre;
      const ape = Object.assign({ efectivo_bs: 0, dolares: 0, pesos: 0 }, u.apertura || {});
      const sis = Object.assign(totalesCero(), u.cierre ? (u.cierre.sistema || {}) : (u.sistema || {}));
      const cie = u.cierre || {
        contado: totalesCero(),
        sistema: sis,
        diferencia: totalesCero(),
        fondo_dejado: { efectivo_bs: 0, dolares: 0, pesos: 0 }
      };

      let obs = '';
      if (u.apertura?.observaciones) obs += `<small style="color:var(--dash-text-muted);display:block;"><b>Ape:</b> ${escCaja(u.apertura.observaciones)}</small>`;
      if (u.cierre?.observaciones) obs += `<small style="color:var(--dash-text-muted);display:block;"><b>Cie:</b> ${escCaja(u.cierre.observaciones)}</small>`;

      const inicio = (u.apertura && u.apertura.hora_apertura) ?
        `<small style="display:block;color:var(--dash-text-muted);font-size:11px;margin-top:2px;">Inicio ${escCaja(u.apertura.hora_apertura)}</small>` : '';
      const sucursalLinea = (!sucursal && u.sucursal_nombre)
        ? `<small class="user-sucursal">${escCaja(u.sucursal_nombre)}</small>` : '';
      const badgeSinCierre = sinCierre ? '<span class="badge-sin-cierre">Sin cierre</span>' : '';
      const botonCierre = (esAdminCaja && sinCierre && Number(u.usuario_id) > 0)
        ? `<button type="button" class="btn-cerrar-admin" data-usuario="${u.usuario_id}">Cerrar caja</button>` : '';

      const vacio = (label) => `<td class="valor text-end vacio" data-label="${label}" style="color:var(--dash-text-muted);">-</td>`;

      const unidad = {
        efectivo_bs: 'Bs',
        dolares: '$',
        pesos: 'COP',
        punto: 'Bs',
        pago_movil: 'Bs',
        transferencia: 'Bs',
        biopago: 'Bs'
      };
      const decimales = (clave) => clave === 'pesos' ? 0 : 2;
      const celdaContado = (clave, label) => {
        const valor = sinCierre ? '—' : `${fmt(cie.contado[clave], decimales(clave))} ${unidad[clave]}`;
        return `<td class="valor text-end table-info-cell" data-label="${label}">${valor}</td>`;
      };
      const celdaSistema = (clave, label) =>
        `<td class="valor text-end table-info-cell" data-label="${label}">${fmt(sis[clave], decimales(clave))} ${unidad[clave]}</td>`;
      const celdaDiferencia = (clave, label) => {
        if (sinCierre) {
          return `<td class="valor text-end table-info-cell" data-label="${label}">—</td>`;
        }
        const valor = cie.diferencia[clave];
        return `<td class="valor text-end table-info-cell ${colorDif(valor)}" data-label="${label}"><strong>${fmt(valor, decimales(clave))} ${unidad[clave]}</strong></td>`;
      };
      const celdaFondo = (clave, label) => {
        const valor = sinCierre ? '—' : `${fmt(cie.fondo_dejado[clave], decimales(clave))} ${unidad[clave]}`;
        return `<td class="valor text-end table-warning-cell" data-label="${label}">${valor}</td>`;
      };

      const tb = document.createElement('tbody');
      tb.className = 'datos';
      tb.innerHTML = `
      <tr>
        <td rowspan="5" class="usuario align-middle text-center" style="border-bottom:2px solid var(--dash-border);vertical-align:middle;min-width:140px;">
          <strong>${escCaja(u.nombre)}</strong>
          ${sucursalLinea}
          ${inicio}
          ${obs}
          ${badgeSinCierre}
          ${botonCierre}
        </td>
        <td class="concepto"><strong>Apertura (Fondo Recibido)</strong></td>
        <td class="valor text-end" data-label="Bs">${fmt(ape.efectivo_bs)} Bs</td>
        <td class="valor text-end" data-label="USD">${fmt(ape.dolares)} $</td>
        <td class="valor text-end" data-label="Pesos">${fmt(ape.pesos, 0)} COP</td>
        ${vacio('Punto')}
        ${vacio('P.Móvil')}
        ${vacio('Transfer.')}
        ${vacio('Biopago')}
      </tr>
      <tr>
        <td class="concepto table-info-cell"><strong>Cierre (Contado)</strong></td>
        ${celdaContado('efectivo_bs', 'Bs')}
        ${celdaContado('dolares', 'USD')}
        ${celdaContado('pesos', 'Pesos')}
        ${celdaContado('punto', 'Punto')}
        ${celdaContado('pago_movil', 'P.Móvil')}
        ${celdaContado('transferencia', 'Transfer.')}
        ${celdaContado('biopago', 'Biopago')}
      </tr>
      <tr>
        <td class="concepto table-info-cell">Sistema</td>
        ${celdaSistema('efectivo_bs', 'Bs')}
        ${celdaSistema('dolares', 'USD')}
        ${celdaSistema('pesos', 'Pesos')}
        ${celdaSistema('punto', 'Punto')}
        ${celdaSistema('pago_movil', 'P.Móvil')}
        ${celdaSistema('transferencia', 'Transfer.')}
        ${celdaSistema('biopago', 'Biopago')}
      </tr>
      <tr>
        <td class="concepto table-info-cell"><strong>Diferencia</strong></td>
        ${celdaDiferencia('efectivo_bs', 'Bs')}
        ${celdaDiferencia('dolares', 'USD')}
        ${celdaDiferencia('pesos', 'Pesos')}
        ${celdaDiferencia('punto', 'Punto')}
        ${celdaDiferencia('pago_movil', 'P.Móvil')}
        ${celdaDiferencia('transferencia', 'Transfer.')}
        ${celdaDiferencia('biopago', 'Biopago')}
      </tr>
      <tr>
        <td class="concepto table-warning-cell"><strong>Fondo Dejado (Cierre)</strong></td>
        ${celdaFondo('efectivo_bs', 'Bs')}
        ${celdaFondo('dolares', 'USD')}
        ${celdaFondo('pesos', 'Pesos')}
        ${vacio('Punto')}
        ${vacio('P.Móvil')}
        ${vacio('Transfer.')}
        ${vacio('Biopago')}
      </tr>`;
      tabla.appendChild(tb);
    });
  })
  .catch(err => {
    console.error(err);
    document.getElementById('tbody-cortes').innerHTML = '<tr><td colspan="9" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Error de conexión.</td></tr>';
  });
}

async function abrirCierreAdmin(usuarioId) {
  const u = datosCortes.get(String(usuarioId));
  if (!u || !esAdminCaja) return;

  const fmt = (n, dec = 2) => (Number(n) || 0).toLocaleString('es-VE', {
    minimumFractionDigits: dec,
    maximumFractionDigits: dec
  });
  const sis = Object.assign(totalesCero(), u.cierre ? (u.cierre.sistema || {}) : (u.sistema || {}));
  const ape = Object.assign({ efectivo_bs: 0, dolares: 0, pesos: 0 }, u.apertura || {});
  const fecha = document.getElementById('fechaFiltro').value;
  const fmtInput = (valor) => (Number(valor) || 0).toFixed(2);

  const campo = (id, label, valor) => `
    <div class="admin-cierre-field">
      <label for="${id}">${label}</label>
      <input type="number" min="0" step="0.01" id="${id}" value="${fmtInput(valor)}">
    </div>`;

  const html = `
    <div class="admin-cierre">
      <div class="admin-cierre-head">
        <strong>${escCaja(u.nombre)}</strong>
        <span>${escCaja(u.sucursal_nombre || 'Sucursal')} · ${escCaja(fecha)}</span>
      </div>
      <p class="admin-cierre-nota">
        El contado viene precargado con los totales del sistema. Ajústalo si el conteo real es distinto.
      </p>

      <div class="admin-cierre-resumen">
        <span><b>Bs</b> ${fmt(sis.efectivo_bs)}</span>
        <span><b>USD</b> ${fmt(sis.dolares)}</span>
        <span><b>COP</b> ${fmt(sis.pesos, 0)}</span>
        <span><b>Punto</b> ${fmt(sis.punto)}</span>
        <span><b>P.Móvil</b> ${fmt(sis.pago_movil)}</span>
        <span><b>Transf.</b> ${fmt(sis.transferencia)}</span>
        <span><b>Biopago</b> ${fmt(sis.biopago)}</span>
      </div>

      <div class="admin-cierre-section">Cierre contado</div>
      <div class="admin-cierre-grid">
        ${campo('ac-efectivo_bs_contado', 'Efectivo Bs', sis.efectivo_bs)}
        ${campo('ac-efectivo_usd_contado', 'Efectivo USD', sis.dolares)}
        ${campo('ac-pesos_contado', 'Efectivo COP', sis.pesos)}
        ${campo('ac-punto_contado', 'Punto', sis.punto)}
        ${campo('ac-pago_movil_contado', 'Pago Móvil', sis.pago_movil)}
        ${campo('ac-transferencia_contado', 'Transferencia', sis.transferencia)}
        ${campo('ac-biopago_contado', 'Biopago', sis.biopago)}
      </div>

      <div class="admin-cierre-section">Fondo dejado</div>
      <div class="admin-cierre-grid">
        ${campo('ac-efectivo_bs_fondo', 'Fondo Bs', ape.efectivo_bs)}
        ${campo('ac-efectivo_usd_fondo', 'Fondo USD', ape.dolares)}
        ${campo('ac-pesos_fondo', 'Fondo COP', ape.pesos)}
      </div>

      <div class="admin-cierre-field admin-cierre-obs">
        <label for="ac-observaciones">Observaciones</label>
        <textarea id="ac-observaciones" placeholder="Opcional"></textarea>
      </div>
    </div>`;

  const resultado = await Swal.fire({
    title: 'Registrar cierre de caja',
    html,
    width: 680,
    showCancelButton: true,
    confirmButtonText: 'Registrar cierre',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#2dd4a0',
    focusConfirm: false,
    customClass: { popup: 'admin-cierre-popup' },
    preConfirm: () => {
      const mapa = {
        efectivo_bs_contado: 'ac-efectivo_bs_contado',
        efectivo_usd_contado: 'ac-efectivo_usd_contado',
        pesos_contado: 'ac-pesos_contado',
        punto_contado: 'ac-punto_contado',
        pago_movil_contado: 'ac-pago_movil_contado',
        transferencia_contado: 'ac-transferencia_contado',
        biopago_contado: 'ac-biopago_contado',
        efectivo_bs_fondo: 'ac-efectivo_bs_fondo',
        efectivo_usd_fondo: 'ac-efectivo_usd_fondo',
        pesos_fondo: 'ac-pesos_fondo'
      };
      const salida = {};

      for (const campoNombre in mapa) {
        const input = document.getElementById(mapa[campoNombre]);
        const valor = input ? parseFloat(String(input.value).replace(',', '.')) : NaN;
        if (!Number.isFinite(valor) || valor < 0) {
          Swal.showValidationMessage('Revisa los montos: deben ser números mayores o iguales a cero.');
          return false;
        }
        salida[campoNombre] = valor;
      }

      const obs = document.getElementById('ac-observaciones');
      salida.observaciones = obs ? obs.value.trim() : '';
      return salida;
    }
  });

  if (!resultado.isConfirmed || !resultado.value) return;

  const body = new URLSearchParams();
  body.set('usuario_id', u.usuario_id);
  body.set('sucursal_id', u.sucursal_id || '');
  body.set('fecha', fecha);
  Object.entries(resultado.value).forEach(([clave, valor]) => {
    body.set(clave, valor);
  });

  Swal.fire({
    title: 'Registrando cierre...',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading()
  });

  try {
    const respuesta = await fetch('../../configurar/cerrar_caja_admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });
    const data = await respuesta.json();

    if (data.status !== 'success') {
      throw new Error(data.message || 'No se pudo registrar el cierre.');
    }

    Swal.close();
    Alerta.toast('success', data.message || 'Cierre registrado correctamente.');
    cargarCortes();
  } catch (error) {
    Swal.close();
    Swal.fire('Error', error.message || 'No se pudo registrar el cierre.', 'error');
  }
}

document.addEventListener('click', (event) => {
  const boton = event.target.closest('.btn-cerrar-admin');
  if (!boton) return;
  abrirCierreAdmin(boton.dataset.usuario);
});

document.getElementById('fechaFiltro').addEventListener('change', cargarCortes);
const sucFiltro = document.getElementById('sucursalFiltro');
if (sucFiltro) sucFiltro.addEventListener('change', cargarCortes);

cargarCortes();
</script>
</body>
</html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>
