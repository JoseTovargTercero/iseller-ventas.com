<?php
require_once("configuracion.php");
require_once('session.php');
header('Content-Type: application/json');
$input = json_decode(file_get_contents("php://input"), true);


$sucursal = ($_SESSION["nivel"] == 1)
  ? ($input['sucursal'] ?? null)
  : $_SESSION["sucursal"];

$periodo_tiempo = $input["periodo"];
$filtro_fecha = $input['fechaSolic'];

$extraCond = $sucursal ? ' AND id_sucursal=' . $sucursal : '';

$extraCond2 = $extraCond != '' ? ' AND o.id_sucursal=' . $sucursal : '';


switch ($periodo_tiempo) {
  case 'dia':
    $today = ($filtro_fecha == '' ? date('Y-m-d') : $filtro_fecha);
    $tipoFiltroColumna = 'modified'; // Por dia
    break;
  case 'semana':
    $today = ($filtro_fecha == '' ? date('Y-Y') : date('Y') . '-' . $filtro_fecha);
    $tipoFiltroColumna = 'semana'; // Por dia
    break;
  case 'mes':
    $today = ($filtro_fecha == '' ? date('Y-m') : date('Y') . '-' . $filtro_fecha);
    $tipoFiltroColumna = 'fecha'; // Por dia
    break;
  default:
    echo json_encode(['status' => 'error', 'mensaje' => 'No se indico un periodo de tiempo']);
    exit;
    break;
}





$total = 0;
$totalVentas = 0;

// Ventas status = 1
$query = "SELECT total_price FROM orden WHERE $tipoFiltroColumna = '$today' AND status = '1' AND bss_id='$bss_id' $extraCond";
$result = $conexion->query($query);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $totalVentas++;
    $total += $row['total_price'];
  }
}

// Ventas status = 4
$total2 = 0;
$totalVentas2 = 0;

$query = "SELECT total_price_bs FROM orden WHERE $tipoFiltroColumna = '$today' AND status = '4' AND bss_id='$bss_id' $extraCond";
$result = $conexion->query($query);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $totalVentas2++;
    $total2 += $row['total_price_bs'];
  }
}

// OBTIENE LAS GANANCIAS POR TIPO DE MONEDA
function returnGanancias($tipo)
{
  global $conexion;
  global $bss_id;
  global $extraCond;
  global $today;
  global $tipoFiltroColumna;
  $ganancias = 0;

  $sqlGanancias = "SELECT * FROM orden WHERE $tipoFiltroColumna='$today' AND status='$tipo' AND bss_id='$bss_id' $extraCond";
  $search = $conexion->query($sqlGanancias);
  if ($search->num_rows > 0) {
    while ($row = $search->fetch_assoc()) {
      $idOrder = $row['id'];
      $descontado = $row['descontado'];

      $query0000003344 = "SELECT * FROM orden_articulos WHERE order_id='$idOrder'";
      $buscarAlumnos0000003344 = $conexion->query($query0000003344);
      if ($buscarAlumnos0000003344->num_rows > 0) {
        while ($filaAlumnos0000003344 = $buscarAlumnos0000003344->fetch_assoc()) {

          $precioCompra = $filaAlumnos0000003344['precio'] * $filaAlumnos0000003344['quantity'];
          $precioVenta = $filaAlumnos0000003344['precio_venta_dolar'] * $filaAlumnos0000003344['quantity'];


          if ($tipo == '4') {
            $precioVenta = $precioVenta - ($precioVenta * $descontado / 100);
            $ganancias += $precioVenta - $precioCompra;
          } else {
            $ganancias += $precioVenta - $precioCompra;
          }
        }
      }
    }
  }
  return $ganancias;
}


// TOTAL POR TIPO DE PAGO
function obtenerTotalPorTipoPago($conexion, $today, $tipoPago, $campoTotal)
{
  global $tipoFiltroColumna;
  global $extraCond;

  global $bss_id;
  $sql = "
         SELECT $campoTotal 
         FROM orden 
         WHERE $tipoFiltroColumna = ? 
         AND tipoPago = ? 
         AND (status = '1' OR status = '4')
         AND bss_id='$bss_id' $extraCond
     ";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("si", $today, $tipoPago);
  $stmt->execute();
  $result = $stmt->get_result();

  $total = 0;
  while ($row = $result->fetch_assoc()) {
    $total += $row[$campoTotal];
  }

  return $total;
}

