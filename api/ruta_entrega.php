<?php
/**
 * API — Ruta de entrega (iSeller POS)
 *
 * GET/POST /api/ruta_entrega.php?codigo=1234-abcd
 *
 * Autenticación: cabecera `X-Api-Key` (o `?key=`). La clave se lee de la variable
 * POS_API_KEY del .env. Ver contrato en el README/§ notas.
 *
 * Fuente de datos:
 *   tienda_planes_entrega            -> ruta (nombre, codigo, created_at)
 *   tienda_planes_entrega_pedidos    -> pedidos de la ruta
 *   tienda_ordenes                   -> pedido
 *   tienda_usuarios                  -> nombre/teléfono del cliente
 *   tienda_usuarios_direcciones      -> dirección, referencia, lat/lng
 */

// ── Salida JSON y errores genéricos (nunca exponer detalles internos) ──────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// Debe ir antes de configuracion.php para capturar cualquier fallo de arranque.
set_exception_handler(function () {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../configurar/configuracion.php';

// ── Solo GET y POST ────────────────────────────────────────────────────────────
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($metodo !== 'GET' && $metodo !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Rate limit por IP (evita fuerza bruta sobre el código) ─────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlFile = sys_get_temp_dir() . '/iseller_ruta_' . md5($ip) . '.json';
$rlMax = 120;      // peticiones
$rlWindow = 60;    // por 60 segundos
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
    http_response_code(429);
    echo json_encode(['error' => 'Demasiadas peticiones'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Autenticación por API key (fail-closed) ────────────────────────────────────
$apiKeyEsperada = trim($_ENV['POS_API_KEY'] ?? '');
$apiKeyRecibida = trim($_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? ($_POST['key'] ?? '')));

if ($apiKeyEsperada === '') {
    // Sin clave configurada el endpoint queda deshabilitado salvo flag explícito de desarrollo.
    if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
        http_response_code(503);
        echo json_encode(['error' => 'API no configurada'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} elseif ($apiKeyRecibida === '' || !hash_equals($apiKeyEsperada, $apiKeyRecibida)) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Código: normalizar y validar formato ───────────────────────────────────────
$codigo = strtolower(trim($_GET['codigo'] ?? ($_POST['codigo'] ?? '')));

if (!preg_match('/^\d{4}-[a-z]{4}$/', $codigo)) {
    http_response_code(400);
    echo json_encode(['error' => 'Código con formato inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Buscar la ruta ─────────────────────────────────────────────────────────────
$stmtPlan = $conexion->prepare(
    "SELECT id, bss_id, nombre, codigo, created_at
       FROM tienda_planes_entrega
      WHERE codigo = ?
      LIMIT 1"
);
if (!$stmtPlan) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmtPlan->bind_param('s', $codigo);
$stmtPlan->execute();
$plan = $stmtPlan->get_result()->fetch_assoc();
$stmtPlan->close();

if (!$plan) {
    http_response_code(404);
    echo json_encode(['error' => 'Código no encontrado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Pedidos de la ruta ─────────────────────────────────────────────────────────
$stmtPed = $conexion->prepare(
    "SELECT o.id AS orden_id, o.status AS orden_status, o.cliente AS orden_cliente,
            u.nombre AS cliente_nombre, u.telefono AS cliente_telefono,
            d.nombre_receptor, d.telefono AS direccion_telefono,
            d.direccion, d.referencia, d.lat, d.lng
       FROM tienda_planes_entrega_pedidos pp
       INNER JOIN tienda_ordenes o ON o.id = pp.orden_id AND o.bss_id = ?
       LEFT JOIN tienda_usuarios u ON u.id = o.customer_id
       LEFT JOIN tienda_usuarios_direcciones d ON d.id = o.direccion_id
      WHERE pp.plan_id = ?
      ORDER BY pp.id ASC"
);
if (!$stmtPed) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
}
$planId = (int)$plan['id'];
$bssId = (int)$plan['bss_id'];
$stmtPed->bind_param('ii', $bssId, $planId);
$stmtPed->execute();
$rows = $stmtPed->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtPed->close();

$pedidos = [];
$i = 0;
foreach ($rows as $r) {
    $lat = $r['lat'];
    $lng = $r['lng'];
    $tieneGps = ($lat !== null && $lng !== null && !((float)$lat === 0.0 && (float)$lng === 0.0));
    $coords = $tieneGps ? ((string)$lat . ',' . (string)$lng) : '';

    $nombreCliente = $r['cliente_nombre'] ?: ($r['nombre_receptor'] ?: (string)$r['orden_cliente']);
    $telefono = preg_replace('/\D/', '', (string)($r['cliente_telefono'] ?: $r['direccion_telefono']));

    // El contrato exige "pedidos" como OBJETO con claves "0","1",...
    $pedidos[(string)$i] = [
        'coords'           => $coords,
        'direccion'        => (string)($r['direccion'] ?? ''),
        'puntoreferencia'  => (string)($r['referencia'] ?? ''),
        'nombre_cliente'   => (string)$nombreCliente,
        'telefono_cliente' => (string)$telefono,
        'numero_pedido'    => (string)$r['orden_id'],
        // Estado real de la entrega en el servidor: así la app puede sincronizarse
        // (4 = entregado, mismos valores que el panel).
        'estatus_entrega'  => ((int)$r['orden_status'] === 4 ? 'ENTREGADO' : 'PENDIENTE'),
    ];
    $i++;
}

$ruta = [
    // El código va en la respuesta para que la app pueda referirse a la ruta
    // (p. ej. al marcar un pedido entregado contra api/pedido_entregado.php).
    'codigo'     => (string)$plan['codigo'],
    'fecha_ruta' => date('Y-m-d', strtotime($plan['created_at'])),
    'nombre'     => (string)$plan['nombre'],
    'pedidos'    => (object)$pedidos,
];

echo json_encode($ruta, JSON_UNESCAPED_UNICODE);
