<?php
header('Content-Type: application/json');
require_once 'configuracion.php'; // Asegúrate de incluir la conexión
require_once 'session.php'; // Asegúrate de incluir la conexión
$user = $_SESSION["id"];
$sucursal =  $_SESSION['sucursal'];

$ordenesData = [];
$queryOrden = "SELECT * FROM orden WHERE customer_id = ? AND id_sucursal = ? ORDER BY id DESC LIMIT 6";
$stmtOrden = $conexion->prepare($queryOrden);
$stmtOrden->bind_param('ii', $user, $sucursal);
$stmtOrden->execute();
$resultOrden = $stmtOrden->get_result();


function fechaEnTexto($fecha, $formato24h = false)
{
    $meses = [
        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre'
    ];


    setlocale(LC_TIME, 'es_ES.UTF-8'); // Asegúrate de tener configurado el locale en tu servidor
    date_default_timezone_set('America/Caracas'); // Cambia según tu zona horaria

    $timestamp = strtotime($fecha);

    // Obtener partes de la fecha
    $dia = strftime('%d', $timestamp);
    $mes = $meses[date('n', $timestamp)];
    $anio = strftime('%Y', $timestamp);

    if ($formato24h) {
        $hora = date('H:i', $timestamp);
        $textoHora = "a las $hora";
    } else {
        $hora = date('g:i A', $timestamp);
        $textoHora = "a las $hora";
    }

    // Primera letra del mes en minúscula
    $mes = mb_strtolower($mes, 'UTF-8');

    return "$dia de $mes de $anio $textoHora";
}



if ($resultOrden->num_rows > 0) {
    while ($orden = $resultOrden->fetch_assoc()) {
        $productos = '';

        $orderId = $orden['id'];
        // Usuario

        // Productos
        $stmtArticulos = $conexion->prepare("SELECT product_id, quantity FROM orden_articulos WHERE order_id = ?");
        $stmtArticulos->bind_param("i", $orderId);
        $stmtArticulos->execute();
        $resultArticulos = $stmtArticulos->get_result();

        while ($articulo = $resultArticulos->fetch_assoc()) {
            $stmtProducto = $conexion->prepare("SELECT nombre FROM productos WHERE id = ?");
            $stmtProducto->bind_param("i", $articulo['product_id']);
            $stmtProducto->execute();
            $resultProducto = $stmtProducto->get_result();
            if ($resultProducto->num_rows > 0) {
                $productoData = $resultProducto->fetch_assoc();
                $productos .= $articulo['quantity'] . ' ' . $productoData['nombre'] . ', ';
            }
            $stmtProducto->close();
        }
        $stmtArticulos->close();

        $productos = rtrim($productos, ', ');

        $tiposPago = [
            '1' => 'Punto',
            '2' => 'Pago Móvil',
            '3' => 'Transferencia',
            '4' => 'BS Efectivo',
            '5' => 'Dólares',
            '6' => 'Pesos',
            '7' => 'Biopago',
            '8' => 'Fraccionado',
        ];
        $pagoPor = $tiposPago[$orden['tipoPago']] ?? 'Pendiente';

        $tiposVenta = [
            '1' => 'Al detal',
            '3' => 'Descuento',
            '4' => 'Al mayor',
        ];
        $tVenta = $tiposVenta[$orden['status']] ?? 'Crédito';

        $ordenesData[] = [
            'id' => $orderId,
            'tipoVenta' => $tVenta,
            'pagoPor' => $pagoPor,
            'fecha' => fechaEnTexto($orden['created']),
            'total' => number_format($orden['total_price'], 2, ',', '.'),
        ];
    }
}



echo json_encode($ordenesData);
