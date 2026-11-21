<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_canal.php');
require_once('_stock_traslados.php');
require_once('_stock_cargamentos.php');
require_once('_stock_auditoria.php');

header('Content-Type: application/json; charset=utf-8');

function mt_responder(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$contenido = file_get_contents('php://input');
$json = json_decode($contenido, true);
$input = is_array($json) ? $json : $_POST;

$bssId          = (int) ($_SESSION['bss_id'] ?? 0);
$usuarioId      = (int) ($_SESSION['id'] ?? 0);
$usuarioNombre  = (string) ($_SESSION['nombre'] ?? '');
$nivel          = (int) ($_SESSION['nivel'] ?? 0);

if ($bssId <= 0 || $usuarioId <= 0 || ($nivel !== 1 && $nivel !== 2)) {
    mt_responder(['success' => false, 'message' => 'Sesión no válida.'], 401);
}

$sucursal  = $nivel === 2 ? (int) ($_SESSION['sucursal'] ?? 0) : (int) ($input['sucursal'] ?? 0);
$sucursal2 = (int) ($input['sucursal_2'] ?? 0);
$motivo    = trim(strip_tags((string) ($input['motivo'] ?? '')));

if (function_exists('mb_substr')) {
    $motivo = mb_substr($motivo, 0, 1000, 'UTF-8');
} else {
    $motivo = substr($motivo, 0, 1000);
}

if ($sucursal <= 0 || $sucursal2 <= 0) {
    mt_responder(['success' => false, 'message' => 'Seleccione la sucursal de origen y destino.'], 400);
}
if ($sucursal === $sucursal2) {
    mt_responder(['success' => false, 'message' => 'La sucursal de origen y destino no pueden ser iguales.'], 400);
}
if ($motivo === '') {
    mt_responder(['success' => false, 'message' => 'Indique el motivo del cargamento.'], 400);
}

foreach ([$sucursal, $sucursal2] as $sucursalId) {
    $stmt = $conexion->prepare('SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1');
    if (!$stmt) {
        mt_responder(['success' => false, 'message' => 'Error de base de datos.'], 500);
    }
    $stmt->bind_param('ii', $sucursalId, $bssId);
    $stmt->execute();
    $existe = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$existe) {
        mt_responder(['success' => false, 'message' => 'La sucursal no pertenece al negocio.'], 400);
    }
}

$itemsInput = $input['items'] ?? null;
if (!is_array($itemsInput) || count($itemsInput) === 0) {
    // Compatibilidad con el envío anterior de un solo producto.
    $productoLegacy = (int) ($input['producto'] ?? 0);
    $cantidadLegacy = (float) str_replace(',', '.', (string) ($input['unidades_transferir'] ?? '0'));
    if ($productoLegacy > 0 && $cantidadLegacy > 0) {
        $itemsInput = [['producto' => $productoLegacy, 'cantidad' => $cantidadLegacy]];
    }
}

if (!is_array($itemsInput) || count($itemsInput) === 0) {
    mt_responder(['success' => false, 'message' => 'Agregue al menos un artículo al cargamento.'], 400);
}
if (count($itemsInput) > 200) {
    mt_responder(['success' => false, 'message' => 'El cargamento supera el máximo de artículos permitido.'], 400);
}

// Agrupa por producto para que un artículo repetido no genere movimientos
// duplicados dentro del mismo cargamento.
$itemsPorProducto = [];
foreach ($itemsInput as $item) {
    $productoId = (int) ($item['producto'] ?? $item['producto_id'] ?? 0);
    $cantidad = (float) str_replace(',', '.', (string) ($item['cantidad'] ?? $item['unidades_transferir'] ?? 0));
    if ($productoId <= 0 || !is_finite($cantidad) || $cantidad <= 0) {
        mt_responder(['success' => false, 'message' => 'Hay un artículo con producto o cantidad inválida.'], 400);
    }
    if (!isset($itemsPorProducto[$productoId])) {
        $itemsPorProducto[$productoId] = ['producto' => $productoId, 'cantidad' => 0.0];
    }
    $itemsPorProducto[$productoId]['cantidad'] += $cantidad;
}
$items = array_values($itemsPorProducto);

