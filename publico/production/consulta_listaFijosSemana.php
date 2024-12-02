<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");


    $query="SELECT * FROM gastosfijos ORDER BY nombre ASC";
    $buscarAlumnos=$conexion->query($query);
    if ($buscarAlumnos->num_rows > 0){
        $count = 1;
    	while($filaAlumnos= $buscarAlumnos->fetch_assoc()){     
       if ($filaAlumnos['activo'] == '0') {
        $activo = 'checked';
       }else {
        $activo = '';
       }
        $id = $filaAlumnos['id'];

        $tabla .=  ' <li style="place-content: space-between;">
                        <p>'.$filaAlumnos['nombre'] . '
                        <br>
                        <small>Importe: '.$filaAlumnos['importe'] . '$</small>
                        </p>
                        <span>
                            <label class="switch">
                                <input onclick="setGasto(\''.$id.'\')" type="checkbox" '.$activo.'>
                                <span class="slider round"></span>
                            </label>
                        </span>
                    </li>';
    	}
    }else{
		$tabla='No hay nada para mostrar';
	}

echo $tabla;
?>


                                                          
                                                          
                                                      
