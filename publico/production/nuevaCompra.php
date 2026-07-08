<?php
require_once('includes/requires.php');
if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {

    $topnav = topnav();

    $tipo_u =  $_SESSION['nivel'];
    $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";

    if ($tipo_u == 2) {
        // Solo para los usuarios tipo 2
        $id_sucursal = $_SESSION['sucursal'];
        $query .= " AND id='$id_sucursal'";
    }

    $query .= "  ORDER BY principal DESC";

    $stmt = mysqli_prepare($conexion, $query);
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
    <html lang='es'>

    <head>

        <title>Compras</title>
        <?php require_once('includes/headers.php'); ?>

        <style>
            .table td,
            .table th {
                padding: .55rem;

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
                <div class='right_col' role='main'>
                    <div class='row'>
                        <div class="col-lg-6">
                            <form id='demo-form2'>
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Datos del Producto <small>* obligatorio</small></h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                            </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content">
                                        <div class='x_content'>
                                            <?php if ($tipo_u == 1): ?>
                                                <div class='form-group mb-3'>
                                                    <label class='form-label' for='first-name'>Sucursal </label>
                                                    <select class="form-control" id="sucursal" name="sucursal">
                                                        <?php if (count($sucursales) > 1): ?>
                                                            <option value="">-- Seleccione --</option>
                                                        <?php endif; ?>

                                                        <?php foreach ($sucursales as $row): ?>
                                                            <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($row['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            <?php endif; ?>


                                            <div class='mb-3 form-group'>
                                                <div class='row'>
                                                    <div class="col-lg-4">
                                                        <label for='codigo' for='codigo'>Filtro</label>
                                                        <input type='text' required="required" class='form-control' name='codigo' placeholder="Nombre" id='codigo'>
                                                    </div>
                                                    <div class="col-lg-8">
                                                        <label for='producto'>Seleccione el producto</label>
                                                        <select id="producto" name="producto" class="form-control" required>
                                                            <option>-- Indique un filtro --</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3 form-group">
                                                <label class="form-label" for="comprado">Unidades compradas</label>
                                                <input type="text" id="comprado" name="comprado" required="required" class="form-control" placeholder="Cantidad de unidades compradas">
                                            </div>

                                            <div class="ln_solid"></div>

                                            <div class="form-group">
                                                <label for="precio">Precio de Compra</label>
                                                <div class="row">

                                                    <div class="input-con-simbolo col-md-7">
                                                        <input type="text" class="form-control form-control-simbolo" id="precio" name="precio" required="required" placeholder="Introduzca el precio">
                                                        <span class="simbolo-final">$</span>
                                                    </div>

                                                    <div class="col-md-5">
                                                        <button type="button" style="text-wrap: nowrap;" class="btn w-100 btn-info" id="open-modal-button">Calcular precio</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-lg-6">
                                                    <label for="cantidad">Unidades por bulto</label>
                                                    <input type="number" id="cantidad" placeholder="Unidades por bulto/paquete/caja/etc" name="cantidad" required="required" class="form-control">
                                                </div>
                                                <div class="col-lg-6">
                                                    <label for="porcentaje">Porcentaje</label>
                                                    <input type="number" id="porcentaje" name="porcentaje" placeholder="Porcentaje de ganancia" required="required" class="form-control">
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="proveedor">Proveedor</label>
                                                <input type="text" id="proveedor" name="proveedor" placeholder="Nombre del proveedor" required="required" class="form-control">
                                            </div>

                                            <div class='form-group mt-3 text-end'>
                                                <input type='submit' style="float: right;" class="btn btn-success actualizar" value="Agregar">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Compras</h2>
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                        </li>
                                    </ul>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <p class="font-13 m-b-30">Lista general de los productos agregados al inventario.</p>
                                            <div class="card-box table-responsive" style="max-height: 70vh;">
                                                <table id="datatable" class="table table-striped table-bordered" style="width:100%">
                                                    <thead>
                                                        <tr class="headings">
                                                            <th class="column-title">Producto</th>
                                                            <th class="column-title">Cantidad</th>
                                                            <th class="column-title"></th>
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
        <?php require('../assets/templates/modal.html'); ?>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="../build/js/modal.js"></script>
        <script src="js/nombre_pagina.js"></script>

        <script>
            // tasas de cambio
            const tasas = {
                'cop': "<?php echo $pesoDolar ?>",
                'bs': "<?php echo $bcv ?>"
            }

            let precio_consultado
            let producto_consultado



            // Consultar productos
            function obtener_registros(producto) {
                var $sucursal = $('#sucursal');
                var sucursal = $sucursal.length ? $sucursal.val() : '';

                if ($('#sucursal').length && $('#sucursal').val() == '') {
                    $('#codigo').val('')
                    Alerta.toast('error', 'Debe seleccionar una sucursal antes de buscar el producto')
                    return
                }


                $.ajax({
                    url: "../../configurar/consulta_codigo_producto.php",
                    type: "POST",
                    dataType: "json", // <-- importante: estamos esperando JSON ahora
                    data: {
                        producto: producto,
                        sucursal: sucursal
                    },
                }).done(function(resultado2) {
                    const $select = $("#producto");
                    $select.empty(); // Limpia opciones anteriores

                    if (resultado2.length > 0) {
                        // Agrega las opciones recibidas
                        $select.append(`<option value="">-- Seleccione --</option>`);
                        resultado2.forEach(function(item) {
                            $select.append(`<option value="${item.id}">${item.nombre}</option>`);
                        });
                    } else {
                        // Indique un filtro
                        $select.append('<option value="">-- Indique un filtro --</option>');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("Error en la petición AJAX:", textStatus, errorThrown);
                    $("#producto").html('<option >-- Error al cargar --</option>');
                });
            }

            // Evento al escribir en #codigo
            $(document).on("keyup", "#codigo", function() {
                if ($(this).val().trim() !== "") {
                    obtener_registros($(this).val());
                } else {
                    $("#producto").html('<option value="">-- Indique un filtro --</option>');
                }
            });


            // Resetear formulario
            function form_reset() {
                const form = document.getElementById('demo-form2');
                const select = document.getElementById('sucursal'); // Asegúrate de que el select tenga este ID

                const selectedValue = select.value; // Guarda la opción seleccionada
                form.reset(); // Resetea el formulario
                select.value = selectedValue; // Restablece la opción seleccionada
            }

            $(document).on('change', '#sucursal', function() {
                form_reset()
            }); // resetear form al cambiar el select






            $(document).on('change', '#producto', function() {
                var producto = $(this).val();
                if (producto != "") {
                    buscarProducto(producto);
                }
            });



            //Consultar informacion del producto
            function buscarProducto(codigo) {

                var $sucursal = $('#sucursal');
                var sucursal = $sucursal.length ? $sucursal.val() : '';

                if ($('#sucursal').length && $('#sucursal').val() == '') {
                    $('#codigo').val('')
                    Alerta.toast('error', 'Debe seleccionar una sucursal antes de seleccionar el producto')
                    return
                }

                fetch('precio_producto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            producto: codigo,
                            sucursal: sucursal
                        })
                    })
                    .then(res => res.text())
                    .then(text => {
                        let res;
                        try {
                            res = JSON.parse(text);
                        } catch (e) {
                            console.error("Error al parsear JSON:", e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Respuesta no válida',
                                text: 'El servidor no devolvió un JSON válido.'
                            });
                            return;
                        }

                        if (res.success) {
                            precio_consultado = res.data.precio_compra;
                            producto_consultado = res.data.nombre

                            document.getElementById('comprado').value = '';
                            document.getElementById('precio').value = res.data.precio_compra;
                            document.getElementById('cantidad').value = res.data.cantidad_unidades;
                            document.getElementById('porcentaje').value = res.data.porcentaje;
                            document.getElementById('proveedor').value = res.data.proveedor;
                            //        document.querySelector('[name=nombrepor]').value = res.data.nombre;
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Producto no encontrado',
                                text: 'Verifica el código ingresado'
                            });
                        }

                        console.log("Tasas de cambio:", res.pesoDolar, res.bsDolar);
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo obtener los datos del producto'
                        });
                    });
            }


            // ENVIAR LA NUEVA COMPRA AL BACK
            document.getElementById('demo-form2').addEventListener('submit', function(e) {
                e.preventDefault(); // Evitar envío tradicional

                const form = e.target;
                const formData = new FormData(form);

                console.log("Enviando datos del formulario:");
                for (let [key, value] of formData.entries()) {
                    if (value == '') {
                        console.log('Campo vacío: ' + key)
                        Alerta.toast('error', 'Error: Campo (a) vacío (s)')
                        return
                    }
                }

                fetch('../../configurar/nuevaCompra.php', {
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
                            Alerta.mostrar('success', json.message);
                            form.reset(); // Opcional: limpia el formulario
                            cargarTabla()
                        } else {
                            Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
                        }
                    })
                    .catch(err => {
                        console.error("Error en la solicitud:", err);
                        Alerta.mostrar('error', 'No se pudo contactar con el servidor');
                    });
            });


            // CARGAR LA TABLA CON LAS COMPRAS
            function cargarTabla() {
                const tabla = document.getElementById('datatable')

                fetch('../../configurar/listado_compras.php')
                    .then(res => res.text())
                    .then(text => {
                        tabla.innerHTML = text;
                    })
                    .catch(err => {
                        console.error("Error en la solicitud:", err);
                        Alerta.mostrar('error', 'No se pudo contactar con el servidor');
                    });
            }
            cargarTabla()



            // MODIFICAR EL MODAL
            $(document).ready(function() {
                const modal_body = document.getElementById('modal-body')
                modal_body.style = 'overflow: auto;'
                modal_body.innerHTML = `<h2 class="mb-0 mt-0" id="titulo_modal">Calcular precio </h2>
                <hr>
                    <div class=" form-group">
                        <label  class="form-label"   for="moneda">Moneda </label>
                            <select id="moneda" class="form-control">
                                <option >Seleccione</option>
                                <option value="bs">Bolívares</option>
                                <option value="cop">Pesos</option>
                            </select>
                    </div>

                    <div class=" form-group">
                        <label class="form-label" for="tasa_proveedor">Tasa del proveedor
                        </label>
                            <input type="text" id="tasa_proveedor" class="form-control">
                    </div>

                    <div class=" form-group">
                        <label class="form-label" for="precio_proveedor">Precio
                        </label>
                            <input type="text" id="precio_proveedor" class="form-control">
                    </div>

                    <div class=" form-group">
                        <label  class="form-label"  for="impuesto">Impuesto 
                        </label>
                            <select id="impuesto" class="form-control">
                                <option >Seleccione</option>
                                <option value="exento">Exento</option>
                                <option value="iva">Iva (16%)</option>
                            </select>
                    </div>`

                document.getElementById('modal').style = 'height: 100% !important;'
                const modal_footer = document.getElementById('modal-footer')
                modal_footer.classList.remove('hide')
                modal_footer.innerHTML = `
                           <div class="d-flex" style='gap: 15px;'>
                         <div class="me-2 vista_precio" id="texto_vista_previo"> </div>
                        <div class="me-2 vista_precio" id="vista_previa_precio"></div>
                        </div>

                        <div class="d-flex" style='gap: 8px;'>
                            <button class="btn btn-sm btn-danger" onclick=' modalContainer.classList.remove("active");'>Cerrar</button>
                            <button class="btn btn-sm btn-success" id="btn-actualizar">Actualizar</button>
                        </div>
                        `
                const modal = document.getElementById('w-modal')
                modal.classList.add('w-50', 'h-60')
                modal.style = "padding: 0px;"



                // mostrar modal
                document.getElementById('open-modal-button').addEventListener('click', function() {
                    const precio = document.getElementById('precio').value
                    if (precio.trim() != '') {

                        let texto_superior = (producto_consultado != undefined ? producto_consultado + ' <span title="Precio anterior">PA: </span>$' + precio_consultado : '')
                        document.getElementById('titulo_modal').innerHTML = `Calcular precio <small>${texto_superior}</small>`

                        modalContainer.classList.add("active");
                    } else {
                        Alerta.toast('error', 'Seleccione un producto')
                    }
                })



                var n_precio = 0;

                // Cambiar la tasa
                document.getElementById('moneda').addEventListener('change', function() {
                    document.getElementById('tasa_proveedor').value = tasas[this.value];
                })
                // Calcular precio
                document.getElementById('precio_proveedor').addEventListener('change', function() {
                    if (this.value != '') {
                        calcularPrecio()
                    }
                })
                document.getElementById('impuesto').addEventListener('change', function() {
                    calcularPrecio()
                })

                function calcularPrecio() {
                    const texto_vista_previo = document.getElementById('vista_previa_precio');
                    const divPrecio = document.getElementById('vista_previa_precio');
                    const tasaValor = parseFloat(document.getElementById('tasa_proveedor').value);
                    const precioValor = parseFloat(document.getElementById('precio_proveedor').value);
                    const impuesto = document.getElementById('impuesto').value;
                    let precioViejo = parseFloat(precio_consultado);

                    // Validación de entradas numéricas
                    if (isNaN(tasaValor) || isNaN(precioValor) || tasaValor <= 0 || precioValor < 0) {
                        divPrecio.innerHTML = '';
                        divPrecio.className = ''; // Limpiar clases anteriores
                        return;
                    }


                    // Cálculo del nuevo precio
                    let precioFinal = precioValor / tasaValor;
                    if (impuesto === 'iva') {
                        precioFinal += precioFinal * 0.16;
                    }

                    const precioFinalRecortado = recortarADosDecimales(precioFinal);
                    n_precio = precioFinalRecortado
                    const diferencia = precioFinalRecortado - precioViejo;

                    // Establecer clases visuales según la variación
                    divPrecio.classList.remove('text-success', 'text-danger', 'text-muted');

                    if (diferencia > 0) {
                        divPrecio.classList.add('text-danger');
                        divPrecio.innerHTML = `Incrementó <strong>$${formatNumber(recortarADosDecimales(diferencia))}</strong> &nbsp; → Nuevo precio: $${formatNumber(precioFinalRecortado)}`;
                    } else if (diferencia < 0) {
                        divPrecio.classList.add('text-success');
                        divPrecio.innerHTML = `Disminuyó <strong>$${formatNumber(recortarADosDecimales(Math.abs(diferencia)))}</strong> &nbsp; → Nuevo precio: $${formatNumber(precioFinalRecortado)}`;
                    } else {
                        divPrecio.classList.add('text-muted');
                        divPrecio.innerHTML = `Sin variación &nbsp; → $${formatNumber(precioFinalRecortado)}`;
                    }
                }

                document.getElementById('btn-actualizar').addEventListener('click', function() {
                    if (n_precio != 0) {
                        document.getElementById('precio').value = n_precio
                        document.getElementById('precio_proveedor').value = ''
                        const divPrecio = document.getElementById('vista_previa_precio');
                        divPrecio.innerHTML = '';
                        divPrecio.className = ''; // Limpiar clases anteriores
                        modalContainer.classList.remove("active");
                    }
                })

            })
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>