// Uso de la función para cada tipo de pago
$total_Punto = obtenerTotalPorTipoPago($conexion, $today, 1, 'total_price_bs'); // Punto
$total_Pmovil = obtenerTotalPorTipoPago($conexion, $today, 2, 'total_price_bs'); // Pago movil
$total_Transferencia = obtenerTotalPorTipoPago($conexion, $today, 3, 'total_price_bs'); // Transferencia
$total_Efectivo = obtenerTotalPorTipoPago($conexion, $today, 4, 'total_price_bs'); // efectivo
$total_Dolares = obtenerTotalPorTipoPago($conexion, $today, 5, 'total_price');    // Dólares
$total_pesos = obtenerTotalPorTipoPago($conexion, $today, 6, 'total_price_cop'); // Pesos
$total_Biopago = obtenerTotalPorTipoPago($conexion, $today, 7, 'total_price_bs'); // Biopago
// TOTAL POR TIPO DE PAGO


function obtenerVentasTotalesPorStatus($conexion, $today, $statuses = ['1', '3'])
{
  global $tipoFiltroColumna;
  global $bss_id;
  global $extraCond;

  $statusList = "'" . implode("','", $statuses) . "'";
  $query = "SELECT total_price, total_price_bs, total_price_cop 
         FROM orden 
         WHERE $tipoFiltroColumna = '$today' 
         AND status IN ($statusList)
          AND bss_id='$bss_id' $extraCond";

  $result = $conexion->query($query);


  $total = 0;
  while ($row = $result->fetch_assoc()) {
    $total += $row['total_price'];
  }

  return $total;
}

function calcularGananciaPorTipoPago($conexion, $today, $tipoPago, $campoPrecioArticulo = 'precio', $campoTotalOrden = 'total_price')
{
  global $tipoFiltroColumna;
  global $bss_id;
  global $extraCond;

  $query = "
         SELECT id, $campoTotalOrden AS monto 
         FROM orden 
         WHERE $tipoFiltroColumna = '$today' 
         AND status IN ('1', '4') 
         AND tipoPago = '$tipoPago'
          AND bss_id='$bss_id' $extraCond
     ";

  $result = $conexion->query($query);
  $totalVentas = 0;
  $totalCosto = 0;

  while ($orden = $result->fetch_assoc()) {
    $totalVentas += $orden['monto'];
    $ordenId = $orden['id'];

    $queryArt = "
             SELECT quantity, $campoPrecioArticulo AS precio_unitario 
             FROM orden_articulos 
             WHERE order_id = '$ordenId'
         ";
    $resultArt = $conexion->query($queryArt);
    while ($art = $resultArt->fetch_assoc()) {
      $totalCosto += $art['precio_unitario'] * $art['quantity'];
    }
  }

  return $totalVentas - $totalCosto;
}

// Total de ventas por tipo de despacho
$total_detal = obtenerVentasTotalesPorStatus($conexion, $today);
$total_mayor = obtenerVentasTotalesPorStatus($conexion, $today, [4]);



function calcularGananciaPorMoneda($conexion, $today, $tipoPagos, $campoMontoOrden, $campoPrecioArticulo)
{
  $ganancia = 0;
  $totalVentas = 0;
  $costoTotal = 0;
  global $tipoFiltroColumna;
  global $bss_id;
  global $extraCond;

  // Preparar lista de tipoPago para la consulta
  $tipos = implode("','", $tipoPagos);

  $query = "
         SELECT id, $campoMontoOrden as monto 
         FROM orden 
         WHERE $tipoFiltroColumna = ? 
         AND status IN ('1', '4') 
         AND tipoPago IN ('$tipos')
          AND bss_id='$bss_id' $extraCond
     ";

  $stmt = $conexion->prepare($query);
  $stmt->bind_param("s", $today);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($orden = $result->fetch_assoc()) {
    $ordenId = $orden['id'];
    $totalVentas += $orden['monto'];

    $queryArticulos = "SELECT quantity, $campoPrecioArticulo as precio_unitario FROM orden_articulos WHERE order_id = ?";
    $stmtArt = $conexion->prepare($queryArticulos);
    $stmtArt->bind_param("i", $ordenId);
    $stmtArt->execute();
    $resArt = $stmtArt->get_result();

    while ($articulo = $resArt->fetch_assoc()) {
      $costoTotal += $articulo['precio_unitario'] * $articulo['quantity'];
    }
  }

  $ganancia = $totalVentas - $costoTotal;
  return $ganancia;
}

// Calcular ganancias
$gananciasBolivar = calcularGananciaPorMoneda(
  $conexion,
  $today,
  ['1', '2', '3', '4', '7'],
  'total_price_bs',
  'bolivar'
);

