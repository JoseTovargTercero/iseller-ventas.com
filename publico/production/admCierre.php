<?php
require_once('includes/requires.php');



if ($_SESSION['nivel'] == 1) {


    if (!$_GET['mesConsulta']) {
        $mesConsulta = date('Y-m');
    } else {
        $mesConsulta = date('Y') . '-' . $_GET['mesConsulta'];
    }


    $topnav = topnav();

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


        <?php require_once 'includes/headers.php' ?>

    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>
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

                </div>
            </div>

            <!-- jQuery -->
            <script src='../vendors/jquery/dist/jquery.min.js'></script>
            <!-- Bootstrap -->
            <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>

            <!-- DataTables core -->
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
            <!-- Buttons extension -->
            <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
            <!-- PDF export -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

            <!-- Custom Theme Scripts -->
            <script src='../build/js/custom.js'></script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>