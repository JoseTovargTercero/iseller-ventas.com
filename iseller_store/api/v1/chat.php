<?php
/**
 * Chat del cliente (Bearer).
 *
 * GET  chat.php                     → conversaciones del usuario
 * GET  chat.php?id=12               → conversación + mensajes (marca leídos)
 * GET  chat.php?order_id=44         → conversación del pedido (la crea si falta)
 * POST chat.php                     → envía un mensaje
 *
 * Body POST: { "conversation_id": 12, "message": "Hola" }
 */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';
require_once __DIR__ . '/../../core/notificaciones_admin.php';

$user = api_auth();
$userId = (int)$user['id'];
global $conexion, $conexion_store;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST') {
    api_rate_limit('chat_post', 30, 300);
} else {
    api_rate_limit('chat_get', 120, 60);
}

// Las tablas de chat son utf8mb4: emojis incluidos.
$conexion_store->set_charset('utf8mb4');

/** Resumen de una conversación, con nombre/logo de la tienda y no leídos. */
function api_chat_conversacion(array $c): array
{
    return [
        'id'            => (int)$c['id'],
        'order_id'      => $c['pedido_id'] !== null ? (int)$c['pedido_id'] : null,
        'subject'       => (string)$c['asunto'],
        'status'        => (string)$c['estado'],
        'store_name'    => (string)($c['store_name'] ?? ''),
        'store_logo'    => api_image($c['logo_url'] ?? '', 160, 78),
        'unread'        => (int)($c['unread'] ?? 0),
        'last_message'  => (string)($c['last_message'] ?? ''),
        'last_message_at' => (string)($c['ultimo_mensaje_en'] ?? $c['creada_en'] ?? ''),
        'created_at'    => (string)($c['creada_en'] ?? ''),
    ];
}

