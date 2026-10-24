<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != '1') {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}

$topnav = topnav();
$bss_id = $_SESSION["bss_id"];

$tickets = 0;
$ticketsFijo = 0;
$cortes_caja = 0;
$registro_clientes = 0;

$query = "SELECT * FROM configuracion WHERE bss_id = $bss_id";
$search = $conexion->query($query);
if ($search->num_rows > 0) {
    while ($rowT = $search->fetch_assoc()) {
        $tickets = $rowT['tickets'];
        $ticketsFijo = $rowT['bs_ticket'];
        $cortes_caja = $rowT['cortes_caja'];
        $registro_clientes = $rowT['registro_clientes'];
    }
}


?>


<!DOCTYPE html>
<html lang='es'>

<head>
    <title>Configuración | iSeller POS</title>
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
                <div class="dash-header">
                    <div id="nombre_pagina"></div>
                    <span class="prefs-auto"><i class="bi bi-cloud-check"></i> Los cambios se guardan al instante</span>
                </div>

                <div class="dash-panel prefs-panel">
                    <div class="panel-header">
                        <h6><i class="bi bi-sliders2"></i> Preferencias del sistema</h6>
                    </div>
                    <div class="panel-body p-0">
                        <?php
                        // Interruptor de cada opción; el JS guarda por AJAX con el name del input
                        function renderToggle($name, $isChecked, $icon, $title, $description)
                        {
                            return '
                            <div class="pref-item">
                                <span class="pref-icon"><i class="bi ' . $icon . '"></i></span>
                                <div class="pref-text">
                                    <h6>' . $title . '</h6>
                                    <p>' . $description . '</p>
                                </div>
                                <label class="pref-switch">
                                    <input type="checkbox" name="' . htmlspecialchars($name) . '" class="config-toggle" ' . ($isChecked ? 'checked' : '') . '>
                                    <span class="track"></span>
                                </label>
                            </div>';
                        }
                        ?>

                        <?= renderToggle(
                            'tickets_imp',
                            $tickets == 1,
                            'bi-printer',
                            'Impresión de tickets',
                            'Imprime un ticket automáticamente al realizar una venta.'
                        ); ?>

                        <?= renderToggle(
                            'only_bs',
                            $ticketsFijo == 1,
                            'bi-currency-exchange',
                            'Imprimir solo en Bolívares',
                            'Si está desactivado, el ticket se imprime en la moneda con la que pague el cliente.'
                        ); ?>

                        <?= renderToggle(
                            'cortes_caja',
                            $cortes_caja == 1,
                            'bi-calculator',
                            'Cortes de caja',
                            'Permite realizar el corte de caja al cerrar el turno.'
                        ); ?>

                        <?= renderToggle(
                            'registro_clientes',
                            $registro_clientes == 1,
                            'bi-person-vcard',
                            'Registro de clientes al vender',
                            'Solicita los datos del cliente al momento de realizar una venta.'
                        ); ?>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.querySelectorAll('.config-toggle').forEach(checkbox => {
                            checkbox.addEventListener('change', function() {
                                const name = this.name;
                                const status = this.checked ? '1' : '0';
                                const checkboxElement = this;

                                checkboxElement.disabled = true;

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
                                            Alerta.toast('success', data.message);
                                        } else {
                                            throw new Error(data.message || 'Error desconocido');
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error al actualizar la configuración:', error.message);
                                        alert('No se pudo guardar el cambio: ' + error.message);
                                        checkboxElement.checked = !checkboxElement.checked;
                                    })
                                    .finally(() => {
                                        checkboxElement.disabled = false;
                                    });
                            });
                        });
                    });
                </script>
            </div>
            <!-- /page content -->

            <style>
                .right_col {
                    background: var(--dash-bg);
                    min-height: 100vh;
                    padding: 24px 28px !important;
                }

                .prefs-panel {
                    max-width: 860px;
                }

                .dash-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 16px;
                    margin-bottom: 24px;
                }

                .dash-panel {
                    border: 1px solid var(--dash-border);
                    border-radius: 14px;
                    background: var(--dash-card);
                    margin-bottom: 24px;
                    overflow: hidden;
                }

                .dash-panel .panel-header {
                    padding: 16px 22px;
                    border-bottom: 1px solid var(--dash-border);
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 12px;
                    flex-wrap: wrap;
                }

                .dash-panel .panel-header h6 {
                    margin: 0;
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--dash-text);
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .dash-panel .panel-header h6 i {
                    color: var(--dash-mint);
                    font-size: 16px;
                }

                .prefs-auto {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 12px;
                    color: var(--dash-text-muted);
                }

                .prefs-auto i {
                    color: var(--dash-mint);
                    font-size: 14px;
                }

                .pref-item {
                    display: flex;
                    align-items: center;
                    gap: 16px;
                    padding: 18px 22px;
                    border-bottom: 1px solid var(--dash-border);
                }

                .pref-item:last-child {
                    border-bottom: 0;
                }

                .pref-icon {
                    width: 42px;
                    height: 42px;
                    border-radius: 12px;
                    background: var(--dash-mint-dim);
                    color: var(--dash-mint);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 19px;
                    flex-shrink: 0;
                }

                .pref-text {
                    flex: 1;
                    min-width: 0;
                }

                .pref-text h6 {
                    margin: 0 0 3px;
                    font-size: 14px;
                    font-weight: 700;
                    color: var(--dash-text);
                }

                .pref-text p {
                    margin: 0;
                    font-size: 12.5px;
                    line-height: 1.45;
                    color: var(--dash-text-muted);
                }

                /* Interruptor */
                .pref-switch {
                    position: relative;
                    display: inline-flex;
                    flex-shrink: 0;
                    margin: 0;
                    cursor: pointer;
                }

                .pref-switch input {
                    position: absolute;
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .pref-switch .track {
                    width: 46px;
                    height: 26px;
                    border-radius: 99px;
                    background: #39414c;
                    border: 1px solid var(--dash-border);
                    display: block;
                    position: relative;
                    transition: background .2s, border-color .2s;
                }

                .pref-switch .track::after {
                    content: '';
                    position: absolute;
                    top: 2px;
                    left: 2px;
                    width: 20px;
                    height: 20px;
                    border-radius: 50%;
                    background: #fff;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, .35);
                    transition: transform .2s;
                }

                .pref-switch input:checked + .track {
                    background: var(--dash-mint);
                    border-color: var(--dash-mint);
                }

                .pref-switch input:checked + .track::after {
                    transform: translateX(20px);
                }

                .pref-switch input:focus-visible + .track {
                    box-shadow: 0 0 0 3px rgba(45, 212, 160, .25);
                }

                .pref-switch input:disabled + .track {
                    opacity: .55;
                    cursor: wait;
                }

                @media (max-width: 575px) {
                    .right_col {
                        padding: 16px !important;
                    }

                    .pref-item {
                        padding: 16px;
                        gap: 12px;
                    }

                    .prefs-auto {
                        display: none;
                    }
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
    <script src="js/nombre_pagina.js"></script>

</body>

</html>
