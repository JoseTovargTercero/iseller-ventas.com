<?php
require_once( 'configuracion.php' );

    $order_id = $_GET['id'];

    $stmt = $conexion->prepare( "DELETE FROM orden WHERE id='$order_id'" );
    $stmt->execute();
$stmt->close();


    $query2 = "SELECT * FROM orden_articulos WHERE order_id='$order_id'";
    $buscarAlumnos2 = $conexion->query( $query2 );
    if ( $buscarAlumnos2->num_rows > 0 ) {
        while( $filaAlumnos2 = $buscarAlumnos2->fetch_assoc() ){
            $product_id = $filaAlumnos2['product_id'];
            $quantity = $filaAlumnos2['quantity'];


            $query = "SELECT * FROM productos WHERE id='$product_id'";
            $buscarAlumnos = $conexion->query( $query );
            if ( $buscarAlumnos->num_rows > 0 ) {
                while( $filaAlumnos = $buscarAlumnos->fetch_assoc() ){
                    $restoreProducto = $filaAlumnos['stock'] + $quantity;

                    $stmt = $conexion->prepare( "UPDATE productos SET stock='$restoreProducto' WHERE id='$product_id'" );
                    $stmt->execute();
$stmt->close();

                }
            } 
            
        }
    } 
    
    $stmt = $conexion->prepare( "DELETE FROM orden_articulos WHERE order_id='$order_id'" );
    $stmt->execute();
$stmt->close();




   define('PAGINA_INICIO','../publico/production/ventas.php');
   header('Location: '.PAGINA_INICIO);


?>