<?php
require_once('../../../configurar/configuracion.php'); // Tu configuración de BD
require_once('TCPDF-main/tcpdf.php'); // Asegúrate de que la ruta de TCPDF sea correcta

// Obtener datos de la empresa
$query2 = "SELECT * FROM empresa WHERE id=1";
$buscarAlumnos2 = $conexion->query($query2);
if ($buscarAlumnos2->num_rows > 0) {
    while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
        $stockCritico = $filaAlumnos2['stockCritico'];
        $nameempresa = $filaAlumnos2['emp'];
    }
}

// Crear objeto TCPDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Mi Tienda');
$pdf->SetTitle('Lista de Compras');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

// Estilo CSS para el PDF
$css = '
<style>
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid black; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    h1 { text-align: center; }
    h5 { text-align: center; }
</style>';

// Construcción del HTML
$html = $css . '
<h1><img src="../../production/images/logo1-inv-compact.png" height="20px"> iSeller</h1>
<h5> LISTA DE COMPRAS</h5>

<table>
<thead>
<tr>
<th>Producto</th>
<th>Stock</th>
<th>Valor de Compra</th>
<th>Unidades</th>
<th>Proveedor</th>
</tr>
</thead>
<tbody>';

// Obtener productos con stock crítico
$query22 = "SELECT * FROM productos WHERE activo='0' ORDER BY proveedor ASC";
$buscarAlumnos22 = $conexion->query($query22);
if ($buscarAlumnos22->num_rows > 0) {
    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
        $stock = $filaAlumnos22['stock'];
        // convierte $stock a float
        $stock = (float)$stock;

        if ($stock <= $stockCritico) {
            $nameProducto = strtoupper($filaAlumnos22["nombre"]);
            $html .= '<tr>
        <td>' . $nameProducto . '</td>
        <td>' . $filaAlumnos22["stock"] . '</td>
        <td>$ ' . number_format($filaAlumnos22["precio_compra"], 2) . '</td>
        <td>' . $filaAlumnos22["cantidad_unidades"] . '</td>
        <td>' . $filaAlumnos22["proveedor"] . '</td>
        </tr>';
        }
    }
}

$html .= '</tbody></table>';

// Agregar HTML al PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Descargar el PDF
$pdf->Output('lista_compras.pdf', 'D'); // 'D' para descargar, 'I' para mostrar en el navegador
