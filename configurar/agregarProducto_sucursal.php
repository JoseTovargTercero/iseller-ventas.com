<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

// Sanitizar y preparar los datos
$producto = $_POST['producto'];
$stock = $_POST['stock'] ?? 0;
$sucursal = ($_SESSION["nivel"] == 2 ? $_SESSION["sucursal"] : $_POST['sucursal']);
$bss_id = $_SESSION["bss_id"];

// Validación básica
if (empty($producto) || empty($sucursal)) {
  echo json_encode(['tipo' => 'error', 'mensaje' => 'Faltan datos requeridos.']);
  exit;
}

$stmt = mysqli_prepare($conexion, "SELECT * FROM `productos` WHERE id = ?");
$stmt->bind_param('s', $producto);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $porcentaje = $row['porcentaje'];
  }
} else {
  echo json_encode(['tipo' => 'error', 'mensaje' => 'El producto no existe']);
  exit;
}
$stmt->close();



$conexion->begin_transaction();

try {
  // Insertar en productos
  $query = "INSERT INTO stock (
  id_producto, porcentaje, stock, id_sucursal, bss_id) VALUES (?, ?, ?, ?, ?)";

  $stmt = $conexion->prepare($query);
  if (!$stmt) {
    throw new Exception('Error al preparar la consulta de productos.');
  }

  $stmt->bind_param("iddii", $producto, $porcentaje, $stock, $sucursal, $bss_id);
  if (!$stmt->execute()) {
    throw new Exception('No se pudo registrar el producto.');
  }

  $stmt->close();

  $conexion->commit();
  echo json_encode(['tipo' => 'success', 'mensaje' => 'Producto agregado correctamente.']);
} catch (Exception $e) {
  $conexion->rollback();
  echo json_encode(['tipo' => 'error', 'mensaje' => $e->getMessage()]);
}

$conexion->close();
