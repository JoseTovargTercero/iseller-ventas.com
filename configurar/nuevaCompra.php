<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_validador_campos.php');
require_once('_canal.php');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$campos_requeridos = ['producto', 'precio', 'porcentaje', 'comprado', 'cantidad', 'proveedor'];
if ($_SESSION["nivel"] == 1) {
    array_push($campos_requeridos, 'sucursal');
}
$validador = new ValidadorCampos($campos_requeridos, 'POST');
$validador->validar();


$id            = (int) trim($_POST['producto']);
$precio        = floatval($_POST['precio']);
$porcentaje    = floatval($_POST['porcentaje']);
$cantidadNueva = floatval($_POST['comprado']);
$cantidadPP    = intval($_POST['cantidad']);
$proveedor     = trim($_POST['proveedor']);
$sucursal      = $_SESSION["nivel"] == 1 ? (int) trim($_POST['sucursal']) : (int) $_SESSION["sucursal"];
$bss_id        = (int) $_SESSION["bss_id"];
$usuarioId     = (int) ($_SESSION['id'] ?? 0);

if ($cantidadNueva <= 0) {
  echo json_encode(['success' => false, 'message' => 'La cantidad comprada debe ser mayor que cero.']);
  exit;
}

// Obtener producto actual
$stmt = $conexion->prepare("SELECT S.stock, P.nombre, P.seguimiento_estricto, P.modo_control, P.unidad
FROM stock AS S
INNER JOIN productos AS P ON P.id = S.id_producto
WHERE S.id_producto=? AND S.id_sucursal=? AND S.bss_id=? LIMIT 1");
$stmt->bind_param("iii", $id, $sucursal, $bss_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Producto no encontrado.';
    echo json_encode($response);
    exit;
}

$producto = $result->fetch_assoc();
$nombreP = $producto['nombre'];
$stmt->close();

$estricto = canal_producto_estricto($conexion, $id, $bss_id);
if ($estricto && empty($_POST['conciliacion_confirmada'])) {
  try {
    $contexto = canal_contexto_estricto($conexion, $id, $sucursal, $bss_id);
  } catch (Throwable $e) {
    $contexto = null;
  }
  echo json_encode([
    'success' => false,
    'requiere_conciliacion' => true,
    'message' => 'Confirme la cantidad anterior antes de sumar la compra.',
    'contexto' => $contexto
  ]);
  exit;
}

$stockVisto = isset($_POST['stock_visto']) ? floatval($_POST['stock_visto']) : -1;
if ($estricto && $stockVisto < 0) {
  echo json_encode(['success' => false, 'message' => 'Falta confirmar el stock anterior.']);
  exit;
}

$conexion->begin_transaction();

try {
  if ($estricto) {
    $stmt = $conexion->prepare("SELECT stock FROM stock WHERE id_producto=? AND id_sucursal=? AND bss_id=? LIMIT 1 FOR UPDATE");
    $stmt->bind_param("iii", $id, $sucursal, $bss_id);
    $stmt->execute();
    $filaStock = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $stockActual = (float) ($filaStock['stock'] ?? 0);
    if (abs($stockActual - $stockVisto) > 0.0001) {
      $conexion->rollback();
      try {
        $contexto = canal_contexto_estricto($conexion, $id, $sucursal, $bss_id);
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

  // Suma la compra al stock y, si es una res CANAL, al lote abierto.
  $compra = canal_sumar_compra($conexion, $id, $sucursal, $bss_id, $cantidadNueva, $usuarioId);

  $stmt = $conexion->prepare("UPDATE stock SET porcentaje=? WHERE id_producto=? AND id_sucursal=? AND bss_id=?");
  $stmt->bind_param("diii", $porcentaje, $id, $sucursal, $bss_id);
  $stmt->execute();
  $stmt->close();

  // Actualizar producto
  $stmt = $conexion->prepare("UPDATE productos SET precio_compra=?, cantidad_unidades=?, proveedor=? WHERE id=?");
  $stmt->bind_param("diss", $precio, $cantidadPP, $proveedor, $id);
  $stmt->execute();
  $stmt->close();

  // Si el usuario confirmó, el producto al mayor asociado hereda solo el precio de compra
  if (!empty($_POST['actualizar_mayor'])) {
      $stmt = $conexion->prepare("UPDATE productos SET precio_compra=? WHERE id_producto_relacionado=? AND mayor=1 AND bss_id=?");
      if ($stmt) {
          $stmt->bind_param("dii", $precio, $id, $bss_id);
          $stmt->execute();
          $stmt->close();
      }
  }

  // Registrar en historial de compras
  $fechaActual = date('d-m-Y h:i a');
  $semana = date('Y-W');
  $mes = date('Y-m');
  $dia = date('Y-m-d');
  $usuario = $_SESSION['nombre'] ?? 'desconocido';
  $cantidadTexto = canal_numero($cantidadNueva);

  $stmt = $conexion->prepare("INSERT INTO compras (status, cod, producto, precio, cantidad, CporPrecio, Porcentaje, fecha, user, semana, dia, mes, id_sucursal, bss_id)
      VALUES (2, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssssssssssss", $id, $nombreP, $precio, $cantidadTexto, $cantidadPP, $porcentaje, $fechaActual, $usuario, $semana, $dia, $mes, $sucursal, $bss_id);
  $stmt->execute();
  $stmt->close();

  $conexion->commit();
  $response['success'] = true;
  $response['message'] = 'Producto actualizado. Stock: ' . canal_numero($compra['stock']);
  $response['stock'] = $compra['stock'];
  echo json_encode($response);
} catch (Throwable $e) {
  $conexion->rollback();
  $response['message'] = $e->getMessage();
  echo json_encode($response);
}
