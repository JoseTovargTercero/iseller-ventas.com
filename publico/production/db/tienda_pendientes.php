<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

header('Content-Type: application/json');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['pendientes' => 0]);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$r = $conexion->query("SELECT COUNT(*) c FROM tienda_ordenes WHERE bss_id=$bss_id AND status=1");
echo json_encode(['pendientes' => (int)$r->fetch_assoc()['c']]);
