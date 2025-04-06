<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');



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
    $nivelUsuario = $_SESSION['nivel'];






    $query2 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }
    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }

    $query2255 = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos225 = $conexion->query($query2255);
    if ($buscarAlumnos225->num_rows > 0) {
        while ($filaAlumnos225 = $buscarAlumnos225->fetch_assoc()) {
            $pesoDolar = $filaAlumnos225['pesoDolar'];
            $bolivarPesoTrans = $filaAlumnos225['bolivarPesoTrans'];
            $bsDolar = $filaAlumnos225['DolarBolivar'];
        }
    }



?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
     
        <title>Agregar Producto</title>

        
        <?php require_once('includes/headers.php'); ?>



        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
   
        <script src='peticion.js'></script>
        <script src='peticion_codigo.js'></script>
  

        <?php
        @$registrado = $_GET['agregado'];
        @$foto = $_GET['foto'];
        @$codigo = $_GET['codigo'];
        switch ($registrado) {
            case ('correcto'):
                echo '<script>
            function mensaje(){	
			}
            </script>
            <body onload="mensaje()">
            </body>';
                break;
        }

        switch ($_GET['accion']) {
            case ('borrado'):
             /*   echo '<script>
            function mensaje(){	
			alertify.success("Producto eliminado.");}
            </script>
            <body onload="mensaje()">
            </body>';*/
                break;

            case ('editado'):
            /*    echo '<script>
            function mensaje(){	
			alertify.success("Se modifico un producto.");}
            </script>
            <body onload="mensaje()">
            </body>';*/
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
                            setInterval(() => {
                                actualizarTasaCambio();
                                realizarCalculos();
                            }, 1000);

                            // Actualiza el tipo de cambio del dólar
                            function actualizarTasaCambio() {
                                cambioDolar = parseFloat(<?php echo $bsDolar ?>);
                            }

                            // Realiza los cálculos principales
                            function realizarCalculos() {
                                const precioMonedaOrigen = parseFloat(document.getElementById("precioMonedaOrigen").value) || 0;
                                const monedaSeleccionada = document.getElementById("moneda").value;

                                let resultadoConversion = 0;

                                if (monedaSeleccionada === "pesos") {
                                    const conversionPesos = (precioMonedaOrigen / cambioPesoRecepcion) / 1000000;
                                    resultadoConversion = conversionPesos / cambioDolar;
                                } else if (monedaSeleccionada === "bolivares") {
                                    resultadoConversion = precioMonedaOrigen / cambioDolar;
                                } else {
                                    resultadoConversion = precioMonedaOrigen; // En dólares no se transforma
                                }

                                // Actualiza el input con el valor formateado
                                document.getElementById('precio').value = resultadoConversion;

                                // Calcula otros valores relacionados
                                calcularValoresExtras(precioMonedaOrigen);
                            }



                            // Cálculo de división y valores adicionales
                            function calcularValoresExtras(precioCompra) {
                                const cantidadUnidades = parseInt(document.calculadora.cantidad.value) || 1; // Evita división por 0
                                const porcentaje = parseFloat(document.calculadora.porcentaje.value) || 0;

                                try {
                                    // Divide precio entre unidades
                                    const precioUnitario = (precioCompra / cantidadUnidades).toFixed(2);

                                    // Calcula el precio en dólares (compra y venta)
                                    const precioDolarCompra = parseFloat(precioUnitario);
                                    const precioDolarVenta = ((precioDolarCompra * porcentaje / 100) + precioDolarCompra).toFixed(2);
                                    const pesoSalida = Math.round(precioDolarVenta * parseFloat(<?php echo $pesoDolar ?>));
                                    const tipoCambio_pesosBs = parseFloat(<?php echo $bolivarPesoTrans ?>);

                                    // Actualiza resultados en dólares
                                    document.calculadora.resultado.value = `$ ${precioDolarCompra}`;
                                    document.calculadora.resultado2.value = `$ ${precioDolarVenta}`;

                                    // Conversión a bolívares
                                    let bolivarSalida;
                                    const tipoConversion = document.getElementById('origenProducto').value


                                    if (tipoConversion == 'c') {
                                        bolivarSalida = ((pesoSalida / tipoCambio_pesosBs) / 1000).toFixed(2);
                                    } else {
                                        bolivarSalida = (precioDolarVenta * cambioDolar).toFixed(2);
                                    }

                                    document.getElementById('resultado4').value = `${bolivarSalida} BS`;
                                    document.getElementById('resultado3').value = `${formatNumber(pesoSalida)} COP`;

                                } catch (error) {
                                    console.error("Error en los cálculos:", error);
                                }
                            }

                            // Agrega separadores de miles
                            function formatNumber(valor) {
                                return valor.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        </script>
                        <form name='calculadora' action='../../configurar/agregarProducto.php' method='post' id='demo-form2' data-parsley-validate class='form-horizontal form-label-left   fadeInUp animated'>
                            <div class='row'>
                                <div class='col-lg-6 '>
                                    <div class='x_panel'>
                                        <div class='x_title'>
                                            <h2>Datos del Producto <small>* obligatorio</small></h2>

                                            <div class='clearfix'></div>
                                        </div>
                                        <div class='x_content'>
                                            <br />


                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Nombre <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='nombre' name='nombre' required='required' class='form-control '>
                                                </div>
                                            </div>

                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Precio de Compra <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-5 col-sm-5 '>
                                                    <input type='text' id='precioMonedaOrigen' name='precioMonedaOrigen' required='required' class='form-control '>
                                                </div>
                                                <div class='col-md-4 col-sm-4'>
                                                    <select class="form-control" required='required' name="moneda" id="moneda">
                                                        <option value="dolares">Dolares</option>
                                                        <option value="bolivares">Bolivares</option>
                                                        <option value="pesos">Pesos</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <input type='text' id='precio' name='precio' hidden required='required' class='form-control '>

                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Unidades <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='cantidad' name='cantidad' required='required' class='form-control '>
                                                </div>
                                            </div>
                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Porcentaje <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='porcentaje' name='porcentaje' required='required' class='form-control '>
                                                </div>
                                            </div>

                                            <section id='tabla_resultado_codigo'>
                                            </section>

                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Cantidad en Stock <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='stock' name='stock' required='required' class='form-control '>
                                                </div>

                                                <div class='col-md-4 col-sm-4 ' style="display: none;">
                                                    <select class="form-control" name="categoria">
                                                        <option> -- Categoria -- </option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Origen <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <select class="form-control" required='required' name="origenProducto" id="origenProducto">
                                                        <option value="">Seleccione</option>
                                                        <option value="v">Venezolano</option>
                                                        <option value="c">Colombiano</option>
                                                    </select>
                                                </div>

                                            </div>
                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Proveedor <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='proveedor' name='proveedor' required='required' class='form-control '>
                                                </div>
                                            </div>



                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Código de barras <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='c_barras' name='c_barras' required='required' class='form-control '>
                                                </div>
                                            </div>


                                            <div class='ln_solid'></div>
                                            <div class='item form-group'>
                                                <div class='col-md-12 col-sm-12 '>
                                                    <!-- <label for="favorito" class="favorite">Agregar a favoritos &nbsp;&nbsp;<input id="favorito" type="checkbox" class="flat"></label>-->

                                                    <button type='submit' class="btn btn-success actualizar">Agregar</button>

                                                </div>
                                            </div>



                                        </div>
                                    </div>
                                </div>



                                <div class='col-lg-6 '>
                                    <div class='x_panel'>
                                        <div class='x_title'>
                                            <h2>Precios de venta <small>vista previa</small></h2>

                                            <div class='clearfix'></div>
                                        </div>
                                        <div class='x_content'>
                                            <br />
                                            <form class='form-label-left input_mask'>

                                                <div class='form-group row'>
                                                    <label class='col-form-label col-md-3 col-sm-3 '>Precio/Compra</label>
                                                    <div class='col-md-9 col-sm-9 '>
                                                        <input type='text' class='form-control' disabled='disabled' name='resultado' id='resultado'>
                                                        <span class='form-control-feedback right2' aria-hidden='true'><i class='fa fa-dollar'></i></span>
                                                    </div>
                                                </div>

                                                <div class='form-group row'>
                                                    <label class='col-form-label col-md-3 col-sm-3 '>Precio/Venta </label>
                                                    <div class='col-md-9 col-sm-9 '>
                                                        <input type='text' class='form-control' disabled='disabled' name='resultado2' id='resultado2'>
                                                        <span class='form-control-feedback right2' aria-hidden='true'><i class='fa fa-dollar'></i></span>
                                                    </div>
                                                </div>

                                                <div class='form-group row'>
                                                    <label class='col-form-label col-md-3 col-sm-3 '>Precio/Venta</label>
                                                    <div class='col-md-9 col-sm-9 '>
                                                        <input type='text' class='form-control' readonly='readonly' name='resultado3' id='resultado3'>
                                                        <span class='form-control-feedback right2' aria-hidden='true'><strong>COP</strong></span>
                                                    </div>
                                                </div>

                                                <div class='form-group row'>
                                                    <label class='col-form-label col-md-3 col-sm-3 '>Precio/Venta
                                                    </label>
                                                    <div class='col-md-9 col-sm-9 '>
                                                        <input class='date-picker form-control' type='text' readonly='readonly' name='resultado4' id='resultado4'>
                                                        <span class='form-control-feedback right2' aria-hidden='true'><strong>BS</strong></span>
                                                    </div>
                                                </div>


                                                <small><br></small>
                                                <small><br></small>
                                                <small><br></small>


                                                <style>
                                                    .code {
                                                        width: 100%;
                                                        border: 2px solid #ced4da;
                                                        color: #b0b0b0;
                                                        border-style: dashed;
                                                        border-radius: 10px;
                                                    }
                                                </style>




                                            </form>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </form>
                        <?php

                        if (!$_GET['codigo']) {
                            $oculto = "contain: strict";
                        } else {
                            $oculto = "";
                        }

                        ?>
                        <div class='row' style="<?php echo $oculto ?>">
                            <div class='col-lg-12 '>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Producto Agregado</h2>
                                        <ul class='nav navbar-right panel_toolbox'>
                                            <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a>
                                            </li>

                                        </ul>
                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>
                                        <br />
                                        <p>
                                            <br />
                                        <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                                            <thead>
                                                <tr class="headings">
                                                    <th>#</th>
                                                    <th>Nombre </th>
                                                    <th>Precio <small>(Compra)</small></th>
                                                    <th>Cant.</th>
                                                    <th>%</th>
                                                    <th>Stock</th>
                                                    <th>Precio <small>($)</small></th>
                                                    <th>Precio <small>(COP)</small></th>
                                                    <th>Precio <small>(BS)</small></th>
                                                    <th><span class="nobr">Eli.</span>
                                                    </th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <?php
                                                $query6 = $conexion->query("SELECT * FROM productos WHERE codigo='$codigo' AND activo='0'");

                                                if ($query6->num_rows > 0) {
                                                    $tabla6 = '';
                                                    $contador = 1;
                                                    while ($row6 = $query6->fetch_assoc()) {
                                                        $cantidadUnidad = $row6["cantidad_unidades"];
                                                        $precioDolarCompra = $row6["precio_compra"] / $cantidadUnidad;
                                                        $porcentaje = $row6["porcentaje"];
                                                        $foto = $row6["foto"];
                                                        $codeProducto = $row6["codigo"];





                                                        $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
                                                        $precioDolarVenta = number_format($precioDolarVenta, '2', '.', '.');
                                                        ///////////////
                                                        ///////////////
                                                        ///////////////
                                                        ///////////////
                                                        ///////////////     
                                                        $precioBsVenta = $precioDolarVenta * $bsDolar;
                                                        $precioPesoVenta = $precioDolarVenta *  $pesoDolar;
                                                        ///////////////
                                                        ///////////////
                                                        ///////////////
                                                        ///////////////
                                                        ///////////////
                                                        $precioPesoVenta =   number_format($precioPesoVenta, '0', ',', '.');
                                                        $precioBsVenta =   number_format($precioBsVenta, '2', ',', '.');


                                                        if ($row6['favorito'] == 1) {
                                                            $favProducto =  '<a href="../../configurar/listaProductos.php?id=' . $codeProducto . '&favorito=NO"><i class="fa fav fa-star"></i></a>';
                                                        } else {
                                                            $favProducto =  '<a href="../../configurar/listaProductos.php?id=' . $codeProducto . '&favorito=SI"><i class="fa nofav fa-star-o"></i></a>';
                                                        }

                                                        /*
                                                        $campoCategoria = $row6["categoria"];
                                                        $query2222222222222 = "SELECT * FROM categorias WHERE id='$campoCategoria'";
                                                        $buscarAlumnos2222222222222 = $conexion->query($query2222222222222);
                                                        if ($buscarAlumnos2222222222222->num_rows > 0) {
                                                            while ($filaAlumnos2222222222222 = $buscarAlumnos2222222222222->fetch_assoc()) {
                                                                $categoria = $filaAlumnos2222222222222['nombre_categoria'];
                                                            }
                                                        } else {
                                                            $categoria = "Ninguna";
                                                        }
*/


                                                        if ($foto == "SI") {
                                                            $imgProducto = '<img  class="avatar" alt="Avatar" src="images/stock/' . $codeProducto . '.jpg" alt="">';
                                                        } else {
                                                            $imgProducto = "";
                                                        }

                                                        $monto = number_format($row6["precio_compra"], '2', ',', '.');

                                                        switch ($row6["monedaOrigen"]) {
                                                            case ("bolivares"):
                                                                $mon = "BS";
                                                                break;
                                                            case ("pesos"):
                                                                $mon = "COP";
                                                                break;
                                                            case ("dolares"):
                                                                $mon = "$";
                                                                break;
                                                        }




                                                        $tabla6 .= '
          <tr class="even pointer">
                            <td class=" ">' . $contador++ . '</td>
                            <td class=" "><a href="ficha.php?id=' . $row6["id"] . '">' . $row6["nombre"] . '</a></td>
                            <td class=" ">' . $monto . '</td>
                            <td class=" ">' . $row6["cantidad_unidades"] . '</td>
                            <td class=" ">' . $row6["porcentaje"] . '%</td>
                            <td class=" ">' . $row6["stock"] . '</td>
                            <td class=" ">' . $precioDolarVenta . ' $</td>
                            <td class=" ">' . $precioPesoVenta . ' <small>COP</small></td>
                            <td class="a-right a-right ">' . $precioBsVenta . ' <small>BS</small></td>
                            
                            
                            
                            <td class=" last"><a href="../../configurar/listaProductos.php?id=' . $codeProducto . '&borrar=borrar&origen=nuevo"><i class="gray line icon-trash"></i></a>

                          </tr>
       ';
                                                    }
                                                    echo $tabla6;
                                                }
                                                ?>
                                            </tbody>
                                        </table>

                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>

                    <style>
                        .form-control-feedback.right2 {
                            border-left: 1px solid #ccc;
                            right: 25px !important;
                            padding-left: inherit;
                        }

                        .favorite {
                            float: left
                        }

                        .actualizar {
                            float: right;
                        }

                        .texto {
                            position: absolute;
                            margin: auto;
                            transform: translate(-50%, 0px) scale(1);
                        }


                        input[type=file] {
                            display: block;
                        }



                        .fileinput-button input {
                            cursor: pointer;
                            direction: ltr;
                            font-size: 16px;
                            margin: 0;
                            opacity: 0;
                            right: 0;
                            top: 50px;
                        }

                        .fileinput-button {
                            min-width: 100% !important;
                        }

                        input[type=file] {
                            display: block;
                            min-width: 100% !important;
                        }


                        #photo {
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            width: 10%;
                            height: 10%;
                            display: inline-flex;
                            opacity: 0;
                            cursor: pointer;

                        }

                        #estilo_foto {


                            border-radius: 25px;
                            position: absolute;
                            margin-left: 24%;
                            margin-right: 24%;
                            max-width: 100%;

                        }

                        #estilo_foto_car {


                            border-radius: 25px !important;
                            position: absolute !important;
                            margin-left: 30.5% !important;
                            margin-right: 30.5% !important;
                            max-width: 100% !important;

                        }

                        .gray {
                            color: #b8b8b8f0;
                            font-size: 24px;


                        }
                    </style>


                </div>
                <!-- /page content -->

                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
                    </div>
                    <div class='clearfix'></div>
                </footer>
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

        <script>
            // detectar el submit de demo-form2
            $('#demo-form2').submit(function(event) {
                event.preventDefault();
                var form = $(this);
                var formData = new FormData(this);
                $.ajax({
                    type: 'POST',
                    url: form.attr('action'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(data) {
                       // alertify.success("El producto se agrego correctamente.");
                        form[0].reset();
                    }
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