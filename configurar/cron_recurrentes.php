<?php
/**
 * Cron job: Aplica gastos recurrentes para TODOS los negocios activos.
 * Ejecutar: php cron_recurrentes.php
 * Frecuencia recomendada: diaria a las 23:59
 */

date_default_timezone_set('America/Caracas');

require_once __DIR__ . '/configuracion.php';

$log_dir  = __DIR__ . '/../logs';
$log_file = $log_dir . '/cron_recurrentes_' . date('Y-m') . '.log';

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function cron_log($msg) {
    global $log_file;
    $line = date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL;
    file_put_contents($log_file, $line, FILE_APPEND);
}

cron_log('--- INICIO CRON RECURRENTES ---');
cron_log('PHP ' . PHP_VERSION . ' | ' . php_uname('n'));

function obtenerSiguienteCodigo($conexion, $bss_id) {
    $stmt = $conexion->prepare("SELECT codigo FROM gastos WHERE bss_id=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
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

// Fecha actual en Venezuela
$fecha_actual = date('Y-m-d');
$mes_actual   = substr($fecha_actual, 0, 7);
$ultimo_dia_mes = date('t', strtotime($mes_actual . '-01'));
$primer_dia_mes = $mes_actual . '-01';

// Todos los negocios activos
$negocios = $conexion->query("SELECT id FROM negocio WHERE estado='activo'");
if (!$negocios || $negocios->num_rows === 0) {
    cron_log('No hay negocios activos. Finalizando.');
    echo date('Y-m-d H:i:s') . " No hay negocios activos.\n";
    exit;
}
cron_log('Negocios activos: ' . $negocios->num_rows);

$total_aplicados = 0;

while ($neg = $negocios->fetch_assoc()) {
    $bss_id = (int) $neg['id'];

    // Obtener usuario admin para usuario_id
    $stmt_u = $conexion->prepare("SELECT id FROM usuarios WHERE bss_id=? AND nivel=1 LIMIT 1");
    $stmt_u->bind_param('i', $bss_id);
    $stmt_u->execute();
    $row_u = $stmt_u->get_result()->fetch_assoc();
    $stmt_u->close();
    if (!$row_u) continue;
    $usuario_id = (int) $row_u['id'];

    // Obtener tasas de cambio
    $dolarBolivar = 1.0;
    $pesoDolar = 1.0;
    $stmt_t = $conexion->prepare("SELECT DolarBolivar, pesoDolar FROM cambio WHERE bss_id=? LIMIT 1");
    $stmt_t->bind_param('i', $bss_id);
    $stmt_t->execute();
    $row_t = $stmt_t->get_result()->fetch_assoc();
    $stmt_t->close();
    if ($row_t) {
        $dolarBolivar = floatval($row_t['DolarBolivar'] ?: 1);
        $pesoDolar = floatval($row_t['pesoDolar'] ?: 1);
    }

    // Obtener reglas activas (todas las sucursales)
    $stmt_r = $conexion->prepare(
        "SELECT id, concepto, categoria_id, tipo, frecuencia, monto_estimado, moneda, dia_ejecucion, observacion, id_sucursal
         FROM gastos_recurrentes
         WHERE activo=1 AND bss_id=?"
    );
    $stmt_r->bind_param('i', $bss_id);
    $stmt_r->execute();
    $reglas = $stmt_r->get_result();
    $stmt_r->close();
    $total_reglas = $reglas->num_rows;

    $aplicados_neg = 0;
    $skipped_neg = 0;

    while ($regla = $reglas->fetch_assoc()) {
        $dia_ej = intval($regla['dia_ejecucion'] ?? 0);
        $frecuencia = $regla['frecuencia'];
        $cat_id = $regla['categoria_id'] ?: null;
        $suc_regla = (int) $regla['id_sucursal'];
        list($monto_est, $tasa, $monto_usd) = calcularMontoUSD($regla, $dolarBolivar, $pesoDolar);

        cron_log("  Regla #{$regla['id']} \"{$regla['concepto']}\" freq={$frecuencia} dia_ej={$dia_ej} moneda={$regla['moneda']} monto={$monto_est} sucursal={$suc_regla}");

        if ($frecuencia === 'SEMANAL') {
            for ($d = 1; $d <= $ultimo_dia_mes; $d++) {
                $fecha_str = $mes_actual . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                $dia_n = (int) date('N', strtotime($fecha_str));
                if ($dia_n !== $dia_ej || $fecha_str > $fecha_actual) continue;

                // Check duplicado por fecha
                $chk = $conexion->prepare(
                    "SELECT id FROM gastos WHERE recurrente_id=? AND fecha=? AND bss_id=?"
                );
                $chk->bind_param('isi', $regla['id'], $fecha_str, $bss_id);
                $chk->execute();
                if ($chk->get_result()->num_rows > 0) { $chk->close(); $skipped_neg++; cron_log("    SKIP duplicado fecha {$fecha_str}"); continue; }
                $chk->close();

                $semana_gasto = date('Y-W', strtotime($fecha_str));
                $codigo = obtenerSiguienteCodigo($conexion, $bss_id);
                if (insertarGasto($conexion, $codigo, $fecha_str, $semana_gasto, $mes_actual, $regla, $cat_id, $usuario_id, $suc_regla, $bss_id, $monto_est, $tasa, $monto_usd)) {
                    $aplicados_neg++;
                    cron_log("    OK {$codigo} fecha={$fecha_str} monto_usd=\${$monto_usd}");
                } else {
                    cron_log("    FAIL insert {$regla['concepto']} fecha={$fecha_str}");
                }
            }
        } else {
            // Check duplicado por mes (UNICO/MENSUAL/QUINCENAL/DIARIO)
            $chk = $conexion->prepare(
                "SELECT id FROM gastos WHERE recurrente_id=? AND mes=? AND bss_id=?"
            );
            $chk->bind_param('isi', $regla['id'], $mes_actual, $bss_id);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) { $chk->close(); $skipped_neg++; cron_log("    SKIP duplicado mes {$mes_actual}"); continue; }
            $chk->close();

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
                default: // UNICO y cualquier otro
                    $aplica = true;
            }

            if (!$aplica) {
                $skipped_neg++;
                cron_log("    SKIP no aplica hoy (freq={$frecuencia}, dia_ej={$dia_ej}, hoy=" . date('d') . ")");
                continue;
            }

            $codigo = obtenerSiguienteCodigo($conexion, $bss_id);
            $semana_gasto = date('Y-W', strtotime($fecha_gasto));
            if (insertarGasto($conexion, $codigo, $fecha_gasto, $semana_gasto, $mes_actual, $regla, $cat_id, $usuario_id, $suc_regla, $bss_id, $monto_est, $tasa, $monto_usd)) {
                $aplicados_neg++;
                cron_log("    OK {$codigo} fecha={$fecha_gasto} monto_usd=\${$monto_usd}");
            } else {
                cron_log("    FAIL insert {$regla['concepto']} fecha={$fecha_gasto}");
            }
        }
    }

    $total_aplicados += $aplicados_neg;
    cron_log("Negocio #{$bss_id}: {$total_reglas} reglas activas, {$aplicados_neg} aplicados, {$skipped_neg} saltados.");
    if ($aplicados_neg > 0) {
        echo date('Y-m-d H:i:s') . " Negocio #{$bss_id}: {$aplicados_neg} gastos aplicados.\n";
    }
}

cron_log("TOTAL: {$total_aplicados} gastos aplicados.");
cron_log('--- FIN CRON RECURRENTES ---');
echo date('Y-m-d H:i:s') . " Total: {$total_aplicados} gastos aplicados.\n";
echo "Log: {$log_file}\n";
