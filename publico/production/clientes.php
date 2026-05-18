<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $text_vista = 'Gestión de clientes';
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

    $stmt_clientes = mysqli_prepare($conexion, "SELECT * FROM `clientes` WHERE bss_id = ? ORDER BY id DESC");
    $stmt_clientes->bind_param('i', $bss_id);
    $stmt_clientes->execute();
    $result_clientes = $stmt_clientes->get_result();

    $clientes = [];
    if ($result_clientes->num_rows > 0) {
        while ($row = $result_clientes->fetch_assoc()) {
            $clientes[] = $row;
        }
    }
    $stmt_clientes->close();

    ?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Lista de Clientes</title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">

    </head>
    <style>
        .table-warning,
        .table-warning>td,
        .table-warning>th {
            background-color: #ffeeba00 !important;
        }

        .table-info,
        .table-info>td,
        .table-info>th {
            background-color: #bee5eb0f !important;
        }
    </style>

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
                                <h4>Clientes</h4>
                                <p style="margin-top: -10px;"><?php echo $text_vista ?></p>
                            </div>


                        </div>
                        <div class='clearfix'></div>



                        <!-- TAB 1: RESUMEN -->
                        <div class='row   fadeInUp animated'>

                            <div class='col-lg-12'>
                                <div class='x_panel'>

                                    <div class='d-flex justify-content-between'>
                                        <div style="display: flex; flex-direction: column;">
                                            <h2 class="m-0">Listado de clientes</h2>
                                            <small class="text-muted">Administra los clientes registrados en el sistema.</small>
                                        </div>
                                        <div class="p-2">
                                            <!-- <button class="btn btn-primary btn-sm">Nuevo Cliente</button> -->
                                        </div>
                                    </div>
                                    <div class='x_content '>
                                        <div class='card-box'>
                                            <table id="datatable" class="table table-bordered" style="width:100%">
                                                <thead>
                                                    <tr class="headings">
                                                        <th>#</th>
                                                        <th>Cedula</th>
                                                        <th>Nombre</th>
                                                        <th>Telefono</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="datos-tabla">
                                                    <?php 
                                                    $count = 1;
                                                    foreach ($clientes as $cliente): ?>
                                                        <tr>
                                                            <td><?= $count++ ?></td>
                                                            <td><?= htmlspecialchars($cliente['cedula'] ?? '') ?></td>
                                                            <td><?= htmlspecialchars($cliente['nombre'] ?? '') ?></td>
                                                            <td><?= htmlspecialchars($cliente['telefono'] ?? '') ?></td>
                                                            <td>
                                                                <button class="btn btn-sm btn-info btn-editar-cliente" data-id="<?= $cliente['id'] ?>" data-cedula="<?= htmlspecialchars($cliente['cedula'] ?? '') ?>" data-nombre="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>" data-telefono="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>" title="Editar"><i class="fa fa-pencil"></i></button>
                                                                <button class="btn btn-sm btn-danger btn-eliminar-cliente" data-id="<?= $cliente['id'] ?>" title="Eliminar"><i class="fa fa-trash"></i></button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>


                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
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

                    $(document).on('click', '.btn-editar-cliente', function() {
                        const btn = $(this);
                        const id = btn.data('id');
                        const cedula = btn.data('cedula');
                        const nombre = btn.data('nombre');
                        const telefono = btn.data('telefono');

                        Swal.fire({
                            title: 'Editar Cliente',
                            html: `
                                <div class="form-group text-left">
                                    <label>Cédula</label>
                                    <input type="text" id="swal-cedula" class="form-control" value="${cedula}">
                                </div>
                                <div class="form-group text-left">
                                    <label>Nombre</label>
                                    <input type="text" id="swal-nombre" class="form-control" value="${nombre}">
                                </div>
                                <div class="form-group text-left">
                                    <label>Teléfono</label>
                                    <input type="text" id="swal-telefono" class="form-control" value="${telefono}">
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Guardar',
                            cancelButtonText: 'Cancelar',
                            preConfirm: () => {
                                const c = document.getElementById('swal-cedula').value.trim();
                                const n = document.getElementById('swal-nombre').value.trim();
                                const t = document.getElementById('swal-telefono').value.trim();
                                if (!c || !n) {
                                    Swal.showValidationMessage('Cédula y Nombre son requeridos');
                                    return false;
                                }
                                return { id, cedula: c, nombre: n, telefono: t };
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: '../../configurar/updateClienteAjax.php',
                                    method: 'POST',
                                    data: result.value,
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.status) {
                                            Swal.fire('Éxito', response.msg, 'success').then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire('Error', response.msg, 'error');
                                        }
                                    },
                                    error: function() {
                                        Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                                    }
                                });
                            }
                        });
                    });

                    $(document).on('click', '.btn-eliminar-cliente', function() {
                        const id = $(this).data('id');
                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: 'Esta acción no se puede deshacer',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: '../../configurar/deleteClienteAjax.php',
                                    method: 'POST',
                                    data: { id: id },
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.status) {
                                            Swal.fire('Eliminado', response.msg, 'success').then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire('Error', response.msg, 'error');
                                        }
                                    },
                                    error: function() {
                                        Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                                    }
                                });
                            }
                        });
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