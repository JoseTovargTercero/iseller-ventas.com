<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != '1') {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}




$topnav = topnav();
$nivelUsuario = $_SESSION['nivel'];

if ($_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}



?>
<!DOCTYPE html>
<html lang='es'>

<head>

    <title>Agregar Producto</title>
    <?php require_once('includes/headers.php'); ?>


    <?php
    @$registrado = $_GET['agregado'];
    switch ($registrado) {
        case ('correcto'):
            echo '<script>
            function mensaje(){	
			alertify.success("El producto se agrego correctamente.");}
            </script>
            <body onload="mensaje()">
            </body>';
            break;
    }

    ?>


</head>



<body class='nav-md'>
    <div class='container body'>
        <div class='main_container'>
            <div class='col-md-3 left_col'>
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
            <div class='right_col' role='main'>
                <div class=''>

                    <h4>Tasas de cambio</h4>
                    <p style="margin-top: -10px;">Configuración de las tasas de cambio</p>


                    <div class='clearfix'></div>


                    <div class='row'>
                        <div class='col-lg-6 '>
                            <div class='x_panel'>
                                <div class='x_title'>
                                    <h2> Tasas de cambio</h2>

                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content'>

                                    <?php

                                    if ($redondeo == 0) {
                                        $options_2 = '
                                            <option value="0">Ninguno</option>
                                            <option value="1">Entero mas cercano (+ .5)</option>
                                            <option value="2">Entero mas cercano (- .5)</option>';
                                    } elseif ($redondeo == 1) {
                                        $options_2 = '
                                            <option value="1">Entero mas cercano (+ .5)</option>
                                            <option value="2">Entero mas cercano (- .5)</option>
                                            <option value="0">Ninguno</option>';
                                    } else {
                                        $options_2 = '
                                            <option value="2">Entero mas cercano (- .5)</option>
                                            <option value="1">Entero mas cercano (+ .5)</option>
                                            <option value="0">Ninguno</option>';
                                    }

                                    if ($tipo_tasa_bs == 1) {
                                        $display_tasa = '';
                                        $display_marg = 'display: none';

                                        $display = 'display: none';
                                        $options = '
                                            <option value="1">Tasa manual</option>
                                            <option value="2">Tasa BCV</option>
                                            <option value="3">Tasa BCV + Margen neto</option>';
                                    } elseif ($tipo_tasa_bs == 2) {
                                        $display_tasa = 'display: none';
                                        $display_marg = 'display: none';

                                        $display = 'display: none';
                                        $options = '
                                            <option value="2">Tasa BCV</option>
                                            <option value="1">Tasa manual</option>
                                            <option value="3">Tasa BCV + Margen neto</option>';
                                    } else {
                                        $display_tasa = 'display: none';
                                        $display_marg = '';




                                        $display = '';
                                        $options = '
                                            <option value="3">Tasa BCV + Margen neto</option>
                                            <option value=1"">Tasa manual</option>
                                            <option value="2">Tasa BCV</option>';
                                    }

                                    ?>
                                    <form action="../../configurar/tasas.php" method="post">
                                        <h5 class="text-center">Dolar <i style="vertical-align: text-bottom;" class="bx bx-right-arrow-alt"></i> Bolívares</h5>

                                        <p class="text-center">
                                            <em>Se utiliza para calcular el valor de la tasa de cambio de Dólares a Bolívares</em>
                                        </p>
                                        <div class="mb-3">
                                            <label class="form-label">Tipo de tasa (BS)</label>
                                            <select name="tipoTasa" class="form-control" onchange="tipoCambio(this.value)">
                                                <?php echo $options ?>
                                            </select>
                                        </div>

                                        <div class="mb-3" id="section_tasa_bs" style="<?php echo $display_tasa ?>">
                                            <label class="form-label">Valor del cambio</label>
                                            <input value="<?php echo $dolarBolivar ?>" name="bolivar" class="form-control">
                                        </div>

                                        <div class="mb-3" id="section_margen" style="<?php echo $display_marg ?>">
                                            <label class="form-label">Margen neto</label>
                                            <input value="<?php echo $margen_neto ?>" name="margen" class="form-control">
                                        </div>

                                        <div class="mb-3" id="section_redondeo" style="<?php echo $display ?>">
                                            <label class="form-label">Tipo de redondeo</label>
                                            <select name="redondeo" class="form-control">
                                                <?php echo $options_2 ?>
                                            </select>
                                        </div>
                                        <h5 class="text-center">
                                            Dolar
                                            <i style="vertical-align: text-bottom;" class="bx bx-right-arrow-alt"></i>
                                            Pesos
                                        </h5>
                                        <p class="text-center">
                                            <em>Se utiliza para calcular el valor de la tasa de cambio de Dólares a pesos</em>
                                        </p>

                                        <div class="mb-3">
                                            <label class="form-label">Pesos/Dolar</label>
                                            <input value="<?php echo $pesoDolar ?>" name="peso" class="form-control">
                                        </div>

                                        <h5 class="text-center">
                                            Pesos
                                            <i style="vertical-align: text-bottom;" class="bx bx-right-arrow-alt"></i>
                                            Bolívares
                                        </h5>
                                        <p class="text-center">
                                            <em>Se aplica a los productos de origen colombiano y se emplea para determinar el valor de cambio de pesos a bolívares (Los productos de origen venezolanos seguirán siendo calculados a la tasa definida para Dolar/Bs)</em>
                                        </p>
                                        <div class="mb-3">
                                            <label class="form-label">Pesos/Bolívar</label>
                                            <input value="<?php echo $peso_bolivar ?>" name="peso_bolivar" class="form-control">
                                        </div>

                                        <h5 class="text-center">
                                            Bolívares
                                            <i style="vertical-align: text-bottom;" class="bx bx-right-arrow-alt"></i>
                                            Pesos
                                        </h5>

                                        <p class="text-center">
                                            <em>Se aplica a los productos de origen venezolano y se emplea para determinar el valor de cambio de bolívares a pesos (Los productos de origen colombiano seguirán siendo calculados a la tasa definida para Dolar/Pesos)</em>
                                        </p>

                                        <div class="mb-3">
                                            <label class="form-label">Bolívar/Pesos</label>
                                            <input value="<?php echo $bolivar_peso ?>" name="bolivarPeso" class="form-control">
                                        </div>


                                        <div class="pt-3 d-flex justify-content-between">
                                            <button class="btn btn-success">Actualizar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class='col-lg-6 '>
                            <div class='x_panel'>
                                <div class='x_title'>
                                    <h2> Tasa mostradas en el carrito</h2>

                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content'>

                                    <form id="form-tasas" class="p-4 border rounded shadow-sm ">
                                        <div id="contenedor-tasas"></div>

                                        <button type="submit" class="btn btn-primary mt-3" id="btn-actualizar">Actualizar</button>
                                    </form>
                                </div>
                            </div>
                        </div>


                        <script>
                            const tasasDisponibles = {
                                "precio_dolar_visible": [
                                    "Precio Dólar",
                                    "Dolares",
                                    "Muestra el precio del producto en dólares estadounidenses."
                                ],
                                "precio_peso_visible": [
                                    "Dolar a Pesos",
                                    "Pesos",
                                    "Muestra el precio en pesos colombianos, calculado a partir del valor en dólares (Dólar/Peso)."
                                ],
                                "precio_bs_visible": [
                                    "Precio Bs",
                                    "Bolívares",
                                    "Muestra el precio en bolívares, considerando el país de origen del producto y la tasa correspondiente para bolívares (Dólar/Bs o Peso/Bs)."
                                ],
                                "precio_bolivar_peso": [
                                    "Bolívar a Peso",
                                    "Pesos",
                                    "Muestra el precio en pesos para productos venezolanos, calculado con la tasa Bs/Peso. Los productos colombianos mantienen su precio original."
                                ],
                                "precio_bolivar_dolar": [
                                    "Bolívar a Dólar",
                                    "Dolares",
                                    "Muestra el precio en dólares basado en la tasa oficial BCV, utilizando como referencia el valor final del producto en bolívares."
                                ]
                            };

                            function cargarTasas() {
                                fetch('../../configurar/tasas_mostradas.php?accion=obtener')
                                    .then(res => res.json())
                                    .then(res => {
                                        const contenedor = document.getElementById('contenedor-tasas');
                                        contenedor.innerHTML = "";

                                        if (res.status === 'success') {
                                            const tasas = res.data;

                                            const grupos = {
                                                Dolares: [],
                                                Pesos: [],
                                                Bolívares: []
                                            };

                                            Object.entries(tasasDisponibles).forEach(([key, label]) => {
                                                grupos[label[1]].push({
                                                    key,
                                                    label
                                                });
                                            });

                                            Object.entries(grupos).forEach(([moneda, items]) => {
                                                const groupName = `tasas_grupo_${moneda}`;
                                                contenedor.innerHTML += `<div class="mt-3 mb-3"><b>${moneda.toUpperCase()}</b><br/>`;

                                                items.forEach(({
                                                    key,
                                                    label
                                                }) => {
                                                    const checked = tasas[key] && tasas[key][0] ? 'checked' : '';
                                                    const isBS = label[1] === 'Bolívares';
                                                    const disabled = isBS ? 'disabled' : '';
                                                    const finalChecked = isBS ? 'checked' : checked;
                                                    contenedor.innerHTML += `
                                                        <div class="form-check mb-1">
                                                        <input style="display: flex; align-items: flex-start; gap: 8px;" class="form-check-input" type="radio" id="${key}" 
                                                            name="${groupName}" 
                                                            value="${key}" ${finalChecked} ${disabled} data-moneda="${moneda}">
                                                            <label class="form-check-label" for="${key}">
                                                                <b>${label[0]}</b><br>
                                                                <small>${label[2]}</small>
                                                            </label>
                                                        </div>
                                                    `;
                                                });

                                                contenedor.innerHTML += `</div>`;
                                            });
                                        } else {
                                            Swal.fire("Error", res.message, "error");
                                        }
                                    })
                                    .catch(() => Swal.fire("Error", "Error al obtener las tasas.", "error"));
                            }

                            // Inicial
                            cargarTasas();

                            // Envío del formulario
                            document.getElementById('form-tasas').addEventListener('submit', function(e) {
                                e.preventDefault();

                                const formData = new FormData();
                                const monedas = ['Dolares', 'Pesos', 'Bolívares'];
                                // Solo agregar los radios marcados que tienen atributo name
                                monedas.forEach(moneda => {
                                    const seleccionado = document.querySelector(`input[name="tasas_grupo_${moneda}"]:checked`);
                                    if (seleccionado) {
                                        formData.append(`tasas[${seleccionado.value}]`, "1");
                                    }
                                });


                                formData.append('accion', 'actualizar');

                                fetch('../../configurar/tasas_mostradas.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.status === 'success') {
                                            Swal.fire("Actualizado", data.message, "success").then(() => {
                                                cargarTasas();
                                            });
                                        } else {
                                            Swal.fire("Error", data.message, "error");
                                        }
                                    })
                                    .catch(() => {
                                        Swal.fire("Error", "No se pudo actualizar.", "error");
                                    });
                            });
                        </script>



                        <script>
                            function tipoCambio(value) {



                                if (value == 2) {
                                    $('#section_redondeo').hide(300)
                                    $('#section_tasa_bs').hide()
                                    $('#section_margen').hide(300)

                                } else if (value == 3) {
                                    $('#section_redondeo').show(300)
                                    $('#section_tasa_bs').hide()
                                    $('#section_margen').show(300)

                                } else {
                                    $('#section_tasa_bs').show(300)
                                    $('#section_margen').hide()
                                    $('#section_redondeo').hide()
                                }
                            }
                        </script>

                    </div>



                </div>



            </div>
            <!-- /page content -->

            <!-- footer content -->

            <!-- /footer content -->
        </div>
    </div>

    <!-- jQuery -->
    <script src='../vendors/jquery/dist/jquery.min.js'>
    </script>
    <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'>
    </script>
    <!-- FastClick -->
    <script src='../vendors/fastclick/lib/fastclick.js'></script>
    <!-- NProgress -->
    <script src='../vendors/nprogress/nprogress.js'></script>
    <!-- bootstrap-progressbar -->
    <script src='../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js'></script>
    <!-- Dropzone.js -->
    <script src='../vendors/dropzone/dist/min/dropzone.min.js'></script>

    <!-- iCheck -->
    <script src='../vendors/iCheck/icheck.min.js'></script>
    <!-- bootstrap-daterangepicker -->
    <script src='../vendors/moment/min/moment.min.js'></script>
    <script src='../vendors/bootstrap-daterangepicker/daterangepicker.js'></script>
    <!-- bootstrap-wysiwyg -->
    <script src='../vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js'></script>
    <script src='../vendors/jquery.hotkeys/jquery.hotkeys.js'></script>
    <script src='../vendors/google-code-prettify/src/prettify.js'></script>
    <!-- jQuery Tags Input -->
    <script src='../vendors/jquery.tagsinput/src/jquery.tagsinput.js'></script>
    <!-- Switchery -->
    <script src='../vendors/switchery/dist/switchery.min.js'></script>
    <!-- Select2 -->
    <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
    <!-- Parsley -->
    <script src='../vendors/parsleyjs/dist/parsley.min.js'></script>
    <!-- Autosize -->
    <script src='../vendors/autosize/dist/autosize.min.js'></script>
    <!-- jQuery autocomplete -->
    <script src='../vendors/devbridge-autocomplete/dist/jquery.autocomplete.min.js'></script>
    <!-- starrr -->
    <script src='../vendors/starrr/dist/starrr.js'></script>
    <!-- Custom Theme Scripts -->
    <script src='../build/js/custom.min.js'></script>

</body>

</html>