<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../configurar/configuracion.php');
require_once(__DIR__ . '/../../../configurar/session.php');
require_once(__DIR__ . '/../../../configurar/_ajustes_stock.php');

header('Content-Type: application/json; charset=utf-8');

function aj_respuesta(array $data, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id']) || empty($_SESSION['id'])) {
    aj_respuesta(['ok' => false, 'message' => 'SesiÃ³n no vÃ¡lida.'], 401);
}

$bssId        = (int) $_SESSION['bss_id'];
$nivel        = (int) ($_SESSION['nivel'] ?? 2);
$usuarioId    = (int) $_SESSION['id'];
$usuarioNombre = trim((string) ($_SESSION['nombre'] ?? 'Usuario'));

if ($nivel === 2) {
    $tienePermiso = false;
    if (!empty($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        foreach ($_SESSION['permisos'] as $item) {
            if (is_array($item) && in_array('ajustes_stock.php', $item, true)) {
                $tienePermiso = true;
                break;
            }
            if ($item === 'ajustes_stock.php') {
                $tienePermiso = true;
                break;
            }
        }
    }

    if (!$tienePermiso) {
        $stmtPermiso = $conexion->prepare(
            "SELECT up.id
             FROM users_permisos up
             INNER JOIN menu m ON m.id = up.id_item_menu
             WHERE up.id_user = ? AND m.dir = 'ajustes_stock.php'
             LIMIT 1"
        );
        if ($stmtPermiso) {
            $stmtPermiso->bind_param('i', $usuarioId);
            $stmtPermiso->execute();
            if ($stmtPermiso->get_result()->num_rows > 0) {
                $tienePermiso = true;
            }
            $stmtPermiso->close();
        }
    }

    if (!$tienePermiso) {
        aj_respuesta(['ok' => false, 'message' => 'No tienes permisos para este mÃ³dulo.'], 403);
    }
}

function aj_consulta($conexion, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta.');
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Error al ejecutar la consulta.');
    }
    $resultado = $stmt->get_result();
    $filas = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
    }
    $stmt->close();
    return $filas;
}

function aj_fecha($texto): ?string
{
    $texto = trim((string) $texto);
    $fecha = DateTime::createFromFormat('Y-m-d', $texto);
    if (!$fecha || $fecha->format('Y-m-d') !== $texto) {
        return null;
    }
    return $texto;
}

function aj_sucursal($conexion, int $bssId, int $nivel, $valor): array
{
    if ($nivel === 2) {
        $sucursal = (int) ($_SESSION['sucursal'] ?? 0);
        return $sucursal > 0 ? [true, $sucursal] : [false, 0];
    }

    $sucursal = (int) $valor;
    if ($sucursal <= 0) {
        return [true, 0];
    }

    $stmt = $conexion->prepare('SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1');
    if (!$stmt) {
        return [false, 0];
    }
    $stmt->bind_param('ii', $sucursal, $bssId);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $existe ? [true, $sucursal] : [false, 0];
}

function aj_filtros(
    int $bssId,
    int $sucursal,
    ?string $desde,
    ?string $hasta,
    int $categoria,
    int $producto,
    string $tipo,
    string $estado = 'ACTIVO',
    string $grupo = ''
): array {
    $where  = ['m.bss_id = ?'];
    $params = [$bssId];
    $types  = 'i';

    if ($sucursal > 0) {
        $where[]  = 'm.id_sucursal = ?';
        $params[] = $sucursal;
        $types   .= 'i';
    }
    if ($desde !== null && $hasta !== null) {
        $where[]  = 'm.fecha BETWEEN ? AND ?';
        $params[] = $desde;
        $params[] = $hasta;
        $types   .= 'ss';
    }
    if ($categoria > 0) {
        $where[]  = 'm.categoria_id = ?';
        $params[] = $categoria;
        $types   .= 'i';
    }
    if ($grupo !== '') {
        $where[]  = 'm.categoria_id IN (SELECT cp.id FROM categorias_producto cp WHERE cp.grupo = ? AND (cp.bss_id = 0 OR cp.bss_id = ?))';
        $params[] = $grupo;
        $params[] = $bssId;
        $types   .= 'si';
    }
    if ($producto > 0) {
        $where[]  = 'm.producto_id = ?';
        $params[] = $producto;
        $types   .= 'i';
    }
    if ($tipo === 'MERMA' || $tipo === 'AJUSTE') {
        $where[]  = 'm.tipo = ?';
        $params[] = $tipo;
        $types   .= 's';
    }
    if ($estado === 'ANULADO') {
        $where[] = "m.estado = 'ANULADO'";
    } elseif ($estado === 'TODOS') {
        // sin filtro de estado
    } else {
        $where[] = "m.estado = 'ACTIVO'";
    }

    return [$where, $params, $types];
}

