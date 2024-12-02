<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');


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

    $query = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

            $PesoDolar = $filaAlumnos['pesoDolar'];

            $dolarBolivar = $filaAlumnos['DolarBolivar'];
        }
    }
    $query2 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
        }
    }

    // initializ shopping cart class
    include 'La-carta.php';
    $cart = new Cart;

    $today = date('Y-m-d');




    if (isset($_POST['fechaSolic'])) {
        $semana = $_POST['fechaSolic'];
    }else {
        $semana = date('Y-W');
    }



    $registrosSemana = contar("SELECT COUNT(*) FROM cierres WHERE semana='$semana'");






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





    $totalDolares = 0;
    $query77sa = "SELECT * FROM cierres WHERE semana='$semana' ORDER BY dia asc LIMIT 150";
    $buscarAlumnos77as = $conexion->query($query77sa);
    if ($buscarAlumnos77as->num_rows > 0) {
        while ($filaAlumnos77we = $buscarAlumnos77as->fetch_assoc()) {
       
        $subTotal2 = 0;
        $subTotal2 +=  $filaAlumnos77we['punto'] / $filaAlumnos77we['bolivarDolar'];
        $subTotal2 +=  $filaAlumnos77we['bioPago'] / $filaAlumnos77we['bolivarDolar'];
        $subTotal2 +=  $filaAlumnos77we['efectivo'] / $filaAlumnos77we['bolivarDolar'];
        $subTotal2 +=  $filaAlumnos77we['pesos'] / $filaAlumnos77we['pesoDolar'];
        $subTotal2 +=  $filaAlumnos77we['dolares'];

        $totalDolares += $subTotal2; 
        $totalDolaresNetos += $filaAlumnos77we['dolares'];
        $totalPesos += $filaAlumnos77we['pesos']; 
        $totalesBs += $filaAlumnos77we['punto'] + $filaAlumnos77we['bioPago'] + $filaAlumnos77we['efectivo']; 


        }
    }


    if ($totalDolares < $totalVentasSemana) {
        $down = 'fa-arrow-down';
        $display = 'color: #ff9b9b; opacity: 1';
    }elseif ($totalDolares > $totalVentasSemana) {
        $down = 'fa-question';
        $display = ' opacity: 0';
    }else {
        $display = ' opacity: 0';
        $down = '';
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
        <script src='peticion.js'></script>
        <script src='peticion_producto.js'></script>

        
        <script src='ex/jquery.min.js'></script>
        <script src='ex/bootstrap.min.js'></script>

        
        <script src="../assets/sweetalert.min.js"></script>
        <script src="../assets/sweetalert2.all.min.js"></script>
          </head>

    <body class='nav-md'>





    <style>
      
        button {
            background-color: #32d7c0;
            border: 0;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            color: #fff;
            font-size: 14px;
            padding: 5px;
        }

        .modal-container5 {
            z-index: 99;
            display: flex;
            background-color: rgba(0, 0, 0, 0.3);
            align-items: center;
            justify-content: center;
            position: fixed;
            pointer-events: none;
            opacity: 0;
            top: 0;
            left: 0;
            height: 100vh;
            width: 100vw;
            transition: opacity 0.3s ease;
        }

        .show {
            pointer-events: auto;
            opacity: 1;
        }

        .modal5 {
            background-color: #fff;
            width: 600px;
            max-width: 100%;
            padding: 30px 50px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .modal5 h1 {
            margin: 0;
        }

        .modal5 p {
            opacity: 0.7;
            font-size: 14px;
        }

    </style>


    <div id="modal_container" class="modal-container5">
            <div class="modal5">
                    <h2>Filtrar por semana</h2>
                    <form action="" method="post">
                        <p>
                            <select class="form-control" required='required' name="fechaSolic" onchange="capturar()" division()>
                                <option value="<?php echo date('Y-W') ?>"> <?php echo date('Y-W') ?> (actual)</option>
                                <?php
                                $semanaPasada = date('W') - 1;
                                while ($semanaPasada >= 3) {
                                    echo '<option value="' . date('Y-') . $semanaPasada . '">' . date('Y-') . $semanaPasada . '</option>';
                                    $semanaPasada -= 1;
                                }
                                ?>
                            </select>
                        </p>
                        <button class="btn btn-success">Filtrar</button>
                        <a style="color: white; cursor: pointer" class="btn btn-info" onclick="cerrarModal()">Cerrar</a>
                    </form>
            </div>
        </div>


















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
                                <div class='x_panel ' style="min-height: 500px;">
                                    <div class='x_title'>
                                        <h2>Detalles <small>Semana consultada: <strong><?php echo $semana ?></strong> / Semana actual: <strong><?php echo date('Y-W') ?></strong></small> </h2>
                                        <span style="float: right;">
                                     

                                        <button onclick="mostrarModal()">
                                            <i title="Filtrar por semana" class="fa fa-filter"></i>
                                        </button>

                                    </span>



<script>
       function mostrarModal() {
                modal_container.classList.add('show');
            }

            function cerrarModal() {
                modal_container.classList.remove('show');
            }
</script>




                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>

                                        <style>
                                            h4 {
                                                font-size: 18px !important;
                                                margin-top: 5px;
                                            }
                                        </style>

                                        <div class='row'>


                                            <div class='col-lg-12' style="padding-top: 50px;">


                                                <div class='animated flipInY col-lg-1' style="text-align:center">
                                                </div>




                                                <div class='animated flipInY col-lg-2' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/PUNTO-DE-VENTA.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px">&nbsp; <input type="text" class="form-control" onkeyup="verificarContenido('punto')" id="punto" style="text-align: center;" value="0" placeholder="Punto de venta"> </span>
                                                    </div>
                                                    <h4>PUNTO DE VENTA.</h4>
                                                </div>
                                                <div class='animated flipInY col-lg-2 ' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/BIOPAGO.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px">&nbsp; <input type="text" class="form-control" onkeyup="verificarContenido('biopago')" id="biopago" style="text-align: center;" value="0" placeholder="Bio-Pago"> </span>
                                                    </div>
                                                    <h4>BIOPAGO.</h4>
                                                </div>

                                                <div class='animated flipInY col-lg-2 ' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/EFECTIVO-BOLIVAR.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px">&nbsp; <input type="text" class="form-control" onkeyup="verificarContenido('efectivo')" id="efectivo" style="text-align: center;" value="0" placeholder="Bolivares en efectivo"></span>
                                                    </div>
                                                    <h4>EFECTIVO.</h4>
                                                </div>

                                                <div class='animated flipInY col-lg-2 ' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/EFECTIVO-DOLAR.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px">&nbsp; <input type="text" class="form-control" onkeyup="verificarContenido('dolares')" id="dolares" style="text-align: center;" value="0" placeholder="Dolares"></span>
                                                    </div>
                                                    <h4>DOLARES.</h4>
                                                </div>

                                                <div class='animated flipInY col-lg-2' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/EFECTIVO-PESOS.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px">&nbsp; <input type="text" class="form-control" onkeyup="verificarContenido('pesos')" id="pesos" style="text-align: center;" value="0" placeholder="Pesos"></span>
                                                    </div>
                                                    <h4>PESOS.</h4>
                                                </div>



                                               <input hidden type="text" class="form-control" id="semana" value="<?php echo $semana ?>">



                                            </div>



                                            <div style="width: 100%; margin: 40px 0 0 0; display: flex; ">

                                                <div style="width: 80%; margin: 0 0 0 10%; display: flex;">

                                                    <div style="width: 70%;">
                                                        <select class="form-control" required='required' id="dia" onchange="capturar()" division()>
                                                            <option value="1">Lunes</option>
                                                            <option value="2">Martes</option>
                                                            <option value="3">Miercoles</option>
                                                            <option value="4">Jueves</option>
                                                            <option value="5">Viernes</option>
                                                            <option value="6">Sabado</option>
                                                            <option value="7">Domingo</option>
                                                        </select>


                                                    </div>
                                                    <button style="width: 28%; margin-left: 2%" class="btn btn-success" onclick="saveDiaZ()">Guardar</button>
                                                </div>
                                            </div>
                                            <p style="    float: right;width: 100%;text-align: right;margin-right: 40px;">
<br>
<br> * El registro de ingreso diario quedara anclado a la tasa de cambio configurada actualmente.
</p>

                                            <script>

                                                function verificarContenido(campo) {
                                                    let contenido = $('#'+campo).val();

                                                    if (contenido.indexOf(',') != '-1') {
                                                        contenido = contenido.replaceAll(',', '')
                                                        $('#'+campo).val(contenido);
                                                        alert('1: No utilice separador de miles. 2: Si desea indicar un valor decimal utilice el punto "."')
                                                    }

                                                    var indices = [];
                                                    for(var i = 0; i < contenido.length; i++) {
                                                        if (contenido[i].toLowerCase() === ".") indices.push(i);
                                                    }

                                                    if (indices.length >= 2) {
                                                        $('#'+campo).val(contenido.substring(0, contenido.length - 1));
                                                    }


                                                    contenido = contenido.replace(/[^0-9.]/g, '');
                                                    $('#'+campo).val(contenido);


                                                    
                                                }

                                                function saveDiaZ() {
                                                    let punto = $('#punto').val();
                                                    let biopago = $('#biopago').val();
                                                    let efectivo = $('#efectivo').val();
                                                    let dolares = $('#dolares').val();
                                                    let pesos = $('#pesos').val();
                                                    let semana = $('#semana').val();
                                                    let dia = $('#dia').val();


                                                    if (punto == '' || biopago == '' || efectivo == '' || dolares == '' || pesos == '' || semana == '' || dia == '') {
                                                        
                                                        alert('Campos vacios')
                                                        return;
                                                    }






                                                    $.ajax({
                                                        url: '../../configurar/addCierre.php',
                                                        type: 'POST',
                                                        dataType: 'html',
                                                        data: {
                                                            punto : punto,
                                                            biopago : biopago,
                                                            efectivo : efectivo,
                                                            dolares : dolares,
                                                            pesos : pesos,
                                                            semana : semana,
                                                            dia: dia
                                                        },
                                                        })

                                                        .done(function(resultado) {
                                                            if (resultado.trim() == 'ok') {
                                                                location.reload()
                                                            }else{
                                                                alert(resultado);
                                                            }
                                                        })
                                                }


                                            </script>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class='col-lg-3'>



<div class='x_panel tile'  style="min-height: 500px;">
    <div class='x_title' >
        <h2>Comparativa</h2>
        <div class='clearfix'></div>
    </div>
    <div class='x_content' >



        <div class='col-lg-12'> <br>

            <div class="fila ">
                <div class="col-lg-9">
                    <h5 class="h3edit">BOLIVARES</h5>
                    <span><?php  echo number_format($totalesBs, '2', '.', '.'); ?> - Total de ingresos </span>
                </div>
                <div class="col-lg-3">
                    <div class="icon"><br><img src='images/EFECTIVO-BOLIVAR.png' alt='BOLIVAR'>
                    </div>
                </div>
            </div>




            <div class="fila ">
                <div class="col-lg-9">
                    <h5 class="h3edit">PESOS</h5>
                    <span><?php echo number_format($totalPesos, '0', '.', '.'); ?> - Total de ingresos </span>
                </div>
                <div class="col-lg-3">
                    <div class="icon"><br><img src='images/EFECTIVO-PESOS.png' alt='BOLIVAR'>
                    </div>
                </div>
            </div>


            <div class="fila ">
                <div class="col-lg-9">
                    <h5 class="h3edit">Dolares</h5>
                    <span>$<?php echo number_format($totalDolaresNetos, '0', '.', '.'); ?> - Total de ingresos </span>
                </div>
                <div class="col-lg-3">
                    <div class="icon"><br><img src='images/EFECTIVO-DOLAR.png' alt='BOLIVAR'>
                    </div>

                </div>
                
            </div>


<div class="col-lg-12">
    <br>
<hr>
<br>
</div>


            <div class="fila">
                <div class="col-lg-9">
                    
                    <h5 class="h3edit">


                    <i style="position: absolute; margin-left: -25px; display: block;  color: #ff9b9b" class="fa <?php echo $down ?>"></i>




                    DOLARES</h5>
                    <span>$<?php echo number_format($totalDolares, '2', '.', '.'); ?> / $<?php echo number_format($totalVentasSemana, '2', '.', '.'); ?> </span>
                    <span style="<?php echo $display ?>">/ - $<?php echo number_format($totalVentasSemana - $totalDolares, '2', '.', '.'); ?></span>
                    <p>Conversión a dolares.</p>
                    <p>Total de ingresos declarados por 'Cierres diarios' <strong>($<?php echo number_format($totalDolares, '2', '.', '.'); ?>)</strong> / total de ingresos por el valor de la ventas realizadas <strong>($<?php echo number_format($totalVentasSemana, '2', '.', '.'); ?>)</strong></p>
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




                            <div class='col-lg-12'>
                                <div class='x_panel  ' >
                                    <div class='x_title'>
                                        <h2 style="width: 100%;">Registros de la semana

                                        <span style="float: right; margin-right: 15px"><?php echo $registrosSemana ?>/7</span>
                                        </h2>

                                        <div class='clearfix'></div>
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
                                                                    <th class='column-title'></th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                <?php

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
                                                                        $subTotal +=  $filaAlumnos77['punto'] / $filaAlumnos77['bolivarDolar'];
                                                                        $subTotal +=  $filaAlumnos77['bioPago'] / $filaAlumnos77['bolivarDolar'];
                                                                        $subTotal +=  $filaAlumnos77['efectivo'] / $filaAlumnos77['bolivarDolar'];
                                                                        $subTotal +=  $filaAlumnos77['pesos'] / $filaAlumnos77['pesoDolar'];
                                                                        $subTotal +=  $filaAlumnos77['dolares'];

                                                                        echo '
                                                                        <tr id="row' . $filaAlumnos77['id'] . '" class="even pointer">
                                                                        <td class=" ">' . $contador++ . '</td>
                                                                        <td>' . $diaText . '</td>
                                                                        <td>' . $filaAlumnos77['semana'] . '</td>
                                                                        <td>' .number_format($filaAlumnos77['punto'], '2', ',', '.') . '</td>
                                                                        <td>' . number_format($filaAlumnos77['bioPago'], '2', ',', '.') . ' </td>
                                                                        <td>' . number_format($filaAlumnos77['efectivo'], '2', ',', '.') . ' </td>
                                                                        <td>$' . number_format($filaAlumnos77['dolares'], '2', ',', '.') . ' </td>
                                                                        <td>' . number_format($filaAlumnos77['pesos'], '0', ',', '.') . ' </td>
                                                                        <td>' .number_format($subTotal, '2', ',', '.') . ' </td>

                                                                        <td style="text-align: center"  class="">
                                                              <a style="cursor: pointer" onclick="confirm('.$filaAlumnos77["id"].')"><i class="gray line icon-trash"></i></a>
                                                            </tr>
                                                                        </tr>';

                                                                    }
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
                                                  

                                                  function confirm(id){



                                                            Swal.fire({
                                                                title: 'Esta seguro?',
                                                                html: 'Se eliminara el registro ¿desea continuar?',
                                                                icon: 'question',
                                                                confirmButtonText: 'Eliminar',
                                                                cancelButtonText: 'Cancelar',
                                                                confirmButtonColor: '#32d7c0',
                                                                showCancelButton: true,

                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    elimi(id)
                                                                }
                                                            })



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
                                                               $("#row"+params).hide(300);
                                                           })
   
                                                             
                                              }
                                              </script>

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