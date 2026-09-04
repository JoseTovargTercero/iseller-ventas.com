<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_tasas_cambio.php');

header('Content-Type: application/json');

$bss_id      = (int) $_SESSION['bss_id'];
$usuario_id  = (int) $_SESSION['id'];
$id_sucursal = (int) $_SESSION['sucursal'];

if (!$bss_id || !$usuario_id) {
    echo json_encode(['status' => 'error', 'aplicados' => 0]);
    exit;
}

$fecha_cliente = trim($_POST['fecha_local'] ?? '');
if ($fecha_cliente && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_cliente)) {
    $fecha_actual = $fecha_cliente;
} else {
    $fecha_actual = date('Y-m-d');
}

$mes_actual = substr($fecha_actual, 0, 7);
$semana_mes = date('Y-W', strtotime($fecha_actual));
$ultimo_dia_mes = date('t', strtotime($mes_actual . '-01'));
$primer_dia_mes = $mes_actual . '-01';

$stmt = $conexion->prepare(
    "SELECT id, concepto, categoria_id, tipo, frecuencia, monto_estimado, moneda, dia_ejecucion, observacion
     FROM gastos_recurrentes
     WHERE activo=1 AND bss_id=? AND (id_sucursal=? OR ?=0)"
);
$stmt->bind_param('iii', $bss_id, $id_sucursal, $id_sucursal);
$stmt->execute();
$reglas = $stmt->get_result();
$stmt->close();

$aplicados = 0;

function obtenerSiguienteCodigo($conexion, $bss_id) {
    $stmt2 = $conexion->prepare("SELECT codigo FROM gastos WHERE bss_id=? ORDER BY id DESC LIMIT 1");
    $stmt2->bind_param('i', $bss_id);
    $stmt2->execute();
    $row = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    $numero = $row ? (int) substr($row['codigo'], 2) + 1 : 1;
    return 'G-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
}

function calcularMontoUSD($regla, $dolarBolivar, $pesoDolar) {
    $monto_est = floatval($regla['monto_estimado']);
    $tasa = 1.0;
    $monto_usd = $monto_est;
    if ($regla['moneda'] === 'VES' && $dolarBolivar > 0) {
        $tasa = floatval($dolarBolivar);
        $monto_usd = round($monto_est / $tasa, 2);
    } elseif ($regla['moneda'] === 'COP' && $pesoDolar > 0) {
        $tasa = floatval($pesoDolar);
        $monto_usd = round($monto_est / $tasa, 2);
    }
    return [$monto_est, $tasa, $monto_usd];
}

