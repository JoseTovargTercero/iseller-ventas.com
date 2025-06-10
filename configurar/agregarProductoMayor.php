<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

// Sanitizar y preparar los datos
$producto = $_POST['producto'] ?? '';
$porcentaje = $_POST['porcentaje'] ?? 0;
$denominacion_paquete = $_POST['denominacion_paquete'] ?? '';

// Validación básica
if (empty($producto) || empty($porcentaje) || empty($denominacion_paquete)) {
  echo json_encode(['tipo' => 'error', 'mensaje' => 'Faltan datos requeridos.']);
  exit;
}

$conexion->begin_transaction();

try {

  $datos = [];
  $stmt = mysqli_prepare($conexion, "SELECT * FROM `productos` WHERE id = ? AND bss_id= ?");
  $stmt->bind_param('ii', $producto, $bss_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $datos = [
        'nombre' => strtoupper($denominacion_paquete) . $row['nombre'],
        'precio_compra' => $row['precio_compra'],
        'cantidad_unidades' => $row['cantidad_unidades'],
        'porcentaje' => $porcentaje,
        'codigo_barras' => $row['codigo_barras'],
        'origen' => $row['origen'],
        'proveedor' => $row['proveedor'],
      ];
    }
  }
  $stmt->close();

  if (empty($datos)) {
    echo json_encode(['tipo' => 'error', 'mensaje' => 'No se encontro el producto']);
    exit;
  }


  // Insertar en productos
  $query = "INSERT INTO productos (
      nombre, precio_compra, cantidad_unidades, porcentaje, codigo_barras, origen, proveedor, bss_id, mayor, id_producto_relacionado
  ) VALUES (?,?,?,?,?,?,?,?,'1', ?)";

  $stmt = $conexion->prepare($query);
  if (!$stmt) {
    throw new Exception('Error al preparar la consulta de productos.');
  }

  $stmt->bind_param("sdddsssii", $datos['nombre'], $datos['precio_compra'], $datos['cantidad_unidades'], $datos['porcentaje'], $datos['codigo_barras'], $datos['origen'], $datos['proveedor'], $bss_id, $producto);
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
  $mayor = 1;

  $stmt_o = $conexion->prepare("INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, id_stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
  if (!$stmt_o) {
    throw new Exception('Error al preparar la consulta de stock.');
  }


  $stmt_s = mysqli_prepare($conexion, "SELECT * FROM `stock` WHERE id_producto = ? AND id_sucursal = ?");


  //$producto y $idSucursal
  foreach ($sucursales_stock as $item) {
    $idSucursal = $item;
    $stock = 0;

    $stmt_s->bind_param('ii', $producto, $idSucursal);
    $stmt_s->execute();
    $result = $stmt_s->get_result();
    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $idStock = $row['id'];
      }
    }

    $stmt_o->bind_param("ddiiiii", $id_registro, $porcentaje, $stock, $idSucursal, $bss_id, $mayor, $idStock);
    if (!$stmt_o->execute()) {
      throw new Exception('Error al registrar el stock en la sucursal con ID: ' . $idSucursal);
    }
  }
  $stmt_o->close();
  $stmt_s->close();

  // Confirmar transacción
  $conexion->commit();
  echo json_encode(['tipo' => 'success', 'mensaje' => 'Producto agregado correctamente.']);
} catch (Exception $e) {
  $conexion->rollback();
  echo json_encode(['tipo' => 'error', 'mensaje' => $e->getMessage()]);
}

$conexion->close();
