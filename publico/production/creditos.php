<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();
  $tipo_u = $_SESSION['nivel'];

  $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";
  if ($tipo_u == 2) {
    $id_sucursal = $_SESSION['sucursal'];
    $query .= " AND id='$id_sucursal'";
  }
  $query .= " ORDER BY principal DESC";

  $stmt = mysqli_prepare($conexion, $query);
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <title>Control de Créditos</title>
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
    .dash-panel .panel-body.p-0 { padding: 0 !important; }

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

    .deuda-banner {
      background: var(--dash-card);
      border: 1px solid var(--dash-border);
      border-radius: 14px;
      padding: 20px 24px;
      margin-bottom: 20px;
      text-align: center;
    }
    .deuda-banner h2 {
      font-size: 16px;
      font-weight: 600;
      color: var(--dash-text);
      margin: 0;
    }
    .deuda-banner strong {
      color: var(--dash-mint);
      font-weight: 700;
    }

    .btn-detalles {
      width: 32px; height: 32px;
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
      text-decoration: none;
    }
    .btn-detalles:hover {
      border-color: var(--dash-mint);
      color: var(--dash-mint);
      text-decoration: none;
    }
    .btn-detalles ion-icon { font-size: 16px; }

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
  </style>
</head>
<body class="nav-md">
<div class="container body">
  <div class="main_container">
    <?php echo $menu ?>
    <?php echo $topnav ?>

    <div class="right_col">
      <div class="dash-header">
        <h3>Créditos</h3>
        <p>Listado de créditos otorgados</p>
      </div>

      <?php
      if (isset($_GET['cedulaDeudor'])) {
        $varDeudor = $_GET['cedulaDeudor'];
        function totalDeuda($var) {
          global $conexion;
          global $pesoDolar;
          global $dolarBolivar;
          $totalDeudor = 0;
          $nombreDeudor = '';
          $query2 = "SELECT * FROM creditos WHERE cedula='$var'";
          $buscarAlumnos2 = $conexion->query($query2);
          if ($buscarAlumnos2->num_rows > 0) {
            while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
              $nombreDeudor = $filaAlumnos2['cliente'];
              $totalDeudor += $filaAlumnos2['total_price'];
            }
          }
          $DeudaPesos = $totalDeudor * $pesoDolar;
          $DeudaBs = $totalDeudor * $dolarBolivar;
          echo '<div class="deuda-banner">';
          echo '<h2>' . htmlspecialchars($nombreDeudor) . ' — Deuda total: <strong>' . number_format($totalDeudor, 2, '.', ',') . ' USD</strong> / <strong>' . number_format($DeudaPesos, 0, ',', '.') . ' COP</strong> / <strong>' . number_format($DeudaBs, 2, ',', '.') . ' Bs</strong></h2>';
          echo '</div>';
        }
        totalDeuda($varDeudor);
      }
      ?>

      <div class="dash-panel">
        <div class="panel-header">
          <h6><ion-icon name="receipt-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Listado de créditos</h6>
          <?php if ($tipo_u == 1): ?>
            <select class="dash-select" id="sucursal-selector" name="sucursal-selector">
              <?php if (count($sucursales) > 1): ?>
                <option value="">-- Todos --</option>
              <?php endif; ?>
              <?php foreach ($sucursales as $row): ?>
                <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                  <?= htmlspecialchars($row['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
        <div class="panel-body p-0">
          <div class="dash-table-wrap">
            <table id="datatable" class="dash-table" style="width:100%">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Cliente</th>
                  <th>Créditos</th>
                  <th>Total USD</th>
                  <th>Sucursal</th>
                  <th></th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
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

<script>
const tabla = $('#datatable').DataTable();

function cargarTabla(sucursal = null) {
  $.ajax({
    url: '../../configurar/creditos_list.php',
    type: 'POST',
    dataType: 'html',
    data: { tabla: true, sucursal: sucursal },
    cache: false,
    success: function(response) {
      if (response) {
        var data = JSON.parse(response);
        tabla.clear();
        let count = 1;
        for (var i = 0; i < data.length; i++) {
          tabla.row.add([
            count++,
            data[i].cliente,
            data[i].cantidad_creditos,
            data[i].suma_monto ? '$ ' + data[i].suma_monto : '-',
            data[i].sucursal,
            `<a class="btn-detalles" href="creditos_cliente.php?cliente=${encodeURIComponent(data[i].cliente)}" title="Ver detalles">
              <ion-icon name="eye-outline"></ion-icon>
            </a>`
          ]);
        }
        tabla.draw();
      }
    }
  });
}
cargarTabla();

$(document).ready(function() {
  $('#sucursal-selector').on('change', function() {
    cargarTabla(this.value);
  });
});
</script>
</body>
</html>
<?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}
?>