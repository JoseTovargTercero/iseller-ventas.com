<?php
$url = 'https://ve.dolarapi.com/v1/dolares/oficial';
$method = 'GET';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>