<?php
require_once("configuracion.php");
require_once('session.php');
header('Content-Type: application/json');

$response = [];


if (isset($_POST['producto']) && !empty(trim($_POST['producto']))) {
    $q = $conexion->real_escape_string(trim($_POST['producto']));
    //$q = 'POLLO E';

    // Consulta para obtener productos que NO están en la tabla 'stock' para la sucursal
    $query = "SELECT 
    p.id, 
    p.nombre
FROM productos p
LEFT JOIN stock s 
    ON p.id = s.id_producto
WHERE s.id_producto_mayor IS NULL
  AND p.mayor IS NULL
  AND p.nombre LIKE ?
  AND p.bss_id = ?
  AND p.activo = 0
GROUP BY p.id, p.nombre";

    $stmt = $conexion->prepare($query);
    $like = "%$q%";
    $stmt->bind_param("si", $like, $bss_id);
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
