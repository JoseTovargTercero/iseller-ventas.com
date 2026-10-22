<?php
require_once('configuracion.php');
require_once('session.php');

$orderId = $_POST['id'];



// Obtener los datos de la venta antes de eliminarla
$stmtArticulos = $conexion->prepare("SELECT * FROM orden WHERE id = ? AND bss_id = ?");
$stmtArticulos->bind_param("ii", $orderId, $bss_id);
$stmtArticulos->execute();
$resultArticulos = $stmtArticulos->get_result();
if ($resultArticulos->num_rows > 0) {
    while ($rowArticulo = $resultArticulos->fetch_assoc()) {
        if ($_SESSION['nivel'] == 2 && $_SESSION['sucursal'] != $rowArticulo['id_sucursal']) {
            echo json_encode(['status' => false, 'msg' => 'Usted no tiene permisos para realizar esta accion']);
            exit;
        }
    }
} else {
    echo json_encode(['status' => false, 'msg' => 'Usted no tiene permisos para realizar esta accion']);
    exit;
}



try {
    // Iniciar transacción
    $conexion->begin_transaction();

    // Obtener productos de la orden
    $stmtArticulos = $conexion->prepare("SELECT product_id, quantity, id_sucursal  FROM orden_articulos WHERE order_id = ?");
    $stmtArticulos->bind_param("i", $orderId);
    $stmtArticulos->execute();
    $resultArticulos = $stmtArticulos->get_result();

    while ($rowArticulo = $resultArticulos->fetch_assoc()) {
        $productId = $rowArticulo['product_id'];
        $cantidad = $rowArticulo['quantity'];
        $id_sucursal = $rowArticulo["id_sucursal"];

        // Verificar si el producto es un combo para devolver stock a cada producto hijo
        $stmtCombo = $conexion->prepare("SELECT producto_id, cantidad FROM combo_items WHERE combo_producto_id = ? AND bss_id = ?");
        $stmtCombo->bind_param("ii", $productId, $bss_id);
        $stmtCombo->execute();
        $resCombo = $stmtCombo->get_result();

        if ($resCombo && $resCombo->num_rows > 0) {
            while ($hijo = $resCombo->fetch_assoc()) {
                $idHijo = (int)$hijo['producto_id'];
                $cantDevolver = (float)$cantidad * (float)$hijo['cantidad'];

                $stmtProdHijo = $conexion->prepare("SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ?");
                $stmtProdHijo->bind_param("ii", $idHijo, $id_sucursal);
                $stmtProdHijo->execute();
                $resProdHijo = $stmtProdHijo->get_result();
                if ($prodH = $resProdHijo->fetch_assoc()) {
                    $nuevoStockH = (float)$prodH['stock'] + $cantDevolver;
                    $stmtUpdH = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ?");
                    $stmtUpdH->bind_param("sii", $nuevoStockH, $idHijo, $id_sucursal);
                    $stmtUpdH->execute();
                    $stmtUpdH->close();
                }
                $stmtProdHijo->close();
            }
            $stmtCombo->close();
            continue;
        }
        $stmtCombo->close();

        // Venta al mayor: la cantidad vendida son pacas/cajas, pero el stock físico vive
        // en el producto base enlazado por id_stock; se devuelve por cantidad_unidades.
        $stmtMayor = $conexion->prepare("
            SELECT p.mayor, p.cantidad_unidades, s.id_stock
              FROM productos p
             INNER JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
             WHERE p.id = ? AND p.bss_id = ?
             LIMIT 1");
        $stmtMayor->bind_param("iiii", $id_sucursal, $bss_id, $productId, $bss_id);
        $stmtMayor->execute();
        $prodMayor = $stmtMayor->get_result()->fetch_assoc();
        $stmtMayor->close();

        if ($prodMayor && $prodMayor['mayor'] == '1') {
            $unidades = (float) $prodMayor['cantidad_unidades'];
            $idStock = (int) $prodMayor['id_stock'];
            if ($idStock > 0 && $unidades > 0) {
                $stmtUpdMayor = $conexion->prepare("UPDATE stock SET stock = stock + ? WHERE id = ? AND id_sucursal = ? AND bss_id = ?");
                $cantDevolver = (float) $cantidad * $unidades;
                $stmtUpdMayor->bind_param("diii", $cantDevolver, $idStock, $id_sucursal, $bss_id);
                $stmtUpdMayor->execute();
                $stmtUpdMayor->close();
            }
        } else {
            $stmtUpdateStock = $conexion->prepare("UPDATE stock SET stock = stock + ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
            $stmtUpdateStock->bind_param("diii", $cantidad, $productId, $id_sucursal, $bss_id);
            $stmtUpdateStock->execute();
            $stmtUpdateStock->close();
        }
    }

    $stmtArticulos->close();


    // Eliminar el desglose de pago fraccionado asociado (si existe)
    $stmtDeleteFrac = $conexion->prepare("DELETE FROM fracciones WHERE id_order = ?");
    if ($stmtDeleteFrac) {
        $stmtDeleteFrac->bind_param("i", $orderId);
        $stmtDeleteFrac->execute();
        $stmtDeleteFrac->close();
    }

    // Eliminar la orden
    $stmtDeleteOrden = $conexion->prepare("DELETE FROM orden WHERE id = ?");
    $stmtDeleteOrden->bind_param("i", $orderId);
    $stmtDeleteOrden->execute();
    $stmtDeleteOrden->close();



    echo json_encode(['status' => true, 'msg' => '']);

    // Confirmar transacción
    $conexion->commit();


    exit;
} catch (Exception $e) {
    // Revertir en caso de error
    $conexion->rollback();
    echo "Error al procesar la solicitud: " . $e->getMessage();
}
