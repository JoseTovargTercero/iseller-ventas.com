<?php
/**
 * GET  /iseller_store/api/v1/orders.php          → historial (últimas 50)
 * GET  /iseller_store/api/v1/orders.php?id=123   → detalle
 * POST /iseller_store/api/v1/orders.php          → crea el pedido
 *
 * Body POST:
 * {
 *   "slug": "yola",
 *   "items": [{"product_id": 10, "branch_id": 2, "quantity": 1}],
 *   "delivery_type": "delivery" | "retiro_tienda",
 *   "address_id": 5,
 *   "payment_reference": "123456",
 *   "payment_hour": "2026-10-24 10:30",
 *   "payment_method": 2
 * }
 *
 * Precios, stock y total se recalculan aquí. El cliente solo dice QUÉ y CUÁNTO.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';
require_once __DIR__ . '/../../core/notificaciones_admin.php';

$user = api_auth();
$userId = (int)$user['id'];
global $conexion, $conexion_store;

function api_order_summary(array $o): array
{
    return [
        'id'                => (int)$o['id'],
        'created_at'        => (string)($o['created'] ?? ''),
        'total_usd'         => (float)($o['total_price'] ?? 0),
        'total_bs'          => (float)($o['total_price_bs'] ?? 0),
        'total_cop'         => (float)($o['total_price_cop'] ?? 0),
        'delivery_type'     => (string)($o['tipo_entrega'] ?? ''),
        // El estado 6 es interno del panel; para el cliente es "transportista_asignado".
        'status'            => ((int)($o['status'] ?? 0) === 6)
            ? 'transportista_asignado'
            : (string)($o['estatus'] ?? 'pendiente'),
        'payment_reference' => (string)($o['numero_operacion_bancaria'] ?? ''),
        'store_id'          => (int)($o['negocio_id'] ?? 0),
        'store_name'        => (string)($o['store_name'] ?? ''),
        'store_slug'        => (string)($o['store_slug'] ?? ''),
        'item_count'        => (int)($o['item_count'] ?? 0),
    ];
}

// ── GET ──────────────────────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    api_rate_limit('orders_get', 120, 60);
    $id = (int)($_GET['id'] ?? 0);

    $sql = "SELECT o.id, o.created, o.total_price, o.total_price_bs, o.total_price_cop,
                   o.status, o.tipo_entrega, o.numero_operacion_bancaria, o.negocio_id, o.direccion_id,
                   COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) AS store_name, n.slug AS store_slug,
                   (SELECT c.estatus FROM tienda_compras_por_usuarios c
                     WHERE c.compra_id = o.id AND c.usuario_id = o.customer_id
                     ORDER BY c.id DESC LIMIT 1) AS estatus,
                   (SELECT COUNT(*) FROM tienda_orden_articulos a WHERE a.order_id = o.id) AS item_count
            FROM tienda_ordenes o
            LEFT JOIN negocio n ON n.id = o.negocio_id
            WHERE o.customer_id = ?";
    if ($id > 0) {
        $sql .= " AND o.id = ?";
    }
    $sql .= " ORDER BY o.id DESC LIMIT 50";

    $stmt = $conexion_store->prepare($sql);
    if (!$stmt) {
        api_error(500, 'No se pudo consultar los pedidos');
    }
    if ($id > 0) {
        $stmt->bind_param('ii', $userId, $id);
    } else {
        $stmt->bind_param('i', $userId);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $ordenes = [];
    while ($fila = $res->fetch_assoc()) {
        $ordenes[] = $fila;
    }
    $stmt->close();

    if ($id > 0) {
        if (!$ordenes) {
            api_error(404, 'Pedido no encontrado');
        }
        $orden = $ordenes[0];
        $stmt = $conexion_store->prepare(
            "SELECT a.product_id, a.quantity, a.precio_venta_dolar, a.precio_venta_bs,
                    a.precio_venta_cop, p.nombre
             FROM tienda_orden_articulos a
             LEFT JOIN productos p ON p.id = a.product_id
             WHERE a.order_id = ?"
        );
        $stmt->bind_param('i', $orden['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $items = [];
        while ($fila = $res->fetch_assoc()) {
            $items[] = [
                'product_id' => (int)$fila['product_id'],
                'name'       => (string)($fila['nombre'] ?? ''),
                'quantity'   => (float)$fila['quantity'],
                'price_usd'  => (float)$fila['precio_venta_dolar'],
                'price_bs'   => (float)$fila['precio_venta_bs'],
                'price_cop'  => (float)$fila['precio_venta_cop'],
            ];
        }
        $stmt->close();

        $direccion = null;
        if ((int)$orden['direccion_id'] > 0) {
            $stmt = $conexion_store->prepare(
                "SELECT direccion, referencia, lat, lng FROM tienda_usuarios_direcciones
                 WHERE id = ? AND usuario_id = ? LIMIT 1"
            );
            $stmt->bind_param('ii', $orden['direccion_id'], $userId);
            $stmt->execute();
            $dir = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($dir) {
                $direccion = [
                    'address'   => $dir['direccion'],
                    'reference' => $dir['referencia'],
                    'lat'       => $dir['lat'] !== null ? (float)$dir['lat'] : null,
                    'lng'       => $dir['lng'] !== null ? (float)$dir['lng'] : null,
                ];
            }
        }

        api_ok([
            'order'   => api_order_summary($orden),
            'items'   => $items,
            'address' => $direccion,
            'store'   => [
                'id'   => (int)$orden['negocio_id'],
                'name' => (string)$orden['store_name'],
                'slug' => (string)$orden['store_slug'],
            ],
        ]);
    }

    api_ok(['items' => array_map('api_order_summary', $ordenes)]);
}

// ── POST: crear pedido ───────────────────────────────────────────────────────
api_method('POST');
api_rate_limit('orders_post', 20, 300);
$in = api_body();

$tienda = api_store_by_slug(api_str($in, 'slug', 150));
$bssId = (int)$tienda['id'];

$entrega = api_str($in, 'delivery_type', 30);
if (!in_array($entrega, ['delivery', 'retiro_tienda'], true)) {
    api_error(400, 'Tipo de entrega inválido', 'invalid_delivery');
}

$addressId = (int)($in['address_id'] ?? 0);
if ($entrega === 'delivery') {
    if ($addressId <= 0) {
        api_error(400, 'Selecciona una dirección de entrega', 'address_required');
    }
    $stmt = $conexion_store->prepare(
        "SELECT id FROM tienda_usuarios_direcciones WHERE id = ? AND usuario_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $addressId, $userId);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if (!$ok) {
        api_error(400, 'Dirección de entrega inválida', 'invalid_address');
    }
} else {
    $addressId = 0;
}

$referencia = api_str($in, 'payment_reference', 100);
if ($referencia === '') {
    api_error(400, 'Falta la referencia del pago', 'payment_required');
}
$hora = api_str($in, 'payment_hour', 50);
if ($hora === '') {
    $hora = date('Y-m-d H:i:s');
}
$metodoPago = (int)($in['payment_method'] ?? 2);

$itemsIn = $in['items'] ?? null;
if (!is_array($itemsIn) || count($itemsIn) < 1 || count($itemsIn) > 50) {
    api_error(400, 'El carrito está vacío o es demasiado grande', 'invalid_cart');
}

$calc = api_calc($bssId);
$stmtProd = $conexion->prepare(
    "SELECT p.id, p.nombre, p.precio_compra, p.cantidad_unidades, p.origen, p.mayor,
            s.stock, s.porcentaje, s.bss_id, s.id_sucursal
     FROM productos p
     INNER JOIN negocio_productos np ON p.id = np.producto_id AND np.estado_publicacion = 1
     INNER JOIN negocio n ON np.negocio_id = n.id
     INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id
          AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
     WHERE p.id = ? AND n.id = ? AND s.id_sucursal = ?
       AND p.activo = 0 AND p.origen != 'c'
       AND n.tiene_tienda_virtual = 1 AND n.estado = 'activo'
     LIMIT 1"
);
if (!$stmtProd) {
    api_error(500, 'No se pudo validar el carrito');
}

$items = [];
$totalUsd = 0.0;
$totalBs = 0.0;
$totalCop = 0.0;

foreach ($itemsIn as $item) {
    if (!is_array($item)) {
        api_error(400, 'Producto inválido', 'invalid_item');
    }
    $pid = (int)($item['product_id'] ?? 0);
    $sid = (int)($item['branch_id'] ?? 0);
    $qty = (float)($item['quantity'] ?? 0);
    $clave = $pid . '-' . $sid;
    if ($pid <= 0 || $sid <= 0 || !is_finite($qty) || $qty <= 0 || $qty > 999) {
        api_error(400, 'Producto o cantidad inválida', 'invalid_item');
    }
    if (isset($items[$clave])) {
        api_error(400, 'Producto duplicado en el carrito', 'duplicate_item');
    }

    $stmtProd->bind_param('iii', $pid, $bssId, $sid);
    $stmtProd->execute();
    $prod = $stmtProd->get_result()->fetch_assoc();
    if (!$prod) {
        api_error(409, 'Uno de los productos ya no está disponible', 'product_unavailable');
    }
    if ((float)$prod['stock'] < $qty) {
        $stmtProd->close();
        api_error(409, 'No hay stock suficiente de ' . $prod['nombre'], 'out_of_stock');
    }

    $precios = $calc->calcularPrecios($prod);
    $items[$clave] = [
        'id'        => $pid,
        'qty'       => $qty,
        'branch'    => $sid,
        'price_usd' => (float)$precios['precio_venta_dolar'],
        'price_bs'  => (float)$precios['precio_venta_bs'],
        'price_cop' => (float)$precios['precio_venta_peso'],
    ];
    $totalUsd += api_trunc2((float)$precios['precio_venta_dolar'] * $qty);
    $totalBs  += api_trunc2((float)$precios['precio_venta_bs'] * $qty);
    $totalCop += api_trunc2((float)$precios['precio_venta_peso'] * $qty);
}
$stmtProd->close();
$items = array_values($items);

// Envío: mismas reglas que el checkout web.
$envioUsd = 0.0;
$envioBs = 0.0;
if ($entrega !== 'retiro_tienda') {
    $config = json_decode((string)($tienda['config_delivery'] ?? '{}'), true) ?: [];
    $tipo = (string)($config['tipo'] ?? '');
    $minimo = (float)($config['minimo'] ?? 0);
    $cobra = $tipo === 'pago' || ($tipo === 'gratis' && $minimo > 0 && $totalUsd < $minimo);
    if ($cobra) {
        $envioUsd = api_trunc2((float)($config['costo'] ?? 0));
        $envioBs = api_trunc2($envioUsd * $calc->getTasa('bcv'));
        $totalUsd += $envioUsd;
        $totalBs += $envioBs;
        $totalCop += api_trunc2($envioUsd * $calc->getTasa('dolarPeso'));
    }
}

$totalUsd = api_trunc2($totalUsd);
$totalBs = api_trunc2($totalBs);
$totalCop = api_trunc2($totalCop);
$creado = date('Y-m-d H:i:s');
$idSucursal = (int)$items[0]['branch'];
$cliente = htmlspecialchars((string)($user['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');

$conexion_store->begin_transaction();
try {
    $stmt = $conexion_store->prepare(
        "SELECT id FROM tienda_ordenes WHERE bss_id = ? AND numero_operacion_bancaria = ? LIMIT 1 FOR UPDATE"
    );
    if (!$stmt) {
        throw new RuntimeException('dup_check');
    }
    $stmt->bind_param('is', $bssId, $referencia);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        throw new DomainException('payment_duplicate');
    }
    $stmt->close();

    $stmt = $conexion_store->prepare(
        "UPDATE stock SET stock = stock - ?
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? AND stock >= ?"
    );
    if (!$stmt) {
        throw new RuntimeException('stock_prepare');
    }
    foreach ($items as $it) {
        $stmt->bind_param('diiid', $it['qty'], $it['id'], $it['branch'], $bssId, $it['qty']);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new DomainException('out_of_stock');
        }
    }
    $stmt->close();

    $stmt = $conexion_store->prepare(
        "INSERT INTO tienda_ordenes
           (negocio_id, status, customer_id, total_price, created, modified, fecha, semana, ano,
            total_price_bs, total_price_cop, cliente, tipoPago, numero_operacion_bancaria,
            hora_operacion_bancaria, tipo_entrega, dia, descontado, id_sucursal, bss_id, direccion_id)
         VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Si', ?, ?, ?)"
    );
    if (!$stmt) {
        throw new RuntimeException('order_prepare');
    }
    $fecha = date('m-Y');
    $semana = date('Y-W');
    $ano = date('Y');
    $dia = date('d');
    $totUsd = (string)$totalUsd;
    $totBs = (string)$totalBs;
    $totCop = (string)$totalCop;
    $stmt->bind_param(
        'iisssssssssissssiii',
        $bssId, $userId, $totUsd, $creado, $creado, $fecha, $semana, $ano,
        $totBs, $totCop, $cliente, $metodoPago, $referencia, $hora, $entrega, $dia,
        $idSucursal, $bssId, $addressId
    );
    if (!$stmt->execute()) {
        $errno = $conexion_store->errno;
        $stmt->close();
        if ($errno === 1062) {
            throw new DomainException('payment_duplicate');
        }
        throw new RuntimeException('order_insert');
    }
    $ordenId = (int)$stmt->insert_id;
    $stmt->close();

    $stmt = $conexion_store->prepare(
        "INSERT INTO tienda_compras_por_usuarios
           (usuario_id, compra_id, direccion_id, valor_compra, valor_compra_bs,
            numero_operacion_bancaria, hora_operacion_bancaria, ganancia_generada,
            fecha, tipo_entrega, importe_envio, importe_envio_bs, estatus,
            puntos_generados, nivel_resultante, ahorrado, ahorrado_bs, estado)
         VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 'pendiente', 0, 0, 0, 0, 'pendiente')"
    );
    if (!$stmt) {
        throw new RuntimeException('history_prepare');
    }
    $addrParam = $addressId ?: null;
    $stmt->bind_param(
        'iiiddssssdd',
        $userId, $ordenId, $addrParam, $totalUsd, $totalBs,
        $referencia, $hora, $creado, $entrega, $envioUsd, $envioBs
    );
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('history_insert');
    }
    $stmt->close();

    $stmt = $conexion_store->prepare(
        "INSERT INTO tienda_orden_articulos
           (order_id, product_id, quantity, precio, bolivar, peso,
            precio_venta_dolar, precio_venta_bs, precio_venta_cop, id_sucursal, bss_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        throw new RuntimeException('items_prepare');
    }
    foreach ($items as $it) {
        $qtyStr = (string)$it['qty'];
        $usd = (string)$it['price_usd'];
        $bs = (string)$it['price_bs'];
        $cop = (string)$it['price_cop'];
        $stmt->bind_param(
            'iisssssssii',
            $ordenId, $it['id'], $qtyStr, $usd, $bs, $cop, $usd, $bs, $cop,
            $it['branch'], $bssId
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('items_insert');
        }
    }
    $stmt->close();

    $conexion_store->commit();
} catch (DomainException $e) {
    $conexion_store->rollback();
    if ($e->getMessage() === 'payment_duplicate') {
        api_error(409, 'Esa referencia de pago ya fue usada en otro pedido', 'payment_duplicate');
    }
    api_error(409, 'No hay stock suficiente para completar el pedido', 'out_of_stock');
} catch (Throwable $e) {
    $conexion_store->rollback();
    error_log('API orders: ' . $e->getMessage());
    api_error(500, 'No se pudo guardar el pedido');
}

// Efectos posteriores: chat de compra + aviso al comercio (in-app y push).
try {
    require_once __DIR__ . '/../../core/chat_compra.php';
    chat_compra_crear_conversacion($conexion_store, $userId, $bssId, $ordenId);
} catch (Throwable $e) {
    error_log('API orders chat: ' . $e->getMessage());
}
try {
    notificar_admin_tienda(
        $conexion,
        $bssId,
        "Nueva compra #$ordenId",
        'Cliente: ' . $cliente . ' | Total: $' . number_format($totalUsd, 2) .
            ' | ' . ($entrega === 'delivery' ? 'Delivery' : 'Retiro en Tienda'),
        'compra'
    );
} catch (Throwable $e) {
    error_log('API orders notify: ' . $e->getMessage());
}

api_ok([
    'order_id'     => $ordenId,
    'total_usd'    => $totalUsd,
    'total_bs'     => $totalBs,
    'total_cop'    => $totalCop,
    'delivery_usd' => $envioUsd,
    'status'       => 'pendiente',
], ['message' => 'Pedido creado']);
