<?php
require_once('../core/db.php');
require_once('../core/session.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!validateCSRFToken($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

$user_id    = (int)getUserId();
$orden_id   = (int)($_POST['orden_id'] ?? 0);
$recomienda = isset($_POST['recomienda']) ? (int)$_POST['recomienda'] : -1;
$comentario = trim((string)($_POST['comentario'] ?? ''));

if ($orden_id <= 0 || ($recomienda !== 0 && $recomienda !== 1)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

if (mb_strlen($comentario) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El comentario supera los 500 caracteres']);
    exit;
}

try {
    $stmt = $conexion->prepare("SELECT id, bss_id, customer_id, status FROM tienda_ordenes WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $orden = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$orden) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }

    if ((int)$orden['customer_id'] !== $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Este pedido no pertenece a tu cuenta']);
        exit;
    }

    if ((int)$orden['status'] !== 4) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Solo se califica un pedido entregado']);
        exit;
    }

    $stmt = $conexion->prepare("SELECT id FROM tienda_reputacion_votos WHERE orden_id = ? LIMIT 1");
    $stmt->bind_param("i", $orden_id);
    $stmt->execute();
    $yaVoto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($yaVoto) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ya calificaste este pedido']);
        exit;
    }

    $negocio_id = (int)$orden['bss_id'];
    $comentario_db = $comentario !== '' ? $comentario : null;

    $conexion->begin_transaction();

    $stmt = $conexion->prepare("
        INSERT INTO tienda_reputacion_votos (orden_id, negocio_id, usuario_id, recomienda, comentario)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiis", $orden_id, $negocio_id, $user_id, $recomienda, $comentario_db);
    $stmt->execute();
    $stmt->close();

    require_once('../core/reputacion.php');
    $agregado = reputacion_recalcular($conexion, $negocio_id);

    $conexion->commit();

    echo json_encode([
        'success'     => true,
        'total'       => $agregado['total'],
        'recomiendan' => $agregado['recomiendan'],
        'pct'         => $agregado['pct'],
        'visible'     => $agregado['visible']
    ]);
} catch (mysqli_sql_exception $e) {
    $conexion->rollback();
    if ($e->getCode() === 1062 || stripos($e->getMessage(), 'Duplicate entry') !== false) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ya calificaste este pedido']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo registrar la calificación']);
} catch (Throwable $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo registrar la calificación']);
}
