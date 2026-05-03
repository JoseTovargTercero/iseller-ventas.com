<?php
session_start();
$_SESSION['id'] = 1;
$_SESSION['sucursal'] = 1;
$_SESSION['bss_id'] = 1;
$_GET['id'] = 1;
$_POST = [
    'efectivo_bs_contado' => 10,
    'efectivo_usd_contado' => 10,
    'pesos_contado' => 10,
    'punto_contado' => 10,
    'biopago_contado' => 10,
    'pago_movil_contado' => 10,
    'transferencia_contado' => 10,
    'efectivo_bs_fondo' => 10,
    'efectivo_usd_fondo' => 10,
    'pesos_fondo' => 10,
    'observaciones' => 'Test'
];

require 'registro_cierre.php';
