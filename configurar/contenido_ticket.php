<?php
require_once('configuracion.php');
require_once('session.php');
$id = $_POST["id"];
$totalPrice = 0;
$moneda = '';
$ticketsFijo = 0;
$precio = 0;
$moneda = $_POST["moneda"];


function redondearCentena($numero)
{
    $resto = $numero % 100;

    if ($resto > 50) {
        return ceil($numero / 100) * 100; // Redondea hacia arriba
    } else {
        return floor($numero / 100) * 100; // Redondea hacia abajo
    }
}



$queryOrden = "SELECT * FROM orden WHERE id='$id'";
$ordenResult = $conexion->query($queryOrden);

if ($ordenResult->num_rows > 0) {
    $orden = $ordenResult->fetch_assoc();
    $tipopago = $orden['tipoPago'];

    if ($moneda == 'default') {
        switch ($tipopago) {
            case '5':
                $monedaTexto = 'USD';
                break;
            case '6':
                $monedaTexto = 'COP';
                break;
            default:
                $monedaTexto = 'BS';
                break;
        }
    } else {
        $monedaTexto = $moneda;
    }


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

            if ($moneda == 'default') {

                switch ($tipopago) {
                    case '5':
                        $precio = $articulo['precio_venta_dolar'] * $cantidad;
                        $monedaTexto = '<small>$</small>';
                        break;
                    case '6':
                        $precio = redondearCentena($articulo['precio_venta_cop'] * $cantidad);
                        $monedaTexto = '<small>COP</small>';
                        break;
                    default:
                        $precio = $articulo['precio_venta_bs'] * $cantidad;
                        $monedaTexto = '<small>BS</small>';
                        break;
                }
            } else {
                //TODO: REDONDEAR CUANDO SEA PESOS 
                switch ($moneda) {
                    case 'USD':
                        $precio = $articulo['precio_venta_dolar'] * $cantidad;
                        $monedaTexto = '<small>$</small>';
                        break;
                    case 'COP':
                        $precio = redondearCentena($articulo['precio_venta_cop'] * $cantidad);
                        $monedaTexto = '<small>COP</small>';
                        break;
                    default:
                        $precio = $articulo['precio_venta_bs'] * $cantidad;
                        $monedaTexto = '<small>BS</small>';
                        break;
                }
            }

            $totalPrice += $precio;

            echo "<tr>
            <td  style='font-size: 11px; width: 15%; word-wrap: break-word;'>$cantidad</td>
            <td  style='font-size: 11px; width: 60%; word-wrap: break-word;'>{$articulo['nombre']}</td>
            <td  style='font-size: 11px; width: 25%;'>" . number_format($precio, 2, ',', '.') . "</td>
          </tr>";
        }
    }

    echo "</tbody></table>";
    echo "<br><div>Total: " . number_format($totalPrice, 2, ',', '.') . " $monedaTexto</div>";

    // ── Forma de pago y monto en la moneda de pago ──────────────────────
    $etiquetasPago = [
        '0' => 'Crédito',
        '1' => 'Punto',
        '2' => 'Pago Móvil',
        '3' => 'Transferencia',
        '4' => 'Efectivo (Bs)',
        '5' => 'Dólares',
        '6' => 'Pesos',
        '7' => 'BioPago',
        '8' => 'Fraccionado',
    ];
    $tipoPagoStr = (string) $tipopago;
    $estadoOrden = (string) $orden['status'];
    $esCredito = ($estadoOrden === '2');
    $esDescuento = ($estadoOrden === '3');
    $formaPago = $etiquetasPago[$tipoPagoStr] ?? 'No especificado';
    if ($esCredito) {
        $formaPago = 'Crédito';
    } elseif ($esDescuento) {
        $formaPago = 'Descuento de productos';
    }

    $pagoLineas = [];
    if ($esDescuento) {
        $pagoLineas[] = 'Valor descontado: $' . number_format((float) $orden['total_price'], 2, ',', '.');
    } elseif ($esCredito) {
        $pagoLineas[] = 'Moneda del crédito: Bs';
        $pagoLineas[] = 'Monto del crédito: ' . number_format((float) $orden['total_price_bs'], 2, ',', '.') . ' Bs';
    } elseif ($tipoPagoStr === '8') {
        $fraccion = null;
        $stmtFrac = $conexion->prepare("SELECT SUM(punto) AS punto, SUM(pagoMovil) AS pagoMovil, SUM(transferencia) AS transferencia, SUM(bioPago) AS bioPago, SUM(efectivo) AS efectivo, SUM(pesos) AS pesos, SUM(dolares) AS dolares FROM fracciones WHERE id_order = ? AND bss_id = ?");
        if ($stmtFrac) {
            $stmtFrac->bind_param('ii', $id, $bss_id);
            $stmtFrac->execute();
            $fraccion = $stmtFrac->get_result()->fetch_assoc();
            $stmtFrac->close();
        }

        $mapaFracciones = [
            'punto' => ['Punto', 'Bs', 2],
            'efectivo' => ['Efectivo', 'Bs', 2],
            'pagoMovil' => ['Pago Móvil', 'Bs', 2],
            'transferencia' => ['Transferencia', 'Bs', 2],
            'bioPago' => ['BioPago', 'Bs', 2],
            'dolares' => ['Dólares', 'USD', 2],
            'pesos' => ['Pesos', 'COP', 2],
        ];

        $pagoLineas[] = 'Moneda de pago: Mixta';
        $detallesPago = [];
        if ($fraccion) {
            foreach ($mapaFracciones as $columna => $datos) {
                $valor = (float) ($fraccion[$columna] ?? 0);
                if ($valor <= 0) {
                    continue;
                }
                $detallesPago[] = $datos[0] . ': ' . number_format($valor, $datos[2], ',', '.') . ' ' . $datos[1];
            }
        }
        if ($detallesPago) {
            foreach ($detallesPago as $detalle) {
                $pagoLineas[] = $detalle;
            }
        } else {
            $pagoLineas[] = 'Sin desglose registrado';
        }
    } else {
        switch ($tipoPagoStr) {
            case '5':
                $monedaPago = 'USD';
                $montoPago = (float) $orden['total_price'];
                break;
            case '6':
                $monedaPago = 'COP';
                $montoPago = (float) $orden['total_price_cop'];
                break;
            default:
                $monedaPago = 'Bs';
                $montoPago = (float) $orden['total_price_bs'];
                break;
        }
        $pagoLineas[] = 'Moneda de pago: ' . $monedaPago;
        $pagoLineas[] = 'Monto pagado: ' . number_format($montoPago, 2, ',', '.') . ' ' . $monedaPago;
    }

    echo "<div class='line'></div>";
    echo "<div style='font-size:11px;'><b>Forma de pago:</b> " . htmlspecialchars($formaPago) . "</div>";
    foreach ($pagoLineas as $lineaPago) {
        echo "<div style='font-size:11px;'>" . htmlspecialchars($lineaPago) . "</div>";
    }
}
