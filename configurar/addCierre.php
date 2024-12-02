<?php

require_once('configuracion.php');




$query = "";

$stmt = mysqli_prepare($conexion, "SELECT * FROM cambio WHERE id='1'");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $pesoDolar = $row['pesoDolar'];
    $dolarBolivar = $row['DolarBolivar'];
}
}
$stmt->close();









$punto  = strip_tags( addslashes( $_POST['punto'] ) );
$biopago = strip_tags( addslashes( $_POST['biopago'] ) );
$efectivo = strip_tags( addslashes( $_POST['efectivo'] ) );
$dolares = strip_tags( addslashes( $_POST['dolares'] ) );
$pesos = strip_tags( addslashes( $_POST['pesos'] ) );
$semana = strip_tags( addslashes( $_POST['semana'] ) );
$dia = strip_tags( addslashes( $_POST['dia'] ) );
$ano = explode('-', $semana)[0];
$mes = '';




$stmt = mysqli_prepare($conexion, "SELECT * FROM cierres WHERE semana='$semana' AND dia='$dia'");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo 'Ya hay un registro previo para esta fecha';
    
}else {
  

    $insertar = "INSERT INTO cierres (ano, mes, semana, dia, dolares, pesos, efectivo, bioPago, punto, pesoDolar, bolivarDolar) VALUES 
                                  ('$ano','$mes','$semana','$dia','$dolares','$pesos', '$efectivo', '$biopago', '$punto', '$pesoDolar', '$dolarBolivar')";
    
    $resultado2 = mysqli_query( $conexion, $insertar );
    if ( !$resultado2 ) {
       echo 'Error interno'; 
      } else {
        echo 'ok'; 
    }
    
    }

$stmt->close();










?>

