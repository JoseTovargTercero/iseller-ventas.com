<?php
require_once('configuracion.php'); 


if ( isset( $_POST['producto'] ) ) {
    $codigo  = strip_tags( addslashes( $_POST['producto'] ) );
    $precio = strip_tags( addslashes( $_POST['precio'] ) );
    $Porcentaje = strip_tags( addslashes( $_POST['porcentaje'] ) );
    $cantidadNueva = strip_tags( addslashes( $_POST['comprado'] ) );
    $cantidadporprecio = strip_tags( addslashes( $_POST['cantidad'] ) );

    $query = "SELECT * FROM productos WHERE codigo='$codigo'";
    $buscarAlumnos = $conexion->query( $query );
    if ( $buscarAlumnos->num_rows > 0 ) {
        while( $filaAlumnos = $buscarAlumnos->fetch_assoc() )
        {

            $cantidadActual = $filaAlumnos['stock'];

            $nombreP = $filaAlumnos['nombre'];

        }
    }
    $cantidad = $cantidadNueva + $cantidadActual;


    $stmt_o = $conexion->prepare("UPDATE productos SET precio_compra='$precio', cantidad_unidades='$cantidadporprecio', porcentaje='$Porcentaje', stock='$cantidad' WHERE codigo='$codigo'");
    $stmt_o->execute();
    
    
    $query22 = "SELECT * FROM empresa";
$buscarAlumnos22 = $conexion->query( $query22 );
if ( $buscarAlumnos22->num_rows > 0 ) {
    while( $filaAlumnos22 = $buscarAlumnos22->fetch_assoc() )
    {
       
        $deshacerCompra = $filaAlumnos22['deshacerCompra'];
    }
}
    $deshacerCompra += 1;
            
        if($deshacerCompra >= 3){
       $deshacerCompra = 3; 
    }

    


        
    $stmt2 = $conexion->prepare("UPDATE empresa SET deshacerCompra='$deshacerCompra' WHERE id='1'");
    $stmt2->execute();
    $stmt2 -> close();




    $fdeCompra = date( 'd-m-Y h:i a' );
    $SdeCompra = date( 'Y-W' );
    $mdeCompra = date( 'Y-m' );
    $ddeCompra = date( 'Y-m-d' );
    $fdeUser = $_SESSION['nombre'];

    $insertar = "INSERT INTO compras (cod, producto, precio, cantidad, fecha, user, semana, dia, mes) VALUES ('$codigo','$nombreP','$precio','$cantidadNueva','$fdeCompra','$fdeUser','$SdeCompra','$ddeCompra','$mdeCompra')";

    $resultado2 = mysqli_query( $conexion, $insertar );
    if ( !$resultado2 ) {

        echo 'oops!';
    } else {
        define( 'PAGINA_RETORNO', '../publico/production/nuevaCompra.php?agregado=correcto' );
        header( 'Location: '.PAGINA_RETORNO );

    }
}
