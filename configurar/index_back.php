<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

try {
  ob_start();
  $data = json_decode(file_get_contents('php://input'), true);
$sucursal = $_SESSION["nivel"] == 2 ? $_SESSION["sucursal"] : (@$data["sucursal"] ?? null);
$sucursal = $sucursal === "todas" || $sucursal == "" || $sucursal == null ? "" : $sucursal; // Permitir que se envíe false para no filtrar por sucursal
$extraCond = $sucursal !== "" ? ' AND id_sucursal = ' . (int)$sucursal : '';



$user_cond = "";
if ($extraCond != '' && isset($data['usuario'])) {
  if ($data['usuario'] != 'todos') {
    $usuario = (int)$data['usuario'];
    $user_cond = " AND usuario  = $usuario";
  }
}


$bss_id = $_SESSION['bss_id'] ?? 1;
$periodo = $data['periodo'] ?? 'mes';
$periodoPie = $data['periodoPie'] ?? 'mes';
$periodoProductos = $data['periodoProductos'] ?? 'mes';
$stockCritico = 10;

$hoy = date('Y-m-d');
$semana = date('Y-W');
$mes = date('Y-m');
$ano = date('Y');
$dia_ant = date('Y-m-d', strtotime('-1 day'));
$semana_ant = date('Y-W', strtotime('-1 week'));
$mes_ant = date('Y-m', strtotime('first day of -1 month'));

// Periodo filter for client ranking
$periodCond = '';
if ($periodo === 'semana') {
  $periodCond = " AND o.semana = '$semana'";
} else {
  $periodCond = " AND o.fecha = '$mes'";
}

// Top compradores optimizado
$topClientes = [];

$sql = "
SELECT 
        t.cliente AS cedula,
        COALESCE(MAX(c.nombre), t.cliente) AS nombre,
        t.total_gastado AS total
    FROM (
        SELECT 
            o.cliente, 
            SUM(o.total_price) AS total_gastado 
        FROM orden o
        WHERE o.bss_id = $bss_id 
          AND o.status IN (1,4) 
          AND o.cliente > '' 
          $extraCondO $user_cond $periodCond 
        GROUP BY o.cliente 
        ORDER BY total_gastado DESC 
        LIMIT 15
    ) t
    LEFT JOIN clientes c ON c.cedula = t.cliente AND c.bss_id = $bss_id
    GROUP BY t.cliente, t.total_gastado
    ORDER BY t.total_gastado DESC
";

$res = $conexion->query($sql);

while ($row = $res->fetch_assoc()) {
  $row['total'] = (float)$row['total'];
  $topClientes[] = $row;
}

// Periodo filter for ventas por sucursal pie chart
$periodCondPie = '';
if ($periodoPie === 'dia') {
  $periodCondPie = " AND o.modified = '$hoy'";
} elseif ($periodoPie === 'semana') {
  $periodCondPie = " AND o.semana = '$semana'";
} else {
  $periodCondPie = " AND o.fecha = '$mes'";
}

// Ventas por sucursal (pie chart)
$ventasPorSucursal = [];

// IMPORTANTE: asegurar índices en:
// orden(bss_id, status, id_sucursal) y sucursales(id)

$sql = "
SELECT 
    s.nombre AS sucursal,
    SUM(o.total_price) AS total
FROM orden o
INNER JOIN sucursales s ON s.id = o.id_sucursal
WHERE 
    o.status IN (1,4)
    AND o.bss_id = $bss_id
    $extraCondO
    $periodCondPie
GROUP BY o.id_sucursal, s.nombre
ORDER BY total DESC
";

// ejecutar una sola vez
$res = $conexion->query($sql);

// fetch más rápido (sin overhead de while + asignaciones pesadas)
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $ventasPorSucursal[] = [
      'sucursal' => $row['sucursal'],
      'total' => (float) $row['total']
    ];
  }
}

// Ventas promedio por hora (últimos 7 días) - Promedio Estricto
$ventasPorHora = array_fill(0, 24, 0);

$sql = "
    SELECT 
        HOUR(o.created) AS hora, 
        ROUND(SUM(o.total_price) / 7, 2) AS promedio
    FROM orden o 
    WHERE o.bss_id = $bss_id 
      AND o.created >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
      AND o.status IN (1,4) 
      $extraCondO $user_cond 
    GROUP BY hora
