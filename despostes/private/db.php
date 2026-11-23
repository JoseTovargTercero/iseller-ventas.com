<?php
declare(strict_types=1);

/**
 * Conexión y utilidades de autenticación. Requiere lib.php.
 */

function ds_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = ds_config()['db'] ?? [];
    $host = (string) ($db['host'] ?? '127.0.0.1');
    $name = (string) ($db['name'] ?? 'desposte_membresia');
    $user = (string) ($db['user'] ?? 'root');
    $pass = (string) ($db['pass'] ?? '');

    $esLocal = in_array($host, ['127.0.0.1', 'localhost'], true);
    if (!$esLocal && ($user === 'root' || $pass === '')) {
        throw new RuntimeException('Configura credenciales de base de datos de producción');
    }

    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4',
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function ds_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function ds_token_crear(PDO $pdo, string $usuarioId): string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $dias = max(1, (int) (ds_config()['auth_token_days'] ?? 30));
    $expira = date('Y-m-d H:i:s', time() + $dias * 86400);

    $stmt = $pdo->prepare(
        'INSERT INTO auth_tokens (usuario_id, token_hash, creado, expira)
         VALUES (:usuario, :hash, NOW(), :expira)'
    );
    $stmt->execute([
        ':usuario' => $usuarioId,
        ':hash' => $hash,
        ':expira' => $expira,
    ]);

    return $token;
}

function ds_bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $nombre => $valor) {
            if (strcasecmp($nombre, 'Authorization') === 0) {
                $header = $valor;
                break;
            }
        }
    }
    if ($header === '') {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    }
    if (preg_match('/Bearer\s+(.+)/i', $header, $m)) {
        return trim($m[1]);
    }
    return trim((string) ($_SERVER['HTTP_X_AUTH_TOKEN'] ?? ($_POST['token'] ?? '')));
}

function ds_usuario_autenticado(): ?array
{
    $token = ds_bearer_token();
    if ($token === '') {
        return null;
    }

    $hash = hash('sha256', $token);
    $stmt = ds_db()->prepare(
        'SELECT u.id, u.nombre, u.email, u.premium_hasta, t.expira
         FROM auth_tokens t
         INNER JOIN usuarios u ON u.id = t.usuario_id
         WHERE t.token_hash = :hash
         LIMIT 1'
    );
    $stmt->execute([':hash' => $hash]);
    $fila = $stmt->fetch();

    if (!$fila || strtotime((string) $fila['expira']) < time()) {
        return null;
    }

    unset($fila['expira']);
    return $fila;
}

function ds_requiere_sesion(): array
{
    $usuario = ds_usuario_autenticado();
    if ($usuario === null) {
        ds_json(['ok' => false, 'mensaje' => 'Sesión vencida. Inicia sesión de nuevo.'], 401);
    }
    return $usuario;
}

function ds_input(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function ds_email_valido(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
} 
