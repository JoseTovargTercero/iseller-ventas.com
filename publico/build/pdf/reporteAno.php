<?php
require_once('lib/pdf/mpdf.php');
require_once('../../../configurar/configuracion.php');


$query22222222 = "SELECT * FROM mail WHERE id=1";
$buscarAlumnos22222222 = $conexion->query($query22222222);
if ($buscarAlumnos22222222->num_rows > 0) {
    while ($filaAlumnos22222222 = $buscarAlumnos22222222->fetch_assoc()) {
        $correo = $filaAlumnos22222222['correo'];
        $cierre = $filaAlumnos22222222['cierre'];
    }
}


$mesConsulta = date('Y-m');



function ventasAno($mesC)
{
    global $conexion;




    $query00000000 = "SELECT * FROM orden WHERE fecha='$mesC'  AND status!='5' AND status!='5.2'";
    $buscarAlumnos00000000 = $conexion->query($query00000000);
    if ($buscarAlumnos00000000->num_rows > 0) {
        while ($filaAlumnos00000000 = $buscarAlumnos00000000->fetch_assoc()) {
            $ordenId = $filaAlumnos00000000['id'];

            $query000000000 = "SELECT * FROM orden_articulos WHERE order_id='$ordenId'";
            $buscarAlumnos000000000 = $conexion->query($query000000000);
            if ($buscarAlumnos000000000->num_rows > 0) {
                while ($filaAlumnos000000000 = $buscarAlumnos000000000->fetch_assoc()) {
                    $despachados += $filaAlumnos000000000['quantity'];
                }
            }
        }
    }

    if ($despachados == "") {
        $despachados = 0;
    }









    $query9999 = "SELECT * FROM orden WHERE fecha='$mesC' AND status='1' OR fecha='$mesC' AND status='4'";
    $buscarAlumnos9999 = $conexion->query($query9999);
    if ($buscarAlumnos9999->num_rows > 0) {
        while ($filaAlumnos9999 = $buscarAlumnos9999->fetch_assoc()) {
            $VentasMes0 = $filaAlumnos9999['total_price'];
            $totalVentasMes0 += $VentasMes0;
        }
    }
    if ($totalVentasMes0 == "") {
        $totalVentasMes0 = "0";
    }


    /////////////////////////////////////////////////////////////   
    //////////////////VENTAS DEL ANO/////////////////////////////   
    /////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////   
    /////////////////GANANCIAS DEL ANO///////////////////////////   
    /////////////////////////////////////////////////////////////   


    $queryAAAAA3 = "SELECT * FROM orden WHERE fecha='$mesC' AND status='1' OR fecha='$mesC' AND status='4'";
    $buscarAlumnosAAAAA3 = $conexion->query($queryAAAAA3);
    if ($buscarAlumnosAAAAA3->num_rows > 0) {
        while ($filaAlumnosAAAAA3 = $buscarAlumnosAAAAA3->fetch_assoc()) {
            $VentaA = $filaAlumnosAAAAA3['id'];
            $VentasSeA += $filaAlumnosAAAAA3['total_price'];
            $queryAAAAAA33 = "SELECT * FROM orden_articulos WHERE order_id='$VentaA'";
            $buscarAlumnosAAAAAA33 = $conexion->query($queryAAAAAA33);
            if ($buscarAlumnosAAAAAA33->num_rows > 0) {
                while ($filaAlumnosAAAAAA33 = $buscarAlumnosAAAAAA33->fetch_assoc()) {
                    $VentaPrductoA = $filaAlumnosAAAAAA33['product_id'];
                    $quantityA = $filaAlumnosAAAAAA33['quantity'];

                    $precioPrductoA = $filaAlumnosAAAAAA33['precio'];
                    $precioNetoA = $precioPrductoA * $quantityA;
                    $precioTotalA += $precioNetoA;
                }
            }
            $gananciasANO = $VentasSeA - $precioTotalA;
        }
    }
    if ($gananciasANO == "") {
        $gananciasANO = "0";
    }


    $lahora =  str_split($mesC, 1);
    $horaDef =  $lahora[5] . $lahora[6];



    switch ($horaDef) {
        case (1):
            $textoMes = 'ENERO';
            break;

        case (2):
            $textoMes = 'FEBRERO';
            break;

        case (3):
            $textoMes = 'MARZO';
            break;

        case (4):
            $textoMes = 'ABRIL';
            break;

        case (5):
            $textoMes = 'MAYO';
            break;

        case (6):
            $textoMes = 'JUNIO';
            break;

        case (7):
            $textoMes = 'JULIO';
            break;

        case (8):
            $textoMes = 'AGOSTO';
            break;

        case (9):
            $textoMes = 'SEPTIEMBRE';
            break;

        case (10):
            $textoMes = 'OCTUBRE';
            break;

        case (11):
            $textoMes = 'NOVIEMBRE';
            break;

        case (12):
            $textoMes = 'DICIEMBRE';
            break;
    }


    ///////////////////////////////////////////
    ///////////////////////////////////////////
    ///////////////////////////////////////////
    /////////////PASADO MES////////////////////
    ///////////////////////////////////////////
    ///////////////////////////////////////////
    ///////////////////////////////////////////



    $mesaPasada = str_split($mesC, 5);
    $mesaPasadaProce = $mesaPasada[1] - 1;
    if ($mesaPasadaProce != 10 && $mesaPasadaProce != 11 &&  $mesaPasadaProce != 12) {
        $mesaPasadaProce = "0" . $mesaPasadaProce;
    }
    $mesaPasadaProcesada = $mesaPasada[0] . $mesaPasadaProce;

    $query1111161 = "SELECT * FROM orden WHERE fecha='$mesaPasadaProcesada' AND status='1' OR fecha='$mesaPasadaProcesada' AND status='4'";
    $buscarAlumnos1111161 = $conexion->query($query1111161);
    if ($buscarAlumnos1111161->num_rows > 0) {
        while ($filaAlumnos1111161 = $buscarAlumnos1111161->fetch_assoc()) {
            $VentasSe1 += $filaAlumnos1111161['total_price'];
        }
    }
    $gananciasUltimames = $VentasSe1;





    if ($gananciasUltimames > round($totalVentasMes0, 2, PHP_ROUND_HALF_DOWN)) {
        $bal = '<img src="../../production/images/botto.png" height="15px">';
    } elseif ($gananciasUltimames < round($totalVentasMes0, 2, PHP_ROUND_HALF_DOWN)) {
        $bal = '<img src="../../production/images/top.png" height="15px">';
    } elseif ($gananciasUltimames == "") {
        $bal = '__';
    } elseif ($gananciasUltimames == round($totalVentasMes0, 2, PHP_ROUND_HALF_DOWN)) {
        $bal = '__';
    }









    if ($textoMes == "ENERO") {
        $bal = ' <img src="../../production/images/neutro.png" height="15px"> ';
    }



    $tabla = '
    <tr>
    <td>' . $textoMes . '</td>
    <td>' . $despachados . '</td>
    <td>$' . round($totalVentasMes0, 2, PHP_ROUND_HALF_DOWN) . '</td>
    <td>$' . round($gananciasANO, 2, PHP_ROUND_HALF_DOWN) . '</td>
    <td>' . $bal . '</td>
</tr>
   ';

    return $tabla;
}

