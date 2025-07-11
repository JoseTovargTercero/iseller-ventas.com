<?php
require_once 'configuracion.php';
require_once 'session.php';
require_once '_tasas_cambio.php';
require("_calculadrora_precios.php");
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);


header('Content-Type: application/json; charset=utf-8');

$cliente = $_POST['cliente'] ?? null;
if (!$cliente) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

/* ─────────────────────────────────────  FUNCIONES  ───────────────────────────────────── */
function datosProductos($producto)
{
    global $conexion, $pesoDolar, $peso_bolivar, $dolarBolivar, $calculadora;

    $sql = "SELECT * FROM productos WHERE id = ? AND activo = 0 LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    $precios = $calculadora->calcularPrecios($row);


    $cantidadUnidad     = $row['cantidad_unidades'];
    $origen             = $row['origen'];
    $precioDolarCompra  = $row['precio_compra'] / $cantidadUnidad;
    $precioDolarVenta   = round($precioDolarCompra * (1 + $row['porcentaje'] / 100), 2);
    $precioPesoVenta    = formatPesoVista($precioDolarVenta * $pesoDolar);

    $precioBsVenta = $origen === 'c'
        ? ($precioPesoVenta / $peso_bolivar) / 1000
        : $precioDolarVenta * $dolarBolivar;

    $nombre = strtoupper(preg_replace('/[^A-Za-z0-9\s]/', '', $row['nombre']));

    return [
        'id'                   => (int)$row['id'],
        'stock'                => (int)$row['stock'],
        'nombre'               => $nombre,
        'precio_dolar_visible' => (float)$precios['precio_venta_dolar'],
        'precio_peso_visible'  => (float)$precios['precio_venta_peso'],
        'precio_bs_visible'    => (float)$precios['precio_venta_bs'],
        'codigo'               => $row['codigo'],
        'origen'               => $row['origen'],
    ];
}

function getProductos($orderId)
{
    global $conexion;
    $stmt = $conexion->prepare(
        "SELECT product_id, quantity
           FROM orden_articulos
          WHERE order_id = ?"
    );
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $res = $stmt->get_result();

    $productos = [];
    while ($row = $res->fetch_assoc()) {
        $datos = datosProductos($row['product_id']);
        if (empty($datos)) continue;

        $productos[] = [
            'id'       => (int)$row['product_id'],
            'cantidad' => (int)$row['quantity'],
            'datos'    => $datos,
        ];
    }
    $stmt->close();
    return $productos;
}
/* ─────────────────────────────────────  QUERY PRINCIPAL  ───────────────────────────────────── */
$sql = "SELECT creditos.tipoCompra,
               creditos.id            AS id_credito,
               creditos.order_id,
               orden.created,
               orden.total_price
          FROM creditos
     LEFT JOIN orden ON orden.id = creditos.order_id
         WHERE estado = '2' AND negocio = ?
      ORDER BY negocio DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $cliente);
$stmt->execute();
$res = $stmt->get_result();

$payload = [
    'ordenes'         => [],
    'totales_global'  => ['usd' => 0, 'cop' => 0, 'bs' => 0],
];
$totales = ['usd' => 0, 'cop' => 0, 'bs' => 0];

while ($row = $res->fetch_assoc()) {
    $productos = getProductos($row['order_id']);

    foreach ($productos as $p) {
        $totales['usd'] += $p['datos']['precio_dolar_visible'] * $p['cantidad'];
        $totales['cop'] += $p['datos']['precio_peso_visible']  * $p['cantidad'];
        $totales['bs']  += $p['datos']['precio_bs_visible']    * $p['cantidad'];
    }

    $payload['totales_global']['usd'] += $totales['usd'];
    $payload['totales_global']['cop'] += $totales['cop'];
    $payload['totales_global']['bs']  += $totales['bs'];

    $payload['ordenes'][] = [
        'id'          => $row['order_id'],
        'id_credito'  => $row['id_credito'],
        'tipoCompra'  => $row['tipoCompra'],
        'fecha'       => $row['created'],
        'total'       => (float)$row['total_price'],
        'totales'     => $totales,
        'productos'   => $productos,
    ];
}

$stmt->close();
echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
