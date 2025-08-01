<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {


    $topnav = topnav();



?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Agregar Producto</title>
        <?php require_once('includes/headers.php'); ?>
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
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

                        <h4>Nuevo producto</h4>
                        <p style="margin-top: -10px;">Agregar producto para venta al mayor</p>


                        <div class='clearfix'></div>



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
                            const cambioPesoRecepcion = 1000000; // Ajustar si este valor es dinámico
                            const pesoDolar = parseFloat(<?php echo $pesoDolar ?>);
                            const pesoBolivar = parseFloat(<?php echo $peso_bolivar ?>);



                            // Realiza la conversión principal según la moneda seleccionada
                            function realizarCalculos() {
                                const precioInput = parseFloat(document.getElementById("precioMonedaOrigen").value) || 0;

                                let resultado = 0;

                                resultado = precioInput;

                                // Mostrar resultado convertido en input 'precio'
                                document.getElementById("precio").value = resultado;

                                // Calcular precios adicionales
                                calcularValoresExtras(precioInput);
                            }

                            // Cálculos adicionales como precio unitario, venta y conversiones
                            function calcularValoresExtras(precioCompra) {
                                const cantidad = 1;
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
                        <form id='form-data' action='../../configurar/agregarProducto.php' method='post' class='form-horizontal form-label-left   fadeInUp animated'>
                            <div class='row'>
                                <div class='col-lg-6 '>
                                    <div class='x_panel'>
                                        <div class='x_title'>
                                            <h2>Datos del Producto</h2>
                                            <div class='clearfix'></div>

                                        </div>
                                        <div class='x_content'>
                                            <div class="tabs">
                                                <section>

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
                                                        <label for='denominacion_paquete'>Presentación del producto</label>
                                                        <select id="denominacion_paquete" name="denominacion_paquete" class="form-control" required>
                                                            <option>Seleccione</option>
                                                            <option value="Paquete de ">PAQUETE</option>
                                                            <option value="Caja de ">CAJA</option>
                                                            <option value="Paca de ">PACA</option>
                                                            <option value="Bulto de ">BULTO</option>
                                                            <option value="Saco de ">SACO</option>
                                                            <option value="Ristra de ">RISTRA</option>
                                                            <option value="Fardo de ">FARDO</option>
                                                            <option value="Rollo de ">ROLLO</option>
                                                            <option value="Atado de ">ATADO</option>
                                                            <option value="Barril de ">BARRIL</option>
                                                            <option value="Tambor de ">TAMBOR</option>
                                                            <option value="Contenedor de ">CONTENEDOR</option>
                                                            <option value="Tira de ">TIRA</option>
                                                            <option value="Carton de ">CARTON</option>
                                                        </select>
                                                    </div>


                                                    <input type='text' id='precio' name='precio' hidden class='form-control '>

                                                    <div class="row mb-2">
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='first-name'>Precio de Compra
                                                            </label>
                                                            <input disabled type='text' required placeholder="Precio del bulto" id='precioMonedaOrigen' name='precioMonedaOrigen' class='form-control '>
                                                        </div>
                                                        <div class='col-lg-6 form-group'>

                                                            <label class='col-form-label' for='first-name'>Origen del producto</label>
                                                            <select disabled class="form-control" required name="origenProducto" id="origenProducto">
                                                                <option value="">Seleccione</option>
                                                                <option value="v">Venezolano</option>
                                                                <option value="c">Colombiano</option>
                                                            </select>
                                                        </div>
                                                    </div>


                                                    <div class="row  mb-2">

                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='first-name'>Unidades por bulto
                                                            </label>
                                                            <input type='text' required id='cantidad' placeholder="Unidades que contiene el bulto" name='cantidad' class='form-control '>
                                                        </div>

                                                        <select style="display: none;" class="form-control" name="categoria">
                                                            <option> -- Categoria -- </option>
                                                        </select>
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='first-name'>Porcentaje
                                                            </label>
                                                            <input required type='text' id='porcentaje' name='porcentaje' placeholder="Porcentaje incrementado" class='form-control '>

                                                        </div>

                                                    </div>


                                                    <div id='tabla_resultado_codigo'>
                                                    </div>


                                                    <div class='ln_solid'></div>


                                                    <div class="mb-3">
                                                        <h6 class="mb-3">Sucursales donde se va a vender el producto</h6>
                                                        <?php
                                                        $stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ? ORDER BY principal DESC");
                                                        $stmt->bind_param('i', $bss_id);
                                                        $stmt->execute();
                                                        $result = $stmt->get_result();
                                                        if ($result->num_rows > 0) {
                                                            while ($row = $result->fetch_assoc()) {
                                                                $checked = $row['principal'] == 1 ? 'checked' : '';

                                                                echo <<<HTML
                                                        <div class="form-check">
                                                            <input class="form-check-input" {$checked} name="sucursales[]" data-nombre="{$row['nombre']}" data-id="{$row['id']}" type="checkbox" value="{$row['id']}" id="suc-{$row['id']}">
                                                            <label class="form-check-label" for="suc-{$row['id']}">
                                                                {$row['nombre']}
                                                            </label>
                                                        </div>
                                                        HTML;
                                                            }
                                                        }
                                                        $stmt->close();

                                                        ?>
                                                    </div>
                                                </section>
                                            </div>

                                            <div class='ln_solid'></div>
                                            <div class="w-100 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-success actualizar">Siguiente</button>
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
                                            <div class='form-group mb-3'>
                                                <label>Precio de Compra ($USD)</label>
                                                <input type='text' class='form-control' readonly='readonly' name='resultado' id='resultado'>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class='ml-2'>Precio de Venta ($USD) </label>
                                                <input type='text' class='form-control' readonly='readonly' name='resultado2' id='resultado2'>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class='ml-2'>Precio de Venta (COP)</label>
                                                <input type='text' class='form-control' readonly='readonly' name='resultado3' id='resultado3'>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class='ml-2'>Precio de Venta (BS)</label>
                                            <input class='date-picker form-control' type='text' readonly='readonly' name='resultado4' id='resultado4'>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
        </div>
        </div>
        <?php require('../assets/templates/modal.html'); ?>

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <script src='../build/js/custom.js'></script>
        <script src="../build/js/modal.js"></script>

        <script>
            // tasas de cambio
            const tasas = {
                'cop': "<?php echo $pesoDolar ?>",
                'bs': "<?php echo $bcv ?>"
            }


            // Consultar productos
            function obtener_registros(producto) {
                var $sucursal = $('#sucursal');
                var sucursal = $sucursal.length ? $sucursal.val() : '';

                $.ajax({
                    url: "../../configurar/consulta_codigo_producto_mayor.php",
                    type: "POST",
                    dataType: "json", // <-- importante: estamos esperando JSON ahora
                    data: {
                        producto: producto
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


            $(document).on('change', '#producto', function() {
                var producto = $(this).val();
                if (producto != "") {
                    buscarProducto(producto);
                }
            });



            //Consultar informacion del producto
            function buscarProducto(codigo) {

                fetch('../../configurar/precio_producto_mayor.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            producto: codigo
                        })
                    })
                    .then(res => res.text())
                    .then(text => {
                        let res;
                        console.log("🚀 ~ buscarProducto ~ res:", res)
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

                            document.getElementById('precio').value = res.data.precio_compra;
                            document.getElementById('precioMonedaOrigen').value = res.data.precio_compra;
                            document.getElementById('cantidad').value = res.data.cantidad_unidades;
                            document.getElementById('origenProducto').value = res.data.origen ?? 'v';

                            realizarCalculos()
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




            // Generar los inputs con el stock
            function obtenerSucursalesSeleccionadas() {
                const checks = document.querySelectorAll('input[name="sucursales[]"]:checked');
                const seleccionadas = [];
                let html = '';

                checks.forEach(check => {
                    const id = check.getAttribute('data-id');
                    seleccionadas.push(id);
                });


                return seleccionadas;
            }

            // este codigo me regresa: Uncaught TypeError: form.querySelectorAll is not a function

            // Enviar el producto al back
            document.getElementById('form-data').addEventListener('submit', function(e) {
                e.preventDefault();
                const formElement = this; // <- El formulario real
                const formData = new FormData(formElement); // <- El objeto FormData


                // Usar tu función para obtener sucursales seleccionadas con su stock
                const stockPorSucursal = obtenerSucursalesSeleccionadas();

                // Guardar en FormData como JSON
                formData.append('sucursales_stock', JSON.stringify(stockPorSucursal));


                // Envío por fetch
                fetch('../../configurar/agregarProductoMayor.php', {
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

                        this.reset();
                        Alerta.toast(json.tipo, json.mensaje);
                    })

            });

            // asi se generan los inputs:check
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>