<?php
/**
 * API — Login del POS nativo de Windows  →  POST /api/pos_login.php
 *
 * Valida usuario/contraseña como el login web y devuelve un token Bearer de
 * larga duración, más la sucursal, permisos y configuración que la caja
 * necesita para operar sin conexión.
 *
 * Autenticación: X-Api-Key (POS_API_KEY del .env).
 * Contrato: PLAN.md §5 y api/README.md.
 */

require_once __DIR__ . '/_pos_comun.php';
require_once __DIR__ . '/../configurar/MembresiaHelper.php';

exigirMetodo('POST');
exigirApiKey();
limitar('login', 15, 300);

$in = entrada();
$usuario = trim((string)($in['usuario'] ?? ''));
$password = (string)($in['password'] ?? '');
$sucursalPedida = isset($in['sucursal_id']) && $in['sucursal_id'] !== '' ? (int)$in['sucursal_id'] : null;

if ($usuario === '' || $password === '') {
    salir(400, ['ok' => false, 'error' => 'Faltan datos']);
}

$stmt = $conexion->prepare(
    "SELECT id, status, nombre, bss_id, nivel, contrasena, id_sucursal
     FROM usuarios WHERE usuario = ? LIMIT 1"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('s', $usuario);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u) {
    salir(401, ['ok' => false, 'error' => 'Credenciales incorrectas']);
}
if ((int)$u['status'] === 1) {
    salir(403, ['ok' => false, 'error' => 'Usuario inactivo por incumplimiento de pago']);
}
if (!password_verify($password, (string)$u['contrasena'])) {
    salir(401, ['ok' => false, 'error' => 'Credenciales incorrectas']);
}

$bssId = (int)$u['bss_id'];

// ── Sucursal ───────────────────────────────────────────────────────────────────
$sucursales = [];
$stmtS = $conexion->prepare("SELECT id, UPPER(nombre) AS nombre FROM sucursales WHERE bss_id = ? ORDER BY id");
if ($stmtS) {
    $stmtS->bind_param('i', $bssId);
    $stmtS->execute();
    $resS = $stmtS->get_result();
    while ($filaS = $resS->fetch_assoc()) {
        $sucursales[] = ['id' => (int)$filaS['id'], 'nombre' => (string)$filaS['nombre']];
    }
    $stmtS->close();
}

$sucursal = null;
if ($sucursalPedida !== null) {
    foreach ($sucursales as $s) {
        if ($s['id'] === $sucursalPedida) {
            $sucursal = $s;
            break;
        }
    }
}
if ($sucursal === null && (int)$u['nivel'] === 2 && (int)$u['id_sucursal'] > 0) {
    foreach ($sucursales as $s) {
        if ($s['id'] === (int)$u['id_sucursal']) {
            $sucursal = $s;
            break;
        }
    }
}
if ($sucursal === null && count($sucursales) === 1) {
    $sucursal = $sucursales[0];
}
if ($sucursal === null) {
    salir(409, [
        'ok' => false,
        'error' => 'Selecciona una sucursal',
        'sucursales' => $sucursales,
    ]);
}

$membresia = MembresiaHelper::obtenerEstado($conexion, $bssId);
if (!empty($membresia['esta_vencida'])) {
    salir(403, ['ok' => false, 'error' => 'Membresía vencida', 'renovacion' => true]);
}

// ── Permisos ───────────────────────────────────────────────────────────────────
$permisos = [];
if ((int)$u['nivel'] !== 1) {
    $stmtP = $conexion->prepare("SELECT id_item_menu FROM users_permisos WHERE id_user = ?");
    if ($stmtP) {
        $stmtP->bind_param('i', $u['id']);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        while ($filaP = $resP->fetch_assoc()) {
            $permisos[] = (int)$filaP['id_item_menu'];
        }
        $stmtP->close();
    }
}

// ── Configuración del negocio ──────────────────────────────────────────────────
$configuracion = ['tickets' => 0, 'bs_ticket' => 0, 'cortes_caja' => 0, 'registro_clientes' => 0];
$stmtC = $conexion->prepare(
    "SELECT tickets, bs_ticket, cortes_caja, registro_clientes
     FROM configuracion WHERE bss_id = ? ORDER BY id DESC LIMIT 1"
);
if ($stmtC) {
    $stmtC->bind_param('i', $bssId);
    $stmtC->execute();
    $filaC = $stmtC->get_result()->fetch_assoc();
    $stmtC->close();
    if ($filaC) {
        $configuracion = [
            'tickets' => (int)$filaC['tickets'],
            'bs_ticket' => (int)$filaC['bs_ticket'],
            'cortes_caja' => (int)$filaC['cortes_caja'],
            'registro_clientes' => (int)$filaC['registro_clientes'],
        ];
    }
}

$token = PosAuth::emitir($conexion, (int)$u['id'], $bssId, (int)$sucursal['id']);

salir(200, [
    'ok' => true,
    'token' => $token,
    'expira_en' => PosAuth::TTL,
    'usuario' => [
        'id' => (int)$u['id'],
        'nombre' => (string)$u['nombre'],
        'usuario' => $usuario,
        'nivel' => (int)$u['nivel'],
    ],
    'bss_id' => $bssId,
    'sucursal' => $sucursal,
    'permisos' => $permisos,
    'puede_vender_al_mayor' => puedeVenderAlMayor($conexion, (int)$u['id'], (int)$u['nivel']),
    'configuracion' => $configuracion,
    'servidor_en' => servidorEn(),
]);
