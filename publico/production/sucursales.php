<?php

require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');




if ($_SESSION['nivel'] == 1) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Inicio == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    $topnav = topnav();
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }


?>

    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Sucursales </title>

        <?php
        require_once('includes/headers.php');
        ?>
    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>

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

                <!-- page content -->
                <div class='right_col'>
                    <h4>Sucursales</h4>
                    <p style="margin-top: -10px;">Gestión de sucursales</p>
                    <div class='row to-animate fadeInRight animated'>
                        <div class='col-lg-12'>
                            <div class='x_panel tile'>
                                <div class='x_title d-flex justify-content-between'>
                                    <h2>Sucursales</h2>
                                    <button class="btn btn-sm btn-success" id="btn-add-sucursal"> <i class="bx bx-plus"></i> Agregar</button>
                                </div>
                                <div class='x_content'>
                                    <div class='card-box table-responsive'>
                                        <table id='datatable-responsive' class='table table-striped' style='width:100%'>
                                            <thead>
                                                <tr class='headings'>
                                                    <th class='column-title'>Tipo</th>
                                                    <th class='column-title'>Nombre</th>
                                                    <th class='column-title text-center'>Usuarios</th>
                                                    <th class='column-title text-center'>Productos</th>
                                                    <th class='column-title text-center'></th>
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
                <!-- /page content -->
                <?php require('../assets/templates/modal.html'); ?>

                <!-- footer content -->


                <!-- /footer content -->
            </div>
        </div>

        <!-- jQuery -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <!-- FastClick -->
        <script src="../vendors/fastclick/lib/fastclick.js"></script>

        <script src="../vendors/nprogress/nprogress.js"></script>

        <script src="../build/js/custom.min.js"></script>
        <script src="../build/js/modal.js"></script>



        <script>
            const formSucursal = `
                <h5 class="mb-0">Registro de sucursales</h5>
                <hr>
                <form id="sucursal-form" class="p-0">
                    <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre de sucursal:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo de sucursal:</label>
                    <input list="tipos-comercio" id="tipo" name="tipo" class="form-control" required>
                    <datalist id="tipos-comercio">
                        <option value="Panadería"></option>
                        <option value="Carnicería"></option>
                        <option value="Heladería"></option>
                        <option value="Frigorífico"></option>
                        <option value="Supermercado"></option>
                        <option value="Verdulería"></option>
                        <option value="Pescadería"></option>
                        <option value="Tienda de Ropa"></option>
                        <option value="Joyería"></option>
                        <option value="Farmacia"></option>
                        <option value="Ferretería"></option>
                        <option value="Papelería"></option>
                        <option value="Librería"></option>
                        <option value="Tienda de Electrónica"></option>
                        <option value="Tienda de Mascotas"></option>
                        <option value="Floristería"></option>
                        <option value="Barbería"></option>
                        <option value="Peluquería"></option>
                        <option value="Restaurante"></option>
                        <option value="Cafetería"></option>
                        <option value="Tienda de Deportes"></option>
                        <option value="Juguetería"></option>
                        <option value="Boutique"></option>
                        <option value="Auto Lavado"></option>
                        <option value="Gimnasio"></option>
                        <option value="Tienda de Muebles"></option>
                        <option value="Centro de Estética"></option>
                        <option value="Tienda de Telefonía"></option>
                        <option value="Otro"></option>
                    </datalist>
                    </div>

                        <div class="mb-3">
                    <label for="productos_accion" class="form-label">Acción para los productos registrados:</label>
                    <select type="text" id="productos_accion" name="productos_accion" class="form-control" required>
                            <option value="">Seleccione</option>
                            <option value="copiar">Copiar todos los productos</option>
                            <option value="no_copiar">No copiar ninguno</option>
                    </select>
                    </div>



                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                </form>`;


            $(document).ready(function() {
                document.getElementById('modal').innerHTML = formSucursal;
                document.getElementById('w-modal').classList.add('modal-w50');

                document.getElementById('btn-add-sucursal').addEventListener('click', function() {
                    showModal()
                })


                document.getElementById('sucursal-form').addEventListener('submit', function(e) {
                    e.preventDefault(); // Evitar envío tradicional

                    const form = e.target;
                    const formData = new FormData(form);

                    fetch('../../configurar/sucursales.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.text())
                        .then(text => {
                            console.log("Respuesta cruda del servidor:", text);
                            let json;
                            try {
                                json = JSON.parse(text);
                            } catch (e) {
                                console.error("Error al parsear JSON:", e);

                                Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                                return;
                            }

                            if (json.success) {
                                modalContainer.classList.remove("active");

                                Alerta.mostrar('success', json.message);
                                form.reset(); // Opcional: limpia el formulario
                                cargar_tabla()
                            } else {

                                Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
                            }
                        })
                        .catch(err => {
                            modalContainer.classList.remove("active");

                            console.error("Error en la solicitud:", err);
                            Alerta.mostrar('error', 'No se pudo contactar con el servidor');
                        });
                });


            })

            function cargar_tabla() {
                fetch('../../configurar/sucursales_lista.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            table: 'sucursales',
                            config: '_sucursales'
                        })
                    })
                    .then(response => response.text()) // Primero obtenemos el texto plano
                    .then(text => {
                        //console.log("Respuesta cruda:", text); // Debug: ver el texto antes del parseo

                        try {
                            const data = JSON.parse(text); // Luego lo intentamos convertir a JSON
                            // recorre data [{"id":1,"tipo":"BODEGA","nombre":"YOLA MARKET S1","productos":0,"usuarios":1},{"id":2,"tipo":"bodega","nombre":"Maisanta barato","productos":0,"usuarios":0}]
                            // imprime los resultados en datatable-responsive [Tipo	Nombre	Usuarios	Productos]
                            const $tabla = $('#datatable-responsive tbody');
                            $tabla.empty(); // Limpiamos filas anteriores

                            if (data.length > 0) {
                                data.forEach(item => {
                                    const fila = `
                                    <tr>
                                        <td>${item.tipo}</td>
                                        <td>${item.nombre}</td>
                                        <td class='text-center'>${item.usuarios}</td>
                                        <td class='text-center'>${item.productos}</td>
                                        <td class='text-center'>${item.id}</td>
                                    </tr>
                                `;
                                    $tabla.append(fila);
                                });
                            } else {
                                $tabla.append('<tr><td colspan="4">-- Sin resultados --</td></tr>');
                            }
                        } catch (e) {
                            console.error("Error al parsear JSON:", e, "\nTexto recibido:", text);
                        }
                    })
                    .catch(error => {
                        console.error("Error en la solicitud:", error);
                    });
            }

            cargar_tabla()

            /*
                        document.addEventListener("DOMContentLoaded", function() {

                        });*/
        </script>



    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
    ?>