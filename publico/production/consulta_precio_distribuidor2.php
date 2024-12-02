 <?php
    /////// CONEXIÓN A LA BASE DE DATOS /////////
    require_once("../../configurar/configuracion.php");
    $query2 = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos = $conexion->query($query2);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
            $dolarBolivar = $filaAlumnos['DolarBolivar'];
        }
    }

    if (isset($_POST['rep2'])) {
        $q = $conexion->real_escape_string($_POST['rep2']);
        $precioDolarVenta = $q;
        $precioBsVenta = $precioDolarVenta * $dolarBolivar;
        $precioBsVenta = round($precioBsVenta, 2, PHP_ROUND_HALF_DOWN);
        $precioBsVenta2 = number_format($precioBsVenta, '0', ',', '.');
        $precioBsVenta2 = $precioBsVenta2 . " BS";

        echo '<input class="form-control" type="text" disabled name="stock" value="' . $precioBsVenta2 . '">';
    } else {
        echo '<input class="form-control" type="text" disabled name="stock" value="">';
    }
    ?>
