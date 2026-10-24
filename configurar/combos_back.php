<?php
require_once('configuracion.php');
require_once('session.php');
require_once(__DIR__ . '/_calculadrora_precios.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida']);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$nivel  = (int)($_SESSION['nivel'] ?? 2);
$sucursal_sesion = (int)($_SESSION['sucursal'] ?? 0);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_REQUEST;
}

$accion = $input['accion'] ?? '';
$sucursal_id = ($nivel === 1 && !empty($input['sucursal_id'])) ? (int)$input['sucursal_id'] : $sucursal_sesion;

if ($sucursal_id <= 0) {
    $stmtS = $conexion->prepare("SELECT id FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, id ASC LIMIT 1");
    $stmtS->bind_param("i", $bss_id);
    $stmtS->execute();
    $resS = $stmtS->get_result();
    if ($rowS = $resS->fetch_assoc()) {
        $sucursal_id = (int)$rowS['id'];
    }
    $stmtS->close();
}

function normalizarOrigen($origen): string
{
    $origen = strtolower(trim((string)$origen));
    return in_array($origen, ['c', 'm'], true) ? $origen : 'v';
}

function etiquetaOrigen(string $origen): string
{
    if ($origen === 'c') return 'Colombiano';
    if ($origen === 'm') return 'Mixto';
    return 'Venezolano';
}

function tasasCombo($conexion, int $bssId): array
{
    $tasas = ['pesoDolar' => 0.0, 'DolarBolivar' => 0.0, 'peso_bolivar' => 0.0, 'bolivar_peso' => 0.0, 'bcv' => 0.0];

    $stmt = $conexion->prepare("SELECT pesoDolar, DolarBolivar, peso_bolivar, bolivar_peso FROM cambio WHERE bss_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $bssId);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            foreach (['pesoDolar', 'DolarBolivar', 'peso_bolivar', 'bolivar_peso'] as $k) {
                $tasas[$k] = (float)$row[$k];
            }
        }
        $stmt->close();
    }

    $res = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC, id DESC LIMIT 1");
    if ($res && ($row = $res->fetch_assoc())) {
        $tasas['bcv'] = (float)$row['valor'];
    }

    return $tasas;
}

/**
 * Convierte un precio nominal en USD a USD normalizado por BCV.
 * Colombiano: USD -> COP -> Bs (/peso_bolivar) -> USD (/BCV).
 * Manual: USD -> Bs (*DolarBolivar) -> USD (/BCV).
 * Si faltan tasas, devuelve el valor nominal para no romper la pantalla.
 */
function preciosBcvCombo(CalculadoraPrecios $calc, float $costoUnit, float $ventaUnit, string $origen, array $tasas): array
{
    $nominal = ['costo' => round($costoUnit, 4), 'venta' => round($ventaUnit, 4)];
    if ($tasas['bcv'] <= 0) {
        return $nominal;
    }

    $base = ['cantidad_unidades' => 1, 'origen' => $origen, 'mayor' => 0];
    $costo = $calc->calcularPrecios($base + ['precio_compra' => $costoUnit, 'porcentaje' => 0]);
    $venta = $calc->calcularPrecios($base + ['precio_compra' => $costoUnit, 'porcentaje' => (($ventaUnit / max($costoUnit, 1e-9)) - 1) * 100]);

    $costoBcv = round(((float)$costo['precio_venta_bs']) / $tasas['bcv'], 4);
    $ventaBcv = round(((float)$venta['precio_venta_bs']) / $tasas['bcv'], 4);

    if ($costoBcv <= 0 || $ventaBcv <= 0) {
        return $nominal;
    }

    return ['costo' => $costoBcv, 'venta' => $ventaBcv];
}

$tasasCombo = tasasCombo($conexion, $bss_id);
$calcCombo = new CalculadoraPrecios(
    $tasasCombo['pesoDolar'],
    $tasasCombo['peso_bolivar'],
    $tasasCombo['DolarBolivar'],
    $tasasCombo['bolivar_peso'],
    $tasasCombo['bcv'],
    []
);

