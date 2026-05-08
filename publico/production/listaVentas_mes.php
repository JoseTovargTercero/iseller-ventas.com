<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();
    $text_vista = 'Ventas del mes';



    $fecha = date('Y-m');


    $mes = explode('-', $fecha)[1];
    $mes = (strlen($mes) == 1 ? $mes = '0' . $mes : $mes);




    $stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ? ORDER BY principal DESC");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sucursales = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sucursales[] = $row;
        }
    }
    $stmt->close();

    $stmt = mysqli_prepare($conexion, "SELECT id, nombre, id_sucursal FROM `usuarios` WHERE bss_id = ?");
    $stmt->bind_param('s', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    $stmt->close();


    ?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Lista de Ventas</title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">

    </head>

    <!-- Modal -->
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Aplicar filtro</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="sucursal" class="form-label">Sucursal</label>
                        <select class="form-control" id="sucursal_selector" name="sucursal_a_editar">
                            <?php if (count($sucursales) > 1): ?>
                                <option value="">Todas las sucursales</option>
                            <?php endif; ?>

                            <?php foreach ($sucursales as $row): ?>
                                <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <select id="usuario" class="me-2 form-control form-control-sm">
                            <option value="todos">-- Seleccione --</option>
                        </select>
                    </div>


                </div>
                <div class="modal-footer text-end">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

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


                        <div class="d-flex justify-content-between w-100">
                            <div>
                                <h4>Ventas</h4>
                                <p style="margin-top: -10px;"><?php echo $text_vista ?></p>
                            </div>
                            <?php if ($_SESSION["nivel"] == 1): ?>
                                <div style="    align-self: anchor-center;">
                                    <button type="button" style="height: min-content;" class="btn btn-success btn-sm"
                                        data-toggle="modal" data-target="#exampleModalCenter">
                                        Aplicar Filtro
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class='clearfix'></div>

                        <div class='row   fadeInUp animated'>

                            <div class='col-lg-12'>
                                <div class='x_panel'>

                                    <div class='d-flex justify-content-between'>
                                        <div style="display: flex; flex-direction: column;">
                                            <h2 class="m-0">Listado de ventas</h2>
                                            <small class="text-muted">Los créditos otorgados se visualizarán en la lista;
                                                <br>sin embargo, no serán considerados en la totalización hasta su
                                                cancelación.</small>
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
                                                    echo "<option value='" . $key . "' " . ($key == $mes ? 'selected' : '') . ">" . $item . "</option>";
                                                }
                                                ?>

                                            </select>


                                        </div>
                                    </div>
                                    <div class='x_content '>
                                        <div class='card-box'>
                                            <table id="datatable" class="table table-bordered" style="width:100%">
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
                                                        <th>Cliente</th>
                                                        <th>Detalles</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="datos-tabla">
                                                </tbody>
                                            </table>


                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="x_panel tile">
                                    <div class="p-0 card-body">
                                        <!-- Punto de Venta -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/PUNTO-DE-VENTA.png" height="60px"
                                                            alt="PUNTO DE VENTA" class="rounded-circle">
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
                                                <span class="fs-15 fw-semibold" id="total_Punto">
                                                </span><small> Bs</small>
                                            </div>
                                        </div>
                                        <!-- Pago Móvil -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/PAGO-MOVIL.png" height="60px" alt="PAGO MOVIL"
                                                            class="rounded-circle">
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
                                                <span class="fs-15 fw-semibold" id="total_Pmovil">
                                                </span><small> Bs</small>
                                            </div>
                                        </div>


                                        <!-- Transferencia -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/TRANSFERENCIA.png" height="60px"
                                                            alt="TRANSFERENCIA" class="rounded-circle">
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
                                                <span class="fs-15 fw-semibold" id="total_Transferencia">
                                                </span><small> Bs</small>
                                            </div>
                                        </div>

                                        <!-- Biopago -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/BIOPAGO.png" height="60px" alt="BIOPAGO"
                                                            class="rounded-circle">
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
                                                <span class="fs-15 fw-semibold" id="total_Biopago">
                                                </span><small> Bs</small>
                                            </div>
                                        </div>

                                        <!-- Efectivo Bolívares -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/EFECTIVO-BOLIVAR.png" height="60px" alt="EFECTIVO"
                                                            class="rounded-circle">
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
                                                <span class="fs-15 fw-semibold" id="total_Efectivo">
                                                </span><small> Bs</small>
                                            </div>
                                        </div>

                                        <!-- Efectivo Dólares -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/EFECTIVO-DOLAR.png" height="60px" alt="DOLARES"
                                                            class="rounded-circle">
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
                                                <span class="fs-15 fw-semibold" id="total_Dolares"></span><small> $ </small>
                                            </div>
                                        </div>


                                        <!-- Pesos -->
                                        <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                                            <div class="py-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-xl me-3">
                                                        <img src="images/EFECTIVO-PESOS.png" height="60px" alt="PESOS"
                                                            class="rounded-circle">
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
                                                <span class="fs-15 fw-semibold" id="total_pesos"></span>
                                                <small> Cop</small>
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

                                        <div class="p-0 card-body">
                                            <?php

                                            $items = [
                                                [
                                                    'titulo' => 'Bolivares',
                                                    'subtitulo' => 'ganacias_Bolivares',
                                                    'valor' => 'valor_Bolivares',
                                                    'bg' => 'bg-warning',
                                                    'text' => 'text-dark'
                                                ],
                                                [
                                                    'titulo' => 'Dolares',
                                                    'subtitulo' => 'ganacias_Dolares',
                                                    'valor' => 'valor_Dolares',
                                                    'bg' => 'bg-success',
                                                    'text' => 'text-white'
                                                ],
                                                [
                                                    'titulo' => 'Pesos',
                                                    'subtitulo' => 'ganacias_Pesos',
                                                    'valor' => 'valor_Pesos',
                                                    'bg' => 'bg-secondary',
                                                    'text' => 'text-white'
                                                ],

                                                [
                                                    'titulo' => 'Mayor',
                                                    'subtitulo' => 'ganacias_Mayor',
                                                    'valor' => 'valor_Mayor',
                                                    'bg' => 'bg-dark',
                                                    'text' => 'text-white'
                                                ],
                                                [
                                                    'titulo' => 'Detal',
                                                    'subtitulo' => 'ganacias_Detal',
                                                    'valor' => 'valor_Detal',
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
                                                                        <p class='m-0' >{$item['titulo']}</p>
                                                                        <small class='text-muted' id='{$item['subtitulo']}'></small>
                                                                    </span>
                                                                </h6>
                                                            </div>
                                                        </div>
                                                        <div class='p-2'>
                                                            <div class='fs-15 fw-semibold' id='{$item['valor']}'></div>
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
                <script>
                    let table = new DataTable('#datatable');
                    const periodo = 'mes';
                </script>

                <script src='../build/js/info_ventas.js'></script>
    </body>
    <script>
        document.getElementById('sucursal_selector').addEventListener('change', function () {
            const sucursalId = this.value;
            const usuarioSelect = document.getElementById('usuario');
            usuarioSelect.innerHTML = '<option value="todos">-- Seleccione --</option>'; // Limpiar opciones anteriores

            if (sucursalId === 'todas') {
                return;
            }

            // Filtrar usuarios por sucursal
            const usuariosFiltrados = <?php echo json_encode($usuarios); ?>.filter(usuario => usuario.id_sucursal == sucursalId);

            if (usuariosFiltrados.length > 0) {
                usuariosFiltrados.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.id;
                    option.textContent = usuario.nombre;
                    usuarioSelect.appendChild(option);
                });
            } else {
                usuarioSelect.innerHTML = '<option value="">No hay usuarios disponibles</option>';
            }
        });
    </script>

    </html>
    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>