function aj_totales($conexion, array $where, array $params, string $types): array
{
    $sql = "SELECT
                COALESCE(SUM(CASE WHEN m.direccion = 'SALIDA' THEN m.monto_usd ELSE 0 END), 0) AS perdida_usd,
                COALESCE(SUM(CASE WHEN m.direccion = 'SALIDA' THEN m.cantidad ELSE 0 END), 0) AS unidades,
                COALESCE(SUM(CASE WHEN m.tipo = 'MERMA' AND m.direccion = 'SALIDA' THEN m.monto_usd ELSE 0 END), 0) AS merma_usd,
                COALESCE(SUM(CASE WHEN m.tipo = 'AJUSTE' AND m.direccion = 'SALIDA' THEN m.monto_usd ELSE 0 END), 0) AS ajuste_usd,
                COUNT(CASE WHEN m.direccion = 'SALIDA' THEN 1 END) AS registros
            FROM stock_ajustes m
            WHERE " . implode(' AND ', $where);
    $filas = aj_consulta($conexion, $sql, $types, $params);
    $fila = $filas[0] ?? [];

    return [
        'perdida_usd' => round((float) ($fila['perdida_usd'] ?? 0), 2),
        'unidades'    => round((float) ($fila['unidades'] ?? 0), 2),
        'merma_usd'   => round((float) ($fila['merma_usd'] ?? 0), 2),
        'ajuste_usd'  => round((float) ($fila['ajuste_usd'] ?? 0), 2),
        'registros'   => (int) ($fila['registros'] ?? 0),
    ];
}

$accion = trim((string) ($_POST['accion'] ?? $_GET['accion'] ?? ''));
$hoy = date('Y-m-d');

$sucursalParam = (int) ($_POST['sucursal'] ?? $_GET['sucursal'] ?? 0);
[$sucursalOk, $sucursal] = aj_sucursal($conexion, $bssId, $nivel, $sucursalParam);
if (!$sucursalOk) {
    aj_respuesta(['ok' => false, 'message' => 'Sucursal no vÃ¡lida.'], 403);
}

$desdeParam = trim((string) ($_POST['desde'] ?? ''));
$hastaParam = trim((string) ($_POST['hasta'] ?? ''));
$desde = aj_fecha($desdeParam);
$hasta = aj_fecha($hastaParam);
if ($desde === null || $hasta === null || $desde > $hasta) {
    $desde = date('Y-m-01');
    $hasta = $hoy;
}
if ($hasta > $hoy) {
    $hasta = $hoy;
}

$categoria = (int) ($_POST['categoria_id'] ?? $_GET['categoria_id'] ?? 0);
$producto  = (int) ($_POST['producto_id'] ?? $_GET['producto_id'] ?? 0);
$tipoFiltro = strtoupper(trim((string) ($_POST['tipo'] ?? $_GET['tipo'] ?? 'TODAS')));
if ($tipoFiltro !== 'MERMA' && $tipoFiltro !== 'AJUSTE') {
    $tipoFiltro = 'TODAS';
}
$grupoFiltro = trim((string) ($_POST['grupo'] ?? $_GET['grupo'] ?? ''));

