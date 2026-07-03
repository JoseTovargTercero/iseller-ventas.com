<?php
require_once('includes/requires.php');

if (empty($_SESSION['sucursal']) || $_SESSION['sucursal'] == null) {
    define('PAGINA_INICIO', 'seleccion_sucursal.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
}
$nivelUsuario = $_SESSION['nivel'];
$nombreUsuario = $_SESSION['nombre'];
$usuario_id = $_SESSION['id'];
$bss_id = $_SESSION['bss_id'];
$sucursal = $_SESSION['sucursal'];
$sucursal_nombre = '';







$query = "SELECT * FROM configuracion WHERE bss_id = $bss_id";
$search = $conexion->query($query);
if ($search->num_rows > 0) {
    while ($rowT = $search->fetch_assoc()) {
        $tickets = $rowT['tickets'];
        $ticketsFijo = $rowT['bs_ticket'];
        $cortes_caja = $rowT['cortes_caja'];
        $registro_clientes = $rowT['registro_clientes'];
    }
}




$sql = "SELECT UPPER(nombre) AS nombre FROM sucursales WHERE id = ? AND bss_id = ?";

if ($stmt = $conexion->prepare($sql)) {
    $stmt->bind_param("ii", $sucursal, $bss_id);
    $stmt->execute();
    $stmt->bind_result($sucursal_nombre);

    if ($stmt->fetch()) {
    }

    $stmt->close();
}



require("../../configurar/_calculadrora_precios.php");
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);

$topnav = topnav();

include '../../configurar/la-carta.php';
$cart = new Cart;


$_SESSION["ventas"] = "activa";
if (@$_SESSION["dist_ventas"] == "activa") {
    unset($_SESSION["dist_ventas"]);
    $cart->destroy();
}



$sql = "SELECT 
            p.cantidad_unidades,
            p.origen,
            p.precio_compra,
            s.porcentaje,
            p.nombre,
            p.precio_compra,
            p.cantidad_unidades,
            p.mayor,
            p.id,
            s.stock,
            p.codigo_barras
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        WHERE p.activo = 0
        AND s.id_sucursal = ?
        AND s.bss_id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $sucursal, $bss_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$codigos = [];
$productos_por_id = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nombre = strtoupper($row["nombre"]);
        $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);
        $precios = $calculadora->calcularPrecios($row);
        $codigo = trim($row['codigo_barras']);

        // Formatear nombre
        /* PRODUCTOS PARA LA BUSQUEDA */

        $valorUnidad = (float) $row['precio_compra'] / (float) $row['cantidad_unidades'];
        $mayor = floatval($row['mayor']);


        $productos_por_id[$row['id']] = [
            'id' => $row['id'],
            'stock' => $row['stock'],
            'porcentaje' => $row['porcentaje'],
            'nombre' => $row['nombre'],
            'cantidad_unidades' => $row['cantidad_unidades'],
            'codigo' => $row['codigo_barras'],
            'mayor' => $mayor,
            "precio_dolar_visible" => $precios['precio_venta_dolar'], // Precio de Venta
            "precio_peso_visible" => $precios['precio_venta_peso'], // Precio de Venta
            "precio_bs_visible" => $precios['precio_venta_bs'], // Precio de Venta
            'price_C' => $valorUnidad, // precio de compra
            'price_C_Bs' => $valorUnidad * $dolarBolivar, // precio de compra
            'price_C_Cop' => $valorUnidad * $pesoDolar, // precio de compra
            'cantidadPaca' => $row['cantidad_unidades']
        ];
        /* PRODUCTOS PARA LA BUSQUEDA */


        //  PARA LA BUSQUEDA POR CODIGO DE BARRA
        if (strlen($codigo) > 5) {


            $data["$codigo"] = [
                'id' => $row['id'],
                'stock' => $row['stock'],
                'codigo' => trim($codigo),
                'nombre' => $nombre,
                'precio_dolar_visible' => $precios['precio_venta_dolar'],
                'precio_peso_visible' => $precios['precio_venta_peso'],
                'precio_bs_visible' => $precios['precio_venta_bs'],
                'mayor' => $row['mayor'],
            ];

            $codigos[] = $codigo;
        }
    }
}

$stmt->close();


// Obtener clientes
$clientes = [];
$sql = "SELECT cedula, nombre, telefono FROM clientes WHERE bss_id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $bss_id);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    while ($cliente = $result->fetch_assoc()) {
        $clientes[$cliente['cedula']] = [
            $cliente['nombre'],
            $cliente['telefono']
        ];
    }
}
$stmt->close();

?>




<!DOCTYPE html>
<html lang='es'>

<head>

    <title>Ventas</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dexie/4.2.0/dexie.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>



<style>
    :root {
        --dash-bg: #1a1e24;
        --dash-card: #232931;
        --dash-border: #2e353e;
        --dash-mint: #2dd4a0;
        --dash-mint-dim: rgba(45, 212, 160, 0.12);
        --dash-text: #e8edf2;
        --dash-text-muted: #8892a0;
        --dash-danger: #ef5a6f;
        --dash-info: #5b9cf5;
    }

    .right_col {
        background: var(--dash-bg);
        min-height: 100vh;
        padding: 24px 28px !important;
    }

    .x_panel {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 14px !important;
        padding: 20px 22px !important;
    }

    .x_title {
        border-bottom: 1px solid var(--dash-border) !important;
        padding-bottom: 14px !important;
    }

    .x_title h2 {
        color: var(--dash-text) !important;
        font-size: 15px !important;
        font-weight: 600 !important;
    }

    .x_title .text-danger {
        color: var(--dash-danger) !important;
    }

    .caja-cerrada-container {
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        margin: 20px auto;
        border: 1px solid var(--dash-border);
        background: var(--dash-card);
        font-family: 'Segoe UI', Roboto, sans-serif;
    }

    .caja-cerrada-container h2 {
        color: var(--dash-text);
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
        font-weight: 600;
    }

    .btn-primary {
        background: linear-gradient(135deg, #2dd4a0, #25b88a);
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 3px 12px rgba(45, 212, 160, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(45, 212, 160, 0.35);
        background: linear-gradient(135deg, #2dd4a0, #25b88a);
        color: white;
    }

    .btn-primary:active {
        transform: scale(0.98);
    }

    .btn-secondary {
        color: var(--dash-text-muted) !important;
        background-color: var(--dash-border) !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 7px 18px !important;
        font-size: 13px !important;
    }

    .btn-success {
        background: linear-gradient(135deg, #2dd4a0, #25b88a) !important;
        border: none !important;
        border-radius: 8px !important;
        color: #fff !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        padding: 7px 16px !important;
        box-shadow: 0 2px 8px rgba(45, 212, 160, 0.2) !important;
        transition: all 0.2s ease !important;
    }

    .btn-success:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 14px rgba(45, 212, 160, 0.35) !important;
    }

    .btn-dark {
        background: rgba(255, 255, 255, 0.06) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 8px !important;
        color: var(--dash-text) !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        padding: 8px 16px !important;
        transition: all 0.2s ease !important;
    }

    .btn-dark:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: var(--dash-mint) !important;
        color: var(--dash-mint) !important;
    }

    .btn-danger {
        background: rgba(239, 90, 111, 0.15) !important;
        border: 1px solid rgba(239, 90, 111, 0.3) !important;
        border-radius: 8px !important;
        color: var(--dash-danger) !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
    }

    .btn-danger:hover {
        background: rgba(239, 90, 111, 0.25) !important;
    }

    .btn-outline-secondary {
        background: transparent !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 8px !important;
        color: var(--dash-text-muted) !important;
    }

    .btn-outline-secondary:hover {
        border-color: var(--dash-text-muted) !important;
        color: var(--dash-text) !important;
        background: rgba(255, 255, 255, 0.04) !important;
    }

    .btn-outline-success {
        background: transparent !important;
        border: 1px solid rgba(45, 212, 160, 0.3) !important;
        border-radius: 8px !important;
        color: var(--dash-mint) !important;
    }

    .btn-outline-success:hover {
        background: rgba(45, 212, 160, 0.1) !important;
        border-color: var(--dash-mint) !important;
    }

    .btn-outline-danger {
        background: transparent !important;
        border: 1px solid rgba(239, 90, 111, 0.3) !important;
        border-radius: 8px !important;
        color: var(--dash-danger) !important;
    }

    .btn-outline-danger:hover {
        background: rgba(239, 90, 111, 0.1) !important;
        border-color: var(--dash-danger) !important;
    }

    .modal-cierre-seccion {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--dash-text);
        margin: 16px 0 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-card {
        border: 1px solid var(--dash-border);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 12px;
        background: var(--dash-bg);
    }

    .section-card.cash {
        border-left: 4px solid var(--dash-mint);
    }

    .section-card.digital {
        border-left: 4px solid var(--dash-info);
    }

    .section-card.fondo {
        border-left: 4px solid #f5b45b;
        background: rgba(245, 180, 91, 0.06);
    }

    .cierre-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--dash-text-muted);
        margin-bottom: 3px;
        letter-spacing: 0.3px;
    }

    .input-group-text {
        min-width: 52px;
        justify-content: center;
        font-weight: 600;
        background: var(--dash-bg);
        color: var(--dash-text-muted);
        border-color: var(--dash-border);
    }

    .form-control {
        background: var(--dash-bg) !important;
        border: 1px solid var(--dash-border) !important;
        color: var(--dash-text) !important;
        border-radius: 8px !important;
        font-size: 13px !important;
    }

    .form-control:focus {
        border-color: var(--dash-mint) !important;
        box-shadow: 0 0 0 2px rgba(45, 212, 160, 0.15) !important;
    }

    select.form-control {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%238892a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.75rem center !important;
        background-size: 16px 12px !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        appearance: none !important;
    }

    .scroll-area {
        max-height: 70vh;
        overflow-y: auto;
        padding-right: 6px;
    }

    .swal2-container {
        z-index: 99999;
    }

    .precio {
        font-size: 1rem;
    }

    .item {
        padding: 5px;
        border-bottom: 1px solid var(--dash-border);
    }

    .hide {
        display: none !important;
    }

    .loader {
        width: 48px;
        height: 6px;
        display: block;
        margin: auto;
        position: relative;
        border-radius: 4px;
        color: #FFF;
        box-sizing: border-box;
        animation: animloader 0.6s linear infinite;
    }

    @keyframes animloader {
        0% {
            box-shadow: -10px 20px, 10px 35px, 0px 50px
        }

        25% {
            box-shadow: 0px 20px, 0px 35px, 10px 50px
        }

        50% {
            box-shadow: 10px 20px, -10px 35px, 0px 50px
        }

        75% {
            box-shadow: 0px 20px, 0px 35px, -10px 50px
        }

        100% {
            box-shadow: -10px 20px, 10px 35px, 0px 50px
        }
    }

    .contenedor-loader {
        z-index: 99999;
        background-color: #0000006b;
        height: 100%;
        width: 100%;
        place-items: center;
        display: grid;
        position: fixed;
    }

    .dgrid-center {
        display: grid;
        place-items: center;
    }

    .responsi {
        height: 80%;
        overflow-y: auto;
    }

    .responsi::-webkit-scrollbar {
        height: 7px;
        width: 7px;
        background: transparent;
        margin-bottom: 15px;
    }

    .responsi::-webkit-scrollbar-thumb {
        background: var(--dash-mint);
        border-radius: 5px;
    }

    .table thead th {
        vertical-align: bottom;
        border-bottom: 1px solid var(--dash-border) !important;
        color: var(--dash-text-muted);
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .table td,
    .table th {
        padding: 8px 6px !important;
        vertical-align: middle;
        color: var(--dash-text);
        border-color: var(--dash-border) !important;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background: rgba(255, 255, 255, 0.02) !important;
    }

    .table-striped tbody tr:hover {
        background: rgba(45, 212, 160, 0.04) !important;
    }

    .input-td {
        text-align: center;
        border-radius: 5px;
        max-width: 50px;
        min-height: 25px;
        background: var(--dash-bg) !important;
        color: var(--dash-text) !important;
        border: 1px solid var(--dash-border) !important;
    }

    #result-escaner {
        width: -webkit-fill-available;
    }

    #tabla_resultado_codigo_producto td:nth-child(1) {
        display: grid;
        place-items: center;
    }

    .text-total {
        font-size: 1rem;
        font-weight: bold;
    }

    .text-muted {
        color: var(--dash-text-muted) !important;
    }

    .text-danger {
        color: var(--dash-danger) !important;
    }

    .text-success {
        color: var(--dash-mint) !important;
    }

    .text-info {
        color: var(--dash-info) !important;
    }

    .cart {
        height: 100%;
        display: flex;
        flex-direction: column;
        place-content: space-between;
    }

    .btn-group-sm>.btn,
    .btn-sm {
        font-size: 0.64rem !important;
        padding: .20rem .34rem;
    }

    .totales-pendiente {
        gap: 5px;
    }

    /* Modal dark theme */
    .modal-content {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 14px !important;
    }

    .modal-header {
        border-bottom: 1px solid var(--dash-border) !important;
    }

    .modal-header .modal-title {
        color: var(--dash-text) !important;
        font-weight: 600 !important;
    }

    .modal-header .btn-close {
        filter: invert(0.7);
    }

    .modal-body {
        color: var(--dash-text);
    }

    .modal-footer {
        border-top: 1px solid var(--dash-border) !important;
    }

    .modal-body label {
        color: var(--dash-text-muted);
        font-weight: 600;
        font-size: 13px;
    }

    .modal-body small.text-muted,
    .modal-body .text-muted {
        color: var(--dash-text-muted) !important;
    }

    /* Dashboard-style filter buttons (replaces old btn-dark buttons in botones-container) */
    .botones-container .btn-dark {
        background: rgba(255, 255, 255, 0.06) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 8px !important;
        color: var(--dash-text) !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        padding: 10px 12px !important;
        transition: all 0.2s ease !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .botones-container .btn-dark:hover {
        background: rgba(45, 212, 160, 0.1) !important;
        border-color: var(--dash-mint) !important;
        color: var(--dash-mint) !important;
    }

    .botones-container .btn-danger {
        background: rgba(239, 90, 111, 0.12) !important;
        border: 1px solid rgba(239, 90, 111, 0.25) !important;
        border-radius: 8px !important;
        color: var(--dash-danger) !important;
        font-weight: 600 !important;
        font-size: 12px !important;
        padding: 10px 12px !important;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        transition: all 0.2s ease !important;
    }

    .botones-container .btn-danger:hover {
        background: rgba(239, 90, 111, 0.2) !important;
    }

    #btn-vender {
        background: linear-gradient(135deg, var(--dash-mint), #25b88a) !important;
        border: none !important;
        border-radius: 8px !important;
        color: #fff !important;
        font-weight: 700 !important;
        font-size: 13px !important;
        padding: 10px 16px !important;
        box-shadow: 0 3px 12px rgba(45, 212, 160, 0.25) !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.2s ease !important;
    }

    #btn-vender:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(45, 212, 160, 0.4) !important;
    }

    /* Nav tabs dark theme */
    .nav-tabs {
        border-bottom: 1px solid var(--dash-border) !important;
    }

    .nav-tabs .nav-link {
        color: var(--dash-text-muted) !important;
        border: none !important;
        border-radius: 0 !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        padding: 10px 16px !important;
        transition: all 0.2s ease !important;
        position: relative;
    }

    .nav-tabs .nav-link:hover {
        color: var(--dash-text) !important;
        background: rgba(255, 255, 255, 0.03) !important;
    }

    .nav-tabs .nav-link.active {
        color: var(--dash-mint) !important;
        background: transparent !important;
        border-bottom: 2px solid var(--dash-mint) !important;
    }

    /* Links in dark mode */
    a {
        transition: color 0.2s ease;
    }

    a:hover {
        color: var(--dash-mint) !important;
    }

    /* Dropdown menu dark */
    .dropdown-menu {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 10px !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3) !important;
    }

    .dropdown-item {
        color: var(--dash-text) !important;
        transition: all 0.15s ease !important;
    }

    .dropdown-item:hover {
        background: rgba(45, 212, 160, 0.08) !important;
        color: var(--dash-mint) !important;
    }

    /* Card / dash-panel pattern */
    .dash-panel {
        background: var(--dash-card) !important;
        border: 1px solid var(--dash-border) !important;
        border-radius: 14px !important;
    }

    .dash-panel .dash-panel-header {
        border-bottom: 1px solid var(--dash-border) !important;
        padding-bottom: 14px !important;
        margin-bottom: 18px !important;
    }

    /* Badge dark */
    .badge {
        background: rgba(255, 255, 255, 0.06) !important;
        color: var(--dash-text-muted) !important;
        font-weight: 600 !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
    }

    /* Badge info / success / danger overrides */
    .badge.bg-info {
        background: rgba(91, 156, 245, 0.15) !important;
        color: var(--dash-info) !important;
    }

    .badge.bg-success {
        background: rgba(45, 212, 160, 0.15) !important;
        color: var(--dash-mint) !important;
    }

    .badge.bg-danger {
        background: rgba(239, 90, 111, 0.15) !important;
        color: var(--dash-danger) !important;
    }

    .badge.bg-warning {
        background: rgba(245, 180, 91, 0.15) !important;
        color: #f5b45b !important;
    }

    .table .btn.btn-sm.btn-success {
        padding: 4px 10px !important;
        font-size: 11px !important;
    }

    .badge-subtle-success {
        background: rgba(45, 212, 160, 0.12) !important;
        color: var(--dash-mint) !important;
        border: none !important;
        font-weight: 600 !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
        font-size: 11px !important;
    }

    /* Button group fix: join buttons as a single visual unit */
    .btn-group .btn-success {
        border: none !important;
        border-radius: 0 !important;
    }

    .btn-group>.btn-success:first-child {
        border-radius: 8px 0 0 8px !important;
    }

    .btn-group>.btn-group:last-child>.btn-success:first-child {
        border-radius: 0 8px 8px 0 !important;
    }

    .btn-group>.btn-group:first-child>.btn-success:first-child {
        border-radius: 8px 0 0 8px !important;
    }

    /* Divider line between grouped buttons */
    .btn-group>.btn-success+.btn-success,
    .btn-group>.btn-success+.btn-group>.btn-success:first-child,
    .btn-group>.btn-group+.btn-success {
        border-left: 1px solid rgba(0, 0, 0, 0.2) !important;
    }
