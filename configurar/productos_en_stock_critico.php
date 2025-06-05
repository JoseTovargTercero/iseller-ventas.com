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

$datos = [];

// Verifica que las variables estén definidas y sean numéricas
if (isset($sucursal, $stockCritico) && is_numeric($sucursal) && is_numeric($stockCritico)) {
    $sql = "SELECT S.id, P.nombre, P.proveedor, S.stock, P.precio_compra, P.origen
            FROM `stock` AS S
            LEFT JOIN productos AS P ON P.id = S.id_producto
            WHERE S.id_sucursal = ? AND S.stock <= ? AND P.activo = 0
            ORDER BY P.proveedor DESC";

    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("ii", $sucursal, $stockCritico);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $datos[] = [
                "id" => $row["id"],
                "nombre" => $row["nombre"],
                "stock" => $row["stock"],
                "proveedor" => $row["proveedor"],
                "precio_compra" => $row["precio_compra"],
                "origen" => $row["origen"],
            ];
        }

        $stmt->close();
    } else {
        // Error en la preparación del statement
        error_log("Error al preparar la consulta: " . $conexion->error);
    }
} else {
    // Error por variables inválidas
    echo json_encode(['status' => 'success', "data" => ''], JSON_PRETTY_PRINT);
}



echo json_encode(['status' => 'success', "data" => $datos], JSON_PRETTY_PRINT);
