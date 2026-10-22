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

// ===================================================================
// ACTION: totales_por_usuario
// ===================================================================
if (($input['action'] ?? $_GET['action'] ?? '') === 'totales_por_usuario') {
    $filtro_fecha_u = $input['fechaSolic'] ?? $_GET['fechaSolic'] ?? date('Y-m-d');
    if (empty($filtro_fecha_u))
        $filtro_fecha_u = date('Y-m-d');

    $sucursal_u = ($_SESSION["nivel"] == 1) ? ($input['sucursal'] ?? $_GET['sucursal'] ?? null) : $_SESSION["sucursal"];
    $extraCond_u = $sucursal_u ? " AND c.sucursal_id=" . (int) $sucursal_u : '';

    // Obtener todos los cortes de caja del día (apertura y cierre), con el nombre del usuario
    $sqlCortes = "
        SELECT c.*, u.nombre as nombre_usuario
        FROM cortes_de_caja c
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.fecha = '$filtro_fecha_u'
          AND c.bss_id = '$bss_id'
          $extraCond_u
        ORDER BY c.creado_en ASC, c.id ASC
    ";

    $resCortes = $conexion->query($sqlCortes);
    $usuarios_cortes = [];

    if ($resCortes) {
        while ($row = $resCortes->fetch_assoc()) {
            $uid = $row['usuario_id'];
            if (!isset($usuarios_cortes[$uid])) {
                $usuarios_cortes[$uid] = [
                    'usuario_id' => $uid,
                    'nombre' => $row['nombre_usuario'] ?? "Usuario #{$uid}",
                    'apertura' => null,
                    'cierre' => null,
                    'sort_time' => $row['creado_en'] ?? $row['id']
                ];
            }

            if ($row['tipo_corte'] === 'apertura') {
                $usuarios_cortes[$uid]['apertura'] = [
                    'efectivo_bs' => (float) $row['efectivo_bs_fondo'],
                    'dolares' => (float) $row['efectivo_usd_fondo'],
                    'pesos' => (float) $row['pesos_fondo'],
                    'observaciones' => $row['observaciones'],
                    'hora_apertura' => date('H:i:s', strtotime($row['creado_en']))
                ];
            } elseif ($row['tipo_corte'] === 'cierre') {
                $usuarios_cortes[$uid]['cierre'] = [
                    'contado' => [
                        'efectivo_bs' => (float) $row['efectivo_bs_contado'],
                        'dolares' => (float) $row['efectivo_usd_contado'],
                        'pesos' => (float) $row['pesos_contado'],
                        'punto' => (float) $row['punto_contado'],
                        'biopago' => (float) $row['biopago_contado'],
                        'pago_movil' => (float) $row['pago_movil_contado'],
                        'transferencia' => (float) $row['transferencia_contado']
                    ],
                    'sistema' => [
                        'efectivo_bs' => (float) $row['efectivo_bs_sistema'],
                        'dolares' => (float) $row['efectivo_usd_sistema'],
                        'pesos' => (float) $row['pesos_sistema'],
                        'punto' => (float) $row['punto_sistema'],
                        'biopago' => (float) $row['biopago_sistema'],
                        'pago_movil' => (float) $row['pago_movil_sistema'],
                        'transferencia' => (float) $row['transferencia_sistema']
                    ],
                    'diferencia' => [
                        'efectivo_bs' => (float) $row['diferencia_efectivo_bs'],
                        'dolares' => (float) $row['diferencia_efectivo_usd'],
                        'pesos' => (float) $row['diferencia_pesos'],
                        'punto' => (float) $row['diferencia_punto'],
                        'biopago' => (float) $row['diferencia_biopago'],
                        'pago_movil' => (float) $row['diferencia_pago_movil'],
                        'transferencia' => (float) $row['diferencia_transferencia']
                    ],
                    'fondo_dejado' => [
                        'efectivo_bs' => (float) $row['efectivo_bs_fondo'],
                        'dolares' => (float) $row['efectivo_usd_fondo'],
                        'pesos' => (float) $row['pesos_fondo']
                    ],
                    'observaciones' => $row['observaciones']
                ];
            }
        }
    }

    // Ordenar por hora de apertura (sort_time)
    $cortes = array_values($usuarios_cortes);
    usort($cortes, function ($a, $b) {
        return $a['sort_time'] <=> $b['sort_time'];
    });

    echo json_encode([
        'status' => 'success',
        'cortes' => $cortes
    ]);
    exit;
}

