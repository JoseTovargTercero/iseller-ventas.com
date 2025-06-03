<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();

    $fecha = date('Y-m');
    $hayunError = "NO";

    if (isset($_GET['fechaSolic'])) {
        $fecha = date('Y') . '-' . $_GET['fechaSolic'];
    }

    $mes = explode('-', $fecha)[1];
    $mes =  (strlen($mes) == 1 ? $mes = '0' . $mes : $mes);


    $total = 0;
    $totalVentas = 0;

    $tipoFiltroColumna = 'fecha'; // Por dia
    $text_vista = 'Ventas del mes';

    // Ventas status = 1
    $query = "SELECT total_price FROM orden WHERE $tipoFiltroColumna = '$fecha' AND status = '1'";
    $result = $conexion->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $totalVentas++;
            $total += $row['total_price'];
        }
    }

    // Ventas status = 4
    $total2 = 0;
    $totalVentas2 = 0;

    $query = "SELECT total_price_bs FROM orden WHERE $tipoFiltroColumna = '$fecha' AND status = '4'";
    $result = $conexion->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $totalVentas2++;
            $total2 += $row['total_price_bs'];
        }
    }

    // OBTIENE LAS GANANCIAS POR TIPO DE MONEDA
    function returnGanancias($tipo)
    {
        global $conexion;
        global $fecha;
        global $tipoFiltroColumna;
        $ganancias = 0;

        $sqlGanancias = "SELECT * FROM orden WHERE $tipoFiltroColumna='$fecha' AND status='$tipo'";
        $search = $conexion->query($sqlGanancias);
        if ($search->num_rows > 0) {
            while ($row = $search->fetch_assoc()) {
                $idOrder = $row['id'];
                $descontado = $row['descontado'];

                $query0000003344 = "SELECT * FROM orden_articulos WHERE order_id='$idOrder'";
                $buscarAlumnos0000003344 = $conexion->query($query0000003344);
                if ($buscarAlumnos0000003344->num_rows > 0) {
                    while ($filaAlumnos0000003344 = $buscarAlumnos0000003344->fetch_assoc()) {

                        $precioCompra = $filaAlumnos0000003344['precio'] * $filaAlumnos0000003344['quantity'];
                        $precioVenta = $filaAlumnos0000003344['precio_venta_dolar'] * $filaAlumnos0000003344['quantity'];


                        if ($tipo == '4') {
                            $precioVenta = $precioVenta - ($precioVenta * $descontado / 100);
                            $ganancias += $precioVenta - $precioCompra;
                        } else {
                            $ganancias += $precioVenta - $precioCompra;
                        }
                    }
                }
            }
        }
        return $ganancias;
    }


    // TOTAL POR TIPO DE PAGO
    function obtenerTotalPorTipoPago($conexion, $fecha, $tipoPago, $campoTotal)
    {
        global $tipoFiltroColumna;
        $sql = "
            SELECT $campoTotal 
            FROM orden 
            WHERE $tipoFiltroColumna = ? 
            AND tipoPago = ? 
            AND (status = '1' OR status = '4')
        ";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $fecha, $tipoPago);
        $stmt->execute();
        $result = $stmt->get_result();

        $total = 0;
        while ($row = $result->fetch_assoc()) {
            $total += $row[$campoTotal];
        }

        return $total;
    }

    // Uso de la función para cada tipo de pago
    $total_Punto = obtenerTotalPorTipoPago($conexion, $fecha, 1, 'total_price_bs'); // Punto
    $total_Pmovil = obtenerTotalPorTipoPago($conexion, $fecha, 2, 'total_price_bs'); // Pago movil
    $total_Transferencia = obtenerTotalPorTipoPago($conexion, $fecha, 3, 'total_price_bs'); // Transferencia
    $total_Efectivo = obtenerTotalPorTipoPago($conexion, $fecha, 4, 'total_price_bs'); // efectivo
    $total_Dolares = obtenerTotalPorTipoPago($conexion, $fecha, 5, 'total_price');    // Dólares
    $total_pesos = obtenerTotalPorTipoPago($conexion, $fecha, 6, 'total_price_cop'); // Pesos
    $total_Biopago = obtenerTotalPorTipoPago($conexion, $fecha, 7, 'total_price_bs'); // Biopago
    // TOTAL POR TIPO DE PAGO


    function obtenerVentasTotalesPorStatus($conexion, $fecha, $statuses = ['1', '3'])
    {
        global $tipoFiltroColumna;
        $statusList = "'" . implode("','", $statuses) . "'";
        $query = "SELECT total_price, total_price_bs, total_price_cop 
            FROM orden 
            WHERE $tipoFiltroColumna = '$fecha' 
            AND status IN ($statusList)";

        $result = $conexion->query($query);


        $total = 0;
        while ($row = $result->fetch_assoc()) {
            $total += $row['total_price'];
        }

        return $total;
    }

    function calcularGananciaPorTipoPago($conexion, $fecha, $tipoPago, $campoPrecioArticulo = 'precio', $campoTotalOrden = 'total_price')
    {
        global $tipoFiltroColumna;
        $query = "
            SELECT id, $campoTotalOrden AS monto 
            FROM orden 
            WHERE $tipoFiltroColumna = '$fecha' 
            AND status IN ('1', '4') 
            AND tipoPago = '$tipoPago'
        ";

        $result = $conexion->query($query);
        $totalVentas = 0;
        $totalCosto = 0;

        while ($orden = $result->fetch_assoc()) {
            $totalVentas += $orden['monto'];
            $ordenId = $orden['id'];

            $queryArt = "
                SELECT quantity, $campoPrecioArticulo AS precio_unitario 
                FROM orden_articulos 
                WHERE order_id = '$ordenId'
            ";
            $resultArt = $conexion->query($queryArt);
            while ($art = $resultArt->fetch_assoc()) {
                $totalCosto += $art['precio_unitario'] * $art['quantity'];
            }
        }

        return $totalVentas - $totalCosto;
    }

    // Total de ventas por tipo de despacho
    $total_detal = obtenerVentasTotalesPorStatus($conexion, $fecha);
    $total_mayor = obtenerVentasTotalesPorStatus($conexion, $fecha, [4]);



    function calcularGananciaPorMoneda($conexion, $fecha, $tipoPagos, $campoMontoOrden, $campoPrecioArticulo)
    {
        $ganancia = 0;
        $totalVentas = 0;
        $costoTotal = 0;
        global $tipoFiltroColumna;

        // Preparar lista de tipoPago para la consulta
        $tipos = implode("','", $tipoPagos);

        $query = "
            SELECT id, $campoMontoOrden as monto 
            FROM orden 
            WHERE $tipoFiltroColumna = ? 
            AND status IN ('1', '4') 
            AND tipoPago IN ('$tipos')
        ";

        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $fecha);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($orden = $result->fetch_assoc()) {
            $ordenId = $orden['id'];
            $totalVentas += $orden['monto'];

            $queryArticulos = "SELECT quantity, $campoPrecioArticulo as precio_unitario FROM orden_articulos WHERE order_id = ?";
            $stmtArt = $conexion->prepare($queryArticulos);
            $stmtArt->bind_param("i", $ordenId);
            $stmtArt->execute();
            $resArt = $stmtArt->get_result();

            while ($articulo = $resArt->fetch_assoc()) {
                $costoTotal += $articulo['precio_unitario'] * $articulo['quantity'];
            }
        }

        $ganancia = $totalVentas - $costoTotal;
        return $ganancia;
    }

    // Calcular ganancias
    $gananciasBolivar = calcularGananciaPorMoneda(
        $conexion,
        $fecha,
        ['1', '2', '3', '4', '7'],
        'total_price_bs',
        'bolivar'
    );

    $gananciasPeso = calcularGananciaPorMoneda(
        $conexion,
        $fecha,
        ['6'],
        'total_price_cop',
        'peso'
    );

    $gananciasDolares = calcularGananciaPorMoneda(
        $conexion,
        $fecha,
        ['5'],
        'total_price',
        'precio'
    );



    /**
     * Formatea un array de montos a un total con dos decimales, separando miles con puntos y decimales con coma.
     *
     * @param array $montos Array de montos a totalizar.
     * @return string Total formateado.
     */

    function totalizar_formatear($montos)
    {
        $total = 0;
        foreach ($montos as $monto) {
            $total += $monto;
        }
        return number_format($total, 2, '.', ',');
    }


