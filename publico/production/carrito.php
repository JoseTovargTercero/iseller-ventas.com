<?php
require_once("../../configurar/configuracion.php");

include 'La-carta.php';
$cart = new Cart;


//get cart items from session
$cartItems = $cart->contents();

$data = array(
  'cantidad' => 0,
  'carrito' => [],
  'total' => []
);

foreach ($cartItems as $item) {

  $itemPeso = str_replace('.', '', $item["pricePeso"]);
  $itemPeso = str_replace(',', '', $item["pricePeso"]);
  $itemBsss = str_replace('.', '', $item["priceBolivar"]);
  $itemBsss = str_replace(',', '', $item["priceBolivar"]);

  $todoPeso += $itemPeso * $item["qty"];
  $todoBolivar += $itemBsss * $item["qty"];


  array_push(
    $data['carrito'],
    [
      "id" => $item["rowid"],
      "nombre" => $item["name"],
      "cantidad" => $item["qty"],
      "subtotalPeso" => number_format($item["pricePeso"] * $item["qty"],  0, '.', ''),
      "subtotalBolivar" => number_format($item["priceBolivar"] * $item["qty"],  2, '.', ''),
      "subtotalDolar" => number_format($item["subtotal"],  2, '.', ''),
    ]
  );
}

$data['total'] = [
  "bolivares" => number_format($todoBolivar,  2, '.', ''),
  "dolares" => number_format($cart->total(),  2, '.', ''),
  "pesos" => $todoPeso
];

$data['cantidad'] = count($data['carrito']);

echo json_encode($data, JSON_PRETTY_PRINT);
