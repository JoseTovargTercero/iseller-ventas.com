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
              <input type="date" class="dash-date-input" id="fechaFiltro" value="<?php echo date('Y-m-d') ?>">
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
    // Tarjetas de la carga anterior (en móvil cada usuario es una tarjeta)
    tabla.querySelectorAll('tbody.datos').forEach(tb => tb.remove());

    if (!data.cortes || data.cortes.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">No hay cortes de caja registrados para esta fecha.</td></tr>';
      return;
    }

    const fmt = (n, dec = 2) => Number(n).toLocaleString('es-VE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    const colorDif = val => val < 0 ? 'text-success' : (val > 0 ? 'text-danger' : '');

    data.cortes.forEach(u => {
      let obs = '';
      if (u.apertura?.observaciones) obs += `<small style="color:var(--dash-text-muted);display:block;"><b>Ape:</b> ${u.apertura.observaciones}</small>`;
      if (u.cierre?.observaciones) obs += `<small style="color:var(--dash-text-muted);display:block;"><b>Cie:</b> ${u.cierre.observaciones}</small>`;

      const ape = u.apertura || { efectivo_bs: 0, dolares: 0, pesos: 0 };
      const cie = u.cierre || {
        contado: { efectivo_bs: 0, dolares: 0, pesos: 0, punto: 0, pago_movil: 0, transferencia: 0, biopago: 0 },
        sistema: { efectivo_bs: 0, dolares: 0, pesos: 0, punto: 0, pago_movil: 0, transferencia: 0, biopago: 0 },
        diferencia: { efectivo_bs: 0, dolares: 0, pesos: 0, punto: 0, pago_movil: 0, transferencia: 0, biopago: 0 },
        fondo_dejado: { efectivo_bs: 0, dolares: 0, pesos: 0 }
      };

      const inicio = (u.apertura && u.apertura.hora_apertura) ?
        `<small style="display:block;color:var(--dash-text-muted);font-size:11px;margin-top:2px;">Inicio ${u.apertura.hora_apertura}</small>` : '';
      const vacio = (label) => `<td class="valor text-end vacio" data-label="${label}" style="color:var(--dash-text-muted);">-</td>`;

      const tb = document.createElement('tbody');
      tb.className = 'datos';
      tb.innerHTML = `
      <tr>
        <td rowspan="5" class="usuario align-middle text-center" style="border-bottom:2px solid var(--dash-border);vertical-align:middle;min-width:140px;">
          <strong>${u.nombre}</strong>
          ${inicio}
          ${obs}
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
        <td class="valor text-end table-info-cell" data-label="Bs">${fmt(cie.contado.efectivo_bs)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="USD">${fmt(cie.contado.dolares)} $</td>
        <td class="valor text-end table-info-cell" data-label="Pesos">${fmt(cie.contado.pesos, 0)} COP</td>
        <td class="valor text-end table-info-cell" data-label="Punto">${fmt(cie.contado.punto)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="P.Móvil">${fmt(cie.contado.pago_movil)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="Transfer.">${fmt(cie.contado.transferencia)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="Biopago">${fmt(cie.contado.biopago)} Bs</td>
      </tr>
      <tr>
        <td class="concepto table-info-cell">Sistema</td>
        <td class="valor text-end table-info-cell" data-label="Bs">${fmt(cie.sistema.efectivo_bs)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="USD">${fmt(cie.sistema.dolares)} $</td>
        <td class="valor text-end table-info-cell" data-label="Pesos">${fmt(cie.sistema.pesos, 0)} COP</td>
        <td class="valor text-end table-info-cell" data-label="Punto">${fmt(cie.sistema.punto)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="P.Móvil">${fmt(cie.sistema.pago_movil)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="Transfer.">${fmt(cie.sistema.transferencia)} Bs</td>
        <td class="valor text-end table-info-cell" data-label="Biopago">${fmt(cie.sistema.biopago)} Bs</td>
      </tr>
      <tr>
        <td class="concepto table-info-cell"><strong>Diferencia</strong></td>
        <td class="valor text-end table-info-cell ${colorDif(cie.diferencia.efectivo_bs)}" data-label="Bs"><strong>${fmt(cie.diferencia.efectivo_bs)} Bs</strong></td>
        <td class="valor text-end table-info-cell ${colorDif(cie.diferencia.dolares)}" data-label="USD"><strong>${fmt(cie.diferencia.dolares)} $</strong></td>
        <td class="valor text-end table-info-cell ${colorDif(cie.diferencia.pesos)}" data-label="Pesos"><strong>${fmt(cie.diferencia.pesos, 0)} COP</strong></td>
        <td class="valor text-end table-info-cell ${colorDif(cie.diferencia.punto)}" data-label="Punto"><strong>${fmt(cie.diferencia.punto)} Bs</strong></td>
        <td class="valor text-end table-info-cell ${colorDif(cie.diferencia.pago_movil)}" data-label="P.Móvil"><strong>${fmt(cie.diferencia.pago_movil)} Bs</strong></td>
        <td class="valor text-end table-info-cell ${colorDif(cie.diferencia.transferencia)}" data-label="Transfer."><strong>${fmt(cie.diferencia.transferencia)} Bs</strong></td>
        <td class="valor text-end table-info-cell ${colorDif(cie.diferencia.biopago)}" data-label="Biopago"><strong>${fmt(cie.diferencia.biopago)} Bs</strong></td>
      </tr>
      <tr>
        <td class="concepto table-warning-cell"><strong>Fondo Dejado (Cierre)</strong></td>
        <td class="valor text-end table-warning-cell" data-label="Bs">${fmt(cie.fondo_dejado.efectivo_bs)} Bs</td>
        <td class="valor text-end table-warning-cell" data-label="USD">${fmt(cie.fondo_dejado.dolares)} $</td>
        <td class="valor text-end table-warning-cell" data-label="Pesos">${fmt(cie.fondo_dejado.pesos, 0)} COP</td>
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
