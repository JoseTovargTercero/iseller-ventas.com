<?php
require_once('configuracion.php');
require_once('session.php');
$id = $_POST["id"];
$totalPrice = 0;
$moneda = '';
$ticketsFijo = 0;
$precio = 0;

$queryOrden = "SELECT * FROM orden WHERE id='$id'";
$ordenResult = $conexion->query($queryOrden);

if ($ordenResult->num_rows > 0) {
    $orden = $ordenResult->fetch_assoc();
    $tipopago = $orden['tipoPago'];

    /*  switch ($tipopago) {
        case '5':
            $monedaTexto = 'USD';
            break;
        case '6':
            $monedaTexto = 'COP';
            break;
        default:
            $monedaTexto = 'BS';
            break;
    }*/
    $monedaTexto = 'USD';

    echo "<table>
        <thead>
          <tr>
            <th  style='font-size: 11px; width: 15%; word-wrap: break-word;'>#</th>
            <th  style='font-size: 11px; width: 60%; word-wrap: break-word;'>Producto</th>
            <th  style='font-size: 11px; width: 25%;'>$monedaTexto</th>
          </tr>
        </thead>
        <tbody>";

    $queryArticulos = "SELECT oa.quantity, oa.precio_venta_dolar, oa.precio_venta_bs, oa.precio_venta_cop, p.nombre 
                   FROM orden_articulos oa 
                   LEFT JOIN productos p ON p.id = oa.product_id
                   WHERE oa.order_id = '$id'";

    $articulosResult = $conexion->query($queryArticulos);
    if ($articulosResult->num_rows > 0) {
        while ($articulo = $articulosResult->fetch_assoc()) {
            $cantidad = $articulo['quantity'];
            /*
            switch ($tipopago) {
                case '5':
                    $precio = $articulo['precio_venta_dolar'] * $cantidad;
                    $moneda = '<small>$</small>';
                    break;
                case '6':
                    $precio = $articulo['precio_venta_cop'] * $cantidad;
                    $moneda = '<small>COP</small>';
                    break;
                default:
                    $precio = $articulo['precio_venta_bs'] * $cantidad;
                    $moneda = '<small>BS</small>';
                    break;
            }*/
            $precio = $articulo['precio_venta_dolar'] * $cantidad;
            $moneda = '<small>$</small>';

            $totalPrice += $precio;

            echo "<tr>
            <td  style='font-size: 11px; width: 15%; word-wrap: break-word;'>$cantidad</td>
            <td  style='font-size: 11px; width: 60%; word-wrap: break-word;'>{$articulo['nombre']}</td>
            <td  style='font-size: 11px; width: 25%;'>" . number_format($precio, 2, ',', '.') . "</td>
          </tr>";
        }
    }

    echo "</tbody></table>";
    echo "<br><div>Total: " . number_format($totalPrice, 2, ',', '.') . " $moneda</div>";
}
