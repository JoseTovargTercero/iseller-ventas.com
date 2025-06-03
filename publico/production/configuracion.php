<?php
require_once('includes/requires.php');



if ($_SESSION['nivel'] != '1') {
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


$query2222222 = 'SELECT * FROM mail WHERE id="1"';
$buscarAlumnos2222222 = $conexion->query($query2222222);
if ($buscarAlumnos2222222->num_rows > 0) {
    while ($filaAlumnos2222222 = $buscarAlumnos2222222->fetch_assoc()) {
        $cierre = $filaAlumnos2222222['cierre'];
        $correo = $filaAlumnos2222222['correo'];
    }
}





$query = "SELECT * FROM sistem";
$search = $conexion->query($query);
if ($search->num_rows > 0) {
    while ($rowT = $search->fetch_assoc()) {
        $tickets = $rowT['tickets'];
        $ticketsFijo = $rowT['bsFijoTicket'];
    }
}






if (@$_GET['accion'] == "respaldar") {

    function exportarTablas()
    {
        set_time_limit(3000);
        $tablasARespaldar = [];
        global $conexion;
        $tablas = $conexion->query('SHOW TABLES');
        while ($fila = $tablas->fetch_row()) {
            $tablasARespaldar[] = $fila[0];
        }
        $contenido = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `inventario`\r\n--\r\n\r\n\r\n";
        foreach ($tablasARespaldar as $nombreDeLaTabla) {
            if (empty($nombreDeLaTabla)) {
                continue;
            }
            $datosQueContieneLaTabla = $conexion->query('SELECT * FROM `' . $nombreDeLaTabla . '`');
            $cantidadDeCampos = $datosQueContieneLaTabla->field_count;
            $cantidadDeFilas = $conexion->affected_rows;
            $esquemaDeTabla = $conexion->query('SHOW CREATE TABLE ' . $nombreDeLaTabla);
            $filaDeTabla = $esquemaDeTabla->fetch_row();
            $contenido .= "\n\n" . $filaDeTabla[1] . ";\n\n";
            for ($i = 0, $contador = 0; $i < $cantidadDeCampos; $i++, $contador = 0) {
                while ($fila = $datosQueContieneLaTabla->fetch_row()) {
                    //La primera y cada 100 veces
                    if ($contador % 100 == 0 || $contador == 0) {
                        $contenido .= "\nINSERT INTO " . $nombreDeLaTabla . " VALUES";
                    }
                    $contenido .= "\n(";
                    for ($j = 0; $j < $cantidadDeCampos; $j++) {
                        $fila[$j] = str_replace("\n", "\\n", addslashes($fila[$j]));
                        if (isset($fila[$j])) {
                            $contenido .= '"' . $fila[$j] . '"';
                        } else {
                            $contenido .= '""';
                        }
                        if ($j < ($cantidadDeCampos - 1)) {
                            $contenido .= ',';
                        }
                    }
                    $contenido .= ")";
                    # Cada 100...
                    if ((($contador + 1) % 100 == 0 && $contador != 0) || $contador + 1 == $cantidadDeFilas) {
                        $contenido .= ";";
                    } else {
                        $contenido .= ",";
                    }
                    $contador = $contador + 1;
                }
            }
            $contenido .= "\n\n\n";
        }
        $contenido .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";

        # Se guardará dependiendo del directorio, en una carpeta llamada respaldos
        $carpeta = __DIR__ . "/respaldos";
        if (!file_exists($carpeta)) {
            mkdir($carpeta);
        }

        # Calcular un ID único
        $id = uniqid();

        # También la fecha
        $fecha = date("Y-m-d");

        # Crear un archivo que tendrá un nombre como respaldo_2018-10-22_asd123.sql
        $nombreDelArchivo = sprintf('%s/respaldo_%s.sql', $carpeta, $fecha);

        #Escribir todo el contenido. Si todo va bien, file_put_contents NO devuelve FALSE
        return file_put_contents($nombreDelArchivo, $contenido) !== false;
    }
    exportarTablas();

    $name = "respaldo_" . date("Y-m-d") . ".sql";


    echo '<script>window.open("http://localhost/iseller/publico/production/respaldos/' . $name . '","_blank")</script>';
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

    <title>Configuracion </title>
    <?php require_once('includes/headers.php'); ?>


    <link rel='stylesheet' href='..//assets/AlertifyJS/css/alertify.min.css' />
    <link rel='stylesheet' href='..//assets/AlertifyJS/css/themes/semantic.min.css' />
    <script src='..//assets/AlertifyJS/alertify.min.js'></script>

    <?php
    @$accion = $_GET['accion'];

    switch ($accion) {
        case ('stockCritico'):
            echo '<script>
            function mensaje(){	
			alertify.success("Actualizado el valor minimo de stock critico."); }
            </script>
            <body onload="mensaje()">
            </body>';

            break;
        case ('correo'):
            echo '<script>
            function mensaje(){	
			alertify.success("La configuracion se guardo correctamente."); }
            </script>
            <body onload="mensaje()">
            </body>';


            break;

        case ('notificacion'):
            echo '<script>
            function mensaje(){	
			alertify.success("Actualizado el estado de las notificaciones de stock critico."); }
            </script>
            <body onload="mensaje()">
            </body>';
            break;

        case ('tasas'):
            echo '<script>
            function mensaje(){	
			alertify.success("Se han actualizado las tasas de cambio.");}
            </script>
            <body onload="mensaje()">
            </body>';
            break;

        case ('empresa'):
            echo '<script>
            function mensaje(){	
			alertify.success("Se ha actualizado el nombre de la empresa.");}
            </script>
            <body onload="mensaje()">
            </body>';
            break;
        case ('respaldar'):
            echo '<script>
            function mensaje(){	
			alertify.success("Copia de seguridad generada correctamente.");}
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

                    <h4>Configuracion</h4>
                    <p style="margin-top: -10px;">Configuracion del sistema</p>

                    <div class='clearfix'></div>

                    <div class='row'>





                        <style>
                            .form-control-feedback.right {
                                border-left: 1px solid #ccc;
                                right: 80px !important;
                                padding-left: inherit;
                            }
                        </style>





                        <div class='col-lg-12'>
                            <div class='x_panel'>
                                <div class='x_title'>
                                    <h2>Configuración<small>del sistema</small></h2>
                                    <ul class='nav navbar-right panel_toolbox'>
                                        <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a>
                                        </li>

                                    </ul>
                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content'>






                                    <div class="col-lg-3" <?php echo $permisos; ?> style="text-align: right; margin-top: 10px">Notificaciones de stock crítico. </div>
                                    <div class="col-lg-9" <?php echo $permisos; ?>> <?php
                                                                                    if ($notificacionStockCritico = 0) {
                                                                                        echo '<a href="../../configurar/empresa.php?accion=activar" class=""><img src="images/activado-no.png" height="" alt=""></a>';
                                                                                    } else {
                                                                                        echo '<a href="../../configurar/empresa.php?accion=desactivar" class=""><img src="images/activado.png" height="" alt=""></a>';
                                                                                    }
                                                                                    ?>
                                        <br> <small><br></small>Si desactiva las notificaciones de stock crítico, dejara de recibir alertas cuando un producto se esté agotando. <br> <br> <br>

                                    </div>



                                    <div class="col-lg-3" <?php echo $permisos; ?> style="text-align: right; margin-top: 10px">Cantidad mínima para notificaciones de stock critico.</div>
                                    <div class="col-lg-9" <?php echo $permisos; ?>>
                                        <form action='../../configurar/empresa.php' method='post'>

                                            <div class='input-group'>
                                                <input type='number' class='form-control col-lg-2' name='stockCritico' id='stockCritico' value="<?php echo $stockCritico; ?>">

                                                <button class='btn btn-success right'>Actualizar</button>


                                            </div>
                                            Por debajo del número establecido, comenzara a recibir notificaciones.
                                        </form>

                                    </div>


                                    <div class="col-lg-12">
                                        <hr>
                                        <br>
                                    </div>

                                    <div class="col-lg-3" <?php echo $permisos; ?> style="text-align: right; margin-top: 10px">Impresion de tickets.</div>
                                    <div class="col-lg-9" <?php echo $permisos; ?>>

                                        <?php
                                        if ($tickets = 1) {
                                            echo '<a href="../../configurar/distribuidor.php?accion=activar" class=""><img src="images/activado-no.png" height="" alt=""></a>';
                                        } else {
                                            echo '<a href="../../configurar/distribuidor.php?accion=desactivar" class=""><img src="images/activado.png" height="" alt=""></a>';
                                        }
                                        ?>


                                        <br> <small><br></small> Imprime tickets al realizar una venta. <br> <br>

                                    </div>











                                    <div class="col-lg-3" <?php echo $permisos; ?> style="text-align: right; margin-top: 10px">Imprimir solo en Bolivares</div>
                                    <div class="col-lg-9" <?php echo $permisos; ?>>

                                        <?php
                                        if ($ticketsFijo == 0) {
                                            echo '<a href="../../configurar/bsFijoTicket.php?accion=activar" class=""><img src="images/activado-no.png" height="" alt=""></a>';
                                        } else {
                                            echo '<a href="../../configurar/bsFijoTicket.php?accion=desactivar" class=""><img src="images/activado.png" height="" alt=""></a>';
                                        }
                                        ?>


                                        <br> <small><br></small> Si está desactivado, los ticket se imprimiran en la moneda por la cual este paganado el cliente. <br>

                                    </div>

















                                    <div class="col-lg-12">
                                        <hr>
                                    </div>




                                    <div class="col-lg-3" <?php echo $permisos; ?> style="text-align: right; margin-top: 10px">Copias de seguridad.</div>
                                    <div class="col-lg-9" <?php echo $permisos; ?>>
                                        <a href="?accion=respaldar" class='btn btn-success'>Respaldar. <i class="fa fa-database"></i></a>
                                        <br> <small><br></small> Respalde la base de datos periódicamente y evite perdidas de información. <br> <br> <br>

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