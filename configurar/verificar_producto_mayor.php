<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
$mayores = [];

if ($id > 0) {
    // El producto al mayor apunta al base con id_producto_relacionado (mayor = 1)
    $stmt = $conexion->prepare("SELECT id, nombre FROM productos WHERE id_producto_relacionado = ? AND mayor = 1 AND bss_id = ? ORDER BY nombre");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $bss_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $mayores[] = $row;
        }
        $stmt->close();
    }
}

echo json_encode(['success' => true, 'mayores' => $mayores]);
