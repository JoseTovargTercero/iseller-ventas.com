<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');

$bss_id      = (int) $_SESSION['bss_id'];
$usuario_id  = (int) $_SESSION['id'];
$id_sucursal = (int) $_SESSION['sucursal'];

if (!$bss_id || !$usuario_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Sesión no válida']);
    exit;
}

$accion = trim($_POST['accion'] ?? '');

switch ($accion) {

    case 'listar':
        $nivel = (int) $_SESSION['nivel'];
        if ($nivel == 1 && isset($_POST['id_sucursal'])) {
            $id_sucursal = (int) $_POST['id_sucursal'];
        }

        $where = "e.bss_id = $bss_id";
        if ($id_sucursal > 0) {
            $id_sucursal_esc = $conexion->real_escape_string($id_sucursal);
            $where .= " AND e.id_sucursal = $id_sucursal_esc";
        }

        $sql = "SELECT e.id, e.nombre, e.rol, e.tipo_pago, e.monto_pago, e.moneda,
                       e.dia_ejecucion, e.recurrente_id, e.activo, e.observacion,
                       e.id_sucursal, e.created_at,
                       s.nombre AS sucursal_nombre
                FROM empleados e
                LEFT JOIN sucursales s ON e.id_sucursal = s.id
                WHERE $where
                ORDER BY e.activo DESC, e.nombre ASC";

        $result = $conexion->query($sql);
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }

        echo json_encode(['status' => 'ok', 'data' => $items]);
        break;

    case 'crear':
        $nivel = (int) $_SESSION['nivel'];
        if ($nivel == 1 && isset($_POST['id_sucursal'])) {
            $id_sucursal = (int) $_POST['id_sucursal'];
        }

        $nombre     = trim(strip_tags($_POST['nombre'] ?? ''));
        $rol        = trim(strip_tags($_POST['rol'] ?? ''));
        $tipo_pago  = in_array($_POST['tipo_pago'] ?? '', ['SEMANAL', 'MENSUAL']) ? $_POST['tipo_pago'] : null;
        $monto_pago = floatval($_POST['monto_pago'] ?? 0);
        $moneda     = 'USD';
        $dia_ej     = trim($_POST['dia_ejecucion'] ?? '');
        $obs        = trim(strip_tags($_POST['observacion'] ?? ''));

        if (!$nombre || !$rol || !$tipo_pago || $monto_pago <= 0 || !$dia_ej) {
            echo json_encode(['status' => 'error', 'msg' => 'Campos obligatorios incompletos']);
            exit;
        }

        // Verificar/crear categoría "Personal"
        $cat_id = null;
        $stmt = $conexion->prepare("SELECT id FROM gastos_categorias WHERE nombre='Personal' AND bss_id=?");
        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $cat_id = $row['id'];
        } else {
            $stmt = $conexion->prepare("INSERT INTO gastos_categorias (nombre, bss_id) VALUES ('Personal', ?)");
            $stmt->bind_param('i', $bss_id);
            $stmt->execute();
            $cat_id = $stmt->insert_id;
            $stmt->close();
        }

        // INSERT en gastos_recurrentes
        $concepto = 'Pago: ' . $nombre;
        $stmt = $conexion->prepare(
            "INSERT INTO gastos_recurrentes
             (concepto, categoria_id, tipo, frecuencia, monto_estimado, moneda,
              dia_ejecucion, fecha_inicio, activo, observacion, usuario_id, id_sucursal, bss_id)
             VALUES (?, ?, 'FIJO', ?, ?, ?, ?, CURDATE(), 1, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sisssssiii',
            $concepto, $cat_id, $tipo_pago, $monto_pago, $moneda,
            $dia_ej, $obs, $usuario_id, $id_sucursal, $bss_id
        );
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'msg' => 'Error al crear regla recurrente']);
            $stmt->close();
            exit;
        }
        $recurrente_id = $stmt->insert_id;
        $stmt->close();

        // INSERT en empleados
        $stmt = $conexion->prepare(
            "INSERT INTO empleados
             (nombre, rol, tipo_pago, monto_pago, moneda, dia_ejecucion,
              recurrente_id, activo, observacion, usuario_id, id_sucursal, bss_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssssisiii',
            $nombre, $rol, $tipo_pago, $monto_pago, $moneda,
            $dia_ej, $recurrente_id, $obs, $usuario_id, $id_sucursal, $bss_id
        );
        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'id' => $stmt->insert_id, 'msg' => 'Empleado registrado']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al registrar empleado']);
        }
        $stmt->close();
        break;

    case 'editar':
        $nivel = (int) $_SESSION['nivel'];
        if ($nivel == 1 && isset($_POST['id_sucursal'])) {
            $id_sucursal = (int) $_POST['id_sucursal'];
        }

        $id         = intval($_POST['id'] ?? 0);
        $nombre     = trim(strip_tags($_POST['nombre'] ?? ''));
        $rol        = trim(strip_tags($_POST['rol'] ?? ''));
        $tipo_pago  = in_array($_POST['tipo_pago'] ?? '', ['SEMANAL', 'MENSUAL']) ? $_POST['tipo_pago'] : null;
        $monto_pago = floatval($_POST['monto_pago'] ?? 0);
        $moneda     = 'USD';
        $dia_ej     = trim($_POST['dia_ejecucion'] ?? '');
        $obs        = trim(strip_tags($_POST['observacion'] ?? ''));

        if (!$id || !$nombre || !$rol || !$tipo_pago || $monto_pago <= 0 || !$dia_ej) {
            echo json_encode(['status' => 'error', 'msg' => 'Campos obligatorios incompletos']);
            exit;
        }

        // Verificar que el empleado pertenece a este bss_id
        $stmt = $conexion->prepare("SELECT recurrente_id FROM empleados WHERE id=? AND bss_id=?");
        $stmt->bind_param('ii', $id, $bss_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['status' => 'error', 'msg' => 'Empleado no encontrado']);
            exit;
        }

        // UPDATE empleados
        $stmt = $conexion->prepare(
            "UPDATE empleados SET nombre=?, rol=?, tipo_pago=?, monto_pago=?, moneda=?,
             dia_ejecucion=?, observacion=? WHERE id=? AND bss_id=?"
        );
        $stmt->bind_param('sssssssii', $nombre, $rol, $tipo_pago, $monto_pago, $moneda, $dia_ej, $obs, $id, $bss_id);
        $stmt->execute();
        $stmt->close();

        // UPDATE gastos_recurrentes asociado
        if ($row['recurrente_id']) {
            $concepto = 'Pago: ' . $nombre;
            $stmt = $conexion->prepare(
                "UPDATE gastos_recurrentes SET concepto=?, frecuencia=?, monto_estimado=?, moneda=?,
                 dia_ejecucion=? WHERE id=? AND bss_id=?"
            );
            $stmt->bind_param('sssssii', $concepto, $tipo_pago, $monto_pago, $moneda, $dia_ej, $row['recurrente_id'], $bss_id);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode(['status' => 'ok', 'msg' => 'Empleado actualizado']);
        break;

    case 'desactivar':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'msg' => 'ID requerido']);
            exit;
        }

        $stmt = $conexion->prepare("SELECT recurrente_id FROM empleados WHERE id=? AND bss_id=?");
        $stmt->bind_param('ii', $id, $bss_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['status' => 'error', 'msg' => 'Empleado no encontrado']);
            exit;
        }

        $stmt = $conexion->prepare("UPDATE empleados SET activo=0 WHERE id=? AND bss_id=?");
        $stmt->bind_param('ii', $id, $bss_id);
        $stmt->execute();
        $stmt->close();

        if ($row['recurrente_id']) {
            $stmt = $conexion->prepare("UPDATE gastos_recurrentes SET activo=0 WHERE id=? AND bss_id=?");
            $stmt->bind_param('ii', $row['recurrente_id'], $bss_id);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode(['status' => 'ok', 'msg' => 'Empleado desactivado']);
        break;

    case 'activar':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'msg' => 'ID requerido']);
            exit;
        }

        $stmt = $conexion->prepare("SELECT recurrente_id FROM empleados WHERE id=? AND bss_id=?");
        $stmt->bind_param('ii', $id, $bss_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['status' => 'error', 'msg' => 'Empleado no encontrado']);
            exit;
        }

        $stmt = $conexion->prepare("UPDATE empleados SET activo=1 WHERE id=? AND bss_id=?");
        $stmt->bind_param('ii', $id, $bss_id);
        $stmt->execute();
        $stmt->close();

        if ($row['recurrente_id']) {
            $stmt = $conexion->prepare("UPDATE gastos_recurrentes SET activo=1 WHERE id=? AND bss_id=?");
            $stmt->bind_param('ii', $row['recurrente_id'], $bss_id);
            $stmt->execute();
            $stmt->close();
        }

        echo json_encode(['status' => 'ok', 'msg' => 'Empleado activado']);
        break;

    case 'eliminar':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'msg' => 'ID requerido']);
            exit;
        }

        $stmt = $conexion->prepare("SELECT recurrente_id FROM empleados WHERE id=? AND bss_id=? AND activo=0");
        $stmt->bind_param('ii', $id, $bss_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['status' => 'error', 'msg' => 'Empleado no encontrado o aún está activo']);
            exit;
        }

        if ($row['recurrente_id']) {
            $stmt = $conexion->prepare("DELETE FROM gastos_recurrentes WHERE id=? AND bss_id=?");
            $stmt->bind_param('ii', $row['recurrente_id'], $bss_id);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conexion->prepare("DELETE FROM empleados WHERE id=? AND bss_id=?");
        $stmt->bind_param('ii', $id, $bss_id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'ok', 'msg' => 'Empleado eliminado']);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}
