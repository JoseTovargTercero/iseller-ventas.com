<?php

function topnav()
{
    global $conexion;

    $tipoUser = ($_SESSION['nivel'] == '1') ? 'Administrador' : 'Empleado';

    ob_start(); // Empezamos a capturar la salida

?>
    <script src='../assets/qrious.js'></script>
    <script src='../assets/qrcode.min.js'></script>

    <div class='top_nav'>
        <div class='nav_menu'>
            <div class='nav toggle '>
                <a id='menu_toggle'><i class='icon-options-vertical'></i></a>
            </div>
            <nav class='nav navbar-nav'>
                <ul class='navbar-right' style='text-align: right;'>

                    <li class='nav-item dropdown open' style='padding-left: 15px;'>
                        <a href='javascript:;' class='user-profile dropdown-toggle' aria-haspopup='true' id='navbarDropdown' data-toggle='dropdown' aria-expanded='false'>
                            <img src='images/img.png' style='margin-top: -5px' height='50px'>
                        </a>
                        <div class='dropdown-menu dropdown-usermenu pull-right' aria-labelledby='navbarDropdown'>
                            <a class='dropdown-item'><?= htmlspecialchars($_SESSION['nombre']) ?></a>
                            <a class='dropdown-item'><?= $tipoUser ?></a>
                            <hr class='hr-dr'>

                            <?php if ($_SESSION['nivel'] == '1'): ?>
                                <a class='dropdown-item' href='configuracion.php'>
                                    <i class='line icon-settings pull-left'></i> &nbsp; Configuración
                                </a>
                                <a class='dropdown-item' href='actualizar.php'>
                                    <i class='line icon-heart pull-left'></i> &nbsp; Actualizaciones
                                </a>
                                <a class='dropdown-item' href='cambiar_tasas.php'>
                                    <i class='line icon-anchor'></i> &nbsp; Tasas de cambio
                                </a>
                            <?php endif; ?>

                            <a class='dropdown-item' href='../../configurar/darkMode.php'>
                                <i class='bx bx-sun'></i> &nbsp; Modo de luz
                            </a>

                            <hr class='hr-dr'>
                            <a class='dropdown-item' href='../../login/salir.php'>Cerrar Sesión</a>
                        </div>
                    </li>

                </ul>
            </nav>
        </div>
    </div>
<?php

    return ob_get_clean(); // Devolvemos el contenido HTML generado
}
?>