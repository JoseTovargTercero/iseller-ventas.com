<?php
declare(strict_types=1);

/**
 * Utilidades compartidas del backend. No se sirve directamente.
 */

function ds_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        date_default_timezone_set($config['timezone'] ?? 'UTC');

        $dataDir = $config['data_dir'];
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0775, true);
        }
    }
    return $config;
}

function ds_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ds_archivo(string $nombre): string
{
    return ds_config()['data_dir'] . '/' . $nombre . '.json';
}

function ds_leer(string $nombre): array
{
    $archivo = ds_archivo($nombre);
    if (!is_file($archivo)) {
        return [];
    }

    $fp = @fopen($archivo, 'r');
    if (!$fp) {
        return [];
    }

    flock($fp, LOCK_SH);
    $contenido = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $datos = json_decode((string) $contenido, true);
    return is_array($datos) ? $datos : [];
}

/**
 * Lee, modifica y guarda un archivo JSON con bloqueo exclusivo.
 * El callback recibe el array por referencia y puede devolver un valor.
 */
function ds_actualizar(string $nombre, callable $cambio)
{
    $archivo = ds_archivo($nombre);
    $fp = @fopen($archivo, 'c+');
    if (!$fp) {
        throw new RuntimeException('No se pudo abrir el almacenamiento');
    }

    flock($fp, LOCK_EX);
    $contenido = stream_get_contents($fp);
    $datos = json_decode((string) $contenido, true);
    if (!is_array($datos)) {
        $datos = [];
    }

    $resultado = $cambio($datos);

    rewind($fp);
    ftruncate($fp, 0);
    fwrite($fp, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $resultado;
}

function ds_dispositivo_valido(string $id): bool
{
    return (bool) preg_match('/^[A-Za-z0-9-]{8,64}$/', $id);
}

function ds_codigo_normalizar(string $codigo): string
{
    return strtoupper(preg_replace('/\s+/', '', trim($codigo)));
}

function ds_codigo_valido(string $codigo): bool
{
    return (bool) preg_match('/^[A-Z0-9-]{3,32}$/', $codigo);
}

function ds_generar_codigo(string $prefijo): string
{
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $parte = static function (int $largo) use ($caracteres): string {
        $texto = '';
        for ($i = 0; $i < $largo; $i++) {
            $texto .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        return $texto;
    };

    return strtoupper($prefijo) . '-' . $parte(4) . '-' . $parte(4);
}

function ds_hoy(): string
{
    return date('Y-m-d');
}

function ds_ahora(): string
{
    return date('Y-m-d H:i:s');
}

function ds_sumar_dias(int $dias): string
{
    return date('Y-m-d', strtotime('+' . $dias . ' days'));
}

function ds_codigo_activo_para(array $codigos, string $dispositivo): ?array
{
    $encontrado = null;
    $hoy = ds_hoy();

    foreach ($codigos as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (($item['dispositivo'] ?? '') !== $dispositivo) {
            continue;
        }
        $expira = $item['expira_en'] ?? null;
        if (!$expira || $expira < $hoy) {
            continue;
        }
        if ($encontrado === null || $expira > ($encontrado['expira_en'] ?? '')) {
            $encontrado = $item;
        }
    }

    return $encontrado;
}

function ds_escape(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function ds_largo(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function ds_recortar(string $texto, int $maximo): string
{
    return function_exists('mb_substr')
        ? mb_substr($texto, 0, $maximo, 'UTF-8')
        : substr($texto, 0, $maximo);
}

function ds_csrf(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function ds_csrf_valido(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals((string) $_SESSION['csrf'], (string) $_POST['csrf']);
}
