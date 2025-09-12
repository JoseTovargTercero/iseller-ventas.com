<?php

require_once 'configuracion.php';
require_once 'session.php';
require_once '_tasas_cambio.php';
require("_calculadrora_precios.php");

$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);
//header('Content-Type: application/json; charset=utf-8');

// Validar cliente
$cliente = $_POST['cliente'] ?? null;
if (!$cliente) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Decodificar arreglo de abonos si existe
//$abonos = [];


$abonos = [];
$stmt = $conexion->prepare("SELECT * FROM abonos WHERE cliente = ?");
$stmt->bind_param("s", $cliente);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $abonos[] = [
        "id"  => $row["id"],
        "monto"  => $row["monto"],
        "moneda" => $row["moneda"],
        "fecha"  => $row["fecha"]
    ];
}

$stmt->close();



/*if (!empty($_POST['abonos'])) {
    $json = $_POST['abonos'];
    $abonos = json_decode($json, true);
    if (!is_array($abonos)) $abonos = [];
}
*/






/*

echo 'Recibe 1$ y es Venezolano';
print_r($calculadora->convertirMonto(1, 'usd', 'v'));
echo "<br>";
echo 'Recibe 1$ y es Colombiano';
print_r($calculadora->convertirMonto(1, 'usd', 'c'));
echo "<br>";
echo 'Recibe 125 bs y es Venezolano';
print_r($calculadora->convertirMonto(125, 'bs', 'v'));
echo "<br>";
echo 'Recibe 125 bs y es Colombiano';
print_r($calculadora->convertirMonto(125, 'bs', 'c'));
echo "<br>";
echo 'Recibe 4000 pesos y es Venezolano';
print_r($calculadora->convertirMonto(4000, 'cop', 'v'));
echo "<br>";
echo 'Recibe 4000 pesos y es Colombiano';
print_r($calculadora->convertirMonto(4000, 'cop', 'c'));
echo "<br>";
*/




function datosProductos($producto)
{
    global $conexion, $pesoDolar, $peso_bolivar, $dolarBolivar, $calculadora;

    $sql = "SELECT * FROM productos WHERE id = ? AND activo = 0 LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return null;

    $precios = $calculadora->calcularPrecios($row);

    $cantidadUnidad = $row['cantidad_unidades'];
    $origen = $row['origen'];
    $precioDolarCompra = $row['precio_compra'] / $cantidadUnidad;
    $precioDolarVenta = round($precioDolarCompra * (1 + $row['porcentaje'] / 100), 2);
    $precioPesoVenta = formatPesoVista($precioDolarVenta * $pesoDolar);

    $precioBsVenta = $origen === 'c'
        ? ($precioPesoVenta / $peso_bolivar) / 1000
        : $precioDolarVenta * $dolarBolivar;

    $nombre = strtoupper(preg_replace('/[^A-Za-z0-9\s]/', '', $row['nombre']));

    return [
        'id' => (int)$row['id'],
        'stock' => (int)$row['stock'],
        'nombre' => $nombre,
        'precio_dolar_visible' => (float)$precios['precio_venta_dolar'],
        'precio_peso_visible' => (float)$precios['precio_venta_peso'],
        'precio_bs_visible' => (float)$precios['precio_venta_bs'],
        'codigo' => $row['codigo'],
        'origen' => $row['origen'],
    ];
}

function getProductos($orderId)
{
    global $conexion;
    $stmt = $conexion->prepare("SELECT product_id, quantity FROM orden_articulos WHERE order_id = ?");
    $stmt->bind_param('s', $orderId);
    $stmt->execute();
    $res = $stmt->get_result();

    $productos = [];
    while ($row = $res->fetch_assoc()) {
        $datos = datosProductos($row['product_id']);
        $datos['cantidad'] =    (float)$row['quantity']; // Agregar cantidad al array de datos 
        if (empty($datos)) continue;

        $productos[] = [
            'id' => (int)$row['product_id'],
            'cantidad' => (float)$row['quantity'],
            'datos' => $datos,
        ];
    }
    $stmt->close();
    return $productos;
}

