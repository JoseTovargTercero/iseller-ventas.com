<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

$bss_id      = (int) $_SESSION['bss_id'];
$id_sucursal = (int) $_SESSION['sucursal'];
$nivel       = (int) $_SESSION['nivel'];

if ($nivel == 1 && isset($_POST['id_sucursal'])) {
    $id_sucursal = (int) $_POST['id_sucursal'];
}

if (!$bss_id) {
    echo 'Sesión no válida';
    exit;
}

$semana = trim($_POST['semana'] ?? '');

$fecha_desde   = trim($_POST['fecha_desde'] ?? '');
$fecha_hasta   = trim($_POST['fecha_hasta'] ?? '');
$categoria_id  = intval($_POST['categoria_id'] ?? 0);
$tipo          = trim($_POST['tipo'] ?? '');
$frecuencia    = trim($_POST['frecuencia'] ?? '');
$estado        = trim($_POST['estado'] ?? '');

$bss_id_esc   = $conexion->real_escape_string($bss_id);
$sucursal_esc = $conexion->real_escape_string($id_sucursal);

$where = "g.bss_id = $bss_id_esc";

if ($id_sucursal > 0) {
    $where .= " AND g.id_sucursal = $sucursal_esc";
}

if (!empty($fecha_desde) && !empty($fecha_hasta)) {
    $fd = $conexion->real_escape_string($fecha_desde);
    $fh = $conexion->real_escape_string($fecha_hasta);
    $where .= " AND g.fecha BETWEEN '$fd' AND '$fh'";
} elseif (!empty($semana)) {
    $semana_esc = $conexion->real_escape_string($semana);
    $where .= " AND g.semana = '$semana_esc'";
}

if ($categoria_id > 0) {
    $cat_esc = $conexion->real_escape_string($categoria_id);
    $where .= " AND g.categoria_id = $cat_esc";
}

if (!empty($tipo) && in_array($tipo, ['FIJO', 'VARIABLE'])) {
    $tipo_esc = $conexion->real_escape_string($tipo);
    $where .= " AND g.tipo = '$tipo_esc'";
}

if (!empty($frecuencia) && in_array($frecuencia, ['UNICO','DIARIO','SEMANAL','QUINCENAL','MENSUAL'])) {
    $frec_esc = $conexion->real_escape_string($frecuencia);
    $where .= " AND g.frecuencia = '$frec_esc'";
}

if (!empty($estado) && in_array($estado, ['ACTIVO', 'ANULADO'])) {
    $est_esc = $conexion->real_escape_string($estado);
    $where .= " AND g.estado = '$est_esc'";
}

$sql = "SELECT g.id, g.codigo, g.fecha, g.concepto, g.tipo, g.frecuencia,
               g.moneda, g.monto, g.monto_usd, g.estado, g.observacion,
               gc.nombre AS categoria_nombre, u.nombre AS usuario_nombre
        FROM gastos g
        LEFT JOIN gastos_categorias gc ON g.categoria_id = gc.id
        LEFT JOIN usuarios u ON g.usuario_id = u.id
        WHERE $where
        ORDER BY g.fecha DESC, g.id DESC";

$result = $conexion->query($sql);
if (!$result) {
    echo 'Error al consultar gastos';
    exit;
}

if ($result->num_rows === 0) {
    echo '<div style="text-align:center;padding:40px 0;color:var(--dash-text-muted);font-size:13px;">No se encontraron gastos para los filtros aplicados</div>';
    exit;
}

$tipo_labels = ['FIJO' => 'Fijo', 'VARIABLE' => 'Variable'];
$count = 0;

echo '<table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
<thead>
<tr class="headings">
<th style="padding:10px !important; width:3%">#</th>
<th style="padding:10px !important; width:7%">Código</th>
<th style="padding:10px !important; width:7%">Fecha</th>
<th style="padding:10px !important; width:15%">Concepto</th>
<th style="padding:10px !important; width:10%">Categoría</th>
<th style="padding:10px !important; width:6%">Tipo</th>
<th style="padding:10px !important; width:8%">Frecuencia</th>
<th style="padding:10px !important; width:8%">Monto</th>
<th style="padding:10px !important; width:6%">Estado</th>
<th style="padding:10px !important; width:6%">Acciones</th>
</tr>
</thead>
<tbody>';

while ($fila = $result->fetch_assoc()) {
    $count++;
    $monto_formateado = number_format($fila['monto'], 2, ',', '.');

    $badge_tipo = $fila['tipo'] === 'FIJO'
        ? '<span class="label label-primary">Fijo</span>'
        : '<span class="label label-warning">Variable</span>';

    if ($fila['estado'] === 'ANULADO') {
        $badge_estado = '<span class="badge-anulado">Anulado</span>';
        $acciones = '<span title="Gasto anulado" style="color:rgba(136,146,160,.4);cursor:default;font-size:11px;">Anulado</span>';
    } else {
        $badge_estado = '<span class="badge-activo">Activo</span>';
        $acciones = '<button type="button" class="btn-action" onclick="verDetalle(this)" title="Ver detalle">'
                   . '<ion-icon name="eye-outline" style="font-size:15px;"></ion-icon></button>'
                   . '<button type="button" class="btn-action btn-danger" onclick="anularGasto(' . $fila['id'] . ', \'' . addslashes($fila['codigo']) . '\')" title="Anular gasto">'
                   . '<ion-icon name="ban-outline" style="font-size:15px;"></ion-icon></button>';
    }

    $detalle_json = htmlspecialchars(json_encode([
        'codigo' => $fila['codigo'],
        'fecha' => $fila['fecha'],
        'concepto' => $fila['concepto'],
        'categoria' => $fila['categoria_nombre'] ?? '—',
        'tipo' => $tipo_labels[$fila['tipo']] ?? $fila['tipo'],
        'frecuencia' => $fila['frecuencia'],
        'simbolo' => '$',
        'monto' => $monto_formateado,
        'monto_usd' => $monto_formateado,
        'estado' => $fila['estado'],
        'observacion' => $fila['observacion'] ?? '',
        'usuario' => $fila['usuario_nombre'] ?? '—'
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

    echo '<tr data-detalle=\'' . $detalle_json . '\'>'
        . '<td>' . $count . '</td>'
        . '<td><strong>' . htmlspecialchars($fila['codigo']) . '</strong></td>'
        . '<td>' . htmlspecialchars($fila['fecha']) . '</td>'
        . '<td>' . htmlspecialchars($fila['concepto']) . '</td>'
        . '<td>' . htmlspecialchars($fila['categoria_nombre'] ?? '—') . '</td>'
        . '<td>' . $badge_tipo . '</td>'
        . '<td>' . htmlspecialchars($fila['frecuencia']) . '</td>'
        . '<td style="text-align:right;"><strong>$ ' . $monto_formateado . '</strong></td>'
        . '<td>' . $badge_estado . '</td>'
        . '<td style="text-align:center;">' . $acciones . '</td>'
        . '</tr>';
}

echo '</tbody></table>';
