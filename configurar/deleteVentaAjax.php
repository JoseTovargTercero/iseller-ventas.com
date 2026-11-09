<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_canal.php');

$orderId = (int) ($_POST['id'] ?? 0);
$accion = trim((string) ($_POST['accion'] ?? 'eliminar'));
$nivel = (int) ($_SESSION['nivel'] ?? 0);
$usuarioSesion = (int) ($_SESSION['id'] ?? 0);
$bssId = (int) ($bss_id ?? ($_SESSION['bss_id'] ?? 0));
$sucursalSesion = (int) ($_SESSION['sucursal'] ?? 0);

if ($orderId <= 0 || ($nivel !== 1 && $nivel !== 2)) {
    echo json_encode(['status' => false, 'msg' => 'Solicitud inválida.']);
    exit;
}

// Obtener los datos de la venta antes de eliminarla
$stmtOrden = $conexion->prepare("SELECT id, usuario, id_sucursal, deshacer_pendiente FROM orden WHERE id = ? AND bss_id = ?");
if (!$stmtOrden) {
    echo json_encode(['status' => false, 'msg' => 'Error de base de datos.']);
    exit;
}
$stmtOrden->bind_param("ii", $orderId, $bssId);
$stmtOrden->execute();
$orden = $stmtOrden->get_result()->fetch_assoc();
$stmtOrden->close();

if (!$orden) {
    echo json_encode(['status' => false, 'msg' => 'Usted no tiene permisos para realizar esta accion']);
    exit;
}
if ($nivel === 2 && ((int) $orden['id_sucursal'] !== $sucursalSesion || (int) $orden['usuario'] !== $usuarioSesion)) {
    echo json_encode(['status' => false, 'msg' => 'Usted no tiene permisos para realizar esta accion']);
    exit;
}

// El administrador puede rechazar una solicitud y conservar la venta.
if ($nivel === 1 && $accion === 'rechazar') {
    $stmtRechazar = $conexion->prepare("UPDATE orden SET deshacer_pendiente = 0, deshacer_solicitado_por = NULL, deshacer_solicitado_en = NULL WHERE id = ? AND bss_id = ?");
    if (!$stmtRechazar || !$stmtRechazar->bind_param("ii", $orderId, $bssId) || !$stmtRechazar->execute()) {
        echo json_encode(['status' => false, 'msg' => 'No se pudo rechazar la solicitud.']);
        exit;
    }
    $stmtRechazar->close();
    echo json_encode(['status' => true, 'msg' => 'Solicitud rechazada. La venta se mantiene.']);
    exit;
}

// El usuario estándar no elimina: deja la venta pendiente de aprobación.
if ($nivel === 2) {
    if ((int) $orden['deshacer_pendiente'] === 1) {
        echo json_encode(['status' => false, 'pending' => true, 'msg' => 'La venta ya está pendiente por deshacer. Un administrador debe completar la operación.']);
        exit;
    }

    $stmtPendiente = $conexion->prepare("UPDATE orden SET deshacer_pendiente = 1, deshacer_solicitado_por = ?, deshacer_solicitado_en = NOW() WHERE id = ? AND bss_id = ? AND deshacer_pendiente = 0");
    if (!$stmtPendiente) {
        echo json_encode(['status' => false, 'msg' => 'Error de base de datos.']);
        exit;
    }
    $stmtPendiente->bind_param("iii", $usuarioSesion, $orderId, $bssId);
    if (!$stmtPendiente->execute() || $stmtPendiente->affected_rows < 1) {
        $stmtPendiente->close();
        echo json_encode(['status' => false, 'pending' => true, 'msg' => 'La venta ya está pendiente por deshacer.']);
        exit;
    }
    $stmtPendiente->close();

    echo json_encode(['status' => true, 'pending' => true, 'msg' => 'Solicitud enviada. Un administrador debe completar la eliminación.']);
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

        $canal = canal_devolver($conexion, (int) $productId, (int) $id_sucursal, (int) $bss_id, (float) $cantidad, (int) $orderId, (int) ($_SESSION['id'] ?? 0));
        if (!empty($canal['error'])) {
            throw new Exception($canal['error']);
        }
        if (($canal['modo'] ?? 'NORMAL') === 'CANAL') {
            continue;
        }

        // Verificar si el producto es un combo para devolver stock a cada producto hijo
        $stmtCombo = $conexion->prepare("SELECT producto_id, cantidad FROM combo_items WHERE combo_producto_id = ? AND bss_id = ?");
        $stmtCombo->bind_param("ii", $productId, $bss_id);
        $stmtCombo->execute();
        $resCombo = $stmtCombo->get_result();

        if ($resCombo && $resCombo->num_rows > 0) {
            while ($hijo = $resCombo->fetch_assoc()) {
                $idHijo = (int)$hijo['producto_id'];
                $cantDevolver = (float)$cantidad * (float)$hijo['cantidad'];

                $canalHijo = canal_devolver($conexion, $idHijo, (int) $id_sucursal, (int) $bss_id, $cantDevolver, (int) $orderId, (int) ($_SESSION['id'] ?? 0));
                if (!empty($canalHijo['error'])) {
                    throw new Exception($canalHijo['error']);
                }
                if (($canalHijo['modo'] ?? 'NORMAL') === 'CANAL') {
                    continue;
                }

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
    $stmtDeleteFrac = $conexion->prepare("DELETE FROM fracciones WHERE id_order = ? AND bss_id = ?");
    if ($stmtDeleteFrac) {
        $stmtDeleteFrac->bind_param("ii", $orderId, $bssId);
        $stmtDeleteFrac->execute();
        $stmtDeleteFrac->close();
    }

    // Eliminar la orden (sus artículos se eliminan por la FK)
    $stmtDeleteOrden = $conexion->prepare("DELETE FROM orden WHERE id = ? AND bss_id = ?");
    if (!$stmtDeleteOrden) {
        throw new Exception('Error de base de datos.');
    }
    $stmtDeleteOrden->bind_param("ii", $orderId, $bssId);
    if (!$stmtDeleteOrden->execute() || $stmtDeleteOrden->affected_rows < 1) {
        $stmtDeleteOrden->close();
        throw new Exception('No se pudo eliminar la venta.');
    }
    $stmtDeleteOrden->close();

    // Confirmar transacción
    $conexion->commit();

    echo json_encode(['status' => true, 'msg' => 'Venta eliminada correctamente.']);
    exit;
} catch (Throwable $e) {
    // Revertir en caso de error
    $conexion->rollback();
    echo json_encode(['status' => false, 'msg' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}
