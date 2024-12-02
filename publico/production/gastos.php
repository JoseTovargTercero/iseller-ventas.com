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
        if ($Inicio == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    $topnav = topnav();
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];



    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }




    $query2 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
        }
    }
    function retornarMes($fecha)
    {
        $explodeFecha = explode('-', $fecha);


        $dias = ($explodeFecha[1] * 7) * 86400;

        if ($explodeFecha[1] == date('W')) {
            $diasSemana = (7 - date('N')) * 86400;
            $dias = $dias - $diasSemana;
        }

        $pr = strtotime($explodeFecha[0] . '-01-01');
        $pr += $dias;

        return date('Y-m', $pr);
    }


    if (isset($_POST['fechaSolic'])) {
        $semana = $_POST['fechaSolic'];
        $mes =  retornarMes($semana);
    } else {
        $semana = date('Y-W');
        $mes = date('Y-m');
    }




    $querysas = "SELECT * FROM gastos WHERE mes='$mes'";
    $buscarAlumnossas = $conexion->query($querysas);
    if ($buscarAlumnossas->num_rows > 0) {
        while ($filaAlumnosasd = $buscarAlumnossas->fetch_assoc()) {
            $gastosMes += $filaAlumnosasd['importe'];
        }
    } else {
        $gastosMes = '0';
    }



    $query = "SELECT * FROM gastos WHERE semana='$semana'";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
            $gastosSemana += $filaAlumnos['importe'];
        }
    } else {
        $gastosSemana = '0';
    }







    ///////////////////GANANCIAS DE LA SEMANA/////////////////////

    $query22222 = "SELECT * FROM orden WHERE semana='$semana' AND status='1' OR semana='$semana' AND status='4'";
    $buscarAlumnos22222 = $conexion->query($query22222);
    if ($buscarAlumnos22222->num_rows > 0) {
        while ($filaAlumnos22222 = $buscarAlumnos22222->fetch_assoc()) {
            $Venta = $filaAlumnos22222['id'];
            $VentasSe += $filaAlumnos22222['total_price'];

            $query222222 = "SELECT * FROM orden_articulos WHERE order_id='$Venta'";
            $buscarAlumnos222222 = $conexion->query($query222222);
            if ($buscarAlumnos222222->num_rows > 0) {
                while ($filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc()) {
                    $VentaPrducto = $filaAlumnos222222['product_id'];
                    $quantity14 = $filaAlumnos222222['quantity'];

                    $precioPrducto = number_format($filaAlumnos222222['precio'], '2', '.', '.');
                    $precioNeto = $precioPrducto * $quantity14;

                    $precioTotal += $precioNeto;
                }
            }
            $gananciasSe = $VentasSe - $precioTotal;
        }
    }



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////GANANCIAS DEL mes//////////////////////////
    $query2222234 = "SELECT * FROM orden WHERE fecha='$mes' AND status='1' OR fecha='$mes' AND status='4'";
    $buscarAlumnos2222234 = $conexion->query($query2222234);
    if ($buscarAlumnos2222234->num_rows > 0) {
        while ($filaAlumnos2222234 = $buscarAlumnos2222234->fetch_assoc()) {
            $Venta24 = $filaAlumnos2222234['id'];
            $VentasSe24 += $filaAlumnos2222234['total_price'];

            $query2222223344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta24'";
            $buscarAlumnos2222223344 = $conexion->query($query2222223344);
            if ($buscarAlumnos2222223344->num_rows > 0) {
                while ($filaAlumnos2222223344 = $buscarAlumnos2222223344->fetch_assoc()) {
                    $VentaPrducto24 = $filaAlumnos2222223344['product_id'];

                    $quantity154 = $filaAlumnos2222223344['quantity'];

                    $precioPrducto24 = number_format($filaAlumnos2222223344['precio'], '2', '.', '.');

                    $precioNeto24 = $precioPrducto24 * $quantity154;
                    $precioTotal24 += $precioNeto24;
                }
            }
            $gananciasMes = $VentasSe24 - $precioTotal24;
        }
    }



    $gananciaNetaSemana = $gananciasSe - $gastosSemana;
    $gananciaNetaMes = $gananciasMes - $gastosMes;




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

        <title>Gastos </title>


        <!-- Bootstrap -->
        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <!-- Font Awesome -->
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <!-- NProgress -->
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <!-- iCheck -->
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
        <link href='../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css' rel='stylesheet'>
        <!-- JQVMap -->
        <link href='../vendors/jqvmap/dist/jqvmap.min.css' rel='stylesheet' />
        <!-- bootstrap-daterangepicker -->
        <link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>
        <link href="js/jquerysctipttop.css" rel="stylesheet" type="text/css">
        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>


        <script src="../assets/sweetalert.min.js"></script>
        <script src="../assets/sweetalert2.all.min.js"></script>

        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">

        <link href="assets/chart/css/jquerysctipttop.css" rel="stylesheet" type="text/css">
        <link href="assets/chart/plugins/morris/morris.css" rel="stylesheet" />
        <!-- 
	<link rel="stylesheet" href="../../iseller.es/css/magnific-popup.css">

	<link rel="stylesheet" href="../../iseller.es/css/bootstrap.css">

    Magnific Popup -->

        <style>
            /* The switch - the box around the slider */
            .switch {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 24px;
            }

            /* Hide default HTML checkbox */
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            /* The slider */
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                -webkit-transition: .4s;
                transition: .4s;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 4px;
                bottom: 3px;
                background-color: white;
                -webkit-transition: .4s;
                transition: .4s;

            }

            input:checked+.slider {
                background-color: #32d7c0;
            }

            input:focus+.slider {
                box-shadow: 1px 1px 5px #26af9c;
            }

            input:checked+.slider:before {
                -webkit-transform: translateX(26px);
                -ms-transform: translateX(26px);
                transform: translateX(26px);
            }

            .green {
                color: #32d7c0 !important;
            }

            /* Rounded sliders */
            .slider.round {
                border-radius: 24px;
            }

            .slider.round:before {
                border-radius: 50%;
            }
        </style>
    </head>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background-color: #edeef6;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

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

        .col-lg-3 {
            overflow: hidden;
        }

        .col-lg-3>label {
            white-space: nowrap;
        }
    </style>



    <body class='nav-md' onload="obtener_registros()">

        <div id="modal_container" class="modal-container5">
            <div class="modal5">


                <div id="Divfiltro" style="display: none;">
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

                <div id="divGRecurrentes" style="display: none;">
                    <h1 style="font-size: 22px;">Gastos recurrentes</h1>
                    <br>
                    <p style="text-align: left !important;">Se aplicaran los siguientes gastos a su lista de gastos semanales, <strong>(para la semana consultada)</strong>.
                    <p>
                    <p style="text-align: left !important;">En caso de heber aplicado previamente una configuración de gastos recurrentes para la semana consultada, <strong>se remplazara con la nueva configuración</strong>.</p>
                    <br>
                    <ul id="listaGastosModal" style="text-align: left !important;">

                    </ul>


                    <br>
                    <button class="btn btn-success" onclick="aplicarGastosRecurrentes()">Aplicar</button>
                    <a style="color: white; cursor: pointer" class="btn btn-info" onclick="cerrarModal()">Cerrar</a>
                </div>

                <div id="divEmpleados" style="display: none;">

                    <h1 style="font-size: 22px;">Empleados</h1>

                    <br>
                    <p style="text-align: left !important;">Se aplicaran los siguientes gastos a su lista de gastos semanales, <strong>(para la semana consultada)</strong>.
                    <p>
                    <p style="text-align: left !important;">
                        * Cada rol se multiplica por la cantidad de empleados registrados.
                        <br>
                        <a style="cursor: pointer; color: #32d7c0" onclick="nuevoEmpleado()">Agregar nuevo rol</a>.
                    </p>

                    <br>




                    <ul id="listaEmpleadosModal" style="text-align: left !important;"></ul>


                    <div style="display: none" id="divModificarEmpleados" class="row">
                        <div class="col-lg-12">
                            <hr>
                        </div>
                        <input type="number" hidden class="form-control" id="id" value="0">

                        <div class='col-lg-3'>
                            <label for="nombreRol">Rol del empleado</label>
                            <input type="text" class="form-control" id="nombreRol" value="">
                        </div>

                        <div class='col-lg-3'>
                            <label for="importe">Monto</label>
                            <input type="number" class="form-control" id="importe" value="" placeholder="">
                        </div>
                        <div class='col-lg-3'>
                            <label for="cantidadRol">Empleados</label>
                            <input type="number" class="form-control" id="cantidadRol" value="" placeholder="">
                        </div>

                        <div class='col-lg-3'>
                            <label for="" style="color: white;">Guardar </label>
                            <button class="btn btn-success" onclick="guardaEmpleado()">Guardar</button>
                        </div>

                    </div>




                    <br>
                    <br>
                    <button class="btn btn-success" onclick="guardarConfiguracionEmpleados()">Guardar y Aplicar</button>
                    <a style="color: white; cursor: pointer" class="btn btn-info" onclick="cerrarModal()">Cerrar</a>
                </div>



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
                <style>
                    .h3ini {
                        font-size: 16px;
                    }

                    .count {
                        font-size: 32px !important;
                    }
                </style>

                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->

                <!-- page content -->
                <div class='right_col'>




                    <h4>Gastos</h4>
                    <p style="margin-top: -10px;">Consulta</p>



                    <style>
                        .gastos {
                            font-size: 12px;
                            position: absolute;
                            margin-top: 35px;
                            color: #ff8989;
                            font-weight: 900;
                        }
                    </style>
                    <div class='row'>

                        <div class="animated fadeInLeft col-lg-3">
                            <div class="tile-stats" style="text-align:center">

                                <div class="count green count33"><?php echo number_format($gananciasSe, '2', '.', '.'); ?>$

                                    <span class="gastos"><span id="gastosSemanaCount"><?php echo $gastosSemana ?></span>$ <i style="font-size: 10px;margin-left: -3px;" class="line icon-arrow-down-circle"></i></span>

                                </div>






                                <h3 class="h3ini h3edit">Ganancias de la semana</h3>
                                <p>&nbsp;</p>

                            </div>
                        </div>

                        <div class="animated fadeInLeft col-lg-3">
                            <div class="tile-stats" style="text-align:center">

                                <div class="count count33"><?php echo number_format($gananciasMes, '2', '.', '.'); ?>$

                                    <span class="gastos"><span id="gastosMesCount"><?php echo $gastosMes ?></span>$ <i style="font-size: 10px;margin-left: -3px;" class="line icon-arrow-down-circle"></i></span>

                                </div>


                                <h3 class="h3ini h3edit">Ganancias del mes</h3>
                                <p>&nbsp;</p>

                            </div>
                        </div>


                        <div class="animated fadeInLeft col-lg-3">
                            <div class="tile-stats" style="text-align:center">
                                <div class="count green count33"><span id="gananciaNetaSemana"><?php echo number_format($gananciaNetaSemana, '2', '.', '.'); ?></span>$</div>
                                <h3 class="h3ini h3edit">Beneficio neto de la semana</h3>
                                <p>&nbsp;</p>
                            </div>
                        </div>

                        <div class="animated fadeInLeft col-lg-3">
                            <div class="tile-stats" style="text-align:center">
                                <div class="count count33"><span id="gananciaNetaMes"><?php echo number_format($gananciaNetaMes, '2', '.', '.'); ?></span>$</div>
                                <h3 class="h3ini h3edit">Beneficio neto del mes</h3>
                                <p>&nbsp;</p>
                            </div>
                        </div>

                    </div>
                    <style>
                        ul {
                            list-style: none;
                            padding-left: 0;
                        }

                        .ul>li {
                            border-bottom: 1px solid #f1f1f1;
                            padding: 5px 5px;
                            display: flex;
                        }

                        li>p {
                            font-weight: 600;
                            color: #747474;
                        }

                        li>span {
                            float: right;
                        }
                    </style>


                    <div class="row">
                        <div class="col-lg-3  animated fadeInRight" style="padding: 0 !important;">

                            <div class="col-lg-12">
                                <div class="x_panel">
                                    <h2 style="font-size: 15px; ">Gastos recurrentes
                                        <i title="Esto gastos se aplicaran de manera automatica a la semana en curso" style="float: right; cursor: pointer; border: 1px solid; padding: 2px 6px; border-radius: 50%;" class="line icon-exclamation"></i>
                                    </h2>
                                    <div class="x_content ">
                                        <br>

                                        <ul class="ul" id="listFijosSemanas">




                                        </ul>
                                        <div id="divAplicar" style="text-align: right; width: 100%; ">

                                        </div>
                                    </div>
                                </div>
                            </div>





                            <div class="col-lg-12">
                                <div class="x_panel">
                                    <div class="x_content ">

                                        <ul class="ul" style="padding: 0 !important;margin-top: 13px !important;">
                                            <li style="border: none; ">

                                                <div style="margin-right: 20px; padding-top: 4px;">
                                                    <i style="font-size: 32px;" class="line green icon-people"></i>
                                                </div>

                                                <div>
                                                    <span>Empleados.</span><br>
                                                    <a onclick="mostrarModal('divEmpleados')" style="cursor: pointer; color: #45cbb8;">Configurar...</a>
                                                </div>

                                                <div style="text-align: right;position: absolute; right: 0;">

                                                    <?php
                                                    $query2 = "SELECT * FROM gastosEmpleados";
                                                    $buscarAlumnos2 = $conexion->query($query2);
                                                    if ($buscarAlumnos2->num_rows > 0) {
                                                        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {

                                                            $pagar += $filaAlumnos2['importe'] * $filaAlumnos2['cantidad'];
                                                            $emp += $filaAlumnos2['cantidad'];
                                                        }
                                                    } else {
                                                        $pagar = 0;
                                                        $emp = 0;
                                                    }

                                                    $result =  '  <span>' . $pagar . '$</span><br>
                                                        <span>' . $emp . ' empleados</span>';
                                                    ?>
                                                    <div id="empledosResumen">
                                                        <?php echo $result ?>
                                                    </div>



                                                </div>

                                            </li>


                                        </ul>
                                    </div>
                                </div>
                            </div>







                        </div>



                        <div class="col-lg-9 animated fadeInUp" id="nuevoGastoDiv" style="display: none;">
                            <div class="x_panel">
                                <h2 style="font-size: 15px; ">Nuevo gasto


                                    <span style="float: right;">
                                        <button onclick="$('#nuevoGastoDiv').toggle();$('#tablaGastos').toggle()">
                                            <i title="Nuevo gasto" class="fa fa-close"></i>
                                        </button>
                                    </span>

                                </h2>
                                <div class="x_content ">

                                    <div class='item form-group'>
                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Nombre del gasto</label>
                                        <input type='text' id='nombreGasto' required='required' class='form-control '>
                                    </div>

                                    <div class='item form-group'>
                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Tipo </label>
                                        <select onchange="mostrarFecha()" class="form-control" required='required' name="tipo" id="tipo">
                                            <option value="">Seleccione</option>
                                            <option value="1">Gasto recurrente</option>
                                            <option value="2">Gasto unico</option>
                                        </select>
                                    </div>


                                    <div class='item form-group'>
                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Cantidad a pagar <small>(USD)</small></label>
                                        <input type='number' id='pagar' name='pagar' required='required' class='form-control '>
                                    </div>




                                    <div class='item form-group' id="divFechaGasto" style="display: none;">
                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Semana a aplicar </label>
                                        <input type="date" id="fechaAplicar" class="form-control">
                                    </div>

                                    <button onclick="salvarGasto()" style="float: right; margin-top: 15px;" class="btn btn-success">Guardar</button>

                                </div>
                            </div>
                        </div>


                        <script>
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'bottom-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                            })

                            function mostrarFecha() {

                                if ($('#tipo').val() == '2') {
                                    $('#divFechaGasto').show();
                                } else {
                                    $('#divFechaGasto').hide();
                                }
                            }



                            function validarCampos(campo) {
                                if ($('#' + campo).val() == '') {
                                    Toast.fire({
                                        icon: 'error',
                                        title: 'Rellene todos los campos'
                                    })
                                    return true;
                                } else {
                                    return false;
                                }
                            }


                            function salvarGasto() {
                                let nombreGasto = $('#nombreGasto').val()
                                let tipo = $('#tipo').val()
                                let pagar = $('#pagar').val()
                                let fechaAplicar;

                                if ($('#tipo').val() == '2') {
                                    fechaAplicar = $('#fechaAplicar').val()
                                    if (validarCampos('fechaAplicar')) {
                                        return;
                                    }
                                } else {
                                    fechaAplicar = 'NA';
                                }
                                if (validarCampos('nombreGasto')) {
                                    return;
                                }
                                if (validarCampos('tipo')) {
                                    return;
                                }
                                if (validarCampos('pagar')) {
                                    return;
                                }


                                $.ajax({
                                        url: '../../configurar/aggregarGasto.php',
                                        type: 'POST',
                                        dataType: 'html',
                                        data: {
                                            nombreGasto: nombreGasto,
                                            tipo: tipo,
                                            pagar: pagar,
                                            fechaAplicar: fechaAplicar
                                        },
                                    })
                                    .done(function(resultado1) {
                                        Toast.fire({
                                            icon: 'success',
                                            title: 'Se agrego correctamente'
                                        })

                                        $('#nombreGasto').val('')
                                        $('#tipo').val('')
                                        $('#pagar').val('')
                                        $('#divFechaGasto').hide()

                                        obtener_registros()
                                        nuevoGasto()
                                    })




                            }

                            function aplicarGastosRecurrentes() {
                                let semana = "<?php echo $semana ?>";
                                $.ajax({
                                        url: '../../configurar/aggregarGastoLista.php',
                                        type: 'POST',
                                        dataType: 'html',
                                        data: {
                                            semana: semana
                                        },
                                    })
                                    .done(function(resultado1) {
                                        Toast.fire({
                                            icon: 'success',
                                            title: 'Se actualizo correctamente'
                                        })
                                        obtener_registros()
                                        cerrarModal()
                                    })


                            }


                            function guardarConfiguracionEmpleados() {
                                let semana = "<?php echo $semana ?>";
                                $.ajax({
                                        url: '../../configurar/aggregarGastoListaEmpleado.php',
                                        type: 'POST',
                                        dataType: 'html',
                                        data: {
                                            semana: semana
                                        },
                                    })
                                    .done(function(resultado1) {
                                        Toast.fire({
                                            icon: 'success',
                                            title: 'Se actualizo correctamente'
                                        })

                                        $('#empledosResumen').html(resultado1)
                                        obtener_registros()
                                        cerrarModal()
                                    })
                            }
                        </script>









                        <div class="col-lg-9 animated fadeInRight" id="tablaGastos">
                            <div class="x_panel">
                                <h2 style="font-size: 15px; ">Gastos aplicados a la semana <strong><?php echo $semana;
                                                                                                    if ($semana == date('Y-W')) {
                                                                                                        echo ' (Semana actual)';
                                                                                                    } else {
                                                                                                        echo ' (Se muestra una semana anterior)';
                                                                                                    }
                                                                                                    ?></strong>
                                    <span style="float: right;">
                                        <button onclick="nuevoGasto()">
                                            <i title="Nuevo gasto" class="fa fa-plus"></i>
                                        </button>

                                        <button onclick="mostrarModal('Divfiltro')">
                                            <i title="Filtrar por semana" class="fa fa-filter"></i>
                                        </button>

                                    </span>

                                </h2>
                                <div class="x_content " id="tablaContenidoPagos">
                                    Cargando tabla de gastos <i class="fa fa-spin fa-spinner"></i>
                                </div>
                            </div>
                        </div>

                    </div>


                    <script>
                        function nuevoGasto() {
                            $('#nuevoGastoDiv').toggle()
                            $('#tablaGastos').toggle()
                        }



                        function obtener_registros() {
                            let semana = "<?php echo $semana ?>";
                            $.ajax({
                                    url: 'consulta_tablaPagos.php',
                                    type: 'POST',
                                    dataType: 'html',
                                    data: {
                                        semana: semana
                                    },
                                })

                                .done(function(resultado) {
                                    $("#tablaContenidoPagos").html(resultado);
                                })


                            $.ajax({
                                    url: 'consulta_listaFijosSemana.php',
                                    type: 'POST',
                                    dataType: 'html',
                                    data: {},
                                })

                                .done(function(resultado) {
                                    if (resultado != 'No hay nada para mostrar') {
                                        $("#divAplicar").html('<a onclick="mostrarModal(\'divGRecurrentes\')" style="cursor: pointer">Aplicar gastos</a>');
                                    }

                                    $("#listFijosSemanas").html(resultado);



                                    actualizarCounts();
                                })

                        }


                        function actualizarCounts() {
                            let semana = "<?php echo $semana ?>"
                            let mes = "<?php echo $mes ?>"
                            let gananciasSemana = "<?php echo $gananciasSe ?>"
                            let gananciasMes = "<?php echo $gananciasMes ?>"

                            $.ajax({
                                    url: 'consulta_gastosCount.php',
                                    type: 'POST',
                                    dataType: 'html',
                                    data: {
                                        semana: semana,
                                        mes: mes,
                                        gananciasSemana: gananciasSemana,
                                        gananciasMes: gananciasMes
                                    },
                                })

                                .done(function(resultado1) {
                                    let resultado = resultado1.split('*')
                                    $('#gastosSemanaCount').html(resultado[0])
                                    $('#gastosMesCount').html(resultado[1])
                                    $('#gananciaNetaSemana').html(resultado[2])
                                    $('#gananciaNetaMes').html(resultado[3])
                                })

                        }


                        function setGasto(id) {
                            $.ajax({
                                url: '../../configurar/setGastoAjax.php',
                                type: 'POST',
                                dataType: 'html',
                                data: {
                                    id: id
                                },
                            })
                        }

                        function setEmpleados(id) {
                            $.ajax({
                                url: '../../configurar/setEmpleadosAjax.php',
                                type: 'POST',
                                dataType: 'html',
                                data: {
                                    id: id
                                },
                            })
                        }


                        function confirm(id) {

                            Swal.fire({
                                title: 'Esta seguro?',
                                html: 'No se aplicara el gasto a la semana ¿desea continuar?',
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
                                    url: '../../configurar/deleteGastoAjax.php',
                                    type: 'POST',
                                    dataType: 'html',
                                    data: {
                                        id: params
                                    },
                                })

                                .done(function(resultado1) {
                                    Toast.fire({
                                        icon: 'success',
                                        title: 'Se elimino correctamente'
                                    })

                                    obtener_registros()
                                })


                        }
                    </script>


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








        <script>
            function actualizarGastosActivoModal() {
                $.ajax({
                        url: 'consulta_listaFijosSemanaModal.php',
                        type: 'POST',
                        dataType: 'html'
                    })

                    .done(function(gastosFios) {
                        $('#listaGastosModal').html(gastosFios)
                    })
            }


            function actualizarEmpleadosActivoModal() {

                $.ajax({
                        url: 'consulta_listaEmpleados.php',
                        type: 'POST',
                        dataType: 'html'
                    })

                    .done(function(gastosFios) {
                        $('#listaEmpleadosModal').html(gastosFios)
                        $('#divModificarEmpleados').hide()
                    })
            }

            function guardaEmpleado() {

                let id = $('#id').val()
                let nombreRol = $('#nombreRol').val()
                let importe = $('#importe').val()
                let cantidadRol = $('#cantidadRol').val()

                if (validarCampos('id')) {
                    return;
                }
                if (validarCampos('nombreRol')) {
                    return;
                }
                if (validarCampos('importe')) {
                    return;
                }
                if (validarCampos('cantidadRol')) {
                    return;
                }



                $.ajax({
                        url: '../../configurar/addEmpleadoAjax.php',
                        type: 'POST',
                        dataType: 'html',
                        data: {
                            id: id,
                            nombreRol: nombreRol,
                            importe: importe,
                            cantidadRol: cantidadRol
                        },
                    })
                    .done(function(resultado1) {
                        Toast.fire({
                            icon: 'success',
                            title: 'Se actualizo correctamente'
                        })
                        $('#empledosResumen').html(resultado1)
                        actualizarEmpleadosActivoModal()
                    })

            }



            function modificarEmpleado(id, nombre, cantidad, pago) {
                $('#divModificarEmpleados').show(300)
                $('#id').val(id)
                $('#nombreRol').val(nombre)
                $('#importe').val(pago)
                $('#cantidadRol').val(cantidad)
            }


            function nuevoEmpleado() {
                $('#divModificarEmpleados').show(300)
                $('#id').val(0)
                $('#nombreRol').val('')
                $('#importe').val('')
                $('#cantidadRol').val('')
            }




            var arrayDivsModal = ['Divfiltro', 'divGRecurrentes', 'divEmpleados'];

            function mostrarModal(div) {
                arrayDivsModal.forEach(element => {
                    if (element == div) {
                        $('#' + element).show();
                    } else {
                        $('#' + element).hide();
                    }
                });

                if (div == 'divGRecurrentes') {
                    actualizarGastosActivoModal();
                } else if (div == 'divEmpleados') {
                    actualizarEmpleadosActivoModal();
                }

                modal_container.classList.add('show');

            }

            function cerrarModal() {
                modal_container.classList.remove('show');
            }
        </script>

        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <!-- FastClick -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <script src="../vendors/nprogress/nprogress.js"></script>
        <script src="../build/js/custom.min.js"></script>


    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
    ?>