<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();

    $idProducto = $_GET['id'];
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    $id = $_GET['id'];


    function verificarProductoVenta($productoID, $ventaId)
    {
        global $conexion;


        $sql = "SELECT * FROM orden_articulos WHERE product_id='$productoID' AND order_id='$ventaId'";
        $buscarAlumnos22 = $conexion->query($sql);
        if ($buscarAlumnos22->num_rows > 0) {
            while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
                return  $filaAlumnos22['precio_venta_dolar'] * $filaAlumnos22['quantity'];
            }
        } else {
            return 0;
        }
    }
    //////////////////////////// GANANCIAS /////////////////////////////////
    //////////////////////////// GANANCIAS /////////////////////////////////
    //////////////////////////// GANANCIAS /////////////////////////////////




    function verificarProductoVentaGanancias($productoID, $ventaId, $tipo, $porcentaje)
    {
        global $conexion;

        $sql = "SELECT * FROM orden_articulos WHERE product_id='$productoID' AND order_id='$ventaId'";
        $buscarAlumnos22 = $conexion->query($sql);
        if ($buscarAlumnos22->num_rows > 0) {
            while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
                if ($tipo == '4') {
                    $valuePorcentual = ($filaAlumnos22['precio_venta_dolar'] * $filaAlumnos22['quantity']) - (($filaAlumnos22['precio_venta_dolar'] * $filaAlumnos22['quantity']) * $porcentaje / 100);
                    return  $valuePorcentual - ($filaAlumnos22['precio'] * $filaAlumnos22['quantity']);
                } else {
                    return ($filaAlumnos22['precio_venta_dolar'] * $filaAlumnos22['quantity']) - ($filaAlumnos22['precio'] * $filaAlumnos22['quantity']);
                }
            }
        } else {
            return 0;
        }
    }



    $dia = date('Y-m-d');
    $semana = date('Y-W');
    $ano = date('Y');


    function ventasDiarias($tipo)
    {
        global $conexion;
        global $id;
        global $dia;
        $totalVentasDiarias = 0;
        $totalGananciasDiarias = 0;

        $query22 = "SELECT * FROM orden WHERE modified='$dia' AND status='$tipo'";
        $buscarAlumnos22 = $conexion->query($query22);
        if ($buscarAlumnos22->num_rows > 0) {
            while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
                $idVenta = $filaAlumnos22['id'];

                if ($tipo == '4') {
                    $por = $filaAlumnos22['descontado'];
                    $totalVentasDiarias += verificarProductoVenta($id, $idVenta) - (verificarProductoVenta($id, $idVenta) * $por / 100);
                    $totalGananciasDiarias += verificarProductoVentaGanancias($id, $idVenta, $tipo, $por);
                } else {
                    $totalVentasDiarias += verificarProductoVenta($id, $idVenta);
                    $totalGananciasDiarias += verificarProductoVentaGanancias($id, $idVenta, $tipo, 0);
                }
            }
        }
        return $totalVentasDiarias . '*' . $totalGananciasDiarias;
    }





    function ventasSemanales($tipo)
    {
        global $conexion;
        global $id;
        global $semana;
        $totalVentasDiarias = 0;
        $totalGananciasDiarias = 0;

        $query222 = "SELECT * FROM orden WHERE semana='$semana' AND status='$tipo'";
        $buscarAlumnos222 = $conexion->query($query222);
        if ($buscarAlumnos222->num_rows > 0) {
            while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
                $idVenta = $filaAlumnos222['id'];
                if ($tipo == '4') {
                    $por = $filaAlumnos222['descontado'];
                    $totalVentasDiarias += verificarProductoVenta($id, $idVenta) - (verificarProductoVenta($id, $idVenta) * $por / 100);
                    $totalGananciasDiarias += verificarProductoVentaGanancias($id, $idVenta, $tipo, $por);
                } else {
                    $totalVentasDiarias += verificarProductoVenta($id, $idVenta);
                    $totalGananciasDiarias += verificarProductoVentaGanancias($id, $idVenta, $tipo, 0);
                }
            }
        }

        return $totalVentasDiarias . '*' . $totalGananciasDiarias;
    }




    function ventasMensuales($tipo, $mes)
    {
        global $conexion;
        global $id;
        $totalVentasDiarias = 0;
        $totalGananciasDiarias = 0;

        $query2222 = "SELECT * FROM orden WHERE fecha='$mes' AND status='$tipo'";
        $buscarAlumnos2222 = $conexion->query($query2222);
        if ($buscarAlumnos2222->num_rows > 0) {
            while ($filaAlumnos2222 = $buscarAlumnos2222->fetch_assoc()) {
                $idVenta = $filaAlumnos2222['id'];
                if ($tipo == '4') {
                    $por = $filaAlumnos2222['descontado'];
                    $totalVentasDiarias += verificarProductoVenta($id, $idVenta) - (verificarProductoVenta($id, $idVenta) * $por / 100);
                    $totalGananciasDiarias += verificarProductoVentaGanancias($id, $idVenta, $tipo, $por);
                } else {
                    $totalVentasDiarias += verificarProductoVenta($id, $idVenta);
                    $totalGananciasDiarias += verificarProductoVentaGanancias($id, $idVenta, $tipo, 0);
                }
            }
        }
        return $totalVentasDiarias . '*' . $totalGananciasDiarias;
    }

    $query6 = $conexion->query("SELECT * FROM productos WHERE id='$idProducto'");
    if ($query6->num_rows > 0) {
        while ($row6 = $query6->fetch_assoc()) {

            $cantidadUnidad = $row6["cantidad_unidades"];
            $precioDolarCompra = $row6["precio_compra"] / $cantidadUnidad;
            $porcentaje = $row6["porcentaje"];
            $foto = $row6["foto"];
            $codeProducto = $row6["codigo"];


            $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
            $porporpor = $precioDolarCompra * $porcentaje / 100;



            $precioBsVentaAct = $precioDolarVenta * $dolarBolivar;
            $precioPesoVenta =  $precioDolarVenta * $pesoDolar;


            if ($foto == "SI") {
                $imgProducto = '<img  class=" imgProducto" alt="Avatar" src="images/stock/' . $codeProducto . '.jpg" alt="">';
            } else {
                $imgProducto = '<img  class=" imgProducto" alt="Avatar" src="images/producto_base.png" alt="">';
            }



            $idcode = $row6["codigo"];
            $idp = $row6["id"];
            $nombreP = $row6["nombre"];
            $pCompra = $row6["precio_compra"] . " $";
            $porcentajee = $row6["porcentaje"] . '%';
            $diponible = $row6["stock"];
        }
    }


    function ventasSemana($semana, $dia)
    {
        global $conexion;
        $totalVentasMes0 = 0;

        $query0000 = "SELECT * FROM orden WHERE semana='$semana' AND dia='$dia' AND status='1' OR semana='$semana' AND dia='$dia' AND status='4'";
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






    function gananciasSemana($semana, $dia)
    {
        global $conexion;
        $VentasSeA = 0;
        $precioTotalA = 0;

        $queryAAAAA3 = "SELECT * FROM orden WHERE semana='$semana' AND dia='$dia' AND status='1' OR semana='$semana' AND dia='$dia' AND status='4'";
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













    $ultimaScala = 10;
    for ($i = 1; $i <= 7; $i++) {
        if (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 1000) {
            $scala = 200;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 900) {
            $scala = 100;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 800) {
            $scala = 90;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 700) {
            $scala = 80;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 600) {
            $scala = 70;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 500) {
            $scala = 60;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 400) {
            $scala = 50;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 300) {
            $scala = 40;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 200) {
            $scala = 30;
        } elseif (explode('*', ventasMensuales('1', date('Y') . '-' . $i))[1] > 100) {
            $scala = 20;
        } else {
            $scala = 10;
        }

        if ($ultimaScala <= $scala) {
            $ultimaScala = $scala;
        }
    }

    $scala = $ultimaScala;







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

        <title>Producto</title>
        <?php require_once('includes/headers.php'); ?>

        <style>
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

            .right_col {
                min-height: 100% !important;
            }


            .right {
                font-size: 28px;
                float: right;
            }

            .color {
                background-color: whitesmoke;
                border: 1px solid lightgray;
                opacity: 0.5;
                margin-bottom: 80px;
            }

            .color:hover {
                background-color: whitesmoke;
                border: 1px solid lightgray;
                opacity: 1;
            }

            .title {
                text-align: center;
                font-size: 28px;
                margin-bottom: 18px;
            }

            .title2 {
                font-family: 'Kaushan Script', cursive;
                text-align: center;
                font-size: 62px;
            }

            .style {
                margin-left: 10%;
                height: 470px !important;
                width: 80% !important;
            }

            table.jambo_table thead {
                background: #32d7c1;
                color: #ffffff;
            }
        </style>


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
                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->

                <!-- /top navigation -->
                <div class='right_col' role='main'>
                    <div class=''>
                        <h4><?php echo $nombreP; ?></h4>
                        <p style="margin-top: -10px;">Detalles del producto</p>
                        <div class="clearfix"></div>

                        <div class='row'>

                            <div class="animated flipInY col-lg-2">
                                <div class="tile-stats" style="text-align:center">
                                    <div>
                                        <div class="count count3"><?php echo number_format(explode('*', ventasDiarias('4'))[0], '2', '.', '.'); ?>$</div>
                                        <span class="tagGanancias"><?php echo number_format(explode('*', ventasDiarias('4'))[1], '2', '.', '.'); ?>$</span>
                                        <h3 class="h3ini h3edit">Ventas al mayor del dia</h3>
                                    </div>
                                </div>
                            </div>


                            <div class="animated flipInY col-lg-2">
                                <div class="tile-stats" style="text-align:center">
                                    <div>
                                        <div class="count count33"><?php echo number_format(explode('*', ventasSemanales('4'))[0], '2', '.', '.'); ?>$</div>
                                        <span class="tagGanancias"><?php echo number_format(explode('*', ventasSemanales('4'))[1], '2', '.', '.'); ?>$</span>
                                        <h3 class="h3ini h3edit">Ventas al mayor de la semana</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="animated flipInY col-lg-2">
                                <div class="tile-stats" style="text-align:center">
                                    <div>
                                        <div class="count count33"><?php echo number_format(explode('*', ventasMensuales('4',  date('Y-m')))[0], '2', '.', '.'); ?>$</div>
                                        <span class="tagGanancias"><?php echo number_format(explode('*', ventasMensuales('4',  date('Y-m')))[1], '2', '.', '.'); ?>$</span>
                                        <h3 class="h3ini h3edit">Ventas al mayor del mes</h3>
                                    </div>
                                </div>
                            </div>




                            <div class="animated flipInY col-lg-2">
                                <div class="tile-stats" style="text-align:center">
                                    <div>
                                        <div class="count count33"><?php echo number_format(explode('*', ventasDiarias('1'))[0], '2', '.', '.'); ?>$</div>
                                        <span class="tagGanancias"><?php echo number_format(explode('*', ventasDiarias('1'))[1], '2', '.', '.'); ?>$</span>
                                        <h3 class="h3ini h3edit">Ventas al detal del dia</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="animated flipInY col-lg-2">
                                <div class="tile-stats" style="text-align:center">
                                    <div>
                                        <div class="count count33"><?php echo number_format(explode('*', ventasSemanales('1'))[0], '2', '.', '.'); ?>$</div>
                                        <span class="tagGanancias"><?php echo number_format(explode('*', ventasSemanales('1'))[1], '2', '.', '.'); ?>$</span>
                                        <h3 class="h3ini h3edit">Ventas al detal de la semana</h3>
                                    </div>
                                </div>
                            </div>

                            <div class="animated flipInY col-lg-2">
                                <div class="tile-stats" style="text-align:center">
                                    <div>
                                        <div class="count count33"><?php echo number_format(explode('*', ventasMensuales('1',  date('Y-m')))[0], '2', '.', '.'); ?>$</div>
                                        <span class="tagGanancias"><?php echo number_format(explode('*', ventasMensuales('1',  date('Y-m')))[1], '2', '.', '.'); ?>$</span>
                                        <h3 class="h3ini h3edit">Ventas al detal del mes</h3>
                                    </div>
                                </div>
                            </div>

                        </div>






                        <style>
                            .tagGanancias {
                                position: absolute;
                                margin: -50px 0 0 0;
                                color: #32d7c0;
                                right: 0;
                                margin-right: 10%;
                            }

                            .h3ini {
                                font-size: 13px !important;
                            }

                            .count {
                                font-size: 25px !important;
                            }

                            .tile-stats {
                                min-height: 120px;
                                display: grid;
                                place-items: center;
                                padding: 5px;
                            }
                        </style>

                        <div class="row">

                            <div class="col-lg-12">

                                <div class='x_panel tile '>
                                    <div class='x_title'>
                                        <h5>Historico de ganancias del producto</h5>

                                        <div class='clearfix'></div>
                                    </div>


                                    <div class='x_content' style="margin-top: -20px;">

                                        <div id="graph"></div>

                                    </div>
                                </div>
                            </div>



                            <div class="col-lg-6">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Compras</h2>

                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="card-box table-responsive">
                                                    <p class="text-muted font-13 m-b-30">
                                                        Ultimas compras del producto
                                                    </p>
                                                    <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                                                        <thead>
                                                            <tr class="headings">
                                                                <th class="column-title">#</th>
                                                                <th class="column-title">Fecha </th>
                                                                <th class="column-title">Usuario </th>
                                                                <th class="column-title">Cantidad</th>
                                                                <th class="column-title">USD</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            <?php

                                                            echo $idcode;

                                                            $tabla6 = '';
                                                            $contador = 1;



                                                            $query2 = "SELECT * FROM compras WHERE cod='$idcode' ORDER BY id DESC LIMIT 150";
                                                            $buscarAlumnos22 = $conexion->query($query2);
                                                            if ($buscarAlumnos22->num_rows > 0) {
                                                                while ($row6 = $buscarAlumnos22->fetch_assoc()) {

                                                                    $valor = $row6["precio"];
                                                                    $precioBsVenta = $valor * $tasaCambioDolar;


                                                                    $tabla6 .= '<tr class="even pointer">
                                                                                            <td class=" ">' . $contador++ . '</td>
                                                                                            <td class=" ">' . $row6["fecha"] . '</td>
                                                                                            <td class=" ">' . $row6["user"] . '</td>
                                                                                        
                                                                                            <td class=" ">' . $row6["cantidad"] . '</td>
                                                                                            
                                                                                            <td class=" ">' . round($valor, 2, PHP_ROUND_HALF_DOWN) . ' <small>$</small></td>
                                                                                            
                                                                                        </tr>';
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
                            <div class="col-md-6 col-sm-6  ">
                                <div class="x_panel ">
                                    <div class="x_title">
                                        <h2>Producto</h2>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content ">


                                        <?php




                                        $totalcomprasdia = 0;
                                        $totalcomprasMes = 0;
                                        $totalcomprasSemana = 0;

                                        $mesaso = date("Y-m");
                                        $diaso = date("Y-m-d");


                                        $query22222 = "SELECT * FROM compras WHERE semana='$semana' AND cod='$idcode'";
                                        $buscarAlumnos22222 = $conexion->query($query22222);
                                        if ($buscarAlumnos22222->num_rows > 0) {
                                            while ($filaAlumnos22222 = $buscarAlumnos22222->fetch_assoc()) {
                                                $comprasSemana = $filaAlumnos22222['cantidad'];
                                                $totalcomprasSemana += $comprasSemana;
                                            }
                                        }

                                        $query222222 = "SELECT * FROM compras WHERE mes='$mesaso' AND cod='$idcode'";
                                        $buscarAlumnos222222 = $conexion->query($query222222);
                                        if ($buscarAlumnos222222->num_rows > 0) {
                                            while ($filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc()) {
                                                $comprasMes = $filaAlumnos222222['cantidad'];
                                                $totalcomprasMes += $comprasMes;
                                            }
                                        }
                                        $query2222222 = "SELECT * FROM compras WHERE dia='$diaso' AND cod='$idcode'";
                                        $buscarAlumnos2222222 = $conexion->query($query2222222);
                                        if ($buscarAlumnos2222222->num_rows > 0) {
                                            while ($filaAlumnos2222222 = $buscarAlumnos2222222->fetch_assoc()) {
                                                $comprasdia = $filaAlumnos2222222['cantidad'];
                                                $totalcomprasdia += $comprasdia;
                                            }
                                        }











                                        ?>

                                        <div class="table-responsive col-lg-12">
                                            <h2 class="title"></h2>

                                            <table class="table table-striped  jambo_table bulk_action">
                                                <thead>
                                                    <tr class="headings">

                                                        <th style="width:10%;" class="column-title">Precio <small>(Compra)</small></th>
                                                        <th style="width:9%;" class="column-title">Porcentaje</th>
                                                        <th style="width:10%;" class="column-title">Stock</th>
                                                        <th style="width:9%;" class="column-title">Venta Usd</th>
                                                        <th style="width:9%;" class="column-title">Venta Cop</th>
                                                        <th style="width:9%;" class="column-title">Venta Bs</th>

                                                    </tr>

                                                </thead>
                                                <tbody>
                                                    <?php

                                                    $tabla6 = '';
                                                    $tabla6 .= '
                                  <tr class="even pointer">
                                                    <td class=" ">' . $pCompra . '</td>
                                                    <td class=" ">' . $porcentajee . '</td>
                                                    <td class=" ">' . $diponible . '</td>
                                                    <td class=" ">' . number_format($precioDolarVenta, '2', '.', '.') . '</td>
                                                    <td class=" ">' . number_format($precioPesoVenta, '0', '.', '.') . '</td>
                                                    <td class=" ">' . number_format($precioBsVentaAct, '2', '.', '.') . '</td>


                                                  </tr>







                               ';

                                                    echo $tabla6;





                                                    //////////////////////////////////////////////////////////////
                                                    //////////////////////////////////////////////////////////////
                                                    //////////////////////////////////////////////////////////////
                                                    //////////////////////////////////////////////////////////////
                                                    //////////////////////////////////////////////////////////////
                                                    ///////////////////GANANCIAS DE LA SEMANA/////////////////////


                                                    $query2222222 = "SELECT * FROM orden_articulos WHERE product_id='$idProducto'";
                                                    $buscarAlumnos2222222 = $conexion->query($query2222222);
                                                    if ($buscarAlumnos2222222->num_rows > 0) {
                                                        $precioPrducto = 0;
                                                        $cantidadinicial = 0;
                                                        while ($filaAlumnos2222222 = $buscarAlumnos2222222->fetch_assoc()) {
                                                            $precioPrducto1 = $filaAlumnos2222222['precio'];
                                                            $percioporcantidad = $precioPrducto1 * $filaAlumnos2222222['quantity'];
                                                            $precioPrducto += $percioporcantidad;
                                                            $cantidadinicial += $filaAlumnos2222222['quantity'];
                                                        }
                                                    }



                                                    $query222222 = "SELECT * FROM productos WHERE id='$idProducto'";
                                                    $buscarAlumnos222222 = $conexion->query($query222222);
                                                    if ($buscarAlumnos222222->num_rows > 0) {
                                                        while ($filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc()) {
                                                            $porcen = $filaAlumnos222222['porcentaje'];
                                                        }
                                                    }

                                                    $precioPrducto2 = $precioPrducto * $porcen / 100;


                                                    $precioPrducto3 =  $precioPrducto2 + $precioPrducto;

                                                    $gananciasSe = $precioPrducto3;




                                                    $hoy = date('Y-m-d');
                                                    $semana = date('Y-W');
                                                    $mes = date('Y-m');




                                                    $query2222222 = "SELECT * FROM orden WHERE modified='$hoy'";
                                                    $buscarAlumnos2222222 = $conexion->query($query2222222);
                                                    if ($buscarAlumnos2222222->num_rows > 0) {
                                                        while ($filaAlumnos2222222 = $buscarAlumnos2222222->fetch_assoc()) {
                                                            $compraDay = $filaAlumnos2222222['id'];

                                                            $quer2y2222222 = "SELECT * FROM orden_articulos WHERE 	order_id='$compraDay' AND product_id='$idProducto'";
                                                            $buscarAlumnos22222222 = $conexion->query($quer2y2222222);
                                                            if ($buscarAlumnos22222222->num_rows > 0) {
                                                                while ($filaAlumnos22222222 = $buscarAlumnos22222222->fetch_assoc()) {
                                                                    $cantidadReil += $filaAlumnos22222222['quantity'];

                                                                    $precio2 = $filaAlumnos22222222['precio'] + $porporpor;
                                                                    $precio2 = number_format($precio2, '2', '.', '.');
                                                                    $precio22 = $precio2 * $filaAlumnos22222222['quantity'];
                                                                    $precio222 += $precio22;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if ($cantidadReil == "") {
                                                        $cantidadReil = 0;
                                                    }




                                                    $query3333333 = "SELECT * FROM orden WHERE fecha='$mes'";
                                                    $buscarAlumnos3333333 = $conexion->query($query3333333);
                                                    if ($buscarAlumnos3333333->num_rows > 0) {
                                                        while ($filaAlumnos3333333 = $buscarAlumnos3333333->fetch_assoc()) {
                                                            $compraDay2 = $filaAlumnos3333333['id'];
                                                            $quer3y3333333 = "SELECT * FROM orden_articulos WHERE 	order_id='$compraDay2' AND product_id='$idProducto'";
                                                            $buscarAlumnos33333333 = $conexion->query($quer3y3333333);
                                                            if ($buscarAlumnos33333333->num_rows > 0) {
                                                                while ($filaAlumnos33333333 = $buscarAlumnos33333333->fetch_assoc()) {

                                                                    $cantidadReil2 += $filaAlumnos33333333['quantity'];

                                                                    $precio1 = $filaAlumnos33333333['precio'] + $porporpor;
                                                                    $precio1 = number_format($precio1, '2', '.', '.');
                                                                    $precio11 = $precio1 * $filaAlumnos33333333['quantity'];
                                                                    $precio111 += $precio11;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if ($cantidadReil2 == "") {
                                                        $cantidadReil2 = 0;
                                                    }



                                                    $query4444444 = "SELECT * FROM orden WHERE semana='$semana'";
                                                    $buscarAlumnos4444444 = $conexion->query($query4444444);
                                                    if ($buscarAlumnos4444444->num_rows > 0) {
                                                        while ($filaAlumnos4444444 = $buscarAlumnos4444444->fetch_assoc()) {
                                                            $compraDay4 = $filaAlumnos4444444['id'];
                                                            $quer4y4444444 = "SELECT * FROM orden_articulos WHERE 	order_id='$compraDay4' AND product_id='$idProducto'";
                                                            $buscarAlumnos44444444 = $conexion->query($quer4y4444444);
                                                            if ($buscarAlumnos44444444->num_rows > 0) {
                                                                while ($filaAlumnos44444444 = $buscarAlumnos44444444->fetch_assoc()) {

                                                                    $cantidadReil4 += $filaAlumnos44444444['quantity'];

                                                                    $precio3 = $filaAlumnos44444444['precio'] + $porporpor;
                                                                    $precio3 = number_format($precio3, '2', '.', '.');

                                                                    $precio33 = $precio3 * $filaAlumnos44444444['quantity'];
                                                                    $precio333 += $precio33;
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if ($cantidadReil4 == "") {
                                                        $cantidadReil4 = 0;
                                                    }




                                                    switch (date('m')) {
                                                        case ('01'):
                                                            $finFor = 31;
                                                            break;

                                                        case ('02'):
                                                            $finFor = 28;
                                                            break;

                                                        case ('03'):
                                                            $finFor = 31;
                                                            break;

                                                        case ('04'):
                                                            $finFor = 30;
                                                            break;

                                                        case ('05'):
                                                            $finFor = 31;
                                                            break;

                                                        case ('06'):
                                                            $finFor = 30;
                                                            break;

                                                        case ('07'):
                                                            $finFor = 31;
                                                            break;

                                                        case ('08'):
                                                            $finFor = 31;
                                                            break;

                                                        case ('09'):
                                                            $finFor = 30;
                                                            break;

                                                        case ('10'):
                                                            $finFor = 31;
                                                            break;

                                                        case ('11'):
                                                            $finFor = 30;
                                                            break;

                                                        case ('12'):
                                                            $finFor = 31;
                                                            break;
                                                    }

                                                    $hoy = date('d');







                                                    ?>


                                                </tbody>



                                            </table>


                                            <table class="table table-striped  jambo_table bulk_action">
                                                <thead>
                                                    <tr class="headings">

                                                        <th class="column-title">Periodo</th>
                                                        <th class="column-title">Despachos</th>
                                                        <th class="column-title">Valor (Referencial)</th>

                                                        <th class="column-title">Comprado</th>

                                                    </tr>

                                                </thead>

                                                <tbody>

                                                    <tr class="even pointer">
                                                        <td class=" ">Hoy</td>
                                                        <td class=" "><?php echo  $cantidadReil; ?></td>
                                                        <td class=" "><?php echo number_format($precio222, '2', '.', '.'); ?>$</td>
                                                        <td class=" "><?php echo $totalcomprasdia; ?></td>


                                                    </tr>
                                                    <tr class="even pointer">
                                                        <td class=" ">Esta Semana</td>
                                                        <td class=" "><?php echo  $cantidadReil4; ?></td>
                                                        <td class=" "><?php echo number_format($precio333, '2', '.', '.'); ?>$</td>
                                                        <td class=" "><?php echo $totalcomprasSemana; ?></td>


                                                    </tr>
                                                    <tr class="even pointer">
                                                        <td class=" ">Este mes</td>
                                                        <td class=" "><?php echo  $cantidadReil2; ?></td>
                                                        <td class=" "><?php echo number_format($precio111, '2', '.', '.'); ?>$</td>
                                                        <td class=" "><?php echo $totalcomprasMes; ?></td>

                                                    </tr>
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

        <script src="../vendors/nprogress/nprogress.js"></script>


        <script src="../build/js/custom.min.js"></script>

        <script src="chart/js/graph2.js"></script>
        <script>
            //new Date().getFullYear()


            $(function() {
                $('#graph').graphify({
                    //options: true,
                    start: 'bar',
                    obj: {
                        id: 'gggx',
                        width: '100%',
                        height: 350,
                        padding: 10,
                        xGrid: true,
                        legend: true,
                        scale: <?php echo $scala  ?>,
                        points: [
                            [
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-01'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-02'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-03'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-04'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-05'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-06'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-07'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-08'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-09'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-10'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-11'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('1', date('Y') . '-12'))[1]  ?>
                            ],

                            [
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-01'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-02'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-03'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-04'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-05'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-06'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-07'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-08'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-09'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-10'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-11'))[1]  ?>,
                                <?php echo  explode('*', ventasMensuales('4', date('Y') . '-12'))[1]  ?>

                            ]
                        ],
                        colors: ['#1caf9a', '#a1efe3'],
                        xDist: 110,
                        dataNames: ['Detal (G)', 'Mayor (G)'],
                        xName: 'Ventas_Semana',
                        animations: true,
                        design: {
                            tooltipColor: '#fff',
                            gridColor: '#f3f1f1',
                            tooltipBoxColor: '#d9534f',
                            averageLineColor: '#d9534f',
                            pointColor: '#d9534f',
                            lineStrokeColor: 'grey',
                            yLabelsColor: 'red'
                        }
                    }
                });
                $('#graph2').graphify({
                    start: 'area',
                    obj: {
                        id: 'lol',
                        legend: false,
                        showPoints: true,
                        width: '100%',
                        legendX: 450,
                        pieSize: 200,
                        shadow: true,
                        height: 400,
                        animations: true,
                        x: [2000, 2001, 2002, 2003, 2004, 2005, 2006, 2007, 2008, 2009, 2010, 2011, 2012],
                        points: [17, 33, 64, 22, 87, 45, 38, 33, 64, 22, 87, 45, 38, 33, 64, 22, 87, 45, 38],
                        xDist: 100,
                        scale: 12,
                        yDist: 35,
                        grid: false,
                        xName: 'Semana',
                        dataNames: ['Amount'],
                        design: {
                            lineColor: '#d9534f',
                            tooltipFontSize: '20px',
                            pointColor: '#d9534f',
                            barColor: '#428bca',
                            areaColor: '#f0ad4e'
                        }
                    }
                });
                var bar = new GraphBar({
                    attachTo: '#graph3',
                    special: 'combo',
                    height: 725,
                    width: '100%',
                    yDist: 60,
                    xDist: 150,
                    showPoints: false,
                    xGrid: false,
                    legend: true,
                    points: [
                        [17, 21, 51, 74, 12, 49, 33],
                        [32, 15, 75, 20, 45, 90, 52]
                    ],
                    colors: ['red', 'orange'],
                    dataNames: ['Hot', 'Warm'],
                    xName: 'Day',
                    tooltipWidth: 15,
                    design: {
                        tooltipColor: '#fff',
                        gridColor: 'black',
                        tooltipBoxColor: 'green',
                        averageLineColor: 'blue',
                    }
                });
                bar.init();
            });
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>