// Determinar la sucursal según el nivel del usuario
$sucursal = ($_SESSION["nivel"] == 1) ? ($input['sucursal'] ?? null) : $_SESSION["sucursal"];

// Variables base
$periodo_tiempo = $input["periodo"] ?? '';
$filtro_fecha = $input['fechaSolic'] ?? '';

// Construir condiciones adicionales (usamos alias 'o' para todas las consultas a la tabla orden)
$extraCond = $sucursal ? " AND o.id_sucursal=" . (int) $sucursal : '';

$user_cond = "";
if ($extraCond != '' && isset($input['usuario']) && $input['usuario'] != 'todos') {
    $usuario = (int) $input['usuario'];
    $user_cond = " AND o.usuario = $usuario";
}

// Determinar periodo de tiempo. Se filtra siempre por la fecha real de la venta
// (orden.created); no por modified/fecha/semana para que todos los reportes cuadren.
$filtroPeriodo = '';
switch ($periodo_tiempo) {
    case 'dia':
        $fechaDia = trim((string) $filtro_fecha);
        $objFecha = DateTime::createFromFormat('Y-m-d', $fechaDia);
        if (!$objFecha || $objFecha->format('Y-m-d') !== $fechaDia) {
            $fechaDia = date('Y-m-d');
        }
        $today = $fechaDia;
        $filtroPeriodo = "DATE(o.created) = '$today'";
        break;
    case 'semana':
        // El selector manda el número de semana (ej. "5"). Se usa año-semana ISO.
        $semanaNum = (int) $filtro_fecha;
        if ($semanaNum < 1 || $semanaNum > 53) {
            $semanaNum = (int) date('W');
        }
        $today = date('o') . '-' . str_pad($semanaNum, 2, '0', STR_PAD_LEFT);
        $filtroPeriodo = "DATE_FORMAT(o.created, '%x-%v') = '$today'";
        break;
    case 'mes':
        $mesNum = (int) $filtro_fecha;
        if ($mesNum < 1 || $mesNum > 12) {
            $mesNum = (int) date('m');
        }
        $today = date('Y') . '-' . str_pad($mesNum, 2, '0', STR_PAD_LEFT);
        $filtroPeriodo = "DATE_FORMAT(o.created, '%Y-%m') = '$today'";
        break;
    default:
        echo json_encode(['status' => 'error', 'mensaje' => 'No se indico un periodo de tiempo']);
        exit;
}

// -------------------------------------------------------------------
// 1. OBTENER TOTALES POR TIPO DE PAGO
// -------------------------------------------------------------------
$totalesPago = [
    'Punto' => 0,
    'Pmovil' => 0,
    'Transferencia' => 0,
    'Efectivo' => 0,
    'Dolares' => 0,
    'Pesos' => 0,
    'Biopago' => 0
];

$sqlTotalesPago = "
    SELECT o.tipoPago, SUM(o.total_price) as sum_usd, SUM(o.total_price_bs) as sum_bs, SUM(o.total_price_cop) as sum_cop
    FROM orden o
    WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
    GROUP BY o.tipoPago
";
$resultTotales = $conexion->query($sqlTotalesPago);
if ($resultTotales) {
    while ($row = $resultTotales->fetch_assoc()) {
        switch ($row['tipoPago']) {
            case 1:
                $totalesPago['Punto'] += $row['sum_bs'];
                break;
            case 2:
                $totalesPago['Pmovil'] += $row['sum_bs'];
                break;
            case 3:
                $totalesPago['Transferencia'] += $row['sum_bs'];
                break;
            case 4:
                $totalesPago['Efectivo'] += $row['sum_bs'];
                break;
            case 5:
                $totalesPago['Dolares'] += $row['sum_usd'];
                break;
            case 6:
                $totalesPago['Pesos'] += $row['sum_cop'];
                break;
            case 7:
                $totalesPago['Biopago'] += $row['sum_bs'];
                break;
        }
    }
}

