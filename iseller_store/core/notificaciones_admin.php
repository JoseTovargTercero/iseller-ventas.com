<?php
/**
 * Notificación in-app para el comercio (campanita del panel).
 *
 * Destino: el usuario configurado en negocio.notificacion_email. Si el negocio no
 * tiene ninguno configurado, se avisa a todos sus administradores activos (nivel 1).
 *
 * Push (Android): cada notificación se empuja a los dispositivos del destinatario.
 * Respaldo: si el usuario configurado no tiene ningún dispositivo registrado, el
 * push se manda a los administradores de la tienda para que el aviso no se pierda
 * (la notificación in-app sigue siendo sólo para el usuario configurado).
 *
 * Uso:
 *   require_once('core/notificaciones_admin.php');   // o ../../core/... desde api/
 *   notificar_admin_tienda($conexion, $bss_id, 'Nueva compra #12', 'Cliente: Ana | Total: $5.00', 'compra');
 */
require_once(__DIR__ . '/../../publico/production/includes/notificaciones.php');
require_once(__DIR__ . '/../../publico/production/includes/push_fcm.php');

if (!function_exists('notificacion_usuario_configurado')) {
    /** Usuario configurado en negocio.notificacion_email, o null si no hay. */
    function notificacion_usuario_configurado(mysqli $conexion, int $bss_id): ?int
    {
        $stmt = $conexion->prepare("SELECT u.id AS uid FROM negocio n INNER JOIN usuarios u ON u.id = CAST(n.notificacion_email AS UNSIGNED) AND u.bss_id = n.id WHERE n.id = ? AND n.notificacion_email IS NOT NULL AND n.notificacion_email != '' LIMIT 1");
        if (!$stmt) return null;

        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($row && (int)$row['uid'] > 0) ? (int)$row['uid'] : null;
    }

    /** Administradores activos (nivel 1) del negocio. */
    function administradores_tienda(mysqli $conexion, int $bss_id): array
    {
        $ids = [];
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE bss_id = ? AND nivel = 1 AND status = 0");
        if (!$stmt) return $ids;

        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['id'];
        }
        $stmt->close();

        return $ids;
    }

    /** ¿El usuario tiene algún dispositivo registrado para push? */
    function push_tiene_dispositivo(mysqli $conexion, string $tipo, int $usuario_id): bool
    {
        $stmt = $conexion->prepare("SELECT 1 FROM push_dispositivos WHERE tipo = ? AND usuario_id = ? LIMIT 1");
        if (!$stmt) return false;

        $stmt->bind_param('si', $tipo, $usuario_id);
        $stmt->execute();
        $tiene = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $tiene;
    }

    function notificar_admin_tienda(mysqli $conexion, int $bss_id, string $titulo, string $cuerpo, string $tipo = 'sistema'): void
    {
        if ($bss_id <= 0) return;

        $configurado = notificacion_usuario_configurado($conexion, $bss_id);

        // Sin usuario configurado: a los administradores activos del negocio
        if ($configurado === null) {
            foreach (administradores_tienda($conexion, $bss_id) as $uid) {
                Notificaciones::crear($conexion, $bss_id, $uid, $titulo, $cuerpo, $tipo);
            }
            return;
        }

        Notificaciones::crear($conexion, $bss_id, $configurado, $titulo, $cuerpo, $tipo);

        if (push_habilitado() && !push_tiene_dispositivo($conexion, 'panel', $configurado)) {
            foreach (administradores_tienda($conexion, $bss_id) as $admin) {
                enviar_push_usuario($conexion, 'panel', $admin, $titulo, $cuerpo, ['tipo' => $tipo]);
            }
        }
    }
}
