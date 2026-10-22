<?php
/**
 * Chat automático al completar una compra.
 *
 * Al cerrar una orden se abre una conversación de chat con el cliente y se le
 * deja un mensaje de la tienda. El texto lo configura cada tienda en
 * publico/production/tienda_configuracion.php y se guarda en la tabla
 * tienda_chat_mensaje_compra (una fila por bss_id).
 */

// Mensaje que recibe el cliente cuando la tienda no configuró uno propio.
function chat_compra_mensaje_default(): string
{
    return '¡Hola! 👋 Recibimos tu pedido y ya lo estamos preparando. Te escribiremos por este chat cuando esté listo. ¡Gracias por comprar con nosotros! 🛒';
}

// Las conexiones del proyecto abren en utf8 (3 bytes) y romperían los emojis;
// esta tabla es utf8mb4, así que se cambia el charset antes de tocarla.
function chat_compra_charset(mysqli $conexion): void
{
    $conexion->set_charset('utf8mb4');
}

// Mensaje configurado por la tienda, o el default si no hay nada guardado.
function chat_compra_mensaje(mysqli $conexion, int $bss_id): string
{
    chat_compra_charset($conexion);
    if ($bss_id > 0) {
        $stmt = $conexion->prepare("SELECT mensaje FROM tienda_chat_mensaje_compra WHERE bss_id = ? LIMIT 1");
        $stmt->bind_param("i", $bss_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && trim((string)$row['mensaje']) !== '') {
            return $row['mensaje'];
        }
    }
    return chat_compra_mensaje_default();
}

/**
 * Crea la conversación y el mensaje automático de compra.
 * La venta ya está cerrada: si el chat falla, se ignora para no romper el checkout.
 */
function chat_compra_crear_conversacion(mysqli $conexion, int $usuario_id, int $bss_id, int $orden_id): void
{
    if ($usuario_id <= 0 || $orden_id <= 0) {
        return;
    }

    $categoria_id = 2; // Soporte post-venta
    $asunto = 'Pedido #' . $orden_id;

    try {
        $mensaje = chat_compra_mensaje($conexion, $bss_id);

        $conexion->begin_transaction();

        $stmt = $conexion->prepare("INSERT INTO tienda_chat_conversaciones (usuario_id, bss_id, pedido_id, categoria_id, asunto, estado, ultimo_mensaje_en) VALUES (?, ?, ?, ?, ?, 'abierta', NOW())");
        $stmt->bind_param("iiiis", $usuario_id, $bss_id, $orden_id, $categoria_id, $asunto);
        $stmt->execute();
        $conversacion_id = $conexion->insert_id;
        $stmt->close();

        $stmt = $conexion->prepare("INSERT INTO tienda_chat_mensajes (conversacion_id, es_admin, mensaje, leido) VALUES (?, 1, ?, 0)");
        $stmt->bind_param("is", $conversacion_id, $mensaje);
        $stmt->execute();
        $stmt->close();

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
    }
}
