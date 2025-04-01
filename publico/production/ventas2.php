<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');



if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {


    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }


    $topnav = topnav();

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
            $Pesobolivar = $filaAlumnos['peso_bolivar'];
            $bolivarPesoTrans = $filaAlumnos['bolivarPesoTrans'];
            $dolarBolivar = $filaAlumnos['DolarBolivar'];
        }
    }

    $query3 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query3);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }

    $query2 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }

    include 'La-carta.php';
    $cart = new Cart;

?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='icon' href='images/favicon.ico' type='image/ico' />
        <title>Ventas </title>
        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <!-- Font Awesome -->
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <!-- NProgress -->
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <!-- iCheck -->
        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">
        <!-- bootstrap-wysiwyg -->
        <link href='../vendors/google-code-prettify/bin/prettify.min.css' rel='stylesheet'>
        <!-- Select2 -->
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
        <!-- Switchery -->
        <link href='../vendors/switchery/dist/switchery.min.css' rel='stylesheet'>
        <!-- starrr -->
        <link href='../vendors/starrr/dist/starrr.css' rel='stylesheet'>
        <!-- bootstrap-daterangepicker -->
        <link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>
        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>
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

            function confirmarDescuento() {
                var confirm = alertify.confirm('Descontar del almacen', 'Esta apunto de  descontar productos del almacen ¿desea continuar?', null, null).set('labels', {
                    ok: 'Confirmar',
                    cancel: 'Cancelar'
                });
                //callbak al pulsar botón positivo
                confirm.set('onok', function() {
                    window.open("AccionCarta.php?action=placeOrder&statusV=3", "_self");
                });
                //callbak al pulsar botón negativo
                confirm.set('oncancel', function() {
                    alertify.error('Cancelado');
                })
            }
        </script>
        <?php
        switch ($_GET['accion']) {
            case ('vendido'):
                echo '<script>
        function mensajeVenta(){	
        alertify.success("Exito al vender");  }
                </script>
                <body onload="mensajeVenta()">
                </body>';
                break;
            case ('credito'):
                echo '<script>
        function mensajeVenta(){	
        alertify.warning("Se acreditaron productos");  }
                </script>
                <body onload="mensajeVenta()">
                </body>';
                break;
            case ('descuento'):
                echo '<script>
        function mensajeVenta(){	
        alertify.warning("Se descontaron productos");  }
                </script>
                <body onload="mensajeVenta()">
                </body>';
                break;
        }
        ?>
    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>
                    <div class='left_col scroll-view'>
                        <div class='navbar nav_title' style='border: 0;'>
                            <a href='index.php' class='site_title'><img src="images/logo1-inv-compact.png" style="max-width:45px"> <span><img src='images/LETTER.png' style='max-width:140px'><span></a>
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
                <!-- page content -->
                <div class='right_col' role='main'>
                    <div class=''>
                        <div class='page-title'>
                            <div class='title_left'>
                                <h3>Ventas</h3>
                            </div>
                            <div class='title_right'>
                                <div class='col-md-5 col-sm-5  form-group pull-right top_search'>
                                    <div class='input-group'>
                                        <input type='text' class='form-control' id='busqueda' name='busqueda' placeholder="Peso/Dolar: <?php echo $PesoDolar ?>">
                                        <span class='input-group-btn'>
                                            <button class='btn btn-default' type='button'>Go!</button>
                                        </span>
                                    </div>
                                    <section style='float:right; margin-right:10px;' id='tabla_resultado'>
                                    </section>
                                </div>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                        <div class='row'>
                            <div class="col-lg-12">
                                <br>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="x_panel">
                                    <div class="x_title" style="border-bottom: none">
                                        <caption>
                                            <input type='text' class='form-control' id="search" name='search' placeholder="Nombre del producto">
                                        </caption>
                                        <ul class="nav navbar-right panel_toolbox">
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content fijo">
                                        <section id="tabla_resultado_codigo_producto">
                                        </section>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Carrito </h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                            </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content fijo">
                                        <div class="responsi">
                                            <table class="table">
                                                <thead style="min-width:100%; ">
                                                    <tr class="">
                                                        <th style="width:10%" class="column-title">Cant.</th>
                                                        <th style="width:25%" class="column-title">Producto</th>
                                                        <th style="width:10%;;" class="column-title"><i style="margin-left:10px" class="fa fa-dollar"></i></th>
                                                        <th style="width:20%" class="column-title">Peso</th>
                                                        <th style="width:20%" class="column-title">BS</th>
                                                        <th style="width:10%" class="column-title">SubTotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    if ($cart->total_items() > 0) {
                                                        //get cart items from session
                                                        $cartItems = $cart->contents();
                                                        foreach ($cartItems as $item) {
                                                    ?>
                                                            <tr>
                                                                <td><?php echo $item["qty"]; ?></td>
                                                                <td><?php echo $item["name"]; ?></td>
                                                                <td><?php echo '$' . number_format($item["price"], '2', ',', '.'); ?></td>
                                                                <td><?php echo number_format($item["pricePeso"], '0', ',', '.'); ?></td>
                                                                <td><?php echo number_format($item["priceBolivar"], '0', ',', '.'); ?></td>
                                                                <td><?php echo '$' . number_format($item["subtotal"], '2', ',', '.'); ?></td>
                                                                <td>
                                                                    <a href="AccionCarta.php?action=removeCartItem&id=<?php echo $item["rowid"]; ?>" class="btn btn-danger" onclick="return confirm('¿Desea eliminar este producto del carrito?')"><i class="fa fa-trash-o"></i></a>
                                                                </td>
                                                            </tr>
                                                        <?php }
                                                    } else { ?>
                                                        <tr>
                                                            <td colspan="8">
                                                                <p class="text-center">
                                                                    <br>
                                                                    <br>
                                                                    <br> <br><br> <br>
                                                                    <br>
                                                                    El carrito esta vacio.....
                                                                    <br>
                                                                    <br>
                                                                    <br> <br> <br> <br>
                                                                    <br>
                                                                </p>
                                                            </td>
                                                        <?php } ?>
                                                </tbody>
                                                <tbody>
                                                    <?php $valorVenta = round($cart->total(), 2, PHP_ROUND_HALF_DOWN);
                                                    $valorVentaPeso = $valorVenta * $PesoDolar;
                                                    $valorBolivarVenta = $valorVenta * $dolarBolivar;
                                                    ?>
                                                    <tr>
                                                        <td></td>
                                                        <td></td>
                                                        <td>Total:</td>
                                                        <td><strong> <?php echo number_format($valorVentaPeso, '0', ',', '.'); ?> <small>COP</small></strong></td>
                                                        <td><strong> <?php echo number_format($valorBolivarVenta, '0', ',', '.'); ?> <small>Bs</small></strong></td>
                                                        <td><strong> <?php echo '$' . number_format($cart->total(), '2', ',', '.'); ?></strong></td>
                                                        <td></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <tfoot>
                                                <tr>
                                                    <?php if ($cart->total_items() > 0) { ?>
                                                </tr>
                                            </tfoot>
                                        </div>
                                        <br>
                                        <div class="footer">
                                            <a onclick="confirmarDescuento()" class="btn btn-secondary" style="float:left; color:white; margin-left: 15px;">Descontar</a>
                                            <a href="pagos_Venta.php" class="btn btn-success" style="float:right; color:white;">Vender</a>
                                        <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Favoritos </h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                            </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content contenedoe">
                                        <ul class="list-unstyled timeline">
                                            <?php

                                            $query22 = 'SELECT * FROM empresa';
                                            $buscarAlumnos22 = $conexion->query($query22);
                                            if ($buscarAlumnos22->num_rows > 0) {
                                                while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
                                                    $stockCritico = $filaAlumnos22['stockCritico'];
                                                }
                                            }

                                            $query6 = $conexion->query("SELECT * FROM productos WHERE favorito=1  AND activo= 0 ORDER BY nombre ASC");
                                            if ($query6->num_rows > 0) {
                                                $tabla6 = '';
                                                while ($row6 = $query6->fetch_assoc()) {

                                                    $cantidadUnidad = $row6["cantidad_unidades"];
                                                    $precioDolarCompra = $row6["precio_compra"] / $cantidadUnidad;
                                                    $porcentaje = $row6["porcentaje"];
                                                    $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
                                                    $precioDolarVenta = round($precioDolarVenta, 2, PHP_ROUND_HALF_DOWN);
                                                    $precioPesoVenta = $precioDolarVenta * $PesoDolar;
                                                    $precioPesoVenta = round($precioPesoVenta, 2, PHP_ROUND_HALF_DOWN);
                                                    $precioPesoVenta2 = number_format($precioPesoVenta, '0', ',', '.');
                                                    $precioBsVenta = $precioDolarVenta * $dolarBolivar;
                                                    $precioBsVenta = round($precioBsVenta, 2, PHP_ROUND_HALF_DOWN);
                                                    $precioBsVenta2 = number_format($precioBsVenta, '0', ',', '.');
                                                    $precioBsTransfVenta = $precioPesoVenta / $bolivarPesoTrans;
                                                    if ($row6['stock'] == '0') {
                                                        $alerta = "Agotado";
                                                        $color = 'color:red';
                                                    } elseif ($row6['stock'] <= $stockCritico && $row6['stock'] >= 1) {
                                                        $alerta = "Bajo(" . $row6['stock'] . ")";
                                                        $color = 'color:#1ABB9C';
                                                    } else {
                                                        $alerta = "Optimo(" . $row6["stock"] . ")";
                                                        $color = 'color:lightgray';
                                                    }
                                                    $tabla6 .= '
        <form action="AccionCarta.php" class="animated flipInY col-lg-2">
                <input type="text" id="action" name="action" hidden value="addToCart">
                <input type="text" id="id" name="id" hidden value="' . $row6['id'] . '">
                <input type="text" id="codigo" name="codigo" hidden value="' . $row6['codigo'] . '">
                <input type="text" id="dolarventa" name="dolarventa" hidden value="' . $precioDolarVenta . '">
                <input type="text" id="pesoventa" name="pesoventa" hidden value="' . $precioPesoVenta . '">
                <input type="text" id="bolivarventa" name="bolivarventa" hidden value="' . $precioBsVenta . '">
                <input type="text" id="bolivarventatrans" name="bolivarventatrans" hidden value="' . $precioBsTransfVenta . '">
                <div class="tile-stats">
                <h3 class="htres"><a href="ficha.php?id=' . $row6["id"] . '">' . $row6["nombre"] . '</a> </h3>
                <h3 class="htress" style="' . $color . '"><input type="number" style="width:35%" class="cant2" id="cant" name="cant" value="1">' . $alerta . '</h3>
                <p><small>' . '$' . $precioDolarVenta . ' - ' . $precioPesoVenta2 . ' COP - ' . $precioBsVenta2 . ' <small>BS</small>' . '</small></p>
                <br>
                <div class=""><button class="btn btn-success vender"><i class="fa fa-shopping-cart"></i></button></div>
                </div>
        </form>';
                                                }
                                                echo $tabla6;
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <style>
                    .tile-stats {
                        box-shadow: none !important;
                        border-right: 1px solid #f6f6f6
                    }

                    .btn-secondary {
                        color: #909090 !important;
                        background-color: lightgray !important;
                        border-color: lightgray !important;
                    }

                    .vender {
                        bottom: 15px;
                        margin-left: 10px;
                        position: absolute
                    }

                    .contenedoe {
                        height: 165px;
                        overflow-y: scroll;
                    }

                    .contenedoe::-webkit-scrollbar {
                        width: 7px;
                    }

                    .contenedoe::-webkit-scrollbar-thumb {
                        background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 600%);
                        border-radius: 5px;
                    }

                    .formulario {
                        max-height: 30px !important;
                    }

                    .fijo {
                        height: 350px;
                    }

                    .responsi {
                        height: 300px;
                        overflow-y: auto;
                    }

                    .responsi::-webkit-scrollbar {
                        height: 7px;
                        width: 7px;
                        background: #FFF;
                        margin-bottom: 15px;
                    }

                    .responsi::-webkit-scrollbar-thumb {
                        background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 600%);
                        border-radius: 5px;
                    }

                    .col-car-2 {
                        width: 210px;
                        position: relative;
                        min-height: 1px;
                        float: left;
                        padding-right: 10px;
                        padding-left: 10px;
                    }

                    .tile-stats {
                        height: 160px !important;
                        max-width: 100%;
                    }

                    .tile-stats .icon i {
                        margin: 0;
                        font-size: 40px;
                        line-height: 0;
                        vertical-align: bottom;
                        padding: 0;
                        color: #1ABB9C;
                    }

                    .htres {
                        margin-top: 15px !important;
                        color: dimgray !important;
                    }

                    .htres {
                        font-size: 18px;
                        margin-bottom: 10px !important;
                    }

                    .htress {
                        font-size: 15px;
                    }

                    .tile-stats .icon {
                        width: 100px;
                        height: 70px;
                        color: #BAB8B8;
                        position: absolute;
                        right: 5px;
                        top: 0px !important;
                        z-index: 1 !important;
                    }

                    .fotoProducto {
                        height: 70px;
                        width: 80px;
                    }

                    .right {
                        float: right;
                    }

                    .table {
                        width: 100%;
                        margin-bottom: 1rem;
                        color: #909090 !important;
                    }

                    .table td,
                    .table th {
                        padding: .75rem;
                        vertical-align: top;
                        border-top: none !important;
                    }

                    .table thead th {
                        vertical-align: bottom;
                        border-bottom: none !important;
                    }

                    .cant {
                        text-align: center;
                        max-width: 60px;
                        min-height: 25px;
                        border: 1px solid #909090 !important;
                        color: #909090 !important;
                        padding-left: 10px;
                        border-radius: 5px;
                        margin-right: 5px
                    }

                    .cant2 {
                        margin-right: 2%;
                        min-height: 25px;
                        border: 1px solid #909090 !important;
                        color: #909090 !important;
                        padding-left: 10px;
                        border-radius: 5px;
                    }

                    .table td,
                    .table th {
                        padding: .1rem !important;
                    }
                </style>
                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        I-SELLER - by <a href=''>Jose Ricardo Tovarg III</a>
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
        <!-- bootstrap-progressbar -->
        <script src='../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js'></script>
        <!-- Dropzone.js -->
        <script src='../vendors/dropzone/dist/min/dropzone.min.js'></script>
        <!-- iCheck -->
        <script src='../vendors/iCheck/icheck.min.js'></script>
        <!-- bootstrap-daterangepicker -->
        <script src='../vendors/moment/min/moment.min.js'></script>
        <script src='../vendors/bootstrap-daterangepicker/daterangepicker.js'></script>
        <!-- bootstrap-wysiwyg -->
        <script src='../vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js'></script>
        <script src='../vendors/jquery.hotkeys/jquery.hotkeys.js'></script>
        <script src='../vendors/google-code-prettify/src/prettify.js'></script>
        <!-- jQuery Tags Input -->
        <script src='../vendors/jquery.tagsinput/src/jquery.tagsinput.js'></script>
        <!-- Switchery -->
        <script src='../vendors/switchery/dist/switchery.min.js'></script>
        <!-- Select2 -->
        <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
        <!-- Parsley -->
        <script src='../vendors/parsleyjs/dist/parsley.min.js'></script>
        <!-- Autosize -->
        <script src='../vendors/autosize/dist/autosize.min.js'></script>
        <!-- jQuery autocomplete -->
        <script src='../vendors/devbridge-autocomplete/dist/jquery.autocomplete.min.js'></script>
        <!-- starrr -->
        <script src='../vendors/starrr/dist/starrr.js'></script>
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