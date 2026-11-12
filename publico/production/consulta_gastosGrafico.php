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
    echo json_encode(['labels' => [], 'ganancias' => [], 'gastos' => [], 'mermas' => [], 'netos' => []]);
    exit;
}

if ($nivel != 1 && $id_sucursal <= 0) {
    echo json_encode(['labels' => [], 'ganancias' => [], 'gastos' => [], 'mermas' => [], 'netos' => []]);
    exit;
}

// Últimos 12 meses, incluido el actual
$nombres = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];
$mesActual = date('Y-m');
$hoy       = new DateTime('first day of this month');
$meses     = [];
$labels    = [];
for ($i = 11; $i >= 0; $i--) {
    $d = (clone $hoy)->modify("-$i months");
    $meses[]  = $d->format('Y-m');
    $labels[] = $nombres[$d->format('m')] . ' ' . $d->format('y');
}
$pasados = array_values(array_filter($meses, function ($m) use ($mesActual) {
    return $m < $mesActual;
}));

$ganancias = array_fill_keys($meses, 0.0);
$gastos    = array_fill_keys($meses, 0.0);
$mermas    = array_fill_keys($meses, 0.0);

$gananciasDeRango = function ($desde, $hasta) use ($conexion, $bss_id, $id_sucursal) {
    $filtroSucursal = $id_sucursal > 0 ? ' AND o.id_sucursal = ' . $id_sucursal : '';
    $d = $conexion->real_escape_string($desde);
    $h = $conexion->real_escape_string($hasta);
    $out = [];
    $sql = "SELECT o.fecha, o.total_price,
                   COALESCE(SUM(oa.precio * oa.quantity), 0) AS costo
            FROM orden o
            LEFT JOIN orden_articulos oa ON oa.order_id = o.id
            WHERE o.bss_id = $bss_id
              AND o.status IN (1,4)
              AND o.fecha BETWEEN '$d' AND '$h'
              $filtroSucursal
            GROUP BY o.id";
    $res = $conexion->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[$row['fecha']] = ($out[$row['fecha']] ?? 0) + (float)$row['total_price'] - (float)$row['costo'];
        }
    }
    return $out;
};

$gastosDeRango = function ($desde, $hasta) use ($conexion, $bss_id, $id_sucursal) {
    $filtroSucursal = $id_sucursal > 0 ? ' AND g.id_sucursal = ' . $id_sucursal : '';
    $d = $conexion->real_escape_string($desde);
    $h = $conexion->real_escape_string($hasta);
    $out = [];
    $sql = "SELECT g.mes, COALESCE(SUM(g.monto_usd), 0) AS total
            FROM gastos g
            WHERE g.bss_id = $bss_id
              AND g.estado = 'ACTIVO'
              AND g.mes BETWEEN '$d' AND '$h'
              $filtroSucursal
            GROUP BY g.mes";
    $res = $conexion->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[$row['mes']] = (float)$row['total'];
        }
    }
    return $out;
};

$mermasDeRango = function ($desde, $hasta) use ($conexion, $bss_id, $id_sucursal) {
    $filtroSucursal = $id_sucursal > 0 ? ' AND a.id_sucursal = ' . $id_sucursal : '';
    $d = $conexion->real_escape_string($desde);
    $h = $conexion->real_escape_string($hasta);
    $out = [];
    $sql = "SELECT DATE_FORMAT(a.fecha, '%Y-%m') AS mes,
                   COALESCE(SUM(a.monto_usd), 0) AS total
            FROM stock_ajustes a
            WHERE a.bss_id = $bss_id
              AND a.estado = 'ACTIVO'
              AND a.tipo = 'MERMA'
              AND a.direccion = 'SALIDA'
              AND a.fecha BETWEEN '$d' AND '$h'
              $filtroSucursal
            GROUP BY mes";
    $res = $conexion->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[$row['mes']] = (float)$row['total'];
        }
    }
    return $out;
};

// Meses pasados: leer de la tabla resumen
$faltantes = $pasados;
$cacheDisponible = false;
if ($pasados) {
    $stmt = $conexion->prepare(
        "SELECT mes, ganancias, gastos FROM gastos_resumen_mensual
         WHERE bss_id=? AND id_sucursal=? AND mes BETWEEN ? AND ?"
    );
    if ($stmt) {
        $cacheDisponible = true;
        $desde = $pasados[0];
        $hasta = $pasados[count($pasados) - 1];
        $stmt->bind_param('iiss', $bss_id, $id_sucursal, $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();
        $cacheados = [];
        while ($row = $res->fetch_assoc()) {
            $cacheados[$row['mes']] = true;
            $ganancias[$row['mes']] = (float)$row['ganancias'];
            $gastos[$row['mes']]    = (float)$row['gastos'];
        }
        $stmt->close();
        $faltantes = array_values(array_diff($pasados, array_keys($cacheados)));
    }
}

// Calcular y guardar los meses pasados que aún no están en la tabla
if ($faltantes) {
    $desde = $faltantes[0];
    $hasta = $faltantes[count($faltantes) - 1];
    $gan = $gananciasDeRango($desde, $hasta);
    $gas = $gastosDeRango($desde, $hasta);

    $stmtIns = $cacheDisponible ? $conexion->prepare(
        "INSERT INTO gastos_resumen_mensual (bss_id, id_sucursal, mes, ganancias, gastos)
         VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE ganancias=VALUES(ganancias), gastos=VALUES(gastos), calculado_at=NOW()"
    ) : false;

    foreach ($faltantes as $m) {
        $g  = round($gan[$m] ?? 0, 2);
        $gs = round($gas[$m] ?? 0, 2);
        $ganancias[$m] = $g;
        $gastos[$m]    = $gs;

        if ($stmtIns) {
            $stmtIns->bind_param('iisdd', $bss_id, $id_sucursal, $m, $g, $gs);
            $stmtIns->execute();
        }
    }
    if ($stmtIns) {
        $stmtIns->close();
    }
}

// El mes en curso siempre se calcula en vivo
$ganActual = $gananciasDeRango($mesActual, $mesActual);
$gasActual = $gastosDeRango($mesActual, $mesActual);
$ganancias[$mesActual] = round($ganActual[$mesActual] ?? 0, 2);
$gastos[$mesActual]    = round($gasActual[$mesActual] ?? 0, 2);

// Mermas: solo registros de tipo MERMA, sin ajustes. Se calculan en vivo para
// los 12 meses porque la tabla de cache mensual solo guarda ganancias y gastos.
$primerMes = $meses[0] . '-01';
$ultimoMes = date('Y-m-t', strtotime($meses[count($meses) - 1] . '-01'));
$mer = $mermasDeRango($primerMes, $ultimoMes);
foreach ($meses as $m) {
    $mermas[$m] = round($mer[$m] ?? 0, 2);
}

// Meses con descuadre de ventas: se muestran en 0 en el gráfico
$mesesEnCero = ['2026-01'];
foreach ($mesesEnCero as $m) {
    if (isset($ganancias[$m])) {
        $ganancias[$m] = 0;
        $gastos[$m]    = 0;
        $mermas[$m]    = 0;
    }
}

$out = ['labels' => $labels, 'ganancias' => [], 'gastos' => [], 'mermas' => [], 'netos' => []];
foreach ($meses as $m) {
    $out['ganancias'][] = $ganancias[$m];
    $out['gastos'][]    = $gastos[$m];
    $out['mermas'][]    = $mermas[$m];
    $out['netos'][]     = round($ganancias[$m] - $gastos[$m] - $mermas[$m], 2);
}

echo json_encode($out);
