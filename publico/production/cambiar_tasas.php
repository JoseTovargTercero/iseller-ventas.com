<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');



if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Nuevo_Producto == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }


    $topnav = topnav();
    $nivelUsuario = $_SESSION['nivel'];






    $query2 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }
    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }

    $query2255 = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos225 = $conexion->query($query2255);
    if ($buscarAlumnos225->num_rows > 0) {
        while ($filaAlumnos225 = $buscarAlumnos225->fetch_assoc()) {
            $pesoDolar = $filaAlumnos225['pesoDolar'];
            $bolivarPesoTrans = $filaAlumnos225['bolivarPesoTrans'];
            $bsDolar = $filaAlumnos225['DolarBolivar'];
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

        <title>Agregar Producto</title>

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
        <script src='peticion_codigo.js'></script>
        <link rel='stylesheet' href='..//assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='..//assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>

        <?php
        @$registrado = $_GET['agregado'];
        switch ($registrado) {
            case ('correcto'):
                echo '<script>
            function mensaje(){	
			alertify.success("El producto se agrego correctamente.");}
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

                        <h4>Tasas de cambio</h4>
                        <p style="margin-top: -10px;">Configuración de las tasas de cambio</p>


                        <div class='clearfix'></div>



                        <div class='row'>
                            <div class='col-lg-6 '>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2> Tasas de cambio</h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>










                                        <?php
                                        $query = "SELECT * FROM cambio WHERE id='1'";
                                        $buscarAlumnos = $conexion->query($query);
                                        if ($buscarAlumnos->num_rows > 0) {
                                            while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
                                                $DolarBolivar = $filaAlumnos['DolarBolivar'];
                                                $pesoDolar = $filaAlumnos['pesoDolar'];
                                                $tipo_tasa_bs = $filaAlumnos['tipo_tasa_bs'];
                                                $margen_neto = $filaAlumnos['margen_neto'];
                                                $redondeo = $filaAlumnos['redondeo'];
                                            }
                                        }

                                        if ($redondeo == 0) {
                                            $options_2 = '
                                            <option value="0">Ninguno</option>
                                            <option value="1">Entero mas cercano (+ .5)</option>
                                            <option value="2">Entero mas cercano (- .5)</option>';
                                        } elseif ($redondeo == 1) {
                                            $options_2 = '
                                            <option value="1">Entero mas cercano (+ .5)</option>
                                            <option value="2">Entero mas cercano (- .5)</option>
                                            <option value="0">Ninguno</option>';
                                        } else {
                                            $options_2 = '
                                            <option value="2">Entero mas cercano (- .5)</option>
                                            <option value="1">Entero mas cercano (+ .5)</option>
                                            <option value="0">Ninguno</option>';
                                        }

                                        if ($tipo_tasa_bs == 1) {
                                            $display_tasa = '';
                                            $display_marg = 'display: none';

                                            $display = 'display: none';
                                            $options = '
                                            <option value="1">Tasa manual</option>
                                            <option value="2">Tasa BCV</option>
                                            <option value="3">Tasa BCV + Margen neto</option>';
                                        } elseif ($tipo_tasa_bs == 2) {
                                            $display_tasa = 'display: none';
                                            $display_marg = 'display: none';

                                            $display = 'display: none';
                                            $options = '
                                            <option value="2">Tasa BCV</option>
                                            <option value="1">Tasa manual</option>
                                            <option value="3">Tasa BCV + Margen neto</option>';
                                        } else {
                                            $display_tasa = 'display: none';
                                            $display_marg = '';




                                            $display = '';
                                            $options = '
                                            <option value="3">Tasa BCV + Margen neto</option>
                                            <option value=1"">Tasa manual</option>
                                            <option value="2">Tasa BCV</option>';
                                        }

                                        ?>
                                        <form action="../../configurar/tasas.php" method="post">
                                            <div class="mb-3">
                                                <label class="form-label">Tipo de tasa (BS)</label>
                                                <select name="tipoTasa" class="form-control" onchange="tipoCambio(this.value)">
                                                    <?php echo $options ?>
                                                </select>
                                            </div>

                                            <div class="mb-3" id="section_tasa_bs" style="<?php echo $display_tasa ?>">
                                                <label class="form-label">Valor del cambio</label>
                                                <input value="<?php echo $DolarBolivar ?>" name="bolivar" class="form-control">
                                            </div>

                                            <div class="mb-3" id="section_margen" style="<?php echo $display_marg ?>">
                                                <label class="form-label">Margen neto</label>
                                                <input value="<?php echo $margen_neto ?>" name="margen" class="form-control">
                                            </div>

                                            <div class="mb-3" id="section_redondeo" style="<?php echo $display ?>">
                                                <label class="form-label">Tipo de redondeo</label>
                                                <select name="redondeo" class="form-control">
                                                    <?php echo $options_2 ?>
                                                </select>
                                            </div>
                                            <hr class="mt-3 mb-3">
                                            <div class="mb-3">
                                                <label class="form-label">Pesos/Dolar</label>
                                                <input value="<?php echo $pesoDolar ?>" name="peso" class="form-control">
                                            </div>
                                            <hr class="mt-3 mb-3">
                                            <div class="mb-3">
                                                <label class="form-label">Pesos/Bolívar</label>
                                                <input value="<?php echo $bolivarPesoTrans ?>" name="bolivarPesoTrans" class="form-control">
                                            </div>


                                            <div class="pt-3 d-flex justify-content-between">
                                                <button class="btn btn-success">Actualizar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>



                            <script>
                                function tipoCambio(value) {



                                    if (value == 2) {
                                        $('#section_redondeo').hide(300)
                                        $('#section_tasa_bs').hide()
                                        $('#section_margen').hide(300)

                                    } else if (value == 3) {
                                        $('#section_redondeo').show(300)
                                        $('#section_tasa_bs').hide()
                                        $('#section_margen').show(300)

                                    } else {
                                        $('#section_tasa_bs').show(300)
                                        $('#section_margen').hide()
                                        $('#section_redondeo').hide()
                                    }
                                }
                            </script>

                        </div>



                    </div>



                </div>
                <!-- /page content -->

                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
                    </div>
                    <div class='clearfix'></div>
                </footer>
                <!-- /footer content -->
            </div>
        </div>

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'>
        </script>
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'>
        </script>
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