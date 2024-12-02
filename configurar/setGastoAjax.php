<?php
require_once( 'configuracion.php' );

    $codigo = $_POST['id'];


    
    $query="SELECT * FROM gastosfijos WHERE id='$codigo'";
    $buscarAlumnos=$conexion->query($query);
    if ($buscarAlumnos->num_rows > 0){
    	while($filaAlumnos= $buscarAlumnos->fetch_assoc()){     
            if ($filaAlumnos['activo'] == '0') {
                $stmt = $conexion->prepare( "UPDATE gastosfijos SET activo='1' WHERE id='$codigo'" );
                $stmt->execute();
                $stmt->close();
            }else {
                $stmt = $conexion->prepare( "UPDATE gastosfijos SET activo='0' WHERE id='$codigo'" );
                $stmt->execute();
                $stmt->close();
            }
        }
    }




    echo 'id - '.$codigo;


?>