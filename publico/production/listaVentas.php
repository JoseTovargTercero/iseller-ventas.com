<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $text_vista = 'Ventas del dia';
    $topnav = topnav();



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
<style>
  .table-warning, .table-warning>td, .table-warning>th {
    background-color: #ffeeba00 !important;
}
.table-info, .table-info>td, .table-info>th {
    background-color: #bee5eb0f !important;
}
</style>

    <!-- Modal -->
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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
                                    <button type="button" style="height: min-content;" class="btn btn-success btn-sm" data-toggle="modal" data-target="#exampleModalCenter">
                                        Aplicar Filtro
                                    </button>
                                </div>
                            <?php endif; ?>

                        </div>
                        <div class='clearfix'></div>

                        <!-- NAV TABS -->
                        <ul class="nav nav-tabs mb-3" id="ventasTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="tab-resumen" data-toggle="tab" href="#pane-resumen" role="tab">Resumen del día</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="tab-usuarios" onclick="cargarTotalesUsuarios()" data-toggle="tab" href="#pane-usuarios" role="tab">Totales por Usuario</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                        <!-- TAB 1: RESUMEN -->
                        <div class="tab-pane fade show active" id="pane-resumen" role="tabpanel">
                        <div class='row   fadeInUp animated'>

                            <div class='col-lg-12'>
                                <div class='x_panel'>

                                    <div class='d-flex justify-content-between'>
                                        <div style="display: flex; flex-direction: column;">
                                            <h2 class="m-0">Listado de ventas</h2>
                                            <small class="text-muted">Los créditos otorgados se visualizarán en la lista; <br>sin embargo, no serán considerados en la totalización hasta su cancelación.</small>
                                        </div>
                                        <div class="p-2">
                                            <input required type="date" class="form-control form-control-sm" name="fechaSolic" id="fechaSolic">
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
                                                <span class="fs-15 fw-semibold" id="total_Punto">
                                                </span><small> Bs</small>
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
                                                <span class="fs-15 fw-semibold" id="total_Pmovil">
                                                </span><small> Bs</small>
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
                                                <span class="fs-15 fw-semibold" id="total_Transferencia">
                                                </span><small> Bs</small>
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
                                                <span class="fs-15 fw-semibold" id="total_Biopago">
                                                </span><small> Bs</small>
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
                                                <span class="fs-15 fw-semibold" id="total_Efectivo">
                                                </span><small> Bs</small>
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
                                                <span class="fs-15 fw-semibold" id="total_Dolares"></span><small> $ </small>
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
                                                                        <small class='text-muted d-none' id='{$item['subtitulo']}'></small>
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
                        </div><!-- /tab-pane resumen -->

                        <!-- TAB 2: TOTALES POR USUARIO -->
                        <div class="tab-pane fade" id="pane-usuarios" role="tabpanel">
                            <div class="x_panel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="m-0">Totales por Usuario vs Corte de Caja</h5>
                                    <span id="estado-corte-badge"></span>
                                </div>

                                <!-- Tabla usuarios -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" id="tabla-usuarios-totales">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Hora de inicio</th>
                                                <th>Concepto</th>
                                                <th class="text-end">Efect. Bs</th>
                                                <th class="text-end">USD</th>
                                                <th class="text-end">Pesos</th>
                                                <th class="text-end">Punto</th>
                                                <th class="text-end">P.Móvil</th>
                                                <th class="text-end">Transfer.</th>
                                                <th class="text-end">Biopago</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-usuarios">
                                            <tr><td colspan="9" class="text-center text-muted">Cargando...</td></tr>
                                        </tbody>
                                        <tfoot id="tfoot-usuarios"></tfoot>
                                    </table>
                                </div>
                                
                                <div id="seccion-corte" class="mt-4" style="display:none;"></div>
                            </div>
                        </div><!-- /tab-pane usuarios -->

                        </div><!-- /tab-content -->
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
                <script src='../build/js/global-loader.js'></script>
                <script>
                    let table = new DataTable('#datatable');
                    const periodo = 'dia';
                </script>

                <script src='../build/js/info_ventas.js'></script>
                <script>
                let _totalesUsuariosCargado = false;

                // Cargar datos cuando se muestra el tab
              
                function cargarTotalesUsuarios(force) {
                    if (_totalesUsuariosCargado && !force) return;
                    _totalesUsuariosCargado = true;

                    const fecha = document.getElementById('fechaSolic').value || '';
                    const sucursal = document.getElementById('sucursal_selector')?.value || '';

                    fetch('../../configurar/listaVentas_back.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'totales_por_usuario', fechaSolic: fecha, sucursal: sucursal })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status !== 'success') return console.error('Error cargando totales usuario:', data);

                        const tbody = document.getElementById('tbody-usuarios');
                        const tfoot = document.getElementById('tfoot-usuarios');
                        tbody.innerHTML = '';
                        tfoot.innerHTML = '';

                        if (!data.cortes || data.cortes.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No hay cortes de caja registrados para este día.</td></tr>';
                            return;
                        }

                        const fmt = (n, dec=2) => Number(n).toLocaleString('es-VE', {minimumFractionDigits:dec, maximumFractionDigits:dec});
                        const colorDif = val => val < 0 ? 'text-success' : (val > 0 ? 'text-danger' : '');

                        data.cortes.forEach(u => {
                            let obs = '';
                            if (u.apertura?.observaciones) obs += `<small class="text-muted d-block"><b>Ape:</b> ${u.apertura.observaciones}</small>`;
                            if (u.cierre?.observaciones) obs += `<small class="text-muted d-block"><b>Cie:</b> ${u.cierre.observaciones}</small>`;
                            
                            const ape = u.apertura || { efectivo_bs: 0, dolares: 0, pesos: 0 };
                            const cie = u.cierre || {
                                contado: { efectivo_bs:0, dolares:0, pesos:0, punto:0, pago_movil:0, transferencia:0, biopago:0 },
                                sistema: { efectivo_bs:0, dolares:0, pesos:0, punto:0, pago_movil:0, transferencia:0, biopago:0 },
                                diferencia: { efectivo_bs:0, dolares:0, pesos:0, punto:0, pago_movil:0, transferencia:0, biopago:0 },
                                fondo_dejado: { efectivo_bs:0, dolares:0, pesos:0 }
                            };

                            tbody.innerHTML += `
                                <tr>
                                    <td rowspan="5" class="align-middle text-center" style="border-bottom: 2px solid #dee2e6;">
                                        <strong>${u.nombre}</strong><br>${obs}
                                    </td>
                                    <td rowspan="5" class="align-middle text-center" style="border-bottom: 2px solid #dee2e6;">
                                        <strong>${u.apertura.hora_apertura}</strong>
                                    </td>


                                    <td class=""><strong>Apertura (Fondo Recibido)</strong></td>
                                    <td class="text-end">${fmt(ape.efectivo_bs)} Bs</td>
                                    <td class="text-end">${fmt(ape.dolares)} $</td>
                                    <td class="text-end">${fmt(ape.pesos,0)} COP</td>
                                    <td class="text-end text-muted">-</td>
                                    <td class="text-end text-muted">-</td>
                                    <td class="text-end text-muted">-</td>
                                    <td class="text-end text-muted">-</td>
                                </tr>
                                <tr>
                                    <td class="table-info"><strong>Cierre (Contado)</strong></td>
                                    <td class="text-end table-info">${fmt(cie.contado.efectivo_bs)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.contado.dolares)} $</td>
                                    <td class="text-end table-info">${fmt(cie.contado.pesos,0)} COP</td>
                                    <td class="text-end table-info">${fmt(cie.contado.punto)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.contado.pago_movil)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.contado.transferencia)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.contado.biopago)} Bs</td>
                                </tr>
                                <tr>
                                    <td class="table-info">Sistema</td>
                                    <td class="text-end table-info">${fmt(cie.sistema.efectivo_bs)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.sistema.dolares)} $</td>
                                    <td class="text-end table-info">${fmt(cie.sistema.pesos,0)} COP</td>
                                    <td class="text-end table-info">${fmt(cie.sistema.punto)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.sistema.pago_movil)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.sistema.transferencia)} Bs</td>
                                    <td class="text-end table-info">${fmt(cie.sistema.biopago)} Bs</td>
                                </tr>
                                <tr>
                                    <td class="table-info"><strong>Diferencia</strong></td>
                                    <td class="text-end table-info ${colorDif(cie.diferencia.efectivo_bs)}"><strong>${fmt(cie.diferencia.efectivo_bs)} Bs</strong></td>
                                    <td class="text-end table-info ${colorDif(cie.diferencia.dolares)}"><strong>${fmt(cie.diferencia.dolares)} $</strong></td>
                                    <td class="text-end table-info ${colorDif(cie.diferencia.pesos)}"><strong>${fmt(cie.diferencia.pesos,0)} COP</strong></td>
                                    <td class="text-end table-info ${colorDif(cie.diferencia.punto)}"><strong>${fmt(cie.diferencia.punto)} Bs</strong></td>
                                    <td class="text-end table-info ${colorDif(cie.diferencia.pago_movil)}"><strong>${fmt(cie.diferencia.pago_movil)} Bs</strong></td>
                                    <td class="text-end table-info ${colorDif(cie.diferencia.transferencia)}"><strong>${fmt(cie.diferencia.transferencia)} Bs</strong></td>
                                    <td class="text-end table-info ${colorDif(cie.diferencia.biopago)}"><strong>${fmt(cie.diferencia.biopago)} Bs</strong></td>
                                </tr>
                                <tr style="border-bottom: 2px solid #dee2e6;">
                                    <td class="table-warning"><strong>Fondo Dejado (Cierre)</strong></td>
                                    <td class="text-end table-warning">${fmt(cie.fondo_dejado.efectivo_bs)} Bs</td>
                                    <td class="text-end table-warning">${fmt(cie.fondo_dejado.dolares)} $</td>
                                    <td class="text-end table-warning">${fmt(cie.fondo_dejado.pesos,0)} COP</td>
                                    <td class="text-end table-warning text-muted">-</td>
                                    <td class="text-end table-warning text-muted">-</td>
                                    <td class="text-end table-warning text-muted">-</td>
                                    <td class="text-end table-warning text-muted">-</td>
                                </tr>
                            `;
                        });
                    })
                    .catch(err => console.error('Error cargando totales usuario:', err));
                }

                // Si se cambia la fecha, resetear caché del tab usuarios
                document.getElementById('fechaSolic').addEventListener('change', function() {
                    _totalesUsuariosCargado = false;
                    if (document.getElementById('pane-usuarios').classList.contains('show')) {
                        cargarTotalesUsuarios(true);
                    }
                });
                </script>
                <script>
                    document.getElementById('sucursal_selector').addEventListener('change', function() {
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
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>