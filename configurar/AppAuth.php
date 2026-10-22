<?php
/**
 * AppAuth — emisión y consumo de tokens de un solo uso para el login desde la app nativa.
 *
 * El token nunca es la sesión: es un vale corto (TTL 90 s) que la app canjea contra
 * login/app_autologin.php, el cual crea la sesión web y la deja en la cookie del WebView.
 * Se guarda hasheado en un directorio privado fuera del webroot y se consume una sola vez.
 */
class AppAuth
{
    const TTL = 90; // segundos de validez

    private static function dir(): string
    {
        $dir = sys_get_temp_dir() . '/iseller_app_tokens';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        return $dir;
    }

    private static function ruta(string $token): string
    {
        return self::dir() . '/' . hash('sha256', $token) . '.json';
    }

    /**
     * Emite un token nuevo ligado al usuario. Devuelve el token en claro (solo se guarda el hash).
     */
    public static function issue(int $userId, array $extra = []): string
    {
        self::gc();
        $token = bin2hex(random_bytes(32));
        $data  = array_merge(['uid' => $userId, 'exp' => time() + self::TTL], $extra);
        @file_put_contents(self::ruta($token), json_encode($data), LOCK_EX);
        return $token;
    }

    /**
     * Consume un token (uso único). Devuelve los datos o null si es inválido/expirado.
     */
    public static function consume(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $file = self::ruta($token);
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string)@file_get_contents($file), true);
        @unlink($file); // un solo uso, aunque el token sea válido
        if (!is_array($data) || empty($data['uid']) || (int)($data['exp'] ?? 0) < time()) {
            return null;
        }
        return $data;
    }

    /**
     * Limpieza oportunista de tokens caducados.
     */
    private static function gc(): void
    {
        $dir = self::dir();
        $expiran = time() - self::TTL;
        foreach ((array)@glob($dir . '/*.json') as $f) {
            $m = @filemtime($f);
            if ($m !== false && $m < $expiran) {
                @unlink($f);
            }
        }
    }
}
