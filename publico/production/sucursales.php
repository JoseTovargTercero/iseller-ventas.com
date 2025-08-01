<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {

    $topnav = topnav();

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
        <style>
            td {
                vertical-align: middle !important;
            }
        </style>
    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <?php echo $menu ?>

                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->

                <!-- page content -->
                <div class='right_col'>
                    <h4>Sucursales</h4>
                    <p style="margin-top: -10px;">Gestión de sucursales</p>




                    <section id="section_edit" class="hide w-100  to-animate fadeInRight animated">
                        <form id='form-data' enctype="multipart/form-data" class='form-horizontal form-label-left'>
                            <div class='row'>
                                <div class='col-md-7 col-sm-7 m-auto'>
                                    <div class='x_panel'>
                                        <div class='x_title'>
                                            <h2>Datos de las sucursal <small>* obligatorio</small></h2>
                                            <div class='clearfix'></div>
                                        </div>
                                        <div class='x_content'>
                                            <div class='form-group'>
                                                <label class='form-label ' for='first-name'>Nombre de sucursal </label>
                                                <input type='text' id='edit_nombre' name='edit_nombre' required='required' class='form-control '>
                                            </div>


                                            <div class='form-group'>
                                                <label class='form-label ' for='first-name'>Cantidad mínima para stock crítico</label>
                                                <input type='text' id='edit_stock_critico' name='edit_stock_critico' required='required' class='form-control '>
                                            </div>


                                            <!-- Carga de imagen -->
                                            <div class="mb-3">
                                                <label for="foto2" class="form-label fw-semibold">Logo de la sucursal (JPG o PNG)</label>
                                                <input type="file" id="foto2" name="foto2" class="form-control" accept=".jpg, .jpeg, .png" required>
                                            </div>



                                            <div class='ln_solid'></div>

                                            <div class='d-flex justify-content-between'>
                                                <button type='button' id="btn-cancelar" class="btn btn-danger">Cancelar</button>
                                                <button type='submit' class="btn btn-success actualizar">Actualizar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </section>


                    <div class='row to-animate fadeInRight animated' id="section_tabla">

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
                                                    <th class='column-title'></th>
                                                    <th class='column-title'>Tipo</th>
                                                    <th class='column-title'>Nombre</th>
                                                    <th class='column-title text-center'>Usuarios</th>
                                                    <th class='column-title text-center'>Productos</th>
                                                    <th class='column-title text-center'>Stock mínimo</th>
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

                <template id="template-form-sucursal">

                    <h5 class="mb-3">Registro de Sucursales</h5>
                    <hr>
                    <form id="sucursal-form" class="needs-validation" novalidate>

                        <!-- Nombre de sucursal -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre de sucursal</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" placeholder="Ej: Sucursal Centro"
                                required>
                        </div>

                        <!-- Tipo de sucursal -->
                        <div class="mb-3">
                            <label for="tipo" class="form-label fw-semibold">Tipo de sucursal</label>
                            <input list="tipos-comercio" id="tipo" name="tipo" class="form-control" placeholder="Ej: Panadería"
                                required>
                            <datalist id="tipos-comercio">
                                <option value="Panadería">
                                <option value="Carnicería">
                                <option value="Heladería">
                                <option value="Frigorífico">
                                <option value="Supermercado">
                                <option value="Verdulería">
                                <option value="Pescadería">
                                <option value="Tienda de Ropa">
                                <option value="Joyería">
                                <option value="Farmacia">
                                <option value="Ferretería">
                                <option value="Papelería">
                                <option value="Librería">
                                <option value="Tienda de Electrónica">
                                <option value="Tienda de Mascotas">
                                <option value="Floristería">
                                <option value="Barbería">
                                <option value="Peluquería">
                                <option value="Restaurante">
                                <option value="Cafetería">
                                <option value="Tienda de Deportes">
                                <option value="Juguetería">
                                <option value="Boutique">
                                <option value="Auto Lavado">
                                <option value="Gimnasio">
                                <option value="Tienda de Muebles">
                                <option value="Centro de Estética">
                                <option value="Tienda de Telefonía">
                                <option value="Otro">
                            </datalist>
                        </div>

                        <!-- Stock crítico -->
                        <div class="mb-3">
                            <label for="stock_critico" class="form-label fw-semibold">Cantidad mínima para stock crítico</label>
                            <input type="number" id="stock_critico" name="stock_critico" class="form-control" min="0"
                                placeholder="Ej: 10" required>
                        </div>

                        <!-- Carga de imagen -->
                        <div class="mb-3">
                            <label for="foto" class="form-label fw-semibold">Logo de la sucursal (JPG o PNG)</label>
                            <input type="file" id="foto" name="foto" class="form-control" accept=".jpg, .jpeg, .png" required>
                        </div>



                        <!-- Acción sobre productos -->
                        <div class="mb-3">
                            <label for="productos_accion" class="form-label fw-semibold">Acción para productos registrados</label>
                            <select id="productos_accion" name="productos_accion" class="form-control" required>
                                <option value="">Seleccione una opción</option>
                                <option value="copiar">Copiar todos los productos</option>
                                <option value="no_copiar">No copiar ninguno</option>
                            </select>
                        </div>

                        <!-- Botón -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>

                    </form>
                </template>
            </div>
        </div>

        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <script src="../vendors/nprogress/nprogress.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="../build/js/modal.js"></script>
        <script src="js/formHandler.js"></script>

        <script>
            const template = document.getElementById('template-form-sucursal');
            const formSucursal = template.content.cloneNode(true);
            document.getElementById('modal').innerHTML = '';
            document.getElementById('modal').appendChild(formSucursal);

            $(document).ready(function() {
                document.getElementById('w-modal').classList.add('modal-w50');

                document.getElementById('btn-add-sucursal').addEventListener('click', function() {
                    showModal()
                })

                document.getElementById('sucursal-form').addEventListener('submit', function(e) {
                    e.preventDefault(); // Evitar envío tradicional

                    const form = e.target;
                    const formData = new FormData(form);
                    // recorre los campos, todos son obligatorios
                    for (const [key, value] of formData.entries()) {
                        if (key === 'foto') continue; // Lo validamos aparte
                        if (!value) {
                            Alerta.toast('error', `Todos los campos son obligatorios.`);
                            return;
                        }
                    }

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

            let sucursales = []
            let sucursal_editar

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
                        try {
                            const data = JSON.parse(text); // Luego lo intentamos convertir a JSON
                            const $tabla = $('#datatable-responsive tbody');
                            $tabla.empty(); // Limpiamos filas anteriores

                            if (data.length > 0) {
                                data.forEach(item => {
                                    sucursales[item.id] = item
                                    const fila = `
                                    <tr>
                                        <td>
                                                <img class="avatar" 
                                                src="images/sucursal_logo/${item.id}.png" 
                                                height="50px" 
                                                onerror="this.onerror=null; this.src='images/sucursal_logo/default.png';">
                                            </td>
                                        <td>${item.tipo}</td>
                                        <td>${item.nombre}</td>
                                        <td class='text-center'>${item.usuarios}</td>
                                        <td class='text-center'>${item.productos}</td>
                                        <td class='text-center'>${item.stockCritico}</td>
                                        <td class='text-center'>
                                        <a data-id="${item.id}" class="pointer btn btn-sm btn-success btn-edit"><i class="line icon-pencil"></i></a>
                                        </td>
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


            // Editar stock critico
            document.addEventListener('click', async (event) => {
                if (event.target.closest('.btn-edit')) {
                    const id = event.target.closest('.btn-edit').getAttribute('data-id');
                    cargarDatosForm(sucursales[id])
                }
            });



            // Mostrar el formulario con las opciones indicadas
            function cargarDatosForm(datos) {
                sucursal_editar = datos.id
                // Asignación de valores
                document.getElementById('edit_nombre').value = datos.nombre;
                document.getElementById('edit_stock_critico').value = datos.stockCritico;

                // Mostrar formulario de edición
                document.getElementById('section_edit').classList.remove('hide');
                document.getElementById('section_tabla').classList.add('hide');
            }

            // Cancelar actualizacion
            function cancelarActualizacion() {
                document.getElementById('section_edit').classList.add('hide');
                document.getElementById('section_tabla').classList.remove('hide');

                // Resetear el formulario
                document.getElementById('form-data').reset();

                // Reactivar todos los campos
                const fields = document.querySelectorAll('#form-data input, #form-data select, #form-data textarea');
                fields.forEach(field => {
                    field.disabled = false;
                });
            }

            // Restaurar el formulario al hacer clic en cancelar
            document.getElementById('btn-cancelar').addEventListener('click', cancelarActualizacion);



            // Actualizar datos de la sucursal
            new FormHandler({
                formId: 'form-data',
                url: '../../configurar/editar_sucursal.php',
                data_extra: [
                    ['id_editar', () => sucursal_editar]
                ],
                onSuccess: (json, form) => {
                    Alerta.toast('success', json.mensaje);
                    cargar_tabla()
                    cancelarActualizacion()
                },
                onError: (json, form) => {
                    Alerta.toast('error', json.mensaje || 'Error desconocido.');
                    console.warn('Error en respuesta:', json);
                },
                onFail: (error, form) => {
                    console.error('Fallo en conexión o JSON:', error);
                }
            })
        </script>
    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
    ?>