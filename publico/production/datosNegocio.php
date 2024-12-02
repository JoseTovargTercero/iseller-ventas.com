<?php
require_once("../../configurar/configuracion.php");


if(isset($_POST['negocio']) )
{ 
    $q=$conexion->real_escape_string($_POST['negocio']);
    $query="SELECT * FROM clientes WHERE email='$q' AND status= 1";
    $buscarAlumnos=$conexion->query($query);
        if ($buscarAlumnos->num_rows > 0){
	       while($filaAlumnos= $buscarAlumnos->fetch_assoc()){ 
    
        $tabla_codigo= " 
    <div class='form-group  col-lg-12'>
        <input class='form-control  col-lg-12' type='text' name='nombreNegocio' value='".$filaAlumnos['negocio']."' placeholder='Nombre del Negocio'>
    </div>
    <div class='form-group  col-lg-12'>
        <input class='form-control  col-lg-12' type='text' name='direccionNegocio' value='".$filaAlumnos['direccion']."' placeholder='Direccion'>
    </div>
        ";
    }
}else{
    $tabla_codigo= "  
        <div class='form-group  col-lg-12'>
            <input class='form-control  col-lg-12' type='text' name='nombreNegocio' placeholder='Nombre del Negocio'>
        </div>
        <div class='form-group  col-lg-12'>
            <input class='form-control  col-lg-12' type='text' name='direccionNegocio' placeholder='Direccion'>
        </div>
        "; 
}    
}else{
        $tabla_codigo= "  
        <div class='form-group  col-lg-12'>
            <input class='form-control  col-lg-12' type='text' name='nombreNegocio' placeholder='Nombre del Negocio'>
        </div>
        <div class='form-group  col-lg-12'>
            <input class='form-control  col-lg-12' type='text' name='direccionNegocio' placeholder='Direccion'>
        </div>
        ";
	}
echo $tabla_codigo;
?>


