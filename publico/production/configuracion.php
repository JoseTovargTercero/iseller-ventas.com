<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != '1') {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}

$topnav = topnav();
$bss_id = $_SESSION["bss_id"];

$query = "SELECT * FROM configuracion WHERE bss_id = $bss_id";
$search = $conexion->query($query);
if ($search->num_rows > 0) {
    while ($rowT = $search->fetch_assoc()) {
        $tickets = $rowT['tickets'];
        $ticketsFijo = $rowT['bs_ticket'];
    }
}


?>


<!DOCTYPE html>
<html lang='es'>

<head>
    <title>Configuracion </title>
    <?php require_once('includes/headers.php'); ?>
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

                    <h4>Configuracion</h4>
                    <p class="mt-0">Configuracion del sistema</p>

                    <div class='clearfix'></div>
                    <div class='row'>
                        <div class='col-lg-12'>
                            <div class='x_panel'>
                                <div class='x_title'>
                                    <h2>Configuración<small>del sistema</small></h2>
                                    <ul class='nav navbar-right panel_toolbox'>
                                        <li><a class='collapse-link'><i class='fa fa-chevron-up'></i></a>
                                        </li>

                                    </ul>
                                    <div class='clearfix'></div>
                                </div>
                                <div class="x_content">
                                    <?php
                                    // Función para generar los checkbox
                                    function renderCheckbox($name, $isChecked)
                                    {
                                        $checked = $isChecked ? 'checked' : '';
                                        return '
                                            <div class="checkbox_item citem_3">
                                                <label class="checkbox_wrap">
                                                    <input type="checkbox" name="' . htmlspecialchars($name) . '" class="checkbox_inp config-toggle" ' . $checked . '>
                                                    <span class="checkbox_mark"></span>
                                                </label>
                                            </div>';
                                    }
                                    ?>

                                    <!-- Opción: Impresión de tickets -->
                                    <div class="d-flex gap-1">
                                        <div>
                                            <?= renderCheckbox('tickets_imp', $tickets == 1); ?>
                                        </div>
                                        <div>
                                            <h6 class="m-0">
                                                Impresión de tickets.
                                            </h6>
                                            <small class="text-muted"> Imprime tickets al realizar una venta.</small>
                                        </div>


                                    </div>
                                    <hr>
                                    <!-- Opción: Imprimir solo en Bolívares -->
                                    <div class="d-flex gap-1">
                                        <div>
                                            <?= renderCheckbox('only_bs', $ticketsFijo == 1); ?>
                                        </div>
                                        <div>
                                            <h6 class="m-0">Imprimir solo en Bolívares</h6>
                                            <small class="text-muted">Si está desactivado, los tickets se imprimirán en la moneda con la que pague el cliente.</small>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                document.querySelectorAll('.config-toggle').forEach(checkbox => {
                                    checkbox.addEventListener('change', function() {
                                        const name = this.name;
                                        const status = this.checked ? '1' : '0';
                                        const checkboxElement = this;

                                        fetch('../../configurar/configuracion_sistema.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/x-www-form-urlencoded'
                                                },
                                                body: new URLSearchParams({
                                                    name: name.trim(),
                                                    status: status.trim()
                                                })
                                            })
                                            .then(response => {
                                                if (!response.ok) {
                                                    throw new Error('Error en la respuesta del servidor');
                                                }
                                                return response.json();
                                            })
                                            .then(data => {
                                                if (data.status === 'success') {
                                                    Alerta.toast('success', data.message); // Puedes reemplazar con swal/toast
                                                } else {
                                                    throw new Error(data.message || 'Error desconocido');
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error al actualizar la configuración:', error.message);
                                                alert('No se pudo guardar el cambio: ' + error.message);
                                                checkboxElement.checked = !checkboxElement.checked; // Revertir checkbox
                                            });
                                    });
                                });
                            });
                        </script>



                    </div>

                </div>

            </div>
            <!-- /page content -->
            <style>
                .right {
                    float: right;
                }

                .green {
                    color: #1ABB9C;
                    font-size: 28px;
                    margin-left: 10px;
                }

                .gray {
                    color: indianred;
                    font-size: 26px;
                    margin-left: 10px;
                }
            </style>

        </div>
    </div>

    <!-- jQuery -->
    <script src='../vendors/jquery/dist/jquery.min.js'></script>
    <!-- Bootstrap -->
    <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
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
    <script src='../build/js/custom.js'></script>

</body>

</html>