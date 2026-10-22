<?php
require_once('../../core/db.php');
require_once('../../core/session.php');
require_once('chat_helpers.php');
require_once('../../core/notificaciones_admin.php');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$categoria_id = (int)($input['categoria_id'] ?? 0);
$asunto = trim((string)($input['asunto'] ?? ''));
$mensaje = trim((string)($input['mensaje'] ?? ''));

if (!$categoria_id || mb_strlen($asunto) < 3 || mb_strlen($mensaje) < 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}
if (mb_strlen($asunto) > 200) $asunto = mb_substr($asunto, 0, 200);
if (mb_strlen($mensaje) > 5000) $mensaje = mb_substr($mensaje, 0, 5000);

// Solo clientes con sesión: antes se podía abrir un chat a nombre de cualquier
// email registrado (suplantación) o crear cuentas sin contraseña.
$usuario_id = (int)getUserId();
if ($usuario_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Inicia sesión para iniciar un chat']);
    exit;
}

try {
    $conexion_store->begin_transaction();

    $bss_id = (int)($input['bss_id'] ?? 0);
    $stmtTienda = $conexion_store->prepare("SELECT id FROM negocio WHERE id = ? AND tiene_tienda_virtual = 1 AND estado = 'activo' LIMIT 1");
    $stmtTienda->bind_param("i", $bss_id);
    $stmtTienda->execute();
    $tiendaOk = $stmtTienda->get_result()->num_rows > 0;
    $stmtTienda->close();
    if (!$tiendaOk) {
        $conexion_store->rollback();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tienda inválida']);
        exit;
    }

    $stmt = $conexion_store->prepare("INSERT INTO tienda_chat_conversaciones (usuario_id, bss_id, categoria_id, asunto, estado, ultimo_mensaje_en) VALUES (?, ?, ?, ?, 'abierta', NOW())");
    $stmt->bind_param("iiis", $usuario_id, $bss_id, $categoria_id, $asunto);
    $stmt->execute();
    $conversacion_id = $conexion_store->insert_id;
    $stmt->close();

    $stmt = $conexion_store->prepare("INSERT INTO tienda_chat_mensajes (conversacion_id, es_admin, mensaje, leido) VALUES (?, 0, ?, 0)");
    $stmt->bind_param("is", $conversacion_id, $mensaje);
    $stmt->execute();
    $stmt->close();

    $conexion_store->commit();

    @enviarNotificacionNuevoMensaje($usuario_id, $conversacion_id, $mensaje);

    // Notificación in-app al dueño de la tienda (o a sus administradores si no hay uno configurado)
    if ($bss_id > 0) {
        $nombreCliente = getUserName() ?: 'Cliente';
        notificar_admin_tienda(
            $conexion,
            $bss_id,
            'Nuevo mensaje de chat',
            "De: $nombreCliente | Asunto: $asunto",
            'chat'
        );
    }

    echo json_encode(['success' => true, 'conversacion_id' => $conversacion_id]);
} catch (Throwable $e) {
    $conexion_store->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear conversación']);
}
