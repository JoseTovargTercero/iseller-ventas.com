<?php
require_once('../core/db.php');
require_once('../core/session.php');

header('Content-Type: application/json');

$usuario_id = getUserId();
if (!$usuario_id) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'listar';

switch ($action) {

    case 'listar':
        $limite = min((int)($_GET['limite'] ?? 20), 50);
        $stmt = $conexion_store->prepare(
            "SELECT id, titulo, cuerpo, tipo, enlace, leida, created_at
             FROM tienda_cliente_notificaciones
             WHERE usuario_id = ?
             ORDER BY created_at DESC
             LIMIT ?"
        );
        $stmt->bind_param("ii", $usuario_id, $limite);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'no_leidas':
        $stmt = $conexion_store->prepare(
            "SELECT COUNT(*) AS total
             FROM tienda_cliente_notificaciones
             WHERE usuario_id = ? AND leida = 0"
        );
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $total = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
        echo json_encode(['success' => true, 'total' => $total]);
        break;

    case 'marcar_leida':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            break;
        }
        $stmt = $conexion_store->prepare(
            "UPDATE tienda_cliente_notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?"
        );
        $stmt->bind_param("ii", $id, $usuario_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        break;

    case 'marcar_todas':
        $stmt = $conexion_store->prepare(
            "UPDATE tienda_cliente_notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0"
        );
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción desconocida']);
        break;
}
