<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once('../../configurar/_asignaciones_sucursales.php');
require_once(__DIR__ . '/../build/pdf/TCPDF-main/tcpdf.php');

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$usuarioId = (int) ($_SESSION['id'] ?? 0);
$nivel = (int) ($_SESSION['nivel'] ?? 0);
$sucursalSesion = (int) ($_SESSION['sucursal'] ?? 0);
$esAdmin = $nivel === 1;
$asignacionId = (int) ($_GET['id'] ?? 0);

if (!$esAdmin && !in_array('asignaciones.php', $_SESSION['permisos'] ?? [], true)) {
    http_response_code(403);
    exit('No autorizado');
}

$resultado = asig_detalle_asignacion($conexion, $bssId, $usuarioId, $esAdmin, $sucursalSesion, $asignacionId);
if ($resultado['status'] !== 'ok') {
    http_response_code(404);
    exit(htmlspecialchars($resultado['msg'], ENT_QUOTES, 'UTF-8'));
}

$asignacion = $resultado['data']['asignacion'];
$detalles = $resultado['data']['detalles'];
$cuenta = $resultado['data']['cuenta'];
$pagos = $resultado['data']['pagos'];

$abonado = 0.0;
foreach ($pagos as $pago) {
    $abonado += (float) $pago['monto_usd'];
}
$montoOriginal = $cuenta ? (float) $cuenta['monto_original'] : (float) $asignacion['total'];
$saldo = max(0, round($montoOriginal - $abonado, 2));
$excedente = max(0, round($abonado - $montoOriginal, 2));
$estadoDeuda = $abonado <= 0 ? 'PENDIENTE' : ($abonado < $montoOriginal ? 'PARCIAL' : 'PAGADA');

