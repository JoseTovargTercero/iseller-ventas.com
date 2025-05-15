<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

// initializ shopping cart class
include '../../configurar/la-carta.php';
$cart = new Cart;

// redirect to home if cart is empty
if ($cart->total_items() <= 0) {
    header('Location: index.php');
}





// set customer ID in session
$_SESSION['sessCustomerID'] = 1;

// get customer details by session customer ID
$query = $conexion->query('SELECT * FROM clientes WHERE id = ' . $_SESSION['sessCustomerID']);
$custRow = $query->fetch_assoc();



if (isset($_POST['rep_codigo2'])) {
    $descontado = $_POST['rep_codigo2'];
    $tipoDescuento = $_POST['tipoDescuento'];

    $valInicial = $cart->total();

    if ($tipoDescuento == '2') {
        $descontado = $descontado * 100 / $valInicial;
    }

    // dinero $descontado * 100 / total


    $_SESSION['descontado'] = $descontado;


    $valProceFinal = $descontado * $valInicial / 100;
    $valFinal = $valInicial - $valProceFinal;
    $valorVentaPeso2 = $_GET['todoPeso'];
    $valorBolivarVenta2 = $_GET['todoBolivar'];

    $valorDescontadoEnPesos =  $descontado * $valorVentaPeso2 / 100;
    $valorDescontadoEnBolivares =  $descontado * $valorBolivarVenta2 / 100;


    $valorVentaPeso3 = $valorVentaPeso2 - $valorDescontadoEnPesos;
    $valorBolivarVenta3 = $valorBolivarVenta2 - $valorDescontadoEnBolivares;

    $valorVentaPeso4 = $valorDescontadoEnPesos;
    $valorBolivarVenta4 = $valorDescontadoEnBolivares;


    $tabla_codigo = '


    <div class="row">
        <div class="col-lg-4 text-center" style="font-size: 2rem;">' . number_format($valFinal, '2', ',', '.') . ' <small>$</small> <br></div>
        <div class="col-lg-4 text-center" style="font-size: 2rem;">' . number_format($valorBolivarVenta3, '2', ',', '.') . ' <small>BS</small></div>
        <div class="col-lg-4 text-center" style="font-size: 2rem;">' . number_format($valorVentaPeso3, '0', ',', '.') . ' <small>COP</small></div>
    </div>



    
    
    
<input type="text" value="' . $valFinal . '" name="valVentaMayor" hidden>
<input type="text" value="' . $valorBolivarVenta3 . '" name="valVentaMayorBs" hidden>
<input type="text" value="' . $valorVentaPeso3 . '" name="valVentaMayorCop" hidden>



<input type="text" value="' . round($valFinal, 2, PHP_ROUND_HALF_DOWN) . '" name="valVentaMayor2" hidden>
<input type="text" value="' . round($valorBolivarVenta3, 2, PHP_ROUND_HALF_DOWN) . '" name="valVentaMayorBs2" hidden>
<input type="text" value="' . round($valorVentaPeso3, 2, PHP_ROUND_HALF_DOWN) . '" name="valVentaMayorCop2" hidden>
                 
    
    
    ';

    if ($valFinal < $_SESSION['pri']) {
        $tabla_codigo .= "
       <h2 style='color:red; text-align: center'>Ingresando perdidas</h2>";
    }
} else {
    $valInicial = $cart->total();
    $valorVentaPeso2 = $_GET['todoPeso'];
    $valorBolivarVenta2 = $_GET['todoBolivar'];




    $tabla_codigo = '

        <input type="text" value="' . round($valInicial, 2, PHP_ROUND_HALF_DOWN) . '" name="valVentaMayor2" hidden >
<input type="text" value="' . round($valorBolivarVenta2, 2, PHP_ROUND_HALF_DOWN) . '" name="valVentaMayorBs2" hidden >
<input type="text" value="' . round($valorVentaPeso2, 2, PHP_ROUND_HALF_DOWN) . '" name="valVentaMayorCop2" hidden >
   

      ';
}
echo $tabla_codigo;
