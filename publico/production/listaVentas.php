<?php

require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if (!$_GET['mesConsulta']) {
        $mesConsulta = date('Y-m');
    } else {
        $mesConsulta = date('Y') . '-' . $_GET['mesConsulta'];
    }
    $topnav = topnav();

    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Ventas == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    if ($_SESSION['validate'] != 'ok') {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    $query = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

            $PesoDolar = $filaAlumnos['pesoDolar'];

            $dolarBolivar = $filaAlumnos['DolarBolivar'];
        }
    }
    $query2 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $stockCritico = $filaAlumnos2['stockCritico'];
        }
    }

    // initializ shopping cart class
    include 'La-carta.php';
    $cart = new Cart;

    $today = date('Y-m-d');

    if (isset($_POST['fechaSolic'])) {
        $consultada = str_split($_POST['fechaSolic'], 1);
        $consultadaAno = $consultada[0] . $consultada[1] . $consultada[2] . $consultada[3];
        $consultadaMes = $consultada[5] . $consultada[6];
        $consultadaDia = $consultada[8] . $consultada[9];



        if ($consultadaAno > date('Y')) {
            $hayunError = "SI";
        } else {
            if ($consultadaMes > date('m') && $$consultadaAno >= date('Y')) {
                $hayunError = "SI";
            } else {
                if ($consultadaDia > date('d') && $consultadaMes >= date('m')) {
                    $hayunError = "SI";
                } else {
                    $today = $_POST['fechaSolic'];
                }
            }
        }
    }


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
            if ($porcentajeCubiertoEnBolivares >= 100) {                             // SI EL PORCENTAJE ES IGUAL O MAYOR QUE 100 ENTONCES SACO LAS GANANCIAS
                $residuoBs += $totalEnBs - $precioNeto04Bs;                            // la ganancia en bolivares es igual al total abonado por el cliente menos el costo
                $residuoPesos += $TotalEnpesos;                                        // La ganancias en pesos es igual al total abonado por el cliente
                $residuoDolar += $TotalEndolares;                                      // La ganancias en dolares es igual al total abonado por el cliente
            } else {

                $precioNeto04Peso = $precioNeto04Peso - ($porcentajeCubiertoEnBolivares * $precioNeto04Peso / 100);         // Saco el nuevo *precio neto* en peso y doalres, siendo igual a sus 
                $precioNeto04Dolar = $precioNeto04Dolar - ($porcentajeCubiertoEnBolivares * $precioNeto04Dolar / 100);      // totales menos el porcentaje que ya ha sido cancelado en bolivares



                $porcentajeCubiertoEnPesos = $TotalEnpesos * 100 / $precioNeto04Peso;     // Saco el porcentaje que representa los cancelado en pesos al nuevo total sacado en el paso anterior
                if ($porcentajeCubiertoEnPesos >= 100) {                                // SI EL PORCENTAJE ES IGUAL O MAYOR QUE 100 ENTONCES SACO LAS GANANCIAS
                    $residuoBs += 0;                                                  // las ganancias en bolivares son de cero
                    $residuoPesos += $TotalEnpesos - $precioNeto04Peso;               // las ganancias en pesos son igual al total abonado por el cliente menos el nuevo *precio neto
                    $residuoDolar += $TotalEndolares;                                 // La ganancias en dolares es igual al total abonado por el cliente

                } else {

                    $precioNeto04Dolar = $precioNeto04Dolar - ($porcentajeCubiertoEnPesos * $precioNeto04Dolar / 100);
                    $porcentajeCubiertoEnDolares = $TotalEndolares * 100 / $precioNeto04Dolar;
                    if ($porcentajeCubiertoEnDolares >= 100) {
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

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////






    $query00 = "SELECT * FROM orden WHERE modified='$today' AND status='1'";
    $buscarAlumnos00 = $conexion->query($query00);
    if ($buscarAlumnos00->num_rows > 0) {
        while ($filaAlumnos00 = $buscarAlumnos00->fetch_assoc()) {
            $totalVentas += 1;
            $total += $filaAlumnos00['total_price'];
        }
    }

    if ($totalCreditosValor == '') {
        $totalCreditosValor = '0';
    }
    if ($total == '') {
        $total = '0';
    }

    $query000000 = "SELECT * FROM orden WHERE modified='$today' AND status='4'";
    $buscarAlumnos000000 = $conexion->query($query000000);
    if ($buscarAlumnos000000->num_rows > 0) {
        while ($filaAlumnos000000 = $buscarAlumnos000000->fetch_assoc()) {
            $totalVentas2 += 1;
            $total2 += $filaAlumnos000000['total_price_bs'];
        }
    }

    if ($total2 == '') {
        $total2 = '0';
    }

    $montotTotal = $total + $total2;

    $totalVentasaaaaaaa = 0;
    $queryaaaaaaa = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='8' AND status='1' OR modified='$today' AND tipoPago='8' AND status='4'";
    $buscarAlumnosaaaaaaa = $conexion->query($queryaaaaaaa);
    if ($buscarAlumnosaaaaaaa->num_rows > 0) {
        while ($filaAlumnosaaaaaaa = $buscarAlumnosaaaaaaa->fetch_assoc()) {
            $ordenSekec = $filaAlumnosaaaaaaa['id'];

            $query0000000 = "SELECT * FROM fracciones WHERE id_order='$ordenSekec'";
            $buscarAlumnos0000000 = $conexion->query($query0000000);
            if ($buscarAlumnos0000000->num_rows > 0) {
                while ($filaAlumnos0000000 = $buscarAlumnos0000000->fetch_assoc()) {

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
    $totalBs =  $punto + $pagoMovil + $transferencia + $bioPago +  $efectivo;



    ///////////// MFS /////////////
    function returnGanancias($tipo){
        global $conexion;
        global $today;
        $ganancias = 0;

        $sqlGanancias = "SELECT * FROM orden WHERE modified='$today' AND status='$tipo'";
        $search = $conexion->query($sqlGanancias);
        if ($search->num_rows > 0) {
            while ($row = $search->fetch_assoc()) {
                $idOrder = $row['id'];
                $descontado = $row['descontado'];

                $query0000003344 = "SELECT * FROM orden_articulos WHERE order_id='$idOrder'";
                $buscarAlumnos0000003344 = $conexion->query($query0000003344);
                if ($buscarAlumnos0000003344->num_rows > 0) {
                    while ($filaAlumnos0000003344 = $buscarAlumnos0000003344->fetch_assoc()) {
    
                        $precioCompra = $filaAlumnos0000003344['precio'] * $filaAlumnos0000003344['quantity'];
                        $precioVenta = $filaAlumnos0000003344['precio_venta_dolar'] * $filaAlumnos0000003344['quantity'];

                        
                        if ($tipo == '4') {
                            $precioVenta = $precioVenta - ($precioVenta * $descontado / 100);
                            $ganancias += $precioVenta - $precioCompra; 
                        }else {
                            $ganancias += $precioVenta - $precioCompra; 
                        }

    
                    }
                }
            }
        }
        return $ganancias;
    }
     ///////////// MFS /////////////

    $totalVentas1111111 = 0;
    $query1111111 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='1' AND status='1' OR modified='$today' AND tipoPago='1' AND status='4'";
    $buscarAlumnos1111111 = $conexion->query($query1111111);
    if ($buscarAlumnos1111111->num_rows > 0) {
        while ($filaAlumnos1111111 = $buscarAlumnos1111111->fetch_assoc()) {
            $totalVentas1111111 += 1;
            $total1111111 += $filaAlumnos1111111['total_price_bs'];
        }
    }
    // PUNTO DE VENTA/////////////////////////

    $totalVentas222222 = 0;
    $query222222 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='2' AND status='1' OR modified='$today' AND tipoPago='2' AND status='4'";
    $buscarAlumnos222222 = $conexion->query($query222222);
    if ($buscarAlumnos222222->num_rows > 0) {
        while ($filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc()) {
            $totalVentas222222 += 3;
            $total222222 += $filaAlumnos222222['total_price_bs'];
        }
    }
    // PAGO MOVIL

    $totalVentas33333333 = 0;
    $query33333333 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='3' AND status='1' OR modified='$today' AND tipoPago='3' AND status='4'";
    $buscarAlumnos33333333 = $conexion->query($query33333333);
    if ($buscarAlumnos33333333->num_rows > 0) {
        while ($filaAlumnos33333333 = $buscarAlumnos33333333->fetch_assoc()) {
            $totalVentas33333333 += 3;
            $total33333333 += $filaAlumnos33333333['total_price_bs'];
        }
    }
    // TRANSFERENCIA

    $totalVentas4444444 = 0;
    $query4444444 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='4' AND status='1' OR modified='$today' AND tipoPago='4' AND status='4'";
    $buscarAlumnos4444444 = $conexion->query($query4444444);
    if ($buscarAlumnos4444444->num_rows > 0) {
        while ($filaAlumnos4444444 = $buscarAlumnos4444444->fetch_assoc()) {
            $totalVentas4444444 += 3;
            $total4444444 += $filaAlumnos4444444['total_price_bs'];
        }
    }
    // BS EFECTIVO
    $totalVentas55555 = 0;
    $query55555 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='5' AND status='1' OR modified='$today' AND tipoPago='5' AND status='4'";
    $buscarAlumnos55555 = $conexion->query($query55555);
    if ($buscarAlumnos55555->num_rows > 0) {
        while ($filaAlumnos55555 = $buscarAlumnos55555->fetch_assoc()) {
            $totalVentas55555 += 3;
            $total55555 += $filaAlumnos55555['total_price'];
        }
    }
    // DOLARES
    $totalVentas666666 = 0;
    $query666666 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='6' AND status='1' OR modified='$today' AND tipoPago='6' AND status='4'";
    $buscarAlumnos666666 = $conexion->query($query666666);
    if ($buscarAlumnos666666->num_rows > 0) {
        while ($filaAlumnos666666 = $buscarAlumnos666666->fetch_assoc()) {
            $totalVentas666666 += 3;
            $total666666 += $filaAlumnos666666['total_price_cop'];
        }
    }
    // PESOS

    $totalVentas7777777 = 0;
    $query7777777 = "SELECT * FROM orden WHERE modified='$today' AND tipoPago='7' AND status='1' OR modified='$today' AND tipoPago='7' AND status='4'";
    $buscarAlumnos7777777 = $conexion->query($query7777777);
    if ($buscarAlumnos7777777->num_rows > 0) {
        while ($filaAlumnos7777777 = $buscarAlumnos7777777->fetch_assoc()) {
            $totalVentas7777777 += 3;
            $total7777777 += $filaAlumnos7777777['total_price_bs'];
        }
    }
    // BIOPAGO





    $textoMes1 = 'ENERO';
    $textoMes2 = 'FEBRERO';
    $textoMes3 = 'MARZO';
    $textoMes4 = 'ABRIL';
    $textoMes5 = 'MAYO';
    $textoMes6 = 'JUNIO';
    $textoMes7 = 'JULIO';
    $textoMes8 = 'AGOSTO';
    $textoMes9 = 'SEPTIEMBRE';
    $textoMes10 = 'OCTUBRE';
    $textoMes11 = 'NOVIEMBRE';
    $textoMes12 = 'DICIEMBRE';



    switch (date('N')) {
        case (1):
            $textoDia = 'LUNES';
            break;
        case (2):
            $textoDia = 'MARTES';
            break;
        case (3):
            $textoDia = 'MIERCOLES';
            break;
        case (4):
            $textoDia = 'JUEVES';
            break;
        case (5):
            $textoDia = 'VIERNES';
            break;
        case (6):
            $textoDia = 'SABADO';
            break;
        case (7):
            $textoDia = 'DOMINGO';
            break;
    }



    if (isset($_POST['fechaSolic'])) {
        $miMes = $consultadaMes;
    } else {
        $miMes = date("m");
    }



    switch ($miMes) {
        case ('01'):
            $textoMes = 'ENERO';
            break;
        case ('02'):
            $textoMes = 'FEBRERO';
            break;
        case ('03'):
            $textoMes = 'MARZO';
            break;
        case (date('04')):
            $textoMes = 'ABRIL';
            break;
        case ('05'):
            $textoMes = 'MAYO';
            break;
        case ('06'):
            $textoMes = 'JUNIO';
            break;
        case ('07'):
            $textoMes = 'JULIO';
            break;
        case ('08'):
            $textoMes = 'AGOSTO';
            break;
        case ('09'):
            $textoMes = 'SEPTIEMBRE';
            break;
        case ('10'):
            $textoMes = 'OCTUBRE';
            break;
        case ('11'):
            $textoMes = 'NOVIEMBRE';
            break;
        case ('12'):
            $textoMes = 'DICIEMBRE';
            break;
    }





    function total($tipo)
    {
        global $today;
        global $conexion;
        $VentasMesDolar = 0;
        $query5 = "SELECT * FROM orden WHERE modified='$today' AND status='$tipo'";
        $buscarAlumnos25 = $conexion->query($query5);
        if ($buscarAlumnos25->num_rows > 0) {
            while ($filaAlumnos25 = $buscarAlumnos25->fetch_assoc()) {
                $VentasMesDolar += $filaAlumnos25['total_price'];
            }
            return $VentasMesDolar;
        } else {
            return 0;
        }
    
    }

    $query5 = "SELECT * FROM orden WHERE modified='$today' AND status='1' OR modified='$today' AND status='4'";
    $buscarAlumnos25 = $conexion->query($query5);
    if ($buscarAlumnos25->num_rows > 0) {
        while ($filaAlumnos25 = $buscarAlumnos25->fetch_assoc()) {
            $VentasMesDolar = $filaAlumnos25['total_price'];
            $totalVentasMesDolar += $VentasMesDolar;

            $VentasMesPesos = $filaAlumnos25['total_price_cop'];
            $totalVentasMesPesos += $VentasMesPesos;

            $VentasMesBolivar = $filaAlumnos25['total_price_bs'];
            $totalVentasMesBolivar += $VentasMesBolivar;
        }
    } else {
        $totalVentasMesDolar = 0;
        $totalVentasMesPesos = 0;
        $totalVentasMesBolivar = 0;
    }





    //////////////////GANANCIAS DEL mes//////////////////////////
    $query2222234 = "SELECT * FROM orden WHERE modified='$today' AND status='1' AND tipoPago='5' OR modified='$today' AND status='4' AND tipoPago='5'";
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



    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////GANANCIAS  PESOS//////////////////////////
    $query4444434 = "SELECT * FROM orden WHERE 
modified='$today' AND status='1' AND tipoPago='6' OR
modified='$today' AND status='4' AND tipoPago='6'";

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







?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
        <!-- Meta, title, CSS, favicons, etc. -->
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel='icon' href='images/favicon.ico' type='image/ico' />

        <title>Lista de Ventas</title>

        <!-- Bootstrap -->
        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <!-- Font Awesome -->
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <!-- NProgress -->
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <!-- iCheck -->
        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">
        <link href='../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css' rel='stylesheet'>
        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>
        <script src='js/jquery.min.js'></script>
        <script src='peticion.js'></script>
        <script src='peticion_producto.js'></script>
        <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>
        <script src='ex/jquery.min.js'></script>
        <script src='ex/bootstrap.min.js'></script>

        <?php

        if ($hayunError == "SI") {
            echo '<script>
            function mensaje(){	
			alertify.error("Error en la fecha consultada.");}
            </script>
            <body onload="mensaje()">
            </body>';
        }
        ?>


    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>


                    <div class='left_col scroll-view'>
                        <div class='navbar nav_title' style='border: 0;'>
                            <a href='index.php' class='site_title'>
                                <img src='images/logo1-inv-compact.png' style='max-width:45px; opacity: 0.8'> <span>
                                    <img style='max-width:140px'><span> </a>
                        </div>
                        <div class='clearfix'></div>
                        <!-- /menu profile quick info -->
                        <br />
                        <?php echo $menu ?>
                    </div>
                </div>

                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->

                <!-- page content -->
                <div class='right_col' role='main'>

                    <div class=''>

                        <h4>Ventas</h4>
                        <p style="margin-top: -10px;">Ventas del dia</p>

                        <div class='clearfix'></div>

                        <div class='row   fadeInUp animated'>




                            <div class='col-lg-9'>
                                <div class='x_panel  '>
                                    <div class='x_title'>
                                        <h2>Detalles <small><?php


                                                            if (isset($_POST['fechaSolic'])) {
                                                                echo "FECHA CONSULTADA:";
                                                                $diaFecha = $consultada[8] . $consultada[9];
                                                                $anoDate = $consultada[0] . $consultada[1] . $consultada[2] . $consultada[3];
                                                            } else {
                                                                echo $textoDia;
                                                                $diaFecha = date('d');
                                                                $anoDate =  date('Y');
                                                            }




                                                            echo " " . $diaFecha . " DE " . $textoMes . " DEL " . $anoDate;




                                                            ?></small> </h2>

                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>


                                        <div class='row'>


                                            <div class='col-lg-10'>

                                                <div class='animated flipInY col-lg-2' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/PUNTO-DE-VENTA.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px"><?php echo number_format($total1111111 + $punto, '2', '.', '.'); ?></span>
                                                    </div>
                                                    <h4>PUNTO DE VENTA.</h4>
                                                </div>


                                                <div class='animated flipInY col-lg-2 ' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/PAGO-MOVIL.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px"> <?php echo number_format($total222222 + $pagoMovil, '2', '.', '.'); ?></span>
                                                    </div>
                                                    <h4>PAGO MOVIL.</h4>
                                                </div>

                                                <div class='animated flipInY col-lg-2' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/TRANSFERENCIA.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px"> <?php echo number_format($total33333333 + $transferencia, '2', '.', '.'); ?></span>
                                                    </div>
                                                    <h4>TRANSFERENCIA.</h4>
                                                </div>




                                                <div class='animated flipInY col-lg-2 ' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/BIOPAGO.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px"><?php echo number_format($total7777777 + $bioPago, '2', '.', '.'); ?></span>
                                                    </div>
                                                    <h4>BIOPAGO.</h4>
                                                </div>

                                                <div class='animated flipInY col-lg-2 ' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/EFECTIVO-BOLIVAR.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px"><?php echo number_format($total4444444 +  $efectivo, '2', '.', '.'); ?></span>
                                                    </div>
                                                    <h4>EFECTIVO.</h4>
                                                </div>

                                                <div class='animated flipInY col-lg-2 ' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/EFECTIVO-DOLAR.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px"> <?php echo number_format($total55555 + $dolares, '2', '.', '.'); ?></span>
                                                    </div>
                                                    <h4>DOLARES.</h4>
                                                </div>


                                            </div>
                                            <div class="col-lg-2 ">
                                                <div class='animated flipInY col-lg-12' style="text-align:center">
                                                    <div class='icon iconPerso'>
                                                        <br><img src='images/EFECTIVO-PESOS.png' height='60px' alt='BOLIVAR'><br>
                                                        <span style="font-size: 17px"><?php echo number_format($total666666 + $pesos, '0', '.', '.'); ?></span>
                                                    </div>
                                                    <h4>PESOS.</h4>
                                                </div>
                                            </div>



                                            <div class='col-lg-12'>
                                                <br>
                                                <div class='card-box table-responsive'>

                                                    <table id='datatable-responsive' class='table table-striped table-bordered' style='width:100%'>
                                                        <thead>
                                                            <tr class='headings'>
                                                                <th class='column-title'>#</th>
                                                                <th class='column-title'>T</th>
                                                                <th class='column-title'>Pago por</th>
                                                                <th class='column-title'>Usuario</th>
                                                                <th class='column-title'>Fecha</th>
                                                                <th class='column-title'>Monto</th>
                                                                <th class='column-title'>COP</th>
                                                                <th class='column-title'>Bs</th>
                                                                <th class='column-title'>Detalles</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            <?php

                                                            $query77 = "SELECT * FROM orden WHERE status='1' AND modified='$today' OR status='4'  AND modified='$today' ORDER BY id DESC LIMIT 150";
                                                            $buscarAlumnos77 = $conexion->query($query77);
                                                            if ($buscarAlumnos77->num_rows > 0) {
                                                                $contador = 1;
                                                                while ($filaAlumnos77 = $buscarAlumnos77->fetch_assoc()) {
                                                                    $users = $filaAlumnos77['customer_id'];
                                                                    $orderid = $filaAlumnos77['id'];

                                                                    $query999999999 = "SELECT * FROM usuarios WHERE id='$users'";
                                                                    $buscarAlumnos999999999 = $conexion->query($query999999999);
                                                                    if ($buscarAlumnos999999999->num_rows > 0) {
                                                                        while ($filaAlumnos999999999 = $buscarAlumnos999999999->fetch_assoc()) {
                                                                            $usuario1 = $filaAlumnos999999999['nombre'];
                                                                        }
                                                                    }

                                                                    $query7E = $conexion->query("SELECT * FROM orden_articulos WHERE order_id='$orderid' ");
                                                                    if ($query7E->num_rows > 0) {

                                                                        while ($row7E = $query7E->fetch_assoc()) {
                                                                            $producto  = $row7E['product_id'];
                                                                            $productoquanty  = $row7E['quantity'];

                                                                            $query9999999999 = "SELECT * FROM productos WHERE id='$producto'";
                                                                            $buscarAlumnos9999999999 = $conexion->query($query9999999999);
                                                                            if ($buscarAlumnos9999999999->num_rows > 0) {
                                                                                while ($filaAlumnos9999999999 = $buscarAlumnos9999999999->fetch_assoc()) {
                                                                                    $porductos .= $productoquanty . ' ' . $filaAlumnos9999999999['nombre'] . ', ';
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                    $porductos = substr($porductos, 0, -2);
                                                                    $valorPeso = $filaAlumnos77['total_price_cop'];
                                                                    $valorbolivar = $filaAlumnos77['total_price_bs'];

                                                                    switch ($filaAlumnos77['tipoPago']) {

                                                                        case ('1'):
                                                                            $pagoPor = 'Punto';
                                                                            break;

                                                                        case ('2'):
                                                                            $pagoPor = 'Pago Movil';
                                                                            break;

                                                                        case ('3'):
                                                                            $pagoPor = 'Transferencia';
                                                                            break;

                                                                        case ('4'):
                                                                            $pagoPor = 'BS Efectivo';
                                                                            break;

                                                                        case ('5'):
                                                                            $pagoPor = 'Dolares';
                                                                            break;

                                                                        case ('6'):
                                                                            $pagoPor = 'Pesos';
                                                                            break;
                                                                        case ('7'):
                                                                            $pagoPor = 'Biopago';
                                                                            break;
                                                                        case ('8'):
                                                                            $pagoPor = 'Fraccionado';
                                                                            break;
                                                                    }

                                                                    if ($filaAlumnos77['status'] == '4') {
                                                                        $tVenta = 'M';
                                                                    } elseif ($filaAlumnos77['status'] == '1') {
                                                                        $tVenta = 'V';
                                                                    }

                                                                    echo '
                             <tr class="even pointer">
          
                            <td class=" ">' . $contador++ . '</td>
                                                        <td>' . $tVenta . '</td>

                            <td>' . $pagoPor . '</td>
                            
                            
                            
                            
                            <td>' . $usuario1 . '</td>
                             <td>' . $filaAlumnos77['created'] . '</td>

                            <td>$' . number_format($filaAlumnos77['total_price'], '2', ',', '.') . '</td>
                            <td>' . number_format($valorPeso, '0', ',', '.') . ' </td>
                            <td>' . number_format($valorbolivar, '2', ',', '.') . ' </td>
                            <td><a href="detallesVenta.php?id='.$filaAlumnos77['id'].'">Detalles</a></td>

                      
                            
                            
          
                          </tr>';

                                                                    $porductos = '';
                                                                }
                                                            }

                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class='col-lg-3'>



                                <div class='x_panel tile'>
                                    <div class='x_title'>
                                        <form action="" method="post" class='nav navbar-right panel_toolbox' style="    margin-top: -4px;">
                                            <li><input required type="date" class="form-control control2" name="fechaSolic"></li>
                                            <li><input type="submit" class="btn btn-success info2" value="Filtrar"></li>
                                        </form>
                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>



                                        <div class='col-lg-12'> <br>

                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">BOLIVARES</h5>
                                                    <span><?php $sumaBolivar = $total1111111 + $total222222 + $total33333333 + $total4444444 + $total7777777 + $totalBs;
                                                            echo number_format($sumaBolivar, '2', '.', '.'); ?> - Total de ventas </span>
                                                    <p><?php echo number_format($gananciasMesBolivar + $residuoBs, '2', '.', '.'); ?> - Ganancias.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div  class="icon"><br><img src='images/EFECTIVO-BOLIVAR.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>





                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">DOLARES</h5>
                                                    <span><?php echo number_format($total55555 + $dolares, '2', '.', '.'); ?> - Total de ventas </span>
                                                    <p><?php echo number_format($gananciasMes + $residuoDolar, '2', '.', '.'); ?> - Ganancias.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br><img src='images/EFECTIVO-DOLAR.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>





                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">PESOS</h5>
                                                    <span><?php echo number_format($total666666 + $pesos, '0', '.', '.'); ?> - Total de ventas </span>
                                                    <p> <?php echo number_format($gananciasMesPeso + $residuoPesos, '2', '.', '.'); ?> - Ganancias.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br><img src='images/EFECTIVO-PESOS.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>



                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">BOLIVARES</h5>
                                                    <span><?php echo number_format($totalVentasMesBolivar, '2', '.', '.'); ?>Bs </span>
                                                    <p>Conversión a bolivares.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br>
                                                        <i class="line icon-reload"></i>
                                                    </div>
                                                </div>
                                            </div>




                                            <div class="fila ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">DOLARES</h5>
                                                    <span>$ <?php echo number_format($totalVentasMesDolar, '2', '.', '.'); ?> </span>
                                                    <p>Conversión a dolares.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br>
                                                        <i class="line icon-reload"></i>
                                                    </div>
                                                </div>
                                            </div>




                                            <div class="fila  ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">PESOS</h5>
                                                    <span><?php echo number_format($totalVentasMesPesos, '0', '.', '.'); ?> COP </span>
                                                    <p>Conversión a pesos.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br>
                                                        <i class="line icon-reload"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-12">
                                                <hr>
                                            </div>



                                            <div class="fila">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">Mayor</h5>
                                                    <span>$<?php echo number_format(total('4'), '2', '.', '.'); ?> </span>
                                                      <p>$<?php echo number_format(returnGanancias('4'), '2', '.', '.') ?> Ganancias.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br>
                                                    <div class="icon"><br><img src='images/icono/ganancia.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="fila  ">
                                                <div class="col-lg-9">
                                                    <h5 class="h3edit">Detal</h5>
                                                    <span>$<?php echo number_format(total('1'), '2', '.', '.'); ?> </span>
                                                    <p>$<?php echo number_format(returnGanancias('1'), '2', '.', '.') ?> Ganancias.</p>
                                                </div>
                                                <div class="col-lg-3">
                                                    <div class="icon"><br>
                                                    <div class="icon"><br><img src='images/icono/valordelstock.png' alt='BOLIVAR'>
                                                    </div>
                                                </div>
                                            </div>










                                        </div>

                                        <style>
                                            .iconPerso {
                                                font-size: 28px !important;
                                            }

                                            .tile-stats {
                                                box-shadow: none !important;
                                            }

                                            .control2 {
                                                max-width: 170px !important;
                                                border: none;
                                                margin-bottom: 0 !important;
                                            }

                                            .info2 {
                                                max-height: 50px !important;
                                                opacity: 0.4
                                            }

                                            .info2:hover {
                                                opacity: 1
                                            }

                                            .subg {
                                                color: #BAB8B8;
                                                font-size: 12px !important;
                                                margin-left: 0 !important;
                                                margin-top: -5 !important;
                                            }
                                        </style>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class='row' style='display: block;'>


                        </div>
                    </div>
                </div>
                <!-- /page content -->

                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        I-SELLER - by <a href='#'>Jose Ricardo Tovarg III</a>
                    </div>
                    <div class='clearfix'></div>
                </footer>
                <!-- /footer content -->
            </div>
        </div>

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- FastClick -->
        <script src='../vendors/fastclick/lib/fastclick.js'></script>
        <!-- NProgress -->
        <script src='../vendors/nprogress/nprogress.js'></script>
        <!-- iCheck -->
        <script src='../vendors/iCheck/icheck.min.js'></script>
        <!-- Datatables -->
        <script src='../vendors/datatables.net/js/jquery.dataTables.min.js'></script>
        <script src='../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/dataTables.buttons.min.js'></script>
        <script src='../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.flash.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.html5.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.print.min.js'></script>
        <script src='../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js'></script>
        <script src='../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js'></script>
        <script src='../vendors/datatables.net-responsive/js/dataTables.responsive.min.js'></script>
        <script src='../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js'></script>
        <script src='../vendors/datatables.net-scroller/js/dataTables.scroller.min.js'></script>
        <script src='../vendors/jszip/dist/jszip.min.js'></script>
        <script src='../vendors/pdfmake/build/pdfmake.min.js'></script>
        <script src='../vendors/pdfmake/build/vfs_fonts.js'></script>

        <!-- Custom Theme Scripts -->
        <script src='../build/js/custom.min.js'></script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>