<?php
/**
 * Helpers comunes de los endpoints del POS nativo de Windows (api/pos_*.php).
 *
 * Mismas convenciones que el resto de api/: JSON, X-Api-Key del .env,
 * rate limit por IP, errores genéricos y consultas preparadas.
 * Además de la API key, estos endpoints exigen un token Bearer de usuario
 * (configurar/PosAuth.php).
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// El catálogo lleva todos los clientes del negocio (decenas de miles): sin gzip
// pesa varios MB y el POS no alcanza a bajarlo. El POS pide y descomprime gzip.
if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
    ini_set('zlib.output_compression', '1');
    ini_set('zlib.output_compression_level', '6');
}

set_exception_handler(function ($e) {
    // Rastro local para diagnosticar; al cliente solo se le da un error genérico.
    @file_put_contents(
        sys_get_temp_dir() . '/iseller_pos_error.log',
        date('c') . '  ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno'], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once __DIR__ . '/../configurar/configuracion.php';
require_once __DIR__ . '/../configurar/PosAuth.php';

function salir(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function exigirMetodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        salir(405, ['ok' => false, 'error' => 'Método no permitido']);
    }
}

/** Igual que el resto de api/: fail-closed si no hay POS_API_KEY configurada. */
function exigirApiKey(): void
{
    $esperada = trim($_ENV['POS_API_KEY'] ?? '');
    $recibida = trim($_SERVER['HTTP_X_API_KEY'] ?? '');

    if ($esperada === '') {
        if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
            salir(503, ['ok' => false, 'error' => 'API no configurada']);
        }
        return;
    }

    if ($recibida === '' || !hash_equals($esperada, $recibida)) {
        salir(401, ['ok' => false, 'error' => 'No autorizado']);
    }
}

function limitar(string $ambito, int $max, int $ventanaSegundos): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $archivo = sys_get_temp_dir() . '/iseller_pos_' . $ambito . '_' . md5($ip) . '.json';
    $ahora = time();

    $estado = ['inicio' => $ahora, 'n' => 0];
    if (is_file($archivo)) {
        $previo = json_decode((string)@file_get_contents($archivo), true);
        if (is_array($previo) && isset($previo['inicio'], $previo['n']) && ($ahora - (int)$previo['inicio']) < $ventanaSegundos) {
            $estado = ['inicio' => (int)$previo['inicio'], 'n' => (int)$previo['n']];
        }
    }
    $estado['n']++;
    @file_put_contents($archivo, json_encode($estado));

    if ($estado['n'] > $max) {
        salir(429, ['ok' => false, 'error' => 'Demasiadas peticiones']);
    }
}

/** Cuerpo de la petición como array, venga JSON o formulario. */
function entrada(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);
    return is_array($json) ? $json : $_POST;
}

/** Usuario del token Bearer o 401. */
function exigirSesion($conexion): array
{
    $usuario = PosAuth::usuarioDesdeBearer($conexion);
    if (!$usuario) {
        salir(401, ['ok' => false, 'error' => 'Sesión no válida o expirada']);
    }
    return $usuario;
}

/**
 * Tasas del negocio, en el mismo formato que espera CalculadoraPrecios:
 * tasas numéricas + data_monedas tal cual (la app comprueba sus claves).
 */
function tasasDe($conexion, int $bssId): array
{
    $tasas = [
        'peso_dolar' => 0.0,
        'peso_bolivar' => 0.0,
        'dolar_bolivar' => 0.0,
        'bolivar_peso' => 0.0,
        'bcv' => 0.0,
        'data_monedas' => [],
    ];

    $stmt = $conexion->prepare(
        "SELECT pesoDolar, peso_bolivar, bolivar_peso, DolarBolivar
         FROM cambio WHERE bss_id = ? ORDER BY id DESC LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('i', $bssId);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($fila) {
            $tasas['peso_dolar'] = (float)$fila['pesoDolar'];
            $tasas['peso_bolivar'] = (float)$fila['peso_bolivar'];
            $tasas['bolivar_peso'] = (float)$fila['bolivar_peso'];
            $tasas['dolar_bolivar'] = (float)$fila['DolarBolivar'];
        }
    }

    $resBcv = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC LIMIT 1");
    if ($resBcv && $resBcv->num_rows > 0) {
        $tasas['bcv'] = (float)$resBcv->fetch_assoc()['valor'];
    }

    $stmt = $conexion->prepare("SELECT tasas FROM cambios_mostrados WHERE bss_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $bssId);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($fila && !empty($fila['tasas'])) {
            $monedas = json_decode((string)$fila['tasas'], true);
            if (is_array($monedas)) {
                $tasas['data_monedas'] = $monedas;
            }
        }
    }

    return $tasas;
}

/**
 * Mismo criterio que publico/production/ventas.php: nivel 1 siempre puede;
 * un empleado solo con el permiso "Venta de productos al mayor".
 */
function puedeVenderAlMayor($conexion, int $userId, int $nivel): bool
{
    if ($nivel === 1) {
        return true;
    }

    $stmt = $conexion->prepare(
        "SELECT 1 FROM users_permisos up
         INNER JOIN menu m ON m.id = up.id_item_menu
         WHERE up.id_user = ? AND m.nombre = 'Venta de productos al mayor'
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $tiene = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $tiene;
}

/** Hora del servidor en ISO-8601 con offset, para corregir relojes desfasados. */
function servidorEn(): string
{
    return date('c');
}
