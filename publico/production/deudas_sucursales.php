<?php
require_once('includes/requires.php');
require_once('../../configurar/_asignaciones_sucursales.php');

$esAdmin = (int) $_SESSION['nivel'] === 1;
$sucursalSesion = (int) ($_SESSION['sucursal'] ?? 0);

if (!$esAdmin && !in_array('deudas_sucursales.php', $_SESSION['permisos'] ?? [], true)) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();
$sucursales = asig_listar_sucursales($conexion, (int) $bss_id);
if (!$esAdmin) {
    $sucursales = array_values(array_filter($sucursales, static fn($s) => (int) $s['id'] === $sucursalSesion));
}
$hoy = date('Y-m-d');
$bcvActual = isset($bcv) ? (float) $bcv : 0;
$pesoDolarActual = isset($pesoDolar) ? (float) $pesoDolar : 0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Deudas entre sucursales</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .d-panel-body {
            padding: 18px 20px;
        }

        .d-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--dash-text-muted);
            margin: 0;
        }

        .d-input,
        .d-select,
        .d-textarea {
            width: 100%;
            background: rgba(255, 255, 255, .035);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            color: var(--dash-text);
            padding: 9px 12px;
            font-size: 13px;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .d-input:focus,
        .d-select:focus,
        .d-textarea:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .d-select option {
            background: #181b22;
            color: var(--dash-text);
        }

        .d-textarea {
            min-height: 72px;
            resize: vertical;
        }

        .d-pay-form {
            border: 1px solid var(--dash-border);
            border-radius: 11px;
            padding: 16px;
            background: rgba(45, 212, 160, .025);
            margin: 0;
        }

        .d-pay-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .d-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .d-form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--dash-border);
        }

        .d-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: 1px solid transparent;
            border-radius: 9px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            transition: all .18s ease;
            white-space: nowrap;
            text-decoration: none;
        }

        .d-btn ion-icon {
            font-size: 16px;
        }

        .d-btn-primary {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #07130f;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
        }

        .d-btn-primary:hover {
            color: #07130f;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        }

        .d-btn-ghost {
            background: rgba(255, 255, 255, .04);
            border-color: var(--dash-border);
            color: var(--dash-text-muted);
        }

        .d-btn-ghost:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, .45);
            background: rgba(45, 212, 160, .06);
        }

        .d-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .d-info {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .d-info-cell {
            border: 1px solid var(--dash-border);
            border-radius: 9px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, .02);
        }

        .d-info-cell span {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--dash-text-muted);
            margin-bottom: 3px;
        }

        .d-info-cell strong {
            font-size: 14px;
            color: var(--dash-text);
            font-variant-numeric: tabular-nums;
        }

        .d-info-msg {
            grid-column: 1 / -1;
            padding: 10px 12px;
            border: 1px dashed var(--dash-border);
            border-radius: 9px;
            color: var(--dash-text-muted);
            font-size: 12.5px;
            background: rgba(255, 255, 255, .02);
        }

        .d-info-msg.error {
            color: #ef5a6f;
            border-color: rgba(239, 90, 111, .35);
            background: rgba(239, 90, 111, .05);
        }

        .d-empty {
            padding: 44px 20px;
            text-align: center;
            color: var(--dash-text-muted);
        }

        .d-empty ion-icon {
            font-size: 40px;
            opacity: .35;
        }

        .d-empty h5 {
            color: var(--dash-text);
            font-size: 15px;
            margin: 12px 0 4px;
        }

        .d-empty p {
            margin: 0;
            font-size: 12.5px;
        }

        .d-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .d-table {
            width: 100%;
            min-width: 880px;
            border-collapse: collapse;
            font-size: 13px;
        }

        .d-table thead th {
            padding: 10px 13px;
            text-align: left;
            font-weight: 700;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--dash-text-muted);
            border-bottom: 1px solid var(--dash-border);
            background: rgba(255, 255, 255, .015);
            white-space: nowrap;
        }

        .d-table tbody td {
            padding: 11px 13px;
            color: var(--dash-text);
            border-bottom: 1px solid rgba(46, 53, 62, .55);
            vertical-align: middle;
        }

        .d-table tbody tr:last-child td {
            border-bottom: none;
        }

        .d-table tbody tr:hover {
            background: rgba(45, 212, 160, .025);
        }

        .d-right {
            text-align: right !important;
        }

        .d-center {
            text-align: center !important;
        }

        .d-muted {
            color: var(--dash-text-muted) !important;
        }

        .d-money {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .d-obs {
            min-width: 160px;
            max-width: 280px;
            white-space: normal;
            word-break: break-word;
        }

        .d-modal .modal-content {
            background: #181b22;
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            color: var(--dash-text);
            max-height: calc(100vh - 80px);
        }

        .d-modal .modal-header,
        .d-modal .modal-footer {
            border-color: var(--dash-border);
            padding: 15px 20px;
            flex-shrink: 0;
        }

        .d-modal .modal-body {
            padding: 18px 20px;
            display: block;
            flex: 1 1 auto;
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .d-modal .modal-title {
            font-size: 16px;
            font-weight: 700;
        }

        .d-modal .close {
            color: var(--dash-text-muted);
            opacity: .8;
            text-shadow: none;
        }

        .d-hist-resumen {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        @media (max-width: 1000px) {
            .d-info,
            .d-hist-resumen {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .right_col {
                padding: 14px 12px !important;
            }

            .d-panel-body {
                padding: 14px;
            }

            .d-pay-grid {
                grid-template-columns: 1fr;
            }

            .d-pay-form {
                padding: 13px;
            }

            .d-form-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .d-form-actions .d-btn {
                width: 100%;
            }

            .d-info,
            .d-hist-resumen {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class="right_col" role="main">
                <?php if (!$esAdmin): ?>
                    <div class="dash-panel">
                        <div class="d-empty">
                            <ion-icon name="lock-closed-outline"></ion-icon>
                            <h5>Acceso restringido</h5>
                            <p>Solo un administrador puede registrar abonos entre sucursales.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="dash-panel">
                        <div class="panel-header">
                            <h6><ion-icon name="wallet-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Registrar abono</h6>
                        </div>
                        <div class="panel-body d-panel-body">
                            <form id="form-abono" class="d-pay-form" autocomplete="off">
                                <div class="d-pay-grid" style="margin-bottom:14px;">
                                    <div class="d-field">
                                        <label class="d-label" for="deuda-acreedora">Sucursal acreedora *</label>
                                        <select id="deuda-acreedora" class="d-select" required>
                                            <option value="">Seleccione</option>
                                            <?php foreach ($sucursales as $sucursal): ?>
                                                <option value="<?= (int) $sucursal['id'] ?>"><?= htmlspecialchars($sucursal['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="d-field">
                                        <label class="d-label" for="deuda-deudora">Sucursal deudora *</label>
                                        <select id="deuda-deudora" class="d-select" required>
                                            <option value="">Seleccione</option>
                                            <?php foreach ($sucursales as $sucursal): ?>
                                                <option value="<?= (int) $sucursal['id'] ?>"><?= htmlspecialchars($sucursal['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div id="deuda-info" class="d-info d-none"></div>

                                <div class="d-pay-grid">
                                    <div class="d-field">
                                        <label class="d-label" for="pago-monto">Importe USD *</label>
                                        <input type="number" id="pago-monto" class="d-input" min="0.01" step="0.01" required>
                                    </div>
                                    <div class="d-field">
                                        <label class="d-label" for="pago-fecha">Fecha del pago *</label>
                                        <input type="date" id="pago-fecha" class="d-input" value="<?= htmlspecialchars($hoy, ENT_QUOTES, 'UTF-8') ?>" required>
                                    </div>
                                    <div class="d-field">
                                        <label class="d-label" for="pago-bcv">Tasa BCV</label>
                                        <input type="number" id="pago-bcv" class="d-input" min="0" step="0.0001" value="<?= htmlspecialchars((string) $bcvActual, ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="d-field">
                                        <label class="d-label" for="pago-peso">Dólar / peso</label>
                                        <input type="number" id="pago-peso" class="d-input" min="0" step="0.0001" value="<?= htmlspecialchars((string) $pesoDolarActual, ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="d-field">
                                        <label class="d-label" for="pago-metodo">Método de pago</label>
                                        <select id="pago-metodo" class="d-select">
                                            <option value="">Seleccione</option>
                                            <option value="Punto">(1) Punto</option>
                                            <option value="BioPago">(2) BioPago</option>
                                            <option value="Pesos">(3) Pesos</option>
                                            <option value="Efectivo">(4) Efectivo</option>
                                            <option value="Pago Móvil">(5) Pago Móvil</option>
                                            <option value="Transferencia">(6) Transferencia</option>
                                            <option value="Dólares">(7) Dólares</option>
                                            <option value="Fraccionado">(8) Fraccionado</option>
                                        </select>
                                    </div>
                                    <div class="d-field">
                                        <label class="d-label" for="pago-referencia">Referencia</label>
                                        <input type="text" id="pago-referencia" class="d-input" placeholder="Número o referencia">
                                    </div>
                                    <div class="d-field" style="grid-column:1/-1;">
                                        <label class="d-label" for="pago-observaciones">Observaciones</label>
                                        <textarea id="pago-observaciones" class="d-textarea" placeholder="Opcional"></textarea>
                                    </div>
                                    <div class="d-field" style="grid-column:1/-1;">
                                        <label class="d-label" for="pago-comprobante">Comprobante (imagen o PDF)</label>
                                        <input type="file" id="pago-comprobante" class="d-input" accept="image/jpeg,image/png,image/webp,application/pdf">
                                        <small class="d-muted" style="font-size:11px;">Máximo 5 MB. Formatos: JPG, PNG, WEBP o PDF.</small>
                                    </div>
                                </div>

                                <div class="d-form-actions">
                                    <button type="button" class="d-btn d-btn-ghost" id="btn-historial" disabled>
                                        <ion-icon name="time-outline"></ion-icon> Historial de abonos
                                    </button>
                                    <button type="submit" class="d-btn d-btn-primary" id="btn-registrar-pago" disabled>
                                        <ion-icon name="save-outline"></ion-icon> Registrar abono
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($esAdmin): ?>
        <div class="modal fade d-modal" id="modalHistorial" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="historial-titulo">Historial de abonos</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-hist-resumen" id="historial-resumen"></div>
                        <div class="d-table-wrap">
                            <table class="d-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Documento</th>
                                        <th class="d-right">Monto</th>
                                        <th class="d-right">BCV</th>
                                        <th class="d-right">Dólar/peso</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th>Observaciones</th>
                                        <th>Comprobante</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody id="historial-body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="d-btn d-btn-ghost" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <script src="../vendors/nprogress/nprogress.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="../build/js/modal.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <?php if ($esAdmin): ?>
        <script>
            const CFG = <?= json_encode([
                            'endpoint' => '../../configurar/asignaciones_sucursales_back.php',
                            'hoy' => $hoy,
                            'bcv' => $bcvActual,
                            'pesoDolar' => $pesoDolarActual,
                        ], JSON_UNESCAPED_UNICODE) ?>;

            const esc = (valor) => String(valor ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[c]));

            const money = (valor) => '$' + Number(valor || 0).toLocaleString('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            async function post(accion, datos = {}, opciones = {}) {
                const body = new URLSearchParams();
                body.set('accion', accion);
                Object.entries(datos).forEach(([clave, valor]) => body.set(clave, valor ?? ''));

                const ejecutar = opciones.silencioso && typeof window.fetchSilencioso === 'function' ?
                    window.fetchSilencioso :
                    fetch;

                const res = await ejecutar(CFG.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body
                });
                const texto = await res.text();
                try {
                    return JSON.parse(texto);
                } catch (error) {
                    console.error('Respuesta no válida:', texto);
                    return {
                        status: 'error',
                        msg: 'El servidor devolvió una respuesta no válida.'
                    };
                }
            }

            async function postForm(accion, formData) {
                formData.set('accion', accion);
                const res = await fetch(CFG.endpoint, {
                    method: 'POST',
                    body: formData
                });
                const texto = await res.text();
                try {
                    return JSON.parse(texto);
                } catch (error) {
                    console.error('Respuesta no válida:', texto);
                    return {
                        status: 'error',
                        msg: 'El servidor devolvió una respuesta no válida.'
                    };
                }
            }

            const selAcreedora = document.getElementById('deuda-acreedora');
            const selDeudora = document.getElementById('deuda-deudora');
            const infoDeuda = document.getElementById('deuda-info');
            const formAbono = document.getElementById('form-abono');
            const btnRegistrar = document.getElementById('btn-registrar-pago');
            const btnHistorial = document.getElementById('btn-historial');
            const modalHistorial = $('#modalHistorial');

            let deudaActual = null;
            let consultaSeq = 0;
            let registrando = false;

            function mostrarInfoMensaje(mensaje, esError = false) {
                infoDeuda.classList.remove('d-none');
                infoDeuda.innerHTML = '<div class="d-info-msg' + (esError ? ' error' : '') + '">' + esc(mensaje) + '</div>';
            }

            function pintarInfo(par) {
                infoDeuda.classList.remove('d-none');
                infoDeuda.innerHTML = `
                    <div class="d-info-cell"><span>Obligaciones</span><strong>${Number(par.obligaciones) || 0}</strong></div>
                    <div class="d-info-cell"><span>Original</span><strong>${money(par.total_original)}</strong></div>
                    <div class="d-info-cell"><span>Abonado</span><strong>${money(par.total_abonado)}</strong></div>
                    <div class="d-info-cell"><span>Saldo pendiente</span><strong>${money(par.saldo)}</strong></div>
                `;
            }

            async function consultarDeuda() {
                const secuencia = ++consultaSeq;
                deudaActual = null;
                btnRegistrar.disabled = true;
                btnHistorial.disabled = true;

                const acreedora = Number(selAcreedora.value) || 0;
                const deudora = Number(selDeudora.value) || 0;

                if (!acreedora || !deudora) {
                    infoDeuda.classList.add('d-none');
                    infoDeuda.innerHTML = '';
                    return;
                }

                if (acreedora === deudora) {
                    mostrarInfoMensaje('La sucursal acreedora y la deudora no pueden ser la misma.', true);
                    return;
                }

                mostrarInfoMensaje('Consultando deuda...');

                const data = await post('deudas_general_detalle', {
                    sucursal_acreedora: acreedora,
                    sucursal_deudora: deudora
                }, {
                    silencioso: true
                });

                if (secuencia !== consultaSeq) return;

                if (data.status !== 'ok') {
                    mostrarInfoMensaje(data.msg || 'No se pudo consultar la deuda.', true);
                    return;
                }

                deudaActual = data.data;
                pintarInfo(data.data.par);
                btnRegistrar.disabled = !data.data.puede_abonar;
                btnHistorial.disabled = false;
            }

            function pintarHistorial(data) {
                const par = data.par || {};
                const pagos = (data.pagos || []).slice().reverse();

                document.getElementById('historial-titulo').textContent =
                    'Historial de abonos · ' + (par.acreedora_nombre || '') + ' → ' + (par.deudora_nombre || '');

                document.getElementById('historial-resumen').innerHTML = `
                    <div class="d-info-cell"><span>Original</span><strong>${money(par.total_original)}</strong></div>
                    <div class="d-info-cell"><span>Abonado</span><strong>${money(par.total_abonado)}</strong></div>
                    <div class="d-info-cell"><span>Saldo</span><strong>${money(par.saldo)}</strong></div>
                    <div class="d-info-cell"><span>Excedente</span><strong>${money(par.excedente)}</strong></div>
                `;

                const tbody = document.getElementById('historial-body');
                if (!pagos.length) {
                    tbody.innerHTML = '<tr><td colspan="10" class="d-muted d-center" style="padding:24px;">Sin abonos registrados.</td></tr>';
                    return;
                }

                tbody.innerHTML = pagos.map((pago) => `<tr>
                    <td>${esc(pago.fecha)}</td>
                    <td>${esc(pago.numero_documento || '—')}</td>
                    <td class="d-right d-money">${money(pago.monto_usd)}</td>
                    <td class="d-right">${pago.tasa_bcv !== null ? Number(pago.tasa_bcv) : '—'}</td>
                    <td class="d-right">${pago.tasa_peso_dolar !== null ? Number(pago.tasa_peso_dolar) : '—'}</td>
                    <td>${esc(pago.metodo_pago || '—')}</td>
                    <td>${esc(pago.referencia || '—')}</td>
                    <td class="d-obs">${esc(pago.observaciones || '—')}</td>
                    <td>${pago.comprobante ? '<a href="../../' + encodeURI(pago.comprobante) + '" target="_blank" rel="noopener">Ver</a>' : '—'}</td>
                    <td>${esc(pago.usuario_nombre || '—')}</td>
                </tr>`).join('');
            }

            async function abrirHistorial() {
                const acreedora = Number(selAcreedora.value) || 0;
                const deudora = Number(selDeudora.value) || 0;

                if (!acreedora || !deudora || acreedora === deudora) {
                    Alerta.toast('error', 'Selecciona una deuda entre sucursales válida.');
                    return;
                }

                btnHistorial.disabled = true;

                const data = await post('deudas_general_detalle', {
                    sucursal_acreedora: acreedora,
                    sucursal_deudora: deudora
                }, {
                    silencioso: true
                });

                // Si el usuario cambió la selección mientras se consultaba, se ignora.
                if (Number(selAcreedora.value) !== acreedora || Number(selDeudora.value) !== deudora) {
                    return;
                }

                if (data.status !== 'ok') {
                    btnHistorial.disabled = false;
                    Alerta.toast('error', data.msg || 'No se pudo cargar el historial.');
                    return;
                }

                deudaActual = data.data;
                pintarInfo(data.data.par);
                btnRegistrar.disabled = !data.data.puede_abonar;
                pintarHistorial(data.data);
                btnHistorial.disabled = false;
                modalHistorial.modal('show');
            }

            selAcreedora.addEventListener('change', consultarDeuda);
            selDeudora.addEventListener('change', consultarDeuda);
            btnHistorial.addEventListener('click', abrirHistorial);

            formAbono.addEventListener('submit', async (evento) => {
                evento.preventDefault();

                if (registrando) return;

                const acreedora = Number(selAcreedora.value) || 0;
                const deudora = Number(selDeudora.value) || 0;

                if (!deudaActual || !acreedora || !deudora || acreedora === deudora) {
                    Alerta.toast('error', 'Selecciona una deuda entre sucursales válida.');
                    return;
                }

                if (!deudaActual.puede_abonar) {
                    Alerta.toast('error', 'No tienes permiso para registrar abonos.');
                    return;
                }

                const monto = Number(document.getElementById('pago-monto').value || 0);
                if (!(monto > 0)) {
                    Alerta.toast('error', 'El monto del abono debe ser mayor que cero.');
                    return;
                }

                const fecha = document.getElementById('pago-fecha').value;
                if (!fecha) {
                    Alerta.toast('error', 'La fecha del pago es obligatoria.');
                    return;
                }

                const archivo = document.getElementById('pago-comprobante').files[0];
                if (archivo && archivo.size > 5 * 1024 * 1024) {
                    Alerta.toast('error', 'El comprobante supera los 5 MB.');
                    return;
                }

                const formData = new FormData();
                formData.set('sucursal_acreedora', acreedora);
                formData.set('sucursal_deudora', deudora);
                formData.set('monto_usd', monto);
                formData.set('fecha', fecha);
                formData.set('tasa_bcv', document.getElementById('pago-bcv').value);
                formData.set('tasa_peso_dolar', document.getElementById('pago-peso').value);
                formData.set('metodo_pago', document.getElementById('pago-metodo').value);
                formData.set('referencia', document.getElementById('pago-referencia').value);
                formData.set('observaciones', document.getElementById('pago-observaciones').value);
                if (archivo) {
                    formData.set('comprobante', archivo);
                }

                registrando = true;
                btnRegistrar.disabled = true;
                const contenidoBoton = btnRegistrar.innerHTML;
                btnRegistrar.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Registrando...';

                try {
                    const data = await postForm('registrar_pago_general', formData);

                    if (data.status !== 'ok') {
                        Alerta.toast('error', data.msg || 'No se pudo registrar el abono.');
                        return;
                    }

                    Alerta.toast('success', data.msg || 'Abono registrado correctamente.');
                    document.getElementById('pago-monto').value = '';
                    document.getElementById('pago-referencia').value = '';
                    document.getElementById('pago-observaciones').value = '';
                    document.getElementById('pago-comprobante').value = '';
                    await consultarDeuda();
                } finally {
                    registrando = false;
                    btnRegistrar.innerHTML = contenidoBoton;
                    btnRegistrar.disabled = !(deudaActual && deudaActual.puede_abonar);
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>
