<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");




    $query = "SELECT * FROM sistem";
    $search = $conexion->query($query);
    if ($search->num_rows > 0) {
      while ($rowT = $search->fetch_assoc()) {
        $ticketsFijo = $rowT['bsFijoTicket'];
      }
    }






	$id=$conexion->real_escape_string($_POST['id']);


    
    $query222222 = "SELECT * FROM orden WHERE id='$id'";
    $buscarAlumnos222222 = $conexion->query($query222222);
    if ($buscarAlumnos222222->num_rows > 0) {
      
      echo '<table>';
      
      while ($filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc()) {
        $tipopago = $filaAlumnos222222['tipoPago'];
        $total_price = $filaAlumnos222222['total_price'];
        $total_price_bs = $filaAlumnos222222['total_price_bs'];
        $total_price_cop = $filaAlumnos222222['total_price_cop'];



        if ($ticketsFijo == 1 || $tipopago == '1' || $tipopago == '2' || $tipopago == '3' || $tipopago == '4' || $tipopago == '7') {
          // Bolivar...
          $moneda = 'BS';
        } elseif ($tipopago == '5') {
          // Dolar...
          $moneda = 'USD';
        } else {
          // pesos...
          $moneda = 'COP';
        }


        echo '<thead>
                                  <tr>
                                      <th class="cantidad" style="padding-left: 0 !important; margin-left: 0 !important">#</th>
                                      <th class="producto" style="padding-left: 0 !important; margin-left: 0 !important">Producto</th>
                                      <th class="precio" style="padding-left: 0 !important; margin-left: 0 !important">' . $moneda . '</th>
                                  </tr>
                                  </thead>   
                                  <tbody>';

        $query23 = "SELECT orden_articulos.quantity, orden_articulos.precio_venta_dolar, orden_articulos.precio_venta_bs, orden_articulos.precio_venta_cop, productos.nombre  FROM orden_articulos 
                          LEFT JOIN productos ON productos.id = orden_articulos.product_id
                          WHERE orden_articulos.order_id='$id'";
        $search23 = $conexion->query($query23);
        if ($search23->num_rows > 0) {
          while ($fila23 = $search23->fetch_assoc()) {

            $quantity = $fila23['quantity'];
            $precio_venta_dolar = $fila23['precio_venta_dolar'];
            $precio_venta_bs = $fila23['precio_venta_bs'];
            $precio_venta_cop = $fila23['precio_venta_cop'];


            if ($ticketsFijo == 1 || $tipopago == '1' || $tipopago == '2' || $tipopago == '3' || $tipopago == '4' || $tipopago == '7') {
              // Bolivar...
              $price = $precio_venta_bs * $quantity;
              $moneda = '<small>BS</small>';
            } elseif ($tipopago == '5') {
              // Dolar...
              $price = $precio_venta_dolar * $quantity;
              $moneda = '<small>$</small>';
            } else {
              // pesos...
              $price = $precio_venta_cop * $quantity;
              $moneda = '<small>COP</small>';
            }
            $totalPrice += $price;

            echo '<tr>
                                      <td class="cantidad">' . $quantity . '</td>
                                      <td class="producto">' . $fila23['nombre'] . '</td>
                                      <td class="precio">' . number_format($price, 2, ',', '.') . '</td>
                                  </tr>
                                 ';
          }
        }
      }
    }

    echo '</table><br><br><div class="total">Total: '.number_format($totalPrice, 2, ',', '.') . ' ' . $moneda.'</div>';


?>


