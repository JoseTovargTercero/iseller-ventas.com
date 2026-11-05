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
$sucursalesTodas = asig_listar_sucursales($conexion, (int) $bss_id);
$sucursalesFiltro = $esAdmin
    ? $sucursalesTodas
    : array_values(array_filter($sucursalesTodas, static fn($s) => (int) $s['id'] === $sucursalSesion));
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

        .d-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .d-stat {
            position: relative;
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            background: rgba(255, 255, 255, .02);
            padding: 16px 18px;
            overflow: hidden;
        }

        .d-stat::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--stat-color, var(--dash-mint));
        }

        .d-stat span {
            display: block;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .45px;
            text-transform: uppercase;
            color: var(--dash-text-muted);
            margin-bottom: 7px;
        }

        .d-stat strong {
            display: block;
            font-size: 23px;
            font-weight: 700;
            color: var(--dash-text);
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
        }

        .d-stat small {
            display: block;
            font-size: 11.5px;
            color: var(--dash-text-muted);
            margin-top: 5px;
        }

        .d-stat.original {
            --stat-color: #5b9cf5;
        }

        .d-stat.abonado {
            --stat-color: var(--dash-mint);
        }

        .d-stat.saldo {
            --stat-color: #f5b45b;
        }

        .d-stat.excedente {
            --stat-color: #b18cff;
        }

        .d-filters {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            padding: 16px 20px;
            border-bottom: 1px solid var(--dash-border);
        }

        .d-filter {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 160px;
        }

        .d-filter label,
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

        .d-icon-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .18s ease;
            text-decoration: none;
        }

        .d-icon-btn:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, .5);
            background: rgba(45, 212, 160, .06);
            text-decoration: none;
        }

        .d-icon-btn ion-icon {
            font-size: 16px;
        }

        .d-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .d-table {
            width: 100%;
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

        .d-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .25px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .d-badge-pendiente {
            background: rgba(245, 180, 91, .12);
            color: #f5b45b;
        }

        .d-badge-parcial {
            background: rgba(91, 156, 245, .12);
            color: #5b9cf5;
        }

        .d-badge-pagada {
            background: rgba(45, 212, 160, .12);
            color: var(--dash-mint);
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

        .d-modal .modal-content {
            background: #181b22;
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            color: var(--dash-text);
        }

        .d-modal .modal-header,
        .d-modal .modal-footer {
            border-color: var(--dash-border);
            padding: 15px 20px;
        }

        .d-modal .modal-body {
            padding: 18px 20px;
            max-height: 74vh;
            overflow-y: auto;
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

        .d-detail-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .d-detail-cell {
            border: 1px solid var(--dash-border);
            border-radius: 9px;
            padding: 10px 12px;
            background: rgba(255, 255, 255, .02);
        }

        .d-detail-cell span {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--dash-text-muted);
            margin-bottom: 3px;
        }

        .d-detail-cell strong {
            font-size: 13px;
            color: var(--dash-text);
        }

        .d-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--dash-text-muted);
            margin: 18px 0 8px;
        }

        .d-summary-line {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 7px 0;
            border-bottom: 1px solid rgba(46, 53, 62, .45);
            font-size: 12.5px;
        }

        .d-summary-line span {
            color: var(--dash-text-muted);
        }

        .d-summary-line b {
            color: var(--dash-text);
            font-variant-numeric: tabular-nums;
        }

        .d-summary-line.total b {
            color: var(--dash-mint);
            font-size: 16px;
        }

        .d-pay-form {
            border: 1px solid var(--dash-border);
            border-radius: 11px;
            padding: 14px;
            background: rgba(45, 212, 160, .025);
            margin-top: 8px;
        }

        .d-pay-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .d-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        @media (max-width: 1000px) {
            .d-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .d-detail-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .right_col {
                padding: 14px 12px !important;
            }

            .d-stats {
                grid-template-columns: 1fr;
            }

            .d-stat {
                padding: 14px 16px;
            }

            .d-filters {
                padding: 14px;
            }

            .d-filter {
                width: 100%;
            }

            .d-pay-grid {
                grid-template-columns: 1fr;
            }

            .d-table thead {
                display: none;
            }

            .d-table tbody tr {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px 10px;
                padding: 12px;
                border-bottom: 1px solid var(--dash-border);
            }

            .d-table tbody td {
                padding: 2px 0;
                border: none;
            }

            .d-table tbody td[data-label]::before {
                content: attr(data-label) ': ';
                color: var(--dash-text-muted);
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: .3px;
            }

            .d-detail-grid {
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
                <div class="d-stats">
                    <div class="d-stat original">
                        <span>Deuda original</span>
                        <strong id="stat-original">$0.00</strong>
                        <small id="stat-obligaciones">0 obligaciones</small>
                    </div>
                    <div class="d-stat abonado">
                        <span>Total abonado</span>
                        <strong id="stat-abonado">$0.00</strong>
                        <small>Pagos registrados</small>
                    </div>
                    <div class="d-stat saldo">
                        <span>Saldo pendiente</span>
                        <strong id="stat-saldo">$0.00</strong>
                        <small id="stat-estados">0 pendientes · 0 parciales · 0 pagadas</small>
                    </div>
                    <div class="d-stat excedente">
                        <span>Excedente</span>
                        <strong id="stat-excedente">$0.00</strong>
                        <small>Sobre pagos por encima de la deuda</small>
                    </div>
                </div>

                <div class="dash-panel" style="margin-bottom:18px;">
                    <div class="panel-header">
                        <h6><ion-icon name="git-compare-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Deuda global por sucursal</h6>
                    </div>
                    <div class="panel-body p-0">
                        <div class="d-table-wrap">
                            <table class="d-table" id="tabla-pares">
                                <thead>
                                    <tr>
                                        <th>Sucursal acreedora</th>
                                        <th>Sucursal deudora</th>
                                        <th class="d-center">Obligaciones</th>
                                        <th class="d-right">Original</th>
                                        <th class="d-right">Abonado</th>
                                        <th class="d-right">Saldo</th>
                                        <th class="d-right">Excedente</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="d-empty d-none" id="pares-vacios">
                            <ion-icon name="git-compare-outline"></ion-icon>
                            <h5>Sin relaciones de deuda</h5>
                            <p>Las obligaciones entre sucursales aparecerán aquí.</p>
                        </div>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="panel-header">
                        <h6><ion-icon name="wallet-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>Obligaciones económicas</h6>
                    </div>
                    <div class="panel-body p-0">
                        <div class="d-filters">
                            <div class="d-filter">
                                <label for="filtro-deudora">Sucursal deudora</label>
                                <select id="filtro-deudora" class="d-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($sucursalesFiltro as $sucursal): ?>
                                        <option value="<?= (int) $sucursal['id'] ?>"><?= htmlspecialchars($sucursal['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="d-filter">
                                <label for="filtro-estado">Estado</label>
                                <select id="filtro-estado" class="d-select">
                                    <option value="">Todos</option>
                                    <option value="PENDIENTE">Pendiente</option>
                                    <option value="PARCIAL">Parcial</option>
                                    <option value="PAGADA">Pagada</option>
                                </select>
                            </div>
                            <button type="button" class="d-btn d-btn-ghost" id="btn-filtrar">
                                <ion-icon name="funnel-outline"></ion-icon> Filtrar
                            </button>
                        </div>
                        <div class="d-table-wrap">
                            <table class="d-table" id="tabla-deudas">
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th>Fecha</th>
                                        <th>Acreedora</th>
                                        <th>Deudora</th>
                                        <th class="d-right">Original</th>
                                        <th class="d-right">Abonado</th>
                                        <th class="d-right">Saldo</th>
                                        <th class="d-right">Excedente</th>
                                        <th class="d-center">Estado</th>
                                        <th class="d-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="d-empty d-none" id="deudas-vacias">
                            <ion-icon name="wallet-outline"></ion-icon>
                            <h5>No hay obligaciones registradas</h5>
                            <p>Las deudas se generan al aplicar una asignación de mercancía.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade d-modal" id="modalDeuda" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deuda-titulo">Detalle de la obligación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" id="deuda-body"></div>
                <div class="modal-footer">
                    <button type="button" class="d-btn d-btn-ghost" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <script src="../vendors/nprogress/nprogress.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="../build/js/modal.js"></script>
    <script src="js/alerta.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        const CFG = <?= json_encode([
                    'esAdmin' => $esAdmin,
                    'endpoint' => '../../configurar/asignaciones_sucursales_back.php',
                    'hoy' => $hoy,
                    'bcv' => $bcvActual,
                    'pesoDolar' => $pesoDolarActual,
                ], JSON_UNESCAPED_UNICODE) ?>;

        let cuentas = {};

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

        const qty = (valor) => {
            const numero = Number(valor || 0);
            return Number.isInteger(numero) ? String(numero) : String(numero).replace(/\.?0+$/, '');
        };

        const estadoBadge = (estado) => {
            const clase = estado === 'PAGADA' ? 'd-badge-pagada' : (estado === 'PARCIAL' ? 'd-badge-parcial' : 'd-badge-pendiente');
            return '<span class="d-badge ' + clase + '">' + esc(estado) + '</span>';
        };

        async function post(accion, datos = {}) {
            const body = new URLSearchParams();
            body.set('accion', accion);
            Object.entries(datos).forEach(([clave, valor]) => body.set(clave, valor ?? ''));
            const res = await fetch(CFG.endpoint, {
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

        function filtros() {
            return {
                sucursal_deudora: document.getElementById('filtro-deudora').value,
                estado: document.getElementById('filtro-estado').value
            };
        }

        async function cargarTodo() {
            const datos = filtros();
            const [resumen, lista] = await Promise.all([
                post('deudas_resumen', datos),
                post('deudas_listar', datos)
            ]);

            if (resumen.status === 'ok') {
                pintarResumen(resumen.data.resumen);
            }
            if (lista.status === 'ok') {
                pintarDeudas(lista.data.items || []);
            } else {
                document.querySelector('#tabla-deudas tbody').innerHTML = '<tr><td colspan="10" class="d-center" style="padding:24px;color:#ef5a6f;">' + esc(lista.msg) + '</td></tr>';
            }
        }

        function pintarResumen(resumen) {
            document.getElementById('stat-original').textContent = money(resumen.total_original);
            document.getElementById('stat-abonado').textContent = money(resumen.total_abonado);
            document.getElementById('stat-saldo').textContent = money(resumen.saldo);
            document.getElementById('stat-excedente').textContent = money(resumen.excedente);
            document.getElementById('stat-obligaciones').textContent = resumen.obligaciones + (resumen.obligaciones === 1 ? ' obligación' : ' obligaciones');
            document.getElementById('stat-estados').textContent = resumen.pendientes + ' pendientes · ' + resumen.parciales + ' parciales · ' + resumen.pagadas + ' pagadas';

            const $tbody = document.querySelector('#tabla-pares tbody');
            const pares = resumen.pares || [];
            if (pares.length === 0) {
                $tbody.innerHTML = '';
                document.getElementById('pares-vacios').classList.remove('d-none');
                return;
            }
            document.getElementById('pares-vacios').classList.add('d-none');
            $tbody.innerHTML = pares.map((par) => `<tr>
                <td data-label="Acreedora">${esc(par.acreedora_nombre)}</td>
                <td data-label="Deudora">${esc(par.deudora_nombre)}</td>
                <td data-label="Obligaciones" class="d-center">${par.obligaciones}</td>
                <td data-label="Original" class="d-right d-money">${money(par.total_original)}</td>
                <td data-label="Abonado" class="d-right d-money">${money(par.total_abonado)}</td>
                <td data-label="Saldo" class="d-right d-money">${money(par.saldo)}</td>
                <td data-label="Excedente" class="d-right d-money">${money(par.excedente)}</td>
            </tr>`).join('');
        }

        function pintarDeudas(items) {
            cuentas = {};
            items.forEach((item) => {
                cuentas[item.id] = item;
            });

            const $tbody = document.querySelector('#tabla-deudas tbody');
            if (items.length === 0) {
                $tbody.innerHTML = '';
                document.getElementById('deudas-vacias').classList.remove('d-none');
                return;
            }
            document.getElementById('deudas-vacias').classList.add('d-none');
            $tbody.innerHTML = items.map((item) => {
                const accion = item.puede_abonar
                    ? '<button type="button" class="d-icon-btn" data-ver="' + item.id + '" title="Ver y abonar"><ion-icon name="cash-outline"></ion-icon></button>'
                    : '<button type="button" class="d-icon-btn" data-ver="' + item.id + '" title="Ver detalle"><ion-icon name="eye-outline"></ion-icon></button>';
                return `<tr>
                    <td data-label="Documento"><strong>${esc(item.numero_documento)}</strong></td>
                    <td data-label="Fecha">${esc(item.fecha)}</td>
                    <td data-label="Acreedora">${esc(item.acreedora_nombre)}</td>
                    <td data-label="Deudora">${esc(item.deudora_nombre)}</td>
                    <td data-label="Original" class="d-right d-money">${money(item.monto_original)}</td>
                    <td data-label="Abonado" class="d-right d-money">${money(item.total_abonado)}</td>
                    <td data-label="Saldo" class="d-right d-money">${money(item.saldo)}</td>
                    <td data-label="Excedente" class="d-right d-money">${money(item.excedente)}</td>
                    <td data-label="Estado" class="d-center">${estadoBadge(item.estado_deuda)}</td>
                    <td data-label="Acciones" class="d-center">${accion}</td>
                </tr>`;
            }).join('');
        }

        async function verDetalle(cuentaId) {
            const data = await post('deudas_detalle', {
                id: cuentaId
            });
            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }

            const d = data.data;
            const c = d.cuenta;
            const pagos = d.pagos || [];
            const abonado = pagos.reduce((s, p) => s + Number(p.monto_usd || 0), 0);
            const original = Number(c.monto_original);
            const saldo = Math.max(0, original - abonado);
            const excedente = Math.max(0, abonado - original);
            const puedeAbonar = CFG.esAdmin && d.es_creador;

            const filasProductos = (d.detalles || []).map((detalle) => `<tr>
                <td>${esc(detalle.producto_nombre)}</td>
                <td class="d-right">${qty(detalle.cantidad)}</td>
                <td class="d-right d-money">${money(detalle.costo_unitario)}</td>
                <td class="d-right d-money">${money(detalle.subtotal)}</td>
            </tr>`).join('');

            const filasPagos = pagos.length
                ? pagos.map((pago) => `<tr>
                    <td>${esc(pago.fecha)}</td>
                    <td class="d-right d-money">${money(pago.monto_usd)}</td>
                    <td class="d-right">${pago.tasa_bcv !== null ? Number(pago.tasa_bcv) : '—'}</td>
                    <td class="d-right">${pago.tasa_peso_dolar !== null ? Number(pago.tasa_peso_dolar) : '—'}</td>
                    <td>${esc(pago.metodo_pago || '—')}</td>
                    <td>${esc(pago.referencia || '—')}</td>
                    <td>${esc(pago.usuario_nombre || '—')}</td>
                </tr>`).join('')
                : '<tr><td colspan="7" class="d-muted d-center">Sin abonos registrados.</td></tr>';

            document.getElementById('deuda-titulo').textContent = 'Obligación ' + (c.numero_documento || '');
            document.getElementById('deuda-body').innerHTML = `
                <div class="d-detail-grid">
                    <div class="d-detail-cell"><span>Documento</span><strong>${esc(c.numero_documento)}</strong></div>
                    <div class="d-detail-cell"><span>Fecha</span><strong>${esc(c.fecha)}</strong></div>
                    <div class="d-detail-cell"><span>Acreedora</span><strong>${esc(c.acreedora_nombre)}</strong></div>
                    <div class="d-detail-cell"><span>Deudora</span><strong>${esc(c.deudora_nombre)}</strong></div>
                </div>

                <div class="d-summary-line"><span>Monto original</span><b>${money(original)}</b></div>
                <div class="d-summary-line"><span>Total abonado</span><b>${money(abonado)}</b></div>
                <div class="d-summary-line"><span>Saldo pendiente</span><b>${money(saldo)}</b></div>
                <div class="d-summary-line"><span>Excedente</span><b>${money(excedente)}</b></div>
                <div class="d-summary-line"><span>Estado de la deuda</span><b>${estadoBadge(c.estado_deuda)}</b></div>

                <div class="d-section-title">Productos de la asignación</div>
                <div class="d-table-wrap">
                    <table class="d-table">
                        <thead><tr><th>Producto</th><th class="d-right">Cantidad</th><th class="d-right">Costo unitario</th><th class="d-right">Subtotal</th></tr></thead>
                        <tbody>${filasProductos || '<tr><td colspan="4" class="d-muted d-center">Sin productos.</td></tr>'}</tbody>
                    </table>
                </div>

                <div class="d-section-title">Historial de abonos</div>
                <div class="d-table-wrap">
                    <table class="d-table">
                        <thead><tr><th>Fecha</th><th class="d-right">Monto</th><th class="d-right">BCV</th><th class="d-right">Dólar/peso</th><th>Método</th><th>Referencia</th><th>Usuario</th></tr></thead>
                        <tbody>${filasPagos}</tbody>
                    </table>
                </div>

                ${puedeAbonar ? `
                <div class="d-section-title">Registrar abono</div>
                <form class="d-pay-form" id="form-pago">
                    <input type="hidden" id="pago-cuenta" value="${Number(c.id)}">
                    <div class="d-pay-grid">
                        <div class="d-field">
                            <label class="d-label" for="pago-monto">Importe USD *</label>
                            <input type="number" id="pago-monto" class="d-input" min="0.01" step="0.01" required>
                        </div>
                        <div class="d-field">
                            <label class="d-label" for="pago-fecha">Fecha del pago *</label>
                            <input type="date" id="pago-fecha" class="d-input" value="${esc(CFG.hoy)}" required>
                        </div>
                        <div class="d-field">
                            <label class="d-label" for="pago-bcv">Tasa BCV</label>
                            <input type="number" id="pago-bcv" class="d-input" min="0" step="0.0001" value="${Number(CFG.bcv || 0)}">
                        </div>
                        <div class="d-field">
                            <label class="d-label" for="pago-peso">Dólar / peso</label>
                            <input type="number" id="pago-peso" class="d-input" min="0" step="0.0001" value="${Number(CFG.pesoDolar || 0)}">
                        </div>
                        <div class="d-field">
                            <label class="d-label" for="pago-metodo">Método de pago</label>
                            <select id="pago-metodo" class="d-select">
                                <option value="">Seleccione</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Pago móvil">Pago móvil</option>
                                <option value="Zelle">Zelle</option>
                                <option value="Otro">Otro</option>
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
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-top:12px;">
                        <button type="submit" class="d-btn d-btn-primary" id="btn-registrar-pago">
                            <ion-icon name="save-outline"></ion-icon> Registrar abono
                        </button>
                    </div>
                </form>` : (CFG.esAdmin ? '<div class="d-muted" style="font-size:12px;margin-top:14px;">Solo el administrador que creó la asignación puede registrar abonos sobre esta obligación.</div>' : '')}
            `;

            $('#modalDeuda').modal('show');
        }

        document.getElementById('btn-filtrar').addEventListener('click', cargarTodo);

        document.getElementById('tabla-deudas').addEventListener('click', (e) => {
            const boton = e.target.closest('[data-ver]');
            if (!boton) return;
            verDetalle(Number(boton.dataset.ver));
        });

        document.getElementById('deuda-body').addEventListener('submit', async (e) => {
            if (e.target.id !== 'form-pago') return;
            e.preventDefault();

            const monto = Number(document.getElementById('pago-monto').value || 0);
            if (monto <= 0) {
                Alerta.toast('error', 'El monto del abono debe ser mayor que cero.');
                return;
            }

            const boton = document.getElementById('btn-registrar-pago');
            boton.disabled = true;

            const data = await post('registrar_pago', {
                cuenta_id: document.getElementById('pago-cuenta').value,
                monto_usd: monto,
                fecha: document.getElementById('pago-fecha').value,
                tasa_bcv: document.getElementById('pago-bcv').value,
                tasa_peso_dolar: document.getElementById('pago-peso').value,
                metodo_pago: document.getElementById('pago-metodo').value,
                referencia: document.getElementById('pago-referencia').value,
                observaciones: document.getElementById('pago-observaciones').value
            });

            boton.disabled = false;

            if (data.status !== 'ok') {
                Alerta.toast('error', data.msg);
                return;
            }

            Alerta.toast('success', data.msg);
            await cargarTodo();
            await verDetalle(Number(document.getElementById('pago-cuenta').value));
        });

        cargarTodo();
    </script>
</body>

</html>
