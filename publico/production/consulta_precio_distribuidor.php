
 <?php
    /////// CONEXIÓN A LA BASE DE DATOS /////////
    require_once("../../configurar/configuracion.php");
    require_once('../../configurar/session.php');
    require_once('../../configurar/_tasas_cambio.php');


    if (isset($_POST['rep_cantidad'])) {
        $q = $conexion->real_escape_string($_POST['rep_cantidad']);
        $precioDolarVenta = $q;
        $precioPesoVenta = $precioDolarVenta * $pesoDolar;
        $precioPesoVenta = round($precioPesoVenta, 2, PHP_ROUND_HALF_DOWN);
        $precioPesoVenta2 = number_format($precioPesoVenta, '0', ',', '.');
        $precioPesoVenta2 = number_format($precioPesoVenta, '0', ',', '.');

        echo '<input class="form-control" type="text" disabled name="stock" value="' . $precioPesoVenta2 . ' COP">';
    } else {
        echo '<input class="form-control" type="number" disabled name="stock" value="">';
    }
    ?>
