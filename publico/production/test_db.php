<?php
session_start();
$_SESSION['id'] = 1;
$_SESSION['sucursal'] = 1;
$_SESSION['bss_id'] = 1;
$_SESSION['nivel'] = 1;
$_GET['id'] = 1;

require 'includes/requires.php';

$bss_id = 1;
$id_usuario = 1;
$fecha = date('Y-m-d');
$sqlTotalesPago = "
    SELECT o.tipoPago, SUM(o.total_price) as sum_usd, SUM(o.total_price_bs) as sum_bs, SUM(o.total_price_cop) as sum_cop
    FROM orden o
    WHERE o.modified = '$fecha' AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' AND customer_id = '$id_usuario'
    GROUP BY o.tipoPago
";

$resultTotales = $conexion->query($sqlTotalesPago);
if (!$resultTotales) {
    echo "Error en consulta de totales: " . $conexion->error . "\n";
} else {
    echo "Consulta de totales OK\n";
}

$sql = "INSERT INTO cortes_de_caja (
    usuario_id, bss_id, sucursal_id, tipo_corte, fecha,
    efectivo_bs_sistema, efectivo_usd_sistema, pesos_sistema, punto_sistema, biopago_sistema, pago_movil_sistema, transferencia_sistema,
    efectivo_bs_contado, efectivo_usd_contado, pesos_contado, punto_contado, biopago_contado, pago_movil_contado, transferencia_contado,
    efectivo_bs_fondo, efectivo_usd_fondo, pesos_fondo,
    diferencia_efectivo_bs, diferencia_efectivo_usd, diferencia_pesos, diferencia_punto, diferencia_biopago, diferencia_pago_movil, diferencia_transferencia,
    observaciones
) VALUES (?, ?, ?, 'cierre', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?,?)";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo "Error en prepare INSERT: " . $conexion->error . "\n";
} else {
    echo "Prepare INSERT OK\n";
}
