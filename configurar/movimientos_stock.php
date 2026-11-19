<?php
/**
 * Listado de traslados de stock entre sucursales, con filtros por producto,
 * sucursal origen y sucursal destino.
 */
require_once(__DIR__ . '/configuracion.php');
require_once(__DIR__ . '/session.php');
require_once(__DIR__ . '/_stock_traslados.php');

header('Content-Type: application/json; charset=utf-8');

if (!in_array((int) ($_SESSION['nivel'] ?? 0), [1, 2], true)) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$productoId = (int) ($_GET['producto'] ?? 0);
$origen = (int) ($_GET['origen'] ?? 0);
$destino = (int) ($_GET['destino'] ?? 0);
if ($bssId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Negocio inválido']);
    exit;
}

if (!st_asegurar_tabla($conexion)) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo preparar la tabla de traslados.']);
    exit;
}

$sql = "SELECT T.created_at AS fecha, T.cantidad, T.usuario_nombre,
               COALESCE(so.nombre, '—') AS origen, COALESCE(sd.nombre, '—') AS destino
        FROM stock_traslados T
        LEFT JOIN sucursales so ON so.id = T.sucursal_origen AND so.bss_id = T.bss_id
        LEFT JOIN sucursales sd ON sd.id = T.sucursal_destino AND sd.bss_id = T.bss_id
        WHERE T.bss_id = $bssId";
if ($productoId > 0) {
    $sql .= " AND T.id_producto = $productoId";
}
if ($origen > 0) {
    $sql .= " AND T.sucursal_origen = $origen";
}
if ($destino > 0) {
    $sql .= " AND T.sucursal_destino = $destino";
}
$sql .= " ORDER BY T.created_at DESC LIMIT 100";

$movimientos = [];
$res = $conexion->query($sql);
if ($res) {
    while ($fila = $res->fetch_assoc()) {
        $movimientos[] = [
            'fecha' => (string) $fila['fecha'],
            'tipo' => 'TRASLADO',
            'cantidad' => round((float) $fila['cantidad'], 2),
            'origen' => (string) $fila['origen'],
            'destino' => (string) $fila['destino'],
            'usuario' => (string) $fila['usuario_nombre'],
        ];
    }
}

echo json_encode([
    'ok' => true,
    'movimientos' => $movimientos,
]);
