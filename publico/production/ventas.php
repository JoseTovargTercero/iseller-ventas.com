<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }

    $topnav = topnav();


    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Vender == 0) {


            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }

    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];


    $query7 = $conexion->query("SELECT * FROM cambio WHERE id='1'");
    if ($query7->num_rows > 0) {
        while ($row7 = $query7->fetch_assoc()) {
            $pesoDolar = $row7['pesoDolar'];
            $DolarBolivar = $row7['DolarBolivar'];
            $bolivarPesoTrans = $row7['bolivarPesoTrans'];
        }
    }
    $query3 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query3);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }

    include 'La-carta.php';
    $cart = new Cart;


    $_SESSION["ventas"] = "activa";
    if (@$_SESSION["dist_ventas"] == "activa") {
        unset($_SESSION["dist_ventas"]);
        $cart->destroy();
    }







    /* CARGAR PRODUCTOS PARA EL LECTOR */

    $query = $conexion->query("SELECT cantidad_unidades, origen, precio_compra, porcentaje, nombre, id, stock, codigo_barras FROM productos  WHERE activo= 0  AND codigo_barras!=''");
    $data = [];
    $codigos = [];


    if ($query->num_rows > 0) {
        while ($row = $query->fetch_assoc()) {

            if (strlen(trim($row['codigo_barras'])) > 5) {

                $cantidadUnidad = $row["cantidad_unidades"];
                $origen = $row["origen"];

                $precioDolarCompra = (float) $row["precio_compra"] / (float) $cantidadUnidad;
                $porcentaje = $row["porcentaje"];
                $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;

                $precioDolarVenta = number_format($precioDolarVenta, '2', '.', ',');

                $precioPesoVenta = $precioDolarVenta * $pesoDolar;
                if ($origen == 'c') {
                    $precioBsVenta = ($precioPesoVenta / $bolivarPesoTrans) / 1000;
                } else {
                    $precioBsVenta = $precioDolarVenta * $DolarBolivar;
                }


                //        $precioPesoVenta  = number_format(, '0', ',', '.');
                //        $precioBsVenta = number_format(, '2', ',', '.');

                $nombre = strtoupper($row["nombre"]);
                // quitar caracteres especiales del nombre
                $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);

                $codigo = $row['codigo_barras'];
                $data[trim($codigo)] = [
                    'id' => $row['id'],
                    'stock' => $row['stock'],
                    'nombre' =>  "$nombre",
                    'precio_dolar_visible' => $precioDolarVenta,
                    'precio_peso_visible' => $precioPesoVenta,
                    'precio_bs_visible' => $precioBsVenta,
                ];

                array_push($codigos, $codigo);
            }
        }
    }

    /* CARGAR PRODUCTOS PARA EL LECTOR */

