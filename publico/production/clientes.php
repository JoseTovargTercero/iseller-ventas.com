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
    if ($Clientes == 0) {
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
      $Pesobolivar = $filaAlumnos['peso_bolivar'];
      $bolivarPesoTrans = $filaAlumnos['bolivarPesoTrans'];
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

    <title>Clientes</title>
    <!-- Bootstrap -->
    <link href="cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
    <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <!-- Datatables -->

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

    <?php

    @$accion = $_GET['accion'];

    switch ($accion) {
      case ('borrado'):
        echo '<script>
            function mensaje(){	
			alertify.success("Producto borrado correctamente"); }
            </script>
            <body onload="mensaje()">
            </body>';

        break;

      case ('editado'):
        echo '<script>
            function mensaje(){	
			alertify.success("Producto Actualizado."); }
            </script>
            <body onload="mensaje()">
            </body>';
        break;

      case ('favorito-SI'):
        echo '<script>
            function mensaje(){	
			alertify.success("Agregado a favoritos.");}
            </script>
            <body onload="mensaje()">
            </body>';
        break;

      case ('favorito-NO'):
        echo '<script>
            function mensaje(){	
			alertify.success("Quitado de favoritos.");}
            </script>
            <body onload="mensaje()">
            </body>';
        break;
      case ('editado'):
        echo '<script>
            function mensaje(){	
			alertify.success("Editado correctamente.");}
            </script>
            <body onload="mensaje()">
            </body>';
        break;
      case ('editado_error_letra'):
        echo '<script>
            function mensaje(){	
			alertify.error("No puede cambiar la inicial del nombre del producto.");}
            </script>
            <body onload="mensaje()">
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


        <!-- page content -->
        <div class="right_col ">




          <h4>Cliente</h4>
          <p style="margin-top: -10px;">Libro de clientes</p>


          <div class="x_panel  fadeInUp animated">
            <div class="x_title">
              <h2>Clientes</h2>

              <div class="clearfix"></div>
            </div>
            <div class="x_content">



              <div class="row">







                <div class="col-lg-12">
                  <div class="card-box table-responsive">

                    <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                      <thead>
                        <tr class="headings">
                          <th>#</th>
                          <th>Nombre</th>
                          <th>Documento</th>
                          <th>Telefono</th>
                          <th>Compras</th>
                          <th>Gastado</th>
                        </tr>
                      </thead>




                      <tbody>
                        <?php




                        $query6 = $conexion->query("SELECT * FROM clientes WHERE status=1 AND id='1' ORDER BY created DESC");
                        if ($query6->num_rows > 0) {
                          $tabla6 = '';
                          $contador = 1;
                          while ($row6 = $query6->fetch_assoc()) {
                            $tabla6 .= '
          <tr class="even pointer">
                            <td class=" ">' . $contador++ . '</td>
                            <td class=" ">' . $row6["name"] . '</td>
                            <td class=" ">' . number_format($row6["email"], '0', ',', '.') . '</td>
                            <td class=" ">' . $row6["phone"] . '</td>
                            <td class=" ">' . $row6["address"] . '</td>
                            <td class=" ">$' . number_format($row6["created"], '2', ',', '.') . '</td>
                          </tr>
       ';
                          }
                          echo $tabla6;
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