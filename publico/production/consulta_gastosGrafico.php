<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

header('Content-Type: application/json; charset=utf-8');

$bss_id      = (int) $_SESSION['bss_id'];
$id_sucursal = (int) $_SESSION['sucursal'];
$nivel       = (int) $_SESSION['nivel'];

if ($nivel == 1 && isset($_POST['id_sucursal'])) {
    $id_sucursal = (int) $_POST['id_sucursal'];
}

if (!$bss_id) {
    echo json_encode(['labels' => [], 'ganancias' => [], 'gastos' => [], 'netos' => []]);
    exit;
}

// Últimos 12 meses, incluido el actual
$nombres = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];
$hoy    = new DateTime('first day of this month');
$meses  = [];
$labels = [];
for ($i = 11; $i >= 0; $i--) {
    $d = (clone $hoy)->modify("-$i months");
    $meses[]  = $d->format('Y-m');
    $labels[] = $nombres[$d->format('m')] . ' ' . $d->format('y');
}
$desde = $meses[0];

$ganancias = array_fill_keys($meses, 0.0);
$gastos    = array_fill_keys($meses, 0.0);

// Ganancias por mes: total de la orden - costo de sus artículos
$filtroSucursal = $id_sucursal > 0 ? ' AND o.id_sucursal = ' . $id_sucursal : '';
$sql = "SELECT o.fecha, o.total_price,
               COALESCE(SUM(oa.precio * oa.quantity), 0) AS costo
        FROM orden o
        LEFT JOIN orden_articulos oa ON oa.order_id = o.id
        WHERE o.bss_id = $bss_id
          AND o.status IN (1,4)
          AND o.fecha >= '" . $conexion->real_escape_string($desde) . "'
          $filtroSucursal
        GROUP BY o.id";
$res = $conexion->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $key = $row['fecha'];
        if (isset($ganancias[$key])) {
            $ganancias[$key] += (float)$row['total_price'] - (float)$row['costo'];
        }
    }
}

// Gastos activos por mes
$filtroSucursalG = $id_sucursal > 0 ? ' AND g.id_sucursal = ' . $id_sucursal : '';
$sqlG = "SELECT g.mes, COALESCE(SUM(g.monto_usd), 0) AS total
         FROM gastos g
         WHERE g.bss_id = $bss_id
           AND g.estado = 'ACTIVO'
           AND g.mes >= '" . $conexion->real_escape_string($desde) . "'
           $filtroSucursalG
         GROUP BY g.mes";
$resG = $conexion->query($sqlG);
if ($resG) {
    while ($rowG = $resG->fetch_assoc()) {
        $key = $rowG['mes'];
        if (isset($gastos[$key])) {
            $gastos[$key] += (float)$rowG['total'];
        }
    }
}

$out = ['labels' => $labels, 'ganancias' => [], 'gastos' => [], 'netos' => []];
foreach ($meses as $m) {
    $out['ganancias'][] = round($ganancias[$m], 2);
    $out['gastos'][]    = round($gastos[$m], 2);
    $out['netos'][]     = round($ganancias[$m] - $gastos[$m], 2);
}

echo json_encode($out);
