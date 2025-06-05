<?php
require_once("configuracion.php");
require_once("session.php");

// Aceptar JSON POST
$input = json_decode(file_get_contents("php://input"), true);

$id = $input['order_id'] ?? null;
$pagoTipo = $input['pagoTipo'] ?? null;
$tipo = 1;
$precioPesoVenta = $input['precioPesoVenta'] ?? 0;
$precioBsVenta = $input['precioBsVenta'] ?? 0;


if (!$id || !$pagoTipo || !$tipo) {
    echo json_encode(["success" => false, "message" => "Faltan parámetros requeridos."]);
    exit();
}

$date1 = date('Y-m-d H:i:s');
$date2 = date('Y-m-d');
$date3 = date('Y-m');
$date4 = date('Y-W');
$date5 = date('Y');

try {
    $conexion->begin_transaction();

    // Verificar existencia del crédito
    $stmt = $conexion->prepare("SELECT * FROM creditos WHERE order_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $conexion->rollback();
        echo json_encode(["success" => false, "message" => "Crédito no encontrado."]);
        exit();
    }
    $stmt->close();

    // Actualizar crédito
    $stmt = $conexion->prepare("UPDATE creditos SET estado = '1' WHERE order_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Actualizar orden
    $stmt = $conexion->prepare("UPDATE orden 
        SET status = ?, created = ?, modified = ?, fecha = ?, semana = ?, ano = ?, 
            total_price_bs = ?, total_price_cop = ?, tipoPago = ? 
        WHERE id = ?");
    $stmt->bind_param(
        "ssssssdssi",
        $tipo,
        $date1,
        $date2,
        $date3,
        $date4,
        $date5,
        $precioBsVenta,
        $precioPesoVenta,
        $pagoTipo,
        $id
    );
    $stmt->execute();
    $stmt->close();

    // Confirmar cambios
    $conexion->commit();

    echo json_encode(["success" => true, "message" => "Crédito y orden actualizados correctamente."]);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(["success" => false, "message" => "Error al procesar la solicitud.", "error" => $e->getMessage()]);
}
