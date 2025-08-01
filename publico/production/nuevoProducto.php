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

    <style>
        .tabs section {
            display: none;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .tabs section.active {
            display: block;
            opacity: 1;
        }
    </style>

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
                        <p style="margin-top: -10px;">Agregar un producto nuevo al stock</p>


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
                                            <div class="tabs">
                                                <section>

                                                    <div class="mb-2">
                                                        <label class='col-form-label' for='first-name'>Nombre del producto</label>
                                                        <div class="row">

                                                            <div class='col-md-7  col-sm-7'>
                                                                <input type='text' id='nombre' required placeholder="Nombre del producto" name='nombre' class='form-control '>
                                                            </div>

                                                            <div class='col-md-5 col-sm-5'>
                                                                <button type="button" style="text-wrap: nowrap;" class="btn w-100 btn-info" id="open-modal">Calcular precio</button>
                                                            </div>

                                                        </div>
                                                    </div>

                                                    <input type='text' id='precio' name='precio' hidden class='form-control '>

                                                    <div class="row mb-2">
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='first-name'>Precio de Compra
                                                            </label>
                                                            <input type='text' required placeholder="Precio del bulto" id='precioMonedaOrigen' name='precioMonedaOrigen' class='form-control '>
                                                        </div>
                                                        <div class='col-lg-6 form-group'>

                                                            <label class='col-form-label' for='first-name'>Origen del producto</label>
                                                            <select class="form-control" required name="origenProducto" id="origenProducto">
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

                                                    <div class="row  mb-2">
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='first-name'>Proveedor
                                                            </label>
                                                            <input required type='text' id='proveedor' name='proveedor' placeholder="Nombre del proveedor" class='form-control '>
                                                        </div>
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='first-name'>Código de barras
                                                            </label>
                                                            <input required type='text' id='c_barras' name='c_barras' placeholder="Código de barras del producto" class='form-control '>
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
                                                <section>
                                                    <div id="listado_stock"></div>
                                                </section>
                                            </div>

                                            <div class='ln_solid'></div>
                                            <div class="w-100 d-flex justify-content-between">
                                                <button type="button" onclick="sectionsNav('anterior')" class="btn btn-info actualizar">Anterior</button>
                                                <button type="button" onclick="sectionsNav('siguiente')" class="btn btn-success actualizar">Siguiente</button>
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

            let currentSectionIndex = 0;
            let sections = [];

            document.addEventListener('DOMContentLoaded', () => {
                // Recolectar todas las secciones automáticamente
                sections = Array.from(document.querySelectorAll('.tabs section'));

                if (sections.length === 0) return;

                // Mostrar la primera sección
                sections[currentSectionIndex].classList.add('active');
            });

            // Gestor de ventas Wizard
            function sectionsNav(direction) {
                if (sections.length === 0) return;

                const validado = validar_form()
                console.log(validado)
                if (!validado) {
                    Alerta.mostrar('error', 'Faltan datos')
                    return
                };





                const btnSiguiente = document.querySelector("button.btn-success.actualizar");

                if (direction === 'siguiente' && currentSectionIndex === sections.length - 1) {
                    // Si ya estamos en la última sección, enviar el formulario manualmente
                    const form = btnSiguiente.closest('form');
                    if (form) {
                        form.requestSubmit(); // Usa validación nativa y dispara el evento submit
                    }
                    return;

                } else {
                    // seccion personalizada
                    obtenerSucursalesSeleccionadas()
                }

                // Ocultar sección actual
                sections[currentSectionIndex].classList.remove('active');

                // Cambiar índice
                if (direction === 'siguiente' && currentSectionIndex < sections.length - 1) {
                    currentSectionIndex++;
                } else if (direction === 'anterior' && currentSectionIndex > 0) {
                    currentSectionIndex--;
                }

                // Mostrar nueva sección
                setTimeout(() => {
                    sections[currentSectionIndex].classList.add('active');
                }, 50);

                // Cambiar texto del botón, pero mantener type="button"
                if (currentSectionIndex === sections.length - 1) {
                    btnSiguiente.textContent = 'Guardar';
                } else {
                    btnSiguiente.textContent = 'Siguiente';
                }
            }

            // Validar que todos los campos esten seleccionados
            function validar_form() {
                const section = document.querySelector('.tabs section'); // solo la primera sección dentro de .tabs
                const requiredInputs = section.querySelectorAll('input[required], select[required]');
                let valido = true;

                // Validar inputs/selects requeridos
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        valido = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                // Validar que al menos un checkbox de sucursales esté marcado
                const checks = section.querySelectorAll('input[name="sucursales[]"]');
                const algunoMarcado = Array.from(checks).some(chk => chk.checked);

                if (!algunoMarcado) {
                    // Puedes marcar visualmente los checkboxes si quieres
                    checks.forEach(chk => chk.classList.add('is-invalid'));
                    valido = false;
                    alert("Debes seleccionar al menos una sucursal.");
                } else {
                    checks.forEach(chk => chk.classList.remove('is-invalid'));
                }

                return valido;
            }


            // Generar los inputs con el stock
            function obtenerSucursalesSeleccionadas() {
                const checks = document.querySelectorAll('input[name="sucursales[]"]:checked');
                const seleccionadas = [];
                const listado_stock = document.getElementById('listado_stock')
                let html = '';

                checks.forEach(check => {
                    const id = check.getAttribute('data-id');
                    const label = document.querySelector(`label[for="${check.id}"]`);
                    const nombre = label ? label.textContent.trim() : 'Desconocido';

                    html += `  <div class=' mb-2 form-group'>
                                <label class='col-form-label' for='stock_${id}'>STOCK DE <b>${nombre}</b></label>
                                <input type='number' id='stock_${id}' name='stock_${id}' placeholder="Ingrese el stock disponible en ${nombre}" class='form-control '>
                            </div>`

                    seleccionadas.push({
                        id,
                        nombre
                    });
                });

                listado_stock.innerHTML = html;

                return seleccionadas;
            }

            // Obtiene los valores del stock
            function obtenerStockPorSucursal() {
                const checks = document.querySelectorAll('input[name="sucursales[]"]:checked');
                const resultado = [];

                checks.forEach(check => {
                    const id = check.getAttribute('data-id');
                    const label = document.querySelector(`label[for="${check.id}"]`);
                    const nombre = label ? label.textContent.trim() : 'Desconocido';

                    const inputStock = document.getElementById(`stock_${id}`).value;
                    const stock = inputStock ? parseInt(inputStock) : 0;

                    resultado.push([id, stock]);
                });

                return resultado;
            }

            // este codigo me regresa: Uncaught TypeError: form.querySelectorAll is not a function

            // Enviar el producto al back
            document.getElementById('form-data').addEventListener('submit', function(e) {
                e.preventDefault();

                const formElement = this; // <- El formulario real
                const formData = new FormData(formElement); // <- El objeto FormData


                // Usar tu función para obtener sucursales seleccionadas con su stock
                const stockPorSucursal = obtenerStockPorSucursal();

                // Guardar en FormData como JSON
                formData.append('sucursales_stock', JSON.stringify(stockPorSucursal));

                // DEBUG
                const debug = false;
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
                            sectionsNav('anterior')
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


                // Seccion del modal para calcular el precio
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