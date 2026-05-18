<?php
/**
 * REPORTE DE CIERRE DE JORNADA
 * Optimizado y Organizado por Antigravity
 * Librería PDF: TCPDF 6.8.2 (Moderna)
 */

require_once('TCPDF-main/tcpdf.php');
require_once('../../../configurar/configuracion.php');
require("../../../configurar/_tasas_cambio.php");

$stockCritico  = 10;
$today = date('Y-m-d');
$userName = $_SESSION['nombre'] ?? 'Sistema';
$bs_id = $_SESSION['bss_id'] ?? '';

if ($bs_id == '') {
    header('Location: ../login.php');
    exit;
}

// 2. INICIALIZACIÓN DE TOTALES
$stats = [
    'ventasDetal' => 0,
    'montoDetal' => 0,
    'ventasMayor' => 0,
    'montoMayor' => 0,
    'creditos' => 0,
    'montoCreditos' => 0,
    'despachados' => 0,
    'cantidadCritica' => 0
];

$paymentTotals = [
    'punto' => 0,
    'pagoMovil' => 0,
    'transferencia' => 0,
    'bioPago' => 0,
    'efectivoBs' => 0,
    'dolares' => 0,
    'pesos' => 0
];


$residuos = [
    'bs' => 0,
    'pesos' => 0,
    'usd' => 0
];

$ordenes = [];

// 3. PROCESAMIENTO DE ÓRDENES
$ordersQuery = "SELECT * FROM orden WHERE modified = '$today' AND bss_id = '$bs_id' AND status IN ('1', '4')";
$ordersResult = $conexion->query($ordersQuery);

$ordersList = [];
if ($ordersResult->num_rows > 0) {
    while ($order = $ordersResult->fetch_assoc()) {
        $ordersList[] = $order;
        $orderId = $order['id'];
        array_push($ordenes, $orderId);

        if ($order['status'] == '1') {
            $stats['ventasDetal']++;
            $stats['montoDetal'] += $order['total_price'];
        } elseif ($order['status'] == '4') {
            $stats['ventasMayor']++;
            $stats['montoMayor'] += $order['total_price'];
        }

        switch ($order['tipoPago']) {
            case '1': $paymentTotals['punto'] += $order['total_price_bs']; break;
            case '2': $paymentTotals['pagoMovil'] += $order['total_price_bs']; break;
            case '3': $paymentTotals['transferencia'] += $order['total_price_bs']; break;
            case '4': $paymentTotals['efectivoBs'] += $order['total_price_bs']; break;
            case '5': $paymentTotals['dolares'] += $order['total_price']; break;
            case '6': $paymentTotals['pesos'] += $order['total_price_cop']; break;
            case '7': $paymentTotals['bioPago'] += $order['total_price_bs']; break;
        }

        if ($order['status'] != '2') {
            $itemsQuery = "SELECT * FROM orden_articulos WHERE order_id = '$orderId'";
            $itemsResult = $conexion->query($itemsQuery);
            
            $costoTotalOrderBs = 0;
            $costoTotalOrderUsd = 0;
            $costoTotalOrderCop = 0;

            while ($item = $itemsResult->fetch_assoc()) {
                $stats['despachados'] += $item['quantity'];
                $costoTotalOrderBs += $item['bolivar'] * $item['quantity'];
                $costoTotalOrderUsd += $item['precio'] * $item['quantity'];
                $costoTotalOrderCop += $item['peso'] * $item['quantity'];
            }

           
        }
    }
}

// 4. PRODUCTOS MAS VENDIDOS
$articulosVendidosQuery = "SELECT oa.product_id, oa.quantity, oa.id_sucursal, p.nombre, s.nombre as sucursal FROM orden_articulos as oa 
INNER JOIN productos as p ON oa.product_id = p.id 
INNER JOIN sucursales as s ON oa.id_sucursal = s.id
WHERE order_id IN ('" . implode("','", $ordenes) . "')";
$articulosVendidosResult = $conexion->query($articulosVendidosQuery);

$agrupadosPorSucursal = [];
if ($articulosVendidosResult->num_rows > 0) {
    while ($articulosVendidos = $articulosVendidosResult->fetch_assoc()) {
        $nombre = $articulosVendidos['nombre'];
        $cantidad = $articulosVendidos['quantity'];
        $id = $articulosVendidos['product_id'];
        $id_sucursal = $articulosVendidos['id_sucursal'];
        
        $nombre = strtoupper($nombre);
        $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);
        $articulosVendidos['nombre'] = $nombre;

        if (isset($agrupadosPorSucursal[$id_sucursal][$id])) {
            $agrupadosPorSucursal[$id_sucursal][$id]['quantity'] += $cantidad;
        } else {
            $agrupadosPorSucursal[$id_sucursal][$id] = $articulosVendidos;
        }
    }
}

