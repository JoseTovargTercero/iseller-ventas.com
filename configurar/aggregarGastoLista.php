<?php
require_once('configuracion.php');

$semana = strip_tags( addslashes( $_POST['semana'] ) );
function retornarMes($fecha){
  $explodeFecha = explode('-', $fecha);


  $dias = ($explodeFecha[1] * 7) * 86400;
  
  if ($explodeFecha[1] == date('W')) {
      $diasSemana = (7 - date('N')) * 86400;
      $dias = $dias - $diasSemana;
  }

  $pr = strtotime($explodeFecha[0].'-01-01');
 $pr += $dias;

 return date('Y-m', $pr);
}
$mes =  retornarMes($semana);




$query="SELECT * FROM gastos WHERE semana='$semana' AND tipo='1'";
$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0){
  while($filaAlumnos= $buscarAlumnos->fetch_assoc()){     
    $id = $filaAlumnos['id'];

    $delete = $conexion->query("DELETE FROM gastos WHERE id='$id'"); 

  }
}


$query2="SELECT * FROM gastosfijos WHERE activo='0'";
$buscarAlumnos2=$conexion->query($query2);
if ($buscarAlumnos2->num_rows > 0){
  while($filaAlumnos2= $buscarAlumnos2->fetch_assoc()){     
   
    $nombreGasto = $filaAlumnos2['nombre'];
    $pagar = $filaAlumnos2['importe'];
     
    $insertar = "INSERT INTO gastos (nombre, importe, semana, mes, tipo) VALUES ('$nombreGasto','$pagar','$semana','$mes','1')";
    $resultado2 = mysqli_query( $conexion, $insertar );


  }
}


?>

