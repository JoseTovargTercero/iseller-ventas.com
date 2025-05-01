<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require("../../configurar/_tasas_cambio.php");


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Nuevo_Producto == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }

    $topnav = topnav();

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }

    // Informacion de la tipo de cambio estandar
    $cambio = new TasasCambio($conexion);
    $bss_id = $_SESSION["bss_id"];

    $respuesta = $cambio->obtenerCambio($bss_id);
    $tasas = json_decode($respuesta, true);

    $pesoDolar = $tasas['data']['pesoDolar'];
    $peso_bolivar = $tasas['data']['peso_bolivar'];
    $bsDolar = $tasas['data']['DolarBolivar'];
    $bcv = $tasas['data']['bcv'];
    // Informacion de la tipo de cambio estandar






?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Agregar Producto</title>
        <?php require_once('includes/headers.php'); ?>
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
        <script src='peticion.js'></script>
        <script src='peticion_codigo.js'></script>
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
                <div class='right_col' role='main'>
                    <div class=''>

                        <h4>Nuevo producto</h4>
                        <p style="margin-top: -10px;">Agregar un producto nuevo al stock</p>


                        <div class='clearfix'></div>



                        <script>
                            // Ejecutar actualización cada segundo
                            setInterval(() => {
                                actualizarTasaCambio();
                                realizarCalculos();
                            }, 1000);

                            // Variables globales
                            let cambioDolar = parseFloat(<?php echo $bsDolar ?>);
                            const cambioPesoRecepcion = 1000000; // Ajustar si este valor es dinámico
                            const pesoDolar = parseFloat(<?php echo $pesoDolar ?>);
                            const pesoBolivar = parseFloat(<?php echo $peso_bolivar ?>);

                            // Actualiza el tipo de cambio del dólar (por si cambia externamente)
                            function actualizarTasaCambio() {
                                cambioDolar = parseFloat(<?php echo $bsDolar ?>);
                            }

                            // Realiza la conversión principal según la moneda seleccionada
                            function realizarCalculos() {
                                const precioInput = parseFloat(document.getElementById("precioMonedaOrigen").value) || 0;
                                const moneda = document.getElementById("moneda").value;

                                let resultado = 0;

                                switch (moneda) {
                                    case "pesos":
                                        resultado = ((precioInput / cambioPesoRecepcion) / cambioDolar);
                                        break;
                                    case "bolivares":
                                        resultado = precioInput / cambioDolar;
                                        break;
                                    default: // dólares
                                        resultado = precioInput;
                                }

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
                        <form id='form-data' action='../../configurar/agregarProducto.php' method='post' class='form-horizontal form-label-left   fadeInUp animated'>
                            <div class='row'>
                                <div class='col-lg-6 '>
                                    <div class='x_panel'>
                                        <div class='x_title'>
                                            <h2>Datos del Producto</h2>
                                            <div class='clearfix'></div>

                                        </div>
                                        <div class='x_content'>

                                            <div class="mb-2">
                                                <label class='col-form-label' for='first-name'>Nombre del producto</label>
                                                <div class="row">

                                                    <div class='col-md-7  col-sm-7'>
                                                        <input type='text' id='nombre' placeholder="Nombre del producto" name='nombre' required='required' class='form-control '>
                                                    </div>

                                                    <div class='col-md-5 col-sm-5'>
                                                        <button type="button" style="text-wrap: nowrap;" class="btn w-100 btn-info" id="open-modal">Calcular precio</button>
                                                    </div>

                                                </div>
                                            </div>

                                            <input type='text' id='precio' name='precio' hidden required='required' class='form-control '>

                                            <div class="row mb-2">
                                                <div class='col-lg-6 form-group'>
                                                    <label class='col-form-label' for='first-name'>Precio de Compra
                                                    </label>
                                                    <input type='text' placeholder="Precio del bulto" id='precioMonedaOrigen' name='precioMonedaOrigen' required='required' class='form-control '>
                                                </div>
                                                <div class='col-lg-6 form-group'>

                                                    <label class='col-form-label' for='first-name'>Origen del producto</label>
                                                    <select class="form-control" required='required' name="origenProducto" id="origenProducto">
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
                                                    <input type='text' id='cantidad' placeholder="Unidades que contiene el bulto" name='cantidad' required='required' class='form-control '>
                                                </div>

                                                <div class='col-lg-6 form-group'>
                                                    <label class='col-form-label' for='first-name'>Cantidad en Stock
                                                    </label>
                                                    <input type='text' id='stock' name='stock' placeholder="Cantidad total en stock" required='required' class='form-control '>

                                                    <select style="display: none;" class="form-control" name="categoria">
                                                        <option> -- Categoria -- </option>
                                                    </select>
                                                </div>

                                            </div>

                                            <div class=' mb-2 form-group'>
                                                <label class='col-form-label' for='first-name'>Porcentaje
                                                </label>
                                                <input type='text' id='porcentaje' name='porcentaje' placeholder="Porcentaje incrementado" required='required' class='form-control '>
                                            </div>

                                            <section id='tabla_resultado_codigo'>
                                            </section>

                                            <div class="row  mb-2">
                                                <div class='col-lg-6 form-group'>
                                                    <label class='col-form-label' for='first-name'>Proveedor
                                                    </label>
                                                    <input type='text' id='proveedor' name='proveedor' placeholder="Nombre del proveedor" required='required' class='form-control '>
                                                </div>
                                                <div class='col-lg-6 form-group'>
                                                    <label class='col-form-label' for='first-name'>Código de barras
                                                    </label>
                                                    <input type='text' id='c_barras' name='c_barras' placeholder="Código de barras del producto" required='required' class='form-control '>
                                                </div>
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
                                                            <input class="form-check-input" {$checked} name="sucursales[]" data-id="{$row['id']}" type="checkbox" value="{$row['id']}" id="suc-{$row['id']}">
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


                                            <div class='ln_solid'></div>
                                            <div class="w-100 text-end">
                                                <button type='submit' class="btn btn-success actualizar">Agregar</button>
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
                                                <input type='text' class='form-control' disabled='disabled' name='resultado' id='resultado'>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class='ml-2'>Precio de Venta ($USD) </label>
                                                <input type='text' class='form-control' disabled='disabled' name='resultado2' id='resultado2'>
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
        <script src='../build/js/custom.min.js'></script>
        <script src="../build/js/modal.js"></script>

        <script>
            // tasas de cambio
            const tasas = {
                'cop': "<?php echo $pesoDolar ?>",
                'bs': "<?php echo $bcv ?>"
            }


            // este codigo me regresa: Uncaught TypeError: form.querySelectorAll is not a function

            // Enviar el producto al back
            document.getElementById('form-data').addEventListener('submit', function(e) {
                e.preventDefault();


                const formElement = this; // <- El formulario real
                const formData = new FormData(formElement); // <- El objeto FormData

                // Aquí usamos formElement para buscar los checkboxes marcados
                const checks = formElement.querySelectorAll('input[name="sucursales[]"]:checked');
                const sucursales = [];

                checks.forEach(check => {
                    const id = check.getAttribute('data-id');
                    sucursales.push(id);
                });

                // Agregar sucursales al objeto FormData
                formData.append('sucursales_marcadas', JSON.stringify(sucursales));

                // DEBUG
                const debug = true;
                if (debug) {
                    console.log("Formulario enviado con los siguientes datos:");
                    for (let [key, value] of formData.entries()) {
                        console.log(`${key}: ${value}`);
                    }

                    if (!confirm("¿Deseas enviar el formulario con estos datos?")) {
                        Alerta.toast('info', 'Envío cancelado por el usuario (modo debug activo).');
                        return;
                    }
                }


                // Envío por fetch
                fetch('../../configurar/agregarProducto.php', {
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

                        if (json.tipo === 'success') {
                            // Opcional: limpiar el formulario
                            this.reset();
                        }
                        Alerta.toast(json.tipo, json.mensaje);

                    })

            });

            // asi se generan los inputs:check



            // MODIFICAR EL MODAL
            $(document).ready(function() {
                const modal_body = document.getElementById('modal-body')
                modal_body.style = 'overflow: auto;'
                modal_body.innerHTML = `<h2 class="mb-0 mt-0" id="titulo_modal">Calcular precio </h2>
                <hr>
                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 " for="moneda">Moneda <span class="required">*</span>
                        </label>
                        <div class="col-md-9 col-sm-9 ">
                            <select id="moneda" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="bs">Bolívares</option>
                                <option value="cop">Pesos</option>
                            </select>
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3" for="tasa_proveedor">Tasa del proveedor<span class="required">*</span>
                        </label>
                        <div class="col-md-9 col-sm-9 ">
                            <input type="text" id="tasa_proveedor" class="form-control">
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3" for="precio_proveedor">Precio<span class="required">*</span>
                        </label>
                        <div class="col-md-9 col-sm-9 ">
                            <input type="text" id="precio_proveedor" class="form-control">
                        </div>
                    </div>

                    <div class="item form-group">
                        <label class="col-form-label col-md-3 col-sm-3 " for="impuesto">Impuesto <span class="required">*</span>
                        </label>
                        <div class="col-md-9 col-sm-9 ">
                            <select id="impuesto" class="form-control">
                                <option value="">Seleccione</option>
                                <option value="exento">Exento</option>
                                <option value="iva">Iva (16%)</option>
                            </select>
                        </div>
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

                    // Establecer clases visuales según la variación
                    divPrecio.classList.remove('text-success', 'text-danger', 'text-muted');

                    divPrecio.classList.add('text-success');
                    divPrecio.innerHTML = `Precio de compra → $${formatNumber(precioFinalRecortado)}`;
                }

                document.getElementById('btn-actualizar').addEventListener('click', function() {
                    if (n_precio != 0) {
                        document.getElementById('precioMonedaOrigen').value = n_precio
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