<?php
require_once('configuracion.php'); 

    $fdeCompra = date( 'd-m-Y h:i a' );
    $SdeCompra = date( 'Y-W' );
    $mdeCompra = date( 'Y-m' );
    $ddeCompra = date( 'Y-m-d' );
    $fdeUser = $_SESSION['nombre'];
        
     $query333 = "SELECT * FROM cambio WHERE id='1'";
$buscarAlumno333s = $conexion->query( $query333 );
if ( $buscarAlumno333s->num_rows > 0 ) {
    while( $filaAlumnos333 = $buscarAlumno333s->fetch_assoc() ){
        
        $PesoDolar = $filaAlumnos333['pesoDolar'];
        $dolarBolivar = $filaAlumnos333['DolarBolivar'];
  
    }
}   
//////////////////////////////////////////

if(isset($_GET['tipo']) && $_GET['tipo'] == "configurar"){
    $accion = $_GET['accion'];
    

    $stmt_o = $conexion->prepare("UPDATE empresa SET factura='$accion' WHERE id='1'");
    $stmt_o->execute();
    $stmt_o -> close();

    define( 'PAGINA_RETORNO', '../publico/production/configuracion.php?accion=accionFactura' );
    header( 'Location: '.PAGINA_RETORNO );
    }  

//////////////////////////////////////////  ESTO ACTIVA O DESACTIVA EL REGISTRO DE FACTURAS


if(isset($_GET['idPagar'])){
    $idactivar = $_GET['idPagar'];
    

      
    $stmt_o = $conexion->prepare("UPDATE compras SET status='1' WHERE factura='$idactivar'");
$stmt_o->execute();
$stmt_o -> close();
    
    
    define( 'PAGINA_RETORNO', '../publico/production/nuevaCompraFacturas.php?accion=mensajePagado' );
        header( 'Location: '.PAGINA_RETORNO );
        }  

//////////////////////////////////////////   ESTO ES PARA PAGAR LAS FACTURAS PENDIENTES



if(isset($_GET['idDeshacer'])){
    $idCompra = $_GET['idDeshacer'];
    
         $query2 = "SELECT * FROM compras WHERE id='$idCompra'";
    $buscarAlumnos2 = $conexion->query( $query2 );
    if ( $buscarAlumnos2->num_rows > 0 ) {
        while( $filaAlumnos2 = $buscarAlumnos2->fetch_assoc() ){

            $cod = $filaAlumnos2['cod'];
            $cantidadAgregada = $filaAlumnos2['cantidad'];
        }
    }    
        
        $query = "SELECT * FROM productos WHERE codigo='$cod'";
    $buscarAlumnos = $conexion->query( $query );
    if ( $buscarAlumnos->num_rows > 0 ) {
        while( $filaAlumnos = $buscarAlumnos->fetch_assoc() ){
            $cantidadActual = $filaAlumnos['stock'];
        }
    } 
          $cantidadFinal = $cantidadActual - $cantidadAgregada ;


$stmt_o = $conexion->prepare("UPDATE productos SET stock='$cantidadFinal' WHERE codigo='$cod'");
$stmt_o->execute();
$stmt_o -> close();



$stmt_o = $conexion->prepare("DELETE FROM compras WHERE id='$idCompra'");
$stmt_o->execute();
$stmt_o -> close();











    $query22 = "SELECT * FROM empresa";
$buscarAlumnos22 = $conexion->query( $query22 );
if ( $buscarAlumnos22->num_rows > 0 ) {
    while( $filaAlumnos22 = $buscarAlumnos22->fetch_assoc() )
    {
       
        $deshacerCompra = $filaAlumnos22['deshacerCompra'];
    }
}
    $deshacerCompra -= 1;
    if($deshacerCompra <= 0){
       $deshacerCompra = 0; 
    }

    

    
$stmt_o = $conexion->prepare("UPDATE empresa SET deshacerCompra='$deshacerCompra' WHERE id='1'");
$stmt_o->execute();
$stmt_o -> close();


    if(isset($_GET['origen']) && $_GET['origen'] == 'simple'){
        define( 'PAGINA_RETORNO', '../publico/production/nuevaCompra.php?accion=mensajeDeshacer' );
        header( 'Location: '.PAGINA_RETORNO );  
    }else{
        
     define( 'PAGINA_RETORNO', '../publico/production/nuevaCompraFacturas.php?accion=mensajeDeshacer' );
        header( 'Location: '.PAGINA_RETORNO );
    }
        }        

