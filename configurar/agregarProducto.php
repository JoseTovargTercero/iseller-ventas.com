<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

// Sanitizar y preparar los datos
$nombre = strtoupper($_POST['nombre']) ?? '';
$precio = $_POST['precio'] ?? 0;
$cantidad = $_POST['cantidad'] ?? 0;
$porcentaje = $_POST['porcentaje'] ?? 0;
$codigo = $_POST['codigo'] ?? 'ND';
$categoria = $_POST['categoria'] ?? '';
$moneda = $_POST['moneda'] ?? '';
$precioMonedaOrigen = $_POST['precioMonedaOrigen'] ?? 0;
$c_barras = $_POST['c_barras'] ?? '';
$origenProducto = $_POST['origenProducto'] ?? '';
$proveedor = $_POST['proveedor'] ?? '';
$foto = "NO";

// Validación básica
if (empty($nombre)) {
  echo json_encode(['tipo' => 'error', 'mensaje' => 'Faltan datos requeridos.']);
  exit;
}

$conexion->begin_transaction();

try {
  // Insertar en productos
  $query = "INSERT INTO productos (
      nombre, precio_compra, cantidad_unidades, porcentaje, codigo_barras, origen, proveedor, bss_id
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $conexion->prepare($query);
  if (!$stmt) {
    throw new Exception('Error al preparar la consulta de productos.');
  }

  $stmt->bind_param("sdddsssi", $nombre, $precio, $cantidad, $porcentaje, $c_barras, $origenProducto, $proveedor, $bss_id);
  if (!$stmt->execute()) {
    throw new Exception('No se pudo registrar el producto.');
  }

  $id_registro = $conexion->insert_id;
  $stmt->close();

  // Decodificar el arreglo enviado desde JS
  $sucursales_stock = json_decode($_POST['sucursales_stock'], true);
  if (!is_array($sucursales_stock)) {
    throw new Exception('Debe haber alguna sucursal seleccionada.');
  }

  $stmt_o = $conexion->prepare("INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id) VALUES (?, ?, ?, ?, ?)");
  if (!$stmt_o) {
    throw new Exception('Error al preparar la consulta de stock.');
  }

  foreach ($sucursales_stock as $item) {
    $idSucursal = $item[0];
    $stock = $item[1];

    $stmt_o->bind_param("ddiii", $id_registro, $porcentaje, $stock, $idSucursal, $bss_id);
    if (!$stmt_o->execute()) {
      throw new Exception('Error al registrar el stock en la sucursal con ID: ' . $idSucursal);
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
