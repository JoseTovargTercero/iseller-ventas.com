<?php
require_once('lib/pdf/mpdf.php');
require_once('../../../configurar/configuracion.php');


$query2 = "SELECT * FROM cambio WHERE id=1";
$buscarAlumnos2 = $conexion->query($query2);
if ($buscarAlumnos2->num_rows > 0) {
    while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
        $pesoDolar = $filaAlumnos2['pesoDolar'];
        $dolarBolivar = $filaAlumnos2['DolarBolivar'];
    }
}



$html = "   <head>
       <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='icon' href='../../production/images/favicon.ico' type='image/ico' />



    <link rel='stylesheet' href='style.css' media='all' />
  </head>


    
 <body >
 ";
$query22 = "SELECT * FROM productos WHERE activo=0 ORDER BY nombre ASC";
$buscarAlumnos22 = $conexion->query($query22);
if ($buscarAlumnos22->num_rows > 0) {
    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {

        $cantidadUnidad = $filaAlumnos22["cantidad_unidades"];
        $precioDolarCompra = $filaAlumnos22["precio_compra"] / $cantidadUnidad;
        $porcentaje = $filaAlumnos22["porcentaje"];
        $foto = $filaAlumnos22["foto"];
        $codeProducto = $filaAlumnos22["codigo"];

        $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
        $precioPesoVenta = $precioDolarVenta * $pesoDolar;
        $precioBsVenta = $precioDolarVenta * $dolarBolivar;
        $nameProducto = $filaAlumnos22["nombre"];
        $nameProducto = strtoupper($nameProducto);
        $html .= '
     <div style="margin-left: 20%; border:3px solid gray; width:60%; margin-bottom:6px; padding-left: 15; padding-top: 8; padding-bottom: 8; text-align: center;">
      <span style="font-size:22px; font-weight: bolder; color: gray;">' . $nameProducto . '</span><br>
      
         <span style="font-size:19px; font-weight: bolder;"> ' . number_format($precioBsVenta, '0', '.', '.') . ' <small>BS</small></span> <br>

         <span style="font-size:19px; font-weight: bolder; ">' . number_format($precioDolarVenta, '2', '.', '.') . ' $ - ' . number_format($precioPesoVenta, '0', '.', '.') . ' <small>COP</small></span>
         
     </div>
';
    }
    $html .= '  </body>
                ';
}

$mpdf = new mPDF('c', 'A4');
$css = file('style.css');
$mpdf->writeHTML($css, 1);
$mpdf->writeHTML($html);
$mpdf->Output('Etiquetas.pdf', 'I')
