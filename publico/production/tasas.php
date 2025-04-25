<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');




if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
    }

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }


    if ($_SESSION['nivel'] == 2) {
        $permisos = "hidden";
    } else {
        $permisos = "";
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
            $peso_bolivar = $filaAlumnos['peso_bolivar'];
        }
    }

    $query2 = 'SELECT * FROM empresa WHERE id="1"';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
            $notificacionStockCritico = $filaAlumnos2['notificacionStockCritico'];
            $distribuidor = $filaAlumnos2['distribuidor'];
            $factura = $filaAlumnos2['factura'];
        }
    }

    $query2222222 = 'SELECT * FROM mail WHERE id="1"';
    $buscarAlumnos2222222 = $conexion->query($query2222222);
    if ($buscarAlumnos2222222->num_rows > 0) {
        while ($filaAlumnos2222222 = $buscarAlumnos2222222->fetch_assoc()) {
            $cierre = $filaAlumnos2222222['cierre'];
            $correo = $filaAlumnos2222222['correo'];
        }
    }

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
        <title>Tasas </title>
        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">
        <link href='../vendors/google-code-prettify/bin/prettify.min.css' rel='stylesheet'>
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
        <link href='../vendors/switchery/dist/switchery.min.css' rel='stylesheet'>
        <link href='../vendors/starrr/dist/starrr.css' rel='stylesheet'>
        <link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>
        <link href='../build/css/custom.min.css' rel='stylesheet'>

        <script src='js/jquery.min.js'></script>
        <script src='peticion.js'></script>

        <link rel='stylesheet' href='..//assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='..//assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>

        <?php
        @$accion = $_GET['accion'];

        switch ($accion) {
            case ('tasas'):
                echo '<script>
            function mensaje(){	
			alertify.success("Se agrego correctamente."); }
            </script>
            <body onload="mensaje()">
            </body>';

                break;
            case ('borrado'):
                echo '<script>
            function mensaje(){	
			alertify.success("Se elimino correctamente."); }
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

                        <h4>Tasas de cambio</h4>
                        <p style="margin-top: -10px;">Registro de tasas de cambio</p>


                        <div class='clearfix'></div>



                        <div class='row    fadeInUp animated'>





                            <style>
                                .form-control-feedback.right {
                                    border-left: 1px solid #ccc;
                                    right: 80px !important;
                                    padding-left: inherit;
                                }
                            </style>


                            <script>
                                $(obtener_registros_codigo());

                                function obtener_registros_codigo(rep_codigo) {
                                    $.ajax({
                                            url: 'consulta_NuevaTasa.php',
                                            type: 'POST',
                                            dataType: 'html',
                                            data: {
                                                rep_codigo: rep_codigo
                                            },
                                        })

                                        .done(function(resultado_codigo) {
                                            $("#campos").html(resultado_codigo);
                                        })
                                }

                                $(document).on('change', '#tipoTasa', function() {
                                    var valornombre = $(this).val();
                                    if (valornombre != "") {
                                        obtener_registros_codigo(valornombre);
                                    } else {
                                        obtener_registros_codigo();
                                    }
                                });
                            </script>

                            <div class='col-lg-6'>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Agregar Nueva tasa de cambio</h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>
                                        <form name='calculadora' action='../../configurar/nuevaTasa.php' method='post' id='demo-form2' data-parsley-validate class='form-horizontal form-label-left'>
                                            <div class='x_content'>
                                                <br />
                                                <div class='item form-group'>
                                                    <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Tipo de tasa<span class='required'>*</span>
                                                    </label>
                                                    <div class='col-md-9 col-sm-9'>
                                                        <select class="form-control" name="tipoTasa" id="tipoTasa">
                                                            <option>-- Seleccione --</option>
                                                            <option value="dolares">Dolar/Bolivar</option>
                                                            <option value="bolivares">Peso/Bolivar</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class='item form-group'>
                                                    <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Nombre <span class='required'>*</span>
                                                    </label>
                                                    <div class='col-md-9 col-sm-9 '>
                                                        <input type='text' id='nombre' name='nombre' required='required' class='form-control ' placeholder='Nombre de la tasa de cambio'>
                                                    </div>
                                                </div>
                                                <section id="campos">
                                                </section>
                                                <div class='item form-group'>
                                                    <div class='col-md-12 col-sm-12 '>
                                                        <br>
                                                        <button type='submit' class="btn btn-success actualizar right">Agregar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class='col-lg-6'>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Tasas Activas</h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>

                                        <style>
                                            .icono {
                                                font-size: 28px !important;
                                                color: darkgray !important;
                                            }

                                            .icono:hover {
                                                font-size: 29px !important;
                                                color: gray !important;
                                                cursor: pointer;
                                            }
                                        </style>

                                        <ul class="list-unstyled timeline">




                                            <?php

                                            $query = "SELECT * FROM tasas_dolar";
                                            $buscarAlumnos = $conexion->query($query);
                                            if ($buscarAlumnos->num_rows > 0) {
                                                while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

                                                    $id = $filaAlumnos['id'];
                                                    $cantidad = contar("SELECT COUNT(*) FROM productos WHERE tasaDolar='$id'");
                                                    if ($cantidad == "0") {
                                                        $trash = ' <a href="../../configurar/nuevaTasa.php?idBorrar=' . $id . '&tipo=dolar"><i class="fa fa-trash icono right"></i></a>';
                                                    }
                                                    echo '
            <li>
            <div class="block">
              <div class="tags">
                <a href="" class="tag">
                  <span>DOLAR</span>
                </a>
              </div>
              <div class="block_content">
                <h2 class="title">
                                <a>' . $filaAlumnos['tasa'] . '</a>
                            </h2>
                <div class="byline">
                  <span>' . $cantidad . ' Productos asociados a esta tasa</a>
               ' . $trash . '
                </div>
                <p class="excerpt">Valor de la tasa es: <strong>' . number_format($filaAlumnos['recepcion'], '2', '.', '.') . '</strong> BS </p>
              </div>
            </div>
            </li>
            ';
                                                }
                                            }
                                            echo "