?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Lista de Ventas</title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">

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

                <!-- page content -->
                <div class='right_col' role='main'>

                    <div class=''>

                        <h4>Ventas</h4>
                        <p style="margin-top: -10px;"><?php echo $text_vista ?></p>

                        <div class='clearfix'></div>

                        <div class='row   fadeInUp animated'>

                            <div class='col-lg-12'>
                                <div class='x_panel'>

                                    <div class='d-flex justify-content-between'>
                                        <div style="display: flex; flex-direction: column;">
                                            <h2 class="m-0">Listado de ventas</h2>
                                            <small class="text-muted">Los créditos otorgados se visualizarán en la lista; <br>sin embargo, no serán considerados en la totalización hasta su cancelación.</small>
                                        </div>
                                        <div class="p-2">

                                            <?php

                                            $meses = [
                                                '01' => 'ENERO',
                                                '02' => 'FEBRERO',
                                                '03' => 'MARZO',
                                                '04' => 'ABRIL',
                                                '05' => 'MAYO',
                                                '06' => 'JUNIO',
                                                '07' => 'JULIO',
                                                '08' => 'AGOSTO',
                                                '09' => 'SEPTIEMBRE',
                                                '10' => 'OCTUBRE',
                                                '11' => 'NOVIEMBRE',
                                                '12' => 'DICIEMBRE'
                                            ];

                                            ?>


                                            <select class="form-control form-control-sm" name="fechaSolic" id="fechaSolic">
                                                <?php
                                                foreach ($meses as $key => $item) {
                                                    echo "<option value='" . $key . "'>" . $item . "</option>";
                                                }
                                                ?>

                                            </select>


                                        </div>
                                    </div>
                                    <div class='x_content '>
                                        <div class='card-box'>
                                            <table id="datatable-responsive" class="table table-bordered" style="width:100%">
                                                <thead>
                                                    <tr class="headings">
                                                        <th>#</th>
                                                        <th>T</th>
                                                        <th>Pago por</th>
                                                        <th>Usuario</th>
                                                        <th>Fecha</th>
                                                        <th>Monto</th>
                                                        <th>COP</th>
                                                        <th>Bs</th>
                                                        <th>Detalles</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php

                                                    $query = "
                                                            SELECT o.*, u.nombre AS usuario
                                                            FROM orden o
                                                            JOIN usuarios u ON o.customer_id = u.id
                                                            WHERE (o.status = '1' OR o.status = '2' OR o.status = '4') AND o.$tipoFiltroColumna = '$fecha'
                                                            ORDER BY o.id DESC
                                                            LIMIT 150
                                                        ";
                                                    $result = $conexion->query($query);
                                                    $contador = 1;

                                                    $tiposPago = [
                                                        '1' => 'Punto',
                                                        '2' => 'Pago Móvil',
                                                        '3' => 'Transferencia',
                                                        '4' => 'BS Efectivo',
                                                        '5' => 'Dólares',
                                                        '6' => 'Pesos',
                                                        '7' => 'Biopago',
                                                        '8' => 'Fraccionado'
                                                    ];

                                                    while ($row = $result->fetch_assoc()) {
                                                        $orderId = $row['id'];
                                                        $tipoPago = $tiposPago[$row['tipoPago']] ?? '<span class="badge bg-danger">Pendiente</span>';
                                                        $tVenta = $row['status'] == '4' ? 'M' : 'V';

                                                        // Obtener productos
                                                        $productos = [];
                                                        $queryProd = "SELECT oa.quantity, p.nombre
                                                                FROM orden_articulos oa
                                                                JOIN productos p ON oa.product_id = p.id
                                                                WHERE oa.order_id = '$orderId'";
                                                        $resProd = $conexion->query($queryProd);
                                                        while ($prod = $resProd->fetch_assoc()) {
                                                            $productos[] = $prod['quantity'] . ' ' . $prod['nombre'];
                                                        }

                                                        $productosTexto = htmlspecialchars(implode(', ', $productos));

                                                        echo "<tr>
                                                                <td>{$contador}</td>
                                                                <td>{$tVenta}</td>
                                                                <td>{$tipoPago}</td>
                                                                <td>" . htmlspecialchars($row['usuario']) . "</td>
                                                                <td>{$row['created']}</td>
                                                                <td>$" . number_format($row['total_price'], 2, ',', '.') . "</td>
                                                                <td>" . number_format($row['total_price_cop'], 0, ',', '.') . "</td>
                                                                <td>" . number_format($row['total_price_bs'], 2, ',', '.') . "</td>
                                                                <td><a href='detallesVenta.php?id={$orderId}' title='{$productosTexto}'>Detalles</a></td>
                                                            </tr>
                                                        ";
                                                        $contador++;
                                                    }


                                                    ?>
                                                </tbody>
                                            </table>


                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="x_panel tile">
                                    <div class="p-0 card-body" id="apex_chart_1">
                                        <!-- Punto de Venta -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/PUNTO-DE-VENTA.png" height="60px" alt="PUNTO DE VENTA" class="rounded-circle">
                                                    </div>
                                                    <h6 class="d-flex mb-0 align-items-center">
                                                        <span>
                                                            <p class="m-0">PUNTO DE VENTA</p>
                                                            <small class='text-muted'>Pago con punto</small>
                                                        </span>
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <span class="fs-15 fw-semibold">
                                                    <?php echo number_format($total_Punto, 2, '.', '.'); ?>
                                                </span><small>Bs</small>
                                            </div>
                                        </div>
                                        <!-- Pago Móvil -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/PAGO-MOVIL.png" height="60px" alt="PAGO MOVIL" class="rounded-circle">
                                                    </div>
                                                    <h6 class="d-flex mb-0 align-items-center">
                                                        <span>
                                                            <p class="m-0">PAGO MÓVIL</p>
                                                            <small class='text-muted'>Transferencia telefónica</small>
                                                        </span>
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <span class="fs-15 fw-semibold">
                                                    <?php echo number_format($total_Pmovil, 2, '.', '.'); ?>
                                                </span><small>Bs</small>
                                            </div>
                                        </div>


                                        <!-- Transferencia -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/TRANSFERENCIA.png" height="60px" alt="TRANSFERENCIA" class="rounded-circle">
                                                    </div>
                                                    <h6 class="d-flex mb-0 align-items-center">
                                                        <span>
                                                            <p class="m-0">TRANSFERENCIA</p>
                                                            <small class='text-muted'>Pago bancario</small>
                                                        </span>
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <span class="fs-15 fw-semibold">
                                                    <?php echo number_format($total_Transferencia, 2, '.', '.'); ?>
                                                </span><small>Bs</small>
                                            </div>
                                        </div>

                                        <!-- Biopago -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/BIOPAGO.png" height="60px" alt="BIOPAGO" class="rounded-circle">
                                                    </div>
                                                    <h6 class="d-flex mb-0 align-items-center">
                                                        <span>
                                                            <p class="m-0">BIOPAGO</p>
                                                            <small class='text-muted'>Pago biométrico</small>
                                                        </span>
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <span class="fs-15 fw-semibold">
                                                    <?php echo number_format($total_Biopago, 2, '.', '.'); ?>
                                                </span><small>Bs</small>
                                            </div>
                                        </div>

                                        <!-- Efectivo Bolívares -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/EFECTIVO-BOLIVAR.png" height="60px" alt="EFECTIVO" class="rounded-circle">
                                                    </div>
                                                    <h6 class="d-flex mb-0 align-items-center">
                                                        <span>
                                                            <p class="m-0">EFECTIVO BS</p>
                                                            <small class='text-muted'>Pago en efectivo</small>
                                                        </span>
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <span class="fs-15 fw-semibold">
                                                    <?php echo number_format($total_Efectivo, 2, '.', '.'); ?>
                                                </span><small>Bs</small>
                                            </div>
                                        </div>

                                        <!-- Efectivo Dólares -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/EFECTIVO-DOLAR.png" height="60px" alt="DOLARES" class="rounded-circle">
                                                    </div>
                                                    <h6 class="d-flex mb-0 align-items-center">
                                                        <span>
                                                            <p class="m-0">DÓLARES</p>
                                                            <small class='text-muted'>Pago en USD</small>
                                                        </span>
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <span class="fs-15 fw-semibold">
                                                    <?php echo number_format($total_Dolares, 2, '.', '.'); ?>
                                                </span><small>$ </small>
                                            </div>
                                        </div>


                                        <!-- Pesos -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/EFECTIVO-PESOS.png" height="60px" alt="PESOS" class="rounded-circle">
                                                    </div>
                                                    <h6 class="d-flex mb-0 align-items-center">
                                                        <span>
                                                            <p class="m-0">PESOS</p>
                                                            <small class='text-muted'>Pago en COP</small>
                                                        </span>
                                                    </h6>
                                                </div>
                                            </div>
                                            <div class="p-2">
                                                <span class="fs-15 fw-semibold">
                                                    <?php echo number_format($total_pesos, 0, '.', '.'); ?>
                                                </span>
                                                <small>Cop</small>
                                            </div>
                                        </div>

                                    </div>



                                </div>
                            </div>


                            <div class='col-lg-6'>



                                <div class='x_panel tile'>
                                    <div class='x_title'>
                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>

                                        <div class="p-0 card-body" id="apex_chart_1">
                                            <?php

                                            $items = [
                                                [
                                                    'titulo' => 'Bolivares',
                                                    'subtitulo' => totalizar_formatear([$gananciasBolivar]) . ' - Ganancias',
                                                    'valor' => totalizar_formatear([$total_Punto, $total_Pmovil, $total_Transferencia, $total_Efectivo, $total_Biopago]),
                                                    'prefijo' => '',
                                                    'bg' => 'bg-warning',
                                                    'text' => 'text-dark'
                                                ],
                                                [
                                                    'titulo' => 'Dolares',
                                                    'subtitulo' => totalizar_formatear([$gananciasDolares]) . ' - Ganancias',
                                                    'valor' => totalizar_formatear([$total_Dolares]),
                                                    'prefijo' => '$ ',
                                                    'bg' => 'bg-success',
                                                    'text' => 'text-white'
                                                ],
                                                [
                                                    'titulo' => 'Pesos',
                                                    'subtitulo' => totalizar_formatear([$gananciasPeso]) . ' - Ganancias',
                                                    'valor' => totalizar_formatear([$total_pesos]),
                                                    'prefijo' => '',
                                                    'bg' => 'bg-secondary',
                                                    'text' => 'text-white'
                                                ],

                                                [
                                                    'titulo' => 'Mayor',
                                                    'subtitulo' => '$' . number_format(returnGanancias('4'), '2', '.', '.') . ' Ganancias.',
                                                    'valor' => number_format($total_mayor, '2', '.', ','),
                                                    'prefijo' => '$ ',
                                                    'bg' => 'bg-dark',
                                                    'text' => 'text-white'
                                                ],
                                                [
                                                    'titulo' => 'Detal',
                                                    'subtitulo' => '$' . number_format(returnGanancias('1'), '2', '.', ',') . ' Ganancias.',
                                                    'valor' => number_format($total_detal, '2', '.', '.'),
                                                    'prefijo' => '$ ',
                                                    'bg' => 'bg-dark',
                                                    'text' => 'text-white'
                                                ],
                                            ];


                                            foreach ($items as $item) {
                                                echo "
                                                    <div class='d-flex justify-content-between py-2 g-0 border-bottom border-200'>
                                                        <div class='py-1'>
                                                            <div class='d-flex align-items-center'>
                                                                <div class='avatar avatar-xl me-3'>
                                                                    <div class='avatar-name rounded-circle {$item['bg']}'>
                                                                        <span class='fs-9 {$item['text']}'>" . strtoupper($item['titulo'][0]) . "</span>
                                                                    </div>
                                                                </div>
                                                                <h6 class='d-flex mb-0 align-items-center'>
                                                                    <span>
                                                                        <p class='m-0'>{$item['titulo']}</p>
                                                                        <small class='text-muted'>{$item['subtitulo']}</small>
                                                                    </span>
                                                                </h6>
                                                            </div>
                                                        </div>
                                                        <div class='p-2'>
                                                            <div class='fs-15 fw-semibold'>{$item['prefijo']}{$item['valor']}</div>
                                                        </div>
                                                    </div>
                                                    ";
                                            }
                                            ?>
                                        </div>

                                    </div>
                                </div>

                                <div class='row' style='display: block;'>


                                </div>
                            </div>
                        </div>
                        <!-- /page content -->


                    </div>
                </div>

                <!-- jQuery -->
                <script src='../vendors/jquery/dist/jquery.min.js'></script>
                <!-- Bootstrap -->
                <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
                <script src='../vendors/datatables.net/js/jquery.dataTables.min.js'></script>
                <script src='../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js'></script>
                <!-- Custom Theme Scripts -->
                <script src='../build/js/custom.min.js'></script>
                <script>
                    document.getElementById('fechaSolic').addEventListener('change', function() {
                        const fecha = this.value
                        window.location.href = '?fechaSolic=' + fecha
                        console.log(fecha)
                    })

                    const fechaSolicitada = "<?php echo $mes ?>"
                    document.getElementById('fechaSolic').value = fechaSolicitada
                </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>