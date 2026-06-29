<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

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

// Top compradores
$topClientes = [];
$res = $conexion->query("SELECT o.cliente, c.nombre, SUM(o.total_price) AS total_gastado FROM orden o LEFT JOIN clientes c ON o.cliente = c.cedula AND c.bss_id = $bss_id WHERE o.status IN (1,4) AND o.bss_id = $bss_id AND o.cliente IS NOT NULL AND o.cliente != '' $extraCond $user_cond $periodCond GROUP BY o.cliente ORDER BY total_gastado DESC LIMIT 15");
while ($row = $res->fetch_assoc()) {
  $topClientes[] = ['cedula' => $row['cliente'], 'nombre' => $row['nombre'] ?? $row['cliente'], 'total' => (float)$row['total_gastado']];
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
$res = $conexion->query("SELECT s.nombre AS sucursal, SUM(o.total_price) AS total FROM orden o INNER JOIN sucursales s ON o.id_sucursal = s.id WHERE o.status IN (1,4) AND o.bss_id = $bss_id $extraCond $periodCondPie GROUP BY o.id_sucursal ORDER BY total DESC");
while ($row = $res->fetch_assoc()) {
  $ventasPorSucursal[] = ['sucursal' => $row['sucursal'], 'total' => (float)$row['total']];
}

// Ventas promedio por hora (últimos 7 días)
$ventasPorHora = array_fill(0, 24, 0);
$res = $conexion->query("SELECT HOUR(o.created) AS hora, SUM(o.total_price) AS total, COUNT(DISTINCT DATE(o.created)) AS dias FROM orden o WHERE o.created >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND o.status IN (1,4) AND o.bss_id = $bss_id $extraCond $user_cond GROUP BY HOUR(o.created) ORDER BY hora");
while ($row = $res->fetch_assoc()) {
  $h = (int)$row['hora'];
  $dias = (int)$row['dias'];
  $ventasPorHora[$h] = $dias > 0 ? round((float)$row['total'] / $dias, 2) : 0;
}

// Top 10 productos más vendidos (ingreso = precio_compra * (1 + %/100) * cantidad)
$topProductos = [];
$res = $conexion->query("SELECT oa.product_id, COALESCE(p.nombre, 'Producto #' + oa.product_id) AS producto, SUM(oa.quantity) AS total_vendido, precio_venta_dolar AS ingreso_total FROM orden_articulos oa INNER JOIN orden o ON oa.order_id = o.id LEFT JOIN productos p ON oa.product_id = p.id WHERE o.status IN (1,4) AND o.bss_id = $bss_id $extraCond $user_cond GROUP BY oa.product_id ORDER BY total_vendido DESC LIMIT 10");
while ($row = $res->fetch_assoc()) {
  $topProductos[] = [
    'producto' => $row['producto'],
    'cantidad' => (int)$row['total_vendido'],
    'ingreso' => (float)$row['ingreso_total']
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
/*
function obtenerVentas($columna, $valor, $status = [1, 4])
{
  global $conexion, $bss_id, $extraCond;
  $statusList = implode(",", array_map('intval', $status));
  $sql = "SELECT SUM(total_price) AS total FROM orden WHERE $columna = '$valor' AND status IN ($statusList) AND bss_id = $bss_id $extraCond";
  $res = $conexion->query($sql);
  return $res ? (float)$res->fetch_assoc()['total'] : 0;
}*/


// Ventas por dia de la semana
$diasSemana = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'];
$arraySemana = array_fill_keys($diasSemana, 0);
$res = $conexion->query("SELECT dia, total_price FROM orden WHERE semana = '$semana' AND status IN (1,4) AND bss_id = $bss_id $extraCond $user_cond");
while ($row = $res->fetch_assoc()) {
  $dia = (int)$row['dia'];
  if (isset($diasSemana[$dia])) $arraySemana[$diasSemana[$dia]] += (float)$row['total_price'];
}











function obtenerVentas($bss_id, $extraCond)
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

  $ordenes = [];

  $res = $conexion->query("SELECT id, total_price, modified, semana, fecha, dia FROM orden WHERE (fecha = '$mes' OR fecha = '$mes_ant') AND bss_id = $bss_id AND status IN (1,4) $extraCond $user_cond");
  while ($row = $res->fetch_assoc()) {
    $id = $row['id'];
    $precio = (float)$row['total_price'];
    $mod = $row['modified'];
    $sem = $row['semana'];
    $fec = $row['fecha'];
    $diaN = (int)$row['dia'];

    if ($mod === $hoy) $ventas['hoy'] += $precio;
    if ($mod === $dia_ant) $ventas['ayer'] += $precio;
    if ($sem === $semana) $ventas['semana'] += $precio;
    if ($sem === $semana_ant) $ventas['semana_ant'] += $precio;
    if ($fec === $mes) $ventas['mes'] += $precio;
    if ($fec === $mes_ant) $ventas['mes_ant'] += $precio;

    $nombreDia = $diasSemana[$diaN] ?? null;
    if ($nombreDia && $sem === $semana) $ventas['por_dia_semana'][$nombreDia] += $precio;

    $ordenes[$id] = ['mod' => $mod, 'sem' => $sem, 'fec' => $fec, 'total' => $precio];
  }

  $costos = [
    'dia' => 0,
    'semana' => 0,
    'mes' => 0
  ];

  $ids = implode(',', array_keys($ordenes));
  if ($ids) {
    $res = $conexion->query("SELECT order_id, precio, quantity FROM orden_articulos WHERE order_id IN ($ids)");
    while ($row = $res->fetch_assoc()) {
      $order_id = $row['order_id'];
      $costo = $row['precio'] * $row['quantity'];
      $o = $ordenes[$order_id];
      if ($o['mod'] === $hoy) $costos['dia'] += $costo;
      if ($o['sem'] === $semana) $costos['semana'] += $costo;
      if ($o['fec'] === $mes) $costos['mes'] += $costo;
    }
  }

  $ventas['gananciasDia'] = $ventas['hoy'] - $costos['dia'];
  $ventas['gananciasSemana'] = $ventas['semana'] - $costos['semana'];
  $ventas['gananciasMes'] = $ventas['mes'] - $costos['mes'];

  return $ventas;
}

$ventasResumen = obtenerVentas($bss_id, $extraCond);



// Uso de la variable como reemplazo a las llamadas anteriores
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















/*


function calcularGanancias($columna, $valor)
{
  global $conexion, $bss_id, $extraCond;
  $ventas = 0;
  $costos = 0;
  $ordenes = $conexion->query("SELECT id, total_price FROM orden WHERE $columna = '$valor' AND status IN (1, 4) AND bss_id = $bss_id $extraCond");
  while ($o = $ordenes->fetch_assoc()) {
    $ventas += $o['total_price'];
    $articulos = $conexion->query("SELECT precio, quantity FROM orden_articulos WHERE order_id = {$o['id']}");
    while ($a = $articulos->fetch_assoc()) {
      $costos += $a['precio'] * $a['quantity'];
    }
  }
  return $ventas - $costos;
}*/

// Valor de stock
$valor_stock_con_ganancia = 0;
$valor_stock_sin_ganancia = 0;
$res = $conexion->query("SELECT P.precio_compra, P.cantidad_unidades, P.porcentaje, S.stock 
  FROM productos AS P 
  LEFT JOIN stock AS S ON S.id_producto = P.id 
  WHERE P.activo='0' AND P.bss_id = $bss_id $extraCond");

while ($row = $res->fetch_assoc()) {
  $unidad = (float) $row['cantidad_unidades'] ?: 1;
  $unit_cost = (float) $row['precio_compra'] / $unidad;
  $unit_sale = (float)  $unit_cost * (1 + (float)  $row['porcentaje'] / 100);
  $valor_stock_con_ganancia += $unit_sale * $row['stock'];
  $valor_stock_sin_ganancia += $unit_cost * $row['stock'];
}
$gananciasEsperadas = $valor_stock_con_ganancia - $valor_stock_sin_ganancia;
/*
// Ventas y ganancias
$totalVentasDiarias = obtenerVentas('modified', $hoy);
$totalVentasSemana = obtenerVentas('semana', $semana);
$totalVentasMes = obtenerVentas('fecha', $mes);
$totalVentasDiarias_anterior = obtenerVentas('modified', $dia_ant);
$totalVentasSemana_anterior = obtenerVentas('semana', $semana_ant);
$totalVentasMes_anterior = obtenerVentas('fecha', $mes_ant);
$gananciasDi = calcularGanancias('modified', $hoy);
$gananciasSe = calcularGanancias('semana', $semana);
$gananciasMes = calcularGanancias('fecha', $mes);*/
$gastosSemana = obtenerImportes('gastos', 'semana', $semana);
$gastosMes = obtenerImportes('gastos', 'mes', $mes);

$ventas = contar2("SELECT COUNT(*) FROM orden WHERE modified = '$hoy' $user_cond AND status IN (1,4) AND ##COND##");
$credit = contar2("SELECT COUNT(*) FROM orden WHERE modified = '$hoy' $user_cond AND status = 2 AND ##COND##");
$cantidadCritica = contar2("SELECT COUNT(*) FROM productos WHERE stock <= $stockCritico AND activo = 0 AND ##COND##");

$despachados = 0;
$res = $conexion->query("SELECT id FROM orden WHERE modified = '$hoy' AND status NOT IN ('5','5.2') AND bss_id = $bss_id $extraCond $user_cond");
while ($row = $res->fetch_assoc()) {
  $arts = $conexion->query("SELECT quantity FROM orden_articulos WHERE order_id = {$row['id']}");
  while ($a = $arts->fetch_assoc()) $despachados += $a['quantity'];
}

$despachados22 = 0;
$res = $conexion->query("SELECT id FROM orden WHERE fecha = '$mes' AND status = '3' AND bss_id = $bss_id $extraCond $user_cond");
while ($row = $res->fetch_assoc()) {
  $arts = $conexion->query("SELECT quantity FROM orden_articulos WHERE order_id = {$row['id']}");
  while ($a = $arts->fetch_assoc()) $despachados22 += $a['quantity'];
}

//$totalVentasMesDejado = obtenerVentas('fecha', $mes, [3]);
$totalVentasMesDejado = 0; //TODO arreglar

$almacen = 0;
$res = $conexion->query("SELECT stock.stock FROM stock LEFT JOIN productos AS P ON P.id = stock.id_producto WHERE stock.bss_id = $bss_id AND P.activo = 0 $extraCond ");
while ($row = $res->fetch_assoc()) $almacen += $row['stock'];

// Análisis por semanas
// Créditos por cliente (estado=2 activos, agrupados por negocio/cedula)
$creditosPorCliente = [];
$res = $conexion->query("SELECT negocio AS cliente, SUM(total_price) AS total_credito FROM creditos WHERE estado = '2' AND bss_id = $bss_id GROUP BY negocio ORDER BY total_credito DESC LIMIT 20");
while ($row = $res->fetch_assoc()) {
  $creditosPorCliente[] = ['cliente' => $row['cliente'], 'total' => (float)$row['total_credito']];
}

$arraSemanas = [];
$res = $conexion->query("SELECT DISTINCT semana FROM orden WHERE bss_id = $bss_id $extraCond  $user_cond ORDER BY semana ASC");
while ($row = $res->fetch_assoc()) {
  $semanaC = $row['semana'];
  //$ventasS = obtenerVentas('semana', $semanaC);
  $ventasS = 10;
  //$gananciaNeta = calcularGanancias('semana', $semanaC) - obtenerImportes('gastos', 'semana', $semanaC);
  $gananciaNeta = 10;
  $gasto = obtenerImportes('gastos', 'semana', $semanaC);
  $arraSemanas[$semanaC] = [$ventasS, $gananciaNeta, $gasto];
}



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