$conciliaciones = is_array($input['conciliaciones'] ?? null) ? $input['conciliaciones'] : [];
$productos = [];

foreach ($items as &$item) {
    $productoId = (int) $item['producto'];

    $stmt = $conexion->prepare(
        'SELECT id, nombre, modo_control, porcentaje FROM productos WHERE id = ? AND bss_id = ? LIMIT 1'
    );
    if (!$stmt) {
        mt_responder(['success' => false, 'message' => 'Error de base de datos.'], 500);
    }
    $stmt->bind_param('ii', $productoId, $bssId);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$producto) {
        mt_responder(['success' => false, 'message' => 'Uno de los productos no existe en el catálogo.'], 400);
    }
    if ((string) ($producto['modo_control'] ?? 'NORMAL') === 'CANAL') {
        mt_responder([
            'success' => false,
            'message' => 'El producto "' . $producto['nombre'] . '" es una res en canal. Transfiera la res completa desde Ajustes de stock.'
        ], 400);
    }

    $itemsStmt = $conexion->prepare(
        'SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1'
    );
    if (!$itemsStmt) {
        mt_responder(['success' => false, 'message' => 'Error de base de datos.'], 500);
    }
    $itemsStmt->bind_param('iii', $productoId, $sucursal, $bssId);
    $itemsStmt->execute();
    $stockOrigenFila = $itemsStmt->get_result()->fetch_assoc();
    $itemsStmt->close();

    if (!$stockOrigenFila) {
        mt_responder([
            'success' => false,
            'message' => 'El producto "' . $producto['nombre'] . '" no existe en la sucursal de origen.'
        ], 400);
    }
    if ((float) $stockOrigenFila['stock'] + 0.0001 < (float) $item['cantidad']) {
        mt_responder([
            'success' => false,
            'message' => 'No hay stock suficiente para "' . $producto['nombre'] . '". Disponible: ' . canal_numero((float) $stockOrigenFila['stock']) . '.'
        ], 400);
    }

    $productos[$productoId] = $producto;
    $item['nombre'] = (string) $producto['nombre'];
    $item['porcentaje'] = (string) ($producto['porcentaje'] ?? '0');
    $item['estricto'] = canal_producto_estricto($conexion, $productoId, $bssId);
}
unset($item);

// Antes de tocar stock se piden las confirmaciones de los productos estrictos.
$pendientes = [];
foreach ($items as $item) {
    if (!$item['estricto']) {
        continue;
    }
    try {
        $contexto = canal_contexto_estricto($conexion, (int) $item['producto'], $sucursal2, $bssId);
    } catch (Throwable $e) {
        $contexto = null;
    }
    if (!$contexto) {
        continue;
    }

    $productoId = (int) $item['producto'];
    $conc = $conciliaciones[$productoId] ?? $conciliaciones[(string) $productoId] ?? null;
    $stockVisto = $conc ? (float) ($conc['stock_visto'] ?? -1) : -1;
    $confirmado = $conc && !empty($conc['confirmado']);
    if (!$confirmado || abs($stockVisto - (float) $contexto['stock_actual']) > 0.0001) {
        $pendientes[$productoId] = $contexto;
    }
}

if ($pendientes) {
    mt_responder([
        'success' => false,
        'requiere_conciliacion' => true,
        'message' => 'Confirme la cantidad anterior de los productos con seguimiento estricto.',
        'productos' => array_values($pendientes),
    ]);
}

// Las tablas de historial se aseguran fuera de la transacción.
if (!st_asegurar_tabla($conexion) || !sc_asegurar_tabla($conexion) || !sa_asegurar_tabla($conexion)) {
    mt_responder(['success' => false, 'message' => 'No se pudo preparar el historial de traslados y auditoría.'], 500);
}

$totalUnidades = 0.0;
foreach ($items as $item) {
    $totalUnidades += (float) $item['cantidad'];
}

$conexion->begin_transaction();

