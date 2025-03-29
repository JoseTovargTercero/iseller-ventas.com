<?php
require_once('configuracion.php');

if (isset($_GET['borrar'])) {
    $codigo = $_GET['id'];

    $stmt = $conexion->prepare("UPDATE productos SET activo='1' WHERE codigo='$codigo'");
    $stmt->execute();
    $stmt->close();

    if (isset($_GET['origen'])) {
        define('PAGINA_INICIO', '../publico/production/nuevoProducto.php?accion=borrado');
        header('Location: ' . PAGINA_INICIO);
    } else {
        define('PAGINA_INICIO', '../publico/production/productos.php?accion=borrado');
        header('Location: ' . PAGINA_INICIO);
    }
}

if (isset($_GET['favorito'])) {
    $codigo = $_GET['id'];
    $fav = $_GET['favorito'];
    if ($fav == 'SI') {
        $stmt = $conexion->prepare("UPDATE productos SET favorito='1' WHERE codigo='$codigo'");
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conexion->prepare("UPDATE productos SET favorito='0' WHERE codigo='$codigo'");
        $stmt->execute();
        $stmt->close();
    }

    $favoritos =  'favorito-' . $fav;

    define('PAGINA_INICIO', '../publico/production/productos.php?accion=' . $favoritos . '');
    header('Location: ' . PAGINA_INICIO);
}

if (isset($_POST['codigoEditar'])) {
    $id = $_POST['codigoEditar'];
    $nombre = $_POST['nombre'];
    $precio_compra = $_POST['precio'];
    $cantidad_unidades = $_POST['cantidad'];
    $porcentaje = $_POST['porcentaje'];
    $origenProducto = $_POST['origenProducto'];
    $codigo_barra = $_POST['codigo_barra'];
    $proveedor = $_POST['proveedor'];

    $stmt = $conexion->prepare("UPDATE productos SET nombre='$nombre', precio_compra='$precio_compra', cantidad_unidades='$cantidad_unidades', porcentaje='$porcentaje', origen='$origenProducto', codigo_barras='$codigo_barra', proveedor='$proveedor' WHERE id='$id'");
    $stmt->execute();
    $stmt->close();


    if (isset($_POST['origen'])) {
        define('PAGINA_INICIO', '../publico/production/nuevoProducto.php?accion=editado&codigo=' . $codigo . '');
        header('Location: ' . PAGINA_INICIO);
    } else {
        define('PAGINA_INICIO', '../publico/production/productos.php?accion=editado');
        header('Location: ' . PAGINA_INICIO);
    }
}
