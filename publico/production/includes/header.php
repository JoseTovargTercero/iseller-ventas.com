<style>
    .bot {
        font-family: cursive;
        color: white !important;
        border: 1px solid white !important;
        margin: -2px 0px 0px 0px !important;
        padding: 3px 10px 0 10px !important;
        height: 25px !important;
        font-size: 12px !important;
    }

    .peque {
        font-size: 9px !important;
    }

    p {
        font-size: 13px !important;
    }

    th {
        font-size: 13px !important;
    }

    td {
        font-size: 13px !important;
    }

    tr {
        font-size: 13px !important;
    }

    .myFuckinClass {
        position: absolute;
        margin-top: 2px;
        font-size: 15px !important;
        max-width: 20%;
        height: 20px;
        overflow: auto;
    }

    .toggle a {
        padding: 10px 15px 0 !important;
        margin: 0;
        cursor: pointer;
    }

    .myFuckinClass::-webkit-scrollbar {
        width: 7px;
        height: 7px;
        background: rgba(88, 115, 254, 0.04)
    }

    .myFuckinClass::-webkit-scrollbar-thumb {
        background: #1ABB9C;
        height: 10px;
        border-radius: 5px;
    }

    .nav a,
    .nav i:hover {
        color: #79d6c9f5;
    }
</style>

<?php

