<?php
require_once('configuracion.php');
require_once('session.php');
$response = [
    'success' => false,
    'data' => [],
    'pesoDolar' => null,
    'bsDolar' => null,
];



// Buscar producto si se recibe código
if (isset($_POST['producto']) && !empty(trim($_POST['producto']))) {

    $codigo = $conexion->real_escape_string(trim($_POST['producto']));

    $query = "SELECT 
                p.nombre,
                p.precio_compra,
                p.cantidad_unidades,
                p.origen
              FROM productos AS p
              WHERE p.id = ?";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        $response['success'] = true;
        $response['data'] = [
            'precio_compra' => $producto['precio_compra'],
            'cantidad_unidades' => $producto['cantidad_unidades'],
            'origen' => $producto['origen'],
        ];
    }

    $stmt->close();
}

// Devolver JSON
header('Content-Type: application/json');
echo json_encode($response);
