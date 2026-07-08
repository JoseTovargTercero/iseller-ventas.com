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

        <title>Productos con stock crítico</title>
        <?php require_once('includes/headers.php'); ?>

        <?php
        @$accion = $_GET['accion'];
        @$origen = $_GET['origen'];

        switch ($accion) {


            case ('borrado'):
                echo '<script>
            function mensaje(){	
			alertify.success("Producto borrado correctamente"); }
            </script>
            <body onload="mensaje()">
            </body>';

                break;
        }

        ?>



    </head>

    <body class="nav-md">
        <div class="container body">
            <div class="main_container">

                <?php echo $menu ?>

                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->


                <!-- page content -->
                <div class="right_col">
                
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



                                                <section id="sucursal_section" class='form-group mb-3'>
                                                    <label class='form-label' for='first-name'>Sucursal </label>
                                                    <select class="form-control" id="sucursal_a_editar" name="sucursal_a_editar">
                                                        <?php if (count($sucursales) > 1): ?>
                                                            <option value="">Seleccione la sucursal</option>
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
                                <div class="d-flex flex-column">
                                    <h2>Productos</h2>
                                    <small>Puede descargar por proveedor usando el campo de búsqueda</small>
                                </div>

                                <select class="form-control" style="max-width: 200px;" id="sucursal-selector" name="sucursal-selector">
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
                                                        <th>Producto</th>
                                                        <th>Precio</th>
                                                        <th>Origen</th>
                                                        <th>Stock actual</th>
                                                        <th>Proveedor</th>
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
            <!-- /footer content -->

            <!-- jQuery -->
            <script src="../vendors/jquery/dist/jquery.min.js"></script>
            <!-- Bootstrap -->
            <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

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
            <script src="../build/js/global-loader.js"></script>
            <!--<script src="../build/js/global-loader.js"></script>
                                    -->
        <script src="js/nombre_pagina.js"></script>


            <script>
                const tabla = $('#datatable-responsive').DataTable({

                    dom: 'Bfrtip',
                    buttons: [{
                        extend: 'pdfHtml5',
                        text: 'Descargar PDF',
                        title: '', // deja vacío
                        orientation: 'portrait', // Vertical
                        pageSize: 'A4',
                        exportOptions: {
                            columns: ':visible'
                        },
                        customize: function(doc) {
                            const n_sucursal = document.querySelector('#sucursal-selector option:checked').textContent;
                            doc.styles.tableHeader.alignment = 'left';

                            // Centrar el título
                            doc.content.unshift({
                                text: [{
                                        text: 'Listado de productos con stock crítico\n',
                                        fontSize: 14,
                                        bold: true
                                    },
                                    {
                                        text: 'Sucursal ' + n_sucursal.trim() + '\n',
                                        fontSize: 12
                                    },
                                    {
                                        text: 'Fecha: ' + new Date().toLocaleDateString(),
                                        fontSize: 10
                                    }
                                ],
                                alignment: 'center',
                                margin: [0, 0, 0, 12]
                            });



                            doc.styles.title = {
                                alignment: 'center',
                                fontSize: 14
                            };

                            // Ocupar el ancho total de la página
                            const columnCount = doc.content[1].table.body[0].length;
                            const widths = Array(columnCount).fill('*'); // Distribuye el ancho equitativamente
                            doc.content[1].table.widths = widths;

                            // Tamaño de fuente en la tabla
                            doc.styles.tableHeader.fontSize = 10;
                            doc.defaultStyle.fontSize = 9;
                        }
                    }]
                });


                // Cargar etabla de productos
                function cargarProductos(sucursal = '') {


                    fetch('../../configurar/productos_en_stock_critico.php', {
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
                                        tabla.row.add([
                                            contador++,
                                            row.nombre,
                                            row.precio_compra + '$',
                                            (row.origen == 'c' ? 'Colombiano' : 'Venezolano'),
                                            row.stock,
                                            row.proveedor
                                        ]);
                                    });

                                    tabla.draw(); // Redibuja la tabla

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