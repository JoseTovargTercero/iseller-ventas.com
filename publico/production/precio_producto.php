<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once('../../configurar/_tasas_cambio.php');
$response = [
    'success' => false,
    'data' => [],
    'pesoDolar' => null,
    'bsDolar' => null,
];

$response['pesoDolar'] = $pesoDolar;
$response['bsDolar'] = $dolarBolivar;



// Validar sucursal
if ($_SESSION["nivel"] == 1 && empty($_POST["sucursal"])) {
    throw new Exception("No se recibió la sucursal", 1);
    exit;
}

$sucursal = ($_SESSION["nivel"] == '1') ? $_POST["sucursal"] : $_SESSION["id_sucursal"];



// Buscar producto si se recibe código
if (isset($_POST['producto']) && !empty(trim($_POST['producto']))) {

    $codigo = $conexion->real_escape_string(trim($_POST['producto']));

    $query = "SELECT 
                p.nombre,
                p.precio_compra,
                p.cantidad_unidades,
                p.proveedor,
                s.porcentaje
              FROM productos AS p
              INNER JOIN stock AS s ON p.id = s.id_producto
              WHERE p.id = ? AND s.id_sucursal = ?";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $codigo, $sucursal);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        $response['success'] = true;
        $response['data'] = [
            'nombre' => $producto['nombre'],
            'precio_compra' => $producto['precio_compra'],
            'cantidad_unidades' => $producto['cantidad_unidades'],
            'porcentaje' => $producto['porcentaje'], // desde stock
            'proveedor' => $producto['proveedor']
        ];
    }

    $stmt->close();
}

// Devolver JSON
header('Content-Type: application/json');
echo json_encode($response);