////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
function DetalleMoneda($mesCou)
{
    global $conexion;
    $lahora =  str_split($mesCou, 1);
    $horaDef =  $lahora[5] . $lahora[6];

    switch ($horaDef) {
        case (1):
            $textoMes = 'ENERO';
            break;

        case (2):
            $textoMes = 'FEBRERO';
            break;

        case (3):
            $textoMes = 'MARZO';
            break;

        case (4):
            $textoMes = 'ABRIL';
            break;

        case (5):
            $textoMes = 'MAYO';
            break;

        case (6):
            $textoMes = 'JUNIO';
            break;

        case (7):
            $textoMes = 'JULIO';
            break;

        case (8):
            $textoMes = 'AGOSTO';
            break;

        case (9):
            $textoMes = 'SEPTIEMBRE';
            break;

        case (10):
            $textoMes = 'OCTUBRE';
            break;

        case (11):
            $textoMes = 'NOVIEMBRE';
            break;

        case (12):
            $textoMes = 'DICIEMBRE';
            break;
    }

    ///////////////////GANANCIAS DEL DOLARES///////////////////////
    $query2222234 = "SELECT * FROM orden WHERE fecha='$mesCou' AND status='1' AND tipoPago='5' OR fecha='$mesCou' AND status='4' AND tipoPago='5'";
    $buscarAlumnos2222234 = $conexion->query($query2222234);
    if ($buscarAlumnos2222234->num_rows > 0) {
        while ($filaAlumnos2222234 = $buscarAlumnos2222234->fetch_assoc()) {
            $Venta24 = $filaAlumnos2222234['id'];
            $VentasSe24 += $filaAlumnos2222234['total_price'];

            $query2222223344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta24'";
            $buscarAlumnos2222223344 = $conexion->query($query2222223344);
            if ($buscarAlumnos2222223344->num_rows > 0) {
                while ($filaAlumnos2222223344 = $buscarAlumnos2222223344->fetch_assoc()) {
                    $VentaPrducto24 = $filaAlumnos2222223344['product_id'];

                    $quantity154 = $filaAlumnos2222223344['quantity'];

                    $precioPrducto24 = $filaAlumnos2222223344['precio'];

                    $precioNeto24 = $precioPrducto24 * $quantity154;
                    $precioTotal24 += $precioNeto24;
                }
            }
            $gananciasMes = $VentasSe24 - $precioTotal24;
        }
    }
    ///////////////////GANANCIAS BOLIVAR//////////////////////////
    $query3333334 = "SELECT * FROM orden WHERE
fecha='$mesCou' AND status='1' AND tipoPago='1' OR
fecha='$mesCou' AND status='4' AND tipoPago='1' OR
fecha='$mesCou' AND status='1' AND tipoPago='2' OR 
fecha='$mesCou' AND status='4' AND tipoPago='2' OR 
fecha='$mesCou' AND status='1' AND tipoPago='3' OR 
fecha='$mesCou' AND status='4' AND tipoPago='3' OR 
fecha='$mesCou' AND status='1' AND tipoPago='4' OR 
fecha='$mesCou' AND status='4' AND tipoPago='4' OR
fecha='$mesCou' AND status='1' AND tipoPago='7' OR
fecha='$mesCou' AND status='4' AND tipoPago='7' 
";
    $buscarAlumnos3333334 = $conexion->query($query3333334);
    if ($buscarAlumnos3333334->num_rows > 0) {
        while ($filaAlumnos3333334 = $buscarAlumnos3333334->fetch_assoc()) {
            $Venta34 = $filaAlumnos3333334['id'];
            $VentasSe34 += $filaAlumnos3333334['total_price_bs'];

            $query3333333344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta34'";
            $buscarAlumnos3333333344 = $conexion->query($query3333333344);
            if ($buscarAlumnos3333333344->num_rows > 0) {
                while ($filaAlumnos3333333344 = $buscarAlumnos3333333344->fetch_assoc()) {
                    $VentaPrducto34 = $filaAlumnos3333333344['product_id'];

                    $quantity154 = $filaAlumnos3333333344['quantity'];

                    $precioPrducto34 = $filaAlumnos3333333344['bolivar'];

                    $precioNeto34 = $precioPrducto34 * $quantity154;
                    $precioTotal34 += $precioNeto34;
                }
            }
            $gananciasMesBolivar = $VentasSe34 - $precioTotal34;
        }
    }

    ///////////////////GANANCIAS  PESOS//////////////////////////
    $query4444434 = "SELECT * FROM orden WHERE 
fecha='$mesCou' AND status='1' AND tipoPago='6' OR
fecha='$mesCou' AND status='4' AND tipoPago='6'";

    $buscarAlumnos4444434 = $conexion->query($query4444434);
    if ($buscarAlumnos4444434->num_rows > 0) {
        while ($filaAlumnos4444434 = $buscarAlumnos4444434->fetch_assoc()) {
            $Venta44 = $filaAlumnos4444434['id'];
            $VentasSe44 += $filaAlumnos4444434['total_price_cop'];

            $query4444443344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta44'";
            $buscarAlumnos4444443344 = $conexion->query($query4444443344);
            if ($buscarAlumnos4444443344->num_rows > 0) {
                while ($filaAlumnos4444443344 = $buscarAlumnos4444443344->fetch_assoc()) {
                    $VentaPrducto44 = $filaAlumnos4444443344['product_id'];

                    $quantity154 = $filaAlumnos4444443344['quantity'];

                    $precioPrducto44 = $filaAlumnos4444443344['peso'];

                    $precioNeto44 = $precioPrducto44 * $quantity154;
                    $precioTotal44 += $precioNeto44;
                }
            }
            $gananciasMesPeso = $VentasSe44 - $precioTotal44;
        }
    }



    $mesaPasada2 = str_split($mesCou, 5);
    $mesaPasadaProce2 = $mesaPasada2[1] - 1;

    if ($mesaPasadaProce2 < 10) {
        $mesaPasadaProce2 = "0" . $mesaPasadaProce2;
    }
    $Mespasado = $mesaPasada2[0] . $mesaPasadaProce2;


    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////  
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////  
    ///////////////////GANANCIAS DEL DOLARES///////////////////////
    $query1313234 = "SELECT * FROM orden WHERE fecha='$Mespasado' AND status='1' AND tipoPago='5' OR fecha='$Mespasado' AND status='4' AND tipoPago='5'";
    $buscarAlumnos1313234 = $conexion->query($query1313234);
    if ($buscarAlumnos1313234->num_rows > 0) {
        while ($filaAlumnos1313234 = $buscarAlumnos1313234->fetch_assoc()) {
            $VentasSe34pasado += $filaAlumnos1313234['total_price'];
        }
    }
    ///////////////////GANANCIAS BOLIVAR//////////////////////////
    $query1515154 = "SELECT * FROM orden WHERE
fecha='$Mespasado' AND status='1' AND tipoPago='1' OR
fecha='$Mespasado' AND status='4' AND tipoPago='1' OR
fecha='$Mespasado' AND status='1' AND tipoPago='2' OR 
fecha='$Mespasado' AND status='4' AND tipoPago='2' OR 
fecha='$Mespasado' AND status='1' AND tipoPago='3' OR 
fecha='$Mespasado' AND status='4' AND tipoPago='3' OR 
fecha='$Mespasado' AND status='1' AND tipoPago='4' OR 
fecha='$Mespasado' AND status='4' AND tipoPago='4' OR
fecha='$Mespasado' AND status='1' AND tipoPago='7' OR
fecha='$Mespasado' AND status='4' AND tipoPago='7' 
";

    $buscarAlumnos1515154 = $conexion->query($query1515154);
    if ($buscarAlumnos1515154->num_rows > 0) {
        while ($filaAlumnos1515154 = $buscarAlumnos1515154->fetch_assoc()) {
            $VentasSe345pasado += $filaAlumnos1515154['total_price_bs'];
        }
    }

    ///////////////////GANANCIAS  PESOS//////////////////////////

    $query55434 = "SELECT * FROM orden WHERE 
fecha='$Mespasado' AND status='1' AND tipoPago='6' OR
fecha='$Mespasado' AND status='4' AND tipoPago='6'";

    $buscarAlumnos55434 = $conexion->query($query55434);
    if ($buscarAlumnos55434->num_rows > 0) {
        while ($filaAlumnos55434 = $buscarAlumnos55434->fetch_assoc()) {

            $VentasSe5pasado += $filaAlumnos55434['total_price_cop'];
        }
    }


    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   
    /////////////////////////////////////////////////////////////////////////////////   




    if (number_format($VentasSe34pasado, '2', ',', '.') > number_format($VentasSe24, '2', ',', '.')) {
        $img1 = ' <img src="../../production/images/botto.png" height="15px"> ';
    } elseif (number_format($VentasSe34pasado, '2', ',', '.') < number_format($VentasSe24, '2', ',', '.')) {
        $img1 = ' <img src="../../production/images/top.png" height="15px"> ';
    } elseif (number_format($VentasSe34pasado, '2', ',', '.') == "") {
        $img1 = '';
    } elseif (number_format($VentasSe34pasado, '2', ',', '.') == round($VentasSe24, 2, PHP_ROUND_HALF_DOWN)) {
        $img1 = '---';
    }





    if (number_format($VentasSe345pasado, '0', ',', '.') > number_format($VentasSe34, '0', ',', '.')) {
        $img2 = ' <img src="../../production/images/botto.png" height="15px"> ';
    } elseif (number_format($VentasSe345pasado, '0', ',', '.') < number_format($VentasSe34, '0', ',', '.')) {
        $img2 = ' <img src="../../production/images/top.png" height="15px"> ';
    } elseif (number_format($VentasSe345pasado, '0', ',', '.') == "") {
        $img2 = '';
    } elseif (number_format($VentasSe345pasado, '0', ',', '.') == round($VentasSe34, 2, PHP_ROUND_HALF_DOWN)) {
        $img2 = '---';
    }





    if (number_format($VentasSe5pasado, '0', ',', '.') > number_format($VentasSe44, '0', ',', '.')) {
        $img3 = ' <img src="../../production/images/botto.png" height="15px"> ';
    } elseif (number_format($VentasSe5pasado, '0', ',', '.') < number_format($VentasSe44, '0', ',', '.')) {
        $img3 = ' <img src="../../production/images/top.png" height="15px"> ';
    } elseif (number_format($VentasSe5pasado, '0', ',', '.') == "") {
        $img3 = '';
    } elseif (number_format($VentasSe5pasado, '0', ',', '.') == round($VentasSe44, 2, PHP_ROUND_HALF_DOWN)) {
        $img3 = '---';
    }





    if ($textoMes == "ENERO") {
        $img1 = ' <img src="../../production/images/neutro.png" height="15px"> ';
        $img2 = ' <img src="../../production/images/neutro.png" height="15px"> ';
        $img3 = ' <img src="../../production/images/neutro.png" height="15px"> ';
    }


    $tablaDetalle = '
<tr>
<td>' . $textoMes . '</td>
<td>' . number_format($VentasSe24, '2', ',', '.') . '</td>
<td>' . $img1 . '</td>


<td>' . number_format($VentasSe34, '0', ',', '.') . '</td>
<td>' . $img2 . '</td>


<td>' . number_format($VentasSe44, '0', ',', '.') . '</td>
<td>' . $img3 . '</td>
</tr>
';


    return $tablaDetalle;
}
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////
////////////////





