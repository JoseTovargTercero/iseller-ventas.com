<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <title>Loader #6 - Jelly Box</title>
  <!--

  <link rel="stylesheet" href="../assets/css/styles-ticket.css">


  <link rel="stylesheet" href="https://printjs.crabbly.com/print.min.css">
  <script src="https://printjs.crabbly.com/print.min.js"></script>


  -->
  <link rel="stylesheet" href="https://printjs-4de6.kxcdn.com/print.min.css">
  <script src="https://printjs-4de6.kxcdn.com/print.min.js"></script>


  <style>
    #ticket {
      width: 58mm;
      max-width: 100%;
      font-family: monospace;
      font-size: 11px;
      margin: 0 auto;
      padding: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      text-align: left;
      padding: 2px 0;
    }

    .centrado {
      text-align: center;
    }

    .total {
      text-align: right;
      margin-top: 10px;
      font-weight: bold;
    }

    /* SOLO para impresión */
    @media print {
      @page {
        size: 58mm auto;
        margin: 0;
      }

      body {
        margin: 0;
        padding: 0;
      }

      #ticket {
        width: 58mm;
        margin: 0 auto;
        page-break-after: avoid;
      }
    }
  </style>


</head>




<body>

  <div class="ticket" id="ticket" style="height: fit-content !important;">

    <p class="text-center">
      J410285427<br>
      Mercado Rebusque Mayabiro<br>
      <?= date('Y-m-d H:i a') ?><br>
      <strong></strong>
      <small>* Nota de entrega</small>
    </p>

    <?php
    $id = $_GET['id'];
    $totalPrice = 0;
    $moneda = '';
    $ticketsFijo = 1;

    $queryOrden = "SELECT * FROM orden WHERE id='$id'";
    $ordenResult = $conexion->query($queryOrden);

    if ($ordenResult->num_rows > 0) {
      $orden = $ordenResult->fetch_assoc();
      $tipopago = $orden['tipoPago'];

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

      echo "<table>
            <thead>
              <tr>
                <th class='cantidad'>#</th>
                <th class='producto'>Producto</th>
                <th class='precio'>$monedaTexto</th>
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
          $precio = 0;

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
          }

          $totalPrice += $precio;

          echo "<tr>
                <td class='cantidad'>$cantidad</td>
                <td class='producto'>{$articulo['nombre']}</td>
                <td class='precio'>" . number_format($precio, 2, ',', '.') . "</td>
              </tr>";
        }
      }

      echo "</tbody></table>";
      echo "<br><div class='total'>Total: " . number_format($totalPrice, 2, ',', '.') . " $moneda</div>";
    }
    ?>

    <br>
    <div class="line"></div>
    <p class="text-center" style="font-size: 10px;">¡GRACIAS POR SU COMPRA!</p>
    <div class="line"></div>

  </div>
  <button onclick="imprimirTicket()">
    Imprimir Ticket
  </button>

  <script>
    function imprimirTicket() {
      printJS({
        printable: 'ticket',
        type: 'html',
        scanStyles: false,
        style: `
      @page { size: 58mm auto; margin: 0; }
      body { margin: 0; padding: 0; }
      #ticket { width: 58mm; margin: 0 auto; font-family: monospace; font-size: 11px; }
      table { width: 100%; border-collapse: collapse; }
      th, td { text-align: left; padding: 2px 0; }
      .centrado { text-align: center; }
      .total { text-align: right; font-weight: bold; margin-top: 10px; }
    `
      });
    }

    function imprimir() {
      let id = " <?php echo $id ?>";
      const printContents = document.getElementById('ticket').innerHTML;
      const originalContents = document.body.innerHTML;
      document.body.innerHTML = printContents;
      window.print();
      document.body.innerHTML = originalContents;
      //open('ventas.php?id=' + id, '_self').close();
    }
    //imprimir()
  </script>

</body>

</html>