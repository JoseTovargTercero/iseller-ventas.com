
 <?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");

$query22 = 'SELECT * FROM empresa';
$buscarAlumnos22 = $conexion->query( $query22 );
if ( $buscarAlumnos22->num_rows > 0 ) {
    while( $filaAlumnos22 = $buscarAlumnos22->fetch_assoc() ) {
        $stockCritico = $filaAlumnos22['stockCritico'];
    }
}
 /* 
 
||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
|||||                                                                                                      |||||
|||||  ||||||||||||| ||||         ||||||||||||| ||||||||||||||  ||||||||||||| ||||||||||||| ||||||||||||   |||||     
|||||  ||||||||||||| ||||         ||||||||||||| ||||||||||||||  ||||||||||||| ||||||||||||| |||||||||||||  |||||     
|||||  ||||          ||||         ||||     |||| |||||                ||||     ||||          ||||     ||||  |||||
|||||  ||||          ||||         ||||     |||| |||||                ||||     ||||          ||||     ||||  |||||
|||||  ||||   |||||| ||||         ||||     |||| ||||||||||||||       ||||     ||||||||||||  ||||  |||||    |||||
|||||  ||||   |||||| ||||         ||||     |||| ||||||||||||||       ||||     ||||||||||||  |||||||||||    |||||
|||||  ||||     |||| ||||         ||||     ||||           ||||       ||||     ||||          ||||||||||     |||||
|||||  ||||     |||| ||||         ||||     ||||           ||||       ||||     ||||          ||||   ||||    |||||
|||||  ||||||||||||| |||||||||||| ||||||||||||| ||||||||||||||       ||||     ||||||||||||  ||||    ||||   |||||
|||||  ||||||||||||| |||||||||||| ||||||||||||| ||||||||||||||       ||||     ||||||||||||  ||||     ||||  |||||
|||||                                                                                                      |||||
||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||
  */
               
if(isset($_POST['rep2'])){
	$q=$conexion->real_escape_string($_POST['rep2']);
       $query = $conexion->query("SELECT * FROM productos  WHERE nombre LIKE '%$q%' AND activo='1' AND distribuidor='si' ORDER BY id DESC");
        if($query->num_rows > 0){ 
            $tabla.='<div class="responsi">
                      <table class="table">
                        <thead style="min-width:100%" >
                          <tr class="">
                            <th style="width:10%"  class="column-title">Rest</th>
                            <th style="width:30%" class="column-title">Producto</th>
                            <th style="width:15%" class="column-title">Cantidad</th>
                            <th style="width:10%;;" class="column-title">Dolares</th>
                            <th style="width:10%" class="column-title">Pesos</th>
                            <th style="width:15%" class="column-title">Bolivares</th>
                            <th style="width:5%" class="column-title">Agregar</th>
                          </tr>
                        </thead>
                        </table>
                    
';
            while($row = $query->fetch_assoc()){
                    
       $cantidadUnidad = $row["cantidad_unidades"];
       $precioDolarCompra = $row["precio_compra"] / $cantidadUnidad;
       $porcentaje = $row["porcentaje"];
       $precioDolarVenta = ($precioDolarCompra * $porcentaje / 100) + $precioDolarCompra;
       
       $precioDolarVenta2 = $precioDolarVenta;
       $precioDolarVenta = round($precioDolarVenta, 2, PHP_ROUND_HALF_DOWN);




       $idTasaPeso = $row["TasaPeso"];
       $idTasaDolar = $row["tasaDolar"];


$query7 = $conexion->query("SELECT * FROM tasas_dolar WHERE id='$idTasaDolar'");
if ($query7->num_rows > 0) {
while ($row7 = $query7->fetch_assoc()){
$dolarBolivar = $row7['recepcion'];

}
}

$query8 = $conexion->query("SELECT * FROM tasas_pesos WHERE id_peso='$idTasaPeso'");
if ($query8->num_rows > 0) {
while ($row8 = $query8->fetch_assoc()){
$pesoBolivarPublicacion = $row8['publicacion_peso'];

}
}
 ///////////////////////////////////////
 ///////////////////////////////////////
 ///////////////////////////////////////
 ///////////////////////////////////////
  $precioBsVenta = $precioDolarVenta2 * $dolarBolivar;
  $precioBsVentaH = $precioDolarVenta2 * $dolarBolivar;
  $precioBsVenta = round($precioBsVenta, 2, PHP_ROUND_HALF_DOWN);
  $precioBsVenta2 = number_format($precioBsVenta,'0', ',','.');  
  $precioPesoVenta = $precioBsVentaH * $pesoBolivarPublicacion;
 ////////////////////////////////////////
 ////////////////////////////////////////
 ////////////////////////////////////////
 ////////////////////////////////////////



 $precioPesoVenta = round($precioPesoVenta, 2, PHP_ROUND_HALF_DOWN);
 $precioPesoVenta2 = number_format($precioPesoVenta,'0', ',','.'); 



               if($row['stock'] == '0') {
                   $alerta = "(0)";
                   $color = 'color:red';
               }elseif($row['stock'] <= $stockCritico && $row['stock'] >= 1){
                   $alerta = "(".$row['stock'].")";
                   $color = 'color:#1ABB9C';
               }else{
                   $alerta = "(".$row["stock"].")";
                   $color = 'color:lightgray';
               }
    	$tabla.='
         <form action="AccionCarta2.php" class="formulario">
                   <input type="text" id="action" name="action" hidden value="addToCart">
                  <input type="text" id="id" name="id" hidden value="'.$row['id'].'">
                  <input type="text" id="codigo" name="codigo" hidden value="'.$row['codigo'].'">
                  
                  
                  <input type="text" id="dolarventa" name="dolarventa" hidden value="'.$precioDolarVenta2.'">
                  <input type="text" id="pesoventa" name="pesoventa" hidden value="'.round($precioPesoVenta, 2, PHP_ROUND_HALF_DOWN).'">
                  <input type="text" id="bolivarventa" name="bolivarventa" hidden value="'.round($precioBsVenta, 2, PHP_ROUND_HALF_DOWN).'">
                  
       <table class="table">
         <tbody style="min-width:100%" >
                          <tr class="">
                          
                            <td  style="width:10%"  '.$color.'" class=" ">'.$alerta.'</td>
                            <td class="de" style="width:30%" class=" ">'.strtoupper($row["nombre"]).'</td>
                            <td  style="width:15%"  class=" "><input type="number" class="cant" id="cant" name="cant" value="1"></td>
                            <td  style="width:10%;;" class="a-right a-right ">'.'$'.round($precioDolarVenta, 2, PHP_ROUND_HALF_DOWN).'</td>
                            <td style="border-left:1px solid whitesmoke; " data-toggle="tooltip" data-placement="top" title="PESOS"  style="width:10%"  class="a-right a-right ">'.$precioPesoVenta2.'</td>
                            <td  style="border-left:1px solid whitesmoke; " data-toggle="tooltip" data-placement="top" title="BOLIVARES" style="width:15%"  class="a-right a-right ">'.$precioBsVenta2.' </td>
                            <td  style="width:5%"  ><button class="btn btn-success" ><i class="fa fa-shopping-cart"></i></button></td>
                           
                          </tr>
                          
                          
                        </tbody>
                    </table>
             </form>';
   }
            $tabla.='</div>';
  }
}else{
    echo '<h1 style="font-size:20em; text-align:center; color:lightgray"><i class="fa fa-shopping-cart"></i></h1>';
}
echo $tabla;
?>
