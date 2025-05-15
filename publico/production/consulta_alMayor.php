<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');



///////// LO QUE OCURRE AL TECLEAR SOBRE EL INPUT DE CI ////////////

if (isset($_POST['rep_codigo']) && $_POST['rep_codigo'] == 4) {


    $tabla_codigo = '
        
        <div class="form-group col-lg-6">
             <label class="control-label">Descuento</label>
             <input  required type="text" name="valorDescuento" id="valorDescuento" value="0" class="form-control">
         </div>


         <div class="form-group col-lg-6" >
         <label class="control-label">Tipo de descuento</label>
         <select  class="form-control" name="tipoDescuento" id="tipoDescuento">
         <option value="1">Porcentaje</option>
         <option value="2">Dolares</option>
     </select>

     </div>



</div>';
} else {
    $tabla_codigo = '
        <div class="form-group col-lg-6">
             <label class="control-label">Descuento</label>
                 <select   class="form-control" name="valorDescuento" id="valorDescuento">
                     <option value="NINGUNO">Ninguno</option>
                 </select>
         </div>
         <div class="form-group col-lg-6" >
         <label class="control-label">Tipo de descuento</label>

         <select   class="form-control" name="tipoDescuento" id="tipoDescuento">
         <option value="1">Ninguno</option>
     </select>';
}
echo $tabla_codigo;
