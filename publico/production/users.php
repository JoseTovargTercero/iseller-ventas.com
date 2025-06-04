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
    </head>
    <div id="preload" style="display: none;">Cargando...</div>

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

                        <h4>Usuarios</h4>
                        <p style="margin-top: -10px;">Registro de usuarios</p>


                        <div class='clearfix'></div>

                        <div class='row   fadeInUp animated'>

                            <div class='col-md-10 col-sm-10 m-auto hide' id="registro_section">
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Nuevo Usuario <small>* Obligatorio</small></h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content '>
                                        <form id="form-data" method='post' autocomplete="off">

                                            <label class='col-form-label '>Nombre</label>
                                            <div class='field item form-group'>
                                                <input class='form-control' data-validate-length-range='6' data-validate-words='2' name='name' placeholder='' required='required' />
                                            </div>


                                            <label class='col-form-label   label-align'>Nombre de Usuario</label>
                                            <div class='field item form-group'>
                                                <input class='form-control' name='user' placeholder='' required='required' />
                                            </div>



                                            <label class='col-form-label  label-align'>Nivel</label>
                                            <div class='field item form-group'>
                                                <select name="nivel" required='required' class='form-control' id="nivel">
                                                    <option value="2">Estandar</option>
                                                    <option value="1">Administrador</option>
                                                </select>
                                            </div>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <label class='col-form-label   label-align'>Contraseña</label>
                                                    <div class='field item form-group'>
                                                        <input class='form-control' type='password' name='password' data-validate-length='6,7,8,9,10,11,12' required='required' />
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <label placeholder="De 6 a 12 caracteres" class='col-form-label  label-align'>Repetir Contraseña</label>
                                                    <div class='field item form-group'>
                                                        <input class='form-control' type='password' name='password2' data-validate-linked='password' required='required' />
                                                    </div>
                                                </div>
                                            </div>

                                            <label class='col-form-label  label-align'>Sucursal</label>
                                            <div class='field item form-group'>
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


                                            <div class="d-flex justify-content-between mt-3">
                                                <button type="button" class='btn btn-danger' id="btn-cancelar">Cancelar</button>
                                                <button type="submit" class='btn btn-success'>Registrar</button>
                                            </div>


                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class='col-md-12 col-sm-12'>
                                <div class='x_panel'>
                                    <div class='x_title d-flex justify-content-between'>
                                        <h2>Usuarios</h2>
                                        <button class="btn btn-success" id="btn-nuevo">Nuevo</button>
                                    </div>
                                    <div class='x_content altoScroll'>


                                        <div class="table-responsive">
                                            <table id="tabla-usuarios" class="table table-striped align-middle">
                                                <thead class="table-dark">
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
                </div>
                <!-- /page content -->

            </div>
        </div>
        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- Custom Theme Scripts -->
        <script src='../build/js/custom.js'></script>
        <script src='js/tablas.js'></script>
        <script>
            ['btn-nuevo', 'btn-cancelar'].forEach(id => {
                document.getElementById(id).addEventListener('click', () => {
                    toggleView('registro_section');
                });
            });


            document.getElementById('form-data').addEventListener('submit', function(e) {
                e.preventDefault();

                const formElement = this; // <- El formulario real
                const formData = new FormData(formElement); // <- El objeto FormData


                // Usar tu función para obtener sucursales seleccionadas con su stock

                // DEBUG
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


                // Envío por fetch
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
                            // Opcional: limpiar el formulario
                            cargar_tabla()
                            toggleView('registro_section')
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
                        tbody.innerHTML = ''; // Limpiar la tabla antes de insertar
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
                                    .then(res => res.text()) // <- Primero como texto
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