<?php

require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();

    $stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ? ORDER BY principal DESC");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sucursales = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sucursales[] = $row;
        }
    }
    $stmt->close();



?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <title>Productos</title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">

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
                <div class="right_col">
                    <div class="col-lg-12">
                        <h4>Productos</h4>
                        <p style="margin-top: -10px;">Listado de productos</p>

                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <script>
                            // Ejecutar actualización cada segundo
                            $(document).ready(function() {
                                ['precioMonedaOrigen', 'cantidad', 'porcentaje'].forEach(element => {
                                    document.getElementById(element).addEventListener('keyup', () => realizarCalculos())
                                });

                                document.getElementById('origenProducto').addEventListener('change', () => realizarCalculos())
                            })

                            // Variables globales
                            let cambioDolar = parseFloat(<?php echo $bsDolar ?>);
                            const pesoDolar = parseFloat(<?php echo $pesoDolar ?>);
                            const pesoBolivar = parseFloat(<?php echo $peso_bolivar ?>);



                            // Realiza la conversión principal según la moneda seleccionada
                            function realizarCalculos() {
                                const precioInput = parseFloat(document.getElementById("precioMonedaOrigen").value) || 0;

                                let resultado = precioInput;

                                // Mostrar resultado convertido en input 'precio'
                                document.getElementById("precio").value = resultado;

                                // Calcular precios adicionales
                                calcularValoresExtras(precioInput);
                            }

                            // Cálculos adicionales como precio unitario, venta y conversiones
                            function calcularValoresExtras(precioCompra) {
                                const cantidad = parseInt(document.getElementById("cantidad").value) || 1;
                                const porcentaje = parseFloat(document.getElementById("porcentaje").value) || 0;
                                const origenProducto = document.getElementById("origenProducto").value;

                                try {
                                    // Calcular precio unitario
                                    const precioUnitario = (precioCompra / cantidad).toFixed(2);
                                    const precioDolarCompra = parseFloat(precioUnitario);

                                    // Precio de venta con porcentaje
                                    const precioDolarVenta = ((precioDolarCompra * porcentaje / 100) + precioDolarCompra).toFixed(2);

                                    // Convertir a pesos
                                    const pesoSalida = Math.round(precioDolarVenta * pesoDolar);

                                    // Convertir a bolívares dependiendo del tipo de origen
                                    let bolivarSalida;
                                    if (origenProducto === 'c') {
                                        bolivarSalida = ((pesoSalida / pesoBolivar) / 1000).toFixed(2);
                                    } else {
                                        bolivarSalida = (precioDolarVenta * cambioDolar).toFixed(2);
                                    }
                                    // Actualizar campos con resultados
                                    document.getElementById("resultado").value = `$ ${precioDolarCompra}`;
                                    document.getElementById("resultado2").value = `$ ${precioDolarVenta}`;
                                    document.getElementById("resultado3").value = `${formatNumber(pesoSalida)} COP`;
                                    document.getElementById("resultado4").value = `${formatNumber(bolivarSalida)} BS`;
                                } catch (error) {
                                    console.error("Error en los cálculos:", error);
                                }
                            }

                            // Formatear número con separadores de miles
                            function formatNumber(valor) {
                                return valor.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        </script>



                        <section id="section_edit" class="hide w-100">
                            <form id='form-data' class='form-horizontal form-label-left'>
                                <div class='row'>
                                    <div class='col-md-6 col-sm-6 '>
                                        <div class='x_panel'>
                                            <div class='x_title'>
                                                <h2>Datos del Producto <small>* obligatorio</small></h2>
                                                <div class='clearfix'></div>
                                            </div>
                                            <div class='x_content'>

                                                <input class="form-control" type="text" hidden name="codigoEditar" value="<?php echo $codeEditar ?>">


                                                <section id="sucursal_section" class='form-group mb-3'>
                                                    <label class='form-label' for='first-name'>Sucursal </label>
                                                    <select class="form-control" id="sucursal_a_editar" name="sucursal_a_editar">
                                                        <?php if (count($sucursales) > 1): ?>
                                                            <option value="">Todas las sucursales</option>
                                                        <?php endif; ?>

                                                        <?php foreach ($sucursales as $row): ?>
                                                            <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($row['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </section>

                                                <div class='form-group mb-3'>
                                                    <label class='form-label' for='first-name'>Nombre del producto</label>
                                                    <div class="row">
                                                        <div class='col-md-7  col-sm-7'>
                                                            <input type='text' id='nombre' name='nombre' required='required' class='form-control ' placeholder='Nombre del Producto'>
                                                        </div>
                                                        <div class='col-md-5 col-sm-5'>
                                                            <button type="button" style="text-wrap: nowrap;" class="btn w-100 btn-info" id="open-modal">Calcular precio</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class='col-lg-7 form-group'>
                                                        <label class='form-label ' for='first-name'>Precio de Compra </label>
                                                        <input type='text' id='precioMonedaOrigen' name='precioMonedaOrigen' required='required' class='form-control ' placeholder='Precio'>
                                                    </div>


                                                    <div class='col-lg-5 form-group'>
                                                        <label class='form-label ' for='first-name'>Origen </label>
                                                        <select class="form-control" required='required' name="origenProducto" id="origenProducto">
                                                            <option value="">Seleccione</option>
                                                            <option <?php echo ($origen == 'v' ? 'selected' : '') ?> value="v">Venezolano</option>
                                                            <option <?php echo ($origen == 'c' ? 'selected' : '') ?> value="c">Colombiano</option>
                                                        </select>

                                                    </div>

                                                </div>

                                                <input type='text' id='precio' name='precio' hidden required='required' class='form-control ' placeholder='Precio en dolares'>

                                                <div class="row mb-3">
                                                    <div class='col-lg-7 form-group'>
                                                        <label class='form-label ' for='first-name'>Unidades por bulto </label>
                                                        <input type='text' id='cantidad' name='cantidad' required='required' class='form-control ' placeholder='Unidades'>
                                                    </div>
                                                    <div class='col-lg-5 form-group'>

                                                        <label class='form-label ' for='first-name'>Porcentaje </label>
                                                        <input type='text' id='porcentaje' name='porcentaje' required='required' class='form-control ' placeholder='XXX'>
                                                        <?php

                                                        if ($origen == "nuevo") {
                                                            echo " <input type='text' hidden name='origen' value='nuevo'>";
                                                        } else {
                                                        }
                                                        ?>
                                                    </div>
                                                </div>



                                                <div class="row">
                                                    <div class="col-lg-7">
                                                        <div class='form-group'>
                                                            <label class='form-label ' for='first-name'>Proveedor </label>
                                                            <input type='text' id='proveedor' name='proveedor' required='required' class='form-control ' placeholder='Proveedor'>
                                                        </div>


                                                    </div>
                                                    <div class="col-lg-5">
                                                        <div class='form-group'>
                                                            <label class='form-label ' for='first-name'>Código de barras </label>
                                                            <input type='text' id='codigo_barra' name='codigo_barra' required='required' class='form-control ' placeholder='Código de barras'>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class='ln_solid'></div>

                                                <div class='d-flex justify-content-between'>
                                                    <button type='button' id="btn-cancelar" class="btn btn-danger">Cancelar</button>
                                                    <button type='submit' class="btn btn-success actualizar">Actualizar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='col-lg-6 '>
                                        <div class='x_panel'>
                                            <div class='x_title'>
                                                <h2>Precios de venta (Unidad)</h2>

                                                <div class='clearfix'></div>
                                            </div>
                                            <div class='x_content'>
                                                <br />
                                                <form class='form-label-left input_mask'>

                                                    <div class='form-group '>
                                                        <label class='form-label '>Precio de Compra ($USD)</label>
                                                        <input type='text' class='form-control' readonly='readonly' name='resultado' id='resultado'>
                                                    </div>

                                                    <div class='form-group row'>
                                                        <label class='form-label'>Precio de Venta ($USD)</label>
                                                        <input type='text' class='form-control' readonly='readonly' name='resultado2' id='resultado2'>
                                                    </div>

                                                    <div class='form-group row'>
                                                        <label class='form-label'>Precio de Venta (COP)</label>
                                                        <input type='text' class='form-control' readonly='readonly' name='resultado3' id='resultado3'>
                                                    </div>

                                                    <div class='form-group row'>
                                                        <label class='form-label'>Precio de Venta (BS)
                                                        </label>
                                                        <input class='date-picker form-control' type='text' readonly='readonly' name='resultado4' id='resultado4'>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </section>
                    </div>


                    <div class="col-lg-12" id="section_lista">
                        <div class="x_panel   fadeInUp animated">
                            <div class="w-100 x_title d-flex justify-content-between">
                                <h2>Productos</h2>
                                <select class="form-control" style="max-width: 200px;" id="sucursal-selector" name="sucursal-selector">
                                    <?php if (count($sucursales) > 1): ?>
                                        <option value="">Todas las sucursales</option>
                                    <?php endif; ?>

                                    <?php foreach ($sucursales as $row): ?>
                                        <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($row['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            </div>
                            <div class="clearfix"></div>
                            <div class="x_content">
                                <div class="row">

                                    <div class="col-lg-12">
                                        <div class="card-box table-responsive">

                                            <table id="datatable-responsive" class="table " style="width:100%">
                                                <thead>
                                                    <tr class="headings">
                                                        <th>#</th>
                                                        <th>Nombre </th>
                                                        <th>Compra</small></th>
                                                        <th>Cant.</th>
                                                        <th>%</th>
                                                        <th>Stock</th>
                                                        <th>USD</th>
                                                        <th>COP</th>
                                                        <th>BS</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
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


            <!-- jQuery -->
            <script src="../vendors/jquery/dist/jquery.min.js"></script>
            <!-- Bootstrap -->
            <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
            <!-- FastClick -->
            <!-- DataTables core -->
            <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
            <!-- Buttons extension -->
            <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
            <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
            <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
            <!-- PDF export -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
            <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
            <script src="../build/js/custom.js"></script>
            <!-- 


            <script src="../build/js/global-loader.js"></script>
                                    -->


            <script>
                const tabla = $('#datatable-responsive').DataTable();

                let productos_editar
                let metodo_editar

                // Actualizar el producto
                document.getElementById('form-data').addEventListener('submit', function(e) {
                    e.preventDefault(); // Evitar envío tradicional

                    const form = e.target;
                    const formData = new FormData(form);

                    formData.append('accion', 'editar');
                    formData.append('metodo', metodo_editar);
                    formData.append('id', productos_editar);

                    // Obtener valores
                    const valores = {
                        nombre: form.nombre.value,
                        precio: form.precioMonedaOrigen.value,
                        cantidad: form.cantidad.value,
                        porcentaje: form.porcentaje.value,
                        origen: form.origenProducto.value,
                        proveedor: form.proveedor.value,
                        codigo_barra: form.codigo_barra.value,
                        sucursal: document.getElementById('sucursal-selector').value,
                        sucursal_a_editar: document.getElementById('sucursal_a_editar').value
                    };

                    // Validación por método
                    switch (metodo_editar) {
                        case 'generales':
                            if (camposVacios([valores.nombre, valores.codigo_barra, valores.proveedor])) {
                                Alerta.mostrar('warning', 'Completa todos los campos: nombre, proveedor y código de barras.');
                                return;
                            }
                            break;

                        case 'precio':
                            if (camposVacios([valores.precio, valores.cantidad, valores.porcentaje, valores.origen])) {
                                Alerta.mostrar('warning', 'Completa todos los campos: precio, cantidad, porcentaje y origen.');
                                return;
                            }
                            break;

                        case 'porcentaje':
                            if (camposVacios([valores.porcentaje])) {
                                Alerta.mostrar('warning', 'El campo porcentaje no puede estar vacío.');
                                return;
                            }
                            if (!valores.sucursal && !valores.sucursal_a_editar) {
                                Alerta.mostrar('warning', 'Selecciona una sucursal.');
                                return;
                            }
                            break;
                    }


                    fetch('../../configurar/productos_gestor.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.text())
                        .then(text => {
                            let json;
                            try {
                                json = JSON.parse(text);
                            } catch (e) {
                                console.error("Error al parsear JSON:", e);

                                Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                                return;
                            }

                            if (json.success == true) {

                                Alerta.mostrar('success', json.message);
                                cancelarActualizacion()
                                const sucursal_filtro = document.getElementById('sucursal-selector').value
                                cargarProductos(sucursal_filtro)

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




                document.addEventListener("DOMContentLoaded", function() {
                    let input = document.getElementById("codigo_barra");
                    let lastScan = 0; // Guarda el tiempo del último escaneo
                    let timeoutDuration = 6000; // Tiempo en milisegundos para bloquear (ajústalo según tu lector)

                    input.addEventListener("input", function() {});
                });


                let productos = []

                // Cargar etabla de productos
                function cargarProductos(sucursal = '') {

                    fetch('../../configurar/productos_list.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                sucursal: sucursal
                            })
                        })
                        .then(response => response.text()) // Primero obtenemos el texto plano
                        .then(text => {
                            try {
                                const data = JSON.parse(text);

                                if (data.status === "success") {
                                    // Limpia la tabla antes de insertar nuevos datos
                                    tabla.clear();

                                    let contador = 1;
                                    // Agrega fila por fila
                                    data.data.forEach(row => {
                                        productos[row.id] = row

                                        if (row.nombre == 'SPEED MAX BEBIDA ENERGETICA 269ml') {
                                            console.log(row.nombre)
                                            console.log(row.id)
                                        }

                                        const rest = (row.mayor == '1' ? '<span style="margin: 5px;" class="fw-medium text-decoration-none me-2 badge badge-subtle-success">Mayor</span>' : row.stock)



                                        tabla.row.add([
                                            contador++,
                                            row.nombre,
                                            row.precio_compra,
                                            row.cantidad_unidades,
                                            row.porcentaje,
                                            rest,
                                            formatNumber(row.precio_venta_dolar),
                                            formatNumber(formatPeso(row.precio_venta_peso)),
                                            formatNumber(recortarADosDecimales(row.precio_venta_bs)),
                                            `<a data-id="${row.id}" class="btn-edit"><i class="icon-gray line icon-pencil"></i></a>`,
                                            `<a href="ficha.php?id=${row.id}"><i style="color: #41c1af" class="icon-gray line icon-chart"></i></a>`,
                                            `<a class="c-pointer btn-delete" data-stock_id="${row.stock_id}" data-id="${row.id}"><i class="icon-gray line icon-trash"></i></a>`
                                        ]);
                                    });

                                    tabla.draw(); // Redibuja la tabla

                                    if (sucursal == '') {
                                        tabla.column(4).visible(false);
                                    } else {
                                        tabla.column(4).visible(true);
                                    }

                                } else {
                                    console.error("Error:", data.message);
                                }

                            } catch (e) {
                                console.error("Error al parsear JSON:", e, "\nTexto recibido:", text);
                            }
                        })
                        .catch(error => {
                            console.error("Error en la solicitud:", error);
                        });

                }

                $(document).ready(function() {
                    cargarProductos($('#sucursal-selector').val());
                    // Filtro por sucursal (opcional)
                    $('#sucursal-selector').on('change', function() {
                        cargarProductos(this.value);
                    });
                });


                // Escuchar el evento keydown en todo el documento
                document.addEventListener('keydown', function(event) {
                    // Si se presiona Enter y el foco no está en un textarea
                    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                        event.preventDefault(); // Evita que se dispare el submit
                        return false;
                    }
                });



                const nv = "<?php echo $_SESSION["nivel"] ?>"


                document.addEventListener('click', async (event) => {
                    if (event.target.closest('.btn-edit')) {
                        const id = event.target.closest('.btn-edit').getAttribute('data-id');


                        const sucursalSeleccionada = document.querySelector('#sucursal-selector')?.value;

                        if (sucursalSeleccionada !== '' && nv != 1) {
                            // Si hay una sucursal seleccionada, omitir el Swal y continuar con 'porcentaje'
                            const opcion = 'porcentaje';
                            cargarDatosForm(productos[id], opcion)

                            return;
                        }

                        const swalOptions = {
                            title: '¿Que desea hacer?',
                            icon: 'question',
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonText: 'Cancelar',
                            html: `
                            <div style="text-align: center;">
                                <div class="swal-option" data-option="generales" style="margin-bottom: 15px; cursor: pointer;">
                                    <strong class="text-info">Modificar datos generales del producto</strong><br>
                                    <em>Establece valores globales del producto. (nombre, código de barras, proveedor, etc.)</em>
                                </div>
                                <div class="swal-option" data-option="precio" style="margin-bottom: 15px; cursor: pointer;">
                                    <strong class="text-info">Modificar precio general del producto</strong><br>
                                    <em>Define el precio base por defecto.</em>
                                </div>
                                <div class="swal-option" data-option="porcentaje" style="cursor: pointer;">
                                    <strong class="text-info">Modificar porcentaje por sucursal</strong><br>
                                    <em>Define la ganancia por cada sucursal.</em>
                                </div>
                            </div>`,
                            didOpen: () => {
                                const opciones = Swal.getHtmlContainer().querySelectorAll('.swal-option');
                                opciones.forEach(div => {
                                    div.addEventListener('click', () => {
                                        const opcion = div.getAttribute('data-option');
                                        Swal.close(); // Cierra el SweetAlert
                                        cargarDatosForm(productos[id], opcion)

                                    });
                                });
                            }
                        };
                        await Swal.fire(swalOptions);
                    }

                    if (event.target.closest('.btn-delete')) {
                        const id = event.target.closest('.btn-delete').getAttribute('data-id')
                        const stock_id = event.target.closest('.btn-delete').getAttribute('data-stock_id')
                        confirmarDelete(id, stock_id)
                    }
                });



                // Eliminar producto
                function confirmarDelete(id, stock_id) {
                    Swal.fire({
                        title: 'Esta seguro?',
                        html: 'Se eliminara el producto ¿desea continuar?',
                        icon: 'question',
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#32d7c0',
                        showCancelButton: true,

                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteProduct(id, stock_id)
                        }
                    })
                }

                function deleteProduct(id, stock_id) {
                    const sucursal = document.getElementById('sucursal-selector').value
                    const modo = (sucursal == '' ? 'p' : 's')

                    $.ajax({
                        url: '../../configurar/deleteProAjax.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id: id,
                            modo: modo,
                            stock_id: stock_id
                        },
                        success: function(response) {
                            if (response.success) {
                                Alerta.toast('success', response.message);
                                cargarProductos($('#sucursal-selector').val());
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                            alert('Ocurrió un error en la comunicación con el servidor.');
                        }
                    });
                }





                // Mostrar el formulario con las opciones indicadas
                function cargarDatosForm(datos, opcion) {
                    productos_editar = datos.id
                    metodo_editar = opcion
                    // Asignación de valores
                    document.getElementById('nombre').value = datos.nombre;
                    document.getElementById('precioMonedaOrigen').value = datos.precio_compra;
                    document.getElementById('cantidad').value = datos.cantidad_unidades;

                    const porcentajeLimpio = typeof datos.porcentaje === 'string' ?
                        datos.porcentaje.replace('%', '').trim() :
                        datos.porcentaje;
                    document.getElementById('porcentaje').value = porcentajeLimpio;

                    document.getElementById('origenProducto').value = datos.origen;
                    document.getElementById('proveedor').value = datos.proveedor;
                    document.getElementById('codigo_barra').value = datos.codigo_barras;

                    // Mostrar formulario de edición
                    document.getElementById('section_edit').classList.remove('hide');

                    // Acciones según opción
                    const campos = {
                        nombre: document.getElementById('nombre'),
                        precio: document.getElementById('precioMonedaOrigen'),
                        cantidad: document.getElementById('cantidad'),
                        porcentaje: document.getElementById('porcentaje'),
                        origen: document.getElementById('origenProducto'),
                        proveedor: document.getElementById('proveedor'),
                        codigo_barra: document.getElementById('codigo_barra')
                    };

                    const sucursalSection = document.getElementById('sucursal_section');
                    const sucursalSelector = document.getElementById('sucursal-selector');
                    const sucursalEditar = document.getElementById('sucursal_a_editar');

                    // Habilitar todos los campos primero
                    for (let key in campos) campos[key].disabled = false;

                    if (opcion === 'precio') {
                        // Desactivar todos excepto los permitidos
                        campos.nombre.disabled = true;
                        campos.proveedor.disabled = true;
                        campos.codigo_barra.disabled = true;

                    } else if (opcion === 'porcentaje') {
                        // Desactivar todos excepto porcentaje
                        campos.nombre.disabled = true;
                        campos.precio.disabled = true;
                        campos.cantidad.disabled = true;
                        campos.origen.disabled = true;
                        campos.proveedor.disabled = true;

                        // Manejo de sucursales
                        if (sucursalSelector.value === '') {
                            sucursalSection.classList.remove('hide');
                        } else {
                            sucursalSection.classList.add('hide');
                            sucursalEditar.value = sucursalSelector.value;
                        }
                    } else if (opcion === 'generales') {
                        campos.precio.disabled = true;
                        campos.cantidad.disabled = true;
                        campos.origen.disabled = true;
                        campos.porcentaje.disabled = true;
                    }

                    document.getElementById('section_lista').classList.add('hide');

                    realizarCalculos();
                }

                function cancelarActualizacion() {
                    document.getElementById('section_edit').classList.add('hide');
                    document.getElementById('section_lista').classList.remove('hide');

                    // Resetear el formulario
                    document.getElementById('form-data').reset();

                    // Reactivar todos los campos
                    const fields = document.querySelectorAll('#form-data input, #form-data select, #form-data textarea');
                    fields.forEach(field => {
                        field.disabled = false;
                    });

                    // Ocultar sección de sucursal (si se mostró)
                    const sucursalSection = document.getElementById('sucursal_section');
                    if (sucursalSection) {
                        sucursalSection.classList.add('hide');
                    }
                }

                // Restaurar el formulario al hacer clic en cancelar
                document.getElementById('btn-cancelar').addEventListener('click', cancelarActualizacion);

                // Al seleccionar una sucursal
                document.getElementById('sucursal_a_editar').addEventListener('change', (event) => {
                    actualizarSucursal(event.target.value);
                });


                function actualizarSucursal(sucursal) {
                    const idProducto = productos_editar;



                    fetch('../../configurar/porcentaje_producto_especifico.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                producto_id: idProducto,
                                sucursal: sucursal
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            //   Swal.close(); // Cerrar loader

                            if (data.success) {
                                const porcentajeInput = document.getElementById("porcentaje");
                                porcentajeInput.value = data.data;

                                realizarCalculos()
                            } else {
                                Alerta.mostrar('warning', data.mensaje);
                            }
                        })
                        .catch(error => {
                            //    Swal.close(); // Cerrar loader en caso de error
                            console.error('Error en la petición:', error);
                            Alerta.mostrar('error', 'Error de red o del servidor.');
                        });
                }
                //   
            </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>