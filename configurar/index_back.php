<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

/* CALCULAR VALOR DEL STOCK */


// Decodificar a array asociativo
$data = json_decode(file_get_contents('php://input'), true);


/*
$extraCond = '';
if (@$data["sucursal"] != null) {
  $sucursal = $data["sucursal"];
  $extraCond = ' AND id_sucursal = ' . $sucursal;
}

if ($_SESSION["nivel"] == 2) {
  $sucursal = $_SESSION["sucursal"];
  $extraCond = ' AND id_sucursal = ' . $sucursal;
}*/

$sucursal = $_SESSION["nivel"] == 2 ? $_SESSION["sucursal"] : (@$data["sucursal"] ?? null);
$extraCond = $sucursal !== null ? ' AND id_sucursal = ' . (int)$sucursal : '';



$stockCritico = 10;

// Inicializamos los acumuladores
$valor_stock_con_ganancia = 0;
$valor_stock_sin_ganancia = 0;

// Consulta de productos activos
$stmt = mysqli_prepare($conexion, "SELECT P.precio_compra, P.cantidad_unidades, P.porcentaje, S.stock FROM productos AS P 
LEFT JOIN stock AS S ON S.id_producto = P.id  
WHERE P.activo='0' AND P.bss_id = $bss_id $extraCond");
$stmt->execute();
$resultado = $stmt->get_result();

while ($producto = $resultado->fetch_assoc()) {
  $precio_compra = (float) $producto['precio_compra'];
  $unidades = (float) $producto['cantidad_unidades'];
  $stock = (float) $producto['stock'];
  $porcentaje = (float) $producto['porcentaje'];

  // Evitar división por cero
  if ($unidades <= 0) continue;

  $valor_unitario_compra = $precio_compra / $unidades;
  $valor_unitario_venta = $valor_unitario_compra * (1 + $porcentaje / 100);

  $valor_stock_con_ganancia += $valor_unitario_venta * $stock;
  $valor_stock_sin_ganancia += $valor_unitario_compra * $stock;
}

// Calculamos la ganancia esperada
$gananciasEsperadas = $valor_stock_con_ganancia - $valor_stock_sin_ganancia;


$dia = date('Y-m-d');
$semana = date('Y-W');
$mes = date('Y-m');
$ano = date('Y');



function obtenerImportes($conexion, $tabla, $columna, $valor)
{
  global $bss_id;
  global $extraCond;
  $total = 0;
  $sql = "SELECT importe FROM $tabla WHERE $columna='$valor'  AND bss_id = $bss_id $extraCond";
  $res = $conexion->query($sql);
  while ($row = $res->fetch_assoc()) {
    $total += $row['importe'];
  }
  return $total;
}

$gastosMes = obtenerImportes($conexion, 'gastos', 'mes', $mes);
$gastosSemana = obtenerImportes($conexion, 'gastos', 'semana', $semana);



// Función para obtener total_price de órdenes
function obtenerVentas($conexion, $columna, $valor)
{
  global $bss_id;
  global $extraCond;
  $total = 0;
  $sql = "SELECT total_price FROM orden WHERE ($columna='$valor' AND (status='1' OR status='4'))  AND bss_id = $bss_id $extraCond";
  $res = $conexion->query($sql);
  while ($row = $res->fetch_assoc()) {
    $total += $row['total_price'];
  }
  return $total;
}

$totalVentasDiarias = obtenerVentas($conexion, 'modified', $dia);
$totalVentasSemana = obtenerVentas($conexion, 'semana', $semana);
$totalVentasMes = obtenerVentas($conexion, 'fecha', $mes);


// FECHAS ANTERIORES
$dia_anterior = date('Y-m-d', strtotime('-1 day'));
$semana_anterior = date('Y-W', strtotime('-1 week'));

// Para el mes anterior, hay que tener en cuenta el cambio de año
$mes_anterior = date('Y-m', strtotime('first day of -1 month'));

// Para el año anterior
$ano_anterior = date('Y', strtotime('-1 year'));

$totalVentasDiarias_anterior = obtenerVentas($conexion, 'modified', $dia_anterior);
$totalVentasSemana_anterior = obtenerVentas($conexion, 'semana', $semana_anterior);
$totalVentasMes_anterior = obtenerVentas($conexion, 'fecha', $mes_anterior);


// Función para calcular ganancias
function calcularGanancias($conexion, $columna, $valor)
{
  $ventas = 0;
  $costos = 0;
  global $bss_id;
  global $extraCond;

  $sql = "SELECT id, total_price FROM orden WHERE ($columna='$valor' AND (status='1' OR status='4')) AND bss_id = $bss_id $extraCond";
  $res = $conexion->query($sql);
  while ($row = $res->fetch_assoc()) {
    $ventas += $row['total_price'];
    $orden_id = $row['id'];
    $articulos = $conexion->query("SELECT precio, quantity FROM orden_articulos WHERE order_id='$orden_id'");
    while ($articulo = $articulos->fetch_assoc()) {
      $costos += $articulo['precio'] * $articulo['quantity'];
    }
  }
  return $ventas - $costos;
}


$gananciasDi = calcularGanancias($conexion, 'modified', $dia);
$gananciasSe = calcularGanancias($conexion, 'semana', $semana);
$gananciasMes = calcularGanancias($conexion, 'fecha', $mes);


// Contadores
$ventas = contar("SELECT COUNT(*) FROM orden WHERE (modified='$dia' AND (status='1' OR status='4'))");
$credit = contar("SELECT COUNT(*) FROM orden WHERE modified='$dia' AND status='2'");
$cantidadCritica = contar("SELECT COUNT(*) FROM productos WHERE stock<='$stockCritico' AND activo='0'"); // TODO HERE



// Productos despachados hoy
$despachados = 0;
$res = $conexion->query("SELECT id FROM orden WHERE modified='$dia' AND status NOT IN ('5', '5.2')  AND bss_id = $bss_id $extraCond");
while ($row = $res->fetch_assoc()) {
  $articulos = $conexion->query("SELECT quantity FROM orden_articulos WHERE order_id='{$row['id']}'");
  while ($articulo = $articulos->fetch_assoc()) {
    $despachados += $articulo['quantity'];
  }
}
$despachados = $despachados ?: 0;


// Productos despachados en el mes (status 3)
$despachados22 = 0;
$res = $conexion->query("SELECT id FROM orden WHERE fecha='$mes' AND status='3' AND bss_id = $bss_id $extraCond");
while ($row = $res->fetch_assoc()) {
  $articulos = $conexion->query("SELECT quantity FROM orden_articulos WHERE order_id='{$row['id']}'");
  while ($articulo = $articulos->fetch_assoc()) {
    $despachados22 += $articulo['quantity'];
  }
}
$despachados22 = $despachados22 ?: 0;


// Ventas confirmadas con status 3 del mes
$totalVentasMesDejado = 0;
$res = $conexion->query("SELECT total_price FROM orden WHERE fecha='$mes' AND status='3' AND bss_id = $bss_id $extraCond");
while ($row = $res->fetch_assoc()) {
  $totalVentasMesDejado += $row['total_price'];
}


// Stock actual en almacén
$almacen = 0;
$query = "
  SELECT stock.stock
  FROM stock
  LEFT JOIN productos AS P ON P.id = stock.id_producto
  WHERE stock.bss_id = $bss_id AND P.activo = 0 $extraCond
";

$res = $conexion->query($query);
while ($row = $res->fetch_assoc()) {
  $almacen += $row['stock'];
}


// Funciones adicionales para estadísticas semanales
function ventasSemana($semana)
{
  global $conexion;
  return round(obtenerVentas($conexion, 'semana', $semana), 1, PHP_ROUND_HALF_DOWN);
}

function gananciasSemana($semana)
{
  global $conexion;
  $ganancia = calcularGanancias($conexion, 'semana', $semana);
  return round($ganancia ?: 0, 2, PHP_ROUND_HALF_DOWN);
}

function gastosSemana($semana)
{
  global $conexion;
  return obtenerImportes($conexion, 'gastos', 'semana', $semana);
}

// Análisis por semanas
$arraSemanas = [];
$res = $conexion->query("SELECT DISTINCT semana FROM orden WHERE bss_id = $bss_id $extraCond ORDER BY semana ASC");
while ($row = $res->fetch_assoc()) {
  $semanaC = $row['semana'];
  $ventas = ventasSemana($semanaC);
  $gastos = gastosSemana($semanaC);
  $gananciaNeta = gananciasSemana($semanaC) - $gastos;
  $arraSemanas[$semanaC] = [$ventas, $gananciaNeta, $gastos];
}



function obtenerVentasPorDia($conexion, $columna, $valor)
{
  global $bss_id;
  global $extraCond;

  // Mapea los números de días (1 a 7) a nombres de días en español
  $diasSemana = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miercoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sabado',
    7 => 'Domingo'
  ];

  $ventasPorDia = array_fill_keys($diasSemana, 0); // Inicializa con 0 cada día

  $sql = "SELECT dia, total_price 
            FROM orden 
            WHERE ($columna = '$valor' AND (status = '1' OR status = '4')) 
              AND bss_id = $bss_id 
              $extraCond";

  $res = $conexion->query($sql);

  while ($row = $res->fetch_assoc()) {
    $diaNum = (int)$row['dia']; // Asegura que sea entero del 1 al 7
    if (isset($diasSemana[$diaNum])) {
      $nombreDia = $diasSemana[$diaNum];
      $ventasPorDia[$nombreDia] += number_format($row['total_price'], '2', '.', '');
    }
  }

  return $ventasPorDia;
}

