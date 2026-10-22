<?php
/**
 * Puente de auto-login para la app nativa.
 *
 * GET /login/app_autologin.php?token=<64 hex>
 *
 * Consume el token de un solo uso emitido por /api/auth_login.php, crea la sesión web
 * (deja la cookie PHPSESSID en el WebView) y redirige al sistema. NO se pega a esta URL
 * dos veces con el mismo token.
 */

header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/../configurar/configuracion.php';
require_once __DIR__ . '/../configurar/AppAuth.php';
require_once __DIR__ . '/../configurar/MembresiaHelper.php';

$fallo = function (string $motivo) {
    header('Location: ../login.php?app_error=' . urlencode($motivo));
    exit;
};

$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
$data  = AppAuth::consume($token);

if (!$data || empty($data['uid'])) {
    $fallo('token');
}

$uid = (int)$data['uid'];
$stmt = $conexion->prepare("SELECT id, status, nombre, bss_id, nivel, id_sucursal, darkMode FROM usuarios WHERE id = ? LIMIT 1");
if (!$stmt) {
    $fallo('token');
}
$stmt->bind_param('i', $uid);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario || (int)$usuario['status'] === 1) {
    $fallo('usuario');
}

// ── Crear la sesión (espejo de login/guardar.php) ──────────────────────────────
$duracion = 10800;
$esHttps  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $duracion,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', $duracion);
    session_start();
}

session_regenerate_id(true);
$_SESSION = [];

$_SESSION['id']     = $usuario['id'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['nivel']  = $usuario['nivel'];

if ((int)$usuario['nivel'] === 2) {
    $_SESSION['sucursal'] = $usuario['id_sucursal'];
} else {
    $sucursalAuto = null;
    $stmtS = $conexion->prepare("SELECT id FROM sucursales WHERE bss_id = ?");
    if ($stmtS) {
        $bssVal = (int)$usuario['bss_id'];
        $stmtS->bind_param('i', $bssVal);
        $stmtS->execute();
        $resS = $stmtS->get_result();
        if ($resS && $resS->num_rows === 1) {
            $sucursalAuto = (int)$resS->fetch_assoc()['id'];
        }
        $stmtS->close();
    }
    if ($sucursalAuto !== null) {
        $_SESSION['sucursal'] = $sucursalAuto;
    } else {
        unset($_SESSION['sucursal']);
    }
}

$_SESSION['darkMode'] = ((int)$usuario['darkMode'] === 1) ? 'SI' : 'NO';
$_SESSION['bss_id']   = $usuario['bss_id'];
$_SESSION['validate'] = 'ok';

$redirect = 'publico/production/ventas.php';

// Permisos para empleados (nivel 2) — mismo mapa que login/guardar.php
if ((int)$usuario['nivel'] !== 1) {
    $subdirectorios = [
        '11' => ['creditos_cliente.php'],
        '8'  => ['ficha.php'],
    ];
    $permisos = [];
    $stmtP = $conexion->prepare("SELECT sup.id_item_menu, menu.dir FROM users_permisos AS sup LEFT JOIN menu ON menu.id = sup.id_item_menu WHERE id_user = ?");
    if ($stmtP) {
        $stmtP->bind_param('i', $usuario['id']);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        while ($rowP = $resP->fetch_assoc()) {
            $permisos[$rowP['id_item_menu']] = $rowP['dir'];
            if (isset($subdirectorios[$rowP['id_item_menu']])) {
                foreach ($subdirectorios[$rowP['id_item_menu']] as $sub) {
                    $permisos[] = $sub;
                }
            }
        }
        $stmtP->close();
    }
    $_SESSION['permisos'] = $permisos;

    if (!in_array('ventas.php', $permisos, true)) {
        $primer = reset($permisos);
        if ($primer) {
            $redirect = 'publico/production/' . $primer;
        }
    }
}

// Membresía vencida → paywall (igual que el login web)
$membresia = MembresiaHelper::obtenerEstado($conexion, $usuario['bss_id']);
if (!empty($membresia['esta_vencida'])) {
    $redirect = 'publico/production/membresia_renovacion.php';
}

header('Location: ../' . $redirect);
exit;
