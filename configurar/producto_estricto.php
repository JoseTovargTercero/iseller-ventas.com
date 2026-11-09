<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit;
}

$bssId = (int) $_SESSION['bss_id'];
$nivel = (int) ($_SESSION['nivel'] ?? 2);
if ($nivel !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solo el administrador puede cambiar el seguimiento estricto.']);
    exit;
}

$estricto = (int) ($_POST['estricto'] ?? 0) === 1 ? 1 : 0;
$productoIdsRaw = $_POST['producto_ids'] ?? null;

// Acción masiva: "marcar todos" desde el encabezado de la tabla.
if ($productoIdsRaw !== null) {
    $ids = json_decode((string) $productoIdsRaw, true);
    if (!is_array($ids)) {
        $ids = explode(',', (string) $productoIdsRaw);
    }
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
        return $id > 0;
    })));

    if (empty($ids) || count($ids) > 5000) {
        echo json_encode(['success' => false, 'message' => 'Lista de productos no válida.']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("UPDATE productos SET seguimiento_estricto = ? WHERE id = ? AND bss_id = ?");
        if (!$stmt) {
            throw new Exception('Error de base de datos.');
        }
        foreach ($ids as $id) {
            $stmt->bind_param('iii', $estricto, $id, $bssId);
            if (!$stmt->execute()) {
                throw new Exception('No se pudo actualizar un producto.');
            }
        }
        $stmt->close();
        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    $cantidad = count($ids);
    echo json_encode([
        'success' => true,
        'message' => 'Seguimiento estricto ' . ($estricto ? 'activado' : 'desactivado') . ' en ' . $cantidad . ' producto' . ($cantidad === 1 ? '' : 's') . '.',
        'estricto' => $estricto,
        'total' => $cantidad
    ]);
    exit;
}

$productoId = (int) ($_POST['producto_id'] ?? 0);

if ($productoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no válido.']);
    exit;
}

$stmt = $conexion->prepare("UPDATE productos SET seguimiento_estricto = ? WHERE id = ? AND bss_id = ?");
$stmt->bind_param('iii', $estricto, $productoId, $bssId);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => $estricto ? 'Seguimiento estricto activado.' : 'Seguimiento estricto desactivado.', 'estricto' => $estricto]);
