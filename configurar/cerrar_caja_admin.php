<?php
/**
 * Cierre de caja realizado por un administrador para un usuario que no
 * registró su cierre. También permite modificar un cierre ya existente
 * (enviando cierre_id). Calcula los totales del sistema y guarda el cierre
 * dejando constancia del administrador en las observaciones.
 */
require_once("configuracion.php");
require_once('session.php');
require_once(__DIR__ . '/_caja_totales.php');

header('Content-Type: application/json; charset=utf-8');

$responder = static function (string $status, string $message, array $extra = []): void {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
};

if ((int) ($_SESSION['nivel'] ?? 0) !== 1) {
    $responder('error', 'Solo un administrador puede realizar este cierre.');
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$adminNombre = trim((string) ($_SESSION['nombre'] ?? ''));
$payload = $_POST;
if (empty($payload)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json)) {
        $payload = $json;
    }
}

$usuarioId = (int) ($payload['usuario_id'] ?? 0);
$sucursalId = (int) ($payload['sucursal_id'] ?? 0);
$fecha = trim((string) ($payload['fecha'] ?? ''));

if ($bssId <= 0 || $usuarioId <= 0) {
    $responder('error', 'No se indicó un usuario válido.');
}

$dt = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$dt || $dt->format('Y-m-d') !== $fecha) {
    $responder('error', 'La fecha del cierre no es válida.');
}
if ($fecha > date('Y-m-d')) {
    $responder('error', 'No se puede registrar un cierre con fecha futura.');
}

/* ── Usuario destino ─────────────────────────────────────────────────── */
$stmt = $conexion->prepare(
    "SELECT id, nombre, id_sucursal
     FROM usuarios
     WHERE id = ? AND bss_id = ?
     LIMIT 1"
);
if (!$stmt) {
    $responder('error', 'No se pudo consultar el usuario.');
}
$stmt->bind_param('ii', $usuarioId, $bssId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    $responder('error', 'El usuario no pertenece a este negocio.');
}

if ($sucursalId <= 0) {
    $sucursalId = (int) ($usuario['id_sucursal'] ?? 0);
}
if ($sucursalId <= 0) {
    $sucursalId = (int) ($_SESSION['sucursal'] ?? 0);
}
if ($sucursalId <= 0) {
    $responder('error', 'No se pudo determinar la sucursal del cierre.');
}

$stmt = $conexion->prepare("SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
if (!$stmt) {
    $responder('error', 'No se pudo consultar la sucursal.');
}
$stmt->bind_param('ii', $sucursalId, $bssId);
$stmt->execute();
$sucursal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sucursal) {
    $responder('error', 'La sucursal no pertenece a este negocio.');
}

/* ── Cierre existente (solo el administrador puede modificarlo) ──────── */
$cierreId = (int) ($payload['cierre_id'] ?? 0);
$cierreExistente = null;

if ($cierreId > 0) {
    $stmt = $conexion->prepare(
        "SELECT *
         FROM cortes_de_caja
         WHERE id = ? AND bss_id = ? AND tipo_corte = 'cierre'
         LIMIT 1"
    );
    if (!$stmt) {
        $responder('error', 'No se pudo consultar el cierre a modificar.');
    }
    $stmt->bind_param('ii', $cierreId, $bssId);
    $stmt->execute();
    $cierreExistente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cierreExistente) {
        $responder('error', 'El cierre que intentas modificar no existe o no pertenece a este negocio.');
    }
    if (
        (int) $cierreExistente['usuario_id'] !== $usuarioId ||
        (int) $cierreExistente['sucursal_id'] !== $sucursalId ||
        (string) $cierreExistente['fecha'] !== $fecha
    ) {
        $responder('error', 'El cierre no coincide con el usuario, la sucursal o la fecha indicada.');
    }
} else {
    /* Evitar un segundo cierre para el mismo usuario/día/sucursal */
    $stmt = $conexion->prepare(
        "SELECT id
         FROM cortes_de_caja
         WHERE bss_id = ? AND usuario_id = ? AND sucursal_id = ? AND fecha = ?
           AND tipo_corte = 'cierre'
         LIMIT 1"
    );
    if (!$stmt) {
        $responder('error', 'No se pudo verificar el cierre existente.');
    }
    $stmt->bind_param('iiis', $bssId, $usuarioId, $sucursalId, $fecha);
    $stmt->execute();
    $cierreExistente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cierreExistente) {
        $responder('error', 'Este usuario ya tiene un cierre registrado para esa fecha y sucursal.');
    }
}

/* ── Totales del sistema: se conservan al modificar un cierre ────────── */
$esEdicion = $cierreExistente !== null && $cierreId > 0;

