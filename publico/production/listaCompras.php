<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');



if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Nueva_Compra == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    $topnav = topnav();
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    $query = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

            $PesoDolar = $filaAlumnos['pesoDolar'];
            $dolarBolivar = $filaAlumnos['DolarBolivar'];
            $Pesobolivar = $filaAlumnos['bolivar_peso'];
            $peso_bolivar = $filaAlumnos['peso_bolivar'];
        }
    }
    $query2 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $deshacerCompra = $filaAlumnos2['deshacerCompra'];
        }
    }
    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }

    if (!$_SESSION['proveedor']) {

        $visible = "";
    } elseif (isset($_POST['proveedor'])) {
        $visible = "contain: strict";
    } elseif ($_SESSION['proveedor']) {
        $visible = "contain: strict";
    }




    if ($_SESSION['compras_dis'] == "activa") {

        unset($_SESSION['compras_dis']);

        $_SESSION['facturaCompleta'] = array();
        $_SESSION['facturaCompletaMostrada'] = array();
        unset($_SESSION['proveedor']);
        unset($_SESSION['factura']);
        unset($_SESSION['fechaFactura']);
        unset($_SESSION['totalFa']);
        unset($_SESSION['statusFactura']);


        define('PAGINA_INICIO', 'nuevaCompraFacturas.php');
        header('Location: ' . PAGINA_INICIO);
    }


    $_SESSION['compras'] = "activa";


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

        <title>Compras</title>

        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <!-- Font Awesome -->
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <!-- NProgress -->
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <!-- iCheck -->

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
        <link href="../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">

        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>

        <script src='js/jquery.min.js'></script>
        <script src='peticion.js'></script>


        <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>


        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">

        <?php
        @$registrado = $_GET['agregado'];
        $cantidadNueva = $_GET['m1'];
        $mensajePrecio = $_GET['m2'];
        $mensajePorcentaje = $_GET['m3'];

        switch ($registrado) {
            case ('correcto'):
                echo '<script>
function mensaje(){	
			alertify.success("Se han agregado productos al stock");    
		}
                </script>
                <body onload="mensaje()">
                </body>';
                break;
        }


        if ($_GET['accion'] == "mensajeExito" && $_SESSION['statusFactura'] == "") {

            echo '<script>
        function mensaje(){	
			alertify.success("Se han agregado productos al stock");    
		}
        
        </script>
        <body onload="mensaje()">
        </body>';
        }
        if ($_GET['accion'] == "mensajePagado" && $_SESSION['statusFactura'] == "") {

            echo '<script>
            function mensaje(){	
			alertify.success("Una factura fue pagada");    
		    }
            
            </script>
            <body onload="mensaje()">
            </body>';
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
                <div class='right_col' role='main'>
                    <div class=''>
                        <div class="col-lg-12">
                            <h4>Compras</h4>
                            <p style="margin-top: -10px;">Listado de compras realizadas</p>

                        </div>



                        <div class='clearfix'></div>


                        <script>
                            $(obtener_registros_precio());

                            function obtener_registros_precio(rep_precio) {
                                $.ajax({
                                        url: 'precio_producto.php',
                                        type: 'POST',
                                        dataType: 'html',
                                        data: {
                                            rep_precio: rep_precio
                                        },
                                    })

                                    .done(function(resultadoPrecio) {
                                        $("#precio_producto").html(resultadoPrecio);
                                    })
                            }

                            $(document).on('change', '#producto', function() {
                                var valorprecio = $(this).val();
                                if (valorprecio != "") {
                                    obtener_registros_precio(valorprecio);
                                } else {
                                    obtener_registros_precio();
                                }
                            });
                        </script>
                        <script>
                            setInterval(function() {
                                capturar1()
                                capturar()
                            }, 100);


                            function capturar1() {
                                cambioDolar = parseFloat(document.getElementById("cambioDolar").value);
                                cambioPesoRecepcion = parseFloat(document.getElementById("cambioPesoRecepcion").value);
                                cambioPesoPublicacion = parseFloat(document.getElementById("cambioPesoPublicacion").value);
                            }
                            ////////////////////////////////////////////////////////////////
                            ////////////////////////////////////////////////////////////////


                            $(obtener_registro2());

                            function obtener_registro2(rep2) {
                                $.ajax({
                                        url: 'tasas_Dolar2.php?idDolar=<?php echo $idDolar ?>',
                                        type: 'POST',
                                        dataType: 'html',
                                        data: {
                                            rep2: rep2
                                        },
                                    })

                                    .done(function(resultado2) {
                                        $("#resultadoDolares").html(resultado2);
                                    })
                            }

                            $(document).on('change', '#Tasas_dolares', function() {
                                var valorBusqueda2 = $(this).val();
                                if (valorBusqueda2 != "") {
                                    obtener_registro2(valorBusqueda2);
                                } else {
                                    obtener_registro2();
                                }
                                division();
                            });
                            ///////////////////////////////
                            ///////////////////////////////

                            $(obtener_registro1());

                            function obtener_registro1(rep1) {
                                $.ajax({
                                        url: 'tasas_Pesos2.php?idPeso=<?php echo $idPeso ?>',
                                        type: 'POST',
                                        dataType: 'html',
                                        data: {
                                            rep1: rep1
                                        },
                                    })

                                    .done(function(resultado1) {
                                        $("#resultadoPesos").html(resultado1);
                                    })
                            }

                            $(document).on('change', '#Tasas_pesos', function() {
                                var valorBusqueda1 = $(this).val();
                                if (valorBusqueda1 != "") {
                                    obtener_registro1(valorBusqueda1);
                                } else {
                                    obtener_registro1();
                                }
                                division();
                            });
                            //////////////////////////
                            //////////////////////////

                            function capturar() {
                                var precioMonedaOrigen = document.getElementById("precioMonedaOrigen").value;
                                var pruebaa = "2.515151";
                                // Obtenemos el valor por el Nombre
                                var selec = document.getElementById("moneda").value;


                                if (selec == "pesos") {

                                    var moneda1 = precioMonedaOrigen / cambioPesoRecepcion;
                                    var moneda = moneda1 / cambioDolar;
                                    var monedaSinRound = moneda1 / cambioDolar;


                                } else if (selec == "bolivares") {
                                    var moneda = precioMonedaOrigen / cambioDolar;
                                } else {
                                    var moneda = precioMonedaOrigen;

                                }

                                function financial(x) {
                                    return Number.parseFloat(x).toFixed(30);
                                }

                                document.getElementById('precio').value = moneda;
                                division();
                            }

                            function division(cif = 3, dec = 2) {
                                var precio = document.calculadora.precio.value;
                                var cantidad = document.calculadora.cantidad.value;
                                var porcentaje = document.calculadora.porcentaje.value;

                                Number.prototype.round = function(places) {
                                    return +(Math.round(this + "e+" + places) + "e-" + places);
                                }

                                try {
                                    precio = (isNaN(parseInt(precio))) ? 0 :
                                        parseFloat(precio);

                                    cantidad = (isNaN(parseInt(cantidad))) ? 0 :
                                        parseInt(cantidad);

                                    porcentaje = (isNaN(parseInt(porcentaje))) ? 0 :
                                        parseFloat(porcentaje);

                                    var preciodolarCompraMostrado = precio / cantidad;
                                    var preciodolarCompraNoMostrado = precio / cantidad;




                                    document.getElementById('resultado5').value = preciodolarCompraMostrado * cambioDolar
                                    var var1 = preciodolarCompraMostrado * cambioDolar
                                    document.getElementById('resultado6').value = var1 * cambioPesoPublicacion


                                    var preciodolarCompraMostrado = preciodolarCompraMostrado.round(2); // 1.78
                                    document.calculadora.resultado.value = '$ ' + preciodolarCompraMostrado;
                                    var preciodolar = precio / cantidad;

                                    var preciodolarVentaMostrado = (preciodolar * porcentaje / 100) + preciodolar;
                                    var preciodolarVentaMostrado = preciodolarVentaMostrado.round(2); // 1.78
                                    document.calculadora.resultado2.value = '$ ' + preciodolarVentaMostrado;

                                    var preciodolarVenta = (preciodolar * porcentaje / 100) + preciodolar;
                                    //////////////////////
                                    //////////////////////







                                    //////////////////////
                                    //////////////////////

                                    var pesoTransferencia = preciodolarVenta * cambioDolar;

                                    var numb2 = pesoTransferencia.round(2); // 1.78
                                    let inputNum = numb2

                                    inputNum = inputNum.toString()
                                    inputNum = inputNum.split('.')

                                    if (!inputNum[1]) {
                                        inputNum[1] = '00'
                                    }

                                    let separados

                                    if (inputNum[0].length > cif) {
                                        let uno = inputNum[0].length % cif
                                        if (uno === 0) {
                                            separados = []
                                        } else {
                                            separados = [inputNum[0].substring(0, uno)]
                                        }
                                        let posiciones = parseInt(inputNum[0].length / cif)
                                        for (let i = 0; i < posiciones; i++) {
                                            let pos = ((i * cif) + uno)
                                            console.log(uno, pos)
                                            separados.push(inputNum[0].substring(pos, (pos + 3)))
                                        }
                                    } else {
                                        separados = [inputNum[0]]
                                    }

                                    document.getElementById('resultado4').value = separados.join(',') + '.' + inputNum[1] + ''

                                    ///////
                                    ///////
                                    ///////
                                    ///////
                                    ///////
                                    /////// calculo a pesos

                                    var precioBolivarOculta = preciodolarVenta * cambioDolar;


                                    var preciopesoVento = precioBolivarOculta * cambioPesoPublicacion;
                                    var ventaPesoRoundListo = preciopesoVento.round(0); // 1.78
                                    let inputNumbP = ventaPesoRoundListo

                                    inputNumbP = inputNumbP.toString()
                                    inputNumbP = inputNumbP.split('.')

                                    if (!inputNumbP[1]) {
                                        inputNumbP[1] = '00'
                                    }

                                    let separadoP

                                    if (inputNumbP[0].length > cif) {
                                        let unosP = inputNumbP[0].length % cif
                                        if (unosP === 0) {
                                            separadoP = []
                                        } else {
                                            separadoP = [inputNumbP[0].substring(0, unosP)]
                                        }
                                        let possPicionessP = parseInt(inputNumbP[0].length / cif)
                                        for (let i = 0; i < possPicionessP; i++) {
                                            let possP = ((i * cif) + unosP)
                                            console.log(unosP, possP)
                                            separadoP.push(inputNumbP[0].substring(possP, (possP + 3)))
                                        }
                                    } else {
                                        separadoP = [inputNumbP[0]]
                                    }

                                    document.getElementById('resultado3').value = separadoP.join(',') + '.' + inputNumbP[1] + ''
                                    ///////////////////
                                } catch (e) {}
                            }
                        </script>


                        <div class="col-lg-12 " <?php if ($_GET['nFactura'] == "") {
                                                    echo 'style="contain: strict"';
                                                } ?>>
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Factura Numero: <?php echo $_GET['nFactura'] ?></h2>
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li><a style="color: #909090" href="listaCompras.php">X</a>
                                        </li>


                                    </ul>
                                    <div class="clearfix"></div>
                                </div>
                                <div class='x_content'>
                                    <div class="content-carrito">
                                        <div class="carrito" style="    max-width: 350px;">
                                            <div class="topLine"></div>
                                            <div style="width: 100%; text-align: center">

                                                <?php

                                                $numerodelaFactura = $_GET['nFactura'];
                                                $query22 = "SELECT * FROM compras WHERE factura='$numerodelaFactura' ORDER BY producto ASC LIMIT 1";
                                                $buscarAlumnos22 = $conexion->query($query22);
                                                if ($buscarAlumnos22->num_rows > 0) {
                                                    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {

                                                        echo  '<p>
                                                        Proveedor: ' . $filaAlumnos22['proveedor'] . '<br>
                                                        N# de la Factura ' . $filaAlumnos22['factura'] . '<br>
                                                        Fecha de Factura ' . $filaAlumnos22['fechaFactura'] . '<br></p> ';
                                                    }
                                                }

                                                echo "<div class='ln_solid2'></div>";

                                                ?>
                                            </div>

                                            <?php
                                            foreach ($_SESSION['facturaCompletaMostrada'] as $nombre2) {
                                                echo $nombre2;
                                            }
                                            ?>





                                            <div class="divisor">
                                                <br>



                                                <?php




                                                $query22 = "SELECT * FROM compras WHERE factura='$numerodelaFactura' ORDER BY producto ASC";
                                                $buscarAlumnos22 = $conexion->query($query22);
                                                if ($buscarAlumnos22->num_rows > 0) {
                                                    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {

                                                        echo  '
                                                        <div class="myRow">
                                                         <div class="myRowLeft">
                                                             <strong>' . $filaAlumnos22['producto'] . '</strong>
                                                             <p>Cantidad <strong>' . $filaAlumnos22['cantidad'] . '</strong> </p>
                                                         </div>
                                                                                                        
                                                         <div class="myRowRight">
                                                                                                        
                                                             <strong>' . number_format($filaAlumnos22['pesosSubTotal'], '0', ',', '.') . ' Cop</strong>
                                                             <p>$' . number_format($filaAlumnos22['dolaresSubTotal'], '2', ',', '.') . ' /  ' . number_format($filaAlumnos22['bolivaresSubTotal'], '2', ',', '.') . 'Bs
                                                             </p>
                                                                                                        
                                                         </div>
                                                     </div>';
                                                        $dolaresSubTotal += $filaAlumnos22['dolaresSubTotal'];
                                                        $pesosSubTotal += $filaAlumnos22['pesosSubTotal'];
                                                        $bolivaresSubTotal += $filaAlumnos22['bolivaresSubTotal'];
                                                        $pagadaono = $filaAlumnos22['status'];
                                                    }
                                                }


                                                echo  ' <div class="myRow">
                                                <div class="myRowLeft">
                                                                                            
                                                </div>
                                                                                            
                                                <div class="myRowRight">
                                                                                            
                                                    <strong>' . number_format($pesosSubTotal, '0', ',', '.') . ' Cop</strong>
                                                    <p>$' . number_format($dolaresSubTotal, '2', ',', '.') . ' /  ' . number_format($bolivaresSubTotal, '2', ',', '.') . 'Bs
                                                    </p>
                                                                                            
                                                </div>
                                                </div>';




                                                ?>


                                                <div class="divRight"></div>
                                            </div>



                                            <div class="myRow" style="padding: 5px;">


                                                <img src="images/barcode.png" style="opacity: 0.3; margin: auto" width="90%" height="60px" alt="barcode">

                                            </div>

                                            <p style="font-size: 11px !important; width: 100%; text-align: center; margin-top: -5px">

                                                <?php if ($pagadaono == "1") {
                                                    echo "*Factura pagada.";
                                                } else {
                                                    echo "*Factura pendiente.";
                                                }
                                                ?></p>





                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>


                        <div class="row  fadeInUp animated" style="display: block;">

                            <body onload='capturar()'> </body>
                            <div class="col-lg-12">
                                <div class="x_panel ">
                                    <div class="x_title">
                                        <h2>Lista de compras</h2>
                                        <a href="?antiguas=SI">
                                            <h2 style="float: right;"> <small> Ver entradas Antiguas</small></h2>
                                        </a>

                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content alto">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="card-box table-responsive">

                                                    <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                                                        <thead>
                                                            <tr class="headings">
                                                                <th class="column-title">#</th>
                                                                <th class="column-title">N# de Factura </th>
                                                                <th class="column-title">Proveedor </th>
                                                                <th class="column-title">Fecha de registro</th>
                                                                <th class="column-title">Usuario </th>
                                                                <th class="column-title">Producto</th>
                                                                <th class="column-title">Cantidad</th>
                                                                <th style="text-align: center" class="column-title">Ver Factura</th>
                                                                <th class="column-title">Status</th>
                                                                <th class="column-title"></th>
                                                            </tr>
                                                        </thead>




                                                        <tbody>
                                                            <?php


                                                            if ($_GET["antiguas"] == "SI") {
                                                                $query6 = $conexion->query("SELECT * FROM compras WHERE tipo!='dist' ORDER BY id DESC");
                                                            } else {
                                                                $query6 = $conexion->query("SELECT * FROM compras WHERE tipo!='dist' ORDER BY id DESC LIMIT 15");
                                                            }






                                                            if ($query6->num_rows > 0) {
                                                                $tabla6 = '';
                                                                $contador = 1;
                                                                while ($row6 = $query6->fetch_assoc()) {




                                                                    if ($contador <= $deshacerCompra) {
                                                                        $deshacer = '<a  class="btn2 btn-secondary" href="../../configurar/vaciarFactura.php?idDeshacer=' . $row6["id"] . '">Deshacer</a>';
                                                                    } else {
                                                                        $deshacer = '';
                                                                    }
                                                                    if ($row6["status"] == 2) {
                                                                        $statusF =  "<span ><a style='color: orangered' href='../../configurar/vaciarFactura.php?idPagar=" . $row6['factura'] . "'>Pendiente</a> </span>";
                                                                    } elseif ($row6["status"] == 1) {
                                                                        $statusF = "Pagado";
                                                                    } elseif ($row6["status"] == 0) {
                                                                        $statusF = "S/F";
                                                                    }

                                                                    if ($row6["status"] != 0) {
                                                                        $nameProveedor  = $row6["proveedor"];
                                                                        $numberTill  = $row6["factura"];
                                                                        $linkVer = '<a href="?nFactura=' . $row6["factura"] . '"><i class="fa fa-file-text-o icono"></i></a>';
                                                                    } else {
                                                                        $linkVer = 'S/F';
                                                                        $nameProveedor = 'S/F';
                                                                        $numberTill = 'S/F';
                                                                    }







                                                                    $tabla6 .= '
          <tr class="even pointer">
                            <td class=" ">' . $contador++ . '</td>
                            <td class=" ">' . $numberTill . '</td>
                            <td class=" ">' . $nameProveedor . '</td>
                            <td class=" ">' . $row6["fecha"] . '</td>
                            <td class=" ">' . $row6["user"] . '</td>
                            
                            <td class=" ">' . $row6["producto"] . '</td>
                            <td class=" ">' . $row6["cantidad"] . '</td>
                            
                           
                            <td style="text-align: center">' . $linkVer . '</td>
                            <td class="a-right a-right ">' . $statusF . '</td>
                            <td class="a-right a-right ">' . $deshacer . '</td>
                               
                          </tr>
                          
                          
                          
        
        
        
        
       ';
                                                                }
                                                                echo $tabla6;
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
                        </div>
                    </div>


                    <style>
                        .ancho {
                            overflow-x: hidden;
                        }

                        .ln_solid2 {
                            border-top: 1px solid #e5e5e5;
                            height: 1px;
                        }

                        .table td {
                            padding-top: .35rem !important;
                            vertical-align: top;
                            border-top: none !important;
                        }

                        .membrete {
                            text-align: center;
                            margin: 15px;
                        }

                        .factura {
                            border: 1px solid lightgray;
                            min-height: 380px;
                            border-radius: 10px;
                        }

                        .icono {
                            font-size: 25px;
                        }

                        .btn2 {
                            font-weight: 400;
                            color: #797979;
                            background-color: lightgray;
                            text-align: center;
                            vertical-align: middle;
                            -webkit-user-select: none;
                            -moz-user-select: none;
                            -ms-user-select: none;
                            user-select: none;
                            background-color: transparent;
                            border: 1px solid transparent;
                            padding: .375rem .75rem;
                            font-size: 1rem;
                            line-height: 1.5;
                            border-radius: .25rem;
                            transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
                        }

                        .gray {
                            color: rgba(52, 73, 94, 0.94);
                            font-size: 26px;


                        }

                        .minus {
                            color: lightgray !important;
                            border: none !important;
                            margin-top: 3px;
                        }

                        .fotoProducto {
                            width: 99.4%;
                            height: 298px;
                            margin-left: 0.3%;
                            margin-top: 1px;

                        }

                        .bordeFoto {
                            border: 2px solid #909090;
                            margin-left: 10%;
                            height: 304px;
                            background-color: lightgray;


                        }
                    </style>

                </div>
                <!-- /page content -->

                <!-- footer content -->

                <!-- /footer content -->
            </div>
        </div>

        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <!-- FastClick -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <!-- NProgress -->
        <script src="../vendors/nprogress/nprogress.js"></script>
        <!-- iCheck -->
        <script src="../vendors/iCheck/icheck.min.js"></script>
        <!-- Datatables -->
        <script src="../vendors/datatables.net/js/jquery.dataTables.min.js"></script>
        <script src="../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
        <script src="../vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
        <script src="../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
        <script src="../vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
        <script src="../vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
        <script src="../vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
        <script src="../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
        <script src="../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
        <script src="../vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
        <script src="../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
        <script src="../vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
        <script src="../vendors/jszip/dist/jszip.min.js"></script>
        <script src="../vendors/pdfmake/build/pdfmake.min.js"></script>
        <script src="../vendors/pdfmake/build/vfs_fonts.js"></script>

        <!-- Custom Theme Scripts -->
        <script src="../build/js/custom.min.js"></script>

    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>