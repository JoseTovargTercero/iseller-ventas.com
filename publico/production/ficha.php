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















    $query6 = $conexion->query("SELECT * FROM productos WHERE id='$idProducto'");
    if ($query6->num_rows > 0) {
        while ($row6 = $query6->fetch_assoc()) {
            $nombreP = $row6["nombre"];
        }
    }




    // Optimizado



    function ventasMensualesAnual($tipo, $anio)
    {
        global $conexion, $id;
        $resultados = [];

        // Inicializar array de enero a diciembre
        for ($mes = 1; $mes <= 12; $mes++) {
            $mesKey = str_pad($mes, 2, '0', STR_PAD_LEFT);
            $resultados[$mesKey] = ['ventas' => 0, 'ganancias' => 0];
        }

        // Buscar todas las órdenes del año y tipo
        $inicio = "$anio-01";
        $fin = "$anio-12";

        $query = "SELECT id, fecha, descontado FROM orden 
              WHERE fecha LIKE '$anio-%' AND status = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param('s', $tipo);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($fila = $res->fetch_assoc()) {
            $mes = substr($fila['fecha'], 5, 2);
            $idVenta = $fila['id'];
            $descuento = floatval($fila['descontado']);

            $venta = verificarProductoVenta($id, $idVenta);
            $ganancia = verificarProductoVentaGanancias($id, $idVenta, $tipo, $tipo == '4' ? $descuento : 0);

            if ($tipo == '4') {
                $venta = $venta - ($venta * $descuento / 100);
            }

            $resultados[$mes]['ventas'] += $venta;
            $resultados[$mes]['ganancias'] += $ganancia;
        }

        return $resultados;
    }


    $datosDetal = ventasMensualesAnual('1', date('Y'));
    $datosMayor = ventasMensualesAnual('4', date('Y'));

    // Construir arrays de ganancias para los 12 meses
    function obtenerSerieGanancia($datos)
    {
        $serie = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $mesKey = str_pad($mes, 2, '0', STR_PAD_LEFT);
            $serie[] = round($datos[$mesKey]['ganancias'], 2);
        }
        return implode(',', $serie);
    }

    $mesActual = date('m');









    function ventasSemana($semana, $dia)
    {
        global $conexion;
        $total = 0;

        $query = "SELECT total_price FROM orden WHERE semana = ? AND dia = ? AND status IN (1, 4)";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param('ss', $semana, $dia);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($fila = $result->fetch_assoc()) {
            $total += floatval($fila['total_price']);
        }

        return round($total, 1, PHP_ROUND_HALF_DOWN);
    }

    function mostrarVentaDesdeAnual($titulo, $datosAnuales, $mes)
    {
        $mesKey = str_pad($mes, 2, '0', STR_PAD_LEFT);
        $ventas = $datosAnuales[$mesKey]['ventas'] ?? 0;
        $ganancias = $datosAnuales[$mesKey]['ganancias'] ?? 0;

        $ventasFormatted = number_format($ventas, 2, '.', '.');
        $gananciasFormatted = number_format($ganancias, 2, '.', '.');

        echo <<<HTML
        <div class="animated flipInY col-lg-2">
            <div class="tile-stats" style="text-align:center">
                <div>
                    <div class="count count33">{$ventasFormatted}$</div>
                    <span class="tagGanancias">{$gananciasFormatted}$</span>
                    <h3 class="h3ini h3edit">{$titulo}</h3>
                </div>
            </div>
        </div>
        HTML;
    }

    function mostrarVenta($titulo, $funcion, $param1, $param2 = null)
    {
        $resultado = $param2 ? $funcion($param1, $param2) : $funcion($param1);
        [$monto, $ganancia] = explode('*', $resultado);
        $monto = number_format($monto, 2, '.', '.');
        $ganancia = number_format($ganancia, 2, '.', '.');

        echo <<<HTML
        <div class="animated flipInY col-lg-2">
            <div class="tile-stats" style="text-align:center">
                <div>
                    <div class="count count33">{$monto}$</div>
                    <span class="tagGanancias">{$ganancia}$</span>
                    <h3 class="h3ini h3edit">{$titulo}</h3>
                </div>
            </div>
        </div>
        HTML;
    }


?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='icon' href='images/favicon.ico' type='image/ico' />

        <title>Producto</title>
        <?php require_once('includes/headers.php'); ?>



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

                            <?php
                            mostrarVenta('Ventas al mayor del dia', 'ventasDiarias', '4');
                            mostrarVenta('Ventas al mayor de la semana', 'ventasSemanales', '4');
                            mostrarVentaDesdeAnual('Ventas al mayor del mes', $datosMayor, $mesActual);

                            mostrarVenta('Ventas al detal del dia', 'ventasDiarias', '1');
                            mostrarVenta('Ventas al detal de la semana', 'ventasSemanales', '1');
                            mostrarVentaDesdeAnual('Ventas al detal del mes', $datosDetal, $mesActual);
                            ?>

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

                            <div class="col-lg-12" style="display: none;">

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


            /*     document.addEventListener("DOMContentLoaded", function() {
                     const options1 = {
                         chart: {
                             type: 'bar',
                             height: 350
                         },
                         theme: {
                             mode: 'dark'
                         },
                         tooltip: {
                             theme: 'dark'
                         },
                         series: [{
                                 name: 'Detal (G)',
                                 data: [<?php // echo obtenerSerieGanancia($datosDetal); 
                                        ?>]
                             },
                             {
                                 name: 'Mayor (G)',
                                 data: [<?php // echo obtenerSerieGanancia($datosMayor); 
                                        ?>]
                             }
                         ],
                         xaxis: {
                             categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                             title: {
                                 text: 'Ventas_Mensuales'
                             }
                         },
                         colors: ['#1caf9a', '#a1efe3'],
                         legend: {
                             position: 'top'
                         }
                     };

                     const chart = new ApexCharts(document.querySelector("#graph"), options1);
                     chart.render();
                 });*/
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