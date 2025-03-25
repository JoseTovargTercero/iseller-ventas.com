<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');



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

    if ($Creditos == 0) {
      define('PAGINA_INICIO', '../../index.php');
      header('Location: ' . PAGINA_INICIO);
    }
  }

  $nivelUsuario = $_SESSION['nivel'];
  $nombreUsuario = $_SESSION['nombre'];
  $cliente = $_GET['cliente'];

  $query7 = $conexion->query("SELECT * FROM cambio WHERE id='1'");
  if ($query7->num_rows > 0) {
    while ($row7 = $query7->fetch_assoc()) {
      $pesoDolar = $row7['pesoDolar'];
      $DolarBolivar = $row7['DolarBolivar'];
      $bolivarPesoTrans = $row7['bolivarPesoTrans'];
    }
  }



  // initializ shopping cart class
  include 'La-carta.php';
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

    <title>Control de Creditos</title>

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

    switch (@$_GET['accion']) {
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


            <h4>Creditos</h4>
            <p style="margin-top: -10px;">Listado de creditos otorgados</p>


            <div class="clearfix"></div>
            <div class="row" style="display: block;">





              <div class="col-lg-12">
                <div class="x_panel  fadeInUp animated">
                  <div class="x_title">
                    <h2>Creditos</h2>

                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="row">
                      <div class="col-lg-12">
                        <div class="card-box table-responsive">

                          <table class="table table-bordered" style="width:100%">
                            <thead>
                              <tr class="headings">
                                <th class="column-title">#</th>
                                <th class="column-title">Producto</th>
                                <th class="column-title text-center">Valor ($)</th>
                                <th class="column-title text-center">Valor (COP)</th>
                                <th class="column-title text-center">Valor (BS)</th>
                              </tr>
                            </thead>




                            <tbody>
                              <?php

                              function datosProductos($producto)
                              {
                                global $conexion, $pesoDolar, $bolivarPesoTrans, $DolarBolivar;


                                $query = $conexion->query("SELECT * FROM productos  WHERE id = '$producto' AND activo= 0");
                                if ($query->num_rows > 0) {
                                  while ($row = $query->fetch_assoc()) {

                                    $cantidadUnidad = $row["cantidad_unidades"];
                                    $origen = $row["origen"];

                                    $precioDolarCompra = $row["precio_compra"] / $cantidadUnidad;
                                    $porcentaje = $row["porcentaje"];
                                    $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
                                    $precioDolarVenta = number_format($precioDolarVenta, '2', '.', ',');


                                    $precioPesoVenta = $precioDolarVenta * $pesoDolar;
                                    $precioPesoVenta = formatPesoVista($precioPesoVenta);


                                    if ($origen == 'c') {
                                      $precioBsVenta = ($precioPesoVenta / $bolivarPesoTrans) / 1000;
                                    } else {
                                      $precioBsVenta = $precioDolarVenta * $DolarBolivar;
                                    }

                                    $nombre = strtoupper($row["nombre"]);
                                    // quitar caracteres especiales del nombre
                                    $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);

                                    $data = [
                                      'id' => $row['id'],
                                      'stock' => $row['stock'],
                                      'nombre' =>  "$nombre",
                                      'precio_dolar_visible' => $precioDolarVenta,
                                      'precio_peso_visible' => $precioPesoVenta,
                                      'precio_bs_visible' => $precioBsVenta,
                                      'codigo' => $row['codigo'],
                                      'origen' => $row['origen'],
                                    ];
                                  }
                                  return $data;
                                }
                              }

                              function getProductos($id_order)
                              {
                                global $conexion;
                                $productos = [];

                                $stmt = mysqli_prepare($conexion, "SELECT product_id, quantity FROM orden_articulos WHERE order_id=?");
                                $stmt->bind_param('s', $id_order);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                  while ($row = $result->fetch_assoc()) {
                                    $productos[] = [ // ← Quitamos $productos[$id_order][]
                                      'id' => $row['product_id'],
                                      'cantidad' => $row['quantity'],
                                      'datos' => datosProductos($row['product_id'])
                                    ];
                                  }
                                }

                                $stmt->close();
                                return $productos;
                              }

                              $productos_by_order = [];

                              $stmt = mysqli_prepare($conexion, "SELECT creditos.order_id, orden.created, orden.total_price FROM creditos LEFT JOIN orden ON orden.id = creditos.order_id WHERE estado='2' AND negocio = ? ORDER BY negocio DESC");
                              $stmt->bind_param('s', $cliente);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                  $productos_by_order[] = [
                                    'id' => $row['order_id'],
                                    'fecha' => $row['created'],
                                    'total' => $row['total_price'],
                                    'productos' => getProductos($row['order_id'])
                                  ];
                                }
                              }
                              $stmt->close();


                              $total_general_usd = 0;
                              $total_general_cop = 0;
                              $total_general_bss = 0;


                              foreach ($productos_by_order as  $item) {
                                $total_seccion_usd = 0;
                                $total_seccion_cop = 0;
                                $total_seccion_bss = 0;


                                foreach ($item['productos'] as $sub_item) {

                                  /* aca adentro ocurre el error al acceder a los datos */

                                  $precio_usd = $sub_item['datos']['precio_dolar_visible'] * $sub_item['cantidad'];
                                  $precio_cop = $sub_item['datos']['precio_peso_visible'] * $sub_item['cantidad'];
                                  $precio_bss = $sub_item['datos']['precio_bs_visible'] * $sub_item['cantidad'];

                                  $total_seccion_usd += $precio_usd;
                                  $total_seccion_cop += $precio_cop;
                                  $total_seccion_bss += $precio_bss;

                                  $total_general_usd += $precio_usd;
                                  $total_general_cop += $precio_cop;
                                  $total_general_bss += $precio_bss;


                                  echo '<tr>';
                                  echo '
                                    <td></td>
                                    <td>' . htmlspecialchars($sub_item['datos']['nombre']) . '</td>
                                    <td class="text-center">' . $precio_usd . ' <small>$</small></td>
                                    <td class="text-center">' . number_format($precio_cop, 0, '.', ',') . ' <small>Cop</small></td>
                                    <td class="text-center">' . number_format($precio_bss, 2, '.', ',') . ' <small>Bs</small></td>';
                                  echo '</tr>';
                                }

                                echo '<tr style="background-color: rgba(0, 0, 0, .05);">';
                                echo '
                                  <td>COMPRA: <b>' . $item['id'] . '</b></td>
                                  <td>Fecha: ' . $item['fecha'] . '</td>
                                  <td class="text-center"><b>TOTAL: ' . $total_seccion_usd . '<small></small></b></td>
                                  <td class="text-center"><b>TOTAL: ' . number_format($total_seccion_cop, 0, '.', ',') . '<small></small></b></td>
                                  <td class="text-center"><b>TOTAL: ' . number_format($total_seccion_bss, 2, '.', ',') . '<small></small></b></td>';
                                echo '</tr>';
                              }


                              echo '<tr style="background-color: rgba(0, 0, 0, .2);">';
                              echo '
                                <td colspan=2 >DEUDA TOTAL:</td>
                                <td class="text-center"><b>' . $total_general_usd . '<small>$</small></b></td>
                                <td class="text-center"><b>' . number_format($total_general_cop, 0, '.', ',') . '<small>Cop</small></b></td>
                                <td class="text-center"><b>' . number_format($total_general_bss, 2, '.', ',') . '<small>Bs</small></b></td>';
                              echo '</tr>';


                              ?>
                            </tbody>
                          </table>


                          <br>


                          
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