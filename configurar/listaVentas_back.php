<?php
/**
 * Script para obtener los datos de la tabla de ventas y sus métricas.
 * Se ha optimizado para reducir las consultas N+1 y agrupar los resultados,
 * mejorando significativamente el rendimiento y legibilidad.
 */
require_once("configuracion.php");
require_once('session.php');
header('Content-Type: application/json');

// Obtener datos del payload
$input = json_decode(file_get_contents("php://input"), true);

// Determinar la sucursal según el nivel del usuario
$sucursal = ($_SESSION["nivel"] == 1) ? ($input['sucursal'] ?? null) : $_SESSION["sucursal"];

// Variables base
$periodo_tiempo = $input["periodo"] ?? '';
$filtro_fecha = $input['fechaSolic'] ?? '';

// Construir condiciones adicionales (usamos alias 'o' para todas las consultas a la tabla orden)
$extraCond = $sucursal ? " AND o.id_sucursal=" . (int)$sucursal : '';

$user_cond = "";
if ($extraCond != '' && isset($input['usuario']) && $input['usuario'] != 'todos') {
    $usuario = (int)$input['usuario'];
    $user_cond = " AND o.customer_id = $usuario";
}

// Determinar periodo de tiempo y columna a filtrar
switch ($periodo_tiempo) {
    case 'dia':
        $today = empty($filtro_fecha) ? date('Y-m-d') : $filtro_fecha;
        $tipoFiltroColumna = 'modified'; 
        break;
    case 'semana':
        $today = empty($filtro_fecha) ? date('Y-Y') : date('Y') . '-' . $filtro_fecha;
        $tipoFiltroColumna = 'semana';
        break;
    case 'mes':
        $today = empty($filtro_fecha) ? date('Y-m') : date('Y') . '-' . $filtro_fecha;
        $tipoFiltroColumna = 'fecha';
        break;
    default:
        echo json_encode(['status' => 'error', 'mensaje' => 'No se indico un periodo de tiempo']);
        exit;
}

// -------------------------------------------------------------------
// 1. OBTENER TOTALES POR TIPO DE PAGO
// -------------------------------------------------------------------
$totalesPago = [
    'Punto' => 0, 'Pmovil' => 0, 'Transferencia' => 0, 'Efectivo' => 0, 
    'Dolares' => 0, 'Pesos' => 0, 'Biopago' => 0
];

$sqlTotalesPago = "
    SELECT o.tipoPago, SUM(o.total_price) as sum_usd, SUM(o.total_price_bs) as sum_bs, SUM(o.total_price_cop) as sum_cop
    FROM orden o
    WHERE o.$tipoFiltroColumna = '$today' AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
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

// -------------------------------------------------------------------
// 2. OBTENER TOTALES DE VENTAS POR STATUS (Detal = 1,3 / Mayor = 4)
// -------------------------------------------------------------------
$total_detal = 0;
$total_mayor = 0;

$sqlVentasStatus = "
    SELECT o.status, SUM(o.total_price) as total_usd
    FROM orden o
    WHERE o.$tipoFiltroColumna = '$today' AND o.status IN ('1', '3', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
    GROUP BY o.status
";
$resultVentasStatus = $conexion->query($sqlVentasStatus);
if ($resultVentasStatus) {
    while ($row = $resultVentasStatus->fetch_assoc()) {
        if (in_array($row['status'], ['1', '3'])) {
            $total_detal += $row['total_usd'];
        } elseif ($row['status'] == '4') {
            $total_mayor += $row['total_usd'];
        }
    }
}

// -------------------------------------------------------------------
// 3. CALCULAR GANANCIAS POR TIPO DE VENTA (Detal y Mayor)
// -------------------------------------------------------------------
function obtenerGananciasPorTipoVenta($conexion, $tipo, $today, $tipoFiltroColumna, $bss_id, $extraCond, $user_cond) {
    $sql = "
        SELECT SUM(
            (oa.precio_venta_dolar * oa.quantity * CASE WHEN o.status = '4' THEN (1 - COALESCE(o.descontado, 0) / 100) ELSE 1 END) 
            - (oa.precio * oa.quantity)
        ) AS ganancia
        FROM orden o
        JOIN orden_articulos oa ON oa.order_id = o.id
        WHERE o.$tipoFiltroColumna = '$today' AND o.status = '$tipo' AND o.bss_id = '$bss_id' $extraCond $user_cond
    ";
    $res = $conexion->query($sql);
    $row = $res ? $res->fetch_assoc() : ['ganancia' => 0];
    return $row['ganancia'] ? (float)$row['ganancia'] : 0;
}

$ganancia_detal = obtenerGananciasPorTipoVenta($conexion, '1', $today, $tipoFiltroColumna, $bss_id, $extraCond, $user_cond);
$ganancia_mayor = obtenerGananciasPorTipoVenta($conexion, '4', $today, $tipoFiltroColumna, $bss_id, $extraCond, $user_cond);

// -------------------------------------------------------------------
// 4. CALCULAR GANANCIAS POR MONEDA
// -------------------------------------------------------------------
function obtenerGananciasPorMoneda($conexion, $today, $tipoFiltroColumna, $bss_id, $extraCond, $user_cond) {
    // Calculamos las ventas y costos en una sola consulta para todas las monedas
    $sql = "
        SELECT o.tipoPago, o.total_price_bs, o.total_price_cop, o.total_price,
               COALESCE((SELECT SUM(oa.bolivar * oa.quantity) FROM orden_articulos oa WHERE oa.order_id = o.id), 0) as costo_bolivar,
               COALESCE((SELECT SUM(oa.peso * oa.quantity) FROM orden_articulos oa WHERE oa.order_id = o.id), 0) as costo_peso,
               COALESCE((SELECT SUM(oa.precio * oa.quantity) FROM orden_articulos oa WHERE oa.order_id = o.id), 0) as costo_dolar
        FROM orden o
        WHERE o.$tipoFiltroColumna = '$today' AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
    ";
    
    $res = $conexion->query($sql);
    $ganancias = ['Bolivar' => 0, 'Peso' => 0, 'Dolar' => 0];
    
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $tipoPago = $row['tipoPago'];
            if (in_array($tipoPago, ['1', '2', '3', '4', '7'])) {
                $ganancias['Bolivar'] += ($row['total_price_bs'] - $row['costo_bolivar']);
            }
            if ($tipoPago == '6') {
                $ganancias['Peso'] += ($row['total_price_cop'] - $row['costo_peso']);
            }
            if ($tipoPago == '5') {
                $ganancias['Dolar'] += ($row['total_price'] - $row['costo_dolar']);
            }
        }
    }
    return $ganancias;
}

