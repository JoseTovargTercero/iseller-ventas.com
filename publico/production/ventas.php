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
            s.porcentaje,
            p.nombre,
            p.precio_compra,
            p.cantidad_unidades,
            p.mayor,
            p.id,
            s.stock,
            p.codigo_barras
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        WHERE p.activo = 0
        AND s.id_sucursal = ?
        AND s.bss_id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $sucursal, $bss_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$codigos = [];
$productos_por_id = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nombre = strtoupper($row["nombre"]);
        $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);
        $precios = $calculadora->calcularPrecios($row);
        $codigo = trim($row['codigo_barras']);

        // Formatear nombre
        /* PRODUCTOS PARA LA BUSQUEDA */

        $valorUnidad = (float) $row['precio_compra'] / (float) $row['cantidad_unidades'];
        $mayor = floatval($row['mayor']);


        $productos_por_id[$row['id']] = [
            'id' => $row['id'],
            'stock' => $row['stock'],
            'porcentaje' => $row['porcentaje'],
            'nombre' => $row['nombre'],
            'cantidad_unidades' => $row['cantidad_unidades'],
            'codigo' => $row['codigo_barras'],
            'mayor' => $mayor,
            "precio_dolar_visible" => $precios['precio_venta_dolar'], // Precio de Venta
            "precio_peso_visible" => $precios['precio_venta_peso'], // Precio de Venta
            "precio_bs_visible" => $precios['precio_venta_bs'], // Precio de Venta
            'price_C' => $valorUnidad, // precio de compra
            'price_C_Bs' => $valorUnidad * $dolarBolivar, // precio de compra
            'price_C_Cop' => $valorUnidad * $pesoDolar, // precio de compra
            'cantidadPaca' => $row['cantidad_unidades']
        ];
        /* PRODUCTOS PARA LA BUSQUEDA */


        //  PARA LA BUSQUEDA POR CODIGO DE BARRA
        if (strlen($codigo) > 5) {


            $data["$codigo"] = [
                'id' => $row['id'],
                'stock' => $row['stock'],
                'codigo' => trim($codigo),
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
print_r($productos_por_id);
echo '</pre>';
*/
?>




<!DOCTYPE html>
<html lang='es'>

<head>

    <title>Ventas </title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dexie/4.2.0/dexie.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

</head>



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

    .totales-pendiente {
        gap: 5px;
    }
</style>
<div class="contenedor-loader" id="cargando">
    <span class="loader"></span>
</div>

<body class='nav-md'>

    <style>
        .section-scanner {
            position: fixed;
            inset: 0;
            /* Equivalente a top:0; right:0; bottom:0; left:0 */
            z-index: 999;
            background-color: rgba(0, 0, 0, 0.55);
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
            transition: opacity 0.3s ease;
        }

        .section-scanner.hide {
            opacity: 0;
            pointer-events: none;
        }

        #result-escaner {
            position: relative;
            background: #1c1c1c;
            padding: 20px;
            border-radius: 12px;
            min-width: 300px;
            max-width: 50%;
            min-height: 150px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.4);
            color: white;
            animation: modalFadeIn 0.3s ease;
        }

        /* Botón de cerrar */
        #result-escaner .btn-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #ccc;
            font-size: 20px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        #result-escaner .btn-close:hover {
            color: #fff;
        }

        /* Animación */
        @keyframes modalFadeIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Contenedor principal del producto escaneado */
        .scan-product {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 15px;
            border-radius: 10px;
            color: #fff;
        }

        /* Encabezado con nombre y stock */
        .scan-product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .scan-stock {
            font-size: 0.9rem;
            color: #bbb;
        }

        /* Sección de precios */
        .scan-prices {
            display: flex;
            gap: 15px;
            font-weight: bold;
            font-size: 1rem;
        }

        .scan-price-usd {
            color: #4caf50;
        }

        .scan-price-cop {
            color: #00bcd4;
        }

        .scan-price-bs {
            color: #f44336;
        }

        /* Controles (cantidad + botones) */
        .scan-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .scan-quantity {
            max-width: 80px;
            text-align: center;
            border-radius: 6px;
            border: none;
            padding: 5px;
            font-size: 1rem;
        }

        /* Botones */
        .scan-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            transition: background 0.2s ease;
        }

        .scan-btn.add {
            background: #4caf50;
            color: white;
        }

        .scan-btn.add:hover {
            background: #45a049;
        }

        .scan-btn.remove {
            background: #f44336;
            color: white;
        }

        .scan-btn.remove:hover {
            background: #e53935;
        }
    </style>

    <section class="d-flex section-scanner hide" id="section-scanner">
        <div id="result-escaner"> </div>
    </section>

    <script>
        function cerrarScanner() {
            document.getElementById('section-scanner').classList.add('hide');
            document.getElementById('result-escaner').innerHTML = ''; // Limpiar el contenido del escáner
        }
        // al precionar la tecla escape se cierra el scanner
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarScanner();
            }
        });
    </script>


    <div class='container body'>
        <div class='main_container'>
            <?php echo $menu ?>

            <!-- top navigation -->
            <?php echo $topnav ?>
            <!-- /top navigation -->
            <div class="right_col h-100" role='main'>



                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0 text-danger">Ventas</h4>
                        <p>Caja de despacho</p>
                    </div>
                    <div class="pt-1">


                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab" aria-controls="home" aria-selected="true">Carrito activo</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="false">No guardados <span id="cantidad-no-enviada"></span> </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">Reservados <span id="cantidad-reservados"></span></button>
                            </li>
                        </ul>




                    </div>
                </div>


                <style>
                    .text-dark {
                        color: #5a5a5a !important;
                    }

                    .item-reservado {
                        background-color: #22222217;
                        border: 1px solid #00000045;
                        border-radius: 5px;
                        border-style: dashed;
                    }

                    .avatar {
                        background: #8b8b8b14;
                        padding: 7px;
                        font-size: 25px;
                        width: 45px;
                        height: 45px;
                        text-align: center;
                        border-radius: 50%;
                    }

                    .item-reservado-header {
                        display: flex;
                        gap: 5px;
                    }

                    .btn-list-item {
                        flex-direction: column
                    }

                    .botones-container {
                        display: grid;
                        gap: 10px;
                        /* Espacio entre botones */
                        grid-template-columns: repeat(3, 1fr);
                        /* 4 columnas */
                    }

                    .botones-container .error-internet {
                        grid-column: 1 / -1;
                    }

                    .btn-info {
                        background-color: #40909d !important;
                    }

                    /* Para pantallas medianas (2 columnas) */
                    @media (max-width: 992px) {
                        .botones-container {
                            grid-template-columns: repeat(2, 1fr);
                        }
                    }



                    /* Para pantallas pequeñas (1 columna) */
                    @media (max-width: 576px) {
                        .botones-container {
                            grid-template-columns: 1fr;
                        }
                    }

                    .alert-danger {
                        color: #721c24;
                        background-color: #f8d7da;
                        border-color: #f5c6cb;
                    }
                </style>




                <div class="row" id="myTabContent">
                    <div class="tab-pane fade col-lg-12  show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                        <div class="x_panel" style="min-height: 80vh">
                            <div class="x_title d-flex justify-content-between">
                                <div style="display: grid">
                                    <h2>Carrito del cliente</h2>
                                    <span><b>SUCURSAL: </b><span id="sucursal_nombre">
                                        </span></span>
                                </div>
                                <button class="btn btn-sm btn-success" style="height: min-content" id="open-modal"> (B) BÚSQUEDA</button>
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





                                    <div style=" bottom: 0; flex-wrap: wrap-reverse;" class="pt-3 botones-container hide w-100" id="botones_acciones">


                                        <?php

                                        // Mostrar botones según permisos
                                        if (!empty($_SESSION['permisos'][11]) || $_SESSION["nivel"] == 1) {
                                            echo '<button onclick="confirmarVenta(\'credito\')" class="btn btn-dark text-white">CRÉDITO</button>';
                                        }

                                        if (!empty($_SESSION['permisos'][12]) || $_SESSION["nivel"] == 1) {
                                            echo '<a onclick="confirmarDescuento()" class="btn btn-dark text-white" style="cursor: pointer">DESCONTAR PRODUCTOS</a>';
                                        }

                                        ?>


                                        <button class="btn btn-dark" id="btn-reservar">RESERVAR CARRITO</button>
                                        <button class="btn btn-dark" id="calcularVuelto">(C) CALCULAR CAMBIO</button>
                                        <button id="btn-vender" class="btn btn-dark" style="color:white;">(V) VENDER</button>
                                        <a onclick="vaciarCarritoJs()" class="btn btn-danger " style="color:white; cursor: pointer">DESTRUIR CARRITO</a>



                                        <div class="error-internet">

                                            <div class="alert alert-danger hide" id="alert-internet" role="alert" style="display: flex;gap: 5px;">
                                                <ion-icon style="font-size: 20px ;" name="warning-outline"></ion-icon>
                                                <span>
                                                    En estos momentos no tiene conexion a internet, las ventas se guardaran en su dispositivo y se enviarán cuando vuelva a tener conexión.

                                                </span>

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade col-lg-12 " id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <div class="x_panel" style="min-height: 60vh">
                            <div class="x_title d-flex justify-content-between">
                                <div>
                                    <h2>No guardados</h2>
                                </div>
                            </div>
                            <div class="x_content cart">
                                <ul class="p-0" id="ul-productos-sin-enviar">

                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade col-lg-12" id="contact" role="tabpanel" aria-labelledby="contact-tab">

                        <div class="x_panel" style="min-height: 60vh">
                            <div class="x_title d-flex justify-content-between">
                                <div>
                                    <h2>Reservados</h2>
                                </div>
                            </div>
                            <div class="x_content cart">
                                <ul class="p-0" id="ul-productos-reservados">

                                </ul>
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
                                        <thead style="font-size: medium;">
                                            <tr>
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
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <script src="../vendors/nprogress/nprogress.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="../build/js/modal.js"></script>
    <!-- FastClick -->
    <script>
        var productos = <?php echo json_encode($data); ?>;
        var productos_por_id = <?php echo json_encode($productos_por_id); ?>;
        var codigos = []



        const base_url = '../../configurar/';
        const sucursal_n = <?php echo json_encode($sucursal_nombre) ?>;
        const sucursal_i = <?php echo json_encode($sucursal) ?>;


        /* buscador de productos */
        const codigos_indexados = Object.values(productos);
        const productos_indexados = Object.values(productos_por_id);

        // Instanciar Fuse con configuración mínima y precisa
        const fuse = new Fuse(productos_indexados, {
            keys: ['nombre'], // Puedes incluir 'codigo' si deseas buscar también por él
            threshold: 0.28, // 0.1 puede ser demasiado estricto para nombres incompletos
            ignoreLocation: true, // Permite coincidencias en cualquier parte del string
            includeScore: false, // No necesitas el score si solo devuelves los ítems
            useExtendedSearch: false // Acelera búsqueda si no usas operadores especiales
        });


        const fuseCodigos = new Fuse(codigos_indexados, {
            keys: ['codigo'], // Puedes incluir 'codigo' si deseas buscar también por él
            threshold: 0, // 0.1 puede ser demasiado estricto para nombres incompletos
            ignoreLocation: false, // Permite coincidencias en cualquier parte del string
            includeScore: false, // No necesitas el score si solo devuelves los ítems
            useExtendedSearch: true // Acelera búsqueda si no usas operadores especiales
        });

        // Función de búsqueda rápida y limpia
        const buscarConFuse = termino => fuse.search(`=${termino}`).map(r => r.item);
        const buscarCodigoFuse = codigo => fuseCodigos.search(codigo).map(r => r.item);

        /* buscador de productos */




        const metodosPago = {
            'option1': 'Punto',
            'option2': 'Pago Móvil',
            'option3': 'Transferencia',
            'option4': 'Efectivo',
            'option5': 'Dólares',
            'option6': 'Pesos',
            'option7': 'BioPago'
        };








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
                                    <td> 
                                    ${orden.pagoPor} 
                                    <small>(${orden.tipoVenta})</small>
                                    <br>
                                    <small class="text-muted">${orden.fecha}</small></td>
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
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                return false;
            }
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
                    procesarPedido('0', 2, nombreCliente);
                } else {
                    const metodoPago = result.value; // e.g. "option3"
                    procesarPedido(metodoPago, 1);
                }
            });
        }



        function calcularVuelto() {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                return false;
            }

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
                modo = 2
            }


            if (event.target.closest('.delete-scan')) {
                document.getElementById('result-escaner').innerHTML = '';
                $('.section-scanner').addClass('hide')
            }

        });

        let ultimo_escaneado = 0



        // Se usa para el modo lector
        function buscarProducto(lectura, modo) {
            const codigo = parseFloat(lectura.trim().replace(/[^0-9]/g, '')); // Eliminar caracteres no numéricos
            console.log(String(codigo))

            const resultado = buscarCodigoFuse(String(codigo));

            console.log(resultado)

            if (resultado.length == 0) {
                Alerta.toast('error', 'El producto no existe, agrégalo de forma manual.')
                return
            } else {
                const datos = productos[lectura.trim()];
                $('.section-scanner').removeClass('hide');

                // Construir solo las tasas visibles según tasasMostrar
                $("#result-escaner").html(`
                <div class="scan-product">
                    <div class="scan-product-header">
                        <span><b>${datos.nombre}</b></span>
                        <span class="scan-stock">${datos.stock} en stock</span>
                    </div>

                    <div class="scan-prices">
                        <span class="scan-price-usd">$${formatNumber(datos.precio_dolar_visible)}</span>
                        <span class="scan-price-cop">${formatearMiles(datos.precio_peso_visible)} P</span>
                        <span class="scan-price-bs">${formatNumber(recortarADosDecimales(datos.precio_bs_visible))} Bs</span>
                    </div>

                    <div class="scan-controls">
                        <input type="number" id="cantidad-scan" class="scan-quantity cantidad-scan" 
                            data-cantidad-id="${datos.id}" value="1">

                        <button class="scan-btn add btn-add-to-car no-send" 
                            id="btn_${datos.id}"
                            data-add-id="${datos.id}"
                            data-codigo="${datos.codigo}"
                            data-P_D="${datos.precio_dolar_visible}"
                            data-P_P="${datos.precio_peso_visible}"
                            data-P_B="${datos.precio_bs_visible}"
                            data-mayor="${datos.mayor}"
                            data-cantidad_por_mayor="${datos.cantidadPaca}">
                            <i class="bx bx-cart-add"></i>
                        </button>

                        <button class="scan-btn remove delete-scan">
                            <i class="bx bx-cart-download"></i>
                        </button>
                    </div>
                </div>
            `);

                setTimeout(() => {
                    $(`#btn_${datos.id}`).removeClass('no-send');
                }, 900);
                ultimo_escaneado = datos.id;
            }

        }




        function representarResultado(resultado) {
            $("#tabla_resultado_codigo_producto").html('')
            if (!Array.isArray(resultado)) return;

            resultado.forEach(item => {
                const rest = (item.mayor === '1' ?
                    '<span style="margin: 5px;" class="fw-medium text-decoration-none me-2 badge badge-subtle-success">Mayor</span>' :
                    item.stock);

                $("#tabla_resultado_codigo_producto").append(`
                        <tr>
                            <td>${rest}</td>
                            <td style="font-size: 15px;"><span>${item.nombre}</span></td>
                            <td style="place-content: center" class="text-center text-total text-success">
                                <span>${formatNumber(item.precio_dolar_visible)}$</span>
                            </td>
                            <td style="place-content: center" class="text-center text-total text-info">
                                <span>${formatearMiles(formatPeso(item.precio_peso_visible))} Cop</span>
                            </td>
                            <td style="place-content: center" class="text-center text-total text-danger">
                                <span>${formatNumber(recortarADosDecimales(item.precio_bs_visible))} Bs</span>
                            </td>
                            <td class="text-center">
                                <input 
                                    data-nombre='${item.nombre}' 
                                    data-precios='${item.precio_peso_visible}/${item.precio_dolar_visible}/${item.precio_bs_visible}' 
                                    type="number" 
                                    style="color: black !important; width: 70px; text-align: center; border: 1px solid gray;" 
                                    class="form-control-sm cantidad-input" 
                                    data-cantidad-id="${item.id}" 
                                    value="1">
                            </td>
                            <td style="place-content: center" class="text-center">
                                <button class="btn btn-sm btn-success btn-add-to-car" 
                                    data-add-id="${item.id}"
                                    data-codigo="${item.codigo || ''}"
                                    data-P_D="${item.precio_dolar_visible}"
                                    data-P_P="${item.precio_peso_visible}"
                                    data-P_B="${item.precio_bs_visible}"
                                    data-mayor="${item.mayor}"
                                    data-cantidad_por_mayor="${item.cantidadPaca}">
                                    <i class="fa fa-shopping-cart"></i>
                                </button>
                            </td>
                        </tr>
                    `);
            });
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
        })


        $(document).on('keyup', '#search', function() {
            var nombreProducto = $(this).val();
            if (nombreProducto.length > 2) {

                let resultados = buscarConFuse(nombreProducto)
                representarResultado(resultados)


            } else {
                // vacia la tabla
                $("#tabla_resultado_codigo_producto").html('');
                const modal_footer = document.getElementById('modal-footer')
                modal_footer.innerHTML = ''
                modal_footer.classList.add('hide')
            }
        });




        // * IndexedBD //
        // Inicializar base IndexedDB con Dexie
        const db = new Dexie("POS_DB");

        // Definir estructura
        db.version(1).stores({
            carritoActivo: 'id', // clave primaria será el id del producto
            carritosVenta: 'id', // para ventas ya procesadas
            carritosReservados: 'id' // para reservados
        });
        // * IndexedBD //


        let carritoActivo = {};

        // Cargar carrito activo desde IndexedDB al iniciar
        (async function cargarCarritoInicial() {
            const items = await db.carritoActivo.toArray();
            carritoActivo = items.reduce((obj, item) => {
                obj[item.id] = item;
                return obj;
            }, {});
            actualizarCarritoJs();
        })();

        async function actualizarCarritoActivo() {
            const items = await db.carritoActivo.toArray();
            carritoActivo = items.reduce((obj, item) => {
                obj[item.id] = item;
                return obj;
            }, {});
            actualizarCarritoJs();
        }


        // REVISADO
        async function addtocarJS(id, dolarventa_p, pesoventa_p, bolivarventa_p, mayor, cantidad_por_mayor, cantidad_scann = null) {
            const inputCantidad = document.querySelector(`input[data-cantidad-id="${id}"]`);
            let cant = inputCantidad ? parseFloat(inputCantidad.value) : 1;


            // verifica que cantidad_scann sea un numero
            if (isNaN(cant) || cant <= 0) {
                Alerta.toast('error', 'Cantidad inválida. Debe ser un número mayor a 0.');
                return;
            }


            if (cantidad_scann != null) cant = parseFloat(cantidad_scann);

            if (!productos_por_id[id]) {
                console.error(`Producto con ID ${id} no encontrado.`);
                return;
            }
            const idPedido = id.toString(); // Asegurarse de que el ID sea una cadena

            const producto = productos_por_id[idPedido];

            if (carritoActivo[idPedido]) {
                carritoActivo[idPedido].qty += cant;
            } else {
                carritoActivo[idPedido] = {
                    id: idPedido,
                    name: producto.nombre,
                    price_C: parseFloat(producto.price_C),
                    price_C_Bs: parseFloat(producto.price_C_Bs),
                    price_C_Cop: parseFloat(producto.price_C_Cop),
                    price: parseFloat(dolarventa_p),
                    pricePeso: parseFloat(pesoventa_p),
                    priceBolivar: parseFloat(bolivarventa_p),
                    qty: cant,
                    mayor: mayor,
                    cantidadPaca: cantidad_por_mayor
                };
            }

            if (carritoActivo[idPedido].qty == 0) {
                await db.carritoActivo.delete(idPedido);
            } else {
                await db.carritoActivo.put(carritoActivo[idPedido]);
            }

            $("#tabla_resultado_codigo_producto").html('');
            actualizarCarritoJs();
            $("#search").val('');
        }

        function vaciarCarritoJs() {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                return false;
            }
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción vaciará tu carrito.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, vaciar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await db.carritoActivo.clear();
                    carritoActivo = {};
                    actualizarCarritoJs();
                    Alerta.toast('success', 'Carrito vaciado correctamente');
                }
            });
        }


        async function actualizarCarritoJs() {

            $("#tabla-carrito tbody").html('');
            $("#tabla-carrito tfoot").html('');

            // Para sumar o restar unidades a un producto específico

            total_pesos = total_dolares = total_bolivares = 0;


            const items = Object.values(carritoActivo);

            if (items.length > 0) {
                items.forEach(element => {
                    let subtotalPeso = element.pricePeso * element.qty;
                    let subtotalBolivar = element.priceBolivar * element.qty;
                    let subtotalDolar = element.price * element.qty;
                    subtotalPeso = Math.round(subtotalPeso);

                    $("#tabla-carrito tbody").append(`
                        <tr>
                            <td class="ac-c">${element.qty}</td>
                            <td class="ac-c">${element.name}</td>
                            <td class="ac-c">${formatearMiles(subtotalPeso)} Cop</td>
                            <td class="ac-c">${formatNumber(subtotalBolivar)} Bs</td>
                            <td class="ac-c">$${formatNumber(subtotalDolar)}</td>
                            <td class="ac-c">
                                <button class="btn btn-sm btn-outline-success" onclick="actualizarProductosCantidad('${element.id}', 'sumar')"><ion-icon style="font-size: 12px" name="arrow-up"></ion-icon></button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="actualizarProductosCantidad('${element.id}', 'restar')"><ion-icon style="font-size: 12px" name="arrow-down"></ion-icon></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="quitarProductoJs('${element.id}')"><ion-icon style="font-size: 12px"  name="trash-outline"></ion-icon></button>
                            </td>
                        </tr>
                    `);

                    total_pesos += subtotalPeso;
                    total_dolares += subtotalDolar;
                    total_bolivares += subtotalBolivar;
                });
                // elimina los decimales de total_pesos
                total_pesos = Math.round(total_pesos);

                $("#tabla-carrito tfoot").html(`
                    <tr>
                        <td></td>
                        <td><b>TOTAL:</b></td>
                        <td class="text-info">${formatearMiles(total_pesos)} Cop</td>
                        <td class="text-danger">${formatNumber(total_bolivares)} Bs</td>
                        <td class="text-success">$${formatNumber(total_dolares)}</td>
                        <td></td>
                    </tr>
                    `);

                $('#botones_acciones').removeClass('hide');
            } else {
                $('#botones_acciones').addClass('hide');
            }
        }

        // Para sumar o restar unidades a un producto específico
        async function actualizarProductosCantidad(id, accion) {
            if (id && accion) {
                if (carritoActivo[id]) {
                    if (accion === 'sumar') {
                        carritoActivo[id].qty += 1;
                        await db.carritoActivo.put(carritoActivo[id]);
                    } else if (accion === 'restar') {
                        carritoActivo[id].qty -= 1;
                        if (carritoActivo[id].qty <= 0) {
                            delete carritoActivo[id]; // también quitar de memoria
                            await deleteFromIndexedDB('carritoActivo', id);
                        } else {
                            await db.carritoActivo.put(carritoActivo[id]);
                        }
                    }
                    actualizarCarritoJs();
                }
            }
        }

        // Eliminar un producto del carrito
        async function quitarProductoJs(id) {
            await deleteFromIndexedDB('carritoActivo', id);
            await actualizarCarritoActivo()
        }
        // Eliminar un producto del carrito




        // ======================
        // FORMATEO DE NÚMEROS
        // ======================
        function formatNumber(num) {
            return parseFloat(num).toLocaleString('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // ======================
        // PROCESAR PEDIDO (VENTA)
        // ======================
        async function procesarPedido(metodoPago, despacho, nombreC = null) {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                console.warn("No hay carrito activo para procesar.");
                return false;
            }

            const idPedido = String(Date.now()) + '-' + Math.floor(Math.random() * 10000);
            const datosCliente = {
                nombre: nombreC || "",
                cedula: "",
                telefono: ""
            };

            let valorFinalBs = 0;
            let valorFinalCop = 0;
            let valorFinalVenta = 0;

            for (let k in carritoActivo) {
                if (carritoActivo.hasOwnProperty(k)) {
                    let prod = carritoActivo[k];
                    console.log('PRODU' + prod)
                    // aquí asumo que quieres precio * cantidad
                    valorFinalVenta += prod.price * (prod.qty ?? 1);
                    valorFinalBs += prod.priceBolivar * (prod.qty ?? 1);
                    valorFinalCop += prod.pricePeso * (prod.qty ?? 1);
                }
            }
            console.log("ValorFinalVenta:", valorFinalBs);
            console.log("ValorFinalVenta:", valorFinalCop);


            let nuevoPedido = {
                id: idPedido,
                metodoPago,
                despacho, // 1= Venta normal, 2= crédito, 3= descuento
                valorFinalVenta,
                valorFinalBs,
                valorFinalCop,
                datosCliente,
                productos: carritoActivo
            };

            // Guardar en IndexedDB
            try {
                await db.carritosVenta.put(nuevoPedido);
                //  console.log(`Pedido ${idPedido} guardado en IndexedDB`);


                // Limpiar carrito activo
                carritoActivo = {};
                await db.carritoActivo.clear();

                $("#tabla-carrito tbody").html('');
                $("#tabla-carrito tfoot").html('');

                // Llamar función de envío (puede sincronizar cuando haya internet)
                enviarPedidosProcesados();

                return true;


            } catch (e) {
                console.error("Error guardando en IndexedDB, ", e);
            }


        }

        document.getElementById('btn-vender').addEventListener('click', function() {
            confirmarVenta('venta');
        });


        // ======================
        // GUARDAR CARRITO RESERVADO
        // ======================
        async function reservarCarrito() {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                console.warn("No hay carrito activo para procesar.");
                return false;
            }

            Swal.fire({
                title: 'Identifique el carrito para reservar',
                html: `<input id="cliente" class="form-control" placeholder="Identificador (puede ser el nombre del cliente)">`,
                confirmButtonText: 'Continuar',
                confirmButtonColor: '#32d7c0',
                customClass: {
                    popup: 'swal-metodo-pago'
                },
                didOpen: () => Swal.getConfirmButton().setAttribute('id', 'btnVender'),
                preConfirm: () => {
                    const nombre = document.getElementById('cliente').value.trim();
                    if (!nombre) Swal.showValidationMessage('Por favor, ingresa el identificador del carrito');
                    return nombre;
                }
            }).then(async (result) => {
                if (!result.isConfirmed) return;

                const cliente = result.value;
                const idPedido = String(Date.now()) + '-' + Math.floor(Math.random() * 10000);

                let nuevoPedidoReservado = {
                    cliente,
                    id: idPedido,
                    productos: carritoActivo
                };

                // Guardar en IndexedDB
                try {
                    await db.carritosReservados.put(nuevoPedidoReservado);
                    console.log(`Carrito reservado ${idPedido} guardado en IndexedDB`);
                } catch (e) {
                    console.error("Error guardando en IndexedDB:", e);
                }
                await db.carritoActivo.clear();
                carritoActivo = {};

                $("#tabla-carrito tbody").html('');
                $("#tabla-carrito tfoot").html('');
                Alerta.toast('success', 'Carrito reservado correctamente');

                actualizarProductosReservados();
            });
        }

        document.getElementById('btn-reservar').addEventListener('click', function() {
            reservarCarrito();
        });

        // ======================
        // ENVIAR PEDIDOS PROCESADOS
        // ======================
        async function enviarPedidosProcesados() {
            // Leer pedidos de IndexedDB primero

            let pedidosIndexedDB = await db.carritosVenta.toArray();

            if (pedidosIndexedDB.length === 0) {
                console.warn("No hay pedidos procesados para enviar.");
                return;
            }

            total_dolares = 0;
            total_bolivares = 0;
            total_pesos = 0;



            comprobarConexion(async function(hayInternet) {
                if (!hayInternet) {
                    Alerta.toast('warning', 'No hay conexión a internet. Las ventas se guardarán localmente.');
                    actualizarProductosSinEnviar();
                    return;
                }



                fetch(base_url + 'accion_carta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'enviarPedidos',
                            pedidos: JSON.stringify(pedidosIndexedDB),
                        }),
                    })
                    .then((res) => res.text()) // obtener siempre como texto
                    .then(async (text) => {
                        console.log('Respuesta cruda:', text);

                        let response;
                        try {
                            response = JSON.parse(text); // intentar parsear a JSON
                        } catch (e) {
                            console.error('Error al parsear JSON:', e);
                            Alerta.toast('error', 'Respuesta no válida del servidor.');
                            return;
                        }

                        // Lógica principal
                        if (response.status) {
                            Alerta.toast('success', 'Información enviada correctamente.');
                            await db.carritosVenta.clear();
                        } else {
                            Alerta.toast('error', response.data || 'Error en la respuesta del servidor.');
                        }
                    })
                    .catch((error) => {
                        actualizarProductosSinEnviar();
                        console.error('Error en fetch:', error);
                        Alerta.toast('error', 'Error al enviar los pedidos. Intente nuevamente.');
                    });




            });
        }

        // ======================
        // MOSTRAR CARRITOS RESERVADOS
        // ======================
        async function actualizarProductosReservados() {
            document.getElementById('ul-productos-reservados').innerHTML = '';
            document.getElementById('cantidad-reservados').innerHTML = '';

            // Leer desde IndexedDB
            let items = await db.carritosReservados.toArray();
            items = items.reverse();

            if (items.length > 0) {
                document.getElementById('cantidad-reservados').innerHTML = `<span class="badge text-dark bg-warning">${items.length}</span>`;
                items.forEach(element => {
                    const fecha = new Date(parseInt(element.id));
                    const fechaEspañol = fecha.toLocaleDateString('es-VE', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                    const cliente = element.cliente || 'Cliente no especificado';

                    let productosHTML = '';
                    let totalPesos = 0;
                    let totalDolares = 0;
                    let totalBolivares = 0;
                    Object.values(element.productos).forEach(prod => {
                        productosHTML += `<span class="badge bg-light text-dark">${prod.name} (x${prod.qty})</span> `;
                        totalPesos += prod.pricePeso * prod.qty;
                        totalDolares += prod.price * prod.qty;
                        totalBolivares += prod.priceBolivar * prod.qty;
                    });

                    let html = `
                <li class="list-none item-reservado mb-2">
                    <div class="item-reservado-header p-3">
                        <div class="avatar">
                            <ion-icon name="briefcase-outline"></ion-icon>
                        </div>
                        <div>
                            <p class="m-0 p-0">${cliente}</p>
                            <small>${fechaEspañol}</small>
                        </div>
                    </div>
                    <div class="item-reservado-body pl-4 pb-3 row" >
                        <div class="d-flex justify-content-between flex-column col-lg-9">
                            <div>${productosHTML}</div>
                            <div class="d-flex mt-2 totales-pendiente">
                                <p>TOTALES:</p>
                                <p class="text-info">${formatNumber(totalPesos)} P</p>
                                <p class="text-danger">${formatNumber(totalBolivares)} Bs</p>
                                <p class="text-success">$${formatNumber(totalDolares)}</p>
                            </div>
                        </div>
                        <div class="btn-list-item text-center d-flex pr-4 col-lg-3 text-end">
                            <button class="btn btn-success" onclick="retomarCarrito('${element.id}')">Retomar carrito</button>
                            <button class="btn btn-danger" onclick="eliminarCarritoReservado('${element.id}')">Eliminar</button>
                        </div>
                    </div>
                </li>
            `;
                    document.getElementById('ul-productos-reservados').innerHTML += html;
                });
            }
        }

        // ======================
        // SINCRONIZACIÓN AUTOMÁTICA
        // ======================

        // Inicializar al cargar
        actualizarProductosReservados();
        enviarPedidosProcesados();


        //here

        async function deleteFromIndexedDB(storeName, id) {
            try {
                await db.table(storeName).delete(String(id));
                console.log(`Registro con ID ${id} eliminado de ${storeName}`);
            } catch (err) {
                console.error(`Error eliminando de ${storeName}`, err);
            }
        }

        // Obtiene un registro por clave primaria
        async function getFromIndexedDB(storeName, id) {
            try {
                return await db.table(storeName).get(String(id)); // forzar string
            } catch (error) {
                console.error(`Error obteniendo registro de ${storeName}`, error);
                return null;
            }
        }

        // Obtiene todos los registros de un store
        async function getAllFromIndexedDB(storeName) {
            try {
                return await db.table(storeName).toArray();
            } catch (error) {
                console.error(`Error obteniendo todos los registros de ${storeName}`, error);
                return [];
            }
        }


        // RETOMAR CARRITO RESERVADO
        async function retomarCarrito(carritoId) {
            try {
                const key = String(carritoId);
                const carritoData = await getFromIndexedDB('carritosReservados', key);

                if (!carritoData) {
                    Alerta.toast('error', 'Carrito reservado no encontrado');
                    return;
                }

                // 1) Limpiar store de carrito activo en IndexedDB
                await db.carritoActivo.clear();

                // 2) Reconstruir carritoActivo en memoria y preparar puts
                carritoActivo = {}; // reset en memoria
                const entries = Object.entries(carritoData.productos || {}); // [ [id, prod], ... ]

                const puts = entries.map(([prodId, prod]) => {
                    const idStr = String(prod.id ?? prodId); // asegurar string consistente
                    const record = {
                        ...prod,
                        id: idStr,
                        qty: Number(prod.qty) || 1
                    };
                    // actualizar memoria
                    carritoActivo[idStr] = record;
                    // devolver la promesa put (no await aquí)
                    return db.carritoActivo.put(record);
                });

                // 3) Esperar que terminen todas las operaciones de guardado
                await Promise.all(puts);

                // 4) Borrar el carrito reservado
                await deleteFromIndexedDB('carritosReservados', key);

                // Opcional: debug (quita en producción)
                console.log('carritoActivo (en memoria) después de retomar:', carritoActivo);
                console.log('Contenido de db.carritoActivo:', await db.carritoActivo.toArray());

                // 5) Actualizar la UI solo ahora que la memoria y la DB están listas
                await actualizarCarritoJs();

                // 6) Actualizar lista de reservados y UI general
                await actualizarProductosReservados();
                Alerta.toast('success', 'Carrito retomado correctamente');
                document.getElementById('home-tab')?.click();

            } catch (error) {
                console.error("Error al retomar carrito:", error);
                Alerta.toast('error', 'Error al retomar carrito.');
            }
        }





        // ELIMINAR CARRITO RESERVADO
        async function eliminarCarritoReservado(carritoId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará el carrito reservado.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await deleteFromIndexedDB('carritosReservados', carritoId);

                    actualizarProductosReservados();
                    Alerta.toast('success', 'Carrito eliminado correctamente');
                }
            });
        }
        // ELIMINAR CARRITO RESERVADO


        // MOSTRAR PRODUCTOS SIN ENVIAR
        async function actualizarProductosSinEnviar() {
            document.getElementById('ul-productos-sin-enviar').innerHTML = '';
            document.getElementById('cantidad-no-enviada').innerHTML = '';

            items = await getAllFromIndexedDB('carritosVenta');
            items.reverse();

            if (items.length > 0) {
                document.getElementById('cantidad-no-enviada').innerHTML = `<span class="badge bg-danger">${items.length}</span>`;

                items.forEach(element => {
                    const fecha = new Date(parseInt(element.id));
                    const fechaEspañol = fecha.toLocaleDateString('es-VE', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });

                    const metodoPago = element.metodoPago;
                    let productosHTML = '';
                    let totalPesos = 0,
                        totalDolares = 0,
                        totalBolivares = 0;

                    Object.values(element.productos).forEach(prod => {
                        productosHTML += `<span class="badge bg-light text-dark">${prod.name} (x${prod.qty})</span> `;
                        totalPesos += prod.pricePeso * prod.qty;
                        totalDolares += prod.price * prod.qty;
                        totalBolivares += prod.priceBolivar * prod.qty;
                    });

                    let tipoVenta = {
                        1: 'Venta',
                        2: 'Crédito',
                        3: 'Descuento'
                    }

                    let html = `
                <li class="list-none item-reservado mb-2">
                    <div class="item-reservado-header p-3">
                        <div class="avatar"><ion-icon name="briefcase-outline"></ion-icon></div>
                        <div>
                            <p class="m-0 p-0">${fechaEspañol}</p>
                            <small>Método de pago: <b class="text-success">${metodosPago[metodoPago] ?? 'PENDIENTE'}</b> - (${tipoVenta[element.despacho]})</small> 
                        </div>
                    </div>
                    <div class="item-reservado-body pl-4 pb-3 row">
                        <div class="d-flex justify-content-between flex-column col-lg-9">
                            <div>${productosHTML}</div>
                            <div class="d-flex mt-2 totales-pendiente">
                                <p>TOTALES:</p>
                                <p class="text-info">${formatearMiles(totalPesos)} P</p>
                                <p class="text-danger">${formatNumber(totalBolivares)} Bs</p>
                                <p class="text-success">$${formatNumber(totalDolares)}</p>
                            </div>
                        </div>
                        <div class="hide btn-list-item text-center d-flex pr-4 col-lg-3 text-end">
                            <button class="btn btn-success">Modificar</button>
                            <button class="btn btn-secondary text-dark">Cancelar envío</button>
                        </div>
                    </div>
                </li>
            `;
                    document.getElementById('ul-productos-sin-enviar').innerHTML += html;
                });
            }
        }
        // MOSTRAR PRODUCTOS SIN ENVIAR


        // Verificar la conexion
        function comprobarConexion(callback) {



            async function verificar() {
                try {
                    // Intentar una solicitud ligera para comprobar conexión
                    await fetch("https://www.google.com/favicon.ico?_=" + Date.now(), {
                        method: "HEAD",
                        mode: "no-cors",
                        cache: "no-store"
                    });

                    // Hay conexión
                    // quitarAviso();
                    document.getElementById('alert-internet').classList.add('hide');
                    callback(true);
                } catch (e) {
                    // No hay conexión
                    document.getElementById('alert-internet').classList.remove('hide');

                    callback(false);
                    setTimeout(verificar, 20000); // Reintentar en 20 segundos
                }
            }

            verificar();
        }






        document.addEventListener('click', function(event) {
            if (event.target.closest('.btn-add-to-car') && !event.target.closest('.no-send')) {
                let id_p = event.target.closest('.btn-add-to-car').getAttribute('data-add-id');

                let dolarventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_D')
                let pesoventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_P')
                let bolivarventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_B')
                let mayor = event.target.closest('.btn-add-to-car').getAttribute('data-mayor')
                let cantidad_por_mayor = event.target.closest('.btn-add-to-car').getAttribute('data-cantidad_por_mayor')
                let cantidad_scan = $('#cantidad-scan').val()

                $('#result-escaner').html('')
                $('#search').val('')
                $('.section-scanner').addClass('hide')


                addtocarJS(id_p, dolarventa_p, pesoventa_p, bolivarventa_p, mayor, cantidad_por_mayor, cantidad_scan);

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


        //todo

        function confirmarDescuento() {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                return false;
            }
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

                    procesarPedido('0', 3);

                    /*  $.ajax({
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

                      */
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
                        document.getElementById('home-tab')?.click();

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



                }
            } else if (key === 'escape') {
                closeModalButton.click();
                document.getElementById("section-scanner").classList.add('hide');
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


        setInterval(() => {
            fetch("mantener_sesion.php");
        }, 5 * 60 * 1000); // cada 5 minutos
    </script>
</body>

</html>