";

$res = $conexion->query($sql);

while ($row = $res->fetch_assoc()) {
  $ventasPorHora[(int)$row['hora']] = (float)$row['promedio'];
}




// Periodo filter for top productos
$periodCondProductos = '';
if ($periodoProductos === 'dia') {
  $periodCondProductos = " AND o.modified = '$hoy'";
} elseif ($periodoProductos === 'semana') {
  $periodCondProductos = " AND o.semana = '$semana'";
} else {
  $periodCondProductos = " AND o.fecha = '$mes'";
}

// Top 5 productos más vendidos optimizado
$topProductos = [];

$sql = "
    SELECT 
        t.product_id,
        COALESCE(p.nombre, CONCAT('Producto #', t.product_id)) AS producto,
        t.total_vendido AS cantidad,
        t.ingreso_total AS ingreso
    FROM (
        SELECT 
            oa.product_id, 
            SUM(oa.quantity) AS total_vendido, 
            SUM(oa.quantity * oa.precio_venta_dolar) AS ingreso_total
        FROM orden_articulos oa 
        INNER JOIN orden o ON oa.order_id = o.id 
        WHERE o.bss_id = $bss_id 
          AND o.status IN (1,4) 
          $extraCondO $user_cond $periodCondProductos
        GROUP BY oa.product_id 
        ORDER BY total_vendido DESC 
        LIMIT 5
    ) t
    LEFT JOIN productos p ON t.product_id = p.id
    ORDER BY t.total_vendido DESC
";

$res = $conexion->query($sql);

while ($res && $row = $res->fetch_assoc()) {
  $topProductos[] = [
    'producto' => $row['producto'],
    'cantidad' => (int)$row['cantidad'],
    'ingreso'  => (float)$row['ingreso']
  ];
}





function contar2($sql)
{
  global $conexion, $extraCond, $bss_id;
  $res = $conexion->query(str_replace('##COND##', "bss_id = $bss_id $extraCond ", $sql));
  return $res ? (int)$res->fetch_row()[0] : 0;
}

function obtenerImportes($tabla, $columna, $valor)
{
  global $conexion, $bss_id, $extraCond;
  $sql = "SELECT SUM(importe) AS total FROM $tabla WHERE $columna = '$valor' AND bss_id = $bss_id $extraCond";
  $res = $conexion->query($sql);
  return $res ? (float)$res->fetch_assoc()['total'] : 0;
}

// Ventas por dia de la semana
$diasSemana = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
$arraySemana = array_fill_keys($diasSemana, 0);
$res = $conexion->query("SELECT dia, total_price FROM orden WHERE semana = '$semana' AND status IN (1,4) AND bss_id = $bss_id $extraCond $user_cond");
while ($row = $res->fetch_assoc()) {
  $dia = (int)$row['dia'];
  if (isset($diasSemana[$dia])) $arraySemana[$diasSemana[$dia]] += (float)$row['total_price'];
}
// OPTIMIZADO

