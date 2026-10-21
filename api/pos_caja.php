<?php
/**
 * API — Caja del POS nativo  →  POST /api/pos_caja.php
 *
 * Guarda aperturas y cierres hechos sin conexión, con idempotencia por UUID,
 * y permite reabrir una caja (solo con red).
 *
 * Cuerpo:
 *   { "accion": "guardar", "id": "uuid", "tipo": "apertura|cierre",
 *     "fecha": "2026-10-18", "montos": { ... }, "observaciones": "" }
 *   { "accion": "reabrir", "corte_id": "uuid" }
 *
 * Autenticación: X-Api-Key + Bearer. El usuario, negocio y sucursal salen del token.
 */

require_once __DIR__ . '/_pos_comun.php';

exigirMetodo('POST');
exigirApiKey();
limitar('caja', 60, 60);

$sesion = exigirSesion($conexion);
$bssId = $sesion['bss_id'];
$sucursalId = (int)($sesion['sucursal_id'] ?? 0);
$usuarioId = $sesion['id_user'];

if ($sucursalId <= 0) {
    salir(409, ['ok' => false, 'error' => 'La sesión no tiene sucursal']);
}

asegurarUuid($conexion);

$entrada = entrada();
$accion = (string)($entrada['accion'] ?? 'guardar');

if ($accion === 'reabrir') {
    salir(200, reabrirCaja($conexion, $entrada, $bssId, $sucursalId, $usuarioId));
}

if ($accion !== 'guardar') {
    salir(400, ['ok' => false, 'error' => 'Acción no válida']);
}

$id = trim((string)($entrada['id'] ?? ''));
$tipo = (string)($entrada['tipo'] ?? '');
$fecha = trim((string)($entrada['fecha'] ?? ''));
$montos = $entrada['montos'] ?? [];
$observaciones = trim((string)($entrada['observaciones'] ?? ''));

if ($id === '') {
    salir(400, ['ok' => false, 'error' => 'Falta el id del corte']);
}
if ($tipo !== 'apertura' && $tipo !== 'cierre') {
    salir(400, ['ok' => false, 'error' => 'tipo debe ser apertura o cierre']);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}
if (!is_array($montos)) {
    $montos = [];
}

// Idempotencia: si el UUID ya está, no se inserta otra vez.
$stmt = $conexion->prepare("SELECT id FROM cortes_de_caja WHERE uuid = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($existente) {
        salir(200, [
            'ok' => true,
            'duplicada' => true,
            'corte_id' => (int)$existente['id'],
            'servidor_en' => servidorEn(),
        ]);
    }
}

if ($tipo === 'apertura') {
    salir(200, guardarApertura($conexion, $id, $fecha, $montos, $observaciones, $bssId, $sucursalId, $usuarioId));
}

salir(200, guardarCierre($conexion, $id, $fecha, $montos, $observaciones, $bssId, $sucursalId, $usuarioId));

// ───────────────────────────────────────────────────────────────────────────────

function numero($montos, string $clave): float
{
    return isset($montos[$clave]) ? (float)$montos[$clave] : 0.0;
}

function guardarApertura($conexion, string $uuid, string $fecha, array $montos, string $obs, int $bssId, int $sucursalId, int $usuarioId): array
{
    $stmt = $conexion->prepare(
        "INSERT INTO cortes_de_caja (
            usuario_id, bss_id, sucursal_id, tipo_corte, fecha,
            efectivo_bs_fondo, efectivo_usd_fondo, pesos_fondo, observaciones, uuid
         ) VALUES (?, ?, ?, 'apertura', ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo preparar la apertura'];
    }

    $bs = numero($montos, 'efectivo_bs_fondo');
    $usd = numero($montos, 'efectivo_usd_fondo');
    $pesos = numero($montos, 'pesos_fondo');

    $stmt->bind_param('iiisdddss', $usuarioId, $bssId, $sucursalId, $fecha, $bs, $usd, $pesos, $obs, $uuid);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return ['ok' => false, 'error' => 'Error al guardar la apertura: ' . $error];
    }
    $corteId = (int)$stmt->insert_id;
    $stmt->close();

    return ['ok' => true, 'duplicada' => false, 'corte_id' => $corteId, 'servidor_en' => servidorEn()];
}