function VentaProductos($mesCoun)
{
    $arreglo = array();
    global $conexion;
    $lahora =  str_split($mesCoun, 1);
    $horaDef =  $lahora[5] . $lahora[6];

    switch ($horaDef) {
        case (1):
            $textoMes = 'ENERO';
            break;

        case (2):
            $textoMes = 'FEBRERO';
            break;

        case (3):
            $textoMes = 'MARZO';
            break;

        case (4):
            $textoMes = 'ABRIL';
            break;

        case (5):
            $textoMes = 'MAYO';
            break;

        case (6):
            $textoMes = 'JUNIO';
            break;

        case (7):
            $textoMes = 'JULIO';
            break;

        case (8):
            $textoMes = 'AGOSTO';
            break;

        case (9):
            $textoMes = 'SEPTIEMBRE';
            break;

        case (10):
            $textoMes = 'OCTUBRE';
            break;

        case (11):
            $textoMes = 'NOVIEMBRE';
            break;

        case (12):
            $textoMes = 'DICIEMBRE';
            break;
    }

    $query2222234 = "SELECT * FROM productos";
    $buscarAlumnos2222234 = $conexion->query($query2222234);
    if ($buscarAlumnos2222234->num_rows > 0) {
        while ($filaAlumnos2222234 = $buscarAlumnos2222234->fetch_assoc()) {
            $nombre = $filaAlumnos2222234['nombre'];
            $id = $filaAlumnos2222234['id'];


            $query3333334 = "SELECT * FROM orden WHERE fecha='$mesCoun' AND status='1' OR fecha='$mesCoun' AND status='4'";
            $buscarAlumnos3333334 = $conexion->query($query3333334);
            if ($buscarAlumnos3333334->num_rows > 0) {
                while ($filaAlumnos3333334 = $buscarAlumnos3333334->fetch_assoc()) {
                    $Venta34 = $filaAlumnos3333334['id'];

                    $query3333333344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta34' AND product_id='$id'";
                    $buscarAlumnos3333333344 = $conexion->query($query3333333344);
                    if ($buscarAlumnos3333333344->num_rows > 0) {
                        while ($filaAlumnos3333333344 = $buscarAlumnos3333333344->fetch_assoc()) {
                            $quantity += $filaAlumnos3333333344['quantity'];


                            $arreglo['' . $nombre . ''] = $quantity;
                        }
                    }
                }
            }


            $quantity = 0;
        }
    }


    $vari = '<tr>
 <td>' . $textoMes . '</td>
 ';


    arsort($arreglo);

    foreach ($arreglo as $producto => $cantidad) {
        $vari .= "<td>" . ucfirst($producto) . "</td><td>" . $cantidad . "</td>";

        $counter++;

        if ($counter == 5) {
            goto mi_etiqueta;
        }
    }

    mi_etiqueta:

    $vari .= '</tr>';

    return $vari;
}









