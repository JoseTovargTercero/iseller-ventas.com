<?php
require_once('includes/requires.php');

if (empty($_SESSION['sucursal'])) {
    define('PAGINA_INICIO', 'seleccion_sucursal.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
}
$nivelUsuario = $_SESSION['nivel'];
$nombreUsuario = $_SESSION['nombre'];
$bss_id = $_SESSION['bss_id'];
$sucursal = $_SESSION['sucursal'];
$sucursal_nombre = '';

$sql = "SELECT UPPER(nombre) AS nombre FROM sucursales WHERE id = ? AND bss_id = ?";

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("ii", $sucursal, $bss_id);
    $stmt->execute();
    $stmt->bind_result($sucursal_nombre);

    if ($stmt->fetch()) {
    }

    $stmt->close();
}



require("../../configurar/_calculadrora_precios.php");
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);

$topnav = topnav();

include '../../configurar/la-carta.php';
$cart = new Cart;


$_SESSION["ventas"] = "activa";
if (@$_SESSION["dist_ventas"] == "activa") {
    unset($_SESSION["dist_ventas"]);
    $cart->destroy();
}



$sql = "SELECT 
            p.cantidad_unidades,
            p.origen,
            p.precio_compra,
            p.porcentaje,
            p.nombre,
            p.mayor,
            p.id,
            s.stock,
            p.codigo_barras
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        WHERE p.activo = 0
        AND p.codigo_barras != ''
        AND s.id_sucursal = ?
        AND s.bss_id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $sucursal, $bss_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$codigos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $codigo = trim($row['codigo_barras']);

        if (strlen($codigo) > 5) {
            $precios = $calculadora->calcularPrecios($row);

            $nombre = strtoupper($row["nombre"]);
            $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);

            $data[trim($codigo)] = [
                'id' => $row['id'],
                'stock' => $row['stock'],
                'nombre' => $nombre,
                'precio_dolar_visible' => $precios['precio_venta_dolar'],
                'precio_peso_visible' => $precios['precio_venta_peso'],
                'precio_bs_visible' => $precios['precio_venta_bs'],
                'mayor' => $row['mayor'],
            ];

            $codigos[] = $codigo;
        }
    }
}

$stmt->close();

/*
echo '<pre>';
print_r($data);
echo '</pre>';*/
?>




<!DOCTYPE html>
<html lang='es'>

<head>

    <title>Ventas </title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">

</head>

<script>
    var productos = <?php echo json_encode($data); ?>;
    var codigos = []

    <?php

    foreach ($codigos as  $value) {
        echo 'codigos.push("' . trim($value) . '");';
    }

    ?>
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
        padding: 5px 2px !important;
        vertical-align: middle;
    }


    #result-escaner {
        width: -webkit-fill-available;
    }

    #tabla_resultado_codigo_producto td:nth-child(1) {
        display: grid;
        place-items: center;
    }



    .text-total {
        font-size: 1rem;
        font-weight: bold;
    }

    .cart {
        height: 100%;
        display: flex;
        flex-direction: column;
        place-content: space-between;

    }

    .btn-group-sm>.btn,
    .btn-sm {
        font-size: 0.64rem !important;
        padding: .20rem .34rem
    }
</style>
<div class="contenedor-loader" id="cargando">
    <span class="loader"></span>
</div>

