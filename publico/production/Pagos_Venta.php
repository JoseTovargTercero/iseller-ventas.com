<?php
require_once('includes/requires.php');

if (!isset($_GET["metodo"])) {
    header("Location: ventas.php");
} else {
    $metodo_predifinido = $_GET["metodo"];
}



$topnav = topnav();

if (isset($_POST['valVentaIni'])) {



    //////Valor traido de la sumatoria de los precios indivuiduales para pesos y volivares
    $valorBolivarFinal1 = $_POST['valorBolivarFinal1'];
    $valorPesoFinal1 = $_POST['valorPesoFinal1'];


    $tipoDespacho = $_POST['tipoDespacho'];



    $pagoTipo = $_POST['pagoTipo'];
    $valorFinalVenta = $_POST['valVentaIni'];

    $nombre = $_POST['nombreNegocio'];


    if ($tipoDespacho == "2") {
        header("Location: ../../configurar/accion_carta.php?action=placeOrderCredito&valorFinalVenta=" . $valorFinalVenta . "&valorFinalBs=" . $valorBolivarFinal1 . "&valorFinalCop=" . $valorPesoFinal1 . "&compraTipo=1&pagoTipo=" . $pagoTipo . "&nombreC=" . $nombre . "");
    } elseif ($tipoDespacho == "1" && $_POST['nombreNegocio'] != "") {
        header("Location: ../../configurar/accion_carta.php?action=placeOrder&valorFinalVenta=" . $valorFinalVenta . "&valorFinalBs=" . $valorBolivarFinal1 . "&valorFinalCop=" . $valorPesoFinal1 . "&compraTipo=1&pagoTipo=" . $pagoTipo . "&nombreC=" . $nombre . "");
    }
} // FINALIZA EL ISSET "CEDULA"

// initializ shopping cart class
include '../../configurar/la-carta.php';
$cart = new Cart;

// redirect to home if cart is empty
if ($cart->total_items() <= 0) {
    header('Location: ventas.php');
}
?>
<!DOCTYPE html>
<html lang='es'>

<style>
    input[type="radio"] {
        display: none;
    }
</style>

<head>

    <title>Pago</title>


    <?php require_once('includes/headers.php'); ?>

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

                                        <div class="row">
                                            <div class="form-group col-lg-6">
                                                <label class="control-label col-md-3 col-sm-3 ">Método de venta</label>
                                                <select class="form-control" name="tipoDespacho" id="tipoDespacho" style="color: #1ABB9C" required>
                                                    <option value="1">Venta</option>
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
        </script>

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- Custom Theme Scripts -->
        <script src='../build/js/custom.min.js'></script>

    </body>


</body>

</html>