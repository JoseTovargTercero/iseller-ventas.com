<?php
require_once('includes/requires.php');

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


    $acciones = [
        'activar' => '1',
        'desactivar' => '0',
        'activarG' => [
            'Inicio' => ['Inicio', 'Clientes'],
            'Ventas' => ['Ventas', 'VentasSemana', 'VentasMes'],
            'Nuevo_Producto' => ['Nueva_Compra', 'Nuevo_Producto'],
            'Control_Stock' => ['Creditos', 'Dejado_Ganar', 'Listado_Productos']
        ],
        'desactivarG' => [
            'Inicio' => ['Inicio', 'Clientes'],
            'Ventas' => ['Ventas', 'VentasSemana', 'VentasMes'],
            'Nuevo_Producto' => ['Nueva_Compra', 'Nuevo_Producto'],
            'Control_Stock' => ['Creditos', 'Dejado_Ganar', 'Listado_Productos']
        ]
    ];

    // Campos individuales
    $campos_individuales = [
        'Inicio',
        'Ventas',
        'VentasSemana',
        'VentasMes',
        'Clientes',
        'Nueva_Compra',
        'Nuevo_Producto',
        'Creditos',
        'Dejado_Ganar',
        'Listado_Productos',
        'DolarToday',
        'Vender',
        'Consultas'
    ];

    // Procesar activar/desactivar
    foreach (['activar', 'desactivar'] as $accion) {
        if (!empty($_GET[$accion]) && in_array($_GET[$accion], $campos_individuales)) {
            $campo = $_GET[$accion];
            $valor = $acciones[$accion];
            $stmt = $conexion->prepare("UPDATE acces SET $campo=? WHERE id='1'");
            $stmt->bind_param('s', $valor);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Procesar activarG/desactivarG (múltiples campos)
    foreach (['activarG', 'desactivarG'] as $accion) {
        if (!empty($_GET[$accion]) && isset($acciones[$accion][$_GET[$accion]])) {
            $campos = $acciones[$accion][$_GET[$accion]];
            $valor = ($accion === 'activarG') ? '1' : '0';

            $sets = implode('=?, ', $campos) . '=?';
            $stmt = $conexion->prepare("UPDATE acces SET $sets WHERE id='1'");

            $params = array_fill(0, count($campos), $valor);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);

            $stmt->execute();
            $stmt->close();
        }
    }




    $query222 = 'SELECT * FROM acces WHERE id="1"';
    $buscarAlumnos222 = $conexion->query($query222);
    if ($buscarAlumnos222->num_rows > 0) {
        while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
            $Inicio = $filaAlumnos222['Inicio'];
            $Ventas = $filaAlumnos222['Ventas'];
            $VentasSemana = $filaAlumnos222['ventasSemana'];
            $VentasMes = $filaAlumnos222['ventasMes'];
            $Clientes = $filaAlumnos222['Clientes'];
            $Nueva_Compra = $filaAlumnos222['Nueva_Compra'];
            $Nuevo_Producto = $filaAlumnos222['Nuevo_Producto'];
            $Creditos = $filaAlumnos222['Creditos'];
            $Dejado_Ganar = $filaAlumnos222['Dejado_Ganar'];
            $Listado_Productos = $filaAlumnos222['Listado_Productos'];
            $DolarToday = $filaAlumnos222['DolarToday'];
            $Vender = $filaAlumnos222['Vender'];
            $Consultas = $filaAlumnos222['Consultas'];
        }
    }






