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
    <link rel="stylesheet" href="theme.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>


    <style>
      .swal2-container {
        z-index: 99999;
      }


      .loader {
        width: 48px;
        height: 6px;
        display: block;
        margin: auto;
        position: relative;
        border-radius: 4px;
        color: #FFF;
        box-sizing: border-box;
        animation: animloader 0.6s linear infinite;
      }

      @keyframes animloader {
        0% {
          box-shadow: -10px 20px, 10px 35px, 0px 50px
        }

        25% {
          box-shadow: 0px 20px, 0px 35px, 10px 50px
        }

        50% {
          box-shadow: 10px 20px, -10px 35px, 0px 50px
        }

        75% {
          box-shadow: 0px 20px, 0px 35px, -10px 50px
        }

        100% {
          box-shadow: -10px 20px, 10px 35px, 0px 50px
        }
      }

      .contenedor-loader {
        z-index: 99999;
        background-color: #0000006b;
        height: 100%;
        width: 100%;
        place-items: center;
        display: grid;
        position: fixed;
      }
      .form-check {
  display: block;
  min-height: 1.5rem;
  padding-left: 2.5em;
  margin-bottom: 0.125rem;
}

.form-check-input {
  width: 2em;
  height: 1em;
  margin-left: -2.5em;
  vertical-align: top;
  background-color: #e9ecef;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='%236c757d'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: left center;
  background-size: contain;
  border-radius: 2em;
  appearance: none;
  border: 1px solid rgba(0,0,0,.25);
  transition: background-position .15s ease-in-out,
              background-color .15s ease-in-out,
              border-color .15s ease-in-out,
              box-shadow .15s ease-in-out;
  cursor: pointer;
}
.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
  background-position: right center;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3E%3Ccircle r='3' fill='white'/%3E%3C/svg%3E");
}
.form-check-input:focus {
  outline: 0;
  box-shadow: 0 0 0 .25rem rgba(13,110,253,.25);
}
.form-check-label {
  cursor: pointer;
}
.form-switch {
  padding-left: 2.5em;
}

