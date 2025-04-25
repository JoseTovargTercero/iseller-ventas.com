<?php
require_once('lib/pdf/mpdf.php');
require_once('../../../configurar/configuracion.php');



$query2 = "SELECT * FROM empresa WHERE id=1";
$buscarAlumnos2 = $conexion->query($query2);
if ($buscarAlumnos2->num_rows > 0) {
  while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
    $stockCritico = $filaAlumnos2['stockCritico'];
    $nameempresa = $filaAlumnos2['emp'];
  }
}


$query = "SELECT * FROM cambio WHERE id='1'";
$buscarAlumnos = $conexion->query($query);
if ($buscarAlumnos->num_rows > 0) {
  while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

    $PesoDolar = $filaAlumnos['pesoDolar'];
    $Pesobolivar = $filaAlumnos['bolivar_peso'];
    $peso_bolivar = $filaAlumnos['peso_bolivar'];
    $dolarBolivar = $filaAlumnos['DolarBolivar'];
  }
}


$html = '   <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css" media="all" />
  </head>

</h1><img src="../../production/images/logo1-inv-compact.png" height="14px" width="14px" alt=""> MI TIENDA<h1>
</h5>' . $nameempresa . ' - LISTA DE PRECIOS<h5>
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
  echo "hola mundo";
  $contador = 1;
  while ($row6 = $query6->fetch_assoc()) {
    $cantidadUnidad = $row6["cantidad_unidades"];
    $precioDolarCompra = $row6["precio_compra"] / $cantidadUnidad;
    $porcentaje = $row6["porcentaje"];
    $foto = $row6["foto"];
    $codeProducto = $row6["codigo"];


    $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
    $precioPesoVenta = $precioDolarVenta * $PesoDolar;
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








$mpdf = new mPDF('c', 'A4');
$css = file('style.css');
$mpdf->writeHTML($css, 1);
$mpdf->writeHTML($html);
$mpdf->Output('Etiquetas.pdf', 'I')
