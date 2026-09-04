<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');

$bss_id      = (int) $_SESSION['bss_id'];
$usuario_id  = (int) $_SESSION['id'];

if (!$bss_id || !$usuario_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Sesión no válida']);
    exit;
}

$accion = trim($_POST['accion'] ?? '');

switch ($accion) {

    case 'listar':
        $stmt = $conexion->prepare(
            "SELECT id, nombre, padre_id, activo
             FROM gastos_categorias
             WHERE bss_id = ?
             ORDER BY activo DESC, nombre ASC"
        );
        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $categorias = [];
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
        $stmt->close();
        echo json_encode(['status' => 'ok', 'data' => $categorias]);
        break;

    case 'crear':
        $nombre   = trim(strip_tags($_POST['nombre'] ?? ''));
        $padre_id = intval($_POST['padre_id'] ?? 0) ?: null;

        if (empty($nombre)) {
            echo json_encode(['status' => 'error', 'msg' => 'El nombre es obligatorio']);
            exit;
        }

        if (strlen($nombre) > 100) {
            echo json_encode(['status' => 'error', 'msg' => 'El nombre no puede exceder 100 caracteres']);
            exit;
        }

        if ($padre_id !== null) {
            $check = $conexion->prepare("SELECT id FROM gastos_categorias WHERE id=? AND bss_id=?");
            $check->bind_param('ii', $padre_id, $bss_id);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $check->close();
                echo json_encode(['status' => 'error', 'msg' => 'La categoría padre no existe']);
                exit;
            }
            $check->close();
        }

        $stmt = $conexion->prepare("INSERT INTO gastos_categorias (nombre, padre_id, bss_id) VALUES (?,?,?)");
        $stmt->bind_param('sii', $nombre, $padre_id, $bss_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'msg' => 'Categoría creada', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al crear categoría']);
        }
        $stmt->close();
        break;

    case 'editar':
        $id     = intval($_POST['id'] ?? 0);
        $nombre = trim(strip_tags($_POST['nombre'] ?? ''));

        if ($id <= 0 || empty($nombre)) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        $stmt = $conexion->prepare(
            "UPDATE gastos_categorias SET nombre=? WHERE id=? AND bss_id=?"
        );
        $stmt->bind_param('sii', $nombre, $id, $bss_id);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            echo json_encode(['status' => 'ok', 'msg' => 'Categoría actualizada']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar']);
        }
        $stmt->close();
        break;

    case 'desactivar':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
            exit;
        }

        $stmt = $conexion->prepare(
            "UPDATE gastos_categorias SET activo=0 WHERE id=? AND bss_id=?"
        );
        $stmt->bind_param('ii', $id, $bss_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'msg' => 'Categoría desactivada']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al desactivar']);
        }
        $stmt->close();
        break;

    case 'activar':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
            exit;
        }

        $stmt = $conexion->prepare(
            "UPDATE gastos_categorias SET activo=1 WHERE id=? AND bss_id=?"
        );
        $stmt->bind_param('ii', $id, $bss_id);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'msg' => 'Categoría activada']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al activar']);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}
