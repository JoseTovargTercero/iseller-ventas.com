<?php
require_once('../core/db.php');
require_once('../core/session.php');

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = getUserId();
try {
    // 1. Información del Usuario
    $stmtUser = $conexion_store->prepare("
        SELECT id, nombre, email, puntos, nivel, creado_en, referral_code, foto 
        FROM tienda_usuarios 
        WHERE id = ?
    ");
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $userData = $resultUser->fetch_assoc();
    $stmtUser->close();

    if (!$userData) {
        throw new Exception("Usuario no encontrado");
    }

    // Calcular progreso de puntos
    $puntos = floatval($userData['puntos']);
    $nivel = intval($userData['nivel']);
    $progreso = fmod($puntos, 10);
    $porcentaje = ($progreso / 10) * 100;
    $falta = 10 - $progreso;

    // 2. Direcciones del Usuario
    $stmtAddress = $conexion_store->prepare("
        SELECT id, direccion, referencia, lat, lng, es_principal, nombre_receptor, telefono, comunidad, delivery_gratis_confirmado
        FROM tienda_usuarios_direcciones 
        WHERE usuario_id = ?
        ORDER BY es_principal DESC, id DESC
    ");
    $stmtAddress->bind_param("i", $user_id);
    $stmtAddress->execute();
    $resultAddress = $stmtAddress->get_result();
    $addresses = [];
    while ($row = $resultAddress->fetch_assoc()) {
        $addresses[] = $row;
    }
    $stmtAddress->close();

    // 3. Recompensas del Usuario
    $stmtRewards = $conexion_store->prepare("
        SELECT id, nivel_desbloqueo, tipo, monto, estado, fecha_creacion, fecha_uso
        FROM tienda_recompensas_usuario 
        WHERE usuario_id = ?
        ORDER BY estado ASC, nivel_desbloqueo DESC
    ");
    $stmtRewards->bind_param("i", $user_id);
    $stmtRewards->execute();
    $resultRewards = $stmtRewards->get_result();
    $rewards = [];
    while ($row = $resultRewards->fetch_assoc()) {
        $rewards[] = $row;
    }
    $stmtRewards->close();

    // 4. Compras Pendientes (no entregadas)
    $stmtOrders = $conexion_store->prepare("
        SELECT 
            c.id,
            c.fecha,
            c.estado,
            c.valor_compra,
            c.importe_envio,
            c.tipo_entrega,
            c.puntos_generados,
            c.ganancia_generada,
            c.ahorrado,
            o.id AS orden_id,
            o.status AS orden_status,
            o.bss_id AS negocio_id,
            n.nombre_visible AS negocio_nombre,
            n.slug AS negocio_slug,
            (r.id IS NOT NULL) AS ya_calificada,
            (SELECT cv.id FROM tienda_chat_conversaciones cv WHERE cv.pedido_id = o.id AND cv.usuario_id = c.usuario_id ORDER BY cv.id DESC LIMIT 1) AS conversacion_id,
            (SELECT oa.product_id FROM tienda_orden_articulos oa WHERE oa.order_id = o.id ORDER BY oa.id ASC LIMIT 1) AS primer_producto_id,
            (SELECT p.nombre FROM tienda_orden_articulos oa LEFT JOIN productos p ON p.id = oa.product_id WHERE oa.order_id = o.id ORDER BY oa.id ASC LIMIT 1) AS primer_producto_nombre,
            (SELECT SUM(oa.quantity) FROM tienda_orden_articulos oa WHERE oa.order_id = o.id) AS total_unidades,
            (SELECT COUNT(*) FROM tienda_orden_articulos oa WHERE oa.order_id = o.id) AS total_items
        FROM tienda_compras_por_usuarios c
        LEFT JOIN tienda_ordenes o ON o.id = c.compra_id
        LEFT JOIN negocio n ON n.id = o.bss_id
        LEFT JOIN tienda_reputacion_votos r ON r.orden_id = o.id
        WHERE c.usuario_id = ?
        ORDER BY c.fecha DESC
        LIMIT 50
    ");
    $stmtOrders->bind_param("i", $user_id);
    $stmtOrders->execute();
    $resultOrders = $stmtOrders->get_result();
    $orders = [];
    while ($row = $resultOrders->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmtOrders->close();

    // 5. Estadísticas adicionales
    $stmtStats = $conexion_store->prepare("
        SELECT 
            COUNT(*) as total_compras,
            SUM(valor_compra) as total_gastado,
            SUM(puntos_generados) as total_puntos_ganados,
            SUM(ahorrado) as total_ahorrado
        FROM tienda_compras_por_usuarios
        WHERE usuario_id = ?
    ");
    $stmtStats->bind_param("i", $user_id);
    $stmtStats->execute();
    $resultStats = $stmtStats->get_result();
    $stats = $resultStats->fetch_assoc();
    $stmtStats->close();

    // Respuesta
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $userData['id'],
            'nombre' => $userData['nombre'],
            'email' => $userData['email'],
            'puntos' => $puntos,
            'nivel' => $nivel,
            'progreso' => round($progreso, 2),
            'porcentaje' => round($porcentaje, 2),
            'falta' => round($falta, 2),
            'falta' => round($falta, 2),
            'referral_code' => $userData['referral_code'],
            'foto' => $userData['foto'],
            'created_at' => $userData['creado_en']
        ],
        'addresses' => $addresses,
        'rewards' => $rewards,
        'orders' => $orders,
        'stats' => [
            'total_compras' => intval($stats['total_compras']),
            'total_gastado' => floatval($stats['total_gastado']),
            'total_puntos_ganados' => floatval($stats['total_puntos_ganados']),
            'total_ahorrado' => floatval($stats['total_ahorrado'])
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar datos: ' . $e->getMessage()
    ]);
}
?>
