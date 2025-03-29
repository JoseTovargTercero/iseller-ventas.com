<?php

require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');



if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();
    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }

    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Listado_Productos == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }

    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    $query2 = "SELECT * FROM empresa";
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


    $query2255 = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos225 = $conexion->query($query2255);
    if ($buscarAlumnos225->num_rows > 0) {
        while ($filaAlumnos225 = $buscarAlumnos225->fetch_assoc()) {
            $pesoDolar = $filaAlumnos225['pesoDolar'];
            $bsDolar = $filaAlumnos225['DolarBolivar'];
            $bolivarPesoTrans = $filaAlumnos225['bolivarPesoTrans'];
        }
    }




?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='icon' href='images/favicon.ico' type='image/ico' />

        <title>Productos</title>
        <!-- Bootstrap -->
        <link href="cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
        <link href="../vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome -->
        <link href="../vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
        <!-- NProgress -->
        <link href="../vendors/nprogress/nprogress.css" rel="stylesheet">
        <!-- iCheck -->
        <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
        <!-- Datatables -->

        <link href="../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
        <link href="../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">

        <!-- Custom Theme Style -->
        <link href="../build/css/custom.min.css" rel="stylesheet">

        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">

        <script src='js/jquery.min.js'></script>
        <script src='peticion.js'></script>
        <script src='peticion_producto.js'></script>


        <script src="../assets/sweetalert.min.js"></script>
        <script src="../assets/sweetalert2.all.min.js"></script>

        <script src='..//assets/AlertifyJS/alertify.min.js'></script>
        <script src="ex/jquery.min.js"></script>
        <script src="ex/bootstrap.min.js"></script>

        <?php
        @$accion = $_GET['accion'];
        @$origen = $_GET['origen'];

        switch ($accion) {
            case ('codigoBarras'):
                echo '<script>
            function mensaje(){	
			alertify.success("Producto actualizado"); }
            </script>
            <body onload="mensaje()">
            </body>';

                break;

            case ('borrado'):
                echo '<script>
            function mensaje(){	
			alertify.success("Producto borrado correctamente"); }
            </script>
            <body onload="mensaje()">
            </body>';

                break;

            case ('editado'):
                echo '<script>
            function mensaje(){	
			alertify.success("Producto actualizado."); }
            </script>
            <body onload="mensaje()">
            </body>';
                break;

            case ('favorito-SI'):
                echo '<script>
            function mensaje(){	
			alertify.success("Agregado a favoritos.");}
            </script>
            <body onload="mensaje()">
            </body>';
                break;

            case ('favorito-NO'):
                echo '<script>
            function mensaje(){	
			alertify.success("Quitado de favoritos.");}
            </script>
            <body onload="mensaje()">
            </body>';
                break;
            case ('editado'):
                echo '<script>
            function mensaje(){	
			alertify.success("Editado correctamente.");}
            </script>
            <body onload="mensaje()">
            </body>';
                break;
            case ('editado_error_letra'):
                echo '<script>
            function mensaje(){	
			alertify.error("No puede cambiar la inicial del nombre del producto.");}
            </script>
            <body onload="mensaje()">
            </body>';
                break;
        }

        ?>



    </head>

    <body class="nav-md">
        <div class="container body">
            <div class="main_container">
                <div class="col-md-3 left_col">

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
                <style>
                    .gray {
                        color: rgba(52, 73, 94, 0.94);
                        font-size: 24px;
                    }

                    .fav {
                        color: #1ABB9C;
                        font-size: 24px;
                    }

                    .nofav {
                        color: #b8b8b8f0;
                        font-size: 24px;
                    }

                    .favorite {
                        float: left
                    }

                    .actualizar {
                        float: right;
                    }

                    .texto {
                        position: absolute;
                        margin: auto;
                        transform: translate(-50%, 0px) scale(1);
                    }

                    .fileti {
                        font-size: 12px;

                    }
                </style>

                <!-- page content -->
                <div class="right_col">
                    <div class="col-lg-12">
                        <h4>Productos</h4>
                        <p style="margin-top: -10px;">Listado de productos</p>

                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <?php

                        @$accionE = $_GET['accion'];
                        @$codeEditar = $_GET['id'];

                        if ($accionE == "editar") {

                            $query7E = $conexion->query("SELECT * FROM productos WHERE id='$codeEditar' LIMIT 1");
                            if ($query7E->num_rows > 0) {
                                $tabla7E = '';
                                while ($row7E = $query7E->fetch_assoc()) {
                                    $nombrePro = $row7E["nombre"];
                                    $PrecioPro = $row7E["precio_compra"];
                                    $CantidadPro = $row7E["cantidad_unidades"];
                                    $porcentajePro = $row7E["porcentaje"];
                                    $origen = $row7E["origen"];
                                    $codigo_barras = $row7E["codigo_barras"];
                                    $proveedor = $row7E["proveedor"];
                                }
                            }
                            $visible = "";
                        } else {
                            $visible = "contain: strict";
                        }
                        ?>

                        <script>
                            setInterval(function() {
                                capturar1()
                                capturar()
                            }, 1000);


                            function capturar1() {
                                cambioDolar = parseFloat(<?php echo $bsDolar ?>);
                            }

                            //////////////////////////
                            //////////////////////////
                            //////////////////////////
                            //////////////////////////



                            function capturar() {
                                var precioMonedaOrigen = document.getElementById("precioMonedaOrigen").value;
                                var pruebaa = "2.515151";
                                // Obtenemos el valor por el Nombre
                                var selec = document.getElementById("moneda").value;


                                if (selec == "pesos") {

                                    var moneda1 = (precioMonedaOrigen / cambioPesoRecepcion) / 1000000;
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




                                    ///////
                                    ///////
                                    ///////
                                    ///////
                                    ///////
                                    /////// calculo a pesos

                                    var preciopesoVento = preciodolarVenta * parseFloat(<?php echo $pesoDolar ?>);

                                    var ventaPesoRoundListo = preciopesoVento.round(0); // 1.78
                                    let inputNumbP = ventaPesoRoundListo;

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
                                            separadoP.push(inputNumbP[0].substring(possP, (possP + 3)))
                                        }
                                    } else {
                                        separadoP = [inputNumbP[0]]
                                    }
                                    if (separadoP != "NaN") {
                                        document.getElementById('resultado3').value = separadoP.join(',') + '.' + inputNumbP[1] + ' COP'
                                    }
                                    ///////////////////


                                    //////////////////////
                                    //////////////////////

                                    var bolivarSalida = preciodolarVenta * cambioDolar;
                                    let tipoConversion = document.getElementById('origenProducto').value
                                    var tipoCambio_pesosBs = parseFloat(<?php echo $bolivarPesoTrans ?>);


                                    if (tipoConversion == 'c') {
                                        bolivarSalida = (preciopesoVento / tipoCambio_pesosBs) / 1000;
                                    } else {
                                        bolivarSalida = preciodolarVenta * cambioDolar;
                                    }

                                    var numb2 = bolivarSalida.round(2); // 1.78
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
                                            separados.push(inputNum[0].substring(pos, (pos + 3)))
                                        }
                                    } else {
                                        separados = [inputNum[0]]
                                    }



                                    if (separados != "NaN") {
                                        document.getElementById('resultado4').value = separados.join(',') + '.' + inputNum[1] + ' BS'
                                    }



                                } catch (e) {}
                            }
                        </script>


                        <style>
                            .right_col {
                                min-height: 940px !important;
                            }
                        </style>
                        <div class="col-lg-12" style="<?php echo $visible ?>">

                            <div class="x_content">



                                <form name='calculadora' action='../../configurar/listaProductos.php' method='POST' id='demo-form2' data-parsley-validate class='form-horizontal form-label-left'>
                                    <div class='row'>
                                        <div class='col-md-6 col-sm-6 '>
                                            <div class='x_panel'>
                                                <div class='x_title'>
                                                    <h2>Datos del Producto <small>* obligatorio</small></h2>
                                                    <div class='clearfix'></div>
                                                </div>
                                                <div class='x_content'>
                                                    <br />

                                                    <input class="form-control" type="text" hidden name="codigoEditar" value="<?php echo $codeEditar ?>">


                                                    <div class='item form-group'>
                                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Nombre <span class='required'>*</span>
                                                        </label>

                                                        <div class='col-md-9 col-sm-9 '>
                                                            <input type='text' id='nombre' name='nombre' value="<?php echo $nombrePro; ?>" required='required' class='form-control ' placeholder='Nombre del Producto'>
                                                        </div>
                                                    </div>

                                                    <div class='item form-group'>
                                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Precio de Compra <span class='required'>*</span>
                                                        </label>
                                                        <div class='col-md-5 col-sm-5 '>
                                                            <input type='text' value="<?php echo $PrecioPro; ?>" id='precioMonedaOrigen' onkeyup="capturar()" name='precioMonedaOrigen' required='required' class='form-control ' placeholder='Precio'>
                                                        </div>
                                                        <div class='col-md-4 col-sm-4'>
                                                            <select class="form-control" required='required' name="moneda" id="moneda" onchange="capturar()" division()>
                                                                <option value="dolares">Dolares</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <input type='text' id='precio' name='precio' hidden required='required' class='form-control ' placeholder='Precio en dolares' onKeyUp='division()'>

                                                    <div class='item form-group'>
                                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Cantidad <span class='required'>*</span>
                                                        </label>
                                                        <div class='col-md-9 col-sm-9 '>
                                                            <input type='text' id='cantidad' name='cantidad' value="<?php echo $CantidadPro; ?>" required='required' class='form-control ' placeholder='Unidades' onKeyUp='division()'>
                                                        </div>
                                                    </div>


                                                    <div class='item form-group'>
                                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Porcentaje <span class='required'>*</span>
                                                        </label>
                                                        <div class='col-md-9 col-sm-9 '>

                                                            <input type='text' id='porcentaje' name='porcentaje' value="<?php echo $porcentajePro; ?>" required='required' class='form-control ' placeholder='XXX' onKeyUp='division()'>
                                                            <?php

                                                            if ($origen == "nuevo") {
                                                                echo " <input type='text' hidden name='origen' value='nuevo'>";
                                                            } else {
                                                            }

                                                            ?>
                                                        </div>
                                                    </div>

                                                    <div class='item form-group'>
                                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Origen <span class='required'>*</span>
                                                        </label>
                                                        <div class='col-md-9 col-sm-9 '>
                                                            <select class="form-control" required='required' name="origenProducto" id="origenProducto" onchange="capturar()" division()>
                                                                <option value="">Seleccione</option>
                                                                <option <?php echo ($origen == 'v' ? 'selected' : '') ?> value="v">Venezolano</option>
                                                                <option <?php echo ($origen == 'c' ? 'selected' : '') ?> value="c">Colombiano</option>
                                                            </select>
                                                        </div>

                                                    </div>

                                                    <div class='item form-group'>
                                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Proveedor <span class='required'>*</span>
                                                        </label>
                                                        <div class='col-md-9 col-sm-9 '>
                                                            <input type='text' id='proveedor' name='proveedor' value="<?php echo $proveedor; ?>" required='required' class='form-control ' placeholder='Proveedor'>
                                                        </div>
                                                    </div>

                                                    <div class='item form-group'>
                                                        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Código de barras <span class='required'>*</span>
                                                        </label>
                                                        <div class='col-md-9 col-sm-9 '>
                                                            <input type='text' id='codigo_barra' name='codigo_barra' value="<?php echo $codigo_barras; ?>" required='required' class='form-control ' placeholder='Código de barras'>
                                                        </div>
                                                    </div>


                                                    <div class='ln_solid'></div>
                                                    <div class='item form-group'>
                                                        <div class='col-md-12 col-sm-12 '>
                                                            <button type='submit' class="btn btn-success actualizar">Actualizar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <body onload='capturar()'>
                                        </body>


                                        <script>
                                            document.addEventListener("DOMContentLoaded", function() {
                                                let input = document.getElementById("codigo_barra"); // Asegúrate de que el ID coincide con tu input
                                                let lastScan = 0; // Guarda el tiempo del último escaneo
                                                let timeoutDuration = 6000; // Tiempo en milisegundos para bloquear (ajústalo según tu lector)

                                                input.addEventListener("input", function() {});
                                            });
                                        </script>


                                        <div class='col-lg-6 '>
                                            <div class='x_panel'>
                                                <div class='x_title'>
                                                    <h2>Precios de venta <small>vista previa</small></h2>

                                                    <div class='clearfix'></div>
                                                </div>
                                                <div class='x_content'>
                                                    <br />
                                                    <form class='form-label-left input_mask'>

                                                        <div class='form-group row'>
                                                            <label class='col-form-label col-md-3 col-sm-3 '>Precio (Unidad)</label>
                                                            <div class='col-md-9 col-sm-9 '>
                                                                <input type='text' class='form-control' disabled='disabled' name='resultado' id='resultado'>
                                                                <span class='form-control-feedback right2' aria-hidden='true'><i class='fa fa-dollar'></i></span>
                                                            </div>
                                                        </div>

                                                        <div class='form-group row'>
                                                            <label class='col-form-label col-md-3 col-sm-3 '>Venta</label>
                                                            <div class='col-md-9 col-sm-9 '>
                                                                <input type='text' class='form-control' disabled='disabled' name='resultado2' id='resultado2'>
                                                                <span class='form-control-feedback right2' aria-hidden='true'><i class='fa fa-dollar'></i></span>
                                                            </div>
                                                        </div>

                                                        <div class='form-group row'>
                                                            <label class='col-form-label col-md-3 col-sm-3 '>Venta</label>
                                                            <div class='col-md-9 col-sm-9 '>
                                                                <input type='text' class='form-control' readonly='readonly' name='resultado3' id='resultado3'>
                                                                <span class='form-control-feedback right2' aria-hidden='true'><strong>COP</strong></span>
                                                            </div>
                                                        </div>

                                                        <div class='form-group row'>
                                                            <label class='col-form-label col-md-3 col-sm-3 '>Venta
                                                            </label>
                                                            <div class='col-md-9 col-sm-9 '>
                                                                <input class='date-picker form-control' type='text' readonly='readonly' name='resultado4' id='resultado4'>
                                                                <span class='form-control-feedback right2' aria-hidden='true'><strong>BS</strong></span>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>

                            </form>

                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="x_panel   fadeInUp animated">
                            <div class="x_title">
                                <h2>Productos</h2>

                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                                <div class="row">

                                    <div class="col-lg-12">
                                        <div class="card-box table-responsive">

                                            <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                                                <thead>
                                                    <tr class="headings">
                                                        <th>#</th>
                                                        <th>Nombre </th>
                                                        <th>Compra</small></th>
                                                        <th>Cant.</th>
                                                        <th>%</th>
                                                        <th>Stock</th>
                                                        <th>USD</th>
                                                        <th>COP</th>
                                                        <th>BS</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <?php




                                                    if ($accionE == "editar") {
                                                        $query6 = $conexion->query("SELECT * FROM productos WHERE activo='0' AND codigo!='$codeEditar'  ORDER BY id ASC");
                                                    } else {
                                                        $query6 = $conexion->query("SELECT * FROM productos WHERE activo=0  ORDER BY id ASC");
                                                    }


                                                    if ($query6->num_rows > 0) {
                                                        $tabla6 = '';
                                                        $contador = 1;
                                                        while ($row6 = $query6->fetch_assoc()) {
                                                            $cantidadUnidad = $row6["cantidad_unidades"];
                                                            $precioDolarCompra = (float) $row6["precio_compra"] / $cantidadUnidad;
                                                            $porcentaje = $row6["porcentaje"];
                                                            $foto = $row6["foto"];
                                                            $codeProducto = $row6["codigo"];
                                                            $id_p = $row6["id"];


                                                            $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;


                                                            $precioDolarVenta = number_format($precioDolarVenta, '2', '.', '.');


                                                            $precioBsVenta = $precioDolarVenta * $bsDolar;
                                                            $precioPesoVenta = $precioDolarVenta * $pesoDolar;


                                                            $precioPesoVenta =   number_format($precioPesoVenta, '0', ',', '.');
                                                            $precioBsVenta =   number_format($precioBsVenta, '2', ',', '.');


                                                            if ($row6['favorito'] == 1) {
                                                                $favProducto =  '<a href="../../configurar/listaProductos.php?id=' . $codeProducto . '&favorito=NO"><i class="fav line icon-star"></i></a>';
                                                            } else {
                                                                $favProducto =  '<a href="../../configurar/listaProductos.php?id=' . $codeProducto . '&favorito=SI"><i class="nofav line icon-star"></i></a>';
                                                            }

                                                            $categoria = "Ninguna";



                                                            if ($foto == "SI") {
                                                                $imgProducto = '<img  class="avatar" alt="Avatar" src="images/stock/' . $codeProducto . '.jpg" alt="">';
                                                            } else {
                                                                $imgProducto = "";
                                                            }



                                                            $nombre =  $row6["nombre"];
                                                            $tabla6 .= '
                                                             <tr class="even pointer" id="row' . $row6["id"] . '">
                                                              <td class=" ">' . $contador++ . '</td>
                                                              <td class=" ">' . $row6["nombre"] . '</td>
                                                              <td class=" ">' . number_format((float) $row6["precio_compra"], '2', ',', '.') . ' $</td>
                                                              <td class=" ">' . $row6["cantidad_unidades"] . '</td>
                                                              <td class=" ">' . $row6["porcentaje"] . '%</td>
                                                              <td class=" ">' . $row6["stock"] . '</td>
                                                              <td class=" ">' . $precioDolarVenta . '</td>
                                                              <td class=" ">' . $precioPesoVenta . '</td>
                                                              <td class="a-right a-right ">' . $precioBsVenta . ' </td>';


                                                            $tabla6 .=  '
                                                              <td style="text-align: center"  class=""><a href="?id=' . $id_p . '&accion=editar"><i class="gray  line icon-pencil"></i></a></td>

                                                              <td style="text-align: center" ><a href="ficha.php?id=' . $row6["id"] . '"><i style="color: #41c1af" class="gray line icon-chart"></i></a></td>
                                                              <td style="text-align: center"  class="">
                                                              <a style="cursor: pointer" onclick="confirm(' . $row6["id"] . ')"><i class="gray line icon-trash"></i></a>
                                                            </tr>';
                                                        }
                                                        echo $tabla6;
                                                    }

                                                    /*
                                                          
 href="../../configurar/listaProductos.php?id=' . $codeProducto . '&borrar=borrar"

                                                        */
                                                    ?>
                                                </tbody>
                                            </table>


                                            <script>
                                                function confirm(id) {
                                                    Swal.fire({
                                                        title: 'Esta seguro?',
                                                        html: 'Se eliminara el producto ¿desea continuar?',
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
                                                            url: '../../configurar/deleteProAjax.php',
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer>
                <div class="pull-right">
                    i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
                </div>
                <div class="clearfix"></div>
            </footer>
            <!-- /footer content -->
        </div>
        </div>
        <style>
            .form-control-feedback.right2 {
                border-left: 1px solid #ccc;
                right: 25px !important;
                padding-left: inherit;
            }

            .favorite {
                float: left
            }

            .actualizar {
                float: right;
            }

            .texto {
                position: absolute;
                margin: auto;
                transform: translate(-50%, 0px) scale(1);
            }


            input[type=file] {
                display: block;
            }



            .fileinput-button input {
                cursor: pointer;
                direction: ltr;
                font-size: 16px;
                margin: 0;
                opacity: 0;
                right: 0;
                top: 50px;
            }

            .fileinput-button {
                min-width: 100% !important;
            }

            input[type=file] {
                display: block;
                min-width: 100% !important;
            }


            #photo {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                width: 10%;
                height: 10%;
                display: inline-flex;
                opacity: 0;
                cursor: pointer;

            }

            #estilo_foto {


                border-radius: 25px;
                position: absolute;
                margin-left: 24%;
                margin-right: 24%;
                max-width: 100%;

            }

            #estilo_foto_car {


                border-radius: 25px !important;
                position: absolute !important;
                margin-left: 30.5% !important;
                margin-right: 30.5% !important;
                max-width: 100% !important;

            }

            .gray {
                color: #b8b8b8f0;
                font-size: 18px;


            }
        </style>
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
        <script>
            document.getElementById('codigo_barra').addEventListener('click', function() {

            })
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>