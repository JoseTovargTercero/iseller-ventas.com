<?php
/**
 * REABRIR CAJA
 * Elimina el último registro de cierre del día para el usuario actual
 */
require_once("configuracion.php");
require_once('session.php');

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "Sesión no iniciada"]);
    exit;
}

$usuario_id = $_SESSION['id'];
$sucursal = $_SESSION['sucursal'];
$bss_id = $_SESSION['bss_id'];
$fecha = date('Y-m-d');

// Buscamos y eliminamos el último cierre realizado hoy por este usuario en esta sucursal
$sql = "DELETE FROM cortes_de_caja 
        WHERE usuario_id = ? 
        AND sucursal_id = ? 
        AND bss_id = ? 
        AND tipo_corte = 'cierre' 
        AND fecha = ?
        ORDER BY id DESC LIMIT 1";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $conexion->error]);
    exit;
}

$stmt->bind_param("iiis", $usuario_id, $sucursal, $bss_id, $fecha);

if ($stmt->execute()) {
    if ($conexion->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Caja reabierta exitosamente"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No se encontró ningún cierre para reabrir hoy"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al ejecutar la reapertura: " . $stmt->error
    ]);
}

$stmt->close();
$conexion->close();
?>