// Fraccionado (tipoPago = 8): cada fracción suma a su medio de pago / moneda
$sqlFracciones = "
    SELECT
        SUM(f.punto) AS punto,
        SUM(f.pagoMovil) AS pagoMovil,
        SUM(f.transferencia) AS transferencia,
        SUM(f.bioPago) AS bioPago,
        SUM(f.efectivo) AS efectivo,
        SUM(f.pesos) AS pesos,
        SUM(f.dolares) AS dolares
    FROM fracciones f
    INNER JOIN orden o ON o.id = f.id_order
    WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.tipoPago = 8 AND o.bss_id = '$bss_id' $extraCond $user_cond
";
$resFracciones = $conexion->query($sqlFracciones);
if ($resFracciones && ($rowFrac = $resFracciones->fetch_assoc())) {
    $totalesPago['Punto']         += (float) $rowFrac['punto'];
    $totalesPago['Pmovil']        += (float) $rowFrac['pagoMovil'];
    $totalesPago['Transferencia'] += (float) $rowFrac['transferencia'];
    $totalesPago['Efectivo']      += (float) $rowFrac['efectivo'];
    $totalesPago['Biopago']       += (float) $rowFrac['bioPago'];
    $totalesPago['Dolares']       += (float) $rowFrac['dolares'];
    $totalesPago['Pesos']         += (float) $rowFrac['pesos'];
}

// -------------------------------------------------------------------
// 2. OBTENER TOTALES DE VENTAS POR STATUS (Detal = 1,3 / Mayor = 4)
// -------------------------------------------------------------------
$total_detal = 0;
$total_mayor = 0;

$sqlVentasStatus = "
    SELECT o.status, SUM(o.total_price) as total_usd
    FROM orden o
    WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
    GROUP BY o.status
";
$resultVentasStatus = $conexion->query($sqlVentasStatus);
if ($resultVentasStatus) {
    while ($row = $resultVentasStatus->fetch_assoc()) {
        // El status 3 es descuento de stock, no una venta: se reporta en su vista aparte.
        if ($row['status'] == '1') {
            $total_detal += $row['total_usd'];
        } elseif ($row['status'] == '4') {
            $total_mayor += $row['total_usd'];
        }
    }
}

// -------------------------------------------------------------------
// 3. CALCULAR GANANCIAS POR TIPO DE VENTA (Detal y Mayor)
// -------------------------------------------------------------------
function obtenerGananciasPorTipoVenta($conexion, $tipo, $filtroPeriodo, $bss_id, $extraCond, $user_cond)
{
    $sql = "
        SELECT SUM(
            (oa.precio_venta_dolar * oa.quantity * CASE WHEN o.status = '4' THEN (1 - COALESCE(o.descontado, 0) / 100) ELSE 1 END) 
            - (oa.precio * oa.quantity)
        ) AS ganancia
        FROM orden o
        JOIN orden_articulos oa ON oa.order_id = o.id
        WHERE $filtroPeriodo AND o.status = '$tipo' AND o.bss_id = '$bss_id' $extraCond $user_cond
    ";
    $res = $conexion->query($sql);
    $row = $res ? $res->fetch_assoc() : ['ganancia' => 0];
    return $row['ganancia'] ? (float) $row['ganancia'] : 0;
}

$ganancia_detal = obtenerGananciasPorTipoVenta($conexion, '1', $filtroPeriodo, $bss_id, $extraCond, $user_cond);
$ganancia_mayor = obtenerGananciasPorTipoVenta($conexion, '4', $filtroPeriodo, $bss_id, $extraCond, $user_cond);

