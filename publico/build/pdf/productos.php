<?php
require_once('TCPDF-main/tcpdf.php');
require_once('../../../configurar/configuracion.php');
require("../../../configurar/_tasas_cambio.php");


$html = '   <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css" media="all" />
  </head>

</h1><img src="../../production/images/logo1-inv-96.png" height="14px" width="14px" alt=""> MI TIENDA<h1>
</h5>LISTA DE PRECIOS<h5>
     <div class="table-responsive">
                      <table class="table table-striped  jambo_table bulk_action">
                        <thead>
                          <tr class="headings">
                            <th class="column-title">#</th>
                            <th class="column-title">Nombre </th>
                            <th class="column-title">Precio <small>($)</small></th>
                            <th class="column-title">COP</th>
                            <th class="column-title">BS</th>
                           
                          </tr>
                        </thead>
                           <tbody>
 ';


$query6 = $conexion->query("SELECT * FROM productos WHERE activo=0 ORDER BY nombre ASC");
if ($query6->num_rows > 0) {
  $contador = 1;
  while ($row6 = $query6->fetch_assoc()) {
    $cantidadUnidad = $row6["cantidad_unidades"];
    $precioDolarCompra = $row6["precio_compra"] / $cantidadUnidad;
    $porcentaje = $row6["porcentaje"];
    $foto = $row6["foto"];
    $codeProducto = $row6["codigo"];


    $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
    $precioPesoVenta = $precioDolarVenta * $pesoDolar;
    $precioBsVenta = $precioDolarVenta * $dolarBolivar;


    $precioPesoVenta =   number_format($precioPesoVenta, '0', ',', '.');
    $precioBsVenta =   number_format($precioBsVenta, '2', ',', '.');




    $html .= '
          <tr class="even pointer">
                            <td class=" ">' . $contador++ . '</td>
                            <td class=" "><a href="ficha.php?id=' . $row6["id"] . '">' . $row6["nombre"] . '</a></td>
                            <td class=" ">' . round($precioDolarVenta, 2, PHP_ROUND_HALF_DOWN) . ' $</td>
                            <td class=" ">' . $precioPesoVenta . ' </td>
                            <td class="a-right a-right ">' . $precioBsVenta . ' </td>
                          </tr>
                          
                          
                          
        
        
        
        
       ';
  }
}
$html .= '    </tbody>
                      </table>
                    </div>
 ';








$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();
$css = file_get_contents('style.css');
$pdf->writeHTML('<style>' . $css . '</style>' . $html, true, false, true, false, '');
$pdf->Output('Etiquetas.pdf', 'I');