function obtenerVentas($bss_id, $extraCondO)
{
  global $hoy, $dia_ant, $semana, $semana_ant, $mes, $mes_ant, $diasSemana, $conexion, $user_cond;

  $ventas = [
    'hoy' => 0,
    'ayer' => 0,
    'semana' => 0,
    'semana_ant' => 0,
    'mes' => 0,
    'mes_ant' => 0,
    'por_dia_semana' => array_fill_keys($diasSemana, 0),
    'gananciasDia' => 0,
    'gananciasSemana' => 0,
    'gananciasMes' => 0,
  ];

  // 1. Unificamos las consultas con LEFT JOIN y GROUP BY
  // MySQL calculará el costo total de los artículos por cada orden antes de enviarlo a PHP.
  $sql = "
        SELECT 
            o.total_price, 
            o.modified, 
            o.semana, 
            o.fecha, 
            o.dia,
            COALESCE(SUM(oa.precio * oa.quantity), 0) AS costo_total
        FROM orden o
        LEFT JOIN orden_articulos oa ON o.id = oa.order_id
        WHERE (o.fecha = '$mes' OR o.fecha = '$mes_ant') 
          AND o.bss_id = " . (int)$bss_id . " 
          AND o.status IN (1,4) 
          $extraCondO $user_cond
        GROUP BY o.id
    ";

  $res = $conexion->query($sql);

  // 2. Procesamos todo en un único bucle
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $precio = (float)$row['total_price'];
      $costo = (float)$row['costo_total'];

      $mod = $row['modified'];
      $sem = $row['semana'];
      $fec = $row['fecha'];
      $diaN = (int)$row['dia'];

      // Evaluaciones Diarias
      if ($mod === $hoy) {
        $ventas['hoy'] += $precio;
        $ventas['gananciasDia'] += ($precio - $costo);
      }
      if ($mod === $dia_ant) {
        $ventas['ayer'] += $precio;
      }

      // Evaluaciones Semanales
      if ($sem === $semana) {
        $ventas['semana'] += $precio;
        $ventas['gananciasSemana'] += ($precio - $costo);

        $nombreDia = $diasSemana[$diaN] ?? null;
        if ($nombreDia) {
          $ventas['por_dia_semana'][$nombreDia] += $precio;
        }
      }
      if ($sem === $semana_ant) {
        $ventas['semana_ant'] += $precio;
      }

      // Evaluaciones Mensuales
      if ($fec === $mes) {
        $ventas['mes'] += $precio;
        $ventas['gananciasMes'] += ($precio - $costo);
      }
      if ($fec === $mes_ant) {
        $ventas['mes_ant'] += $precio;
      }
    }
  }

  return $ventas;
}

// Variables de retorno (sin cambios en tu lógica base)
$ventasResumen = obtenerVentas($bss_id, $extraCondO);

$totalVentasDiarias = $ventasResumen['hoy'];
$totalVentasSemana = $ventasResumen['semana'];
$totalVentasMes = $ventasResumen['mes'];

$totalVentasDiarias_anterior = $ventasResumen['ayer'];
$totalVentasSemana_anterior = $ventasResumen['semana_ant'];
$totalVentasMes_anterior = $ventasResumen['mes_ant'];

$gananciasDi = $ventasResumen['gananciasDia'];
$gananciasSe = $ventasResumen['gananciasSemana'];
$gananciasMes = $ventasResumen['gananciasMes'];

$arraySemana = $ventasResumen['por_dia_semana'];
// OPTIMIZADO



