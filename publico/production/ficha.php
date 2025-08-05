<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();
    $bss_id = $_SESSION['bss_id'];
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

                <?php echo $menu ?>

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


                            <div class="col-md-12 col-sm-12  ">
                                <div class="x_panel ">
                                    <div class="x_content ">

                                        <div class="table-responsive col-lg-12">
                                            <?php



                                            // Fechas
                                            $hoy = date('Y-m-d');
                                            $semana = date('Y-W');
                                            $mes = date('Y-m');

                                            // Reutilizar función para obtener datos por fecha
                                            function obtenerVentasPorFecha($conexion, $idProducto, $campoFecha, $valorFecha, $sucursal)
                                            {
                                                $queryOrdenes = "SELECT id FROM orden WHERE $campoFecha='$valorFecha' AND id_sucursal=$sucursal";
                                                $ordenes = $conexion->query($queryOrdenes);

                                                $cantidad = 0;
                                                $total = 0;

                                                while ($orden = $ordenes->fetch_assoc()) {
                                                    $idOrden = $orden['id'];
                                                    $queryArticulos = "SELECT precio, quantity, precio_venta_dolar FROM orden_articulos WHERE order_id='$idOrden' AND product_id='$idProducto'";
                                                    $articulos = $conexion->query($queryArticulos);

                                                    while ($articulo = $articulos->fetch_assoc()) {
                                                        $precio = $articulo['precio_venta_dolar'];
                                                        $subtotal = $precio * $articulo['quantity'];
                                                        $cantidad += $articulo['quantity'];
                                                        $total += $subtotal;
                                                    }
                                                }

                                                return ['cantidad' => $cantidad, 'total' => number_format($total, 2, '.', ',')];
                                            }


                                            // Día final del mes
                                            $mesActual = date('m');
                                            $diasMes = cal_days_in_month(CAL_GREGORIAN, $mesActual, date('Y'));
                                            $diaHoy = date('d');



                                            $stmt = mysqli_prepare($conexion, "SELECT id, nombre FROM `sucursales` WHERE bss_id = ?");
                                            $stmt->bind_param('s', $bss_id);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            if ($result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    $sucursal = $row['id'];


                                                    $ventasDia = obtenerVentasPorFecha($conexion, $idProducto, 'modified', $hoy, $sucursal);
                                                    $ventasMes = obtenerVentasPorFecha($conexion, $idProducto, 'fecha', $mes, $sucursal);
                                                    $ventasSemana = obtenerVentasPorFecha($conexion, $idProducto, 'semana', $semana, $sucursal);



                                                    echo <<<HTML
                                                        <div class="text-center">
                                                            <h2 class="">{$row['nombre']}</h2>
                                                        </div>
                                                      <table class="table table-striped  jambo_table bulk_action">
                                                        <thead>
                                                            <tr class="headings">

                                                                <th class="column-title">Periodo</th>
                                                                <th class="column-title">Despachos</th>
                                                                <th class="column-title">Valor (Referencial)</th>
                                                            </tr>

                                                        </thead>

                                                        <tbody>
                                                            <tr class="even pointer">
                                                                <td>Hoy</td>
                                                                <td>{$ventasDia['cantidad']}</td>
                                                                <td>{$ventasDia['total']}$</td>
                                                            </tr>
                                                            <tr class="even pointer">
                                                                <td>Esta Semana</td>
                                                                <td>{$ventasSemana['cantidad']}</td>
                                                                <td>{$ventasSemana['total']}$</td>
                                                            </tr>
                                                            <tr class="even pointer">
                                                                <td>Este Mes</td>
                                                                <td>{$ventasMes['cantidad']}</td>
                                                                <td>{$ventasMes['total']}$</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    HTML;
                                                }
                                            }
                                            $stmt->close();



                                            ?>







                                            <br>
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
        <!-- FastClick -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>

        <script src="../vendors/nprogress/nprogress.js"></script>


        <script src="../build/js/custom.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

        <script>
            //new Date().getFullYear()



            document.addEventListener("DOMContentLoaded", function() {
                // === PRIMER GRÁFICO ===
                const options1 = {
                    chart: {
                        type: 'bar',
                        height: 350
                    },
                    theme: {
                        mode: 'dark' // 👈 Aplica texto y ejes oscuros
                    },
                    tooltip: {
                        theme: 'dark'
                    },
                    series: [{
                            name: 'Detal (G)',
                            data: [
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
                            ]
                        },
                        {
                            name: 'Mayor (G)',
                            data: [
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
                        }
                    ],
                    xaxis: {
                        categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                        title: {
                            text: 'Ventas_Semana'
                        }
                    },
                    colors: ['#1caf9a', '#a1efe3'],
                    legend: {
                        position: 'top'
                    }
                };

                const chart1 = new ApexCharts(document.querySelector("#graph"), options1);
                chart1.render();

                // === SEGUNDO GRÁFICO ===
                const options2 = {
                    chart: {
                        type: 'area',
                        height: 400
                    },
                    series: [{
                        name: 'Amount',
                        data: [17, 33, 64, 22, 87, 45, 38, 33, 64, 22, 87, 45, 38, 33, 64, 22, 87, 45, 38]
                    }],
                    xaxis: {
                        categories: [2000, 2001, 2002, 2003, 2004, 2005, 2006, 2007, 2008, 2009, 2010, 2011, 2012],
                        title: {
                            text: 'Semana'
                        }
                    },
                    colors: ['#f0ad4e'],
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        colors: ['#d9534f']
                    },
                    markers: {
                        size: 5,
                        colors: ['#d9534f']
                    },
                    tooltip: {
                        style: {
                            fontSize: '20px'
                        }
                    }
                };

            });
        </script>

        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>