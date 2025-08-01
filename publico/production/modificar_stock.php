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
                    <div class=''>


                        <div class="col-lg-12">
                            <h4>Compras</h4>
                            <p style="margin-top: -10px;">Nuevas compras realizadas</p>
                        </div>
                        <div class='clearfix'></div>
                        <div class="row">
                            <div class="col-lg-6 m-auto">
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
                                                    <label class="form-label" for="comprado">Unidades en stock</label>
                                                    <input type="text" id="cantidad_stock" name="cantidad_stock" required="required" class="form-control" placeholder="Cantidad de unidades compradas">
                                                </div>
                                                <div class="ln_solid"></div>
                                                <div class='form-group mt-3 text-end'>
                                                    <input type='submit' style="float: right;" class="btn btn-success actualizar" value="Actualizar">
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
        </div>
        <?php require('../assets/templates/modal.html'); ?>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="../build/js/modal.js"></script>
        <script>
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

                            document.getElementById('cantidad_stock').value = res.data.stock;
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

                console.log("Enviando datos del formulario:");
                for (let [key, value] of formData.entries()) {
                    if (value == '') {
                        console.log('Campo vacío: ' + key)
                        Alerta.toast('error', 'Error: Campo (a) vacío (s)')
                        return
                    }
                }

                fetch('../../configurar/modificar_stock.php', {
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