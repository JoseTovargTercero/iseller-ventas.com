<?php
require_once( 'configuracioneticion.php' );

if ( isset( $_POST['relacion'] ) ) {




    $id  = strip_tags( addslashes( $_POST['id'] ) );
    $relacion = strip_tags( addslashes( $_POST['relacion'] ) );
    $enviar = strip_tags( addslashes( $_POST['enviar'] ) );




    $query = "SELECT * FROM productos WHERE id='$relacion'";
    $buscarAlumnos = $conexion->query( $query );
    if ( $buscarAlumnos->num_rows > 0 ) {
        while( $filaAlumnos = $buscarAlumnos->fetch_assoc() )
        {
            $cantidadActual = $filaAlumnos['stock'];
        }
    }

$queryBuscar = "SELECT * FROM productos WHERE id='$id'";
$buscarPro = $conexion->query($queryBuscar);
    if ($buscarPro->num_rows > 0) {
        while ($filaPro = $buscarPro->fetch_assoc()) {
            $dist_producto_cant = $filaPro['stock'];
            $unidades_dist = $filaPro['unidades_dist'];
    }
}




if( $enviar > $dist_producto_cant){
    define( 'PAGINA_RETORNO', '../publico/production/dist_productos.php?accion=cantidadMala' );
    header( 'Location: '.PAGINA_RETORNO );
}else{



    $cantidadMAYOR = $dist_producto_cant - $enviar;
    $cantidadDETAL = $cantidadActual + ($enviar *  $unidades_dist);

   
    $stmt = $conexion->prepare( "UPDATE productos SET stock='$cantidadMAYOR' WHERE id='$id'" );
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conexion->prepare( "UPDATE productos SET stock='$cantidadDETAL' WHERE id='$relacion'" );
    $stmt->execute();
    $stmt->close();

    
        define( 'PAGINA_RETORNO', '../publico/production/dist_productos.php?accion=correcto' );
        header( 'Location: '.PAGINA_RETORNO );
    }





}

?>