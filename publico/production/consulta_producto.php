
 <?php
  require_once("../../configurar/configuracion.php");
  require_once('../../configurar/session.php');
  require("../../configurar/_tasas_cambio.php");
  require("../../configurar/_calculadrora_precios.php");
  $calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);


  // Función para eliminar caracteres no numéricos
  function removeNonNumeric($string)
  {
    return preg_replace('/\D/', '', $string);
  }

  // Validar entrada
  if (!isset($_POST['producto'])) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Algo falló en el proceso']);
    exit;
  }

  $producto = trim($_POST['producto']);
  $modo = isset($_POST['modo']) ? (int)$_POST['modo'] : 1;

  // Determinar sucursal del usuario
  if ($_SESSION["nivel"] == 1 && empty($_POST["sucursal"])) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Sucursal no especificada.']);
    exit;
  }

  $sucursal = ($_SESSION["nivel"] == 1) ? $_POST["sucursal"] : $_SESSION["id_sucursal"];



  $data = [];

  if ($modo === 1) {
    // Búsqueda por nombre
    $producto = "%{$producto}%";
    $query = "
        SELECT p.id, p.nombre, p.codigo, s.stock, s.porcentaje, p.cantidad_unidades, p.origen, p.precio_compra 
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        WHERE p.nombre LIKE ?
          AND p.activo = 0
          AND s.id_sucursal = ?
        ORDER BY p.id
        LIMIT 35";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("si", $producto, $sucursal);
  } else {
    // Búsqueda por código de barras
    $producto = removeNonNumeric($producto);
    $query = "
        SELECT p.id, p.nombre, p.codigo, s.stock, s.porcentaje, p.cantidad_unidades, p.origen, p.precio_compra 
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        WHERE p.codigo_barras = ?
          AND p.activo = 0
          AND s.id_sucursal = ?
        LIMIT 1";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("si", $producto, $sucursal);
  }
  // Ejecutar consulta
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $precios = $calculadora->calcularPrecios($row);

      // Formatear nombre
      $nombre = strtoupper($row["nombre"]);
      $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);

      $data[] = [
        'id'                    => $row['id'],
        'stock'                 => $row['stock'],
        'porcentaje'            => $row['porcentaje'],
        'nombre'                => $nombre,
        'precio_dolar_visible' => $precios['precio_venta_dolar'],
        'precio_peso_visible'  => $precios['precio_venta_peso'],
        'precio_bs_visible'    => $precios['precio_venta_bs'],
        'codigo'               => $row['codigo'],
      ];
    }

    echo json_encode(['status' => 'ok', 'data' => $data]);
  } else {
    echo json_encode(['status' => 'error', 'mensaje' => 'No se encontró el producto ' . htmlspecialchars($_POST['producto'])]);
  }


  ?>