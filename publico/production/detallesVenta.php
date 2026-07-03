<?php
require_once('includes/requires.php');
if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();

?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Detalles</title>
        <?php require_once('includes/headers.php'); ?>
        <style>
            .right_col {
                background: var(--dash-bg);
                min-height: 100vh;
                padding: 24px 28px !important;
            }

            .dash-header {
                margin-bottom: 28px;
            }

            .dash-header h3 {
                font-size: 20px;
                font-weight: 700;
                color: var(--dash-text);
                margin: 0;
                letter-spacing: -0.3px;
            }

            .dash-header p {
                color: var(--dash-text-muted);
                margin: 2px 0 0;
                font-size: 13px;
            }

            .dash-panel {
                background: var(--dash-card);
                border: 1px solid var(--dash-border);
                border-radius: 14px;
                overflow: hidden;
            }

            .dash-panel .panel-header {
                padding: 18px 22px 14px;
                border-bottom: 1px solid var(--dash-border);
            }

            .dash-panel .panel-header h6 {
                font-size: 14px;
                font-weight: 600;
                color: var(--dash-text);
                margin: 0;
            }

            .dash-panel .panel-body {
                padding: 16px 22px;
                color: var(--dash-text-muted);
                font-size: 13px;
                line-height: 1.6;
            }

            .dash-panel .panel-body strong {
                color: var(--dash-text);
            }

            .dash-table-wrap {
                overflow-x: auto;
                margin-top: 16px;
            }

            .dash-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .dash-table thead th {
                padding: 10px 12px;
                text-align: left;
                font-weight: 600;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: .3px;
                color: var(--dash-text-muted);
                border-bottom: 1px solid var(--dash-border);
                background: transparent;
            }

            .dash-table tbody tr {
                transition: background .15s ease;
                border-bottom: 1px solid rgba(46, 53, 62, .4);
            }

            .dash-table tbody tr:last-child {
                border-bottom: none;
            }

            .dash-table tbody tr:hover {
                background: rgba(45, 212, 160, .03);
            }

            .dash-table tbody td {
                padding: 10px 12px;
                color: var(--dash-text);
                vertical-align: middle;
            }

            .dash-table tbody td small {
                color: var(--dash-text-muted);
                font-size: 11px;
            }
        </style>
    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <!-- page content -->
                <div class='right_col' role='main'>
                    <div class="dash-header">
                        <h3>Ventas</h3>
                        <p>Detalles</p>
                    </div>

                    <div class="dash-panel">
                        <div class="panel-header">
                            <h6><ion-icon name="receipt-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Detalles de la venta</h6>
                        </div>
                        <div class="panel-body x_content">
                            <?php
                            // 1. Validación temprana y tipado
                            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

                            if (!$id) {
                                echo '<div class="alert alert-warning">ID no válido o no proporcionado.</div>';
                                exit;
                            }

                            // 2. Consulta optimizada (solo pedir lo necesario)
                            $sql = "SELECT created, status, descontado, total_price FROM orden WHERE id = ?";
                            $stmt = $conexion->prepare($sql);
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $orden = $result->fetch_assoc();

                            // 3. Lógica separada de la presentación
                            if (!$orden): ?>
                                <p>No se encontró la orden solicitada.</p>
                            <?php else: ?>
                                <div class="orden-detalle">
                                    <p>Despachado el <strong><?= htmlspecialchars($orden['created']) ?></strong></p>

                                    <?php if ($orden['status'] == '4'): ?>
                                        <p>Esta venta se realizó bajo la modalidad "al mayor" y se le aplicó un descuento del
                                            <strong><?= number_format($orden['descontado'], 2, ',', '.') ?>%</strong>
                                        </p>
                                    <?php endif; ?>

                                    <p>Valor de la venta: <strong><?= number_format($orden['total_price'], 2, ',', '.') ?> $</strong></p>
                                </div>
                            <?php endif; ?>

                            <div>

                                <table class='dash-table' style='width:100%'>
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Pago por</th>
                                            <th>Producto</th>
                                            <th>Cantidad</th>
                                            <th>Pagado</th>
                                            <th>Moneda de pago</th>
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
                                                $userId = $orden['usuario'];

                                                $stmtUsuario = $conexion->prepare("SELECT nombre FROM usuarios WHERE id = ?");
                                                $stmtUsuario->bind_param("i", $userId);
                                                $stmtUsuario->execute();
                                                $resultUsuario = $stmtUsuario->get_result();
                                                $usuario1 = $resultUsuario->fetch_assoc()['nombre'] ?? 'Desconocido';

                                                echo '
                                                 <div class="orden-detalle">
                                                    <p >Usuario: <strong>' . htmlspecialchars($usuario1) . '</strong></p>
                                                </div>
                                                ';

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

                                                    $stmtProducto = $conexion->prepare("SELECT nombre FROM productos WHERE id = ?");
                                                    $stmtProducto->bind_param("i", $productoId);
                                                    $stmtProducto->execute();
                                                    $resultProducto = $stmtProducto->get_result();
                                                    $nombreProducto = $resultProducto->fetch_assoc()['nombre'] ?? 'Producto desconocido';

                                                    if ($tipoV == 4) {
                                                        $precioDescontado = $precioDolar - ($precioDolar * $descuentoDel / 100);
                                                        $precioFinal = $precioDescontado * $cantidad;
                                                    } else {
                                                        $precioFinal = $precioDolar * $cantidad;
                                                    }

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
                                                <tr>
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

        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <script src='../build/js/custom.js'></script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>