$articulosVendidosList = [];
// Filtrar los 5 más vendidos de cada sucursal
foreach ($agrupadosPorSucursal as $id_sucursal => $productos) {
    usort($productos, function($a, $b) {
        return $b['quantity'] <=> $a['quantity'];
    });
    
    $top5 = array_slice($productos, 0, 5);
    
    foreach ($top5 as $prod) {
        $id = $prod['product_id'];
        $articulosVendidosList[$id][$id_sucursal] = $prod;
    }
}




// 4.1 Obetener stock actual de cada producto vendido

function obtener_stock_actual($id_producto, $id_sucursal) {
    global $conexion;
    $stockActualProductos = [];
    $stockActualProductosQuery = "SELECT stock FROM stock WHERE id_producto = '$id_producto' AND id_sucursal = '$id_sucursal'";
    $stockActualProductosResult = $conexion->query($stockActualProductosQuery);
    if ($stockActualProductosResult->num_rows > 0) {
        while ($stockActualProductos = $stockActualProductosResult->fetch_assoc()) {
            return $stockActualProductos['stock'];
        }
    }
    return 0;
}

foreach ($articulosVendidosList as $key => $value) {
    foreach ($value as $key2 => $value2) {
        $articulosVendidosList[$key][$key2]['stock'] = obtener_stock_actual($key, $key2);
        if ($articulosVendidosList[$key][$key2]['stock'] <= $stockCritico) {
            $articulosVendidosList[$key][$key2]['critico'] = true;
        } else {
            $articulosVendidosList[$key][$key2]['critico'] = false;
        }
    }
}




$stats['cantidadCritica'] = contar("SELECT COUNT(*) FROM productos WHERE stock <= '$stockCritico' AND activo = '0'");

// 5. GENERACIÓN DE HTML
$html = '
<body>
    <div style="text-align: left">
         i-SELLER
    </div>
    
    <p style="text-align:center">
        <span style="font-size:15px">REPORTE DE CIERRE DE JORNADA</span><br>
        Fecha del servidor: ' . date("d/m/Y - h:i a") . '<br>
        Usuario que reporta: <strong>' . $userName . '</strong>
    </p>

 

    <p style="text-align:center"><span style="font-size:15px">RESUMEN DE VENTAS DEL DÍA</span></p>
    <table class="table table-striped" border="0.1" cellpadding="5">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>Punto</th>
                <th>Pago Móvil</th>
                <th>Transf.</th>
                <th>Biopago</th>
                <th>BS Efec.</th>
                <th>Dólares</th>
                <th>Pesos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . number_format($paymentTotals['punto'], 0, ',', '.') . ' BS</td>
                <td>' . number_format($paymentTotals['pagoMovil'], 0, ',', '.') . ' BS</td>
                <td>' . number_format($paymentTotals['transferencia'], 0, ',', '.') . ' BS</td>
                <td>' . number_format($paymentTotals['bioPago'], 0, ',', '.') . ' BS</td>
                <td>' . number_format($paymentTotals['efectivoBs'], 0, ',', '.') . ' BS</td>
                <td>$ ' . number_format($paymentTotals['dolares'], 2, ',', '.') . '</td>
                <td>' . number_format($paymentTotals['pesos'], 0, ',', '.') . ' COP</td>
            </tr> 
        </tbody>
    </table>


    <p style="text-align:center"><span style="font-size:15px">MOVIMIENTOS DEL DÍA</span></p>
    <table class="table table-striped" border="0.1" cellpadding="3">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>#</th>
                <th>Tipo</th>
                <th>Pago</th>
                <th>Usuario</th>
                <th>Hora</th>
                <th>USD</th>
                <th>COP</th>
                <th>BS</th>
                <th>Productos</th>
            </tr>
        </thead>
        <tbody>';

$userIds = array_unique(array_column($ordersList, 'usuario'));
$userNamesMap = [];
if (!empty($userIds)) {
    $userIdsStr = implode("','", $userIds);
    $uQuery = "SELECT id, nombre FROM usuarios WHERE id IN ('$userIdsStr')";
    $uRes = $conexion->query($uQuery);
    while ($u = $uRes->fetch_assoc()) {
        $userNamesMap[$u['id']] = $u['nombre'];
    }
}

$orderIds = array_column($ordersList, 'id');
$itemsMap = [];
if (!empty($orderIds)) {
    $orderIdsStr = implode("','", $orderIds);
    $iQuery = "SELECT oa.order_id, p.nombre, oa.quantity 
               FROM orden_articulos oa 
               JOIN productos p ON oa.product_id = p.id 
               WHERE oa.order_id IN ('$orderIdsStr')";
    $iRes = $conexion->query($iQuery);
    while ($item = $iRes->fetch_assoc()) {
        $itemsMap[$item['order_id']][] = $item['quantity'] . " " . $item['nombre'];
    }
}

