<?php
require_once( 'configuracion.php' );
//$_GET['idDeshacer'] aqui tengo el numero de la factura y la fecha

if(isset($_GET['idDeshacer'])){
    $idCompra = $_GET['idDeshacer'];
    $fecha = $_GET['fecha'];
    
    
    
    $query2 = "SELECT * FROM compras WHERE factura='$idCompra' AND fecha='$fecha'";
    $buscarAlumnos2 = $conexion->query( $query2 );
    if ( $buscarAlumnos2->num_rows > 0 ) {
        while( $filaAlumnos2 = $buscarAlumnos2->fetch_assoc() ){

            $cod = $filaAlumnos2['cod'];
            $cantidadAgregada = $filaAlumnos2['cantidad'];
            $idRow = $filaAlumnos2['id'];



            $query = "SELECT * FROM productos WHERE codigo='$cod'";
            $buscarAlumnos = $conexion->query( $query );
            if ( $buscarAlumnos->num_rows > 0 ) {
                while( $filaAlumnos = $buscarAlumnos->fetch_assoc() ){
                    $cantidadActual = $filaAlumnos['stock'];
                }
            } 
                  $cantidadFinal = $cantidadActual - $cantidadAgregada ;
            $stmt = $conexion->prepare( "UPDATE productos SET stock='$cantidadFinal' WHERE codigo='$cod'" );
            $stmt->execute();
            $stmt->close();
            $stmt = $conexion->prepare( "DELETE FROM compras WHERE id='$idRow'" );
            $stmt->execute();
            $stmt->close();
            
        }
    }    
        





    
    $query22 = "SELECT * FROM empresa";
        $buscarAlumnos22 = $conexion->query( $query22 );
        if ( $buscarAlumnos22->num_rows > 0 ) {
            while( $filaAlumnos22 = $buscarAlumnos22->fetch_assoc() ){
                $deshacerCompra = $filaAlumnos22['deshacerCompra'];
            }
        }



    $deshacerCompra -= 1;
    if($deshacerCompra <= 0){
       $deshacerCompra = 0; 
    }
    $stmt = $conexion->prepare( "UPDATE empresa SET deshacerCompra='$deshacerCompra' WHERE id='1'" );
    $stmt->execute();
$stmt->close();
    
         
    define( 'PAGINA_RETORNO', '../publico/production/nuevaCompraFacturas.php?accion=mensajeDeshacer' );
    header( 'Location: '.PAGINA_RETORNO );
        }        

?>