if ($accion === 'listar_productos') {
    $sql = "SELECT 
                p.id,
                p.nombre,
                p.codigo_barras,
                p.origen,
                p.precio_compra,
                p.cantidad_unidades,
                s.porcentaje,
                s.stock
            FROM productos p
            INNER JOIN stock s ON s.id_producto = p.id
            WHERE p.bss_id = ?
              AND s.bss_id = ?
              AND s.id_sucursal = ?
              AND p.activo = 0
              AND (p.mayor IS NULL OR p.mayor != 1)
              AND (p.es_combo IS NULL OR p.es_combo = 0)
            ORDER BY p.nombre ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $bss_id, $bss_id, $sucursal_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $productos = [];
    while ($row = $res->fetch_assoc()) {
        $unidades = max(1, (float)$row['cantidad_unidades']);
        $costoUnit = round((float)$row['precio_compra'] / $unidades, 4);
        $pct = (float)$row['porcentaje'];
        $ventaUnit = round($costoUnit * (1 + ($pct / 100)), 2);
        $origenNorm = normalizarOrigen($row['origen']);
        $bcv = preciosBcvCombo($calcCombo, $costoUnit, $ventaUnit, $origenNorm, $tasasCombo);

        $productos[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'codigo_barras' => $row['codigo_barras'] ?? '',
            'origen' => $origenNorm,
            'origen_label' => etiquetaOrigen($origenNorm),
            'stock' => (float)$row['stock'],
            'costo_unitario' => $costoUnit,
            'porcentaje' => $pct,
            'venta_unitario' => $ventaUnit,
            'costo_unitario_bcv' => $bcv['costo'],
            'venta_unitario_bcv' => $bcv['venta']
        ];
    }
    $stmt->close();

    echo json_encode(['status' => 'success', 'productos' => $productos, 'sucursal_id' => $sucursal_id]);
    exit;
}

