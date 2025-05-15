<?php
require_once('includes/requires.php');



if ($_SESSION['nivel'] == 1) {


    if (!$_GET['mesConsulta']) {
        $mesConsulta = date('Y-m');
    } else {
        $mesConsulta = date('Y') . '-' . $_GET['mesConsulta'];
    }


    $topnav = topnav();

    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Ventas == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    if ($_SESSION['validate'] != 'ok') {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];


    $today = date('Y-m-d');




    if (isset($_POST['fechaSolic'])) {
        $semana = $_POST['fechaSolic'];
    } else {
        $semana = date('Y-W');
    }

    $semanaPasada = date('W') - 1;
    $semanaAntePasada = date('W') - 2;

    $semanaPasada = date('Y') . '-' . $semanaPasada;
    $semanaAntePasada = date('Y') . '-' . $semanaAntePasada;




    $registrosSemana = contar("SELECT COUNT(*) FROM cierres WHERE semana='$semana'");
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////Datos de ventas//////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    $semana = date('Y-W');
    $query222 = "SELECT * FROM orden WHERE semana='$semana' AND status='1' OR semana='$semana' AND status='4'";
    $buscarAlumnos222 = $conexion->query($query222);
    if ($buscarAlumnos222->num_rows > 0) {
        while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
            $VentasSemana = $filaAlumnos222['total_price'];
            $totalVentasSemana += $VentasSemana;
        }
    } else {
        $totalVentasSemana = 0;
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

        <title>Cierres diarios</title>

        <!-- Bootstrap -->
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
        <link href='../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css' rel='stylesheet'>
        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>
        <script src='js/jquery.min.js'></script>
        <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>
        <script src='ex/jquery.min.js'></script>
        <script src='ex/bootstrap.min.js'></script>

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

                        <h4>Ventas</h4>
                        <p style="margin-top: -10px;">Dinero ingresado</p>

                        <div class='clearfix'></div>

                        <div class='row   fadeInUp animated'>









                            <div class='col-lg-9'>
                                <div class='x_panel  '>
                                    <div class='x_title'>
                                        <h2 style="width: 100%;">
                                            <span style="margin-top: 5px !important;position: absolute;">Registros de la semana</span>
                                            <form action="" method="post" class='nav navbar-right panel_toolbox'>
                                                <li>
                                                    <select class="form-control control2" required='required' name="fechaSolic" onchange="capturar()" division()>
                                                        <option value="<?php echo date('Y-W') ?>">Semana actual</option>
                                                        <option value="<?php echo $semanaPasada ?>">Semana pasada</option>
                                                        <option style="display: none;" value="<?php echo $semanaAntePasada ?>">Semana antepasada</option>
                                                    </select>

                                                </li>

                                                <li style="margin-left: 5px;"><input type="submit" class="btn btn-success info2" value="Filtrar"></li>
                                            </form>

                                        </h2>

                                        <div class='clearfix'></div>
                                        <span>Total de registro: <?php echo $registrosSemana ?>/7</span>

                                        <div class='x_content' style="margin-top: 50px;">

                                            <div class="row">
                                                <div class='col-lg-12'>
                                                    <div class='card-box table-responsive'>

                                                        <table id="datatable" class='table table-striped table-bordered' style='width:100%;'>
                                                            <thead>
                                                                <tr class='headings'>
                                                                    <th class='column-title'>#</th>
                                                                    <th class='column-title'>Dia</th>
                                                                    <th class='column-title'>Semana</th>
                                                                    <th class='column-title'>Punto</th>
                                                                    <th class='column-title'>BioPago</th>
                                                                    <th class='column-title'>Efectivo (bs)</th>
                                                                    <th class='column-title'>Dolares</th>
                                                                    <th class='column-title'>Pesos</th>
                                                                    <th class='column-title'>SubTotal ($)</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                <?php
                                                                $totalDolares = 0;
                                                                $query77 = "SELECT * FROM cierres WHERE semana='$semana' ORDER BY dia asc LIMIT 150";
                                                                $buscarAlumnos77 = $conexion->query($query77);
                                                                if ($buscarAlumnos77->num_rows > 0) {
                                                                    $contador = 1;
                                                                    while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                                                        switch ($filaAlumnos77['dia']) {

                                                                            case ('1'):
                                                                                $diaText = 'Lunes';
                                                                                break;
                                                                            case ('2'):
                                                                                $diaText = 'Martes';
                                                                                break;
                                                                            case ('3'):
                                                                                $diaText = 'Miercoles';
                                                                                break;
                                                                            case ('4'):
                                                                                $diaText = 'Jueves';
                                                                                break;
                                                                            case ('5'):
                                                                                $diaText = 'Viernes';
                                                                                break;
                                                                            case ('6'):
                                                                                $diaText = 'Sabado';
                                                                                break;
                                                                            case ('7'):
                                                                                $diaText = 'Domingo';
                                                                                break;
                                                                        }
                                                                        $subTotal = 0;
                                                                        $subTotal +=  $filaAlumnos77['punto'] * $filaAlumnos77['bolivarDolar'];
                                                                        $subTotal +=  $filaAlumnos77['bioPago'] * $filaAlumnos77['bolivarDolar'];
                                                                        $subTotal +=  $filaAlumnos77['efectivo'] * $filaAlumnos77['bolivarDolar'];
                                                                        $subTotal +=  $filaAlumnos77['pesos'] / $filaAlumnos77['pesoDolar'];
                                                                        $subTotal +=  $filaAlumnos77['dolares'];
                                                                        $totalDolares += $subTotal;
                                                                        $totalPesos += $filaAlumnos77['pesos'] / $filaAlumnos77['pesoDolar'];
                                                                        $totalesBs += ($filaAlumnos77['punto'] * $filaAlumnos77['bolivarDolar']) + ($filaAlumnos77['bioPago'] * $filaAlumnos77['bolivarDolar']) + $filaAlumnos77['efectivo'] * $filaAlumnos77['bolivarDolar'];



                                                                        echo '
                                                                        <tr id="row' . $filaAlumnos77['id'] . '" class="even pointer">
                                                                        <td class=" ">' . $contador++ . '</td>
                                                                        <td>' . $diaText . '</td>
                                                                        <td>' . $filaAlumnos77['semana'] . '</td>
                                                                        <td>' . number_format($filaAlumnos77['punto'], '2', ',', '.') . '</td>
                                                                        <td>' . number_format($filaAlumnos77['bioPago'], '2', ',', '.') . ' </td>
                                                                        <td>' . number_format($filaAlumnos77['efectivo'], '2', ',', '.') . ' </td>
                                                                        <td>$' . number_format($filaAlumnos77['dolares'], '2', ',', '.') . ' </td>
                                                                        <td>' . number_format($filaAlumnos77['pesos'], '0', ',', '.') . ' </td>
                                                                        <td>' . number_format($subTotal, '2', ',', '.') . ' </td>
                                                                  
                                                                        </tr>';
                                                                    }
                                                                }




                                                                if ($totalVentasSemana < $totalDolares) {
                                                                    $up = 'display: none';
                                                                    $down = 'display: block;  color: #ff9b9b';
                                                                } else {
                                                                    $up = 'display: block';
                                                                    $down = 'display: none;';
                                                                }



                                                                ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <script>
                                    function confirm(id) {
                                        var confirm = alertify.confirm('Eliminar', 'Se eliminara el producto ¿desea continuar?', null, null).set('labels', {
                                            ok: 'Confirmar',
                                            cancel: 'Cancelar'
                                        });
                                        //callbak al pulsar botón positivo
                                        confirm.set('onok', function() {

                                            elimi(id)

                                        });

                                    }


                                    function elimi(params) {
                                        $.ajax({
                                                url: '../../configurar/deleteCieAjax.php',
                                                type: 'POST',
                                                dataType: 'html',
                                                data: {
                                                    id: params
                                                },
                                            })

                                            .done(function(resultado1) {
                                                $("#row" + params).hide(300);
                                            })


                                    }
                                </script>

                            </div>



                            <div class='col-lg-3'>



                                <div class='x_panel tile'>
                                    <div class='x_title'>
                                        <h2>
                                            Comparativa
                                        </h2>
                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>



                                        <div class='col-lg-12'> <br>

                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">BOLIVARES</h5>
                                                    <span><?php $sumaBolivar = $total1111111 + $total222222 + $total33333333 + $total4444444 + $total7777777 + $totalBs;
                                                            echo number_format($sumaBolivar, '2', '.', '.'); ?> - Total de ventas </span>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br><img src='images/EFECTIVO-BOLIVAR.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>





                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">DOLARES</h5>
                                                    <span><?php echo number_format($total55555 + $dolares, '2', '.', '.'); ?> - Total de ventas </span>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br><img src='images/EFECTIVO-DOLAR.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>





                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">PESOS</h5>
                                                    <span><?php echo number_format($total666666 + $pesos, '0', '.', '.'); ?> - Total de ventas </span>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br><img src='images/EFECTIVO-PESOS.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>





                                            <div class="fila  ">
                                                <div class="col-lg-9">

                                                    <h5 class="h3edit">


                                                        <i style="position: absolute; margin-left: -25px; <?php echo $up ?>" class="fa fa-check"></i>
                                                        <i style="position: absolute; margin-left: -25px; <?php echo $down ?>" class="fa fa-arrow-down"></i>




                                                        DOLARES
                                                    </h5>
                                                    <span>$ <?php echo number_format($totalDolares, '2', '.', '.'); ?> / $ <?php echo number_format($totalVentasSemana, '2', '.', '.'); ?> </span>
                                                    <p>Conversión a dolares.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br>
                                                        <i class="line icon-reload"></i>
                                                    </div>
                                                </div>
                                            </div>




                                        </div>

                                        <style>
                                            .iconPerso {
                                                font-size: 28px !important;
                                            }

                                            .tile-stats {
                                                box-shadow: none !important;
                                            }

                                            .control2 {
                                                max-width: 170px !important;
                                                border: none;
                                                margin-bottom: 0 !important;
                                            }

                                            .info2 {
                                                max-height: 50px !important;
                                                opacity: 0.4
                                            }

                                            .info2:hover {
                                                opacity: 1
                                            }

                                            .subg {
                                                color: #BAB8B8;
                                                font-size: 12px !important;
                                                margin-left: 0 !important;
                                                margin-top: -5 !important;
                                            }
                                        </style>

                                    </div>
                                </div>
                            </div>


                            <div class='row' style='display: block;'>


                            </div>
                        </div>
                    </div>
                    <!-- /page content -->

                    <!-- footer content -->
                    <footer>
                        <div class='pull-right'>
                            I-SELLER - by <a href='#'>Jose Ricardo Tovarg III</a>
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
            <!-- iCheck -->
            <script src='../vendors/iCheck/icheck.min.js'></script>
            <!-- Datatables -->
            <script src='../vendors/datatables.net/js/jquery.dataTables.min.js'></script>
            <script src='../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js'></script>
            <script src='../vendors/datatables.net-buttons/js/dataTables.buttons.min.js'></script>
            <script src='../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js'></script>
            <script src='../vendors/datatables.net-buttons/js/buttons.flash.min.js'></script>
            <script src='../vendors/datatables.net-buttons/js/buttons.html5.min.js'></script>
            <script src='../vendors/datatables.net-buttons/js/buttons.print.min.js'></script>
            <script src='../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js'></script>
            <script src='../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js'></script>
            <script src='../vendors/datatables.net-responsive/js/dataTables.responsive.min.js'></script>
            <script src='../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js'></script>
            <script src='../vendors/datatables.net-scroller/js/dataTables.scroller.min.js'></script>
            <script src='../vendors/jszip/dist/jszip.min.js'></script>
            <script src='../vendors/pdfmake/build/pdfmake.min.js'></script>
            <script src='../vendors/pdfmake/build/vfs_fonts.js'></script>

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