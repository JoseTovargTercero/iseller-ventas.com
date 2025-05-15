<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

if(isset($_POST['metodos']) && $_POST['metodos'] == 2)
{ 
  
    
        $tabla_codigo= '
       
        <div class="metodos">
        <div class="wrapper">
        <input type="radio" name="pagoTipo" value="1" id="option1">
        <input type="radio" name="pagoTipo" value="2" id="option2">
        <input type="radio" name="pagoTipo" value="3" id="option3">
        <input type="radio" name="pagoTipo" value="4" id="option4">
        <input type="radio" name="pagoTipo" value="5" id="option5">
        <input type="radio" name="pagoTipo" value="6" id="option6">
        <input type="radio" name="pagoTipo" value="7" id="option7">
       
        <label for="option1" class="option option1">
        <div class="dot"></div>
        <span>Punto</span>
        </label>

        <label for="option2" class="option option2">
        <div class="dot"></div>
        <span>P.Movil</span>
        </label>

        <label for="option3" class="option option3">
        <div class="dot"></div>
        <span>Transfe.</span>
        </label>

        <label for="option7" class="option option7">
        <div class="dot"></div>
        <span>BioPago</span>
        </label>
        
        <label for="option4" class="option option4">
        <div class="dot"></div>
        <span>Efectivo</span>
        </label>

        <label for="option5" class="option option5">
        <div class="dot"></div>
        <span>Dolares</span>
        </label>

        <label for="option6" class="option option6">
        <div class="dot"></div>
        <span>Pesos</span>
        </label>


        </div>

        </div>
        ';
        
}elseif (isset($_POST['metodos']) && $_POST['metodos'] == 3){
    $tabla_codigo= '
    
                                             <table class="table">
                                             <input type="text" name="pagoTipo" value="8" class="form-control" hidden>

    <tbody>
        <tr>
            <td>
           <div class="btn-group2" data-toggle="buttons" >
                        <input type="text" onkeyup="pagoFracionado()" name="punto" class="form-control" value="0"><small><br></small> <span> Punto.V</span>
                </div>
                </td>
            <td><div class="btn-group2" data-toggle="buttons" >
                                  <input onkeyup="pagoFracionado()" type="text" name="pagoMovil" class="form-control" value="0"><small><br></small>   <span>P.Movil </span>
                              </div></td>
            <td><div class="btn-group2" data-toggle="buttons" >
                                  <input onkeyup="pagoFracionado()" type="text" name="pesos" class="form-control" value="0"><small><br></small>   <span>Pesos </span>
                              </div></td>
            <td><div class="btn-group2" data-toggle="buttons" >
                                  <input onkeyup="pagoFracionado()" type="text" name="transferencia" class="form-control" value="0"><small><br></small>   <span>Transfe. </span>
                              </div></td>
            <td><div class="btn-group2" data-toggle="buttons" >
                                  <input onkeyup="pagoFracionado()" type="text" name="bioPago" class="form-control" value="0"><small><br></small>   <span>Biopago </span>
                              </div></td>
            <td><div class="btn-group2" data-toggle="buttons" >
                                  <input onkeyup="pagoFracionado()" type="text" name="efectivo" class="form-control" value="0"><small><br></small>   <span>BS.Efec </span>
                              </div></td>
            <td><div class="btn-group2" data-toggle="buttons">
                                  <input onkeyup="pagoFracionado()" type="text" name="dolares" class="form-control" value="0"><small><br></small>  <span>Dolares </span>
                              </div></td>
        </tr>
    </tbody>
    </table>
    ';
	}else{
    $tabla_codigo= '
    <div class="metodos">
    <div class="wrapper">
    <input type="radio" name="pagoTipo" value="1" id="option1" required>
    <input type="radio" name="pagoTipo" value="2" id="option2">
    <input type="radio" name="pagoTipo" value="3" id="option3">
    <input type="radio" name="pagoTipo" value="4" id="option4">
    <input type="radio" name="pagoTipo" value="5" id="option5">
    <input type="radio" name="pagoTipo" value="6" id="option6">
    <input type="radio" name="pagoTipo" value="7" id="option7">
   
    <label for="option1" class="option option1">
    <div class="dot"></div>
    <span>Punto</span>
    </label>

    <label for="option2" class="option option2">
    <div class="dot"></div>
    <span>P.Movil</span>
    </label>

    <label for="option3" class="option option3">
    <div class="dot"></div>
    <span>Transfe.</span>
    </label>

    <label for="option7" class="option option7">
    <div class="dot"></div>
    <span>BioPago</span>
    </label>
    
    <label for="option4" class="option option4">
    <div class="dot"></div>
    <span>Efectivo</span>
    </label>

    <label for="option5" class="option option5">
    <div class="dot"></div>
    <span>Dolares</span>
    </label>

    <label for="option6" class="option option6">
    <div class="dot"></div>
    <span>Pesos</span>
    </label>


    </div>

    </div>
    ';
  }
echo $tabla_codigo;
?>