//////////////////////////////////////////   ESTO ES PARA DESHACER UNA COMPRA QUE NO SE AGREGO CORRECTAMENTE


if($_GET['accion'] == 'vaciarF'){
       $_SESSION['facturaCompleta'] = array(); 
       $_SESSION['facturaCompletaMostrada'] = array(); 
            unset($_SESSION['proveedor']);
            unset($_SESSION['factura']);
            unset($_SESSION['fechaFactura']);
            unset($_SESSION['totalFa']);
            unset($_SESSION['statusFactura']);
        
        }

//////////////////////////////////////////  ESTO VACIA EL ARRAY QUE ALMACENA LOS DATOS DE LAS FACTURAS

if(isset($_POST['producto'])){
        
    if(isset($_POST['status'])){
    $_SESSION['statusFactura']  = strip_tags( addslashes( $_POST['status'] ) );  
    }    

    $_SESSION['proveedor']  = strip_tags( addslashes( $_POST['proveedor'] ) );            //   DEFINO LA VARIABLE DE SESION PARA EL NOMBRE DEL PROVEEDOR
    $_SESSION['factura']  = strip_tags( addslashes( $_POST['factura'] ) );                //   DEFINO LA VARIABLE DE SESION PARA EL NUMERO DE LA FACTURA
    $_SESSION['fechaFactura']  = strip_tags( addslashes( $_POST['fechaFactura'] ) );      //   DEFINO LA VARIABLE DE SESION PARA LA FECHA DE LA FACTURA
            
    $nombrepor  = strip_tags( addslashes( $_POST['nombrepor'] ) );
    $codigo  = strip_tags( addslashes( $_POST['producto'] ) );
    $precio = strip_tags( addslashes( $_POST['precio'] ) );
    $Porcentaje = strip_tags( addslashes( $_POST['porcentaje'] ) );
    $cantidadNueva = strip_tags( addslashes( $_POST['comprado'] ) );
    $cantidadporprecio = strip_tags( addslashes( $_POST['cantidad'] ) );
   
    $Tasas_dolares = strip_tags( addslashes( $_POST['Tasas_dolares'] ) );
    $Tasas_pesos = strip_tags( addslashes( $_POST['Tasas_pesos'] ) );
    $precioMonedaOrigen = strip_tags( addslashes( $_POST['precioMonedaOrigen'] ) );
    $moneda = strip_tags( addslashes( $_POST['moneda'] ) );


    $resultado5 = strip_tags( addslashes( $_POST['resultado5'] ) );   /// BOLIVAR UNITARIO
    $resultado6 = strip_tags( addslashes( $_POST['resultado6'] ) );   /// PESO UNITARIO

    $_SESSION['numero'] += 1;

    $precio = str_replace(',', '.', $precio);
    $precio2 = $precio / $cantidadporprecio;
    $precio2 = $precio2 * $cantidadNueva;


       
        $valoresM = '
        <div class="myRow">
        <div class="myRowLeft">
            <strong>'.$nombrepor.'</strong>
            <p>Cantidad <strong>'.$cantidadNueva.'</strong> </p>
        </div>
    
        <div class="myRowRight">
    
            <strong>Precio</strong>
            <p>$'.$precio2.'
            </p>
    
        </div>
    </div>
        ';
        
    $valores='("'.$_SESSION['proveedor'].'","'.$_SESSION['factura'].'","'.$_SESSION['fechaFactura'].'","'.$_SESSION['statusFactura'].'","'.$codigo.'","'.$nombrepor.'","'.$precio.'","'.$cantidadNueva.'","'.$cantidadporprecio.'","'.$Porcentaje.'","'.$fdeCompra.'","'.$fdeUser.'","'.$SdeCompra.'","'.$ddeCompra.'","'.$mdeCompra.'"),';

    $valoresQ= substr($valores, 0, -1);
    
    $_SESSION['facturaCompletaMostrada'][$codigo] = $valoresM;
    $_SESSION['facturaCompleta'][$codigo] = $valoresQ;
    $_SESSION['totalFa'][$codigo] = $precio2;


}