function topnav()
{
    global $conexion;
    $query2 = 'SELECT * FROM empresa';
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $stockCritico = $filaAlumnos2['stockCritico'];
            $notificacionStockCritico = $filaAlumnos2['notificacionStockCritico'];
        }
    }



    if ($notificacionStockCritico == "1") {


        $stmt = mysqli_prepare($conexion, "SELECT * FROM productos WHERE stock<='$stockCritico' AND activo='0'");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {

            $tabla66 = "";
            $cantidad = 0;
            while ($row6 = $result->fetch_assoc()) {

                $cantidad += 1;
                $tabla66 .= "
                        
                        <li class='nav-item'>
                                        <a class='dropdown-item'>
                                         
                                                <span><strong>" . $row6['nombre'] . "</strong></span>
                                                
                                                <span class='time'><small>Stock Critico</small></span>
                                            </span>
                                            <span class='message'>
                                                Restan <strong>" . $row6['stock'] . " " . $row6['nombre'] . "</strong> en el stock.
                                            </span>
                                        </a>
                                    </li>
                        ";
            }
        }
        if ($cantidad >= 1) {
            $ico = "line icon-envelope-letter";
            $solo = "class='bot' href='javascript:;' class='dropdown-toggle info-number' style='color: black;' id='navbarDropdown1' data-toggle='dropdown' aria-expanded='false'";
        }
    } else {
        $ico = "line icon-ghost";
        $solo = "class='bot'";
    }



    if ($_SESSION['nivel'] == '1') {
        $tipoUser = 'Administrador';
    } else {
        $tipoUser = 'Empleado';
    }




    $menu =  "
    <script src='../assets/qrious.js'></script>
	<script src='../assets/qrcode.min.js'></script>


    <div class='top_nav'>
                <div class='nav_menu'>
                    <div class='nav toggle'>
                        <a id='menu_toggle'><i class='icon-options-vertical'></i></a>
                    </div>   
                    <nav class='nav navbar-nav'>
                        <ul class=' navbar-right'>
                              <li class='nav-item dropdown open' style='padding-left: 15px;'>
                                <a href='javascript:;' class='user-profile dropdown-toggle' aria-haspopup='true' id='navbarDropdown' data-toggle='dropdown' aria-expanded='false'>
                                   <img src='images/img.png' style='margin-top: -5px' hegiht='50px'>
                                </a>
                                <div class='dropdown-menu dropdown-usermenu pull-right' aria-labelledby='navbarDropdown'>
                                  <a class='dropdown-item' style='color: #797979 !important' >" . $_SESSION['nombre'] . "</a>
                                  <a class='dropdown-item' style='color: #797979 !important' >" . $tipoUser . "</a>
                                  <hr class='hr-dr'>";
    if ($_SESSION['nivel'] == '1') {
        $menu .= "<a class='dropdown-item' style='color: #797979 !important' href='configuracion.php'><i class='line icon-settings pull-left'></i> &nbsp; Configuración</a>
                                                                    <a class='dropdown-item' style='color: #797979 !important' href='actualizar.php'><i class='line icon-heart pull-left'></i> &nbsp; Actualizaciones</a>";
    }
    $menu .= "<hr class='hr-dr'>
                                      <a class='dropdown-item' style='color: #797979 !important' href='../../login/salir.php'> Cerrar Sesión</a>
                                    </div>
                                </li>
                                    <li role='presentation' class='nav-item dropdown open'>";


    if ($_SESSION['nivel'] == '1') {

        $internetError = false;

        $query = "SELECT * FROM cambio WHERE id='1'";
        $buscarAlumnos = $conexion->query($query);
        if ($buscarAlumnos->num_rows > 0) {
            while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
                $bcv = $filaAlumnos['bcv'];
                $last_u_bcv = $filaAlumnos['last_u_bcv'];
                $DolarBolivar = $filaAlumnos['DolarBolivar'];
                $tipo_tasa_bs = $filaAlumnos['tipo_tasa_bs'];
                $margen_neto = $filaAlumnos['margen_neto'];
                $redondeo = $filaAlumnos['redondeo'];
            }
        }


        if ($tipo_tasa_bs == 2 || $tipo_tasa_bs == 3) {
            $time1 = $last_u_bcv;
            $time2 = time();


            if ($time2 - $time1 > 21600) {
                // Función para verificar conexión a Internet
                function checkConnection($url)
                {
                    $connected = @fsockopen(parse_url($url, PHP_URL_HOST), 80); // Probar conexión en el puerto 80
                    if ($connected) {
                        fclose($connected);
                        return true;
                    }
                    return false;
                }


                $apiUrl = "https://api.exchangedyn.com/";

                // Verificar si hay conexión antes de abrir la URL
                if (checkConnection($apiUrl)) {
                    $ar = fopen($apiUrl, "r");
                    $linea = '';
                    while (!feof($ar)) {
                        $linea .= fgets($ar);
                    }
                    fclose($ar);

                    $linea = substr($linea, 0, -2);
                    $linea = substr($linea, 2, strlen($linea));
                    $linea = str_replace('"API endpoints and data structure subject to changes at anytime. ALL INFORMATION IS PROVIDED \"', '', $linea);
                    $linea = str_replace('"warning": AS IS', '', $linea);
                    $linea = str_replace('". EACH PARTY MAKES NO WARRANTIES, EXPRESS, IMPLIED OR OTHERWISE, REGARDING ITS ACCURACY, COMPLETENESS OR PERFORMANCE."', '', $linea);
                    $linea = str_replace('\\', '', $linea);
                    $linea = str_replace('},', '}', $linea);
                    $iter = explode(',', $linea);

                    $last_u_bcv = substr($iter[2], 54, -1);
                    $last_u_bcv = date('d-m-Y H:i a', strtotime($last_u_bcv));
                    $bcv = number_format(substr($iter[4], 17, -15), 2, '.', '');

                    $bolivar = $bcv;

                    if ($tipo_tasa_bs == 3) {
                        if ($margen_neto != 0 && $margen_neto != '') {
                            $bolivar += $margen_neto;
                        }
                        if ($redondeo == 1) {
                            $bolivar = round($bolivar, 0, PHP_ROUND_HALF_UP);
                        } elseif ($redondeo == 2) {
                            $bolivar = round($bolivar, 0, PHP_ROUND_HALF_DOWN);
                        }
                    }

                    $update = "UPDATE cambio SET DolarBolivar='$bolivar', bcv='$bcv', last_u_bcv='$time2' WHERE id='1'";
                    $result = mysqli_query($conexion, $update);
                } else {
                    $internetError = true;
                    $last_u_bcv = date('d-m-Y H:i a', $last_u_bcv);
                }
            } else {
                $last_u_bcv = date('d-m-Y H:i a', $last_u_bcv);
            }


            $menu .= "<span class='bot' >
        " . ($internetError ? '<span class="text-danger">Sin conexion, la tasa no fue comprobada.</span>' : '') . "
                                <span class='text-success'>" . $bcv . " Bs</span> - <span class='text-muted'>UA: <span class='text-info'>" . $last_u_bcv . "</span></span>
                            </span>";
        }









        $menu .= "
                                    <a type='button' id='tasas' href='cambiar_tasas.php'  class='bot' style='color: #909090 !important'>
                                        <i class='line icon-anchor'></i>
                                    </a>";
        $menu .= "<a type='button' href='../../configurar/darkMode.php' class='bot' style='color: #909090 !important'>  
                                                            <i class='line icon-sun'></i>
                                                        </a>
                                                        <a type='button'  " . $solo . "><i style='color: #979897;' class='" . $ico . " '></i></a>
                                                        <ul class='dropdown-menu list-unstyled msg_list' role='menu' aria-labelledby='navbarDropdown1'>
                                                        " . $tabla66 . " 
                                                        </ul>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>";
    }

    return $menu;
}
function obt_bcv($var)
{
    # code...
}
?>