<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');

$bss_id      = (int) $_SESSION['bss_id'];
$usuario_id  = (int) $_SESSION['id'];
$id_sucursal = (int) $_SESSION['sucursal'];

if (!$bss_id || !$usuario_id) {
    echo json_encode(['status' => 'error', 'msg' => 'Sesión no válida']);
    exit;
}

$accion = trim($_POST['accion'] ?? '');

switch ($accion) {

    case 'listar':
        $nivel = (int) $_SESSION['nivel'];
        $filtroSucursal = ($id_sucursal > 0) ? 'AND r.id_sucursal = ?' : '';
        $sql = "SELECT r.id, r.concepto, r.tipo, r.frecuencia, r.monto_estimado, r.moneda,
                    r.dia_ejecucion, r.fecha_inicio, r.fecha_fin, r.activo, r.observacion,
                    gc.nombre AS categoria_nombre
             FROM gastos_recurrentes r
             LEFT JOIN gastos_categorias gc ON r.categoria_id = gc.id
             WHERE r.bss_id = ? $filtroSucursal
             ORDER BY r.activo DESC, r.concepto ASC";
        $stmt = $conexion->prepare($sql);
        if ($id_sucursal > 0) {
            $stmt->bind_param('ii', $bss_id, $id_sucursal);
        } else {
            $stmt->bind_param('i', $bss_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        echo json_encode(['status' => 'ok', 'data' => $items]);
        break;

    case 'crear':
        $concepto     = trim(strip_tags($_POST['concepto'] ?? ''));
        $categoria_id = intval($_POST['categoria_id'] ?? 0) ?: null;
        $tipo         = in_array($_POST['tipo'] ?? '', ['FIJO', 'VARIABLE']) ? $_POST['tipo'] : null;
        $frecuencia   = in_array($_POST['frecuencia'] ?? '', [
            'DIARIO','SEMANAL','QUINCENAL','MENSUAL'
        ]) ? $_POST['frecuencia'] : null;
        $moneda        = in_array($_POST['moneda'] ?? '', ['USD', 'VES', 'COP']) ? $_POST['moneda'] : 'USD';
        $monto_estimado = floatval($_POST['monto_estimado'] ?? 0) ?: null;
        $dia_ejecucion  = trim(strip_tags($_POST['dia_ejecucion'] ?? ''));
        $fecha_inicio   = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin      = trim($_POST['fecha_fin'] ?? '') ?: null;
        $observacion    = trim(strip_tags($_POST['observacion'] ?? ''));

        if (!$concepto || !$tipo || !$frecuencia || !$fecha_inicio) {
            echo json_encode(['status' => 'error', 'msg' => 'Campos obligatorios incompletos']);
            exit;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
        if (!$dt || $dt->format('Y-m-d') !== $fecha_inicio) {
            echo json_encode(['status' => 'error', 'msg' => 'Fecha de inicio inválida']);
            exit;
        }

        if ($fecha_fin !== null) {
            $dt2 = DateTime::createFromFormat('Y-m-d', $fecha_fin);
            if (!$dt2 || $dt2->format('Y-m-d') !== $fecha_fin) {
                echo json_encode(['status' => 'error', 'msg' => 'Fecha de fin inválida']);
                exit;
            }
        }

        $stmt = $conexion->prepare(
            "INSERT INTO gastos_recurrentes
             (concepto, categoria_id, tipo, frecuencia, monto_estimado, moneda,
              dia_ejecucion, fecha_inicio, fecha_fin, observacion, usuario_id, bss_id, id_sucursal)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            'ssssssssssiii',
            $concepto, $categoria_id, $tipo, $frecuencia, $monto_estimado, $moneda,
            $dia_ejecucion, $fecha_inicio, $fecha_fin, $observacion, $usuario_id, $bss_id, $id_sucursal
        );

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'msg' => 'Regla creada', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al crear regla']);
        }
        $stmt->close();
        break;

    case 'editar':
        $id           = intval($_POST['id'] ?? 0);
        $concepto     = trim(strip_tags($_POST['concepto'] ?? ''));
        $categoria_id = intval($_POST['categoria_id'] ?? 0) ?: null;
        $tipo         = in_array($_POST['tipo'] ?? '', ['FIJO', 'VARIABLE']) ? $_POST['tipo'] : null;
        $frecuencia   = in_array($_POST['frecuencia'] ?? '', [
            'DIARIO','SEMANAL','QUINCENAL','MENSUAL'
        ]) ? $_POST['frecuencia'] : null;
        $moneda        = in_array($_POST['moneda'] ?? '', ['USD', 'VES', 'COP']) ? $_POST['moneda'] : 'USD';
        $monto_estimado = floatval($_POST['monto_estimado'] ?? 0) ?: null;
        $dia_ejecucion  = trim(strip_tags($_POST['dia_ejecucion'] ?? ''));
        $fecha_inicio   = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin      = trim($_POST['fecha_fin'] ?? '') ?: null;
        $observacion    = trim(strip_tags($_POST['observacion'] ?? ''));

        if ($id <= 0 || !$concepto || !$tipo || !$frecuencia || !$fecha_inicio) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        $stmt = $conexion->prepare(
            "UPDATE gastos_recurrentes
             SET concepto=?, categoria_id=?, tipo=?, frecuencia=?, monto_estimado=?, moneda=?,
                 dia_ejecucion=?, fecha_inicio=?, fecha_fin=?, observacion=?
             WHERE id=? AND bss_id=? AND id_sucursal=?"
        );
        $stmt->bind_param(
            'ssssssssssiii',
            $concepto, $categoria_id, $tipo, $frecuencia, $monto_estimado, $moneda,
            $dia_ejecucion, $fecha_inicio, $fecha_fin, $observacion, $id, $bss_id, $id_sucursal
        );

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok', 'msg' => 'Regla actualizada']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Error al actualizar']);
        }
        $stmt->close();
        break;

    case 'desactivar':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status' => 'error', 'msg' => 'ID inválido']); exit; }
        $stmt = $conexion->prepare("UPDATE gastos_recurrentes SET activo=0 WHERE id=? AND bss_id=? AND id_sucursal=?");
        $stmt->bind_param('iii', $id, $bss_id, $id_sucursal);
        if ($stmt->execute()) { echo json_encode(['status' => 'ok', 'msg' => 'Regla desactivada']); }
        else { echo json_encode(['status' => 'error', 'msg' => 'Error']); }
        $stmt->close();
        break;

    case 'activar':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['status' => 'error', 'msg' => 'ID inválido']); exit; }
        $stmt = $conexion->prepare("UPDATE gastos_recurrentes SET activo=1 WHERE id=? AND bss_id=? AND id_sucursal=?");
        $stmt->bind_param('iii', $id, $bss_id, $id_sucursal);
        if ($stmt->execute()) { echo json_encode(['status' => 'ok', 'msg' => 'Regla activada']); }
        else { echo json_encode(['status' => 'error', 'msg' => 'Error']); }
        $stmt->close();
        break;

    case 'aplicar':
        $semana = trim($_POST['semana'] ?? date('Y-W'));
        if (!preg_match('/^(\d{4})-(\d{2})$/', $semana, $m)) {
            echo json_encode(['status' => 'error', 'msg' => 'Formato de semana inválido']);
            exit;
        }
        // strtotime soporta formato ISO "YYYY-WXX-D" (D=1 es lunes)
        $fecha_semana = date('Y-m-d', strtotime($semana . '-1'));
        $mes = substr($fecha_semana, 0, 7);

        $filtroSucursalAplicar = ($id_sucursal > 0) ? 'AND id_sucursal = ?' : '';
        $stmt = $conexion->prepare(
            "SELECT id, concepto, categoria_id, tipo, frecuencia, monto_estimado, moneda, observacion, dia_ejecucion, id_sucursal
             FROM gastos_recurrentes
             WHERE activo=1 AND bss_id=? $filtroSucursalAplicar"
        );
        if ($id_sucursal > 0) {
            $stmt->bind_param('ii', $bss_id, $id_sucursal);
        } else {
            $stmt->bind_param('i', $bss_id);
        }
        $stmt->execute();
        $reglas = $stmt->get_result();
        $aplicados = 0;

        while ($regla = $reglas->fetch_assoc()) {
            $regla_sucursal = $regla['id_sucursal']; // Usar la sucursal de la regla en lugar de la sesión (para admin general)
            
            $check = $conexion->prepare(
                "SELECT id FROM gastos
                 WHERE recurrente_id=? AND semana=? AND bss_id=? AND id_sucursal=? AND estado='ACTIVO'"
            );
            $check->bind_param('isii', $regla['id'], $semana, $bss_id, $regla_sucursal);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $check->close();
                continue;
            }
            $check->close();

            // Verificar si la regla aplica para la semana objetivo
            $aplica = false;
            $dia_ej = intval($regla['dia_ejecucion'] ?? 0);
            
            // Determinar fechas de la semana objetivo
            $inicio_semana_target = new DateTime();
            $inicio_semana_target->setISODate((int)substr($semana, 0, 4), (int)substr($semana, 5, 2));
            $fin_semana_target = clone $inicio_semana_target;
            $fin_semana_target->modify('+6 days');

            switch ($regla['frecuencia']) {
                case 'DIARIO':
                case 'SEMANAL':
                case 'QUINCENAL':
                    // Semanal siempre aplica una vez por semana (el query de duplicados ya verifica que no se repita en la misma semana).
                    // El usuario forzará el pago de la semana sin importar qué día de la semana sea.
                    $aplica = true;
                    break;
                case 'MENSUAL':
                    if ($dia_ej > 0) {
                        // Verificamos si el día de ejecución cae dentro de la semana objetivo
                        $mes_inicio = (int) $inicio_semana_target->format('m');
                        $anio_inicio = (int) $inicio_semana_target->format('Y');
                        
                        $ultimo_dia_mes = (int) date('t', strtotime($inicio_semana_target->format('Y-m-01')));
                        $dia_ajustado = min($dia_ej, $ultimo_dia_mes);
                        $fecha_ejecucion_mes = new DateTime("$anio_inicio-$mes_inicio-$dia_ajustado");
                        
                        if ($fecha_ejecucion_mes >= $inicio_semana_target && $fecha_ejecucion_mes <= $fin_semana_target) {
                            $aplica = true;
                        } else {
                            // También verificar si el día cae en el mes del fin de la semana (si cruza de mes)
                            $mes_fin = (int) $fin_semana_target->format('m');
                            if ($mes_inicio !== $mes_fin) {
                                $anio_fin = (int) $fin_semana_target->format('Y');
                                $ultimo_dia_mes_fin = (int) date('t', strtotime($fin_semana_target->format('Y-m-01')));
                                $dia_ajustado_fin = min($dia_ej, $ultimo_dia_mes_fin);
                                $fecha_ejecucion_mes_fin = new DateTime("$anio_fin-$mes_fin-$dia_ajustado_fin");
                                if ($fecha_ejecucion_mes_fin >= $inicio_semana_target && $fecha_ejecucion_mes_fin <= $fin_semana_target) {
                                    $aplica = true;
                                }
                            }
                        }
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

            $stmt2 = $conexion->prepare("SELECT codigo FROM gastos WHERE bss_id=? ORDER BY id DESC LIMIT 1");
            $stmt2->bind_param('i', $bss_id);
            $stmt2->execute();
            $row = $stmt2->get_result()->fetch_assoc();
            $numero = $row ? (int) substr($row['codigo'], 2) + 1 : 1;
            $codigo = 'G-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
            $stmt2->close();

            $monto_est = floatval($regla['monto_estimado']);
            $tasa = 1.0;
            $monto_usd = $monto_est;
            if ($regla['moneda'] === 'VES') {
                $stmt_t = $conexion->prepare("SELECT DolarBolivar FROM cambio WHERE bss_id=?");
                $stmt_t->bind_param('i', $bss_id);
                $stmt_t->execute();
                $tasa = $stmt_t->get_result()->fetch_assoc()['DolarBolivar'] ?? 0;
                $stmt_t->close();
                $monto_usd = ($tasa > 0) ? round($monto_est / $tasa, 2) : 0;
            } elseif ($regla['moneda'] === 'COP') {
                $stmt_t = $conexion->prepare("SELECT pesoDolar FROM cambio WHERE bss_id=?");
                $stmt_t->bind_param('i', $bss_id);
                $stmt_t->execute();
                $tasa = $stmt_t->get_result()->fetch_assoc()['pesoDolar'] ?? 0;
                $stmt_t->close();
                $monto_usd = ($tasa > 0) ? round($monto_est / $tasa, 2) : 0;
            }

            $obs = $regla['observacion'];
            $cat_id = $regla['categoria_id'] ?? null;
            if ($cat_id) {
                $ins = $conexion->prepare(
                    "INSERT INTO gastos (codigo, fecha, semana, mes, concepto, categoria_id, tipo, frecuencia,
                     moneda, monto, tasa_cambio, monto_usd, observacion, recurrente_id, usuario_id, id_sucursal, bss_id)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                );
                $ins->bind_param(
                    'sssssisssdddsiiii',
                    $codigo, $fecha_semana, $semana, $mes, $regla['concepto'], $cat_id,
                    $regla['tipo'], $regla['frecuencia'], $regla['moneda'], $monto_est, $tasa, $monto_usd,
                    $obs, $regla['id'], $usuario_id, $regla_sucursal, $bss_id
                );
            } else {
                $ins = $conexion->prepare(
                    "INSERT INTO gastos (codigo, fecha, semana, mes, concepto, tipo, frecuencia,
                     moneda, monto, tasa_cambio, monto_usd, observacion, recurrente_id, usuario_id, id_sucursal, bss_id)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                );
                $ins->bind_param(
                    'ssssssssdddsiiii',
                    $codigo, $fecha_semana, $semana, $mes, $regla['concepto'],
                    $regla['tipo'], $regla['frecuencia'], $regla['moneda'], $monto_est, $tasa, $monto_usd,
                    $obs, $regla['id'], $usuario_id, $regla_sucursal, $bss_id
                );
            }

            if ($ins->execute()) {
                $aplicados++;
            }
            $ins->close();
        }
        $stmt->close();

        echo json_encode(['status' => 'ok', 'msg' => "$aplicados gastos aplicados para la semana $semana", 'aplicados' => $aplicados]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}
