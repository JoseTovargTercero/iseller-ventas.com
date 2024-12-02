<?php
require_once( 'lib/pdf/mpdf.php' );

require_once('../../../configurar/configuracion.php');



$query2 = "SELECT * FROM empresa WHERE id=1";
$buscarAlumnos2 = $conexion->query( $query2 );
if ( $buscarAlumnos2->num_rows > 0 ) {
    while( $filaAlumnos2 = $buscarAlumnos2->fetch_assoc() ) {
        $stockCritico = $filaAlumnos2['stockCritico'];
        $nameempresa = $filaAlumnos2['emp'];
    }

}
$query22222222 = "SELECT * FROM mail WHERE id=1";
$buscarAlumnos22222222 = $conexion->query( $query22222222 );
if ( $buscarAlumnos22222222->num_rows > 0 ) {
    while( $filaAlumnos22222222 = $buscarAlumnos22222222->fetch_assoc() ) {
        $correo = $filaAlumnos22222222['correo'];
        $cierre = $filaAlumnos22222222['cierre'];
    }

}

$query = "SELECT * FROM cambio WHERE id='1'";
$buscarAlumnos = $conexion->query( $query );
if ( $buscarAlumnos->num_rows > 0 ) {
    while( $filaAlumnos = $buscarAlumnos->fetch_assoc() )
    {

        $PesoDolar = $filaAlumnos['pesoDolar'];
        $dolarBolivar = $filaAlumnos['DolarBolivar'];
    }
}

  $today = date( 'Y-m-d' );

    $query00 = "SELECT * FROM orden WHERE modified='$today' AND status='1'";
    $buscarAlumnos00 = $conexion->query( $query00 );
    if ( $buscarAlumnos00->num_rows > 0 ) {
        while( $filaAlumnos00 = $buscarAlumnos00->fetch_assoc() ) {
            $totalVentas += 1;
            $total += $filaAlumnos00['total_price'];
        }
    }
    $query000 = "SELECT * FROM orden WHERE modified='$today' AND status='2'";
    $buscarAlumnos000 = $conexion->query( $query000 );
    if ( $buscarAlumnos000->num_rows > 0 ) {
        while( $filaAlumnos000 = $buscarAlumnos000->fetch_assoc() ) {
            $totalCreditos +=1;
            $totalCreditosValor += $filaAlumnos000['total_price'];
        }
    }

    $query0000 = "SELECT * FROM orden WHERE modified='$today'  AND status!='5' AND status!='5.2'";
    $buscarAlumnos0000 = $conexion->query( $query0000 );
    if ( $buscarAlumnos0000->num_rows > 0 ) {
        while( $filaAlumnos0000 = $buscarAlumnos0000->fetch_assoc() ) {
            $ordenId = $filaAlumnos0000['id'];

            $query00000 = "SELECT * FROM orden_articulos WHERE order_id='$ordenId'";
            $buscarAlumnos00000 = $conexion->query( $query00000 );
            if ( $buscarAlumnos00000->num_rows > 0 ) {
                while( $filaAlumnos00000 = $buscarAlumnos00000->fetch_assoc() ) {
                    $despachados += $filaAlumnos00000['quantity'];

                }
            }

        }
    }
     $cantidadCritica = contar( "SELECT COUNT(*) FROM productos WHERE stock<='$stockCritico' AND activo='0'" );
  

    if ( $totalCreditosValor == '' ) {
        $totalCreditosValor = '0';
    }
    if ( $total == '' ) {
        $total = '0';
    }












  
   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    $residuoBs = 0;
    $residuoCop = 0;
    $residuoUsd = 0;

    $query0000034 = "SELECT * FROM orden WHERE modified='$today' AND status='1' AND tipoPago='8' OR modified='$today' AND status='4' AND tipoPago='8'";
    $buscarAlumnos0000034 = $conexion->query($query0000034);
    if ($buscarAlumnos0000034->num_rows > 0) {
        while ($filaAlumnos0000034 = $buscarAlumnos0000034->fetch_assoc()) {

            

            $Venta04 = $filaAlumnos0000034['id'];

            $query00000000 = "SELECT * FROM fracciones WHERE id_order='$Venta04'";
            $buscarAlumnos00000000 = $conexion->query($query00000000);
            if ($buscarAlumnos00000000->num_rows > 0) {
                while ($filaAlumnos00000000 = $buscarAlumnos00000000->fetch_assoc()) {

                    $totalEnBs += $filaAlumnos00000000['punto'] + $filaAlumnos00000000['pagoMovil'] + $filaAlumnos00000000['transferencia'] + $filaAlumnos00000000['bioPago'] + $filaAlumnos00000000['efectivo'];
                    $TotalEnpesos += $filaAlumnos00000000['pesos'];
                    $TotalEndolares += $filaAlumnos00000000['dolares'];
                    //// TOTALES CANCELADOS
                }
            }


            $query0000003344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta04'";
            $buscarAlumnos0000003344 = $conexion->query($query0000003344);
            if ($buscarAlumnos0000003344->num_rows > 0) {

                while ($filaAlumnos0000003344 = $buscarAlumnos0000003344->fetch_assoc()) {

                    $precioNeto04Bs += $filaAlumnos0000003344['bolivar'] * $filaAlumnos0000003344['quantity'];   // MONTO QUE SE DEBE CUBRIR EN BS AL MOMENTO
                    $precioNeto04Dolar += $filaAlumnos0000003344['precio'] * $filaAlumnos0000003344['quantity'];   // MONTO QUE SE DEBE CUBRIR EN BS AL MOMENTO
                    $precioNeto04Peso += $filaAlumnos0000003344['peso'] * $filaAlumnos0000003344['quantity'];   // MONTO QUE SE DEBE CUBRIR EN BS AL MOMENTO   6.786

                }
            }


























            $porcentajeCubiertoEnBolivares = $totalEnBs * 100 / $precioNeto04Bs;   // Saco el porcentaje que representa los cancelado en bolivares por el cliente con respecto al total (inicial)
            if($porcentajeCubiertoEnBolivares >= 100){                             // SI EL PORCENTAJE ES IGUAL O MAYOR QUE 100 ENTONCES SACO LAS GANANCIAS
            $residuoBs += $totalEnBs - $precioNeto04Bs;                            // la ganancia en bolivares es igual al total abonado por el cliente menos el costo
            $residuoPesos += $TotalEnpesos;                                        // La ganancias en pesos es igual al total abonado por el cliente
            $residuoDolar += $TotalEndolares;                                      // La ganancias en dolares es igual al total abonado por el cliente
            }else{

                $precioNeto04Peso = $precioNeto04Peso - ($porcentajeCubiertoEnBolivares * $precioNeto04Peso / 100);         // Saco el nuevo *precio neto* en peso y doalres, siendo igual a sus 
                $precioNeto04Dolar = $precioNeto04Dolar - ($porcentajeCubiertoEnBolivares * $precioNeto04Dolar / 100);      // totales menos el porcentaje que ya ha sido cancelado en bolivares
    
                
            
                $porcentajeCubiertoEnPesos = $TotalEnpesos * 100 / $precioNeto04Peso;     // Saco el porcentaje que representa los cancelado en pesos al nuevo total sacado en el paso anterior
                    if($porcentajeCubiertoEnPesos >= 100){                                // SI EL PORCENTAJE ES IGUAL O MAYOR QUE 100 ENTONCES SACO LAS GANANCIAS
                        $residuoBs += 0;                                                  // las ganancias en bolivares son de cero
                        $residuoPesos += $TotalEnpesos - $precioNeto04Peso;               // las ganancias en pesos son igual al total abonado por el cliente menos el nuevo *precio neto
                        $residuoDolar += $TotalEndolares;                                 // La ganancias en dolares es igual al total abonado por el cliente

                    }else{
                        
                        $precioNeto04Dolar = $precioNeto04Dolar - ($porcentajeCubiertoEnPesos * $precioNeto04Dolar / 100);
                        $porcentajeCubiertoEnDolares = $TotalEndolares * 100 / $precioNeto04Dolar;
                            if($porcentajeCubiertoEnDolares >= 100){
                                $residuoBs += 0;                                                  // las ganancias en bolivares son de cero
                                $residuoPesos += 0;                                               // las ganancias en pesos son de cero
                                $residuoDolar += $TotalEndolares - $precioNeto04Dolar;            // las ganancias en pesos son igual al total abonado por el cliente menos el nuevo *precio neto
                            }
                    }
            }


$totalEnBs = 0;
$TotalEnpesos = 0;
$TotalEndolares = 0;
$precioNeto04Bs = 0;
$precioNeto04Dolar = 0;
$precioNeto04Peso = 0;


         }
    }






    $query000000 = "SELECT * FROM orden WHERE modified='$today' AND status='4'";
    $buscarAlumnos000000 = $conexion->query( $query000000 );
    if ( $buscarAlumnos000000->num_rows > 0 ) {
        while( $filaAlumnos000000 = $buscarAlumnos000000->fetch_assoc() ) {
            $totalVentas2 += 1;
            $total2 += $filaAlumnos000000['total_price'];
        }
    }

  if ( $total2 == '' ) {
        $total2 = '0';
    }


