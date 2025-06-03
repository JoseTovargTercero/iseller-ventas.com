<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_validador_campos.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$campos_requeridos = ['producto', 'precio', 'porcentaje', 'comprado', 'cantidad', 'proveedor'];
if ($_SESSION["nivel"] == 1) {
    array_push($campos_requeridos, 'sucursal');
}
$validador = new ValidadorCampos($campos_requeridos, 'POST');
$validador->validar();


$id            = trim($_POST['producto']);
$precio        = floatval($_POST['precio']);
$porcentaje    = floatval($_POST['porcentaje']);
$cantidadNueva = intval($_POST['comprado']);
$cantidadPP    = intval($_POST['cantidad']);
$proveedor     = trim($_POST['proveedor']);
$sucursal      = $_SESSION["nivel"] == 1 ? trim($_POST['sucursal']) : $_SESSION["sucursal"];
$bss_id        = $_SESSION["bss_id"];

// Obtener producto actual
$stmt = $conexion->prepare("SELECT S.stock, P.nombre FROM stock AS S 
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

$producto = $result->fetch_assoc();
$cantidadActual = (int)$producto['stock'];
$nombreP = $producto['nombre'];
$cantidadTotal = $cantidadActual + $cantidadNueva;
$stmt->close();

// Actualizar producto
$stmt = $conexion->prepare("UPDATE productos SET precio_compra=?, cantidad_unidades=?, proveedor=? WHERE id=?");
$stmt->bind_param("diss", $precio, $cantidadPP, $proveedor, $id);
$stmt->execute();
$stmt->close();

// Actualizar el stock
$stmt = $conexion->prepare("UPDATE stock SET stock=?, porcentaje=? WHERE id_producto=? AND id_sucursal=? AND bss_id=?");
$stmt->bind_param("idiii", $cantidadTotal, $porcentaje, $id, $sucursal, $bss_id);
$stmt->execute();
$stmt->close();



// Registrar en historial de compras
$fechaActual = date('d-m-Y h:i a');
$semana = date('Y-W');
$mes = date('Y-m');
$dia = date('Y-m-d');
$usuario = $_SESSION['nombre'] ?? 'desconocido';

$stmt = $conexion->prepare("INSERT INTO compras (status, cod, producto, precio, cantidad, CporPrecio, Porcentaje, fecha, user, semana, dia, mes, id_sucursal, bss_id) 
    VALUES (2, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssssss", $id, $nombreP, $precio, $cantidadNueva, $cantidadPP, $porcentaje, $fechaActual, $usuario, $semana, $dia, $mes, $sucursal, $bss_id);
$stmt->execute();
$stmt->close();

$response['success'] = true;
$response['message'] = 'Producto actualizado y registrado exitosamente.';
echo json_encode($response);
