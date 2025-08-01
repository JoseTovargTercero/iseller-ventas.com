<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {

    $topnav = topnav();
?>

    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Ventas </title>
        <?php require_once('includes/headers.php'); ?>
        <style>
            .list-group-item {
                background-color: transparent;
            }

            .active-item {
                background-color: #0a989a1f !important
            }
        </style>
    </head>



    <div class="contenedor-loader" id="cargando">
        <span class="loader"></span>
    </div>

    <body class='nav-md' style="background-color: #ebebeb;">
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>
                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->
                <div class="right_col h-100" role='main'>
                    <div class=''>

                        <h4 class="mb-0">Sucursales</h4>
                        <p>Seleccione una sucursal antes de proceder con la ventas</p>

                        <div class="row p-3">
                            <div class="col-lg-8 m-auto ">
                                <div class="x_panel" style="padding-bottom: 30px;">
                                    <div class="x_title">
                                        <h2 style="font-size: 15px; font-weight: bold">Seleccione una sucursal</h2>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content h-60">
                                        <ul class="list-group" id="sucursales">
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>
            <!-- jQuery -->
            <script src="../vendors/jquery/dist/jquery.min.js"></script>
            <!-- Bootstrap -->
            <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
            <!-- FastClick -->
            <script src="../vendors/fastclick/lib/fastclick.js"></script>
            <script src="../vendors/nprogress/nprogress.js"></script>
            <script src="../build/js/custom.js"></script>
            <script src='js/tablas.js'></script>
            <script src="../build/js/global-loader.js"></script>



            <!-- FastClick -->
            <script>
                // lista de ventas
                function cargar_tabla() {
                    const loader = new TablaLoader('../../configurar/DatabaseHandler/_DBH-select.php');

                    loader.cargar('sucursales', '_sucursales').then(data => {


                        // Verificamos si data es un array y está vacío
                        if (!Array.isArray(data) || data.length === 0) {
                            window.location.href = 'sucursales.php';
                            return;
                        }


                        if (data) {
                            const section = document.querySelector('#sucursales');
                            section.innerHTML = ''; // Limpiar la tabla antes de insertar
                            let c = 1
                            console.log('data')
                            data.forEach(data => {
                                const fila = document.createElement('tr');


                                fila.innerHTML = `
                                  <li id="s-${data.id}" class="list-group-item d-flex justify-content-between align-items-center" >
                                                ${data.nombre} 
                                               <button data-id='${data.id}' class="btn-def btn btn-sm btn-danger btn-delete" title="Seleccionar"><i class="bx bx-store"></i></button>
                                            </li>
                            `;
                                section.appendChild(fila);
                            });
                            getSucursalActivada();
                        }
                    });
                }
                cargar_tabla()

                // Llamar cuando cargue la página
                //   document.addEventListener('DOMContentLoaded', cargarUltimasOrdenes);


                async function def_s(id) {

                    const res = await fetch('../../configurar/seleccion_sucursal.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `sucursal=${encodeURIComponent(id)}`
                    });

                    if (!res.ok) throw new Error('Error al obtener datos');
                    const data = await res.json();

                    Alerta.toast((data.success ? 'success' : 'error'), data.success)
                    if (data.success) {
                        location.href = 'ventas.php'
                    }
                }

                document.addEventListener('click', function(event) {
                    if (event.target.closest('.btn-def')) {
                        const id = event.target.closest('.btn-def').getAttribute('data-id');
                        def_s(id)
                    }

                })


                async function getSucursalActivada() {
                    try {
                        const res = await fetch('../../configurar/seleccion_sucursal_activada.php');

                        if (res.status === 204) return; // nada que hacer

                        const {
                            ok,
                            sucursal
                        } = await res.json();
                        if (ok && sucursal) {

                            document.querySelectorAll('.active-item').forEach(el => {
                                el.classList.remove('active-item');
                            });

                            document.querySelector(`#s-${sucursal}`)
                                .classList.add('active-item');
                        }
                    } catch (err) {
                        console.error('Error obteniendo sucursal:', err);
                    }
                }
            </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>