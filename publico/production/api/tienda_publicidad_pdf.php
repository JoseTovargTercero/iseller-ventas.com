<?php
/**
 * API: genera un PDF A4 (listo para imprimir) con el póster de publicidad física.
 * POST multipart: "imagen" (JPEG generado en el navegador) + "nombre" (slug, sólo para el nombre del archivo).
 */
ob_start();

require_once(__DIR__ . '/../../../configurar/configuracion.php');
require_once(__DIR__ . '/../../../configurar/session.php');

function pdf_error(int $codigo, string $mensaje): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

// Mismo acceso que la página de publicidad: cualquier usuario con sesión de negocio.
if (empty($_SESSION['bss_id'])) {
    pdf_error(403, 'Acceso denegado');
}

$archivo = $_FILES['imagen'] ?? null;
if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    pdf_error(400, 'Imagen inválida.');
}
if (($archivo['size'] ?? 0) > 15 * 1024 * 1024) {
    pdf_error(400, 'La imagen es demasiado grande.');
}
$info = @getimagesize($archivo['tmp_name']);
if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') {
    pdf_error(400, 'Imagen inválida.');
}

$nombre = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['nombre'] ?? '');
if ($nombre === '') {
    $nombre = 'publicidad';
}

// Los errores de TCPDF se lanzan como excepción para responder texto plano en vez de HTML.
define('K_TCPDF_THROW_EXCEPTION_ERROR', true);
require_once(__DIR__ . '/../../build/pdf/TCPDF-main/tcpdf.php');

try {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('iSeller');
    $pdf->SetTitle('Publicidad - ' . $nombre);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    $pdf->Image($archivo['tmp_name'], 0, 0, 210, 297, 'JPEG', '', '', true, 300, '', false, false, 0, false, false, false);
    $pdf->Output('publicidad-' . $nombre . '.pdf', 'D');
} catch (Throwable $e) {
    pdf_error(500, 'No se pudo generar el PDF.');
}
