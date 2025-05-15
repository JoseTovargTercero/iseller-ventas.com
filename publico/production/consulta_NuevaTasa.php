<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');


///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////

if (isset($_POST['rep_codigo']) && $_POST['rep_codigo'] == "dolares") {


    $tabla_codigo = "
        <div class='item form-group'>
        <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Valor de cambio <span class='required'>*</span>
        </label>
        <div class='col-md-9 col-sm-9 '>
            <input type='text' id='recepcion' name='recepcion' required='required' class='form-control ' placeholder='Valor de recepción y publicación'>
        </div>
    </div>
        
        ";
} else {
    $tabla_codigo = "
        <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Recepción <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='recepcion' name='recepcion' required='required' class='form-control ' placeholder='Valor de recepción'>
                                                </div>
                                            </div>


                                            <div class='item form-group'>
                                                <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Publicación <span class='required'>*</span>
                                                </label>
                                                <div class='col-md-9 col-sm-9 '>
                                                    <input type='text' id='publicacion' name='publicacion' required='required' class='form-control ' placeholder='Valor de publicación'>
                                                </div>
                                            </div>
        
        
        ";
}

echo $tabla_codigo;
