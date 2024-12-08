<?php
//require_once('lib/pdf/mpdf.php');
require_once('../../../configurar/configuracion.php');

$query2 = "SELECT * FROM empresa WHERE id=1";
$buscarAlumnos2 = $conexion->query($query2);
if ($buscarAlumnos2->num_rows > 0) {
    while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
        $stockCritico = $filaAlumnos2['stockCritico'];
        $nameempresa = $filaAlumnos2['emp'];
    }
}




$html = '   <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css" media="all" />
  </head>

</h1><img src="../../production/images/logo1-inv-compact.png" height="14px" width="14px" alt=""> MI TIENDA<h1>
</h5>' . $nameempresa . ' - LISTA DE COMPRAS<h5>
<table>
    
 <thead>
<tr>
<th>Producto</th>
<th>Stock</th>
<th>Valor de Compra</th>
<th>Unidades</th>
</tr>
</thead>
 ';
$query22 = "SELECT * FROM productos WHERE stock<='$stockCritico' AND activo='0' ORDER BY nombre ASC";
$buscarAlumnos22 = $conexion->query($query22);
if ($buscarAlumnos22->num_rows > 0) {
    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {

        $var1 = $filaAlumnos22["precio_compra"];
        $var2 = $filaAlumnos22["cantidad_unidades"];

        $nameProducto = $filaAlumnos22["nombre"];
        $nameProducto = strtoupper($nameProducto);
        $html .= '
        <tbody>
<tr>
<td>' . $nameProducto . '</td>
<td>' . $filaAlumnos22["stock"] . '</td>
<td>$ ' . $var1 . '</td>
<td>' . $var2 . '</td>
</tr>
</tbody>
    
';
    }
    $html .= '  
           </table>     ';
}

echo $html;
/*
$mpdf = new mPDF( 'c', 'A4' );
$css = file( 'style.css' );
$mpdf->writeHTML( $css, 1 );
$mpdf->writeHTML( $html );
$mpdf->Output( 'Etiquetas.pdf', 'I' )
*/
