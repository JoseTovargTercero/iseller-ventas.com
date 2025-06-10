<?php
//header('Content-Type: application/json');
require_once 'configuracion.php'; // Asegúrate de conectar a tu base de datos
require_once 'session.php'; // Asegúrate de conectar a tu base de datos
require("_tasas_cambio.php");
require("_calculadrora_precios.php");
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);

// Informacion de la tipo de cambio estandar
$input = json_decode(file_get_contents("php://input"), true);

$filtro = isset($input['sucursal']) ? $input['sucursal'] : null;


$sucursales = [];
$stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ?");
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        array_push($sucursales, $row['id']);
    }
}
$stmt->close();


if ($filtro) {

    if (!in_array($filtro, $sucursales)) {
        // validar que tenga permisos para esta consulta
        echo json_encode(["status" => 'error', 'message' => 'No tiene permisos para consultar estos datos']);
        exit;
    }
}

/*
if (!$filtro) {
    # code...
    $sql = "SELECT DISTINCT(S.id_producto), SUM(S.stock) AS stock, SUM(S.porcentaje) / COUNT(*) AS porcentaje, P.codigo_barras, P.id as producto_id, S.id, P.nombre, P.proveedor, P.precio_compra, P.cantidad_unidades, P.origen, P.activo FROM `stock` AS S
    LEFT JOIN productos AS P ON P.id = S.id_producto
    WHERE s.bss_id=$bss_id";
} else {
    $sql = "SELECT DISTINCT(S.id_producto), SUM(S.stock) AS stock, S.porcentaje,  P.id as producto_id,S.id, P.nombre, P.proveedor, P.precio_compra, P.codigo_barras, P.cantidad_unidades, P.origen, P.activo FROM `stock` AS S
    LEFT JOIN productos AS P ON P.id = S.id_producto
    WHERE s.id_sucursal=$filtro";
}

$sql .= " AND P.activo = 0 GROUP BY id_producto ORDER BY id ASC";
*/


$select = !$filtro
    ? "SUM(S.stock) AS stock, SUM(S.porcentaje) / COUNT(*) AS porcentaje"
    : "SUM(S.stock) AS stock, S.porcentaje";

$where = !$filtro
    ? "S.bss_id = $bss_id"
    : "S.id_sucursal = $filtro";

$sql = "SELECT DISTINCT(S.id_producto), $select, P.codigo_barras, P.mayor, P.id as producto_id, S.id, P.nombre, P.proveedor, P.precio_compra, P.cantidad_unidades, P.origen, P.activo
FROM stock AS S
LEFT JOIN productos AS P ON P.id = S.id_producto
WHERE $where AND P.activo = 0
GROUP BY id_producto
ORDER BY id ASC";


$result = $conexion->query($sql);

$datos = [];

while ($row = $result->fetch_assoc()) {
    $precios = $calculadora->calcularPrecios($row);



    $datos[] = [
        "id" => $row["producto_id"],
        "stock_id" => $row["id"],
        "nombre" => $row["nombre"],
        "precio_compra" => number_format((float) $row["precio_compra"], 2, ',', '.') . ' $',
        "cantidad_unidades" => $row["cantidad_unidades"],
        "porcentaje" => number_format($row["porcentaje"], 2) . '%',
        "stock" => $row["stock"],
        "precio_venta_dolar" => $precios['precio_venta_dolar'],
        "precio_venta_bs" => $precios['precio_venta_bs'],
        "precio_venta_peso" => $precios['precio_venta_peso'],
        "codigo_barras" => $row['codigo_barras'],
        "origen" =>  $row['origen'],
        "proveedor" =>  $row['proveedor'],
        "activo" =>  $row['activo'],
        "mayor" =>  $row['mayor'],

    ];
}

echo json_encode(['status' => 'success', "data" => $datos], JSON_PRETTY_PRINT);