function api_chat_buscar(int $userId, int $convId): ?array
{
    global $conexion_store;
    $stmt = $conexion_store->prepare(
        "SELECT c.*, COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) AS store_name, n.logo_url,
                (SELECT COUNT(*) FROM tienda_chat_mensajes m
                  WHERE m.conversacion_id = c.id AND m.es_admin = 1 AND m.leido = 0) AS unread,
                (SELECT m.mensaje FROM tienda_chat_mensajes m
                  WHERE m.conversacion_id = c.id ORDER BY m.creado_en DESC, m.id DESC LIMIT 1) AS last_message
         FROM tienda_chat_conversaciones c
         LEFT JOIN negocio n ON n.id = c.bss_id
         WHERE c.id = ? AND c.usuario_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $convId, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function api_chat_mensajes(int $convId): array
{
    global $conexion_store;
    $stmt = $conexion_store->prepare(
        "SELECT id, es_admin, mensaje, creado_en
         FROM tienda_chat_mensajes WHERE conversacion_id = ?
         ORDER BY creado_en ASC, id ASC"
    );
    $stmt->bind_param('i', $convId);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($m = $res->fetch_assoc()) {
        $items[] = [
            'id'         => (int)$m['id'],
            'mine'       => (int)$m['es_admin'] === 0,
            'message'    => (string)$m['mensaje'],
            'created_at' => (string)$m['creado_en'],
        ];
    }
    $stmt->close();
    return $items;
}

function api_chat_detalle(int $userId, int $convId): void
{
    global $conexion_store;
    $conv = api_chat_buscar($userId, $convId);
    if ($conv === null) {
        api_error(404, 'Conversación no encontrada', 'not_found');
    }
    // El cliente leyó los mensajes de la tienda.
    $stmt = $conexion_store->prepare(
        "UPDATE tienda_chat_mensajes SET leido = 1, leido_en = NOW()
         WHERE conversacion_id = ? AND es_admin = 1 AND leido = 0"
    );
    $stmt->bind_param('i', $convId);
    $stmt->execute();
    $stmt->close();

    api_ok([
        'conversation' => api_chat_conversacion($conv),
        'messages'     => api_chat_mensajes($convId),
    ]);
}

if ($method === 'GET') {
    $orderId = (int)($_GET['order_id'] ?? 0);
    $convId = (int)($_GET['id'] ?? 0);

    if ($orderId > 0) {
        $stmt = $conexion_store->prepare(
            "SELECT id FROM tienda_chat_conversaciones
             WHERE pedido_id = ? AND usuario_id = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila) {
            api_chat_detalle($userId, (int)$fila['id']);
        }

        $stmt = $conexion_store->prepare(
            "SELECT id, negocio_id FROM tienda_ordenes WHERE id = ? AND usuario_id = ? LIMIT 1"
        );
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$orden) {
            api_error(404, 'Pedido no encontrado', 'not_found');
        }
        try {
            require_once __DIR__ . '/../../core/chat_compra.php';
            chat_compra_crear_conversacion(
                $conexion_store,
                $userId,
                (int)$orden['negocio_id'],
                $orderId
            );
        } catch (Throwable $e) {
            error_log('API chat crear: ' . $e->getMessage());
        }
        $stmt = $conexion_store->prepare(
            "SELECT id FROM tienda_chat_conversaciones
             WHERE pedido_id = ? AND usuario_id = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$fila) {
            api_error(500, 'No se pudo abrir el chat');
        }
        api_chat_detalle($userId, (int)$fila['id']);
    }

    if ($convId > 0) {
        api_chat_detalle($userId, $convId);
    }

    $stmt = $conexion_store->prepare(
        "SELECT c.*, COALESCE(NULLIF(n.nombre_visible, ''), n.nombre) AS store_name, n.logo_url,
                (SELECT COUNT(*) FROM tienda_chat_mensajes m
                  WHERE m.conversacion_id = c.id AND m.es_admin = 1 AND m.leido = 0) AS unread,
                (SELECT m.mensaje FROM tienda_chat_mensajes m
                  WHERE m.conversacion_id = c.id ORDER BY m.creado_en DESC, m.id DESC LIMIT 1) AS last_message
         FROM tienda_chat_conversaciones c
         LEFT JOIN negocio n ON n.id = c.bss_id
         WHERE c.usuario_id = ?
         ORDER BY COALESCE(c.ultimo_mensaje_en, c.creada_en) DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($c = $res->fetch_assoc()) {
        $items[] = api_chat_conversacion($c);
    }
    $stmt->close();
    api_ok(['items' => $items]);
}

if ($method === 'POST') {
    $in = api_body();
    $convId = (int)($in['conversation_id'] ?? 0);
    $texto = trim((string)($in['message'] ?? ''));
    if ($convId <= 0 || $texto === '') {
        api_error(400, 'Escribe un mensaje', 'invalid_message');
    }
    if (mb_strlen($texto) > 5000) {
        $texto = mb_substr($texto, 0, 5000);
    }
    $conv = api_chat_buscar($userId, $convId);
    if ($conv === null) {
        api_error(404, 'Conversación no encontrada', 'not_found');
    }
    if ((string)$conv['estado'] === 'cerrada') {
        api_error(409, 'Esta conversación está cerrada', 'closed');
    }

    $stmt = $conexion_store->prepare(
        "INSERT INTO tienda_chat_mensajes (conversacion_id, es_admin, mensaje, leido)
         VALUES (?, 0, ?, 0)"
    );
    $stmt->bind_param('is', $convId, $texto);
    if (!$stmt->execute()) {
        $stmt->close();
        api_error(500, 'No se pudo enviar el mensaje');
    }
    $msgId = $conexion_store->insert_id;
    $stmt->close();
    $conexion_store->query(
        "UPDATE tienda_chat_conversaciones SET ultimo_mensaje_en = NOW() WHERE id = " . (int)$convId
    );

    try {
        $nombre = trim(((string)($user['nombre'] ?? '')) . ' ' . ((string)($user['apellido'] ?? '')));
        notificar_admin_tienda(
            $conexion,
            (int)$conv['bss_id'],
            'Nuevo mensaje de chat',
            ($nombre !== '' ? $nombre : 'Cliente') . ': ' . mb_substr($texto, 0, 140),
            'chat'
        );
    } catch (Throwable $e) {
        error_log('API chat notificar: ' . $e->getMessage());
    }

    api_ok([
        'message' => [
            'id'         => (int)$msgId,
            'mine'       => true,
            'message'    => $texto,
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ], ['message' => 'Mensaje enviado']);
}

api_error(405, 'Método no permitido', 'method');
