<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');

if ($_SESSION['nivel'] == 1) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
    }
    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
    $topnav = topnav();


    $accion = $_GET['accion'] ?? null;
    $usuarioBorrar = $_GET['borrar'] ?? null;

?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Usuarios </title>
        <?php require_once('includes/headers.php'); ?>

        <script src='peticion.js'></script>
        <script src='peticion_codigo_producto.js'></script>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const accion = "<?= $accion ?>";
                const usuarioBorrar = "<?= $usuarioBorrar ?>";

                switch (accion) {
                    case 'borrado':
                        Swal.fire('Éxito', 'Borrado correctamente.', 'success');
                        break;
                    case 'exito':
                        Swal.fire('Éxito', 'Agregado correctamente.', 'success');
                        break;
                    case 'contra':
                        Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
                        break;
                    case 'NOSEPUEDE':
                        Swal.fire('Error', 'No puede eliminar este usuario.', 'error');
                        break;
                    case 'vacio':
                        Swal.fire('Advertencia', 'Los campos no fueron rellenados correctamente.', 'warning');
                        break;
                }

                if (usuarioBorrar) {
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
                                    // Opcional: preload visual propio
                                    const preload = document.getElementById("preload");
                                    if (preload) {
                                        preload.style.display = "block";
                                        preload.style.animation = "fadeout 1s ease";
                                    }

                                    setTimeout(() => {
                                        window.location.href = `../../configurar/borrarUsuario.php?id=${usuarioBorrar}`;
                                    }, 1000); // Tiempo de "carga"
                                }
                            });
                        } else {
                            Swal.fire('Cancelado', 'Acción cancelada.', 'info');
                        }
                    });
                }

            });
        </script>




    </head>
    <div id="preload" style="display: none;">Cargando...</div>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>
                    <div class='left_col scroll-view'>
                        <div class='navbar nav_title' style='border: 0;'>
                            <a href='index.php' class='site_title'>
                                <img src='images/logo1-inv-compact.png' style='max-width:147px; opacity: 0.8'> <span>
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

                            <div class='col-md-6 col-sm-6'>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Nuevo Usuario <small>* Obligatorio</small></h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content altoScroll'>
                                        <form class='' action='../../configurar/agguser.php' method='post' novalidate>
                                            <div class='field item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3  label-align'>Nombre<span class='required'>*</span></label>
                                                <div class='col-md-9 col-sm-9'>
                                                    <input class='form-control' data-validate-length-range='6' data-validate-words='2' name='name' placeholder='' required='required' />
                                                </div>
                                            </div>


                                            <div class='field item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3  label-align'>Nombre de Usuario<span class='required'>*</span></label>
                                                <div class='col-md-9 col-sm-9'>
                                                    <input class='form-control' name='user' placeholder='' required='required' />
                                                </div>
                                            </div>



                                            <div class='field item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3  label-align'>Nivel<span class='required'>*</span></label>
                                                <div class='col-md-9 col-sm-9'>
                                                    <select name="nivel" required='required' class='form-control' id="nivel">
                                                        <option value="2">Estandar</option>
                                                        <option value="1">Administrador</option>
                                                    </select>
                                                </div>
                                            </div>


                                            <div class='field item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3  label-align'>Contraseña<span class='required'>*</span></label>
                                                <div class='col-md-9 col-sm-9'>
                                                    <input class='form-control' type='password' name='password' data-validate-length='6,7,8,9,10,11,12' required='required' />
                                                </div>
                                            </div>
                                            <div class='field item form-group'>
                                                <label placeholder="De 6 a 12 caracteres" class='col-form-label col-md-3 col-sm-3  label-align'>Repetir Contraseña<span class='required'>*</span></label>
                                                <div class='col-md-9 col-sm-9'>
                                                    <input class='form-control' type='password' name='password2' data-validate-linked='password' required='required' />
                                                </div>
                                            </div> <br>

                                            <button class='btn btn-success right'>Registrar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class='col-md-12 col-sm-12'>
                                <div class='x_panel'>
                                    <div class='x_title d-flex justify-content-between'>
                                        <h2>Usuarios</h2>
                                        <button class="btn btn-success">Nuevo</button>
                                    </div>
                                    <div class='x_content altoScroll'>

                                        <?php
                                        $queryUsuarios = "SELECT * FROM usuarios WHERE status = 0";
                                        $resultado = $conexion->query($queryUsuarios);

                                        if ($resultado && $resultado->num_rows > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped table-hover align-middle">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Nombre</th>
                                                            <th>Usuario</th>
                                                            <th>Nivel</th>
                                                            <th>Descripción</th>
                                                            <th>Acción</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($fila = $resultado->fetch_assoc()):
                                                            $nivel = ($fila['nivel'] == '1') ? 'Administrador' : 'Estándar';
                                                            $descnivel = ($fila['nivel'] == '1')
                                                                ? 'Usuario con acceso total al sistema, puede modificar tasas de cambio, nombre de la empresa, reportes, estadísticas y eliminar usuarios.'
                                                                : 'Usuario con permisos definidos por el administrador.';

                                                            $accionelimi = ($fila['id'] == '1')
                                                                ? "?accion=NOSEPUEDE"
                                                                : "?borrar=" . $fila['id'];
                                                        ?>
                                                            <tr>
                                                                <td><?= $fila['id'] ?></td>
                                                                <td><?= htmlspecialchars($fila['nombre']) ?></td>
                                                                <td><?= htmlspecialchars($fila['usuario']) ?></td>
                                                                <td><span class="badge bg-primary"><?= $nivel ?></span></td>
                                                                <td><?= $descnivel ?></td>
                                                                <td>
                                                                    <a href="<?= $accionelimi ?>" class="btn btn-sm btn-danger" title="Eliminar Usuario">
                                                                        <i class="fa fa-trash"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">No hay usuarios disponibles.</div>
                                        <?php endif; ?>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /page content -->

                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        I-SELLER - by <a href=''>Jose Ricardo Tovarg III</a>
                    </div>
                    <div class='clearfix'></div>
                </footer>
                <!-- /footer content -->
            </div>
        </div>
        <style>
            .tag2 {
                margin-left: 40%;
                color: lightblue;
            }

            .tag2:hover {
                color: #1ABB9C;
            }

            .altoScroll {
                height: 375px !important;
                overflow-y: auto;
            }

            .altoScroll::-webkit-scrollbar {
                width: 7px;
                height: 7px;
                background: rgba(88, 115, 254, 0.04)
            }

            .altoScroll::-webkit-scrollbar-thumb {
                background: #03A9F5;
                height: 10px;
                border-radius: 5px;
            }

            .right {
                float: right;
            }

            .green {
                color: #1ABB9C;
                font-size: 28px;
                margin-left: 10px;
            }

            .gray {
                color: indianred;
                font-size: 26px;
                margin-left: 10px;
            }
        </style>

        <script src='https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script>
        <script src='../vendors/validator/multifield.js'></script>
        <script src='../vendors/validator/validator.js'></script>

        <script>
            // initialize a validator instance from the 'FormValidator' constructor.
            // A '<form>' element is optionally passed as an argument, but is not a must
            var validator = new FormValidator({
                'events': ['blur', 'input', 'change']
            }, document.forms[0]);
            // on form 'submit' event
            document.forms[0].onsubmit = function(e) {
                var submit = true,
                    validatorResult = validator.checkAll(this);
                console.log(validatorResult);
                return !!validatorResult.valid;
            };
            // on form 'reset' event
            document.forms[0].onreset = function(e) {
                validator.reset();
            };
            // stuff related ONLY for this demo page:
            $('.toggleValidationTooltips').change(function() {
                validator.settings.alerts = !this.checked;
                if (this.checked)
                    $('form .alert').remove();
            }).prop('checked', false);
        </script>

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
        <script src='../build/js/custom.min.js'></script>

    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>