<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();



?>


  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='icon' href='images/favicon.ico' type='image/ico' />

    <title>Descontado</title>

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


            <h4>Descontado</h4>
            <p style="margin-top: -10px;">Productos descontados del stock</p>


            <div class="clearfix"></div>
            <div class="row" style="display: block;">






              <div class="col-lg-12">
                <div class="x_panel   fadeInUp animated">
                  <div class="x_title">
                    <h2>Descontado</h2>

                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="row">






                      <div class="col-lg-12">
                        <div class="card-box table-responsive">

                          <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                              <tr class="headings">
                                <th class="column-title">#</th>
                                <th class="column-title">Usuario </th>
                                <th class="column-title">Fecha </th>
                                <th class="column-title">USD</th>
                                <th class="column-title">COP</th>
                                <th class="column-title">BS</th>
                                <th class="column-title">Poductos</th>
                              </tr>
                            </thead>




                            <tbody>
                              <?php
                              $query77 = "SELECT * FROM orden WHERE status='3' ORDER BY id DESC LIMIT 150";

                              $buscarAlumnos77 = $conexion->query($query77);
                              if ($buscarAlumnos77->num_rows > 0) {
                                $contador = 1;
                                while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                  $users = $filaAlumnos77['customer_id'];
                                  $orderid = $filaAlumnos77['id'];

                                  $query999999999 = "SELECT * FROM usuarios WHERE id='$users'";
                                  $buscarAlumnos999999999 = $conexion->query($query999999999);
                                  if ($buscarAlumnos999999999->num_rows > 0) {
                                    while ($filaAlumnos999999999 = $buscarAlumnos999999999->fetch_assoc()) {
                                      $usuario1 = $filaAlumnos999999999['nombre'];
                                    }
                                  }

                                  $query7E = $conexion->query("SELECT * FROM orden_articulos WHERE order_id='$orderid' ");
                                  if ($query7E->num_rows > 0) {

                                    while ($row7E = $query7E->fetch_assoc()) {
                                      $producto  = $row7E['product_id'];
                                      $productoquanty  = $row7E['quantity'];

                                      $query9999999999 = "SELECT * FROM productos WHERE id='$producto'";
                                      $buscarAlumnos9999999999 = $conexion->query($query9999999999);
                                      if ($buscarAlumnos9999999999->num_rows > 0) {
                                        while ($filaAlumnos9999999999 = $buscarAlumnos9999999999->fetch_assoc()) {
                                          $porductos .= $productoquanty . ' ' . $filaAlumnos9999999999['nombre'] . ', ';
                                        }
                                      }
                                    }
                                  }
                                  $porductos = substr($porductos, 0, -2);
                                  $valorPeso = $filaAlumnos77['total_price_cop'];
                                  $valorbolivar = $filaAlumnos77['total_price_bs'];


                                  echo '
                             <tr class="even pointer">
                            <td class=" ">' . $contador++ . '</td>
                            <td>' . $usuario1 . '</td>
                            
                            <td>' . $filaAlumnos77['created'] . '</td>
                            <td>$' . $filaAlumnos77['total_price'] . '</td>
                            <td>' . number_format($valorPeso, '0', ',', '.') . ' COP</td>
                            <td>' . number_format($valorbolivar, '2', ',', '.') . ' Bs</td>
                            <td>' . $porductos . '</td>
                          </tr>';

                                  $porductos = '';
                                }
                              }




                              ?>

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

    <!-- jQuery -->
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
    <!-- Custom Theme Scripts -->
    <script src="../build/js/custom.js"></script>
  </body>

  </html>
<?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}
?>