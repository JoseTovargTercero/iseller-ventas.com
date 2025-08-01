<?php
require_once('includes/requires.php');

// Obtener mes de consulta con validación
$mesConsulta = isset($_GET['mesConsulta']) && !empty($_GET['mesConsulta'])
    ? date('Y') . '-' . $_GET['mesConsulta']
    : date('Y-m');

// Variables generales
$topnav = topnav();
$today = date('Y-m-d');

// Obtener semana de consulta
$semana = isset($_POST['fechaSolic']) && !empty($_POST['fechaSolic'])
    ? $_POST['fechaSolic']
    : date('Y-W');

// Contar registros de cierres para la semana
$registrosSemana = contar("SELECT COUNT(*) FROM cierres WHERE semana='$semana'");

// Calcular total de ventas de la semana
$totalVentasSemana = 0;
$queryVentas = "SELECT total_price FROM orden WHERE semana='$semana' AND (status='1' OR status='4')";
$resultadoVentas = $conexion->query($queryVentas);

if ($resultadoVentas && $resultadoVentas->num_rows > 0) {
    while ($fila = $resultadoVentas->fetch_assoc()) {
        $totalVentasSemana += (float)$fila['total_price'];
    }
}

// Calcular totales en dólares y otras monedas
$totalDolares = 0;
$totalDolaresNetos = 0;
$totalPesos = 0;
$totalesBs = 0;

$queryCierres = "SELECT * FROM cierres WHERE semana='$semana' ORDER BY dia ASC LIMIT 150";
$resultadoCierres = $conexion->query($queryCierres);

if ($resultadoCierres && $resultadoCierres->num_rows > 0) {
    while ($fila = $resultadoCierres->fetch_assoc()) {
        $bolivarDolar = max($fila['bolivarDolar'], 1); // evitar división por 0
        $pesoDolar = max($fila['pesoDolar'], 1);       // evitar división por 0

        $subtotal = (
            $fila['punto'] +
            $fila['bioPago'] +
            $fila['efectivo']
        ) / $bolivarDolar;

        $subtotal += $fila['pesos'] / $pesoDolar;
        $subtotal += $fila['dolares'];

        $totalDolares += $subtotal;
        $totalDolaresNetos += $fila['dolares'];
        $totalPesos += $fila['pesos'];
        $totalesBs += $fila['punto'] + $fila['bioPago'] + $fila['efectivo'];
    }
}

// Determinar indicador visual
if ($totalDolares < $totalVentasSemana) {
    $down = 'fa-arrow-down';
    $display = 'color: #ff9b9b; opacity: 1';
} elseif ($totalDolares > $totalVentasSemana) {
    $down = 'fa-question';
    $display = 'opacity: 0';
} else {
    $down = '';
    $display = 'opacity: 0';
}




?>
<!DOCTYPE html>
<html lang='es'>

<head>


    <title>Cierres diarios</title>


    <?php require_once('includes/headers.php'); ?>

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
                                                let contenido = $('#' + campo).val();

                                                if (contenido.indexOf(',') != '-1') {
                                                    contenido = contenido.replaceAll(',', '')
                                                    $('#' + campo).val(contenido);
                                                    alert('1: No utilice separador de miles. 2: Si desea indicar un valor decimal utilice el punto "."')
                                                }

                                                var indices = [];
                                                for (var i = 0; i < contenido.length; i++) {
                                                    if (contenido[i].toLowerCase() === ".") indices.push(i);
                                                }

                                                if (indices.length >= 2) {
                                                    $('#' + campo).val(contenido.substring(0, contenido.length - 1));
                                                }


                                                contenido = contenido.replace(/[^0-9.]/g, '');
                                                $('#' + campo).val(contenido);



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
                                                            punto: punto,
                                                            biopago: biopago,
                                                            efectivo: efectivo,
                                                            dolares: dolares,
                                                            pesos: pesos,
                                                            semana: semana,
                                                            dia: dia
                                                        },
                                                    })

                                                    .done(function(resultado) {
                                                        if (resultado.trim() == 'ok') {
                                                            location.reload()
                                                        } else {
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



                            <div class='x_panel tile' style="min-height: 500px;">
                                <div class='x_title'>
                                    <h2>Comparativa</h2>
                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content'>



                                    <div class='col-lg-12'> <br>

                                        <div class="fila ">
                                            <div class="col-lg-9">
                                                <h5 class="h3edit">BOLIVARES</h5>
                                                <span><?php echo number_format($totalesBs, '2', '.', '.'); ?> - Total de ingresos </span>
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




                                                    DOLARES
                                                </h5>
                                                <span>$<?php echo number_format($totalDolares, '2', '.', '.'); ?> / $<?php echo number_format($totalVentasSemana, '2', '.', '.'); ?> </span>
                                                <span style="<?php echo $display ?>">/ - $<?php echo number_format($totalVentasSemana - $totalDolares, '2', '.', '.'); ?></span>
                                                <p>Conversión a dolares.</p>
                                                <p>Total de ingresos declarados por 'Cierres diarios' <strong>($<?php echo number_format($totalDolares, '2', '.', '.'); ?>)</strong> / total de ingresos por el valor de las ventas realizadas <strong>($<?php echo number_format($totalVentasSemana, '2', '.', '.'); ?>)</strong></p>
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
                            <div class='x_panel  '>
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
                                                                        <td>' . number_format($filaAlumnos77['punto'], '2', ',', '.') . '</td>
                                                                        <td>' . number_format($filaAlumnos77['bioPago'], '2', ',', '.') . ' </td>
                                                                        <td>' . number_format($filaAlumnos77['efectivo'], '2', ',', '.') . ' </td>
                                                                        <td>$' . number_format($filaAlumnos77['dolares'], '2', ',', '.') . ' </td>
                                                                        <td>' . number_format($filaAlumnos77['pesos'], '0', ',', '.') . ' </td>
                                                                        <td>' . number_format($subTotal, '2', ',', '.') . ' </td>

                                                                        <td style="text-align: center"  class="">
                                                              <a style="cursor: pointer" onclick="confirm(' . $filaAlumnos77["id"] . ')"><i class="gray line icon-trash"></i></a>
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
                                function confirm(id) {



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
                                            $("#row" + params).hide(300);
                                        })


                                }
                            </script>

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