<body class='nav-md'>
    <div class='container body'>
        <div class='main_container'>
            <?php echo $menu ?>

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
                                    <div style="display: grid">
                                        <h2>Carrito del cliente</h2>
                                        <span><b>SUCURSAL: </b><span id="sucursal_nombre">
                                            </span></span>
                                    </div>
                                    <button class="btn btn-success" style="height: min-content" id="open-modal"> (B) Búsqueda</button>
                                </div>
                                <div class="x_content cart">
                                    <div>
                                        <div class="table-container" style="overflow: auto">
                                            <table class="table table-striped table-hover" id="tabla-carrito">
                                                <thead style="min-width:100%; ">
                                                    <tr>
                                                        <th style="width:5%" class="column-title">Cant.</th>
                                                        <th style="width:30%" class="column-title">Producto</th>
                                                        <th style="width:20%" class="column-title">Peso</th>
                                                        <th style="width:20%" class="column-title">BS</th>
                                                        <th style="width:10%" class="column-title">Dolares</th>
                                                        <th style="width:10%" class="column-title">Acciones</th>
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

                                        <div style=" bottom: 0; flex-wrap: wrap-reverse;" class="pt-3 footer d-flex hide w-100 justify-content-center" id="botones_acciones">


                                            <?php

                                            // Mostrar botones según permisos
                                            if (!empty($_SESSION['permisos'][11]) || $_SESSION["nivel"] == 1) {
                                                echo '<button onclick="confirmarVenta(\'credito\')" class="btn btn-info text-white">Crédito</button>';
                                            }

                                            if (!empty($_SESSION['permisos'][12]) || $_SESSION["nivel"] == 1) {
                                                echo '<a onclick="confirmarDescuento()" class="btn btn-dark text-white" style="cursor: pointer">Descontar</a>';
                                            }

                                            ?>


                                            <a onclick="destroy_cart()" class="btn btn-danger " style="color:white; cursor: pointer">Destruir carrito</a>
                                            <button class="btn btn-light" id="calcularVuelto">(C) Calcular cambio</button>
                                            <button class="btn btn-warning text-dark hide" id="calcularDiferencia">Diferencia</button>
                                            <button onclick="confirmarVenta()" id="btn-vender" class="btn btn-success" style="color:white;">(V) Vender</button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">


                        <div class="col-lg-12 mt-3 ">
                            <div class="x_panel" style="padding-bottom: 30px;">
                                <div class="x_title">
                                    <h2 style="font-size: 15px; font-weight: bold">Ultimas ventas realizadas</h2>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content ">
                                    <div class="">
                                        <table class="table table-responsive table-striped">
                                            <thead>
                                                <tr>
                                                    <th class='column-title'>#</th>
                                                    <th class='column-title'>Tipo</th>
                                                    <th class='column-title'>Pago por</th>
                                                    <th class='column-title'>Fecha</th>
                                                    <th class='column-title'>Monto</th>
                                                    <th class='column-title'>Detalles</th>
                                                    <th style="padding: 10px !important; text-align: center" class='column-title'>Ticket</th>
                                                    <th style="width: 7%; padding: 10px !important" class='column-title'>Eliminar</th>
                                                </tr>
                                            </thead>
                                            <tbody id='tabla_ventas'>
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

            </div>
        </div>
        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <!-- FastClick -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <script src="../vendors/nprogress/nprogress.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="../build/js/modal.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
        <!-- FastClick -->
        <script>
            const base_url = '../../configurar/';
            const sucursal_n = <?php echo json_encode($sucursal_nombre) ?>;
            const sucursal_i = <?php echo json_encode($sucursal) ?>;

            // lista de ventas
            function cargarUltimasOrdenes() {
                fetch(base_url + 'ultimas_ventas.php')
                    .then(response => response.json())
                    .then(data => {
                        const tabla = document.getElementById('tabla_ventas');
                        tabla.innerHTML = ''; // Limpiar antes de insertar

                        let contador = 1;

                        data.forEach(orden => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                    <td>${contador++}</td>
                                    <td>${orden.tipoVenta}</td>
                                    <td>${orden.pagoPor}</td>
                                    <td>${orden.fecha}</td>
                                    <td>$${orden.total}</td>
                                    <td><a href="detallesVenta.php?id=${orden.id}">Detalles</a></td>
                                    <td style="text-align: center">
                                    <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                        <button type="button" class="btn btn-sm btn-success" onclick="print(${orden.id})">
                                            <i class="line icon-printer"></i>
                                        </button>
                                        <div class="btn-group" role="group">
                                            <button id="btnGroupDrop1" type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                            <li><a class="dropdown-item" onclick="print(${orden.id}, 'USD')">Imprimir en dolares</a></li>
                                            <li><a class="dropdown-item" onclick="print(${orden.id}, 'COP')">Imprimir en pesos</a></li>
                                            <li><a class="dropdown-item" onclick="print(${orden.id}, 'BS')">Imprimir en bolivares</a></li>
                                            </ul>
                                        </div>
                                        </div>    
                                    </td>

                                    <td style="text-align: center"><a class="btn btn-danger btn-sm" style="cursor: pointer; color: white" title="Deshacer compra" onclick="confirm(${orden.id})"><i class="line icon-reload"></i></a></td>
                                `;
                            tabla.appendChild(row);
                        });
                    })
                    .catch(error => {
                        console.error('Error al cargar órdenes:', error);
                    });
            }

            // Llamar cuando cargue la página
            document.addEventListener('DOMContentLoaded', cargarUltimasOrdenes);


            function print(id, moneda = 'default') {
                $('#cargando').show();

                $.ajax({
                        url: base_url + 'contenido_ticket.php',
                        type: 'POST',
                        data: {
                            id: id,
                            moneda: moneda
                        },
                        dataType: 'html'
                    })
                    .done(function(result) {
                        const fecha = new Date().toLocaleString('es-VE', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });

                        const contenido = `
                            <html>
                            <head>
                                <title>Ticket</title>
                                <style>
                                 * {
                                        box-sizing: border-box;
                                    }

                                    body {
                                        font-family: Arial, sans-serif;
                                        font-size: 11px !important;
                                        margin: 0;
                                        padding: 0;
                                        max-width: 44mm
                                    }


                                    .centrado {
                                        text-align: center;
                                    }

                                      @media print {
                                    @page {
                                        size: 44mm auto;
                                        margin: 0;
                                    }

                                    body, html {
                                        margin: 0;
                                        padding: 0;
                                    }

                                    #ticket {
                                        page-break-after: avoid;
                                    }
                                }

                                 table {
                                    width: 100%; /* Usa 100% en lugar de 58mm para que no exceda su contenedor */
                                    border-collapse: collapse;
                                    table-layout: fixed; /* Asegura que las columnas se ajusten */
                                    
                                }

                                th, td {
                                    text-align: left;
                                    padding: 2px 0;
                                }

                                .line {
                                    border-top: 1px dashed #000;
                                    margin: 5px 0;
                                }

                                </style>
                            </head>
                              <body>
                                <div class="ticket" id="ticket">
                                    <p class="centrado">
 <img src="images/sucursal_logo/${sucursal_i}.png" height="50px" onerror="this.parentNode.removeChild(this)">
                                    <br>
                                    <br>
                                        ${sucursal_n}
                                        <br>
                                        ${fecha}<br>
                                        <strong></strong><br>
                                        <small>* Nota de entrega</small>
                                    </p>
                                    ${result}
                                    <div class="line"></div>
                                    <p class="centrado" style="font-size: 10px;">¡GRACIAS POR SU COMPRA!</p>
                                    <div class="line"></div>
                                </div>
                              </body>
                            </html>
                        `;

                        const ventana = window.open('', '_blank', 'width=600,height=600');
                        ventana.document.open();
                        ventana.document.write(contenido);
                        ventana.document.close();

                        ventana.onload = function() {
                            ventana.print();
                            ventana.close();
                        };

                        $('#cargando').hide();
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Error al cargar el ticket:', error);
                        $('#cargando').hide();
                        alert('Hubo un error al generar el ticket. Intente nuevamente.');
                    });
            }



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
                        fetch(base_url + "cart-destroy.php")
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
                fetch(base_url + 'tasas_mostradas.php?accion=obtener')
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


            var total_pesos = 0;
            var total_dolares = 0;
            var total_bolivares = 0;



            function confirmarVenta(tipo = 'venta') {
                // 1. Reglas comunes ───────────────────────────────────────────────
                if (total_dolares === 0) return; // nada que vender
                $('button').blur(); // quitar focus de botones

                if ($('#result-escaner').find('.btn-add-to-car').length > 0) {
                    Swal.fire({
                        title: 'Atención',
                        html: 'Hay un producto en la cola de ventas, agréguelo al carrito o descártelo antes de continuar',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#32d7c0',
                    });
                    return;
                }

                // 2. Configurar diálogo dependiendo del tipo ───────────────────────
                const esCredito = (tipo === 'credito');

                // 2.1 Contenido HTML
                const htmlContent = esCredito ?
                    `<input id="nombreCliente" class="form-control" placeholder="Nombre del cliente">` :
                    `(1) Punto, (2) BioPago, (3) Pesos,<br> (4) Efectivo, (5) Pago Movil,
               (6) Transferencia, (7) Dólares.</>
               
           <select id="metodoPago" class="form-control">
               <option value="">Seleccione</option>
               <option value="option1">(1) Punto</option>
               <option value="option7">(2) BioPago</option>
               <option value="option6">(3) Pesos</option>
               <option value="option4">(4) Efectivo</option>
               <option value="option2">(5) Pago Movil</option>
               <option value="option3">(6) Transferencia</option>
               <option value="option5">(7) Dólares</option>
           </select>`;

                // 2.2 Título
                const titulo = esCredito ?
                    'Ingresa el nombre del cliente' :
                    'Selecciona un método de pago';

                // 2.3 Diálogo SweetAlert2
                Swal.fire({
                    title: titulo,
                    html: htmlContent,
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: '#32d7c0',
                    customClass: {
                        popup: 'swal-metodo-pago'
                    },
                    didOpen: () => Swal.getConfirmButton().setAttribute('id', 'btnVender'),
                    preConfirm: () => {
                        if (esCredito) {
                            const nombre = document.getElementById('nombreCliente').value.trim();
                            if (!nombre) Swal.showValidationMessage('Por favor, ingresa el nombre del cliente');
                            return nombre; // se devuelve para result.value
                        } else {
                            const metodo = document.getElementById('metodoPago').value;
                            if (!metodo) Swal.showValidationMessage('Por favor, selecciona un método de pago');
                            return metodo; // se devuelve para result.value
                        }
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    if (esCredito) {
                        const nombreCliente = result.value; // valor devuelto en preConfirm
                        procesarPedido('0', 'placeOrderCredito', nombreCliente);
                    } else {
                        const metodoPago = result.value; // e.g. "option3"
                        procesarPedido(metodoPago, 'placeOrder');
                    }
                });
            }


            function procesarPedido(metodoPago, despacho, nombreC = null) {
                const valorFinalVenta = total_dolares;
                const valorFinalBs = total_bolivares;
                const valorFinalCop = total_pesos;
                const pagoTipo = metodoPago.replace('option', '');
                const action = despacho;
                const compraTipo = 1;

                // pendiente al nombre del cliente nombreC // placeOrderCredito

                let tipoDespacho
                if (despacho == 'placeOrderCredito') {
                    tipoDespacho = 2;
                } else {
                    tipoDespacho = 1;
                }

                $.ajax({
                        url: base_url + 'accion_carta.php',
                        type: 'GET',
                        data: {
                            valorFinalVenta: valorFinalVenta,
                            valorFinalBs: valorFinalBs,
                            valorFinalCop: valorFinalCop,
                            pagoTipo: pagoTipo,
                            action: action,
                            compraTipo: compraTipo,
                            nombreC: nombreC,
                            tipoV: tipoDespacho
                        },
                        dataType: 'html'
                    })
                    .done(function(result) {
                        const response = JSON.parse(result)
                        if (response.status) {
                            total_pesos = 0
                            total_dolares = 0
                            total_bolivares = 0
                            Alerta.toast('success', response.data)
                            actualizar_carrito()
                            cargarUltimasOrdenes()
                        } else {
                            Alerta.toast('error', 'No se pudo realizar la acción')
                        }

                    });

            }






            /* 
            ACTUALIZAR CARRITO
               Gestiona el carrito y actualiza las cantidades de productos
            */

            function actualizar_carrito(id = null, accion = null) {
                $.ajax({
                        url: id && accion ? base_url + 'cantidades.php' : base_url + 'carrito.php',
                        type: 'POST',
                        data: id && accion ? {
                            id,
                            accion
                        } : {},
                        dataType: 'html'
                    })
                    .done(function(result) {
                        //     console.log(result)
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
                                    <td style="width:5%; text-align: center" class="ac-c">${element.cantidad}</td>
                                    <td style="width:30%" class="ac-c">${element.nombre}</td>
                                    <td style="width:20%" class="ac-c">${element.subtotalPeso} P</td>
                                    <td style="width:20%" class="ac-c">${formatNumber(element.subtotalBolivar)} Bs</td>
                                    <td style="width:10%" class="ac-c">$${formatNumber(element.subtotalDolar)}</td>
                                    <td style="width:10%" class="ac-c">
                                             <button class="btn btn-sm btn-outline-success" onclick="actualizar_carrito('${element.id}', 'sumar')"><i class="fa fa-arrow-up"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="actualizar_carrito('${element.id}', 'restar')"><i class="fa fa-arrow-down"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="quitar_producto('${element.id}')"><i class="fa fa-trash-o"></i></button>
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
                                    </tr>
                                    `);

                            $('#botones_acciones').removeClass('hide');
                        } else {
                            $('#botones_acciones').addClass('hide');
                        }
                        $('[data-toggle="popover"]').popover({
                            html: true
                        });

                    });
            }
            actualizar_carrito()

            // ACTUALIZAR CARRITO



            function calcularVuelto() {
                if (total_dolares == 0) return;

                total_pesos = String(total_pesos).replace(',', '');

                let handleEnterKey;

                Swal.fire({
                    title: 'Indique la cantidad recibida',
                    html: `<input type="number" id="cantidadRecibida" class="swal2-input" placeholder="Cantidad recibida">`,
                    confirmButtonText: 'Calcular vuelto',
                    confirmButtonColor: '#32d7c0',
                    didOpen: () => {
                        const input = document.getElementById('cantidadRecibida');
                        input.focus();

                        handleEnterKey = (e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                Swal.clickConfirm();
                            }
                        };

                        input.addEventListener('keydown', handleEnterKey);
                    },
                    willClose: () => {
                        const input = document.getElementById('cantidadRecibida');
                        if (input && handleEnterKey) {
                            input.removeEventListener('keydown', handleEnterKey);
                        }
                    },
                    preConfirm: () => {
                        const cantidadRecibida = parseFloat(document.getElementById('cantidadRecibida').value);
                        if (isNaN(cantidadRecibida) || cantidadRecibida <= 0) {
                            Swal.showValidationMessage('Por favor, ingresa una cantidad válida');
                        }
                        return {
                            cantidadRecibida
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Aquí ya el primer Swal se cerró y el listener fue removido

                        const {
                            cantidadRecibida
                        } = result.value;

                        let vueltoPesos = cantidadRecibida - parseInt(total_pesos);
                        let vueltoDolares = cantidadRecibida - total_dolares;
                        let vueltoBolivares = cantidadRecibida - total_bolivares;

                        // Ahora mostramos el segundo Swal sin que el Enter anterior lo cierre
                        setTimeout(() => {
                            Swal.fire({
                                title: 'Vuelto calculado',
                                html: `
                                            <p><strong class="text-total text-info">Pesos:</strong> <span style="font-size: 18px">${formatNumber(vueltoPesos.toFixed(2))}</span></p>
                                            <p><strong class="text-total text-danger">Dólares:</strong> <span style="font-size: 18px">${formatNumber(vueltoDolares.toFixed(2))}</span></p>
                                            <p><strong class="text-total text-success">Bolívares:</strong> <span style="font-size: 18px">${formatNumber(vueltoBolivares.toFixed(2))}</span></p>
                                        `,
                                confirmButtonText: 'Cerrar',
                                confirmButtonColor: '#32d7c0',
                                allowEscapeKey: true // Habilita cerrar con ESC
                            });
                        }, 50);
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
                    document.getElementById("search").focus();
                    Alerta.toast('success', 'Agregado correctamte')
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
                            url: base_url + 'accion_carta.php',
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
            function buscarProducto(lectura, modo) {
                if (modo == 2) {
                    const codigo = lectura.trim();

                    if (!productos[codigo]) {
                        Alerta.toast('error', 'El producto no existe, agrégalo de forma manual.')
                        return
                    } else {
                        const datos = productos[lectura.trim()];
                        $('.section-scanner').removeClass('hide');

                        // Construir solo las tasas visibles según tasasMostrar

                        $("#result-escaner").append(`
                                    <div class="row">
                                        <div class="col-lg-4 ac-c">
                                            [${datos.stock}] <b>${datos.nombre}</b>
                                        </div>
                                        <div class="col-lg-8 d-flex" style="gap: 8px; justify-content: flex-end;">
                                            <div class="d-flex" style="gap: 8px; font-size: 1rem">
                                               <div class="ac-c text-bold text-success">$${formatNumber(datos.precio_dolar_visible)}</div>
                                                <div class="ac-c text-bold text-info">${formatNumber(formatPeso(datos.precio_peso_visible))} P</div>
                                                <div class="ac-c text-bold text-danger">${formatNumber(recortarADosDecimales(datos.precio_bs_visible))} Bs</div>
                                            </div>

                                            <input type="number" id="cantidad-scan" style="max-width: 30%;" class="cantidad-input cantidad-scan text-center form-control" data-cantidad-id="${datos.id}" value="1">

                                            <button class="m-0 btn  btn-success btn-add-to-car no-send" id="btn_${datos.id}"
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
                            url: base_url + 'consulta_producto.php',
                            type: 'POST',
                            dataType: 'html',
                            data: {
                                producto: lectura,
                                modo: modo
                            },
                        })
                        .done(function(result) {

                            const resultado = JSON.parse(result);

                            //console.log(resultado)

                            if (resultado.status == 'error' && resultado.mensaje == 'Sucursal no especificada.') {
                                Alerta.toast('error', 'No se ha especificado ninguna sucursal')
                                return
                            }

                            if (modo == 1) {
                                $("#tabla_resultado_codigo_producto").html('');

                                if (resultado.status == 'ok') {
                                    resultado.data.forEach(item => {
                                        const rest = (item.mayor == '1' ? '<span style="margin: 5px;" class="fw-medium text-decoration-none me-2 badge badge-subtle-success">Mayor</span>' : item.stock)

                                        $("#tabla_resultado_codigo_producto").append(`
                                                <tr>
                                                    <td>${rest}</td>
                                                    <td style="font-size: 15px;"><span>${item.nombre}</span></td>
                                                    <td style="place-content: center" class="text-center text-total text-success"><span>${formatNumber(item.precio_dolar_visible)}$</span></td>
                                                    <td style="place-content: center" class="text-center text-total text-info"><span>${formatNumber(formatPeso(item.precio_peso_visible))} Cop</span></td>
                                                    <td style="place-content: center" class="text-center text-total text-danger"><span>${formatNumber(recortarADosDecimales(item.precio_bs_visible))} Bs</span></td>
                                                    <td class="text-center">
                                                        <input data-nombre='${item.nombre}' data-precios='${item.precio_peso_visible}/${item.precio_dolar_visible}/${item.precio_bs_visible}' type="number" style="color: black !important; width: 70px; text-align: center; border: 1px solid gray;" class="form-control-sm cantidad-input" data-cantidad-id="${item.id}" value="1">
                                                    </td>
                                                    <td style="place-content: center" class="text-center">
                                                        <button class="btn btn-sm btn-success btn-add-to-car" 
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



            function addtocar(id, codigo, dolarventa_p, pesoventa_p, bolivarventa_p, mayor, cantidad_scann = null) {



                // Seleccionar el input usando el valor de data-cantidad-id
                const inputCantidad = document.querySelector(`input[data-cantidad-id="${id}"]`);
                let cant = inputCantidad ? inputCantidad.value : 1;
                if (cantidad_scann != null) {
                    cant = cantidad_scann;
                }
                const peso = formatPeso(pesoventa_p)

                $.ajax({
                        url: base_url + 'accion_carta.php',
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
                if (event.target.closest('.btn-add-to-car') && !event.target.closest('.no-send')) {
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
                            // buscarProducto(barcode.replace(/Shift/g, ""), 2);
                            buscarProducto(barcode.trim(), 2);
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
                        eliminarVenta(id)
                    }
                })
            }


            function eliminarVenta(id) {
                $.ajax({
                        url: base_url + 'deleteVentaAjax.php',
                        type: 'POST',
                        dataType: 'html',
                        data: {
                            id: id
                        },
                    })
                    .done(function(resultado1) {
                        cargarUltimasOrdenes()
                    })
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


                        $.ajax({
                            url: base_url + 'accion_carta.php',
                            type: 'GET',
                            data: {
                                statusV: 3,
                                action: 'placeOrder'
                            }, // Pasar el parámetro id en la solicitud
                            success: function(response) {
                                const respuesta = JSON.parse(response)
                                if (respuesta.status) {
                                    Alerta.toast('info', 'Se descontaron productos del inventario')
                                    actualizar_carrito()
                                }
                            },
                            error: function(xhr, status, error) {
                                // Manejar cualquier error
                                console.error('Error al eliminar la venta:', error);
                            }
                        });
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



            function agregarNombreSucursal(sucursal_n) {
                const navbar = document.querySelector('ul.navbar-right');
                if (!navbar) {
                    console.warn("No se encontró el elemento 'ul.navbar-right'");
                    return;
                }

                const div = document.getElementById('sucursal_nombre');
                const element = (nv == 1 ? 'a' : 'span');

                const a = document.createElement(element);
                a.href = (nv == 1 ? 'seleccion_sucursal.php' : '');
                a.textContent = sucursal_n;

                div.appendChild(a);
            }

            const nv = <?php echo json_encode($_SESSION["nivel"]) ?>

            agregarNombreSucursal(sucursal_n)
        </script>
</body>

</html>