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

    .dash-panel {
      background: var(--dash-card);
      border: 1px solid var(--dash-border);
      border-radius: 14px;
      overflow: hidden;
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
    .dash-panel .panel-body { padding: 6px 0; }

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
  </style>
</head>
<body class="nav-md">
<div class="container body">
  <div class="main_container">
    <?php echo $menu ?>
    <?php echo $topnav ?>

    <div class="right_col">
      <div class="d-flex justify-content-between dash-header">
        <div>
          <h3>Cierres de Caja</h3>
          <p>Cortes de caja por usuario</p>
        </div>
      </div>

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
                  <th>Usuario</th>
                  <th>Hora inicio</th>
                  <th>Concepto</th>
                  <th class="text-end">Efect. Bs</th>
                  <th class="text-end">USD</th>
                  <th class="text-end">Pesos</th>
                  <th class="text-end">Punto</th>
                  <th class="text-end">P.Móvil</th>
                  <th class="text-end">Transfer.</th>
                  <th class="text-end">Biopago</th>
                </tr>
              </thead>
              <tbody id="tbody-cortes">
                <tr><td colspan="10" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Cargando...</td></tr>
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

<script>
function cargarCortes() {
  const fecha = document.getElementById('fechaFiltro').value;
  const sucursal = document.getElementById('sucursalFiltro')?.value || '';

  document.getElementById('tbody-cortes').innerHTML = '<tr><td colspan="10" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Cargando...</td></tr>';
  document.getElementById('tfoot-cortes').innerHTML = '';

  fetch('../../configurar/listaVentas_back.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'totales_por_usuario', fechaSolic: fecha, sucursal: sucursal })
  })
  .then(r => r.json())
  .then(data => {
    if (data.status !== 'success') {
      document.getElementById('tbody-cortes').innerHTML = '<tr><td colspan="10" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Error al cargar datos.</td></tr>';
      return;
    }

    const tbody = document.getElementById('tbody-cortes');
    const tfoot = document.getElementById('tfoot-cortes');
    tbody.innerHTML = '';
    tfoot.innerHTML = '';

    if (!data.cortes || data.cortes.length === 0) {
      tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">No hay cortes de caja registrados para esta fecha.</td></tr>';
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

      tbody.innerHTML += `
      <tr>
        <td rowspan="5" class="align-middle text-center" style="border-bottom:2px solid var(--dash-border);vertical-align:middle;">
          <strong>${u.nombre}</strong><br>${obs}
        </td>
        <td rowspan="5" class="align-middle text-center" style="border-bottom:2px solid var(--dash-border);vertical-align:middle;">
          <strong>${u.apertura.hora_apertura}</strong>
        </td>
        <td><strong>Apertura (Fondo Recibido)</strong></td>
        <td class="text-end">${fmt(ape.efectivo_bs)} Bs</td>
        <td class="text-end">${fmt(ape.dolares)} $</td>
        <td class="text-end">${fmt(ape.pesos, 0)} COP</td>
        <td class="text-end" style="color:var(--dash-text-muted);">-</td>
        <td class="text-end" style="color:var(--dash-text-muted);">-</td>
        <td class="text-end" style="color:var(--dash-text-muted);">-</td>
        <td class="text-end" style="color:var(--dash-text-muted);">-</td>
      </tr>
      <tr>
        <td class="table-info-cell"><strong>Cierre (Contado)</strong></td>
        <td class="text-end table-info-cell">${fmt(cie.contado.efectivo_bs)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.contado.dolares)} $</td>
        <td class="text-end table-info-cell">${fmt(cie.contado.pesos, 0)} COP</td>
        <td class="text-end table-info-cell">${fmt(cie.contado.punto)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.contado.pago_movil)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.contado.transferencia)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.contado.biopago)} Bs</td>
      </tr>
      <tr>
        <td class="table-info-cell">Sistema</td>
        <td class="text-end table-info-cell">${fmt(cie.sistema.efectivo_bs)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.sistema.dolares)} $</td>
        <td class="text-end table-info-cell">${fmt(cie.sistema.pesos, 0)} COP</td>
        <td class="text-end table-info-cell">${fmt(cie.sistema.punto)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.sistema.pago_movil)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.sistema.transferencia)} Bs</td>
        <td class="text-end table-info-cell">${fmt(cie.sistema.biopago)} Bs</td>
      </tr>
      <tr>
        <td class="table-info-cell"><strong>Diferencia</strong></td>
        <td class="text-end table-info-cell ${colorDif(cie.diferencia.efectivo_bs)}"><strong>${fmt(cie.diferencia.efectivo_bs)} Bs</strong></td>
        <td class="text-end table-info-cell ${colorDif(cie.diferencia.dolares)}"><strong>${fmt(cie.diferencia.dolares)} $</strong></td>
        <td class="text-end table-info-cell ${colorDif(cie.diferencia.pesos)}"><strong>${fmt(cie.diferencia.pesos, 0)} COP</strong></td>
        <td class="text-end table-info-cell ${colorDif(cie.diferencia.punto)}"><strong>${fmt(cie.diferencia.punto)} Bs</strong></td>
        <td class="text-end table-info-cell ${colorDif(cie.diferencia.pago_movil)}"><strong>${fmt(cie.diferencia.pago_movil)} Bs</strong></td>
        <td class="text-end table-info-cell ${colorDif(cie.diferencia.transferencia)}"><strong>${fmt(cie.diferencia.transferencia)} Bs</strong></td>
        <td class="text-end table-info-cell ${colorDif(cie.diferencia.biopago)}"><strong>${fmt(cie.diferencia.biopago)} Bs</strong></td>
      </tr>
      <tr style="border-bottom:2px solid var(--dash-border);">
        <td class="table-warning-cell"><strong>Fondo Dejado (Cierre)</strong></td>
        <td class="text-end table-warning-cell">${fmt(cie.fondo_dejado.efectivo_bs)} Bs</td>
        <td class="text-end table-warning-cell">${fmt(cie.fondo_dejado.dolares)} $</td>
        <td class="text-end table-warning-cell">${fmt(cie.fondo_dejado.pesos, 0)} COP</td>
        <td class="text-end table-warning-cell" style="color:var(--dash-text-muted);">-</td>
        <td class="text-end table-warning-cell" style="color:var(--dash-text-muted);">-</td>
        <td class="text-end table-warning-cell" style="color:var(--dash-text-muted);">-</td>
        <td class="text-end table-warning-cell" style="color:var(--dash-text-muted);">-</td>
      </tr>`;
    });
  })
  .catch(err => {
    console.error(err);
    document.getElementById('tbody-cortes').innerHTML = '<tr><td colspan="10" class="text-center" style="padding:40px 0;color:var(--dash-text-muted);font-size:13px;">Error de conexión.</td></tr>';
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
