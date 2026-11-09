<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once('../../configurar/_canal.php');
$response = [
    'success' => false,
    'data' => [],
    'pesoDolar' => null,
    'bsDolar' => null,
];


$sucursal = $_SESSION["nivel"] == 2 ? $_SESSION["sucursal"] : (@$_POST["sucursal"] ?? null);

if ($sucursal == null) {
    header('Content-Type: application/json');
    $response['error'] = 'No se recibió la sucursal';
    echo json_encode($response);
    exit;
}


// Buscar producto si se recibe código
if (isset($_POST['producto']) && !empty(trim($_POST['producto']))) {

    $codigo = $conexion->real_escape_string(trim($_POST['producto']));

    $query = "SELECT 
                p.nombre,
                p.precio_compra,
                p.cantidad_unidades,
                p.unidad,
                p.proveedor,
                s.porcentaje,
                s.stock
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
        $bssId = (int) ($_SESSION['bss_id'] ?? 0);
        $estricto = $bssId > 0 ? canal_producto_estricto($conexion, (int) $codigo, $bssId) : false;
        $contexto = null;
        if ($estricto) {
            try {
                $contexto = canal_contexto_estricto($conexion, (int) $codigo, (int) $sucursal, $bssId);
            } catch (Throwable $e) {
                $contexto = null;
            }
        }

        $response['data'] = [
            'nombre' => $producto['nombre'],
            'precio_compra' => $producto['precio_compra'],
            'cantidad_unidades' => $producto['cantidad_unidades'],
            'porcentaje' => $producto['porcentaje'], // desde stock
            'proveedor' => $producto['proveedor'],
            'unidad' => $producto['unidad'] ?? 'UND',
            'stock' => $producto['stock'],
            'seguimiento_estricto' => $estricto ? 1 : 0,
            'contexto_estricto' => $contexto
        ];
    }

    $stmt->close();
}

// Devolver JSON
header('Content-Type: application/json');
echo json_encode($response);