<div class='col-lg-12'>
<div class='ln_solid'></div>
</div>
";

                                            $query2 = "SELECT * FROM tasas_pesos";
                                            $buscarAlumnos2 = $conexion->query($query2);
                                            if ($buscarAlumnos2->num_rows > 0) {
                                                while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {

                                                    $id2 = $filaAlumnos2['id_peso'];
                                                    $cantidad2 = contar("SELECT COUNT(*) FROM productos WHERE TasaPeso='$id2'");
                                                    if ($cantidad2 == "0") {
                                                        $trash2 = ' <a href="../../configurar/nuevaTasa.php?idBorrar=' . $id2 . '&tipo=peso"><i class="fa fa-trash icono right"></i></a>';
                                                    }
                                                    echo '
            <li>
            <div class="block">
              <div class="tags">
                <a href="" class="tag">
                  <span>PESOS</span>
                </a>
              </div>
              <div class="block_content">
                <h2 class="title">
                                <a>' . $filaAlumnos2['tasa_peso'] . '</a>
                            </h2>
                <div class="byline">
                  <span>' . $cantidad2 . ' Productos asociados a esta tasa</a>
               ' . $trash2 . '
                </div>
                <p class="excerpt">El valor de recepción de la tasa es: <strong>' . $filaAlumnos2['recepcion_peso'] . '</strong>, y de publicación: <strong>' . $filaAlumnos2['publicacion_peso'] . '</strong></p>
              </div>
            </div>
            </li>
            ';
                                                }
                                            }








                                            ?>



                                        </ul>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>




            </div>

        </div>

        </div>
        <!-- /page content -->
        <style>
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