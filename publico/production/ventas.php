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
            $peso_bolivar = $row7['peso_bolivar'];
        }
    }
    $query3 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query3);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }

    include 'la-carta.php';
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
                    $precioBsVenta = ($precioPesoVenta / $peso_bolivar) / 1000;
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
            padding: 2px !important;
        }

        .table tfoot {
            background-color: #343a40;
        }

        #result-escaner {
            width: -webkit-fill-available;
        }

        #tabla_resultado_codigo_producto td:nth-child(1) {
            display: grid;
            place-items: center;
        }



        .text-total {
            font-size: 18px !important;
            font-weight: bold;
        }

        .cart {
            height: 100%;
            display: flex;
            flex-direction: column;
            place-content: space-between;

        }
    </style>
    <div class="contenedor-loader" id="cargando">
        <span class="loader"></span>
    </div>

    <body class='nav-md' onload="notificacion('<?php echo $mensaje ?>')" style="background-color: #ebebeb;">
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
                <div class="right_col h-100" role='main'>
                    <div class=''>

                        <h4 class="mb-0">Ventas</h4>
                        <p>Caja de despacho</p>

                        <div class="row ">

                            <div class="col-lg-12">
                                <div class="x_panel" style="min-height: 60vh">
                                    <div class="x_title d-flex justify-content-between">
                                        <h2 style="font-size: 15px; font-weight: bold">Carrito </h2>
                                        <button class="btn btn-success btn-sm" id="open-modal"> <i class='bx bx-search-alt' style="vertical-align: text-bottom;"></i> Búsqueda manual</button>
                                    </div>
                                    <div class="x_content cart">
                                        <div>
                                            <div class="table-container">
                                                <table class="table table-striped  table-fixed" id="tabla-carrito">
                                                    <thead class="thead-dark" style="min-width:100%; ">
                                                        <tr>
                                                            <th style="width:5%" class="column-title">Cant.</th>
                                                            <th style="width:30%" class="column-title">Producto</th>
                                                            <th style="width:20%" class="column-title">Peso</th>
                                                            <th style="width:20%" class="column-title">BS</th>
                                                            <th style="width:10%" class="column-title">Dolares</th>
                                                            <th style="width:10%" class="column-title"></th>
                                                            <th style="width:5%" class="column-title"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>

                                                    </tbody>
                                                    <tfoot></tfoot>

                                                </table>

                                            </div>

                                            <section class="d-flex section-scanner hide">
                                                <div id="result-escaner"> </div>
                                            </section>

                                            <div style="position: absolute; bottom: 0;" class="pt-3 footer d-flex hide w-100 justify-content-center" id="botones_acciones">
                                                <a onclick="confirmarDescuento()" class="btn btn-dark" style="color:white; cursor: pointer">Descontar</a>
                                                <a onclick="destroy_cart()" class="btn btn-danger " style="color:white; cursor: pointer">Destruir carrito</a>
                                                <button class="btn btn-light" id="calcularVuelto">Calcular cambio</button>
                                                <button class="btn btn-warning text-dark hide" id="calcularDiferencia">Diferencia</button>

                                                <button onclick="confirmarVenta()" id="btn-vender" class="btn btn-success" style="color:white;">Vender</button>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">


                            <div class="col-lg-12 mt-3 hide">
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

                        </div>

                    </div>


                    <?php require('../assets/templates/modal.html'); ?>

                    <!-- /page content -->

                    <!-- footer content -->
                    <footer class="ml-0 rounded">
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
                // Destruir carrito
                function destroy_cart() {
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Esta acción vaciará tu carrito.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, vaciar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("cart-destroy.php")
                                .then(response => {
                                    actualizar_carrito()
                                })
                                .catch(error => {
                                    console.error("Error en la solicitud:", error);
                                    Swal.fire('Error', 'Ocurrió un problema al vaciar el carrito.', 'error');
                                });
                        }
                    });
                }
                // Destruir carrito


                // Obtener listado de tasas a mostrar
                var tasasMostrar

                function cargarTasasMostrar() {
                    fetch('../../configurar/tasas_mostradas.php?accion=obtener')
                        .then(res => res.json())
                        .then(res => {

                            if (res.status === 'success') {
                                tasasMostrar = res.data;
                            } else {
                                Swal.fire("Error", res.message, "error");
                            }
                        })
                        .catch(error => {
                            // Mostrar el error en consola para debugeo
                            console.error("Error al obtener las tasas:", error);
                            Swal.fire("Error", "Error al obtener las tasas.", "error");
                        });
                }

                cargarTasasMostrar();
                // Obtener listado de tasas a mostrar

                function obtenerTiposDeCambio() {
                    fetch("https://magicloops.dev/api/loop/4b921d65-98a4-4a6e-827a-76552b0c53af/run", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                consulta: "Obtener tipos de cambio actuales"
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error("Error en la respuesta: " + response.status);
                            }
                            console.log("resp" + response.json())
                        })
                        .then(data => {
                            console.log("Respuesta del servidor:", data);
                        })
                        .catch(error => {
                            console.error("Error en la solicitud:", error);
                        });
                }


                obtenerTiposDeCambio()

                var total_pesos = 0;
                var total_dolares = 0;
                var total_bolivares = 0;



                function confirmarVenta(param) {
                    // Opciones de pago habituales
                    if (total_dolares == 0) {
                        return
                    }

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
                    <p>
                        (1) Punto, (2) BioPago, (3) Pesos,<br> (4) Efectivo, (5) Pago Movil, (6) Transferencia, (7) Dolares.
                    </p>


                    <select id="metodoPago" class="form-control">
                        <option value="">Seleccione</option>
                        <option value="option1">(1) Punto</option>
                        <option value="option7">(2) BioPago</option>
                        <option value="option6">(3) Pesos</option>
                        <option value="option4">(4) Efectivo</option>
                        <option value="option2">(5) Pago Movil</option>
                        <option value="option3">(6) Transferencia</option>
                        <option value="option5">(7) Dolares</option>
                    </select>`;

                    // Mostrar el diálogo
                    Swal.fire({
                        title: 'Selecciona un método de pago',
                        html: opcionesPago,
                        confirmButtonText: 'Continuar',
                        confirmButtonColor: '#32d7c0',
                        customClass: {
                            popup: 'swal-metodo-pago'
                        },
                        didOpen: () => {
                            const btn = Swal.getConfirmButton();
                            btn.setAttribute('id', 'btnVender');
                        },
                        preConfirm: () => {
                            const metodoPago = document.getElementById('metodoPago').value;
                            if (!metodoPago) {
                                Swal.showValidationMessage('Por favor, selecciona un método de pago');
                            }
                            return metodoPago;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const metodoPago = result.value;
                            window.location.href = `pagos_Venta.php?metodo=${encodeURIComponent(metodoPago)}`;
                        }
                    });

                }


                /* 
                ACTUALIZAR CARRITO
                   Gestiona el carrito y actualiza las cantidades de productos
                */

                function actualizar_carrito(id = null, accion = null) {
                    $.ajax({
                            url: id && accion ? 'cantidades.php' : 'carrito.php',
                            type: 'POST',
                            data: id && accion ? {
                                id,
                                accion
                            } : {},
                            dataType: 'html'
                        })
                        .done(function(result) {
                            total_pesos = 0
                            total_dolares = 0
                            total_bolivares = 0

                            const resultado = JSON.parse(result);
                            $("#tabla-carrito tbody").html('');
                            $("#tabla-carrito tfoot").html('');
                            if (resultado.cantidad > 0) {
                                resultado.carrito.forEach(element => {
                                    $("#tabla-carrito tbody").append(`
                                <tr>
                                    <td style="width:5%" class="ac-c">${element.cantidad}</td>
                                    <td style="width:30%" class="ac-c">${element.nombre}</td>
                                    <td style="width:20%" class="ac-c">${element.subtotalPeso} P</td>
                                    <td style="width:20%" class="ac-c">${formatNumber(element.subtotalBolivar)} Bs</td>
                                    <td style="width:10%" class="ac-c">$${formatNumber(element.subtotalDolar)}</td>
                                    <td style="width:10%" class="ac-c">
                                        <div class="d-flex">
                                            <button class="btn btn-secondary btn-sm" onclick="actualizar_carrito('${element.id}', 'restar')"><i class="fa fa-arrow-down"></i></button>
                                            <button class="btn btn-secondary btn-sm" onclick="actualizar_carrito('${element.id}', 'sumar')"><i class="fa fa-arrow-up"></i></button>
                                        </div>
                                    </td>
                                    <td style="width:5%">
                                        <button class="btn btn-danger btn-sm" onclick="quitar_producto('${element.id}')"><i class="fa fa-trash-o"></i></button>
                                    </td>
                                </tr>`);
                                });

                                total_pesos = parseFloat(resultado.total.pesos);
                                total_dolares = parseFloat(resultado.total.dolares);
                                total_bolivares = parseFloat(resultado.total.bolivares);

                                $("#tabla-carrito tfoot").html(`
                                    <tr >
                                        <td style="padding-top: 0.5rem !important; width:5%"></td>
                                        <td style="padding-top: 0.5rem !important; width:30%"><b>TOTAL:</b></td>
                                        <td style="padding-top: 0.5rem !important; width:20%" id="precio-total-pesos" class="text-total text-info">${formatNumber(formatPeso(resultado.total.pesos))} P</td>
                                        <td style="padding-top: 0.5rem !important; width:20%" id="precio-total-bolivar" class="text-total text-danger">${formatNumber(resultado.total.bolivares)} Bs</td>
                                        <td style="padding-top: 0.5rem !important; width:10%" id="precio-total-dolar" class="text-total text-success">$${formatNumber(resultado.total.dolares)}</td>
                                        <td style="padding-top: 0.5rem !important; width:10%"></td>
                                        <td style="padding-top: 0.5rem !important; width:5%"></td>
                                    </tr>
                                    `);

                                $('#botones_acciones').removeClass('hide');
                            } else {
                                $('#botones_acciones').addClass('hide');
                            }
                        });
                }
                actualizar_carrito()

                // ACTUALIZAR CARRITO




                function calcularVuelto() {
                    if (total_dolares == 0) {
                        return
                    }

                    document.getElementById("cantidadRecibida").focus();

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


                    if (event.target.closest('.delete-scan')) {
                        document.getElementById('result-escaner').innerHTML = '';
                        $('.section-scanner').addClass('hide')
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
                                    actualizar_carrito()
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
                                title: 'El producto no existe, agrégalo de forma manual.'
                            });
                        } else {
                            const datos = productos[producto.trim()];
                            $('.section-scanner').removeClass('hide');

                            // Construir solo las tasas visibles según tasasMostrar
                            let tasasHTML = '';
                            if (tasasMostrar.precio_dolar_visible) {
                                tasasHTML += `<div class="ac-c text-bold text-success">$${formatNumber(datos.precio_dolar_visible)}</div>`;
                            }
                            if (tasasMostrar.precio_peso_visible) {
                                tasasHTML += `<div class="ac-c text-bold text-info">${formatNumber(formatPeso(datos.precio_peso_visible))} P</div>`;
                            }
                            if (tasasMostrar.precio_bs_visible) {
                                tasasHTML += `<div class="ac-c text-bold text-danger">${formatNumber(recortarADosDecimales(datos.precio_bs_visible))} Bs</div>`;
                            }

                            $("#result-escaner").html(`
                                    <div class="row">
                                        <div class="col-lg-4 ac-c">
                                            [${datos.stock}] <b>${datos.nombre}</b>
                                        </div>
                                        <div class="col-lg-8 d-flex" style="gap: 8px; justify-content: flex-end;">
                                            <div class="d-flex" style="gap: 8px; font-size: 1rem">
                                                ${tasasHTML}
                                            </div>

                                            <input type="number" id="cantidad-scan" style="max-width: 30%;" class="cantidad-input cantidad-scan text-center form-control" data-cantidad-id="${datos.id}" value="1">

                                            <button class="m-0 btn btn-success btn-add-to-car no-send" id="btn_${datos.id}"
                                                data-add-id="${datos.id}"
                                                data-codigo="${datos.codigo}"
                                                data-P_D="${datos.precio_dolar_visible}"
                                                data-P_P="${datos.precio_peso_visible}"
                                                data-P_B="${datos.precio_bs_visible}">
                                                <i class="bx bx-cart-add"></i>
                                            </button>

                                            <button class="m-0 btn btn-danger delete-scan">
                                                <i class="bx bx-cart-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                `);

                            setTimeout(() => {
                                $(`#btn_${datos.id}`).removeClass('no-send');
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
                                console.log(result);
                                const resultado = JSON.parse(result);

                                if (modo == 1) {
                                    $("#tabla_resultado_codigo_producto").html('');

                                    // recorre resultado
                                    if (resultado.status == 'ok') {
                                        resultado.data.forEach(item => {
                                            // Solo mostramos las tasas activas


                                            // Añadir fila con tasas activas
                                            $("#tabla_resultado_codigo_producto").append(`
                                                <tr>
                                                    <td><span>${item.stock}</span></td>
                                                    <td style="font-size: 15px;"><span>${item.nombre}</span></td>
                                                    <td style="place-content: center" class="text-center text-total text-success"><span>${formatNumber(item.precio_dolar_visible)}$</span></td>
                                                    <td style="place-content: center" class="text-center text-total text-info"><span>${formatNumber(formatPeso(item.precio_peso_visible))} Cop</span></td>
                                                    <td style="place-content: center" class="text-center text-total text-danger"><span>${formatNumber(recortarADosDecimales(item.precio_bs_visible))} Bs</span></td>
                                                    <td class="text-center">
                                                        <input data-nombre='${item.nombre}' data-precios='${item.precio_peso_visible}/${item.precio_dolar_visible}/${item.precio_bs_visible}' type="number" style="color: black !important; width: 70px; text-align: center;" class="mt-2 form-control cantidad-input" data-cantidad-id="${item.id}" value="1">
                                                    </td>
                                                    <td style="place-content: center" class="text-center">
                                                        <button class="btn btn-success btn-add-to-car" 
                                                            data-add-id="${item.id}"
                                                            data-codigo="${item.codigo}"
                                                            data-P_D="${item.precio_dolar_visible}"
                                                            data-P_P="${item.precio_peso_visible}"
                                                            data-P_B="${item.precio_bs_visible}">
                                                            <i class="fa fa-shopping-cart"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            `);
                                        });
                                    }
                                }
                            });
                    }
                }




                // Enter para escaneo
                document.addEventListener('keyup', function(event) {
                    $('button').blur();
                    if (modo == 2 && ultimo_escaneado != 0 && event.key == 'Enter') {
                        $('#btn_' + ultimo_escaneado).click();
                    }
                });
                // Enter para escaneo





                document.addEventListener('keyup', function(event) {
                    if (event.target.closest('.cantidad-input') && !event.target.closest('.cantidad-scan')) {
                        const input = event.target.closest('.cantidad-input');

                        const cantidad = input.value;
                        const nombre = input.getAttribute('data-nombre');

                        let precio_dolar = parseFloat(input.getAttribute('data-precios').split('/')[1]);
                        let precio_peso = parseFloat(input.getAttribute('data-precios').split('/')[0]);
                        let precio_bs = parseFloat(input.getAttribute('data-precios').split('/')[2]);

                        precio_peso = Math.round(precio_peso / 100) * 100

                        const modal_footer = document.getElementById('modal-footer')
                        modal_footer.classList.remove('hide')


                        let carrito_total_pesos = total_pesos + (precio_peso * cantidad);
                        let carrito_total_dolar = total_dolares + (precio_dolar * cantidad);
                        let carrito_total_bolivar = total_bolivares + (precio_bs * cantidad);


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
                        <div class="me-2 vista_precio text-info">${formatNumber(precio_peso * cantidad)} Cop</div>
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
                        const modal_footer = document.getElementById('modal-footer')
                        modal_footer.innerHTML = ''
                        modal_footer.classList.add('hide')
                    }
                });


                function addtocar(id, codigo, dolarventa_p, pesoventa_p, bolivarventa_p, cantidad_scann = null) {

                    // Seleccionar el input usando el valor de data-cantidad-id
                    const inputCantidad = document.querySelector(`input[data-cantidad-id="${id}"]`);
                    let cant = inputCantidad ? inputCantidad.value : 1;
                    if (cantidad_scann != null) {
                        cant = cantidad_scann;
                    }
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
                            actualizar_carrito()
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
                        let cantidad_scan = $('#cantidad-scan').val()

                        $('#result-escaner').html('')
                        $('#search').val('')
                        $('.section-scanner').addClass('hide')


                        addtocar(id_p, codigo_p, dolarventa_p, pesoventa_p, bolivarventa_p, cantidad_scan);

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


                // Control de funciones desde el teclado
                let lastKey = null;
                let actionTimeout = null;

                // Lógica separada para ejecutar la acción según la tecla
                function ejecutarAccion(key) {
                    const modal = document.querySelector('#modal-container');
                    const swalPersonalizado = document.querySelector('.swal2-popup.swal-metodo-pago');

                    if (!modal.classList.contains('active') && !swalPersonalizado) {


                        switch (key) {
                            case 'b':
                                openModalButton.click();
                                document.getElementById("search").focus();
                                document.getElementById("search").value = "";
                                break;
                            case 'v':
                                confirmarVenta();
                                break;
                            case 'c':
                                document.getElementById('calcularVuelto').click();
                                break;
                            case '+':
                                if ($('#result-escaner').find('.btn-add-to-car').length > 0) {
                                    const cantidad = $('#cantidad-scan').val()
                                    const cantidadActual = parseInt(cantidad) + 1
                                    $('#cantidad-scan').val(cantidadActual)
                                }
                                break;
                            case '-':
                                if ($('#result-escaner').find('.btn-add-to-car').length > 0) {

                                    const cantidad = $('#cantidad-scan').val()
                                    const cantidadActual = parseInt(cantidad) - 1
                                    if (cantidadActual > 0) {
                                        $('#cantidad-scan').val(cantidadActual)
                                    }
                                }
                                break;
                            case '-':
                                if ($('#result-escaner').find('.btn-add-to-car').length > 0) {
                                    const cantidad = $('#cantidad-scan').val()
                                    const cantidadActual = parseInt(cantidad) - 1
                                    if (cantidadActual >= 1) {
                                        $('#cantidad-scan').val(cantidadActual)
                                    }
                                }
                                break;


                        }
                    } else if (key === 'escape') {
                        closeModalButton.click();
                    }

                    if (swalPersonalizado) {
                        const select = document.getElementById('metodoPago');
                        const btnVender = document.getElementById('btnVender');

                        const opciones = {
                            '1': 'option1',
                            '2': 'option7',
                            '3': 'option6',
                            '4': 'option4',
                            '5': 'option2',
                            '6': 'option3',
                            '7': 'option5',
                        };

                        if (opciones[key]) {
                            select.value = opciones[key];
                            select.dispatchEvent(new Event('change'));
                        }

                        if (key === 'enter') {
                            btnVender?.click();
                        }
                    }
                }

                document.addEventListener('keyup', function(event) {
                    const key = event.key.toLowerCase();

                    const allowedKeys = ['+', '-', 'b', 'v', 'c', 'escape', 'enter', '1', '2', '3', '4', '5', '6', '7'];

                    if (!allowedKeys.includes(key)) return; // Ignorar teclas no relevantes

                    if (key === lastKey) {
                        // Reinicia el timeout si es la misma tecla
                        clearTimeout(actionTimeout);
                        actionTimeout = setTimeout(() => {
                            ejecutarAccion(key);
                            lastKey = null;
                        }, 100);
                    } else {
                        // Tecla distinta: cancelar cualquier acción pendiente
                        clearTimeout(actionTimeout);
                        lastKey = key;
                        actionTimeout = setTimeout(() => {
                            ejecutarAccion(key);
                            lastKey = null;
                        }, 100);
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