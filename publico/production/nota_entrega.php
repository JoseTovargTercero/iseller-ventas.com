<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once(__DIR__ . '/../build/pdf/TCPDF-main/tcpdf.php');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$cliente = trim((string)($_POST['cliente'] ?? ''));
$moneda = strtoupper(trim((string)($_POST['moneda'] ?? '')));
$productos = json_decode((string)($_POST['productos'] ?? ''), true);

$monedas = [
    'USD' => ['campo' => 'price', 'etiqueta' => 'USD'],
    'COP' => ['campo' => 'pricePeso', 'etiqueta' => 'COP'],
    'BS'  => ['campo' => 'priceBolivar', 'etiqueta' => 'Bs'],
];

if (!isset($monedas[$moneda]) || !is_array($productos) || count($productos) === 0) {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    exit('Datos inválidos para la nota de entrega.');
}

$campoPrecio = $monedas[$moneda]['campo'];
$etiqueta = $monedas[$moneda]['etiqueta'];
$filas = [];
$total = 0.0;

foreach (array_slice($productos, 0, 500) as $producto) {
    if (!is_array($producto)) {
        continue;
    }

    $nombre = trim((string)($producto['name'] ?? $producto['nombre'] ?? ''));
    $cantidad = (float)($producto['qty'] ?? 0);

    // El precio unitario se redondea ANTES de multiplicar, para que el
    // subtotal coincida exactamente con lo que se ve en el PDF.
    $precio = (float)($producto[$campoPrecio] ?? 0);
    $precio = $moneda === 'COP' ? round($precio) : round($precio, 2);

    if ($nombre === '' || $cantidad <= 0 || $precio < 0) {
        continue;
    }

    $subtotal = $moneda === 'COP'
        ? round($precio * $cantidad)
        : round($precio * $cantidad, 2);
    $total += $subtotal;

    $filas[] = [
        'nombre' => $nombre,
        'cantidad' => $cantidad,
        'precio' => $precio,
        'subtotal' => $subtotal,
    ];
}

if (count($filas) === 0) {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    exit('La nota de entrega no tiene productos válidos.');
}

$total = $moneda === 'COP' ? round($total) : round($total, 2);

function formatoNota($valor, $moneda)
{
    if ($moneda === 'COP') {
        return number_format((float)$valor, 0, ',', '.') . ' Cop';
    }
    if ($moneda === 'BS') {
        return number_format((float)$valor, 2, ',', '.') . ' Bs';
    }
    return '$' . number_format((float)$valor, 2, ',', '.');
}

function cantidadNota($valor)
{
    $texto = number_format((float)$valor, 2, ',', '.');
    return rtrim(rtrim($texto, '0'), ',');
}

$clienteHtml = htmlspecialchars($cliente !== '' ? $cliente : 'Cliente', ENT_QUOTES, 'UTF-8');
$sucursal = trim((string)($_POST['sucursal'] ?? ''));
$sucursalHtml = htmlspecialchars($sucursal !== '' ? $sucursal : '—', ENT_QUOTES, 'UTF-8');

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
            <span class="titulo">NOTA DE ENTREGA</span><br>
            <span class="subtitulo">Documento de entrega de mercancía</span>
        </td>
        <td width="30%" style="text-align:right;vertical-align:top;">
            <span class="etiqueta">FECHA</span><br>
            <span class="valor">' . date('d/m/Y') . '</span>
        </td>
    </tr>
</table>
<div style="border-top:2px solid #2dd4a0;margin:8px 0 16px 0;height:1px;"></div>

<table width="100%" border="1" cellpadding="10" cellspacing="0" style="margin-bottom:16px;">
    <tr>
        <td width="60%" style="background-color:#f7f9fa;text-align:left;vertical-align:top;">
            <span class="etiqueta">NOMBRE DEL CLIENTE</span><br>
            <span class="valor">' . $clienteHtml . '</span>
        </td>
        <td width="40%" style="background-color:#f7f9fa;text-align:left;vertical-align:top;">
            <span class="etiqueta">SUCURSAL</span><br>
            <span class="valor">' . $sucursalHtml . '</span>
        </td>
    </tr>
</table>

<table width="100%" border="1" cellpadding="7" cellspacing="0">
    <thead>
        <tr>
            <th width="12%" style="text-align:center;">Cantidad</th>
            <th width="44%" style="text-align:left;">Concepto</th>
            <th width="22%" style="text-align:right;">Precio individual (' . $etiqueta . ')</th>
            <th width="22%" style="text-align:right;">Subtotal (' . $etiqueta . ')</th>
        </tr>
    </thead>
    <tbody>';

$indice = 0;
foreach ($filas as $fila) {
    $fondo = $indice % 2 === 0 ? '#ffffff' : '#f8fafb';
    $html .= '
        <tr>
            <td width="12%" style="background-color:' . $fondo . ';text-align:center;">' . cantidadNota($fila['cantidad']) . '</td>
            <td width="44%" style="background-color:' . $fondo . ';text-align:left;">' . htmlspecialchars($fila['nombre'], ENT_QUOTES, 'UTF-8') . '</td>
            <td width="22%" style="background-color:' . $fondo . ';text-align:right;">' . formatoNota($fila['precio'], $moneda) . '</td>
            <td width="22%" style="background-color:' . $fondo . ';text-align:right;">' . formatoNota($fila['subtotal'], $moneda) . '</td>
        </tr>';
    $indice++;
}

$html .= '
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" width="78%" style="background-color:#eef2f5;text-align:right;font-weight:bold;">Total</td>
            <td width="22%" style="background-color:#eef2f5;text-align:right;font-weight:bold;">' . formatoNota($total, $moneda) . '</td>
        </tr>
    </tfoot>
</table>

<div style="height:18px;"></div>
<div style="border-top:1px solid #d9dee3;padding-top:8px;text-align:center;font-size:8px;color:#6b7785;">
    Documento generado desde iseller-tiendas.com/inventario
</div>';

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('iSeller POS');
$pdf->SetAuthor('iSeller POS');
$pdf->SetTitle('Nota de entrega');
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
$pdf->Output('nota_entrega_' . date('Ymd_His') . '.pdf', 'I');
