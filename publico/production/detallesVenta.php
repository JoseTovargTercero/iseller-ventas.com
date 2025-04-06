<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');



if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {



    $topnav = topnav();

    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Ventas == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    if ($_SESSION['validate'] != 'ok') {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];


    $query2 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
        }
    }




?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Detalles </title>

        <?php require_once('includes/headers.php'); ?>

        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">
        <link href='../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css' rel='stylesheet'>
        <!-- Custom Theme Style -->

        <?php

        if ($hayunError == "SI") {
            echo '<script>
            function mensaje(){	
			alertify.error("Error en la fecha consultada.");}
            </script>
            <body onload="mensaje()">
            </body>';
        }
        ?>


    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>


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

                <!-- page content -->
                <div class='right_col' role='main'>

                    <div class=''>

                        <h4>Ventas</h4>
                        <p style="margin-top: -10px;">Detalles</p>

                        <div class='clearfix'></div>

                        <div class='row   fadeInUp animated'>




                            <div class='col-lg-12'>
                                <div class='x_panel  '>
                                    <div class='x_title'>
                                        <h2>Detalles de la venta</h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>


                                        <div class='row'>



                                            <div class='col-lg-12'>

                                                <br>
                                                <p style="margin-left: 20px">
                                                    <?php
                                                    $id = $_GET['id'];

                                                    $query77 = "SELECT * FROM orden WHERE id='$id'";
                                                    $buscarAlumnos77 = $conexion->query($query77);
                                                    if ($buscarAlumnos77->num_rows > 0) {
                                                        $contador = 1;
                                                        while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                                            echo 'Despachado el <strong>' . $filaAlumnos77['created'] . '</strong>';
                                                            if ($filaAlumnos77['status'] == '4') {
                                                                echo '<br>Esta venta se realizo bajo la modalidad "al mayor" y se le aplico un descuento del <strong>' . number_format($filaAlumnos77['descontado'], '2', ',', '.') . '%</strong>';
                                                            }

                                                            echo '<br>Valor de la venta: <strong>' . number_format($filaAlumnos77['total_price'], '2', ',', '.') . '$</strong>';
                                                        }
                                                    }
                                                    ?>
                                                </p>

                                                <div class='card-box table-responsive' style="margin-top: 20px;">

                                                    <table id='datatable-responsive' class='table table-striped table-bordered' style='width:100%'>
                                                        <thead>
                                                            <tr class='headings'>
                                                                <th class='column-title'>#</th>
                                                                <th class='column-title'>Pago por</th>
                                                                <th class='column-title'>Producto</th>
                                                                <th class='column-title'>Cantidad</th>
                                                                <th class='column-title'>Pagado</th>
                                                                <th class='column-title'>Moneda de pago</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            <?php

                                                            $query77 = "SELECT * FROM orden WHERE id='$id'";
                                                            $buscarAlumnos77 = $conexion->query($query77);
                                                            if ($buscarAlumnos77->num_rows > 0) {
                                                                $contador = 1;
                                                                while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                                                    $descuentoDel = $filaAlumnos77['descontado'];
                                                                    $tipoV  = $filaAlumnos77['status'];
                                                                    $orderid = $filaAlumnos77['id'];
                                                                    $tipopago = $filaAlumnos77['tipoPago'];
                                                                    $users = $filaAlumnos77['customer_id'];


                                                                    switch ($filaAlumnos77['tipoPago']) {

                                                                        case ('1'):
                                                                            $pagoPor = 'Punto';
                                                                            break;

                                                                        case ('2'):
                                                                            $pagoPor = 'Pago Movil';
                                                                            break;

                                                                        case ('3'):
                                                                            $pagoPor = 'Transferencia';
                                                                            break;

                                                                        case ('4'):
                                                                            $pagoPor = 'BS Efectivo';
                                                                            break;

                                                                        case ('5'):
                                                                            $pagoPor = 'Dolares';
                                                                            break;

                                                                        case ('6'):
                                                                            $pagoPor = 'Pesos';
                                                                            break;
                                                                        case ('7'):
                                                                            $pagoPor = 'Biopago';
                                                                            break;
                                                                        case ('8'):
                                                                            $pagoPor = 'Fraccionado';
                                                                            break;
                                                                    }


                                                                    $query999999999 = "SELECT * FROM usuarios WHERE id='$users'";
                                                                    $buscarAlumnos999999999 = $conexion->query($query999999999);
                                                                    if ($buscarAlumnos999999999->num_rows > 0) {
                                                                        while ($filaAlumnos999999999 = $buscarAlumnos999999999->fetch_assoc()) {
                                                                            $usuario1 = $filaAlumnos999999999['nombre'];
                                                                        }
                                                                    }

                                                                    echo '<span style="margin-left: 21px; ">Usuario: <strong>' . $usuario1 . '</strong><br><br></span>';



                                                                    $query7E = $conexion->query("SELECT * FROM orden_articulos WHERE order_id='$orderid' ");
                                                                    if ($query7E->num_rows > 0) {

                                                                        while ($row7E = $query7E->fetch_assoc()) {
                                                                            $producto  = $row7E['product_id'];
                                                                            $productoquanty  = $row7E['quantity'];
                                                                            $precioP = $row7E['precio_venta_dolar'];
                                                                            $precioFinal = $precioP * $productoquanty;
                                                                            $precio_venta_dolar = $row7E['precio_venta_dolar'];
                                                                            $precio_venta_bs = $row7E['precio_venta_bs'];
                                                                            $precio_venta_cop = $row7E['precio_venta_cop'];


                                                                            $query9999999999 = "SELECT * FROM productos WHERE id='$producto'";
                                                                            $buscarAlumnos9999999999 = $conexion->query($query9999999999);
                                                                            if ($buscarAlumnos9999999999->num_rows > 0) {
                                                                                while ($filaAlumnos9999999999 = $buscarAlumnos9999999999->fetch_assoc()) {


                                                                                    if ($tipoV = 4) {
                                                                                        $precioDescontado = $precioP - ($precioP * $descuentoDel / 100);
                                                                                        $precioDescontado = $precioDescontado;
                                                                                        $precioFinal = $precioDescontado * $productoquanty;
                                                                                    } else {
                                                                                        $precioDescontado = 'No aplica';
                                                                                    }




                                                                                    if ($tipopago == '1' || $tipopago == '2' || $tipopago == '3' || $tipopago == '4' || $tipopago == '7') {
                                                                                        // Bolivar...
                                                                                        $precioFinalMoneda = $precio_venta_bs * $productoquanty;
                                                                                        $moneda = '<small>BS</small>';
                                                                                    } elseif ($tipopago == '5') {
                                                                                        // Dolar...
                                                                                        $precioFinalMoneda = $precio_venta_dolar * $productoquanty;
                                                                                        $moneda = '<small>$</small>';
                                                                                    } else {
                                                                                        // pesos...
                                                                                        $precioFinalMoneda = $precio_venta_cop * $productoquanty;
                                                                                        $moneda = '<small>COP</small>';
                                                                                    }




                                                                                    echo '
                                                                                   <tr class="even pointer">
                                                                                  <td class=" ">' . $contador++ . '</td>
                                                                                  <td>' . $pagoPor . '</td>
                                                                                  <td>' . $filaAlumnos9999999999['nombre'] . '</td>
                                                                                  <td>' . $productoquanty . '</td>
                                                                                  <td>$' . number_format($precioFinal, '2', ',', '.') . '</td>
                                                                                  <td>' . number_format($precioFinalMoneda, '2', ',', '.') . ' ' . $moneda . '</td>
                                                                                  </tr>';
                                                                                }
                                                                            }
                                                                        }
                                                                    }
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

                        <div class='row' style='display: block;'>


                        </div>
                    </div>
                </div>
                <!-- /page content -->

                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        I-SELLER - by <a href='#'>Jose Ricardo Tovarg III</a>
                    </div>
                    <div class='clearfix'></div>
                </footer>
                <!-- /footer content -->
            </div>
        </div>

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- FastClick -->
        <script src='../vendors/fastclick/lib/fastclick.js'></script>
        <!-- NProgress -->
        <script src='../vendors/nprogress/nprogress.js'></script>
        <!-- iCheck -->
        <script src='../vendors/iCheck/icheck.min.js'></script>
        <!-- Datatables -->
        <script src='../vendors/datatables.net/js/jquery.dataTables.min.js'></script>
        <script src='../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/dataTables.buttons.min.js'></script>
        <script src='../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.flash.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.html5.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.print.min.js'></script>
        <script src='../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js'></script>
        <script src='../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js'></script>
        <script src='../vendors/datatables.net-responsive/js/dataTables.responsive.min.js'></script>
        <script src='../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js'></script>
        <script src='../vendors/datatables.net-scroller/js/dataTables.scroller.min.js'></script>
        <script src='../vendors/jszip/dist/jszip.min.js'></script>
        <script src='../vendors/pdfmake/build/pdfmake.min.js'></script>
        <script src='../vendors/pdfmake/build/vfs_fonts.js'></script>

        <!-- Custom Theme Scripts -->
        <script src='../build/js/custom.min.js'></script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>