?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>


        <title>Usuarios </title>

        <?php require_once('includes/headers.php'); ?>


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


    </head>

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

                                        <table class="table-personalizada">
                                            <thead>
                                                <tr>
                                                    <th class="mio">Modulo</th>
                                                    <th class="mio">Acceso</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th class="mio3">Incio</th>
                                                    <th class="mio3"><?php if ($Inicio == 1 || $Clientes == 1) {
                                                                            echo "<a href='?desactivarG=Inicio'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                        } else {
                                                                            echo "<a href='?activarG=Inicio'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                        } ?></th>
                                                </tr>
                                                <tr>
                                                    <th class="mio">Inicio</th>
                                                    <th class="mio"><?php if ($Inicio == 1) {
                                                                        echo "<a href='?desactivar=Inicio'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Inicio'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>

                                                <tr>
                                                    <th class="mio">Clientes</th>
                                                    <th class="mio"><?php if ($Clientes == 1) {
                                                                        echo "<a href='?desactivar=Clientes'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Clientes'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>



                                                <tr>
                                                    <th class="mio3">Ventas</th>
                                                    <th class="mio3"><?php if ($Ventas == 1 || $VentasSemana == 1 || $VentasMes == 1) {
                                                                            echo "<a href='?desactivarG=Ventas'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                        } else {
                                                                            echo "<a href='?activarG=Ventas'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                        } ?></th>
                                                </tr>



                                                <tr>
                                                    <th class="mio">Ventas del dia</th>
                                                    <th class="mio"><?php if ($Ventas == 1) {
                                                                        echo "<a href='?desactivar=Ventas'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Ventas'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>
                                                <tr>
                                                    <th class="mio">Ventas de la semana</th>
                                                    <th class="mio"><?php if ($VentasSemana == 1) {
                                                                        echo "<a href='?desactivar=VentasSemana'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=VentasSemana'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>

                                                <tr>
                                                    <th class="mio">Ventas del mes</th>
                                                    <th class="mio"><?php if ($VentasMes == 1) {
                                                                        echo "<a href='?desactivar=VentasMes'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=VentasMes'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>




                                                <tr>
                                                    <th class="mio3">Nuevo Producto</th>
                                                    <th class="mio3"><?php if ($Nueva_Compra == 1 || $Nuevo_Producto == 1) {
                                                                            echo "<a href='?desactivarG=Nuevo_Producto'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                        } else {
                                                                            echo "<a href='?activarG=Nuevo_Producto'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                        } ?></th>
                                                </tr>

                                                <tr>
                                                    <th class="mio">Nueva Compra</th>
                                                    <th class="mio"><?php if ($Nueva_Compra == 1) {
                                                                        echo "<a href='?desactivar=Nueva_Compra'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Nueva_Compra'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>

                                                <tr>
                                                    <th class="mio">Nuevo Producto</th>
                                                    <th class="mio"><?php if ($Nuevo_Producto == 1) {
                                                                        echo "<a href='?desactivar=Nuevo_Producto'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Nuevo_Producto'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>
                                                <tr>
                                                    <th class="mio3">Control de Stock</th>
                                                    <th class="mio3"><?php if ($Creditos == 1 || $Dejado_Ganar == 1 || $Listado_Productos == 1) {
                                                                            echo "<a href='?desactivarG=Control_Stock'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                        } else {
                                                                            echo "<a href='?activarG=Control_Stock'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                        } ?></th>
                                                </tr>


                                                <tr>
                                                    <th class="mio">Creditos</th>
                                                    <th class="mio"><?php if ($Creditos == 1) {
                                                                        echo "<a href='?desactivar=Creditos'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Creditos'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>
                                                <tr>
                                                    <th class="mio">Dejado de Ganar</th>
                                                    <th class="mio"><?php if ($Dejado_Ganar == 1) {
                                                                        echo "<a href='?desactivar=Dejado_Ganar'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Dejado_Ganar'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>
                                                <tr>
                                                    <th class="mio">Listado De Productos</th>
                                                    <th class="mio"><?php if ($Listado_Productos == 1) {
                                                                        echo "<a href='?desactivar=Listado_Productos'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                    } else {
                                                                        echo "<a href='?activar=Listado_Productos'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                    } ?></th>
                                                </tr>

                                                <tr>
                                                    <th class="mio3">Vender</th>
                                                    <th class="mio3"><?php if ($Vender == 1) {
                                                                            echo "<a href='?desactivar=Vender'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                        } else {
                                                                            echo "<a href='?activar=Vender'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                        } ?></th>
                                                </tr>
                                                <tr>
                                                    <th class="mio3">Consultas</th>
                                                    <th class="mio3"><?php if ($Consultas == 1) {
                                                                            echo "<a href='?desactivar=Consultas'><img src='images/ON.PNG' alt='on' height='20px'</a>";
                                                                        } else {
                                                                            echo "<a href='?activar=Consultas'><img src='images/OFF.PNG' alt='off' height='20px'<a/>";
                                                                        } ?></th>
                                                </tr>
                                            </tbody>
                                        </table>
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
            .table-personalizada {
                width: 70%;

            }

            .mio {
                height: 30px;
                border-bottom: 1px solid lightgray;
            }

            .mio3 {
                background-color: #eee;
                height: 30px;
                border-bottom: 1px solid lightgray;
            }

            .tag2 {
                margin-left: 40%;
                color: lightblue;
            }

            .tag2:hover {
                color: #1ABB9C;
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