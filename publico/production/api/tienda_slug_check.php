<?php
/**
 * API: comprueba si un slug de tienda esta disponible (ni reservado ni usado por otra tienda).
 * GET "slug" -> {"ok": true|false}
 */
require_once('../includes/slug.php');
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

header('Content-Type: application/json');

$slug = slug_normalizar($_GET['slug'] ?? '');
$bss_id = (int)($_SESSION['bss_id'] ?? 0);

echo json_encode([
    'ok' => $slug !== '' && !slug_reservado($slug) && !slug_duplicado($conexion, $slug, $bss_id),
]);
