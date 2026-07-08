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

    echo json_encode([
        'status' => 'success',
        'ventas' => $ventas,
        'negocios' => $negocios,
        'sucursales' => $sucursales,
        'usuarios' => $usuarios
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conexion)) $conexion->close();
}
