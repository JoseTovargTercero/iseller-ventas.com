<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');
require_once('../../../iseller_store/core/estados_orden.php');
require_once(__DIR__ . '/../includes/push_fcm.php');
require_once(__DIR__ . '/transportista_negocio.php');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin sesión']);
    exit;
}

$bss_id = $_SESSION['bss_id'] ?? null;
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// La tabla de planes es utf8mb4; con la conexión en utf8 (3 bytes) un nombre
// con emoji falla en hosting con sql_mode estricto.
$conexion->set_charset('utf8mb4');
if (in_array($action, ['cambiar_estado', 'cambiar_transportista', 'notificar_transportista'], true)) {
    push_asegurar_tabla($conexion);
}

header('Content-Type: application/json');

if ($action === 'listar') {
    $sql = "SELECT o.id, o.status, o.total_price, o.total_price_bs, o.total_price_cop,
                   o.created, o.cliente, o.negocio_id, o.bss_id,
                   o.tipoPago, o.tipo_entrega, o.numero_operacion_bancaria,
                   o.hora_operacion_bancaria, o.direccion_id,
                   u.nombre AS cliente_nombre, u.telefono AS cliente_telefono, u.email AS cliente_email
            FROM tienda_ordenes o
            LEFT JOIN tienda_usuarios u ON o.customer_id = u.id
            WHERE o.bss_id = ?
            ORDER BY o.id DESC";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $bss_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['ok' => true, 'bss_id' => $bss_id, 'count' => count($rows), 'data' => $rows]);
    exit;
}

/* ─── 1b. Transportistas disponibles para asignar un delivery ─────────── */
if ($action === 'listar_transportistas') {
    // Solo los que el negocio agregó en "Mis transportistas" y pueden hacer deliveries.
    $data = array_values(array_filter(
        transportista_negocio_listar($conexion, (int)$bss_id),
        function ($t) {
            return (int)$t['activo'] === 1 && (int)$t['deliverys'] === 1;
        }
    ));
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

/* ─── 2. Detalle de Pedido ────────────────────────────────────────────── */
if ($action === 'detalle') {
    $id = (int)($_GET['id'] ?? 0);
    if(!$id) { echo json_encode(['ok'=>false, 'msg'=>'ID inválido']); exit; }

    // Info de la orden + el envío realmente cobrado (historial del cliente)
    $sql = "SELECT o.*, u.nombre AS cliente_nombre, u.telefono AS cliente_telefono, u.email AS cliente_email,
                   s.nombre AS sucursal_nombre,
                   d.direccion, d.referencia, d.lat, d.lng, d.comunidad,
                   (SELECT c.importe_envio FROM tienda_compras_por_usuarios c WHERE c.compra_id = o.id LIMIT 1) AS importe_envio
            FROM tienda_ordenes o
            LEFT JOIN tienda_usuarios u ON o.customer_id = u.id
            LEFT JOIN sucursales s ON o.id_sucursal = s.id
            LEFT JOIN tienda_usuarios_direcciones d ON o.direccion_id = d.id AND d.usuario_id = o.customer_id
            WHERE o.id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $orden = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$orden || (int)$orden['bss_id'] !== (int)$bss_id) {
        echo json_encode(['ok'=>false, 'msg'=>'Orden no encontrada']); exit;
    }

    // Transportista asignado (si es un delivery ya enviado)
    $tpActual = transportista_servicio_actual($conexion, (int)$bss_id, $id);
    $orden['transportista_id'] = $tpActual['transportista_id'] ?? null;
    $orden['transportista_nombre'] = $tpActual['nombre'] ?? null;
    $orden['transportista_estado'] = $tpActual['estado'] ?? null;

    // Artículos de la orden
    $sqlArts = "SELECT a.*, p.nombre AS producto_nombre, s.nombre AS sucursal_nombre
                FROM tienda_orden_articulos a
                LEFT JOIN productos p ON a.product_id = p.id
                LEFT JOIN sucursales s ON a.id_sucursal = s.id
                WHERE a.order_id = ?";
    $stmtA = $conexion->prepare($sqlArts);
    $stmtA->bind_param("i", $id);
    $stmtA->execute();
    $articulos = $stmtA->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtA->close();

    // Config delivery del negocio
    $sqlDel = "SELECT config_delivery FROM negocio WHERE id = ?";
    $stmtD = $conexion->prepare($sqlDel);
    $stmtD->bind_param("i", $orden['bss_id']);
    $stmtD->execute();
    $rowDel = $stmtD->get_result()->fetch_assoc();
    $stmtD->close();
    $config_delivery = json_decode($rowDel['config_delivery'] ?? '{}', true);

    echo json_encode(['ok' => true, 'orden' => $orden, 'articulos' => $articulos, 'config_delivery' => $config_delivery]);
    exit;
}

