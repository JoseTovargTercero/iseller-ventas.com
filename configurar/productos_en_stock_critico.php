<?php
//header('Content-Type: application/json');
require_once 'configuracion.php'; // Asegúrate de conectar a tu base de datos
require_once 'session.php'; // Asegúrate de conectar a tu base de datos

$input = json_decode(file_get_contents("php://input"), true);
$sucursal      = $_SESSION["nivel"] == 1 ? trim($input['sucursal']) : $_SESSION["sucursal"];

$stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE id = ?");
$stmt->bind_param('i', $sucursal);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stockCritico = $row['stockCritico'];
    }
}
$stmt->close();

$sql = "SELECT S.id, P.nombre, P.proveedor, S.stock, P.precio_compra, P.origen FROM `stock` AS S
LEFT JOIN productos AS P ON P.id = S.id_producto
WHERE s.id_sucursal=$sucursal AND S.stock<=$stockCritico AND P.activo = 0 ORDER BY P.proveedor DESC ";

$result = $conexion->query($sql);
$datos = [];

while ($row = $result->fetch_assoc()) {
    $datos[] = [
        "id" => $row["id"],
        "nombre" => $row["nombre"],
        "stock" => $row["stock"],
        "proveedor" =>  $row['proveedor'],
        "precio_compra" =>  $row['precio_compra'],
        "origen" =>  $row['origen'],
    ];
}

echo json_encode(['status' => 'success', "data" => $datos], JSON_PRETTY_PRINT);
