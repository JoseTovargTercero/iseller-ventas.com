<?php
require_once('configuracion.php');
header('Content-Type: application/json');

// Sanitizar y preparar los datos
$nombre = $_POST['nombre'] ?? '';
$precio = $_POST['precio'] ?? 0;
$cantidad = $_POST['cantidad'] ?? 0;
$porcentaje = $_POST['porcentaje'] ?? 0;
$codigo = $_POST['codigo'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$moneda = $_POST['moneda'] ?? '';
$precioMonedaOrigen = $_POST['precioMonedaOrigen'] ?? 0;
$c_barras = $_POST['c_barras'] ?? '';
$origenProducto = $_POST['origenProducto'] ?? '';
$proveedor = $_POST['proveedor'] ?? '';
$stock = $_POST['stock'] ?? 0;
$foto = "NO";

// Validación básica
if (empty($nombre) || empty($codigo)) {
  echo json_encode(['tipo' => 'error', 'mensaje' => 'Faltan datos requeridos.']);
  exit;
}

$conexion->begin_transaction();

try {
  // Insertar en productos
  $query = "INSERT INTO productos (
      nombre, precio_compra, cantidad_unidades, porcentaje, codigo, stock, codigo_barras, origen, proveedor
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $conexion->prepare($query);
  if (!$stmt) {
    throw new Exception('Error al preparar la consulta de productos.');
  }

  $stmt->bind_param("sdddsdsss", $nombre, $precio, $cantidad, $porcentaje, $codigo, $stock, $c_barras, $origenProducto, $proveedor);
  if (!$stmt->execute()) {
    throw new Exception('No se pudo registrar el producto.');
  }

  $id_registro = $conexion->insert_id;
  $stmt->close();

  // Insertar en stock
  $sucursales_marcadas = json_decode($_POST['sucursales_marcadas'], true);
  if (!is_array($sucursales_marcadas)) {
    throw new Exception('Debe haber alguna sucursal seleccionada.');
  }

  $stock_cero = 0;
  $stmt_o = $conexion->prepare("INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal) VALUES (?, ?, ?, ?)");
  if (!$stmt_o) {
    throw new Exception('Error al preparar la consulta de stock.');
  }

  foreach ($sucursales_marcadas as $idSucursal) {
    $stmt_o->bind_param("ddii", $id_registro, $porcentaje, $stock_cero, $idSucursal);
    if (!$stmt_o->execute()) {
      throw new Exception('Error al registrar el stock en una sucursal.');
    }
  }
  $stmt_o->close();

  // Confirmar transacción
  $conexion->commit();
  echo json_encode(['tipo' => 'success', 'mensaje' => 'Producto agregado correctamente.']);
} catch (Exception $e) {
  $conexion->rollback();
  echo json_encode(['tipo' => 'error', 'mensaje' => $e->getMessage()]);
}

$conexion->close();
