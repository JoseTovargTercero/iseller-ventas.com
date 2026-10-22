<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

// Sanitizar y preparar los datos
$nombre = strtoupper($_POST['nombre']) ?? '';
$precio = $_POST['precioMonedaOrigen'];
$cantidad = $_POST['cantidad'];
$porcentaje = $_POST['porcentaje'] ?? 0;
$codigo = $_POST['codigo'] ?? 'ND';
$categoria = $_POST['categoria'] ?? '';
$moneda = $_POST['moneda'] ?? '';
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

function validarInput($data, $campo)
{
  if ($data === null || $data === '') {
    return [
      'status' => false,
      'campo' => $campo
    ];
  }
  return [
    'status' => true
  ];
}

try {
  // Validar campos requeridos

  $status = true;
  $camposFaltantes = [];
  $validaciones = [
    validarInput($nombre, 'nombre'),
    validarInput($precio, 'precio'),
    validarInput($cantidad, 'cantidad'),
    validarInput($origenProducto, 'origenProducto'),
    validarInput($proveedor, 'proveedor')
  ];
  foreach ($validaciones as $validacion) {
    if (!$validacion['status']) {
      $status = false;
      $camposFaltantes[] = $validacion['campo'];
    }
  }
  if (!$status) {
    throw new Exception('Faltan datos: ' . implode(', ', $camposFaltantes));
  }

  // Insertar en productos
  $query = "INSERT INTO productos (
      nombre, precio_compra, cantidad_unidades, porcentaje, codigo_barras, origen, proveedor, bss_id, categoria
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $conexion->prepare($query);
  if (!$stmt) {
    throw new Exception('Error al preparar la consulta de productos.');
  }

  $stmt->bind_param("sdddsssis", $nombre, $precio, $cantidad, $porcentaje, $c_barras, $origenProducto, $proveedor, $bss_id, $categoria);
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

  // Subir foto del producto si se proporcionó
  if (isset($_FILES['foto_producto']) && $_FILES['foto_producto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto_producto'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allow = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $allow) && $file['size'] <= 5 * 1024 * 1024) {
      $dir = __DIR__ . '/../iseller_store/assets/img/tiendas/' . $bss_id . '/';
      if (!is_dir($dir)) mkdir($dir, 0775, true);

      $filename = $id_registro . '_' . time() . '.' . $ext;
      if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        $foto_url = "assets/img/tiendas/{$bss_id}/{$filename}";
        $stmt_foto = $conexion->prepare(
          "INSERT INTO negocio_productos (negocio_id, producto_id, foto_url, estado_publicacion)
           VALUES (?, ?, ?, 0)
           ON DUPLICATE KEY UPDATE foto_url = VALUES(foto_url)"
        );
        $stmt_foto->bind_param("iis", $bss_id, $id_registro, $foto_url);
        $stmt_foto->execute();
        $stmt_foto->close();
      }
    }
  }

  // Confirmar transacción
  $conexion->commit();
  echo json_encode(['tipo' => 'success', 'mensaje' => 'Producto agregado correctamente.']);
} catch (Exception $e) {
  $conexion->rollback();
  echo json_encode(['tipo' => 'error', 'mensaje' => $e->getMessage()]);
}

$conexion->close();
