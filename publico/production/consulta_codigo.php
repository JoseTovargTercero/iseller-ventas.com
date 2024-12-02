<?php
require_once("../../configurar/configuracion.php");


///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////

if(isset($_POST['rep_codigo']))
{ 
    
    
  $query="SELECT * FROM productos WHERE activo= 0 ORDER BY id DESC LIMIT 1";
$buscarAlumnos=$conexion->query($query);
if ($buscarAlumnos->num_rows > 0)
{
	while($filaAlumnos= $buscarAlumnos->fetch_assoc())
	{      
        $ultimoId= $filaAlumnos['id'];
        $ParteCodigo = $ultimoId + 1;
    
	}
}  
    
        $primeraletracorte = str_split($_POST['rep_codigo'],1);   
        $primeraletra = strtoupper($primeraletracorte[0]);
       
        $tabla_codigo= '<input class="date-picker form-control"  readonly="readonly" name="codigo" type="text"   value="'.$primeraletra.'0'.$ParteCodigo.'" hidden>';
     
        
}else{
        $tabla_codigo= '<input class="date-picker form-control"  readonly="readonly" name="codigo" type="text"  placeholder="Generado Automaticamente" value="" hidden>';
     
        
      
    
	}

echo $tabla_codigo;
?>


