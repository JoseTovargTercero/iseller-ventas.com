<?php
/**
 * PosAuth — tokens de larga duración para el POS nativo de Windows (Bearer).
 *
 * A diferencia de AppAuth (vale de un solo uso de 90 s para abrir el WebView),
 * este token vive días: la caja puede quedar sin internet y volver a conectar
 * sin que el cajero tenga que loguearse otra vez.
 *
 * Solo se guarda el SHA-256 del token. El token en claro existe una sola vez,
 * en la respuesta del login.
 */
class PosAuth
{
    /** 30 días. La app, además, limita a 2 días el uso sin conexión (regla del negocio). */
    const TTL = 2592000;

    private static $tablaAsegurada = false;

    /**
     * Emite un token nuevo para el usuario y devuelve el token en claro.
     */
    public static function emitir($conexion, int $userId, int $bssId, ?int $sucursalId): string
    {
        self::asegurarTabla($conexion);

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expira = date('Y-m-d H:i:s', time() + self::TTL);

        $stmt = $conexion->prepare(
            "INSERT INTO pos_tokens (token_hash, id_user, bss_id, sucursal_id, creado, expira, revocado)
             VALUES (?, ?, ?, ?, NOW(), ?, 0)"
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo emitir el token');
        }
        $stmt->bind_param('siiis', $hash, $userId, $bssId, $sucursalId, $expira);
        $stmt->execute();
        $stmt->close();

        return $token;
    }

    /**
     * Devuelve los datos del usuario dueño del token Bearer, o null si no sirve.
     * Valida: token existente, no revocado, no expirado, usuario activo.
     */
    public static function usuarioDesdeBearer($conexion): ?array
    {
        $token = self::tokenRecibido();
        if ($token === '') {
            return null;
        }

        self::asegurarTabla($conexion);

        $hash = hash('sha256', $token);
        $stmt = $conexion->prepare(
            "SELECT t.id_user, t.bss_id, t.sucursal_id, t.expira, t.revocado,
                    u.nombre, u.usuario, u.nivel, u.status
             FROM pos_tokens t
             INNER JOIN usuarios u ON u.id = t.id_user
             WHERE t.token_hash = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $hash);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$fila) {
            return null;
        }
        if ((int)$fila['revocado'] === 1) {
            return null;
        }
        if (strtotime((string)$fila['expira']) < time()) {
            return null;
        }
        if ((int)$fila['status'] === 1) {
            return null; // usuario inactivo
        }

        return [
            'id_user' => (int)$fila['id_user'],
            'bss_id' => (int)$fila['bss_id'],
            'sucursal_id' => $fila['sucursal_id'] === null ? null : (int)$fila['sucursal_id'],
            'nombre' => $fila['nombre'],
            'usuario' => $fila['usuario'],
            'nivel' => (int)$fila['nivel'],
        ];
    }

    /**
     * Token Bearer del encabezado Authorization. Cadena vacía si no hay.
     */
    private static function tokenRecibido(): string
    {
        if (preg_match('/Bearer\s+([a-f0-9]{64})/i', self::encabezadoAutorizacion(), $m)) {
            return strtolower($m[1]);
        }
        return '';
    }

    private static function encabezadoAutorizacion(): string
    {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $clave) {
            if (!empty($_SERVER[$clave])) {
                return (string)$_SERVER[$clave];
            }
        }
        if (function_exists('getallheaders')) {
            foreach ((array)getallheaders() as $nombre => $valor) {
                if (strcasecmp((string)$nombre, 'Authorization') === 0) {
                    return (string)$valor;
                }
            }
        }
        return '';
    }

    /**
     * Crea la tabla si falta. El proyecto no tiene migraciones, así que cada
     * entorno la obtiene en el primer uso.
     */
    private static function asegurarTabla($conexion): void
    {
        if (self::$tablaAsegurada) {
            return;
        }
        $conexion->query(
            "CREATE TABLE IF NOT EXISTS pos_tokens (
                token_hash  CHAR(64) NOT NULL,
                id_user     INT(11) NOT NULL,
                bss_id      INT(11) NOT NULL,
                sucursal_id INT(11) NULL,
                creado      DATETIME NOT NULL,
                expira      DATETIME NOT NULL,
                revocado    TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (token_hash),
                KEY ix_pos_tokens_user (id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
        );
        self::$tablaAsegurada = true;
    }
}
