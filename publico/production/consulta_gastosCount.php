<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");

$semana = $_POST['semana'];
$mes = $_POST['mes'];
$gananciasSemana = $_POST['gananciasSemana'];
$gananciasMes = $_POST['gananciasMes'];





$querysas="SELECT * FROM gastos WHERE mes='$mes'";
$buscarAlumnossas=$conexion->query($querysas);
if ($buscarAlumnossas->num_rows > 0){
    while($filaAlumnosasd= $buscarAlumnossas->fetch_assoc()){     
    $gastosMes += $filaAlumnosasd['importe'];  
    }
}else{
    $gastosMes = '0';
}


$query="SELECT * FROM gastos WHERE semana='$semana'";
$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0){
    while($filaAlumnos= $buscarAlumnos->fetch_assoc()){     
    $gastosSemana += $filaAlumnos['importe'];  
    }
}else{
    $gastosSemana = '0';
}


$gananciaNetaSemana = $gananciasSemana - $gastosSemana;
$gananciaNetaMes = $gananciasMes - $gastosMes;


 

echo $gastosSemana.'*'.$gastosMes.'*'.$gananciaNetaSemana.'*'.$gananciaNetaMes;



?>


                                                          
                                                          
                                                      
