<?php
require_once("configuracion.php");

$tipoTasa = $_POST['tipoTasa'];
$margen = $_POST['margen'];
$redondeo = $_POST['redondeo'];
$bolivar = $_POST['bolivar'];
$peso = $_POST['peso'];
$bcv = 0;

if ($tipoTasa == 3 || $tipoTasa == 2) {
    $ar = fopen("https://api.exchangedyn.com/", "r") or die('nc');
    if ($ar == 'nc') {
        echo "nc";
        exit();
    } else {
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

        $bolivar = number_format(substr($iter[4], 17, -15), 2, '.', '');
        $bcv = $bolivar;
    }
}





if ($tipoTasa == 3) {
if ($margen != 0 && $margen !='') {
    $bolivar += $margen;
}


if ($redondeo == 1) {
    $bolivar = round($bolivar, 0, PHP_ROUND_HALF_UP);
}elseif ($redondeo == 2) {
    $bolivar = round($bolivar, 0, PHP_ROUND_HALF_DOWN);
}


}



















$update = "UPDATE cambio SET pesoDolar='$peso', DolarBolivar='$bolivar', tipo_tasa_bs='$tipoTasa', margen_neto='$margen', redondeo='$redondeo', bcv='$bcv' WHERE id='1'";
$result = mysqli_query($conexion, $update);



?>

<script>
    window.history.go(-1);
</script>