?>

    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Ventas </title>
        <?php require_once('includes/headers.php'); ?>

        <?php
        switch (@$_GET['accion']) {
            case ('vendido'):
                $mensaje = 'venta';
                break;
            case ('credito'):
                $mensaje = 'none';
                break;
            case ('descuento'):
                $mensaje = 'descuento';
                break;
            default:
                $mensaje = 'none';
                break;
        }
        ?>
    </head>

    <?php
    /*   foreach ($data as $key => $value) {
        # code...
    }
*/


    ?>
    <script>
        var productos = <?php echo json_encode($data); ?>;
        var codigos = []

        <?php

        foreach ($codigos as  $value) {
            echo 'codigos.push("' . trim($value) . '");';
        }

        ?>

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

        function notificacion(params) {
            if (params != 'none') {
                let text;
                let type;
                if (params == 'venta') {
                    text = 'Venta concretada con exito';
                    type = 'success'
                } else if (params == 'descuento') {
                    text = 'Se descontaron productos del almacen';
                    type = 'info'
                }

                Toast.fire({
                    icon: type,
                    title: text
                })
            }
        }
    </script>


    <style>
        .form-control {
            background-color: #fff !important;
        }

        .swal2-container {
            z-index: 99999;
        }

        .precio {
            font-size: 1rem;
        }

        .item {
            padding: 5px;
            border-bottom: 1px solid #f9f9f9;
        }

        .hide {
            display: none !important;
        }

        .loader {
            width: 48px;
            height: 6px;
            display: block;
            margin: auto;
            position: relative;
            border-radius: 4px;
            color: #FFF;
            box-sizing: border-box;
            animation: animloader 0.6s linear infinite;
        }

        @keyframes animloader {
            0% {
                box-shadow: -10px 20px, 10px 35px, 0px 50px
            }

            25% {
                box-shadow: 0px 20px, 0px 35px, 10px 50px
            }

            50% {
                box-shadow: 10px 20px, -10px 35px, 0px 50px
            }

            75% {
                box-shadow: 0px 20px, 0px 35px, -10px 50px
            }

            100% {
                box-shadow: -10px 20px, 10px 35px, 0px 50px
            }
        }

        .contenedor-loader {
            z-index: 99999;
            background-color: #0000006b;
            height: 100%;
            width: 100%;
            place-items: center;
            display: grid;
            position: fixed;
        }

        .dgrid-center {
            display: grid;
            place-items: center;
        }

        .btn-secondary {
            color: #909090 !important;
            background-color: lightgray !important;
            border-color: lightgray !important;
        }

        .responsi {
            height: 80%;
            overflow-y: auto;
        }

        .responsi::-webkit-scrollbar {
            height: 7px;
            width: 7px;
            background: #FFF;
            margin-bottom: 15px;
        }

        .responsi::-webkit-scrollbar-thumb {
            background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 600%);
            border-radius: 5px;
        }



        .table thead th {
            vertical-align: bottom;
            border-bottom: none !important;
        }

        .input-td {
            text-align: center;
            border-radius: 5px;
            max-width: 50px;
            min-height: 25px;
            border: 1px solid #909090 !important;
            color: #909090 !important;
        }

        .table td,
        .table th {
            padding: 5px !important;
        }

        #result-escaner {
            position: relative;
            background-color: #ffffffd1;
        }

        #tabla_resultado_codigo_producto td:nth-child(1) {
            display: grid;
            place-items: center;
        }

        #bg-img {
            position: absolute;
            width: 100%;
            height: 50vh;
            /* Mezcla la imagen y el color */
            background-position: center;
            background-size: 48%;
            background-repeat: no-repeat;
        }

        #bg-img::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Color semitransparente */
            pointer-events: none;
            /* Asegura que el contenido sea accesible */
        }


        .text-total {
            font-size: 18px !important;
            font-weight: bold;
        }
    </style>
    <div class="contenedor-loader" id="cargando">
        <span class="loader"></span>
    </div>

    <body class='nav-sm' onload="notificacion('<?php echo $mensaje ?>')" style="background-color: #ebebeb;">
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
                <div class="right_col" role='main'>
                    <div class=''>

                        <h4 class="mb-0">Ventas</h4>
                        <p>Caja de despacho</p>

                        <div class="row">
                            <div class="col-lg-4" id="sect-left">
                                <div class="x_panel" style="height: min-content;">
                                    <div class="x_title d-flex justify-content-between">
                                        <button class="btn btn-success btn-sm" id="open-modal">Consulta</button>

                                        <button class="btn btn-danger btn-sm" id="delete-scan">Descartar</button>

                                    </div>
                                    <div class="x_content" style="height: 60vh; overflow: hidden;">

                                        <div id="bg-img"></div>
                                        <section class="mt-3" id="result-escaner">
                                        </section>

                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8" id="sect-right">
                                <div class="x_panel" style="height: 98%;">
                                    <div class="x_title">
                                        <h2 style="font-size: 15px; font-weight: bold">Carrito </h2>

                                        <div class="clearfix"></div>
                                    </div>


                                    <div class="x_content fijo">
                                        <div class="responsi">
                                            <table class="table table-striped">


                                                <thead class="thead-dark" style="min-width:100%; ">
                                                    <tr class="">
                                                        <th style="width:10%" class="column-title">Cant.</th>
                                                        <th style="width:30%" class="column-title">Producto</th>
                                                        <th style="width:20%" class="column-title">Peso</th>
                                                        <th style="width:20%" class="column-title">BS</th>
                                                        <th style="width:10%" class="column-title">Dolares</th>
                                                        <th style="width:5%" class="column-title"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tabla-carrito">


                                                </tbody>


                                            </table>

                                        </div>

                                        <div class="footer d-flex justify-content-between hide" id="botones_acciones">
                                            <a onclick="confirmarDescuento()" class="btn btn-danger " style="color:white; cursor: pointer">Descontar</a>
                                            <button class="btn btn-light" id="calcularVuelto">Vuelto</button>
                                            <button class="btn btn-warning text-dark" id="calcularDiferencia">Diferencia</button>

                                            <button onclick="confirmarVenta()" id="btn-vender" class="btn btn-success" style="color:white;">Vender</button>
                                        </div>
                                    </div>


                                </div>
                            </div>

                            <?php
                            if ($_SESSION['nivel'] == '1') {
                            ?>

                                <div class="col-lg-12">
                                    <div class="x_panel" style="padding-bottom: 30px;">
                                        <div class="x_title">
                                            <h2 style="font-size: 15px; font-weight: bold">Ultimas ventas realizadas</h2>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="x_content ">
                                            <div class="">
                                                <table id='datatable-responsive' class='table table-striped table-bordered' style='width:100%'>
                                                    <thead>
                                                        <tr class='headings'>
                                                            <th style="padding: 10px !important" class='column-title'>#</th>
                                                            <th style="padding: 10px !important" class='column-title'>Tipo</th>
                                                            <th style="padding: 10px !important" class='column-title'>Pago por</th>
                                                            <th style="padding: 10px !important" class='column-title'>Usuario</th>
                                                            <th style="padding: 10px !important" class='column-title'>Fecha</th>
                                                            <th style="padding: 10px !important" class='column-title'>Monto</th>
                                                            <th style="padding: 10px !important" class='column-title'>Detalles</th>
                                                            <th style="padding: 10px !important; text-align: center" class='column-title'>Ticket</th>
                                                            <th style="width: 7%; padding: 10px !important" class='column-title'>Eliminar</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <?php
                                                        $porductos = '';
                                                        $query77 = "SELECT * FROM orden ORDER BY id DESC LIMIT 3";
                                                        $buscarAlumnos77 = $conexion->query($query77);
                                                        if ($buscarAlumnos77->num_rows > 0) {
                                                            $contador = 1;
                                                            while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                                                $users = $filaAlumnos77['customer_id'];
                                                                $orderid = $filaAlumnos77['id'];

                                                                $query999999999 = "SELECT * FROM usuarios WHERE id='$users'";
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

                                                                switch ($filaAlumnos77['tipoPago']) {

                                                                    case ('1'):
                                                                        $pagoPor = 'Punto';
                                                                        break;

                                                                    case ('2'):
                                                                        $pagoPor = 'Pago Movil';
                                                                        break;

                                                                    case ('3'):
                                                                        $pagoPor = 'Transferencia';
                                                                        break;

                                                                    case ('4'):
                                                                        $pagoPor = 'BS Efectivo';
                                                                        break;

                                                                    case ('5'):
                                                                        $pagoPor = 'Dolares';
                                                                        break;

                                                                    case ('6'):
                                                                        $pagoPor = 'Pesos';
                                                                        break;
                                                                    case ('7'):
                                                                        $pagoPor = 'Biopago';
                                                                        break;
                                                                    case ('8'):
                                                                        $pagoPor = 'Fraccionado';
                                                                        break;
                                                                    default:
                                                                        $pagoPor = 'Pendiente';
                                                                }

                                                                if ($filaAlumnos77['status'] == '4') {
                                                                    $tVenta = 'Al mayor';
                                                                } elseif ($filaAlumnos77['status'] == '1') {
                                                                    $tVenta = 'Al detal';
                                                                } elseif ($filaAlumnos77['status'] == '3') {
                                                                    $tVenta = 'Descuento';
                                                                } else {
                                                                    $tVenta = 'Crédito';
                                                                }

                                                                echo '
                                                            <tr class="even pointer">
                                        
                                                            <td class=" ">' . $contador++ . '</td>
                                                                                        <td>' . $tVenta . '</td>
                                                            <td>' . $pagoPor . '</td>
                                                            
                                                            <td>' . $usuario1 . '</td>
                                                            <td>' . $filaAlumnos77['created'] . '</td>
                                                            <td>$' . number_format($filaAlumnos77['total_price'], '2', ',', '.') . '</td>
                                                            <td><a href="detallesVenta.php?id=' . $filaAlumnos77['id'] . '">Detalles</a></td>
                                                            
                                                            <td style="text-align: center"><a style="font-size: 22px" href="ticket.php?id=' . $filaAlumnos77['id'] . '"><i class="line icon-print"></i></a></td>
                                                            
                                                            <td><a class="btn btn-info btn-sm" style="cursor: pointer; color: white" onclick="confirm(' . $filaAlumnos77["id"] . ')">Deshacer</a></td>

                                                    
                                                            
                                                            
                                        
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

                            <?php
                            }
                            ?>

                        </div>

                    </div>


                    <?php require('../assets/templates/modal.html'); ?>

                    <!-- /page content -->

                    <!-- footer content -->
                    <footer>
                        <div class='pull-right'>
                            i-SELLER - by <a href=''>Jose Ricardo Tovarg III</a>
                        </div>
                        <div class='clearfix'></div>
                    </footer>
                    <!-- /footer content -->
                </div>
            </div>



            <script src='peticion.js'></script>
            <!-- jQuery -->
            <script src="../vendors/jquery/dist/jquery.min.js"></script>
            <!-- Bootstrap -->
            <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
            <!-- FastClick -->
            <script src="../vendors/fastclick/lib/fastclick.js"></script>
            <script src="../vendors/nprogress/nprogress.js"></script>
            <script src="../build/js/custom.min.js"></script>
            <script src="../build/js/modal.js"></script>
            <!-- FastClick -->
            <script>
                var total_pesos = 0;
                var total_dolares = 0;
                var total_bolivares = 0;



                function confirmarVenta(param) {
                    // Opciones de pago habituales

                    // quitar el focus de btn-vender

                    $('button').blur();

                    if ($('#result-escaner').find('.btn-add-to-car').length > 0) {
                        Swal.fire({
                            title: 'Atención',
                            html: 'Hay un producto en la cola de ventas, agréguelo al carrito o descártelo antes de continuar',
                            icon: 'warning',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#32d7c0',
                        })
                        return
                    }

                    const opcionesPago = `
                    <select id="metodoPago" class="form-control">
                        <option value="">Seleccione</option>
                        <option value="option1">Punto</option>
                        <option value="option2">Pago Movil</option>
                        <option value="option3">Transferencia</option>
                        <option value="option7">BioPago</option>
                        <option value="option4">Efectivo</option>
                        <option value="option5">Dolares</option>
                        <option value="option6">Pesos</option>
                    </select>`;

                    // Mostrar el diálogo
                    Swal.fire({
                        title: 'Selecciona un método de pago',
                        html: opcionesPago,
                        confirmButtonText: 'Continuar',
                        confirmButtonColor: '#32d7c0',
                        preConfirm: () => {
                            // Obtener el valor seleccionado
                            const metodoPago = document.getElementById('metodoPago').value;
                            if (!metodoPago) {
                                Swal.showValidationMessage('Por favor, selecciona un método de pago');
                            }
                            return metodoPago; // Retornar el valor seleccionado
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const metodoPago = result.value;
                            // Redirigir a pagos_Venta.php con el método de pago en la URL
                            window.location.href = `pagos_Venta.php?metodo=${encodeURIComponent(metodoPago)}`;
                        }
                    });
                }






                document.getElementById('delete-scan').addEventListener('click', function() {
                    document.getElementById('result-escaner').innerHTML = '';

                })

                function tabla_carrito() {
                    $.ajax({
                            url: 'carrito.php',
                            type: 'POST',
                            dataType: 'html'
                        })
                        .done(function(result) {

                            let resultado = JSON.parse(result)
                            $("#tabla-carrito").html('');
                            if (resultado.cantidad > 0) {

                                resultado.carrito.forEach(element => {
                                    $("#tabla-carrito").append(`<tr>
                                        <td>${element.cantidad}</td>
                                        <td>${element.nombre}</td>
                                        <td>${element.subtotalPeso} P</td>
                                        <td>${formatNumber(element.subtotalBolivar)} Bs</td>
                                        <td>$${formatNumber(element.subtotalDolar)}</td>
                                        <td>
                                            <button class="btn btn-info btn-sm" onclick="quitar_producto('${element.id}')"><i class="fa fa-trash-o"></i></button>
                                        </td>
                                    </tr>`);
                                });


                                total_pesos = formatPeso(resultado.total.pesos);
                                total_dolares = resultado.total.dolares;
                                total_bolivares = resultado.total.bolivares;

                                $("#tabla-carrito").append(`<tr>
                                        <td></td>
                                        <td><b>TOTAL:</b> </td>
                                        <td id="precio-total-pesos" class="text-total text-info">${formatNumber(formatPeso(resultado.total.pesos))} P</td>
                                        <td id="precio-total-bolivar" class="text-total text-danger">${formatNumber(resultado.total.bolivares)} Bs</td>
                                        <td id="precio-total-dolar" class="text-total text-success">$${formatNumber(resultado.total.dolares)}</td>
                                        <td>
                                        </td>
                                    </tr>`);

                                $('#botones_acciones').removeClass('hide')
                            } else {
                                $('#botones_acciones').addClass('hide')
                            }

                        })
                }

                tabla_carrito()


                function calcularVuelto() {

                    total_pesos = String(total_pesos).replace(',', '');

                    Swal.fire({
                        title: 'Indique la cantidad recibida',
                        html: `<input type="number" id="cantidadRecibida" class="swal2-input" placeholder="Cantidad recibida">
        `,
                        confirmButtonText: 'Calcular vuelto',
                        confirmButtonColor: '#32d7c0',
                        preConfirm: () => {
                            const cantidadRecibida = parseFloat(document.getElementById('cantidadRecibida').value);


                            if (isNaN(cantidadRecibida) || cantidadRecibida <= 0) {
                                Swal.showValidationMessage('Por favor, ingresa una cantidad válida');
                            }

                            return {
                                cantidadRecibida
                            }; // Retornar ambos valores
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const {
                                cantidadRecibida
                            } = result.value;

                            // Calcular el vuelto según el método de pago
                            let vueltoPesos = cantidadRecibida - parseInt(total_pesos);
                            let vueltoDolares = cantidadRecibida - total_dolares;
                            let vueltoBolivares = cantidadRecibida - total_bolivares;

                            // Mostrar resultados
                            Swal.fire({
                                title: 'Vuelto calculado',
                                html: `
                    <p><strong class="text-total text-info">Pesos:</strong> <span style="font-size: 18px">${formatNumber(vueltoPesos.toFixed(2))}</span></p>
                    <p><strong class="text-total text-danger">Dólares:</strong> <span style="font-size: 18px">${formatNumber(vueltoDolares.toFixed(2))}</span></p>
                    <p><strong class="text-total text-success">Bolívares:</strong> <span style="font-size: 18px">${formatNumber(vueltoBolivares.toFixed(2))}</span></p>
                `,
                                confirmButtonText: 'Aceptar',
                                confirmButtonColor: '#32d7c0',
                            });
                        }
                    });
                }




                document.getElementById('calcularVuelto').addEventListener('click', calcularVuelto)

                let modo = 2

                // Abrir el modal
                openModalButton.addEventListener("click", () => {

                    modo = 1
                });

                // Cerrar el modal al hacer clic en el botón de cerrar o en el overlay
                closeModalButton.addEventListener("click", () => {

                    modo = 2
                });

                modalOverlay.addEventListener("click", () => {
                    modo = 2

                });



                document.addEventListener('click', function(event) {
                    if (event.target.closest('.btn-add-to-car') && !event.target.closest('.no-send')) {
                        $('#search').val('')
                        Toast.fire({
                            icon: 'success',
                            title: 'Agregado correctamte'
                        })
                        modo = 2
                    }
                });

                // Quitar producto del carrito

                function quitar_producto(id) {
                    Swal.fire({
                        title: 'Esta seguro?',
                        html: '¿Desea eliminar este producto del carrito?',
                        icon: 'question',
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#32d7c0',
                        showCancelButton: true,
                    }).then((result) => {
                        if (result.isConfirmed) {

                            $.ajax({
                                url: 'AccionCarta.php',
                                type: 'GET',
                                data: {
                                    id: id,
                                    action: 'removeCartItem'
                                }, // Pasar el parámetro id en la solicitud
                                success: function(response) {
                                    tabla_carrito()
                                },
                                error: function(xhr, status, error) {
                                    // Manejar cualquier error
                                    console.error('Error al eliminar la venta:', error);
                                }
                            });
                        }
                    })
                }
                let ultimo_escaneado = 0







                // Cargar lista de productos
                function buscarProducto(producto, modo) {

                    if (modo == 2) {
                        const codigo = producto.trim();

                        if (!productos.hasOwnProperty(codigo)) {
                            Toast.fire({
                                icon: 'error',
                                title: 'EL producto no existe, agrégalo de forma manual.'
                            })

                        } else {
                            const datos = productos[producto.trim()]

                            $("#result-escaner").html(`
                                       <p class="text-center"><b style="font-size: 1rem;">${datos.nombre}</b><br>
                                                ${datos.stock} Restantes.
                                            </p>
                                            <ul class="p-0">

                                                <li class="item d-flex justify-content-between">
                                                    <span>Precio en <b>Dolar</b></span>
                                                    <span class="precio">$${formatNumber(datos.precio_dolar_visible)}</span>
                                                </li>
                                                <li class="item d-flex justify-content-between">
                                                    <span>Precio en <b>Peso</b></span>
                                                    <span class="precio">${formatNumber(formatPeso(datos.precio_peso_visible))} P</span>
                                                </li>
                                                <li class="item d-flex justify-content-between">
                                                    <span>Precio en <b>Bolivar</b></span>
                                                    <span class="precio">${formatNumber(recortarADosDecimales(datos.precio_bs_visible))} Bs</span>
                                                </li>
                                            </ul>


                                              <section class="dgrid-center">
                                               <div class="my-3 text-center">
                                                    <label class="form-label"><b>Cantidad de producto</b></label>
                                                    <input type="number" class="cantidad-input text-center form-control" data-cantidad-id="${datos.id}" value="1"">
                                                 </div>
                                                         <button class="btn btn-success mt-2 btn-add-to-car no-send" id="btn_${datos.id}"
                                                        data-add-id="${datos.id}"
                                                        data-codigo="${datos.codigo}"
                                                        data-P_D="${datos.precio_dolar_visible}"
                                                        data-P_P="${datos.precio_peso_visible}"
                                                        data-P_B="${datos.precio_bs_visible}">
                                                    AGREGAR AL CARRITO
                                                    </button>
                                              </section>
                                    `);

                            setTimeout(() => {
                                $(`#btn_${datos.id}`).removeClass('no-send')
                            }, 500);
                            ultimo_escaneado = datos.id;


                        }


                    } else {
                        $.ajax({
                                url: 'consulta_producto.php',
                                type: 'POST',
                                dataType: 'html',
                                data: {
                                    producto: producto,
                                    modo: modo
                                },
                            })
                            .done(function(result) {
                                const resultado = JSON.parse(result)

                                if (modo == 1) {
                                    $("#tabla_resultado_codigo_producto").html('');

                                    // recorre resultado
                                    if (resultado.status == 'ok') {




                                        resultado.data.forEach(item => {
                                            $("#tabla_resultado_codigo_producto").append(`
                                        <tr>
                                            <td><span>${item.stock}</span></td>
                                            <td style="font-size: 15px;"><span>${item.nombre}</span></td>
                                            <td style="place-content: center" class="text-center text-total text-success"><span>${formatNumber(item.precio_dolar_visible)}$</span></td>
                                            <td style="place-content: center" class="text-center text-total text-info"><span>${formatNumber(formatPeso(item.precio_peso_visible))} Cop</span></td>
                                            <td style="place-content: center" class="text-center text-total text-danger"><span>${formatNumber(recortarADosDecimales(item.precio_bs_visible))} Bs</span></td>
                                            <td class="text-center" >
                                                 <input data-nombre='${item.nombre}' data-precios='${item.precio_dolar_visible}/${item.precio_peso_visible}/${item.precio_bs_visible}' type="number" style="color: black !important; width: 70px; text-align: center;" class="mt-2 form-control cantidad-input" data-cantidad-id="${item.id}" value="1"">
                                            </td>
                                            <td style="place-content: center" class="text-center">
                                                <button class="btn btn-success btn-add-to-car" 
                                                data-add-id="${item.id}"
                                                data-codigo="${item.codigo}"
                                                data-P_D="${item.precio_dolar_visible}"
                                                data-P_P="${item.precio_peso_visible}"
                                                data-P_B="${item.precio_bs_visible}"
                                                >
                                                    <i class="fa fa-shopping-cart"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        `);



                                        });

                                    }

                                }
                            })
                    }




                }



                document.addEventListener('keyup', function(event) {
                    $('button').blur();
                    if (modo == 2 && ultimo_escaneado != 0) {
                        $('#btn_' + ultimo_escaneado).click();
                    }
                });


                document.addEventListener('keyup', function(event) {
                    if (event.target.closest('.cantidad-input')) {
                        const input = event.target.closest('.cantidad-input');

                        const cantidad = input.value;
                        const nombre = input.getAttribute('data-nombre');
                        const precio_dolar = input.getAttribute('data-precios').split('/')[0];
                        const precio_peso = input.getAttribute('data-precios').split('/')[1];
                        const precio_bs = input.getAttribute('data-precios').split('/')[2];


                        const modal_footer = document.getElementById('modal-footer')
                        modal_footer.classList.remove('hide')


                        let carrito_total_pesos = parseFloat(total_pesos) + (precio_peso * cantidad);
                        let carrito_total_dolar = parseFloat(total_dolares) + (precio_dolar * cantidad);
                        let carrito_total_bolivar = parseFloat(total_bolivares) + (precio_bs * cantidad);


                        modal_footer.innerHTML = `
                           <div class="d-flex" style='gap: 15px;'>
                         <div class="me-2 vista_precio">TOTAL CARRITO: </div>
                        <div class="me-2 vista_precio text-success">$${recortarADosDecimales(carrito_total_dolar)}</div>
                        <div class="me-2 vista_precio text-info">${formatNumber(formatPeso(carrito_total_pesos))} Cop</div>
                        <div class="me-2 vista_precio text-danger">${formatNumber(recortarADosDecimales(carrito_total_bolivar))} Bs</div>
                        </div>

                        <div class="d-flex" style='gap: 15px;'>
                         <div class="me-2 vista_precio">${nombre}</div>
                        <div class="me-2 vista_precio text-success">$${recortarADosDecimales(precio_dolar * cantidad)}</div>
                        <div class="me-2 vista_precio text-info">${formatNumber(formatPeso(precio_peso * cantidad))} Cop</div>
                        <div class="me-2 vista_precio text-danger">${formatNumber(recortarADosDecimales(precio_bs * cantidad))} Bs</div>
                        </div>
                        `
                    }
                    //here
                })






                $(document).on('keyup', '#search', function() {
                    var nombreProducto = $(this).val();
                    if (nombreProducto != '') {
                        buscarProducto(nombreProducto, 1);
                    } else {
                        // vacia la tabla
                        $("#tabla_resultado_codigo_producto").html('');
                    }
                });

                /*
                $(document).on('change', '#search', function() {
                    let valor = $(this).val();
                    // verifica si valor son solo numeros
                    if (!isNaN(valor)) {
                        // verifica si valor existe en el array codigos
                        buscarProducto(valor, 2);
                    }
                    console.log('cambio' + valor)
                });*/


                // LA CANTIDAD ESTA DANDO PROBLEMAS


                function addtocar(id, codigo, dolarventa_p, pesoventa_p, bolivarventa_p) {

                    // Seleccionar el input usando el valor de data-cantidad-id
                    const inputCantidad = document.querySelector(`input[data-cantidad-id="${id}"]`);
                    const cant = inputCantidad ? inputCantidad.value : 1;


                    const peso = formatPeso(pesoventa_p)

                    $.ajax({
                            url: 'AccionCarta.php',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                action: 'addToCart',
                                id: id,
                                codigo: codigo,
                                dolarventa: dolarventa_p,
                                pesoventa: peso,
                                bolivarventa: bolivarventa_p,
                                cant: cant
                            },
                        })
                        .done(function(result) {
                            console.log(result)
                            tabla_carrito()
                            $("#tabla_resultado_codigo_producto").html('');
                            if (modo == 1) {
                                $("#search").val('');
                            }
                        })
                }


                document.addEventListener('click', function(event) {
                    if (event.target.closest('.btn-add-to-car') && !event.target.closest('.no-send')) { // ACCION DE ELIMINAR
                        let id_p = event.target.closest('.btn-add-to-car').getAttribute('data-add-id');
                        let codigo_p = event.target.closest('.btn-add-to-car').getAttribute('data-codigo');

                        let dolarventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_D')
                        let pesoventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_P')
                        let bolivarventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_B')

                        $('#result-escaner').html('')
                        $('#search').val('')

                        addtocar(id_p, codigo_p, dolarventa_p, pesoventa_p, bolivarventa_p);

                        // ocultar footer del modal
                        const modal_footer = document.getElementById('modal-footer')
                        modal_footer.classList.add('hide')
                    }
                });




                let barcode = "";
                let lastKeyTime = Date.now();

                document.addEventListener("keydown", function(event) {

                    const currentTime = Date.now();

                    // Si el tiempo entre dos teclas es mayor a 100ms, se considera que no es un escaneo
                    if (currentTime - lastKeyTime > 100) {
                        barcode = ""; // Reiniciar el código de barras si el tiempo es mayor
                    }

                    // Si la tecla es "Enter", significa que se ha terminado de escanear
                    if (event.key === "Enter") {
                        if (barcode.length > 0) {
                            if (modo == 1) {
                                alert('activa el modo escaner')
                            } else {
                                buscarProducto(barcode, 2);
                            }
                        }
                        barcode = ""; // Reiniciar el código de barras
                    } else {
                        // Si es cualquier otra tecla, añadirla al código de barras
                        barcode += event.key;
                    }

                    lastKeyTime = currentTime; // Actualizar el tiempo de la última tecla presionada



                });




                $(document).ready(function() {
                    document.getElementById("cargando").style.display = "none";
                });

                function confirm(id) {
                    Swal.fire({
                        title: 'Esta seguro?',
                        html: 'Se eliminara la venta ¿desea continuar?',
                        icon: 'question',
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#32d7c0',
                        showCancelButton: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '../../configurar/deleteVentaAjax.php?id=' + id;
                        }
                    })
                }


                function elimi(params) {
                    $.ajax({
                            url: '../../configurar/deleteVentaAjax.php',
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
                $(obtener_registros_codigo());

                function obtener_registros_codigo() {
                    $.ajax({
                            url: 'cargaPorCodigo.php',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                data: 'data'
                            },
                        })
                        .done(function(resultado_codigo) {
                            if (resultado_codigo.trim() == 'NADA') {} else {
                                var url = 'AccionCarta.php?' + resultado_codigo.trim();
                                window.open(url, '_self')
                            }
                        })
                }

                /*     setInterval(function() {
                     obtener_registros_codigo()
                       }, 2000);
                   */

                function updateCartItem(obj, id) {
                    $.get("cartAction.php", {
                        action: "updateCartItem",
                        id: id,
                        qty: obj.value
                    }, function(data) {
                        if (data == 'ok') {
                            location.reload();
                        } else {
                            alert('Cart update failed, please try again.');
                        }
                    });
                }

                function confirmarDescuento() {

                    Swal.fire({
                        title: 'Esta seguro?',
                        html: 'Se descontaran productos del almacen ¿desea continuar?',
                        icon: 'question',
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#32d7c0',
                        showCancelButton: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open("AccionCarta.php?action=placeOrder&statusV=3&valorFinalBs=0&valorFinalCop=0", "_self");
                        }
                    })

                }

                var min = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "u", "v", "w", "x", "y", "z", ",", "[", "]", "{", "}", "'", '"', "?", "-", "=", "`", ";", "ñ", " "];

                function cantVlue(params) {

                    let value = $('#cant').val();

                    min.forEach(element => {
                        if (value.indexOf(element) != '-1') {
                            while (value.indexOf(element) != '-1') {
                                value = value.replace(element, "")
                            }
                            $('#cant').val(value);
                        }
                    });

                    if (value.indexOf('.') > 1) {
                        let mdi
                    }

                    var cadena = value

                    var indices = [];
                    for (var i = 0; i < cadena.length; i++) {
                        if (cadena[i] === ".") indices.push(i);
                    }

                    if (indices.length > 1) {

                        cadena = [...cadena].reverse().join("");
                        for (let index = 0; index < indices.length - 1; index++) {
                            cadena = cadena.replace('.', "")
                        }


                        cadena = [...cadena].reverse().join("");

                        $('#cant').val(cadena);

                    }

                }
            </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>