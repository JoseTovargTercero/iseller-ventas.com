<?php
header('Content-Type: application/json');
require_once 'configuracion.php';

try {
    $ventas = 0;
    $negocios = 0;
    $sucursales = 0;
    $usuarios = 0;

    $stmt = $conexion->prepare("SELECT COUNT(*) AS c FROM orden");
    if ($stmt) {
        $stmt->execute();
        $ventas = (int)$stmt->get_result()->fetch_assoc()['c'] * 2;
        $stmt->close();
    }

    $stmt = $conexion->prepare("SELECT COUNT(*) AS c FROM negocio");
    if ($stmt) {
        $stmt->execute();
        $negocios = (int)$stmt->get_result()->fetch_assoc()['c'] * 3;
        $stmt->close();
    }

    $stmt = $conexion->prepare("SELECT COUNT(*) AS c FROM sucursales");
    if ($stmt) {
        $stmt->execute();
        $sucursales = (int)$stmt->get_result()->fetch_assoc()['c'] * 3;
        $stmt->close();
    }

    $stmt = $conexion->prepare("SELECT COUNT(*) AS c FROM usuarios");
    if ($stmt) {
        $stmt->execute();
        $usuarios = (int)$stmt->get_result()->fetch_assoc()['c'] * 3;
        $stmt->close();
    }

    $tasa_bcv = "36.06";
    $stmt = $conexion->prepare("SELECT valor FROM cambios_bcv_historico ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $tasa_bcv = $row['valor'];
        }
        $stmt->close();
    }

    $tasa_peso = "4158";
    $stmt = $conexion->prepare("SELECT bolivar_peso FROM cambio WHERE bss_id = 2 LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $tasa_peso = $row['bolivar_peso'] - 0.0001;
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'ventas' => $ventas,
        'negocios' => $negocios,
        'sucursales' => $sucursales,
        'usuarios' => $usuarios,
        'tasa_bcv' => $tasa_bcv,
        'tasa_peso' => $tasa_peso
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conexion)) $conexion->close();
}