/* ─── 3. Cambiar Estado ──────────────────────────────────────────────── */
if ($action === 'cambiar_estado') {
    $id = (int)($_POST['id'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    $transportistaId = (int)($_POST['transportista_id'] ?? 0);

    if (!$id || !$status) { echo json_encode(['ok'=>false, 'msg'=>'Datos inválidos']); exit; }

    // Antes de la transacción: CREATE TABLE provoca commit implícito.
    transportista_negocio_asegurar($conexion);

    $estatus_map     = [1 => 'pendiente', 2 => 'en_revision', 3 => 'enviada', 4 => 'entregada', 5 => 'rechazada'];
    $estatus_cliente = $estatus_map[$status] ?? 'pendiente';
    $statusLabels    = [1 => 'Pendiente', 2 => 'En preparación', 3 => 'Enviado', 4 => 'Entregado', 5 => 'Rechazado'];
    $label           = $statusLabels[$status] ?? "Estatus #$status";

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conexion->begin_transaction();
    try {
        // Bloquea la orden: dos clics/requests simultáneos no pueden repetir la transición.
        $stmt = $conexion->prepare("SELECT status, descontado, customer_id, cliente, tipo_entrega, direccion_id FROM tienda_ordenes WHERE id = ? AND bss_id = ? FOR UPDATE");
        $stmt->bind_param("ii", $id, $bss_id);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$orden) {
            $conexion->rollback();
            echo json_encode(['ok' => false, 'msg' => 'Orden no encontrada']);
            exit;
        }

        // Solo avanzar al siguiente estado (o rechazar desde cualquier punto)
        $actual = (int)$orden['status'];
        $permitido = ($actual < 4 && $status === $actual + 1) || ($status === 5 && $actual !== 5);
        if (!$permitido) {
            $conexion->rollback();
            echo json_encode(['ok' => false, 'msg' => 'Transición no válida: solo se puede avanzar al siguiente estado']);
            exit;
        }

        // Al pasar a "Enviado" un delivery hay que asignarle un transportista.
        $transportistaAsignado = null;
        if ($status === 3 && (string)$orden['tipo_entrega'] === 'delivery') {
            if ($transportistaId <= 0) {
                $conexion->rollback();
                echo json_encode(['ok' => false, 'msg' => 'Selecciona un transportista para enviar el delivery']);
                exit;
            }
            $stmtT = $conexion->prepare(
                "SELECT t.id, t.nombre
                   FROM transportista_por_negocio r
                   INNER JOIN transportistas t ON t.id = r.transportista_id
                  WHERE r.bss_id = ? AND t.id = ? AND t.activo = 1 AND t.deliverys = 1
                  LIMIT 1"
            );
            $stmtT->bind_param("ii", $bss_id, $transportistaId);
            $stmtT->execute();
            $transportistaAsignado = $stmtT->get_result()->fetch_assoc();
            $stmtT->close();
            if (!$transportistaAsignado) {
                $conexion->rollback();
                echo json_encode(['ok' => false, 'msg' => 'Transportista no válido o inactivo']);
                exit;
            }
        }

        // 1. Actualizar estatus de la orden
        $stmt = $conexion->prepare("UPDATE tienda_ordenes SET status = ? WHERE id = ? AND bss_id = ?");
        $stmt->bind_param("iii", $status, $id, $bss_id);
        $stmt->execute();
        $stmt->close();

        // 1b. Historial: hora exacta del cambio de estado (misma transacción)
        registrar_estado_orden($conexion, $id, (int)$bss_id, $status, 'panel');

        // 2. Replicar estatus al historial del cliente
        $stmt = $conexion->prepare("UPDATE tienda_compras_por_usuarios SET estatus = ?, estado = ? WHERE compra_id = ?");
        $stmt->bind_param("ssi", $estatus_cliente, $estatus_cliente, $id);
        $stmt->execute();
        $stmt->close();

        // 3. Stock: desde checkout.php ya se reserva al crear la orden
        //    (descontado='Si'). Órdenes viejas ('No') se descuentan al enviar.
        //    Al rechazar se devuelve lo reservado y queda 'R'.
        $descontado = (string)($orden['descontado'] ?? 'No');

        if ($status === 5) {
            if ($descontado === 'Si') {
                $stmt = $conexion->prepare("SELECT product_id, quantity, id_sucursal, bss_id FROM tienda_orden_articulos WHERE order_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $arts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                $stmtUpd = $conexion->prepare("UPDATE stock SET stock = stock + ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
                foreach ($arts as $a) {
                    $qty = (float)$a['quantity'];
                    $pid = (int)$a['product_id'];
                    $sid = (int)$a['id_sucursal'];
                    $sbss = (int)$a['bss_id'];
                    $stmtUpd->bind_param("diii", $qty, $pid, $sid, $sbss);
                    $stmtUpd->execute();
                }
                $stmtUpd->close();
            }
            $stmt = $conexion->prepare("UPDATE tienda_ordenes SET descontado = 'R' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($status === 3 && $descontado !== 'Si' && $descontado !== 'R') {
            // Compatibilidad con órdenes creadas antes de reservar en el checkout.
            $stmt = $conexion->prepare("SELECT product_id, quantity, id_sucursal, bss_id FROM tienda_orden_articulos WHERE order_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $arts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $stmtUpd = $conexion->prepare("UPDATE stock SET stock = GREATEST(stock - ?, 0) WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
            foreach ($arts as $a) {
                $qty = (int)$a['quantity'];
                $pid = (int)$a['product_id'];
                $sid = (int)$a['id_sucursal'];
                $sbss = (int)$a['bss_id'];
                $stmtUpd->bind_param("iiii", $qty, $pid, $sid, $sbss);
                $stmtUpd->execute();
            }
            $stmtUpd->close();

            $stmt = $conexion->prepare("UPDATE tienda_ordenes SET descontado = 'Si' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // 4. Asignación al transportista (solo delivery al pasar a Enviado)
        if ($transportistaAsignado) {
            transportista_asignar_delivery($conexion, (int)$bss_id, $id, $transportistaId);
        }

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        error_log('tienda_pedidos_api cambiar_estado: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'No se pudo actualizar el pedido']);
        exit;
    }

    // 4. Notificación in-app al cliente (fuera de la transacción)
    if ((int)$orden['customer_id'] > 0) {
        // Ruta relativa al archivo: vale igual en local y en el hosting.
        $notifFile = __DIR__ . '/../../../iseller_store/api/chat/crear_notificacion_cliente.php';
        if (file_exists($notifFile)) {
            require_once($notifFile);
            notificar_cliente(
                $conexion,
                (int)$orden['customer_id'],
                "Pedido #$id actualizado",
                "Tu pedido ahora está: $label",
                'compra',
                'perfil.php?tab=orders'
            );
        }
    }

    // 5. Push al transportista con el delivery recién asignado
    $pushTransportista = null;
    if ($transportistaAsignado) {
        $pushTransportista = transportista_notificar_asignacion($conexion, (int)$bss_id, $id, $transportistaId);
    }

    echo json_encode(['ok' => true, 'push' => $pushTransportista]);
    exit;
}

/* ─── 3b. Cambiar transportista de un delivery ya enviado ─────────────── */
if ($action === 'cambiar_transportista') {
    $id = (int)($_POST['id'] ?? 0);
    $transportistaId = (int)($_POST['transportista_id'] ?? 0);
    if (!$id || !$transportistaId) {
        echo json_encode(['ok' => false, 'msg' => 'Datos inválidos']);
        exit;
    }

    $stmt = $conexion->prepare("SELECT status, tipo_entrega FROM tienda_ordenes WHERE id = ? AND bss_id = ? LIMIT 1");
    $stmt->bind_param("ii", $id, $bss_id);
    $stmt->execute();
    $orden = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$orden) {
        echo json_encode(['ok' => false, 'msg' => 'Orden no encontrada']);
        exit;
    }
    if ((string)$orden['tipo_entrega'] !== 'delivery') {
        echo json_encode(['ok' => false, 'msg' => 'Solo los pedidos delivery tienen transportista']);
        exit;
    }
    if ((int)$orden['status'] !== 3) {
        echo json_encode(['ok' => false, 'msg' => 'Solo se puede cambiar el transportista de un pedido enviado']);
        exit;
    }

    transportista_negocio_asegurar($conexion);
    $stmt = $conexion->prepare(
        "SELECT t.id, t.nombre
           FROM transportista_por_negocio r
           INNER JOIN transportistas t ON t.id = r.transportista_id
          WHERE r.bss_id = ? AND t.id = ? AND t.activo = 1 AND t.deliverys = 1
          LIMIT 1"
    );
    $stmt->bind_param("ii", $bss_id, $transportistaId);
    $stmt->execute();
    $transportista = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$transportista) {
        echo json_encode(['ok' => false, 'msg' => 'Transportista no válido o inactivo']);
        exit;
    }

    if (!transportista_asignar_delivery($conexion, (int)$bss_id, $id, $transportistaId, 'Transportista cambiado desde el panel')) {
        echo json_encode(['ok' => false, 'msg' => 'No se pudo cambiar el transportista']);
        exit;
    }

    $pushTransportista = transportista_notificar_asignacion($conexion, (int)$bss_id, $id, $transportistaId, 'Delivery reasignado');

    echo json_encode(['ok' => true, 'transportista' => $transportista['nombre'], 'push' => $pushTransportista]);
    exit;
}

/* ─── 3c. Reenviar la notificación al transportista asignado ───────────── */
if ($action === 'notificar_transportista') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['ok' => false, 'msg' => 'Pedido inválido']);
        exit;
    }

    $tp = transportista_servicio_actual($conexion, (int)$bss_id, $id);
    if (!$tp || (int)$tp['transportista_id'] <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'El pedido no tiene transportista asignado']);
        exit;
    }

    $pushTransportista = transportista_notificar_asignacion(
        $conexion,
        (int)$bss_id,
        $id,
        (int)$tp['transportista_id'],
        'Delivery asignado'
    );

    echo json_encode(['ok' => true, 'push' => $pushTransportista]);
    exit;
}