function pdfEsc($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function pdfMoney($valor): string
{
    return '$' . number_format((float) $valor, 2, '.', ',');
}

function pdfQty($valor): string
{
    $texto = number_format((float) $valor, 4, '.', ',');
    $texto = rtrim(rtrim($texto, '0'), ',');
    return $texto === '' ? '0' : $texto;
}

$html = '
<style>
    .titulo { font-size: 20px; font-weight: bold; color: #1f2933; }
    .subtitulo { font-size: 9px; color: #6b7785; }
    .etiqueta { font-size: 8px; color: #6b7785; }
    .valor { font-size: 12px; font-weight: bold; color: #1f2933; }
    th { font-size: 9px; font-weight: bold; color: #ffffff; background-color: #2f3b45; }
    td { font-size: 10px; color: #1f2933; }
</style>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width="70%" style="text-align:left;vertical-align:top;">
            <span class="titulo">ASIGNACIÓN DE MERCANCÍA</span><br>
            <span class="subtitulo">Documento ' . pdfEsc($asignacion['numero_documento']) . '</span>
        </td>
        <td width="30%" style="text-align:right;vertical-align:top;">
            <span class="etiqueta">FECHA</span><br>
            <span class="valor">' . pdfEsc(date('d/m/Y', strtotime($asignacion['fecha']))) . '</span>
        </td>
    </tr>
</table>
<div style="border-top:2px solid #2dd4a0;margin:8px 0 16px 0;height:1px;"></div>

<table width="100%" border="1" cellpadding="9" cellspacing="0" style="margin-bottom:12px;">
    <tr>
        <td width="50%" style="background-color:#f7f9fa;">
            <span class="etiqueta">SUCURSAL DE ORIGEN</span><br>
            <span class="valor">' . pdfEsc($asignacion['origen_nombre']) . '</span>
        </td>
        <td width="50%" style="background-color:#f7f9fa;">
            <span class="etiqueta">SUCURSAL DE DESTINO</span><br>
            <span class="valor">' . pdfEsc($asignacion['destino_nombre']) . '</span>
        </td>
    </tr>
    <tr>
        <td>
            <span class="etiqueta">ESTADO</span><br>
            <span class="valor">' . pdfEsc($asignacion['estado']) . '</span>
        </td>
        <td>
            <span class="etiqueta">REGISTRADO POR</span><br>
            <span class="valor">' . pdfEsc($asignacion['usuario_nombre']) . '</span>
        </td>
    </tr>
    <tr>
        <td>
            <span class="etiqueta">INVENTARIO ORIGEN</span><br>
            <span class="valor">' . ((int) $asignacion['descuenta_origen'] === 1 ? 'Descontado' : 'No descontado') . '</span>
        </td>
        <td>
            <span class="etiqueta">INVENTARIO DESTINO</span><br>
            <span class="valor">' . ((int) $asignacion['suma_destino'] === 1 ? 'Sumado' : 'No sumado') . '</span>
        </td>
    </tr>
</table>

<table width="100%" border="1" cellpadding="7" cellspacing="0">
    <thead>
        <tr>
            <th width="79mm" style="text-align:left;">Producto</th>
            <th width="29mm" style="text-align:right;">Cantidad</th>
            <th width="36mm" style="text-align:right;">Costo unitario</th>
            <th width="36mm" style="text-align:right;">Subtotal</th>
        </tr>
    </thead>
    <tbody>';

$indice = 0;
foreach ($detalles as $detalle) {
    $fondo = $indice % 2 === 0 ? '#ffffff' : '#f8fafb';
    $html .= '<tr>
        <td width="79mm" style="background-color:' . $fondo . ';text-align:left;">' . pdfEsc($detalle['producto_nombre']) . '</td>
        <td width="29mm" style="background-color:' . $fondo . ';text-align:right;">' . pdfQty($detalle['cantidad']) . '</td>
        <td width="36mm" style="background-color:' . $fondo . ';text-align:right;">' . pdfMoney($detalle['costo_unitario']) . '</td>
        <td width="36mm" style="background-color:' . $fondo . ';text-align:right;">' . pdfMoney($detalle['subtotal']) . '</td>
    </tr>';
    $indice++;
}

$html .= '
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" width="144mm" style="background-color:#eef2f5;text-align:right;font-weight:bold;">Total asignado</td>
            <td width="36mm" style="background-color:#eef2f5;text-align:right;font-weight:bold;">' . pdfMoney($asignacion['total']) . '</td>
        </tr>
    </tfoot>
</table>';

if ($cuenta) {
    $html .= '
<div style="height:14px;"></div>
<table width="100%" border="1" cellpadding="9" cellspacing="0">
    <tr>
        <td width="25%"><span class="etiqueta">MONTO ORIGINAL</span><br><span class="valor">' . pdfMoney($montoOriginal) . '</span></td>
        <td width="25%"><span class="etiqueta">ABONADO</span><br><span class="valor">' . pdfMoney($abonado) . '</span></td>
        <td width="25%"><span class="etiqueta">SALDO</span><br><span class="valor">' . pdfMoney($saldo) . '</span></td>
        <td width="25%"><span class="etiqueta">ESTADO DEUDA</span><br><span class="valor">' . pdfEsc($estadoDeuda) . '</span></td>
    </tr>
</table>';

    if ($excedente > 0) {
        $html .= '<p style="font-size:10px;color:#1f2933;"><strong>Excedente:</strong> ' . pdfMoney($excedente) . '</p>';
    }
} else {
    $notaFinanciera = $asignacion['estado'] === 'APLICADO'
        ? 'Información financiera reservada a la sucursal deudora.'
        : 'La obligación económica se genera al aplicar la asignación.';
    $html .= '<p style="font-size:10px;color:#6b7785;">' . $notaFinanciera . '</p>';
}

if (trim((string) $asignacion['observaciones']) !== '') {
    $html .= '<div style="height:10px;"></div><div style="font-size:10px;color:#1f2933;"><strong>Observaciones:</strong> ' . nl2br(pdfEsc($asignacion['observaciones'])) . '</div>';
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('iSeller POS');
$pdf->SetAuthor('iSeller POS');
$pdf->SetTitle('Asignación ' . $asignacion['numero_documento']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);
$pdf->SetDrawColor(210, 216, 222);
$pdf->SetTextColor(31, 41, 51);
$pdf->SetLineWidth(0.2);
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('asignacion_' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $asignacion['numero_documento']) . '.pdf', 'I');
