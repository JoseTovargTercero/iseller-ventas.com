<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once( '../../configurar/configuracion.php' );

///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////

if ( isset( $_POST['rep_foto'] ) ) {
    $q = $conexion->real_escape_string( $_POST['rep_foto'] );
    
    $query = "SELECT * FROM productos WHERE codigo='$q'";
    $buscarAlumnos = $conexion->query( $query );
    if ( $buscarAlumnos->num_rows > 0 ) {
        while( $filaAlumnos = $buscarAlumnos->fetch_assoc() )
        {
            $foto = $filaAlumnos['foto'];
        }
    }
if($foto == "NO"){
      echo "<div class='bordeFoto'>
    <img src='images/producto_base.png'  class='fotoProducto'>
</div>";
}else{
    echo "<div class='bordeFoto'>
    <img src='images/stock/".$q.".jpg'  class='fotoProducto'>
</div>";
}
    

} else {

    echo "<div class='bordeFoto'>
    <img src='images/producto_base.png'  class='fotoProducto'>
</div>";
}

?>

