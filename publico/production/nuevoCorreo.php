<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');


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



    $query2 = 'SELECT * FROM empresa WHERE id="1"';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
            $notificacionStockCritico = $filaAlumnos2['notificacionStockCritico'];
            $distribuidor = $filaAlumnos2['distribuidor'];
        }
    }

    $query2222222 = 'SELECT * FROM mail WHERE id="1"';
    $buscarAlumnos2222222 = $conexion->query($query2222222);
    if ($buscarAlumnos2222222->num_rows > 0) {
        while ($filaAlumnos2222222 = $buscarAlumnos2222222->fetch_assoc()) {
            $cortes = $filaAlumnos2222222['corte'];
            $cierre = $filaAlumnos2222222['cierre'];
            $correo = $filaAlumnos2222222['correo'];
        }
    }






    $stmt = mysqli_prepare($conexion, "SELECT * FROM usuarios");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

            if ($row['nivel'] == '1') {
                $nivel = 'Administrador';
                $descnivel = 'Usuario con acceso total al sistema, posee permisos para modificar tasas de cambio, nombre de la empresa, acceso a reportes y estadisticas de venta, tambien puede eliminar a otros usuarios.';
            } else {
                $nivel = 'Estandar';
                $descnivel = 'Usuario con acceso limitado, con permisos de ventas, registro de productos y nuevas compras.';
            }
            $resultado .= '
            
                                             <li>
                                                <div class="block">
                                                    <div class="tags">
                                                        <a href="" class="tag">
                                                            <span>' . $nivel . '</span>
                                                        </a>
                                                    </div>
                                                    <div class="block_content">
                                                        <h2 class="title">
                                                            <a>' . $row['id'] . ' - ' . $row['nombre'] . '</a>
                                                        </h2>
                                                        <div class="byline">
                                                            <span>' . $row['usuario'] . '
                                                        </div>
                                                        <p class="excerpt">' . $descnivel . '
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>';
        }
    }
    $stmt->close();






