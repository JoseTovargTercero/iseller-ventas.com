<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');



$query = "SELECT * FROM gastosfijos WHERE activo='0' ORDER BY nombre ASC";
$buscarAlumnos = $conexion->query($query);
if ($buscarAlumnos->num_rows > 0) {
    while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
        $id = $filaAlumnos['id'];

        $tabla .=  ' <li style="place-content: space-between;">
                        <p>' . $filaAlumnos['nombre'] . '
                        <br>
                        <small>Importe: ' . $filaAlumnos['importe'] . '$</small>
                        </p>
                    </li>';
    }
} else {
    $tabla = 'No hay nada para mostrar';
}

echo $tabla;