function insertarGasto($conexion, $codigo, $fecha, $semana, $mes, $regla, $cat_id, $usuario_id, $id_sucursal, $bss_id, $monto_est, $tasa, $monto_usd) {
    $obs = $regla['observacion'];
    if ($cat_id) {
        $ins = $conexion->prepare(
            "INSERT INTO gastos (codigo, fecha, semana, mes, concepto, categoria_id, tipo, frecuencia,
             moneda, monto, tasa_cambio, monto_usd, observacion, recurrente_id, usuario_id, id_sucursal, bss_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $ins->bind_param(
            'sssssisssdddsiiii',
            $codigo, $fecha, $semana, $mes, $regla['concepto'], $cat_id,
            $regla['tipo'], $regla['frecuencia'], $regla['moneda'], $monto_est, $tasa, $monto_usd,
            $obs, $regla['id'], $usuario_id, $id_sucursal, $bss_id
        );
    } else {
        $ins = $conexion->prepare(
            "INSERT INTO gastos (codigo, fecha, semana, mes, concepto, tipo, frecuencia,
             moneda, monto, tasa_cambio, monto_usd, observacion, recurrente_id, usuario_id, id_sucursal, bss_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $ins->bind_param(
            'ssssssssdddsiiii',
            $codigo, $fecha, $semana, $mes, $regla['concepto'],
            $regla['tipo'], $regla['frecuencia'], $regla['moneda'], $monto_est, $tasa, $monto_usd,
            $obs, $regla['id'], $usuario_id, $id_sucursal, $bss_id
        );
    }
    $ok = $ins->execute();
    $ins->close();
    return $ok;
}

while ($regla = $reglas->fetch_assoc()) {
    $dia_ej = intval($regla['dia_ejecucion'] ?? 0);
    $frecuencia = $regla['frecuencia'];
    $cat_id = $regla['categoria_id'] ?? null;
    list($monto_est, $tasa, $monto_usd) = calcularMontoUSD($regla, $dolarBolivar, $pesoDolar);

    if ($frecuencia === 'SEMANAL') {
        $fechas_pendientes = [];
        for ($d = 1; $d <= $ultimo_dia_mes; $d++) {
            $fecha_str = $mes_actual . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
            $dia_n = (int) date('N', strtotime($fecha_str));
            if ($dia_n === $dia_ej && $fecha_str <= $fecha_actual) {
                $fechas_pendientes[] = $fecha_str;
            }
        }

        foreach ($fechas_pendientes as $fecha_gasto) {
            $check = $conexion->prepare(
                "SELECT id FROM gastos WHERE recurrente_id=? AND fecha=? AND bss_id=? AND (id_sucursal=? OR ?=0)"
            );
            $check->bind_param('isiii', $regla['id'], $fecha_gasto, $bss_id, $id_sucursal, $id_sucursal);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $check->close();
                continue;
            }
            $check->close();

            $semana_gasto = date('Y-W', strtotime($fecha_gasto));
            $codigo = obtenerSiguienteCodigo($conexion, $bss_id);
            $suc_gasto = $id_sucursal > 0 ? $id_sucursal : $regla['id_sucursal'];
            if (insertarGasto($conexion, $codigo, $fecha_gasto, $semana_gasto, $mes_actual, $regla, $cat_id, $usuario_id, $suc_gasto, $bss_id, $monto_est, $tasa, $monto_usd)) {
                $aplicados++;
            }
        }
    } else {
        $check = $conexion->prepare(
            "SELECT id FROM gastos WHERE recurrente_id=? AND mes=? AND bss_id=? AND (id_sucursal=? OR ?=0)"
        );
        $check->bind_param('isiii', $regla['id'], $mes_actual, $bss_id, $id_sucursal, $id_sucursal);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $check->close();
            continue;
        }
        $check->close();

        $aplica = false;
        $fecha_gasto = $primer_dia_mes;

        switch ($frecuencia) {
            case 'DIARIO':
                $aplica = true;
                $fecha_gasto = $fecha_actual;
                break;
            case 'QUINCENAL':
                if ($dia_ej > 0) {
                    $dia_ajustado = min($dia_ej, $ultimo_dia_mes);
                    $aplica = ((int) date('d', strtotime($fecha_actual)) === $dia_ajustado);
                } else {
                    $aplica = true;
                }
                break;
            case 'MENSUAL':
                if ($dia_ej > 0) {
                    $dia_ajustado = min($dia_ej, $ultimo_dia_mes);
                    $aplica = ((int) date('d', strtotime($fecha_actual)) === $dia_ajustado);
                    $fecha_gasto = $mes_actual . '-' . str_pad($dia_ajustado, 2, '0', STR_PAD_LEFT);
                } else {
                    $aplica = true;
                }
                break;
            default:
                $aplica = true;
        }

        if (!$aplica) {
            continue;
        }

        $codigo = obtenerSiguienteCodigo($conexion, $bss_id);
        $semana_gasto = date('Y-W', strtotime($fecha_gasto));
        $suc_gasto = $id_sucursal > 0 ? $id_sucursal : $regla['id_sucursal'];
        if (insertarGasto($conexion, $codigo, $fecha_gasto, $semana_gasto, $mes_actual, $regla, $cat_id, $usuario_id, $suc_gasto, $bss_id, $monto_est, $tasa, $monto_usd)) {
            $aplicados++;
        }
    }
}

echo json_encode(['status' => 'ok', 'aplicados' => $aplicados]);
