<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json; charset=utf-8');

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    http_response_code(403);
    echo json_encode(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'No autorizado']);
    exit;
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
if ($bssId <= 0) {
    http_response_code(400);
    echo json_encode(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Negocio inválido']);
    exit;
}

$draw = (int) ($_POST['draw'] ?? 1);
$start = max(0, (int) ($_POST['start'] ?? 0));
$length = (int) ($_POST['length'] ?? 25);
if ($length < 1 || $length > 200) {
    $length = 25;
}
$searchRaw = $_POST['search']['value'] ?? '';
$search = is_string($searchRaw) ? trim($searchRaw) : '';

// Solo se permiten columnas conocidas para ORDER BY.
$columnas = [
    0 => 'id',
    1 => 'cedula',
    2 => 'nombre',
    3 => 'telefono',
];
$orderColumn = (int) ($_POST['order'][0]['column'] ?? 0);
$orderBy = $columnas[$orderColumn] ?? 'id';
$orderDirRaw = $_POST['order'][0]['dir'] ?? 'DESC';
$orderDir = is_string($orderDirRaw) ? strtoupper($orderDirRaw) : 'DESC';
if ($orderDir !== 'ASC' && $orderDir !== 'DESC') {
    $orderDir = 'DESC';
}

try {
    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM clientes WHERE bss_id = ?");
    if (!$stmt) {
        throw new Exception('Error de base de datos.');
    }
    $stmt->bind_param("i", $bssId);
    $stmt->execute();
    $recordsTotal = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $where = " WHERE bss_id = ?";
    $params = [$bssId];
    $types = "i";

    if ($search !== '') {
        $where .= " AND (cedula LIKE ? OR nombre LIKE ? OR telefono LIKE ?)";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= "sss";
    }

    $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM clientes" . $where);
    if (!$stmt) {
        throw new Exception('Error de base de datos.');
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $recordsFiltered = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    $sql = "SELECT id, cedula, nombre, telefono FROM clientes" . $where
        . " ORDER BY `$orderBy` $orderDir LIMIT ? OFFSET ?";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error de base de datos.');
    }

    $dataParams = $params;
    $dataParams[] = $length;
    $dataParams[] = $start;
    $dataTypes = $types . "ii";
    $stmt->bind_param($dataTypes, ...$dataParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => (int) $row['id'],
            'cedula' => (string) $row['cedula'],
            'nombre' => (string) $row['nombre'],
            'telefono' => (string) $row['telefono'],
        ];
    }
    $stmt->close();

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data' => $data,
    ]);
} catch (Throwable $e) {
    error_log('clientes_list.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'No se pudieron cargar los clientes.',
    ]);
}
