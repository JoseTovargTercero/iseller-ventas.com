<?php
require_once("../../configurar/configuracion.php");
require_once('includes/requires.php');

if(isset($_POST['venta']) && $_POST['venta'] == 2)
{ 
  
    
        $tabla_codigo= " 
        <div class='form-group  col-lg-12'>
            <input class='form-control  col-lg-12' type='text' id='cedula' required name='cedula' placeholder='Cedula del cliente'>
        </div>

        <section id='tabla_resultado_datos'>
            <div class='form-group  col-lg-12'>
                <input class='form-control  col-lg-12' type='text' required name='nombre' placeholder='Nombre del cliente'>
            </div>
            <div class='form-group  col-lg-12'>
                <input class='form-control  col-lg-12' type='text' required name='telefono' placeholder='Telefono del cliente'>
            </div>
        </section>
     </div>

   
 
        ";
        
}else{
        $tabla_codigo= "  
        <div class='form-group  col-lg-12'>
            <input class='form-control  col-lg-12' type='text' id='cedula' name='cedula' placeholder='Cedula del cliente'>
        </div>

        <section id='tabla_resultado_datos'>
            <div class='form-group  col-lg-12'>
                <input class='form-control  col-lg-12' type='text' name='nombre' placeholder='Nombre del cliente'>
            </div>
            <div class='form-group  col-lg-12'>
                <input class='form-control  col-lg-12' type='text' name='telefono' placeholder='Telefono del cliente'>
            </div>
        </section>
        
        
        ";
	}
echo $tabla_codigo;
?>