// -------------------------------------------------------------------
// 4. CALCULAR GANANCIAS POR MONEDA
// -------------------------------------------------------------------
function obtenerGananciasPorMoneda($conexion, $filtroPeriodo, $bss_id, $extraCond, $user_cond)
{
    // Calculamos las ventas y costos en una sola consulta para todas las monedas
    $sql = "
        SELECT o.tipoPago, o.total_price_bs, o.total_price_cop, o.total_price,
               COALESCE((SELECT SUM(oa.bolivar * oa.quantity) FROM orden_articulos oa WHERE oa.order_id = o.id), 0) as costo_bolivar,
               COALESCE((SELECT SUM(oa.peso * oa.quantity) FROM orden_articulos oa WHERE oa.order_id = o.id), 0) as costo_peso,
               COALESCE((SELECT SUM(oa.precio * oa.quantity) FROM orden_articulos oa WHERE oa.order_id = o.id), 0) as costo_dolar,
               COALESCE(fr.frac_bs, 0) as frac_bs,
               COALESCE(fr.frac_cop, 0) as frac_cop,
               COALESCE(fr.frac_usd, 0) as frac_usd
        FROM orden o
        LEFT JOIN (
            SELECT id_order,
                   SUM(efectivo + punto + pagoMovil + transferencia + bioPago) AS frac_bs,
                   SUM(pesos) AS frac_cop,
                   SUM(dolares) AS frac_usd
            FROM fracciones
            GROUP BY id_order
        ) fr ON fr.id_order = o.id
        WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
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
            if ($tipoPago == '8') {
                // Pago fraccionado: se reparte el costo total entre las monedas según
                // la fracción que cada una aportó a la cobertura, normalizada. Así el
                // costo asignado nunca supera el costo real ni queda parte sin asignar.
                $totalBs  = (float) $row['total_price_bs'];
                $totalCop = (float) $row['total_price_cop'];
                $totalUsd = (float) $row['total_price'];
                $fracBs   = (float) $row['frac_bs'];
                $fracCop  = (float) $row['frac_cop'];
                $fracUsd  = (float) $row['frac_usd'];

                $covBs  = $totalBs > 0 ? $fracBs / $totalBs : 0;
                $covCop = $totalCop > 0 ? $fracCop / $totalCop : 0;
                $covUsd = $totalUsd > 0 ? $fracUsd / $totalUsd : 0;
                $covTotal = $covBs + $covCop + $covUsd;

                if ($covTotal > 0) {
                    $ganancias['Bolivar'] += $fracBs - ((float) $row['costo_bolivar'] * ($covBs / $covTotal));
                    $ganancias['Peso']    += $fracCop - ((float) $row['costo_peso'] * ($covCop / $covTotal));
                    $ganancias['Dolar']   += $fracUsd - ((float) $row['costo_dolar'] * ($covUsd / $covTotal));
                }
            }
        }
    }
    return $ganancias;
}

