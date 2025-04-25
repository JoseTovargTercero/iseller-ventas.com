
 <?php
  require_once("../../configurar/configuracion.php");
  require("../../configurar/_tasas_cambio.php");
  $bss_id = $_SESSION["bss_id"];
  $cambio = new TasasCambio($conexion);


  $respuesta = $cambio->obtenerCambio($bss_id);
  $tasas = json_decode($respuesta, true); // true = array asociativo

  $tasaMostradas = $cambio->tasasMostradas($bss_id);
  $tasaMostradas = json_decode($tasaMostradas, true);
  $data_monedas = $tasaMostradas['data'];


  $pesoDolar = $tasas['data']['pesoDolar'];
  $peso_bolivar = $tasas['data']['peso_bolivar'];
  $bolivar_peso = $tasas['data']['bolivar_peso'];
  $DolarBolivar = $tasas['data']['DolarBolivar'];
  $bcv = $tasas['data']['bcv'];
  $tipo_tasa_bs = $tasas['data']['tipo_tasa_bs'];


  // reemplazar todos los caracteres que no sean dígitos
  function removeNonNumeric($string)
  {
    return preg_replace('/\D/', '', $string);
  }


  if (!isset($_POST['producto'])) {
    echo json_encode(['status' => 'error', 'mensaje' => 'Algo falló en el proceso']);
    exit;
  }

  $producto = $conexion->real_escape_string($_POST['producto']);
  $modo     = $conexion->real_escape_string($_POST['modo']);

  $data = [];

  if ($modo == 1) {
    // Búsqueda por nombre
    $query = $conexion->query("SELECT * FROM productos WHERE nombre LIKE '%$producto%' AND activo = 0 ORDER BY id LIMIT 35");
  } else {
    // Búsqueda por código de barras
    $producto = removeNonNumeric($producto);
    $query = $conexion->query("SELECT * FROM productos WHERE codigo_barras LIKE '%$producto%' AND activo = 0 LIMIT 1");
  }

  if ($query && $query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {
      $cantidadUnidad      = (float) $row["cantidad_unidades"];
      $origen              = $row["origen"];
      $precioCompra        = (float) $row["precio_compra"];
      $porcentaje          = (float) $row["porcentaje"];

      // Precio en dólares por unidad
      $precioDolarCompra   = $precioCompra / $cantidadUnidad;

      // Precio de venta en dólares
      $precioDolarVenta    = $precioDolarCompra + ($precioDolarCompra * $porcentaje / 100);
      $precioDolarVisible  = number_format($precioDolarVenta, 2, '.', ',');

      // Precio en pesos
      $precioPesoVenta     = $precioDolarVenta * $pesoDolar;

      // Precio en bolívares según el origen
      if ($origen === 'c') {
        $precioBsVenta = ($precioPesoVenta / $peso_bolivar) / 1000;
      } else {
        $precioBsVenta = $precioDolarVenta * $DolarBolivar;
      }

      // Precio convertido de Bs a Pesos
      $precioBolivarPeso = ($precioBsVenta * $bolivar_peso) * 1000;

      // Precio convertido de Bs a dolares
      $precio_bolivar_dolar = $precioBsVenta / $bcv;


      if (isset($data_monedas['precio_peso_visible'])) {
        $valorPesos = $precioPesoVenta;
      } else {
        $valorPesos = $precioBolivarPeso;
      }

      if (isset($data_monedas['precio_dolar_visible'])) {
        $precioDolar = $precioDolarVisible;
      } else {
        $precioDolar = number_format($precio_bolivar_dolar, 2, '.', ',');
      }

      //{"precio_dolar_visible":"1","precio_peso_visible":"1","precio_bs_visible":"1"}

      // Formatear y limpiar nombre
      $nombre = strtoupper($row["nombre"]);
      $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);

      $data[] = [
        'id'                    => $row['id'],
        'stock'                 => $row['stock'],
        'nombre'                => $nombre,
        'precio_dolar_visible' => $precioDolar,
        'precio_peso_visible'  => $valorPesos,
        'precio_bs_visible'    => $precioBsVenta,
        'codigo'               => $row['codigo'],
      ];
    }

    echo json_encode(['status' => 'ok', 'data' => $data]);
  } else {
    echo json_encode(['status' => 'error', 'mensaje' => 'No se encontró el producto ' . $_POST['producto']]);
    exit;
  }


  ?>