$montotTotal = $total + $total2;

   
    $totalVentas1111111 = 0;
    $query1111111 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='1' AND status='1' OR modified='$today' AND tipoPago='1' AND status='4'";
    $buscarAlumnos1111111 = $conexion->query( $query1111111 );
    if ( $buscarAlumnos1111111->num_rows > 0 ) {
        while( $filaAlumnos1111111 = $buscarAlumnos1111111->fetch_assoc() ) {
            $totalVentas1111111 += 1;
            $total1111111 += $filaAlumnos1111111['total_price_bs'];
        }
    }
    // PUNTO DE VENTA/////////////////////////

    $totalVentas222222 = 0;
    $query222222 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='2' AND status='1' OR modified='$today' AND tipoPago='2' AND status='4'";
    $buscarAlumnos222222 = $conexion->query( $query222222 );
    if ( $buscarAlumnos222222->num_rows > 0 ) {
        while( $filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc() ) {
            $totalVentas222222 += 3;
            $total222222 += $filaAlumnos222222['total_price_bs'];
        }
    }
    // PAGO MOVIL

    $totalVentas33333333 = 0;
    $query33333333 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='3' AND status='1' OR modified='$today' AND tipoPago='3' AND status='4'";
    $buscarAlumnos33333333 = $conexion->query( $query33333333 );
    if ( $buscarAlumnos33333333->num_rows > 0 ) {
        while( $filaAlumnos33333333 = $buscarAlumnos33333333->fetch_assoc() ) {
            $totalVentas33333333 += 3;
            $total33333333 += $filaAlumnos33333333['total_price_bs'];
        }
    }
    // TRANSFERENCIA

    $totalVentas4444444 = 0;
    $query4444444 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='4' AND status='1' OR modified='$today' AND tipoPago='4' AND status='4'";
    $buscarAlumnos4444444 = $conexion->query( $query4444444 );
    if ( $buscarAlumnos4444444->num_rows > 0 ) {
        while( $filaAlumnos4444444 = $buscarAlumnos4444444->fetch_assoc() ) {
            $totalVentas4444444 += 3;
            $total4444444 += $filaAlumnos4444444['total_price_bs'];
        }
    }
    // BS EFECTIVO
    $totalVentas55555 = 0;
    $query55555 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='5' AND status='1' OR modified='$today' AND tipoPago='5' AND status='4'";
    $buscarAlumnos55555 = $conexion->query( $query55555 );
    if ( $buscarAlumnos55555->num_rows > 0 ) {
        while( $filaAlumnos55555 = $buscarAlumnos55555->fetch_assoc() ) {
            $totalVentas55555 += 3;
            $total55555 += $filaAlumnos55555['total_price'];
        }
    }
    // DOLARES
    $totalVentas666666 = 0;
    $query666666 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='6' AND status='1' OR modified='$today' AND tipoPago='6' AND status='4'";
    $buscarAlumnos666666 = $conexion->query( $query666666 );
    if ( $buscarAlumnos666666->num_rows > 0 ) {
        while( $filaAlumnos666666 = $buscarAlumnos666666->fetch_assoc() ) {
            $totalVentas666666 += 3;
            $total666666 += $filaAlumnos666666['total_price_cop'];
        }
    }
    // PESOS

        $totalVentas7777777 = 0;
    $query7777777 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='7' AND status='1' OR modified='$today' AND tipoPago='7' AND status='4'";
    $buscarAlumnos7777777 = $conexion->query( $query7777777 );
    if ( $buscarAlumnos7777777->num_rows > 0 ) {
        while( $filaAlumnos7777777 = $buscarAlumnos7777777->fetch_assoc() ) {
            $totalVentas7777777 += 3;
            $total7777777 += $filaAlumnos7777777['total_price_bs'];
        }
    }
    // BIOPAGO

    


















    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////GANANCIAS dolares//////////////////////////