function guardarCierre($conexion, string $uuid, string $fecha, array $montos, string $obs, int $bssId, int $sucursalId, int $usuarioId): array
{
    $sistema = totalesSistema($conexion, $fecha, $bssId, $sucursalId, $usuarioId);

    $contado = [
        'efectivo_bs' => numero($montos, 'efectivo_bs_contado'),
        'efectivo_usd' => numero($montos, 'efectivo_usd_contado'),
        'pesos' => numero($montos, 'pesos_contado'),
        'punto' => numero($montos, 'punto_contado'),
        'biopago' => numero($montos, 'biopago_contado'),
        'pago_movil' => numero($montos, 'pago_movil_contado'),
        'transferencia' => numero($montos, 'transferencia_contado'),
    ];

    $fondo = [
        'efectivo_bs' => numero($montos, 'efectivo_bs_fondo'),
        'efectivo_usd' => numero($montos, 'efectivo_usd_fondo'),
        'pesos' => numero($montos, 'pesos_fondo'),
    ];

    $diferencias = [
        'efectivo_bs' => $sistema['efectivo_bs'] - $contado['efectivo_bs'],
        'efectivo_usd' => $sistema['efectivo_usd'] - $contado['efectivo_usd'],
        'pesos' => $sistema['pesos'] - $contado['pesos'],
        'punto' => $sistema['punto'] - $contado['punto'],
        'biopago' => $sistema['biopago'] - $contado['biopago'],
        'pago_movil' => $sistema['pago_movil'] - $contado['pago_movil'],
        'transferencia' => $sistema['transferencia'] - $contado['transferencia'],
    ];

    $stmt = $conexion->prepare(
        "INSERT INTO cortes_de_caja (
            usuario_id, bss_id, sucursal_id, tipo_corte, fecha,
            efectivo_bs_sistema, efectivo_usd_sistema, pesos_sistema, punto_sistema,
            biopago_sistema, pago_movil_sistema, transferencia_sistema,
            efectivo_bs_contado, efectivo_usd_contado, pesos_contado, punto_contado,
            biopago_contado, pago_movil_contado, transferencia_contado,
            efectivo_bs_fondo, efectivo_usd_fondo, pesos_fondo,
            diferencia_efectivo_bs, diferencia_efectivo_usd, diferencia_pesos,
            diferencia_punto, diferencia_biopago, diferencia_pago_movil, diferencia_transferencia,
            observaciones, uuid
         ) VALUES (?, ?, ?, 'cierre', ?, " . implode(', ', array_fill(0, 26, '?')) . ")"
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo preparar el cierre'];
    }

    $tipos = 'iiis' . str_repeat('d', 24) . 'ss';
    $stmt->bind_param(
        $tipos,
        $usuarioId,
        $bssId,
        $sucursalId,
        $fecha,
        $sistema['efectivo_bs'], $sistema['efectivo_usd'], $sistema['pesos'], $sistema['punto'],
        $sistema['biopago'], $sistema['pago_movil'], $sistema['transferencia'],
        $contado['efectivo_bs'], $contado['efectivo_usd'], $contado['pesos'], $contado['punto'],
        $contado['biopago'], $contado['pago_movil'], $contado['transferencia'],
        $fondo['efectivo_bs'], $fondo['efectivo_usd'], $fondo['pesos'],
        $diferencias['efectivo_bs'], $diferencias['efectivo_usd'], $diferencias['pesos'],
        $diferencias['punto'], $diferencias['biopago'], $diferencias['pago_movil'],
        $diferencias['transferencia'],
        $obs,
        $uuid
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return ['ok' => false, 'error' => 'Error al guardar el cierre: ' . $error];
    }
    $corteId = (int)$stmt->insert_id;
    $stmt->close();

    return [
        'ok' => true,
        'duplicada' => false,
        'corte_id' => $corteId,
        'sistema' => $sistema,
        'diferencias' => $diferencias,
        'servidor_en' => servidorEn(),
    ];
}

