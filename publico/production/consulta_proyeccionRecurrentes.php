<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

$bss_id      = (int) $_SESSION['bss_id'];
$id_sucursal = (int) $_SESSION['sucursal'];
$nivel       = (int) $_SESSION['nivel'];

if ($nivel == 1 && isset($_POST['id_sucursal'])) {
    $id_sucursal = (int) $_POST['id_sucursal'];
}

if (!$bss_id) {
    echo '0';
    exit;
}

$filtroSucursal = ($id_sucursal > 0) ? 'AND id_sucursal = ' . $id_sucursal : '';
$mes_actual = date('Y-m');
$dias_mes = date('t');

$stmt = $conexion->prepare(
    "SELECT id, frecuencia, moneda, monto_estimado, dia_ejecucion
     FROM gastos_recurrentes
     WHERE activo=1 AND bss_id=? $filtroSucursal"
);
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$aplicados_map = [];
$stmt_a = $conexion->prepare(
    "SELECT recurrente_id, COUNT(*) AS total
     FROM gastos
     WHERE bss_id=? AND mes=? AND recurrente_id IS NOT NULL
     GROUP BY recurrente_id"
);
$stmt_a->bind_param('is', $bss_id, $mes_actual);
$stmt_a->execute();
$res_a = $stmt_a->get_result();
while ($row_a = $res_a->fetch_assoc()) {
    $aplicados_map[$row_a['recurrente_id']] = (int) $row_a['total'];
}
$stmt_a->close();

$proyeccion_usd = 0.0;

while ($regla = $result->fetch_assoc()) {
    $monto = floatval($regla['monto_estimado']);
    if ($monto <= 0) continue;

    $moneda = $regla['moneda'];
    if ($moneda === 'VES') {
        $stmt_t = $conexion->prepare("SELECT DolarBolivar FROM cambio WHERE bss_id=?");
        $stmt_t->bind_param('i', $bss_id);
        $stmt_t->execute();
        $tasa = $stmt_t->get_result()->fetch_assoc()['DolarBolivar'] ?? 0;
        $stmt_t->close();
        $monto_usd = ($tasa > 0) ? $monto / $tasa : 0;
    } elseif ($moneda === 'COP') {
        $stmt_t = $conexion->prepare("SELECT pesoDolar FROM cambio WHERE bss_id=?");
        $stmt_t->bind_param('i', $bss_id);
        $stmt_t->execute();
        $tasa = $stmt_t->get_result()->fetch_assoc()['pesoDolar'] ?? 0;
        $stmt_t->close();
        $monto_usd = ($tasa > 0) ? $monto / $tasa : 0;
    } else {
        $monto_usd = $monto;
    }

    $ya_aplicado = $aplicados_map[$regla['id']] ?? 0;

    switch ($regla['frecuencia']) {
        case 'DIARIO':
            $total_mes = $dias_mes;
            $resta = $ya_aplicado;
            break;
        case 'SEMANAL':
            $total_mes = 4;
            $resta = $ya_aplicado;
            break;
        case 'QUINCENAL':
            $total_mes = 2;
            $resta = $ya_aplicado;
            break;
        case 'MENSUAL':
            $total_mes = 1;
            $resta = $ya_aplicado;
            break;
        default:
            $total_mes = 1;
            $resta = $ya_aplicado;
    }

    $pendientes = max(0, $total_mes - $resta);
    $proyeccion_usd += $monto_usd * $pendientes;
}

echo number_format($proyeccion_usd, 2, '.', '');
