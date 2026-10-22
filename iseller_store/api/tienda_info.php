<?php
require_once(__DIR__ . '/../core/db.php');

header('Content-Type: application/json');

$negocio_id = intval($_GET['negocio_id'] ?? 0);
if ($negocio_id > 0) {
    // Obtener tasas del negocio
    $stmtCambio = $conexion->prepare("SELECT DolarBolivar, pesoDolar FROM cambio WHERE bss_id = ? LIMIT 1");
    $stmtCambio->bind_param('i', $negocio_id);
    $stmtCambio->execute();
    $cambioRow = $stmtCambio->get_result()->fetch_assoc();
    $stmtCambio->close();

    $bcvRes = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC LIMIT 1");
    $bcvRow = $bcvRes ? $bcvRes->fetch_assoc() : null;
    $bcv_val = $bcvRow ? (float)$bcvRow['valor'] : 0;

    $dolarBolivar = (float)($cambioRow['DolarBolivar'] ?? 0);
    if ($dolarBolivar <= 0) {
        $dolarBolivar = $bcv_val > 0 ? $bcv_val : 40.0;
    }
    $dolarPeso = (float)($cambioRow['pesoDolar'] ?? 0);

    // Obtener configuración del negocio
    $stmt = $conexion->prepare("SELECT config_delivery, datos_bancarios FROM negocio WHERE id = ?");
    $stmt->bind_param("i", $negocio_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $config_delivery = json_decode($row['config_delivery'] ?? '{}');
        $datos_bancarios = json_decode($row['datos_bancarios'] ?? '{}');
        
        echo json_encode([
            'success' => true,
            'tasa_dolar' => $dolarBolivar,
            'tasa_peso' => $dolarPeso,
            'config_delivery' => $config_delivery,
            'datos_bancarios' => $datos_bancarios
        ]);
        exit;
    }
}
echo json_encode(['success' => false, 'error' => 'Not found']);
