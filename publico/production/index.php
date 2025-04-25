<?php

require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');




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



    /* CALCULAR VALOR DEL STOCK */

    // Inicializamos los acumuladores
    $valor_stock_con_ganancia = 0;
    $valor_stock_sin_ganancia = 0;

    // Consulta de productos inactivos
    $stmt = mysqli_prepare($conexion, "SELECT precio_compra, cantidad_unidades, porcentaje, stock FROM productos WHERE activo='0'");
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($producto = $resultado->fetch_assoc()) {
        $precio_compra = (float) $producto['precio_compra'];
        $unidades = (float) $producto['cantidad_unidades'];
        $stock = (float) $producto['stock'];
        $porcentaje = (float) $producto['porcentaje'];

        // Evitar división por cero
        if ($unidades <= 0) continue;

        $valor_unitario_compra = $precio_compra / $unidades;
        $valor_unitario_venta = $valor_unitario_compra * (1 + $porcentaje / 100);

        $valor_stock_con_ganancia += $valor_unitario_venta * $stock;
        $valor_stock_sin_ganancia += $valor_unitario_compra * $stock;
    }

    // Calculamos la ganancia esperada
    $gananciasEsperadas = $valor_stock_con_ganancia - $valor_stock_sin_ganancia;


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



    function obtenerImportes($conexion, $tabla, $columna, $valor)
    {
        $total = 0;
        $sql = "SELECT importe FROM $tabla WHERE $columna='$valor'";
        $res = $conexion->query($sql);
        while ($row = $res->fetch_assoc()) {
            $total += $row['importe'];
        }
        return $total;
    }

    $gastosMes = obtenerImportes($conexion, 'gastos', 'mes', $mes);
    $gastosSemana = obtenerImportes($conexion, 'gastos', 'semana', $semana);



    // Función para obtener total_price de órdenes
    function obtenerVentas($conexion, $columna, $valor, $extraCond = "")
    {
        $total = 0;
        $sql = "SELECT total_price FROM orden WHERE ($columna='$valor' AND (status='1' OR status='4')) $extraCond";
        $res = $conexion->query($sql);
        while ($row = $res->fetch_assoc()) {
            $total += $row['total_price'];
        }
        return $total;
    }

    $totalVentasDiarias = obtenerVentas($conexion, 'modified', $dia);
    $totalVentasSemana = obtenerVentas($conexion, 'semana', $semana);
    $totalVentasMes = obtenerVentas($conexion, 'fecha', $mes);



    // Función para calcular ganancias
    function calcularGanancias($conexion, $columna, $valor)
    {
        $ventas = 0;
        $costos = 0;
        $sql = "SELECT id, total_price FROM orden WHERE ($columna='$valor' AND (status='1' OR status='4'))";
        $res = $conexion->query($sql);
        while ($row = $res->fetch_assoc()) {
            $ventas += $row['total_price'];
            $orden_id = $row['id'];
            $articulos = $conexion->query("SELECT precio, quantity FROM orden_articulos WHERE order_id='$orden_id'");
            while ($articulo = $articulos->fetch_assoc()) {
                $costos += $articulo['precio'] * $articulo['quantity'];
            }
        }
        return $ventas - $costos;
    }


    $gananciasDi = calcularGanancias($conexion, 'modified', $dia);
    $gananciasSe = calcularGanancias($conexion, 'semana', $semana);
    $gananciasMes = calcularGanancias($conexion, 'fecha', $mes);


    // Contadores
    $ventas = contar("SELECT COUNT(*) FROM orden WHERE (modified='$dia' AND (status='1' OR status='4'))");
    $credit = contar("SELECT COUNT(*) FROM orden WHERE modified='$dia' AND status='2'");
    $cantidadCritica = contar("SELECT COUNT(*) FROM productos WHERE stock<='$stockCritico' AND activo='0'");



    // Productos despachados hoy
    $despachados = 0;
    $res = $conexion->query("SELECT id FROM orden WHERE modified='$dia' AND status NOT IN ('5', '5.2')");
    while ($row = $res->fetch_assoc()) {
        $articulos = $conexion->query("SELECT quantity FROM orden_articulos WHERE order_id='{$row['id']}'");
        while ($articulo = $articulos->fetch_assoc()) {
            $despachados += $articulo['quantity'];
        }
    }
    $despachados = $despachados ?: 0;


    // Productos despachados en el mes (status 3)
    $despachados22 = 0;
    $res = $conexion->query("SELECT id FROM orden WHERE fecha='$mes' AND status='3'");
    while ($row = $res->fetch_assoc()) {
        $articulos = $conexion->query("SELECT quantity FROM orden_articulos WHERE order_id='{$row['id']}'");
        while ($articulo = $articulos->fetch_assoc()) {
            $despachados22 += $articulo['quantity'];
        }
    }
    $despachados22 = $despachados22 ?: 0;


    // Ventas confirmadas con status 3 del mes
    $totalVentasMesDejado = 0;
    $res = $conexion->query("SELECT total_price FROM orden WHERE fecha='$mes' AND status='3'");
    while ($row = $res->fetch_assoc()) {
        $totalVentasMesDejado += $row['total_price'];
    }


    // Stock actual en almacén
    $almacen = 0;
    $res = $conexion->query("SELECT stock FROM productos WHERE activo='0'");
    while ($row = $res->fetch_assoc()) {
        $almacen += $row['stock'];
    }
    $almacen = $almacen ?: 0;



    // Funciones adicionales para estadísticas semanales
    function ventasSemana($semana)
    {
        global $conexion;
        return round(obtenerVentas($conexion, 'semana', $semana), 1, PHP_ROUND_HALF_DOWN);
    }

    function gananciasSemana($semana)
    {
        global $conexion;
        $ganancia = calcularGanancias($conexion, 'semana', $semana);
        return round($ganancia ?: 0, 2, PHP_ROUND_HALF_DOWN);
    }

    function gastosSemana($semana)
    {
        global $conexion;
        return obtenerImportes($conexion, 'gastos', 'semana', $semana);
    }

    // Análisis por semanas
    $arraSemanas = [];
    $res = $conexion->query("SELECT DISTINCT semana FROM orden ORDER BY semana ASC");
    while ($row = $res->fetch_assoc()) {
        $semanaC = $row['semana'];
        $ventas = ventasSemana($semanaC);
        $gastos = gastosSemana($semanaC);
        $gananciaNeta = gananciasSemana($semanaC) - $gastos;
        $arraSemanas[$semanaC] = [$ventas, $gananciaNeta, $gastos];
    }



?>

    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Inicio </title>
        <?php require_once('includes/headers.php'); ?>
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

                                                $query = "SELECT * FROM cambios_tasas ORDER BY id DESC LIMIT 10";
                                                $buscar = $conexion->query($query);
                                                if ($buscar->num_rows > 0) {
                                                    while ($row = $buscar->fetch_assoc()) {
                                                        echo '
                                                            <tr class="even pointer">
                                                            <td>' . $row['user'] . '</td>
                                                            <td>' . $row['fecha'] . '</td>
                                                            <td>' . $row['hora'] . '</td>
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
                                        <span><strong><?php echo number_format($valor_stock_sin_ganancia, '2', '.', '.'); ?></strong> Dolares</span>
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