<?php
require_once('configuracion.php');


if (isset($_POST['empresa'])) {
    $nombreEmpresa  = strip_tags(addslashes($_POST['empresa']));


    $stmt_o = $conexion->prepare("UPDATE empresa SET emp='$nombreEmpresa' WHERE id='1'");
    $stmt_o->execute();
    $stmt_o->close();


    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=empresa');
    header('Location: ' . PAGINA_INICIO);
}


if (isset($_POST['dolarPeso'])) {
    $dolarPeso  = strip_tags(addslashes($_POST['dolarPeso']));
    $pesoBolivar  = strip_tags(addslashes($_POST['pesoBolivar']));
    $pesoBolivarPublicacion = strip_tags(addslashes($_POST['pesoBolivarPublicacion']));


    $query2 = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $bolivar_peso = $filaAlumnos2['bolivar_peso'];
        }
    }
    $bolivar_peso += 1;


    $stmt_o = $conexion->prepare("UPDATE cambio SET pesoDolar='$dolarPeso', bolivar_peso='$bolivar_peso', DolarBolivar='$pesoBolivar', bolivarPesoVenta='$pesoBolivarPublicacion' WHERE id='1'");
    $stmt_o->execute();
    $stmt_o->close();







    $nombreUsuario = $_SESSION['nombre'];
    $fecha = date("d-m-Y");
    $hora =  date("h:i a");


    $stmt_o = $conexion->prepare("INSERT INTO `cambios_tasas` (`user`, `fecha`, `hora`, `peso`, `bolivar`) VALUES 
 ('$nombreUsuario', '$fecha', '$hora', '$dolarPeso', '$pesoBolivar');");
    $stmt_o->execute();
    $stmt_o->close();


    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=tasas');
    header('Location: ' . PAGINA_INICIO);
}











if (isset($_POST['menosUno'])) {

    $query = "SELECT * FROM tasas_dolar ORDER BY id DESC LIMIT 1";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
            $finForDolar = $filaAlumnos['id'];
        }
    }
    $query12 = "SELECT * FROM tasas_pesos ORDER BY id_peso DESC LIMIT 1";
    $buscarAlumnos12 = $conexion->query($query12);
    if ($buscarAlumnos12->num_rows > 0) {
        while ($filaAlumnos12 = $buscarAlumnos12->fetch_assoc()) {
            $finForPesos = $filaAlumnos12['id_peso'];
        }
    }

    $finForPesos += 1;
    $finForDolar += 1;


    for ($iUSD = 0; $iUSD <= $finForDolar; $iUSD++) {
        $cambioDolar = $_POST['dolarBolivar_' . $iUSD . ''];



        $stmt_o = $conexion->prepare("UPDATE tasas_dolar SET recepcion='$cambioDolar' WHERE id='$iUSD'");
        $stmt_o->execute();
        $stmt_o->close();
    }

    for ($iUSD2 = 0; $iUSD2 <= $finForDolar; $iUSD2++) {
        $recepcionPeso = $_POST['recepcionPeso_' . $iUSD2 . ''];
        $PublicacionPeso = $_POST['Publicacion_' . $iUSD2 . ''];


        $stmt_o = $conexion->prepare("UPDATE tasas_pesos SET recepcion_peso='$recepcionPeso', publicacion_peso='$PublicacionPeso' WHERE id_peso='$iUSD2'");
        $stmt_o->execute();
        $stmt_o->close();
    }

    $nombreUsuario = $_SESSION['nombre'];
    $fecha = date("d-m-Y");
    $hora =  date("h:i a");


    $stmt_o = $conexion->prepare("INSERT INTO `cambios_tasas` (`user`, `fecha`, `hora`, `peso`, `bolivar`) VALUES 
('$nombreUsuario', '$fecha', '$hora', 'CAMBIOS', 'CAMBIOS');");
    $stmt_o->execute();
    $stmt_o->close();


    echo '<script>
window.history.go(-1);
</script>';
}











































if (isset($_POST['stockCritico'])) {
    $stockCritico  = strip_tags(addslashes($_POST['stockCritico']));


    $stmt = $conexion->prepare("UPDATE empresa SET stockCritico='$stockCritico' WHERE id='1'");
    $stmt->execute();
    $stmt->close();



    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=stockCritico');
    header('Location: ' . PAGINA_INICIO);
}

if (isset($_GET['accion'])) {

    if ($_GET['accion'] == "activar") {
        $accion = "1";
    } else {
        $accion = "0";
    }
    $stmt = $conexion->prepare("UPDATE empresa SET notificacionStockCritico='$accion' WHERE id='1'");
    $stmt->execute();
    $stmt->close();
    define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=notificacion');
    header('Location: ' . PAGINA_INICIO);
}
