<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

if (isset($_POST['negocio'])) {
    $q = $conexion->real_escape_string($_POST['negocio']);
    $query = "SELECT * FROM clientes WHERE email='$q' AND status= 1";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

            $tabla_codigo = " 
    <div class='form-group '>
                                                        <label class='control-label col-md-3 col-sm-3 '>Cliente</label>

        <input class='form-control ' type='text' name='nombreNegocio' id='nombreNegocio' value='" . $filaAlumnos['negocio'] . "' placeholder='Nombre del Cliente'>
    </div>
    <div class='form-group ' style='display: none'>
        <input class='form-control ' type='text' name='direccionNegocio' value='" . $filaAlumnos['direccion'] . "' placeholder='Direccion'>
    </div>
        ";
        }
    } else {
        $tabla_codigo = "  
        <div class='form-group '>
                                                        <label class='control-label col-md-3 col-sm-3 '>Cliente</label>

            <input class='form-control ' type='text' name='nombreNegocio' id='nombreNegocio' placeholder='Nombre del Cliente'>
        </div>
        <div class='form-group ' style='display: none'>
            <input class='form-control ' type='text' name='direccionNegocio' placeholder='Direccion'>
        </div>
        ";
    }
} else {
    $tabla_codigo = "  
        <div class='form-group '>
                                                        <label class='control-label col-md-3 col-sm-3 '>Cliente</label>

            <input class='form-control ' type='text' name='nombreNegocio' id='nombreNegocio' placeholder='Nombre del Cliente'>
        </div>
        <div class='form-group ' style='display: none'>
            <input class='form-control ' type='text' name='direccionNegocio' placeholder='Direccion'>
        </div>
        ";
}
echo $tabla_codigo;
