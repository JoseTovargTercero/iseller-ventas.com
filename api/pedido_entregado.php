<?php
/**
 * API — Marcar un pedido de una ruta como ENTREGADO  →  POST /api/pedido_entregado.php
 *
 * Lo llama la app Android desde el mapa de la ruta cuando el repartidor toca "Entregado".
 * Deja la orden como entregada (status = 4, igual que el panel) y avisa al comercio con
 * una notificación in-app + push (helper notificar_admin_tienda).
 *
 * Autenticación del cliente: cabecera `X-Api-Key` (POS_API_KEY del .env), igual que
 * auth_login.php y ruta_entrega.php.
 *
 * Entrada (JSON o formulario):
 *   codigo         string  código de la ruta (0000-aaaa) donde está el pedido
 *   numero_pedido  string  id de tienda_ordenes (= numero_pedido que da ruta_entrega.php)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

set_exception_handler(function () {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../configurar/configuracion.php';

function salir(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Solo POST ──────────────────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    salir(405, ['ok' => false, 'error' => 'Método no permitido']);
}

// ── Rate limit por IP: 60 entregas / 5 min ─────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlFile = sys_get_temp_dir() . '/iseller_entrega_' . md5($ip) . '.json';
$rlMax = 60;
$rlWindow = 300;
$ahora = time();
$rl = ['inicio' => $ahora, 'n' => 0];
if (is_file($rlFile)) {
    $prev = json_decode((string)@file_get_contents($rlFile), true);
    if (is_array($prev) && isset($prev['inicio'], $prev['n']) && ($ahora - (int)$prev['inicio']) < $rlWindow) {
        $rl = ['inicio' => (int)$prev['inicio'], 'n' => (int)$prev['n']];
    }
}
$rl['n']++;
@file_put_contents($rlFile, json_encode($rl));
if ($rl['n'] > $rlMax) {
    salir(429, ['ok' => false, 'error' => 'Demasiadas peticiones']);
}

// ── API key (fail-closed) ──────────────────────────────────────────────────────
$apiKeyEsperada = trim($_ENV['POS_API_KEY'] ?? '');
$apiKeyRecibida = trim($_SERVER['HTTP_X_API_KEY'] ?? '');
if ($apiKeyEsperada === '') {
    if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
        salir(503, ['ok' => false, 'error' => 'API no configurada']);
    }
} elseif ($apiKeyRecibida === '' || !hash_equals($apiKeyEsperada, $apiKeyRecibida)) {
    salir(401, ['ok' => false, 'error' => 'No autorizado']);
}

// ── Entrada: JSON o formulario ─────────────────────────────────────────────────
$raw = file_get_contents('php://input');
$json = json_decode((string)$raw, true);
$in = is_array($json) ? $json : $_POST;

$codigo = strtolower(trim((string)($in['codigo'] ?? '')));
$numeroPedido = trim((string)($in['numero_pedido'] ?? ''));

if (!preg_match('/^\d{4}-[a-z]{4}$/', $codigo)) {
    salir(400, ['ok' => false, 'error' => 'Código con formato inválido']);
}
if ($numeroPedido === '' || !ctype_digit($numeroPedido)) {
    salir(400, ['ok' => false, 'error' => 'Número de pedido inválido']);
}
$ordenId = (int)$numeroPedido;

// ── El pedido debe pertenecer a esa ruta (y al negocio de la ruta) ─────────────
$stmt = $conexion->prepare(
    "SELECT p.id AS plan_id, p.bss_id, p.nombre AS plan_nombre,
            o.id AS orden_id, o.status, o.cliente, o.total_price
       FROM tienda_planes_entrega p
       INNER JOIN tienda_planes_entrega_pedidos pp ON pp.plan_id = p.id AND pp.orden_id = ?
       INNER JOIN tienda_ordenes o ON o.id = pp.orden_id AND o.bss_id = p.bss_id
      WHERE p.codigo = ?
      LIMIT 1"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('is', $ordenId, $codigo);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    salir(404, ['ok' => false, 'error' => 'Pedido no encontrado en esta ruta']);
}

$bssId = (int)$pedido['bss_id'];

// 4 = entregado (mismos valores que el panel: 1 pendiente, 2 preparación, 3 enviado, 4 entregado, 5 rechazado)
if ((int)$pedido['status'] === 4) {
    // Ya estaba entregado: no se repite la notificación.
    salir(200, ['ok' => true, 'numero_pedido' => $ordenId, 'ya_estaba' => true]);
}
if ((int)$pedido['status'] === 5) {
    salir(409, ['ok' => false, 'error' => 'El pedido está rechazado']);
}

// ── Marcar entregado (igual que el panel en db/tienda_pedidos_api.php) ─────────
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conexion->begin_transaction();

    $stmt = $conexion->prepare("UPDATE tienda_ordenes SET status = 4 WHERE id = ? AND bss_id = ?");
    $stmt->bind_param('ii', $ordenId, $bssId);
    $stmt->execute();
    $stmt->close();

    // Réplica al historial del cliente, para que vea el pedido entregado
    $stmt = $conexion->prepare("UPDATE tienda_compras_por_usuarios SET estatus = 'entregada', estado = 'entregada' WHERE compra_id = ?");
    $stmt->bind_param('i', $ordenId);
    $stmt->execute();
    $stmt->close();

    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    error_log('Entrega: no se pudo actualizar la orden ' . $ordenId . ': ' . $e->getMessage());
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
mysqli_report(MYSQLI_REPORT_OFF);

// ── Aviso al comercio (in-app + push). Nunca tumba la respuesta ────────────────
$aviso = false;
try {
    require_once __DIR__ . '/../iseller_store/core/notificaciones_admin.php';
    $cliente = trim((string)$pedido['cliente']);
    $detalle = 'Ruta ' . $pedido['plan_nombre']
        . ($cliente !== '' ? ' · ' . $cliente : '')
        . ($pedido['total_price'] !== null && $pedido['total_price'] !== '' ? ' · $' . $pedido['total_price'] : '');
    notificar_admin_tienda($conexion, $bssId, 'Pedido #' . $ordenId . ' entregado', $detalle, 'compra');
    $aviso = true;
} catch (Throwable $e) {
    error_log('Entrega: notificación falló: ' . $e->getMessage());
}

salir(200, [
    'ok'            => true,
    'numero_pedido' => $ordenId,
    'entregado_en'  => date('Y-m-d H:i:s'),
    'notificado'    => $aviso,
]);
