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

    <title>Stock</title>

    <!-- Bootstrap -->
    <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="../build/css/custom.min.css" rel="stylesheet">



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


    if ($_GET['id']) {
      echo '<script>
function mensajeVenta(){	
			alertify.success("Exito al vender");    
		}
                </script>
                <body onload="mensajeVenta()">
                </body>';
    }



    ?>


  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">

          <div class='left_col scroll-view'>
            <div class='navbar nav_title' style='border: 0;'>
              <a href='index.html' class='site_title'><img src="images/logo1-inv-compact.png" style="max-width:45px"> <span><img src='images/LETTER.png' style='max-width:140px'><span></a>
            </div>

            <div class='clearfix'></div>

            <!-- menu profile quick info -->
            <div class='profile clearfix'>
              <div class='profile_pic'>
                <img src='images/img.png' alt='...' class='img-circle profile_img'>
              </div>
              <div class='profile_info'>
                <h2><?php echo $nombreUsuario ?></h2>
                <span><?php if ($nivelUsuario == '1') {
                        echo 'Administrador';
                      } else {
                        echo 'Empleado';
                      }
                      ?></span>
              </div>
            </div>
            <!-- /menu profile quick info -->

            <br />

            <?php echo $menu ?>

          </div>
        </div>

        <!-- top navigation -->
        <?php echo $topnav ?>
        <!-- /top navigation -->
        <style>
          .table-responsive {
            height: 550px !important;
          }



          .table-responsive::-webkit-scrollbar {
            height: 7px !important;
            width: 7px !important;
            background: rgba(88, 115, 254, 0.04) !important
          }

          .table-responsive::-webkit-scrollbar-thumb {
            background: #1ABB9C !important;
            height: 10px !important;
            border-radius: 5px !important;

          }
        </style>





        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class='page-title'>
              <div class='title_left'>
                <div class='col-md-5 col-sm-5  form-group pull-right'>
                  <div class='input-group'>
                    <h3>Control de Stock</h3>
                  </div>
                </div>
              </div>


            </div>
            <div class="clearfix"></div>
            <div class="row" style="display: block;">
              <div class="col-md-12 col-sm-12  ">
                <div class="x_panel ">
                  <div class="x_title">
                    <h2>Stock</h2>

                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content ">
                    <p>Productos con <code>stock</code> positivo.</p>
                    <div class="table-responsive">
                      <table class="table table-striped  jambo_table bulk_action">
                        <thead>
                          <tr class="headings">
                            <th class="column-title">#</th>
                            <th class="column-title">Nombre </th>
                            <th class="column-title">Precio <small>Compra</small> </th>
                            <th class="column-title">Porcentaje </th>
                            <th class="column-title">Cantidad</th>
                            <th class="column-title">Precio <small>($)</small></th>
                            <th class="column-title">Precio <small>(COP)</small></th>
                            <th class="column-title">Precio <small>(BS)</small></th>

                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $query6 = $conexion->query("SELECT * FROM productos WHERE stock>= 1 AND activo= 0 ORDER BY nombre ASC");
                          if ($query6->num_rows > 0) {
                            $tabla6 = '';
                            $contador = 1;
                            while ($row6 = $query6->fetch_assoc()) {
                              $cantidadUnidad = $row6["cantidad_unidades"];
                              $precioDolarCompra = $row6["precio_compra"] / $cantidadUnidad;
                              $porcentaje = $row6["porcentaje"];
                              $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
                              $precioPesoVenta = $precioDolarVenta * $PesoDolar;
                              $precioBsVenta = $precioDolarVenta * $dolarBolivar;
                              $precioPesoVenta =  round($precioPesoVenta, 2, PHP_ROUND_HALF_DOWN);
                              $precioBsVenta =  round($precioBsVenta, 2, PHP_ROUND_HALF_DOWN);
                              $precioPesoVenta =   number_format($precioPesoVenta, '0', ',', '.');
                              $precioBsVenta =   number_format($precioBsVenta, '2', ',', '.');



                              if ($row6['stock'] <= $stockCritico) {
                                $stock =  '<span style="color:red;font-weight: bolder;">' . $row6['stock'] . '</span>';
                              } else {
                                $stock =  $row6['stock'];
                              }
                              $tabla6 .= '
          <tr class="even pointer">
                            <td class=" ">' . $contador++ . '</td>
                            <td class=" ">' . $row6["nombre"] . '</td>
                            <td class=" ">' . $row6["precio_compra"] . '$</td>
                            <td class=" ">' . $row6["porcentaje"] . '</td>
                            <td class=" ">' . $stock . '</td>
                            <td class=" ">' . round($precioDolarVenta, 2, PHP_ROUND_HALF_DOWN) . ' $</td>
                            <td class=" ">' . $precioPesoVenta . ' <small>COP</small></td>
                            <td class="a-right a-right ">' . $precioBsVenta . ' <small>BS</small></td>
                            </td>
                          </tr>
                          
                          
                          
        
        
        
        
       ';
                            }
                            echo $tabla6;
                          }
                          ?>


                        </tbody>
                      </table>
                    </div>

                    <style>
                      .pdf {
                        float: right;
                        font-size: 28px;
                        margin-right: 10px;

                      }

                      .pdf:hover {
                        color: #1ABB9C;

                      }
                    </style>

                  </div>
                  <a href="../build/pdf/stock.php" class="pdf"><i class="fa  fa-file-pdf-o"></i></a>
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