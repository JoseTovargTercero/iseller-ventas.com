<?php
require_once('configuracion.php');
require_once('_validador_campos.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];



$campos_requeridos = ['producto', 'precio', 'porcentaje', 'comprado', 'cantidad', 'proveedor'];
$validador = new ValidadorCampos($campos_requeridos, 'POST');
$validador->validar();


$id            = trim($_POST['producto']);
$precio        = floatval($_POST['precio']);
$porcentaje    = floatval($_POST['porcentaje']);
$cantidadNueva = intval($_POST['comprado']);
$cantidadPP    = intval($_POST['cantidad']);
$proveedor     = trim($_POST['proveedor']);

// Obtener producto actual
$stmt = $conexion->prepare("SELECT stock, nombre FROM productos WHERE id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Producto no encontrado.';
    echo json_encode($response);
    exit;
}

$producto = $result->fetch_assoc();
$cantidadActual = (int)$producto['stock'];
$nombreP = $producto['nombre'];
$cantidadTotal = $cantidadActual + $cantidadNueva;
$stmt->close();

// Actualizar producto
$stmt = $conexion->prepare("UPDATE productos SET precio_compra=?, cantidad_unidades=?, porcentaje=?, stock=?, proveedor=? WHERE id=?");
$stmt->bind_param("diidss", $precio, $cantidadPP, $porcentaje, $cantidadTotal, $proveedor, $id);
$stmt->execute();
$stmt->close();

// Obtener y actualizar límite de deshacer
$deshacerCompra = 0;
$res = $conexion->query("SELECT deshacerCompra FROM empresa WHERE id = 1");
if ($res && $res->num_rows > 0) {
    $fila = $res->fetch_assoc();
    $deshacerCompra = min((int)$fila['deshacerCompra'] + 1, 3);
}
$conexion->query("UPDATE empresa SET deshacerCompra = $deshacerCompra WHERE id = 1");

// Registrar en historial de compras
$fechaActual = date('d-m-Y h:i a');
$semana = date('Y-W');
$mes = date('Y-m');
$dia = date('Y-m-d');
$usuario = $_SESSION['nombre'] ?? 'desconocido';

$stmt = $conexion->prepare("INSERT INTO compras (status, cod, producto, precio, cantidad, CporPrecio, Porcentaje, fecha, user, semana, dia, mes) 
    VALUES (2, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssss", $id, $nombreP, $precio, $cantidadNueva, $cantidadPP, $porcentaje, $fechaActual, $usuario, $semana, $dia, $mes);
$stmt->execute();
$stmt->close();

$response['success'] = true;
$response['message'] = 'Producto actualizado y registrado exitosamente.';
echo json_encode($response);
