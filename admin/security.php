<?php
/**
 * AdminSecurity — Módulo de seguridad centralizado para el panel de administración iSeller
 */

// 1. Configuración de sesión segura antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Zona horaria consistente con el resto de la aplicación (configurar/configuracion.php)
date_default_timezone_set('America/Manaus');

// 2. Cabeceras de seguridad HTTP
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

class AdminSecurity
{
    // Configuración de rate limiting
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_MINUTES = 15;
    const INACTIVITY_TIMEOUT_SECONDS = 7200; // 2 horas

    /**
     * Obtiene o genera la conexión a la base de datos
     */
    public static function getConexion()
    {
        static $conexion = null;
        if ($conexion !== null && !$conexion->connect_error) {
            return $conexion;
        }

        $archivoEnv = dirname(__DIR__) . '/.env';
        $archivoAlt = dirname(__DIR__) . '/env';
        if (!file_exists($archivoEnv) && file_exists($archivoAlt)) {
            $archivoEnv = $archivoAlt;
        }

        if (file_exists($archivoEnv)) {
            $lineas = file($archivoEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lineas as $linea) {
                if (strpos(trim($linea), '#') === 0) continue;
                $partes = explode('=', $linea, 2);
                if (count($partes) === 2) {
                    $k = trim($partes[0]);
                    $v = trim($partes[1]);
                    if (!isset($_ENV[$k])) $_ENV[$k] = $v;
                }
            }
        }

        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $name = $_ENV['DB_NAME'] ?? 'iseller';
        $host = $_ENV['DB_HOST'] ?? 'localhost';

        $conexion = new mysqli($host, $user, $pass, $name);
        $conexion->set_charset('utf8');

        if ($conexion->connect_error) {
            die('<p style="color:red;font-family:monospace;padding:30px;">Error de conexión a la base de datos.</p>');
        }

        return $conexion;
    }

    /**
     * Valida credenciales contra la base de datos (tabla admin_usuarios)
     * utilizando password_verify() sobre hashes Bcrypt.
     * Cero contraseñas hardcodeadas en código fuente.
     */
    public static function validarCredenciales($user, $pass)
    {
        if (empty($user) || empty($pass)) {
            return false;
        }

        $conexion = self::getConexion();

        // 1. Validar contra la tabla admin_usuarios en base de datos
        $stmt = $conexion->prepare("SELECT id, usuario, nombre, password_hash, rol, activo FROM admin_usuarios WHERE usuario = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $admin = $res->fetch_assoc()) {
                $stmt->close();

                // Verificar si la cuenta está activa y la contraseña coincide con el hash
                if ((int)$admin['activo'] === 1 && password_verify($pass, $admin['password_hash'])) {
                    // Actualizar timestamp de último acceso
                    $stmtUpd = $conexion->prepare("UPDATE admin_usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                    if ($stmtUpd) {
                        $stmtUpd->bind_param("i", $admin['id']);
                        $stmtUpd->execute();
                        $stmtUpd->close();
                    }

                    return [
                        'valid'   => true,
                        'id'      => (int)$admin['id'],
                        'usuario' => $admin['usuario'],
                        'nombre'  => $admin['nombre'],
                        'rol'     => $admin['rol']
                    ];
                }
            } else {
                $stmt->close();
            }
        }

        // 2. Soporte opcional para variable de entorno en .env (ADMIN_USER y ADMIN_PASS_HASH)
        $envUser = $_ENV['ADMIN_USER'] ?? null;
        $envPassHash = $_ENV['ADMIN_PASS_HASH'] ?? null;

        if ($envUser && $envPassHash && hash_equals($envUser, $user) && password_verify($pass, $envPassHash)) {
            return [
                'valid'   => true,
                'id'      => 0,
                'usuario' => $envUser,
                'nombre'  => 'Administrador del Sistema',
                'rol'     => 'superadmin'
            ];
        }

        return false;
    }

    /**
     * Verifica si la IP actual está bloqueada por demasiados intentos fallidos
     */
    public static function checkRateLimit()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $cacheFile = sys_get_temp_dir() . '/iseller_admin_lock_' . md5($ip) . '.json';

        if (!file_exists($cacheFile)) {
            return ['blocked' => false, 'remaining_wait' => 0, 'attempts' => 0];
        }

        $data = json_decode(file_get_contents($cacheFile), true);
        if (!$data || !is_array($data)) {
            return ['blocked' => false, 'remaining_wait' => 0, 'attempts' => 0];
        }

        $intentos = (int)($data['attempts'] ?? 0);
        $ultimoIntento = (int)($data['last_time'] ?? 0);
        $tiempoTranscurrido = time() - $ultimoIntento;
        $tiempoBloqueo = self::LOCKOUT_MINUTES * 60;

        // Si ya expiró el tiempo de bloqueo, limpiar
        if ($tiempoTranscurrido > $tiempoBloqueo) {
            @unlink($cacheFile);
            return ['blocked' => false, 'remaining_wait' => 0, 'attempts' => 0];
        }

        if ($intentos >= self::MAX_LOGIN_ATTEMPTS) {
            $minutosRestantes = ceil(($tiempoBloqueo - $tiempoTranscurrido) / 60);
            return [
                'blocked' => true,
                'remaining_wait' => max(1, $minutosRestantes),
                'attempts' => $intentos
            ];
        }

        return ['blocked' => false, 'remaining_wait' => 0, 'attempts' => $intentos];
    }

    /**
     * Registra un intento fallido de login
     */
    public static function recordFailedLogin()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $cacheFile = sys_get_temp_dir() . '/iseller_admin_lock_' . md5($ip) . '.json';

        $intentos = 1;
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && isset($data['attempts'])) {
                $intentos = (int)$data['attempts'] + 1;
            }
        }

        $payload = [
            'attempts' => $intentos,
            'last_time' => time(),
            'ip' => $ip
        ];

        file_put_contents($cacheFile, json_encode($payload));
        return $intentos;
    }

    /**
     * Limpia los intentos fallidos al iniciar sesión exitosamente
     */
    public static function resetRateLimit()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $cacheFile = sys_get_temp_dir() . '/iseller_admin_lock_' . md5($ip) . '.json';
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    /**
     * Genera o retorna el token CSRF para la sesión
     */
    public static function getCsrfToken()
    {
        if (empty($_SESSION['admin_csrf_token'])) {
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf_token'];
    }

    /**
     * Valida el token CSRF
     */
    public static function verifyCsrfToken($token)
    {
        if (empty($token) || empty($_SESSION['admin_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['admin_csrf_token'], $token);
    }

    /**
     * Verifica si hay una sesión válida de admin activa
     */
    public static function isLoggedIn()
    {
        if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            return false;
        }

        // Comprobación de tiempo de inactividad
        if (isset($_SESSION['admin_last_activity'])) {
            if ((time() - $_SESSION['admin_last_activity']) > self::INACTIVITY_TIMEOUT_SECONDS) {
                self::logout();
                return false;
            }
        }

        // Actualizar último timestamp de actividad
        $_SESSION['admin_last_activity'] = time();
        return true;
    }

    /**
     * Guardia de seguridad estricta para páginas protegidas
     */
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header('Location: index.php?error=expired');
            exit;
        }
    }

    /**
     * Cierra la sesión de admin y destruye cookies
     */
    public static function logout()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        @session_destroy();
    }
}