$html = '   <head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="style.css" media="all" />

  </head>

<img src="../../production/images/logo1-inv-compact.png" height="14px" width="14px" alt=""> MI TIENDA
<h1>

</h1>
<p style="text-align:center"><span style="font-size:15px">AVANCE DEL A&Ntilde;O ' . date('Y') . '</span><br>
Fecha del servidor: ' . date("d/m/Y - h:i a") . '<br>




Usuario que reporta : <strong>' . $_SESSION['nombre'] . '</strong><br>

</p>
<br>


</p>

 ';




$html .= '



 <p style="text-align:center"><span style="font-size:15px">RESUMEN DE VENTAS POR MES (EN DOLARES)</span><br></p>
 
                      <table class="table table-striped  jambo_table bulk_action">
                        <thead>
                          <tr class="headings">
                            <th >Mes</th>
                            <th >Productos Vendidos</th>
                            <th>Total de ventas</th>
                          <th >Ganancias</th>
                          <th ></th>
                          </tr>
                        </thead>
                           <tbody>
 ';

for ($init = 01; $init <= date('m'); $init++) {
    if ($init < 10) {
        $init = "0" . $init;
    }
    $html .= ventasAno('' . date('Y') . '-' . $init . '');
}



$html .= '   
                 </tbody>     </table>
                 
                 
