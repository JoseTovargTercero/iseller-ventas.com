<?php

require_once 'configuracion.php';
require_once 'session.php';
require_once '_tasas_cambio.php';
require("_calculadrora_precios.php");

// Recibir JSON
$data = json_decode(file_get_contents("php://input"), true);

$cliente = $data["cliente"] ?? null;
$moneda  = $data["moneda"] ?? null;
$monto   = $data["monto"] ?? null;
$sucursal_id = $_SESSION["sucursal"] ?? 0; // Valor por defecto 1


if ($cliente && $moneda && $monto) {
    $stmt = $conexion->prepare("INSERT INTO abonos (cliente, moneda, monto, bss_id, sucursal_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdii", $cliente, $moneda, $monto, $bss_id, $sucursal_id); // s=string, d=double

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Error en BD"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
}

$conexion->close();
