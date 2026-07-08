<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {

    $topnav = topnav();


?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Usuarios </title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">
        <style>
            .right_col {
                background: var(--dash-bg);
                min-height: 100vh;
                padding: 24px 28px !important;
            }

            .page-header {
                margin-bottom: 24px;
            }

            .page-header h4 {
                font-size: 20px;
                font-weight: 700;
                color: var(--dash-text);
                margin: 0;
            }

            .page-header p {
                color: var(--dash-text-muted);
                margin: 2px 0 0;
                font-size: 13px;
            }

           
            .dash-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 13px;
            }

            .dash-table thead th {
                padding: 12px 14px;
                text-align: left;
                font-weight: 600;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: .4px;
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
                padding: 12px 14px;
                color: var(--dash-text);
                vertical-align: middle;
            }

            .btn-dash-new {
                background: linear-gradient(135deg, #2dd4a0, #25b88a);
                border: none;
                color: #fff;
                font-size: 13px;
                font-weight: 600;
                padding: 8px 18px;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all .2s ease;
                box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
                cursor: pointer;
            }

            .btn-dash-new:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
                color: #fff;
            }

            .btn-dash-cancel {
                background: rgba(255, 255, 255, .06);
                border: none;
                color: #fff;
                font-size: 13px;
                font-weight: 600;
                padding: 8px 18px;
                border-radius: 8px;
                transition: all .2s ease;
                cursor: pointer;
            }

            .btn-dash-cancel:hover {
                background: rgba(255, 255, 255, .1);
                color: #fff;
            }

            /* Modal */
            .modal-content {
                background: var(--dash-card) !important;
                border: 1px solid var(--dash-border) !important;
                border-radius: 14px !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
            }

            .modal-header {
                border-bottom: 1px solid var(--dash-border);
                padding: 18px 22px 14px;
            }

            .modal-header .close {
                color: var(--dash-text-muted);
                opacity: .7;
                font-size: 24px;
                transition: opacity .15s ease;
            }

            .modal-header .close:hover {
                opacity: 1;
                color: var(--dash-text);
            }

            .modal-title {
                color: var(--dash-text);
                font-size: 16px;
                font-weight: 600;
            }

            .modal-title small {
                color: var(--dash-text-muted);
                font-weight: 400;
            }

            .modal-body {
                padding: 18px 22px;
            }

            .modal-footer {
                border-top: 1px solid var(--dash-border);
                padding: 14px 22px 18px;
            }

            .modal-backdrop {
                background: rgba(0, 0, 0, .6);
            }

            .modal .form-control {
                background: var(--dash-bg);
                border: 1px solid var(--dash-border);
                color: var(--dash-text);
                border-radius: 8px;
                padding: 9px 14px;
                font-size: 13px;
                transition: border-color .2s ease, box-shadow .2s ease;
            }

            .modal .form-control:focus {
                border-color: var(--dash-mint);
                box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
            }

            .modal select.form-control {
                -webkit-appearance: none;
                appearance: none;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%238892a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 10px center;
                background-size: 14px;
                padding-right: 32px;
            }

            .modal .form-group {
                margin-bottom: 6px;
            }

            .modal .col-form-label {
                color: var(--dash-text-muted);
                font-size: 12px;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: .3px;
                padding-top: 8px;
                padding-bottom: 4px;
            }

            .btn-dash-submit {
                background: linear-gradient(135deg, #2dd4a0, #25b88a);
                border: none;
                color: #fff;
                font-size: 13px;
                font-weight: 600;
                padding: 9px 24px;
                border-radius: 8px;
                transition: all .2s ease;
                box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
                cursor: pointer;
            }

            .btn-dash-submit:hover {
                transform: translateY(-1px);
                box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
                color: #fff;
            }

            .btn-dash-close {
                background: rgba(255, 255, 255, .06);
                border: none;
                color: var(--dash-text-muted);
                font-size: 13px;
                font-weight: 600;
                padding: 9px 24px;
                border-radius: 8px;
                transition: all .2s ease;
                cursor: pointer;
            }

            .btn-dash-close:hover {
                background: rgba(255, 255, 255, .1);
                color: var(--dash-text);
            }

            .swal2-popup {
                background: var(--dash-card) !important;
                border: 1px solid var(--dash-border) !important;
                border-radius: 14px !important;
            }

            .swal2-title {
                color: var(--dash-text) !important;
            }

            .swal2-html-container {
                color: var(--dash-text-muted) !important;
            }

            .swal2-confirm {
                background: linear-gradient(135deg, #2dd4a0, #25b88a) !important;
                border: none !important;
                border-radius: 8px !important;
                box-shadow: 0 3px 12px rgba(45, 212, 160, .25) !important;
            }
        </style>
    </head>
    <div id="preload" style="display: none;">Cargando...</div>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>
                <?php echo $topnav ?>

                <div class='right_col' role='main'>
                 

                    <div class='dash-panel'>
                        <div class='panel-header'>
                            <h2>Usuarios</h2>
                            <button class="btn-dash-new" id="btn-nuevo">
                                <ion-icon name="person-add-outline" style="font-size:16px;"></ion-icon>
                                Nuevo
                            </button>
                        </div>
                        <div class='panel-body'>
                            <div class="table-responsive" style="padding:0 16px 16px;">
                                <table id="tabla-usuarios" class="dash-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre</th>
                                            <th>Usuario</th>
                                            <th>Nivel</th>
                                            <th>Sucursal</th>
                                            <th>Acción</th>
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

        <!-- Modal Nuevo Usuario -->
        <div class="modal fade" id="modal-usuario" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Usuario <small>* Obligatorio</small></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="form-data" method='post' autocomplete="off">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class='col-form-label'>Nombre</label>
                                <input class='form-control' data-validate-length-range='6' data-validate-words='2' name='name' placeholder='' required='required' />
                            </div>

                            <div class="form-group">
                                <label class='col-form-label'>Correo electronico</label>
                                <input class='form-control' type="mail" name='user' placeholder='Correo electronico' required='required' />
                            </div>

                            <div class="form-group">
                                <label class='col-form-label'>Nivel</label>
                                <select name="nivel" required='required' class='form-control' id="nivel">
                                    <option value="2">Estandar</option>
                                    <option value="1">Administrador</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class='col-form-label'>Contraseña</label>
                                        <input class='form-control' type='password' name='password' data-validate-length='6,7,8,9,10,11,12' required='required' />
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class='col-form-label'>Repetir Contraseña</label>
                                        <input class='form-control' type='password' name='password2' data-validate-linked='password' required='required' />
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class='col-form-label'>Sucursal</label>
                                <select name="sucursal_asociada" required='required' class='form-control' id="sucursal_asociada">
                                    <option value="">Seleccione</option>

                                    <?php

                                    $stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ?");
                                    $stmt->bind_param('s', $bss_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo <<<HTML
                                            <option value="{$row['id']}">{$row['nombre']}</option>
                                            HTML;
                                        }
                                    }
                                    $stmt->close();

                                    ?>

                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn-dash-close" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn-dash-submit">Registrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <script src='../build/js/custom.js'></script>
        <script src='js/tablas.js'></script>
                <script src="js/nombre_pagina.js"></script>

        <script>
            document.getElementById('btn-nuevo').addEventListener('click', () => {
                $('#modal-usuario').modal('show');
            });

            $('#modal-usuario').on('hidden.bs.modal', function() {
                document.getElementById('form-data').reset();
            });

            document.getElementById('form-data').addEventListener('submit', function(e) {
                e.preventDefault();

                const formElement = this;
                const formData = new FormData(formElement);

                const debug = false;
                if (debug) {
                    console.log("Formulario enviado con los siguientes datos:");
                    for (let [key, value] of formData.entries()) {
                        console.log(`${key}: ${value}`);
                    }

                    if (!confirm("¿Deseas enviar el formulario con estos datos?")) {
                        Alerta.toast('info', 'Envío cancelado por el usuario (modo debug activo).');
                        return;
                    }
                }

                fetch('../../configurar/agguser.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(text => {
                        console.log("Respuesta cruda del servidor:", text);
                        let json;
                        try {
                            json = JSON.parse(text);
                        } catch (e) {
                            console.error("Error al parsear JSON:", e);

                            Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                            return;
                        }

                        if (json.tipo === 'success') {
                            cargar_tabla()
                            $('#modal-usuario').modal('hide');
                            this.reset();
                        }
                        Alerta.toast(json.tipo, json.mensaje);

                    })

            });


            function cargar_tabla() {
                const loader = new TablaLoader('../../configurar/DatabaseHandler/_DBH-select.php');

                loader.cargar('usuarios', '_usuarios_list').then(data => {

                    console.log(data)

                    if (data) {
                        const tbody = document.querySelector('#tabla-usuarios tbody');
                        tbody.innerHTML = '';
                        let c = 1
                        console.log('data')
                        data.forEach(usuario => {
                            const fila = document.createElement('tr');
                            const btn = (usuario.ubss_id === usuario.id ? '' : `<button data-id='${usuario.id}' class="btn btn-sm btn-danger btn-delete" title="Eliminar Usuario"><i class="fa fa-trash"></i></button>`)
                            fila.innerHTML = `
                                <td>${c++}</td>
                                <td>${usuario.nombre}</td>
                                <td>${usuario.usuario}</td>
                                <td>${usuario.nivel}</td>
                                <td>${usuario.s_nombre}</td>
                                <td>${btn}</td>
                            `;
                            tbody.appendChild(fila);
                        });
                    }
                });
            }
            cargar_tabla()

            document.addEventListener('click', function(event) {
                if (event.target.closest('.btn-delete')) {
                    const id = event.target.closest('.btn-delete').getAttribute('data-id')
                    borrar(id)
                }
            })

            function borrar(id) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará al usuario.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Eliminando...',
                            html: 'Por favor espera...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();

                                const preload = document.getElementById("preload");
                                if (preload) {
                                    preload.style.display = "block";
                                    preload.style.animation = "fadeout 1s ease";
                                }

                                fetch('../../configurar/borrarUsuario.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            id: id
                                        })
                                    })
                                    .then(res => res.text())
                                    .then(text => {
                                        console.log("Respuesta cruda del servidor:", text);

                                        let res;
                                        try {
                                            res = JSON.parse(text);
                                        } catch (e) {
                                            console.error("Error al parsear JSON:", e);
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Respuesta no válida',
                                                text: 'El servidor devolvió una respuesta no válida.'
                                            });
                                            return;
                                        }

                                        if (res.tipo === 'success') {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Eliminado',
                                                text: res.mensaje
                                            }).then(() => {
                                                cargar_tabla()
                                            });
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: res.mensaje
                                            });
                                        }
                                    })
                                    .catch(err => {
                                        console.error('Error al eliminar:', err);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error de conexión',
                                            text: 'No se pudo conectar con el servidor.'
                                        });
                                    });
                            }
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