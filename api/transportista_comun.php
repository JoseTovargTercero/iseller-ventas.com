<?php
/**
 * Helpers comunes del módulo Transportistas (iSeller POS).
 *
 * Mismas convenciones que el resto de api/: JSON, X-Api-Key del .env,
 * rate limit por IP, errores genéricos y consultas preparadas.
 *
 * Las tablas se crean solas la primera vez que se usa el módulo
 * (CREATE TABLE IF NOT EXISTS), así el despliegue no requiere migración manual.
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

function salirJson(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function exigirMetodo(string $metodo): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $metodo) {
        salirJson(405, ['ok' => false, 'error' => 'Método no permitido']);
    }
}

/** Igual que el resto de api/: fail-closed si no hay POS_API_KEY configurada. */
function exigirApiKey(): void
{
    $esperada = trim($_ENV['POS_API_KEY'] ?? '');
    $recibida = trim($_SERVER['HTTP_X_API_KEY'] ?? '');

    if ($esperada === '') {
        if (trim($_ENV['POS_API_ALLOW_NO_KEY'] ?? '') !== '1') {
            salirJson(503, ['ok' => false, 'error' => 'API no configurada']);
        }
        return;
    }
    if ($recibida === '' || !hash_equals($esperada, $recibida)) {
        salirJson(401, ['ok' => false, 'error' => 'No autorizado']);
    }
}

function limitar(string $ambito, int $max, int $ventanaSegundos): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $archivo = sys_get_temp_dir() . '/iseller_transp_' . $ambito . '_' . md5($ip) . '.json';
    $ahora = time();
    $estado = ['inicio' => $ahora, 'n' => 0];
    if (is_file($archivo)) {
        $previo = json_decode((string)@file_get_contents($archivo), true);
        if (is_array($previo) && isset($previo['inicio'], $previo['n'])
            && ($ahora - (int)$previo['inicio']) < $ventanaSegundos) {
            $estado = ['inicio' => (int)$previo['inicio'], 'n' => (int)$previo['n']];
        }
    }
    $estado['n']++;
    @file_put_contents($archivo, json_encode($estado));
    if ($estado['n'] > $max) {
        salirJson(429, ['ok' => false, 'error' => 'Demasiadas peticiones. Espera unos minutos.']);
    }
}

/** Cuerpo de la petición como array, venga JSON o formulario. */
function entrada(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode((string)$raw, true);
    return is_array($json) ? $json : $_POST;
}

// ── Tablas ────────────────────────────────────────────────────────────────────

function asegurarTablas($conexion): void
{
    static $hecho = false;
    if ($hecho) {
        return;
    }
    $hecho = true;

    $conexion->query(
        "CREATE TABLE IF NOT EXISTS transportistas (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre VARCHAR(120) NOT NULL,
            cedula VARCHAR(12) NOT NULL,
            telefono VARCHAR(20) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            deliverys TINYINT(1) NOT NULL DEFAULT 1,
            carreras TINYINT(1) NOT NULL DEFAULT 0,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_transportista_cedula (cedula)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conexion->query(
        "CREATE TABLE IF NOT EXISTS transportista_tokens (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            transportista_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL,
            expira_en DATETIME NOT NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_transportista_token (token_hash),
            KEY idx_transportista_tokens (transportista_id),
            CONSTRAINT fk_transportista_tokens FOREIGN KEY (transportista_id)
                REFERENCES transportistas (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    $conexion->query(
        "CREATE TABLE IF NOT EXISTS transportista_servicios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            transportista_id INT UNSIGNED NOT NULL,
            tipo ENUM('delivery','carrera') NOT NULL DEFAULT 'delivery',
            bss_id INT UNSIGNED DEFAULT NULL,
            codigo_ruta VARCHAR(20) DEFAULT NULL,
            numero_pedido VARCHAR(40) DEFAULT NULL,
            cliente VARCHAR(150) DEFAULT NULL,
            direccion VARCHAR(255) DEFAULT NULL,
            telefono VARCHAR(20) DEFAULT NULL,
            lat DECIMAL(10,7) DEFAULT NULL,
            lng DECIMAL(10,7) DEFAULT NULL,
            estado ENUM('asignado','aceptado','en_camino','completado','cancelado','rechazado') NOT NULL DEFAULT 'asignado',
            notas VARCHAR(255) DEFAULT NULL,
            creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_transportista_servicio_pedido (tipo, numero_pedido),
            KEY idx_transportista_servicios (transportista_id, tipo, estado),
            CONSTRAINT fk_transportista_servicios FOREIGN KEY (transportista_id)
                REFERENCES transportistas (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function normalizarCedula(string $cedula): string
{
    return preg_replace('/\D/', '', $cedula);
}

function normalizarTelefono(string $telefono): string
{
    return preg_replace('/\D/', '', $telefono);
}

function perfilTransportista(array $row): array
{
    return [
        'id'        => (int)$row['id'],
        'nombre'    => (string)$row['nombre'],
        'cedula'    => (string)$row['cedula'],
        'telefono'  => (string)$row['telefono'],
        'deliverys' => (bool)(int)$row['deliverys'],
        'carreras'  => (bool)(int)$row['carreras'],
    ];
}

function emitirToken($conexion, int $transportistaId): string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expira = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 días

    $stmt = $conexion->prepare(
        "INSERT INTO transportista_tokens (transportista_id, token_hash, expira_en) VALUES (?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param('iss', $transportistaId, $hash, $expira);
        $stmt->execute();
        $stmt->close();
    }
    return $token;
}

function tokenBearer(): string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    if ($auth === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $clave => $valor) {
            if (strcasecmp($clave, 'Authorization') === 0) {
                $auth = $valor;
                break;
            }
        }
    }
    if (preg_match('/Bearer\s+([A-Fa-f0-9]{64})/', $auth, $m)) {
        return strtolower($m[1]);
    }
    return '';
}

/** Transportista dueño del token Bearer, o null si no es válido. */
function transportistaDesdeToken($conexion): ?array
{
    $token = tokenBearer();
    if ($token === '') {
        return null;
    }
    $hash = hash('sha256', $token);
    $stmt = $conexion->prepare(
        "SELECT t.*
           FROM transportista_tokens tk
           INNER JOIN transportistas t ON t.id = tk.transportista_id
          WHERE tk.token_hash = ? AND tk.expira_en > NOW() AND t.activo = 1
          LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
