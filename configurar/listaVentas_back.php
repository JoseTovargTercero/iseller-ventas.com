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
// Se usa un rango semiabierto (>= inicio AND < fin) en vez de DATE()/DATE_FORMAT():
// así MySQL puede usar el índice sobre created en lugar de recorrer la tabla completa.
$filtroPeriodo = '';
switch ($periodo_tiempo) {
    case 'dia':
        $fechaDia = trim((string) $filtro_fecha);
        $objFecha = DateTime::createFromFormat('Y-m-d', $fechaDia);
        if (!$objFecha || $objFecha->format('Y-m-d') !== $fechaDia) {
            $fechaDia = date('Y-m-d');
        }
        $today = $fechaDia;
        $finDia = date('Y-m-d', strtotime($fechaDia . ' +1 day'));
        $filtroPeriodo = "o.created >= '$fechaDia 00:00:00' AND o.created < '$finDia 00:00:00'";
        break;
    case 'semana':
        // El selector manda el número de semana (ej. "5"). Se usa año-semana ISO.
        $semanaNum = (int) $filtro_fecha;
        if ($semanaNum < 1 || $semanaNum > 53) {
            $semanaNum = (int) date('W');
        }
        $today = date('o') . '-' . str_pad($semanaNum, 2, '0', STR_PAD_LEFT);
        $dtSemana = new DateTime();
        $dtSemana->setISODate((int) date('o'), $semanaNum, 1); // lunes de la semana ISO
        if ($dtSemana->format('o-W') !== $today) {
            $filtroPeriodo = '1=0'; // semana inexistente en este año
        } else {
            $inicioSemana = $dtSemana->format('Y-m-d');
            $finSemana = (clone $dtSemana)->modify('+7 days')->format('Y-m-d');
            $filtroPeriodo = "o.created >= '$inicioSemana 00:00:00' AND o.created < '$finSemana 00:00:00'";
        }
        break;
    case 'mes':
        $mesNum = (int) $filtro_fecha;
        if ($mesNum < 1 || $mesNum > 12) {
            $mesNum = (int) date('m');
        }
        $today = date('Y') . '-' . str_pad($mesNum, 2, '0', STR_PAD_LEFT);
        $inicioMes = $today . '-01';
        $finMes = date('Y-m-d', strtotime($inicioMes . ' +1 month'));
        $filtroPeriodo = "o.created >= '$inicioMes 00:00:00' AND o.created < '$finMes 00:00:00'";
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

// Una sola pasada agrupada por status + tipoPago: alimenta los totales por medio
// de pago y los totales Detal/Mayor (antes eran dos consultas a la misma tabla).
$total_detal = 0;
$total_mayor = 0;
$totalesPorStatusTipo = [];

$sqlTotalesPago = "
    SELECT o.status, o.tipoPago, SUM(o.total_price) as sum_usd, SUM(o.total_price_bs) as sum_bs, SUM(o.total_price_cop) as sum_cop
    FROM orden o
    WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
    GROUP BY o.status, o.tipoPago
";
$resultTotales = $conexion->query($sqlTotalesPago);
if ($resultTotales) {
    while ($row = $resultTotales->fetch_assoc()) {
        $totalesPorStatusTipo[$row['status'] . '|' . $row['tipoPago']] = [
            'bs' => (float) $row['sum_bs'],
            'cop' => (float) $row['sum_cop'],
            'usd' => (float) $row['sum_usd'],
        ];

        switch ((string) $row['tipoPago']) {
            case '1':
                $totalesPago['Punto'] += (float) $row['sum_bs'];
                break;
            case '2':
                $totalesPago['Pmovil'] += (float) $row['sum_bs'];
                break;
            case '3':
                $totalesPago['Transferencia'] += (float) $row['sum_bs'];
                break;
            case '4':
                $totalesPago['Efectivo'] += (float) $row['sum_bs'];
                break;
            case '5':
                $totalesPago['Dolares'] += (float) $row['sum_usd'];
                break;
            case '6':
                $totalesPago['Pesos'] += (float) $row['sum_cop'];
                break;
            case '7':
                $totalesPago['Biopago'] += (float) $row['sum_bs'];
                break;
        }

        // El status 3 es descuento de stock, no una venta: se reporta en su vista aparte.
        if ((string) $row['status'] === '1') {
            $total_detal += (float) $row['sum_usd'];
        } elseif ((string) $row['status'] === '4') {
            $total_mayor += (float) $row['sum_usd'];
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
// 2. TOTALES DETAL/MAYOR: se calculan en la consulta anterior (arriba)
// -------------------------------------------------------------------

// -------------------------------------------------------------------
// 3. CALCULAR GANANCIAS POR TIPO DE VENTA (Detal y Mayor) Y COSTOS
// -------------------------------------------------------------------
// Una sola consulta agrupada por status + tipoPago: además de Detal/Mayor,
// deja los costos listos para el cálculo por moneda (sección 4), evitando
// recorrer orden_articulos una segunda vez.
$ganancia_detal = 0;
$ganancia_mayor = 0;
$costosPorStatusTipo = [];

$sqlGananciasTipo = "
    SELECT o.status, o.tipoPago, SUM(
        (oa.precio_venta_dolar * oa.quantity * CASE WHEN o.status = '4' THEN (1 - COALESCE(o.descontado, 0) / 100) ELSE 1 END)
        - (oa.precio * oa.quantity)
    ) AS ganancia,
           SUM(oa.bolivar * oa.quantity) AS costo_bolivar,
           SUM(oa.peso * oa.quantity) AS costo_peso,
           SUM(oa.precio * oa.quantity) AS costo_dolar
    FROM orden o
    JOIN orden_articulos oa ON oa.order_id = o.id
    WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.bss_id = '$bss_id' $extraCond $user_cond
    GROUP BY o.status, o.tipoPago
";
$resGananciasTipo = $conexion->query($sqlGananciasTipo);
if ($resGananciasTipo) {
    while ($row = $resGananciasTipo->fetch_assoc()) {
        $costosPorStatusTipo[$row['status'] . '|' . $row['tipoPago']] = [
            'costo_bolivar' => (float) $row['costo_bolivar'],
            'costo_peso' => (float) $row['costo_peso'],
            'costo_dolar' => (float) $row['costo_dolar'],
        ];

        if ((string) $row['status'] === '1') {
            $ganancia_detal += (float) $row['ganancia'];
        } elseif ((string) $row['status'] === '4') {
            $ganancia_mayor += (float) $row['ganancia'];
        }
    }
}

// -------------------------------------------------------------------
// 4. CALCULAR GANANCIAS POR MONEDA
// -------------------------------------------------------------------
// Los totales por status+tipoPago ya vienen de la sección 1 y los costos de la
// sección 3: aquí solo se restan en PHP (sin consultas extra por venta).
$ganancias = ['Bolivar' => 0, 'Peso' => 0, 'Dolar' => 0];
foreach ($totalesPorStatusTipo as $clave => $tot) {
    list($status, $tipoPago) = explode('|', $clave);
    if ($tipoPago === '8') {
        continue; // los fraccionados se calculan por venta más abajo
    }
    $costo = $costosPorStatusTipo[$clave] ?? ['costo_bolivar' => 0, 'costo_peso' => 0, 'costo_dolar' => 0];

    if (in_array($tipoPago, ['1', '2', '3', '4', '7'], true)) {
        $ganancias['Bolivar'] += $tot['bs'] - $costo['costo_bolivar'];
    } elseif ($tipoPago === '6') {
        $ganancias['Peso'] += $tot['cop'] - $costo['costo_peso'];
    } elseif ($tipoPago === '5') {
        $ganancias['Dolar'] += $tot['usd'] - $costo['costo_dolar'];
    }
}

// Pagos fraccionados (tipoPago 8): el costo se reparte por cobertura, así que
// se necesitan los totales por venta. Solo se piden esas ventas, no todas.
$sqlFraccionadoMoneda = "
    SELECT o.id, o.total_price, o.total_price_bs, o.total_price_cop,
           COALESCE(SUM(oa.bolivar * oa.quantity), 0) as costo_bolivar,
           COALESCE(SUM(oa.peso * oa.quantity), 0) as costo_peso,
           COALESCE(SUM(oa.precio * oa.quantity), 0) as costo_dolar
    FROM orden o
    LEFT JOIN orden_articulos oa ON oa.order_id = o.id
    WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.tipoPago = 8 AND o.bss_id = '$bss_id' $extraCond $user_cond
    GROUP BY o.id, o.total_price, o.total_price_bs, o.total_price_cop
";

$ordenesFraccionadas = [];
$resFraccionadoMoneda = $conexion->query($sqlFraccionadoMoneda);
if ($resFraccionadoMoneda) {
    while ($row = $resFraccionadoMoneda->fetch_assoc()) {
        $ordenesFraccionadas[] = $row;
    }
}

$fraccionesPorOrden = [];
if ($ordenesFraccionadas) {
    $resFraccionesOrden = $conexion->query("
        SELECT f.id_order,
               SUM(f.efectivo + f.punto + f.pagoMovil + f.transferencia + f.bioPago) AS frac_bs,
               SUM(f.pesos) AS frac_cop,
               SUM(f.dolares) AS frac_usd
        FROM fracciones f
        INNER JOIN orden o ON o.id = f.id_order
        WHERE $filtroPeriodo AND o.status IN ('1', '4') AND o.tipoPago = 8 AND o.bss_id = '$bss_id' $extraCond $user_cond
        GROUP BY f.id_order
    ");
    if ($resFraccionesOrden) {
        while ($row = $resFraccionesOrden->fetch_assoc()) {
            $fraccionesPorOrden[$row['id_order']] = $row;
        }
    }
}

foreach ($ordenesFraccionadas as $row) {
    // Pago fraccionado: se reparte el costo total entre las monedas según
    // la fracción que cada una aportó a la cobertura, normalizada. Así el
    // costo asignado nunca supera el costo real ni queda parte sin asignar.
    $frac = $fraccionesPorOrden[$row['id']] ?? ['frac_bs' => 0, 'frac_cop' => 0, 'frac_usd' => 0];
    $totalBs  = (float) $row['total_price_bs'];
    $totalCop = (float) $row['total_price_cop'];
    $totalUsd = (float) $row['total_price'];
    $fracBs   = (float) $frac['frac_bs'];
    $fracCop  = (float) $frac['frac_cop'];
    $fracUsd  = (float) $frac['frac_usd'];

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

$gananciasMoneda = $ganancias;

// -------------------------------------------------------------------
// 5. OBTENER DATOS DE LA TABLA DE VENTAS
// -------------------------------------------------------------------
$tabla = [];

// Usamos GROUP_CONCAT para traer los productos en la misma consulta y evitar N+1 en la tabla.
// Las fracciones se piden aparte solo para las 150 ventas listadas (antes se agrupaba
// la tabla `fracciones` completa en cada carga).
$queryTabla = "
    SELECT o.*, u.nombre AS usuario, 
           (SELECT GROUP_CONCAT(CONCAT(oa.quantity, ' ', p.nombre) SEPARATOR ', ')
            FROM orden_articulos oa 
            JOIN productos p ON oa.product_id = p.id 
            WHERE oa.order_id = o.id) as productosTexto
    FROM orden o
    LEFT JOIN usuarios u ON o.usuario = u.id
    WHERE o.status IN ('1', '2', '4') 
      AND $filtroPeriodo
      AND o.bss_id = '$bss_id' 
      $extraCond $user_cond
    ORDER BY o.id DESC
    LIMIT 150
";

$resultTabla = $conexion->query($queryTabla);
$filasTabla = [];
if ($resultTabla) {
    while ($row = $resultTabla->fetch_assoc()) {
        $filasTabla[] = $row;
    }
}

$fraccionesTabla = [];
if ($filasTabla) {
    $idsTabla = implode(',', array_map('intval', array_column($filasTabla, 'id')));
    $resFraccionesTabla = $conexion->query("
        SELECT id_order,
               SUM(punto) AS punto, SUM(pagoMovil) AS pagoMovil, SUM(transferencia) AS transferencia,
               SUM(bioPago) AS bioPago, SUM(efectivo) AS efectivo, SUM(pesos) AS pesos, SUM(dolares) AS dolares
        FROM fracciones
        WHERE id_order IN ($idsTabla)
        GROUP BY id_order
    ");
    if ($resFraccionesTabla) {
        while ($row = $resFraccionesTabla->fetch_assoc()) {
            $fraccionesTabla[$row['id_order']] = $row;
        }
    }
}

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

foreach ($filasTabla as $row) {
        $row = array_merge($row, $fraccionesTabla[$row['id']] ?? []);
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

