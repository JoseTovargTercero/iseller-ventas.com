<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin sesión']);
    exit;
}

$bss_id  = $_SESSION['bss_id'];
$userId  = $_SESSION['id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

require_once(__DIR__ . '/../includes/notificaciones.php');

switch ($action) {

    case 'listar':
        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
        $rows = Notificaciones::paraUsuario($conexion, $userId, $limite);
        $count = Notificaciones::noLeidas($conexion, $userId);
        echo json_encode(['ok' => true, 'no_leidas' => $count, 'data' => $rows]);
        break;

    case 'no_leidas':
        $count = Notificaciones::noLeidas($conexion, $userId);
        echo json_encode(['ok' => true, 'no_leidas' => $count]);
        break;

    case 'marcar_leida':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
            break;
        }
        $ok = Notificaciones::marcarLeida($conexion, $id);
        echo json_encode(['ok' => $ok]);
        break;

    case 'marcar_todas':
        $ok = Notificaciones::marcarTodasLeidas($conexion, $userId);
        echo json_encode(['ok' => $ok]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
        break;
}