/* ─── 4. Planes de Entrega (Delivery) ────────────────────────────────── */
function generarCodigoPlan(mysqli $conexion): string {
    $letras = 'abcdefghijklmnopqrstuvwxyz';
    do {
        $nums = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $chars = '';
        for ($i = 0; $i < 4; $i++) {
            $chars .= $letras[random_int(0, 25)];
        }
        $codigo = "{$nums}-{$chars}";
        $stmt = $conexion->prepare("SELECT id FROM tienda_planes_entrega WHERE codigo = ? LIMIT 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    } while ($existe);
    return $codigo;
}

if ($action === 'crear_plan_entrega') {
    $nombre  = trim((string)($_POST['nombre'] ?? ''));
    $user_id = (int)($_SESSION['id'] ?? 0);
    $bss_val = (int)$bss_id;

    if ($nombre === '') {
        echo json_encode(['ok' => false, 'msg' => 'El nombre del plan es obligatorio']);
        exit;
    }
    if (mb_strlen($nombre) > 120) {
        echo json_encode(['ok' => false, 'msg' => 'El nombre del plan no puede superar los 120 caracteres']);
        exit;
    }

    try {
        $plan_id = 0;
        $codigo = '';
        // Reintenta si el código aleatorio choca con el índice UNIQUE.
        for ($intento = 0; $intento < 3; $intento++) {
            $stmt = null;
            try {
                $codigo = generarCodigoPlan($conexion);
                $stmt = $conexion->prepare("INSERT INTO tienda_planes_entrega (bss_id, user_id, nombre, codigo) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    throw new RuntimeException('prepare: ' . $conexion->error);
                }
                $stmt->bind_param("iiss", $bss_val, $user_id, $nombre, $codigo);
                $ok = $stmt->execute();
                $errno = (int)$conexion->errno;
                $error = $conexion->error;
                $insertId = (int)$conexion->insert_id;
                $stmt->close();
                $stmt = null;

                if ($ok) {
                    $plan_id = $insertId;
                    break;
                }
                if ($errno === 1062) {
                    continue; // código duplicado: regenerar
                }
                throw new RuntimeException('insert: ' . $error);
            } catch (Throwable $e) {
                if ($stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
                if ((int)$conexion->errno === 1062) {
                    continue; // código duplicado: regenerar
                }
                throw $e;
            }
        }

        if (!$plan_id) {
            throw new RuntimeException('No se pudo generar un código único para el plan');
        }

        echo json_encode([
            'ok'      => true,
            'plan_id' => $plan_id,
            'nombre'  => $nombre,
            'codigo'  => $codigo
        ]);
    } catch (Throwable $e) {
        error_log('crear_plan_entrega: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'No se pudo crear el plan de entrega']);
    }
    exit;
}

if ($action === 'listar_delivery_disponibles') {
    $bss_val = (int)$bss_id;
    $sql = "SELECT o.id, o.status, o.total_price, o.created, o.cliente, o.tipo_entrega,
                   u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
                   d.direccion, d.referencia, d.comunidad, d.lat, d.lng
            FROM tienda_ordenes o
            LEFT JOIN tienda_usuarios u ON o.customer_id = u.id
            LEFT JOIN tienda_usuarios_direcciones d ON o.direccion_id = d.id
            WHERE o.bss_id = ? AND o.tipo_entrega = 'delivery' AND o.status IN (1, 2, 3)
            ORDER BY o.id DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $bss_val);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['ok' => true, 'data' => $rows]);
    exit;
}

if ($action === 'listar_planes_entrega') {
    $bss_val = (int)$bss_id;
    $sql = "SELECT p.id, p.nombre, p.codigo, p.created_at,
                   COUNT(pp.id) AS total_pedidos,
                   SUM(CASE WHEN o.status = 4 THEN 1 ELSE 0 END) AS entregados,
                   SUM(CASE WHEN o.status != 4 THEN 1 ELSE 0 END) AS pendientes
            FROM tienda_planes_entrega p
            INNER JOIN tienda_planes_entrega_pedidos pp ON pp.plan_id = p.id
            INNER JOIN tienda_ordenes o ON o.id = pp.orden_id AND o.bss_id = p.bss_id
            WHERE p.bss_id = ?
            GROUP BY p.id, p.nombre, p.codigo, p.created_at
            HAVING pendientes > 0
            ORDER BY p.id DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $bss_val);
    $stmt->execute();
    $planes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['ok' => true, 'data' => $planes]);
    exit;
}

