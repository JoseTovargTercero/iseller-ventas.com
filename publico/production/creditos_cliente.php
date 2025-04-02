<?php
ob_start();
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');




if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();

  if ($_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
  }
  if ($_SESSION['nivel'] == '1') {
    $menu = MenuAdministrador();
  } else {
    $menu = MenuStandar();

    if ($Creditos == 0) {
      define('PAGINA_INICIO', '../../index.php');
      header('Location: ' . PAGINA_INICIO);
    }
  }

  $nivelUsuario = $_SESSION['nivel'];
  $nombreUsuario = $_SESSION['nombre'];
  $cliente = $_GET['cliente'];

  $query7 = $conexion->query("SELECT * FROM cambio WHERE id='1'");
  if ($query7->num_rows > 0) {
    while ($row7 = $query7->fetch_assoc()) {
      $pesoDolar = $row7['pesoDolar'];
      $DolarBolivar = $row7['DolarBolivar'];
      $bolivarPesoTrans = $row7['bolivarPesoTrans'];
    }
  }



  // initializ shopping cart class
  include 'La-carta.php';
  $cart = new Cart;
?>


  <!DOCTYPE html>
  <html lang="es">

  <head>
   
    <title>Control de Creditos</title>
    <?php require_once('includes/headers.php'); ?>

    <link href="../vendors/iCheck/skins/flat/green.css" rel="stylesheet">
    <link href="../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">

    <script src='peticion.js'></script>

    <script src='peticion_producto.js'></script>


    <script>
      function updateCartItem(obj, id) {
        $.get("cartAction.php", {
          action: "updateCartItem",
          id: id,
          qty: obj.value
        }, function(data) {
          if (data == 'ok') {
            location.reload();
          } else {
            alert('Cart update failed, please try again.');
          }
        });
      }

      function confirmar() {
        var confirm = alertify.confirm('Confirmar venta', 'Esta apunto de realizar una venta, ¿desea continuar?', null, null).set('labels', {
          ok: 'Confirmar',
          cancel: 'Cancelar'
        });

        //callbak al pulsar botón positivo
        confirm.set('onok', function() {
          window.open("AccionCarta.php?action=placeOrder", "_self");
        });
        //callbak al pulsar botón negativo
        confirm.set('oncancel', function() {
          alertify.error('Venta cancelada');
        })

      }
    </script>







    <?php

    switch (@$_GET['accion']) {
      case ('pagado'):
        echo '<script>
          function mensajeVenta(){	
          alertify.success("Credito cancelado correctamente");  }
                </script>
                <body onload="mensajeVenta()">
                </body>';
        break;
    }




    ?>


  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">

          <div class='left_col scroll-view'>
            <div class='navbar nav_title' style='border: 0;'>
              <a href='index.php' class='site_title'>
                <img src='images/logo1-inv-compact.png' style='max-width:147px; opacity: 0.8'> <span>
                  <img style='max-width:140px'><span> </a>
            </div>
            <div class='clearfix'></div>
            <!-- /menu profile quick info -->
            <br />
            <?php echo $menu ?>
          </div>
        </div>

        <!-- top navigation -->
        <?php echo $topnav ?>
        <!-- /top navigation -->
        <style>
          .gray {
            color: rgba(52, 73, 94, 0.94);
            font-size: 26px;


          }
        </style>





        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">


            <h4>Creditos</h4>
            <p style="margin-top: -10px;">Listado de creditos otorgados</p>


            <div class="clearfix"></div>
            <div class="row" style="display: block;">





              <div class="col-lg-12">
                <div class="x_panel  fadeInUp animated">
                  <div class="x_title">
                    <h2>Creditos</h2>

                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="row">
                      <div class="col-lg-12">
                        <div class="card-box table-responsive">

                          <table class="table table-bordered" style="width:100%">
                            <thead>
                              <tr class="headings">
                                <th class="column-title">#</th>
                                <th class="column-title">Producto</th>
                                <th class="column-title text-center">Valor ($)</th>
                                <th class="column-title text-center">Valor (COP)</th>
                                <th class="column-title text-center">Valor (BS)</th>
                                <th class="column-title text-center"></th>
                              </tr>
                            </thead>




                            <tbody>
                              <?php

                              function datosProductos($producto)
                              {
                                global $conexion, $pesoDolar, $bolivarPesoTrans, $DolarBolivar;


                                $query = $conexion->query("SELECT * FROM productos  WHERE id = '$producto' AND activo= 0");
                                if ($query->num_rows > 0) {
                                  while ($row = $query->fetch_assoc()) {

                                    $cantidadUnidad = $row["cantidad_unidades"];
                                    $origen = $row["origen"];

                                    $precioDolarCompra = $row["precio_compra"] / $cantidadUnidad;
                                    $porcentaje = $row["porcentaje"];
                                    $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
                                    $precioDolarVenta = number_format($precioDolarVenta, '2', '.', ',');


                                    $precioPesoVenta = $precioDolarVenta * $pesoDolar;
                                    $precioPesoVenta = formatPesoVista($precioPesoVenta);


                                    if ($origen == 'c') {
                                      $precioBsVenta = ($precioPesoVenta / $bolivarPesoTrans) / 1000;
                                    } else {
                                      $precioBsVenta = $precioDolarVenta * $DolarBolivar;
                                    }

                                    $nombre = strtoupper($row["nombre"]);
                                    // quitar caracteres especiales del nombre
                                    $nombre = preg_replace('/[^A-Za-z0-9\s]/', '', $nombre);

                                    $data = [
                                      'id' => $row['id'],
                                      'stock' => $row['stock'],
                                      'nombre' =>  "$nombre",
                                      'precio_dolar_visible' => $precioDolarVenta,
                                      'precio_peso_visible' => $precioPesoVenta,
                                      'precio_bs_visible' => $precioBsVenta,
                                      'codigo' => $row['codigo'],
                                      'origen' => $row['origen'],
                                    ];
                                  }
                                  return $data;
                                }
                              }

                              function getProductos($id_order)
                              {
                                global $conexion;
                                $productos = [];

                                $stmt = mysqli_prepare($conexion, "SELECT product_id, quantity FROM orden_articulos WHERE order_id=?");
                                $stmt->bind_param('s', $id_order);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                  while ($row = $result->fetch_assoc()) {
                                    $productos[] = [ // ← Quitamos $productos[$id_order][]
                                      'id' => $row['product_id'],
                                      'cantidad' => $row['quantity'],
                                      'datos' => datosProductos($row['product_id'])
                                    ];
                                  }
                                }

                                $stmt->close();
                                return $productos;
                              }

                              $productos_by_order = [];

                              $stmt = mysqli_prepare($conexion, "SELECT creditos.tipoCompra, creditos.id AS id_credito, creditos.order_id, orden.created, orden.total_price FROM creditos LEFT JOIN orden ON orden.id = creditos.order_id WHERE estado='2' AND negocio = ? ORDER BY negocio DESC");
                              $stmt->bind_param('s', $cliente);
                              $stmt->execute();
                              $result = $stmt->get_result();
                              if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                  $productos_by_order[] = [
                                    'id' => $row['order_id'],
                                    'id_credito' => $row['id_credito'],
                                    'tipoCompra' => $row['tipoCompra'],
                                    'fecha' => $row['created'],
                                    'total' => $row['total_price'],
                                    'productos' => getProductos($row['order_id'])
                                  ];
                                }
                              } else {
                                header("Location: creditos.php");
                                exit;
                              }
                              $stmt->close();


                              $total_general_usd = 0;
                              $total_general_cop = 0;
                              $total_general_bss = 0;


                              foreach ($productos_by_order as  $item) {
                                $total_seccion_usd = 0;
                                $total_seccion_cop = 0;
                                $total_seccion_bss = 0;


                                foreach ($item['productos'] as $sub_item) {

                                  /* aca adentro ocurre el error al acceder a los datos */

                                  $precio_usd = $sub_item['datos']['precio_dolar_visible'] * $sub_item['cantidad'];
                                  $precio_cop = $sub_item['datos']['precio_peso_visible'] * $sub_item['cantidad'];
                                  $precio_bss = $sub_item['datos']['precio_bs_visible'] * $sub_item['cantidad'];

                                  $total_seccion_usd += $precio_usd;
                                  $total_seccion_cop += $precio_cop;
                                  $total_seccion_bss += $precio_bss;

                                  $total_general_usd += $precio_usd;
                                  $total_general_cop += $precio_cop;
                                  $total_general_bss += $precio_bss;

                                  echo '<tr>';
                                  echo '
                                    <td></td>
                                    <td>' . htmlspecialchars($sub_item['datos']['nombre']) . '</td>
                                    <td class="text-center">' . $precio_usd . ' <small>$</small></td>
                                    <td class="text-center">' . number_format($precio_cop, 0, '.', ',') . ' <small>Cop</small></td>
                                    <td class="text-center">' . number_format($precio_bss, 2, '.', ',') . ' <small>Bs</small></td>
                                    <td></td>
                                    
                                    ';
                                  echo '</tr>';
                                }

                                echo '<tr style="background-color: rgba(0, 0, 0, .05);">';
                                echo '
                                  <td>COMPRA: <b>' . $item['id'] . '</b></td>
                                  <td>Fecha: ' . $item['fecha'] . '</td>
                                  <td class="text-center"><b>TOTAL: ' . $total_seccion_usd . '<small></small></b></td>
                                  <td class="text-center"><b>TOTAL: ' . number_format($total_seccion_cop, 0, '.', ',') . '<small></small></b></td>
                                  <td class="text-center"><b>TOTAL: ' . number_format($total_seccion_bss, 2, '.', ',') . '<small></small></b></td>
                                  <td class="text-center">
                                  <button data-tipoCompra="' . $item['tipoCompra'] . '" data-precioPesoVenta = "' . $total_seccion_cop . '" data-precioBsVenta="' . $total_seccion_bss . '" data-id_credito="' . $item['id_credito'] . '" data-id="' . $item['id'] . '" class="btn btn-pagar btn-sm btn-info">Pagar</button>
                                  </td>';
                                echo '</tr>';
                              }


                              echo '<tr style="background-color: rgba(0, 0, 0, .2);">';
                              echo '
                                <td colspan=2 >DEUDA TOTAL:</td>
                                <td class="text-center"><b>' . $total_general_usd . '<small>$</small></b></td>
                                <td class="text-center"><b>' . number_format($total_general_cop, 0, '.', ',') . '<small>Cop</small></b></td>
                                <td class="text-center"><b>' . number_format($total_general_bss, 2, '.', ',') . '<small>Bs</small></b></td>
                                    <td></td>
                                ';
                              echo '</tr>';


                              ?>
                            </tbody>
                          </table>


                          <br>



                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /page content -->
        <!-- footer content -->
        <footer>
          <div class="pull-right">
            i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
          </div>
          <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
      </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FastClick -->
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="../vendors/nprogress/nprogress.js"></script>
    <!-- iCheck -->
    <script src="../vendors/iCheck/icheck.min.js"></script>
    <!-- Datatables -->
    <script src="../vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
    <script src="../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
    <script src="../vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
    <script src="../vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
    <script src="../vendors/jszip/dist/jszip.min.js"></script>
    <script src="../vendors/pdfmake/build/pdfmake.min.js"></script>
    <script src="../vendors/pdfmake/build/vfs_fonts.js"></script>

    <!-- Custom Theme Scripts -->
    <script src="../build/js/custom.min.js"></script>


    <script>
      const opcionesPago = `
                    <select id="metodoPago" class="form-control">
                        <option value="">Seleccione</option>
                        <option value="option1">Punto</option>
                        <option value="option2">Pago Movil</option>
                        <option value="option3">Transferencia</option>
                        <option value="option7">BioPago</option>
                        <option value="option4">Efectivo</option>
                        <option value="option5">Dolares</option>
                        <option value="option6">Pesos</option>
                    </select>`;

      document.addEventListener('click', function(event) {

        if (event.target.closest('.btn-pagar')) { // ACCION DE ELIMINAR
          const elemento = event.target.closest('.btn-pagar')

          const data_id_credito = elemento.getAttribute('data-id_credito');
          const data_id = elemento.getAttribute('data-id');
          const precioPesoVenta = elemento.getAttribute('data-precioPesoVenta')
          const precioBsVenta = elemento.getAttribute('data-precioBsVenta')
          const tipoCompra = elemento.getAttribute('data-tipoCompra')

          pagar(data_id_credito, data_id, precioPesoVenta, precioBsVenta, tipoCompra);
        }

      });







      function pagar(credito, compra, precioPesoVenta, precioBsVenta, tipoCompra) {
        // Mostrar el diálogo
        Swal.fire({
          title: 'Selecciona un método de pago',
          html: opcionesPago,
          confirmButtonText: 'Continuar',
          confirmButtonColor: '#32d7c0',
          preConfirm: () => {
            // Obtener el valor seleccionado
            const metodoPago = document.getElementById('metodoPago').value;
            if (!metodoPago) {
              Swal.showValidationMessage('Por favor, selecciona un método de pago');
            }
            return metodoPago; // Retornar el valor seleccionado
          }
        }).then((result) => {
          if (result.isConfirmed) {
            const metodoPago = result.value;
            // Redirigir a pagos_Venta.php con el método de pago en la URL
            //console.log(`../creditos.php?pagoTipo=${encodeURIComponent(metodoPago)}&order_id=${compra}&precioPesoVenta=${precioPesoVenta}&precioBsVenta=${precioBsVenta}`)
            window.location.href = `../../configurar/pagar.php?pagoTipo=${encodeURIComponent(metodoPago)}&order_id=${compra}&precioPesoVenta=${precioPesoVenta}&precioBsVenta=${precioBsVenta}`;
          }
        });




        /*
          <form method="POST" action="../../configurar/pagar.php?id=' . $row6["order_id"] . '">
          <td class=" "><input type="text" name="tipo" hidden value="' . $row6["tipoCompra"] . '">
-lg-12 ">
          <select class="form-control" name="pagoTipo" required>
          <option value="">-- SELECCIONE --</option>
          <option value="1">Punto de venta</option>
          <option value="2">Pago Movil</option>
          <option value="3">Transferencia</option>
          <option value="7">Biopago</option>
          <option value="4">BS efectivo</option>
          <option value="5">Dolares</option>
          <option value="6">Pesos</option>
          </select>
          </div>
          </td>
          <td><button class="btn"><i class="fa gray fa-credit-card"></i></button></td>
          </form>

        */
      }
    </script>
  </body>

  </html>
<?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}

ob_end_flush();
?>