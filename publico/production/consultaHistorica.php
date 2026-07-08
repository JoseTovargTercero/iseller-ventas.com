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


</head>


<body class='nav-md'>
    <div class='container body'>
        <div class='main_container'>

            <?php echo $menu ?>
            <!-- top navigation -->
            <?php echo $topnav ?>
            <!-- page content -->
            <div class='right_col' role='main'>
                <div class=''>
                    <div class='clearfix'></div>
                    <div class='row'>
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


                                    <div class='dashboard-widget-content col-lg-4'>
                                        <a href='../build/pdf/stock.php' class='boton_pdf' value='Establecer'></a>
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
    <script src="js/nombre_pagina.js"></script>

</body>

</html>