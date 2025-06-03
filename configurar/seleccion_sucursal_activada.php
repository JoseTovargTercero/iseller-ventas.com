<?php
require_once 'session.php';
header('Content-Type: application/json');

if ($_SESSION['nivel'] == 1 && !empty($_SESSION['sucursal'])) {
    echo json_encode(['ok' => true, 'sucursal' => $_SESSION['sucursal']]);
} else {
    // 204 = “Sin contenido”; el front sabrá que no hay sucursal activa
    http_response_code(204);
}