
 <?php
require_once('configuracion.php'); 

$idProducto = $_GET['id'];
$idUsuario = $_SESSION['id'];

$query25 = "SELECT * FROM productos_scan WHERE user='$idUsuario' ORDER BY id LIMIT 1";
$buscarAlumnos25 = $conexion->query($query25);
if ($buscarAlumnos25->num_rows > 0) {
  while ($filaAlumnos25 = $buscarAlumnos25->fetch_assoc()) {
  
    $idScan2 = $filaAlumnos25['id'];

    $idScan = $filaAlumnos25['codigo'];

    $query2 = $conexion->query("DELETE FROM productos_scan WHERE id='$idScan2'");

    }

  }

  $stmt_o = $conexion->prepare("UPDATE productos SET codigoBarras='$idScan' WHERE id='$idProducto'");
  $stmt_o->execute();
  $stmt_o -> close();

  define( 'PAGINA_INICIO', '../publico/production/productos.php?accion=codigoBarras' );
  header( 'Location: '.PAGINA_INICIO );
  ?>