function aplicarAbonos($productos, $abonos)
{
    global $calculadora;

    $aliasMonedas = [
        'DOLARES'   => 'usd',
        'PESOS'     => 'cop',
        'BOLIVARES' => 'bs',
        'USD'       => 'usd',
        'COP'       => 'cop',
        'BS'        => 'bs',
    ];

    // Convertimos abonos a arreglo por moneda
    $abonosDisponibles = ['usd' => 0, 'cop' => 0, 'bs' => 0];
    foreach ($abonos as $abono) {
        $moneda = strtoupper(trim($abono['moneda'] ?? ''));
        $clave = $aliasMonedas[$moneda] ?? null;
        if ($clave && isset($abono['monto']) && is_numeric($abono['monto'])) {
            $abonosDisponibles[$clave] += (float)$abono['monto'];
        }
    }

    $restantes = ['usd' => 0, 'cop' => 0, 'bs' => 0];

    // Aplicar abonos a cada producto
    foreach ($productos as &$producto) {

        $monedas = ['usd', 'cop', 'bs'];
        $pagadoTotal = false;

        foreach ($monedas as $moneda) {
            $clave = 'total_' . $moneda;
            if (!isset($producto[$clave]) || $producto[$clave] <= 0) continue; // SI LA MONEDA NO EXISTE O ES CERO, SALTAR

            $valorProducto = $producto[$clave]; // ya incluye la cantidad
            $abonoDisponible = $abonosDisponibles[$moneda];

            if ($abonoDisponible >= $valorProducto) {
                // Cubierto totalmente en esta moneda → poner todos los montos en 0
                $abonosDisponibles[$moneda] -= $valorProducto;
                // Actualiza el abono con el valor restante despues de cubrir el valor del producto

                foreach ($monedas as $m) {
                    $producto['total_' . $m] = 0;
                }

                $pagadoTotal = true;
                break; // Salimos del foreach monedas
            } elseif ($abonoDisponible > 0) {
                // Aplicar abono parcial

                $montos = $calculadora->convertirMonto($abonoDisponible, $moneda, $producto['origen']);

                /*   echo "<pre>";
                print_r($montos);
                echo "</pre>";
*/

                foreach ($montos as $m => $monto) {
                    $producto['total_' . $m] -= $monto;
                }

                $abonosDisponibles[$moneda] = 0;

                //  




            }
        }

        // Si no fue pagado totalmente, sumamos la deuda restante
        if (!$pagadoTotal) {
            foreach ($monedas as $moneda) {
                $restantes[$moneda] += $producto['total_' . $moneda];
            }
        }
    }

    unset($producto); // buena práctica

    return [$productos, $restantes];
}

// ───── Obtener productos de créditos activos ─────
$sql = "SELECT creditos.order_id
          FROM creditos
    LEFT JOIN orden ON orden.id = creditos.order_id
         WHERE estado = '2' AND negocio = ?
      ORDER BY negocio DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('s', $cliente);
$stmt->execute();
$res = $stmt->get_result();

$productosGlobales = [];

while ($row = $res->fetch_assoc()) {
    $productos = getProductos($row['order_id']);

    // cuenta los registros en $productos
    if (count($productos) === 0) continue;



    foreach ($productos as $p) {
        $datos = $p['datos'];
        if (!isset($datos['precio_dolar_visible'])) continue;
        $cantidad = $p['cantidad'];

        $totalUSD = $datos['precio_dolar_visible'] * $cantidad;
        $totalCOP = $datos['precio_peso_visible'] * $cantidad;
        $totalBS  = $datos['precio_bs_visible'] * $cantidad;

        $productosGlobales[] = [
            'id' => $datos['id'],
            'codigo' => $datos['codigo'],
            'nombre' => $datos['nombre'],
            'origen' => $datos['origen'],
            'stock' => $datos['stock'],
            'precio_dolar' => $datos['precio_dolar_visible'],
            'precio_peso'  => $datos['precio_peso_visible'],
            'precio_bs'    => $datos['precio_bs_visible'],
            'cantidad'     => $cantidad,
            'total_usd'    => $totalUSD,
            'total_cop'    => $totalCOP,
            'total_bs'     => $totalBS,
        ];
    }
}
$stmt->close();

usort($productosGlobales, function ($a, $b) {
    return strcmp($a['origen'], $b['origen']);
});

// Aplicar abonos si hay
[$productosAjustados, $deudaRestante] = aplicarAbonos($productosGlobales, $abonos);

//echo '<pre>';
// Respuesta final
echo json_encode([
    'deuda_restante' => $deudaRestante,
    'productos'      => $productosAjustados,
    'abonos'         => $abonos, // Muestra los abonos aplicados
], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
