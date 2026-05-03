<?php
$_SESSION['nivel'] = 1;
$_SESSION['sucursal'] = 1;
$_SESSION['id'] = 1;

$payload = json_encode([
    'action' => 'totales_por_usuario',
    'fechaSolic' => date('Y-m-d'),
    'sucursal' => 1
]);

$ch = curl_init('http://localhost/iseller-tiendas.com/configurar/listaVentas_back.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

$response = curl_exec($ch);
curl_close($ch);

echo "Response:\n";
echo $response;
