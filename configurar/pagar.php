<?php
require_once("configuracion.php");
require_once('session.php');
$id = $_GET['order_id'];
$pagoTipo = $_GET['pagoTipo'];
$tipo = $_GET['tipo'];

$date1 = date('Y-m-d h:i:s');
$date2 = date('Y-m-d');
$date3 = date('Y-m');
$date4 = date('Y-W');
$date5 = date('Y');

$precioPesoVenta = $_GET['precioPesoVenta'];
$precioBsVenta = $_GET['precioBsVenta'];



$stmt = mysqli_prepare($conexion, "SELECT * FROM creditos WHERE order_id=$id");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {


    $stmt = $conexion->prepare("UPDATE creditos SET estado='1' WHERE order_id='$id'"); //desactivas el credito
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE orden SET status='$tipo', created='$date1', modified='$date2', 	fecha='$date3', semana='$date4', ano='$date5', total_price_bs='$precioBsVenta', total_price_cop='$precioPesoVenta', tipoPago='$pagoTipo' WHERE id='$id'");
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    header("Location: ../publico/production/creditos.php?accion=error");
}
