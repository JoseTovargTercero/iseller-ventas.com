<?php
require_once( 'configuracion.php' );

    $codigo = $_POST['id'];

    $stmt = $conexion->prepare( "DELETE FROM `gastos` WHERE id='$codigo'" );
    $stmt->execute();
$stmt->close();

    echo 'id - '.$codigo;


?>