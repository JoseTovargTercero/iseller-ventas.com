<?php
require_once("configuracion.php");
require_once('session.php');
header('Content-Type: application/json');

$response = [];

if ($_SESSION["nivel"] == 1 && $_POST["sucursal"] == '') {
    throw new Exception("No se recibió la sucursal", 1);
    exit;
}

$sucursal = ($_SESSION["nivel"] == '1') ? $_POST["sucursal"] : $_SESSION["id_sucursal"];

// buscar el archivo encargado de los datos del producto para obtener el porcentaje perzonalizado

if (isset($_POST['producto']) && !empty(trim($_POST['producto']))) {
    $q = $conexion->real_escape_string(trim($_POST['producto']));

    $query = "SELECT p.id, p.nombre
    FROM productos p
    INNER JOIN stock s ON p.id = s.id_producto
    WHERE p.nombre LIKE ?
      AND p.activo = 0
      AND s.id_sucursal = ?";
    $stmt = $conexion->prepare($query);
    $like = "%$q%";
    $stmt->bind_param("si", $like, $sucursal);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre']
        ];
    }

    $stmt->close();
}

echo json_encode($response);