$arraySemana = obtenerVentasPorDia($conexion, 'semana', $semana);







echo json_encode([
  'filtro' => $extraCond,
  'totalVentasDiarias'     => number_format($totalVentasDiarias, '1', '.', ','), // Listo
  'totalVentasSemana'      => number_format($totalVentasSemana, '1', '.', ','), // Listo
  'totalVentasMes'         => number_format($totalVentasMes, '1', '.', ','), // Listo
  'VentasDiarias_anterior' => number_format($totalVentasDiarias_anterior, '1', '.', ','), // Listo
  'VentasSemana_anterior'  => number_format($totalVentasSemana_anterior, '1', '.', ','), // Listo
  'VentasMes_anterior'     => number_format($totalVentasMes_anterior, '1', '.', ','), // Listo
  'gananciasDia'           => number_format($gananciasDi, '1', '.', ','),
  'gananciasSemana'        => number_format($gananciasSe, '1', '.', ','),
  'gastosSemana'           => number_format($gastosSemana, '1', '.', ','),
  'gananciasMes'           => number_format($gananciasMes, '1', '.', '.'), // Total de ventas Listo
  'gastosMes'              => number_format($gastosMes, '1', '.', '.'), // Total de ventas, Listo
  'ventasHoy'              => number_format($ventas, '1', '.', '.'), // Total de ventas, Listo
  'creditosHoy'            => (int)$credit,
  'despachadosHoy'         => (int)$despachados,
  'cantidadCritica'        => (int)$cantidadCritica,
  'ventasMesDescontado'    => number_format($totalVentasMesDejado, '0', '.', ','),
  'almacenProductos'       => number_format((int)$almacen, '0', '.', ','),
  'valorStockSinGanancia'  => number_format($valor_stock_sin_ganancia, '1', '.', ','),
  'gananciasEsperadas'     => number_format($gananciasEsperadas, '1', '.', ','),
  'ventasSemanas'          => $arraSemanas, // Este ya está en formato array asociativo*/
  'ventasSemana'           => $arraySemana
]);
