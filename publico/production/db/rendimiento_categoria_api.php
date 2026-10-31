<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../configurar/configuracion.php');
require_once(__DIR__ . '/../../../configurar/session.php');

header('Content-Type: application/json; charset=utf-8');

// 1. Validar sesión
if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || !isset($_SESSION['bss_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Sesión no válida.']);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$nivel = (int)($_SESSION['nivel'] ?? 2);

// Verificar permisos para empleados (nivel 2)
if ($nivel === 2) {
    // Buscar si el permiso para rendimiento_categoria.php está asignado
    $has_perm = false;
    if (isset($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        foreach ($_SESSION['permisos'] as $p_item) {
            if (is_array($p_item) && in_array('rendimiento_categoria.php', $p_item)) {
                $has_perm = true;
                break;
            } elseif ($p_item === 'rendimiento_categoria.php') {
                $has_perm = true;
                break;
            }
        }
    }
    
    // Consulta directa a users_permisos por seguridad
    if (!$has_perm && isset($_SESSION['id'])) {
        $u_id = (int)$_SESSION['id'];
        $stmtP = $conexion->prepare("
            SELECT up.id 
            FROM users_permisos up
            INNER JOIN menu m ON m.id = up.id_item_menu
            WHERE up.id_user = ? AND m.dir = 'rendimiento_categoria.php'
            LIMIT 1
        ");
        if ($stmtP) {
            $stmtP->bind_param('i', $u_id);
            $stmtP->execute();
            if ($stmtP->get_result()->num_rows > 0) {
                $has_perm = true;
            }
            $stmtP->close();
        }
    }

    if (!$has_perm) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para acceder a este módulo.']);
        exit;
    }
}

// 2. Parámetros de entrada
$categoria_param = $_GET['categoria'] ?? ($_POST['categoria'] ?? null);
$sucursal_param = $_GET['sucursal'] ?? ($_POST['sucursal'] ?? null);

// Alcance de sucursal
if ($nivel === 2) {
    $sucursal_id = (int)($_SESSION['sucursal'] ?? 0);
} else {
    $sucursal_id = ($sucursal_param !== null && $sucursal_param !== '' && $sucursal_param !== 'todas') ? (int)$sucursal_param : 0;
}

$extraCondOrden = ($sucursal_id > 0) ? " AND o.id_sucursal = $sucursal_id" : "";
$extraCondStock = ($sucursal_id > 0) ? " AND s.id_sucursal = $sucursal_id" : "";

// Configurar zona horaria del sistema
date_default_timezone_set('America/Manaus');
$hoy = date('Y-m-d');
$semana = date('o-W');
$mes = date('Y-m');

// 3. Determinar categoría seleccionada
$is_sin_categoria = ($categoria_param === '0' || $categoria_param === 'sin_categoria');
$categoria_id = ($categoria_param !== null && !$is_sin_categoria) ? (int)$categoria_param : null;

// Si no se proporcionó categoría, buscar la primera categoría que tenga productos
if ($categoria_id === null && !$is_sin_categoria) {
    $stmtFirst = $conexion->prepare("
        SELECT cp.id, cp.nombre
        FROM categorias_producto cp
        INNER JOIN productos p ON p.categoria = cp.id
        WHERE p.bss_id = ? AND p.activo = 0
        GROUP BY cp.id, cp.nombre
        HAVING COUNT(p.id) > 0
        ORDER BY cp.nombre ASC
        LIMIT 1
    ");
    $stmtFirst->bind_param('i', $bss_id);
    $stmtFirst->execute();
    $resFirst = $stmtFirst->get_result();
    if ($rowFirst = $resFirst->fetch_assoc()) {
        $categoria_id = (int)$rowFirst['id'];
        $categoria_nombre = $rowFirst['nombre'];
    } else {
        // No hay categorías asociadas, verificar productos sin categoría
        $is_sin_categoria = true;
        $categoria_nombre = 'Sin categoría';
    }
    $stmtFirst->close();
} else {
    if ($is_sin_categoria) {
        $categoria_nombre = 'Sin categoría';
    } else {
        $stmtCat = $conexion->prepare("SELECT nombre FROM categorias_producto WHERE id = ? LIMIT 1");
        $stmtCat->bind_param('i', $categoria_id);
        $stmtCat->execute();
        $resCat = $stmtCat->get_result();
        if ($rowCat = $resCat->fetch_assoc()) {
            $categoria_nombre = $rowCat['nombre'];
        } else {
            $categoria_nombre = "Categoría #$categoria_id";
        }
        $stmtCat->close();
    }
}

// Condición SQL para categoría
if ($is_sin_categoria) {
    $catCondP = "(p.categoria IS NULL OR p.categoria = 0)";
} else {
    $catCondP = "p.categoria = " . (int)$categoria_id;
}

// 4. Indicadores de ventas y ganancias (Hoy, Semana actual, Mes actual)
// Regla: artículos individuales de órdenes válidas (status IN (1, 4)) pertenecientes a la categoría
$sqlKpis = "
    SELECT 
        -- Ventas Hoy
        COALESCE(SUM(CASE WHEN DATE(o.created) = '$hoy'
            THEN (CAST(oa.quantity AS DECIMAL(10,2)) * CAST(oa.precio_venta_dolar AS DECIMAL(10,2))) 
            ELSE 0 END), 0) AS ventas_hoy,
        -- Ganancias Hoy
        COALESCE(SUM(CASE WHEN DATE(o.created) = '$hoy'
            THEN (CAST(oa.quantity AS DECIMAL(10,2)) * (CAST(oa.precio_venta_dolar AS DECIMAL(10,2)) - CAST(oa.precio AS DECIMAL(10,2)))) 
            ELSE 0 END), 0) AS ganancia_hoy,
            
        -- Ventas Semana
        COALESCE(SUM(CASE WHEN DATE_FORMAT(o.created, '%x-%v') = '$semana'
            THEN (CAST(oa.quantity AS DECIMAL(10,2)) * CAST(oa.precio_venta_dolar AS DECIMAL(10,2))) 
            ELSE 0 END), 0) AS ventas_semana,
        -- Ganancias Semana
        COALESCE(SUM(CASE WHEN DATE_FORMAT(o.created, '%x-%v') = '$semana'
            THEN (CAST(oa.quantity AS DECIMAL(10,2)) * (CAST(oa.precio_venta_dolar AS DECIMAL(10,2)) - CAST(oa.precio AS DECIMAL(10,2)))) 
            ELSE 0 END), 0) AS ganancia_semana,
            
        -- Ventas Mes
        COALESCE(SUM(CASE WHEN DATE_FORMAT(o.created, '%Y-%m') = '$mes'
            THEN (CAST(oa.quantity AS DECIMAL(10,2)) * CAST(oa.precio_venta_dolar AS DECIMAL(10,2))) 
            ELSE 0 END), 0) AS ventas_mes,
        -- Ganancias Mes
        COALESCE(SUM(CASE WHEN DATE_FORMAT(o.created, '%Y-%m') = '$mes'
            THEN (CAST(oa.quantity AS DECIMAL(10,2)) * (CAST(oa.precio_venta_dolar AS DECIMAL(10,2)) - CAST(oa.precio AS DECIMAL(10,2)))) 
            ELSE 0 END), 0) AS ganancia_mes
    FROM orden_articulos oa
    INNER JOIN orden o ON oa.order_id = o.id
    INNER JOIN productos p ON oa.product_id = p.id
    WHERE o.bss_id = $bss_id
      AND o.status IN (1, 4)
      AND $catCondP
      $extraCondOrden
";

$resKpis = $conexion->query($sqlKpis);
$kpiRow = $resKpis ? $resKpis->fetch_assoc() : [];

$ventas_hoy = round((float)($kpiRow['ventas_hoy'] ?? 0), 2);
$ganancia_hoy = round((float)($kpiRow['ganancia_hoy'] ?? 0), 2);
$margen_hoy = ($ventas_hoy > 0) ? round(($ganancia_hoy / $ventas_hoy) * 100, 1) : 0;

$ventas_semana = round((float)($kpiRow['ventas_semana'] ?? 0), 2);
$ganancia_semana = round((float)($kpiRow['ganancia_semana'] ?? 0), 2);
$margen_semana = ($ventas_semana > 0) ? round(($ganancia_semana / $ventas_semana) * 100, 1) : 0;

$ventas_mes = round((float)($kpiRow['ventas_mes'] ?? 0), 2);
$ganancia_mes = round((float)($kpiRow['ganancia_mes'] ?? 0), 2);
$margen_mes = ($ventas_mes > 0) ? round(($ganancia_mes / $ventas_mes) * 100, 1) : 0;

// 5. Indicadores de Inventario de la categoría
// A. Cantidad total en stock
// B. Valor total del stock (costo de inventario real = stock * (precio_compra / cantidad_unidades))
$sqlStock = "
    SELECT 
        COALESCE(SUM(CAST(s.stock AS DECIMAL(10,2))), 0) AS total_unidades,
        COALESCE(SUM(CAST(s.stock AS DECIMAL(10,2)) * (CAST(p.precio_compra AS DECIMAL(10,2)) / IFNULL(NULLIF(p.cantidad_unidades, 0), 1))), 0) AS valor_stock_costo,
        COALESCE(SUM(CAST(s.stock AS DECIMAL(10,2)) * (CAST(p.precio_compra AS DECIMAL(10,2)) / IFNULL(NULLIF(p.cantidad_unidades, 0), 1)) * (1 + CAST(p.porcentaje AS DECIMAL(10,2))/100)), 0) AS valor_stock_venta,
        COUNT(DISTINCT p.id) AS total_productos_catalogo
    FROM productos p
    LEFT JOIN stock s ON s.id_producto = p.id
    WHERE p.activo = 0
      AND p.bss_id = $bss_id
      AND $catCondP
      $extraCondStock
";

$resStock = $conexion->query($sqlStock);
$stockRow = $resStock ? $resStock->fetch_assoc() : [];

$stock_unidades = round((float)($stockRow['total_unidades'] ?? 0), 2);
$stock_valor_costo = round((float)($stockRow['valor_stock_costo'] ?? 0), 2);
$stock_valor_venta = round((float)($stockRow['valor_stock_venta'] ?? 0), 2);
$total_productos = (int)($stockRow['total_productos_catalogo'] ?? 0);

// 6. Gráfico semanal: Ventas de los 7 días de la semana actual
// Días 1=Lunes a 7=Domingo (convención del sistema en tabla orden.dia)
$diasNombres = [
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
];
$diasVentas = array_fill(1, 7, 0.0);
$diasGanancias = array_fill(1, 7, 0.0);

$sqlChart = "
    SELECT 
        WEEKDAY(o.created) + 1 AS dia,
        COALESCE(SUM(CAST(oa.quantity AS DECIMAL(10,2)) * CAST(oa.precio_venta_dolar AS DECIMAL(10,2))), 0) AS total_dia,
        COALESCE(SUM(CAST(oa.quantity AS DECIMAL(10,2)) * (CAST(oa.precio_venta_dolar AS DECIMAL(10,2)) - CAST(oa.precio AS DECIMAL(10,2)))), 0) AS ganancia_dia
    FROM orden_articulos oa
    INNER JOIN orden o ON oa.order_id = o.id
    INNER JOIN productos p ON oa.product_id = p.id
    WHERE o.bss_id = $bss_id
      AND o.status IN (1, 4)
      AND DATE_FORMAT(o.created, '%x-%v') = '$semana'
      AND $catCondP
      $extraCondOrden
    GROUP BY dia
";

$resChart = $conexion->query($sqlChart);
if ($resChart) {
    while ($rowC = $resChart->fetch_assoc()) {
        $diaIndex = (int)$rowC['dia'];
        if (isset($diasVentas[$diaIndex])) {
            $diasVentas[$diaIndex] = round((float)$rowC['total_dia'], 2);
            $diasGanancias[$diaIndex] = round((float)$rowC['ganancia_dia'], 2);
        }
    }
}

// 7. Productos vendidos de la categoría en la semana actual.
// De la misma lista se generan dos rankings: más vendidos y más rentables.
$sqlTop = "
    SELECT 
        p.id AS producto_id,
        p.nombre AS producto_nombre,
        COALESCE(SUM(CAST(oa.quantity AS DECIMAL(10,2))), 0) AS unidades_vendidas,
        COALESCE(SUM(CAST(oa.quantity AS DECIMAL(10,2)) * CAST(oa.precio_venta_dolar AS DECIMAL(10,2))), 0) AS total_ingreso,
        COALESCE(SUM(CAST(oa.quantity AS DECIMAL(10,2)) * (CAST(oa.precio_venta_dolar AS DECIMAL(10,2)) - CAST(oa.precio AS DECIMAL(10,2)))), 0) AS total_ganancia
    FROM orden_articulos oa
    INNER JOIN orden o ON oa.order_id = o.id
    INNER JOIN productos p ON oa.product_id = p.id
    WHERE o.bss_id = $bss_id
      AND o.status IN (1, 4)
      AND DATE_FORMAT(o.created, '%x-%v') = '$semana'
      AND $catCondP
      $extraCondOrden
    GROUP BY p.id, p.nombre
    ORDER BY unidades_vendidas DESC, total_ingreso DESC
";

$productosSemana = [];
$resTop = $conexion->query($sqlTop);
if ($resTop && $resTop->num_rows > 0) {
    while ($rowT = $resTop->fetch_assoc()) {
        $productosSemana[] = [
            'id' => (int)$rowT['producto_id'],
            'nombre' => $rowT['producto_nombre'],
            'unidades' => (float)$rowT['unidades_vendidas'],
            'ingreso' => round((float)$rowT['total_ingreso'], 2),
            'ganancia' => round((float)$rowT['total_ganancia'], 2),
            'stock' => 0
        ];
    }

    // Obtener stock actual de los productos vendidos (evitando joins duplicados)
    $product_ids = array_map('intval', array_column($productosSemana, 'id'));
    if (!empty($product_ids)) {
        $inIds = implode(',', $product_ids);
        $sqlProdStock = "SELECT id_producto, COALESCE(SUM(CAST(stock AS DECIMAL(10,2))), 0) AS total_stock FROM stock s WHERE id_producto IN ($inIds) AND bss_id = $bss_id $extraCondStock GROUP BY id_producto";
        $resProdStock = $conexion->query($sqlProdStock);
        $stockMap = [];
        if ($resProdStock) {
            while ($rowS = $resProdStock->fetch_assoc()) {
                $stockMap[(int)$rowS['id_producto']] = (float)$rowS['total_stock'];
            }
        }
        foreach ($productosSemana as &$itemP) {
            $itemP['stock'] = $stockMap[$itemP['id']] ?? 0;
        }
        unset($itemP);
    }
}

// Top 5 por unidades vendidas
$topProductos = $productosSemana;
usort($topProductos, function ($a, $b) {
    $cmp = $b['unidades'] <=> $a['unidades'];
    return $cmp !== 0 ? $cmp : ($b['ingreso'] <=> $a['ingreso']);
});
$topProductos = array_slice($topProductos, 0, 5);

// Top 5 por ganancia generada
$topProductosRentables = $productosSemana;
usort($topProductosRentables, function ($a, $b) {
    $cmp = $b['ganancia'] <=> $a['ganancia'];
    return $cmp !== 0 ? $cmp : ($b['ingreso'] <=> $a['ingreso']);
});
$topProductosRentables = array_slice($topProductosRentables, 0, 5);

// 8. Productos de la categoría con su stock actual. Con una sucursal elegida
// se usa esa; con "Todas" se suma el stock de todas las sucursales del negocio.
$sqlProductosCat = "
    SELECT p.id, p.nombre,
           COALESCE(SUM(CAST(s.stock AS DECIMAL(10,2))), 0) AS stock_actual
    FROM productos p
    LEFT JOIN stock s ON s.id_producto = p.id AND s.bss_id = $bss_id
        " . ($sucursal_id > 0 ? "AND s.id_sucursal = $sucursal_id" : "") . "
    WHERE p.activo = 0
      AND p.bss_id = $bss_id
      AND $catCondP
    GROUP BY p.id, p.nombre
    ORDER BY p.nombre ASC
";
$productosCategoria = [];
$resProdCat = $conexion->query($sqlProductosCat);
if ($resProdCat) {
    while ($rowPC = $resProdCat->fetch_assoc()) {
        $productosCategoria[] = [
            'id' => (int)$rowPC['id'],
            'nombre' => $rowPC['nombre'],
            'stock' => round((float)$rowPC['stock_actual'], 2)
        ];
    }
}

// 9. Responder JSON con toda la estructura
echo json_encode([
    'success' => true,
    'categoria' => [
        'id' => $is_sin_categoria ? 0 : $categoria_id,
        'nombre' => $categoria_nombre,
        'total_productos' => $total_productos
    ],
    'periodo' => [
        'hoy' => $hoy,
        'semana' => $semana,
        'mes' => $mes
    ],
    'kpis' => [
        'ventas_hoy' => $ventas_hoy,
        'ganancia_hoy' => $ganancia_hoy,
        'margen_hoy' => $margen_hoy,
        'ventas_semana' => $ventas_semana,
        'ganancia_semana' => $ganancia_semana,
        'margen_semana' => $margen_semana,
        'ventas_mes' => $ventas_mes,
        'ganancia_mes' => $ganancia_mes,
        'margen_mes' => $margen_mes,
        'stock_unidades' => $stock_unidades,
        'stock_valor_costo' => $stock_valor_costo,
        'stock_valor_venta' => $stock_valor_venta
    ],
    'grafico_semanal' => [
        'labels' => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
        'ventas' => array_values($diasVentas),
        'ganancias' => array_values($diasGanancias)
    ],
    'top_productos' => $topProductos,
    'top_productos_rentables' => $topProductosRentables,
    'productos' => $productosCategoria
], JSON_UNESCAPED_UNICODE);
