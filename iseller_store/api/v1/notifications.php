<?php
/**
 * GET  /iseller_store/api/v1/notifications.php          → notificaciones del cliente + no leídas
 * POST /iseller_store/api/v1/notifications.php {id?}    → marca leída una (o todas si no viene id)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';

$user = api_auth();
global $conexion_store;

function api_notification_public(array $n): array
{
    return [
        'id'         => (int)$n['id'],
        'title'      => (string)$n['titulo'],
        'body'       => (string)($n['cuerpo'] ?? ''),
        'type'       => (string)($n['tipo'] ?? 'sistema'),
        'link'       => (string)($n['enlace'] ?? ''),
        'read'       => (int)($n['leida'] ?? 0) === 1,
        'created_at' => (string)$n['created_at'],
    ];
}

function api_notifications_unread(int $usuarioId): int
{
    global $conexion_store;
    $stmt = $conexion_store->prepare(
        "SELECT COUNT(*) AS total FROM tienda_cliente_notificaciones
         WHERE usuario_id = ? AND leida = 0"
    );
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    return $total;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    api_method('GET');
    api_rate_limit('notifications_get', 120, 60);
    $limite = min(max((int)($_GET['limit'] ?? 30), 1), 50);
    $stmt = $conexion_store->prepare(
        "SELECT id, titulo, cuerpo, tipo, enlace, leida, created_at
         FROM tienda_cliente_notificaciones
         WHERE usuario_id = ?
         ORDER BY created_at DESC
         LIMIT ?"
    );
    $id = (int)$user['id'];
    $stmt->bind_param('ii', $id, $limite);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($fila = $res->fetch_assoc()) {
        $items[] = api_notification_public($fila);
    }
    $stmt->close();
    api_ok(['items' => $items, 'unread' => api_notifications_unread($id)]);
}

api_method('POST');
api_rate_limit('notifications_post', 120, 60);
$in = api_body();
$notificacionId = (int)($in['id'] ?? 0);
$id = (int)$user['id'];

if ($notificacionId > 0) {
    $stmt = $conexion_store->prepare(
        "UPDATE tienda_cliente_notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?"
    );
    $stmt->bind_param('ii', $notificacionId, $id);
} else {
    $stmt = $conexion_store->prepare(
        "UPDATE tienda_cliente_notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0"
    );
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$stmt->close();
api_ok(['unread' => api_notifications_unread($id)]);
