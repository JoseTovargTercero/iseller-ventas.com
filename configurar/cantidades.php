<?php
require_once("configuracion.php");
require_once('session.php');
require_once 'la-carta.php';



$cart = new Cart;

// Solo actualizar si vienen los datos por POST
if (isset($_POST['id']) && isset($_POST['accion'])) {
  $cart->modificar_cantidad($_POST['accion'], $_POST['id']);
}


require_once 'carrito.php';
