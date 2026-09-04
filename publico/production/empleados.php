<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != 1 && $_SESSION['nivel'] != 2) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();
$nivelUsuario = $_SESSION['nivel'];

$sucursales = [];
if ($nivelUsuario == 1) {
    $suc_stmt = $conexion->prepare("SELECT id, nombre FROM sucursales WHERE bss_id=? ORDER BY nombre");
    if ($suc_stmt) {
        $suc_stmt->bind_param('i', $bss_id);
        $suc_stmt->execute();
        $suc_result = $suc_stmt->get_result();
        if ($suc_result) {
            while ($row = $suc_result->fetch_assoc()) {
                $sucursales[] = $row;
            }
        }
        $suc_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Empleados</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .kpi-card {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 22px 24px 18px;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            border-color: rgba(45, 212, 160, 0.35);
            box-shadow: 0 0 0 1px rgba(45, 212, 160, 0.08), 0 8px 30px rgba(0, 0, 0, 0.25);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #2dd4a0, transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .kpi-card:hover::before {
            opacity: 0.6;
        }

        .kpi-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }

        .kpi-info h6 {
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text-muted);
            margin: 0 0 2px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .kpi-info small {
            font-size: 11px;
            color: rgba(136, 146, 160, 0.6);
        }

        .kpi-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(45, 212, 160, 0.1);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(45, 212, 160, 0.15);
            flex-shrink: 0;
        }

        .kpi-icon-circle ion-icon {
            font-size: 22px;
            color: var(--dash-mint);
        }

        .kpi-value {
            font-size: 30px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.1;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .kpi-metrics {
            display: flex;
            gap: 18px;
            font-size: 12px;
        }

        .kpi-metrics .metric-up {
            color: var(--dash-mint);
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .kpi-metrics .metric-down {
            color: var(--dash-danger);
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .dash-panel {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--dash-border);
        }

        .panel-header h6 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-header h6 ion-icon {
            font-size: 16px;
            color: var(--dash-mint);
        }

        .panel-body {
            padding: 0;
        }

        .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .dash-table thead th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--dash-text-muted);
            border-bottom: 1px solid var(--dash-border);
            background: transparent;
        }

        .dash-table tbody tr {
            transition: background .15s ease;
            border-bottom: 1px solid rgba(46, 53, 62, .4);
        }

        .dash-table tbody tr:last-child {
            border-bottom: none;
        }

        .dash-table tbody tr:hover {
            background: rgba(45, 212, 160, .03);
        }

        .dash-table tbody td {
            padding: 12px 14px;
            color: var(--dash-text);
            vertical-align: middle;
        }

        .btn-dash-new {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all .2s ease;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
            cursor: pointer;
        }

        .btn-dash-new:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
            color: #fff;
        }

        .modal-content {
            background: var(--dash-card) !important;
            border: 1px solid var(--dash-border) !important;
            border-radius: 14px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
        }

        .modal-header {
            border-bottom: 1px solid var(--dash-border);
            padding: 18px 22px 14px;
        }

        .modal-header .close {
            color: var(--dash-text-muted);
            opacity: .7;
            font-size: 24px;
        }

        .modal-header .close:hover {
            opacity: 1;
            color: var(--dash-text);
        }

        .modal-title {
            color: var(--dash-text);
            font-size: 16px;
            font-weight: 600;
        }

        .modal-body {
            padding: 18px 22px;
        }

        .modal-footer {
            border-top: 1px solid var(--dash-border);
            padding: 14px 22px 18px;
        }

        .modal .form-control {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
        }

        .modal .form-control:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .modal .form-group {
            margin-bottom: 6px;
        }

        .modal .col-form-label {
            color: var(--dash-text-muted);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding-top: 8px;
            padding-bottom: 4px;
        }

        .btn-dash-submit {
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 24px;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-dash-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        }

        .btn-dash-close {
            background: rgba(255, 255, 255, .06);
            border: none;
            color: var(--dash-text-muted);
            font-size: 13px;
            font-weight: 600;
            padding: 9px 24px;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-dash-close:hover {
            background: rgba(255, 255, 255, .1);
        }

        .badge-on {
            background: #32d7c0;
            color: #fff;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-off {
            background: #666;
            color: #fff;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-action {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
            padding: 0;
            font-size: 14px;
        }

        .btn-action:hover {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
        }

        .btn-action.btn-danger:hover {
            border-color: #ef5a6f;
            color: #ef5a6f;
        }

        .swal2-popup {
            background: var(--dash-card) !important;
            border: 1px solid var(--dash-border) !important;
            border-radius: 14px !important;
        }

        .swal2-title {
            color: var(--dash-text) !important;
        }

        .swal2-html-container {
            color: var(--dash-text-muted) !important;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, #2dd4a0, #25b88a) !important;
            border: none !important;
            border-radius: 8px !important;
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class="right_col" role="main">
                <div class="kpi-row">
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Empleados activos</h6>
                                <small>Personal activo en nómina</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="people-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi-total">0</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Gasto mensual</h6>
                                <small>Total pagos mensuales</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="wallet-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi-mensual">$0</div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-top">
                            <div class="kpi-info">
                                <h6>Gasto semanal</h6>
                                <small>Total pagos semanales</small>
                            </div>
                            <div class="kpi-icon-circle">
                                <ion-icon name="calendar-outline"></ion-icon>
                            </div>
                        </div>
                        <div class="kpi-value" id="kpi-semanal">$0</div>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="panel-header">
                        <h6><ion-icon name="people-outline"></ion-icon>Empleados</h6>
                        <div>
                            <button class="btn-dash-new" onclick="abrirModal()">
                                <ion-icon name="add-outline" style="font-size:16px;"></ion-icon> Nuevo empleado
                            </button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive" style="padding:0 16px 16px;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Rol</th>
                                        <th>Tipo pago</th>
                                        <th>Monto</th>
                                        <th>Día ejecución</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-empleados">
                                    <tr>
                                        <td colspan="8" style="text-align:center;color:var(--dash-text-muted);">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo/Editar Empleado -->
    <div class="modal fade" id="modal-empleado" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Nuevo empleado</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="form-empleado" autocomplete="off">
                    <input type="hidden" id="emp-id" value="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="col-form-label">Nombre *</label>
                                    <input class="form-control" id="emp-nombre" placeholder="Nombre completo" required maxlength="255">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="col-form-label">Rol *</label>
                                    <input class="form-control" id="emp-rol" placeholder="Ej: Mesero, Cajero..." required maxlength="255">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label">Tipo de pago *</label>
                                    <select class="form-control" id="emp-tipo-pago" required onchange="toggleDiaEjecucion()">
                                        <option value="">Seleccione</option>
                                        <option value="SEMANAL">Semanal</option>
                                        <option value="MENSUAL">Mensual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label">Monto (USD) *</label>
                                    <input type="number" class="form-control" id="emp-monto" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                        <?php if ($nivelUsuario == 1): ?>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label">Sucursal *</label>
                                    <select class="form-control" id="emp-sucursal" required>
                                        <?php foreach ($sucursales as $suc): ?>
                                            <option value="<?= $suc['id'] ?>"><?= htmlspecialchars($suc['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label" id="dia-label">Día de ejecución *</label>
                                    <select class="form-control" id="emp-dia" required></select>
                                    <small style="color:var(--dash-text-muted);" id="dia-hint"></small>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label class="col-form-label">Observación</label>
                                    <input class="form-control" id="emp-observacion" maxlength="500">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-dash-close" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-dash-submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'bottom-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        const AJAX_URL = '../../configurar/empleados_crud.php';
        let todosEmpleados = [];

        const DIAS_SEMANA = [
            { val: '1', label: 'Lunes' },
            { val: '2', label: 'Martes' },
            { val: '3', label: 'Miércoles' },
            { val: '4', label: 'Jueves' },
            { val: '5', label: 'Viernes' },
            { val: '6', label: 'Sábado' },
            { val: '7', label: 'Domingo' }
        ];

        function toggleDiaEjecucion() {
            const tipo = $('#emp-tipo-pago').val();
            const select = $('#emp-dia');
            const label = $('#dia-label');
            const hint = $('#dia-hint');

            select.empty();

            if (tipo === 'SEMANAL') {
                label.text('Día de la semana *');
                hint.text('');
                DIAS_SEMANA.forEach(function(d) {
                    select.append('<option value="' + d.val + '">' + d.label + '</option>');
                });
            } else if (tipo === 'MENSUAL') {
                label.text('Día del mes *');
                hint.text('Si el mes no tiene ese día, se aplica el último día disponible');
                for (let i = 1; i <= 31; i++) {
                    const extra = (i >= 29) ? ' (ajuste)' : '';
                    select.append('<option value="' + i + '">' + i + extra + '</option>');
                }
            } else {
                label.text('Día de ejecución *');
                hint.text('');
                select.append('<option value="">Primero seleccione tipo</option>');
            }
        }

        function getSucursalId() {
            return $('#sucursal-select').val() || 0;
        }

        function cargarTabla() {
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                dataType: 'json',
                data: { accion: 'listar', id_sucursal: getSucursalId() }
            }).done(function(resp) {
                if (resp.status !== 'ok') return;
                todosEmpleados = resp.data;
                renderTabla(resp.data);
                calcularKPIs(resp.data);
            });
        }

        function renderTabla(data) {
            const tbody = $('#tbody-empleados');
            tbody.empty();

            if (data.length === 0) {
                tbody.html('<tr><td colspan="8" style="text-align:center;color:var(--dash-text-muted);">No hay empleados registrados</td></tr>');
                return;
            }

            const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

            data.forEach(function(e, i) {
                const monto = parseFloat(e.monto_pago).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                const tipoLabel = e.tipo_pago === 'SEMANAL'
                    ? '<span class="label label-primary">Semanal</span>'
                    : '<span class="label label-warning">Mensual</span>';
                const dia = e.tipo_pago === 'SEMANAL' ? (DIAS[e.dia_ejecucion] || e.dia_ejecucion) : 'Día ' + e.dia_ejecucion;
                const estado = e.activo == 1
                    ? '<span class="badge-on">Activo</span>'
                    : '<span class="badge-off">Inactivo</span>';

                const accVer = '<button type="button" class="btn-action" onclick="verDetalle(this)" title="Ver detalle">'
                    + '<ion-icon name="eye-outline" style="font-size:15px;"></ion-icon></button>';
                const accEditar = '<button type="button" class="btn-action" onclick="editarEmpleado(this)" title="Editar">'
                    + '<ion-icon name="create-outline" style="font-size:15px;"></ion-icon></button>';
                const accToggle = e.activo == 1
                    ? '<button type="button" class="btn-action btn-danger" onclick="toggleEmpleado(' + e.id + ', 0)" title="Desactivar">'
                    + '<ion-icon name="ban-outline" style="font-size:15px;"></ion-icon></button>'
                    : '<button type="button" class="btn-action" onclick="toggleEmpleado(' + e.id + ', 1)" title="Activar">'
                    + '<ion-icon name="checkmark-outline" style="font-size:15px;"></ion-icon></button>'
                    + '<button type="button" class="btn-action btn-danger" onclick="eliminarEmpleado(' + e.id + ')" title="Eliminar">'
                    + '<ion-icon name="trash-outline" style="font-size:15px;"></ion-icon></button>';

                const detalle = {
                    id: e.id, nombre: e.nombre, rol: e.rol, tipo_pago: e.tipo_pago,
                    monto_pago: monto,
                    dia_texto: dia, sucursal: e.sucursal_nombre || '—',
                    activo: e.activo, observacion: e.observacion || ''
                };
                const detalleStr = $('<div>').text(JSON.stringify(detalle)).html();

                tbody.append(
                    '<tr data-detalle=\'' + detalleStr + '\'>'
                    + '<td>' + (i + 1) + '</td>'
                    + '<td><strong>' + $('<span>').text(e.nombre).html() + '</strong></td>'
                    + '<td>' + $('<span>').text(e.rol).html() + '</td>'
                    + '<td>' + tipoLabel + '</td>'
                    + '<td style="text-align:right;"><strong>$ ' + monto + '</strong></td>'
                    + '<td>' + dia + '</td>'
                    + '<td>' + estado + '</td>'
                    + '<td style="text-align:center;">' + accVer + accEditar + accToggle + '</td>'
                    + '</tr>'
                );
            });
        }

        function calcularKPIs(data) {
            let activos = 0, mensual = 0, semanal = 0;
            data.forEach(function(e) {
                if (e.activo == 1) {
                    activos++;
                    const monto = parseFloat(e.monto_pago) || 0;
                    if (e.tipo_pago === 'MENSUAL') {
                        mensual += monto;
                        semanal += monto / 4;
                    } else {
                        mensual += monto * 4;
                        semanal += monto;
                    }
                }
            });
            $('#kpi-total').text(activos);
            $('#kpi-mensual').text('$' + mensual.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
            $('#kpi-semanal').text('$' + semanal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
        }

        function abrirModal() {
            $('#modal-title').text('Nuevo empleado');
            $('#form-empleado')[0].reset();
            $('#emp-id').val('');
            toggleDiaEjecucion();
            $('#modal-empleado').modal('show');
        }

        function editarEmpleado(btn) {
            const fila = $(btn).closest('tr');
            const d = JSON.parse(fila.attr('data-detalle'));
            $('#modal-title').text('Editar empleado');
            $('#emp-id').val(d.id);
            $('#emp-nombre').val(d.nombre);
            $('#emp-rol').val(d.rol);
            $('#emp-tipo-pago').val(d.tipo_pago);
            toggleDiaEjecucion();
            $('#emp-dia').val(d.dia_ejecucion);
            $('#emp-monto').val(d.monto_pago.replace(/,/g, ''));
            $('#emp-observacion').val(d.observacion);
            $('#modal-empleado').modal('show');
        }

        function verDetalle(btn) {
            const fila = $(btn).closest('tr');
            const d = JSON.parse(fila.attr('data-detalle'));
            const estado = d.activo == 1 ? '<span class="badge-on">Activo</span>' : '<span class="badge-off">Inactivo</span>';
            Swal.fire({
                title: 'Detalle del empleado',
                html: '<div style="text-align:left;font-size:13px;line-height:2;color:var(--dash-text-muted);">'
                    + '<strong style="color:var(--dash-text);">Nombre:</strong> ' + $('<span>').text(d.nombre).html() + '<br>'
                    + '<strong style="color:var(--dash-text);">Rol:</strong> ' + $('<span>').text(d.rol).html() + '<br>'
                    + '<strong style="color:var(--dash-text);">Tipo pago:</strong> ' + d.tipo_pago + '<br>'
                    + '<strong style="color:var(--dash-text);">Monto:</strong> $ ' + d.monto_pago + '<br>'
                    + '<strong style="color:var(--dash-text);">Día ejecución:</strong> ' + d.dia_texto + '<br>'
                    + '<strong style="color:var(--dash-text);">Sucursal:</strong> ' + $('<span>').text(d.sucursal).html() + '<br>'
                    + '<strong style="color:var(--dash-text);">Estado:</strong> ' + estado + '<br>'
                    + (d.observacion ? '<strong style="color:var(--dash-text);">Observación:</strong> ' + $('<span>').text(d.observacion).html() : '')
                    + '</div>',
                confirmButtonText: 'Cerrar',
                customClass: { popup: 'swal2-popup' }
            });
        }

        function toggleEmpleado(id, nuevoEstado) {
            const accion = nuevoEstado ? 'activar' : 'desactivar';
            const texto = nuevoEstado ? 'activar' : 'desactivar';
            Swal.fire({
                title: '¿' + texto.charAt(0).toUpperCase() + texto.slice(1) + ' empleado?',
                text: 'El empleado será ' + (nuevoEstado ? 'activado' : 'desactivado'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2dd4a0',
                cancelButtonColor: '#666',
                confirmButtonText: 'Sí, ' + texto
            }).then(function(result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: AJAX_URL,
                    type: 'POST',
                    dataType: 'json',
                    data: { accion: accion, id: id }
                }).done(function(resp) {
                    if (resp.status === 'ok') {
                        Toast.fire({ icon: 'success', title: resp.msg });
                        cargarTabla();
                    } else {
                        Swal.fire('Error', resp.msg, 'error');
                    }
                });
            });
        }

        function eliminarEmpleado(id) {
            Swal.fire({
                title: '¿Eliminar empleado?',
                text: 'Esta acción eliminará el empleado y su regla recurrente asociada permanentemente',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef5a6f',
                cancelButtonColor: '#666',
                confirmButtonText: 'Sí, eliminar'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: AJAX_URL,
                    type: 'POST',
                    dataType: 'json',
                    data: { accion: 'eliminar', id: id }
                }).done(function(resp) {
                    if (resp.status === 'ok') {
                        Toast.fire({ icon: 'success', title: resp.msg });
                        cargarTabla();
                    } else {
                        Swal.fire('Error', resp.msg, 'error');
                    }
                });
            });
        }

        $('#form-empleado').on('submit', function(e) {
            e.preventDefault();
            const id = $('#emp-id').val();
            const sucursalSel = document.getElementById('emp-sucursal');
            const datos = {
                accion: id ? 'editar' : 'crear',
                id: id,
                nombre: $('#emp-nombre').val(),
                rol: $('#emp-rol').val(),
                tipo_pago: $('#emp-tipo-pago').val(),
                monto_pago: $('#emp-monto').val(),
                dia_ejecucion: $('#emp-dia').val(),
                observacion: $('#emp-observacion').val(),
                id_sucursal: sucursalSel ? sucursalSel.value : <?= intval($_SESSION['sucursal'] ?? 0) ?>
            };

            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                dataType: 'json',
                data: datos
            }).done(function(resp) {
                if (resp.status === 'ok') {
                    Toast.fire({ icon: 'success', title: resp.msg });
                    $('#modal-empleado').modal('hide');
                    cargarTabla();
                } else {
                    Swal.fire('Error', resp.msg, 'error');
                }
            });
        });

        $(document).ready(function() {
            toggleDiaEjecucion();
            cargarTabla();
        });
    </script>
</body>

</html>
