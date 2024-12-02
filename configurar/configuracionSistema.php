<?php
require_once('../public/conexion/configuracion.php');

$valorActualizar = $_POST['valorActualizar'];



switch($valorActualizar){
        
   case('estrpatrulla');
   $nuevoValor = $_POST['cantidadEstrPatrulla'];
   $stmt = $conexion->prepare("UPDATE inf_mcp SET estr_patrulla='$nuevoValor' WHERE pq='mp'");
   $stmt->execute();
$stmt->close();
   break;
             
   case('estrraas');
   $nuevoValor = $_POST['cantidadEstrRaas'];
   $stmt = $conexion->prepare("UPDATE inf_mcp SET estr_raas='$nuevoValor' WHERE pq='mp'");
   $stmt->execute();
$stmt->close();
   break;
                 
   case('estrUbch');
   $nuevoValor = $_POST['cantidadEstrUbch'];
   $stmt = $conexion->prepare("UPDATE inf_mcp SET estr_ubch='$nuevoValor' WHERE pq='mp'");
   $stmt->execute();
$stmt->close();
   break;
        
   case('calles');
   $nuevoValor = $_POST['cantidadCalles'];
   $stmt = $conexion->prepare("UPDATE inf_mcp SET calles='$nuevoValor' WHERE pq='mp'");
   $stmt->execute();
$stmt->close();
   break;
        
   case('comunidades');
   $nuevoValor = $_POST['cantidadComunidad'];
   $stmt = $conexion->prepare("UPDATE inf_mcp SET comunidades='$nuevoValor' WHERE pq='mp'");
   $stmt->execute();
$stmt->close();
   break;
        
           
   case('ubch');
   $nuevoValor = $_POST['cantidadUbch'];
   $stmt = $conexion->prepare("UPDATE inf_mcp SET ubch='$nuevoValor' WHERE pq='mp'");
   $stmt->execute();
$stmt->close();
   break;
        
        
}






    
define( 'PAGINA_RETORNO', '../public/configuracion.php?actualizado=correcto' );
header( 'Location: '.PAGINA_RETORNO );
