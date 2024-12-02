<?php
$ar = fopen("https://api.exchangedyn.com/", "r") or die("No se pudo abrir el archivo");


$linea = '';



while (!feof($ar)) {
    $linea .= fgets($ar);
}
fclose($ar);


$linea = substr($linea, 0, -2);
$linea = substr($linea, 2, strlen($linea));
$linea = str_replace('"API endpoints and data structure subject to changes at anytime. ALL INFORMATION IS PROVIDED \"', '', $linea);
$linea = str_replace('"warning": AS IS', '', $linea);
$linea = str_replace('". EACH PARTY MAKES NO WARRANTIES, EXPRESS, IMPLIED OR OTHERWISE, REGARDING ITS ACCURACY, COMPLETENESS OR PERFORMANCE."', '', $linea);
$linea = str_replace('\\', '', $linea);
$linea = str_replace('},', '}', $linea);

$iter = explode(',', $linea);



echo $actualizacion = substr($iter[2], 54, -1);

 $quote = substr($iter[4], 17, -15);



/*
$ar = fopen("https://api.exchangedyn.com/", "r") or die("No se pudo abrir el archivo");
while (!feof($ar)) {
  $linea = fgets($ar);
  echo $linea;
}
fclose($ar);

*/
?>
