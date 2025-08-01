<?php

function topnav()
{
    global $conexion;

    $tipoUser = ($_SESSION['nivel'] == '1') ? 'Administrador' : 'Empleado';

    ob_start(); // Empezamos a capturar la salida

?>
    <script src='../assets/qrious.js'></script>
    <script src='../assets/qrcode.min.js'></script>

    <div class='top_nav' style="display: flex;">
        <div class='nav_menu'>

            <nav class='nav d-flex justify-content-between'>
                <div class=" d-flex">
                    <button id="toggle-navbar" class="navbar-toggle-btn">
                        <ion-icon name="menu-outline"></ion-icon>
                    </button>
                </div>

                <ul class='navbar-right d-flex gap-2' style='text-align: right;'>



                    <?php
                    $darkMode = isset($_SESSION["darkMode"]) && $_SESSION["darkMode"] === "SI";
                    $btnClass = $darkMode ? 'btn-dark text-white' : 'btn-light';
                    $iconTheme = $darkMode ? 'sunny-outline' : 'moon-outline';
                    ?>

                    <a class="btn <?= $btnClass ?> item" href="../../configurar/darkMode.php" title="Cambiar modo de tema">
                        <ion-icon name="<?= $iconTheme ?>"></ion-icon>
                    </a>

                    <a class="btn <?= $btnClass ?> item <?= $darkMode ? '' : 'text-danger' ?>" href="../../login/salir.php" title="Cerrar sesión">
                        <ion-icon name="log-out-outline"></ion-icon>
                    </a>




                </ul>
            </nav>
        </div>
    </div>
<?php

    return ob_get_clean(); // Devolvemos el contenido HTML generado
}
?>