try {
    $cargamentoId = sc_crear($conexion, [
        'bss_id'          => $bssId,
        'sucursal_origen' => $sucursal,
        'sucursal_destino' => $sucursal2,
        'motivo'          => $motivo,
        'usuario_id'      => $usuarioId,
        'usuario_nombre'  => $usuarioNombre,
        'total_productos' => count($items),
        'total_unidades'  => $totalUnidades,
    ]);
    if ($cargamentoId <= 0) {
        throw new Exception('No se pudo crear el registro del cargamento.');
    }

    foreach ($items as $item) {
        $productoId = (int) $item['producto'];
        $cantidad = (float) $item['cantidad'];
        $nombreProducto = (string) $item['nombre'];

        // Stock de origen bloqueado para que la cantidad anterior sea exacta.
        $stmt = $conexion->prepare(
            'SELECT id, stock FROM stock
             WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
             ORDER BY id ASC LIMIT 1 FOR UPDATE'
        );
        if (!$stmt) {
            throw new Exception('No se pudo consultar el stock de origen.');
        }
        $stmt->bind_param('iii', $productoId, $sucursal, $bssId);
        $stmt->execute();
        $filaOrigen = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$filaOrigen) {
            throw new Exception('El producto "' . $nombreProducto . '" ya no tiene stock en la sucursal de origen.');
        }

        $stockOrigenAntes = (float) $filaOrigen['stock'];
        if ($stockOrigenAntes + 0.0001 < $cantidad) {
            throw new Exception('No hay stock suficiente para "' . $nombreProducto . '". Disponible: ' . canal_numero($stockOrigenAntes) . '.');
        }
        $stockOrigenDespues = max(0, $stockOrigenAntes - $cantidad);

        // Stock de destino bloqueado; si no existe se crea en cero.
        $stmt = $conexion->prepare(
            'SELECT id, stock FROM stock
             WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
             ORDER BY id ASC LIMIT 1 FOR UPDATE'
        );
        if (!$stmt) {
            throw new Exception('No se pudo consultar el stock de destino.');
        }
        $stmt->bind_param('iii', $productoId, $sucursal2, $bssId);
        $stmt->execute();
        $filaDestino = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($filaDestino) {
            $stockDestinoId = (int) $filaDestino['id'];
            $stockDestinoAntes = (float) $filaDestino['stock'];
        } else {
            $porcentaje = (string) ($item['porcentaje'] ?? '0');
            $stmt = $conexion->prepare(
                "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id)
                 VALUES (?, ?, '0', ?, ?)"
            );
            if (!$stmt) {
                throw new Exception('No se pudo crear el stock en la sucursal destino.');
            }
            $stmt->bind_param('isii', $productoId, $porcentaje, $sucursal2, $bssId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('No se pudo crear el stock en la sucursal destino.');
            }
            $stockDestinoId = (int) $stmt->insert_id;
            $stmt->close();
            $stockDestinoAntes = 0.0;
        }
        $stockDestinoDespues = $stockDestinoAntes + $cantidad;

        // Verificación final para productos estrictos: el stock de destino
        // no debió cambiar desde que el usuario confirmó.
        if (!empty($item['estricto'])) {
            $conc = $conciliaciones[$productoId] ?? $conciliaciones[(string) $productoId] ?? null;
            $stockVisto = $conc ? (float) ($conc['stock_visto'] ?? -1) : -1;
            if (!$conc || empty($conc['confirmado']) || abs($stockVisto - $stockDestinoAntes) > 0.0001) {
                $conexion->rollback();
                try {
                    $contexto = canal_contexto_estricto($conexion, $productoId, $sucursal2, $bssId);
                } catch (Throwable $e) {
                    $contexto = null;
                }
                mt_responder([
                    'success' => false,
                    'requiere_conciliacion' => true,
                    'message' => 'El stock cambió desde que se mostró. Confirme de nuevo.',
                    'productos' => $contexto ? [$contexto] : [],
                ]);
            }
        }

        $stmt = $conexion->prepare('UPDATE stock SET stock = ? WHERE id = ?');
        if (!$stmt) {
            throw new Exception('No se pudo actualizar el stock de destino.');
        }
        $stockDestinoTexto = canal_numero($stockDestinoDespues);
        $stmt->bind_param('si', $stockDestinoTexto, $stockDestinoId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo actualizar el stock de destino.');
        }
        $stmt->close();

        $stmt = $conexion->prepare('UPDATE stock SET stock = ? WHERE id = ?');
        if (!$stmt) {
            throw new Exception('No se pudo actualizar el stock de origen.');
        }
        $stockOrigenTexto = canal_numero($stockOrigenDespues);
        $stockOrigenId = (int) $filaOrigen['id'];
        $stmt->bind_param('si', $stockOrigenTexto, $stockOrigenId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo actualizar el stock de origen.');
        }
        $stmt->close();

        $stmt = $conexion->prepare(
            "INSERT INTO stock_traslados
                (cargamento_id, bss_id, id_producto, producto_nombre, cantidad,
                 sucursal_origen, sucursal_destino, usuario_id, usuario_nombre)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) {
            throw new Exception('No se pudo registrar el artículo del cargamento.');
        }
        $stmt->bind_param(
            'iiisdiiis',
            $cargamentoId,
            $bssId,
            $productoId,
            $nombreProducto,
            $cantidad,
            $sucursal,
            $sucursal2,
            $usuarioId,
            $usuarioNombre
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo registrar el artículo del cargamento.');
        }
        $trasladoId = (int) $conexion->insert_id;
        $stmt->close();

        $detalle = [
            'cargamento_id'    => $cargamentoId,
            'motivo'           => $motivo,
            'stock_traslado_id' => $trasladoId,
            'sucursal_origen'  => $sucursal,
            'sucursal_destino' => $sucursal2,
        ];

        $auditoriaSalida = sa_registrar($conexion, [
            'bss_id'              => $bssId,
            'id_sucursal'         => $sucursal,
            'id_producto'         => $productoId,
            'producto_nombre'     => $nombreProducto,
            'tipo_movimiento'     => 'TRANSFERENCIA_SALIDA',
            'origen'              => 'cargamento',
            'referencia_tipo'     => 'cargamento',
            'referencia_id'       => $cargamentoId,
            'cantidad'            => $cantidad,
            'stock_anterior'      => $stockOrigenAntes,
            'stock_nuevo'         => $stockOrigenDespues,
            'diferencia'          => $stockOrigenDespues - $stockOrigenAntes,
            'id_sucursal_origen'  => $sucursal,
            'id_sucursal_destino' => $sucursal2,
            'detalle'             => $detalle,
            'usuario_id'          => $usuarioId,
            'usuario_nombre'      => $usuarioNombre,
        ]);

        $auditoriaEntrada = sa_registrar($conexion, [
            'bss_id'              => $bssId,
            'id_sucursal'         => $sucursal2,
            'id_producto'         => $productoId,
            'producto_nombre'     => $nombreProducto,
            'tipo_movimiento'     => 'TRANSFERENCIA_ENTRADA',
            'origen'              => 'cargamento',
            'referencia_tipo'     => 'cargamento',
            'referencia_id'       => $cargamentoId,
            'cantidad'            => $cantidad,
            'stock_anterior'      => $stockDestinoAntes,
            'stock_nuevo'         => $stockDestinoDespues,
            'diferencia'          => $stockDestinoDespues - $stockDestinoAntes,
            'id_sucursal_origen'  => $sucursal,
            'id_sucursal_destino' => $sucursal2,
            'detalle'             => $detalle,
            'usuario_id'          => $usuarioId,
            'usuario_nombre'      => $usuarioNombre,
        ]);

        if (!$auditoriaSalida || !$auditoriaEntrada) {
            throw new Exception('No se pudo registrar la auditoría del cargamento.');
        }
    }

    $conexion->commit();

    mt_responder([
        'success' => true,
        'message' => 'Cargamento trasladado correctamente. Artículos: ' . count($items) . '.',
        'cargamento_id' => $cargamentoId,
        'total_productos' => count($items),
        'total_unidades' => $totalUnidades,
    ]);
} catch (Throwable $e) {
    $conexion->rollback();
    mt_responder(['success' => false, 'message' => $e->getMessage()], 400);
}
