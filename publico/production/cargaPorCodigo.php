
 <?php
require_once("../../configurar/configuracion.php");

$endCode = $_SESSION['qrcode'];
$idUsuario = $_SESSION['id'];


$query25 = "SELECT * FROM productos_scan WHERE user='$idUsuario' ORDER BY id LIMIT 1";
$buscarAlumnos25 = $conexion->query($query25);
if ($buscarAlumnos25->num_rows > 0) {
  while ($filaAlumnos25 = $buscarAlumnos25->fetch_assoc()) {
  
    $idScan2 = $filaAlumnos25['id'];

    $idScan = $filaAlumnos25['codigo'];

    $query2 = $conexion->query("DELETE FROM productos_scan WHERE id='$idScan2'");




  $query = $conexion->query("SELECT * FROM productos WHERE codigoBarras='$idScan' AND activo= 0 ORDER BY id LIMIT 1");
  if ($query->num_rows > 0) {
    while ($row = $query->fetch_assoc()) {

      $cantidadUnidad = $row["cantidad_unidades"];
      $precioDolarCompra = $row["precio_compra"] / $cantidadUnidad;
      $porcentaje = $row["porcentaje"];
      $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;

      $precioDolarVenta2 = $precioDolarVenta;
      $precioDolarVenta = round($precioDolarVenta, 2, PHP_ROUND_HALF_DOWN);

      $idTasaPeso = $row["TasaPeso"];
      $idTasaDolar = $row["tasaDolar"];

      $query7 = $conexion->query("SELECT * FROM tasas_dolar WHERE id='$idTasaDolar'");
      if ($query7->num_rows > 0) {
        while ($row7 = $query7->fetch_assoc()) {
          $dolarBolivar = $row7['recepcion'];
        }
      }

      $query8 = $conexion->query("SELECT * FROM tasas_pesos WHERE id_peso='$idTasaPeso'");
      if ($query8->num_rows > 0) {
        while ($row8 = $query8->fetch_assoc()) {
          $pesoBolivarPublicacion = $row8['publicacion_peso'];
        }
      }
      ///////////////////////////////////////
      ///////////////////////////////////////

      $precioBsVenta = $precioDolarVenta2 * $dolarBolivar;
      $precioBsVentaH = $precioDolarVenta2 * $dolarBolivar;
      $millon = $precioBsVentaH * 1000000;

      $precioBsVenta = round($precioBsVenta, 2, PHP_ROUND_HALF_DOWN);
      $precioBsVenta2 = number_format($precioBsVenta, '2', ',', '.');

      $precioPesoVenta = $millon * $pesoBolivarPublicacion;
      ////////////////////////////////////////
      ////////////////////////////////////////

      $precioPesoVenta = round($precioPesoVenta, 2, PHP_ROUND_HALF_DOWN);
      $precioPesoVenta2 = number_format($precioPesoVenta, '0', ',', '.');

      echo 'cant=1&action=addToCart&id='.$row['id'].'&codigo=' . $row['codigo'] . '&dolarventa=' . $precioDolarVenta2 . '&pesoventa=' . round($precioPesoVenta, 2, PHP_ROUND_HALF_DOWN) . '&bolivarventa=' . round($precioBsVenta, 2, PHP_ROUND_HALF_DOWN);

    }

  }else {
    echo 'NADA';
  }
    



    }

  }else {
      echo 'NADA';
  }

 

  ?>