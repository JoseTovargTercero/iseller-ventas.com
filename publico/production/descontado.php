<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');



if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();

  if ($_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
  }
  if ($_SESSION['nivel'] == '1') {
    $menu = MenuAdministrador();
  } else {
    $menu = MenuStandar();
    if ($Dejado_Ganar == 0) {
      define('PAGINA_INICIO', '../../index.php');
      header('Location: ' . PAGINA_INICIO);
    }
  }

  $nivelUsuario = $_SESSION['nivel'];
  $nombreUsuario = $_SESSION['nombre'];

  $query = "SELECT * FROM cambio WHERE id='1'";
  $buscarAlumnos = $conexion->query($query);
  if ($buscarAlumnos->num_rows > 0) {
    while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

      $PesoDolar = $filaAlumnos['pesoDolar'];
      $Pesobolivar = $filaAlumnos['bolivar_peso'];
      $peso_bolivar = $filaAlumnos['peso_bolivar'];
      $dolarBolivar = $filaAlumnos['DolarBolivar'];
    }
  }

  $query2 = "SELECT * FROM empresa";
  $buscarAlumnos2 = $conexion->query($query2);
  if ($buscarAlumnos2->num_rows > 0) {
    while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
      $nombreEmpresa = $filaAlumnos2['emp'];
      $stockCritico = $filaAlumnos2['stockCritico'];
    }
  }

  // initializ shopping cart class
  include 'la-carta.php';
  $cart = new Cart;

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

    <!-- Bootstrap -->
    <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <link href="../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
    <!-- Custom Theme Style -->
    <link href="../build/css/custom.min.css" rel="stylesheet">

    <link rel="stylesheet" href="../../iseller.es/css/animate.css">
    <!-- Icomoon Icon Fonts-->
    <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
    <!-- Simple Line Icons -->
    <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">

    <script src='js/jquery.min.js'></script>
    <script src='peticion.js'></script>

    <script src='peticion_producto.js'></script>

    <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
    <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
    <script src='..//assets/AlertifyJS/alertify.min.js'></script>

    <script src="ex/jquery.min.js"></script>
    <script src="ex/bootstrap.min.js"></script>


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
          window.open("AccionCarta.php?action=placeOrder", "_self");
        });
        //callbak al pulsar botón negativo
        confirm.set('oncancel', function() {
          alertify.error('Venta cancelada');
        })

      }
    </script>







    <?php

    switch ($_GET['accion']) {
      case ('pagado'):
        echo '<script>
          function mensajeVenta(){	
          alertify.success("Credito cancelado correctamente");  }
                </script>
                <body onload="mensajeVenta()">
                </body>';
        break;
    }




    ?>


  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">

          <div class='left_col scroll-view'>
            <div class='navbar nav_title' style='border: 0;'>
              <a href='index.php' class='site_title'>
                <img src='images/logo1-inv-compact.png' style='max-width:147px; opacity: 0.8'> <span>
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

        <!-- footer content -->
        <footer>
          <div class="pull-right">
            i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
          </div>
          <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
      </div>
    </div>

    <!-- jQuery -->
    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FastClick -->
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="../vendors/nprogress/nprogress.js"></script>
    <!-- iCheck -->
    <script src="../vendors/iCheck/icheck.min.js"></script>
    <!-- Datatables -->
    <script src="../vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
    <script src="../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
    <script src="../vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
    <script src="../vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
    <script src="../vendors/jszip/dist/jszip.min.js"></script>
    <script src="../vendors/pdfmake/build/pdfmake.min.js"></script>
    <script src="../vendors/pdfmake/build/vfs_fonts.js"></script>

    <!-- Custom Theme Scripts -->
    <script src="../build/js/custom.min.js"></script>
  </body>

  </html>
<?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}
?>