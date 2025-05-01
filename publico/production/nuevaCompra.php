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
        if ($Nueva_Compra == 0) {
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





    // Informacion de la tipo de cambio estandar
    $cambio = new TasasCambio($conexion);
    $bss_id = $_SESSION["bss_id"];

    $respuesta = $cambio->obtenerCambio($bss_id);
    $tasas = json_decode($respuesta, true);

    $pesoDolar = $tasas['data']['pesoDolar'];
    $bcv = $tasas['data']['bcv'];
    // Informacion de la tipo de cambio estandar



?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Compras</title>
        <?php require_once('includes/headers.php'); ?>
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
            </div>
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

                    <?php

                    @$accionE = $_GET['accion'];
                    @$codeEditar = $_GET['id'];

                    if ($accionE == "editar") {

                        $query7E = $conexion->query("SELECT * FROM productos WHERE codigo='$codeEditar' LIMIT 1");
                        if ($query7E->num_rows > 0) {
                            $tabla7E = '';
                            while ($row7E = $query7E->fetch_assoc()) {
                                $nombrePro = $row7E["nombre"];
                                $PrecioPro = $row7E["precio_compra"];
                                $CantidadPro = $row7E["cantidad_unidades"];
                                $porcentajePro = $row7E["porcentaje"];
                            }
                        }
                    } else {
                        $visible = "contain: strict";
                    }
                    ?>



                    <div class="col-lg-8">
                        <form name='calculadora' action='../../configurar/nuevaCompra.php' method='post' id='demo-form2' data-parsley-validate class='form-horizontal form-label-left' enctype="multipart/form-data">
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
                                        <br />
                                        <div class='item form-group'>
                                            <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Producto <span class='required'>*</span>
                                            </label>
                                            <div class='col-md-9 col-sm-9 d-flex' style="gap: 3px;">
                                                <input type='text' required="required" class='form-control col-lg-3' name='codigo' placeholder="Nombre" id='codigo'>
                                                <select id="producto" name="producto" class="form-control col-lg-9" required>
                                                    <option value="">-- Sin resultados --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <input type="text" name="nombrepor" value="" hidden>
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3" for="first-name">Unidades compradas<span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="text" id="comprado" name="comprado" required="required" class="form-control ">
                                            </div>
                                        </div>

                                        <div class="ln_solid"></div>

                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Precio de Compra <span class="required">*</span>
                                            </label>
                                            <div class="input-con-simbolo col-md-5">
                                                <input type="text" class="form-control form-control-simbolo" id="precio" name="precio" required="required" placeholder="Introduzca el precio">
                                                <span class="simbolo-final">$</span>
                                            </div>

                                            <div class="col-md-4">
                                                <button type="button" style="text-wrap: nowrap;" class="btn w-100 btn-info" id="open-modal-button">Calcular precio</button>
                                            </div>
                                        </div>

                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Cantidad <span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="number" id="cantidad" name="cantidad" value="" required="required" class="form-control">
                                            </div>
                                        </div>
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Porcentaje <span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="number" id="porcentaje" name="porcentaje" value="" required="required" class="form-control">
                                            </div>
                                        </div>


                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Proveedor <span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="text" id="proveedor" name="proveedor" value="" required="required" class="form-control">
                                            </div>
                                        </div>


                                        <div class='item form-group mt-3'>
                                            <div class='col-md-12 col-sm-12 '>
                                                <input type='submit' style="float: right;" class="btn btn-success actualizar" value="Agregar">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-12">
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
                                        <div class="card-box table-responsive" style="max-height: 65vh;">
                                            <table id="datatable" class="table table-striped table-bordered" style="width:100%">
                                                <thead>
                                                    <tr class="headings">
                                                        <th class="column-title">#</th>
                                                        <th class="column-title">Fecha </th>
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
        <script src="../build/js/custom.min.js"></script>

        <script src="../build/js/modal.js"></script>

        <script>
            // tasas de cambio
            const tasas = {
                'cop': "<?php echo $pesoDolar ?>",
                'bs': "<?php echo $bcv ?>"
            }

            // Consultar productos
            function obtener_registros(producto) {
                console.log(producto);

                $.ajax({
                    url: "../../configurar/consulta_codigo_producto.php",
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
                        // Sin resultados
                        $select.append('<option value="">-- Sin resultados --</option>');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("Error en la petición AJAX:", textStatus, errorThrown);
                    $("#producto").html('<option value="">-- Error al cargar --</option>');
                });
            }

            // Evento al escribir en #codigo
            $(document).on("keyup", "#codigo", function() {
                if ($(this).val().trim() !== "") {
                    obtener_registros($(this).val());
                } else {
                    $("#producto").html('<option value="">-- Ingrese código --</option>');
                }
            });


            $(document).on('change', '#producto', function() {
                var producto = $(this).val();
                if (producto != "") {
                    buscarProducto(producto);
                }
            });

            let precio_consultado
            let producto_consultado

            //Consultar informacion del producto
            function buscarProducto(codigo) {
                fetch('precio_producto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            rep_precio: codigo
                        })
                    })
                    .then(res => res.text())
                    .then(text => {
                        console.log("Respuesta como texto:", text); // Debug: mostrar respuesta cruda
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
                            document.querySelector('[name=nombrepor]').value = res.data.nombre;
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
                    console.log(`${key}: ${value}`);
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