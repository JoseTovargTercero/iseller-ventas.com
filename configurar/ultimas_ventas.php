<?php
header('Content-Type: application/json');
require_once 'configuracion.php'; // Asegúrate de incluir la conexión
require_once 'session.php'; // Asegúrate de incluir la conexión
$user = (int) $_SESSION["id"];
$sucursal = (int) $_SESSION['sucursal'];
$bssId = (int) ($bss_id ?? ($_SESSION['bss_id'] ?? 0));
$nivel = (int) ($_SESSION['nivel'] ?? 0);

$ordenesData = [];
// El administrador ve sus ventas recientes y todas las solicitudes pendientes
// de su sucursal; el usuario estándar solo ve las suyas.
$queryOrden = "SELECT o.*, u.nombre AS usuario_nombre
    FROM orden o
    LEFT JOIN usuarios u ON u.id = o.usuario
    WHERE o.bss_id = ? AND o.id_sucursal = ?";
if ($nivel === 1) {
    $queryOrden .= " AND (o.usuario = ? OR o.deshacer_pendiente = 1)
        ORDER BY o.deshacer_pendiente DESC, o.id DESC LIMIT 50";
} else {
    $queryOrden .= " AND o.usuario = ? ORDER BY o.id DESC LIMIT 6";
}
$stmtOrden = $conexion->prepare($queryOrden);
$stmtOrden->bind_param('iii', $bssId, $sucursal, $user);
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
    $dia = date('d', $timestamp);
    $mes = $meses[date('n', $timestamp)];
    $anio = date('Y', $timestamp);

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
            'pendiente' => (int) ($orden['deshacer_pendiente'] ?? 0),
            'usuarioNombre' => $orden['usuario_nombre'] ?? '',
            'solicitadoEn' => !empty($orden['deshacer_solicitado_en']) ? fechaEnTexto($orden['deshacer_solicitado_en']) : null,
        ];
    }
}



echo json_encode($ordenesData);
