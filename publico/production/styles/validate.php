                                                                 <?php
if ( isset( $_POST['clave'] ) ) {
    
        $claveSumistrada = $_POST['clave'];
        $div = $_POST['div'];
    
    
    if ( $claveSumistrada == $div ) {
        
     require_once( '../../../configurar/configuracion.php' );
     $stmt = $conexion->prepare( "UPDATE sistem SET cla_pro='4684DDW64DW'" );
     $stmt->execute();
$stmt->close();

     echo '<script>
alert("Proceso finalizado correctamente");
window.history.go(-2);
</script>';
    
    }else{
         echo '<script>
alert("La clave no coincide");
window.history.go(-1);
</script>';
}
}

?>

