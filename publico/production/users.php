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

    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    $query = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

            $PesoDolar = $filaAlumnos['pesoDolar'];
            $DolarBolivar = $filaAlumnos['DolarBolivar'];
            $bolivarPesoTrans = $filaAlumnos['bolivarPesoTrans'];
        }
    }

    $query2 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
            $notificacionStockCritico = $filaAlumnos2['notificacionStockCritico'];
        }
    }


?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Usuarios </title>
        <?php require_once('includes/headers.php'); ?>

        <script src='peticion.js'></script>
        <script src='peticion_codigo_producto.js'></script>

       
        <?php
        @$accion = $_GET['accion'];

        switch ($accion) {

            case ('borrado'):
                echo '<script>
            function mensaje(){	
			alertify.success("Borrado Correctamente."); }
            </script>
            <body onload="mensaje()">
            </body>';

                break;

            case ('exito'):
                echo '<script>
            function mensaje(){	
			alertify.success("Agregado Correctamente."); }
            </script>
            <body onload="mensaje()">
            </body>';
                break;

            case ('contra'):
                echo '<script>
            function mensaje(){	
			alertify.error("Las Contraseñas no coinciden.");}
            </script>
            <body onload="mensaje()">
            </body>';
                break;
            case ('NOSEPUEDE'):
                echo '<script>
            function mensaje(){	
			alertify.error("No puede eliminar este usuario.");}
            </script>
            <body onload="mensaje()">
            </body>';
                break;

            case ('vacio'):
                echo '<script>
            function mensaje(){	
			alertify.error("Los campos no fuero rellenados correctamente.");}
            </script>
            <body onload="mensaje()">
            </body>';
                break;
        }

        @$usuarioBorrar = $_GET['borrar'];

        $url = '../../configurar/borrarUsuario.php?id=' . $usuarioBorrar;


        if ($usuarioBorrar) {
            echo '<body onload="confirmar()"></body>';
        }
        ?>

        <script type="text/javascript">
            function confirmar() {
                var confirm = alertify.confirm('Eliminar Usuario', 'Se borrara un usuario del sistema, desea continuar?', null, null).set('labels', {
                    ok: 'Confirmar',
                    cancel: 'Cancelar'
                });

                //callbak al pulsar botón positivo
                confirm.set('onok', function() {
                    alertify.success('Eliminando');
                    (function() {


                        var preload = document.getElementById("preload");
                        var loading = 0;
                        var id = setInterval(frame, 20);

                        function frame() {
                            if (loading == 100) {
                                clearInterval(id);
                                window.open("<?php echo $url; ?>", "_self");
                            } else {
                                loading = loading + 1;
                                if (loading == 90) {
                                    preload.style.animation = "fadeout 1s ease";
                                }
                            }
                        }
                    })();
                });
                //callbak al pulsar botón negativo
                confirm.set('oncancel', function() {
                    alertify.error('Accion Cancelada');
                })

            }
        </script>








    </head>

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

                            <div class='col-md-6 col-sm-6  '>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Usuarios </h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content altoScroll'>
                                        <ul class='list-unstyled timeline'>

                                            <?php









                                            $query222 = 'SELECT * FROM usuarios WHERE status=0';
                                            $buscarAlumnos222 = $conexion->query($query222);
                                            while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
                                                if ($filaAlumnos222['nivel'] == '1') {
                                                    $nivel = 'Administrador';
                                                    $descnivel = 'Usuario con acceso total al sistema, posee permisos para modificar tasas de cambio, nombre de la empresa, acceso a reportes y estadisticas de venta, tambien puede eliminar a otros usuarios.';
                                                } else {
                                                    $nivel = 'Estandar';
                                                    $descnivel = 'Usuario con permisos definidos por el administrador.';
                                                }

                                                if ($filaAlumnos222['id'] == "1") {
                                                    $accionelimi = "?accion=NOSEPUEDE";
                                                } else {
                                                    $accionelimi = "?borrar=" . $filaAlumnos222['id'] . "";
                                                }

                                                echo '
        
                                         <li>
                                            <div class="block">
                                                <div class="tags">
                                                    <a href="" class="tag">
                                                        <span>' . $nivel . '</span>
                                                    </a>
                                                    
                                                    
                                                    <a href="' . $accionelimi . '" class="tag2">
                                                    
                                                    
                                                    
                                                    
                                                    
                                                        <span style="font-size: 35px;"><i class="fa fa-trash"></i></span>
                                                    </a>
                                                    
                                                </div>
                                                <div class="block_content">
                                                    <h2 class="title">
                                                        <a>' . $filaAlumnos222['id'] . ' - ' . $filaAlumnos222['nombre'] . '</a>
                                                    </h2>
                                                    <div class="byline">
                                                        <span>' . $filaAlumnos222['usuario'] . '
                                                    </div>
                                                    <p class="excerpt">' . $descnivel . '
                                                    </p>
                                                </div>
                                            </div>
                                        </li>
       
                 ';
                                            }
















                                            ?>

                                        </ul>

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