<p>Los valores de ventas mostrados se toman directamente del precio unitario de cada producto</p>  






 <p style="text-align:center"><span style="font-size:15px">DETALLE DE VENTAS POR MES (DIVIDIDO EN DIVISAS)</span><br></p>
 
                      <table class="table table-striped  jambo_table bulk_action">

<thead>
                                                        <tr class="headings">
                                                            <th class="column-title">Mes</th>
                                                            <th class="column-title">Vendido ($)</th>
                                                            <th class="column-title"></th>
                                                            <th class="column-title">Vendido (BS)</th>
                                                             <th class="column-title"></th>
                                                            <th class="column-title">Vendido (COP)</th>
                                                             <th class="column-title"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    ';

for ($init2 = 01; $init2 <= date('m'); $init2++) {
    if ($init2 < 10) {
        $init2 = "0" . $init2;
    }
    $html .= DetalleMoneda('' . date('Y') . '-' . $init2 . '');
}

if (date('m') == 12) {
    $saltos = "<br>
   <br>
   <br>
   <br>
   <br>";
}

$html .= '  
   </tbody>
   </table>
   <p style="text-align:center"><span style="font-size:15px">   ' . $saltos . '
PRODUCTOS MAS VENDIDOS POR MES</span><br></p>
 
                      <table class="table table-striped  jambo_table bulk_action">