$gananciasPeso = calcularGananciaPorMoneda(
  $conexion,
  $today,
  ['6'],
  'total_price_cop',
  'peso'
);

$gananciasDolares = calcularGananciaPorMoneda(
  $conexion,
  $today,
  ['5'],
  'total_price',
  'precio'
);



/**
 * Formatea un array de montos a un total con dos decimales, separando miles con puntos y decimales con coma.
 *
 * @param array $montos Array de montos a totalizar.
 * @return string Total formateado.
 */

function totalizar_formatear($montos)
{
  $total = 0;
  foreach ($montos as $monto) {
    $total += $monto;
  }
  return number_format($total, 2, '.', ',');
}


$tabla = [];

$query = "
SELECT o.*, u.nombre AS usuario
FROM orden o
JOIN usuarios u ON o.customer_id = u.id
WHERE (o.status = '1'  OR o.status = '2' OR o.status = '4') AND o.$tipoFiltroColumna = '$today'
 AND o.bss_id='$bss_id' $extraCond2
ORDER BY o.id DESC
LIMIT 150
";
$result = $conexion->query($query);
$contador = 1;

$tiposPago = [
  '1' => 'Punto',
  '2' => 'Pago Móvil',
  '3' => 'Transferencia',
  '4' => 'BS Efectivo',
  '5' => 'Dólares',
  '6' => 'Pesos',
  '7' => 'Biopago',
  '8' => 'Fraccionado'
];

while ($row = $result->fetch_assoc()) {
  $orderId = $row['id'];
  $tipoPago = $tiposPago[$row['tipoPago']] ?? '<span class="badge bg-danger">Pendiente</span>';
  $tVenta = $row['status'] == '4' ? 'M' : 'V';

  // Obtener productos
  $productos = [];
  $queryProd = "SELECT oa.quantity, p.nombre
    FROM orden_articulos oa
    JOIN productos p ON oa.product_id = p.id
    WHERE oa.order_id = '$orderId'";
  $resProd = $conexion->query($queryProd);
  while ($prod = $resProd->fetch_assoc()) {
    $productos[] = $prod['quantity'] . ' ' . $prod['nombre'];
  }

  $productosTexto = htmlspecialchars(implode(', ', $productos));
  $tabla[] = [
    'contador' => $contador++,
    'tVenta' => $tVenta,
    'tipoPago' => $tipoPago,
    'usuario' => htmlspecialchars($row['usuario']),
    'created' => $row['created'],
    'total_price' => number_format($row['total_price'], 2, ',', '.'),
    'total_price_cop' => number_format($row['total_price_cop'], 0, ',', '.'),
    'total_price_bs' => number_format($row['total_price_bs'], 2, ',', '.'),
    'detallesLink' => "detallesVenta.php?id={$orderId}",
    'productosTexto' => $productosTexto
  ];
}


echo json_encode([
  // SECTION RIGHT 
  'ganacias_Bolivares' => totalizar_formatear([$gananciasBolivar]) . ' - Ganancias',
  'valor_Bolivares' => totalizar_formatear([$total_Punto, $total_Pmovil, $total_Transferencia, $total_Efectivo, $total_Biopago]),
  'ganacias_Dolares' => totalizar_formatear([$gananciasDolares]) . ' - Ganancias',
  'valor_Dolares' => totalizar_formatear([$total_Dolares]),
  'ganacias_Pesos' => totalizar_formatear([$gananciasPeso]) . ' - Ganancias',
  'valor_Pesos' => totalizar_formatear([$total_pesos]),
  'ganacias_Mayor' => '$' . number_format(returnGanancias('4'), '2', '.', '.') . ' Ganancias.',
  'valor_Mayor' => number_format($total_mayor, '2', '.', ','),
  'ganacias_Detal' => '$' . number_format(returnGanancias('1'), '2', '.', ',') . ' Ganancias.',
  'valor_Detal' => number_format($total_detal, '2', '.', '.'),
  // SECTION LEFT
  'total_Pmovil' => number_format($total_Pmovil, 2, '.', '.'),
  'total_Transferencia' => number_format($total_Transferencia, 2, '.', '.'),
  'total_Biopago' => number_format($total_Biopago, 2, '.', '.'),
  'total_Efectivo' => number_format($total_Efectivo, 2, '.', '.'),
  'total_Dolares' => number_format($total_Dolares, 2, '.', '.'),
  'total_pesos' => number_format($total_pesos, 0, '.', '.'),
  'total_Punto' => number_format($total_Punto, 2, '.', '.'),
  // SECTION TABLE
  'tabla' => $tabla
]);
