<?php
require_once('includes/requires.php');


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

    <title>Tasas de cambio</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .page-head {
            margin-bottom: 28px;
        }

        .page-head h4 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0;
        }

        .page-head p {
            color: var(--dash-text-muted);
            margin: 2px 0 0;
            font-size: 13px;
        }



        .rate-block {
            padding: 22px 24px 18px;
        }

        .rate-block+.rate-block {
            border-top: 1px solid var(--dash-border);
        }

        .rate-block-header {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 18px;
        }

        .rate-block-header .icon-box {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(45, 212, 160, .12);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .rate-block-header .icon-box ion-icon {
            font-size: 18px;
            color: var(--dash-mint);
        }

        .rate-block-header h6 {
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text);
            margin: 0 0 2px;
        }

        .rate-block-header p {
            font-size: 12px;
            color: var(--dash-text-muted);
            margin: 0;
            line-height: 1.4;
        }

        .rate-field {
            margin-bottom: 14px;
        }

        .rate-field:last-child {
            margin-bottom: 0;
        }

        .rate-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--dash-text-muted);
            margin-bottom: 4px;
        }

        .rate-label .currency-pair {
            color: var(--dash-mint);
            text-transform: none;
            letter-spacing: 0;
            font-size: 13px;
        }

        .rate-hint {
            font-size: 11px;
            color: var(--dash-text-muted);
            margin-bottom: 4px;
            line-height: 1.4;
            opacity: .7;
        }

        .rate-field .form-control {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .rate-field .form-control:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .rate-field select.form-control {
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%238892a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px;
        }

        .conv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .conv-card {
            background: rgba(255, 255, 255, .03);
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            padding: 14px 16px 16px;
            transition: border-color .2s ease, background .2s ease;
        }

        .conv-card:hover {
            border-color: rgba(45, 212, 160, .2);
            background: rgba(45, 212, 160, .02);
        }

        .conv-card .conv-head {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .conv-card .conv-arrow {
            font-size: 13px;
            font-weight: 700;
            color: var(--dash-text);
            letter-spacing: .3px;
        }

        .conv-card .conv-arrow .from {
            color: var(--dash-text-muted);
        }

        .conv-card .conv-arrow .to {
            color: var(--dash-mint);
        }

        .conv-card .conv-arrow .sep {
            color: var(--dash-text-muted);
            margin: 0 4px;
        }

        .conv-card .conv-badge {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding: 2px 8px;
            border-radius: 100px;
            background: rgba(255, 255, 255, .06);
            color: var(--dash-text-muted);
        }

        .conv-card .rate-field {
            margin-bottom: 0;
        }

        .rate-action {
            padding: 16px 24px 20px;
            border-top: 1px solid var(--dash-border);
            display: flex;
            justify-content: flex-end;
        }

        .btn-rate-save {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 10px 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .2s ease;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
            cursor: pointer;
        }

        .btn-rate-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
            color: #fff;
        }

        .btn-rate-save ion-icon {
            font-size: 16px;
        }

        .btn-rate-update {
            background: linear-gradient(135deg, #5b9cf5, #4a8be4);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 10px 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .2s ease;
            box-shadow: 0 3px 12px rgba(91, 156, 245, .25);
            cursor: pointer;
        }

        .btn-rate-update:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(91, 156, 245, .35);
            color: #fff;
        }

        .btn-rate-update ion-icon {
            font-size: 16px;
        }

        .conv-card.dim {
            opacity: .55;
            border-color: rgba(239, 90, 111, .15);
            background: rgba(239, 90, 111, .03);
            pointer-events: none;
        }

        .conv-card.dim .conv-arrow .to {
            color: var(--dash-text-muted);
        }

        .conv-card.dim .conv-badge {
            background: rgba(239, 90, 111, .12);
            color: #ef5a6f;
        }

        .conv-card .form-control[readonly] {
            opacity: .6;
            cursor: default;
        }

        .conv-card .form-control[readonly]:focus {
            border-color: var(--dash-border);
            box-shadow: none;
        }

        .conv-card .bcv-source {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: var(--dash-text-muted);
            margin-top: 4px;
            opacity: .6;
        }

        .conv-card .bcv-source ion-icon {
            font-size: 12px;
            color: #5b9cf5;
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

                    <div class="page-head">
                        <h4>Tasas de cambio</h4>
                        <p>Configuración de las tasas de cambio</p>
                    </div>

                    <div class='row'>
                        <!-- LEFT: Tasas de cambio -->
                        <div class='col-lg-6 mb-4'>
                            <div class='dash-panel'>
                                <div class='panel-header'>
                                    <h2><ion-icon name="swap-horizontal-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);vertical-align:middle;"></ion-icon>Tasas de cambio</h2>
                                </div>

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

                                // 1. Definir los estados posibles
                                $configuraciones = [
                                    1 => ['tasa' => '',      'marg' => 'none', 'disp' => 'none', 'orden' => [1, 2, 3]],
                                    2 => ['tasa' => 'none',  'marg' => 'none', 'disp' => 'none', 'orden' => [2, 1, 3]],
                                    3 => ['tasa' => 'none',  'marg' => '',     'disp' => '',     'orden' => [3, 1, 2]]
                                ];

                                // 2. Obtener valores con fallback a la opción 3 (default)
                                $cfg = $configuraciones[$tipo_tasa_bs] ?? $configuraciones[3];

                                // 3. Asignar variables
                                $display_tasa = ($cfg['tasa'] === 'none') ? 'display: none' : '';
                                $display_marg = ($cfg['marg'] === 'none') ? 'display: none' : '';
                                $display      = ($cfg['disp'] === 'none') ? 'display: none' : '';

                                // 4. Generar opciones dinámicamente
                                $nombres = [1 => 'Tasa manual', 2 => 'Tasa BCV', 3 => 'Tasa BCV + Margen neto'];
                                $options = '';
                                foreach ($cfg['orden'] as $id) {
                                    $options .= '<option value="' . $id . '">' . $nombres[$id] . '</option>';
                                }
                                ?>
                                <form action="../../configurar/tasas.php" method="post">
                                    <div class="rate-block">
                                        <div class="rate-block-header">
                                            <div class="icon-box"><ion-icon name="calculator-outline"></ion-icon></div>
                                            <div>
                                                <h6>Cálculo del Bolívar</h6>
                                                <p>Define cómo se obtiene el precio en bolívares a partir del tipo de cambio</p>
                                            </div>
                                        </div>
                                        <div class="rate-field">
                                            <label class="rate-label">Tipo de tasa</label>
                                            <select name="tipoTasa" class="form-control" onchange="tipoCambio(this.value)">
                                                <?php echo $options ?>
                                            </select>
                                        </div>
                                        <div class="rate-field" id="section_margen" style="<?php echo $display_marg ?>">
                                            <label class="rate-label">Margen neto</label>
                                            <div class="rate-hint">Porcentaje adicional que se suma sobre la tasa BCV</div>
                                            <input value="<?php echo $margen_neto ?>" name="margen" class="form-control">
                                        </div>
                                        <div class="rate-field" id="section_redondeo" style="<?php echo $display ?>">
                                            <label class="rate-label">Redondeo</label>
                                            <div class="rate-hint">Aplica cuando se usa margen neto sobre la tasa BCV</div>
                                            <select name="redondeo" class="form-control">
                                                <?php echo $options_2 ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="rate-block">
                                        <div class="rate-block-header">
                                            <div class="icon-box"><ion-icon name="git-compare-outline"></ion-icon></div>
                                            <div>
                                                <h6>Pares de conversión</h6>
                                                <p>Tasas directas entre divisas según el país de origen del producto</p>
                                            </div>
                                        </div>
                                        <div class="conv-grid">
                                            <div class="conv-card<?php echo ($tipo_tasa_bs != 1) ? ' dim' : '' ?>" id="card-usd-bs">
                                                <div class="conv-head">
                                                    <span class="conv-arrow"><span class="from">USD</span><span class="sep"> → </span><span class="to">Bs</span></span>
                                                    <span class="conv-badge" id="badge-usd-bs"><?php echo ($tipo_tasa_bs == 1) ? 'Manual' : (($tipo_tasa_bs == 3) ? 'BCV + Margen' : 'BCV') ?></span>
                                                </div>
                                                <div class="rate-field">
                                                    <label class="rate-label">Tasa de cambio</label>
                                                    <div class="rate-hint">Aplica a productos con precio base en dólares</div>
                                                    <input value="<?php echo ($tipo_tasa_bs == 1) ? $dolarBolivar : (($tipo_tasa_bs == 3) ? ($bcv + $margen_neto) : $bcv) ?>" name="bolivar" class="form-control" id="input-usd-bs" data-manual="<?php echo $dolarBolivar ?>" <?php echo ($tipo_tasa_bs != 1) ? 'readonly' : '' ?>>
                                                    <div class="bcv-source" id="bcv-source" style="display:<?php echo ($tipo_tasa_bs != 1) ? 'flex' : 'none' ?>">
                                                        <ion-icon name="cloud-download-outline"></ion-icon>
                                                        Tasa BCV · Actualizada automáticamente
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="conv-card">
                                                <div class="conv-head">
                                                    <span class="conv-arrow"><span class="from">USD</span><span class="sep"> → </span><span class="to">COP</span></span>
                                                    <span class="conv-badge">General</span>
                                                </div>
                                                <div class="rate-field">
                                                    <label class="rate-label">Tasa de cambio</label>
                                                    <div class="rate-hint">Pesos colombianos por cada dólar</div>
                                                    <input value="<?php echo $pesoDolar ?>" name="peso" class="form-control">
                                                </div>
                                            </div>
                                            <div class="conv-card">
                                                <div class="conv-head">
                                                    <span class="conv-arrow"><span class="from">COP</span><span class="sep"> → </span><span class="to">Bs</span></span>
                                                    <span class="conv-badge">Origen Colombia</span>
                                                </div>
                                                <div class="rate-field">
                                                    <label class="rate-label">Tasa de cambio</label>
                                                    <div class="rate-hint">Productos colombianos convertidos a bolívares</div>
                                                    <input value="<?php echo $peso_bolivar ?>" name="peso_bolivar" class="form-control">
                                                </div>
                                            </div>
                                            <div class="conv-card">
                                                <div class="conv-head">
                                                    <span class="conv-arrow"><span class="from">Bs</span><span class="sep"> → </span><span class="to">COP</span></span>
                                                    <span class="conv-badge">Origen Venezuela</span>
                                                </div>
                                                <div class="rate-field">
                                                    <label class="rate-label">Tasa de cambio</label>
                                                    <div class="rate-hint">Productos venezolanos convertidos a pesos</div>
                                                    <input value="<?php echo $bolivar_peso ?>" name="bolivarPeso" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rate-action">
                                        <button class="btn-rate-save">
                                            <ion-icon name="checkmark-circle-outline"></ion-icon>
                                            Guardar cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- RIGHT: Tasas mostradas en el carrito -->
                        <div class='col-lg-6 mb-4'>
                            <div class='dash-panel'>
                                <div class='panel-header'>
                                    <h2><ion-icon name="cart-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);vertical-align:middle;"></ion-icon>Tasas mostradas en el carrito</h2>
                                </div>
                                <div class='panel-body'>
                                    <form id="form-tasas" style="padding:20px 24px;">
                                        <div id="contenedor-tasas"></div>
                                        <div style="margin-top:20px;display:flex;justify-content:flex-end;">
                                            <button type="submit" class="btn-rate-update" id="btn-actualizar">
                                                <ion-icon name="refresh-outline"></ion-icon>
                                                Actualizar
                                            </button>
                                        </div>
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
                                                contenedor.innerHTML += `<div style="margin-bottom:16px;"><div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--dash-text-muted);padding:4px 0 10px;border-bottom:1px solid var(--dash-border);margin-bottom:10px;">${moneda.toUpperCase()}</div>`;

                                                items.forEach(({
                                                    key,
                                                    label
                                                }) => {
                                                    const checked = tasas[key] && tasas[key][0] ? 'checked' : '';
                                                    const isBS = label[1] === 'Bolívares';
                                                    const disabled = isBS ? 'disabled' : '';
                                                    const finalChecked = isBS ? 'checked' : checked;
                                                    contenedor.innerHTML += `
                                                        <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1px solid var(--dash-border);border-radius:10px;margin-bottom:8px;cursor:pointer;">
                                                            <input style="margin-top:2px;accent-color:var(--dash-mint);flex-shrink:0;" type="radio" id="${key}" 
                                                                name="${groupName}" 
                                                                value="${key}" ${finalChecked} ${disabled} data-moneda="${moneda}">
                                                            <label for="${key}" style="margin:0;cursor:pointer;">
                                                                <b style="display:block;font-size:13px;font-weight:600;color:var(--dash-text);margin-bottom:2px;">${label[0]}</b>
                                                                <small style="display:block;font-size:11px;color:var(--dash-text-muted);line-height:1.4;">${label[2]}</small>
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

                            cargarTasas();

                            document.getElementById('form-tasas').addEventListener('submit', function(e) {
                                e.preventDefault();

                                const formData = new FormData();
                                const monedas = ['Dolares', 'Pesos', 'Bolívares'];
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
                            const bcvRate = <?php echo $bcv ?>;

                            function calcBcvConMargen() {
                                const margen = parseFloat(document.querySelector('[name="margen"]').value) || 0;
                                return bcvRate + margen;
                            }

                            function actualizarBolivar() {
                                const sel = document.querySelector('[name="tipoTasa"]').value;
                                if (sel == 3) {
                                    document.getElementById('input-usd-bs').value = calcBcvConMargen();
                                }
                            }

                            function tipoCambio(value) {
                                const input = document.getElementById('input-usd-bs');
                                const card = document.getElementById('card-usd-bs');
                                const badge = document.getElementById('badge-usd-bs');
                                const source = document.getElementById('bcv-source');

                                if (value == 2) {
                                    $('#section_redondeo').hide(300);
                                    $('#section_margen').hide(300);
                                    input.value = bcvRate;
                                    input.readOnly = true;
                                    card.classList.add('dim');
                                    badge.textContent = 'BCV';
                                    source.style.display = 'flex';
                                } else if (value == 3) {
                                    $('#section_redondeo').show(300);
                                    $('#section_margen').show(300);
                                    input.value = calcBcvConMargen();
                                    input.readOnly = true;
                                    card.classList.add('dim');
                                    badge.textContent = 'BCV + Margen';
                                    source.style.display = 'flex';
                                } else {
                                    input.value = input.dataset.manual;
                                    input.readOnly = false;
                                    card.classList.remove('dim');
                                    badge.textContent = 'Manual';
                                    source.style.display = 'none';
                                    $('#section_margen').hide();
                                    $('#section_redondeo').hide();
                                }
                            }

                            document.addEventListener('input', function(e) {
                                if (e.target.matches('[name="margen"]')) {
                                    actualizarBolivar();
                                }
                            });
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
    <script src='../build/js/custom.js'></script>

</body>

</html>