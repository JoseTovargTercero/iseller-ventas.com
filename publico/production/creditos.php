<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();



  $tipo_u =  $_SESSION['nivel'];



  $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";

  if ($tipo_u == 2) {
    // Solo para los usuarios tipo 2
    $id_sucursal = $_SESSION['sucursal'];
    $query .= " AND id='$id_sucursal'";
  }

  $query .= "  ORDER BY principal DESC";

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
  <html lang="en">

  <head>


    <title>Control de Creditos</title>
    <?php require_once('includes/headers.php'); ?>

    <script>
      function updateCartItem(obj, id) {
        $.get("cartAction.php", {
          action: "updateCartItem",
          id: id,
          qty: obj.value
        }, function(data) {
          if (data == 'ok') {
            location.reload();
          } else {
            alert('Cart update failed, please try again.');
          }
        });
      }

      function confirmar() {
        var confirm = alertify.confirm('Confirmar venta', 'Esta apunto de realizar una venta, ¿desea continuar?', null, null).set('labels', {
          ok: 'Confirmar',
          cancel: 'Cancelar'
        });

        //callbak al pulsar botón positivo
        confirm.set('onok', function() {
          window.open("accion_carta.php?action=placeOrder", "_self");
        });
        //callbak al pulsar botón negativo
        confirm.set('oncancel', function() {
          alertify.error('Venta cancelada');
        })

      }
    </script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">

          <div class='left_col scroll-view'>
            <div class='navbar nav_title' style='border: 0;'>
              <a href='index.php' class='site_title'>
                <img src='images/logo1-inv-compact.png' style='max-width:45px; opacity: 0.8'> <span>
                  <img style='max-width:140px'><span> </a>
            </div>
            <div class='clearfix'></div>
            <!-- /menu profile quick info -->
            <br />
            <?php echo $menu ?>
          </div>
        </div>

        <!-- top navigation -->
        <?php echo $topnav ?>
        <!-- /top navigation -->
        <style>
          .gray {
            color: rgba(52, 73, 94, 0.94);
            font-size: 26px;


          }
        </style>





        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">


            <h4>Créditos</h4>
            <p style="margin-top: -10px;">Listado de creditos otorgados</p>


            <div class="clearfix"></div>
            <div class="row" style="display: block;">





              <div class="col-lg-12">
                <div class="x_panel  fadeInUp animated">

                  <div class="x_title d-flex justify-content-between">
                    <h2>Listado de créditos</h2>

                    <?php if ($tipo_u == 1): ?>
                      <div class='form-group mb-3 d-flex'>
                        <label class='form-label p-2' for='first-name'>SUCURSAL: </label>
                        <select class="form-control" id="sucursal-selector" name="sucursal-selector">
                          <?php if (count($sucursales) > 1): ?>
                            <option value="">-- Todos --</option>
                          <?php endif; ?>

                          <?php foreach ($sucursales as $row): ?>
                            <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                              <?= htmlspecialchars($row['nombre']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="x_content">
                    <div class="row">


                      <div class="col-lg-12">
                        <div class="card-box table-responsive">

                          <?php
                          if (isset($_GET['cedulaDeudor'])) {
                            $varDeudor = $_GET['cedulaDeudor'];
                            function totalDeuda($var)
                            {
                              global $conexion;
                              global $pesoDolar;
                              global $dolarBolivar;
                              $totalDeudor = 0;
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

                              $tabla = '<br><h2 style="color: #1ABB9C; text-align: center"> ' . $nombreDeudor . ' Posee una deuda por un total de: <strong>' . $totalDeudor . ' USD</strong> / <strong>' . number_format($DeudaPesos, '0', ',', '.') . ' COP</strong> / <strong>' . number_format($DeudaBs, '2', ',', '.') . ' BS</strong> </h2>';
                              echo $tabla;
                            }
                            totalDeuda($varDeudor);
                          }
                          ?>



                          </p>

                          <table id="datatable-responsive-2" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                              <tr class="headings">
                                <th class="column-title" style="width: 5%;">#</th>
                                <th class="column-title">Cliente</th>
                                <th class="column-title text-center" style="width: 5%;">Créditos</th>
                                <th class="column-title text-center">Sucursal</th>
                                <th class="column-title" style="width: 10%;"></th>
                              </tr>
                            </thead>
                            <tbody>

                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /page content -->
      </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables core -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Buttons extension -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <!-- PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="../build/js/custom.js"></script>

    <script>
      const tabla = $('#datatable-responsive-2').DataTable();


      function cargarTabla(sucursal = null) {

        $.ajax({
          url: '../../configurar/creditos_list.php',
          type: 'POST',
          dataType: 'html',
          data: {
            tabla: true,
            sucursal: sucursal
          },
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
                  data[i].sucursal,
                  ` <a class="btn btn-info btn-sm" href="creditos_cliente.php?cliente=${data[i].cliente}">Detalles</a>`
                ]);

              }

              tabla.draw(); // Redibuja la tabla

            }

          }

        });
      }
      cargarTabla()



      $(document).ready(function() {
        // Filtro por sucursal (opcional)
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