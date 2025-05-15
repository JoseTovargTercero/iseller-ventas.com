<?php

require_once('configuracion.php');
require_once('session.php');
$id  = strip_tags(addslashes($_POST['id']));
$nombreRol = strip_tags(addslashes($_POST['nombreRol']));
$importe = strip_tags(addslashes($_POST['importe']));
$cantidadRol = strip_tags(addslashes($_POST['cantidadRol']));

if ($id == '0') {
  $query = "INSERT INTO gastosEmpleados (nombre, importe, cantidad) VALUES ('$nombreRol','$importe','$cantidadRol')";
  $result = mysqli_query($conexion, $query);
} else {
  $stmt2 = $conexion->prepare("UPDATE gastosEmpleados SET nombre='$nombreRol', importe='$importe', cantidad='$cantidadRol' WHERE id='$id'");
  $stmt2->execute();
  $stmt2->close();
}

$query2 = "SELECT * FROM gastosEmpleados";
$buscarAlumnos2 = $conexion->query($query2);
if ($buscarAlumnos2->num_rows > 0) {
  while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
    $nombreGasto = 'Empleados';
    $pagar += $filaAlumnos2['importe'] * $filaAlumnos2['cantidad'];
    $emp += $filaAlumnos2['cantidad'];
  }
}

echo '  <span>' . $pagar . '$</span><br>
<span>' . $emp . ' empleados</span>';