$query2222234 = "SELECT * FROM orden WHERE modified='$today' AND status='1' AND tipoPago='5' OR modified='$today' AND status='4' AND tipoPago='5'";
$buscarAlumnos2222234 = $conexion->query( $query2222234 );
if ( $buscarAlumnos2222234->num_rows > 0 ) {
    while( $filaAlumnos2222234 = $buscarAlumnos2222234->fetch_assoc() ) {
        $Venta24 = $filaAlumnos2222234['id'];
        $VentasSe24 += $filaAlumnos2222234['total_price'];

        $query2222223344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta24'";
        $buscarAlumnos2222223344 = $conexion->query( $query2222223344 );
        if ( $buscarAlumnos2222223344->num_rows > 0 ) {
            while( $filaAlumnos2222223344 = $buscarAlumnos2222223344->fetch_assoc() ) {
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
    
    
    
    
    
        
    
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////GANANCIAS BOLIVAR//////////////////////////
$query3333334 = "SELECT * FROM orden WHERE
modified='$today' AND status='1' AND tipoPago='1' OR
modified='$today' AND status='4' AND tipoPago='1' OR
modified='$today' AND status='1' AND tipoPago='2' OR 
modified='$today' AND status='4' AND tipoPago='2' OR 
modified='$today' AND status='1' AND tipoPago='3' OR 
modified='$today' AND status='4' AND tipoPago='3' OR 
modified='$today' AND status='1' AND tipoPago='4' OR 
modified='$today' AND status='4' AND tipoPago='4' OR
modified='$today' AND status='1' AND tipoPago='7' OR
modified='$today' AND status='4' AND tipoPago='7' 
";
    
$buscarAlumnos3333334 = $conexion->query( $query3333334 );
if ( $buscarAlumnos3333334->num_rows > 0 ) {
    while( $filaAlumnos3333334 = $buscarAlumnos3333334->fetch_assoc() ) {
        $Venta34 = $filaAlumnos3333334['id'];
        $VentasSe34 += $filaAlumnos3333334['total_price_bs'];

        $query3333333344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta34'";
        $buscarAlumnos3333333344 = $conexion->query( $query3333333344 );
        if ( $buscarAlumnos3333333344->num_rows > 0 ) {
            while( $filaAlumnos3333333344 = $buscarAlumnos3333333344->fetch_assoc() ) {
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
    
    
       
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////GANANCIAS  PESOS//////////////////////////
$query4444434 = "SELECT * FROM orden WHERE 
modified='$today' AND status='1' AND tipoPago='6' OR
modified='$today' AND status='4' AND tipoPago='6'";
    
$buscarAlumnos4444434 = $conexion->query( $query4444434 );
if ( $buscarAlumnos4444434->num_rows > 0 ) {
    while( $filaAlumnos4444434 = $buscarAlumnos4444434->fetch_assoc() ) {
        $Venta44 = $filaAlumnos4444434['id'];
        $VentasSe44 += $filaAlumnos4444434['total_price_cop'];

        $query4444443344 = "SELECT * FROM orden_articulos WHERE order_id='$Venta44'";
        $buscarAlumnos4444443344 = $conexion->query( $query4444443344 );
        if ( $buscarAlumnos4444443344->num_rows > 0 ) {
            while( $filaAlumnos4444443344 = $buscarAlumnos4444443344->fetch_assoc() ) {
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


$queryaaaaaaa = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='8' AND status='1' OR modified='$today' AND tipoPago='8' AND status='4'";
$buscarAlumnosaaaaaaa = $conexion->query( $queryaaaaaaa );
if ( $buscarAlumnosaaaaaaa->num_rows > 0 ) {
    while( $filaAlumnosaaaaaaa = $buscarAlumnosaaaaaaa->fetch_assoc() ) {
        $ordenSekec = $filaAlumnosaaaaaaa['id'];

        $query0000000 = "SELECT * FROM fracciones WHERE id_order='$ordenSekec'";
        $buscarAlumnos0000000 = $conexion->query( $query0000000 );
        if ( $buscarAlumnos0000000->num_rows > 0 ) {
            while( $filaAlumnos0000000 = $buscarAlumnos0000000->fetch_assoc() ) {

                $punto += $filaAlumnos0000000['punto'];
                $pagoMovil += $filaAlumnos0000000['pagoMovil'];
                $transferencia += $filaAlumnos0000000['transferencia'];
                $bioPago += $filaAlumnos0000000['bioPago'];
                $efectivo += $filaAlumnos0000000['efectivo'];
                $pesos += $filaAlumnos0000000['pesos'];
                $dolares += $filaAlumnos0000000['dolares'];
            }
        }
    }
}
// Fraccionado/////////////////////////



$html = '   <head>
  
    <link rel="stylesheet" href="style.css" media="all" />
  </head>

<img src="../../production/images/logo1-inv-compact.png" height="14px" width="14px" alt=""> i-SELLER
<h1>

</h1>
<p style="text-align:center"><span style="font-size:15px">'.$nameempresa.' - REPORTE DE CIERRE DE JORNADA</span><br>
Fecha del servidor: '.date("d/m/Y - h:i a").'<br>

Usuario que reporta : <strong>'.$_SESSION['nombre'].'</strong><br>


</p>
<br>

    Total de ventas al detal: <strong>'.$totalVentas.'</strong>.<br>
    Ventas al detal por un total de: <strong>$'.number_format($total,'2', ',','.').'</strong><br> 
    
    Total de ventas al mayor: <strong>'.$totalVentas2.'</strong><br>
    Ventas al mayor por un total de: <strong>$'.number_format($total2,'2', ',','.').'</strong><br>
    
    
    Total de creditos otorgados: <strong>'.$totalCreditos.'</strong><br>
     Creditos por un total de: <strong>$'.number_format($totalCreditosValor,'2', ',','.').'</strong><br>
    Total de articulos despachados: <strong>'.$despachados.'</strong.><br>
    Total de articulos con stock critico: <strong>'.$cantidadCritica.'</strong><br><br>
</p>















 <p style="text-align:center"><span style="font-size:15px">CIERRE DE CAJA</span><br></p>
 
                      <table class="table table-striped  jambo_table bulk_action">
                        <thead>
                          <tr class="headings">
                            <th class="column-title">Punto de Venta</th>
                            <th class="column-title">Pago Movil</th>
                            <th class="column-title">Transferencia</th>
                            <th class="column-title">Biopago</th>
                            <th class="column-title">BS Efectivo</th>
                            <th class="column-title">Dolares</th>
                            <th class="column-title">Pesos</th>
                          </tr>
                        </thead>
                       <tbody>
                         <tr> ';     
                           
                           
           $html .= '       
                            <th class="column-title">'.number_format($total1111111 + $punto,'0', ',','.').' BS</th>
                            <th class="column-title">'.number_format($total222222 + $pagoMovil,'0', ',','.').' BS</th>
                            <th class="column-title">'.number_format($total33333333 + $transferencia,'0', ',','.').' BS</th>
                            <th class="column-title">'.number_format($total7777777 + $bioPago,'0', ',','.').' BS</th>
                            <th class="column-title">'.number_format($total4444444 + $efectivo,'0', ',','.').' BS</th>
                            <th class="column-title">$ '.number_format($total55555 + $dolares,'2', ',','.').'</th>
                            <th class="column-title">'.number_format($total666666 + $pesos,'0', ',','.').' COP</th>
                            ';
                           
$html.= '
</tr> 
</tbody>
</table>


 <p style="text-align:center"><span style="font-size:15px">GANANCIAS</span><br></p>

                      <table style="width: 50%; margin-left:35%" class="table table-striped  jambo_table bulk_action">
                        <thead>
                          <tr class="headings">
                            <th class="column-title">BOLIVARES</th>
                            <th class="column-title">DOLARES</th>
                            <th class="column-title">PESOS</th>

                          </tr>
                        </thead>
                       <tbody>
                         <tr> 
                         
                            <th class="column-title">'.number_format( $gananciasMesBolivar + $residuoBs, '0', '.', '.' ).' BS</th>
                            <th class="column-title">$ '.number_format( $gananciasMes + $residuoDolar, '2', '.', '.' ).'</th>
                            <th class="column-title">'.number_format( $gananciasMesPeso + $residuoPesos, '2', '.', '.' ).' COP</th>
                          </tr>

</tbody>
</table>

 <p style="text-align:center"><span style="font-size:15px">MOVIMIENTOS DEL DIA</span><br></p>
 
                      <table class="table table-striped  jambo_table bulk_action">
                        <thead>
                          <tr class="headings">
                            <th >#</th>
                            <th >Tipo</th>
                            <th >Pago</th>
                            <th>Usuario</th>
                          <th >Hora</th>
                            <th >Monto</th>
                            <th>Monto</th>
                            <th >Monto</th>
                            <th>Productos</th>
                          </tr>
                        </thead>
                           <tbody>
 ';
                          
            $query77 = "SELECT * FROM orden WHERE modified='$today' AND status='1' OR modified='$today' AND status='4' OR modified='$today' AND status='2'";
                            $buscarAlumnos77 = $conexion->query( $query77 );
                            if ( $buscarAlumnos77->num_rows > 0 ) {
                               $contador = 1;
                                while( $filaAlumnos77 = $buscarAlumnos77->fetch_assoc() ) {
                                    $users = $filaAlumnos77['customer_id'];
                                    $orderid = $filaAlumnos77['id'];
                               
                                  $query999999999 = "SELECT * FROM usuarios WHERE id='$users'";
                            $buscarAlumnos999999999 = $conexion->query( $query999999999 );
                            if ( $buscarAlumnos999999999->num_rows > 0 ) {
                                while( $filaAlumnos999999999 = $buscarAlumnos999999999->fetch_assoc() ) {
                                    $usuario1 = $filaAlumnos999999999['nombre'];	
                                    
                                }
                            }
        
                                    
                             $query9999999999999 = "SELECT * FROM orden_articulos WHERE order_id='$orderid'";
                            $buscarAlumnos9999999999999 = $conexion->query( $query9999999999999 );
                            if ( $buscarAlumnos9999999999999->num_rows > 0 ) {
                                while( $filaAlumnos9999999999999 = $buscarAlumnos9999999999999->fetch_assoc() ) {
                                    
                                            
                                      $producto  = $filaAlumnos9999999999999['product_id'];
                                      $productoquanty  = $filaAlumnos9999999999999['quantity'];
                                            
                        
                                            
                                            
                            $query9999999999 = "SELECT * FROM productos WHERE id='$producto'";
                            $buscarAlumnos9999999999 = $conexion->query( $query9999999999 );
                            if ( $buscarAlumnos9999999999->num_rows > 0 ) {
                                while( $filaAlumnos9999999999 = $buscarAlumnos9999999999->fetch_assoc() ) {
                                 $porductos.= $productoquanty." ".$filaAlumnos9999999999['nombre'].", ";	
                                }
                            }
                                        
                        }      
                    }
                                    
                               $porductos = substr($porductos, 0, -2);
                                    
                                    
                           $valorPeso = $filaAlumnos77['total_price_cop'];
                           $valorbolivar = $filaAlumnos77['total_price_bs'];
                       
                                    
                                    
                                  $hora = $filaAlumnos77['created'];
                                    
                                    if($filaAlumnos77['status'] == 1){
                                        $tipo = "V";
                                    }elseif($filaAlumnos77['status'] == 2){
                                        $tipo = "C";
                                    }elseif($filaAlumnos77['status'] == 4){
                                        $tipo = "M";
                                    }
                              
                                    
                                 switch($filaAlumnos77['tipoPago'])  {
                                         case("1"):
                                         $tipoPago = "Punto";
                                         break;
                                     
                                         case("2"):
                                         $tipoPago = "Pago Movil";
                                         break;
                                     
                                         case("3"):
                                         $tipoPago = "Transferencia";
                                         break;
                                     
                                         case("4"):
                                         $tipoPago = "Bs Efectivo";
                                         break;
                                     
                                         case("5"):
                                         $tipoPago = "Dolares";
                                         break;
                                     
                                         case("6"):
                                         $tipoPago = "Pesos";
                                         break;
                                     
                                         case("7"):
                                         $tipoPago = "Biopago";
                                         break;
                                     
                                         case("8"):
                                         $tipoPago = "Fraccionado";
                                         break;
                                 } 
                                    
                                    
                                    
                                    
                                    
                                $lahora =  str_split($hora,2);
                                $horaDef =  $lahora[5].$lahora[6].$lahora[7].$lahora[8].$lahora[9].$lahora[10];      
                                     
                            $html .= ' 
                             <tr class="even pointer">
          
                            <td class=" ">'.$contador++.'</td>
                            <td>'.$tipo.'</td>
                            <td>'.$tipoPago.'</td>
                            <td>'.$usuario1.'</td>
                           <td>'.$horaDef.'</td>
                            <td>$'.number_format($filaAlumnos77['total_price'],'2', ',','.').'</td>
                            <td>'.number_format($valorPeso,'0', ',','.').' COP</td>
                            <td>'.number_format($valorbolivar,'0', ',','.').' Bs</td>
                            <td>'.$porductos.'</td>
                      
          
                          </tr> ';
                                
                                $porductos = "";
                          
                                }
                            }


$html.='   
                 </tbody>     </table>
                 
                 
<p>(V) Venta al detal. (M) Venta al mayor. (C) Creditos</p>  
                       
                       
                       
                       
 <p style="text-align:center"><span style="font-size:15px">PRODUCTOS CON STOCK CRITICO</span><br></p>
<table>
    
 <thead>
<tr>
<th>Producto</th>
<th>En stock</th>
<th>Ultimo valor de compra</th>
<th>Unidades</th>
</tr>
</thead>
';

$query22 = "SELECT * FROM productos WHERE stock<='$stockCritico' AND activo='0' ORDER BY nombre ASC";
$buscarAlumnos22 = $conexion->query( $query22 );
if ( $buscarAlumnos22->num_rows > 0 ) {
    while( $filaAlumnos22 = $buscarAlumnos22->fetch_assoc() ) {
     
       $var1 = $filaAlumnos22["precio_compra"];
       $var2 = $filaAlumnos22["cantidad_unidades"];
        
       $nameProducto = $filaAlumnos22["nombre"];
       $nameProducto = strtoupper($nameProducto);
        $html .= '
        <tbody>
<tr>
<td>'.$nameProducto.'</td>
<td>'.$filaAlumnos22["stock"].'</td>
<td>$ '.$var1.'</td>
<td>'.$var2.'</td>
</tr>
</tbody>
  
';
    }
$html .= '  
           </table>  <p>Cantidad para stock critico establecida en el sistema: '.$stockCritico.'</p>  
           <footer>REPORTE DE CIERRE DE JORNADA DE "I-SELLER".</footer>';
}

if($_SESSION['nivel'] != "1"){
    $modoDescarga = "F";
    // wl documento se descarga
}else{
    $modoDescarga = "I";  
    // el documento se abre
}



$mpdf = new mPDF( 'c', 'A4' );
$css = file( 'style.css' );
$mpdf->writeHTML( $css, 1 );
$mpdf->writeHTML( $html );
$mpdf->Output( 'CIERRE.pdf', ''.$modoDescarga.'' );

    


    
    
    if($modoDescarga == "F" && $cierre == "1"){
        
$Nombre = "REPORTE DE CIERRE DE JORNADA";
$Email = "MI TIENDA";
$Mensaje = "Si desea desactivar los reportes por correo, diríjase a la configuración de MÍ TIENDA y desmarque las casillas: REPORTE DE CIERRE DE JORANADA Y CORTES AUTOMATICOS.";
$archivo = 'CIERRE.pdf';




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
    $mail->Body     =  "MOTIVO: $Nombre \n<br />".    
    "REMITENTE: $Email \n<br />".    
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
    if ($mail->Send()){
        
 if(unlink('CIERRE.pdf')){
           
    echo "<script>
			      window.open('../../production/consultaHistorica.php?accion=enviado', '_self');
                    </script>;";     

 
 }
    }else{

        
    if(unlink('CIERRE.pdf')){
           echo "<script>
			  window.open('../../production/consultaHistorica.php?accion=conexion', '_self');
              </script>;";
               
              }
     
    

    
    }
    
    }else{
       echo "<script>
			  window.open('../../production/consultaHistorica.php?accion=correo', '_self');
              </script>;";  
    }