</style>
<div class="contenedor-loader" id="cargando">
    <span class="loader"></span>
</div>

<body class='nav-md'>

    <style>
        .section-scanner {
            position: fixed;
            inset: 0;
            z-index: 999;
            background-color: rgba(0, 0, 0, 0.55);
            display: flex;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
            transition: opacity 0.3s ease;
        }

        .section-scanner.hide {
            opacity: 0;
            pointer-events: none;
        }

        #result-escaner {
            position: relative;
            background: var(--dash-card, #232931);
            padding: 20px;
            border-radius: 12px;
            min-width: 300px;
            max-width: 50%;
            min-height: 150px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.4);
            color: var(--dash-text, #e8edf2);
            animation: modalFadeIn 0.3s ease;
        }

        #result-escaner .btn-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: var(--dash-text-muted, #8892a0);
            font-size: 20px;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        #result-escaner .btn-close:hover {
            color: var(--dash-text, #e8edf2);
        }

        @keyframes modalFadeIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .scan-product {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 15px;
            border-radius: 10px;
            color: var(--dash-text, #e8edf2);
        }

        .scan-product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .scan-stock {
            font-size: 0.9rem;
            color: var(--dash-text-muted, #8892a0);
        }

        .scan-prices {
            display: flex;
            gap: 15px;
            font-weight: bold;
            font-size: 1rem;
        }

        .scan-price-usd {
            color: var(--dash-mint, #2dd4a0);
        }

        .scan-price-cop {
            color: var(--dash-info, #5b9cf5);
        }

        .scan-price-bs {
            color: var(--dash-danger, #ef5a6f);
        }

        .scan-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .scan-quantity {
            width: 70px;
            text-align: center;
            border-radius: 6px;
            border: 1px solid var(--dash-border, #2e353e);
            padding: 6px 8px;
            font-size: 1rem;
            background: var(--dash-bg, #1a1e24);
            color: var(--dash-text, #e8edf2);
        }

        .scan-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .scan-btn.add {
            background: linear-gradient(135deg, var(--dash-mint, #2dd4a0), #25b88a);
            color: white;
            box-shadow: 0 2px 8px rgba(45, 212, 160, 0.2);
        }

        .scan-btn.add:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(45, 212, 160, 0.35);
        }

        .scan-btn.remove {
            background: rgba(239, 90, 111, 0.15);
            color: var(--dash-danger, #ef5a6f);
            border: 1px solid rgba(239, 90, 111, 0.3);
        }

        .scan-btn.remove:hover {
            background: rgba(239, 90, 111, 0.25);
        }
    </style>

    <section class="d-flex section-scanner hide" id="section-scanner">
        <div id="result-escaner"> </div>
    </section>

    <script>
        function cerrarScanner() {
            document.getElementById('section-scanner').classList.add('hide');
            document.getElementById('result-escaner').innerHTML = ''; // Limpiar el contenido del escáner
        }
        // al precionar la tecla escape se cierra el scanner
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarScanner();
            }
        });
    </script>


    <div class='container body'>
        <div class='main_container'>
            <?php echo $menu ?>

            <!-- top navigation -->
            <?php echo $topnav ?>
            <!-- /top navigation -->
            <div class="right_col h-100" role='main'>



                <div class="d-flex justify-content-between">
                    <div class="mb-2">
                        <h3 class="mb-0" style="color: var(--dash-text); font-weight: 700; letter-spacing: -0.3px;">Ventas</h3>
                        <p style="color: var(--dash-text-muted); margin-bottom: 0;">Caja de despacho</p>
                    </div>
                    <div class="pt-1">


                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="home-tab" data-bs-toggle="tab"
                                    data-bs-target="#home" type="button" role="tab" aria-controls="home"
                                    aria-selected="true">Carrito activo</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                                    type="button" role="tab" aria-controls="profile" aria-selected="false">No guardados
                                    <span id="cantidad-no-enviada"></span> </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact"
                                    type="button" role="tab" aria-controls="contact" aria-selected="false">Reservados
                                    <span id="cantidad-reservados"></span></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="enviados-tab" data-bs-toggle="tab" data-bs-target="#enviados"
                                    type="button" role="tab" aria-controls="enviados" aria-selected="false">Enviados
                                    <span id="cantidad-enviados"></span></button>
                            </li>
                        </ul>




                    </div>
                </div>


                <style>
                    .text-dark {
                        color: var(--dash-text) !important;
                    }

                    .item-reservado {
                        background-color: rgba(255, 255, 255, 0.04);
                        border: 1px dashed var(--dash-border);
                        border-radius: 8px;
                    }

                    .avatar {
                        background: rgba(255, 255, 255, 0.06);
                        padding: 7px;
                        font-size: 25px;
                        width: 45px;
                        height: 45px;
                        text-align: center;
                        border-radius: 50%;
                        color: var(--dash-text-muted);
                    }

                    .item-reservado-header {
                        display: flex;
                        gap: 5px;
                    }

                    .btn-list-item {
                        flex-direction: column
                    }

                    .botones-container {
                        display: grid;
                        gap: 10px;
                        grid-template-columns: repeat(3, 1fr);
                    }

                    .botones-container .error-internet {
                        grid-column: 1 / -1;
                    }

                    .btn-info {
                        background: rgba(91, 156, 245, 0.15) !important;
                        border: 1px solid rgba(91, 156, 245, 0.3) !important;
                        border-radius: 8px !important;
                        color: var(--dash-info) !important;
                        font-weight: 600 !important;
                        font-size: 13px !important;
                    }

                    @media (max-width: 992px) {
                        .botones-container {
                            grid-template-columns: repeat(2, 1fr);
                        }
                    }

                    @media (max-width: 576px) {
                        .botones-container {
                            grid-template-columns: 1fr;
                        }
                    }

                    .alert-danger {
                        color: var(--dash-danger);
                        background-color: rgba(239, 90, 111, 0.1);
                        border-color: rgba(239, 90, 111, 0.2);
                    }
                </style>




                <div class="row" id="myTabContent">
                    <div class="tab-pane col-lg-12  show active" id="home" role="tabpanel"
                        aria-labelledby="home-tab">
                        <div class="x_panel" style="min-height: 80vh">
                            <div class="x_title d-flex justify-content-between">
                                <div style="display: grid">
                                    <h2>Carrito del cliente</h2>
                                    <span><b>SUCURSAL: </b><span id="sucursal_nombre">
                                        </span></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <button class="btn btn-sm btn-success" style="height: min-content" id="open-modal">
                                        (B) BÚSQUEDA</button>
                                    <button class="btn btn-sm d-none btn-danger" style="height: min-content"
                                        id="btn-cerrar-caja"> CERRAR CAJA</button>
                                </div>
                            </div>
                            <div class="x_content cart">
                                <div>
                                    <div class="table-container" style="overflow: auto">
                                        <table class="table table-striped table-hover" id="tabla-carrito">
                                            <thead style="min-width:100%; ">
                                                <tr>
                                                    <th class="column-title">Cant.</th>
                                                    <th class="column-title">Producto</th>
                                                    <th class="column-title">Peso</th>
                                                    <th class="column-title">BS</th>
                                                    <th class="column-title">Dolares</th>
                                                    <th class="column-title">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                            <tfoot></tfoot>

                                        </table>

                                    </div>





                                    <div style=" bottom: 0; flex-wrap: wrap-reverse;"
                                        class="pt-3 botones-container hide w-100" id="botones_acciones">


                                        <?php

                                        // Mostrar botones según permisos
                                        if (!empty($_SESSION['permisos'][11]) || $_SESSION["nivel"] == 1) {
                                            echo '<button onclick="confirmarVenta(\'credito\')" class="btn btn-dark text-white">CRÉDITO</button>';
                                        }

                                        if (!empty($_SESSION['permisos'][12]) || $_SESSION["nivel"] == 1) {
                                            echo '<a onclick="confirmarDescuento()" class="btn btn-dark text-white" style="cursor: pointer">DESCONTAR PRODUCTOS</a>';
                                        }

                                        ?>


                                        <button class="btn btn-dark" id="btn-reservar">RESERVAR CARRITO</button>
                                        <button class="btn btn-dark" id="calcularVuelto">(C) CALCULAR CAMBIO</button>
                                        <button id="btn-vender" class="btn btn-dark" style="color:white;">(V)
                                            VENDER</button>
                                        <a onclick="vaciarCarritoJs()" class="btn btn-danger "
                                            style="color:white; cursor: pointer">DESTRUIR CARRITO</a>



                                        <div class="error-internet">

                                            <div class="alert alert-danger hide" id="alert-internet" role="alert"
                                                style="display: flex;gap: 5px;">
                                                <ion-icon style="font-size: 20px ;" name="warning-outline"></ion-icon>
                                                <span>
                                                    En estos momentos no tiene conexion a internet, las ventas se
                                                    guardaran en su dispositivo y se enviarán cuando vuelva a tener
                                                    conexión.
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane  col-lg-12 " id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        <div class="x_panel" style="min-height: 60vh">
                            <div class="x_title d-flex justify-content-between">
                                <h2>No guardados</h2>
                            </div>
                            <div class="x_content cart">
                                <ul class="p-0" id="ul-productos-sin-enviar">

                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane  col-lg-12" id="enviados" role="tabpanel" aria-labelledby="enviados-tab">

                        <div class="x_panel" style="min-height: 60vh">
                            <div class="x_title d-flex justify-content-between">
                                <h2>Enviados</h2>
                                <button class="btn btn-sm btn-success" onclick="exportarEnviadosExcel()" style="height:min-content;font-size:11px;"><ion-icon name="download-outline" style="margin-right:4px;font-size:13px;"></ion-icon> Exportar Excel</button>
                            </div>
                            <div class="x_content cart">

                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Id Registro</th>
                                            <th>Monto</th>
                                            <th>Moneda</th>
                                            <th>Tipopago</th>
                                            <th>Respuesta</th>
                                            <th>Status</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody id="table-productos-enviados">

                                    </tbody>
                                </table>
                            </div>
                        </div>


                    </div>
                    <div class="tab-pane  col-lg-12" id="contact" role="tabpanel" aria-labelledby="contact-tab">

                        <div class="x_panel" style="min-height: 60vh">
                            <div class="x_title d-flex justify-content-between">
                                <div>
                                    <h2>Reservados</h2>
                                </div>
                            </div>
                            <div class="x_content cart">
                                <ul class="p-0" id="ul-productos-reservados">

                                </ul>
                            </div>
                        </div>


                    </div>
                </div>


                <div class="row">


                    <div class="col-lg-12 mt-3 ">
                        <div class="x_panel" style="padding-bottom: 30px;">
                            <div class="x_title">
                                <h2 style="font-size: 15px; font-weight: bold">Ultimas ventas realizadas</h2>
                                <div class="clearfix"></div>
                            </div>
                            <div class="x_content ">
                                <div class="">
                                    <table class="table table-striped w-100">
                                        <thead style="font-size: medium;">
                                            <tr>
                                                <th class='column-title'>Fecha</th>
                                                <th class='column-title'>Monto</th>
                                                <th class='column-title'>Detalles</th>
                                                <th style="padding: 10px !important; text-align: center"
                                                    class='column-title'>Ticket</th>
                                                <th style="width: 7%; padding: 10px !important" class='column-title'>
                                                    Eliminar</th>
                                            </tr>
                                        </thead>
                                        <tbody id='tabla_ventas'>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>


            <?php require('../assets/templates/modal.html'); ?>

            <!-- Modal Crédito -->
            <div class="modal fade" id="modalDespacho" tabindex="-1" aria-labelledby="modalDespachoLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDespachoLabel">Información del despacho</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            <input type="text" id="tipo_despacho" value="1" hidden>

                            <section id="resumenVenta" class="mb-3">
                                <div class="border-0">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center mb-2">
                                            <p class="fw-semibold mr-2" style="font-size:14px;">Resumen del despacho</p>
                                            <p class="badge  ml-2" id="resumenTipoDespacho" style="font-size:11px;">VENTA</p>
                                        </div>
                                        <div class="row g-1 text-center">
                                            <div class="col-4">
                                                <div class="rounded p-2" style="background:rgba(91,156,245,0.12);">
                                                    <small class="d-block text-muted" style="font-size:10px;line-height:1;">PESOS</small>
                                                    <span class="fw-bold" style="color:var(--dash-info);font-size:15px;" id="resumenPesos">0</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="rounded p-2" style="background:rgba(239,90,111,0.12);">
                                                    <small class="d-block text-muted" style="font-size:10px;line-height:1;">BOLÍVARES</small>
                                                    <span class="fw-bold" style="color:var(--dash-danger);font-size:15px;" id="resumenBolivares">0,00</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="rounded p-2" style="background:rgba(45,212,160,0.12);">
                                                    <small class="d-block text-muted" style="font-size:10px;line-height:1;">DÓLARES</small>
                                                    <span class="fw-bold" style="color:var(--dash-mint);font-size:15px;" id="resumenDolares">$0,00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="tipoPago">
                                <div class="mb-3">
                                    <label class="pb-0" for="metodoPago">Metodo de pago</label>
                                    <br>
                                    <small class="text-muted">(1) Punto, (2) BioPago, (3) Pesos, (4) Efectivo, (5)
                                        Pago Movil, (6) Transferencia, (7) Dólares.</p>
                                        <select id="metodoPago" class="form-control">
                                            <option value="">Seleccione</option>
                                            <option value="option1">(1) Punto</option>
                                            <option value="option7">(2) BioPago</option>
                                            <option value="option6">(3) Pesos</option>
                                            <option value="option4">(4) Efectivo</option>
                                            <option value="option2">(5) Pago Movil</option>
                                            <option value="option3">(6) Transferencia</option>
                                            <option value="option5">(7) Dólares</option>
                                        </select>
                                </div>
                            </section>

                            <section id="datos_cliente">
                                <div class="mb-3">
                                    <label for="cedulaClienteModal">Cedula del cliente</label>
                                    <input type="number" id="cedulaClienteModal" class="form-control"
                                        placeholder="Cedula del cliente">
                                </div>
                                <div class="mb-3">
                                    <label for="nombreClienteModal">Nombre del cliente</label>
                                    <input type="text" id="nombreClienteModal" class="form-control"
                                        placeholder="Nombre del cliente">
                                </div>
                                <div class="mb-3">
                                    <label for="telefonoClienteModal">Telefono del cliente</label>
                                    <input type="text" id="telefonoClienteModal" class="form-control"
                                        placeholder="Telefono del cliente" maxlength="11" minlength="11">
                                </div>
                            </section>

                            <div class="modal-footer p-0 mt-2">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary"
                                    id="btnConfirmarDespacho">Continuar</button>
                            </div>
                        </div>
                    </div>
                </div>



            </div>
        </div>
        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <script src="../vendors/nprogress/nprogress.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="../build/js/modal.js"></script>
        <!-- FastClick -->
        <script>
            const base_url = '../../configurar/';
            const sucursal_n = <?php echo json_encode($sucursal_nombre) ?>;
            const sucursal_i = <?php echo json_encode($sucursal) ?>;
            const nivelUsuario = <?php echo json_encode($nivelUsuario) ?>;
            const registro_clientes = <?php echo json_encode($registro_clientes) ?>;


            const configuraciones = {
                tickets: <?php echo json_encode($tickets) ?>,
                ticketsFijo: <?php echo json_encode($ticketsFijo) ?>,
                cortes_caja: <?php echo json_encode($cortes_caja) ?>
            }


            // Obtener clientes
            const clientes = <?php echo json_encode($clientes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;


            // Verificar Apertura de caja
            $(document).ready(function() {
                if (configuraciones.cortes_caja == 1 && nivelUsuario == 2) {
                    aperturaCaja()
                    consulaCierre()
                }
                document.getElementById("cargando").style.display = "none";
            });

            function aperturaCaja() {
                $.ajax({
                        url: base_url + 'consulta_apertura.php',
                        type: 'GET',
                        data: {
                            tipo_corte: 'apertura'
                        },
                        dataType: 'json'
                    })
                    .done(function(response) {
                        /// console.log(response)
                        if (response.status === 'success' && !response.corte) {
                            // Bloquear la página y pedir apertura
                            Swal.fire({
                                title: '<h4 class="mb-0 fw-bold">Apertura de Caja Obligatoria</h4>',
                                html: `
                                <div class="text-start scroll-area">
                                    <div class="modal-cierre-seccion">
                                        <ion-icon name="wallet-outline"></ion-icon> Fondo de Caja Inicial
                                    </div>
                                    <div class="section-card fondo">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="cierre-label">Bolívares (Bs)</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="efectivo_bs_fondo" class="form-control" placeholder="0.00" step="0.01">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="cierre-label">Dólares (USD)</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="efectivo_usd_fondo" class="form-control" placeholder="0.00" step="0.01">
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="cierre-label">Pesos (COP)</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="pesos_fondo" class="form-control" placeholder="0" step="1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="modal-cierre-seccion">
                                        <ion-icon name="chatbubble-ellipses-outline"></ion-icon> Observaciones
                                    </div>
                                    <div class="section-card">
                                        <textarea id="observaciones_apertura" class="form-control" rows="2" placeholder="Notas adicionales sobre la apertura..."></textarea>
                                    </div>
                                </div>
                            `,
                                icon: 'info',
                                confirmButtonText: 'Realizar Apertura',
                                confirmButtonColor: '#32d7c0',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                customClass: {
                                    popup: 'swal-metodo-pago'
                                },
                                width: '600px',
                                preConfirm: () => {
                                    const bs = Swal.getPopup().querySelector('#efectivo_bs_fondo').value;
                                    const usd = Swal.getPopup().querySelector('#efectivo_usd_fondo').value;
                                    const pesos = Swal.getPopup().querySelector('#pesos_fondo').value;
                                    const obs = Swal.getPopup().querySelector('#observaciones_apertura').value;

                                    if (!bs && !usd && !pesos) {
                                        Swal.showValidationMessage(`Por favor ingrese al menos un monto de fondo`);
                                    }
                                    return {
                                        efectivo_bs_fondo: bs,
                                        efectivo_usd_fondo: usd,
                                        pesos_fondo: pesos,
                                        observaciones: obs
                                    }
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: base_url + 'registro_apertura.php',
                                        type: 'POST',
                                        data: result.value,
                                        dataType: 'json',
                                        success: function(res) {
                                            if (res.status == 'success') {
                                                Swal.fire('¡Éxito!', res.message, 'success');
                                                document.getElementById('btn-cerrar-caja').classList.remove('d-none');
                                            } else {
                                                Swal.fire('Error', res.message || 'No se pudo realizar la apertura', 'error')
                                                    .then(() => {
                                                        aperturaCaja(); // Reintentar
                                                    });
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                                            console.log(error);
                                        }
                                    });
                                }
                            });
                        } else {
                            document.getElementById('btn-cerrar-caja').classList.remove('d-none');
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.log(error)
                        Swal.fire('Error', 'No se pudo conectar con el servidor: ' + error, 'error').then(() => {
                            aperturaCaja(); // Reintentar
                        });
                    });
            }

            function consulaCierre() {
                $.ajax({
                        url: base_url + 'consulta_apertura.php',
                        type: 'GET',
                        data: {
                            tipo_corte: 'cierre'
                        },
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.corte) {
                            document.getElementById('btn-cerrar-caja').classList.add('d-none');
                            // .x_panel
                            const xpanel = document.querySelector('.x_panel');
                            // rellena el contenido de xpanel con un mensaje que diga: 
                            xpanel.innerHTML = xpanel.innerHTML = `
    <div class="caja-cerrada-container">
        <h2>Caja cerrada</h2>
        <button class="btn-primary" onclick="reabrirCaja()">
            Reabrir
        </button>
    </div>
`;



                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.log(error)
                        consulaCierre()
                    });
            }

            function reabrirCaja() {
                Swal.fire({
                    title: '¿Reabrir caja?',
                    text: 'Se eliminará el último registro de cierre de hoy y podrá realizar ventas nuevamente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, reabrir',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: base_url + 'reabrir_caja.php',
                            type: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire(
                                        '¡Reabierta!',
                                        'La caja ha sido reabierta exitosamente.',
                                        'success'
                                    ).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'No se pudo procesar la solicitud', 'error');
                            }
                        });
                    }
                });
            }


            // cerrar caja
            document.getElementById('btn-cerrar-caja').addEventListener('click', function() {
                Swal.fire({
                    title: '¿Está seguro de cerrar la caja?',
                    text: 'No podrá revertir esta acción',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cerrar caja',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        cerrarCaja();
                    }
                });
            });

            function cerrarCaja() {
                Swal.fire({
                    title: 'Cierre de Caja',
                    html: `
              

        <div class="text-start scroll-area">

            <!-- EFECTIVO -->
            <div class="modal-cierre-seccion">
                Dinero Contado
            </div>

            <div class="section-card cash">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="cierre-label">Bolívares en Efectivo</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="efectivo_bs_contado" class="form-control" placeholder="0.00">
                        </div>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="cierre-label">Dólares</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="efectivo_usd_contado" class="form-control" placeholder="0.00">
                        </div>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="cierre-label">Pesos</label>
                        <div class="input-group input-group-sm">
                            <input type="number" id="pesos_contado" class="form-control" placeholder="0">
                        </div>
                    </div>
                </div>
            </div>


            <div class="section-card digital">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="cierre-label">Punto de Venta</label>
                        <input type="number" id="punto_contado" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="cierre-label">BioPago</label>
                        <input type="number" id="biopago_contado" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="cierre-label">Pago Móvil</label>
                        <input type="number" id="pago_movil_contado" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="cierre-label">Transferencia</label>
                        <input type="number" id="transferencia_contado" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <!-- FONDO -->
            <div class="modal-cierre-seccion">
            Fondo de Caja
            </div>

            <div class="section-card fondo">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="cierre-label">Bs</label>
                        <input type="number" id="efectivo_bs_fondo" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="cierre-label">USD</label>
                        <input type="number" id="efectivo_usd_fondo" class="form-control form-control-sm">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="cierre-label">Pesos</label>
                        <input type="number" id="pesos_fondo" class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <!-- OBSERVACIONES -->
            <div class="modal-cierre-seccion">
            Observaciones
            </div>

            <textarea id="observaciones_cierre" class="form-control" rows="2" placeholder="Opcional..."></textarea>

        </div>
                `,
                    confirmButtonText: 'Realizar Cierre',
                    cancelButtonText: 'Cancelar',
                    showCancelButton: true,
                    allowOutsideClick: false,
                    width: '600px',
                    preConfirm: () => {
                        return {
                            efectivo_bs_contado: Swal.getPopup().querySelector('#efectivo_bs_contado').value,
                            efectivo_usd_contado: Swal.getPopup().querySelector('#efectivo_usd_contado').value,
                            pesos_contado: Swal.getPopup().querySelector('#pesos_contado').value,
                            punto_contado: Swal.getPopup().querySelector('#punto_contado').value,
                            biopago_contado: Swal.getPopup().querySelector('#biopago_contado').value,
                            pago_movil_contado: Swal.getPopup().querySelector('#pago_movil_contado').value,
                            transferencia_contado: Swal.getPopup().querySelector('#transferencia_contado').value,
                            efectivo_bs_fondo: Swal.getPopup().querySelector('#efectivo_bs_fondo').value,
                            efectivo_usd_fondo: Swal.getPopup().querySelector('#efectivo_usd_fondo').value,
                            pesos_fondo: Swal.getPopup().querySelector('#pesos_fondo').value,
                            observaciones: Swal.getPopup().querySelector('#observaciones_cierre').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: base_url + 'registro_cierre.php',
                            type: 'POST',
                            data: result.value,
                            dataType: 'json',
                            success: function(res) {
                                if (res.status == 'success') {
                                    Swal.fire('¡Éxito!', res.message, 'success').then(() => {
                                        // Opcional: redirigir o recargar la página para bloquear las ventas
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', res.message || 'No se pudo realizar el cierre', 'error').then(() => {
                                        cerrarCaja(); // Reintentar
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire('Error', 'Error de conexión con el servidor', 'error');
                                console.log(error);
                            }
                        });
                    }
                });
            }



            var productos = <?php echo json_encode($data); ?>;
            var productos_por_id = <?php echo json_encode($productos_por_id); ?>;
            var codigos = []

            /* buscador de productos */
            const codigos_indexados = Object.values(productos);
            const productos_indexados = Object.values(productos_por_id);

            // Instanciar Fuse con configuración mínima y precisa
            const fuse = new Fuse(productos_indexados, {
                keys: ['nombre'], // Puedes incluir 'codigo' si deseas buscar también por él
                threshold: 0.28, // 0.1 puede ser demasiado estricto para nombres incompletos
                ignoreLocation: true, // Permite coincidencias en cualquier parte del string
                includeScore: false, // No necesitas el score si solo devuelves los ítems
                useExtendedSearch: false // Acelera búsqueda si no usas operadores especiales
            });


            const fuseCodigos = new Fuse(codigos_indexados, {
                keys: ['codigo'], // Puedes incluir 'codigo' si deseas buscar también por él
                threshold: 0, // 0.1 puede ser demasiado estricto para nombres incompletos
                ignoreLocation: false, // Permite coincidencias en cualquier parte del string
                includeScore: false, // No necesitas el score si solo devuelves los ítems
                useExtendedSearch: true // Acelera búsqueda si no usas operadores especiales
            });

            // Función de búsqueda rápida y limpia
            const buscarConFuse = termino => fuse.search(`=${termino}`).map(r => r.item);
            const buscarCodigoFuse = codigo => fuseCodigos.search(codigo).map(r => r.item);

            /* buscador de productos */




            const metodosPago = {
                'option1': 'Punto',
                'option2': 'Pago Móvil',
                'option3': 'Transferencia',
                'option4': 'Efectivo',
                'option5': 'Dólares',
                'option6': 'Pesos',
                'option7': 'BioPago'
            };


            // lista de ventas
            function cargarUltimasOrdenes() {
                fetch(base_url + 'ultimas_ventas.php')
                    .then(response => response.json())
                    .then(data => {
                        const tabla = document.getElementById('tabla_ventas');
                        tabla.innerHTML = ''; // Limpiar antes de insertar

                        let contador = 1;

                        data.forEach(orden => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                    <td> 
                                    ${orden.pagoPor} 
                                    <small>(${orden.tipoVenta})</small>
                                    <br>
                                    <small class="text-muted">${orden.fecha}</small></td>
                                    <td>$${orden.total}</td>
                                    <td><a href="detallesVenta.php?id=${orden.id}">Detalles</a></td>
                                    <td style="text-align: center">
                                    <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                        <button type="button" class="btn btn-sm btn-success" onclick="print(${orden.id})">
                                            <i class="line icon-printer"></i>
                                        </button>
                                        <div class="btn-group" role="group">
                                            <button id="btnGroupDrop1" type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                            <li><a class="dropdown-item" onclick="print(${orden.id}, 'USD')">Imprimir en dolares</a></li>
                                            <li><a class="dropdown-item" onclick="print(${orden.id}, 'COP')">Imprimir en pesos</a></li>
                                            <li><a class="dropdown-item" onclick="print(${orden.id}, 'BS')">Imprimir en bolivares</a></li>
                                            </ul>
                                        </div>
                                        </div>    
                                    </td>

                                    <td style="text-align: center"><a class="btn btn-danger btn-sm" style="cursor: pointer; color: white" title="Deshacer compra" onclick="confirm(${orden.id})"><i class="line icon-reload"></i></a></td>
                                `;
                            tabla.appendChild(row);
                        });
                    })
                    .catch(error => {
                        console.error('Error al cargar órdenes:', error);
                    });
            }

            // Llamar cuando cargue la página
            document.addEventListener('DOMContentLoaded', cargarUltimasOrdenes);


            function print(id, moneda = 'default') {
                $('#cargando').show();

                $.ajax({
                        url: base_url + 'contenido_ticket.php',
                        type: 'POST',
                        data: {
                            id: id,
                            moneda: moneda
                        },
                        dataType: 'html'
                    })
                    .done(function(result) {
                        const fecha = new Date().toLocaleString('es-VE', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });

                        const contenido = `
                            <html>
                            <head>
                                <title>Ticket</title>
                                <style>
                                 * {
                                        box-sizing: border-box;
                                    }

                                    body {
                                        font-family: Arial, sans-serif;
                                        font-size: 11px !important;
                                        margin: 0;
                                        padding: 0;
                                        max-width: 44mm
                                    }


                                    .centrado {
                                        text-align: center;
                                    }

                                      @media print {
                                    @page {
                                        size: 44mm auto;
                                        margin: 0;
                                    }

                                    body, html {
                                        margin: 0;
                                        padding: 0;
                                    }

                                    #ticket {
                                        page-break-after: avoid;
                                    }
                                }

                                 table {
                                    width: 100%; /* Usa 100% en lugar de 58mm para que no exceda su contenedor */
                                    border-collapse: collapse;
                                    table-layout: fixed; /* Asegura que las columnas se ajusten */
                                    
                                }

                                th, td {
                                    text-align: left;
                                    padding: 2px 0;
                                }

                                .line {
                                    border-top: 1px dashed #000;
                                    margin: 5px 0;
                                }

                                </style>
                            </head>
                              <body>
                                <div class="ticket" id="ticket">
                                    <p class="centrado">
                        <img src="images/sucursal_logo/${sucursal_i}.png" height="50px" onerror="this.parentNode.removeChild(this)">
                                    <br>
                                    <br>
                                        ${sucursal_n}
                                        <br>
                                        ${fecha}<br>
                                        <strong></strong><br>
                                        <small>* Nota de entrega</small>
                                    </p>
                                    ${result}
                                    <div class="line"></div>
                                    <p class="centrado" style="font-size: 10px;">¡GRACIAS POR SU COMPRA!</p>
                                    <div class="line"></div>
                                </div>
                              </body>
                            </html>
                        `;

                        const ventana = window.open('', '_blank', 'width=600,height=600');
                        ventana.document.open();
                        ventana.document.write(contenido);
                        ventana.document.close();

                        ventana.onload = function() {
                            ventana.print();
                            ventana.close();
                        };

                        $('#cargando').hide();
                    })
                    .fail(function(xhr, status, error) {
                        console.error('Error al cargar el ticket:', error);
                        $('#cargando').hide();
                        alert('Hubo un error al generar el ticket. Intente nuevamente.');
                    });
            }

            function confirmar_e_imprimir(nuevoPedido, moneda = 'default') {
                // 1. Limpiar si existía algún modal nativo previo abierto por error
                $('#modal-impresion-nativo').remove();

                // 2. Crear la estructura del modal nativo con estilos en línea (estilo SweetAlert)
                const modalHTML = `
        <dialog id="modal-impresion-nativo" style="
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            padding: 24px;
            width: 90%;
            max-width: 420px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            text-align: center;
            background: var(--dash-card, #232931);
            color: var(--dash-text, #e8edf2);
        ">
            <div style="
                width: 5rem;
                height: 5rem;
                border: .25rem solid rgba(45, 212, 160, 0.3);
                border-radius: 50%;
                color: var(--dash-mint, #2dd4a0);
                font-size: 3.75rem;
                line-height: 5rem;
                margin: 1.25rem auto;
                text-align: center;
                user-select: none;
            ">?</div>

            <h2 style="color: var(--dash-text, #e8edf2); font-size: 1.5rem; margin: 0 0 0.5rem 0; font-weight: 600;">¿Deseas imprimir el ticket?</h2>
            <p style="color: var(--dash-text-muted, #8892a0); font-size: 1.125rem; margin: 0 0 1.5rem 0;">Se generará la nota de entrega para el pedido #${nuevoPedido.id}</p>
            
            <div style="display: flex; justify-content: center; gap: 10px;">
                <button id="btn-cancelar-nativo" style="
                    background: rgba(239, 90, 111, 0.15); color: var(--dash-danger, #ef5a6f); border: 1px solid rgba(239, 90, 111, 0.3); 
                    padding: 10px 24px; font-size: 1rem; border-radius: 8px; 
                    cursor: pointer; font-weight: 600;
                ">No, cancelar</button>
                
                <button id="btn-confirmar-nativo" style="
                    background: linear-gradient(135deg, var(--dash-mint, #2dd4a0), #25b88a); color: white; border: none; 
                    padding: 10px 24px; font-size: 1rem; border-radius: 8px; 
                    cursor: pointer; font-weight: 600; box-shadow: 0 2px 8px rgba(45, 212, 160, 0.25);
                ">Sí, imprimir</button>
            </div>
        </dialog>
    `;

                // 3. Inyectar en el body
                $('body').append(modalHTML);
                const dialog = document.getElementById('modal-impresion-nativo');

                // 4. Mostrar el modal nativo (showModal bloquea la interacción con el fondo)
                dialog.showModal();

                // 5. Asignar eventos a los botones usando jQuery
                $('#btn-confirmar-nativo').on('click', function() {
                    imprimir_desde_front(nuevoPedido, moneda);
                    dialog.close();
                    $('#modal-impresion-nativo').remove(); // Limpieza del DOM
                });

                $('#btn-cancelar-nativo').on('click', function() {
                    dialog.close();
                    $('#modal-impresion-nativo').remove(); // Limpieza del DOM
                });
            }




            function imprimir_desde_front(nuevoPedido, moneda = 'default') {
                // 1. Replicar la función de redondeo de centenas de PHP
                function redondearCentena(numero) {
                    let resto = numero % 100;
                    if (resto > 50) {
                        return Math.ceil(numero / 100) * 100;
                    } else {
                        return Math.floor(numero / 100) * 100;
                    }
                }

                // 2. Determinar la moneda de texto inicial según el tipo de pago (metodoPago)
                let monedaTexto = '';
                let tipopago = String(nuevoPedido.metodoPago);

                if (moneda === 'default') {
                    switch (tipopago) {
                        case '5':
                            monedaTexto = 'USD';
                            break;
                        case '6':
                            monedaTexto = 'COP';
                            break;
                        default:
                            monedaTexto = 'BS';
                            break;
                    }
                } else {
                    monedaTexto = moneda;
                }

                // 3. Comenzar a armar el HTML de la tabla
                let resultHtml = `
                <table>
                    <thead>
                    <tr>
                        <th style='font-size: 11px; width: 15%; word-wrap: break-word;'>#</th>
                        <th style='font-size: 11px; width: 60%; word-wrap: break-word;'>Producto</th>
                        <th style='font-size: 11px; width: 25%;'>${monedaTexto}</th>
                    </tr>
                    </thead>
                    <tbody>`;

                let totalPrice = 0;

                // 4. Recorrer los productos del carrito (nuevoPedido.productos)
                const productosArray = Object.values(nuevoPedido.productos);

                productosArray.forEach(articulo => {
                    let cantidad = articulo.qty;
                    let precio = 0;
                    let etiquetaMoneda = '';

                    if (moneda === 'default') {
                        switch (tipopago) {
                            case '5':
                                precio = articulo.price * cantidad; // price es dolarventa_p
                                etiquetaMoneda = '<small>$</small>';
                                break;
                            case '6':
                                precio = redondearCentena(articulo.pricePeso * cantidad); // pricePeso es pesoventa_p
                                etiquetaMoneda = '<small>COP</small>';
                                break;
                            default:
                                precio = articulo.priceBolivar * cantidad; // priceBolivar es bolivarventa_p
                                etiquetaMoneda = '<small>BS</small>';
                                break;
                        }
                    } else {
                        switch (moneda) {
                            case 'USD':
                                precio = articulo.price * cantidad;
                                etiquetaMoneda = '<small>$</small>';
                                break;
                            case 'COP':
                                precio = redondearCentena(articulo.pricePeso * cantidad);
                                etiquetaMoneda = '<small>COP</small>';
                                break;
                            default:
                                precio = articulo.priceBolivar * cantidad;
                                etiquetaMoneda = '<small>BS</small>';
                                break;
                        }
                    }

                    totalPrice += precio;

                    // Formatear número al estilo es-VE o es-CL para usar '.' en miles y ',' en decimales
                    let precioFormateado = new Intl.NumberFormat('es-VE', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(precio);

                    resultHtml += `
                <tr>
                    <td style='font-size: 11px; width: 15%; word-wrap: break-word;'>${cantidad}</td>
                    <td style='font-size: 11px; width: 60%; word-wrap: break-word;'>${articulo.name}</td>
                    <td style='font-size: 11px; width: 25%;'>${precioFormateado}</td>
                </tr>`;
                });

                let totalFormateado = new Intl.NumberFormat('es-VE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(totalPrice);

                resultHtml += `</tbody></table>`;
                resultHtml += `<br><div>Total: ${totalFormateado} ${monedaTexto === 'USD' ? '<small>$</small>' : monedaTexto === 'COP' ? '<small>COP</small>' : '<small>BS</small>'}</div>`;

                // 5. Estructurar e Inyectar en la nueva ventana para proceder a la impresión física
                const fecha = new Date().toLocaleString('es-VE', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });

                // Nota: Las variables sucursal_i y sucursal_n deben ser globales o estar accesibles en tu script
                const contenido = `
                <html>
                <head>
                    <title>Ticket</title>
                    <style>
                        * { box-sizing: border-box; }
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 11px !important;
                            margin: 0; padding: 0;
                            max-width: 44mm;
                        }
                        .centrado { text-align: center; }
                        @media print {
                            @page { size: 44mm auto; margin: 0; }
                            body, html { margin: 0; padding: 0; }
                            #ticket { page-break-after: avoid; }
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            table-layout: fixed;
                        }
                        th, td { text-align: left; padding: 2px 0; }
                        .line { border-top: 1px dashed #000; margin: 5px 0; }
                    </style>
                </head>
                <body>
                    <div class="ticket" id="ticket">
                        <p class="centrado">
                            <img src="images/sucursal_logo/${typeof sucursal_i !== 'undefined' ? sucursal_i : 1}.png" height="50px" onerror="this.parentNode.removeChild(this)">
                            <br><br>
                            ${typeof sucursal_n !== 'undefined' ? sucursal_n : 'MI TIENDA'}
                            <br>
                            ${fecha}<br>
                            <small>* Nota de entrega</small>
                        </p>
                        ${resultHtml}
                        <div class="line"></div>
                        <p class="centrado" style="font-size: 10px;">¡GRACIAS POR SU COMPRA!</p>
                        <div class="line"></div>
                    </div>
                </body>
                </html>`;

                const ventana = window.open('', '_blank', 'width=600,height=600');
                ventana.document.open();
                ventana.document.write(contenido);
                ventana.document.close();

                ventana.onload = function() {
                    ventana.print();
                    if (ventana.matchMedia) {
                        const mediaQuery = ventana.matchMedia('print');
                        mediaQuery.addEventListener('change', function mqListener(evt) {
                            if (!evt.matches) {
                                mediaQuery.removeEventListener('change', mqListener);
                                ventana.close();
                            }
                        });
                    } else {
                        setTimeout(() => ventana.close(), 1000);
                    }
                };
            }





            // Obtener listado de tasas a mostrar
            var tasasMostrar

            function cargarTasasMostrar() {
                fetch(base_url + 'tasas_mostradas.php?accion=obtener')
                    .then(res => res.json())
                    .then(res => {

                        if (res.status === 'success') {
                            tasasMostrar = res.data;
                        } else {
                            Swal.fire("Error", res.message, "error");
                        }
                    })
                    .catch(error => {
                        // Mostrar el error en consola para debugeo
                        console.error("Error al obtener las tasas:", error);
                        Swal.fire("Error", "Error al obtener las tasas.", "error");
                    });
            }

            cargarTasasMostrar();
            // Obtener listado de tasas a mostrar


            var total_pesos = 0;
            var total_dolares = 0;
            var total_bolivares = 0;


            let modalDespacho = null;

            function confirmarVenta(tipo = 'venta') {
                // 1. Reglas comunes ───────────────────────────────────────────────
                if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                    return false;
                }
                $('button').blur(); // quitar focus de botones

                const cliente_section = document.getElementById('datos_cliente');
                const metodo_pago_section = document.getElementById('tipoPago');
                const tipo_despacho = document.getElementById('tipo_despacho');
                const esCredito = (tipo === 'credito');

                if (!modalDespacho) {
                    modalDespacho = new bootstrap.Modal(document.getElementById('modalDespacho'));
                }


                /*if ($('#result-escaner').find('.btn-add-to-car').length > 0) {
                    Swal.fire({
                        title: 'Atención',
                        html: 'Hay un producto en la cola de ventas, agréguelo al carrito o descártelo antes de continuar',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#32d7c0',
                    });
                    return;
                }*/


                document.getElementById('cedulaClienteModal').value = '';
                document.getElementById('nombreClienteModal').value = '';
                document.getElementById('telefonoClienteModal').value = '';

                if (esCredito) {
                    setTimeout(() => document.getElementById('cedulaClienteModal').focus(), 500);

                    cliente_section.classList.remove('d-none'); // muestra la seccion de datos del cliente
                    metodo_pago_section.classList.add('d-none'); // oculta la seccion tipo de pago
                    tipo_despacho.value = '2';

                } else {
                    document.getElementById('metodoPago').value = '';
                    //    setTimeout(() => document.getElementById('metodoPago').focus(), 500);
                    metodo_pago_section.classList.remove('d-none'); // muestra la seccion de tipo de pago
                    tipo_despacho.value = '1';


                    if (registro_clientes == 1) {
                        cliente_section.classList.remove('d-none'); // Muestra la vista de datos del cliente al momento de vender si la configuracion esta activada
                    } else {
                        cliente_section.classList.add('d-none'); // Oculta la vista de datos del cliente al momento de vender si la configuracion esta desactivada
                    }
                }


                document.getElementById('resumenPesos').textContent = formatearMiles(total_pesos);
                document.getElementById('resumenBolivares').textContent = formatNumber(total_bolivares) + ' Bs';
                document.getElementById('resumenDolares').textContent = '$' + formatNumber(total_dolares);
                document.getElementById('resumenTipoDespacho').textContent = esCredito ? 'CRÉDITO' : 'VENTA';
                document.getElementById('resumenTipoDespacho').className = 'badge ms-auto ' + (esCredito ? 'bg-warning' : 'bg-success');

                modalDespacho.show();

            }

            function indicadorCampoVacio(campo_id, campo_nombre) {
                console.log(campo_id)
                const campo = document.getElementById(campo_id).value;
                if (campo == '') {
                    Swal.fire('Error', `Por favor, ingrese el campo ${campo_nombre} para continuar.`, 'error');
                    return false;
                }
                return true;
            }


            const btnConfirmarDespacho = document.getElementById('btnConfirmarDespacho');


            document.addEventListener('DOMContentLoaded', () => {

                const tipo_despacho = document.getElementById('tipo_despacho');
                const selectPago = document.getElementById('metodoPago');
                const cedulaClienteModal = document.getElementById('cedulaClienteModal');
                const nombreClienteModal = document.getElementById('nombreClienteModal');
                const telefonoClienteModal = document.getElementById('telefonoClienteModal');

                document.getElementById('nombreClienteModal').addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnConfirmarDespacho.click();
                    }
                }); // Enivar el formulario cuando se presiona enter en el input del nombre

                document.getElementById('metodoPago').addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                    }
                }); // Enivar el formulario cuando se presiona enter en el select de pago

                if (btnConfirmarDespacho) {
                    btnConfirmarDespacho.addEventListener('click', () => {
                        btnConfirmarDespacho.disabled = true;
                        const despacho = tipo_despacho.value
                        despacho == '2' ? validarCredito() : validarVenta();
                    });
                }





                function validarCredito() {
                    const cedula = cedulaClienteModal.value.trim()
                    const nombre = nombreClienteModal.value.trim()
                    const telefo = telefonoClienteModal.value.trim()

                    const campos = [
                        ['cedulaClienteModal', 'Cedula'],
                        ['nombreClienteModal', 'Nombre'],
                        ['telefonoClienteModal', 'Telefono']
                    ];

                    for (const [campo, nombreCampo] of campos) {
                        const result = indicadorCampoVacio(campo, nombreCampo);

                        if (!result) {
                            return; // Detiene la ejecución completa de la función donde se encuentre este código
                        }
                    }
                    // Verificas los datos

                    if (modalDespacho) modalDespacho.hide(); // Ocultas el modal
                    procesarPedido('0', 2, {
                        nombre: nombre,
                        cedula: cedula,
                        telefono: telefo
                    }); // envias el pedido
                } // Valida los datos del credito


                function validarVenta() {
                    let cedula = '';
                    let nombre = '';
                    let telefo = '';

                    if (registro_clientes == 1) {
                        cedula = cedulaClienteModal.value.trim()
                        nombre = nombreClienteModal.value.trim()
                        telefo = telefonoClienteModal.value.trim()

                        const campos = [
                            ['cedulaClienteModal', 'Cedula'],
                            ['nombreClienteModal', 'Nombre'],
                            ['telefonoClienteModal', 'Telefono']
                        ];

                        for (const [campo, nombreCampo] of campos) {
                            const result = indicadorCampoVacio(campo, nombreCampo);
                            if (!result) {
                                console.log('es un error 2 ')
                                return; // Detiene la ejecución completa de la función donde se encuentre este código
                            }
                        }
                        // Verificas los datos
                    }

                    if (selectPago.value == '') {
                        Swal.fire('Error', 'Por favor, selecciona un método de pago', 'error');
                        return;
                    }

                    if (modalDespacho) modalDespacho.hide();
                    procesarPedido(selectPago.value, 1, {
                        nombre: nombre,
                        cedula: cedula,
                        telefono: telefo
                    });
                } // Valida los datos de la venta



                // Auto-consulta de datos del cliente por cédula
                const inputCedula = document.getElementById('cedulaClienteModal');
                const inputTelefono = document.getElementById('telefonoClienteModal');

                if (inputCedula) {
                    inputCedula.addEventListener('keyup', function() {
                        const cedula = this.value.trim();
                        if (cedula.length >= 6) {

                            if (clientes[cedula]) {
                                nombreClienteModal.value = clientes[cedula][0];
                                inputTelefono.value = clientes[cedula][1];
                            }
                        }
                    });

                    inputCedula.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            nombreClienteModal.focus();
                        }
                    });
                }

            });

























            function calcularVuelto() {
                if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                    return false;
                }

                total_pesos = String(total_pesos).replace(',', '');

                let handleEnterKey;

                Swal.fire({
                    title: 'Indique la cantidad recibida',
                    html: `<input type="number" id="cantidadRecibida" class="swal2-input" placeholder="Cantidad recibida">`,
                    confirmButtonText: 'Calcular vuelto',
                    confirmButtonColor: '#32d7c0',
                    didOpen: () => {
                        const input = document.getElementById('cantidadRecibida');
                        input.focus();

                        handleEnterKey = (e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                Swal.clickConfirm();
                            }
                        };

                        input.addEventListener('keydown', handleEnterKey);
                    },
                    willClose: () => {
                        const input = document.getElementById('cantidadRecibida');
                        if (input && handleEnterKey) {
                            input.removeEventListener('keydown', handleEnterKey);
                        }
                    },
                    preConfirm: () => {
                        const cantidadRecibida = parseFloat(document.getElementById('cantidadRecibida').value);
                        if (isNaN(cantidadRecibida) || cantidadRecibida <= 0) {
                            Swal.showValidationMessage('Por favor, ingresa una cantidad válida');
                        }
                        return {
                            cantidadRecibida
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Aquí ya el primer Swal se cerró y el listener fue removido

                        const {
                            cantidadRecibida
                        } = result.value;

                        let vueltoPesos = cantidadRecibida - parseInt(total_pesos);
                        let vueltoDolares = cantidadRecibida - total_dolares;
                        let vueltoBolivares = cantidadRecibida - total_bolivares;

                        // Ahora mostramos el segundo Swal sin que el Enter anterior lo cierre
                        setTimeout(() => {
                            Swal.fire({
                                title: 'Vuelto calculado',
                                html: `
                                            <p><strong class="text-total text-info">Pesos:</strong> <span style="font-size: 18px">${formatNumber(vueltoPesos.toFixed(2))}</span></p>
                                            <p><strong class="text-total text-danger">Dólares:</strong> <span style="font-size: 18px">${formatNumber(vueltoDolares.toFixed(2))}</span></p>
                                            <p><strong class="text-total text-success">Bolívares:</strong> <span style="font-size: 18px">${formatNumber(vueltoBolivares.toFixed(2))}</span></p>
                                        `,
                                confirmButtonText: 'Cerrar',
                                confirmButtonColor: '#32d7c0',
                                allowEscapeKey: true // Habilita cerrar con ESC
                            });
                        }, 50);
                    }
                });
            }



            document.getElementById('calcularVuelto').addEventListener('click', calcularVuelto)

            let modo = 2

            // Abrir el modal
            openModalButton.addEventListener("click", () => {
                modo = 1
            });

            // Cerrar el modal al hacer clic en el botón de cerrar o en el overlay
            closeModalButton.addEventListener("click", () => {
                modo = 2
            });

            modalOverlay.addEventListener("click", () => {
                modo = 2

            });



            document.addEventListener('click', function(event) {
                if (event.target.closest('.btn-add-to-car') && !event.target.closest('.no-send')) {
                    $('#search').val('')
                    document.getElementById("search").focus();
                    modo = 2
                }


                if (event.target.closest('.delete-scan')) {
                    document.getElementById('result-escaner').innerHTML = '';
                    $('.section-scanner').addClass('hide')
                }

            });

            let ultimo_escaneado = 0



            // Se usa para el modo lector
            function buscarProducto(lectura, modo) {
                const codigo = parseFloat(lectura.trim().replace(/[^0-9]/g, '')); // Eliminar caracteres no numéricos
                console.log(String(codigo))

                const resultado = buscarCodigoFuse(String(codigo));

                console.log(resultado)

                if (resultado.length == 0) {
                    Alerta.toast('error', 'El producto no existe, agrégalo de forma manual.')
                    return
                } else {
                    const datos = productos[lectura.trim()];
                    $('.section-scanner').removeClass('hide');

                    // Construir solo las tasas visibles según tasasMostrar
                    $("#result-escaner").html(`
                <div class="scan-product">
                    <div class="scan-product-header">
                        <span><b>${datos.nombre}</b></span>
                        <span class="scan-stock">${datos.stock} en stock</span>
                    </div>

                    <div class="scan-prices">
                        <span class="scan-price-usd">$${formatNumber(datos.precio_dolar_visible)}</span>
                        <span class="scan-price-cop">${formatearMiles(datos.precio_peso_visible)} P</span>
                        <span class="scan-price-bs">${formatNumber(recortarADosDecimales(datos.precio_bs_visible))} Bs</span>
                    </div>

                    <div class="scan-controls">
                        <input type="number" id="cantidad-scan" class="scan-quantity cantidad-scan" 
                            data-cantidad-id="${datos.id}" value="1">

                        <button class="scan-btn add btn-add-to-car no-send" 
                            id="btn_${datos.id}"
                            data-add-id="${datos.id}"
                            data-codigo="${datos.codigo}"
                            data-P_D="${datos.precio_dolar_visible}"
                            data-P_P="${datos.precio_peso_visible}"
                            data-P_B="${datos.precio_bs_visible}"
                            data-mayor="${datos.mayor}"
                            data-cantidad_por_mayor="${datos.cantidadPaca}">
                            <i class="bx bx-cart-add"></i>
                        </button>

                        <button class="scan-btn remove delete-scan">
                            <i class="bx bx-cart-download"></i>
                        </button>
                    </div>
                </div>
            `);

                    setTimeout(() => {
                        $(`#btn_${datos.id}`).removeClass('no-send');
                    }, 900);
                    ultimo_escaneado = datos.id;
                }

            }




            function representarResultado(resultado) {
                $("#tabla_resultado_codigo_producto").html('')
                if (!Array.isArray(resultado)) return;

                resultado.forEach(item => {
                    const rest = (item.mayor === '1' ?
                        '<span style="margin: 5px;" class="fw-medium text-decoration-none me-2 badge badge-subtle-success">Mayor</span>' :
                        item.stock);

                    $("#tabla_resultado_codigo_producto").append(`
                        <tr>
                            <td>${rest}</td>
                            <td style="font-size: 15px;"><span>${item.nombre}</span></td>
                            <td style="place-content: center" class="text-center text-total text-success">
                                <span>${formatNumber(item.precio_dolar_visible)}$</span>
                            </td>
                            <td style="place-content: center" class="text-center text-total text-info">
                                <span>${formatearMiles(formatPeso(item.precio_peso_visible))} Cop</span>
                            </td>
                            <td style="place-content: center" class="text-center text-total text-danger">
                                <span>${formatNumber(recortarADosDecimales(item.precio_bs_visible))} Bs</span>
                            </td>
                            <td class="text-center">
                                <input 
                                    data-nombre='${item.nombre}' 
                                    data-precios='${item.precio_peso_visible}/${item.precio_dolar_visible}/${item.precio_bs_visible}' 
                                    type="number" 
                                    style="width: 70px; text-align: center;" 
                                    class="form-control-sm cantidad-input" 
                                    data-cantidad-id="${item.id}" 
                                    value="1">
                            </td>
                            <td style="place-content: center" class="text-center">
                                <button class="btn btn-sm btn-success btn-add-to-car" 
                                    data-add-id="${item.id}"
                                    data-codigo="${item.codigo || ''}"
                                    data-P_D="${item.precio_dolar_visible}"
                                    data-P_P="${item.precio_peso_visible}"
                                    data-P_B="${item.precio_bs_visible}"
                                    data-mayor="${item.mayor}"
                                    data-cantidad_por_mayor="${item.cantidadPaca}">
                                    <i class="fa fa-shopping-cart"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });
            }






            // Enter para escaneo
            document.addEventListener('keyup', function(event) {
                $('button').blur();
                if (modo == 2 && ultimo_escaneado != 0 && event.key == 'Enter') {
                    $('#btn_' + ultimo_escaneado).click();
                }
            });
            // Enter para escaneo





            document.addEventListener('keyup', function(event) {
                if (event.target.closest('.cantidad-input') && !event.target.closest('.cantidad-scan')) {
                    const input = event.target.closest('.cantidad-input');

                    const cantidad = input.value;
                    const nombre = input.getAttribute('data-nombre');

                    let precio_dolar = parseFloat(input.getAttribute('data-precios').split('/')[1]);
                    let precio_peso = parseFloat(input.getAttribute('data-precios').split('/')[0]);
                    let precio_bs = parseFloat(input.getAttribute('data-precios').split('/')[2]);

                    precio_peso = Math.round(precio_peso / 100) * 100

                    const modal_footer = document.getElementById('modal-footer')
                    modal_footer.classList.remove('hide')


                    let carrito_total_pesos = total_pesos + (precio_peso * cantidad);
                    let carrito_total_dolar = total_dolares + (precio_dolar * cantidad);
                    let carrito_total_bolivar = total_bolivares + (precio_bs * cantidad);


                    modal_footer.innerHTML = `
                           <div class="d-flex" style='gap: 15px;'>
                         <div class="me-2 vista_precio">TOTAL CARRITO: </div>
                        <div class="me-2 vista_precio text-success">$${recortarADosDecimales(carrito_total_dolar)}</div>
                        <div class="me-2 vista_precio text-info">${formatNumber(formatPeso(carrito_total_pesos))} Cop</div>
                        <div class="me-2 vista_precio text-danger">${formatNumber(recortarADosDecimales(carrito_total_bolivar))} Bs</div>
                        </div>

                        <div class="d-flex" style='gap: 15px;'>
                         <div class="me-2 vista_precio">${nombre}</div>
                        <div class="me-2 vista_precio text-success">$${recortarADosDecimales(precio_dolar * cantidad)}</div>
                        <div class="me-2 vista_precio text-info">${formatNumber(precio_peso * cantidad)} Cop</div>
                        <div class="me-2 vista_precio text-danger">${formatNumber(recortarADosDecimales(precio_bs * cantidad))} Bs</div>
                        </div>
                        `
                }
            })


            $(document).on('keyup', '#search', function() {
                var nombreProducto = $(this).val();
                if (nombreProducto.length > 2) {

                    let resultados = buscarConFuse(nombreProducto)
                    representarResultado(resultados)


                } else {
                    // vacia la tabla
                    $("#tabla_resultado_codigo_producto").html('');
                    const modal_footer = document.getElementById('modal-footer')
                    modal_footer.innerHTML = ''
                    modal_footer.classList.add('hide')
                }
            });




            // * IndexedBD //
            // Inicializar base IndexedDB con Dexie
            const db = new Dexie("POS_DB");

            // Definir estructura
            db.version(1).stores({
                carritoActivo: 'id', // clave primaria será el id del producto
                carritosVenta: 'id', // para ventas ya procesadas
                carritosReservados: 'id' // para reservados
            });
            // * IndexedBD //


            let carritoActivo = {};

            // Cargar carrito activo desde IndexedDB al iniciar
            (async function cargarCarritoInicial() {
                const items = await db.carritoActivo.toArray();
                carritoActivo = items.reduce((obj, item) => {
                    obj[item.id] = item;
                    return obj;
                }, {});
                actualizarCarritoJs();
            })();

            async function actualizarCarritoActivo() {
                const items = await db.carritoActivo.toArray();
                carritoActivo = items.reduce((obj, item) => {
                    obj[item.id] = item;
                    return obj;
                }, {});
                actualizarCarritoJs();
            }


            // REVISADO
            async function addtocarJS(id, dolarventa_p, pesoventa_p, bolivarventa_p, mayor, cantidad_por_mayor, cantidad_scann = null) {
                const inputCantidad = document.querySelector(`input[data-cantidad-id="${id}"]`);
                let cant = inputCantidad ? parseFloat(inputCantidad.value) : 1;


                // verifica que cantidad_scann sea un numero
                if (isNaN(cant) || cant <= 0) {
                    Alerta.toast('error', 'Cantidad inválida. Debe ser un número mayor a 0.');
                    return;
                }


                if (cantidad_scann != null) cant = parseFloat(cantidad_scann);

                if (!productos_por_id[id]) {
                    console.error(`Producto con ID ${id} no encontrado.`);
                    return;
                }
                const idPedido = id.toString(); // Asegurarse de que el ID sea una cadena

                const producto = productos_por_id[idPedido];

                if (carritoActivo[idPedido]) {
                    carritoActivo[idPedido].qty += cant;
                } else {
                    carritoActivo[idPedido] = {
                        id: idPedido,
                        name: producto.nombre,
                        price_C: parseFloat(producto.price_C),
                        price_C_Bs: parseFloat(producto.price_C_Bs),
                        price_C_Cop: parseFloat(producto.price_C_Cop),
                        price: parseFloat(dolarventa_p),
                        pricePeso: parseFloat(pesoventa_p),
                        priceBolivar: parseFloat(bolivarventa_p),
                        qty: cant,
                        mayor: mayor,
                        cantidadPaca: cantidad_por_mayor
                    };
                }

                if (carritoActivo[idPedido].qty == 0) {
                    await db.carritoActivo.delete(idPedido);
                } else {
                    await db.carritoActivo.put(carritoActivo[idPedido]);
                }

                $("#tabla_resultado_codigo_producto").html('');
                actualizarCarritoJs();
                $("#search").val('');
            }

            function vaciarCarritoJs() {
                if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                    return false;
                }
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción vaciará tu carrito.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, vaciar',
                    cancelButtonText: 'Cancelar'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        await db.carritoActivo.clear();
                        carritoActivo = {};
                        actualizarCarritoJs();
                        Alerta.toast('success', 'Carrito vaciado correctamente');
                    }
                });
            }


            async function actualizarCarritoJs() {

                $("#tabla-carrito tbody").html('');
                $("#tabla-carrito tfoot").html('');

                // Para sumar o restar unidades a un producto específico

                total_pesos = total_dolares = total_bolivares = 0;


                const items = Object.values(carritoActivo);

                if (items.length > 0) {
                    items.forEach(element => {
                        let subtotalPeso = element.pricePeso * element.qty;
                        let subtotalBolivar = element.priceBolivar * element.qty;
                        let subtotalDolar = element.price * element.qty;
                        subtotalPeso = Math.round(subtotalPeso);

                        $("#tabla-carrito tbody").append(`
                        <tr>
                            <td class="ac-c">${element.qty}</td>
                            <td class="ac-c">${element.name}</td>
                            <td class="ac-c">${formatearMiles(subtotalPeso)} Cop</td>
                            <td class="ac-c">${formatNumber(subtotalBolivar)} Bs</td>
                            <td class="ac-c">$${formatNumber(subtotalDolar)}</td>
                            <td class="ac-c">
                                <button class="btn btn-sm btn-outline-success" onclick="actualizarProductosCantidad('${element.id}', 'sumar')"><ion-icon style="font-size: 12px" name="arrow-up"></ion-icon></button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="actualizarProductosCantidad('${element.id}', 'restar')"><ion-icon style="font-size: 12px" name="arrow-down"></ion-icon></button>
                                <button class="btn btn-sm btn-outline-danger" onclick="quitarProductoJs('${element.id}')"><ion-icon style="font-size: 12px"  name="trash-outline"></ion-icon></button>
                            </td>
                        </tr>
                    `);

                        total_pesos += subtotalPeso;
                        total_dolares += subtotalDolar;
                        total_bolivares += subtotalBolivar;
                    });
                    // elimina los decimales de total_pesos
                    total_pesos = Math.round(total_pesos);

                    $("#tabla-carrito tfoot").html(`
                    <tr>
                        <td></td>
                        <td><b>TOTAL:</b></td>
                        <td class="text-info">${formatearMiles(total_pesos)} Cop</td>
                        <td class="text-danger">${formatNumber(total_bolivares)} Bs</td>
                        <td class="text-success">$${formatNumber(total_dolares)}</td>
                        <td></td>
                    </tr>
                    `);

                    $('#botones_acciones').removeClass('hide');
                } else {
                    $('#botones_acciones').addClass('hide');
                }
            }

            // Para sumar o restar unidades a un producto específico
            async function actualizarProductosCantidad(id, accion) {
                if (id && accion) {
                    if (carritoActivo[id]) {
                        if (accion === 'sumar') {
                            carritoActivo[id].qty += 1;
                            await db.carritoActivo.put(carritoActivo[id]);
                        } else if (accion === 'restar') {
                            carritoActivo[id].qty -= 1;
                            if (carritoActivo[id].qty <= 0) {
                                delete carritoActivo[id]; // también quitar de memoria
                                await deleteFromIndexedDB('carritoActivo', id);
                            } else {
                                await db.carritoActivo.put(carritoActivo[id]);
                            }
                        }
                        actualizarCarritoJs();
                    }
                }
            }

            // Eliminar un producto del carrito
            async function quitarProductoJs(id) {
                await deleteFromIndexedDB('carritoActivo', id);
                await actualizarCarritoActivo()
            }
            // Eliminar un producto del carrito




            // ======================
            // FORMATEO DE NÚMEROS
            // ======================
            function formatNumber(num) {
                return parseFloat(num).toLocaleString('es-VE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // ======================
            // PROCESAR PEDIDO (VENTA)
            // ======================
            async function procesarPedido(metodoPago, despacho, cliente = {
                nombre: '',
                cedula: '',
                telefono: ''
            }) {
                if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                    console.warn("No hay carrito activo para procesar.");
                    return false;
                }

                // proceso extra de verificar que no existan duplicados

                const idPedido = String(Date.now()) + '-' + Math.floor(Math.random() * 10000);
                const datosCliente = {
                    nombre: cliente.nombre || "",
                    cedula: cliente.cedula || "",
                    telefono: cliente.telefono || ""
                };

                let valorFinalBs = 0;
                let valorFinalCop = 0;
                let valorFinalVenta = 0;

                for (let k in carritoActivo) {
                    if (carritoActivo.hasOwnProperty(k)) {
                        let prod = carritoActivo[k];
                        console.log('PRODU' + prod)
                        // aquí asumo que quieres precio * cantidad
                        valorFinalVenta += prod.price * (prod.qty ?? 1);
                        valorFinalBs += prod.priceBolivar * (prod.qty ?? 1);
                        valorFinalCop += prod.pricePeso * (prod.qty ?? 1);
                    }
                }
                console.log("ValorFinalVenta:", valorFinalBs);
                console.log("ValorFinalVenta:", valorFinalCop);


                let nuevoPedido = {
                    id: idPedido,
                    metodoPago,
                    despacho, // 1= Venta normal, 2= crédito, 3= descuento
                    valorFinalVenta,
                    valorFinalBs,
                    valorFinalCop,
                    datosCliente,
                    productos: carritoActivo,
                    status: 'sin enviar',
                    respuesta: 'ndp',
                    usuario_id: "<?php echo $usuario_id ?>",
                    usuario_nombre: "<?php echo $nombreUsuario ?>"
                };

                // =========================================================================
                // 2. ¡AQUÍ LO LLAMAS! - VALIDACIÓN ANTES DE GUARDAR
                // =========================================================================
                const verificacion = validarEstructuraVenta(nuevoPedido);
                if (!verificacion.valido) {
                    console.error("Pedido rechazado por estructura inválida:", verificacion.error);
                    Alerta.toast('error', `Error al armar venta: ${verificacion.error}`);
                    return false; // Frenamos por completo y no limpiamos el carrito de la pantalla
                }



                // Guardar en IndexedDB
                try {
                    await db.carritosVenta.put(nuevoPedido);
                    //  console.log(`Pedido ${idPedido} guardado en IndexedDB`);


                    // Limpiar carrito activo
                    carritoActivo = {};
                    await db.carritoActivo.clear();

                    $("#tabla-carrito tbody").html('');
                    $("#tabla-carrito tfoot").html('');

                    // SOlicitar impresion
                    confirmar_e_imprimir(nuevoPedido, 'BS')

                    // Llamar función de envío (puede sincronizar cuando haya internet)
                    enviarPedidosProcesados();
                    return true;


                } catch (e) {
                    console.error("Error guardando en IndexedDB, ", e);
                }


            }

            document.getElementById('btn-vender').addEventListener('click', function() {
                confirmarVenta('venta');
            });


            // ======================
            // GUARDAR CARRITO RESERVADO
            // ======================
            async function reservarCarrito() {
                if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                    console.warn("No hay carrito activo para procesar.");
                    return false;
                }

                Swal.fire({
                    title: 'Identifique el carrito para reservar',
                    html: `<input id="cliente" class="form-control" placeholder="Identificador (puede ser el nombre del cliente)">`,
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: '#32d7c0',
                    customClass: {
                        popup: 'swal-metodo-pago'
                    },
                    didOpen: () => Swal.getConfirmButton().setAttribute('id', 'btnVender'),
                    preConfirm: () => {
                        const nombre = document.getElementById('cliente').value.trim();
                        if (!nombre) Swal.showValidationMessage('Por favor, ingresa el identificador del carrito');
                        return nombre;
                    }
                }).then(async (result) => {
                    if (!result.isConfirmed) return;

                    const cliente = result.value;
                    const idPedido = String(Date.now()) + '-' + Math.floor(Math.random() * 10000);

                    let nuevoPedidoReservado = {
                        cliente,
                        id: idPedido,
                        productos: carritoActivo
                    };

                    // Guardar en IndexedDB
                    try {
                        await db.carritosReservados.put(nuevoPedidoReservado);
                        console.log(`Carrito reservado ${idPedido} guardado en IndexedDB`);
                    } catch (e) {
                        console.error("Error guardando en IndexedDB:", e);
                    }
                    await db.carritoActivo.clear();
                    carritoActivo = {};

                    $("#tabla-carrito tbody").html('');
                    $("#tabla-carrito tfoot").html('');
                    Alerta.toast('success', 'Carrito reservado correctamente');

                    actualizarProductosReservados();
                });
            }

            document.getElementById('btn-reservar').addEventListener('click', function() {
                reservarCarrito();
            });

            // ======================
            // ENVIAR PEDIDOS PROCESADOS
            // ======================
            /**
             * Valida de forma estricta que el pedido cumpla con la estructura exacta
             * y tipos de datos que el backend PHP espera recibir.
             * * @param {Object} pedido - El objeto del carrito/pedido a validar.
             * @returns {Object} { valido: boolean, error: string|null }
             */
            function validarEstructuraVenta(pedido) {
                if (!pedido || typeof pedido !== 'object') {
                    return {
                        valido: false,
                        error: "El formato del pedido no es un objeto válido."
                    };
                }

                // 1. VALIDACIÓN DE CAMPOS DE LA ORDEN PRINCIPAL
                if (!pedido.id) {
                    return {
                        valido: false,
                        error: "Falta el ID único del pedido (id)."
                    };
                }
                if (!pedido.metodoPago || typeof pedido.metodoPago !== 'string') {
                    return {
                        valido: false,
                        error: `Pedido [ID: ${pedido.id}]: El 'metodoPago' es obligatorio y debe ser texto.`
                    };
                }
                if (!pedido.despacho) {
                    return {
                        valido: false,
                        error: `Pedido [ID: ${pedido.id}]: El campo 'despacho' es obligatorio.`
                    };
                }

                // Validar que los montos finales existan y sean numéricos
                if (pedido.valorFinalBs === undefined || pedido.valorFinalBs === null || isNaN(Number(pedido.valorFinalBs))) {
                    return {
                        valido: false,
                        error: `Pedido [ID: ${pedido.id}]: 'valorFinalBs' es inválido o no numérico.`
                    };
                }
                if (pedido.valorFinalCop === undefined || pedido.valorFinalCop === null || isNaN(Number(pedido.valorFinalCop))) {
                    return {
                        valido: false,
                        error: `Pedido [ID: ${pedido.id}]: 'valorFinalCop' es inválido o no numérico.`
                    };
                }

                // 2. VALIDACIÓN ESTRICTA DE PRODUCTOS
                // Convierte el objeto indexado (carritoActivo) o un array a una lista iterable
                const listaProductos = Array.isArray(pedido.productos) ?
                    pedido.productos :
                    Object.values(pedido.productos || {});

                if (listaProductos.length === 0) {
                    return {
                        valido: false,
                        error: `Pedido [ID: ${pedido.id}]: El listado de 'productos' está vacío o no es válido.`
                    };
                }

                for (let i = 0; i < listaProductos.length; i++) {
                    const prod = listaProductos[i];
                    const path = `Pedido [ID: ${pedido.id}] -> Producto [Index: ${i}]`;

                    if (!prod.id) return {
                        valido: false,
                        error: `${path}: Falta el 'id' del producto.`
                    };
                    if (!prod.name) return {
                        valido: false,
                        error: `${path}: Falta el 'name' (nombre) del producto.`
                    };

                    // Validar cantidad
                    if (prod.qty === undefined || prod.qty === null || isNaN(Number(prod.qty)) || Number(prod.qty) <= 0) {
                        return {
                            valido: false,
                            error: `${path}: La cantidad 'qty' debe ser un número mayor a cero.`
                        };
                    }

                    // Mapeo de los precios requeridos por la función agregarAlCarrito() en el backend
                    const preciosRequeridos = ['price_C', 'price_C_Bs', 'price_C_Cop', 'price', 'pricePeso', 'priceBolivar'];

                    for (let precio of preciosRequeridos) {
                        if (prod[precio] === undefined || prod[precio] === null || isNaN(Number(prod[precio]))) {
                            return {
                                valido: false,
                                error: `${path}: El campo de precio '${precio}' es obligatorio y debe ser numérico.`
                            };
                        }
                    }

                    // Validación especial para ventas al Mayor (El back evalúa si es igual a '1')
                    if (prod.mayor === '1' || prod.mayor === 1) {
                        if (prod.cantidadPaca === undefined || prod.cantidadPaca === null || isNaN(Number(prod.cantidadPaca))) {
                            return {
                                valido: false,
                                error: `${path}: Está marcado como venta al mayor, pero 'cantidadPaca' es inválido o no existe.`
                            };
                        }
                    }
                }

                // 3. VALIDACIÓN DE DATOS DE CLIENTE (Crítico si despacho == '2' (Crédito))
                const datosCliente = pedido.datosCliente || {};

                if (pedido.despacho === '2' || pedido.despacho === 2) {
                    if (!datosCliente.cedula || String(datosCliente.cedula).trim() === '') {
                        return {
                            valido: false,
                            error: `Pedido [ID: ${pedido.id}]: La cédula del cliente es obligatoria para ventas a crédito.`
                        };
                    }
                    if (!datosCliente.nombre || String(datosCliente.nombre).trim() === '') {
                        return {
                            valido: false,
                            error: `Pedido [ID: ${pedido.id}]: El nombre del cliente es obligatorio para ventas a crédito.`
                        };
                    }
                }

                return {
                    valido: true,
                    error: null
                };
            }
            async function enviarPedidosProcesados() {
                let pedidosIndexedDB = await db.carritosVenta.toArray();
                let pendientes = pedidosIndexedDB.filter(p => p.status === 'sin enviar');

                if (pendientes.length === 0) {
                    return;
                }

                comprobarConexion(async function(hayInternet) {
                    if (!hayInternet) {
                        Alerta.toast('warning', 'No hay conexión a internet. Las ventas se guardarán localmente.');
                        btnConfirmarDespacho.disabled = false;
                        actualizarProductosSinEnviar();
                        return;
                    }

                    let exitos = 0;
                    let fallos = 0;

                    for (let pedido of pendientes) {
                        try {
                            let response = await enviarUnPedido(pedido);

                            if (response.status) {
                                let verificadoEnBD = await validacionDeVentasAsincrona(pedido);

                                if (verificadoEnBD) {
                                    pedido.status = 'enviado';
                                    pedido.respuesta = JSON.stringify(response);
                                    await db.carritosVenta.put(pedido);
                                    exitos++;
                                } else {
                                    console.error(`El servidor dijo "éxito" pero el pedido ID ${pedido.id} NO se encontró en la BD.`);
                                    Alerta.toast('error', `Error de consistencia en pedido ${pedido.id}. Se mantendrá local.`);
                                    fallos++;
                                }
                            } else {
                                console.error(`Error en pedido ID ${pedido.id}:`, response.data);
                                Alerta.toast('error', `Error en pedido ${pedido.id}: ${response.data}`);
                                fallos++;
                            }
                        } catch (error) {
                            console.error(`Error crítico de red/servidor en pedido ${pedido.id}:`, error);
                            fallos++;
                            break;
                        }
                    }

                    if (exitos > 0 && fallos === 0) {
                        Alerta.toast('success', 'Todos los pedidos se sincronizaron y verificaron correctamente.');
                    } else if (exitos > 0 && fallos > 0) {
                        Alerta.toast('warning', `Sincronización parcial: ${exitos} verificados, ${fallos} retenidos o fallidos.`);
                    }

                    actualizarProductosSinEnviar();
                    actualizarProductosEnviados();
                    btnConfirmarDespacho.disabled = false;
                    cargarUltimasOrdenes();
                });
            }
            // NUEVA FUNCIÓN DE VALIDACIÓN ASÍNCRONA
            async function validacionDeVentasAsincrona(pedido) {
                try {
                    const respuesta = await fetch(base_url + 'accion_carta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'verificarExistenciaPedido',
                            pedido_id: pedido.id // Enviamos el ID único que identifica este pedido en tu sistema
                        })
                    });

                    if (!respuesta.ok) return false;

                    const resultado = await respuesta.json();

                    // Retorna estrictamente true o false dependiendo de la respuesta real del backend
                    return resultado.existe === true;

                } catch (error) {
                    console.error(`Fallo la verificación de seguridad para el pedido ${pedido.id}:`, error);
                    // Ante cualquier duda o caída de red en este punto, devolvemos false para proteger los datos
                    return false;
                }
            }






            // Función auxiliar para aislar la petición fetch de un solo pedido
            async function enviarUnPedido(pedido) {
                // Nota: Ahora mandamos el objeto pedido directamente en un array, 
                // así no tienes que cambiar drásticamente tu PHP, seguirá recibiendo un array de 1 elemento.
                let respuesta = await fetch(base_url + 'accion_carta.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'enviarPedidos',
                        pedidos: JSON.stringify([pedido]), // Lo envolvemos en un array [ ]
                    }),
                });

                let texto = await respuesta.text();
                return JSON.parse(texto);
            }

            // ======================
            // MOSTRAR CARRITOS RESERVADOS
            // ======================
            async function actualizarProductosReservados() {
                document.getElementById('ul-productos-reservados').innerHTML = '';
                document.getElementById('cantidad-reservados').innerHTML = '';

                // Leer desde IndexedDB
                let items = await db.carritosReservados.toArray();
                items = items.reverse();

                if (items.length > 0) {
                    document.getElementById('cantidad-reservados').innerHTML = `<span class="badge bg-warning">${items.length}</span>`;
                    items.forEach(element => {
                        const fecha = new Date(parseInt(element.id));
                        const fechaEspañol = fecha.toLocaleDateString('es-VE', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                        const cliente = element.cliente || 'Cliente no especificado';

                        let productosHTML = '';
                        let totalPesos = 0;
                        let totalDolares = 0;
                        let totalBolivares = 0;
                        Object.values(element.productos).forEach(prod => {
                            productosHTML += `<span class="badge" style="background:rgba(45,212,160,0.12);color:var(--dash-mint);">${prod.name} (x${prod.qty})</span> `;
                            totalPesos += prod.pricePeso * prod.qty;
                            totalDolares += prod.price * prod.qty;
                            totalBolivares += prod.priceBolivar * prod.qty;
                        });

                        let html = `
                <li class="list-none item-reservado mb-2">
                    <div class="item-reservado-header p-3">
                        <div class="avatar">
                            <ion-icon name="briefcase-outline"></ion-icon>
                        </div>
                        <div>
                            <p class="m-0 p-0">${cliente}</p>
                            <small>${fechaEspañol}</small>
                        </div>
                    </div>
                    <div class="item-reservado-body pl-4 pb-3 row" >
                        <div class="d-flex justify-content-between flex-column col-lg-9">
                            <div>${productosHTML}</div>
                            <div class="d-flex mt-2 totales-pendiente">
                                <p>TOTALES:</p>
                                <p class="text-info">${formatNumber(totalPesos)} P</p>
                                <p class="text-danger">${formatNumber(totalBolivares)} Bs</p>
                                <p class="text-success">$${formatNumber(totalDolares)}</p>
                            </div>
                        </div>
                        <div class="btn-list-item text-center d-flex pr-4 col-lg-3 text-end">
                            <button class="btn btn-success" onclick="retomarCarrito('${element.id}')">Retomar carrito</button>
                            <button class="btn btn-danger" onclick="eliminarCarritoReservado('${element.id}')">Eliminar</button>
                        </div>
                    </div>
                </li>
            `;
                        document.getElementById('ul-productos-reservados').innerHTML += html;
                    });
                }
            }

            // ─── Exportar Enviados a Excel ───
            async function exportarEnviadosExcel() {
                items = await getAllFromIndexedDB('carritosVenta');
                const inicioHoy = new Date();
                inicioHoy.setHours(0, 0, 0, 0);
                const finHoy = new Date();
                finHoy.setHours(23, 59, 59, 999);
                items = items.filter(p => {
                    if (p.status !== 'enviado') return false;
                    const ts = parseInt(String(p.id).split('-')[0]);
                    if (isNaN(ts)) return false;
                    const fecha = new Date(ts);
                    return fecha >= inicioHoy && fecha <= finHoy;
                });

                if (items.length === 0) {
                    Alerta.toast('warning', 'No hay datos para exportar.');
                    return;
                }

                let metodosPago = {
                    option1: '(1) Punto',
                    option2: '(5) Pago Movil',
                    option3: '(6) Transferencia',
                    option4: '(4) Efectivo',
                    option5: '(7) Dólares',
                    option6: '(3) Pesos',
                    option7: '(2) BioPago'
                };

                function formatNumber(n) {
                    return Number(n).toLocaleString('es-VE', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                function formatearMiles(n) {
                    return Number(n).toLocaleString('es-CO', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });
                }

                let csv = '\uFEFF';
                csv += 'Id Registro,Monto,Moneda,Tipopago,Respuesta,Status,Usuario\n';

                items.forEach(element => {
                    const metodoPago = element.metodoPago;
                    let totalPesos = 0,
                        totalDolares = 0,
                        totalBolivares = 0;

                    Object.values(element.productos || {}).forEach(prod => {
                        totalPesos += prod.pricePeso * prod.qty;
                        totalDolares += prod.price * prod.qty;
                        totalBolivares += prod.priceBolivar * prod.qty;
                    });

                    let monedas = {
                        option1: 'Bs',
                        option2: 'Bs',
                        option3: 'Bs',
                        option4: 'Bs',
                        option5: 'Usd',
                        option6: 'Cop',
                        option7: 'Bs'
                    };
                    let moneda = monedas[metodoPago] || 'Bs';
                    let monto;
                    switch (moneda) {
                        case 'Bs':
                            monto = formatNumber(totalBolivares);
                            break;
                        case 'Cop':
                            monto = formatearMiles(totalPesos);
                            break;
                        case 'Usd':
                            monto = '$ ' + formatNumber(totalDolares);
                            break;
                    }

                    let respuesta = JSON.parse(element.respuesta || '{}');
                    let respStr = (respuesta.data || '') + ' - ' + (respuesta.ids || []);
                    let statusStr = respuesta.status === true ? 'OK' : 'ERROR';

                    let idReg = '#';
                    let tipoPago = metodosPago[metodoPago] ?? 'PENDIENTE';
                    let usuario = element.usuario_nombre || '';

                    csv += `${idReg},${monto},${moneda},${tipoPago},"${respStr.replace(/"/g,'""')}",${statusStr},${usuario}\n`;
                });

                const blob = new Blob([csv], {
                    type: 'text/csv;charset=utf-8;'
                });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'enviados_' + new Date().toISOString().slice(0, 10) + '.csv';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            }

            // ======================
            // SINCRONIZACIÓN AUTOMÁTICA
            // ======================

            // Inicializar al cargar
            actualizarProductosReservados();
            actualizarProductosEnviados();
            enviarPedidosProcesados();


            //here

            async function deleteFromIndexedDB(storeName, id) {
                try {
                    await db.table(storeName).delete(String(id));
                    console.log(`Registro con ID ${id} eliminado de ${storeName}`);
                } catch (err) {
                    console.error(`Error eliminando de ${storeName}`, err);
                }
            }

            // Obtiene un registro por clave primaria
            async function getFromIndexedDB(storeName, id) {
                try {
                    return await db.table(storeName).get(String(id)); // forzar string
                } catch (error) {
                    console.error(`Error obteniendo registro de ${storeName}`, error);
                    return null;
                }
            }

            // Obtiene todos los registros de un store
            async function getAllFromIndexedDB(storeName) {
                try {
                    return await db.table(storeName).toArray();
                } catch (error) {
                    console.error(`Error obteniendo todos los registros de ${storeName}`, error);
                    return [];
                }
            }


            // RETOMAR CARRITO RESERVADO
            async function retomarCarrito(carritoId) {
                try {
                    const key = String(carritoId);
                    const carritoData = await getFromIndexedDB('carritosReservados', key);

                    if (!carritoData) {
                        Alerta.toast('error', 'Carrito reservado no encontrado');
                        return;
                    }

                    // 1) Limpiar store de carrito activo en IndexedDB
                    await db.carritoActivo.clear();

                    // 2) Reconstruir carritoActivo en memoria y preparar puts
                    carritoActivo = {}; // reset en memoria
                    const entries = Object.entries(carritoData.productos || {}); // [ [id, prod], ... ]

                    const puts = entries.map(([prodId, prod]) => {
                        const idStr = String(prod.id ?? prodId); // asegurar string consistente
                        const record = {
                            ...prod,
                            id: idStr,
                            qty: Number(prod.qty) || 1
                        };
                        // actualizar memoria
                        carritoActivo[idStr] = record;
                        // devolver la promesa put (no await aquí)
                        return db.carritoActivo.put(record);
                    });

                    // 3) Esperar que terminen todas las operaciones de guardado
                    await Promise.all(puts);

                    // 4) Borrar el carrito reservado
                    await deleteFromIndexedDB('carritosReservados', key);

                    // Opcional: debug (quita en producción)
                    console.log('carritoActivo (en memoria) después de retomar:', carritoActivo);
                    console.log('Contenido de db.carritoActivo:', await db.carritoActivo.toArray());

                    // 5) Actualizar la UI solo ahora que la memoria y la DB están listas
                    await actualizarCarritoJs();

                    // 6) Actualizar lista de reservados y UI general
                    await actualizarProductosReservados();
                    Alerta.toast('success', 'Carrito retomado correctamente');
                    document.getElementById('home-tab')?.click();

                } catch (error) {
                    console.error("Error al retomar carrito:", error);
                    Alerta.toast('error', 'Error al retomar carrito.');
                }
            }





            // ELIMINAR CARRITO RESERVADO
            async function eliminarCarritoReservado(carritoId) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción eliminará el carrito reservado.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        await deleteFromIndexedDB('carritosReservados', carritoId);

                        actualizarProductosReservados();
                        Alerta.toast('success', 'Carrito eliminado correctamente');
                    }
                });
            }
            // ELIMINAR CARRITO RESERVADO


            // MOSTRAR PRODUCTOS ENVIADOS
            async function actualizarProductosEnviados() {
                document.getElementById('table-productos-enviados').innerHTML = '';
                document.getElementById('cantidad-enviados').innerHTML = '';

                items = await getAllFromIndexedDB('carritosVenta');
                const inicioHoy = new Date();
                inicioHoy.setHours(0, 0, 0, 0);
                const finHoy = new Date();
                finHoy.setHours(23, 59, 59, 999);
                items = items.filter(p => {
                    if (p.status !== 'enviado') return false;
                    const ts = parseInt(String(p.id).split('-')[0]);
                    if (isNaN(ts)) return false;
                    const fecha = new Date(ts);
                    return fecha >= inicioHoy && fecha <= finHoy;
                });
                items.reverse();
                let totalesBs = 0;
                let totalesCop = 0;
                let totalesUsd = 0;

                if (items.length > 0) {
                    document.getElementById('cantidad-enviados').innerHTML = `<span class="badge bg-info">${items.length}</span>`;

                    items.forEach(element => {
                        const fecha = new Date(parseInt(element.id));
                        const fechaHoy = new Date();
                        // comparar fechas antes de proceder



                        const fechaEspañol = fecha.toLocaleDateString('es-VE', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        const metodoPago = element.metodoPago;
                        let productosHTML = '';
                        let totalPesos = 0,
                            totalDolares = 0,
                            totalBolivares = 0;

                        Object.values(element.productos || {}).forEach(prod => {
                            productosHTML += `<span class="badge" style="background:rgba(45,212,160,0.12);color:var(--dash-mint);">${prod.name} (x${prod.qty})</span> `;
                            totalPesos += prod.pricePeso * prod.qty;
                            totalDolares += prod.price * prod.qty;
                            totalBolivares += prod.priceBolivar * prod.qty;
                        });


                        let tipoVenta = {
                            1: 'Venta',
                            2: 'Crédito',
                            3: 'Descuento'
                        }

                        let monedas = {
                            option1: 'Bs',
                            option2: 'Bs',
                            option3: 'Bs',
                            option4: 'Bs',
                            option5: 'Usd',
                            option6: 'Cop',
                            option7: 'Bs'
                        }
                        let moneda = monedas[metodoPago];
                        let monto;

                        switch (moneda) {
                            case 'Bs':
                                monto = formatNumber(totalBolivares);
                                totalesBs += totalBolivares;
                                break;
                            case 'Cop':
                                monto = formatearMiles(totalPesos);
                                totalesCop += totalPesos;
                                break;
                            case 'Usd':
                                monto = formatNumber(totalDolares);
                                totalesUsd += totalDolares;
                                break;
                        }
                        let respuesta = JSON.parse(element.respuesta)
                        let result = respuesta.status == true ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">ERROR</span>'

                        let html = `
                        <tr>
                                <td>ID.BD#${respuesta.ids}</td>
                                <td>${monto}</td>
                                <td>${moneda}</td>
                                <td>${metodosPago[metodoPago] ?? 'PENDIENTE'}</b></td>
                                <td><small>${respuesta.data}</small></td>
                                <td>${result}</td>
                                <td>${element.usuario_nombre}</td>
                        </tr>
                        `;
                        document.getElementById('table-productos-enviados').innerHTML += html;
                    });


                    document.getElementById('table-productos-enviados').innerHTML += `
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>TOTALES:</td>
                        <td>${formatNumber(totalesBs)} Bs</td>
                        <td>${formatearMiles(totalesCop)} Cop</b></td>
                        <td>$ ${formatNumber(totalesUsd)}</td>
                    </tr>
                    `;

                } else {
                    document.getElementById('table-productos-enviados').innerHTML = '<tr><td colspa="5">No hay pedidos enviados aún.</td></tr>';
                }
            }

            // MOSTRAR PRODUCTOS SIN ENVIAR
            async function actualizarProductosSinEnviar() {
                document.getElementById('ul-productos-sin-enviar').innerHTML = '';
                document.getElementById('cantidad-no-enviada').innerHTML = '';

                items = await getAllFromIndexedDB('carritosVenta');
                items = items.filter(p => p.status === 'sin enviar');
                items.reverse();

                if (items.length > 0) {
                    document.getElementById('cantidad-no-enviada').innerHTML = `<span class="badge bg-danger">${items.length}</span>`;

                    items.forEach(element => {
                        const fecha = new Date(parseInt(element.id));
                        const fechaEspañol = fecha.toLocaleDateString('es-VE', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });

                        const metodoPago = element.metodoPago;
                        let productosHTML = '';
                        let totalPesos = 0,
                            totalDolares = 0,
                            totalBolivares = 0;

                        Object.values(element.productos || {}).forEach(prod => {
                            productosHTML += `<span class="badge" style="background:rgba(45,212,160,0.12);color:var(--dash-mint);">${prod.name} (x${prod.qty})</span> `;
                            totalPesos += prod.pricePeso * prod.qty;
                            totalDolares += prod.price * prod.qty;
                            totalBolivares += prod.priceBolivar * prod.qty;
                        });

                        let tipoVenta = {
                            1: 'Venta',
                            2: 'Crédito',
                            3: 'Descuento'
                        }

                        let html = `
                <li class="list-none item-reservado mb-2">
                    <div class="item-reservado-header p-3">
                        <div class="avatar"><ion-icon name="briefcase-outline"></ion-icon></div>
                        <div>
                            <p class="m-0 p-0">${fechaEspañol}</p>
                            <small>Método de pago: <b class="text-success">${metodosPago[metodoPago] ?? 'PENDIENTE'}</b> - (${tipoVenta[element.despacho]})</small> 
                        </div>
                    </div>
                    <div class="item-reservado-body pl-4 pb-3 row">
                        <div class="d-flex justify-content-between flex-column col-lg-9">
                            <div>${productosHTML}</div>
                            <div class="d-flex mt-2 totales-pendiente">
                                <p>TOTALES:</p>
                                <p class="text-info">${formatearMiles(totalPesos)} P</p>
                                <p class="text-danger">${formatNumber(totalBolivares)} Bs</p>
                                <p class="text-success">$${formatNumber(totalDolares)}</p>
                            </div>
                        </div>
                        <div class="hide btn-list-item text-center d-flex pr-4 col-lg-3 text-end">
                            <button class="btn btn-success">Modificar</button>
                            <button class="btn btn-secondary">Cancelar envío</button>
                        </div>
                    </div>
                </li>
            `;
                        document.getElementById('ul-productos-sin-enviar').innerHTML += html;
                    });
                } else {
                    document.getElementById('ul-productos-sin-enviar').innerHTML = '<div class="p-4 text-center" style="color:var(--dash-text-muted);">No hay pedidos pendientes.</div>';
                }
            }
            // MOSTRAR PRODUCTOS SIN ENVIAR


            // Verificar la conexion
            function comprobarConexion(callback) {



                async function verificar() {
                    try {
                        // Intentar una solicitud ligera para comprobar conexión
                        await fetch("https://www.google.com/favicon.ico?_=" + Date.now(), {
                            method: "HEAD",
                            mode: "no-cors",
                            cache: "no-store"
                        });

                        // Hay conexión
                        // quitarAviso();
                        document.getElementById('alert-internet').classList.add('hide');
                        callback(true);
                    } catch (e) {
                        // No hay conexión
                        document.getElementById('alert-internet').classList.remove('hide');

                        callback(false);
                        setTimeout(verificar, 20000); // Reintentar en 20 segundos
                    }
                }

                verificar();
            }






            document.addEventListener('click', function(event) {
                if (event.target.closest('.btn-add-to-car') && !event.target.closest('.no-send')) {
                    let id_p = event.target.closest('.btn-add-to-car').getAttribute('data-add-id');

                    let dolarventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_D')
                    let pesoventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_P')
                    let bolivarventa_p = event.target.closest('.btn-add-to-car').getAttribute('data-P_B')
                    let mayor = event.target.closest('.btn-add-to-car').getAttribute('data-mayor')
                    let cantidad_por_mayor = event.target.closest('.btn-add-to-car').getAttribute('data-cantidad_por_mayor')
                    let cantidad_scan = $('#cantidad-scan').val()

                    $('#result-escaner').html('')
                    $('#search').val('')
                    $('.section-scanner').addClass('hide')


                    addtocarJS(id_p, dolarventa_p, pesoventa_p, bolivarventa_p, mayor, cantidad_por_mayor, cantidad_scan);

                    // ocultar footer del modal
                    const modal_footer = document.getElementById('modal-footer')
                    modal_footer.classList.add('hide')
                }
            });


            let barcode = "";
            let lastKeyTime = Date.now();

            document.addEventListener("keydown", function(event) {

                const currentTime = Date.now();

                // Si el tiempo entre dos teclas es mayor a 100ms, se considera que no es un escaneo
                if (currentTime - lastKeyTime > 100) {
                    barcode = ""; // Reiniciar el código de barras si el tiempo es mayor
                }

                // Si la tecla es "Enter", significa que se ha terminado de escanear
                if (event.key === "Enter") {
                    if (barcode.length > 0) {
                        if (modo == 1) {
                            alert('activa el modo escaner')
                        } else {
                            // buscarProducto(barcode.replace(/Shift/g, ""), 2);
                            buscarProducto(barcode.trim(), 2);
                        }
                    }
                    barcode = ""; // Reiniciar el código de barras
                } else {
                    // Si es cualquier otra tecla, añadirla al código de barras
                    barcode += event.key;
                }

                lastKeyTime = currentTime; // Actualizar el tiempo de la última tecla presionada



            });




            function confirm(id) {
                Swal.fire({
                    title: 'Esta seguro?',
                    html: 'Se eliminara la venta ¿desea continuar?',
                    icon: 'question',
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#32d7c0',
                    showCancelButton: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        eliminarVenta(id)
                    }
                })
            }


            function eliminarVenta(id) {
                $.ajax({
                        url: base_url + 'deleteVentaAjax.php',
                        type: 'POST',
                        dataType: 'html',
                        data: {
                            id: id
                        },
                    })
                    .done(function(resultado1) {
                        cargarUltimasOrdenes()
                    })
            }


            //todo

            function confirmarDescuento() {
                if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                    return false;
                }
                Swal.fire({
                    title: 'Esta seguro?',
                    html: 'Se descontaran productos del almacen ¿desea continuar?',
                    icon: 'question',
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#32d7c0',
                    showCancelButton: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        procesarPedido('0', 3);
                    }
                })
            }


            // Control de funciones desde el teclado
            let lastKey = null;
            let actionTimeout = null;

            // Lógica separada para ejecutar la acción según la tecla
            function ejecutarAccion(key) {
                const modal = document.querySelector('#modal-container');
                const modalDespacho = document.querySelector('#modalDespacho');
                const btnConfirmarDesp = document.getElementById('btnConfirmarDespacho');
                // obten el swal fire con clase swal-metodo-pago
                const swalFire = document.querySelector('.swal-metodo-pago');


                if (!modal.classList.contains('active') && !modalDespacho.classList.contains('show') && !swalFire) {
                    switch (key) {
                        case 'b':
                            document.getElementById('home-tab')?.click();

                            openModalButton.click();
                            document.getElementById("search").focus();
                            document.getElementById("search").value = "";

                            break;
                        case 'v':
                            confirmarVenta();
                            break;
                        case 'c':
                            document.getElementById('calcularVuelto').click();
                            break;
                        case '+':
                            if ($('#result-escaner').find('.btn-add-to-car').length > 0) {
                                const cantidad = $('#cantidad-scan').val()
                                const cantidadActual = parseInt(cantidad) + 1
                                $('#cantidad-scan').val(cantidadActual)
                            }
                            break;
                        case '-':
                            if ($('#result-escaner').find('.btn-add-to-car').length > 0) {

                                const cantidad = $('#cantidad-scan').val()
                                const cantidadActual = parseInt(cantidad) - 1
                                if (cantidadActual > 0) {
                                    $('#cantidad-scan').val(cantidadActual)
                                }
                            }
                            break;



                    }
                } else if (key === 'escape') {
                    closeModalButton.click();
                    document.getElementById("section-scanner").classList.add('hide');
                }

                // No seguir si algun input esta focus
                const focusedElement = document.activeElement;
                if (focusedElement.tagName === 'INPUT' || focusedElement.tagName === 'SELECT') {
                    return;
                }

                if (modalDespacho.classList.contains('show')) {
                    const select = document.getElementById('metodoPago');
                    const btnVender = document.getElementById('btnVender');

                    const opciones = {
                        '1': 'option1',
                        '2': 'option7',
                        '3': 'option6',
                        '4': 'option4',
                        '5': 'option2',
                        '6': 'option3',
                        '7': 'option5',
                    };

                    if (opciones[key]) {
                        select.value = opciones[key];
                        select.dispatchEvent(new Event('change'));
                        // quita el foco
                        document.getElementById('cedulaClienteModal').focus()
                    }

                    if (key == 'enter') {
                        btnConfirmarDespacho.click();
                        // Enbiar la venta por el enter
                    }
                }
            }

            document.addEventListener('keyup', function(event) {
                const key = event.key.toLowerCase();

                const allowedKeys = ['+', '-', 'b', 'v', 'c', 'escape', 'enter', '1', '2', '3', '4', '5', '6', '7'];



                if (!allowedKeys.includes(key)) return; // Ignorar teclas no relevantes

                if (key === lastKey) {
                    // Reinicia el timeout si es la misma tecla
                    clearTimeout(actionTimeout);
                    actionTimeout = setTimeout(() => {
                        ejecutarAccion(key);
                        lastKey = null;
                    }, 100);
                } else {
                    // Tecla distinta: cancelar cualquier acción pendiente
                    clearTimeout(actionTimeout);
                    lastKey = key;
                    actionTimeout = setTimeout(() => {
                        ejecutarAccion(key);
                        lastKey = null;
                    }, 100);
                }
            });
            // Control de funciones desde el teclado



            function agregarNombreSucursal(sucursal_n) {
                const navbar = document.querySelector('ul.navbar-right');
                if (!navbar) {
                    console.warn("No se encontró el elemento 'ul.navbar-right'");
                    return;
                }

                const div = document.getElementById('sucursal_nombre');
                const element = (nv == 1 ? 'a' : 'span');

                const a = document.createElement(element);
                a.href = (nv == 1 ? 'seleccion_sucursal.php' : '');
                a.textContent = sucursal_n;

                div.appendChild(a);
            }

            const nv = <?php echo json_encode($_SESSION["nivel"]) ?>

            agregarNombreSucursal(sucursal_n)


            setInterval(() => {
                fetch("mantener_sesion.php");
            }, 5 * 60 * 1000); // cada 5 minutos
        </script>
</body>

</html>