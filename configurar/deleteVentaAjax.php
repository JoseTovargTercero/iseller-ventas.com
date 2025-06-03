<?php
require_once('configuracion.php');
require_once('session.php');

$orderId = $_POST['id'];
$id_sucursal = $_SESSION["sucursal"];
try {
    // Iniciar transacción
    $conexion->begin_transaction();

    // Eliminar la orden
    $stmtDeleteOrden = $conexion->prepare("DELETE FROM orden WHERE id = ?");
    $stmtDeleteOrden->bind_param("i", $orderId);
    $stmtDeleteOrden->execute();
    $stmtDeleteOrden->close();

    // Obtener productos de la orden
    $stmtArticulos = $conexion->prepare("SELECT product_id, quantity FROM orden_articulos WHERE order_id = ?");
    $stmtArticulos->bind_param("i", $orderId);
    $stmtArticulos->execute();
    $resultArticulos = $stmtArticulos->get_result();

    while ($rowArticulo = $resultArticulos->fetch_assoc()) {
        $productId = $rowArticulo['product_id'];
        $cantidad = $rowArticulo['quantity'];

        // Obtener stock actual?
        $stmtProducto = $conexion->prepare("SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ?");
        $stmtProducto->bind_param("ii", $productId, $id_sucursal);
        $stmtProducto->execute();
        $resultProducto = $stmtProducto->get_result();

        if ($producto = $resultProducto->fetch_assoc()) {
            $nuevoStock = $producto['stock'] + $cantidad;

            // Actualizar stock
            $stmtUpdateStock = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ?");
            $stmtUpdateStock->bind_param("iii", $nuevoStock, $productId, $id_sucursal);
            $stmtUpdateStock->execute();
            $stmtUpdateStock->close();
        }

        $stmtProducto->close();
    }

    $stmtArticulos->close();

    // Eliminar artículos de la orden
    $stmtDeleteArticulos = $conexion->prepare("DELETE FROM orden_articulos WHERE order_id = ?");
    $stmtDeleteArticulos->bind_param("i", $orderId);
    $stmtDeleteArticulos->execute();
    $stmtDeleteArticulos->close();

    // Confirmar transacción
    $conexion->commit();

    // Redirigir
    define('PAGINA_INICIO', '../publico/production/ventas.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
} catch (Exception $e) {
    // Revertir en caso de error
    $conexion->rollback();
    echo "Error al procesar la solicitud: " . $e->getMessage();
}
