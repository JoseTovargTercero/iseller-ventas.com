<?php
require_once("configuracion.php");
require_once('session.php');

$usuario_id = $_SESSION['id'];
$sucursal = $_SESSION['sucursal'];
$bss_id = $_SESSION['bss_id'];
$fecha = date('Y-m-d');

// Contado (Real)
$efectivo_bs_contado = $_POST['efectivo_bs_contado'] ?? 0;
$efectivo_usd_contado = $_POST['efectivo_usd_contado'] ?? 0;
$pesos_contado = $_POST['pesos_contado'] ?? 0;
$punto_contado = $_POST['punto_contado'] ?? 0;
$biopago_contado = $_POST['biopago_contado'] ?? 0;
$pago_movil_contado = $_POST['pago_movil_contado'] ?? 0;
$transferencia_contado = $_POST['transferencia_contado'] ?? 0;

// Fondo
$efectivo_bs_fondo = $_POST['efectivo_bs_fondo'] ?? 0;
$efectivo_usd_fondo = $_POST['efectivo_usd_fondo'] ?? 0;
$pesos_fondo = $_POST['pesos_fondo'] ?? 0;

$observaciones = $_POST['observaciones'] ?? '';

// Opcional: Aquí podrías calcular las sumas de las ventas del sistema 

// -------------------------------------------------------------------
// 1. OBTENER TOTALES POR TIPO DE PAGO
// -------------------------------------------------------------------
$totalesPago = [
    'Punto' => 0, 'Pmovil' => 0, 'Transferencia' => 0, 'Efectivo' => 0, 
    'Dolares' => 0, 'Pesos' => 0, 'Biopago' => 0
];
$fecha = date('Y-m-d');
$sqlTotalesPago = "
    SELECT o.tipoPago, SUM(o.total_price) as sum_usd, SUM(o.total_price_bs) as sum_bs, SUM(o.total_price_cop) as sum_cop
    FROM orden o
    WHERE o.modified = '$fecha' AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' AND customer_id = '$usuario_id'
    GROUP BY o.tipoPago
";



$resultTotales = $conexion->query($sqlTotalesPago);
if ($resultTotales) {
    while ($row = $resultTotales->fetch_assoc()) {
        switch ($row['tipoPago']) {
            case 1: $totalesPago['Punto'] += $row['sum_bs']; break;
            case 2: $totalesPago['Pmovil'] += $row['sum_bs']; break;
            case 3: $totalesPago['Transferencia'] += $row['sum_bs']; break;
            case 4: $totalesPago['Efectivo'] += $row['sum_bs']; break;
            case 5: $totalesPago['Dolares'] += $row['sum_usd']; break;
            case 6: $totalesPago['Pesos'] += $row['sum_cop']; break;
            case 7: $totalesPago['Biopago'] += $row['sum_bs']; break;
        }
    }
}

	
$diferencia_efectivo_bs = $totalesPago['Efectivo'] - $efectivo_bs_contado;
$diferencia_efectivo_usd = $totalesPago['Dolares'] - $efectivo_usd_contado;
$diferencia_pesos = $totalesPago['Pesos'] - $pesos_contado;
$diferencia_punto = $totalesPago['Punto'] - $punto_contado;
$diferencia_biopago = $totalesPago['Biopago'] - $biopago_contado;
$diferencia_pago_movil = $totalesPago['Pmovil'] - $pago_movil_contado;
$diferencia_transferencia = $totalesPago['Transferencia'] - $transferencia_contado;


$sql = "INSERT INTO cortes_de_caja (
    usuario_id,
    bss_id,
    sucursal_id,
    tipo_corte,
    fecha,

    efectivo_bs_sistema,
    efectivo_usd_sistema,
    pesos_sistema,
    punto_sistema,
    biopago_sistema,
    pago_movil_sistema,
    transferencia_sistema,

    efectivo_bs_contado,
    efectivo_usd_contado,
    pesos_contado,
    punto_contado,
    biopago_contado,
    pago_movil_contado,
    transferencia_contado,

    efectivo_bs_fondo,
    efectivo_usd_fondo,
    pesos_fondo,


    diferencia_efectivo_bs,
    diferencia_efectivo_usd,
    diferencia_pesos,
    diferencia_punto,
    diferencia_biopago,
    diferencia_pago_movil,
    diferencia_transferencia,

    observaciones
) VALUES (?, ?, ?, 'cierre', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?,?)";

$stmt = $conexion->prepare($sql);

$stmt->bind_param(
    "iiisdddddddddddddddddddddddds",
    $usuario_id,
    $bss_id,
    $sucursal,
    $fecha,
    $totalesPago['Efectivo'],
    $totalesPago['Dolares'],
    $totalesPago['Pesos'],
    $totalesPago['Punto'],
    $totalesPago['Biopago'],
    $totalesPago['Pmovil'],
    $totalesPago['Transferencia'],
    $efectivo_bs_contado,
    $efectivo_usd_contado,
    $pesos_contado,
    $punto_contado,
    $biopago_contado,
    $pago_movil_contado,
    $transferencia_contado,
    $efectivo_bs_fondo,
    $efectivo_usd_fondo,
    $pesos_fondo,
    $diferencia_efectivo_bs,
    $diferencia_efectivo_usd,
    $diferencia_pesos,
    $diferencia_punto,
    $diferencia_biopago,
    $diferencia_pago_movil,
    $diferencia_transferencia,
    $observaciones
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Caja cerrada exitosamente"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al cerrar caja: " . $stmt->error
    ]);
}
?>