$gananciasMoneda = obtenerGananciasPorMoneda($conexion, $today, $tipoFiltroColumna, $bss_id, $extraCond, $user_cond);

// -------------------------------------------------------------------
// 5. OBTENER DATOS DE LA TABLA DE VENTAS
// -------------------------------------------------------------------
$tabla = [];

// Usamos GROUP_CONCAT para traer los productos en la misma consulta y evitar N+1 en la tabla
$queryTabla = "
    SELECT o.*, u.nombre AS usuario, 
           (SELECT GROUP_CONCAT(CONCAT(oa.quantity, ' ', p.nombre) SEPARATOR ', ')
            FROM orden_articulos oa 
            JOIN productos p ON oa.product_id = p.id 
            WHERE oa.order_id = o.id) as productosTexto
    FROM orden o
    LEFT JOIN usuarios u ON o.customer_id = u.id
    WHERE (o.status = '1' OR o.status = '2' OR o.status = '4') 
      AND o.$tipoFiltroColumna = '$today'
      AND o.bss_id = '$bss_id' 
      $extraCond $user_cond
    ORDER BY o.id DESC
    LIMIT 150
";

$resultTabla = $conexion->query($queryTabla);
$contador = 1;

$tiposPagoTexto = [
    '1' => 'Punto',
    '2' => 'Pago Móvil',
    '3' => 'Transferencia',
    '4' => 'BS Efectivo',
    '5' => 'Dólares',
    '6' => 'Pesos',
    '7' => 'Biopago',
    '8' => 'Fraccionado'
];

if ($resultTabla) {
    while ($row = $resultTabla->fetch_assoc()) {
        $orderId = $row['id'];
        $tipoPago = $tiposPagoTexto[$row['tipoPago']] ?? '<span class="badge bg-danger">Pendiente</span>';
        $tVenta = $row['status'] == '4' ? 'M' : 'V';
        $productosTexto = htmlspecialchars($row['productosTexto'] ?? '');

        $tabla[] = [
            'contador' => $contador++,
            'tVenta' => $tVenta,
            'tipoPago' => $tipoPago,
            'usuario' => htmlspecialchars($row['usuario'] ?? 'N/A'),
            'created' => $row['created'],
            'total_price' => number_format($row['total_price'], 2, ',', '.'),
            'total_price_cop' => number_format($row['total_price_cop'], 0, ',', '.'),
            'total_price_bs' => number_format($row['total_price_bs'], 2, ',', '.'),
            'detallesLink' => "detallesVenta.php?id={$orderId}",
            'productosTexto' => $productosTexto
        ];
    }
}

// -------------------------------------------------------------------
// 6. FORMATEAR Y RESPONDER JSON
// -------------------------------------------------------------------
echo json_encode([
    // SECTION RIGHT 
    'ganacias_Bolivares' => number_format($gananciasMoneda['Bolivar'], 2, '.', ',') . ' - Ganancias',
    'valor_Bolivares'    => number_format($totalesPago['Punto'] + $totalesPago['Pmovil'] + $totalesPago['Transferencia'] + $totalesPago['Efectivo'] + $totalesPago['Biopago'], 2, '.', ','),
    'ganacias_Dolares'   => number_format($gananciasMoneda['Dolar'], 2, '.', ',') . ' - Ganancias',
    'valor_Dolares'      => number_format($totalesPago['Dolares'], 2, '.', ','),
    'ganacias_Pesos'     => number_format($gananciasMoneda['Peso'], 2, '.', ',') . ' - Ganancias',
    'valor_Pesos'        => number_format($totalesPago['Pesos'], 2, '.', ','),
    'ganacias_Mayor'     => '$' . number_format($ganancia_mayor, 2, '.', ',') . ' Ganancias.',
    'valor_Mayor'        => number_format($total_mayor, 2, '.', ','),
    'ganacias_Detal'     => '$' . number_format($ganancia_detal, 2, '.', ',') . ' Ganancias.',
    'valor_Detal'        => number_format($total_detal, 2, '.', ','),
    
    // SECTION LEFT
    'total_Pmovil'        => number_format($totalesPago['Pmovil'], 2, '.', ','),
    'total_Transferencia' => number_format($totalesPago['Transferencia'], 2, '.', ','),
    'total_Biopago'       => number_format($totalesPago['Biopago'], 2, '.', ','),
    'total_Efectivo'      => number_format($totalesPago['Efectivo'], 2, '.', ','),
    'total_Dolares'       => number_format($totalesPago['Dolares'], 2, '.', ','),
    'total_pesos'         => number_format($totalesPago['Pesos'], 0, '.', ','),
    'total_Punto'         => number_format($totalesPago['Punto'], 2, '.', ','),
    
    // SECTION TABLE
    'tabla' => $tabla
]);

