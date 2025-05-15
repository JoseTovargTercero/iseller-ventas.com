<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');



///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////

if (isset($_POST['rep_codigo3'])) {
    $q = $conexion->real_escape_string($_POST['rep_codigo3']);
    $query = "SELECT * FROM clientes WHERE email='$q' AND status= 1";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {

            $tabla_codigo = "<div class='form-group  col-lg-12'>
                                                         <input class='form-control  col-lg-12' type='text' name='nombre' value='" . $filaAlumnos['name'] . "' placeholder='Nombre'>
                                                     </div>
                                                     <div class='form-group  col-lg-12'>
                                                         <input class='form-control  col-lg-12' type='text' name='telefono' value='" . $filaAlumnos['phone'] . "' placeholder='Telefono'>
                                                     </div>";
        }
    } else {

        if ($var != 1) {

            $tabla_codigo = "<div class='form-group  col-lg-12'>
        <input class='form-control  col-lg-12' type='text' name='nombre'  placeholder='Nombre'>
    </div>
    <div class='form-group  col-lg-12'>
        <input class='form-control  col-lg-12' type='text' name='telefono'  placeholder='Telefono'>
    </div>";
        }
    }
} else {
    $tabla_codigo = " <div class='form-group  col-lg-12'>
                                                         <input class='form-control  col-lg-12' type='text' name='nombre' placeholder='Nombre'>
                                                     </div>
                                                     <div class='form-group  col-lg-12'>
                                                         <input class='form-control  col-lg-12' type='text' name='telefono' placeholder='Telefono'>
                                                     </div>



                                                     
  ";
}
echo $tabla_codigo;
