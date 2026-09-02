<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != 1 && $_SESSION['nivel'] != 2) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();

$cat_stmt = $conexion->prepare("SELECT id, nombre FROM gastos_categorias WHERE bss_id=? AND activo=1 ORDER BY nombre");
if ($cat_stmt) {
    $cat_stmt->bind_param('i', $bss_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $categorias = [];
    if ($cat_result) {
        while ($row = $cat_result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    $cat_stmt->close();
} else {
    $categorias = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Gastos Recurrentes</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
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
                <div class="dash-panel">
                    <div class="panel-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <div>
                            <h4 style="margin:0;color:var(--dash-text);">Gastos Recurrentes</h4>
                            <small style="color:var(--dash-text-muted);">Reglas de gastos que se aplican manualmente cada semana</small>
                        </div>
                        <div>
                            <button class="btn-dash-new" onclick="aplicarRecurrentes()" style="background:linear-gradient(135deg,#5b9cf5,#4a8ae0);box-shadow:0 3px 12px rgba(91,156,245,.25);">
                                <ion-icon name="play-outline" style="font-size:16px;"></ion-icon> Aplicar a semana actual
                            </button>
                            <button class="btn-dash-new" onclick="abrirModal()">
                                <ion-icon name="add-outline" style="font-size:16px;"></ion-icon> Nueva regla
                            </button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive" style="padding:0 16px 16px;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Concepto</th>
                                        <th>Categoría</th>
                                        <th>Frecuencia</th>
                                        <th>Monto est.</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-reglas">
                                    <tr>
                                        <td colspan="7" style="text-align:center;color:var(--dash-text-muted);">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva/Editar Regla -->
    <div class="modal fade" id="modal-regla" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Nueva regla de gasto recurrente</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>
                <form id="form-regla" autocomplete="off">
                    <input type="hidden" id="reg-id" value="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <label class="col-form-label">Concepto *</label>
                                    <input class="form-control" id="reg-concepto" placeholder="Ej: Nómina, Alquiler..." required maxlength="255">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label">Categoría</label>
                                    <select class="form-control" id="reg-categoria">
                                        <option value="">Sin categoría</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4" style="display:none;">
                                <input type="hidden" id="reg-tipo" value="FIJO">
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label">Frecuencia *</label>
                                    <select class="form-control" id="reg-frecuencia" required onchange="toggleDiaEjecucion()">
                                        <option value="">Seleccione</option>
                                        <option value="DIARIO">Diario</option>
                                        <option value="SEMANAL">Semanal</option>
                                        <option value="QUINCENAL">Quincenal</option>
                                        <option value="MENSUAL">Mensual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4" style="display:none;">
                                <input type="hidden" id="reg-moneda" value="USD">
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label">Monto (USD)</label>
                                    <input type="number" class="form-control" id="reg-monto" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-lg-4" id="dias-ejecucion-group" style="display:none;">
                                <div class="form-group">
                                    <label class="col-form-label" id="dias-ejecucion-label">Día de ejecución</label>
                                    <select class="form-control" id="reg-dias"></select>
                                    <small style="color:var(--dash-text-muted);" id="dias-ejecucion-hint"></small>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <label class="col-form-label">Fecha inicio *</label>
                                    <input type="date" class="form-control" id="reg-fecha-inicio" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 d-none">
                                <div class="form-group">
                                    <label class="col-form-label">Fecha fin (opcional)</label>
                                    <input type="date" class="form-control" id="reg-fecha-fin">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="col-form-label">Observación</label>
                                    <input class="form-control" id="reg-observacion" maxlength="500">
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
        const AJAX_URL = '../../configurar/gastos_recurrentes_crud.php';
        let todasLasReglas = [];

        const DIAS_SEMANA = [{
                val: '1',
                label: 'Lunes'
            },
            {
                val: '2',
                label: 'Martes'
            },
            {
                val: '3',
                label: 'Miércoles'
            },
            {
                val: '4',
                label: 'Jueves'
            },
            {
                val: '5',
                label: 'Viernes'
            },
            {
                val: '6',
                label: 'Sábado'
            },
            {
                val: '7',
                label: 'Domingo'
            }
        ];

        function toggleDiaEjecucion() {
            const freq = $('#reg-frecuencia').val();
            const group = $('#dias-ejecucion-group');
            const select = $('#reg-dias');
            const label = $('#dias-ejecucion-label');
            const hint = $('#dias-ejecucion-hint');

            select.empty();

            if (freq === 'SEMANAL') {
                label.text('Día de la semana');
                hint.text('');
                DIAS_SEMANA.forEach(function(d) {
                    select.append('<option value="' + d.val + '">' + d.label + '</option>');
                });
                group.show();
            } else if (freq === 'MENSUAL') {
                label.text('Día del mes');
                hint.text('Si el mes no tiene ese día, se aplica el último día disponible');
                for (let i = 1; i <= 31; i++) {
                    const extra = (i >= 29) ? ' (ajuste automático)' : '';
                    select.append('<option value="' + i + '">' + i + extra + '</option>');
                }
                group.show();
            } else {
                group.hide();
            }
        }

        function cargarTabla() {
            $.ajax({
                    url: AJAX_URL,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        accion: 'listar'
                    }
                })
                .done(function(resp) {
                    if (resp.status !== 'ok') return;
                    todasLasReglas = resp.data;
                    const tbody = $('#tbody-reglas');
                    tbody.empty();

                    if (resp.data.length === 0) {
                        tbody.html('<tr><td colspan="7" style="text-align:center;color:var(--dash-text-muted);">No hay reglas de gastos recurrentes</td></tr>');
                        return;
                    }

                    resp.data.forEach(function(r, i) {
                        const badge = r.activo == 1 ?
                            '<span class="badge-on">Activa</span>' :
                            '<span class="badge-off">Inactiva</span>';
                        const btnToggle = r.activo == 1 ?
                            '<button class="btn-action btn-danger" onclick="desactivar(' + r.id + ')" title="Desactivar"><ion-icon name="close-outline"></ion-icon></button>' :
                            '<button class="btn-action" onclick="activar(' + r.id + ')" title="Activar"><ion-icon name="checkmark-outline"></ion-icon></button>';
                        const monto = r.monto_estimado ? '$ ' + parseFloat(r.monto_estimado).toFixed(2) : '—';

                        tbody.append('<tr>' +
                            '<td>' + (i + 1) + '</td>' +
                            '<td><strong>' + r.concepto + '</strong></td>' +
                            '<td>' + (r.categoria_nombre || '—') + '</td>' +
                            '<td>' + r.frecuencia + '</td>' +
                            '<td>' + monto + '</td>' +
                            '<td>' + badge + '</td>' +
                            '<td><button class="btn-action" onclick="editar(' + r.id + ')" title="Editar"><ion-icon name="create-outline"></ion-icon></button> ' + btnToggle + '</td>' +
                            '</tr>');
                    });
                });
        }

        function abrirModal() {
            $('#reg-id').val('');
            $('#form-regla')[0].reset();
            $('#reg-fecha-inicio').val(new Date().toISOString().split('T')[0]);
            toggleDiaEjecucion();
            $('#modal-title').text('Nueva regla de gasto recurrente');
            $('#modal-regla').modal('show');
        }

        function editar(id) {
            const r = todasLasReglas.find(x => x.id == id);
            if (!r) return;
            $('#reg-id').val(r.id);
            $('#reg-concepto').val(r.concepto);
            $('#reg-categoria').val(r.categoria_id || '');
            $('#reg-frecuencia').val(r.frecuencia);
            toggleDiaEjecucion();
            if (r.dia_ejecucion) {
                $('#reg-dias').val(String(r.dia_ejecucion));
            }
            $('#reg-monto').val(r.monto_estimado || '');
            $('#reg-fecha-inicio').val(r.fecha_inicio);
            $('#reg-observacion').val(r.observacion || '');
            $('#modal-title').text('Editar regla de gasto recurrente');
            $('#modal-regla').modal('show');
        }

        $('#form-regla').on('submit', function(e) {
            e.preventDefault();
            const id = $('#reg-id').val();
            const data = {
                accion: id ? 'editar' : 'crear',
                concepto: $('#reg-concepto').val(),
                categoria_id: $('#reg-categoria').val(),
                tipo: 'FIJO',
                frecuencia: $('#reg-frecuencia').val(),
                moneda: 'USD',
                monto_estimado: $('#reg-monto').val(),
                dia_ejecucion: $('#reg-dias').val(),
                fecha_inicio: $('#reg-fecha-inicio').val(),
                observacion: $('#reg-observacion').val()
            };
            if (id) data.id = id;

            $.ajax({
                    url: AJAX_URL,
                    type: 'POST',
                    dataType: 'json',
                    data: data
                })
                .done(function(resp) {
                    if (resp.status === 'ok') {
                        Toast.fire({
                            icon: 'success',
                            title: resp.msg
                        });
                        $('#modal-regla').modal('hide');
                        cargarTabla();
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: resp.msg
                        });
                    }
                })
                .fail(function() {
                    Toast.fire({
                        icon: 'error',
                        title: 'Error de conexión'
                    });
                });
        });

        function desactivar(id) {
            Swal.fire({
                title: 'Desactivar regla',
                text: 'La regla dejará de aplicarse hasta que se reactive.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Desactivar',
                cancelButtonText: 'Cancelar'
            }).then(r => {
                if (r.isConfirmed) {
                    $.ajax({
                            url: AJAX_URL,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                accion: 'desactivar',
                                id: id
                            }
                        })
                        .done(function(resp) {
                            Toast.fire({
                                icon: 'success',
                                title: resp.msg
                            });
                            cargarTabla();
                        });
                }
            });
        }

        function activar(id) {
            $.ajax({
                    url: AJAX_URL,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        accion: 'activar',
                        id: id
                    }
                })
                .done(function(resp) {
                    Toast.fire({
                        icon: 'success',
                        title: resp.msg
                    });
                    cargarTabla();
                });
        }

        function aplicarRecurrentes() {
            const semana = "<?= date('Y-W') ?>";
            Swal.fire({
                title: 'Aplicar recurrentes',
                html: 'Se generarán gastos para la semana <strong>' + semana + '</strong> basados en las reglas activas.<br><br>Las reglas ya aplicadas NO se duplicarán.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                cancelButtonText: 'Cancelar'
            }).then(r => {
                if (r.isConfirmed) {
                    $.ajax({
                            url: AJAX_URL,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                accion: 'aplicar',
                                semana: semana
                            }
                        })
                        .done(function(resp) {
                            if (resp.status === 'ok') {
                                Toast.fire({
                                    icon: 'success',
                                    title: resp.msg
                                });
                            } else {
                                Toast.fire({
                                    icon: 'error',
                                    title: resp.msg
                                });
                            }
                        })
                        .fail(function() {
                            Toast.fire({
                                icon: 'error',
                                title: 'Error de conexión'
                            });
                        });
                }
            });
        }

        cargarTabla();
    </script>
</body>

</html>