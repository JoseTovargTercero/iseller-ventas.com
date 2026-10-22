<?php
/**
 * API: genera un PDF A4 (listo para imprimir) con el póster de publicidad física.
 * POST: "imagen" (data URL JPEG generado en el navegador) + "nombre" (slug, sólo para el nombre del archivo).
 */
ob_start();

require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

function pdf_error(int $codigo, string $mensaje): void
{
    ob_end_clean();
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

// Mismo acceso que la página de publicidad: cualquier usuario con sesión de negocio.
if (empty($_SESSION['bss_id'])) {
    pdf_error(403, 'Acceso denegado');
}

$prefijo = 'data:image/jpeg;base64,';
$imagen = $_POST['imagen'] ?? '';
if (strpos($imagen, $prefijo) !== 0) {
    pdf_error(400, 'Imagen inválida.');
}
if (strlen($imagen) > 30 * 1024 * 1024) {
    pdf_error(400, 'La imagen es demasiado grande.');
}
$datos = base64_decode(substr($imagen, strlen($prefijo)), true);
if ($datos === false || strlen($datos) < 100) {
    pdf_error(400, 'Imagen inválida.');
}

$nombre = preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['nombre'] ?? '');
if ($nombre === '') {
    $nombre = 'publicidad';
}

require_once('../../build/pdf/TCPDF-main/tcpdf.php');

ob_end_clean(); // TCPDF no debe recibir salida previa

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('iSeller');
$pdf->SetTitle('Publicidad - ' . $nombre);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();
$pdf->Image('@' . $datos, 0, 0, 210, 297, 'JPEG', '', '', true, 300, '', false, false, 0, false, false, false);
$pdf->Output('publicidad-' . $nombre . '.pdf', 'D');
