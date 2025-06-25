<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_validador_campos.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$campos_requeridos = ['producto'];
if ($_SESSION["nivel"] == 1) {
    array_push($campos_requeridos, 'sucursal');
}
$validador = new ValidadorCampos($campos_requeridos, 'POST');
$validador->validar();



$id                       = intval($_POST['producto']);
$unidades_transferir      = floatval($_POST['unidades_transferir']);
$bss_id                   = $_SESSION["bss_id"];
$sucursal                 = $_POST['sucursal'];
$sucursal_2               = $_POST['sucursal_2'];

// Obtener existencia del porducto

function getStock($sucursal)
{
    global $conexion, $bss_id, $id;

    $sql = "SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $id, $sucursal, $bss_id);
    $stmt->execute();
    $stmt->bind_result($stock);
    $found = $stmt->fetch();
    $stmt->close();

    return $found ? (float)$stock : false;
}


try {
    // VERIFICAR QUE NO SEAN LAS MISMAS SUCURSALES
    if ($sucursal === $sucursal_2) {
        throw new Exception('La sucursal origen y destino no pueden ser iguales.');
    }

    // VERIFICAR SI EXISTE EL PRODUCTO EN ORIGEN Y OBTENER DISPONIBILIDAD
    $stock_disponible = getStock($sucursal);

    if ($stock_disponible === false) {
        throw new Exception('El producto no existe en la sucursal de origen.');
    }


    // SE VERIFICA SI HAY DISPONIBILIDAD PARA CUMPLIR CON LAS UNIDADES A TRANSFERIR
    if ($stock_disponible < $unidades_transferir) {
        throw new Exception('No hay stock suficiente para realizar esta acción.');
    }

    $conexion->begin_transaction(); // INICIAR TRANSACCIÓN

    // VERIFICAR SI EXISTE EL PRODUCTO EN DESTINO
    $stock_destino = getStock($sucursal_2);
    if ($stock_destino === false) {
        // Obtener porcentaje del producto
        $stmt = $conexion->prepare("SELECT porcentaje FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($porcentaje);
        if (!$stmt->fetch()) {
            throw new Exception('Producto no encontrado.');
        }
        $stmt->close();


        // Insertar producto en destino
        $stmt = $conexion->prepare("INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iddii", $id, $porcentaje, $unidades_transferir, $sucursal_2, $bss_id);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo registrar el producto en la sucursal destino.');
        }
        $stmt->close();

        $response['message'] = 'El producto se creó en la sucursal destino y el stock fue actualizado.';
    } else {
        // Actualizar stock en destino
        $nuevo_stock_destino = $stock_destino + $unidades_transferir;

        // Actualizar el stock en destino
        $stmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
        $stmt->bind_param("diii", $nuevo_stock_destino, $id, $sucursal_2, $bss_id);
        if (!$stmt->execute()) {
            throw new Exception('No se pudo actualizar el stock en la sucursal destino.');
        }
        $stmt->close();

        $response['message'] = 'Stock actualizado exitosamente.';
    }

    // Actualizar el stock en origen
    $stock_disponible_origen = $stock_disponible - $unidades_transferir;

    $stmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
    $stmt->bind_param("diii", $stock_disponible_origen, $id, $sucursal, $bss_id);
    if (!$stmt->execute()) {
        throw new Exception('No se pudo actualizar el stock en la sucursal de origen.');
    }

    $stmt->close();
    $conexion->commit(); // CONFIRMAR TRANSACCIÓN
    $response['success'] = true;
} catch (Exception $e) {
    $conexion->rollback(); // REVERTIR SI HAY ERROR
    $response = ['success' => false, 'message' => $e->getMessage()];
}
echo json_encode($response);