function totalesSistema($conexion, string $fecha, int $bssId, int $sucursalId, int $usuarioId): array
{
    $totales = [
        'punto' => 0.0, 'pago_movil' => 0.0, 'transferencia' => 0.0, 'efectivo_bs' => 0.0,
        'efectivo_usd' => 0.0, 'pesos' => 0.0, 'biopago' => 0.0,
    ];

    $stmt = $conexion->prepare(
        "SELECT tipoPago, SUM(total_price) AS usd, SUM(total_price_bs) AS bs, SUM(total_price_cop) AS cop
         FROM orden
         WHERE DATE(created) = ? AND status IN ('1', '4')
           AND bss_id = ? AND id_sucursal = ? AND usuario = ?
         GROUP BY tipoPago"
    );
    if (!$stmt) {
        return $totales;
    }
    $stmt->bind_param('siii', $fecha, $bssId, $sucursalId, $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        switch ((int)$fila['tipoPago']) {
            case 1: $totales['punto'] += (float)$fila['bs']; break;
            case 2: $totales['pago_movil'] += (float)$fila['bs']; break;
            case 3: $totales['transferencia'] += (float)$fila['bs']; break;
            case 4: $totales['efectivo_bs'] += (float)$fila['bs']; break;
            case 5: $totales['efectivo_usd'] += (float)$fila['usd']; break;
            case 6: $totales['pesos'] += (float)$fila['cop']; break;
            case 7: $totales['biopago'] += (float)$fila['bs']; break;
        }
    }
    $stmt->close();

    // Pago fraccionado (tipoPago 8): sumar cada fracción a su medio.
    $stmtFrac = $conexion->prepare(
        "SELECT SUM(f.punto) AS punto, SUM(f.pagoMovil) AS pagoMovil, SUM(f.transferencia) AS transferencia,
                SUM(f.bioPago) AS bioPago, SUM(f.efectivo) AS efectivo, SUM(f.pesos) AS pesos, SUM(f.dolares) AS dolares
         FROM fracciones f
         INNER JOIN orden o ON o.id = f.id_order
         WHERE DATE(o.created) = ? AND o.tipoPago = 8 AND o.status IN ('1', '4')
           AND o.bss_id = ? AND o.id_sucursal = ? AND o.usuario = ?"
    );
    if ($stmtFrac) {
        $stmtFrac->bind_param('siii', $fecha, $bssId, $sucursalId, $usuarioId);
        $stmtFrac->execute();
        $filaFrac = $stmtFrac->get_result()->fetch_assoc();
        $stmtFrac->close();
        if ($filaFrac) {
            $totales['punto'] += (float)$filaFrac['punto'];
            $totales['pago_movil'] += (float)$filaFrac['pagoMovil'];
            $totales['transferencia'] += (float)$filaFrac['transferencia'];
            $totales['biopago'] += (float)$filaFrac['bioPago'];
            $totales['efectivo_bs'] += (float)$filaFrac['efectivo'];
            $totales['pesos'] += (float)$filaFrac['pesos'];
            $totales['efectivo_usd'] += (float)$filaFrac['dolares'];
        }
    }

    return $totales;
}

function reabrirCaja($conexion, array $entrada, int $bssId, int $sucursalId, int $usuarioId): array
{
    $corteId = trim((string)($entrada['corte_id'] ?? ''));
    if ($corteId === '') {
        return ['ok' => false, 'error' => 'Falta el corte_id a reabrir'];
    }

    $stmt = $conexion->prepare(
        "DELETE FROM cortes_de_caja
         WHERE uuid = ? AND usuario_id = ? AND bss_id = ? AND sucursal_id = ? AND tipo_corte = 'cierre'"
    );
    if (!$stmt) {
        return ['ok' => false, 'error' => 'No se pudo preparar la reapertura'];
    }
    $stmt->bind_param('siii', $corteId, $usuarioId, $bssId, $sucursalId);
    $stmt->execute();
    $afectadas = $stmt->affected_rows;
    $stmt->close();

    return [
        'ok' => true,
        'ya_estaba' => $afectadas === 0,
        'servidor_en' => servidorEn(),
    ];
}

/**
 * Añade la columna uuid a cortes_de_caja si falta (aditivo, sin tocar datos).
 * El proyecto no tiene migraciones, así que se asegura en el primer uso.
 */
function asegurarUuid($conexion): void
{
    static $listo = false;
    if ($listo) {
        return;
    }

    $col = $conexion->query("SHOW COLUMNS FROM cortes_de_caja LIKE 'uuid'");
    if ($col && $col->num_rows === 0) {
        $conexion->query("ALTER TABLE cortes_de_caja ADD COLUMN uuid CHAR(36) NULL");
    }

    $idx = $conexion->query("SHOW INDEX FROM cortes_de_caja WHERE Key_name = 'uniq_cortes_uuid'");
    if ($idx && $idx->num_rows === 0) {
        $conexion->query("ALTER TABLE cortes_de_caja ADD UNIQUE INDEX uniq_cortes_uuid (uuid)");
    }

    $listo = true;
}
