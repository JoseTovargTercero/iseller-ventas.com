<?php
header('Content-Type: application/json');
require_once 'configuracion.php'; // Asegúrate de incluir la conexión
require_once 'session.php'; // Asegúrate de incluir la conexión
$user = $_SESSION["id"];

$ordenesData = [];
$queryOrden = "SELECT * FROM orden WHERE customer_id = ? ORDER BY id DESC LIMIT 3";
$stmtOrden = $conexion->prepare($queryOrden);
$stmtOrden->bind_param('i', $user);
$stmtOrden->execute();
$resultOrden = $stmtOrden->get_result();

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
            'fecha' => $orden['created'],
            'total' => number_format($orden['total_price'], 2, ',', '.'),
        ];
    }
}

echo json_encode($ordenesData);
