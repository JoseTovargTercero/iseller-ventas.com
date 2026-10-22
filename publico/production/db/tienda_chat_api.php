<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin sesión']);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$conexion->set_charset('utf8mb4'); // los mensajes de chat son utf8mb4 (emojis)

header('Content-Type: application/json');

// Una conversación sólo puede verla/tocarla la tienda dueña.
function chat_conv_de_tienda(mysqli $conexion, int $conv_id, int $bss_id): bool
{
    $stmt = $conexion->prepare("SELECT id FROM tienda_chat_conversaciones WHERE id = ? AND bss_id = ? LIMIT 1");
    $stmt->bind_param('ii', $conv_id, $bss_id);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

switch ($action) {

    case 'listar':
        $estado   = $_GET['estado'] ?? null;
        $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : null;
        $limite   = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;

        $sql = "SELECT c.id, c.usuario_id, c.asunto, c.estado, c.creada_en, c.ultimo_mensaje_en, c.pedido_id,
                       u.nombre AS usuario_nombre, u.email AS usuario_email,
                       (SELECT COUNT(*) FROM tienda_chat_mensajes WHERE conversacion_id = c.id AND es_admin = 0 AND leido = 0) AS mensajes_sin_leer,
                       (SELECT mensaje FROM tienda_chat_mensajes WHERE conversacion_id = c.id ORDER BY creado_en DESC LIMIT 1) AS ultimo_mensaje,
                       (SELECT COUNT(*) FROM tienda_chat_mensajes WHERE conversacion_id = c.id) AS total_mensajes
                FROM tienda_chat_conversaciones c
                LEFT JOIN tienda_usuarios u ON c.usuario_id = u.id
                WHERE c.bss_id = ?";

        $params = [$bss_id];
        $types  = 'i';

        if ($estado) {
            $sql .= " AND c.estado = ?";
            $params[] = $estado;
            $types .= 's';
        }
        if ($busqueda) {
            $sql .= " AND (c.asunto LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
            $like = "%$busqueda%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'sss';
        }

        $sql .= " ORDER BY CASE WHEN c.estado = 'abierta' THEN 0 ELSE 1 END, c.ultimo_mensaje_en DESC LIMIT ?";
        $params[] = $limite;
        $types .= 'i';

        $stmt = $conexion->prepare($sql);
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Stats
        $stmtStats = $conexion->prepare("SELECT
            SUM(CASE WHEN estado='abierta' THEN 1 ELSE 0 END) AS abiertas,
            SUM(CASE WHEN estado='resuelta' THEN 1 ELSE 0 END) AS resueltas,
            SUM(CASE WHEN estado='cerrada' THEN 1 ELSE 0 END) AS cerradas
            FROM tienda_chat_conversaciones WHERE bss_id = ?");
        $stmtStats->bind_param('i', $bss_id);
        $stmtStats->execute();
        $stats = $stmtStats->get_result()->fetch_assoc();
        $stmtStats->close();

        echo json_encode([
            'ok' => true,
            'conversaciones' => $rows,
            'estadisticas' => [
                'abiertas'  => (int)($stats['abiertas'] ?? 0),
                'resueltas' => (int)($stats['resueltas'] ?? 0),
                'cerradas'  => (int)($stats['cerradas'] ?? 0),
            ]
        ]);
        break;

    case 'mensajes':
        $conv_id = (int)($_GET['conversacion_id'] ?? 0);
        if ($conv_id <= 0 || !chat_conv_de_tienda($conexion, $conv_id, $bss_id)) {
            echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
            break;
        }

        $stmt = $conexion->prepare("SELECT id, es_admin, admin_id, mensaje, leido, leido_en, creado_en
            FROM tienda_chat_mensajes WHERE conversacion_id = ? ORDER BY creado_en ASC");
        $stmt->bind_param('i', $conv_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Marcar no leídos del usuario como leídos
        $unread = [];
        foreach ($rows as $r) {
            if (!$r['es_admin'] && !$r['leido']) $unread[] = (int)$r['id'];
        }
        if (!empty($unread)) {
            $ids = implode(',', $unread);
            $conexion->query("UPDATE tienda_chat_mensajes SET leido=1, leido_en=NOW() WHERE id IN ($ids)");
        }

        echo json_encode(['ok' => true, 'mensajes' => $rows]);
        break;

    case 'responder':
        $input = json_decode(file_get_contents('php://input'), true);
        $conv_id = (int)($input['conversacion_id'] ?? 0);
        $mensaje = trim($input['mensaje'] ?? '');

        if ($conv_id <= 0 || $mensaje === '') {
            echo json_encode(['ok' => false, 'msg' => 'Faltan campos']);
            break;
        }

        // Verificar conversación y obtener datos del cliente
        $stmt = $conexion->prepare("SELECT id, usuario_id, asunto FROM tienda_chat_conversaciones WHERE id = ? AND bss_id = ?");
        $stmt->bind_param('ii', $conv_id, $bss_id);
        $stmt->execute();
        $conv = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$conv) {
            echo json_encode(['ok' => false, 'msg' => 'Conversación no encontrada']);
            break;
        }

        $conexion->begin_transaction();

        $stmt = $conexion->prepare("INSERT INTO tienda_chat_mensajes (conversacion_id, es_admin, mensaje, leido) VALUES (?, 1, ?, 0)");
        $stmt->bind_param('is', $conv_id, $mensaje);
        $stmt->execute();
        $msg_id = $conexion->insert_id;
        $stmt->close();

        $conexion->query("UPDATE tienda_chat_conversaciones SET ultimo_mensaje_en=NOW() WHERE id=$conv_id");

        $conexion->commit();

        // Notificar al cliente
        if ((int)$conv['usuario_id'] > 0) {
            $notifFile = $_SERVER['DOCUMENT_ROOT'] . '/iseller-tiendas.com/iseller_store/api/chat/crear_notificacion_cliente.php';
            if (file_exists($notifFile)) {
                require_once($notifFile);
                notificar_cliente(
                    $conexion,
                    (int)$conv['usuario_id'],
                    'Nueva respuesta en chat',
                    "Respondieron a: " . ($conv['asunto'] ?: 'Tu consulta'),
                    'chat',
                    'chat.php'
                );
            }
        }

        echo json_encode(['ok' => true, 'mensaje_id' => $msg_id]);
        break;

    case 'cambiar_estado':
        $input = json_decode(file_get_contents('php://input'), true);
        $conv_id = (int)($input['conversacion_id'] ?? 0);
        $estado  = $input['estado'] ?? '';

        $valid = ['abierta', 'cerrada', 'resuelta'];
        if ($conv_id <= 0 || !in_array($estado, $valid)) {
            echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos']);
            break;
        }
        if (!chat_conv_de_tienda($conexion, $conv_id, $bss_id)) {
            echo json_encode(['ok' => false, 'msg' => 'Conversación no encontrada']);
            break;
        }

        $stmt = $conexion->prepare("UPDATE tienda_chat_conversaciones SET estado = ? WHERE id = ? AND bss_id = ?");
        $stmt->bind_param('sii', $estado, $conv_id, $bss_id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['ok' => $ok]);
        break;

    case 'poll':
        $conv_id    = (int)($_GET['conversacion_id'] ?? 0);
        $ultimo_id  = (int)($_GET['ultimo_mensaje_id'] ?? 0);
        $timeout    = 20;
        $start      = time();

        if ($conv_id <= 0 || !chat_conv_de_tienda($conexion, $conv_id, $bss_id)) {
            echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
            break;
        }

        session_write_close();

        while ((time() - $start) < $timeout) {
            $stmt = $conexion->prepare("SELECT id, es_admin, mensaje, leido, creado_en
                FROM tienda_chat_mensajes WHERE conversacion_id = ? AND id > ? ORDER BY creado_en ASC");
            $stmt->bind_param('ii', $conv_id, $ultimo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                $msgs = [];
                $unread = [];
                while ($row = $result->fetch_assoc()) {
                    $msgs[] = $row;
                    if (!$row['es_admin'] && !$row['leido']) $unread[] = (int)$row['id'];
                }
                if (!empty($unread)) {
                    $ids = implode(',', $unread);
                    $conexion->query("UPDATE tienda_chat_mensajes SET leido=1, leido_en=NOW() WHERE id IN ($ids)");
                }
                echo json_encode(['ok' => true, 'nuevos_mensajes' => true, 'mensajes' => $msgs]);
                exit;
            }
            sleep(1);
        }

        echo json_encode(['ok' => true, 'nuevos_mensajes' => false, 'mensajes' => []]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
        break;
}
