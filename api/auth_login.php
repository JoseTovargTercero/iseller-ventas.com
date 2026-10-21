<?php
/**
 * API — Login nativo (iSeller POS)  →  POST /api/auth_login.php
 *
 * Valida usuario/contraseña como el login web y devuelve un token de un solo uso.
 * La app abre en el WebView la URL `session_url`, que crea la sesión web y redirige
 * al sistema SIN pasar por el login web.
 *
 * Autenticación del cliente: cabecera `X-Api-Key` (POS_API_KEY del .env).
 * Contrato completo: API_LOGIN_APP.md
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

set_exception_handler(function () {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../configurar/configuracion.php';
require_once __DIR__ . '/../configurar/AppAuth.php';
require_once __DIR__ . '/../configurar/MembresiaHelper.php';

function salir(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Solo POST ──────────────────────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    salir(405, ['ok' => false, 'error' => 'Método no permitido']);
}

// ── Rate limit por IP: 15 intentos / 5 min ─────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlFile = sys_get_temp_dir() . '/iseller_applogin_' . md5($ip) . '.json';
$rlMax = 15;
$rlWindow = 300;
$ahora = time();
$rl = ['inicio' => $ahora, 'n' => 0];
if (is_file($rlFile)) {
    $prev = json_decode((string)@file_get_contents($rlFile), true);
    if (is_array($prev) && isset($prev['inicio'], $prev['n']) && ($ahora - (int)$prev['inicio']) < $rlWindow) {
        $rl = ['inicio' => (int)$prev['inicio'], 'n' => (int)$prev['n']];
    }
}
$rl['n']++;
@file_put_contents($rlFile, json_encode($rl));
if ($rl['n'] > $rlMax) {
    salir(429, ['ok' => false, 'error' => 'Demasiados intentos. Espera unos minutos.']);
}

// ── API key (fail-closed) ──────────────────────────────────────────────────────
$apiKeyEsperada = trim($_ENV['POS_API_KEY'] ?? '');
$apiKeyRecibida = trim($_SERVER['HTTP_X_API_KEY'] ?? '');
if ($apiKeyEsperada === '') {
    if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
        salir(503, ['ok' => false, 'error' => 'API no configurada']);
    }
} elseif ($apiKeyRecibida === '' || !hash_equals($apiKeyEsperada, $apiKeyRecibida)) {
    salir(401, ['ok' => false, 'error' => 'No autorizado']);
}

// ── Entrada: JSON o formulario ─────────────────────────────────────────────────
$raw = file_get_contents('php://input');
$json = json_decode((string)$raw, true);
$in = is_array($json) ? $json : $_POST;

$usuario = trim((string)($in['usuario'] ?? $in['login'] ?? ''));
$password = (string)($in['password'] ?? $in['contrasena'] ?? '');

if ($usuario === '' || $password === '') {
    salir(400, ['ok' => false, 'error' => 'Faltan datos']);
}

// ── Validar credenciales (misma lógica que login/guardar.php) ──────────────────
$stmt = $conexion->prepare("SELECT id, status, nombre, bss_id, nivel, contrasena, id_sucursal, darkMode FROM usuarios WHERE usuario = ? LIMIT 1");
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('s', $usuario);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'app-android';

$registrarLog = function (int $exito, $nivel, string $mensaje) use ($conexion, $usuario, $ip, $userAgent) {
    $stmt = $conexion->prepare("INSERT INTO user_log (usuario, exito, nivel, ip_usuario, user_agent, mensaje) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('sissss', $usuario, $exito, $nivel, $ip, $userAgent, $mensaje);
        $stmt->execute();
        $stmt->close();
    }
};

if (!$u) {
    $registrarLog(0, null, 'App login: usuario no encontrado');
    salir(401, ['ok' => false, 'error' => 'Credenciales incorrectas']);
}

if ((int)$u['status'] === 1) {
    $registrarLog(0, $u['nivel'], 'App login: usuario inactivo');
    salir(403, ['ok' => false, 'error' => 'Usuario inactivo por incumplimiento de pago']);
}

if (!password_verify($password, $u['contrasena'])) {
    $registrarLog(0, $u['nivel'], 'App login: contraseña incorrecta');
    salir(401, ['ok' => false, 'error' => 'Credenciales incorrectas']);
}

// ── Datos de sesión: sucursal ──────────────────────────────────────────────────
$bssId = (int)$u['bss_id'];
$sucursalSesion = null;
if ((int)$u['nivel'] === 2) {
    $sucursalSesion = (int)$u['id_sucursal'];
} else {
    $stmtS = $conexion->prepare("SELECT id FROM sucursales WHERE bss_id = ?");
    if ($stmtS) {
        $stmtS->bind_param('i', $bssId);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        if ($resS && $resS->num_rows === 1) {
            $sucursalSesion = (int)$resS->fetch_assoc()['id'];
        }
        $stmtS->close();
    }
}

$sucursalNombre = null;
if ($sucursalSesion) {
    $stmtSn = $conexion->prepare("SELECT UPPER(nombre) AS nombre FROM sucursales WHERE id = ? AND bss_id = ?");
    if ($stmtSn) {
        $stmtSn->bind_param('ii', $sucursalSesion, $bssId);
        $stmtSn->execute();
        $sucursalNombre = $stmtSn->get_result()->fetch_assoc()['nombre'] ?? null;
        $stmtSn->close();
    }
}

// ── Redirect destino (mismo criterio que el login web) ─────────────────────────
$redirect = 'publico/production/ventas.php';
if ((int)$u['nivel'] !== 1) {
    $permisos = [];
    $stmtP = $conexion->prepare("SELECT sup.id_item_menu, menu.dir FROM users_permisos AS sup LEFT JOIN menu ON menu.id = sup.id_item_menu WHERE id_user = ?");
    if ($stmtP) {
        $stmtP->bind_param('i', $u['id']);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        while ($rowP = $resP->fetch_assoc()) {
            if (!empty($rowP['dir'])) {
                $permisos[$rowP['id_item_menu']] = $rowP['dir'];
            }
        }
        $stmtP->close();
    }
    if (!in_array('ventas.php', $permisos, true)) {
        $primer = reset($permisos);
        if ($primer) {
            $redirect = 'publico/production/' . $primer;
        }
    }
}

$membresia = MembresiaHelper::obtenerEstado($conexion, $bssId);
if (!empty($membresia['esta_vencida'])) {
    $redirect = 'publico/production/membresia_renovacion.php';
}

// ── Token de un solo uso + URL de canje ────────────────────────────────────────
$token = AppAuth::issue((int)$u['id'], ['bss' => $bssId]);

$esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$base = ($esHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$appPath = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$sessionUrl = $base . $appPath . '/login/app_autologin.php?token=' . $token;

$registrarLog(1, $u['nivel'], 'App login exitoso');

salir(200, [
    'ok'          => true,
    'token'       => $token,
    'expira_en'   => AppAuth::TTL,
    'session_url' => $sessionUrl,
    'redirect'    => $redirect,
    'usuario'     => [
        'id'      => (int)$u['id'],
        'nombre'  => $u['nombre'],
        'usuario' => $usuario,
        'nivel'   => (int)$u['nivel'],
    ],
    'negocio'     => [
        'bss_id'          => $bssId,
        'sucursal'        => $sucursalSesion,
        'sucursal_nombre' => $sucursalNombre,
    ],
    'membresia'   => [
        'tipo'            => $membresia['tipo'],
        'estado'          => $membresia['estado'],
        'esta_vencida'    => (bool)$membresia['esta_vencida'],
        'dias_restantes'  => (int)$membresia['dias_restantes'],
        'fecha_fin'       => $membresia['fecha_fin'],
    ],
    'dark_mode'   => ((int)$u['darkMode'] === 1),
]);
