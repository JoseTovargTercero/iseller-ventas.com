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

$bss_id_esc   = $conexion->real_escape_string($bss_id);
$sucursal_esc = $conexion->real_escape_string($id_sucursal);

$where = "e.bss_id = $bss_id_esc";

if ($id_sucursal > 0) {
    $where .= " AND e.id_sucursal = $sucursal_esc";
}

$sql = "SELECT e.id, e.nombre, e.rol, e.tipo_pago, e.monto_pago, e.moneda,
               e.dia_ejecucion, e.activo, e.observacion, e.created_at,
               s.nombre AS sucursal_nombre
        FROM empleados e
        LEFT JOIN sucursales s ON e.id_sucursal = s.id
        WHERE $where
        ORDER BY e.activo DESC, e.nombre ASC";

$result = $conexion->query($sql);
if (!$result) {
    echo 'Error al consultar empleados';
    exit;
}

if ($result->num_rows === 0) {
    echo '<div style="text-align:center;padding:40px 0;color:var(--dash-text-muted);font-size:13px;">No se encontraron empleados</div>';
    exit;
}

$dias_semana = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
$count = 0;

echo '<table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
<thead>
<tr class="headings">
<th style="padding:10px !important; width:3%">#</th>
<th style="padding:10px !important; width:18%">Nombre</th>
<th style="padding:10px !important; width:14%">Rol</th>
<th style="padding:10px !important; width:9%">Tipo pago</th>
<th style="padding:10px !important; width:10%">Monto</th>
<th style="padding:10px !important; width:9%">Frecuencia</th>
<th style="padding:10px !important; width:11%">Día ejecución</th>
<th style="padding:10px !important; width:10%">Sucursal</th>
<th style="padding:10px !important; width:6%">Estado</th>
<th style="padding:10px !important; width:6%">Acciones</th>
</tr>
</thead>
<tbody>';

while ($fila = $result->fetch_assoc()) {
    $count++;
    $monto_formateado = number_format($fila['monto_pago'], 2, ',', '.');

    $badge_tipo = $fila['tipo_pago'] === 'SEMANAL'
        ? '<span class="label label-primary">Semanal</span>'
        : '<span class="label label-warning">Mensual</span>';

    $frecuencia_texto = $fila['tipo_pago'] === 'SEMANAL' ? 'Semanal' : 'Mensual';

    $dia_ej = intval($fila['dia_ejecucion'] ?? 0);
    if ($fila['tipo_pago'] === 'SEMANAL') {
        $dia_texto = $dias_semana[$dia_ej] ?? $dia_ej;
    } else {
        $dia_texto = 'Día ' . $dia_ej;
    }

    if ($fila['activo']) {
        $badge_estado = '<span class="badge-activo">Activo</span>';
        $acciones = '<button type="button" class="btn-action" onclick="verDetalle(this)" title="Ver detalle">'
                   . '<ion-icon name="eye-outline" style="font-size:15px;"></ion-icon></button>'
                   . '<button type="button" class="btn-action" onclick="editarEmpleado(this)" title="Editar">'
                   . '<ion-icon name="create-outline" style="font-size:15px;"></ion-icon></button>'
                   . '<button type="button" class="btn-action btn-danger" onclick="toggleEmpleado(' . $fila['id'] . ', 0)" title="Desactivar">'
                   . '<ion-icon name="ban-outline" style="font-size:15px;"></ion-icon></button>';
    } else {
        $badge_estado = '<span class="badge-anulado">Inactivo</span>';
        $acciones = '<button type="button" class="btn-action" onclick="verDetalle(this)" title="Ver detalle">'
                   . '<ion-icon name="eye-outline" style="font-size:15px;"></ion-icon></button>'
                   . '<button type="button" class="btn-action" onclick="toggleEmpleado(' . $fila['id'] . ', 1)" title="Activar">'
                   . '<ion-icon name="checkmark-outline" style="font-size:15px;"></ion-icon></button>'
                   . '<button type="button" class="btn-action btn-danger" onclick="eliminarEmpleado(' . $fila['id'] . ')" title="Eliminar">'
                   . '<ion-icon name="trash-outline" style="font-size:15px;"></ion-icon></button>';
    }

    $detalle_json = htmlspecialchars(json_encode([
        'id' => $fila['id'],
        'nombre' => $fila['nombre'],
        'rol' => $fila['rol'],
        'tipo_pago' => $fila['tipo_pago'],
        'monto_pago' => $monto_formateado,
        'dia_ejecucion' => $fila['dia_ejecucion'],
        'dia_texto' => $dia_texto,
        'frecuencia' => $frecuencia_texto,
        'sucursal' => $fila['sucursal_nombre'] ?? '—',
        'activo' => $fila['activo'],
        'observacion' => $fila['observacion'] ?? ''
    ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

    echo '<tr data-detalle=\'' . $detalle_json . '\'>'
        . '<td>' . $count . '</td>'
        . '<td><strong>' . htmlspecialchars($fila['nombre']) . '</strong></td>'
        . '<td>' . htmlspecialchars($fila['rol']) . '</td>'
        . '<td>' . $badge_tipo . '</td>'
        . '<td style="text-align:right;"><strong>$ ' . $monto_formateado . '</strong></td>'
        . '<td>' . $frecuencia_texto . '</td>'
        . '<td>' . $dia_texto . '</td>'
        . '<td>' . htmlspecialchars($fila['sucursal_nombre'] ?? '—') . '</td>'
        . '<td>' . $badge_estado . '</td>'
        . '<td style="text-align:center;">' . $acciones . '</td>'
        . '</tr>';
}

echo '</tbody></table>';
