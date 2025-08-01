<?php
require_once('includes/requires.php');
if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();

?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Detalles </title>

        <?php require_once('includes/headers.php'); ?>


    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>

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
                                                    $id = $_GET['id'] ?? null;

                                                    if ($id) {
                                                        $stmt = $conexion->prepare("SELECT * FROM orden WHERE id = ?");
                                                        $stmt->bind_param("i", $id);
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();

                                                        if ($result->num_rows > 0) {
                                                            while ($row = $result->fetch_assoc()) {
                                                                echo 'Despachado el <strong>' . htmlspecialchars($row['created']) . '</strong>';

                                                                if ($row['status'] == '4') {
                                                                    echo '<br>Esta venta se realizó bajo la modalidad "al mayor" y se le aplicó un descuento del <strong>' . number_format($row['descontado'], 2, ',', '.') . '%</strong>';
                                                                }

                                                                echo '<br>Valor de la venta: <strong>' . number_format($row['total_price'], 2, ',', '.') . '$</strong>';
                                                            }
                                                        } else {
                                                            echo 'No se encontró la orden.';
                                                        }
                                                    } else {
                                                        echo 'ID no proporcionado.';
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
                                                            $pagoTipos = [
                                                                '1' => 'Punto',
                                                                '2' => 'Pago Movil',
                                                                '3' => 'Transferencia',
                                                                '4' => 'BS Efectivo',
                                                                '5' => 'Dolares',
                                                                '6' => 'Pesos',
                                                                '7' => 'Biopago',
                                                                '8' => 'Fraccionado'
                                                            ];

                                                            // Obtener orden
                                                            $stmtOrden = $conexion->prepare("SELECT * FROM orden WHERE id = ?");
                                                            $stmtOrden->bind_param("i", $id);
                                                            $stmtOrden->execute();
                                                            $resultOrden = $stmtOrden->get_result();

                                                            if ($resultOrden->num_rows > 0) {
                                                                $contador = 1;
                                                                while ($orden = $resultOrden->fetch_assoc()) {
                                                                    $descuentoDel = $orden['descontado'];
                                                                    $tipoV = $orden['status'];
                                                                    $orderid = $orden['id'];
                                                                    $tipopago = $orden['tipoPago'];
                                                                    $userId = $orden['customer_id'];

                                                                    // Obtener nombre del usuario
                                                                    $stmtUsuario = $conexion->prepare("SELECT nombre FROM usuarios WHERE id = ?");
                                                                    $stmtUsuario->bind_param("i", $userId);
                                                                    $stmtUsuario->execute();
                                                                    $resultUsuario = $stmtUsuario->get_result();
                                                                    $usuario1 = $resultUsuario->fetch_assoc()['nombre'] ?? 'Desconocido';

                                                                    echo '<span style="margin-left: 21px;">Usuario: <strong>' . htmlspecialchars($usuario1) . '</strong><br><br></span>';

                                                                    // Obtener productos de la orden
                                                                    $stmtArticulos = $conexion->prepare("SELECT * FROM orden_articulos WHERE order_id = ?");
                                                                    $stmtArticulos->bind_param("i", $orderid);
                                                                    $stmtArticulos->execute();
                                                                    $resultArticulos = $stmtArticulos->get_result();

                                                                    while ($articulo = $resultArticulos->fetch_assoc()) {
                                                                        $productoId = $articulo['product_id'];
                                                                        $cantidad = $articulo['quantity'];
                                                                        $precioDolar = $articulo['precio_venta_dolar'];
                                                                        $precioBs = $articulo['precio_venta_bs'];
                                                                        $precioCop = $articulo['precio_venta_cop'];

                                                                        // Obtener nombre del producto
                                                                        $stmtProducto = $conexion->prepare("SELECT nombre FROM productos WHERE id = ?");
                                                                        $stmtProducto->bind_param("i", $productoId);
                                                                        $stmtProducto->execute();
                                                                        $resultProducto = $stmtProducto->get_result();
                                                                        $nombreProducto = $resultProducto->fetch_assoc()['nombre'] ?? 'Producto desconocido';

                                                                        // Aplicar descuento si aplica
                                                                        if ($tipoV == 4) {
                                                                            $precioDescontado = $precioDolar - ($precioDolar * $descuentoDel / 100);
                                                                            $precioFinal = $precioDescontado * $cantidad;
                                                                        } else {
                                                                            $precioFinal = $precioDolar * $cantidad;
                                                                        }

                                                                        // Calcular el precio en la moneda correspondiente
                                                                        switch ($tipopago) {
                                                                            case '1':
                                                                            case '2':
                                                                            case '3':
                                                                            case '4':
                                                                            case '7':
                                                                                $precioFinalMoneda = $precioBs * $cantidad;
                                                                                $moneda = 'BS';
                                                                                break;
                                                                            case '5':
                                                                                $precioFinalMoneda = $precioDolar * $cantidad;
                                                                                $moneda = '$';
                                                                                break;
                                                                            default:
                                                                                $precioFinalMoneda = $precioCop * $cantidad;
                                                                                $moneda = 'COP';
                                                                        }

                                                                        echo '
                                                                        <tr class="even pointer">
                                                                            <td>' . $contador++ . '</td>
                                                                            <td>' . ($pagoTipos[$tipopago] ?? 'PENDIENTE') . '</td>
                                                                            <td>' . htmlspecialchars($nombreProducto) . '</td>
                                                                            <td>' . $cantidad . '</td>
                                                                            <td>$' . number_format($precioFinal, 2, ',', '.') . '</td>
                                                                            <td>' . number_format($precioFinalMoneda, 2, ',', '.') . ' <small>' . $moneda . '</small></td>
                                                                        </tr>';
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

            </div>
        </div>

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- DataTables core -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
        <!-- Buttons extension -->
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
        <!-- PDF export -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
        <script src='../build/js/custom.js'></script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>