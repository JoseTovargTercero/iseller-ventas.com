<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');
require_once(__DIR__ . '/transportista_negocio.php');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Sin sesión']);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$conexion->set_charset('utf8mb4');
header('Content-Type: application/json');

if ($action === 'listar') {
    echo json_encode(['ok' => true, 'data' => transportista_negocio_listar($conexion, $bss_id)]);
    exit;
}

if ($action === 'agregar') {
    $cedula = preg_replace('/\D/', '', (string)($_POST['cedula'] ?? ''));

    if ($cedula === '' || strlen($cedula) < 5) {
        echo json_encode(['ok' => false, 'msg' => 'Escribe una cédula válida.']);
        exit;
    }

    if (!transportista_negocio_tabla_existe($conexion)) {
        echo json_encode(['ok' => false, 'msg' => 'Todavía no hay transportistas registrados en la app.']);
        exit;
    }

    $stmt = $conexion->prepare(
        "SELECT id, nombre, cedula, activo, deliverys
           FROM transportistas
          WHERE cedula = ?
          LIMIT 1"
    );
    $stmt->bind_param('s', $cedula);
    $stmt->execute();
    $transportista = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$transportista) {
        echo json_encode(['ok' => false, 'msg' => 'No existe un transportista con esa cédula. Debe registrarse primero en la app.']);
        exit;
    }
    if (!(int)$transportista['activo']) {
        echo json_encode(['ok' => false, 'msg' => 'Ese transportista está inactivo.']);
        exit;
    }
    if (!(int)$transportista['deliverys']) {
        echo json_encode(['ok' => false, 'msg' => 'Ese transportista no está disponible para deliveries.']);
        exit;
    }

    transportista_negocio_asegurar($conexion);
    $stmt = $conexion->prepare(
        "INSERT INTO transportista_por_negocio (bss_id, transportista_id)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE bss_id = VALUES(bss_id)"
    );
    $stmt->bind_param('ii', $bss_id, $transportista['id']);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'msg' => 'Transportista agregado a tu tienda.',
        'transportista' => [
            'id' => (int)$transportista['id'],
            'nombre' => $transportista['nombre'],
            'cedula' => $transportista['cedula'],
        ],
    ]);
    exit;
}

if ($action === 'quitar') {
    $transportistaId = (int)($_POST['transportista_id'] ?? 0);
    if ($transportistaId <= 0) {
        echo json_encode(['ok' => false, 'msg' => 'Transportista inválido.']);
        exit;
    }

    transportista_negocio_asegurar($conexion);
    $stmt = $conexion->prepare(
        "DELETE FROM transportista_por_negocio WHERE bss_id = ? AND transportista_id = ?"
    );
    $stmt->bind_param('ii', $bss_id, $transportistaId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['ok' => true, 'msg' => 'Transportista quitado de tu tienda.']);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'msg' => 'Acción no válida.']);
