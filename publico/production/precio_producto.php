<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once('../../configurar/configuracion.php');

$query2255 = "SELECT * FROM cambio WHERE id='1'";
$buscarAlumnos225 = $conexion->query($query2255);
if ($buscarAlumnos225->num_rows > 0) {
    while ($filaAlumnos225 = $buscarAlumnos225->fetch_assoc()) {
        $pesoDolar = $filaAlumnos225['pesoDolar'];
        $bsDolar = $filaAlumnos225['DolarBolivar'];
    }
}


if (isset($_POST['rep_precio'])) {
    $q = $conexion->real_escape_string($_POST['rep_precio']);
    $query = "SELECT * FROM productos WHERE codigo='$q'";
}

$buscarAlumnos = $conexion->query($query);
if ($buscarAlumnos->num_rows > 0) {

    while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
        $tabla .= '<input type="text" name="nombrepor" value="' . $filaAlumnos['nombre'] . '" hidden>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Unidades compradas<span class="required">*</span>
                    </label>
                    <div class="col-md-9 col-sm-9 ">
                        <input type="text" id="comprado" name="comprado"  required="required" class="form-control "  >
                    </div>
                </div>

                <div class="ln_solid"></div>

                <div class="item form-group">
                <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Precio de Compra <span class="required">*</span>
                </label>
                <div class="col-md-5 col-sm-5 ">
                    <input type="text" value="' . $filaAlumnos['precio_compra'] . '" id="precio" name="precio" required="required" class="form-control " >
                </div>
                <div class="col-md-4 col-sm-4">
                    <select class="form-control" required="required" name="moneda" id="moneda">
                <option value="dolares">Dolares</option>
                    </select>
                </div>
                </div>
                                
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 "  for="first-name">Cantidad <span class="required">*</span>
                    </label>
                    <div class="col-md-9 col-sm-9 ">
                        <input type="number" id="cantidad" name="cantidad" value="' . $filaAlumnos['cantidad_unidades'] . '" required="required" class="form-control "  onKeyUp="division()">
                    </div>
                </div>
                <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Porcentaje <span class="required">*</span>
                    </label>
                    <div class="col-md-9 col-sm-9 ">
                        <input type="number" id="porcentaje" name="porcentaje" value="' . $filaAlumnos['porcentaje'] . '" required="required" class="form-control" "onKeyUp="division()">
                    </div>
                </div>   
                
                
                     <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Proveedor <span class="required">*</span>
                    </label>
                    <div class="col-md-9 col-sm-9 ">
                        <input type="text" id="proveedor" name="proveedor" value="' . $filaAlumnos['proveedor'] . '" required="required" class="form-control">
                    </div>
                </div>     
                
                
                           
        
        ';
    }
} else {
    $tabla .= '
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Unidades compradas<span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="text" id="comprado" name="comprado"  required="required" class="form-control " >
                                            </div>
                                        </div>
                                        <div class="ln_solid"></div>
                                         <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Precio de Compra <span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="text" id="precio" name="precio" required="required" class="form-control "  >
                                            </div>
                                        </div>
                                        <input type="text" id="precio" name="precio" hidden required="required" class="form-control " onKeyUp="division()">
                                    
                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 "  for="first-name">Cantidad <span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="text" id="cantidad" name="cantidad"  required="required" class="form-control " >
                                            </div>
                                        </div>

                                        <div class="item form-group">
                                            <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Porcentaje <span class="required">*</span>
                                            </label>
                                            <div class="col-md-9 col-sm-9 ">
                                                <input type="number" id="porcentaje" name="porcentaje"  required="required" class="form-control "  >
                                            </div>
                                        </div>   
                                           
                                               <div class="item form-group">
                    <label class="col-form-label col-md-3 col-sm-3 " for="first-name">Proveedor <span class="required">*</span>
                    </label>
                    <div class="col-md-9 col-sm-9 ">
                        <input type="text" id="proveedor" name="proveedor" value="" required="required" class="form-control">
                    </div>
                </div>     
                
                                            
        ';
}

echo $tabla;
