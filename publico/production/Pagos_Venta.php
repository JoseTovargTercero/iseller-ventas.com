<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');


if (!isset($_GET["metodo"])) {
    header("Location: ventas.php");
} else {
    $metodo_predifinido = $_GET["metodo"];
}




if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
    }

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    $query2 = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos = $conexion->query($query2);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

            $PesoDolar = $filaAlumnos['pesoDolar'];
            $Pesobolivar = $filaAlumnos['peso_bolivar'];
            $bolivarPesoTrans = $filaAlumnos['bolivarPesoTrans'];
            $dolarBolivar = $filaAlumnos['DolarBolivar'];
            $pesoBolivarPublicacion = $filaAlumnos['bolivarPesoVenta'];
        }
    }
    $topnav = topnav();
    $query4 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query4);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }

    $query5 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query5);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }

    ////////////////////////////
    ////////////////////////////
    ////////////////////////////

    if (isset($_POST['compraTipo'])) {


        $fechaVenta = $_POST['fechaVenta'];
        $compraTipo = $_POST['compraTipo'];
        $valVentaIni = $_POST['valVentaIni'];
        $valVentaMayor = $_POST['valVentaMayor'];


        if (isset($_POST['valVentaMayorBs'])) {
            $valorBolivarFinal1 = $_POST['valVentaMayorBs'];
            $valorPesoFinal1 = $_POST['valVentaMayorCop'];

            //   $valVentaMayorBs = $_POST['valVentaMayorBs'];
            //  $valVentaMayorCop = $_POST['valVentaMayorCop'];
        } else {
            //////Valor traido de la sumatoria de los precios indivuiduales para pesos y volivares
            $valorBolivarFinal1 = $_POST['valorBolivarFinal1'];
            $valorPesoFinal1 = $_POST['valorPesoFinal1'];
        }

        $tipoDespacho = $_POST['tipoDespacho'];







        $punto = $_POST['punto'];
        $pagoMovil = $_POST['pagoMovil'];
        $transferencia = $_POST['transferencia'];
        $bioPago = $_POST['bioPago'];
        $efectivo = $_POST['efectivo'];
        $pesos = $_POST['pesos'];
        $dolares = $_POST['dolares'];

        if ($tipoDespacho == 3) {
            $pagoTipo = 8;
        } else {
            $pagoTipo = $_POST['pagoTipo'];
        }

        //$valorDescuento = $_POST['valorDescuento'];

        if ($compraTipo == '4') {
            $valorFinalVenta = $valVentaMayor;
            $valorFinalVentaBs = $valVentaMayorBs;
            $valorFinalVentaCop = $valVentaMayorCop;
        } else {
            $valorFinalVenta = $valVentaIni;
        }

        if ($tipoDespacho == "2" || $tipoDespacho == "1" && $_POST['cedula'] != "") {
            $fdeCompra = date("d-m-Y h:i a");
            $cedula = $_POST['cedula'];
            $nombre = $_POST['nombre'];
            $telefono = $_POST['telefono'];
            $nombreNegocio = $_POST['nombreNegocio'];
            $direccionNegocio = $_POST['direccionNegocio'];

            if ($telefono == "") {
                $telefono = "0";
            }
            $query66 = "SELECT * FROM clientes WHERE email='$cedula' LIMIT 1";
            $buscarAlumnos66 = $conexion->query($query66);
            if ($buscarAlumnos66->num_rows > 0) {
                while ($filaAlumnos66 = $buscarAlumnos66->fetch_assoc()) {
                    $address = $filaAlumnos66['address'];
                    $created = $filaAlumnos66['created'];

                    $address += 1;
                    $created += $valorFinalVenta;

                    $insertar55 = "UPDATE clientes SET name='$nombre', phone='$telefono', address='$address', created='$created', modified='$fdeCompra', negocio='$nombreNegocio', direccion='$direccionNegocio' WHERE email='$cedula'";
                    mysqli_query($conexion, $insertar55);
                }
            } else {

                $insertar = "INSERT INTO clientes (name, email, phone, address, created, modified, negocio, direccion) VALUES ('$nombre','$cedula','$telefono','1','$valorFinalVenta','$fdeCompra','$nombreNegocio','$direccionNegocio')";
                $resultado2 = mysqli_query($conexion, $insertar);
            }


            if ($tipoDespacho == "2") {
                header("Location: AccionCarta.php?action=placeOrderCredito&fechaVenta=" . $fechaVenta . "&valorFinalVenta=" . $valorFinalVenta . "&valorFinalBs=" . $valorBolivarFinal1 . "&valorFinalCop=" . $valorPesoFinal1 . "&compraTipo=" . $compraTipo . "&pagoTipo=" . $pagoTipo . "&cedula=" . $cedula . "&nombreC=" . $nombre . "&telefono=" . $telefono . "&nombreNego=" . $nombreNegocio . "&direccion=" . $direccionNegocio . "");
            } elseif ($tipoDespacho == "1" && $_POST['cedula'] != "") {
                header("Location: AccionCarta.php?action=placeOrder&fechaVenta=" . $fechaVenta . "&valorFinalVenta=" . $valorFinalVenta . "&valorFinalBs=" . $valorBolivarFinal1 . "&valorFinalCop=" . $valorPesoFinal1 . "&compraTipo=" . $compraTipo . "&pagoTipo=" . $pagoTipo . "&cedula=" . $cedula . "&nombreC=" . $nombre . "&telefono=" . $telefono . "&nombreNego=" . $nombreNegocio . "&direccion=" . $direccionNegocio . "");
            } elseif ($tipoDespacho == "1" && $_POST['cedula'] != "") {
                header("Location: AccionCarta.php?action=placeOrder&fechaVenta=" . $fechaVenta . "&valorFinalVenta=" . $valorFinalVenta . "&valorFinalBs=" . $valorBolivarFinal1 . "&valorFinalCop=" . $valorPesoFinal1 . "&compraTipo=" . $compraTipo . "&pagoTipo=" . $pagoTipo . "&cedula=" . $cedula . "&nombreC=" . $nombre . "&telefono=" . $telefono . "&nombreNego=" . $nombreNegocio . "&direccion=" . $direccionNegocio . "&punto=" . $punto . "&pagoMovil=" . $pagoMovil . "&transferencia=" . $transferencia . "&bioPago=" . $bioPago . "&efectivo=" . $efectivo . "&pesos=" . $pesos . "&dolares=" . $dolares . "");
            }
        } else {
            header("Location: AccionCarta.php?action=placeOrder&fechaVenta=" . $fechaVenta . "&cedula=Sc&valorFinalVenta=" . $valorFinalVenta . "&valorFinalBs=" . $valorBolivarFinal1 . "&valorFinalCop=" . $valorPesoFinal1 . "&compraTipo=" . $compraTipo . "&pagoTipo=" . $pagoTipo . "&punto=" . $punto . "&pagoMovil=" . $pagoMovil . "&transferencia=" . $transferencia . "&bioPago=" . $bioPago . "&efectivo=" . $efectivo . "&pesos=" . $pesos . "&dolares=" . $dolares . "");
        }
    } // FINALIZA EL ISSET "CEDULA"

    // initializ shopping cart class
    include 'La-carta.php';
    $cart = new Cart;

    // redirect to home if cart is empty
    if ($cart->total_items() <= 0) {
        header('Location: index.php');
    }

    // set customer ID in session
    $_SESSION['sessCustomerID'] = 1;

    // get customer details by session customer ID
    $query = $conexion->query('SELECT * FROM clientes WHERE id = ' . $_SESSION['sessCustomerID']);
    $custRow = $query->fetch_assoc();
