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
    $idProducto = $_GET['id'];
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




















    $dia = date('Y-m-d');
    $semana = date('Y-W');
    $mes = date('Y-m');
    $ano = date('Y');

    $query22 = "SELECT * FROM orden WHERE modified='$dia' AND status='1'";
    $buscarAlumnos22 = $conexion->query($query22);
    if ($buscarAlumnos22->num_rows > 0) {
        while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
            $VentasDiarias = $filaAlumnos22['total_price'];
            $totalVentasDiarias += $VentasDiarias;
        }
    }

    $query222 = "SELECT * FROM orden WHERE semana='$semana' AND status='1'";
    $buscarAlumnos222 = $conexion->query($query222);
    if ($buscarAlumnos222->num_rows > 0) {
        while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
            $VentasSemana = $filaAlumnos222['total_price'];
            $totalVentasSemana += $VentasSemana;
        }
    }

    $query2222 = "SELECT * FROM orden WHERE fecha='$mes' AND status='1'";
    $buscarAlumnos2222 = $conexion->query($query2222);
    if ($buscarAlumnos2222->num_rows > 0) {
        while ($filaAlumnos2222 = $buscarAlumnos2222->fetch_assoc()) {
            $VentasMes = $filaAlumnos2222['total_price'];
            $totalVentasMes += $VentasMes;
        }
    }






