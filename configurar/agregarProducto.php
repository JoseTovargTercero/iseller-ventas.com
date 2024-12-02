<?php
require_once('configuracion.php');


$nombre  = strip_tags(addslashes($_POST['nombre']));
$precio = strip_tags(addslashes($_POST['precio']));
$cantidad = strip_tags(addslashes($_POST['cantidad']));
$porcentaje = strip_tags(addslashes($_POST['porcentaje']));
$codigo = strip_tags(addslashes($_POST['codigo']));
//$favorito = strip_tags( addslashes( $_POST['favorito'] ) );
$categoria = strip_tags(addslashes($_POST['categoria']));
$moneda = strip_tags(addslashes($_POST['moneda']));
$precioMonedaOrigen = strip_tags(addslashes($_POST['precioMonedaOrigen']));
$c_barras = strip_tags(addslashes($_POST['c_barras']));


$stock = strip_tags(addslashes($_POST['stock']));
$foto = "NO";

$insertar = "INSERT INTO productos (nombre, precio_compra, cantidad_unidades, porcentaje, codigo, stock, codigo_barras) VALUES 
        ('$nombre','$precio','$cantidad','$porcentaje','$codigo','$stock', '$c_barras')";



$resultado2 = mysqli_query($conexion, $insertar);
if (!$resultado2) {

  echo '<br>';
} else {

  define('PAGINA_RETORNO', '../publico/production/nuevoProducto.php?agregado=correcto&codigo=' . $codigo . '');
  header('Location: ' . PAGINA_RETORNO);
}
