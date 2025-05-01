<?php
require_once("configuracion.php");

header('Content-Type: application/json');

$response = [];

if (isset($_POST['producto']) && !empty(trim($_POST['producto']))) {
    $q = $conexion->real_escape_string(trim($_POST['producto']));

    $query = "SELECT id, nombre FROM productos WHERE nombre LIKE ? AND activo = 0";
    $stmt = $conexion->prepare($query);
    $like = "%$q%";
    $stmt->bind_param("s", $like);
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