?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Pago</title>


        <?php require_once('includes/headers.php'); ?>


        <script src='peticion.js'></script>
        <script>
            $(obtener_registros_codigo());

            function obtener_registros_codigo(rep_codigo) {
                $.ajax({
                        url: 'consulta_alMayor.php',
                        type: 'POST',
                        dataType: 'html',
                        data: {
                            rep_codigo: rep_codigo
                        },
                    })

                    .done(function(resultado_codigo) {
                        $("#tabla_resultado_alMayor").html(resultado_codigo);
                    })
            }

            $(document).on('change', '#compraTipo', function() {
                var valorcompraTipo = $(this).val();



                if (valorcompraTipo != "") {
                    obtener_registros_codigo(valorcompraTipo);
                } else {
                    obtener_registros_codigo();
                }


                if (valorcompraTipo == '4') {} else {
                    $('#tabla_resultado_alMayor').html('');
                }
            });
        </script>
        <script src='peticion_datoscliente.js'></script>
        <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>
        <script src='ex/jquery.min.js'></script>
        <script src='ex/bootstrap.min.js'></script>

    <body>

        <style>
            .textBorrado {
                color: #ff9494;
                text-decoration: line-through
            }
        </style>

        <body class='nav-sm'>
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
                    <!-- top navigation -->
                    <?php echo $topnav ?>
                    <!-- /top navigation -->

                    <!-- /top navigation -->
                    <div class='right_col' role='main'>
                        <div class=''>
                            <h4>Detalles de la venta</h4>
                            <p style="margin-top: -10px;">Metodos de pago y tipo de venta</p>

                            <div class='clearfix'></div>
                            <div class='row   fadeInUp animated'>
                                <form name="formulario" action='' id="formulario" method='post' style="width: 100%;">
                                    <div class='col-lg-12'>
                                        <div class='x_panel'>
                                            <div class='x_title'>
                                                <h2>Detalles de la venta</h2>

                                                <div class='clearfix'></div>
                                            </div>


                                            <div class='form-group  '>
                                                <div style="display: none;" class="form-group col-lg-12">
                                                    <label for="fechaVenta" class="control-label">Fecha de venta</label>
                                                    <input type="date" required class="form-control" value="<?php echo date('Y-m-d') ?>" name="fechaVenta" id="fechaVenta">
                                                </div>
                                            </div>


                                            <div class="row">
                                                <div class="form-group col-lg-6">
                                                    <label class="control-label col-md-3 col-sm-3 ">Método de venta</label>
                                                    <select class="form-control" name="tipoDespacho" id="tipoDespacho" style="color: #1ABB9C" required>
                                                        <option value="1">Venta</option>
                                                        <option value="3">Venta fraccionada (Metodos de pago)</option>
                                                        <option value="2">Credito</option>

                                                    </select>
                                                </div>



                                                <section id="datosNegocio" class="col-lg-6">
                                                    <div class='form-group '>
                                                        <label class="control-label col-md-3 col-sm-3 ">Cliente</label>
                                                        <input class='form-control  col-lg-12' type='text' name='nombreNegocio' placeholder='Nombre del Negocio'>
                                                    </div>
                                                    <div style="display: none !important;">
                                                        <input class='form-control  col-lg-12' type='text' name='direccionNegocio' placeholder='Direccion'>
                                                    </div>
                                                </section>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-4" class="form-group">
                                                    <label class="control-label">Tipo de venta</label>
                                                    <select style="margin-top:10px" class="form-control" name="compraTipo" id="compraTipo">
                                                        <option value="1">Al detal</option>
                                                        <option value="4">Al mayor</option>
                                                    </select>
                                                </div>
                                                <section class="col-lg-8 row" id='tabla_resultado_alMayor' style="margin-top: 15px">
                                                </section>
                                            </div>
                                            <div class="row">


                                                <div class="col-lg-12">
                                                    <hr>
                                                    <h5 style="font-size: 18px;">Metodo de pago</h5>

                                                </div>

                                                <div class="col-lg-12" style="overflow: auto; height: 100px;">


                                                    <br>
                                                    <section id="datosMetodosPago">
                                                        <div class="metodos">
                                                            <div class="wrapper">
                                                                <input type="radio" name="pagoTipo" value="1" id="option1" required>
                                                                <input type="radio" name="pagoTipo" value="2" id="option2">
                                                                <input type="radio" name="pagoTipo" value="3" id="option3">
                                                                <input type="radio" name="pagoTipo" value="4" id="option4">
                                                                <input type="radio" name="pagoTipo" value="5" id="option5">
                                                                <input type="radio" name="pagoTipo" value="6" id="option6">
                                                                <input type="radio" name="pagoTipo" value="7" id="option7">

                                                                <label for="option1" class="option option1">
                                                                    <div class="dot"></div>
                                                                    <span>Punto</span>
                                                                </label>

                                                                <label for="option2" class="option option2">
                                                                    <div class="dot"></div>
                                                                    <span>P.Movil</span>
                                                                </label>

                                                                <label for="option3" class="option option3">
                                                                    <div class="dot"></div>
                                                                    <span>Transfe.</span>
                                                                </label>

                                                                <label for="option7" class="option option7">
                                                                    <div class="dot"></div>
                                                                    <span>BioPago</span>
                                                                </label>

                                                                <label for="option4" class="option option4">
                                                                    <div class="dot"></div>
                                                                    <span>Efectivo</span>
                                                                </label>

                                                                <label for="option5" class="option option5">
                                                                    <div class="dot"></div>
                                                                    <span>Dolares</span>
                                                                </label>

                                                                <label for="option6" class="option option6">
                                                                    <div class="dot"></div>
                                                                    <span>Pesos</span>
                                                                </label>


                                                            </div>

                                                        </div>

                                                    </section>
                                                </div>


                                            </div>
                                        </div>



                                        <script>
                                            //activa el check con id option1
                                            $(document).ready(function() {
                                                document.getElementById("<?php echo $metodo_predifinido ?>").checked = true;
                                            });
                                        </script>




                                    </div>
                                    <div class="col-lg-12">
                                        <div class='x_panel'>
                                            <div class='x_title'>
                                                <h2>Carrito del cliente</h2>
                                                <div class='clearfix'></div>
                                            </div>
                                            <div class="content-carrito">
                                                <div class="carrito">

                                                    <?php
                                                    if ($cart->total_items() > 0) {
                                                        // Obtener los artículos del carrito desde la sesión
                                                        $cartItems = $cart->contents();
                                                        $todoPeso = 0;
                                                        $todoBolivar = 0;
                                                    ?>
                                                        <!-- Tabla con estilo Bootstrap -->
                                                        <table class="table table-striped table-bordered">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>Nombre del Producto</th>
                                                                    <th>Cantidad</th>
                                                                    <th>Precio Total (COP)</th>
                                                                    <th>Precio Total (BS)</th>
                                                                    <th>Subtotal ($)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($cartItems as $item) {
                                                                    $todoPeso += $item["pricePeso"] * $item["qty"];
                                                                    $todoBolivar += $item["priceBolivar"] * $item["qty"];
                                                                ?>
                                                                    <tr>
                                                                        <td><strong><?php echo $item["name"]; ?></strong></td>
                                                                        <td><?php echo $item["qty"]; ?></td>
                                                                        <td><?php echo number_format($item["pricePeso"] * $item["qty"], 0, ',', '.'); ?> COP</td>
                                                                        <td><?php echo number_format($item["priceBolivar"] * $item["qty"], 2, ',', '.'); ?> BS</td>
                                                                        <td><?php echo round($item["subtotal"], 2, PHP_ROUND_HALF_DOWN); ?> $</td>
                                                                    </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr>
                                                                    <th colspan="2">Totales</th>
                                                                    <td style="font-weight: bold;"><?php echo number_format($todoPeso, 0, ',', '.'); ?> COP</td>
                                                                    <td style="font-weight: bold;"><?php echo number_format($todoBolivar, 2, ',', '.'); ?> BS</td>
                                                                    <td style="font-weight: bold;">
                                                                        <?php
                                                                        echo round($cart->total(), 2, PHP_ROUND_HALF_DOWN) . " <small>USD</small>";
                                                                        ?>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    <?php
                                                    } else { ?>

                                                    <?php } ?>

                                                    <?php $valorVenta = $cart->total();

                                                    $valorBolivarVenta = $todoBolivar;
                                                    $valorVentaPeso = $todoPeso;

                                                    ?>

                                                    <input name="valorBolivarFinal1" hidden value="<?php echo $valorBolivarVenta ?>">
                                                    <input name="valorPesoFinal1" hidden value="<?php echo $valorVentaPeso ?>">


                                                    <input class='form-control  col-lg-12' type='text' name='valVentaIni' hidden value='<?php echo $cart->total() ?>'>
                                                    <input class='form-control  col-lg-12' type='text' name='valVentaIniBs' hidden value='<?php echo $cart->total() ?>'>
                                                    <input class='form-control  col-lg-12' type='text' name='valVentaIniCop' hidden value='<?php echo $cart->total() ?>'>



                                                    <div class="row" id='precioDetal'>
                                                        <div class="col-lg-4 text-center" style="font-size: 2rem;"><?php
                                                                                                                    echo formatPeso($todoPeso) . " <small>COP</small>";
                                                                                                                    ?></div>
                                                        <div class="col-lg-4 text-center" style="font-size: 2rem;"><?php
                                                                                                                    echo number_format($todoBolivar, '2', ',', '.') . " <small>BS</small> ";
                                                                                                                    ?></div>

                                                        <div class="col-lg-4 text-center" style="font-size: 2rem;"><?php
                                                                                                                    echo round($cart->total(), 2, PHP_ROUND_HALF_DOWN) . " <small>$</small>";
                                                                                                                    ?></div>

                                                    </div>

                                                    <section style="margin-top: 25px;" id='tabla_resultado_resumen'></section>


                                                </div>
                                            </div>

                                            <br>



                                            <div class='footBtn text-center'>
                                                <section id="prueba"></section>
                                                <section id="desactivar">
                                                    <a href='ventas.php' class='btn btn2 btn-danger'> Cancelar</a>
                                                    <button class='btn btn2 btn-success ' id="btnVender">Continuar</button>
                                                </section>

                                                <section id="desactivar2">

                                                </section>

                                            </div>

                                        </div>



                                    </div>
                                </form>
                            </div>

                        </div>

                    </div>


                </div>


                <script>
                    function pagoFracionado() {

                        // la entrada de dinero en los diferentes metodos de pago aplicados al bolivar
                        var punto = document.formulario.punto.value;
                        var pagoMovil = document.formulario.pagoMovil.value;
                        var transferencia = document.formulario.transferencia.value;
                        var bioPago = document.formulario.bioPago.value;
                        var efectivo = document.formulario.efectivo.value;

                        var objetioDolar2 = Number(document.formulario.valVentaMayor2.value);
                        var objetioBs2 = Number(document.formulario.valVentaMayorBs2.value);
                        var objetioPeso2 = Number(document.formulario.valVentaMayorCop2.value);


                        //el total de dinero que el cliente ha cancelado
                        var sumaBs = Number(punto) + Number(pagoMovil) + Number(transferencia) + Number(bioPago) + Number(efectivo);
                        var pesos = Number(document.formulario.pesos.value) + 0.1;
                        var dolares = Number(document.formulario.dolares.value);

                        //Objetivos a alcanzar
                        var objetioDolar = objetioDolar2;
                        var objetioBs = objetioBs2;
                        var objetioPeso = objetioPeso2;



                        var BolivarCancelaPor = sumaBs * 100 / objetioBs;


                        var retanteBolivar1 = objetioBs - (BolivarCancelaPor / 100 * objetioBs);
                        var retantePeso1 = objetioPeso - (BolivarCancelaPor / 100 * objetioPeso);
                        var retanteDolar1 = objetioDolar - (BolivarCancelaPor / 100 * objetioDolar);
                        if (retanteBolivar1 < 0.1) {
                            var retanteBolivar3 = 0;
                            var retantePeso3 = 0;
                            var retanteDolar3 = 0;
                        } else {

                            var PesoCanceladoPor = pesos * 100 / retantePeso1;
                            var retanteBolivar2 = retanteBolivar1 - (PesoCanceladoPor / 100 * retanteBolivar1);
                            var retantePeso2 = retantePeso1 - (PesoCanceladoPor / 100 * retantePeso1);
                            var retanteDolar2 = retanteDolar1 - (PesoCanceladoPor / 100 * retanteDolar1);
                            if (retanteBolivar1 < 0.1) {
                                var retanteBolivar3 = 0;
                                var retantePeso3 = 0;
                                var retanteDolar3 = 0;
                            } else {
                                var DolarCaceladoPor = dolares * 100 / retanteDolar2;
                                var retanteBolivar3 = retanteBolivar2 - (DolarCaceladoPor / 100 * retanteBolivar2);
                                var retantePeso3 = retantePeso2 - (DolarCaceladoPor / 100 * retantePeso2);
                                var retanteDolar3 = retanteDolar2 - (DolarCaceladoPor / 100 * retanteDolar2);
                            }

                        }

                        /*  Number.prototype.round = function(places) {
                              return +(Math.round(this + "e+" + places) + "e-" + places);
                          }
                          */
                        var BsResta = retanteBolivar3;
                        var pesosRestan = retantePeso3 + 0.1;
                        var dolarResta = retanteDolar3;


                        if (isNaN(BsResta) || isNaN(pesosRestan) || isNaN(dolarResta)) {
                            var problema = "noNumber";
                        } else {
                            var problema = "not";
                        }
                        if (BsResta <= 0) {
                            if (problema == "not") {



                                $("#desactivar").html("<a href='ventas.php' class='btn btn2 btn-info'> Cancelar</a> <button class='btn btn2 btn-success '>Continuar</button>");
                            } else {
                                $("#desactivar").html("<a href='ventas.php' class='btn btn2 btn-info'> Cancelar</a> <button class='btn btn2 btn-success' disabled>Continuar</button>");
                            }


                            notificaciones = "";
                        } else {

                            notificaciones = "<table class='table'><thead><tr><th>Objetivo</th><th style='color: red'>Restan</th></tr></thead><tbody><tr><td>$" + objetioDolar + "</td><td style='color: red'>$" + dolarResta + "</td></tr><tr><td>" + new Intl.NumberFormat("es-ES").format(objetioBs) + " Bs</td><td style='color: red'>" + new Intl.NumberFormat("es-ES").format(BsResta) + " Bs</td></tr><tr><td>" + new Intl.NumberFormat("es-ES").format(objetioPeso) + " COP</td><td style='color: red'>" + new Intl.NumberFormat("es-ES").format(pesosRestan) + " COP</td></tr></tbody></table>";



                            if (problema == "not") {
                                $("#desactivar").html("<a href='ventas.php' class='btn btn2 btn-info'> Cancelar</a> <button class='btn btn2 btn-success' disabled>Continuar</button>");
                            } else {

                                $("#desactivar").html("<a href='ventas.php' class='btn btn2 btn-info'> Cancelar</a> <button class='btn btn2 btn-success' disabled>Continuar</button>");
                            }

                        }
                        $("#prueba").html(notificaciones);

                    }
                </script>

                <style>
                    .btn-group-vertical .btn,
                    .btn-group .btn {
                        margin-bottom: 0;
                        margin-right: 0;
                        max-height: 55px !important;
                    }

                    .btn2 {
                        margin-top: 20px !important;
                    }

                    .btn-secondary {
                        color: #909090 !important;
                        background-color: lightgray !important;
                        border-color: lightgray !important;
                    }

                    .vender {
                        bottom: 15px;
                        margin-left: 10px;
                        position: absolute
                    }

                    .contenedoe {
                        height: 165px;
                        overflow-y: scroll;
                    }

                    .contenedoe::-webkit-scrollbar {
                        width: 7px;
                    }

                    .contenedoe::-webkit-scrollbar-button {
                        background: #1ABB9C;
                        height: 10px;
                    }

                    .contenedoe::-webkit-scrollbar-thumb {
                        background: #1ABB9C;
                    }

                    .fijo {
                        height: 350px;
                    }

                    .responsi {
                        height: 300px;
                        overflow-y: auto;
                    }

                    .responsi::-webkit-scrollbar {
                        width: 7px;
                        background: #FFF;
                    }

                    .responsi::-webkit-scrollbar-thumb {
                        background: #1ABB9C;
                    }

                    .col-car-2 {
                        width: 210px;
                        position: relative;
                        min-height: 1px;
                        float: left;
                        padding-right: 10px;
                        padding-left: 10px;
                    }

                    .tile-stats {
                        height: 160px !important;
                        max-width: 100%;
                    }

                    .btn-group2 {
                        display: grid;
                        place-items: center;
                    }

                    .tile-stats .icon i {
                        margin: 0;
                        font-size: 40px;
                        line-height: 0;
                        vertical-align: bottom;
                        padding: 0;
                        color: #1ABB9C;
                    }

                    .htres {
                        margin-top: 15px !important;
                        color: dimgray !important;
                    }

                    .htres {
                        font-size: 18px;
                        margin-bottom: 10px !important;
                    }

                    .htress {
                        font-size: 15px;
                    }

                    .tile-stats .icon {
                        width: 100px;
                        height: 70px;
                        color: #BAB8B8;
                        position: absolute;
                        right: 5px;
                        top: 0px !important;
                        z-index: 1 !important;
                    }

                    .fotoProducto {
                        height: 70px;
                        width: 80px;
                    }

                    .right {
                        float: right;
                    }

                    .table {
                        width: 100%;
                        margin-bottom: 1rem;
                        color: #909090 !important;
                    }

                    .table td,
                    .table th {
                        padding: .75rem;
                        vertical-align: top;
                        border-top: none !important;
                    }

                    .table thead th {
                        vertical-align: bottom;
                        border-bottom: none !important;
                    }

                    .cant {
                        max-width: 40px;
                        min-height: 25px;
                        border: 1px solid #909090 !important;
                        color: #909090 !important;
                        padding-left: 10px;
                        border-radius: 5px;

                    }

                    .table td,
                    .table th {
                        padding: .1rem !important;
                    }

                    .btn-success2:not(:disabled):not(.disabled).active,
                    .btn-success:not(:disabled):not(.disabled):active,
                    .show>.btn-success.dropdown-toggle {
                        color: #fff;
                        background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 300%);
                        border: 1px solid #169F85;
                    }

                    .btn-success2 {
                        background: gainsboro;
                        border: 1px solid gray;
                        color: black;
                        opacity: .7;
                    }

                    .btn-success2:hover {
                        color: #fff;
                        background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 300%);
                        border: 1px solid #169F85;
                        opacity: 1;
                    }

                    .btn-success3 {
                        background: gainsboro;
                        border: 1px solid gray;
                        color: black;
                        opacity: .7;
                    }

                    .btn-success3:hover {
                        background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 300%);
                        border: 1px solid #169F85;
                        opacity: 1;
                        font-size: 17px;
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

            <script>
                $(obtener_registros2());

                function obtener_registros2(venta) {
                    $.ajax({
                            url: 'datosCliente.php',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                venta: venta
                            },
                        })

                        .done(function(resultado2) {
                            $("#datosCliente").html(resultado2);
                        })
                }

                $(document).on('change', '#tipoDespacho', function() {
                    var valorBusqueda2 = $(this).val();
                    if (valorBusqueda2 != "") {
                        obtener_registros2(valorBusqueda2);
                    } else {
                        obtener_registros2();
                    }
                });




                $(obtener_registros3());

                function obtener_registros3(negocio) {
                    $.ajax({
                            url: 'datosNegocio.php',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                negocio: negocio
                            },
                        })

                        .done(function(resultado3) {
                            $("#datosNegocio").html(resultado3);
                        })
                }

                $(document).on('keyup', '#cedula', function() {
                    var valorBusqueda3 = $(this).val();
                    if (valorBusqueda3 != "") {
                        obtener_registros3(valorBusqueda3);
                    } else {
                        obtener_registros3();
                    }
                });





                $(obtener_registros4());

                function obtener_registros4(metodos) {
                    $.ajax({
                            url: 'datosMetodosPago.php',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                metodos: metodos
                            },
                        })
                        .done(function(resultado4) {
                            $("#datosMetodosPago").html(resultado4);
                        })
                }
                $(document).on('change', '#tipoDespacho', function() {
                    var valorBusqueda4 = $(this).val();
                    if (valorBusqueda4 != "") {
                        console.log(valorBusqueda4)
                        obtener_registros4(valorBusqueda4);
                    } else {
                        obtener_registros4();
                    }
                });
            </script>

            <script>
                function obtener_registros_cantidad() {

                    let tipoDescuento = $('#tipoDescuento').val();
                    let rep_codigo2 = $('#valorDescuento').val();


                    $.ajax({
                            url: 'consulta_resumen.php?todoPeso=<?php echo $todoPeso ?>&todoBolivar=<?php echo $todoBolivar ?>',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                rep_codigo2: rep_codigo2,
                                tipoDescuento: tipoDescuento
                            },
                        })

                        .done(function(resultado) {
                            $("#tabla_resultado_resumen").html(resultado);
                        })
                }



                function verificarContenido() {
                    let contenido = $('#valorDescuento').val();

                    if (contenido.indexOf(',') != '-1') {
                        contenido = contenido.replaceAll(',', '')
                        $('#valorDescuento').val(contenido);
                        alert('1: No utilice separador de miles. 2: Si desea indicar un valor decimal utilice el punto "."')
                    }

                    var indices = [];
                    for (var i = 0; i < contenido.length; i++) {
                        if (contenido[i].toLowerCase() === ".") indices.push(i);
                    }

                    if (indices.length >= 2) {
                        $('#valorDescuento').val(contenido.substring(0, contenido.length - 1));
                    }


                    contenido = contenido.replace(/[^0-9.]/g, '');
                    $('#valorDescuento').val(contenido);


                    if (contenido != '' && contenido != '0') {
                        obtener_registros_cantidad();
                        $('#precioDetal').addClass('textBorrado');

                    } else {
                        $('#tabla_resultado_resumen').html('')
                        $('#precioDetal').removeClass('textBorrado');
                    }


                    if (contenido == '0') {
                        $('#tabla_resultado_alMayor').html('')
                        $('#compraTipo').val('1');
                    }
                }


                $(document).on('keyup', '#valorDescuento', function() {
                    $('#tabla_resultado_resumen').html('')

                    verificarContenido();
                });
                $(document).on('change', '#valorDescuento', function() {
                    $('#tabla_resultado_resumen').html('')

                    verificarContenido();
                });

                $(document).on('change', '#tipoDescuento', function() {
                    $('#tabla_resultado_resumen').html('')

                    verificarContenido();
                });





                $(document).ready(function() {
                    document.getElementById('formulario').addEventListener('submit', function(event) {
                        const tipoDespacho = document.getElementById('tipoDespacho').value;
                        const nombreNegocio = document.getElementById('nombreNegocio').value;

                        // Verificar condiciones
                        if (tipoDespacho === '2' && nombreNegocio.trim() === '') {
                            event.preventDefault(); // Detener el envío del formulario
                            alert('Por favor, complete el campo con el nombre del cliente.');
                        }
                    });


                });
            </script>

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


        <script>
            // Control de funciones desde el teclado
            document.addEventListener('keyup', function(event) {
                const key = event.key.toLowerCase();
                const btnVender = document.getElementById('btnVender');

                if (key === 'enter') {
                    btnVender?.click(); // El ? asegura que solo haga click si existe
                }
            });
            // Control de funciones desde el teclado
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>