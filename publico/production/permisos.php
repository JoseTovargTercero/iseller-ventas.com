<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {
    $topnav = topnav();



    if ($_SESSION["nivel"] != 1) {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }




?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Permisos de usuarios </title>
        <?php require_once('includes/headers.php'); ?>
        <style>
            .avtar.avtar-xs {
                width: 32px;
                height: 32px;
                font-size: 12px;
                border-radius: 2px;
            }

            .btn-light-success {
                background: #e8fdf8;
                color: #1de9b6;
                border-color: #e8fdf8;
            }

            .btn-light-danger {
                background: #feeceb;
                color: #f44236;
                border-color: #feeceb;
            }

            .avtar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                font-size: 18px;
                font-weight: 600;
                width: 48px;
                height: 48px;
            }

            td>a {
                color: gray !important;
            }
        </style>
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


                        <h4>Persmisos</h4>
                        <p style="margin-top: -10px;">Permisos de usuarios</p>


                        <div class='clearfix'></div>

                        <div class='row   fadeInUp animated'>

                            <div class='col-md-12 col-sm-12'>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Permisos</h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content altoScroll'>
                                        <table id="table" class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th class="w-5"></th>
                                                    <th class="w-20">Usuario</th>
                                                    <th class="w-10">Fecha de creación</th>
                                                    <th class="w-50">Acceso</th>
                                                    <th class="w-10"></th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>

                                        </table>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>


            </div>
        </div>

        <?php require('../assets/templates/modal.html'); ?>
        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- FastClick -->
        <script src='../vendors/fastclick/lib/fastclick.js'></script>
        <!-- NProgress -->
        <script src='../vendors/nprogress/nprogress.js'></script>
        <!-- validator -->
        <!-- <script src = '../vendors/validator/validator.js'></script> -->

        <!-- Custom Theme Scripts -->
        <script src='../build/js/custom.js'></script>
        <script src="../build/js/modal.js"></script>

        <script>
            // Modificar modal
            $(document).ready(function() {
                const modal_body = document.getElementById('modal-body')
                modal_body.style = 'overflow: auto;'
                modal_body.innerHTML = `<h2 class="mb-0 mt-0" id="titulo_modal">Permisos </h2>
                <hr>
                  <table class="table table-hover datatable-table" id="list_permisos">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th>Permiso</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>`

                document.getElementById('modal').style = 'height: 100% !important;'

                const modal = document.getElementById('w-modal')
                modal.classList.add('w-50', 'h-60')
                modal.style = "padding: 0px;"

            })
            // Modificar modal



            const url_back = '../../configurar/glob_users_access_back.php';

            function cargarTabla() {

                $.ajax({
                    url: url_back,
                    type: 'POST',
                    data: {
                        tabla: true
                    },
                    cache: false,
                    success: function(response) {

                        $('#table tbody').html('');
                        if (response) {

                            var data = JSON.parse(response);

                            for (var i = 0; i < data.length; i++) {
                                let u_id = data[i].u_id;
                                let u_nombre = data[i].u_nombre;
                                let creado = data[i].creado;
                                let permisos = data[i].permisos;

                                let html_permisos = ''

                                if (permisos) {
                                    permisos.forEach(element => {
                                        html_permisos += `
                  <span title="${element[2]}" class="badge bg-info">${element[1]}</span>
                  `
                                    });
                                }

                                $('#table tbody').append(`<tr>
                                <td><i class="bx bx-user"></i></td>
                                <td>` + u_nombre + `</td>
                                <td>` + creado + `</td>
                                <td>` + html_permisos + `</td>
                                <td><a class="bt nowrap btn-sm btn-info text-white" onclick="modificar(` + u_id + `)"><i class="bx bx-edit-alt me-2"></i> Modificar</a></td>
                                </tr>`);
                            }
                        }

                    }

                });
            }
            cargarTabla()

            let mdf



            function permisosDisponibles(user) { // cargar los permisos que le pueden asignar/quitar al usuario
                mdf = user
                $('#cargando').show()


                $.ajax({
                    url: url_back,
                    type: 'POST',
                    data: {
                        permisos: true,
                        user: user
                    },
                    cache: false,
                    success: function(response) {


                        $('#list_permisos tbody').html('');
                        if (response) {

                            var data = JSON.parse(response);

                            for (var i = 0; i < data.length; i++) {
                                let id = data[i].id;
                                let categoria = data[i].categoria;
                                let nombre = data[i].nombre;
                                let icono = data[i].icono;
                                let permisos = data[i].permisos;

                                $('#list_permisos tbody').append(`
               <tr data-index="0">
                <td>
                  <div class="d-flex align-items-center">
                    <div class="flex-grow-1 ms-3">
                    <h6 class="mb-0">${(categoria != null ? categoria : '<span title="No definido" class="text-muted">ND</span>')}</h6>
                    </div>
                    </div>
                    </td>
                    <td>${nombre}</td>
                    <td>
                      ${(permisos ? '<a onclick="setPermiso(\''+user+'\', \''+id+'\', '+permisos+')" class="avtar avtar-xs btn-light-success"><i class="bx bx-check f-20"></i>' : '</a><a onclick="setPermiso(\''+user+'\', \''+id+'\', '+permisos+')" class="pointer avtar avtar-xs btn-light-danger"><i class="bx bx-x f-20"></i>')}
                    </td>
                    </tr>
               `)
                            }

                        }
                        $('#cargando').hide()

                    }

                });
            }

            function setPermiso(user, permiso, status) {
                $('#cargando').show()


                $.ajax({
                    url: url_back,
                    type: 'POST',
                    data: {
                        set_permisos: true,
                        user: user,
                        permiso: permiso,
                        status: status
                    },
                    cache: false,
                    success: function(response) {
                        let respuesta = JSON.parse(response)

                        if (respuesta.success) {
                            Alerta.toast('success', respuesta.success)
                            permisosDisponibles(user)
                            cargarTabla()
                        } else {
                            Alerta.toast('error', respuesta.success)
                        }
                        $('#cargando').hide()
                    }
                });

            }


            function modificar(user) {
                permisosDisponibles(user)
                modalContainer.classList.add("active");
            }




            /**
             * Deletes a record with the specified ID.
             * @param {number} id - The ID of the record to be deleted.
             * @returns {boolean} - Returns true if the record is deleted successfully, false otherwise.
             */
            function eliminar(id) {
                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "¡No podrás revertir esto!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#04a9f5",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Sí, eliminarlo!",
                    cancelButtonText: "Cancelar",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url_back,
                            type: "POST",
                            data: {
                                eliminar: true,
                                id: id,
                            },
                            success: function(response) {
                                if (response.trim() == "ok") {
                                    cargarTabla();

                                    Alerta.toast("success", "Eliminado con éxito");
                                } else {
                                    Alerta.toast("error", response);
                                }
                            },
                        });
                    }
                });
            }
        </script>


    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>