if ($accion === 'listar_combos') {
    $sqlCombos = "SELECT 
                    p.id,
                    p.nombre,
                    p.origen,
                    p.precio_compra,
                    s.porcentaje
                  FROM productos p
                  INNER JOIN stock s ON s.id_producto = p.id
                  WHERE p.bss_id = ?
                    AND s.bss_id = ?
                    AND s.id_sucursal = ?
                    AND p.activo = 0
                    AND p.es_combo = 1
                  ORDER BY p.id DESC";
    $stmt = $conexion->prepare($sqlCombos);
    $stmt->bind_param("iii", $bss_id, $bss_id, $sucursal_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $combos = [];
    $stmtItems = $conexion->prepare("
        SELECT 
            ci.producto_id,
            ci.cantidad,
            ci.costo_unitario_ref,
            ci.venta_unitario_ref,
            p.nombre,
            p.origen,
            p.precio_compra,
            p.cantidad_unidades,
            COALESCE(s.porcentaje, p.porcentaje, 0) AS porcentaje,
            COALESCE(s.stock, 0) AS stock_actual
        FROM combo_items ci
        INNER JOIN productos p ON p.id = ci.producto_id
        LEFT JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
        WHERE ci.combo_producto_id = ? AND ci.bss_id = ?
    ");

    while ($row = $res->fetch_assoc()) {
        $comboId = (int)$row['id'];
        $costoReg = round((float)$row['precio_compra'], 2);
        $pctReg   = (float)$row['porcentaje'];
        // Igual que el POS (CalculadoraPrecios): el precio de venta se calcula sobre el
        // costo completo, no sobre el redondeado a 2, o difiere en centavos.
        $ventaCombo = round((float)$row['precio_compra'] * (1 + ($pctReg / 100)), 2);
        $origenNorm = normalizarOrigen($row['origen']);

        $stmtItems->bind_param("iiii", $sucursal_id, $bss_id, $comboId, $bss_id);
        $stmtItems->execute();
        $resIt = $stmtItems->get_result();

        $items = [];
        $costoActualHijos = 0.0;
        $ventaActualHijos = 0.0;
        $costoRefHijos    = 0.0;
        $ventaRefHijos    = 0.0;
        $stockDisponible  = null;
        $itemsConCambio   = 0;

        while ($it = $resIt->fetch_assoc()) {
            $qty = (float)$it['cantidad'];
            $unidades = max(1, (float)$it['cantidad_unidades']);
            $costoAct = round((float)$it['precio_compra'] / $unidades, 4);
            $pctAct   = (float)$it['porcentaje'];
            $ventaAct = round($costoAct * (1 + ($pctAct / 100)), 2);
            $bcvAct = preciosBcvCombo($calcCombo, $costoAct, $ventaAct, normalizarOrigen($it['origen']), $tasasCombo);
            if ($origenNorm === 'm') {
                $costoAct = $bcvAct['costo'];
                $ventaAct = $bcvAct['venta'];
            }

            $costoRef = (float)$it['costo_unitario_ref'];
            $ventaRef = (float)$it['venta_unitario_ref'];

            $diffCosto = round($costoAct - $costoRef, 4);
            $diffVenta = round($ventaAct - $ventaRef, 2);
            $cambioItem = (abs($diffCosto) >= 0.005 || abs($diffVenta) >= 0.01);
            if ($cambioItem) {
                $itemsConCambio++;
            }

            $costoActualHijos += $costoAct * $qty;
            $ventaActualHijos += $ventaAct * $qty;
            $costoRefHijos    += $costoRef * $qty;
            $ventaRefHijos    += $ventaRef * $qty;

            $posibles = $qty > 0 ? (int)floor((float)$it['stock_actual'] / $qty) : 0;
            if ($stockDisponible === null || $posibles < $stockDisponible) {
                $stockDisponible = $posibles;
            }

            $items[] = [
                'producto_id'   => (int)$it['producto_id'],
                'nombre'        => $it['nombre'],
                'cantidad'      => $qty,
                'stock_actual'  => (float)$it['stock_actual'],
                'costo_ref'     => round($costoRef, 2),
                'venta_ref'     => round($ventaRef, 2),
                'costo_actual'  => round($costoAct, 2),
                'venta_actual'  => round($ventaAct, 2),
                'diff_costo'    => round($diffCosto, 2),
                'diff_venta'    => round($diffVenta, 2),
                'cambio'        => $cambioItem
            ];
        }

        $costoActualHijos = round($costoActualHijos, 2);
        $ventaActualHijos = round($ventaActualHijos, 2);
        $diffCostoTotal   = round($costoActualHijos - $costoReg, 2);
        $tieneVariacion   = ($itemsConCambio > 0 || abs($diffCostoTotal) >= 0.01);

        $combos[] = [
            'id'                 => $comboId,
            'nombre'             => $row['nombre'],
            'origen'             => $origenNorm,
            'origen_label'       => etiquetaOrigen($origenNorm),
            'costo_registrado'   => $costoReg,
            'costo_actual_hijos' => $costoActualHijos,
            'diff_costo_total'   => $diffCostoTotal,
            'venta_actual_hijos' => $ventaActualHijos,
            'precio_venta_combo' => $ventaCombo,
            'margen_actual_usd'  => round($ventaCombo - $costoActualHijos, 2),
            'stock_disponible'   => max(0, (int)($stockDisponible ?? 0)),
            'tiene_variacion'    => $tieneVariacion,
            'items_con_cambio'   => $itemsConCambio,
            'alerta_perdida'     => ($ventaCombo < $costoActualHijos),
            'items'              => $items
        ];
    }

    $stmtItems->close();
    $stmt->close();

    echo json_encode(['status' => 'success', 'combos' => $combos]);
    exit;
}

if ($accion === 'guardar_combo') {
    $combo_id      = (int)($input['combo_id'] ?? 0);
    $nombre        = trim((string)($input['nombre'] ?? ''));
    $precio_venta  = round((float)($input['precio_venta'] ?? 0), 2);
    $items         = $input['items'] ?? [];

    if ($nombre === '') {
        echo json_encode(['status' => 'error', 'message' => 'El nombre del combo es obligatorio.']);
        exit;
    }
    if ($precio_venta <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'El precio de venta del combo debe ser mayor a $0.']);
        exit;
    }
    if (!is_array($items) || count($items) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Debes agregar al menos un producto al combo.']);
        exit;
    }

    // Consolidar cantidades por producto_id
    $cantidadesPorProd = [];
    foreach ($items as $it) {
        $pid = (int)($it['producto_id'] ?? 0);
        $qty = round((float)($it['cantidad'] ?? 0), 2);
        if ($pid > 0 && $qty > 0) {
            $cantidadesPorProd[$pid] = ($cantidadesPorProd[$pid] ?? 0) + $qty;
        }
    }

    if (empty($cantidadesPorProd)) {
        echo json_encode(['status' => 'error', 'message' => 'Las cantidades de los productos deben ser mayores a 0.']);
        exit;
    }

    // Consultar productos hijos para validar origen y calcular costos reales
    $idsPlaceholders = implode(',', array_fill(0, count($cantidadesPorProd), '?'));
    $types = str_repeat('i', count($cantidadesPorProd)) . 'iii';
    $params = array_keys($cantidadesPorProd);
    $params[] = $sucursal_id;
    $params[] = $bss_id;
    $params[] = $bss_id;

    $sqlCheck = "SELECT 
                    p.id,
                    p.nombre,
                    p.origen,
                    p.precio_compra,
                    p.cantidad_unidades,
                    COALESCE(s.porcentaje, p.porcentaje, 0) AS porcentaje
                 FROM productos p
                 LEFT JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
                 WHERE p.id IN ($idsPlaceholders)
                   AND p.bss_id = ?
                   AND p.activo = 0
                   AND (p.es_combo IS NULL OR p.es_combo = 0)";

    // Reordenar parámetros según aparición en query
    $bindParams = array_merge([$sucursal_id, $bss_id], array_keys($cantidadesPorProd), [$bss_id]);
    $bindTypes  = 'ii' . str_repeat('i', count($cantidadesPorProd)) . 'i';

    $stmtC = $conexion->prepare($sqlCheck);
    $stmtC->bind_param($bindTypes, ...$bindParams);
    $stmtC->execute();
    $resC = $stmtC->get_result();

    $origenesDetectados = [];
    $costoTotalCompra = 0.0;
    $itemsParaInsertar = [];

    while ($rowP = $resC->fetch_assoc()) {
        $pid = (int)$rowP['id'];
        $qty = $cantidadesPorProd[$pid];
        $orig = normalizarOrigen($rowP['origen']);
        $origenesDetectados[$orig] = true;

        $unidades  = max(1, (float)$rowP['cantidad_unidades']);
        $costoUnit = round((float)$rowP['precio_compra'] / $unidades, 4);
        $pctUnit   = (float)$rowP['porcentaje'];
        $ventaUnit = round($costoUnit * (1 + ($pctUnit / 100)), 2);
        $bcv = preciosBcvCombo($calcCombo, $costoUnit, $ventaUnit, $orig, $tasasCombo);

        $itemsParaInsertar[] = [
            'producto_id' => $pid,
            'cantidad'    => $qty,
            'costo_nominal' => $costoUnit,
            'venta_nominal' => $ventaUnit,
            'costo_bcv'     => $bcv['costo'],
            'venta_bcv'     => $bcv['venta']
        ];
    }
    $stmtC->close();

    $origenDetectado = (count($origenesDetectados) === 1)
        ? (string)array_key_first($origenesDetectados)
        : 'm';
    $esMixto = ($origenDetectado === 'm');

    $costoTotalCompra = 0.0;
    foreach ($itemsParaInsertar as &$it) {
        $it['costo_ref'] = $esMixto ? $it['costo_bcv'] : $it['costo_nominal'];
        $it['venta_ref'] = $esMixto ? $it['venta_bcv'] : $it['venta_nominal'];
        $costoTotalCompra += $it['costo_ref'] * $it['cantidad'];
    }
    unset($it);
    $costoTotalCompra = round($costoTotalCompra, 4);

    if (count($itemsParaInsertar) !== count($cantidadesPorProd)) {
        echo json_encode(['status' => 'error', 'message' => 'Uno o más productos seleccionados no son válidos.']);
        exit;
    }

    $costoTotalCompra = round($costoTotalCompra, 4);
    if ($costoTotalCompra > 0) {
        $porcentajeCombo = round((($precio_venta - $costoTotalCompra) / $costoTotalCompra) * 100, 4);
    } else {
        $costoTotalCompra = $precio_venta;
        $porcentajeCombo  = 0;
    }

    $conexion->begin_transaction();
    try {
        if ($combo_id > 0) {
            // Verificar que el combo pertenezca al negocio
            $stmtOwn = $conexion->prepare("SELECT id FROM productos WHERE id = ? AND bss_id = ? AND es_combo = 1 LIMIT 1");
            $stmtOwn->bind_param("ii", $combo_id, $bss_id);
            $stmtOwn->execute();
            if ($stmtOwn->get_result()->num_rows === 0) {
                throw new Exception("Combo no encontrado.");
            }
            $stmtOwn->close();

            $stmtUpd = $conexion->prepare("UPDATE productos SET nombre = ?, precio_compra = ?, porcentaje = ?, origen = ? WHERE id = ? AND bss_id = ?");
            $costoStr = (string)$costoTotalCompra;
            $pctStr   = (string)$porcentajeCombo;
            $stmtUpd->bind_param("ssssii", $nombre, $costoStr, $pctStr, $origenDetectado, $combo_id, $bss_id);
            $stmtUpd->execute();
            $stmtUpd->close();

            $stmtUpdS = $conexion->prepare("UPDATE stock SET porcentaje = ? WHERE id_producto = ? AND bss_id = ?");
            $stmtUpdS->bind_param("sii", $pctStr, $combo_id, $bss_id);
            $stmtUpdS->execute();
            $stmtUpdS->close();

            $stmtDelIt = $conexion->prepare("DELETE FROM combo_items WHERE combo_producto_id = ? AND bss_id = ?");
            $stmtDelIt->bind_param("ii", $combo_id, $bss_id);
            $stmtDelIt->execute();
            $stmtDelIt->close();
        } else {
            $costoStr = (string)$costoTotalCompra;
            $pctStr   = (string)$porcentajeCombo;
            $cantUnidades = 1;
            $activo = 0;
            $mayor  = 0;
            $esCombo = 1;

            $stmtIns = $conexion->prepare("
                INSERT INTO productos (nombre, precio_compra, cantidad_unidades, porcentaje, origen, bss_id, activo, mayor, es_combo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtIns->bind_param("ssissiiii", $nombre, $costoStr, $cantUnidades, $pctStr, $origenDetectado, $bss_id, $activo, $mayor, $esCombo);
            if (!$stmtIns->execute()) {
                throw new Exception("No se pudo crear el combo: " . $stmtIns->error);
            }
            $combo_id = (int)$stmtIns->insert_id;
            $stmtIns->close();

            // Registrar en stock para todas las sucursales del negocio
            $stmtSucs = $conexion->prepare("SELECT id FROM sucursales WHERE bss_id = ?");
            $stmtSucs->bind_param("i", $bss_id);
            $stmtSucs->execute();
            $resSucs = $stmtSucs->get_result();

            $stmtInsStock = $conexion->prepare("INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor) VALUES (?, ?, '0', ?, ?, 0)");
            while ($sRow = $resSucs->fetch_assoc()) {
                $sid = (int)$sRow['id'];
                $stmtInsStock->bind_param("isii", $combo_id, $pctStr, $sid, $bss_id);
                $stmtInsStock->execute();
            }
            $stmtInsStock->close();
            $stmtSucs->close();
        }

        // Insertar los items del combo con su snapshot de precios en $
        $stmtInsIt = $conexion->prepare("
            INSERT INTO combo_items (combo_producto_id, producto_id, cantidad, costo_unitario_ref, venta_unitario_ref, bss_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($itemsParaInsertar as $it) {
            $stmtInsIt->bind_param(
                "iidddi",
                $combo_id,
                $it['producto_id'],
                $it['cantidad'],
                $it['costo_ref'],
                $it['venta_ref'],
                $bss_id
            );
            $stmtInsIt->execute();
        }
        $stmtInsIt->close();

        $conexion->commit();
        echo json_encode([
            'status'   => 'success',
            'message'  => 'Combo guardado correctamente.',
            'combo_id' => $combo_id
        ]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($accion === 'eliminar_combo') {
    $combo_id = (int)($input['combo_id'] ?? 0);
    if ($combo_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID de combo inválido']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("UPDATE productos SET activo = 1 WHERE id = ? AND bss_id = ? AND es_combo = 1");
        $stmt->bind_param("ii", $combo_id, $bss_id);
        $stmt->execute();
        $stmt->close();

        $stmtS = $conexion->prepare("DELETE FROM stock WHERE id_producto = ? AND bss_id = ?");
        $stmtS->bind_param("ii", $combo_id, $bss_id);
        $stmtS->execute();
        $stmtS->close();

        $stmtIt = $conexion->prepare("DELETE FROM combo_items WHERE combo_producto_id = ? AND bss_id = ?");
        $stmtIt->bind_param("ii", $combo_id, $bss_id);
        $stmtIt->execute();
        $stmtIt->close();

        $conexion->commit();
        echo json_encode(['status' => 'success', 'message' => 'Combo eliminado correctamente.']);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar el combo.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida']);