if ($esEdicion) {
    $sistema = [
        'efectivo_bs' => (float) $cierreExistente['efectivo_bs_sistema'],
        'dolares' => (float) $cierreExistente['efectivo_usd_sistema'],
        'pesos' => (float) $cierreExistente['pesos_sistema'],
        'punto' => (float) $cierreExistente['punto_sistema'],
        'biopago' => (float) $cierreExistente['biopago_sistema'],
        'pago_movil' => (float) $cierreExistente['pago_movil_sistema'],
        'transferencia' => (float) $cierreExistente['transferencia_sistema'],
    ];
    $contadoBase = [
        'efectivo_bs' => (float) $cierreExistente['efectivo_bs_contado'],
        'dolares' => (float) $cierreExistente['efectivo_usd_contado'],
        'pesos' => (float) $cierreExistente['pesos_contado'],
        'punto' => (float) $cierreExistente['punto_contado'],
        'biopago' => (float) $cierreExistente['biopago_contado'],
        'pago_movil' => (float) $cierreExistente['pago_movil_contado'],
        'transferencia' => (float) $cierreExistente['transferencia_contado'],
    ];
    $fondoBase = [
        'efectivo_bs' => (float) $cierreExistente['efectivo_bs_fondo'],
        'dolares' => (float) $cierreExistente['efectivo_usd_fondo'],
        'pesos' => (float) $cierreExistente['pesos_fondo'],
    ];
    $observacionesActuales = (string) ($cierreExistente['observaciones'] ?? '');
} else {
    $sistema = caja_totales_sistema($conexion, $bssId, $usuarioId, $fecha);
    $contadoBase = $sistema;
    $fondoBase = null;
    $observacionesActuales = '';
}

$leerMonto = static function (string $campo, float $defecto = 0.0) use ($payload): float {
    if (!array_key_exists($campo, $payload) || $payload[$campo] === '') {
        return $defecto;
    }
    return max(0, (float) $payload[$campo]);
};

/* Fondo recibido al abrir caja (se usa como valor inicial si no se envía). */
$fondoApertura = $fondoBase ?? [
    'efectivo_bs' => 0.0,
    'dolares' => 0.0,
    'pesos' => 0.0,
];
if (!$esEdicion) {
    $stmt = $conexion->prepare(
        "SELECT efectivo_bs_fondo, efectivo_usd_fondo, pesos_fondo
         FROM cortes_de_caja
         WHERE bss_id = ? AND usuario_id = ? AND sucursal_id = ? AND fecha = ?
           AND tipo_corte = 'apertura'
         ORDER BY id DESC
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('iiis', $bssId, $usuarioId, $sucursalId, $fecha);
        $stmt->execute();
        $filaFondo = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($filaFondo) {
            $fondoApertura = [
                'efectivo_bs' => (float) $filaFondo['efectivo_bs_fondo'],
                'dolares' => (float) $filaFondo['efectivo_usd_fondo'],
                'pesos' => (float) $filaFondo['pesos_fondo'],
            ];
        }
    }
}

$contado = [
    'efectivo_bs' => $leerMonto('efectivo_bs_contado', (float) $contadoBase['efectivo_bs']),
    'dolares' => $leerMonto('efectivo_usd_contado', (float) $contadoBase['dolares']),
    'pesos' => $leerMonto('pesos_contado', (float) $contadoBase['pesos']),
    'punto' => $leerMonto('punto_contado', (float) $contadoBase['punto']),
    'biopago' => $leerMonto('biopago_contado', (float) $contadoBase['biopago']),
    'pago_movil' => $leerMonto('pago_movil_contado', (float) $contadoBase['pago_movil']),
    'transferencia' => $leerMonto('transferencia_contado', (float) $contadoBase['transferencia']),
];

$fondo = [
    'efectivo_bs' => $leerMonto('efectivo_bs_fondo', (float) $fondoApertura['efectivo_bs']),
    'dolares' => $leerMonto('efectivo_usd_fondo', (float) $fondoApertura['dolares']),
    'pesos' => $leerMonto('pesos_fondo', (float) $fondoApertura['pesos']),
];

$diferencia = [
    'efectivo_bs' => round($sistema['efectivo_bs'] - $contado['efectivo_bs'], 2),
    'dolares' => round($sistema['dolares'] - $contado['dolares'], 2),
    'pesos' => round($sistema['pesos'] - $contado['pesos'], 2),
    'punto' => round($sistema['punto'] - $contado['punto'], 2),
    'biopago' => round($sistema['biopago'] - $contado['biopago'], 2),
    'pago_movil' => round($sistema['pago_movil'] - $contado['pago_movil'], 2),
    'transferencia' => round($sistema['transferencia'] - $contado['transferencia'], 2),
];