?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='icon' href='images/favicon.ico' type='image/ico' />

        <title>Configuracion </title>

        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <!-- Font Awesome -->
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <!-- NProgress -->
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <!-- iCheck -->
        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">
        <!-- bootstrap-wysiwyg -->
        <link href='../vendors/google-code-prettify/bin/prettify.min.css' rel='stylesheet'>
        <!-- Select2 -->
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
        <!-- Switchery -->
        <link href='../vendors/switchery/dist/switchery.min.css' rel='stylesheet'>
        <!-- starrr -->
        <link href='../vendors/starrr/dist/starrr.css' rel='stylesheet'>
        <!-- bootstrap-daterangepicker -->
        <link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>

        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>

        <script src='js/jquery.min.js'></script>
        <script src='peticion.js'></script>
        <script src='peticion_codigo_producto.js'></script>
        <script src='dolar.js'></script>

        <link rel='stylesheet' href='..//assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='..//assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>

        <?php
        @$accion = $_GET['ok'];

        switch ($accion) {
            case ('ok'):
                echo '<script>
            function mensaje(){	
			alertify.success("Correo agrado correctamente."); }
            </script>
            <body onload="mensaje()">
            </body>';

                break;
        }

        ?>

    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>

                    <div class='left_col scroll-view'>
                        <div class='navbar nav_title' style='border: 0;'>
                            <a href='index.html' class='site_title'><img src='images/logo1-inv-compact.png' style='max-width:45px'> <span><img src='images/LETTER.png' style='max-width:140px'><span></a>
                        </div>

                        <div class='clearfix'></div>

                        <!-- menu profile quick info -->
                        <div class='profile clearfix'>
                            <div class='profile_pic'>
                                <img src='images/img.png' alt='...' class='img-circle profile_img'>
                            </div>
                            <div class='profile_info'>
                                <h2><?php echo $nombreUsuario ?></h2>
                                <span><?php if ($nivelUsuario == '1') {
                                            echo 'Administrador';
                                        } else {
                                            echo 'Empleado';
                                        }
                                        ?></span>
                            </div>
                        </div>
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
                        <div class='page-title'>
                            <div class='title_left'>
                                <h3>Correo</h3>
                            </div>


                        </div>
                        <div class='clearfix'></div>

                        <div class='row'>

                            <div class='col-lg-6 center'>

                                <div class='x_panel '>
                                    <div class='x_title'>
                                        <h2>Correo Electronico</h2>
                                        <ul class='nav navbar-right panel_toolbox'>
                                            <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a>
                                            </li>

                                        </ul>
                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>
                                        <br />

                                        <div class='form-group '>
                                            <form action='' method='post'>


                                                <div class='col-lg-12'>
                                                    <h1 style="text-align:center; font-size:158px">
                                                        <img src="images/Sin-t%C3%ADtulo-1.png" class="mail" alt="mail">


                                                    </h1>
                                                    <br>
                                                    <div class='input-group'>
                                                        <input type='email' placeholder="Correo Electrónico" class='form-control' name='correo' id='correo'>
                                                    </div>
                                                    <br>
                                                    <br>

                                                    <p class="favorite ">RECIBIR REPORTE DE CIERRE DE JORNADA&nbsp;&nbsp;<input name="cierre" checked="checked" type="checkbox" class="flat"></p>



                                                </div>

                                                <button class='btn btn-success right'>Siguiente</button>
                                            </form>
                                            <?php

                                            $correoRecibido = $_POST['correo'];

                                            $cierreRE = $_POST['cierre'];

                                            function ValidaCorreo($var)
                                            {
                                                return (filter_var($var, FILTER_VALIDATE_EMAIL)) ? 1 : 0;
                                            }
                                            if (isset($correoRecibido)) {
                                                if (ValidaCorreo($correoRecibido)) {

                                                    if ($cierreRE == "on") {
                                                        $cierreRE = 1;
                                                    }

                                                    $insertar = "INSERT INTO mail (correo, cierre) VALUES ('$correoRecibido','$cierreRE')";
                                                    $resultado2 = mysqli_query($conexion, $insertar);

                                                    if (!$resultado2) {
                                                    } else {


                                                        echo '<script>
			      window.open("paso2.php?ok=ok", "_self");
                    </script>';
                                                    }
                                                } else {
                                                    echo "EL CORREO NO CUMPLE CON LO REQUERIDO";
                                                }
                                            }

                                            ?>
                                        </div>
                                        <div class='ln_solid'></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- /page content -->
                <style>
                    .mail {
                        max-width: 70%;
                        max-height: 70%;
                    }

                    .center {
                        margin: auto;
                    }

                    .right {
                        float: right;
                    }

                    .left {
                        float: left;
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

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- FastClick -->
        <script src='../vendors/fastclick/lib/fastclick.js'></script>
        <!-- NProgress -->
        <script src='../vendors/nprogress/nprogress.js'></script>
        <!-- bootstrap-progressbar -->
        <script src='../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js'></script>
        <!-- Dropzone.js -->
        <script src='../vendors/dropzone/dist/min/dropzone.min.js'></script>

        <!-- iCheck -->
        <script src='../vendors/iCheck/icheck.min.js'></script>
        <!-- bootstrap-daterangepicker -->
        <script src='../vendors/moment/min/moment.min.js'></script>
        <script src='../vendors/bootstrap-daterangepicker/daterangepicker.js'></script>
        <!-- bootstrap-wysiwyg -->
        <script src='../vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js'></script>
        <script src='../vendors/jquery.hotkeys/jquery.hotkeys.js'></script>
        <script src='../vendors/google-code-prettify/src/prettify.js'></script>
        <!-- jQuery Tags Input -->
        <script src='../vendors/jquery.tagsinput/src/jquery.tagsinput.js'></script>
        <!-- Switchery -->
        <script src='../vendors/switchery/dist/switchery.min.js'></script>
        <!-- Select2 -->
        <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
        <!-- Parsley -->
        <script src='../vendors/parsleyjs/dist/parsley.min.js'></script>
        <!-- Autosize -->
        <script src='../vendors/autosize/dist/autosize.min.js'></script>
        <!-- jQuery autocomplete -->
        <script src='../vendors/devbridge-autocomplete/dist/jquery.autocomplete.min.js'></script>
        <!-- starrr -->
        <script src='../vendors/starrr/dist/starrr.js'></script>
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