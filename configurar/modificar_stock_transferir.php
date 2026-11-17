<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_validador_campos.php');
require_once('_canal.php');
require_once('_stock_traslados.php');

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

$usuario_id      = (int) ($_SESSION['id'] ?? 0);
$usuario_nombre  = (string) ($_SESSION['nombre'] ?? 'Desconocido');

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
    // La bitácora de traslados se crea sola si el hosting no ejecutó la
    // migración 031. Debe hacerse antes de abrir la transacción.
    if (!st_asegurar_tabla($conexion)) {
        throw new Exception('No se pudo preparar la tabla de traslados. Ejecute la migración 031_stock_traslados.sql.');
    }

    $stmtProd = $conexion->prepare("SELECT nombre, modo_control FROM productos WHERE id = ? AND bss_id = ? LIMIT 1");
    $stmtProd->bind_param('ii', $id, $bss_id);
    $stmtProd->execute();
    $prodTransferir = $stmtProd->get_result()->fetch_assoc();
    $stmtProd->close();
    // La res en canal se transfiere completa desde Ajustes de stock; el
    // seguimiento estricto usa el mismo flujo de confirmación de las nuevas
    // compras (muestra el stock actual y pide confirmación, no bloquea).
    if ($prodTransferir && (string) ($prodTransferir['modo_control'] ?? 'NORMAL') === 'CANAL') {
        throw new Exception('Este producto es una res en canal. Transfiera la res completa desde Ajustes de stock.');
    }
    $estricto = canal_producto_estricto($conexion, $id, $bss_id);

    // VERIFICAR QUE NO SEAN LAS MISMAS SUCURSALES
    if ($sucursal === $sucursal_2) {
        throw new Exception('La sucursal origen y destino no pueden ser iguales.');
    }

    // Seguimiento estricto: el backend no confía en el frontend. Sin la
    // confirmación responde con el contexto (stock actual de la sucursal
    // destino, que es donde entra el stock) y no modifica nada.
    if ($estricto && empty($_POST['conciliacion_confirmada'])) {
        try {
            $contexto = canal_contexto_estricto($conexion, $id, $sucursal_2, $bss_id);
        } catch (Throwable $e) {
            $contexto = null;
        }
        echo json_encode([
            'success' => false,
            'requiere_conciliacion' => true,
            'message' => 'Confirme la cantidad anterior antes de transferir.',
            'contexto' => $contexto
        ]);
        exit;
    }

    $stockVisto = isset($_POST['stock_visto']) ? floatval($_POST['stock_visto']) : -1;
    if ($estricto && $stockVisto < 0) {
        throw new Exception('Falta confirmar el stock anterior.');
    }

    // VERIFICAR SI EXISTE EL PRODUCTO EN ORIGEN Y OBTENER DISPONIBILIDAD
    $stock_disponible = getStock($sucursal);

    if ($stock_disponible === false) {
        throw new Exception('El producto no existe en la sucursal de origen.');
    }

    $conexion->begin_transaction(); // INICIAR TRANSACCIÓN

    // Conciliación estricta: el stock de destino no debió cambiar desde que
    // se mostró (es el que se está confirmando).
    if ($estricto) {
        $stmt = $conexion->prepare("SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1 FOR UPDATE");
        $stmt->bind_param("iii", $id, $sucursal_2, $bss_id);
        $stmt->execute();
        $stockActual = (float) ($stmt->get_result()->fetch_assoc()['stock'] ?? 0);
        $stmt->close();
        if (abs($stockActual - $stockVisto) > 0.0001) {
            $conexion->rollback();
            try {
                $contexto = canal_contexto_estricto($conexion, $id, $sucursal_2, $bss_id);
            } catch (Throwable $e) {
                $contexto = null;
            }
            echo json_encode([
                'success' => false,
                'requiere_conciliacion' => true,
                'message' => 'El stock cambió desde que se mostró. Confirme de nuevo.',
                'contexto' => $contexto
            ]);
            exit;
        }
    }

    // SE VERIFICA SI HAY DISPONIBILIDAD PARA CUMPLIR CON LAS UNIDADES A TRANSFERIR
    if ($stock_disponible < $unidades_transferir) {
        throw new Exception('No hay stock suficiente para realizar esta acción. Disponible: ' . canal_numero($stock_disponible) . '.');
    }

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

    // Registrar el traslado para el historial de movimientos (origen → destino)
    $stmtTraslado = $conexion->prepare(
        "INSERT INTO stock_traslados
            (bss_id, id_producto, producto_nombre, cantidad, sucursal_origen, sucursal_destino, usuario_id, usuario_nombre)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmtTraslado) {
        throw new Exception('No se pudo registrar el traslado.');
    }
    $stmtTraslado->bind_param(
        "iisdiiis",
        $bss_id,
        $id,
        $prodTransferir['nombre'],
        $unidades_transferir,
        $sucursal,
        $sucursal_2,
        $usuario_id,
        $usuario_nombre
    );
    if (!$stmtTraslado->execute()) {
        $stmtTraslado->close();
        throw new Exception('No se pudo registrar el traslado.');
    }
    $stmtTraslado->close();

    $conexion->commit(); // CONFIRMAR TRANSACCIÓN
    $response['success'] = true;
} catch (Exception $e) {
    $conexion->rollback(); // REVERTIR SI HAY ERROR
    $response = ['success' => false, 'message' => $e->getMessage()];
}
echo json_encode($response);