// 1. Cálculo de Stock y Valor en una sola consulta
$stockCond = $sucursal !== "" ? ' AND S.id_sucursal = ' . (int)$sucursal : '';
$res = $conexion->query("
    SELECT 
        SUM(S.stock * (P.precio_compra / IFNULL(NULLIF(P.cantidad_unidades, 0), 1))) as valor_sin_ganancia,
        SUM(S.stock * (P.precio_compra / IFNULL(NULLIF(P.cantidad_unidades, 0), 1)) * (1 + P.porcentaje/100)) as valor_con_ganancia,
        SUM(S.stock) as total_almacen
    FROM productos AS P 
    INNER JOIN stock AS S ON S.id_producto = P.id 
    WHERE P.activo='0' AND P.bss_id = $bss_id $stockCond
");
$row = $res ? $res->fetch_assoc() : [];
$valor_stock_sin_ganancia = (float)($row['valor_sin_ganancia'] ?? 0);
$valor_stock_con_ganancia = (float)($row['valor_con_ganancia'] ?? 0);
$almacen = (int)($row['total_almacen'] ?? 0);
$gananciasEsperadas = $valor_stock_con_ganancia - $valor_stock_sin_ganancia;

// 2. Ejecutar conteos múltiples mediante una sola consulta (si la arquitectura lo permite)
// O al menos simplificar las llamadas existentes:
$ventas = contar2("SELECT COUNT(*) FROM orden WHERE modified = '$hoy' $user_cond AND status IN (1,4) AND ##COND##");
$credit = contar2("SELECT COUNT(*) FROM orden WHERE modified = '$hoy' $user_cond AND status = 2 AND ##COND##");
$stockCritCond = $sucursal !== "" ? ' AND S.id_sucursal = ' . (int)$sucursal : '';
$resCrit = $conexion->query("SELECT COUNT(*) AS total FROM stock S INNER JOIN productos P ON S.id_producto = P.id WHERE S.stock <= $stockCritico AND P.activo = 0 AND P.bss_id = $bss_id $stockCritCond");
$cantidadCritica = $resCrit ? (int)$resCrit->fetch_assoc()['total'] : 0;

// 3. Despachados en una sola consulta (combinando condiciones si es posible)
$resDespachos = $conexion->query("
    SELECT 
        SUM(CASE WHEN o.status NOT IN ('5','5.2') THEN oa.quantity ELSE 0 END) as despachados_hoy,
        SUM(CASE WHEN o.status = '3' AND o.fecha = '$mes' THEN oa.quantity ELSE 0 END) as despachados_mes
    FROM orden_articulos oa
    INNER JOIN orden o ON oa.order_id = o.id
    WHERE o.bss_id = $bss_id $extraCondO $user_cond 
    AND (o.modified = '$hoy' OR o.fecha = '$mes')
");
$rowD = $resDespachos ? $resDespachos->fetch_assoc() : [];
$despachados = (int)($rowD['despachados_hoy'] ?? 0);
$despachados22 = (int)($rowD['despachados_mes'] ?? 0);

// 4. Créditos por cliente (Optimizado: ya estaba bien, pero asegurando tipos)
$creditosPorCliente = [];
$res = $conexion->query("SELECT negocio AS cliente, SUM(total_price) AS total_credito FROM creditos WHERE estado = '2' AND bss_id = $bss_id GROUP BY negocio ORDER BY total_credito DESC LIMIT 20");
if ($res) { while ($row = $res->fetch_assoc()) {
  $creditosPorCliente[] = ['cliente' => $row['cliente'], 'total' => (float)$row['total_credito']];
} }

// 5. Análisis por semanas (Mover lógica a consulta única)
$arraSemanas = [];
$res = $conexion->query("SELECT semana FROM orden WHERE bss_id = $bss_id $extraCond $user_cond GROUP BY semana ORDER BY semana ASC");
if ($res) { while ($row = $res->fetch_assoc()) {
  $s = $row['semana'];
  $arraSemanas[$s] = [10, 10, obtenerImportes('gastos', 'semana', $s)]; // Manteniendo tus valores dummy
} }



// Variables faltantes
$gastosSemana = obtenerImportes('gastos', 'semana', $semana);
$gastosMes = obtenerImportes('gastos', 'fecha', $mes);
$totalVentasMesDejado = $totalVentasMes;

// Salida JSON
echo json_encode([
  'filtro' => $extraCond,
  'totalVentasDiarias' => number_format($totalVentasDiarias, 2, '.', ','),
  'totalVentasSemana' => number_format($totalVentasSemana, 2, '.', ','),
  'totalVentasMes' => number_format($totalVentasMes, 2, '.', ','),
  'VentasDiarias_anterior' => number_format($totalVentasDiarias_anterior, 2, '.', ','),
  'VentasSemana_anterior' => number_format($totalVentasSemana_anterior, 2, '.', ','),
  'VentasMes_anterior' => number_format($totalVentasMes_anterior, 2, '.', ','),
  'gananciasDia' => number_format($gananciasDi, 2, '.', ','),
  'gananciasSemana' => number_format($gananciasSe, 2, '.', ','),
  'gastosSemana' => number_format($gastosSemana, 2, '.', ','),
  'gananciasMes' => number_format($gananciasMes, 2, '.', '.'),
  'gastosMes' => number_format($gastosMes, 2, '.', '.'),
  'ventasHoy' => number_format($ventas, 2, '.', '.'),
  'creditosHoy' => $credit,
  'despachadosHoy' => $despachados,
  'cantidadCritica' => $cantidadCritica,
  'ventasMesDescontado' => number_format($totalVentasMesDejado, 2, '.', ','),
  'almacenProductos' => number_format((int)$almacen, 2, '.', ','),
  'valorStockSinGanancia' => number_format($valor_stock_sin_ganancia, 2, '.', ','),
  'gananciasEsperadas' => number_format($gananciasEsperadas, 2, '.', ','),
  'ventasSemanas' => $arraSemanas,
  'ventasSemana' => $arraySemana,
  'creditosPorCliente' => $creditosPorCliente,
  'topClientes' => $topClientes,
  'ventasPorSucursal' => $ventasPorSucursal,
  'ventasPorHora' => $ventasPorHora,
  'topProductos' => $topProductos
]);
  ob_end_flush();
} catch (Throwable $e) {
  ob_end_clean();
  echo json_encode(['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
}
