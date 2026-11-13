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

if ($_POST["cantidad_stock"] == '') {
    $response['message'] = 'La cantidad de stock no puede estar vacía.';
    echo json_encode($response);
    exit;
}


$id            = trim($_POST['producto']);
$cantidad_stock        = floatval($_POST['cantidad_stock']);
$sucursal      = $_SESSION["nivel"] == 1 ? trim($_POST['sucursal']) : $_SESSION["sucursal"];
$bss_id        = $_SESSION["bss_id"];

$usuario_id     = (int) ($_SESSION['id'] ?? 0);
$usuario_nombre = (string) ($_SESSION['nombre'] ?? 'Desconocido');

try {
    $conexion->begin_transaction();

    // Obtener producto actual (FOR UPDATE evita cambios simultáneos sobre la misma fila)
    $stmt = $conexion->prepare("SELECT S.stock, P.nombre, P.modo_control, P.seguimiento_estricto FROM stock AS S
    LEFT JOIN productos AS P ON P.id = S.id_producto
    WHERE id_producto=? AND id_sucursal=? AND S.bss_id=? FOR UPDATE");
    if (!$stmt) {
        throw new Exception('No se pudo consultar el stock del producto.');
    }
    $stmt->bind_param("iii", $id, $sucursal, $bss_id);
    if (!$stmt->execute()) {
        throw new Exception('No se pudo consultar el stock del producto.');
    }
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Producto no encontrado.');
    }

    $productoStock = $result->fetch_assoc();
    $stmt->close();

    if ((string) ($productoStock['modo_control'] ?? 'NORMAL') === 'CANAL' || (int) ($productoStock['seguimiento_estricto'] ?? 0) === 1) {
        throw new Exception('Este producto usa res en canal o seguimiento estricto. Registre el cambio en Ajustes de stock y mermas.');
    }

    $stock_anterior = (float) $productoStock['stock'];
    $diferencia     = $cantidad_stock - $stock_anterior;

    // Actualizar el stock
    $stmt = $conexion->prepare("UPDATE stock SET stock=? WHERE id_producto=? AND id_sucursal=? AND bss_id=?");
    if (!$stmt) {
        throw new Exception('No se pudo actualizar el stock.');
    }
    $stmt->bind_param("diii", $cantidad_stock, $id, $sucursal, $bss_id);
    if (!$stmt->execute()) {
        throw new Exception('No se pudo actualizar el stock.');
    }
    $stmt->close();

    // Guardar el historial: cantidad anterior, cantidad nueva y usuario
    $producto_nombre = (string) ($productoStock['nombre'] ?? '');
    $stmtHist = $conexion->prepare("
        INSERT INTO stock_modificaciones
            (bss_id, id_sucursal, id_producto, producto_nombre, stock_anterior, stock_nuevo, diferencia, usuario_id, usuario_nombre)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmtHist) {
        throw new Exception('No se pudo registrar el historial del cambio.');
    }
    $stmtHist->bind_param("iiisdddis", $bss_id, $sucursal, $id, $producto_nombre, $stock_anterior, $cantidad_stock, $diferencia, $usuario_id, $usuario_nombre);
    if (!$stmtHist->execute()) {
        throw new Exception('No se pudo registrar el historial del cambio.');
    }
    $stmtHist->close();

    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
}


$response['success'] = true;
$response['message'] = 'Producto actualizado y registrado exitosamente.';
echo json_encode($response);
