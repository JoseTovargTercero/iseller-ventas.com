<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {
    $text_vista = 'Errores de Ventas';
    $topnav = topnav();

    // Check if table exists
    $tableExists = $conexion->query("SHOW TABLES LIKE 'errores_ventas'")->num_rows > 0;

    $errores = [];
    if ($tableExists) {
        $bss_id = $_SESSION['bss_id'];
        $sucursal_id = $_SESSION['sucursal'];

        $query = "SELECT * FROM errores_ventas ORDER BY fecha DESC LIMIT 100";
        $stmt = $conexion->prepare($query);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $errores[] = $row;
            }
        }
    }
?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Errores de Ventas</title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">
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
                        <div class="d-flex justify-content-between w-100 mb-3">
                            <div>
                                <h4>Errores de Sincronización</h4>
                                <p style="margin-top: -10px;">Vista simple de errores al sincronizar ventas</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table ">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Fecha</th>
                                                        <th>Sucursal ID</th>
                                                        <th>Error Msg</th>
                                                        <th>Venta (JSON)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($errores) > 0): ?>
                                                        <?php foreach ($errores as $error): ?>
                                                            <tr>
                                                                <td><?= $error['id'] ?></td>
                                                                <td><?= date('d/m/Y h:i A', strtotime($error['fecha'])) ?></td>
                                                                <td><?= $error['sucursal_id'] ?></td>
                                                                <td class="text-danger"><?= htmlspecialchars($error['error_msg']) ?></td>
                                                                <td>
                                                                    <button type="button" class="btn btn-sm btn-info" onclick='mostrarVenta(<?= json_encode($error['venta']) ?>)'>
                                                                        Ver JSON
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center">No hay errores registrados</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
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

        <style>
            .card {
                background-color: transparent !important;
            }
        </style>
        <!-- Modal para JSON -->
        <div class="modal fade" id="modalVenta" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalle de la Venta (JSON)</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <pre id="jsonVentaContent"></pre>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function mostrarVenta(jsonString) {
                try {
                    let obj = typeof jsonString === 'string' ? JSON.parse(jsonString) : jsonString;
                    document.getElementById('jsonVentaContent').textContent = JSON.stringify(obj, null, 2);
                } catch (e) {
                    document.getElementById('jsonVentaContent').textContent = jsonString;
                }
                $('#modalVenta').modal('show');
            }
        </script>
    </body>

    </html>
<?php
} else {
    header('Location: index.php');
}
?>