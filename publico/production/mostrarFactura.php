<?php
/////// CONEXIÓN A LA BASE DE DATOS /////////
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');


$numerodelaFactura = $_POST['nFactura'];

$tabla = '<div class="content-carrito">
                        <div class="carrito" style="    max-width: 350px;">
                            <div class="topLine"></div>
                                <div style="width: 100%; text-align: center">';

$query22 = "SELECT * FROM compras WHERE factura='$numerodelaFactura' ORDER BY producto ASC LIMIT 1";
$buscarAlumnos22 = $conexion->query($query22);
if ($buscarAlumnos22->num_rows > 0) {
    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {

        $tabla .= '<p>
                                                        Proveedor: ' . $filaAlumnos22['proveedor'] . '<br>
                                                        N# de la Factura ' . $filaAlumnos22['factura'] . '<br>
                                                        Fecha de Factura ' . $filaAlumnos22['fechaFactura'] . '<br></p>';
    }
}

$tabla .=  "<div class='ln_solid2'></div></div>";

foreach ($_SESSION['facturaCompletaMostrada'] as $nombre2) {
    $tabla = $nombre2;
}




$tabla .= '
                                            <div class="divisor">
                                                <br>
                                                <div class="divLeft"></div>';

                                                    $query22 = "SELECT * FROM compras WHERE factura='$numerodelaFactura' ORDER BY producto ASC";
                                                    $buscarAlumnos22 = $conexion->query($query22);
                                                    if ($buscarAlumnos22->num_rows > 0) {
                                                        while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {




                                                            $price = ($filaAlumnos22['precio'] / $filaAlumnos22['CporPrecio']) * $filaAlumnos22['cantidad'];


                                                            $tabla .=  '
                                                        
                                                                <div class="myRow">
                                                            <div class="myRowLeft">
                                                                <strong>' . $filaAlumnos22['producto'] . '</strong>
                                                                <p>Cantidad <strong>' . $filaAlumnos22['cantidad'] . '</strong> </p>
                                                            </div>
                                                        
                                                            <div class="myRowRight">
                                                        
                                                                <p>$' . number_format($price, '2', ',', '.') . '
                                                                </p>
                                                        
                                                            </div>
                                                        </div>';
                                                        $dolaresSubTotal += $price;
                                                        $pagadaono = $filaAlumnos22['status'];
                                                    }
                                                }


$tabla .=   '
                                                        
                                                        
                                                <div class="myRow">
                                                <div class="myRowLeft">
                                                
                                                </div>

                                                <div class="myRowRight">

                                                    <p>$' . number_format($dolaresSubTotal, '2', ',', '.') . '
                                                    </p>

                                                </div>
                                                </div>';

$tabla .= '

                                                <div class="divRight"></div>
                                            </div>
                                            <div class="myRow" style="padding: 5px;">
                                                <img src="images/barcode.png" style="opacity: 0.3; margin: auto" width="90%" height="60px" alt="barcode">
                                            </div>
                                            <p style="font-size: 11px !important; width: 100%; text-align: center; margin-top: -5px">';

if ($pagadaono == "1") {
    $tabla .=  "*Factura pagada.";
    $img = '<img src="images/pagado.png" class="statusFactura">';
} else {
    $img = '<img src="images/pendiente.png" class="statusFactura">';
    $tabla .=  "*Factura pendiente.";
}

$tabla .= $img .'
                                                </p>
                                        </div>
                                    </div>
                                    
                                    ';
echo $tabla;
