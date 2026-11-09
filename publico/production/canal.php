<?php
require_once('includes/requires.php');

$tipo_u = (int) ($_SESSION['nivel'] ?? 2);
$bss_id = (int) ($_SESSION['bss_id'] ?? 0);

if ($tipo_u !== 1 && $tipo_u !== 2) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();

$sucursales = [];
if ($tipo_u === 1) {
    $stmt = $conexion->prepare("SELECT id, nombre FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $sucursales[] = $row;
    }
    $stmt->close();
} else {
    $sid = (int) ($_SESSION['sucursal'] ?? 0);
    $stmt = $conexion->prepare("SELECT id, nombre FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
    $stmt->bind_param('ii', $sid, $bss_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $sucursales[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Res en canal / Subproductos | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
    <style>
        .right_col { background: #1a1e24; min-height: 100vh; padding: 24px 28px !important; }
        .canal-card { background: #181b22; border: 1px solid #2e353e; border-radius: 12px; padding: 18px; margin-bottom: 18px; }
        .canal-card h5 { color: #e8edf2; font-size: 15px; font-weight: 700; margin-bottom: 12px; }
        .canal-card label { color: #8892a0; font-size: 12px; }
        .canal-card .form-control, .canal-card .form-select { background: #12151a; border: 1px solid #2e353e; color: #e8edf2; }
        .canal-table { width: 100%; color: #e8edf2; font-size: 13px; }
        .canal-table th { color: #8892a0; font-weight: 600; border-bottom: 1px solid #2e353e; padding: 8px; }
        .canal-table td { border-bottom: 1px solid #232830; padding: 8px; }
        .badge-canal { background: rgba(45,212,160,.12); color: #2dd4a0; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
        .badge-pausa { background: rgba(239,90,111,.12); color: #ef5a6f; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
        .btn-mini { border: 1px solid #2e353e; background: #12151a; color: #e8edf2; border-radius: 7px; padding: 4px 9px; font-size: 12px; }
        .btn-mini:hover { border-color: #2dd4a0; color: #2dd4a0; }
        .muted { color: #8892a0; font-size: 12px; }
        .modal-content { background: #181b22; color: #e8edf2; }
        .modal-header, .modal-footer { border-color: #2e353e; }
        .select2-container--default .select2-selection--single { background:#12151a; border:1px solid #2e353e; height:38px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color:#e8edf2; line-height:36px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height:36px; }
        .select2-dropdown { background:#181b22; border:1px solid #2e353e; color:#e8edf2; }
        .select2-container--default .select2-search--dropdown .select2-search__field { background:#12151a; border:1px solid #2e353e; color:#e8edf2; }
        .select2-container--default .select2-results__option--highlighted[aria-selected] { background:#2dd4a0; color:#111; }
        .select2-container--default .select2-results__option[aria-selected=true] { background:#20252d; color:#e8edf2; }
    </style>
</head>
<body class="nav-md">
<div class="container body">
    <div class="main_container">
        <?php echo $menu; ?>
        <?php echo $topnav; ?>
        <div class="right_col">
            <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                <div>
                    <h3 style="color:#e8edf2;font-weight:700;margin:0;">Res en canal / Subproductos</h3>
                    <p class="muted mb-0">Registre la res, configure los cortes y controle la diferencia al cerrar.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn-mini" id="btn-refresh">Actualizar</button>
                    <div id="canal-estado" class="muted"></div>
                </div>
            </div>

            <div class="canal-card">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label>Sucursal</label>
                        <select id="sucursal" class="form-select">
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Res / producto padre</label>
                        <select id="padre" class="form-select"></select>
                    </div>
                    <div class="col-md-3 d-grid gap-1">
                        <button type="button" class="btn btn-success" id="btn-marcar-padre">Usar como res en canal</button>
                        <button type="button" class="btn-mini" id="btn-nueva-res">Nueva res</button>
                        <a href="nuevoProducto.php" target="_blank" class="btn-mini text-center">Ficha completa</a>
                    </div>
                </div>
                <p class="muted mt-2 mb-0" id="padre-info"></p>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    <div class="canal-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Cortes / subproductos</h5>
                            <div>
                                <button class="btn-mini" id="btn-vincular">Vincular existente</button>
                                <button class="btn-mini" id="btn-nuevo-corte">Nuevo corte</button>
                            </div>
                        </div>
                        <div class="table-responsive mt-2">
                            <table class="canal-table">
                                <thead><tr><th>Producto</th><th>Rend.</th><th>Stock</th><th>Venta</th><th></th></tr></thead>
                                <tbody id="cortes-body"><tr><td colspan="5" class="muted">Seleccione una res.</td></tr></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="canal-card">
                        <h5>Registrar res en canal</h5>
                        <form id="form-abrir" class="row g-2">
                            <div class="col-md-3"><label>Kilaje total</label><input type="number" step="0.01" min="0.01" name="kg" class="form-control" required></div>
                            <div class="col-md-3"><label>Costo total</label><input type="number" step="0.01" min="0" name="costo_total" class="form-control" value="0"></div>
                            <div class="col-md-3"><label>Proveedor</label><input type="text" name="proveedor" class="form-control"></div>
                            <div class="col-md-3"><label>Notas</label><input type="text" name="notas" class="form-control"></div>
                            <div class="col-md-6 d-flex align-items-center gap-3 mt-2">
                                <label class="mb-0"><input type="checkbox" name="sumar_stock" checked> Sumar al stock</label>
                                <label class="mb-0"><input type="checkbox" name="reactivar" checked> Poner cortes en venta</label>
                            </div>
                            <div class="col-md-6 text-end mt-2"><button class="btn btn-success" type="submit">Registrar res</button></div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="canal-card">
                        <h5>Lotes de res</h5>
                        <div class="table-responsive">
                            <table class="canal-table">
                                <thead><tr><th>#</th><th>Inicial</th><th>Restante</th><th>Estado</th><th></th></tr></thead>
                                <tbody id="lotes-body"><tr><td colspan="5" class="muted">Sin lotes.</td></tr></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="canal-card">
                        <h5>Resultado del lote</h5>
                        <div id="reporte" class="muted">Seleccione un lote para ver el reporte.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal nuevo corte -->
<div class="modal fade" id="modalCorte" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo corte / subproducto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="form-corte">
                <div class="modal-body row g-2">
                    <div class="col-md-6"><label>Nombre</label><input type="text" name="nombre" class="form-control" required></div>
                    <div class="col-md-3"><label>Costo por kg</label><input type="number" step="0.01" min="0" name="precio" class="form-control" required></div>
                    <div class="col-md-3"><label>% ganancia</label><input type="number" step="0.01" name="porcentaje" class="form-control" value="30"></div>
                    <div class="col-md-3"><label>Rendimiento (kg por kg)</label><input type="number" step="0.0001" min="0" name="rendimiento" class="form-control" placeholder="0.40"></div>
                    <div class="col-md-3"><label>Origen</label><select name="origen" class="form-select"><option value="v">Venezolano</option><option value="c">Colombiano</option><option value="m">Mixto</option></select></div>
                    <div class="col-md-6"><label>Categoría</label><select name="categoria" id="corte-categoria" class="form-select"><option value="">Sin categoría</option></select></div>
                    <div class="col-md-6"><label>Proveedor</label><input type="text" name="proveedor" class="form-control"></div>
                    <div class="col-md-6"><label>Código de barras (opcional)</label><input type="text" name="codigo_barras" class="form-control"></div>
                    <div class="col-12"><hr style="border-color:#2e353e"><label><input type="checkbox" name="crear_mayor" id="chk-mayor"> Crear también variante al mayor</label></div>
                    <div class="col-md-4"><label>Precio al mayor (costo)</label><input type="number" step="0.01" min="0" name="mayor_precio" class="form-control"></div>
                    <div class="col-md-4"><label>% ganancia al mayor</label><input type="number" step="0.01" name="mayor_porcentaje" class="form-control" value="15"></div>
                    <div class="col-md-4"><label>Kg por empaque al mayor</label><input type="number" step="0.01" min="1" name="mayor_empaque" class="form-control" value="1"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear corte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal nueva res -->
<div class="modal fade" id="modalRes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva res en canal</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="form-res">
                <div class="modal-body row g-2">
                    <div class="col-md-6"><label>Nombre</label><input type="text" name="nombre" class="form-control" placeholder="Ej. Res en canal" required></div>
                    <div class="col-md-3"><label>Costo por kg</label><input type="number" step="0.01" min="0" name="precio" class="form-control" required></div>
                    <div class="col-md-3"><label>% ganancia</label><input type="number" step="0.01" name="porcentaje" class="form-control" value="30"></div>
                    <div class="col-md-6"><label>Categoría</label><select name="categoria" id="res-categoria" class="form-select"><option value="">Sin categoría</option></select></div>
                    <div class="col-md-3"><label>Origen</label><select name="origen" class="form-select"><option value="v">Venezolano</option><option value="c">Colombiano</option><option value="m">Mixto</option></select></div>
                    <div class="col-md-3"><label>Código de barras</label><input type="text" name="codigo_barras" class="form-control"></div>
                    <div class="col-md-12"><label>Proveedor</label><input type="text" name="proveedor" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear res</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal vincular -->
<div class="modal fade" id="modalVincular" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Vincular producto existente</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
            <form id="form-vincular">
                <div class="modal-body">
                    <label>Producto</label>
                    <select name="producto_id" id="vincular-producto" class="form-select" required></select>
                    <label class="mt-2">Rendimiento (kg por kg)</label>
                    <input type="number" step="0.0001" min="0" name="rendimiento" class="form-control" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Vincular</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const API = 'db/canal_api.php';
    let productos = [];
    let categorias = [];
    let padreId = 0;
    let padreModo = 'NORMAL';
    let loteSeleccionado = 0;

    const $ = (id) => document.getElementById(id);
    const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const num = (v) => Number(v || 0).toLocaleString('es-VE', {maximumFractionDigits: 3});
    const estado = (txt, error) => { $('canal-estado').textContent = txt || ''; $('canal-estado').style.color = error ? '#ef5a6f' : '#2dd4a0'; };

    function refreshSelect2() {
        if (!window.jQuery || !jQuery.fn.select2) return;
        const init = (selector, opts) => {
            const el = jQuery(selector);
            if (el.data('select2')) el.select2('destroy');
            el.select2(opts);
        };
        init('#padre', {placeholder: 'Buscar res...', width: '100%', allowClear: true});
        init('#vincular-producto', {placeholder: 'Buscar producto...', width: '100%', allowClear: true, dropdownParent: jQuery('#modalVincular')});
        init('#corte-categoria', {placeholder: 'Sin categoría', width: '100%', allowClear: true, dropdownParent: jQuery('#modalCorte')});
        init('#res-categoria', {placeholder: 'Sin categoría', width: '100%', allowClear: true, dropdownParent: jQuery('#modalRes')});
    }

    function setPadre(valor) {
        $('padre').value = String(valor || '');
        if (window.jQuery && jQuery('#padre').data('select2')) {
            jQuery('#padre').val(String(valor || '')).trigger('change.select2');
        }
    }

    function actualizarInfoPadre() {
        const p = productos.find((x) => Number(x.id) === padreId);
        padreModo = p ? p.modo_control : 'NORMAL';
        if (p) {
            const lote = p.kg_lote != null ? 'Lote abierto: ' + num(p.kg_lote) + ' kg' : 'Sin lote abierto';
            $('padre-info').textContent = 'Modo: ' + p.modo_control + ' · Unidad: ' + p.unidad
                + ' · Stock: ' + num(p.stock) + ' ' + p.unidad
                + ' · ' + lote + ' · Cortes: ' + num(p.cortes);
        } else {
            $('padre-info').textContent = '';
        }
    }

    async function api(accion, data) {
        const fd = new FormData();
        fd.append('accion', accion);
        fd.append('sucursal', $('sucursal').value);
        Object.keys(data || {}).forEach((k) => fd.append(k, data[k]));
        const res = await fetch(API, {method: 'POST', body: fd});
        const json = await res.json();
        if (!json.ok) throw new Error(json.message || 'Error');
        return json;
    }

    async function cargarProductos() {
        const padreActual = $('padre').value;
        $('padre').innerHTML = '<option value="">Cargando productos...</option>';
        const json = await api('productos');
        productos = json.data || [];
        const sel = $('padre');
        sel.innerHTML = '<option value="">-- Seleccione la res --</option>';
        productos.forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.nombre + (p.modo_control === 'CANAL' ? ' (res en canal)' : '');
            sel.appendChild(opt);
        });
        const vs = $('vincular-producto');
        vs.innerHTML = '<option value="">-- Seleccione --</option>';
        productos.forEach((p) => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.nombre;
            vs.appendChild(opt);
        });
        refreshSelect2();
        if (padreActual) {
            setPadre(padreActual);
        } else if (!padreId) {
            const primera = productos.find((p) => p.modo_control === 'CANAL');
            if (primera) {
                setPadre(primera.id);
                $('padre').dispatchEvent(new Event('change'));
            }
        }
        actualizarInfoPadre();
    }

    async function cargarCategorias() {
        const json = await api('categorias');
        categorias = json.data || [];
        ['corte-categoria', 'res-categoria'].forEach((id) => {
            const sel = $(id);
            sel.innerHTML = '<option value="">Sin categoría</option>';
            categorias.forEach((c) => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.nombre;
                sel.appendChild(opt);
            });
        });
        if (window.jQuery && jQuery.fn.select2) {
            const initCat = (selector, modal) => {
                const el = jQuery(selector);
                if (el.data('select2')) el.select2('destroy');
                el.select2({placeholder: 'Sin categoría', width: '100%', allowClear: true, dropdownParent: jQuery(modal)});
            };
            initCat('#corte-categoria', '#modalCorte');
            initCat('#res-categoria', '#modalRes');
        }
    }

    async function cargarCortes() {
        if (!padreId) {
            $('cortes-body').innerHTML = '<tr><td colspan="5" class="muted">Seleccione una res.</td></tr>';
            return;
        }
        $('cortes-body').innerHTML = '<tr><td colspan="5" class="muted">Cargando cortes...</td></tr>';
        const json = await api('cortes', {padre_id: padreId});
        const filas = json.data || [];
        if (!filas.length) {
            $('cortes-body').innerHTML = '<tr><td colspan="5" class="muted">Sin cortes. Cree o vincule uno.</td></tr>';
            return;
        }
        $('cortes-body').innerHTML = filas.map((c) => `
            <tr>
                <td>${esc(c.nombre)}</td>
                <td>${num(c.rendimiento)}${Number(c.rendimiento) > 0 ? ' x kg' : ''}</td>
                <td>${padreModo === 'CANAL' ? 'Según res' : num(c.stock) + ' ' + esc(c.unidad || 'KG')}</td>
                <td>${Number(c.visible_venta) === 1 ? '<span class="badge-canal">En venta</span>' : '<span class="badge-pausa">Pausado</span>'}</td>
                <td class="text-end">
                    <button class="btn-mini" data-visible="${c.id}" data-estado="${Number(c.visible_venta) === 1 ? 0 : 1}">${Number(c.visible_venta) === 1 ? 'Pausar' : 'Activar'}</button>
                    <button class="btn-mini" data-desvincular="${c.id}">Quitar</button>
                </td>
            </tr>`).join('');
    }

    async function cargarLotes() {
        if (!padreId) {
            $('lotes-body').innerHTML = '<tr><td colspan="5" class="muted">Sin lotes.</td></tr>';
            $('reporte').innerHTML = '<span class="muted">Seleccione una res.</span>';
            return;
        }
        $('lotes-body').innerHTML = '<tr><td colspan="5" class="muted">Cargando lotes...</td></tr>';
        const json = await api('lotes', {padre_id: padreId});
        const filas = json.data || [];
        if (!filas.length) {
            $('lotes-body').innerHTML = '<tr><td colspan="5" class="muted">Sin lotes.</td></tr>';
            $('reporte').innerHTML = '<span class="muted">Registre una res para ver el resultado.</span>';
            return;
        }
        $('lotes-body').innerHTML = filas.map((l) => {
            const abierto = l.estado === 'ABIERTO';
            const pendiente = Number(l.diferencia_pendiente) > 0 && Number(l.conciliado) === 0;
            return `<tr>
                <td>#${l.id}</td>
                <td>${num(l.kg_inicial)}</td>
                <td>${num(l.kg_restante)}</td>
                <td>${abierto ? '<span class="badge-canal">Abierto</span>' : (pendiente ? '<span class="badge-pausa">Pendiente</span>' : 'Cerrado')}</td>
                <td class="text-end">
                    <button class="btn-mini" data-reporte="${l.id}">Ver</button>
                    ${abierto ? `<button class="btn-mini" data-cerrar="${l.id}" data-restante="${l.kg_restante}">Cerrar</button>` : ''}
                </td>
            </tr>`;
        }).join('');

        const objetivo = filas.find((l) => Number(l.id) === Number(loteSeleccionado))
            || filas.find((l) => l.estado === 'ABIERTO')
            || filas[0];
        if (objetivo) {
            cargarReporte(objetivo.id).catch((err) => estado(err.message, true));
        }
    }

    async function cargarReporte(loteId) {
        loteSeleccionado = loteId;
        const json = await api('reporte', {lote_id: loteId});
        const l = json.lote || {};
        const cortes = json.cortes || [];
        const apertura = l.fecha_apertura ? String(l.fecha_apertura).replace(' ', ' · ') : '';
        const cierre = l.fecha_cierre ? String(l.fecha_cierre).replace(' ', ' · ') : '';
        let html = `<div class="muted mb-2" style="line-height:1.7;">
            <div><b style="color:#e8edf2;">Lote #${l.id}</b> · ${esc(l.padre_nombre || '')}</div>
            <div>Inicial <b>${num(l.kg_inicial)} kg</b> · Restante <b>${num(l.kg_restante)} kg</b> · Diferencia <b>${num(l.diferencia_pendiente)} kg</b></div>
            <div>Proveedor: ${esc(l.proveedor || 'Sin proveedor')} · Costo: $${num(l.costo_total)}</div>
            <div>Apertura: ${esc(apertura)}${cierre ? ' · Cierre: ' + esc(cierre) : ''}</div>
        </div>`;
        html += '<table class="canal-table"><thead><tr><th>Corte</th><th>Vendido</th><th>Esperado</th><th>Dif.</th></tr></thead><tbody>';
        if (!cortes.length) {
            html += '<tr><td colspan="4" class="muted">Sin cortes registrados.</td></tr>';
        }
        cortes.forEach((c) => {
            const dif = Number(c.diferencia);
            html += `<tr><td>${esc(c.nombre)}</td><td>${num(c.vendido)}</td><td>${num(c.esperado)}</td><td style="color:${dif > 0.001 ? '#ef5a6f' : '#2dd4a0'}">${num(dif)}</td></tr>`;
        });
        html += '</tbody></table>';
        $('reporte').innerHTML = html;
    }

    async function recargar() {
        await cargarCortes();
        await cargarLotes();
    }

    $('btn-refresh').addEventListener('click', async () => {
        try {
            await cargarProductos();
            await recargar();
        } catch (e) {
            estado(e.message, true);
        }
    });
    $('sucursal').addEventListener('change', async () => {
        try {
            await cargarProductos();
            await recargar();
        } catch (e) {
            estado(e.message, true);
        }
    });
    $('padre').addEventListener('change', () => {
        padreId = Number($('padre').value) || 0;
        actualizarInfoPadre();
        recargar().catch((e) => estado(e.message, true));
    });

    $('btn-marcar-padre').addEventListener('click', async () => {
        if (!padreId) return estado('Seleccione un producto.', true);
        try {
            await api('marcar_padre', {producto_id: padreId});
            await cargarProductos();
            setPadre(padreId);
            $('padre').dispatchEvent(new Event('change'));
            estado('Producto configurado como res en canal.');
        } catch (e) { estado(e.message, true); }
    });

    $('btn-nuevo-corte').addEventListener('click', () => {
        if (!padreId) return estado('Seleccione una res.', true);
        $('form-corte').reset();
        $('chk-mayor').checked = false;
        if (window.jQuery && jQuery('#corte-categoria').data('select2')) {
            jQuery('#corte-categoria').val('').trigger('change.select2');
        }
        jQuery('#modalCorte').modal('show');
    });

    $('btn-vincular').addEventListener('click', () => {
        if (!padreId) return estado('Seleccione una res.', true);
        $('form-vincular').reset();
        if (window.jQuery && jQuery('#vincular-producto').data('select2')) {
            jQuery('#vincular-producto').val('').trigger('change.select2');
        }
        jQuery('#modalVincular').modal('show');
    });

    $('btn-nueva-res').addEventListener('click', () => {
        $('form-res').reset();
        if (window.jQuery && jQuery('#res-categoria').data('select2')) {
            jQuery('#res-categoria').val('').trigger('change.select2');
        }
        jQuery('#modalRes').modal('show');
    });

    $('form-res').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const data = {};
        fd.forEach((v, k) => { data[k] = v; });
        try {
            const json = await api('crear_padre', data);
            jQuery('#modalRes').modal('hide');
            await cargarProductos();
            setPadre(json.id);
            $('padre').dispatchEvent(new Event('change'));
            estado(json.message);
        } catch (err) { estado(err.message, true); }
    });

    $('form-corte').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const data = {padre_id: padreId};
        fd.forEach((v, k) => { data[k] = (k === 'crear_mayor' && v === 'on') ? 1 : v; });
        if (!fd.get('crear_mayor')) data.crear_mayor = 0;
        try {
            await api('crear_corte', data);
            jQuery('#modalCorte').modal('hide');
            const padreActual = padreId;
            await cargarProductos();
            setPadre(padreActual);
            await recargar();
            estado('Corte creado.');
        } catch (err) { estado(err.message, true); }
    });

    $('form-vincular').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const data = {padre_id: padreId};
        fd.forEach((v, k) => { data[k] = v; });
        try {
            await api('vincular_corte', data);
            jQuery('#modalVincular').modal('hide');
            await recargar();
            estado('Producto vinculado.');
        } catch (err) { estado(err.message, true); }
    });

    $('form-abrir').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!padreId) return estado('Seleccione una res.', true);
        const fd = new FormData(e.target);
        const data = {padre_id: padreId, sumar_stock: fd.get('sumar_stock') ? 1 : 0, reactivar: fd.get('reactivar') ? 1 : 0};
        ['kg', 'costo_total', 'proveedor', 'notas'].forEach((k) => data[k] = fd.get(k) || '');
        try {
            const json = await api('abrir', data);
            e.target.reset();
            e.target.querySelector('[name=sumar_stock]').checked = true;
            e.target.querySelector('[name=reactivar]').checked = true;
            loteSeleccionado = 0;
            await cargarProductos();
            setPadre(padreId);
            await recargar();
            estado(json.message);
        } catch (err) { estado(err.message, true); }
    });

    $('cortes-body').addEventListener('click', async (e) => {
        const visible = e.target.closest('[data-visible]');
        const quitar = e.target.closest('[data-desvincular]');
        if (visible) {
            try {
                await fetch('../../configurar/producto_visible.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({producto_id: visible.dataset.visible, sucursal: $('sucursal').value, visible: visible.dataset.estado})
                }).then((r) => r.json()).then((j) => { if (!j.success) throw new Error(j.message); });
                await cargarCortes();
            } catch (err) { estado(err.message, true); }
        }
        if (quitar) {
            try {
                await api('desvincular_corte', {producto_id: quitar.dataset.desvincular});
                await recargar();
                estado('Corte desvinculado.');
            } catch (err) { estado(err.message, true); }
        }
    });

    $('lotes-body').addEventListener('click', async (e) => {
        const cerrar = e.target.closest('[data-cerrar]');
        const ver = e.target.closest('[data-reporte]');
        if (ver) {
            cargarReporte(ver.dataset.reporte).catch((err) => estado(err.message, true));
            return;
        }
        if (!cerrar) return;
        const loteId = Number(cerrar.dataset.cerrar);
        const restante = Number(cerrar.dataset.restante || 0);
        const r = await Swal.fire({
            title: 'Cerrar res',
            html: `<p>Quedan <strong>${num(restante)} kg</strong> en la res.</p>
                   <p style="font-size:13px;color:#666">Confirme el monto actual si el stock es correcto, o vaya al módulo de ajuste para registrar la diferencia. En el módulo de ajuste seleccione la <b>res</b>, no los cortes.</p>`,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Confirmar monto actual',
            denyButtonText: 'Ir a ajuste de stock',
            cancelButtonText: 'Cancelar'
        });
        if (r.isConfirmed) {
            try {
                const json = await api('cerrar', {lote_id: loteId, confirmar: 1});
                await cargarProductos();
                setPadre(padreId);
                await recargar();
                estado(json.message);
            } catch (err) { estado(err.message, true); }
        } else if (r.isDenied) {
            window.open('ajustes_stock.php', '_blank');
        }
    });

    cargarCategorias().catch((e) => estado(e.message, true));
    cargarProductos().catch((e) => estado(e.message, true));
})();
</script>
</body>
</html>
