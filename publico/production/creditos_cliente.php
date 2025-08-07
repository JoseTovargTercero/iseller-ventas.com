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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">

        <?php echo $menu ?>

        <!-- top navigation -->
        <?php echo $topnav ?>
        <!-- /top navigation -->
        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="mb-3 d-flex justify-content-between">
              <div>
                <h4>Creditos</h4>
                <p style="margin-top: -10px;">Listado de creditos otorgados</p>
              </div>
              <div>
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Creditos otorgados
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">Deuda total y abonos</button>
                  </li>
                </ul>
              </div>
            </div>



            <div class="row" style="display: block;">
              <div class="col-lg-12">


                <div class="tab-content" id="pills-tabContent">
                  <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                    <div class="x_panel  fadeInUp animated">
                      <div class="x_title">
                        <h2>Creditos otorgados</h2>

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
                                    <th class="column-title text-center">Pagar</th>
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
                  <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                      <div class="x_panel  fadeInUp animated">
                        <div class="x_title">
                          <h2>Deuda total y abonos</h2>

                          <div class="clearfix"></div>
                        </div>
                        <div class="x_content">
                          <div class="row">
                            <div class="col-lg-12">
                              <div class="card-box table-responsive">

                                <table id="tabla-abonos" class="table table-bordered" style="width:100%">
                                  <thead>
                                    <tr class="headings">
                                      <th class="column-title">#</th>
                                      <th class="column-title">Producto</th>
                                      <th class="column-title text-center">Valor ($)</th>
                                      <th class="column-title text-center">Valor (COP)</th>
                                      <th class="column-title text-center">Valor (BS)</th>
                                      <th class="column-title text-center">Pagar</th>
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
            </div>
          </div>
        </div>
        <!-- /page content -->

      </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Buttons extension -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- PDF export -->
    <script src="../build/js/custom.js"></script>


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

          console.log(res)
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


      // cargar tabla
      async function cargarDeuda() {
        const tbody = document.querySelector('#tabla-abonos tbody');
        if (!tbody) return;

        try {
          const res = await fetch('../../configurar/creditos_por_cliente_total_abonado.php', {
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

          const productos = data.productos || [];

          if (productos.length === 0) {
            window.location.href = 'creditos.php';
            return;
          }

          // Recorre productos uno por uno
          productos.forEach(prod => {
            const {
              nombre,
              precio_dolar,
              precio_peso,
              precio_bs,
              cantidad,
              total_usd,
              total_cop,
              total_bs
            } = prod;

            tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${prod.codigo}</td>
          <td>${nombre}</td>
          <td class="text-center">${total_usd.toFixed(2)} <small>$</small></td>
          <td class="text-center">${total_cop.toLocaleString('es-CO')} <small>Cop</small></td>
          <td class="text-center">${total_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })} <small>Bs</small></td>
          <td class="text-center">${cantidad}</td>
        </tr>
         `);
          });

          // Fila de totales globales
          const tg = data.deuda_restante;
          tbody.insertAdjacentHTML('beforeend', `
          <tr style="background-color: rgba(0,0,0,.2);">
            <td colspan="2"><b>DEUDA TOTAL:</b></td>
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

      // Finalizar cargar tablas

      document.addEventListener('DOMContentLoaded', cargarCreditos);
      //      document.addEventListener('DOMContentLoaded', cargarDeuda);

      // cargar tabla

      const opcionesPago = `
                    <select id="metodoPago" class="form-control">
                        <option value="">Seleccione</option>
                        <option value="1">Punto</option>
                        <option value="2">Pago Movil</option>
                        <option value="3">Transferencia</option>
                        <option value="7">BioPago</option>
                        <option value="4">Efectivo</option>
                        <option value="5">Dolares</option>
                        <option value="6">Pesos</option>
                    </select>`;

      document.addEventListener('click', function(event) {

        if (event.target.closest('.btn-pagar')) { // ACCION DE ELIMINAR
          const elemento = event.target.closest('.btn-pagar')

          const data_id_credito = elemento.getAttribute('data-id_credito');
          const data_id = elemento.getAttribute('data-id');
          const precioPesoVenta = elemento.getAttribute('data-precioPesoVenta')
          const precioBsVenta = elemento.getAttribute('data-precioBsVenta')
          const tipoCompra = elemento.getAttribute('data-tipoCompra')

          console.log(data_id_credito)
          console.log(data_id)
          console.log(precioPesoVenta)
          console.log(precioBsVenta)
          console.log(tipoCompra)
          return


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
            // window.location.href = `../../configurar/pagar.php?pagoTipo=${encodeURIComponent(metodoPago)}&order_id=${compra}&precioPesoVenta=${precioPesoVenta}&precioBsVenta=${precioBsVenta}`;
            // Datos a enviar
            const datos = {
              pagoTipo: metodoPago,
              order_id: compra,
              precioPesoVenta: precioPesoVenta,
              precioBsVenta: precioBsVenta,
            };

            fetch('../../configurar/pagar.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  // recarga la pagina
                  location.reload();
                } else {
                  // Error del backend
                  alert(`Error: ${data.message}`);
                  if (data.error) console.error(data.error); // Error técnico (si lo hay)
                }
              })
              .catch(error => {
                console.error('Error en la solicitud:', error);
                alert('Hubo un problema con la conexión al servidor.');
              });

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