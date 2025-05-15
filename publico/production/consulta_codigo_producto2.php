<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');



if (isset($_POST['rep2'])) {
    $q = $conexion->real_escape_string($_POST['rep2']);
    $query = "SELECT * FROM productos WHERE nombre LIKE '%$q%' AND activo= 1 AND distribuidor='si'";
}

$buscarAlumnos = $conexion->query($query);
if ($buscarAlumnos->num_rows > 0) {
    $tabla = '<select id="producto" required="required"  class="form-control col-md-8" onblur="obtener_registros(rep)"  name="producto">
    <option value="">-- Seleccione --</option>  
    ';

    while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {


        $tabla .= '
        <option value="' . $filaAlumnos['codigo'] . '">' . $filaAlumnos['nombre'] . '</option>            
        ';
    }
    $tabla .= ' </select>';
} else {

    $tabla = '
            <select id="producto" required="required"  class="form-control col-md-8" name="producto">
                                                <option value="">-- Sin valor de entrada --</option>
                                            </select>
                    
        ';
}


echo $tabla;
