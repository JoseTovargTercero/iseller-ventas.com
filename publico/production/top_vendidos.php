<?php
// Devuelve el ranking de los 9 productos más vendidos de la sucursal activa (JSON).
// La sucursal se toma SIEMPRE de la sesión; nunca de un valor enviado por el cliente.
require_once("../../configurar/configuracion.php");
require_once("../../configurar/session.php");

header('Content-Type: application/json');

$sucursal = (int) ($_SESSION['sucursal'] ?? 0);
if (!$sucursal) {
    echo json_encode(['ok' => false, 'ids' => []]);
    exit;
}

require_once(__DIR__ . '/includes/top_vendidos.php');
echo json_encode(['ok' => true, 'ids' => calcular_top_vendidos($conexion, $sucursal)]);
