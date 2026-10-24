<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json; charset=utf-8');

$bss_id = (int) $bss_id;
$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada)) $entrada = [];

// Categorías válidas para el negocio (globales + propias)
$validas = [];
$res = $conexion->query("SELECT id, nombre FROM categorias_producto WHERE bss_id = 0 OR bss_id = $bss_id");
while ($r = $res->fetch_assoc()) {
    $validas[(int) $r['id']] = $r['nombre'];
}

// Aplicar los valores elegidos en el front a los productos visibles de la página
if (!empty($entrada['items']) && is_array($entrada['items'])) {
    $upd = $conexion->prepare("UPDATE productos SET categoria = ? WHERE id = ? AND bss_id = ?");
    $n = 0;
    $omitidos = 0;
    foreach (array_slice($entrada['items'], 0, 200) as $item) {
        $id = (int) ($item['id'] ?? 0);
        $cat = (int) ($item['categoria'] ?? 0);
        if ($id <= 0 || !isset($validas[$cat])) {
            $omitidos++;
            continue;
        }
        $upd->bind_param('iii', $cat, $id, $bss_id);
        if ($upd->execute()) {
            $n++;
        } else {
            $omitidos++;
        }
    }
    echo json_encode(['ok' => true, 'procesados' => $n, 'omitidos' => $omitidos, 'bss_id' => $bss_id]);
    exit;
}

$id = (int) ($entrada['id'] ?? 0);
$cat = (int) ($entrada['categoria'] ?? 0);
if ($id <= 0 || !isset($validas[$cat])) {
    echo json_encode(['ok' => false, 'message' => 'Producto o categoría inválidos', 'bss_id' => $bss_id]);
    exit;
}

$stmt = $conexion->prepare("UPDATE productos SET categoria = ? WHERE id = ? AND bss_id = ?");
$stmt->bind_param('iii', $cat, $id, $bss_id);
if (!$stmt->execute()) {
    echo json_encode(['ok' => false, 'message' => 'No se pudo ejecutar el UPDATE: ' . $conexion->error, 'bss_id' => $bss_id]);
    exit;
}

// Confirmar leyendo de vuelta: sin esto un UPDATE que no toca filas parecería exitoso.
$verif = $conexion->prepare("SELECT categoria FROM productos WHERE id = ? AND bss_id = ?");
$verif->bind_param('ii', $id, $bss_id);
$verif->execute();
$fila = $verif->get_result()->fetch_assoc();

if ($fila === null) {
    echo json_encode([
        'ok' => false,
        'message' => "El producto #$id no existe en el negocio #$bss_id. Revisa que la sesión sea del negocio correcto.",
        'bss_id' => $bss_id,
    ]);
    exit;
}
if ((int) $fila['categoria'] !== $cat) {
    echo json_encode([
        'ok' => false,
        'message' => "La base no confirmó el cambio (guardó " . var_export($fila['categoria'], true) . ").",
        'bss_id' => $bss_id,
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'id' => $id,
    'categoria' => $cat,
    'nombre' => $validas[$cat],
    'bss_id' => $bss_id,
]);