.form-switch .form-check-input {
  width: 2em;
}
    </style>
  </head>

  <body class="nav-md">

    <div class="contenedor-loader" id="cargando">
      <span class="loader"></span>
    </div>



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
                <div class="tab-content " id="pills-tabContent">
                  <div class="tab-pane fade   show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                    <div class="x_panel  fadeInUp animated">
                      <div class="x_title">
                        <div class="d-flex justify-content-between">
                          <h2>Creditos otorgados</h2>
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="mostrarValorInicial" >
                            <label class="form-check-label" for="mostrarValorInicial">Mostrar valor inicial</label>
                          </div>
                        </div>

                        <div class="clearfix"></div>
                      </div>
                      <div class="x_content">
                        <div class="row">
                          <div class="col-lg-12">
                            <div class="card-box table-responsive">

                              <table id="tabla-creditos" class="table table-bordered" style="width:100%">
                                <thead>
                                  <tr class="headings">
                                    <th class="column-title">Cant</th>
                                    <th class="column-title">Producto</th>
                                    <th class="column-title text-center">Valor ($)</th>
                                    <th class="column-title text-center">Valor (COP)</th>
                                    <th class="column-title text-center">Valor (BS)</th>
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
                      <div class="row">
                        <div class="col-lg-8">
                          <div class="x_panel  fadeInUp animated">
                            <div class="x_title">
                              <div class="d-flex justify-content-between">
                                <h2>Deuda total y abonos</h2>
                                <button class="btn btn-sm btn-danger" onclick="procesarPagosSecuencial()">Pagar Todo</button>
                              </div>
                              <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                              <div class="row">
                                <div class="col-lg-12">
                                  <div class="card-box table-responsive">

                                    <table id="tabla-abonos" class="table table-bordered" style="width:100%">
                                      <thead>
                                        <tr class="headings">
                                          <th class="column-title">Cant</th>
                                          <th class="column-title">Producto</th>
                                          <th class="column-title text-center">Valor ($)</th>
                                          <th class="column-title text-center">Valor (COP)</th>
                                          <th class="column-title text-center">Valor (BS)</th>
                                        </tr>
                                      </thead>

                                      <tbody>

                                      </tbody>
                                    </table>
                                    <div id="alert-pagado">

                                    </div>



                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="col-lg-4">
                          <div class="x_panel  fadeInUp animated">
                            <div class="x_title">
                              <div class="d-flex justify-content-between">
                                <h2>Abonos</h2>
                              </div>



                              <div class="clearfix"></div>
                            </div>
                            <div class="x_content">
                              <div id="abonos-list"></div>
                              <form class="d-flex align-items-center mt-3" id="data-form">
                                <!-- Input monto -->
                                <input type="text" id="monto" class="form-control me-1" placeholder="Monto">

                                <!-- Select moneda -->
                                <select id="moneda" class="form-control mr-2" style="width: fit-content;">
                                  <option value="USD">USD</option>
                                  <option value="Bs">Bs</option>
                                  <option value="COP">COP</option>
                                </select>

                                <!-- Botón enviar -->
                                <button type="submit" class="btn btn-success">
                                  <ion-icon class="text-white" name="save-outline"></ion-icon>
                                </button>
                              </form>


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
                precio_bs_visible,
              } = sub.datos;
              const cantidad = sub.cantidad;

              const usd = (precio_dolar_visible * cantidad).toFixed(2);
              const cop = (precio_peso_visible * cantidad).toLocaleString('es-CO');
              const bs = (precio_bs_visible * cantidad).toLocaleString('es-VE', {
                minimumFractionDigits: 2
              });

              tbody.insertAdjacentHTML('beforeend', `
              <tr>
                <td>${sub.cantidad}</td>
                <td>${nombre}</td>
                <td class="text-center">${usd} <small>$</small></td>
                <td class="text-center">${cop} <small>Cop</small></td>
                <td class="text-center">${bs} <small>Bs</small></td>
              </tr>
            `);
            });

            // 2. Fila totales por orden
            const ts = ord.totales;
            const ts_inicial = ord.totales_iniciales;
            const ts_inicial_u =  ts_inicial.usd.toFixed(2)
            const ts_inicial_c =  (ts_inicial.cop > 0) ? ts_inicial.cop.toLocaleString('es-CO') : '0,00'
            const ts_inicial_b =  (ts_inicial.bs > 0) ? ts_inicial.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 }) : '0,00'

            
            tbody.insertAdjacentHTML('beforeend', `
        <tr style="background-color: rgba(0,0,0,.05);">
          <td>COMPRA: <b>${ord.id}</b></td>
          <td>Fecha: ${ord.fecha}</td>
          <td class="text-center"><b>TOTAL: 
          
          <span class="total_final">${ts.usd.toFixed(2)}</span>
          <span class="total_inicial hide">${ts_inicial_u}</span>
          <small>$</small></b></td>
          <td class="text-center"><b>TOTAL: 
          
          <span class="total_final">${ts.cop.toLocaleString('es-CO')}</span>
          <span class="total_inicial hide">${ts_inicial_c}</span>
          
          <small>Cop</small></b></td>
          <td class="text-center"><b>TOTAL: 
          
          <span class="total_final">${ts.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</span>
          <span class="total_inicial hide">${ts_inicial_b}</span>
          
          <small>Bs</small></b>
              <button
              data-tipoCompra="${ord.tipoCompra}"
              data-precioPesoVenta="${ts.cop}"
              data-precioBsVenta="${ts.bs}"
              data-id_credito="${ord.id_credito}"
              data-id="${ord.id}"
              class="btn-pagar hide">
              Pagar
            </button>
          </td>
        </tr>
          `);
          });

          // 3. Fila totales globales
          const tg = data.totales_global;
          const tg_inicial = data.totales_global;
          const tg_inicial_u =  tg_inicial.total_inicial_dolares.toFixed(2)
          const tg_inicial_c =  (tg_inicial.total_inicial_cop > 0) ? tg_inicial.total_inicial_cop.toLocaleString('es-CO') : '0,00'
          const tg_inicial_b =  (tg_inicial.total_inicial_bs > 0) ? tg_inicial.total_inicial_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 }) : '0,00'
          tbody.insertAdjacentHTML('beforeend', `
          <tr style="background-color: rgba(0,0,0,.2);">
            <td colspan="2">DEUDA TOTAL:</td>
            <td class="text-center"><b>
            
            <span class="total_final">${tg.usd.toFixed(2)}</span>
            <span class="total_inicial hide">${tg_inicial_u}</span>
            
            <small>$</small></b></td>
            <td class="text-center"><b>
            
            <span class="total_final">${tg.cop.toLocaleString('es-CO')}</span>
            <span class="total_inicial hide">${tg_inicial_c}</span>
            
            <small>Cop</small></b></td>
            <td class="text-center"><b>
            
            <span class="total_final">${tg.bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</span>
            <span class="total_inicial hide">${tg_inicial_b}</span>
            
            <small>Bs</small></b></td>
          </tr>
        `);

        } catch (error) {
          console.error(error);
          tbody.innerHTML = `
          <tr><td colspan="6" class="text-center text-danger">No fue posible cargar los créditos.</td></tr>
        `;
        }
      }

      document.getElementById("mostrarValorInicial").addEventListener("change", function() {
        const mostrarValorInicial = this.checked;
        
        const totalFinal = document.querySelectorAll('.total_final');
        const totalInicial = document.querySelectorAll('.total_inicial');

        totalFinal.forEach(span => {
          if (mostrarValorInicial) {
            span.classList.add('hide');
          } else {
            span.classList.remove('hide');
          }
        });

        totalInicial.forEach(span => {
          if (mostrarValorInicial) {
            span.classList.remove('hide');
          } else {
            span.classList.add('hide');
          }
        });
      });

      function clickearBotones() {
        document.querySelectorAll('.btn-pagar').forEach(boton => {
          boton.click();
        });
      }




      document.getElementById("data-form").addEventListener("submit", async function(e) {
        e.preventDefault();

        // Tomar valores
        const monto = document.getElementById("monto").value.trim();
        const moneda = document.getElementById("moneda").value.trim();

        // Validación simple
        if (!monto || !moneda) {
          alert("Por favor completa todos los campos");
          return;
        }

        // Preparar datos
        const datos = {
          cliente: cliente, // constante definida globalmente
          monto: monto,
          moneda: moneda
        };

        try {
          const response = await fetch("../../configurar/registrar_abono.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json"
            },
            body: JSON.stringify(datos)
          });

          const result = await response.json();
          if (result.success) {
            cargarDeuda()
            Alerta.toast("success", " Abono registrado correctamente");
            // limpiar input
            document.getElementById("monto").value = "";
          } else {
            Alerta.toast("error", "Error: " + result.message);
          }
        } catch (error) {
          console.error("Error en la petición:", error);
          Alerta.toast("error", "Error al registrar el abono");
        }
      });



      const colores_monedas = {
        'usd': 'bg-success',
        'cop': 'bg-info',
        'bs': 'bg-danger'
      };


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


          const abonos_div = document.querySelector('#abonos-list');
          const abonos = data.abonos || [];
          abonos_div.innerHTML = ``;

          if (abonos.length === 0) {
            abonos_div.innerHTML = ``;
          } else {
            abonos.forEach(abns => {
              console.log(abns)
              //here
              abonos_div.insertAdjacentHTML('beforeend', `
                <div class="d-flex justify-content-between py-2 g-0 border-bottom border-200">
                  <div class="py-1">
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-xl me-3">
                        <div class="avatar-name rounded-circle ${colores_monedas[abns.moneda]}">
                       <span class="fs-9">${abns.moneda.toUpperCase().substring(0,1)}</span>
                        </div>
                      </div>
                      <h6 class="d-flex mb-0 align-items-center">
                        <span>
                          <p class="m-0" style="font-size: 12px;"> ${formatearMiles(abns.monto)} <small>${abns.moneda.toUpperCase()}</small> </p>
                          <small class="text-muted">${abns.fecha}</small>
                        </span>
                      </h6>
                    </div>
                  </div>
                  <div class="p-2 text-end">
                    <div class="fs-15 fw-semibold"></div>
                    <small class="text-muted">
                    <ion-icon data-id="${abns.id}" class="text-danger fz-16 pointer delete-abono" name="close-outline"></ion-icon>
                    </small>
                  </div>
                </div>
              `);
            });
          }

          document.querySelectorAll('.delete-abono').forEach(btn => {
            btn.addEventListener('click', function() {
              const abonoId = this.getAttribute('data-id');
              // Confirmar eliminación

              Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminara el abono.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
              }).then(async (result) => {
                if (result.isConfirmed) {
                  eliminarAbono(abonoId);
                }
              });
            });
          });

          function eliminarAbono(id) {
            fetch('../../configurar/eliminar_abono.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${encodeURIComponent(id)}`
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  Alerta.toast('success', ' Abono eliminado correctamente');
                  cargarDeuda();
                } else {
                  Alerta.toast('error', 'Error: ' + data.message);
                }
              })
              .catch(error => {
                console.error('Error en la petición:', error);
                Alerta.toast('error', 'Error al eliminar el abono');
              });
          }






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

            const clase = (total_usd.toFixed(2) === '0.00') ? 'text-muted tachado' : '';
            tbody.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${prod.cantidad}</td>
          <td>${nombre}</td>
          <td class="text-center ${clase}">${total_usd.toFixed(2)} <small>$</small></td>
          <td class="text-center ${clase}">${total_cop.toLocaleString('es-CO')} <small>Cop</small></td>
          <td class="text-center ${clase}">${total_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })} <small>Bs</small></td>
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
          </tr>
        `);


          if (tg.usd.toFixed(2) === '0.00') {
            document.querySelector('#alert-pagado').innerHTML = `
            <div class="alert alert-success d-flex justify-content-between" role="alert">
              <p>
              <strong>¡Todo pagado!</strong> No hay deuda pendiente.
              </p>
              <button class="btn btn-sm btn-success" onclick="procesarPagosSecuencial()">Pagar Todo</button>
            </div>
          `;
          } else {
            document.querySelector('#alert-pagado').innerHTML = ``;
          }




        } catch (error) {
          console.error(error);
          tbody.innerHTML = `
          <tr><td colspan="5" class="text-center text-danger">No fue posible cargar los créditos.</td></tr>
        `;
        }
      }

      // Finalizar cargar tablas

      document.addEventListener('DOMContentLoaded', cargarCreditos);
      document.addEventListener('DOMContentLoaded', cargarDeuda);

      // cargar tabla







      function procesarPagosSecuencial() {
        const botones = Array.from(document.querySelectorAll('.btn-pagar'));
        if (botones.length === 0) {
          alert("No hay botones para procesar");
          return;
        }

        Swal.fire({
          title: '¿Estás seguro?',
          text: "Esta acción cambiara el estatus de todos los creditos a 'pagado'.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, procesar',
          cancelButtonText: 'Cancelar'
        }).then((result) => {
          if (result.isConfirmed) {
            procesarPagos();
          }
        });
      }


      async function procesarPagos() {
        const botones = Array.from(document.querySelectorAll('.btn-pagar'));
        if (botones.length === 0) {
          alert("No hay botones para procesar");
          return;
        }

        $('#cargando').show();


        for (let boton of botones) {
          // Extraer atributos del botón
          const data_id_credito = boton.getAttribute('data-id_credito');
          const data_id = boton.getAttribute('data-id');
          const precioPesoVenta = boton.getAttribute('data-precioPesoVenta');
          const precioBsVenta = boton.getAttribute('data-precioBsVenta');
          const tipoCompra = boton.getAttribute('data-tipoCompra');

          try {
            await pagar(data_id_credito, data_id, precioPesoVenta, precioBsVenta, tipoCompra);
          } catch (error) {
            console.error("Error procesando el pago:", error);
            alert("Error en un pago, se detiene el proceso.");
            break; // Si hay error, salimos del bucle
          }
        }

        $('#cargando').hide();
        window.location.href = `creditos.php`;

      }



      // Convertimos pagar en función async
      async function pagar(credito, compra, precioPesoVenta, precioBsVenta, tipoCompra) {
        const metodoPago = 1;
        const datos = {
          pagoTipo: metodoPago,
          order_id: compra,
          precioPesoVenta: precioPesoVenta,
          precioBsVenta: precioBsVenta,
        };

        const response = await fetch('../../configurar/pagar.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(datos)
        });

        const data = await response.json();

        if (data.success) {
          console.log(`Pago procesado: ${compra}`);

          /*if (data.message == "finalizado") {
            window.location.href = `creditos.php`;
          }*/
          return data;
        } else {
          throw new Error(data.message || "Error en el backend");
        }
      }


      $(document).ready(function() {
        document.getElementById("cargando").style.display = "none";
      });




      /*
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

              function procesarPagosSecuencial() {

              }



            }*/
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