?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Ficha Producto</title>

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

                    .gray {
                        color: rgba(52, 73, 94, 0.94);
                        font-size: 24px;


                    }

                    .fav {
                        color: #1ABB9C;
                        font-size: 24px;


                    }

                    .nofav {
                        color: lightgray;
                        font-size: 24px;


                    }
                </style>





                <!-- page content -->
                <div class="right_col" role="main">
                    <div class="">

                        <div class="clearfix"></div>
                        <div class="row" style="display: block;">
                            <div class="col-md-12 col-sm-12  ">
                                <div class="x_panel ">
                                    <div class="x_title">
                                        <h2>Productos</h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                            </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>

                                    <div id="ex-slider" data-ride="carousel" class="carousel slide pbl">
                                        <ol class="carousel-indicators">
                                            <li data-target="#ex-slider" data-slide-to="0" class="active"></li>
                                            <li data-target="#ex-slider" data-slide-to="1"></li>

                                        </ol>
                                        <div class="carousel-inner">
                                            <div class="item active">
                                                <h1 class="empresa"><strong><?php echo $nombreEmpresa; ?></strong></h1>

                                            </div>



                                            <?php




                                            $query6 = $conexion->query("SELECT * FROM productos WHERE activo='0'");
                                            if ($query6->num_rows > 0) {
                                                while ($row6 = $query6->fetch_assoc()) {





                                                    $cantidadUnidad = $row6["cantidad_unidades"];
                                                    $precioDolarCompra = $row6["precio_compra"] / $cantidadUnidad;
                                                    $porcentaje = $row6["porcentaje"];
                                                    $foto = $row6["foto"];
                                                    $codeProducto = $row6["codigo"];


                                                    $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
                                                    $precioPesoVenta = $precioDolarVenta * $PesoDolar;
                                                    $precioBsVenta = $precioDolarVenta * $dolarBolivar;



                                                    $idp = $row6["codigo"];
                                                    $nombreP = $row6["nombre"];
                                                    $pCompra = $row6["precio_compra"] . " $";
                                                    $porcentajee = $row6["porcentaje"] . '%';
                                                    $diponible = $row6["stock"];

                                                    if ($foto == "SI") {
                                                        $imgProducto = '<img  class="avatar style" alt="Avatar" src="images/stock/' . $codeProducto . '.jpg" alt="">';
                                                    } else {
                                                        $imgProducto = '<img  class="avatar style" alt="Avatar" src="images/producto_base.png" alt="">';
                                                    }
                                                    echo ' <div class="item ">
           
           
           <h1 class="title">' . $nombreP . '</h1>
                    <div class="col-lg-6">
                        ' . $imgProducto . '
                    </div>
                    ';



                                                    $tabla6 = '';
                                                    $tabla6 .= '

                        <div class="col-lg-6">   
                           <br>
                             <h1 class="title2"><strong>' . round($precioDolarVenta, 2, PHP_ROUND_HALF_DOWN) . '</strong> <small>$</small> </h1>
                             <h1 class="title2"><strong>' . number_format($precioPesoVenta, '0', ',', '.') . '</strong> <small>Pesos (COP)</small> </h1>
                             <h1 class="title2"><strong>' . number_format($precioBsVenta, '0', ',', '.') . '</strong> <small>BS</small></h1>
         </div></div>
       ';
                                                    echo $tabla6;
                                                }
                                            }
                                            ?>








                                        </div>
                                    </div>






















                                    <style>
                                        .empresa {
                                            margin-top: 25px;
                                            height: 550px;
                                            font-family: 'Kaushan Script', cursive;
                                            text-align: center;
                                            font-size: 62px;
                                        }

                                        .title {
                                            font-family: 'Kaushan Script', cursive;
                                            text-align: center;
                                            font-size: 42px;
                                            margin-bottom: 18px;


                                        }

                                        .title2 {
                                            margin-top: 35px;
                                            font-family: 'Kaushan Script', cursive;
                                            text-align: center;
                                            font-size: 62px;

                                        }

                                        .style {
                                            height: 470px !important;
                                            width: 80% !important;
                                        }

                                        .style2 {

                                            margin-left: 10%;
                                            height: 470px !important;
                                            width: 80% !important;
                                        }
                                    </style>



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


        <script src="j_carrucel/jquery-2.1.0.js"></script>
        <script src="j_carrucel/bootstrap.min.js"></script>
        <!-- FastClick -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <!-- NProgress -->
        <script src="../vendors/nprogress/nprogress.js"></script>
        <!-- iCheck -->
        <script src="../vendors/iCheck/icheck.min.js"></script>

        <!-- Custom Theme Scripts -->
        <script src="../build/js/custom.min.js"></script>
    </body>
    <style>
        .carousel {
            position: relative
        }

        .carousel-inner {
            position: relative;
            overflow: hidden;
            width: 100%
        }

        .carousel-inner>.item {
            display: none;
            position: relative;
            -webkit-transition: .6s ease-in-out left;
            transition: .6s ease-in-out left
        }

        .carousel-inner>.item>img,
        .carousel-inner>.item>a>img {
            line-height: 1
        }

        .carousel-inner>.active,
        .carousel-inner>.next,
        .carousel-inner>.prev {
            display: block
        }

        .carousel-inner>.active {
            left: 0
        }

        .carousel-inner>.next,
        .carousel-inner>.prev {
            position: absolute;
            top: 0;
            width: 100%
        }

        .carousel-inner>.next {
            left: 100%
        }

        .carousel-inner>.prev {
            left: -100%
        }

        .carousel-inner>.next.left,
        .carousel-inner>.prev.right {
            left: 0
        }

        .carousel-inner>.active.left {
            left: -100%
        }

        .carousel-inner>.active.right {
            left: 100%
        }

        .carousel-control {
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 15%;
            opacity: .5;
            filter: alpha(opacity=50);
            font-size: 20px;
            color: #fff;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0, 0, 0, .6)
        }

        .carousel-control.left {
            background-image: -webkit-linear-gradient(left, color-stop(rgba(0, 0, 0, .5) 0), color-stop(rgba(0, 0, 0, .0001) 100%));
            background-image: linear-gradient(to right, rgba(0, 0, 0, .5) 0, rgba(0, 0, 0, .0001) 100%);
            background-repeat: repeat-x;
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#80000000', endColorstr='#b1b1b100', GradientType=1)
        }

        .carousel-control.right {
            left: auto;
            right: 0;
            background-image: -webkit-linear-gradient(left, color-stop(rgba(0, 0, 0, .0001) 0), color-stop(rgba(0, 0, 0, .5) 100%));
            background-image: linear-gradient(to right, rgba(0, 0, 0, .0001) 0, rgba(0, 0, 0, .5) 100%);
            background-repeat: repeat-x;
            filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#b1b1b100', endColorstr='#80000000', GradientType=1)
        }

        .carousel-control:hover,
        .carousel-control:focus {
            outline: 0;
            color: #fff;
            text-decoration: none;
            opacity: .9;
            filter: alpha(opacity=90)
        }

        .carousel-control .icon-prev,
        .carousel-control .icon-next,
        .carousel-control .glyphicon-chevron-left,
        .carousel-control .glyphicon-chevron-right {
            position: absolute;
            top: 50%;
            z-index: 5;
            display: inline-block
        }

        .carousel-control .icon-prev,
        .carousel-control .glyphicon-chevron-left {
            left: 50%
        }

        .carousel-control .icon-next,
        .carousel-control .glyphicon-chevron-right {
            right: 50%
        }

        .carousel-control .icon-prev,
        .carousel-control .icon-next {
            width: 20px;
            height: 20px;
            margin-top: -10px;
            margin-left: -10px;
            font-family: serif
        }

        .carousel-control .icon-prev:before {
            content: '\2039'
        }

        .carousel-control .icon-next:before {
            content: '\203a'
        }

        .carousel-indicators {
            position: absolute;
            bottom: 10px;
            left: 50%;
            z-index: 15;
            width: 60%;
            margin-left: -30%;
            padding-left: 0;
            list-style: none;
            text-align: center
        }

        .carousel-indicators li {
            display: inline-block;
            width: 10px;
            height: 10px;
            margin: 1px;
            text-indent: -999px;
            border: 1px solid #fff;
            border-radius: 10px;
            cursor: pointer;
            background-color: #b1b1b1;
            background-color: rgba(0, 0, 0, 0)
        }

        .carousel-indicators .active {
            margin: 0;
            width: 12px;
            height: 12px;
            background-color: #fff
        }

        .carousel-caption {
            position: absolute;
            left: 15%;
            right: 15%;
            bottom: 20px;
            z-index: 10;
            padding-top: 20px;
            padding-bottom: 20px;
            color: #fff;
            text-align: center;
            text-shadow: 0 1px 2px rgba(0, 0, 0, .6)
        }

        .carousel-caption .btn {
            text-shadow: none
        }

        @media screen and (min-width:768px) {

            .carousel-control .glyphicon-chevron-left,
            .carousel-control .glyphicon-chevron-right,
            .carousel-control .icon-prev,
            .carousel-control .icon-next {
                width: 30px;
                height: 30px;
                margin-top: -15px;
                margin-left: -15px;
                font-size: 30px
            }

            .carousel-caption {
                left: 20%;
                right: 20%;
                padding-bottom: 30px
            }

            .carousel-indicators {
                bottom: 20px
            }
        }
    </style>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>