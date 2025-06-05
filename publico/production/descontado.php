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
                              $query = "
                            SELECT 
                              o.id AS orden_id,
                              o.customer_id,
                              o.created,
                              o.total_price,
                              o.total_price_cop,
                              o.total_price_bs,
                              u.nombre AS nombre_usuario,
                              p.nombre AS nombre_producto,
                              oa.quantity
                            FROM orden o
                            JOIN usuarios u ON u.id = o.customer_id
                            JOIN orden_articulos oa ON oa.order_id = o.id
                            JOIN productos p ON p.id = oa.product_id
                            WHERE o.status = '3'
                            ORDER BY o.id DESC
                            LIMIT 150
                          ";

                              $result = $conexion->query($query);

                              $ordenes = [];
                              if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                  $id = $row['orden_id'];
                                  if (!isset($ordenes[$id])) {
                                    $ordenes[$id] = [
                                      'contador' => count($ordenes) + 1,
                                      'usuario' => $row['nombre_usuario'],
                                      'fecha' => $row['created'],
                                      'total_price' => $row['total_price'],
                                      'total_price_cop' => $row['total_price_cop'],
                                      'total_price_bs' => $row['total_price_bs'],
                                      'productos' => []
                                    ];
                                  }

                                  $ordenes[$id]['productos'][] = $row['quantity'] . ' ' . $row['nombre_producto'];
                                }

                                foreach ($ordenes as $orden) {
                                  $productos = implode(', ', $orden['productos']);
                                  echo "
                                      <tr class='even pointer'>
                                        <td>{$orden['contador']}</td>
                                        <td>{$orden['usuario']}</td>
                                        <td>{$orden['fecha']}</td>
                                        <td>\${$orden['total_price']}</td>
                                        <td>" . number_format($orden['total_price_cop'], 0, ',', '.') . " COP</td>
                                        <td>" . number_format($orden['total_price_bs'], 2, ',', '.') . " Bs</td>
                                        <td>{$productos}</td>
                                      </tr>
                                      ";
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