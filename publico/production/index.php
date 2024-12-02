<?php

require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');



/////////////////////////// CONTADOR //////////////////////////////


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Inicio == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    $topnav = topnav();
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }

    $query2 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
        }
    }

    $dia = date('Y-m-d');
    $semana = date('Y-W');
    $mes = date('Y-m');
    $ano = date('Y');

    $querysas = "SELECT * FROM gastos WHERE mes='$mes'";
    $buscarAlumnossas = $conexion->query($querysas);
    if ($buscarAlumnossas->num_rows > 0) {
        while ($filaAlumnosasd = $buscarAlumnossas->fetch_assoc()) {
            $gastosMes += $filaAlumnosasd['importe'];
        }
    } else {
        $gastosMes = '0';
    }



    $query = "SELECT * FROM gastos WHERE semana='$semana'";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
            $gastosSemana += $filaAlumnos['importe'];
        }
    } else {
        $gastosSemana = '0';
    }






    $query22 = "SELECT * FROM orden WHERE modified='$dia' AND status='1' OR modified='$dia' AND status='4'";
    $buscarAlumnos22 = $conexion->query($query22);
    if ($buscarAlumnos22->num_rows > 0) {
        while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
            $VentasDiarias = $filaAlumnos22['total_price'];
            $totalVentasDiarias += $VentasDiarias;
        }
    } else {
        $totalVentasDiarias = 0;
    }

    $query222 = "SELECT * FROM orden WHERE semana='$semana' AND status='1' OR semana='$semana' AND status='4'";
    $buscarAlumnos222 = $conexion->query($query222);
    if ($buscarAlumnos222->num_rows > 0) {
        while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
            $VentasSemana = $filaAlumnos222['total_price'];
            $totalVentasSemana += $VentasSemana;
        }
    } else {
        $totalVentasSemana = 0;
    }

    $query2222 = "SELECT * FROM orden WHERE fecha='$mes' AND status='1' OR fecha='$mes' AND status='4'";
    $buscarAlumnos2222 = $conexion->query($query2222);
    if ($buscarAlumnos2222->num_rows > 0) {
        while ($filaAlumnos2222 = $buscarAlumnos2222->fetch_assoc()) {
            $VentasMes = $filaAlumnos2222['total_price'];
            $totalVentasMes += $VentasMes;
        }
    } else {
        $totalVentasMes = 0;
    }


    ///////////////////GANANCIAS DE LA SEMANA/////////////////////

    $query22222 = "SELECT * FROM orden WHERE semana='$semana' AND status='1' OR semana='$semana' AND status='4'";
    $buscarAlumnos22222 = $conexion->query($query22222);
    if ($buscarAlumnos22222->num_rows > 0) {
        while ($filaAlumnos22222 = $buscarAlumnos22222->fetch_assoc()) {
            $Venta = $filaAlumnos22222['id'];
            $VentasSe += $filaAlumnos22222['total_price'];

            $query222222 = "SELECT * FROM orden_articulos WHERE order_id='$Venta'";
            $buscarAlumnos222222 = $conexion->query($query222222);
            if ($buscarAlumnos222222->num_rows > 0) {
                while ($filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc()) {
                    $VentaPrducto = $filaAlumnos222222['product_id'];
                    $quantity14 = $filaAlumnos222222['quantity'];

                    $precioPrducto = number_format($filaAlumnos222222['precio'], '2', '.', '.');
                    $precioNeto = $precioPrducto * $quantity14;

                    $precioTotal += $precioNeto;
                }
            }
            $gananciasSe = $VentasSe - $precioTotal;
        }
    }

    ///////////////////GANANCIAS DEL DIA//////////////////////////
    $query222223 = "SELECT * FROM orden WHERE modified='$dia' AND status='1' OR modified='$dia' AND status='4'";
    $buscarAlumnos222223 = $conexion->query($query222223);
    if ($buscarAlumnos222223->num_rows > 0) {
        while ($filaAlumnos222223 = $buscarAlumnos222223->fetch_assoc()) {
            $Venta2 = $filaAlumnos222223['id'];
            $VentasSe2 += $filaAlumnos222223['total_price'];

            $query22222233 = "SELECT * FROM orden_articulos WHERE order_id='$Venta2'";
            $buscarAlumnos22222233 = $conexion->query($query22222233);
            if ($buscarAlumnos22222233->num_rows > 0) {
                while ($filaAlumnos22222233 = $buscarAlumnos22222233->fetch_assoc()) {
                    $VentaPrducto2 = $filaAlumnos22222233['product_id'];
                    $quantity = $filaAlumnos22222233['quantity'];
                    $precioPrducto2 = number_format($filaAlumnos22222233['precio'], '2', '.', '.');



                    $precioNeto2 = $precioPrducto2 * $quantity;

                    $precioTotal2 += $precioNeto2;
                }
            }
            $gananciasDi = $VentasSe2 - $precioTotal2;
        }
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////GANANCIAS DEL mes//////////////////////////
    $query2222234 = "SELECT * FROM orden WHERE fecha='$mes' AND status='1' OR fecha='$mes' AND status='4'";
    $buscarAlumnos2222234 = $conexion->query($query2222234);
    if ($buscarAlumnos2222234->num_rows > 0) {
        while ($filaAlumnos2222234 = $buscarAlumnos2222234->fetch_assoc()) {
            $Venta24 = $filaAlumnos2222234['id'];
            $VentasSe24 += $filaAlumnos2222234['total_price'];

            $query2222223344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta24'";
            $buscarAlumnos2222223344 = $conexion->query($query2222223344);
            if ($buscarAlumnos2222223344->num_rows > 0) {
                while ($filaAlumnos2222223344 = $buscarAlumnos2222223344->fetch_assoc()) {
                    $VentaPrducto24 = $filaAlumnos2222223344['product_id'];

                    $quantity154 = $filaAlumnos2222223344['quantity'];

                    $precioPrducto24 = number_format($filaAlumnos2222223344['precio'], '2', '.', '.');

                    $precioNeto24 = $precioPrducto24 * $quantity154;
                    $precioTotal24 += $precioNeto24;
                }
            }
            $gananciasMes = $VentasSe24 - $precioTotal24;
        }
    }

  
    $ventas = contar("SELECT COUNT(*) FROM orden WHERE modified='$dia' AND status='1' OR modified='$dia' AND status='4'");
    $credit = contar("SELECT COUNT(*) FROM orden WHERE modified='$dia' AND status='2'");

    $cantidadCritica = contar("SELECT COUNT(*) FROM productos WHERE stock<='$stockCritico' AND activo='0'");


    $query00000000 = "SELECT * FROM orden WHERE modified='$dia' AND status!='5' AND status!='5.2'";
    $buscarAlumnos00000000 = $conexion->query($query00000000);
    if ($buscarAlumnos00000000->num_rows > 0) {
        while ($filaAlumnos00000000 = $buscarAlumnos00000000->fetch_assoc()) {
            $ordenId = $filaAlumnos00000000['id'];

            $query000000000 = "SELECT * FROM orden_articulos WHERE order_id='$ordenId'";
            $buscarAlumnos000000000 = $conexion->query($query000000000);
            if ($buscarAlumnos000000000->num_rows > 0) {
                while ($filaAlumnos000000000 = $buscarAlumnos000000000->fetch_assoc()) {
                    $despachados += $filaAlumnos000000000['quantity'];
                }
            }
        }
    }

    if (@$despachados == "") {
        $despachados = 0;
    }
    $query00000000000 = "SELECT * FROM orden WHERE fecha='$mes' AND status='3'";
    $buscarAlumnos00000000000 = $conexion->query($query00000000000);
    if ($buscarAlumnos00000000000->num_rows > 0) {
        while ($filaAlumnos00000000000 = $buscarAlumnos00000000000->fetch_assoc()) {
            $ordenId22 = $filaAlumnos00000000000['id'];

            $query000000000000 = "SELECT * FROM orden_articulos WHERE order_id='$ordenId22'";
            $buscarAlumnos000000000000 = $conexion->query($query000000000000);
            if ($buscarAlumnos000000000000->num_rows > 0) {
                while ($filaAlumnos000000000000 = $buscarAlumnos000000000000->fetch_assoc()) {
                    $despachados226 = $filaAlumnos000000000000['quantity'];
                    $despachados22 += $despachados226;
                }
            }
        }
    }

    if ($despachados22 == "") {
        $despachados22 = 0;
    }





    $query2222222222222 = "SELECT * FROM orden WHERE fecha='$mes' AND status='3'";
    $buscarAlumnos2222222222222 = $conexion->query($query2222222222222);
    if ($buscarAlumnos2222222222222->num_rows > 0) {
        while ($filaAlumnos2222222222222 = $buscarAlumnos2222222222222->fetch_assoc()) {
            $dejado = $filaAlumnos2222222222222['total_price'];
            $totalVentasMesDejado += $dejado;
        }
    } else {
        $totalVentasMesDejado = 0;
    }





    $query2222222222222222 = "SELECT * FROM productos WHERE activo='0'";
    $buscarAlumnos2222222222222222 = $conexion->query($query2222222222222222);
    if ($buscarAlumnos2222222222222222->num_rows > 0) {
        while ($filaAlumnos2222222222222222 = $buscarAlumnos2222222222222222->fetch_assoc()) {
            $almacen += $filaAlumnos2222222222222222['stock'];
        }
    } else {
        $almacen = 0;
    }








    $query2222222222222222 = "SELECT * FROM productos WHERE activo='0'";
    $buscarAlumnos2222222222222222 = $conexion->query($query2222222222222222);
    if ($buscarAlumnos2222222222222222->num_rows > 0) {
        while ($filaAlumnos2222222222222222 = $buscarAlumnos2222222222222222->fetch_assoc()) {

            $valPro = $filaAlumnos2222222222222222['precio_compra'] / $filaAlumnos2222222222222222['cantidad_unidades'];
            $porPro = $filaAlumnos2222222222222222['porcentaje'];
            $valRealPro = ($valPro * $porPro / 100) + $valPro;
            $valRealPro =  $valRealPro;
            $valSinPor1 =  $valPro;
            $valRealProMult = $valRealPro * $filaAlumnos2222222222222222['stock'];
            $valSinPor = $valSinPor1 * $filaAlumnos2222222222222222['stock'];

            $totalVal += $valRealProMult;
            $sinPorcentaje += $valSinPor;
        }
    } else {
        $totalVal = 0;
        $sinPorcentaje = 0;
    }

    $gananciasEsperadas = $totalVal - $sinPorcentaje;




    function ventasSemana($semana)
    {
        global $conexion;
        $totalVentasMes0 = 0;

        $query0000 = "SELECT * FROM orden WHERE semana='$semana' AND status='1' OR semana='$semana' AND status='4'";
        $buscarAlumnos0000 = $conexion->query($query0000);
        if ($buscarAlumnos0000->num_rows > 0) {
            while ($filaAlumnos0000 = $buscarAlumnos0000->fetch_assoc()) {
                $VentasMes0 = $filaAlumnos0000['total_price'];
                $totalVentasMes0 += $VentasMes0;
            }
        }
        if ($totalVentasMes0 == "") {
            $totalVentasMes0 = "0";
        }
        return round($totalVentasMes0, 1, PHP_ROUND_HALF_DOWN);
    }

    function gananciasSemana($semana)
    {
        global $conexion;
        $VentasSeA = 0;
        $precioTotalA = 0;

        $queryAAAAA3 = "SELECT * FROM orden WHERE semana='$semana' AND status='1' OR semana='$semana' AND status='4'";
        $buscarAlumnosAAAAA3 = $conexion->query($queryAAAAA3);
        if ($buscarAlumnosAAAAA3->num_rows > 0) {
            while ($filaAlumnosAAAAA3 = $buscarAlumnosAAAAA3->fetch_assoc()) {
                $VentaA = $filaAlumnosAAAAA3['id'];
                $VentasSeA += $filaAlumnosAAAAA3['total_price'];
                $queryAAAAAA33 = "SELECT * FROM orden_articulos WHERE order_id='$VentaA'";
                $buscarAlumnosAAAAAA33 = $conexion->query($queryAAAAAA33);
                if ($buscarAlumnosAAAAAA33->num_rows > 0) {
                    while ($filaAlumnosAAAAAA33 = $buscarAlumnosAAAAAA33->fetch_assoc()) {
                        $VentaPrductoA = $filaAlumnosAAAAAA33['product_id'];
                        $quantityA = $filaAlumnosAAAAAA33['quantity'];

                        $precioPrductoA = number_format($filaAlumnosAAAAAA33['precio'], '2', '.', '.');
                        $precioNetoA = $precioPrductoA * $quantityA;
                        $precioTotalA += $precioNetoA;
                    }
                }
                $gananciasANO = $VentasSeA - $precioTotalA;
            }
        }
        if ($gananciasANO == "") {
            $gananciasANO = "0";
        }
        return round($gananciasANO, 2, PHP_ROUND_HALF_DOWN);
    }



    $arraSemanas = array();

    function gastosSemana($semana)
    {
        global $conexion;
        $gastosSemana = 0;

        $query = "SELECT * FROM gastos WHERE semana='$semana'";
        $buscarAlumnos = $conexion->query($query);
        if ($buscarAlumnos->num_rows > 0) {
            while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
                $gastosSemana += $filaAlumnos['importe'];
            }
        }

        return $gastosSemana;
    }




    $querysas = "SELECT * FROM orden ORDER BY semana ASC";
    $buscarAlumnossas = $conexion->query($querysas);
    if ($buscarAlumnossas->num_rows > 0) {
        while ($filaAlumnosasd = $buscarAlumnossas->fetch_assoc()) {
            $semanaC = $filaAlumnosasd['semana'];
            if (!$arraSemanas[$semanaC]) {
                $arraSemanas[$semanaC] = array(0, 0, 0);
            }
        }
    }




    foreach ($arraSemanas as $key => $value) {
        $gananciaNeta = gananciasSemana($key) - gastosSemana($key);
        $arraSemanas[$key] = array(ventasSemana($key), $gananciaNeta, gastosSemana($key));
    }





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

        <title>Inicio </title>


        <!-- Bootstrap -->
        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <!-- Font Awesome -->
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <!-- NProgress -->
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <!-- iCheck -->


        <!-- iCheck -->
        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">
        <link href='../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css' rel='stylesheet'>
        <!-- JQVMap -->
        <link href='../vendors/jqvmap/dist/jqvmap.min.css' rel='stylesheet' />
        <!-- bootstrap-daterangepicker -->
        <link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>
        <link href="js/jquerysctipttop.css" rel="stylesheet" type="text/css">
        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>




        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">

        <link href="assets/chart/css/jquerysctipttop.css" rel="stylesheet" type="text/css">
        <link href="assets/chart/plugins/morris/morris.css" rel="stylesheet" />

        <link rel="stylesheet" href="../vendors/amcharts5/examples/xy/index.css" />




        <!-- 
	<link rel="stylesheet" href="../../iseller.es/css/magnific-popup.css">

	<link rel="stylesheet" href="../../iseller.es/css/bootstrap.css">

    Magnific Popup -->

    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>

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
                <style>
                    .h3ini {
                        font-size: 16px;
                    }

                    .count {
                        font-size: 32px !important;
                    }
                </style>

                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->

                <!-- page content -->
                <div class='right_col'>




                    <h4>Inicio</h4>
                    <p style="margin-top: -10px;">Resumen y estadísticas</p>




                    <div class='row'>

                        <div class="animated flipInY col-lg-2">
                            <div class="tile-stats" style="text-align:center">

                                <div class="count count3"><?php echo number_format($totalVentasDiarias, '2', '.', '.'); ?>$</div>


                                <h3 class="h3ini h3edit">Ventas del día</h3>
                                <p><a href="listaVentas.php"><i class="fa fa-chain"></i>&nbsp;&nbsp;Ver detalles</a></p>
                            </div>
                        </div>


                        <div class="animated flipInY col-lg-2">
                            <div class="tile-stats" style="text-align:center">

                                <div class="count  count33"><?php echo number_format($totalVentasSemana, '2', '.', '.'); ?>$</div>


                                <h3 class="h3ini h3edit">Ventas de la semana</h3>
                                <p><a href="resumenSemana.php"><i class="fa fa-chain"></i>&nbsp;&nbsp;Ver detalles</a></p>
                            </div>
                        </div>

                        <div class="animated flipInY col-lg-2">
                            <div class="tile-stats" style="text-align:center">

                                <div class="count count33"><?php echo number_format($totalVentasMes, '2', '.', '.'); ?>$</div>


                                <h3 class="h3ini h3edit">Ventas del mes</h3>
                                <p><a href="resumenMes.php"><i class="fa fa-chain"></i>&nbsp;&nbsp;Ver detalles</a></p>
                            </div>
                        </div>




                        <div class="animated flipInY col-lg-2">
                            <div class="tile-stats" style="text-align:center">

                                <div class="count count33"><?php echo number_format($gananciasDi, '2', '.', '.'); ?>$</div>


                                <h3 class="h3ini h3edit">Ganancias del día</h3>
                                <p>&nbsp;</p>
                            </div>
                        </div>

                        <div class="animated flipInY col-lg-2" title="Beneficio neto: <?php echo gananciasSemana(date('Y-W')) - gastosSemana($key) ?>">
                            <div class="tile-stats" style="text-align:center">
                                <div class="count green count33"><?php echo number_format($gananciasSe, '2', '.', '.'); ?>$
                                    <span class="gastos"><span id="gastosSemanaCount"><?php echo $gastosSemana ?></span>$ <i style="font-size: 10px;margin-left: -3px;" class="line icon-arrow-down-circle"></i></span>
                                </div>
                                <h3 class="h3ini h3edit">Ganancias de la semana</h3>
                                <p>&nbsp;</p>
                            </div>
                        </div>

                        <style>
                            .gastos {
                                font-size: 12px;
                                position: absolute;
                                margin-top: 35px;
                                color: #ff8989;
                                font-weight: 900;
                            }
                        </style>
                        <div class="animated flipInY col-lg-2">
                            <div class="tile-stats" style="text-align:center">
                                <div class="count count33"><?php echo number_format($gananciasMes, '2', '.', '.'); ?>$
                                    <span class="gastos"><span id="gastosMesCount"><?php echo $gastosMes ?></span>$ <i style="font-size: 10px;margin-left: -3px;" class="line icon-arrow-down-circle"></i></span>
                                </div>
                                <h3 class="h3ini h3edit">Ganancias del mes</h3>
                                <p>&nbsp;</p>
                            </div>
                        </div>

                    </div>

                    <div class='row fh5co-block to-animate fadeInRight animated'>
                        <div class='col-lg-9 '>

                            <div class='x_panel tile '>
                                <div class='x_title' style="border-bottom: none">
                                    <h5 style="font-weight: 400;">Ventas de las ultimas semanas</h5>
                                </div>


                                <div class='x_content' style="margin-top: -20px;">
                                    <div id="chartdiv" style="height: 60vh; padding: 10px;"></div>
                                    <div id="graph36"></div>

                                </div>
                            </div>


                            <div class='x_panel tile'>
                                <div class='x_title'>
                                    <h2>Historial de cambios en las tasas</h2>

                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content'>




                                    <div class='card-box table-responsive'>

                                        <table id='datatable-responsive' class='table table-striped table-bordered' style='width:100%'>
                                            <thead>
                                                <tr class='headings'>
                                                    <th style='width:10%;' class='column-title'>Usuario</th>
                                                    <th style='width:10%;' class='column-title'>Fecha</th>
                                                    <th style='width:10%;' class='column-title'>Hora</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php

                                                $query77 = "SELECT * FROM cambios_tasas ORDER BY id DESC LIMIT 10";
                                                $buscarAlumnos77 = $conexion->query($query77);
                                                if ($buscarAlumnos77->num_rows > 0) {
                                                    while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                                        echo '
                             <tr class="even pointer">
                            <td>' . $filaAlumnos77['user'] . '</td>
                            <td>' . $filaAlumnos77['fecha'] . '</td>
                            <td>' . $filaAlumnos77['hora'] . '</td>
     
                          </tr>';
                                                    }
                                                }

                                                ?>
                                            </tbody>
                                        </table>
                                    </div>


                                </div>
                            </div>

                        </div>


                        <div class="col-lg-3 ">

                            <div class="x_panel tile">

                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Ventas</h5>
                                        <span><strong><?php echo $ventas; ?></strong> ventas</span>
                                        <p>Ventas realizadas hoy.</p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/ventas.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>


                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Créditos</h5>
                                        <span><strong><?php echo $credit; ?></strong> créditos</span>
                                        <p>créditos otorgados hoy.
                                        <p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/credito.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>


                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Despachos</h5>
                                        <span><strong><?php echo $despachados; ?></strong> Productos</span>
                                        <p>Productos despachados hoy.
                                        <p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/despacho.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>

                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Crítico</h5>
                                        <span><strong><?php echo $cantidadCritica; ?></strong> Productos</span>
                                        <p>Productos con stock crítico.
                                        <p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/critico.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>

                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Descontado</h5>
                                        <span><strong><?php echo number_format($totalVentasMesDejado, '2', '.', '.'); ?>$</strong> Dolares</span>
                                        <p>Dinero descontado del mes.
                                        <p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/descuentoBajar.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>

                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Almacen</h5>
                                        <span><strong><?php echo $almacen; ?></strong> Almacen</span>
                                        <p>Productos en el almacen.
                                        <p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/almacen.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>

                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Almacen <small>(valor)</small></h5>
                                        <span><strong><?php echo number_format($sinPorcentaje, '2', '.', '.'); ?></strong> Dolares</span>
                                        <p>Valor del stock
                                        <p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/valordelstock.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>

                                <div class="fila">
                                    <div class="col-lg-9">
                                        <h5 class="h3edit">Ganancias <small>Esp.</small></h5>
                                        <span><strong><?php echo number_format($gananciasEsperadas, '2', '.', '.'); ?></strong> Dolares</span>
                                        <p>Ganancias esperadas
                                        <p>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="icon"><br><img src='images/icono/ganancia.png' alt='BOLIVAR'>
                                        </div>
                                    </div>
                                </div>

                                <a href="../build/pdf/reporteDia.php">
                                    <div class="fila" style=" display: grid; place-items: center;">
                                        <div class="col-lg-12 fastReport" style=" height: 200px">

                                            <div style="text-align: center;">
                                                <span style="height: 40px;"><i class="line icon-paper-clip"></i></span>
                                                <h5 style="margin-top: 10px; color: #6f6f6f !important; font-size: 15px"><strong>Generar cierre del dia</strong></h5>
                                                <p style="margin-top: 5px; color: #b1b1b1">Reporte en pdf</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>

                            </div>



                        </div>











                    </div>

                </div>
                <!-- /page content -->
                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
                    </div>
                    <div class='clearfix'></div>
                </footer>

                <!-- /footer content -->
            </div>
        </div>
        <script src="../vendors/amcharts5/index.js"></script>
        <script src="../vendors/amcharts5/xy.js"></script>
        <script src="../vendors/amcharts5/themes/Animated.js"></script>
        <script src="../vendors/amcharts5/themes/Material.js"></script>

        <script>
            // Create root element
            // https://www.amcharts.com/docs/v5/getting-started/#Root_element
            var root = am5.Root.new("chartdiv");


            // Set themes
            // https://www.amcharts.com/docs/v5/concepts/themes/
            root.setThemes([
                am5themes_Animated.new(root),
                am5themes_Material.new(root)
            ]);


            // Create chart
            // https://www.amcharts.com/docs/v5/charts/xy-chart/
            var chart = root.container.children.push(am5xy.XYChart.new(root, {
                panX: true,
                panY: false,
                wheelX: "zoomX",
                wheelY: "zoomX",
                layout: root.verticalLayout
            }));


            // Add legend
            // https://www.amcharts.com/docs/v5/charts/xy-chart/legend-xy-series/
            var legend = chart.children.push(am5.Legend.new(root, {
                centerX: am5.p50,
                x: am5.p50
            }));
            chart.set("cursor", am5xy.XYCursor.new(root, {
                behavior: "zoomX"
            }));

            var data = [];


            <?php

            $elementosArray = count($arraSemanas);
            $comienzo = $elementosArray - 10;
            $start = 1;




            foreach ($arraSemanas as $key => $value) {
                if ($start > $comienzo) {
                    if ($value[2] != '0') {
                        echo '
            data.push({
                semana: "' . $key . '",
                ventas: ' . $value[0] . ',
                ganancias: ' . $value[1] . ',
                gasto: ' . $value[2] . '
               });
            ';
                    } else {
                        echo '
            data.push({
                semana: "' . $key . '",
                ventas: ' . $value[0] . ',
                ganancias: ' . $value[1] . '
               });
            ';
                    }
                }
                $start++;
            }


            ?>

            /*
            var xAxis = chart.xAxes.push(
              am5xy.CategoryAxis.new(root, {
                maxDeviation: 0.2,
                renderer: am5xy.AxisRendererX.new(root, {})
              }),
              categoryField: "category"
            );



            */


            // Create axes
            // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
            var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                categoryField: "semana",
                extraMax: 0.1,
                renderer: am5xy.AxisRendererX.new(root, {
                    cellStartLocation: 0.1,
                    cellEndLocation: 0.9
                }),
                tooltip: am5.Tooltip.new(root, {})
            }));

            xAxis.data.setAll(data);

            var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                min: 0,
                renderer: am5xy.AxisRendererY.new(root, {})
            }));

            // Create range axis data item
            var rangeDataItem = yAxis.makeDataItem({
                value: 100
            });

            // Create a range
            var range = yAxis.createAxisRange(rangeDataItem);

            var rangeDataItem = yAxis.makeDataItem({
                value: 100,
                endValue: 200
            });



            // Add series
            // https://www.amcharts.com/docs/v5/charts/xy-chart/series/
            function makeSeries(name, fieldName, stacked) {
                var series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    stacked: stacked,
                    name: name,
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: fieldName,
                    categoryXField: "semana"
                }));

                series.columns.template.setAll({
                    tooltipText: "{name}, {categoryX} : ${valueY}",
                    width: am5.percent(90),
                    tooltipY: am5.percent(10)
                });
                series.data.setAll(data);

                // Make stuff animate on load
                // https://www.amcharts.com/docs/v5/concepts/animations/
                series.appear();

                series.bullets.push(function() {
                    return am5.Bullet.new(root, {
                        locationY: 0.5,
                        sprite: am5.Label.new(root, {
                            text: "${valueY}",
                            fill: root.interfaceColors.get("alternativeText"),
                            centerY: am5.percent(50),
                            centerX: am5.percent(50),
                            populateText: true
                        })
                    });
                });

                legend.data.push(series);
            }

            makeSeries("Ventas", "ventas", false);
            makeSeries("Ganancias", "ganancias", true);
            makeSeries("Gastos", "gasto", true);

            // Make stuff animate on load
            // https://www.amcharts.com/docs/v5/concepts/animations/
            chart.appear(1000, 100);
        </script>



        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <!-- FastClick -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>

        <script src="../vendors/nprogress/nprogress.js"></script>


        <script src="../build/js/custom.min.js"></script>



    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
    ?>