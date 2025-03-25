<?php

$stmt = mysqli_prepare($conexion, "SELECT * FROM `go_planes` WHERE ano = ?");
$stmt->bind_param('s', $var);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
  }
}
$stmt->close();


/* */
/* */
/* */


$stmt2 = $conexion->prepare("UPDATE `sheet_r` SET `estatus`='CERRADO' WHERE id=?");
$stmt2->bind_param("s", $caso);
$stmt2->execute();
$stmt2->close();




/* */
/* */
/* */
/* */



$stmt = $conexion->prepare("DELETE FROM `sheet_d2` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();


if ($stmt) {
  echo '1';
} else {
  echo '0';
}


/* */
/* */
/* */
/* */
$stmt_o = $conexion->prepare("INSERT INTO go_tareas (id_operacion, id_plan, tarea, descripcion, fecha, trimestre, ano, ubicacion, cords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt_o->bind_param("sssssssss", $i, $id_plan, $res_car_nombre, $res_car_descripcion, $fecha, $trimestre, $anio, $ubicacion, $map);
$stmt_o->execute();

if ($stmt_o) {
  $id_r = $conexion->insert_id;
} else {
  echo "error";
}
$stmt_o->close();

header("Location: ../../public/index.php");





function contar($condicion)
{
  global $conexion;

  //$condicion = "SELECT count(*) FROM $table WHERE $condicion";
  $stmt = $conexion->prepare("SELECT count(*) FROM go_planes WHERE tipo='2' AND cerrado='1' AND ano='$ano' AND trimestre='1'");
  $stmt->execute();
  $row = $stmt->get_result()->fetch_row();
  $galTotal = $row[0];

  return $galTotal;
}


contar("SELECT count(*) FROM go_planes WHERE tipo='2' AND cerrado='1' AND ano='$ano' AND trimestre='1'")
