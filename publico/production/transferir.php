<?php
require_once('includes/requires.php');
if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {

    $topnav = topnav();

    $tipo_u =  $_SESSION['nivel'];
    $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";

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

        <title>Transferencia de stock</title>
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

                    <div class="row">
                        <div class="col-lg-12">
                            <form id='data-form'>
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

                                            <div class="row">
                                                <div class='col-lg-6 form-group mb-3'>
                                                    <label class='form-label' for='sucursal'>Sucursal origen</label>
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

                                                <div class='col-lg-6 form-group mb-3'>
                                                    <label class='form-label' for='sucursal_2'>Sucursal destino</label>
                                                    <select class="form-control" id="sucursal_2" name="sucursal_2">
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


                                            </div>

                                            <div class="mb-3">

                                                <div class=' form-group'>
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

                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-lg-3">
                                                    <label for='disponible' for='disponible'>Disponibilidad</label>
                                                    <input type='text' readonly required="required" class='form-control' name='disponible' placeholder="Stock disponible" id='disponible'>
                                                </div>
                                                <div class="col-lg-9">

                                                    <div class=" form-group">
                                                        <label class="form-label" for="comprado">Unidades a transferir</label>
                                                        <input type="text" id="unidades_transferir" name="unidades_transferir" required="required" class="form-control" placeholder="Cantidad de unidades a transferir">
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="ln_solid"></div>
                                            <div class='form-group mt-3 text-end'>
                                                <input type='submit' style="float: right;" class="btn btn-success actualizar" value="Guardar">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
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
            function verificarSucursales() {
                const sucursalOrigen = document.getElementById('sucursal').value;
                const sucursalDestino = document.getElementById('sucursal_2').value;

                // Si ambos tienen valor y son iguales, no es válido
                if (sucursalOrigen && sucursalDestino && sucursalOrigen === sucursalDestino) {
                    // Puedes mostrar un mensaje si lo deseas
                    Alerta.toast('error', 'La sucursal de origen y destino no pueden ser la misma')
                    document.getElementById('sucursal_2').value = ''
                    return false;
                }

                return true;
            }

            // Detecta cambios en los selects y verifica automáticamente
            document.getElementById('sucursal').addEventListener('change', verificarSucursales);
            document.getElementById('sucursal_2').addEventListener('change', verificarSucursales);


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
                const form = document.getElementById('data-form');
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
                        console.log('respuesta crudda ' + text)
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

                            document.getElementById('disponible').value = res.data.stock;
                            //        document.querySelector('[name=nombrepor]').value = res.data.nombre;
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Producto no encontrado',
                                text: 'Verifica el código ingresado'
                            });
                        }

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
            document.getElementById('data-form').addEventListener('submit', function(e) {
                e.preventDefault(); // Evitar envío tradicional

                const form = e.target;
                const formData = new FormData(form);


                for (let [key, value] of formData.entries()) {
                    if (value == '') {
                        console.log('Campo vacío: ' + key)
                        Alerta.toast('error', 'Error: Campo (a) vacío (s)')
                        return
                    }
                }

                if (!verificarSucursales()) {
                    return; // Previene el guardado o envío
                }

                const disponible = parseInt(document.getElementById('disponible').value)
                const unidades_transferir = parseInt(document.getElementById('unidades_transferir').value)

                if (unidades_transferir > disponible) {
                    Alerta.toast('No hay suficientes productos en stock')
                    return
                }


                fetch('../../configurar/modificar_stock_transferir.php', {
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
                        } else {
                            Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
                        }
                    })
                    .catch(err => {
                        console.error("Error en la solicitud:", err);
                        Alerta.mostrar('error', 'No se pudo contactar con el servidor');
                    });
            });
        </script>

    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>