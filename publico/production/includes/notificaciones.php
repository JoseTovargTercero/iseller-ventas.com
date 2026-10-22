<?php
/**
 * Clase para gestionar notificaciones in-app.
 * Escalable: soporta múltiples canales (panel, email, push) y tipos (compra, stock, sistema).
 */

class Notificaciones
{
    /**
     * Crear una notificación.
     * @return int|null ID insertado o null en error
     */
    public static function crear(mysqli $conexion, int $bss_id, int $usuario_id, string $titulo, string $cuerpo, string $tipo = 'general', string $canal = 'panel'): ?int
    {
        $stmt = $conexion->prepare(
            "INSERT INTO notificaciones (bss_id, usuario_id, titulo, cuerpo, tipo, canal) VALUES (?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return null;

        $stmt->bind_param('iissss', $bss_id, $usuario_id, $titulo, $cuerpo, $tipo, $canal);
        $ok = $stmt->execute();
        $id = $ok ? $conexion->insert_id : null;
        $stmt->close();

        // Push a los dispositivos del usuario (no-op si FCM no está configurado)
        require_once(__DIR__ . '/push_fcm.php');
        enviar_push_usuario($conexion, 'panel', $usuario_id, $titulo, $cuerpo, ['tipo' => $tipo, 'id' => (string)$id]);

        return $id;
    }

    /**
     * Obtener las últimas notificaciones de un usuario.
     */
    public static function paraUsuario(mysqli $conexion, int $usuario_id, int $limite = 20): array
    {
        $stmt = $conexion->prepare(
            "SELECT id, titulo, cuerpo, tipo, canal, leida, created_at
             FROM notificaciones
             WHERE usuario_id = ?
             ORDER BY created_at DESC
             LIMIT ?"
        );
        if (!$stmt) return [];

        $stmt->bind_param('ii', $usuario_id, $limite);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Contar notificaciones no leídas de un usuario.
     */
    public static function noLeidas(mysqli $conexion, int $usuario_id): int
    {
        $stmt = $conexion->prepare(
            "SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0"
        );
        if (!$stmt) return 0;

        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_row();
        $stmt->close();
        return (int)($row[0] ?? 0);
    }

    /**
     * Marcar una notificación como leída.
     */
    public static function marcarLeida(mysqli $conexion, int $id): bool
    {
        $stmt = $conexion->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Marcar todas las notificaciones de un usuario como leídas.
     */
    public static function marcarTodasLeidas(mysqli $conexion, int $usuario_id): bool
    {
        $stmt = $conexion->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ? AND leida = 0");
        if (!$stmt) return false;

        $stmt->bind_param('i', $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
