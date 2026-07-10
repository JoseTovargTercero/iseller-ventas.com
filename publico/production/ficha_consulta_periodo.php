<?php
require_once('includes/requires.php');

header('Content-Type: application/json');

if ($_SESSION['nivel'] != 1 && $_SESSION['nivel'] != 2) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$idProducto = intval($_GET['id'] ?? 0);
$desde      = $_GET['desde'] ?? date('Y-m-01');
$hasta      = $_GET['hasta'] ?? date('Y-m-d');
$bss_id     = $_SESSION['bss_id'];

if (!$idProducto) {
    echo json_encode(['error' => 'Producto inválido']);
    exit;
}

// Sanitize dates
$desde = date('Y-m-d', strtotime($desde));
$hasta = date('Y-m-d', strtotime($hasta));

$stmt = $conexion->prepare(
    "SELECT id, nombre FROM sucursales WHERE bss_id = ?"
);
$stmt->bind_param('s', $bss_id);
$stmt->execute();
$sucursalesResult = $stmt->get_result();
$stmt->close();

$resultados = [];

while ($suc = $sucursalesResult->fetch_assoc()) {
    $sucId   = $suc['id'];
    $sucNom  = $suc['nombre'];

    // Get all orders within the date range for this branch
    $stmt2 = $conexion->prepare(
        "SELECT o.id, o.descontado, o.status
         FROM orden o
         WHERE o.id_sucursal = ?
           AND o.modified >= ?
           AND o.modified <= ?
           AND o.status IN (1, 4)"
    );
    $stmt2->bind_param('iss', $sucId, $desde, $hasta);
    $stmt2->execute();
    $ordenes = $stmt2->get_result();
    $stmt2->close();

    $cantidad   = 0;
    $totalVenta = 0.0;
    $totalCosto = 0.0;

    while ($orden = $ordenes->fetch_assoc()) {
        $ordId      = $orden['id'];
        $descuento  = floatval($orden['descontado']);
        $status     = $orden['status'];

        $stmt3 = $conexion->prepare(
            "SELECT precio, precio_venta_dolar, quantity
             FROM orden_articulos
             WHERE order_id = ? AND product_id = ?"
        );
        $stmt3->bind_param('ii', $ordId, $idProducto);
        $stmt3->execute();
        $articulos = $stmt3->get_result();
        $stmt3->close();

        while ($art = $articulos->fetch_assoc()) {
            $qty    = floatval($art['quantity']);
            $pvd    = floatval($art['precio_venta_dolar']);
            $costo  = floatval($art['precio']);
            $sub    = $pvd * $qty;

            if ($status == '4') {
                $sub = $sub - ($sub * $descuento / 100);
            }

            $cantidad   += $qty;
            $totalVenta += $sub;
            $totalCosto += $costo * $qty;
        }
    }

    $resultados[] = [
        'sucursal'   => $sucNom,
        'cantidad'   => $cantidad,
        'total'      => round($totalVenta, 2),
        'ganancia'   => round($totalVenta - $totalCosto, 2),
    ];
}

echo json_encode([
    'desde'      => $desde,
    'hasta'      => $hasta,
    'resultados' => $resultados,
]);
