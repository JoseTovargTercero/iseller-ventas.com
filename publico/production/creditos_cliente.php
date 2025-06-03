<?php
ob_start();
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();

  $cliente = $_GET['cliente'];



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
                <img src='images/logo1-inv-compact.png' style='max-width:45px; opacity: 0.8'> <span>
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

                          <table id="tabla-creditos" class="table table-bordered" style="width:100%">
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

                            </tbody>
                          </table>



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
      const cliente = <?php echo json_encode($cliente); ?>;
      // cargar tabla
      async function cargarCreditos() {
        const tbody = document.querySelector('#tabla-creditos tbody');
        if (!tbody) return;

        try {
          const res = await fetch('../../configurar/creditos_por_cliente.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `cliente=${encodeURIComponent(cliente)}`
          });

          if (!res.ok) throw new Error('Error al obtener datos');
          const data = await res.json();

          // Limpia el tbody
          tbody.innerHTML = '';

          if (data.ordenes.length === 0) {
            window.location.href = 'creditos.php';
          }


          // Recorre cada orden
          data.ordenes.forEach(ord => {
            // 1. Fila por cada producto
            ord.productos.forEach(sub => {
              const {
                nombre,
                precio_dolar_visible,
                precio_peso_visible,
                precio_bs_visible
              } = sub.datos;
              const cantidad = sub.cantidad;

              const usd = (precio_dolar_visible * cantidad).toFixed(2);
              const cop = (precio_peso_visible * cantidad).toLocaleString('es-CO');
              const bs = (precio_bs_visible * cantidad).toLocaleString('es-VE', {
                minimumFractionDigits: 2
              });

              tbody.insertAdjacentHTML('beforeend', `
              <tr>
                <td></td>
                <td>${nombre}</td>
                <td class="text-center">${usd} <small>$</small></td>
                <td class="text-center">${cop} <small>Cop</small></td>
                <td class="text-center">${bs} <small>Bs</small></td>
                <td></td>
              </tr>
            `);
            });

            // 2. Fila totales por orden
            const ts = ord.totales;
            tbody.insertAdjacentHTML('beforeend', `
        <tr style="background-color: rgba(0,0,0,.05);">
          <td>COMPRA: <b>${ord.id}</b></td>
          <td>Fecha: ${ord.fecha}</td>
          <td class="text-center"><b>TOTAL: ${ts.usd.toFixed(2)}<small></small></b></td>
          <td class="text-center"><b>TOTAL: ${ts.cop.toLocaleString('es-CO')}<small></small></b></td>
          <td class="text-center"><b>TOTAL: ${ts.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}<small></small></b></td>
          <td class="text-center">
            <button
              data-tipoCompra="${ord.tipoCompra}"
              data-precioPesoVenta="${ts.cop}"
              data-precioBsVenta="${ts.bs}"
              data-id_credito="${ord.id_credito}"
              data-id="${ord.id}"
              class="btn btn-pagar btn-sm btn-info">
              Pagar
            </button>
          </td>
        </tr>
      `);
          });

          // 3. Fila totales globales
          const tg = data.totales_global;
          tbody.insertAdjacentHTML('beforeend', `
      <tr style="background-color: rgba(0,0,0,.2);">
        <td colspan="2">DEUDA TOTAL:</td>
        <td class="text-center"><b>${tg.usd.toFixed(2)} <small>$</small></b></td>
        <td class="text-center"><b>${tg.cop.toLocaleString('es-CO')} <small>Cop</small></b></td>
        <td class="text-center"><b>${tg.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })} <small>Bs</small></b></td>
        <td></td>
      </tr>
    `);

        } catch (error) {
          console.error(error);
          tbody.innerHTML = `
      <tr><td colspan="6" class="text-center text-danger">No fue posible cargar los créditos.</td></tr>
    `;
        }
      }

      /* Ejecuta al cargar la página (o según tu framework) */
      document.addEventListener('DOMContentLoaded', cargarCreditos);

      // cargar tabla



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