foreach ($ordersList as $idx => $order) {
    $orderId = $order['id'];
    $uName = $userNamesMap[$order['usuario']] ?? 'N/A';
    $productsStr = isset($itemsMap[$orderId]) ? implode(", ", $itemsMap[$orderId]) : "";

    $tipo = ($order['status'] == 1) ? "V" : (($order['status'] == 2) ? "C" : "M");
    $pagosMap = ["1" => "Punto", "2" => "Pago Movil", "3" => "Transf.", "4" => "Bs Efec.", "5" => "Dolares", "6" => "Pesos", "7" => "Biopago", "8" => "Fracc."];
    $tipoPago = $pagosMap[$order['tipoPago']] ?? "N/A";
    $hora = substr($order['created'], 11, 8);

    $html .= ' 
        <tr>
            <td>' . ($idx + 1) . '</td>
            <td>' . $tipo . '</td>
            <td>' . $tipoPago . '</td>
            <td>' . $uName . '</td>
            <td>' . $hora . '</td>
            <td>$' . number_format($order['total_price'], 2, ',', '.') . '</td>
            <td>' . number_format($order['total_price_cop'], 0, ',', '.') . '</td>
            <td>' . number_format($order['total_price_bs'], 0, ',', '.') . '</td>
            <td style="font-size: 8px">' . $productsStr . '</td>
        </tr>';
}


$html .= '</tbody></table>

    <p style="text-align:center"><span style="font-size:15px">PRODUCTOS CON MAS MOVIMIENTO DEL STOCK</span></p>
    <table class="table table-striped" border="0.1" cellpadding="5">
        <thead>
            <tr >
                <th>Producto</th>
                <th>Sucursal</th>
                <th>Cantidad vendida</th>
                <th>Cantidad restante</th>
                <th>Estado del stock</th>
            </tr>
        </thead>
        <tbody>';




// recorre el array de productos vendidos y muestra los productos vendidos por sucursal y los que quedan
foreach ($articulosVendidosList as $key => $value) {
    foreach ($value as $key2 => $value2) {
        $html .= ' <tr>
            <td>' . strtoupper($value2['nombre']) . '</td>
            <td>' . $value2['sucursal'] . '</td>
            <td>' . $value2['quantity'] . '</td>
            <td>' . $value2['stock'] . '</td>
            <td>' . ($value2['critico'] ? '<span style="color: red;">Crítico</span>' : '<span style="color: green;">Normal</span>') . '</td>
        </tr>';
    }
}







$html .= ' </tbody></table>

    <p style="text-align:center"><span style="font-size:15px">PRODUCTOS CON STOCK CRÍTICO</span></p>
    <table class="table table-striped" border="0.1" cellpadding="5">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>Producto</th>
                <th>Stock</th>
                <th>U. Compra</th>
                <th>Unidades</th>
            </tr>
        </thead>
        <tbody>';

$critQuery = "SELECT nombre, stock, precio_compra, cantidad_unidades FROM productos WHERE stock <= '$stockCritico' AND activo = '0' ORDER BY nombre ASC";
$critResult = $conexion->query($critQuery);
while ($p = $critResult->fetch_assoc()) {
    $html .= '
        <tr>
            <td>' . strtoupper($p['nombre']) . '</td>
            <td>' . $p['stock'] . '</td>
            <td>$ ' . $p['precio_compra'] . '</td>
            <td>' . $p['cantidad_unidades'] . '</td>
        </tr>';
}

$html .= '</tbody></table>
    <p>Stock crítico configurado en: ' . $stockCritico . '</p>
    <footer>REPORTE DE CIERRE DE JORNADA DE "I-SELLER".</footer>
</body>';

// 6. GENERACIÓN DEL PDF CON TCPDF
$modoDescarga = ($_SESSION['nivel'] != "1") ? "F" : "I";
$fileName = 'CIERRE.pdf';

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('i-SELLER');
$pdf->SetAuthor($userName);
$pdf->SetTitle('Reporte de Cierre');

// Desactivar cabeceras y pies de página automáticos para usar el HTML personalizado
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

if (file_exists('style.css')) {
    $css = file_get_contents('style.css');
    $html = '<style>' . $css . '</style>' . $html;
}

$pdf->writeHTML($html, true, false, true, false, '');

if ($modoDescarga == "I") {
    $pdf->Output($fileName, 'I');
} else {
    $pdf->Output(dirname(__FILE__) . '/' . $fileName, 'F');
}

if ($modoDescarga == "I") {
    // El PDF se abrió en el navegador
} else {
    echo "<script>window.open('../../production/consultaHistorica.php?accion=correo', '_self');</script>";
}
