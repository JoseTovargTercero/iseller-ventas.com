<?php
require_once('../../configurar/configuracion.php');

$response = [
    'success' => false,
    'data' => [],
    'pesoDolar' => null,
    'bsDolar' => null,
];

// Tasa de cambio
$queryCambio = "SELECT * FROM cambio WHERE id='1'";
$resultCambio = $conexion->query($queryCambio);
if ($rowCambio = $resultCambio->fetch_assoc()) {
    $response['pesoDolar'] = $rowCambio['pesoDolar'];
    $response['bsDolar'] = $rowCambio['DolarBolivar'];
}

// Buscar producto si se recibe código
if (isset($_POST['rep_precio']) && !empty(trim($_POST['rep_precio']))) {
    $codigo = $conexion->real_escape_string(trim($_POST['rep_precio']));
    $query = "SELECT * FROM productos WHERE id = '$codigo'";
    $result = $conexion->query($query);

    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        $response['success'] = true;
        $response['data'] = [
            'nombre' => $producto['nombre'],
            'precio_compra' => $producto['precio_compra'],
            'cantidad_unidades' => $producto['cantidad_unidades'],
            'porcentaje' => $producto['porcentaje'],
            'proveedor' => $producto['proveedor']
        ];
    }
}

// Devolver JSON
header('Content-Type: application/json');
echo json_encode($response);
