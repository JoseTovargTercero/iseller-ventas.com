<?php
/**
 * Helper: Crear notificación para el cliente de la tienda
 * Llamar desde cualquier endpoint que requiera notificar al usuario.
 *
 * Uso:
 *   require_once('crear_notificacion_cliente.php');
 *   notificar_cliente($conexion_store, $usuario_id, 'Nuevo mensaje', 'El admin respondió', 'chat', 'chat.php');
 */
if (!function_exists('notificar_cliente')) {
    function notificar_cliente(mysqli $conexion, int $usuario_id, string $titulo, string $cuerpo, string $tipo = 'sistema', ?string $enlace = null): void {
        if ($usuario_id <= 0) return;
        $stmt = $conexion->prepare(
            "INSERT INTO tienda_cliente_notificaciones (usuario_id, titulo, cuerpo, tipo, enlace) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issss", $usuario_id, $titulo, $cuerpo, $tipo, $enlace);
        $stmt->execute();
        $stmt->close();
    }
}
