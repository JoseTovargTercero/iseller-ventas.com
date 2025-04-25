<?php
require_once("configuracion.php");

$tipoTasa = $_POST['tipoTasa'];
$margen = $_POST['margen'];
$redondeo = $_POST['redondeo'];
$bolivar = $_POST['bolivar'];
$peso = $_POST['peso'];
$bolivarPeso = $_POST['bolivarPeso'];
$peso_bolivar = $_POST['peso_bolivar'];
$bcv = 0;

if ($tipoTasa == 3 || $tipoTasa == 2) {

    function obtenerTasaDeApi()
    {
        $api_key = "afa5859e067e3a9f96886ebc";
        $url = "https://v6.exchangerate-api.com/v6/$api_key/pair/USD/VES";


        $response = @file_get_contents($url);
        if ($response === false) {
            $internetError = true;
            return 0;
        }
        $data = json_decode($response, true);
        return $data['conversion_rate'];
    }



    $bolivar = obtenerTasaDeApi();
    $bcv = $bolivar;
}






if ($tipoTasa == 3) {
    if ($margen != 0 && $margen != '') {
        $bolivar += $margen;
    }


    if ($redondeo == 1) {
        $bolivar = round($bolivar, 0, PHP_ROUND_HALF_UP);
    } elseif ($redondeo == 2) {
        $bolivar = round($bolivar, 0, PHP_ROUND_HALF_DOWN);
    }
}



$update = "
    UPDATE cambio 
    SET 
        pesoDolar='$peso', 
        bolivar_peso='$bolivarPeso', 
        DolarBolivar='$bolivar', 
        tipo_tasa_bs='$tipoTasa', 
        margen_neto='$margen', 
        redondeo='$redondeo', 
        bcv='$bcv', 
        peso_bolivar='$peso_bolivar'
    WHERE id='1'
";

// Ejecutar consulta y verificar el resultado
if ($conexion->query($update) === TRUE) {
} else {
    // Manejo del error
    echo "Error al actualizar los datos: " . $conexion->error;
}

?>

<script>
    window.history.go(-1);
</script>