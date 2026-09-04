<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != 1) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Categorías de Gastos</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col { background: var(--dash-bg); min-height: 100vh; padding: 24px 28px !important; }
        .dash-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .dash-table thead th { padding: 12px 14px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; color: var(--dash-text-muted); border-bottom: 1px solid var(--dash-border); background: transparent; }
        .dash-table tbody tr { transition: background .15s ease; border-bottom: 1px solid rgba(46, 53, 62, .4); }
        .dash-table tbody tr:last-child { border-bottom: none; }
        .dash-table tbody tr:hover { background: rgba(45, 212, 160, .03); }
        .dash-table tbody td { padding: 12px 14px; color: var(--dash-text); vertical-align: middle; }
        .btn-dash-new { background: linear-gradient(135deg, #2dd4a0, #25b88a); border: none; color: #fff; font-size: 13px; font-weight: 600; padding: 8px 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; transition: all .2s ease; box-shadow: 0 3px 12px rgba(45, 212, 160, .25); cursor: pointer; }
        .btn-dash-new:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(45, 212, 160, .35); color: #fff; }
        .modal-content { background: var(--dash-card) !important; border: 1px solid var(--dash-border) !important; border-radius: 14px !important; box-shadow: 0 20px 60px rgba(0, 0, 0, .5); }
        .modal-header { border-bottom: 1px solid var(--dash-border); padding: 18px 22px 14px; }
        .modal-header .close { color: var(--dash-text-muted); opacity: .7; font-size: 24px; transition: opacity .15s ease; }
        .modal-header .close:hover { opacity: 1; color: var(--dash-text); }
        .modal-title { color: var(--dash-text); font-size: 16px; font-weight: 600; }
        .modal-body { padding: 18px 22px; }
        .modal-footer { border-top: 1px solid var(--dash-border); padding: 14px 22px 18px; }
        .modal .form-control { background: var(--dash-bg); border: 1px solid var(--dash-border); color: var(--dash-text); border-radius: 8px; padding: 9px 14px; font-size: 13px; transition: border-color .2s ease, box-shadow .2s ease; }
        .modal .form-control:focus { border-color: var(--dash-mint); box-shadow: 0 0 0 2px rgba(45, 212, 160, .12); }
        .modal .form-group { margin-bottom: 6px; }
        .modal .col-form-label { color: var(--dash-text-muted); font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: .3px; padding-top: 8px; padding-bottom: 4px; }
        .btn-dash-submit { background: linear-gradient(135deg, #2dd4a0, #25b88a); border: none; color: #fff; font-size: 13px; font-weight: 600; padding: 9px 24px; border-radius: 8px; transition: all .2s ease; box-shadow: 0 3px 12px rgba(45, 212, 160, .25); cursor: pointer; }
        .btn-dash-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(45, 212, 160, .35); }
        .btn-dash-close { background: rgba(255, 255, 255, .06); border: none; color: var(--dash-text-muted); font-size: 13px; font-weight: 600; padding: 9px 24px; border-radius: 8px; transition: all .2s ease; cursor: pointer; }
        .btn-dash-close:hover { background: rgba(255, 255, 255, .1); color: var(--dash-text-muted); }
        .badge-on { background: #32d7c0; color: #fff; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; }
        .badge-off { background: #666; color: #fff; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: 600; }
        .btn-action { width: 30px; height: 30px; border-radius: 6px; border: 1px solid var(--dash-border); background: transparent; color: var(--dash-text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s ease; padding: 0; font-size: 14px; }
        .btn-action:hover { border-color: var(--dash-mint); color: var(--dash-mint); }
        .btn-action.btn-danger:hover { border-color: #ef5a6f; color: #ef5a6f; }
        .swal2-popup { background: var(--dash-card) !important; border: 1px solid var(--dash-border) !important; border-radius: 14px !important; }
        .swal2-title { color: var(--dash-text) !important; }
        .swal2-html-container { color: var(--dash-text-muted) !important; }
        .swal2-confirm { background: linear-gradient(135deg, #2dd4a0, #25b88a) !important; border: none !important; border-radius: 8px !important; }
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
                            <h4 style="margin:0;color:var(--dash-text);">Categorías de Gastos</h4>
                            <small style="color:var(--dash-text-muted);">Gestionar las categorías para clasificar gastos</small>
                        </div>
                        <button class="btn-dash-new" onclick="abrirModal()">
                            <ion-icon name="add-outline" style="font-size:16px;"></ion-icon> Nueva categoría
                        </button>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive" style="padding:0 16px 16px;">
                            <table class="dash-table" id="tabla-categorias">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nombre</th>
                                        <th>Subcategoría de</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-categorias">
                                    <tr><td colspan="5" style="text-align:center;color:var(--dash-text-muted);">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva/Editar Categoría -->
    <div class="modal fade" id="modal-categoria" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Nueva categoría</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="form-categoria" autocomplete="off">
                    <input type="hidden" id="cat-id" value="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="col-form-label">Nombre *</label>
                            <input class="form-control" id="cat-nombre" placeholder="Nombre de la categoría" required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label class="col-form-label">Categoría padre (opcional)</label>
                            <select class="form-control" id="cat-padre">
                                <option value="">Ninguna (categoría raíz)</option>
                            </select>
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
    const Toast = Swal.mixin({ toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
    const AJAX_URL = '../../configurar/gastos_categorias_crud.php';
    let todasLasCategorias = [];

    function cargarTabla() {
        $.ajax({
            url: AJAX_URL, type: 'POST', dataType: 'json',
            data: { accion: 'listar' }
        })
        .done(function(resp) {
            if (resp.status !== 'ok') return;
            todasLasCategorias = resp.data;
            const tbody = $('#tbody-categorias');
            tbody.empty();

            if (resp.data.length === 0) {
                tbody.html('<tr><td colspan="5" style="text-align:center;color:var(--dash-text-muted);">No hay categorías registradas</td></tr>');
                return;
            }

            const raices = resp.data.filter(c => c.padre_id === null);
            const subs = resp.data.filter(c => c.padre_id !== null);
            let count = 0;

            raices.forEach(cat => {
                const badge = cat.activo == 1
                    ? '<span class="badge-on">Activa</span>'
                    : '<span class="badge-off">Inactiva</span>';
                const btnToggle = cat.activo == 1
                    ? '<button class="btn-action btn-danger" onclick="desactivar(' + cat.id + ')" title="Desactivar"><ion-icon name="close-outline"></ion-icon></button>'
                    : '<button class="btn-action" onclick="activar(' + cat.id + ')" title="Activar"><ion-icon name="checkmark-outline"></ionion-icon></button>';

                tbody.append('<tr>'
                    + '<td>' + (++count) + '</td>'
                    + '<td><strong>' + cat.nombre + '</strong></td>'
                    + '<td>—</td>'
                    + '<td>' + badge + '</td>'
                    + '<td><button class="btn-action" onclick="editar(' + cat.id + ')" title="Editar"><ion-icon name="create-outline"></ion-icon></button> ' + btnToggle + '</td>'
                    + '</tr>');

                subs.filter(s => s.padre_id == cat.id).forEach(sub => {
                    const badgeS = sub.activo == 1
                        ? '<span class="badge-on">Activa</span>'
                        : '<span class="badge-off">Inactiva</span>';
                    const btnToggleS = sub.activo == 1
                        ? '<button class="btn-action btn-danger" onclick="desactivar(' + sub.id + ')" title="Desactivar"><ion-icon name="close-outline"></ion-icon></button>'
                        : '<button class="btn-action" onclick="activar(' + sub.id + ')" title="Activar"><ion-icon name="checkmark-outline"></ion-icon></button>';

                    tbody.append('<tr style="background:rgba(45,212,160,.02);">'
                        + '<td>' + (++count) + '</td>'
                        + '<td style="padding-left:30px;">↳ ' + sub.nombre + '</td>'
                        + '<td>' + cat.nombre + '</td>'
                        + '<td>' + badgeS + '</td>'
                        + '<td><button class="btn-action" onclick="editar(' + sub.id + ')" title="Editar"><ion-icon name="create-outline"></ion-icon></button> ' + btnToggleS + '</td>'
                        + '</tr>');
                });
            });

            // Subcategorías huérfanas
            subs.filter(s => !raices.some(r => r.id == s.padre_id)).forEach(sub => {
                const badgeS = sub.activo == 1
                    ? '<span class="badge-on">Activa</span>'
                    : '<span class="badge-off">Inactiva</span>';
                tbody.append('<tr>'
                    + '<td>' + (++count) + '</td>'
                    + '<td style="padding-left:30px;">↳ ' + sub.nombre + '</td>'
                    + '<td>ID ' + sub.padre_id + '</td>'
                    + '<td>' + badgeS + '</td>'
                    + '<td><button class="btn-action" onclick="editar(' + sub.id + ')" title="Editar"><ion-icon name="create-outline"></ion-icon></button></td>'
                    + '</tr>');
            });

            cargarSelectPadres();
        });
    }

    function cargarSelectPadres() {
        const sel = $('#cat-padre');
        sel.html('<option value="">Ninguna (categoría raíz)</option>');
        todasLasCategorias.filter(c => c.padre_id === null && c.activo == 1).forEach(c => {
            sel.append('<option value="' + c.id + '">' + c.nombre + '</option>');
        });
    }

    function abrirModal() {
        $('#cat-id').val('');
        $('#cat-nombre').val('');
        $('#cat-padre').val('');
        $('#modal-title').text('Nueva categoría');
        $('#modal-categoria').modal('show');
    }

    function editar(id) {
        const cat = todasLasCategorias.find(c => c.id == id);
        if (!cat) return;
        $('#cat-id').val(cat.id);
        $('#cat-nombre').val(cat.nombre);
        $('#cat-padre').val(cat.padre_id || '');
        $('#modal-title').text('Editar categoría');
        $('#modal-categoria').modal('show');
    }

    $('#form-categoria').on('submit', function(e) {
        e.preventDefault();
        const id     = $('#cat-id').val();
        const nombre = $('#cat-nombre').val().trim();
        const padre  = $('#cat-padre').val();

        if (!nombre) { Toast.fire({ icon: 'error', title: 'Ingrese un nombre' }); return; }

        const accion = id ? 'editar' : 'crear';
        const data = { accion: accion, nombre: nombre };
        if (id) data.id = id;
        if (padre) data.padre_id = padre;

        $.ajax({ url: AJAX_URL, type: 'POST', dataType: 'json', data: data })
        .done(function(resp) {
            if (resp.status === 'ok') {
                Toast.fire({ icon: 'success', title: resp.msg });
                $('#modal-categoria').modal('hide');
                cargarTabla();
            } else {
                Toast.fire({ icon: 'error', title: resp.msg });
            }
        })
        .fail(function() { Toast.fire({ icon: 'error', title: 'Error de conexión' }); });
    });

    function desactivar(id) {
        Swal.fire({
            title: 'Desactivar categoría',
            text: 'La categoría dejará de aparecer en los selects, pero no se eliminará.',
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Desactivar', cancelButtonText: 'Cancelar'
        }).then(r => {
            if (r.isConfirmed) {
                $.ajax({ url: AJAX_URL, type: 'POST', dataType: 'json', data: { accion: 'desactivar', id: id } })
                .done(function(resp) { Toast.fire({ icon: resp.status === 'ok' ? 'success' : 'error', title: resp.msg }); cargarTabla(); });
            }
        });
    }

    function activar(id) {
        $.ajax({ url: AJAX_URL, type: 'POST', dataType: 'json', data: { accion: 'activar', id: id } })
        .done(function(resp) { Toast.fire({ icon: resp.status === 'ok' ? 'success' : 'error', title: resp.msg }); cargarTabla(); });
    }

    cargarTabla();
    </script>
</body>
</html>
