<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida.']);
    exit;
}

$bssId = (int) $_SESSION['bss_id'];
$accion = (string) ($_POST['accion'] ?? $_GET['accion'] ?? '');
$paresValidos = ['usd_bs', 'usd_cop', 'cop_bs', 'bs_cop'];

function responder(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizarNombre($nombre): string
{
    $nombre = trim(strip_tags((string) $nombre));
    if (function_exists('mb_substr')) {
        return mb_substr($nombre, 0, 60, 'UTF-8');
    }
    return substr($nombre, 0, 60);
}

function normalizarValor($valor): ?float
{
    if (!is_numeric($valor)) {
        return null;
    }
    $valor = round((float) $valor, 6);
    return $valor > 0 ? $valor : null;
}

function tasaPorId($conexion, int $id, int $bssId): ?array
{
    $stmt = $conexion->prepare("SELECT id, par, nombre, valor FROM cambio_tasas_personalizadas WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ii', $id, $bssId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

if ($accion === 'listar') {
    $stmt = $conexion->prepare(
        "SELECT tp.id, tp.par, tp.nombre, tp.valor,
                (SELECT COUNT(*) FROM producto_tasas_personalizadas pt WHERE pt.tasa_id = tp.id AND pt.bss_id = tp.bss_id) AS uso
         FROM cambio_tasas_personalizadas tp
         WHERE tp.bss_id = ?
         ORDER BY FIELD(tp.par, 'usd_bs','usd_cop','cop_bs','bs_cop'), tp.nombre ASC"
    );
    if (!$stmt) {
        responder(['status' => 'error', 'message' => 'Error al consultar las tasas.']);
    }
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $res = $stmt->get_result();
    $tasas = [];
    while ($fila = $res->fetch_assoc()) {
        $fila['id'] = (int) $fila['id'];
        $fila['valor'] = (float) $fila['valor'];
        $fila['uso'] = (int) $fila['uso'];
        $tasas[] = $fila;
    }
    $stmt->close();
    responder(['status' => 'success', 'data' => $tasas]);
}

if ($accion === 'crear') {
    $par = (string) ($_POST['par'] ?? '');
    $nombre = normalizarNombre($_POST['nombre'] ?? '');
    $valor = normalizarValor($_POST['valor'] ?? null);

    if (!in_array($par, $paresValidos, true)) {
        responder(['status' => 'error', 'message' => 'Par de conversión no válido.']);
    }
    if ($nombre === '') {
        responder(['status' => 'error', 'message' => 'El nombre es obligatorio.']);
    }
    if ($valor === null) {
        responder(['status' => 'error', 'message' => 'El valor debe ser un número mayor que cero.']);
    }

    $check = $conexion->prepare("SELECT id FROM cambio_tasas_personalizadas WHERE bss_id = ? AND par = ? AND nombre = ? LIMIT 1");
    if ($check) {
        $check->bind_param('iss', $bssId, $par, $nombre);
        $check->execute();
        $existe = $check->get_result()->fetch_assoc();
        $check->close();
        if ($existe) {
            responder(['status' => 'error', 'message' => 'Ya existe una tasa con ese nombre para este par.']);
        }
    }

    $stmt = $conexion->prepare("INSERT INTO cambio_tasas_personalizadas (bss_id, par, nombre, valor) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        responder(['status' => 'error', 'message' => 'Error al preparar la consulta.']);
    }
    $stmt->bind_param('issd', $bssId, $par, $nombre, $valor);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        responder(['status' => 'error', 'message' => 'Error al guardar: ' . $error]);
    }
    $id = (int) $stmt->insert_id;
    $stmt->close();

    responder([
        'status' => 'success',
        'message' => 'Tasa personalizada creada.',
        'data' => ['id' => $id, 'par' => $par, 'nombre' => $nombre, 'valor' => $valor, 'uso' => 0]
    ]);
}

if ($accion === 'actualizar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nombre = normalizarNombre($_POST['nombre'] ?? '');
    $valor = normalizarValor($_POST['valor'] ?? null);

    if ($id <= 0) {
        responder(['status' => 'error', 'message' => 'Tasa no válida.']);
    }
    if ($nombre === '') {
        responder(['status' => 'error', 'message' => 'El nombre es obligatorio.']);
    }
    if ($valor === null) {
        responder(['status' => 'error', 'message' => 'El valor debe ser un número mayor que cero.']);
    }

    $tasa = tasaPorId($conexion, $id, $bssId);
    if (!$tasa) {
        responder(['status' => 'error', 'message' => 'Tasa no encontrada.']);
    }

    $check = $conexion->prepare("SELECT id FROM cambio_tasas_personalizadas WHERE bss_id = ? AND par = ? AND nombre = ? AND id <> ? LIMIT 1");
    if ($check) {
        $check->bind_param('issi', $bssId, $tasa['par'], $nombre, $id);
        $check->execute();
        $existe = $check->get_result()->fetch_assoc();
        $check->close();
        if ($existe) {
            responder(['status' => 'error', 'message' => 'Ya existe una tasa con ese nombre para este par.']);
        }
    }

    $stmt = $conexion->prepare("UPDATE cambio_tasas_personalizadas SET nombre = ?, valor = ? WHERE id = ? AND bss_id = ?");
    if (!$stmt) {
        responder(['status' => 'error', 'message' => 'Error al preparar la consulta.']);
    }
    $stmt->bind_param('sdii', $nombre, $valor, $id, $bssId);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        responder(['status' => 'error', 'message' => 'Error al actualizar: ' . $error]);
    }
    $stmt->close();

    responder([
        'status' => 'success',
        'message' => 'Tasa personalizada actualizada.',
        'data' => ['id' => $id, 'par' => $tasa['par'], 'nombre' => $nombre, 'valor' => $valor]
    ]);
}

if ($accion === 'eliminar') {
    $id = (int) ($_POST['id'] ?? 0);
    $forzar = (int) ($_POST['forzar'] ?? 0) === 1;

    if ($id <= 0) {
        responder(['status' => 'error', 'message' => 'Tasa no válida.']);
    }
    $tasa = tasaPorId($conexion, $id, $bssId);
    if (!$tasa) {
        responder(['status' => 'error', 'message' => 'Tasa no encontrada.']);
    }

    $stmt = $conexion->prepare("SELECT COUNT(*) AS uso FROM producto_tasas_personalizadas WHERE tasa_id = ? AND bss_id = ?");
    if (!$stmt) {
        responder(['status' => 'error', 'message' => 'Error al consultar las asignaciones.']);
    }
    $stmt->bind_param('ii', $id, $bssId);
    $stmt->execute();
    $uso = (int) ($stmt->get_result()->fetch_assoc()['uso'] ?? 0);
    $stmt->close();

    if ($uso > 0 && !$forzar) {
        responder([
            'status' => 'error',
            'message' => 'Esta tasa está asignada a ' . $uso . ' producto' . ($uso === 1 ? '' : 's') . '. Confirma para eliminarla y dejar esos pares en la tasa principal.',
            'uso' => $uso
        ]);
    }

    $conexion->begin_transaction();
    try {
        $delAsignaciones = $conexion->prepare("DELETE FROM producto_tasas_personalizadas WHERE tasa_id = ? AND bss_id = ?");
        if ($delAsignaciones) {
            $delAsignaciones->bind_param('ii', $id, $bssId);
            $delAsignaciones->execute();
            $delAsignaciones->close();
        }
        $del = $conexion->prepare("DELETE FROM cambio_tasas_personalizadas WHERE id = ? AND bss_id = ?");
        if (!$del) {
            throw new Exception('Error al preparar la eliminación.');
        }
        $del->bind_param('ii', $id, $bssId);
        if (!$del->execute()) {
            throw new Exception($del->error);
        }
        $del->close();
        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        responder(['status' => 'error', 'message' => 'No se pudo eliminar: ' . $e->getMessage()]);
    }

    responder(['status' => 'success', 'message' => 'Tasa personalizada eliminada.', 'uso' => $uso]);
}

responder(['status' => 'error', 'message' => 'Acción no válida.']);
