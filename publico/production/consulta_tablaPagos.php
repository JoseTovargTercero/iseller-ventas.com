<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");



	$q=$conexion->real_escape_string($_POST['semana']);
    $query="SELECT * FROM gastos WHERE semana='$q' ORDER BY tipo ASC";
    $buscarAlumnos=$conexion->query($query);
    if ($buscarAlumnos->num_rows > 0){
        $count = 1;
        $tabla = '<table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr class="headings" >
            <th style="padding: 10px !important; width: 5%" class="column-title">#</th>
            <th style="padding: 10px !important; width: 5%; border-right: none;" class="column-title"></th>
                <th style="padding: 10px !important; border-left: none;" class="column-title">Nombre</th>
                <th style="padding: 10px !important" class="column-title">Cantidad a pagar</th>
                <th style="padding: 10px !important; width: 5%" class="column-title">Quitar</th>
            </tr>
        </thead>
        <tbody>';
    	while($filaAlumnos= $buscarAlumnos->fetch_assoc()){     
        if ($filaAlumnos['tipo'] == '1') {
            $pin = '<i title="Gasto recurrente" class="line icon-pin"></i>';

        }elseif ($filaAlumnos['tipo'] == '3') {
            $pin = '<i title="Empleados" class="line icon-people"></i>';

        }else {
            $pin = '<i title="Gasto Unico" class="line icon-clock"></i>';
        }


        $tabla .=  '
                    <tr>
                    <td>'.$count++.'</td>
                    <td style="text-align: center; border-right: none;">'.$pin.'</td>
                        <td style="border-left: none;">'.$filaAlumnos['nombre'] . '</td>
                        <td>$'.number_format($filaAlumnos['importe'], '2', ',', '.') . '</td>
                        <td style="text-align: center;"><a style="cursor: pointer" onclick="confirm('.$filaAlumnos["id"].')"><i style=" font-size: 18px" class="gray line icon-trash"></i></a></td>
                    </tr>';



    	}

        $tabla .= '</tbody>
                </table>';
    }else{
		$tabla='<br>No se han aplicado gastos a esta semana<br><br>';
	}


echo $tabla;
?>


                                                          
                                                          
                                                      
