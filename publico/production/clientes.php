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

        /* ── Móvil (WebView de la app) ───────────────────────────────── */
        @media (max-width: 768px) {
            .right_col {
                padding: 14px 12px !important;
            }

            .x_panel {
                padding: 10px;
            }

            /* El contador de filas no aporta en el teléfono */
            #datatable th:first-child,
            #datatable td:first-child {
                display: none;
            }

            #datatable {
                font-size: 12.5px;
            }

            #datatable th,
            #datatable td {
                padding: 10px 8px;
            }

            #datatable td:last-child {
                white-space: nowrap;
            }

            /* Editar / borrar con tamaño de dedo */
            #datatable .btn {
                width: 36px;
                height: 36px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
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


                    <!-- TAB 1: RESUMEN -->
                    <div class='row'>

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
                                            <tbody id="datos-tabla"></tbody>
                                        </table>


                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Bootstrap -->
                <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
                <!-- DataTables core -->
                <link rel="stylesheet" href="../vendors/datatables/jquery.dataTables.min.css">
                <script src="../vendors/jquery/jquery-3.7.1.min.js"></script>
                <script src="../vendors/datatables/jquery.dataTables.min.js"></script>
                <script src='../build/js/custom.js'></script>
                <script src='../build/js/global-loader.js?v=2'></script>
                <script src="js/nombre_pagina.js"></script>

                <script>
                    function escapeHtml(valor) {
                        return String(valor == null ? '' : valor).replace(/[&<>"']/g, function(c) {
                            return {
                                '&': '&amp;',
                                '<': '&lt;',
                                '>': '&gt;',
                                '"': '&quot;',
                                "'": '&#39;'
                            } [c];
                        });
                    }

                    let table = new DataTable('#datatable', {
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '../../configurar/clientes_list.php',
                            type: 'POST',
                            error: function() {
                                Swal.fire('Error', 'No se pudieron cargar los clientes.', 'error');
                            }
                        },
                        pageLength: 25,
                        lengthMenu: [
                            [10, 25, 50, 100],
                            [10, 25, 50, 100]
                        ],
                        order: [
                            [0, 'desc']
                        ],
                        language: {
                            processing: '<span class="text-muted">Cargando clientes...</span>',
                            search: 'Buscar:',
                            lengthMenu: 'Mostrar _MENU_ registros',
                            info: 'Mostrando _START_ a _END_ de _TOTAL_ clientes',
                            infoEmpty: 'Sin clientes para mostrar',
                            infoFiltered: '(filtrado de _MAX_ clientes)',
                            zeroRecords: 'No se encontraron clientes',
                            emptyTable: 'No hay clientes registrados',
                            paginate: {
                                first: 'Primero',
                                last: 'Último',
                                next: 'Siguiente',
                                previous: 'Anterior'
                            }
                        },
                        columns: [{
                                data: 'id',
                                render: function(data, type, row, meta) {
                                    if (type !== 'display') return data;
                                    return meta.settings._iDisplayStart + meta.row + 1;
                                }
                            },
                            {
                                data: 'cedula',
                                render: function(data) {
                                    return escapeHtml(data);
                                }
                            },
                            {
                                data: 'nombre',
                                render: function(data) {
                                    return escapeHtml(data);
                                }
                            },
                            {
                                data: 'telefono',
                                render: function(data) {
                                    return escapeHtml(data);
                                }
                            },
                            {
                                data: null,
                                orderable: false,
                                searchable: false,
                                className: 'text-nowrap',
                                render: function(data, type, row) {
                                    const id = Number(row.id) || 0;
                                    const cedula = escapeHtml(row.cedula);
                                    const nombre = escapeHtml(row.nombre);
                                    const telefono = escapeHtml(row.telefono);
                                    return '<button class="btn btn-sm btn-info btn-editar-cliente" data-id="' + id + '" data-cedula="' + cedula + '" data-nombre="' + nombre + '" data-telefono="' + telefono + '" title="Editar"><i class="fa fa-pencil"></i></button> ' +
                                        '<button class="btn btn-sm btn-danger btn-eliminar-cliente" data-id="' + id + '" title="Eliminar"><i class="fa fa-trash"></i></button>';
                                }
                            }
                        ]
                    });

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
                                    <input type="text" id="swal-cedula" class="form-control" value="${escapeHtml(cedula)}">
                                </div>
                                <div class="form-group text-left">
                                    <label>Nombre</label>
                                    <input type="text" id="swal-nombre" class="form-control" value="${escapeHtml(nombre)}">
                                </div>
                                <div class="form-group text-left">
                                    <label>Teléfono</label>
                                    <input type="text" id="swal-telefono" class="form-control" value="${escapeHtml(telefono)}">
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
                                return {
                                    id,
                                    cedula: c,
                                    nombre: n,
                                    telefono: t
                                };
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
                                                table.ajax.reload(null, false);
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
                                    data: {
                                        id: id
                                    },
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.status) {
                                            Swal.fire('Eliminado', response.msg, 'success').then(() => {
                                                table.ajax.reload(null, false);
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