<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');




$query = "SELECT * FROM gastosEmpleados ORDER BY nombre ASC";
$buscarAlumnos = $conexion->query($query);
if ($buscarAlumnos->num_rows > 0) {
    while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
        $id = $filaAlumnos['id'];
        $nombre = $filaAlumnos['nombre'];
        $cantidad = $filaAlumnos['cantidad'];
        $pago = $filaAlumnos['importe'];

        if ($filaAlumnos['activo'] == '0') {
            $activo = 'checked';
        } else {
            $activo = '';
        }


        $tabla .=  ' <li style="display: grid;grid-auto-flow: column;grid-template-columns: min-content;">
            <div style="font-size: 22px;width: 30px;">
            ' . $cantidad . '
            </div>
            <div>
                <p>' . $filaAlumnos['nombre'] . ' - <small>' . $filaAlumnos['importe'] . '$ (C/U)</small><br>

                <a  style="cursor: pointer; color: #32d7c0" onclick="modificarEmpleado(\'' . $id . '\', \'' . $nombre . '\', \'' . $cantidad . '\', \'' . $pago . '\')">
                Modificar
                </a>



                
                </p>
            </div>

            <div style="padding: 7px;height: 35px;text-align: right;">

            <span>
            <label class="switch">
                <input onclick="setEmpleados(\'' . $id . '\')" type="checkbox" ' . $activo . '>
                <span class="slider round"></span>
            </label>
                </span>


           
            </div>

                    </li>';
    }
} else {
    $tabla = 'No hay nada para mostrar';
}

echo $tabla;
