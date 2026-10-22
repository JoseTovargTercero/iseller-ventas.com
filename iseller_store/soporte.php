<?php
/**
 * Soporte: redirige al chat operativo del cliente (chat.php).
 *
 * La página anterior usaba assets/js/chat.js y endpoints legacy que consultaban
 * tablas inexistentes (chat_conversaciones/chat_mensajes), por lo que el chat
 * de soporte no funcionaba. chat.php usa tienda_chat_* y es el flujo vigente.
 */
require_once('core/check_maintenance.php');
require_once('core/db.php');
require_once('core/session.php');

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$qs = trim((string)($_SERVER['QUERY_STRING'] ?? ''));
header('Location: chat.php' . ($qs !== '' ? '?' . $qs : ''));
exit;