$gananciasMoneda = obtenerGananciasPorMoneda($conexion, $filtroPeriodo, $bss_id, $extraCond, $user_cond);

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
            WHERE oa.order_id = o.id) as productosTexto,
           fr.punto, fr.pagoMovil, fr.transferencia, fr.bioPago, fr.efectivo, fr.pesos, fr.dolares
    FROM orden o
    LEFT JOIN usuarios u ON o.usuario = u.id
    LEFT JOIN (
        SELECT id_order,
               SUM(punto) AS punto, SUM(pagoMovil) AS pagoMovil, SUM(transferencia) AS transferencia,
               SUM(bioPago) AS bioPago, SUM(efectivo) AS efectivo, SUM(pesos) AS pesos, SUM(dolares) AS dolares
        FROM fracciones
        GROUP BY id_order
    ) fr ON fr.id_order = o.id
    WHERE (o.status = '1' OR o.status = '2' OR o.status = '4') 
      AND $filtroPeriodo
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

        // "Cobrado": para fraccionado se muestra el desglose real; para el resto,
        // el monto completo en la moneda del medio de pago.
        $cobrado = '';
        if ($row['status'] == '2') {
            $cobrado = 'Crédito (pendiente)';
        } elseif ($row['tipoPago'] == '8') {
            $partesCobro = [];
            if ((float) ($row['punto'] ?? 0) > 0) $partesCobro[] = 'Bs ' . number_format((float) $row['punto'], 2, ',', '.');
            if ((float) ($row['efectivo'] ?? 0) > 0) $partesCobro[] = 'Bs efectivo ' . number_format((float) $row['efectivo'], 2, ',', '.');
            if ((float) ($row['pagoMovil'] ?? 0) > 0) $partesCobro[] = 'Bs pago móvil ' . number_format((float) $row['pagoMovil'], 2, ',', '.');
            if ((float) ($row['transferencia'] ?? 0) > 0) $partesCobro[] = 'Bs transferencia ' . number_format((float) $row['transferencia'], 2, ',', '.');
            if ((float) ($row['bioPago'] ?? 0) > 0) $partesCobro[] = 'Bs BioPago ' . number_format((float) $row['bioPago'], 2, ',', '.');
            if ((float) ($row['dolares'] ?? 0) > 0) $partesCobro[] = 'USD ' . number_format((float) $row['dolares'], 2, ',', '.');
            if ((float) ($row['pesos'] ?? 0) > 0) $partesCobro[] = 'COP ' . number_format((float) $row['pesos'], 0, ',', '.');
            $cobrado = implode(' + ', $partesCobro);
        } elseif ($row['tipoPago'] == '5') {
            $cobrado = '$ ' . number_format((float) $row['total_price'], 2, ',', '.');
        } elseif ($row['tipoPago'] == '6') {
            $cobrado = number_format((float) $row['total_price_cop'], 0, ',', '.') . ' Cop';
        } else {
            $cobrado = number_format((float) $row['total_price_bs'], 2, ',', '.') . ' Bs';
        }

        $tabla[] = [
            'contador' => $contador++,
            'tVenta' => $tVenta,
            'tipoPago' => $tipoPago,
            'usuario' => htmlspecialchars($row['usuario'] ?? 'N/A'),
            'created' => $row['created'],
            'total_price' => number_format($row['total_price'], 2, ',', '.'),
            'total_price_cop' => number_format($row['total_price_cop'], 0, ',', '.'),
            'total_price_bs' => number_format($row['total_price_bs'], 2, ',', '.'),
            'cobrado' => $cobrado,
            'id' => $orderId,
            'cliente' => $row['cliente'],
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
    'valor_Bolivares' => number_format($totalesPago['Punto'] + $totalesPago['Pmovil'] + $totalesPago['Transferencia'] + $totalesPago['Efectivo'] + $totalesPago['Biopago'], 2, '.', ','),
    'ganacias_Dolares' => number_format($gananciasMoneda['Dolar'], 2, '.', ',') . ' - Ganancias',
    'valor_Dolares' => number_format($totalesPago['Dolares'], 2, '.', ','),
    'ganacias_Pesos' => number_format($gananciasMoneda['Peso'], 2, '.', ',') . ' - Ganancias',
    'valor_Pesos' => number_format($totalesPago['Pesos'], 2, '.', ','),
    'ganacias_Mayor' => '$' . number_format($ganancia_mayor, 2, '.', ',') . ' Ganancias.',
    'valor_Mayor' => number_format($total_mayor, 2, '.', ','),
    'ganacias_Detal' => '$' . number_format($ganancia_detal, 2, '.', ',') . ' Ganancias.',
    'valor_Detal' => number_format($total_detal, 2, '.', ','),

    // SECTION LEFT
    'total_Pmovil' => number_format($totalesPago['Pmovil'], 2, '.', ','),
    'total_Transferencia' => number_format($totalesPago['Transferencia'], 2, '.', ','),
    'total_Biopago' => number_format($totalesPago['Biopago'], 2, '.', ','),
    'total_Efectivo' => number_format($totalesPago['Efectivo'], 2, '.', ','),
    'total_Dolares' => number_format($totalesPago['Dolares'], 2, '.', ','),
    'total_pesos' => number_format($totalesPago['Pesos'], 0, '.', ','),
    'total_Punto' => number_format($totalesPago['Punto'], 2, '.', ','),

    // SECTION TABLE
    'tabla' => $tabla
]);

