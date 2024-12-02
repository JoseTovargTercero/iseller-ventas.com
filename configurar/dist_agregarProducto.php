<?php
require_once('configuracion.php'); 


$nombre  = strip_tags( addslashes( $_POST['nombre'] ) );
$precio = strip_tags( addslashes( $_POST['precio'] ) );
$cantidad = strip_tags( addslashes( $_POST['cantidad'] ) );
$porcentaje = strip_tags( addslashes( $_POST['porcentaje'] ) );
$codigo = strip_tags( addslashes( $_POST['codigo'] ) );
$favorito = strip_tags( addslashes( $_POST['favorito'] ) );
$categoria = strip_tags( addslashes( $_POST['categoria'] ) );
$moneda = strip_tags( addslashes( $_POST['moneda'] ) );
$precioMonedaOrigen = strip_tags( addslashes( $_POST['precioMonedaOrigen'] ) );

$Tasas_dolares = strip_tags( addslashes( $_POST['Tasas_dolares'] ) );
$Tasas_pesos = strip_tags( addslashes( $_POST['Tasas_pesos'] ) );
$relacion = strip_tags( addslashes( $_POST['relacion'] ) );
$unidades = strip_tags( addslashes( $_POST['unidades'] ) );

if($favorito == "on"){
    $favorito = "1";
}else{
    $favorito = "0";
}
$stock = strip_tags( addslashes( $_POST['stock'] ) );
$foto = "NO";
 
$insertar = "INSERT INTO productos (nombre, precio_compra, cantidad_unidades, porcentaje, codigo, stock, foto, activo, favorito, categoria, monedaOrigen, MontoOrigen, tasaDolar, TasaPeso, distribuidor, relacion, unidades_dist) VALUES 
        ('$nombre','$precio','$cantidad','$porcentaje','$codigo','$stock','$foto','1','$favorito', '$categoria', '$moneda', '$precioMonedaOrigen', '$Tasas_dolares', '$Tasas_pesos', 'si', '$relacion', '$unidades')";



$resultado2 = mysqli_query( $conexion, $insertar );
if ( !$resultado2 ) {
    
    echo '<br>';
} else {
  $_SESSION['noticia'] = "correcto";

  define( 'PAGINA_RETORNO', '../publico/production/dist_nuevoProducto.php' );
    header( 'Location: '.PAGINA_RETORNO );

}
?>