try {
    switch ($accion) {
        case 'productos':
            if ($sucursal <= 0 && $nivel === 2) {
                aj_respuesta(['ok' => false, 'message' => 'Debe seleccionar una sucursal.'], 400);
            }

            if ($sucursal > 0) {
                $sql = "SELECT p.id, p.nombre, MIN(s.stock) AS stock,
                               COALESCE(cp.nombre, 'Sin categorÃ­a') AS categoria,
                               ROUND(p.precio_compra / IFNULL(NULLIF(p.cantidad_unidades, 0), 1), 4) AS costo_unitario
                        FROM productos p
                        INNER JOIN stock s
                          ON s.id_producto = p.id
                         AND s.id_sucursal = ?
                         AND s.bss_id = ?
                        LEFT JOIN categorias_producto cp ON cp.id = p.categoria
                        WHERE p.bss_id = ? AND p.activo = 0
                          AND (p.mayor IS NULL OR p.mayor = 0)
                        GROUP BY p.id, p.nombre, cp.nombre, p.precio_compra, p.cantidad_unidades
                        ORDER BY p.nombre ASC";
                $filas = aj_consulta($conexion, $sql, 'iii', [$sucursal, $bssId, $bssId]);
            } else {
                $sql = "SELECT p.id, p.nombre, NULL AS stock,
                               COALESCE(cp.nombre, 'Sin categorÃ­a') AS categoria,
                               ROUND(p.precio_compra / IFNULL(NULLIF(p.cantidad_unidades, 0), 1), 4) AS costo_unitario
                        FROM productos p
                        LEFT JOIN categorias_producto cp ON cp.id = p.categoria
                        WHERE p.bss_id = ? AND p.activo = 0
                          AND (p.mayor IS NULL OR p.mayor = 0)
                        ORDER BY p.nombre ASC";
                $filas = aj_consulta($conexion, $sql, 'i', [$bssId]);
            }

            $productos = [];
            foreach ($filas as $fila) {
                $productos[] = [
                    'id'             => (int) $fila['id'],
                    'nombre'         => $fila['nombre'],
                    'stock'          => $fila['stock'] !== null ? (float) $fila['stock'] : null,
                    'categoria'      => $fila['categoria'],
                    'costo_unitario' => (float) $fila['costo_unitario'],
                ];
            }
            aj_respuesta(['ok' => true, 'data' => $productos]);
            break;

        case 'listar':
            $pagina = max(1, (int) ($_POST['pagina'] ?? 1));
            $limite = 50;
            $offset = ($pagina - 1) * $limite;
            $estado = strtoupper(trim((string) ($_POST['estado'] ?? 'ACTIVO')));
            if (!in_array($estado, ['ACTIVO', 'ANULADO', 'TODOS'], true)) {
                $estado = 'ACTIVO';
            }

            [$where, $params, $types] = aj_filtros($bssId, $sucursal, $desde, $hasta, $categoria, $producto, $tipoFiltro, $estado, $grupoFiltro);

            $sqlTotal = 'SELECT COUNT(*) AS total FROM stock_ajustes m WHERE ' . implode(' AND ', $where);
            $filasTotal = aj_consulta($conexion, $sqlTotal, $types, $params);
            $total = (int) ($filasTotal[0]['total'] ?? 0);

            $sql = "SELECT m.id, m.fecha, m.id_sucursal,
                           COALESCE(s.nombre, 'Sucursal eliminada') AS sucursal,
                           m.producto_id, m.producto_nombre AS producto,
                           m.categoria_nombre AS categoria,
                           m.tipo, m.direccion, m.motivo, m.razon,
                           m.cantidad, m.costo_unitario, m.monto_usd,
                           m.stock_antes, m.stock_despues,
                           m.estado, m.usuario_nombre AS usuario,
                           m.fecha_anulacion, m.motivo_anulacion
                    FROM stock_ajustes m
                    LEFT JOIN sucursales s ON s.id = m.id_sucursal
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY m.fecha DESC, m.id DESC
                    LIMIT ? OFFSET ?";
            $paramsPagina = $params;
            $paramsPagina[] = $limite;
            $paramsPagina[] = $offset;
            $filas = aj_consulta($conexion, $sql, $types . 'ii', $paramsPagina);

            $registros = [];
            foreach ($filas as $fila) {
                $registros[] = [
                    'id'               => (int) $fila['id'],
                    'fecha'            => $fila['fecha'],
                    'id_sucursal'      => (int) $fila['id_sucursal'],
                    'sucursal'         => $fila['sucursal'],
                    'producto_id'      => (int) $fila['producto_id'],
                    'producto'         => $fila['producto'],
                    'categoria'        => $fila['categoria'],
                    'tipo'             => $fila['tipo'],
                    'direccion'        => $fila['direccion'],
                    'motivo'           => $fila['motivo'],
                    'razon'            => $fila['razon'],
                    'cantidad'         => (float) $fila['cantidad'],
                    'costo_unitario'   => (float) $fila['costo_unitario'],
                    'monto_usd'        => (float) $fila['monto_usd'],
                    'stock_antes'      => (float) $fila['stock_antes'],
                    'stock_despues'    => (float) $fila['stock_despues'],
                    'estado'           => $fila['estado'],
                    'usuario'          => $fila['usuario'],
                    'fecha_anulacion'  => $fila['fecha_anulacion'],
                    'motivo_anulacion' => $fila['motivo_anulacion'],
                ];
            }

            aj_respuesta(['ok' => true, 'total' => $total, 'filas' => $registros]);
            break;

        case 'resumen':
            [$whereBase, $paramsBase, $typesBase] = aj_filtros($bssId, $sucursal, null, null, $categoria, $producto, $tipoFiltro, 'ACTIVO', $grupoFiltro);

            $lunes = date('Y-m-d', strtotime('monday this week', strtotime($hoy)));
            $domingo = date('Y-m-d', strtotime($lunes . ' +6 days'));
            $mesDesde = date('Y-m-01');
            $mesHasta = date('Y-m-t');
            $mesAnteriorDesde = date('Y-m-01', strtotime($mesDesde . ' -1 month'));
            $mesAnteriorHasta = date('Y-m-t', strtotime($mesAnteriorDesde));

            $whereSemana = $whereBase;
            $paramsSemana = $paramsBase;
            $typesSemana = $typesBase;
            $whereSemana[] = 'm.fecha BETWEEN ? AND ?';
            $paramsSemana[] = $lunes;
            $paramsSemana[] = $domingo;
            $typesSemana .= 'ss';
            $kpiSemana = aj_totales($conexion, $whereSemana, $paramsSemana, $typesSemana);

            $whereMes = $whereBase;
            $paramsMes = $paramsBase;
            $typesMes = $typesBase;
            $whereMes[] = 'm.fecha BETWEEN ? AND ?';
            $paramsMes[] = $mesDesde;
            $paramsMes[] = $mesHasta;
            $typesMes .= 'ss';
            $kpiMes = aj_totales($conexion, $whereMes, $paramsMes, $typesMes);

            $whereMesAnterior = $whereBase;
            $paramsMesAnterior = $paramsBase;
            $typesMesAnterior = $typesBase;
            $whereMesAnterior[] = 'm.fecha BETWEEN ? AND ?';
            $paramsMesAnterior[] = $mesAnteriorDesde;
            $paramsMesAnterior[] = $mesAnteriorHasta;
            $typesMesAnterior .= 'ss';
            $kpiMesAnterior = aj_totales($conexion, $whereMesAnterior, $paramsMesAnterior, $typesMesAnterior);

            $variacion = 0.0;
            if ($kpiMesAnterior['perdida_usd'] > 0) {
                $variacion = (($kpiMes['perdida_usd'] - $kpiMesAnterior['perdida_usd']) / $kpiMesAnterior['perdida_usd']) * 100;
            } elseif ($kpiMes['perdida_usd'] > 0) {
                $variacion = 100.0;
            }

            // Serie semanal: 8 semanas ISO incluida la actual.
            $inicioSemanal = date('Y-m-d', strtotime($lunes . ' -49 days'));
            $whereSerieSemanal = $whereBase;
            $paramsSerieSemanal = $paramsBase;
            $typesSerieSemanal = $typesBase;
            $whereSerieSemanal[] = "m.direccion = 'SALIDA'";
            $whereSerieSemanal[] = 'm.fecha BETWEEN ? AND ?';
            $paramsSerieSemanal[] = $inicioSemanal;
            $paramsSerieSemanal[] = $domingo;
            $typesSerieSemanal .= 'ss';

            $sqlSerieSemanal = "SELECT DATE_FORMAT(m.fecha, '%x-W%v') AS periodo,
                                       SUM(m.monto_usd) AS monto_usd,
                                       SUM(m.cantidad) AS unidades
                                FROM stock_ajustes m
                                WHERE " . implode(' AND ', $whereSerieSemanal) . "
                                GROUP BY periodo
                                ORDER BY periodo ASC";
            $filasSerieSemanal = aj_consulta($conexion, $sqlSerieSemanal, $typesSerieSemanal, $paramsSerieSemanal);
            $porSemana = [];
            foreach ($filasSerieSemanal as $fila) {
                $porSemana[$fila['periodo']] = $fila;
            }

            $serieSemanal = [];
            for ($i = 7; $i >= 0; $i--) {
                $lunesPeriodo = date('Y-m-d', strtotime($lunes . ' -' . $i . ' weeks'));
                $periodo = date('o-\WW', strtotime($lunesPeriodo));
                $fila = $porSemana[$periodo] ?? null;
                $serieSemanal[] = [
                    'periodo'  => $periodo,
                    'monto_usd' => round((float) ($fila['monto_usd'] ?? 0), 2),
                    'unidades' => round((float) ($fila['unidades'] ?? 0), 2),
                ];
            }
            // Orden cronológico explícito: semana más antigua primero.
            usort($serieSemanal, function ($a, $b) {
                return strcmp((string) $a['periodo'], (string) $b['periodo']);
            });

            // Serie mensual: 6 meses incluido el actual.
            $inicioMensual = date('Y-m-01', strtotime($mesDesde . ' -5 months'));
            $whereSerieMensual = $whereBase;
            $paramsSerieMensual = $paramsBase;
            $typesSerieMensual = $typesBase;
            $whereSerieMensual[] = "m.direccion = 'SALIDA'";
            $whereSerieMensual[] = 'm.fecha BETWEEN ? AND ?';
            $paramsSerieMensual[] = $inicioMensual;
            $paramsSerieMensual[] = $mesHasta;
            $typesSerieMensual .= 'ss';

            $sqlSerieMensual = "SELECT DATE_FORMAT(m.fecha, '%Y-%m') AS periodo,
                                       SUM(m.monto_usd) AS monto_usd,
                                       SUM(m.cantidad) AS unidades
                                FROM stock_ajustes m
                                WHERE " . implode(' AND ', $whereSerieMensual) . "
                                GROUP BY periodo
                                ORDER BY periodo ASC";
            $filasSerieMensual = aj_consulta($conexion, $sqlSerieMensual, $typesSerieMensual, $paramsSerieMensual);
            $porMes = [];
            foreach ($filasSerieMensual as $fila) {
                $porMes[$fila['periodo']] = $fila;
            }

            $serieMensual = [];
            for ($i = 5; $i >= 0; $i--) {
                $periodo = date('Y-m', strtotime($mesDesde . ' -' . $i . ' months'));
                $fila = $porMes[$periodo] ?? null;
                $serieMensual[] = [
                    'periodo'  => $periodo,
                    'monto_usd' => round((float) ($fila['monto_usd'] ?? 0), 2),
                    'unidades' => round((float) ($fila['unidades'] ?? 0), 2),
                ];
            }
            // Orden cronológico explícito: mes más antiguo primero.
            usort($serieMensual, function ($a, $b) {
                return strcmp((string) $a['periodo'], (string) $b['periodo']);
            });

            // PÃ©rdidas por categorÃ­a en el periodo consultado.
            [$whereCategoria, $paramsCategoria, $typesCategoria] = aj_filtros($bssId, $sucursal, $desde, $hasta, $categoria, $producto, $tipoFiltro, 'ACTIVO', $grupoFiltro);
            $whereCategoria[] = "m.direccion = 'SALIDA'";
            $sqlCategoria = "SELECT m.categoria_id,
                                    COALESCE(NULLIF(m.categoria_nombre, ''), 'Sin categorÃ­a') AS categoria,
                                    SUM(m.monto_usd) AS monto_usd,
                                    SUM(m.cantidad) AS unidades
                             FROM stock_ajustes m
                             WHERE " . implode(' AND ', $whereCategoria) . "
                             GROUP BY m.categoria_id, categoria
                             ORDER BY monto_usd DESC";
            $filasCategoria = aj_consulta($conexion, $sqlCategoria, $typesCategoria, $paramsCategoria);

            $porCategoria = [];
            foreach ($filasCategoria as $fila) {
                $porCategoria[] = [
                    'categoria_id' => (int) $fila['categoria_id'],
                    'categoria'    => $fila['categoria'],
                    'monto_usd'    => round((float) $fila['monto_usd'], 2),
                    'unidades'     => round((float) $fila['unidades'], 2),
                ];
            }
            if (count($porCategoria) > 8) {
                $top = array_slice($porCategoria, 0, 7);
                $otrosMonto = 0.0;
                $otrosUnidades = 0.0;
                foreach (array_slice($porCategoria, 7) as $resto) {
                    $otrosMonto += $resto['monto_usd'];
                    $otrosUnidades += $resto['unidades'];
                }
                $top[] = [
                    'categoria_id' => 0,
                    'categoria'    => 'Otros',
                    'monto_usd'    => round($otrosMonto, 2),
                    'unidades'     => round($otrosUnidades, 2),
                ];
                $porCategoria = $top;
            }

            // PÃ©rdidas por sucursal (solo administradores).
            $porSucursal = [];
            if ($nivel === 1) {
                [$whereSucursal, $paramsSucursal, $typesSucursal] = aj_filtros($bssId, $sucursal, $desde, $hasta, $categoria, $producto, $tipoFiltro, 'ACTIVO', $grupoFiltro);
                $whereSucursal[] = "m.direccion = 'SALIDA'";
                $sqlSucursal = "SELECT m.id_sucursal,
                                       COALESCE(s.nombre, 'Sucursal eliminada') AS sucursal,
                                       SUM(m.monto_usd) AS monto_usd,
                                       SUM(m.cantidad) AS unidades
                                FROM stock_ajustes m
                                LEFT JOIN sucursales s ON s.id = m.id_sucursal
                                WHERE " . implode(' AND ', $whereSucursal) . "
                                GROUP BY m.id_sucursal, s.nombre
                                ORDER BY monto_usd DESC";
                $filasSucursal = aj_consulta($conexion, $sqlSucursal, $typesSucursal, $paramsSucursal);
                foreach ($filasSucursal as $fila) {
                    $porSucursal[] = [
                        'id_sucursal' => (int) $fila['id_sucursal'],
                        'sucursal'    => $fila['sucursal'],
                        'monto_usd'   => round((float) $fila['monto_usd'], 2),
                        'unidades'    => round((float) $fila['unidades'], 2),
                    ];
                }
            }

            // Top 10 productos con mayor pÃ©rdida en el periodo.
            [$whereTop, $paramsTop, $typesTop] = aj_filtros($bssId, $sucursal, $desde, $hasta, $categoria, $producto, $tipoFiltro, 'ACTIVO', $grupoFiltro);
            $whereTop[] = "m.direccion = 'SALIDA'";
            $sqlTop = "SELECT m.producto_id, m.producto_nombre AS producto,
                              SUM(m.monto_usd) AS monto_usd,
                              SUM(m.cantidad) AS unidades
                       FROM stock_ajustes m
                       WHERE " . implode(' AND ', $whereTop) . "
                       GROUP BY m.producto_id, m.producto_nombre
                       ORDER BY monto_usd DESC
                       LIMIT 10";
            $filasTop = aj_consulta($conexion, $sqlTop, $typesTop, $paramsTop);
            $topProductos = [];
            foreach ($filasTop as $fila) {
                $topProductos[] = [
                    'producto_id' => (int) $fila['producto_id'],
                    'producto'    => $fila['producto'],
                    'monto_usd'   => round((float) $fila['monto_usd'], 2),
                    'unidades'    => round((float) $fila['unidades'], 2),
                ];
            }

            aj_respuesta([
                'ok' => true,
                'kpis' => [
                    'perdida_semana_usd'      => $kpiSemana['perdida_usd'],
                    'perdida_mes_usd'         => $kpiMes['perdida_usd'],
                    'unidades_mes'            => $kpiMes['unidades'],
                    'registros_mes'           => $kpiMes['registros'],
                    'variacion_mes_pct'       => round($variacion, 1),
                    'merma_mes_usd'           => $kpiMes['merma_usd'],
                    'ajuste_negativo_mes_usd' => $kpiMes['ajuste_usd'],
                ],
                'serie_semanal' => $serieSemanal,
                'serie_mensual' => $serieMensual,
                'por_categoria' => $porCategoria,
                'por_sucursal'  => $porSucursal,
                'top_productos' => $topProductos,
            ]);
            break;

        case 'registrar':
            $tipo = strtoupper(trim((string) ($_POST['tipo'] ?? '')));
            if (!in_array($tipo, ['MERMA', 'AJUSTE'], true)) {
                aj_respuesta(['ok' => false, 'message' => 'Tipo de registro no vÃ¡lido.'], 400);
            }
            if ($sucursal <= 0) {
                aj_respuesta(['ok' => false, 'message' => 'Debe seleccionar una sucursal.'], 400);
            }

            $productoId = (int) ($_POST['producto_id'] ?? 0);
            if ($productoId <= 0) {
                aj_respuesta(['ok' => false, 'message' => 'Debe seleccionar un producto.'], 400);
            }

            $motivo = strtoupper(trim((string) ($_POST['motivo'] ?? '')));
            $motivosValidos = $tipo === 'MERMA' ? ajustes_motivos_merma() : ajustes_motivos_ajuste();
            if (!isset($motivosValidos[$motivo])) {
                aj_respuesta(['ok' => false, 'message' => 'Motivo no vÃ¡lido para este tipo de registro.'], 400);
            }

            $razon = trim(strip_tags((string) ($_POST['razon'] ?? '')));
            if ($razon === '') {
                aj_respuesta(['ok' => false, 'message' => 'La razÃ³n o explicaciÃ³n es obligatoria.'], 400);
            }
            if (function_exists('mb_substr')) {
                $razon = mb_substr($razon, 0, 500, 'UTF-8');
            } else {
                $razon = substr($razon, 0, 500);
            }

            $fecha = aj_fecha($_POST['fecha'] ?? '');
            if ($fecha === null) {
                $fecha = $hoy;
            }
            if ($fecha > $hoy) {
                aj_respuesta(['ok' => false, 'message' => 'La fecha no puede ser futura.'], 400);
            }

            $cantidad   = (float) str_replace(',', '.', (string) ($_POST['cantidad'] ?? '0'));
            $stockReal  = (float) str_replace(',', '.', (string) ($_POST['stock_real'] ?? '0'));

            $conexion->begin_transaction();
            try {
                $stmtProducto = $conexion->prepare(
                    "SELECT p.id, p.nombre, p.precio_compra, p.cantidad_unidades, p.categoria,
                            p.modo_control, cp.nombre AS categoria_nombre
                     FROM productos p
                     LEFT JOIN categorias_producto cp ON cp.id = p.categoria
                     WHERE p.id = ? AND p.bss_id = ? AND p.activo = 0
                       AND (p.mayor IS NULL OR p.mayor = 0)
                     LIMIT 1
                     FOR UPDATE"
                );
                if (!$stmtProducto) {
                    throw new Exception('No se pudo consultar el producto.');
                }
                $stmtProducto->bind_param('ii', $productoId, $bssId);
                $stmtProducto->execute();
                $producto = $stmtProducto->get_result()->fetch_assoc();
                $stmtProducto->close();

                if (!$producto) {
                    throw new Exception('Producto no encontrado o inactivo.');
                }

                $stmtStock = $conexion->prepare(
                    'SELECT id, stock FROM stock
                     WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
                     ORDER BY id ASC LIMIT 1 FOR UPDATE'
                );
                if (!$stmtStock) {
                    throw new Exception('No se pudo consultar el stock.');
                }
                $stmtStock->bind_param('iii', $productoId, $sucursal, $bssId);
                $stmtStock->execute();
                $stockFila = $stmtStock->get_result()->fetch_assoc();
                $stmtStock->close();

                if (!$stockFila) {
                    throw new Exception('El producto no tiene stock registrado en esta sucursal.');
                }

                $stockId = (int) $stockFila['id'];
                $stockAntes = round((float) $stockFila['stock'], 4);

                if ($tipo === 'MERMA') {
                    $cantidad = round($cantidad, 4);
                    if ($cantidad <= 0) {
                        throw new Exception('La cantidad de la merma debe ser mayor que cero.');
                    }
                    if ($stockAntes <= 0) {
                        throw new Exception('No hay existencia suficiente. Stock actual: ' . ajustes_numero($stockAntes) . '.');
                    }
                    if ($cantidad > $stockAntes) {
                        $exceso = $cantidad - $stockAntes;
                        // El stock se muestra redondeado (2 decimales en pantallas
                        // anteriores). Si la diferencia es solo de redondeo, se
                        // descuenta todo el stock para no dejar residuos como 0.005.
                        if ($exceso <= 0.0051) {
                            $cantidad = $stockAntes;
                            $stockDespues = 0.0;
                        } else {
                            throw new Exception('No hay existencia suficiente. Stock actual: ' . ajustes_numero($stockAntes) . '.');
                        }
                    } else {
                        $stockDespues = $stockAntes - $cantidad;
                        if ($stockDespues < 0.00005) {
                            $stockDespues = 0.0;
                        }
                    }
                    $stockDespues = round($stockDespues, 4);
                    $direccion = 'SALIDA';
                } else {
                    $stockReal = round($stockReal, 4);
                    if ($stockReal < 0) {
                        throw new Exception('El stock real no puede ser negativo.');
                    }
                    $diferencia = $stockReal - $stockAntes;
                    if (abs($diferencia) < 0.0001) {
                        throw new Exception('El stock real coincide con el sistema; no hay nada que ajustar.');
                    }
                    $direccion = $diferencia > 0 ? 'ENTRADA' : 'SALIDA';
                    $cantidad = round(abs($diferencia), 4);
                    $stockDespues = $stockReal;
                }

                $unidades = (float) $producto['cantidad_unidades'];
                if ($unidades <= 0) {
                    $unidades = 1;
                }
                $costoUnitario = round((float) $producto['precio_compra'] / $unidades, 4);
                $montoUsd = round($cantidad * $costoUnitario, 2);

                $categoriaId = (int) ($producto['categoria'] ?? 0);
                if ($categoriaId < 0) {
                    $categoriaId = 0;
                }
                $categoriaNombre = trim((string) ($producto['categoria_nombre'] ?? ''));
                if ($categoriaNombre === '') {
                    $categoriaNombre = 'Sin categorÃ­a';
                }

                $stmtUpdate = $conexion->prepare('UPDATE stock SET stock = ? WHERE id = ?');
                if (!$stmtUpdate) {
                    throw new Exception('No se pudo preparar la actualizaciÃ³n de stock.');
                }
                $stockTexto = ajustes_numero($stockDespues);
                $stmtUpdate->bind_param('si', $stockTexto, $stockId);
                if (!$stmtUpdate->execute()) {
                    $stmtUpdate->close();
                    throw new Exception('No se pudo actualizar el stock.');
                }
                $stmtUpdate->close();

                // Si la diferencia se registra sobre una res CANAL, el lote debe
                // quedar sincronizado: el stock y kg_restante son la misma bolsa.
                if ((string) ($producto['modo_control'] ?? 'NORMAL') === 'CANAL') {
                    $stmtLote = $conexion->prepare(
                        "SELECT id FROM lotes
                         WHERE bss_id = ? AND id_sucursal = ? AND padre_id = ? AND estado = 'ABIERTO'
                         ORDER BY id ASC LIMIT 1 FOR UPDATE"
                    );
                    if ($stmtLote) {
                        $stmtLote->bind_param('iii', $bssId, $sucursal, $productoId);
                        $stmtLote->execute();
                        $loteFila = $stmtLote->get_result()->fetch_assoc();
                        $stmtLote->close();

                        if ($loteFila) {
                            $loteId = (int) $loteFila['id'];
                            $delta = $stockDespues - $stockAntes;
                            $stmtLoteUpd = $conexion->prepare('UPDATE lotes SET kg_restante = GREATEST(0, kg_restante + ?) WHERE id = ?');
                            if ($stmtLoteUpd) {
                                $stmtLoteUpd->bind_param('di', $delta, $loteId);
                                $stmtLoteUpd->execute();
                                $stmtLoteUpd->close();
                            }
                            $stmtDet = $conexion->prepare(
                                "INSERT INTO lotes_detalle (lote_id, producto_id, cantidad, origen, usuario_id)
                                 VALUES (?, ?, ?, ?, ?)"
                            );
                            if ($stmtDet) {
                                $origenDet = $tipo === 'MERMA' ? 'MERMA' : 'AJUSTE';
                                $cantidadDet = abs($delta);
                                $stmtDet->bind_param('iidsi', $loteId, $productoId, $cantidadDet, $origenDet, $usuarioId);
                                $stmtDet->execute();
                                $stmtDet->close();
                            }
                        }
                    }
                }

                $stmtInsert = $conexion->prepare(
                    "INSERT INTO stock_ajustes
                        (bss_id, id_sucursal, producto_id, producto_nombre, categoria_id, categoria_nombre,
                         tipo, direccion, motivo, razon, cantidad, costo_unitario, monto_usd,
                         stock_antes, stock_despues, fecha, usuario_id, usuario_nombre)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$stmtInsert) {
                    throw new Exception('No se pudo preparar el registro.');
                }
                $stmtInsert->bind_param(
                    'iiisisssssdddddsis',
                    $bssId,
                    $sucursal,
                    $productoId,
                    $producto['nombre'],
                    $categoriaId,
                    $categoriaNombre,
                    $tipo,
                    $direccion,
                    $motivo,
                    $razon,
                    $cantidad,
                    $costoUnitario,
                    $montoUsd,
                    $stockAntes,
                    $stockDespues,
                    $fecha,
                    $usuarioId,
                    $usuarioNombre
                );
                if (!$stmtInsert->execute()) {
                    $stmtInsert->close();
                    throw new Exception('No se pudo guardar el registro.');
                }
                $registroId = (int) $stmtInsert->insert_id;
                $stmtInsert->close();

                $conexion->commit();

                aj_respuesta([
                    'ok'            => true,
                    'message'       => $tipo === 'MERMA' ? 'Merma registrada correctamente.' : 'Ajuste registrado correctamente.',
                    'id'            => $registroId,
                    'stock_despues' => round($stockDespues, 4),
                    'monto_usd'     => $montoUsd,
                    'usuario'       => $usuarioNombre,
                ]);
            } catch (Throwable $e) {
                $conexion->rollback();
                aj_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'anular':
            $id = (int) ($_POST['id'] ?? 0);
            $motivoAnulacion = trim(strip_tags((string) ($_POST['motivo'] ?? '')));
            if ($id <= 0) {
                aj_respuesta(['ok' => false, 'message' => 'Registro no vÃ¡lido.'], 400);
            }
            if ($motivoAnulacion === '') {
                aj_respuesta(['ok' => false, 'message' => 'El motivo de anulaciÃ³n es obligatorio.'], 400);
            }
            if (function_exists('mb_substr')) {
                $motivoAnulacion = mb_substr($motivoAnulacion, 0, 500, 'UTF-8');
            } else {
                $motivoAnulacion = substr($motivoAnulacion, 0, 500);
            }

            $conexion->begin_transaction();
            try {
                $sqlRegistro = 'SELECT * FROM stock_ajustes WHERE id = ? AND bss_id = ?';
                $tiposRegistro = 'ii';
                $paramsRegistro = [$id, $bssId];
                if ($nivel === 2) {
                    $sqlRegistro .= ' AND id_sucursal = ?';
                    $tiposRegistro .= 'i';
                    $paramsRegistro[] = $sucursal;
                }
                $sqlRegistro .= ' FOR UPDATE';

                $stmtRegistro = $conexion->prepare($sqlRegistro);
                if (!$stmtRegistro) {
                    throw new Exception('No se pudo consultar el registro.');
                }
                $stmtRegistro->bind_param($tiposRegistro, ...$paramsRegistro);
                $stmtRegistro->execute();
                $registro = $stmtRegistro->get_result()->fetch_assoc();
                $stmtRegistro->close();

                if (!$registro) {
                    throw new Exception('Registro no encontrado.');
                }
                if ($registro['estado'] !== 'ACTIVO') {
                    throw new Exception('Este registro ya fue anulado.');
                }

                $stmtStock = $conexion->prepare(
                    'SELECT id, stock FROM stock
                     WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
                     ORDER BY id ASC LIMIT 1 FOR UPDATE'
                );
                if (!$stmtStock) {
                    throw new Exception('No se pudo consultar el stock.');
                }
                $productoRegistro = (int) $registro['producto_id'];
                $sucursalRegistro = (int) $registro['id_sucursal'];
                $stmtStock->bind_param('iii', $productoRegistro, $sucursalRegistro, $bssId);
                $stmtStock->execute();
                $stockFila = $stmtStock->get_result()->fetch_assoc();
                $stmtStock->close();

                if (!$stockFila) {
                    throw new Exception('El producto ya no tiene una fila de stock en esta sucursal.');
                }

                $stockId = (int) $stockFila['id'];
                $stockActual = (float) $stockFila['stock'];
                $cantidadRegistro = (float) $registro['cantidad'];

                if ($registro['direccion'] === 'SALIDA') {
                    $stockNuevo = $stockActual + $cantidadRegistro;
                } else {
                    $stockNuevo = $stockActual - $cantidadRegistro;
                    if ($stockNuevo < -0.0001) {
                        throw new Exception('No se puede revertir: el stock actual no alcanza para deshacer esta entrada.');
                    }
                    $stockNuevo = max(0, $stockNuevo);
                }

                $stmtUpdate = $conexion->prepare('UPDATE stock SET stock = ? WHERE id = ?');
                if (!$stmtUpdate) {
                    throw new Exception('No se pudo preparar la actualizaciÃ³n de stock.');
                }
                $stockTexto = ajustes_numero($stockNuevo);
                $stmtUpdate->bind_param('si', $stockTexto, $stockId);
                if (!$stmtUpdate->execute()) {
                    $stmtUpdate->close();
                    throw new Exception('No se pudo actualizar el stock.');
                }
                $stmtUpdate->close();

                $stmtAnular = $conexion->prepare(
                    "UPDATE stock_ajustes
                     SET estado = 'ANULADO',
                         usuario_anulacion = ?,
                         fecha_anulacion = NOW(),
                         motivo_anulacion = ?
                     WHERE id = ? AND bss_id = ?"
                );
                if (!$stmtAnular) {
                    throw new Exception('No se pudo preparar la anulaciÃ³n.');
                }
                $stmtAnular->bind_param('isii', $usuarioId, $motivoAnulacion, $id, $bssId);
                if (!$stmtAnular->execute()) {
                    $stmtAnular->close();
                    throw new Exception('No se pudo anular el registro.');
                }
                $stmtAnular->close();

                $conexion->commit();

                aj_respuesta([
                    'ok'            => true,
                    'message'       => 'Registro anulado y stock revertido.',
                    'stock_despues' => round($stockNuevo, 4),
                ]);
            } catch (Throwable $e) {
                $conexion->rollback();
                aj_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        default:
            aj_respuesta(['ok' => false, 'message' => 'AcciÃ³n no vÃ¡lida.'], 400);
    }
} catch (Throwable $e) {
    aj_respuesta(['ok' => false, 'message' => $e->getMessage()], 500);
}