$observaciones = trim((string) ($payload['observaciones'] ?? $observacionesActuales));
$notaAdmin = ($esEdicion ? 'Modificado por administrador' : 'Cierre registrado por administrador')
    . ($adminNombre !== '' ? ' (' . $adminNombre . ')' : '')
    . ' el ' . date('Y-m-d H:i');
$observaciones = $observaciones === '' ? $notaAdmin : $observaciones . ' · ' . $notaAdmin;

if ($esEdicion) {
    $sql = "UPDATE cortes_de_caja SET
                efectivo_bs_contado = ?, efectivo_usd_contado = ?, pesos_contado = ?,
                punto_contado = ?, biopago_contado = ?, pago_movil_contado = ?, transferencia_contado = ?,
                efectivo_bs_fondo = ?, efectivo_usd_fondo = ?, pesos_fondo = ?,
                diferencia_efectivo_bs = ?, diferencia_efectivo_usd = ?, diferencia_pesos = ?,
                diferencia_punto = ?, diferencia_biopago = ?, diferencia_pago_movil = ?, diferencia_transferencia = ?,
                observaciones = ?
            WHERE id = ? AND bss_id = ? AND tipo_corte = 'cierre'
            LIMIT 1";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        $responder('error', 'No se pudo preparar la modificación del cierre: ' . $conexion->error);
    }

    $stmt->bind_param(
        'dddddddddddddddddsii',
        $contado['efectivo_bs'],
        $contado['dolares'],
        $contado['pesos'],
        $contado['punto'],
        $contado['biopago'],
        $contado['pago_movil'],
        $contado['transferencia'],
        $fondo['efectivo_bs'],
        $fondo['dolares'],
        $fondo['pesos'],
        $diferencia['efectivo_bs'],
        $diferencia['dolares'],
        $diferencia['pesos'],
        $diferencia['punto'],
        $diferencia['biopago'],
        $diferencia['pago_movil'],
        $diferencia['transferencia'],
        $observaciones,
        $cierreId,
        $bssId
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        $responder('error', 'No se pudo modificar el cierre: ' . $error);
    }
    $stmt->close();

    $responder('success', 'Cierre de caja actualizado correctamente.', [
        'cierre_id' => $cierreId,
        'usuario' => (string) ($usuario['nombre'] ?? ''),
        'fecha' => $fecha,
        'modo' => 'actualizado',
    ]);
}

$sql = "INSERT INTO cortes_de_caja (
            usuario_id, bss_id, sucursal_id, tipo_corte, fecha,
            efectivo_bs_sistema, efectivo_usd_sistema, pesos_sistema,
            punto_sistema, biopago_sistema, pago_movil_sistema, transferencia_sistema,
            efectivo_bs_contado, efectivo_usd_contado, pesos_contado,
            punto_contado, biopago_contado, pago_movil_contado, transferencia_contado,
            efectivo_bs_fondo, efectivo_usd_fondo, pesos_fondo,
            diferencia_efectivo_bs, diferencia_efectivo_usd, diferencia_pesos,
            diferencia_punto, diferencia_biopago, diferencia_pago_movil, diferencia_transferencia,
            observaciones
        ) VALUES (?, ?, ?, 'cierre', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    $responder('error', 'No se pudo preparar el registro del cierre: ' . $conexion->error);
}

$stmt->bind_param(
    'iiisdddddddddddddddddddddddds',
    $usuarioId,
    $bssId,
    $sucursalId,
    $fecha,
    $sistema['efectivo_bs'],
    $sistema['dolares'],
    $sistema['pesos'],
    $sistema['punto'],
    $sistema['biopago'],
    $sistema['pago_movil'],
    $sistema['transferencia'],
    $contado['efectivo_bs'],
    $contado['dolares'],
    $contado['pesos'],
    $contado['punto'],
    $contado['biopago'],
    $contado['pago_movil'],
    $contado['transferencia'],
    $fondo['efectivo_bs'],
    $fondo['dolares'],
    $fondo['pesos'],
    $diferencia['efectivo_bs'],
    $diferencia['dolares'],
    $diferencia['pesos'],
    $diferencia['punto'],
    $diferencia['biopago'],
    $diferencia['pago_movil'],
    $diferencia['transferencia'],
    $observaciones
);

if (!$stmt->execute()) {
    $error = $stmt->error;
    $stmt->close();
    $responder('error', 'No se pudo registrar el cierre: ' . $error);
}

$cierreId = (int) $conexion->insert_id;
$stmt->close();

$responder('success', 'Cierre de caja registrado correctamente.', [
    'cierre_id' => $cierreId,
    'usuario' => (string) ($usuario['nombre'] ?? ''),
    'fecha' => $fecha,
]);