//////////////////////////////////////////   ^ ESTO AGREGA LOS PRODUCTOS AL ARRAY DE LA FACTURA

if(isset($_GET['nuevoRegistro'])){

    foreach($_SESSION['facturaCompleta'] as $nombre) {



    echo $nombre."<br>";



    $insertar = "INSERT INTO compras (proveedor, factura, fechaFactura, status, cod, producto, precio, cantidad, CporPrecio, Porcentaje, fecha, user, semana, dia, mes) VALUES $nombre";
    $resultado2 = mysqli_query( $conexion, $insertar );
        
    $query2 = "SELECT * FROM compras ORDER BY id DESC LIMIT 1";
    $buscarAlumnos2 = $conexion->query( $query2 );
    if ( $buscarAlumnos2->num_rows > 0 ) {
        while( $filaAlumnos2 = $buscarAlumnos2->fetch_assoc() ){

            $cod = $filaAlumnos2['cod'];
            $cantidadAgregada = $filaAlumnos2['cantidad'];
            $precionuevo = $filaAlumnos2['precio'];
            $CporPrecio = $filaAlumnos2['CporPrecio'];
            $PorcentajeNuevo = $filaAlumnos2['Porcentaje'];

        }
    }    
        
    $query = "SELECT * FROM productos WHERE codigo='$cod'";
    $buscarAlumnos = $conexion->query( $query );
    if ( $buscarAlumnos->num_rows > 0 ) {
        while( $filaAlumnos = $buscarAlumnos->fetch_assoc() ){
            $cantidadActual = $filaAlumnos['stock'];
        }
    } 
    $cantidadFinal = $cantidadAgregada + $cantidadActual;

    echo $cantidadFinal;

   
        


    $stmt_o = $conexion->prepare("UPDATE productos SET precio_compra='$precionuevo', cantidad_unidades='$CporPrecio', porcentaje='$PorcentajeNuevo', stock='$cantidadFinal' WHERE codigo='$cod'");
    $stmt_o->execute();
    $stmt_o -> close();


        if(!$resultado2){
            echo "llamar a servicio tecnico: codigo: 0015";
        }else{
            $_SESSION['facturaCompleta'] = array(); 
            $_SESSION['facturaCompletaMostrada'] = array(); 
            unset($_SESSION['proveedor']);
            unset($_SESSION['factura']);
            unset($_SESSION['fechaFactura']);
            unset($_SESSION['totalFa']);
            unset($_SESSION['statusFactura']);
            
$query22 = "SELECT * FROM empresa";
$buscarAlumnos22 = $conexion->query( $query22 );
if ( $buscarAlumnos22->num_rows > 0 ) {
    while( $filaAlumnos22 = $buscarAlumnos22->fetch_assoc() )
    {
       
        $deshacerCompra = $filaAlumnos22['deshacerCompra'];
    }
}
    $deshacerCompra += 1;
            
        if($deshacerCompra >= 3){
       $deshacerCompra = 3; 
    }


    
$stmt_o = $conexion->prepare("UPDATE empresa SET deshacerCompra='$deshacerCompra' WHERE id='1'");
$stmt_o->execute();
$stmt_o -> close();


    define( 'PAGINA_RETORNO', '../publico/production/nuevaCompraFacturas.php?accion=mensajeExito' );
    header( 'Location: '.PAGINA_RETORNO ); 
        }
} 
}

define( 'PAGINA_RETORNO', '../publico/production/nuevaCompraFacturas.php' );
        header( 'Location: '.PAGINA_RETORNO );
