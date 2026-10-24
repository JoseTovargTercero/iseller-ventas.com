<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_categorizar_reglas.php');

$bss_id = (int) $bss_id;

// Categorías disponibles para este negocio (globales + propias)
$categorias = [];
$catPorSlug = [];
$res = $conexion->query("SELECT id, nombre, slug, grupo FROM categorias_producto
                         WHERE bss_id = 0 OR bss_id = $bss_id
                         ORDER BY COALESCE(grupo, 'zzz'), nombre");
while ($r = $res->fetch_assoc()) {
    $categorias[] = $r;
    if ($r['slug'] !== '') {
        $catPorSlug[$r['slug']] = $r;
    }
}
$catOtros = $catPorSlug['otros'] ?? null;
$nombresCat = [];
foreach ($categorias as $c) $nombresCat[(int) $c['id']] = $c['nombre'];

// Nombre del negocio (para dejar claro sobre cuál se está guardando)
$negocioNombre = '';
$stmtNeg = $conexion->prepare("SELECT nombre FROM negocio WHERE id = ?");
if ($stmtNeg) {
    $stmtNeg->bind_param('i', $bss_id);
    $stmtNeg->execute();
    $negocioNombre = (string) ($stmtNeg->get_result()->fetch_row()[0] ?? '');
}
$baseActual = (string) ($conexion->query("SELECT DATABASE()")->fetch_row()[0] ?? '');

// Productos + sugerencia calculada por reglas de nombre
$productos = [];
$res = $conexion->query("SELECT id, nombre, categoria FROM productos WHERE bss_id = $bss_id ORDER BY nombre");
while ($r = $res->fetch_assoc()) {
    $slug = cat_sugerir($r['nombre']);
    $sug = $slug !== null ? ($catPorSlug[$slug] ?? null) : null;
    $r['sugerida'] = $sug !== null ? (int) $sug['id'] : ($catOtros !== null ? (int) $catOtros['id'] : 0);
    $r['coincide'] = $sug !== null;
    $productos[] = $r;
}

// Exportación JSON de las sugerencias
if (isset($_GET['export'])) {
    $salida = [];
    foreach ($productos as $p) {
        $salida[] = [
            'id' => (int) $p['id'],
            'nombre' => $p['nombre'],
            'categoria_sugerida' => [
                'id' => $p['sugerida'],
                'nombre' => $nombresCat[$p['sugerida']] ?? '',
            ],
            'sin_coincidencia' => !$p['coincide'],
            'categoria_actual' => $p['categoria'] !== null ? (int) $p['categoria'] : null,
        ];
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="productos_categorias_bss' . $bss_id . '.json"');
    echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$q = trim($_GET['q'] ?? '');
$filtro = $_GET['filtro'] ?? 'todas';
$pagina = max(1, (int) ($_GET['p'] ?? 1));
$porPagina = 50;

$total = count($productos);
$pendientes = 0;
$dudosas = 0;
foreach ($productos as $p) {
    if ($p['categoria'] === null) $pendientes++;
    if (!$p['coincide']) $dudosas++;
}

$filtrados = array_values(array_filter($productos, function ($p) use ($q, $filtro) {
    if ($filtro === 'pendientes' && $p['categoria'] !== null) return false;
    if ($filtro === 'dudosas' && $p['coincide']) return false;
    if ($q !== '' && stripos($p['nombre'], $q) === false) return false;
    return true;
}));

$paginas = max(1, (int) ceil(count($filtrados) / $porPagina));
$pagina = min($pagina, $paginas);
$visibles = array_slice($filtrados, ($pagina - 1) * $porPagina, $porPagina);

function urlPagina(int $p, string $q, string $filtro): string
{
    return '?' . http_build_query(['q' => $q, 'filtro' => $filtro, 'p' => $p]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Categorizar productos · Negocio #<?= $bss_id ?></title>
<style>
    :root { --borde:#e2e4e9; --texto:#1f2430; --suave:#667085; --ok:#12805c; --alerta:#b54708; --acento:#1f4fd8; }
    * { box-sizing:border-box; }
    body { margin:0; font:14px/1.45 system-ui,Segoe UI,Roboto,Arial,sans-serif; color:var(--texto); background:#f6f7f9; }
    header { background:#fff; border-bottom:1px solid var(--borde); padding:16px 24px; }
    h1 { font-size:18px; margin:0 0 4px; }
    .sub { color:var(--suave); font-size:13px; }
    main { padding:20px 24px 60px; max-width:1400px; margin:0 auto; }
    .barra { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:14px; }
    .chips { display:flex; gap:8px; flex-wrap:wrap; margin-right:auto; }
    .chip { background:#fff; border:1px solid var(--borde); border-radius:999px; padding:4px 12px; font-size:12px; color:var(--suave); }
    .chip b { color:var(--texto); }
    input[type=search], select { padding:7px 10px; border:1px solid var(--borde); border-radius:8px; background:#fff; font:inherit; color:inherit; }
    input[type=search] { min-width:240px; }
    button { font:inherit; border:1px solid var(--borde); background:#fff; border-radius:8px; padding:7px 12px; cursor:pointer; }
    button:hover { border-color:#c5cad5; }
    button.primary { background:var(--acento); border-color:var(--acento); color:#fff; }
    button.primary:hover { filter:brightness(1.05); }
    button:disabled { opacity:.55; cursor:default; }
    .tabla-wrap { background:#fff; border:1px solid var(--borde); border-radius:12px; overflow:auto; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:9px 12px; border-bottom:1px solid var(--borde); text-align:left; vertical-align:middle; }
    th { font-size:12px; text-transform:uppercase; letter-spacing:.03em; color:var(--suave); background:#fafbfc; position:sticky; top:0; z-index:1; }
    tr:last-child td { border-bottom:0; }
    td.producto { min-width:260px; }
    td.cat { min-width:260px; }
    td.cat select { width:100%; max-width:320px; }
    .actual { color:var(--suave); }
    .dudosa { color:var(--alerta); font-size:12px; display:inline-block; margin-left:6px; }
    .estado { font-size:12px; color:var(--suave); min-width:70px; display:inline-block; }
    tr.guardado .estado { color:var(--ok); font-weight:600; }
    .estado.error { color:#b42318; font-weight:600; }
    .pager { display:flex; gap:10px; align-items:center; justify-content:center; margin-top:14px; font-size:13px; }
    .pager a { color:var(--acento); text-decoration:none; padding:5px 9px; border:1px solid var(--borde); border-radius:8px; background:#fff; }
    .vacio { padding:30px; text-align:center; color:var(--suave); }
    @media (max-width:760px) { td.producto { min-width:180px; } td.cat select { max-width:220px; } }
</style>
</head>
<body>
<header>
    <h1>Categorizar productos · Negocio #<?= $bss_id ?><?= $negocioNombre !== '' ? ' · ' . htmlspecialchars($negocioNombre, ENT_QUOTES) : '' ?></h1>
    <div class="sub">Los cambios se guardan en la columna <code>productos.categoria</code> de la base <b><?= htmlspecialchars($baseActual, ENT_QUOTES) ?></b> para este negocio. Revisa la sugerencia, cámbiala si hace falta y pulsa <b>Aceptar</b>.</div>
</header>
<main>
    <div class="barra">
        <div class="chips">
            <span class="chip">Total <b><?= $total ?></b></span>
            <span class="chip">Sin categoría <b><?= $pendientes ?></b></span>
            <span class="chip">Sin coincidencia <b><?= $dudosas ?></b></span>
            <span class="chip">Filtrados <b><?= count($filtrados) ?></b></span>
        </div>
        <button id="btnTodas" class="primary" <?= $pendientes ? '' : 'disabled' ?>>Aplicar todas las sugerencias (<?= $pendientes ?>)</button>
        <a class="chip" href="?export=1" style="text-decoration:none">Descargar JSON</a>
    </div>

    <form class="barra" method="get">
        <input type="search" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES) ?>" placeholder="Buscar producto…">
        <select name="filtro" onchange="this.form.submit()">
            <option value="todas" <?= $filtro === 'todas' ? 'selected' : '' ?>>Todas</option>
            <option value="pendientes" <?= $filtro === 'pendientes' ? 'selected' : '' ?>>Sin categoría</option>
            <option value="dudosas" <?= $filtro === 'dudosas' ? 'selected' : '' ?>>Sin coincidencia</option>
        </select>
        <button type="submit">Filtrar</button>
        <?php if ($q !== '' || $filtro !== 'todas'): ?>
            <a class="chip" href="?" style="text-decoration:none">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="tabla-wrap">
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría actual</th>
                    <th>Categoría a guardar</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="filas">
            <?php foreach ($visibles as $p): ?>
                <tr data-id="<?= (int) $p['id'] ?>">
                    <td class="producto"><?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?></td>
                    <td class="actual" data-actual="<?= $p['categoria'] !== null ? (int) $p['categoria'] : '' ?>">
                        <?= $p['categoria'] !== null ? htmlspecialchars($nombresCat[(int) $p['categoria']] ?? '#' . (int) $p['categoria'], ENT_QUOTES) : '—' ?>
                    </td>
                    <td class="cat">
                        <select class="sel" data-sug="<?= (int) $p['sugerida'] ?>"><option value="">— Elegir —</option></select>
                        <?php if (!$p['coincide']): ?><span class="dudosa" title="Ninguna regla coincidió: se propone Otros">sin coincidencia</span><?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn-ok">Aceptar</button>
                        <span class="estado"></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$visibles): ?>
                <tr><td colspan="4" class="vacio">Sin productos para este filtro.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paginas > 1): ?>
    <div class="pager">
        <?php if ($pagina > 1): ?><a href="<?= htmlspecialchars(urlPagina($pagina - 1, $q, $filtro)) ?>">← Anterior</a><?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $paginas ?></span>
        <?php if ($pagina < $paginas): ?><a href="<?= htmlspecialchars(urlPagina($pagina + 1, $q, $filtro)) ?>">Siguiente →</a><?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<select id="tplCats" hidden>
    <?php $grupoActual = '###'; foreach ($categorias as $c): ?>
        <?php if ($c['grupo'] !== $grupoActual): ?>
            <?= $grupoActual !== '###' ? '</optgroup>' : '' ?>
            <optgroup label="<?= htmlspecialchars($c['grupo'] ?? 'Sin grupo', ENT_QUOTES) ?>">
            <?php $grupoActual = $c['grupo']; ?>
        <?php endif; ?>
        <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?></option>
    <?php endforeach; ?>
    <?= $grupoActual !== '###' ? '</optgroup>' : '' ?>
</select>

<script>
(() => {
    const tpl = document.getElementById('tplCats');
    document.querySelectorAll('select.sel').forEach(sel => {
        sel.innerHTML = tpl.innerHTML;
        sel.value = sel.dataset.sug;
    });

    const guardar = async (payload) => {
        const r = await fetch('categorizar_productos_guardar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        return r.json();
    };

    document.querySelectorAll('.btn-ok').forEach(btn => {
        btn.addEventListener('click', async () => {
            const fila = btn.closest('tr');
            const sel = fila.querySelector('select.sel');
            if (!sel.value) { alert('Elige una categoría.'); return; }
            btn.disabled = true;
            const estado = fila.querySelector('.estado');
            estado.className = 'estado';
            estado.textContent = 'Guardando…';
            try {
                const d = await guardar({ id: Number(fila.dataset.id), categoria: Number(sel.value) });
                if (!d.ok) throw new Error(d.message || 'Error al guardar');
                fila.querySelector('.actual').textContent = d.nombre;
                fila.classList.add('guardado');
                estado.textContent = '✓ Guardado';
            } catch (e) {
                fila.classList.remove('guardado');
                estado.className = 'estado error';
                estado.textContent = '✗ ' + e.message;
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.getElementById('btnTodas').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const total = btn.textContent.match(/\d+/)?.[0] || '';
        if (!confirm(`Se guardará la categoría sugerida en ${total} productos sin categoría. ¿Continuar?`)) return;
        btn.disabled = true;
        btn.textContent = 'Aplicando…';
        try {
            const d = await guardar({ todas: true });
            if (!d.ok) throw new Error(d.message || 'Error al aplicar');
            if (d.sin_categoria > 0) alert('Quedaron ' + d.sin_categoria + ' productos sin categoría.');
            location.reload();
        } catch (err) {
            alert(err.message);
            btn.disabled = false;
        }
    });
})();
</script>
</body>
</html>
