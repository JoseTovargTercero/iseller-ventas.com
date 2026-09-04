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
    echo '0*0*0*0*0*0';
    exit;
}

$semana = trim($_POST['semana'] ?? date('Y-W'));
$mes    = trim($_POST['mes']    ?? date('Y-m'));

$filtroSucursal  = $id_sucursal > 0 ? ' AND o.id_sucursal = ' . $id_sucursal : '';
$filtroSucursalG = $id_sucursal > 0 ? ' AND g.id_sucursal = ' . $id_sucursal : '';

// --- Ganancias: misma lógica que index_back.php (GROUP BY o.id) ---
$gananciaSemana = 0.0;
$gananciaMes    = 0.0;

$sql = "SELECT o.total_price, o.semana, o.fecha,
               COALESCE(SUM(oa.precio * oa.quantity), 0) AS costo_total
        FROM orden o
        LEFT JOIN orden_articulos oa ON o.id = oa.order_id
        WHERE o.fecha IN ('" . $conexion->real_escape_string($mes) . "', '" . $conexion->real_escape_string(date('Y-m', strtotime('first day of -1 month'))) . "')
          AND o.bss_id = $bss_id
          AND o.status IN (1,4)
          $filtroSucursal
        GROUP BY o.id";

$res = $conexion->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $gain = (float)$row['total_price'] - (float)$row['costo_total'];
        if ($row['semana'] === $semana) {
            $gananciaSemana += $gain;
        }
        if (strpos($row['fecha'], $mes) === 0) {
            $gananciaMes += $gain;
        }
    }
}

// --- Gastos semana ---
$gastosSemana = 0.0;
$sqlG = "SELECT COALESCE(SUM(g.monto_usd), 0) AS total
         FROM gastos g
         WHERE g.semana = '" . $conexion->real_escape_string($semana) . "'
           AND g.bss_id = $bss_id
           AND g.estado = 'ACTIVO'
           $filtroSucursalG";
$resG = $conexion->query($sqlG);
if ($resG) {
    $rowG = $resG->fetch_assoc();
    $gastosSemana = $rowG ? (float)$rowG['total'] : 0.0;
}

// --- Gastos mes ---
$gastosMes = 0.0;
$sqlGM = "SELECT COALESCE(SUM(g.monto_usd), 0) AS total
          FROM gastos g
          WHERE g.mes = '" . $conexion->real_escape_string($mes) . "'
            AND g.bss_id = $bss_id
            AND g.estado = 'ACTIVO'
            $filtroSucursalG";
$resGM = $conexion->query($sqlGM);
if ($resGM) {
    $rowGM = $resGM->fetch_assoc();
    $gastosMes = $rowGM ? (float)$rowGM['total'] : 0.0;
}

// --- Neto = ganancia bruta - gastos ---
$gananciaNetaSemana = $gananciaSemana - $gastosSemana;
$gananciaNetaMes    = $gananciaMes    - $gastosMes;

echo number_format($gananciaSemana, 2, '.', '') . '*' . number_format($gananciaMes, 2, '.', '') . '*' . number_format($gastosSemana, 2, '.', '') . '*' . number_format($gastosMes, 2, '.', '') . '*' . number_format($gananciaNetaSemana, 2, '.', '') . '*' . number_format($gananciaNetaMes, 2, '.', '');