if ($action === 'detalle_plan_entrega') {
    $plan_id = (int)($_GET['id'] ?? 0);
    $bss_val = (int)$bss_id;

    $stmtPlan = $conexion->prepare("SELECT id, nombre, codigo, created_at FROM tienda_planes_entrega WHERE id = ? AND bss_id = ?");
    $stmtPlan->bind_param("ii", $plan_id, $bss_val);
    $stmtPlan->execute();
    $plan = $stmtPlan->get_result()->fetch_assoc();
    $stmtPlan->close();

    if (!$plan) {
        echo json_encode(['ok' => false, 'msg' => 'Plan no encontrado']);
        exit;
    }

    $sqlPed = "SELECT o.id, o.status, o.total_price, o.created, o.cliente,
                      u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
                      d.direccion, d.referencia, d.comunidad, d.lat, d.lng
               FROM tienda_planes_entrega_pedidos pp
               INNER JOIN tienda_ordenes o ON o.id = pp.orden_id
               LEFT JOIN tienda_usuarios u ON o.customer_id = u.id
               LEFT JOIN tienda_usuarios_direcciones d ON o.direccion_id = d.id
               WHERE pp.plan_id = ? AND o.bss_id = ?
               ORDER BY pp.id ASC";
    $stmtP = $conexion->prepare($sqlPed);
    $stmtP->bind_param("ii", $plan_id, $bss_val);
    $stmtP->execute();
    $pedidos = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtP->close();

    echo json_encode([
        'ok'      => true,
        'plan'    => $plan,
        'pedidos' => $pedidos
    ]);
    exit;
}

