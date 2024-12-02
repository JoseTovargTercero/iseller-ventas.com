<?php
require_once( 'configuracion.php' );

    $codigo = $_POST['id'];

    $stmt = $conexion->prepare( "UPDATE productos SET activo='1' WHERE id='$codigo'" );
    $stmt->execute();
$stmt->close();

    echo 'id - '.$codigo;


?>