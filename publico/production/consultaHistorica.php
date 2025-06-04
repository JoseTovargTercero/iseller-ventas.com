<?php
require_once('includes/requires.php');

$topnav = topnav();
date_default_timezone_set('America/Manaus');

?>
<!DOCTYPE html>
<html lang='es'>

<head>

    <title>Consultas</title>

    <?php require_once('includes/headers.php'); ?>

    <?php
    @$accion = $_GET['accion'];

    switch ($accion) {
        case ('enviado'):
            echo '<script>
            function mensaje(){	
			alertify.success("Reporte enviado correctamente."); }
            </script>
            <body onload="mensaje()">
            </body>';

            break;



        case ('conexion'):
            echo '<script>
            function mensaje(){	
			alertify.error("Error de conexion, intente de nuevo.");}
            </script>
            <body onload="mensaje()">
            </body>';
            break;


        case ('correo'):
            echo '<script>
            function mensaje(){	
			alertify.error("El administrador no ha agredo un correo.");}
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
            <!-- page content -->
            <div class='right_col' role='main'>
                <div class=''>


                    <div class='clearfix'></div>
                    <div class='row   fadeInUp animated'>


                        <div class="col-lg-12">
                            <h4>Consultas</h4>
                            <p style="margin-top: -10px;">Reportes y consultas</p>
                        </div>
                        <div class='col-lg-6'>
                            <div class='x_panel alto'>
                                <div class='x_title'>
                                    <h2>Reporte de cierre de jornada. <?php echo date('d/m/Y') . ' - ' . date('h:i a');
                                                                        ?></h2>

                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content '>

                                    <p>Para usuarios estandar*: el reporte será enviado al correo del administrador del sistema. </p>

                                    <div class='col-lg-8'>


                                        <div class='' aria-labelledby='navbarDropdown'>
                                            <a class='dropdown-item' href='../build/pdf/reporteDia.php'><i class=' pull-right'></i> Cierre de Jornada</a>
                                            <a class='dropdown-item' href='../build/pdf/reporteSemana.php'><i class=' pull-right'></i> Avance de la Semana</a>
                                            <a class='dropdown-item' href='../build/pdf/reporteMes.php'><i class=' pull-right'></i> Avance del mes</a>
                                            <a class='dropdown-item' href='../build/pdf/reporteAno.php'><i class=' pull-right'></i> Avance del A&ntilde;o</a>

                                        </div>



                                    </div>

                                    <div class='dashboard-widget-content col-lg-4'>
                                        <a href='../build/pdf/reporteDia.php' class='boton_pdf' value='Establecer'></a>
                                    </div>

                                </div>
                            </div>
                        </div>











                        <div class='col-lg-6'>
                            <div class='x_panel alto'>
                                <div class='x_title '>
                                    <h2>Reporte de productos con stock critico.</h2>

                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content '>
                                    <p style='height:7%'>A continuacion, puede generar un reporte de productos con stock critico/lista de compras.</p>

                                    <div class='dashboard-widget-content col-lg-8'>
                                        <h4> TOTAL DE ARTICULOS CON STOCK CRITICO: <strong><?php echo $cantidadCritica; ?></strong></h4>
                                    </div>

                                    <div class='dashboard-widget-content col-lg-4'>
                                        <a href='../build/pdf/stock.php' class='boton_pdf' value='Establecer'></a>
                                    </div>

                                </div>
                            </div>
                        </div>


                    </div>






                    <div class="col-lg-12">
                        <div class="x_panel   fadeInUp animated">
                            <div class="x_title ">
                                <h2>Historico de ventas</h2>

                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <p>Defina el periodo de tiempo sobre el cual desea generar el reporte.</p>

                                <form action='' method='POST'>

                                    <div class='col-lg-5'>
                                        <input type='date' required class='form-control col-lg-6 center' name='fecha_inicio'>
                                    </div>
                                    <div class='col-lg-2'>
                                        <p style="text-align: center; margin-top:5px;">DEFINA UN PERIODO DE TIEMPO VALIDO</p>
                                    </div>


                                    <div class='col-lg-5'>
                                        <input type='date' required class='form-control col-lg-6 center' name='fecha_fin'>
                                    </div>

                                    <div class='col-lg-12'>
                                        <div class='col-lg-5'>
                                        </div>
                                        <div class='col-lg-2'>
                                            <input style="width:100%; margin-top:15px" type="submit" class="btn btn-success" value="Solicitar">

                                        </div>


                                        <div class='col-lg-5'>
                                        </div>
                                    </div>





                                </form>
                                <div class="row">





                                    <?php

                                    @$inicio = $_POST['fecha_inicio'];
                                    @$Fechainicio =  str_split($inicio, 1);
                                    @$FechainicioDia = $Fechainicio[8] . $Fechainicio[9];

                                    @$final = $_POST['fecha_fin'];
                                    @$Fechafinal =  str_split($final, 1);
                                    @$FechafinalDia = $Fechafinal[8] . $Fechafinal[9];




                                    $fina = 31;

                                    for ($resultado = $FechainicioDia; $resultado <= $fina; $resultado += 01) {

                                        $inicio2 = $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . $resultado;

                                        switch ($inicio2) {

                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "2"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "02";
                                                break;
                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "3"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "03";
                                                break;
                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "4"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "04";
                                                break;
                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "5"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "05";
                                                break;
                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "6"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "06";
                                                break;
                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "7"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "07";
                                                break;
                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "8"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "08";
                                                break;
                                            case ("" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "9"):
                                                $inicio2 = "" . $Fechainicio[0] . $Fechainicio[1] . $Fechainicio[2] . $Fechainicio[3] . $Fechainicio[4] . $Fechainicio[5] . $Fechainicio[6] . $Fechainicio[7] . "09";
                                                break;
                                        }

                                        $query777777 = "SELECT * FROM orden WHERE modified='$inicio' LIMIT 1";
                                        $buscarAlumnos777777 = $conexion->query($query777777);
                                        if ($buscarAlumnos777777->num_rows > 0) {
                                            while ($filaAlumnos777777 = $buscarAlumnos777777->fetch_assoc()) {
                                                $idIniicio = $filaAlumnos777777['id'];
                                                $fina = $resultado;
                                            }
                                        } else {

                                            $query7777777 = "SELECT * FROM orden WHERE modified='$inicio2' LIMIT 1";
                                            $buscarAlumnos7777777 = $conexion->query($query7777777);
                                            if ($buscarAlumnos7777777->num_rows > 0) {
                                                while ($filaAlumnos7777777 = $buscarAlumnos7777777->fetch_assoc()) {
                                                    $idIniicio = $filaAlumnos7777777['id'];
                                                    $fina = $resultado;
                                                }
                                            }
                                        }
                                    }





                                    $finaFIN = 1;

                                    for ($resultado2 = $FechafinalDia; $resultado2 >= $finaFIN; $resultado2--) {

                                        $final2 = $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . $resultado2;

                                        switch ($final2) {
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "2"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "02";
                                                break;
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "3"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "03";
                                                break;
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "4"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "04";
                                                break;
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "5"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "05";
                                                break;
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "6"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "06";
                                                break;
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "7"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "07";
                                                break;
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "8"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "08";
                                                break;
                                            case ("" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "9"):
                                                $inicio2 = "" . $Fechafinal[0] . $Fechafinal[1] . $Fechafinal[2] . $Fechafinal[3] . $Fechafinal[4] . $Fechafinal[5] . $Fechafinal[6] . $Fechafinal[7] . "09";
                                                break;
                                        }


                                        $query777777777 = "SELECT * FROM orden WHERE modified='$final'";
                                        $buscarAlumnos777777777 = $conexion->query($query777777777);
                                        if ($buscarAlumnos777777777->num_rows > 0) {
                                            while ($filaAlumnos777777777 = $buscarAlumnos777777777->fetch_assoc()) {
                                                $idFIN = $filaAlumnos777777777['id'];
                                                $finaFIN = $resultado2;
                                            }
                                        } else {

                                            $query7777777777 = "SELECT * FROM orden WHERE modified='$final2'";
                                            $buscarAlumnos7777777777 = $conexion->query($query7777777777);
                                            if ($buscarAlumnos7777777777->num_rows > 0) {
                                                while ($filaAlumnos7777777777 = $buscarAlumnos7777777777->fetch_assoc()) {
                                                    $idFIN = $filaAlumnos7777777777['id'];
                                                }

                                                $finaFIN = $resultado2;
                                            }
                                        }
                                    }


                                    ?>
                                    <div class="col-lg-12">
                                        <div class="card-box table-responsive">

                                            <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                                                <thead>
                                                    <tr class="headings">
                                                        <th style='width:2%;' class='column-title'>#</th>
                                                        <th style='width:10%;' class='column-title'>Usuario</th>
                                                        <th style='width:1%;' class='column-title'>TIPO</th>

                                                        <th style='width:9%;' class='column-title'>Fecha</th>

                                                        <th style='width:10%;' class='column-title'>USD</th>
                                                        <th style='width:10%;' class='column-title'>COP</th>
                                                        <th style='width:10%;' class='column-title'>BS</th>

                                                        <th style='width:13%;' class='column-title'>Productos</th>

                                                    </tr>
                                                </thead>




                                                <tbody>
                                                    <?php



                                                    if (isset($_POST['fecha_inicio']) && isset($_POST['fecha_fin'])) {
                                                        echo "<p style='text-align:center;'>Se muestran los resultados aplicando el filto de fecha.<br></p>";
                                                        $query77 = "SELECT * FROM orden WHERE id>='$idIniicio' AND id<='$idFIN' AND status!='3' AND status!='5' AND status!='5.2' AND status!='2' ORDER BY id DESC LIMIT 1000";
                                                    } else {
                                                        $query77 = "SELECT * FROM orden WHERE status!='3'  AND status!='5' AND status!='5.2' AND status!='2' ORDER BY id DESC LIMIT 1000";
                                                    }

                                                    $buscarAlumnos77 = $conexion->query($query77);
                                                    if ($buscarAlumnos77->num_rows > 0) {
                                                        $contador = 1;
                                                        while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                                            $users = $filaAlumnos77['customer_id'];
                                                            $orderid = $filaAlumnos77['id'];

                                                            $query999999999 = "SELECT * FROM usuarios WHERE id='$users' ";
                                                            $buscarAlumnos999999999 = $conexion->query($query999999999);
                                                            if ($buscarAlumnos999999999->num_rows > 0) {
                                                                while ($filaAlumnos999999999 = $buscarAlumnos999999999->fetch_assoc()) {
                                                                    $usuario1 = $filaAlumnos999999999['nombre'];
                                                                }
                                                            }

                                                            $query7E = $conexion->query("SELECT * FROM orden_articulos WHERE order_id='$orderid' ");
                                                            if ($query7E->num_rows > 0) {

                                                                while ($row7E = $query7E->fetch_assoc()) {
                                                                    $producto  = $row7E['product_id'];
                                                                    $productoquanty  = $row7E['quantity'];

                                                                    $query9999999999 = "SELECT * FROM productos WHERE id='$producto'";
                                                                    $buscarAlumnos9999999999 = $conexion->query($query9999999999);
                                                                    if ($buscarAlumnos9999999999->num_rows > 0) {
                                                                        while ($filaAlumnos9999999999 = $buscarAlumnos9999999999->fetch_assoc()) {
                                                                            $porductos .= $productoquanty . ' ' . $filaAlumnos9999999999['nombre'] . ', ';
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            $porductos = substr($porductos, 0, -2);
                                                            $valorPeso = $filaAlumnos77['total_price_cop'];
                                                            $valorbolivar = $filaAlumnos77['total_price_bs'];




                                                            if ($filaAlumnos77['status'] == "1") {
                                                                $tipo = "V";
                                                            } elseif ($filaAlumnos77['status'] == "2") {
                                                                $tipo = "C";
                                                            } elseif ($filaAlumnos77['status'] == "4") {
                                                                $tipo = "M";
                                                            }

                                                            echo '
                             <tr class="even pointer">
                            <td class=" ">' . $contador++ . '</td>
                            <td>' . $usuario1 . '</td>
                            <td>' . $tipo . '</td>
                            <td>' . $filaAlumnos77['created'] . '</td>
                            <td>' . $filaAlumnos77['total_price'] . '</td>
                            <td>' . number_format($valorPeso, '0', ',', '.') . '</td>
                            <td>' . number_format($valorbolivar, '2', ',', '.') . '</td>
                            <td>' . $porductos . '</td>
                          </tr>';

                                                            $porductos = '';
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















                    <style>
                        .center {
                            min-width: 100%;
                            margin-left: auto
                        }




                        .gray {
                            color: rgba(52, 73, 94, 0.94);
                            font-size: 24px;

                        }

                        .fav {
                            color: #1ABB9C;
                            font-size: 24px;

                        }

                        .nofav {
                            color: lightgray;
                            font-size: 24px;

                        }

                        .boton_pdf {
                            height: 150px;
                            width: 100%;
                            border: 1px solid lightgray;
                            background-image: url(images/pdf_gris.png );
                            background-position: center;
                            background-repeat: no-repeat;
                            background-size: 90px;
                            float: right;

                        }

                        .boton_pdf:hover {
                            background-image: url(images/pdf.PNG );
                            background-position: center;
                            background-repeat: no-repeat;
                            background-size: 90px;

                        }
                    </style>

                </div>
            </div>
        </div>
        <!-- /page content -->


    </div>
    </div>

    <!-- jQuery -->
    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

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
    <script src="../build/js/custom.js"></script>
</body>

</html>