<?php

require_once('configuracion.php');


$nombreGasto  = strip_tags( addslashes( $_POST['nombreGasto'] ) );
$tipo = strip_tags( addslashes( $_POST['tipo'] ) );
$pagar = strip_tags( addslashes( $_POST['pagar'] ) );
$fechaAplicar = strip_tags( addslashes( $_POST['fechaAplicar'] ) );
function diaSemana($fecha){
  return date('N', strtotime($fecha));
}
function Semana($fecha){
  return date('W', strtotime($fecha));
}

if ($fechaAplicar == 'NA') {
  $semana = date('Y-W'); 
  $mes = date('Y-m'); 
}else {
  $explodeFecha = explode('-', $fechaAplicar);
  $mes = $explodeFecha[0].'-'.$explodeFecha[1];
  $semana = $explodeFecha[0].'-'.Semana($fechaAplicar);
}

if ($tipo == '1') {
  $query = "INSERT INTO gastosfijos (nombre, importe, tipo) VALUES ('$nombreGasto','$pagar','$tipo')";
  $result = mysqli_query( $conexion, $query );
}
 
$insertar = "INSERT INTO gastos (nombre, importe, semana, mes, tipo) VALUES ('$nombreGasto','$pagar','$semana','$mes','$tipo')";
$resultado2 = mysqli_query( $conexion, $insertar );


?>

