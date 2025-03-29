
 <?php
  /////// CONEXIÓN A LA BASE DE DATOS /////////
  require_once("../../configurar/configuracion.php");

  $query22 = 'SELECT * FROM empresa';
  $buscarAlumnos22 = $conexion->query($query22);
  if ($buscarAlumnos22->num_rows > 0) {
    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
      $stockCritico = $filaAlumnos22['stockCritico'];
    }
  }


  $query7 = $conexion->query("SELECT * FROM cambio WHERE id='1'");
  if ($query7->num_rows > 0) {
    while ($row7 = $query7->fetch_assoc()) {
      $pesoDolar = $row7['pesoDolar'];
      $DolarBolivar = $row7['DolarBolivar'];
      $bolivarPesoTrans = $row7['bolivarPesoTrans'];
    }
  }
  function removeNonNumeric($string)
  {
    // Usar preg_replace para reemplazar todos los caracteres que no sean dígitos
    return preg_replace('/\D/', '', $string);
  }



  if (isset($_POST['producto'])) {

    $producto = $conexion->real_escape_string($_POST['producto']);
    $modo = $conexion->real_escape_string($_POST['modo']);


    if ($modo == 1) {
      $query = $conexion->query("SELECT * FROM productos  WHERE nombre LIKE '%$producto%' AND activo= 0 ORDER BY id LIMIT 35");
    } else {

      $producto = removeNonNumeric($producto);


      $query = $conexion->query("SELECT * FROM productos  WHERE codigo_barras LIKE '%$producto%' AND activo= 0 LIMIT 1  ");
    }


    $data = [];



    if ($query->num_rows > 0) {
      while ($row = $query->fetch_assoc()) {

        $cantidadUnidad = $row["cantidad_unidades"];
        $origen = $row["origen"];

        $precioDolarCompra = $row["precio_compra"] / $cantidadUnidad;
        $porcentaje = $row["porcentaje"];
        $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;

        $precioDolarVenta = number_format($precioDolarVenta, '2', '.', ',');

        $precioPesoVenta = $precioDolarVenta * $pesoDolar;
        if ($origen == 'c') {
          $precioBsVenta = ($precioPesoVenta / $bolivarPesoTrans) / 1000;
        } else {
          $precioBsVenta = $precioDolarVenta * $DolarBolivar;
        }


        //        $precioPesoVenta  = number_format(, '0', ',', '.');
        //        $precioBsVenta = number_format(, '2', ',', '.');

        $nombre = strtoupper($row["nombre"]);
        // quitar caracteres especiales del nombre
        $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);

        array_push($data, [
          'id' => $row['id'],
          'stock' => $row['stock'],
          'nombre' =>  "$nombre",
          'precio_dolar_visible' => $precioDolarVenta,
          'precio_peso_visible' => $precioPesoVenta,
          'precio_bs_visible' => $precioBsVenta,
          'codigo' => $row['codigo'],
        ]);
      }
      echo json_encode(['status' => 'ok', 'data' => $data]);
    } else {
      echo json_encode(['status' => 'error', 'mensaje' => 'No se encontró el producto ' . $_POST['producto']]);
      exit;
    }
  } else {
    echo json_encode(['status' => 'error', 'mensaje' => 'Algo fallo en el proceso']);
    exit;
  }


  ?>