<thead>
                                                        <tr class="headings">
                                                            <th class="column-title">Mes</th>
                                                            <th class="column-title">1er Producto</th>
                                                            <th class="column-title">#</th>
                                                            
                                                            <th class="column-title">2do Producto</th>
                                                            <th class="column-title">#</th>
                                                            
                                                            <th class="column-title">3er Producto</th>
                                                            <th class="column-title">#</th>
                                                            
                                                            <th class="column-title">4to Producto</th>
                                                            <th class="column-title">#</th>
                                                            
                                                            <th class="column-title">5to Producto</th>
                                                            <th class="column-title">#</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
   ';


for ($init3 = 01; $init3 <= date('m'); $init3++) {
    if ($init3 < 10) {
        $init3 = "0" . $init3;
    }
    $html .= VentaProductos('' . date('Y') . '-' . $init3 . '');
}




$html .= '  
   </tbody>
   </table>

<footer>REPORTE: "AVANCE ANUAL" DE "MI TIENDA".</footer>                                        
';





if ($_SESSION['nivel'] != "1") {
    $modoDescarga = "F";
    // wl documento se descarga
} else {
    $modoDescarga = "I";
    // el documento se abre
}



$mpdf = new mPDF('c', 'A4');
$css = file('style.css');
$mpdf->writeHTML($css, 1);
$mpdf->writeHTML($html);
$mpdf->Output('AVANCE_MESUAL.pdf', '' . $modoDescarga . '');



