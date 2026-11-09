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

// Obtener producto actual
$stmt = $conexion->prepare("SELECT S.stock, P.nombre, P.modo_control, P.seguimiento_estricto FROM stock AS S
LEFT JOIN productos AS P ON P.id = S.id_producto
WHERE id_producto=? AND id_sucursal=? AND S.bss_id=?");
$stmt->bind_param("iii", $id, $sucursal, $bss_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Producto no encontrado.';
    echo json_encode($response);
    exit;
}

$productoStock = $result->fetch_assoc();
if ((string) ($productoStock['modo_control'] ?? 'NORMAL') === 'CANAL' || (int) ($productoStock['seguimiento_estricto'] ?? 0) === 1) {
    $response['message'] = 'Este producto usa res en canal o seguimiento estricto. Registre el cambio en Ajustes de stock y mermas.';
    echo json_encode($response);
    exit;
}

// Actualizar el stock
$stmt = $conexion->prepare("UPDATE stock SET stock=? WHERE id_producto=? AND id_sucursal=? AND bss_id=?");
$stmt->bind_param("diii", $cantidad_stock, $id, $sucursal, $bss_id);
$stmt->execute();
$stmt->close();


$response['success'] = true;
$response['message'] = 'Producto actualizado y registrado exitosamente.';
echo json_encode($response);