if ($action === 'guardar_pedidos_plan') {
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    $ordenes = $_POST['ordenes'] ?? [];
    $bss_val = (int)$bss_id;

    if (is_string($ordenes)) {
        $ordenes = array_filter(array_map('intval', explode(',', $ordenes)));
    } elseif (is_array($ordenes)) {
        $ordenes = array_values(array_unique(array_filter(array_map('intval', $ordenes))));
    } else {
        $ordenes = [];
    }

    if (!$plan_id || empty($ordenes)) {
        echo json_encode(['ok' => false, 'msg' => 'Debes seleccionar al menos un pedido']);
        exit;
    }

    $stmtPlan = $conexion->prepare("SELECT id, nombre, codigo FROM tienda_planes_entrega WHERE id = ? AND bss_id = ?");
    $stmtPlan->bind_param("ii", $plan_id, $bss_val);
    $stmtPlan->execute();
    $plan = $stmtPlan->get_result()->fetch_assoc();
    $stmtPlan->close();

    if (!$plan) {
        echo json_encode(['ok' => false, 'msg' => 'Plan de entrega no encontrado']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ordenes), '?'));
    $types = 'i' . str_repeat('i', count($ordenes));
    $params = array_merge([$bss_val], $ordenes);

    $sqlCheck = "SELECT o.id, o.status, o.total_price, o.created, o.cliente,
                        u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
                        d.direccion, d.referencia, d.comunidad, d.lat, d.lng
                 FROM tienda_ordenes o
                 LEFT JOIN tienda_usuarios u ON o.customer_id = u.id
                 LEFT JOIN tienda_usuarios_direcciones d ON o.direccion_id = d.id
                 WHERE o.bss_id = ? AND o.tipo_entrega = 'delivery' AND o.id IN ($placeholders)
                 ORDER BY o.id ASC";
    $stmtChk = $conexion->prepare($sqlCheck);
    $stmtChk->bind_param($types, ...$params);
    $stmtChk->execute();
    $pedidosValidos = $stmtChk->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtChk->close();

    if (empty($pedidosValidos)) {
        echo json_encode(['ok' => false, 'msg' => 'Ninguno de los pedidos seleccionados es válido para delivery']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $stmtDel = $conexion->prepare("DELETE FROM tienda_planes_entrega_pedidos WHERE plan_id = ?");
        if (!$stmtDel) {
            throw new RuntimeException('prepare delete: ' . $conexion->error);
        }
        $stmtDel->bind_param("i", $plan_id);
        if (!$stmtDel->execute()) {
            throw new RuntimeException('delete: ' . $conexion->error);
        }
        $stmtDel->close();

        $stmtIns = $conexion->prepare("INSERT INTO tienda_planes_entrega_pedidos (plan_id, orden_id) VALUES (?, ?)");
        if (!$stmtIns) {
            throw new RuntimeException('prepare insert: ' . $conexion->error);
        }
        foreach ($pedidosValidos as $p) {
            $oid = (int)$p['id'];
            $stmtIns->bind_param("ii", $plan_id, $oid);
            if (!$stmtIns->execute()) {
                throw new RuntimeException('insert orden ' . $oid . ': ' . $conexion->error);
            }
        }
        $stmtIns->close();
        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        error_log('guardar_pedidos_plan: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Error al guardar los pedidos del plan']);
        exit;
    }

    echo json_encode([
        'ok'      => true,
        'plan'    => $plan,
        'pedidos' => $pedidosValidos
    ]);
    exit;
}

echo json_encode(['ok' => false, 'msg' => 'Acción no reconocida']);