if ($modoDescarga == "F" && $cierre == "1") {

    $Nombre = "AVANCE MESUAL";
    $Email = "MI TIENDA";
    $Mensaje = "Si desea desactivar los reportes por correo, diríjase a la configuración de MÍ TIENDA y desmarque las casillas: REPORTE DE CIERRE DE JORANADA Y CORTES AUTOMATICOS.";
    $archivo = 'AVANCE_MESUAL.pdf';




    require 'phpmailer/class.phpmailer.php';
    require 'phpmailer/class.smtp.php'; //incluimos la clase para envíos por SMTP
    $mail = new PHPMailer();

    $mail->From     = $Email;
    $mail->FromName = $Nombre;
    $mail->AddAddress("$correo"); // Dirección a la que llegaran los mensajes.



    // Aquí van los datos que apareceran en el correo que reciba

    $mail->WordWrap = 50;
    $mail->IsHTML(true);
    $mail->Subject  =  "Contacto";
    $mail->Body     =  "MOTIVO: $Nombre \n<br />" .
        "REMITENTE: $Email \n<br />" .
        "NOTA: $Mensaje \n<br />";
    //$mail->AddAttachment($archivo['tmp_name'], $archivo['name']);   
    $mail->AddAttachment($archivo);


    // Datos del servidor SMTP

    $mail->IsSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "ssl";
    $mail->Host = "smtp.gmail.com"; //servidor smtp, esto lo puedes dejar igual
    $mail->Port = 465; //puerto smtp de gmail, tambien lo puedes dejar igual
    $mail->Username = 'mtienda81@gmail.com';
    $mail->Password = 'jose272727';
    $mail->FromName = 'MI TIENDA'; // 
    $mail->From = 'mtienda81@gmail.com'; //email de remitente desde donde se envía el correo, este caso para evitar spam es el mismo que tu correo gmail
    if ($mail->Send()) {

        if (unlink('AVANCE_MESUAL.pdf')) {

            echo "<script>
			      window.open('../../production/consultaHistorica.php?accion=enviado', '_self');
                    </script>;";
        }
    } else {


        if (unlink('AVANCE_MESUAL.pdf')) {
            echo "<script>
			  window.open('../../production/consultaHistorica.php?accion=conexion', '_self');
              </script>;";
        }
    }
} else {
    echo "<script>
			  window.open('../../production/consultaHistorica.php?accion=correo', '_self');
              </script>;";
}
