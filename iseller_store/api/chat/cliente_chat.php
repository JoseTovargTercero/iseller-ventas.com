<?php
require_once('../../core/db.php');
require_once('../../core/session.php');
require_once('../../core/notificaciones_admin.php');

header('Content-Type: application/json');

$usuario_id = getUserId();
if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// La conexión abre en utf8 (3 bytes) y rompería los emojis guardados en utf8mb4.
$conexion_store->set_charset('utf8mb4');

$action = $_GET['action'] ?? 'listar';

switch ($action) {

    case 'listar':
        $stmt = $conexion_store->prepare(
            "SELECT c.id, c.asunto, c.estado, c.creada_en, c.ultimo_mensaje_en,
                    cat.nombre AS categoria_nombre,
                    COALESCE(NULLIF(n.nombre_visible, ''), n.nombre, NULLIF(n2.nombre_visible, ''), n2.nombre) AS tienda_nombre,
                    COALESCE(n.logo_url, n2.logo_url) AS tienda_logo,
                    (SELECT COUNT(*) FROM tienda_chat_mensajes WHERE conversacion_id = c.id AND es_admin = 1 AND leido = 0) AS mensajes_sin_leer,
                    (SELECT mensaje FROM tienda_chat_mensajes WHERE conversacion_id = c.id ORDER BY creado_en DESC LIMIT 1) AS ultimo_mensaje
             FROM tienda_chat_conversaciones c
             LEFT JOIN tienda_chat_categorias cat ON c.categoria_id = cat.id
             LEFT JOIN negocio n ON n.id = c.bss_id
             LEFT JOIN tienda_ordenes o ON o.id = c.pedido_id
             LEFT JOIN negocio n2 ON n2.id = o.bss_id
             WHERE c.usuario_id = ?
             ORDER BY c.ultimo_mensaje_en DESC"
        );
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'mensajes':
        $conv_id = (int)($_GET['conversacion_id'] ?? 0);
        if ($conv_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            break;
        }
        $stmt = $conexion_store->prepare(
            "SELECT id, es_admin, mensaje, leido, creado_en
             FROM tienda_chat_mensajes WHERE conversacion_id = ? AND (SELECT usuario_id FROM tienda_chat_conversaciones WHERE id = ?) = ?
             ORDER BY creado_en ASC"
        );
        $stmt->bind_param("iii", $conv_id, $conv_id, $usuario_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'responder':
        $input = json_decode(file_get_contents('php://input'), true);
        $conv_id = (int)($input['conversacion_id'] ?? 0);
        $mensaje = trim($input['mensaje'] ?? '');

        if ($conv_id <= 0 || $mensaje === '') {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            break;
        }

        $stmt = $conexion_store->prepare("SELECT id FROM tienda_chat_conversaciones WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $conv_id, $usuario_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Conversación no encontrada']);
            break;
        }
        $stmt->close();

        $stmt = $conexion_store->prepare("SELECT estado, bss_id FROM tienda_chat_conversaciones WHERE id = ?");
        $stmt->bind_param("i", $conv_id);
        $stmt->execute();
        $conv = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($conv['estado'] === 'cerrada') {
            echo json_encode(['success' => false, 'message' => 'Esta conversación está cerrada']);
            break;
        }

        $conexion_store->begin_transaction();

        $stmt = $conexion_store->prepare("INSERT INTO tienda_chat_mensajes (conversacion_id, es_admin, mensaje, leido) VALUES (?, 0, ?, 0)");
        $stmt->bind_param("is", $conv_id, $mensaje);
        $stmt->execute();
        $msg_id = $conexion_store->insert_id;
        $stmt->close();

        $conexion_store->query("UPDATE tienda_chat_conversaciones SET ultimo_mensaje_en=NOW() WHERE id=$conv_id");

        $conexion_store->commit();

        // Notificación in-app al comercio (usuario configurado o, si no hay, sus administradores)
        if ((int)$conv['bss_id'] > 0) {
            $nombreCliente = getUserName() ?: 'Cliente';
            notificar_admin_tienda(
                $conexion,
                (int)$conv['bss_id'],
                'Nuevo mensaje de chat',
                $nombreCliente . ': ' . substr($mensaje, 0, 140),
                'chat'
            );
        }

        echo json_encode(['success' => true, 'mensaje_id' => $msg_id]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
        break;
}
