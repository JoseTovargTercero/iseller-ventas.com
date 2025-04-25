<?php
include 'la-carta.php';
if ($_SESSION['nivel']) {
    $cart = new Cart;

    $cart->destroy();
    echo json_encode('ok', true);
}
