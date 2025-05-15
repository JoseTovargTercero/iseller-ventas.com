<?php
require_once('configuracion.php');
require_once('session.php');
require("_tasas_cambio.php");

// Fechas y usuario
$fdeCompra = date('d-m-Y h:i a');
$SdeCompra = date('Y-W');
$mdeCompra = date('Y-m');
$ddeCompra = date('Y-m-d');
$fdeUser = $_SESSION['nombre'];

// -----------------------------------------------------------------------------
// CONFIGURACIÓN DE FACTURA AUTOMÁTICA
// -----------------------------------------------------------------------------
if (isset($_GET['tipo']) && $_GET['tipo'] == "configurar") {
    $accion = $_GET['accion'];
    $stmt = $conexion->prepare("UPDATE empresa SET factura=? WHERE id='1'");
    $stmt->bind_param("s", $accion);
    $stmt->execute();
    $stmt->close();

    header('Location: ../publico/production/configuracion.php?accion=accionFactura');
    exit;
}

// -----------------------------------------------------------------------------
// DESHACER COMPRA (RESTAURAR STOCK Y ELIMINAR REGISTRO)
// -----------------------------------------------------------------------------
if (isset($_GET['idDeshacer'])) {
    $idCompra = $_GET['idDeshacer'];

    // Obtener detalles de la compra
    $query = $conexion->prepare("SELECT cod, cantidad FROM compras WHERE id=?");
    $query->bind_param("i", $idCompra);
    $query->execute();
    $query->store_result();
    $query->bind_result($cod, $cantidadAgregada);
    $query->fetch();
    $query->close();

    // Obtener stock actual del producto
    $query = $conexion->prepare("SELECT stock FROM productos WHERE codigo=?");
    $query->bind_param("s", $cod);
    $query->execute();
    $query->store_result();
    $query->bind_result($stockActual);
    $query->fetch();
    $query->close();

    $nuevoStock = $stockActual - $cantidadAgregada;

    // Actualizar stock
    $stmt = $conexion->prepare("UPDATE productos SET stock=? WHERE codigo=?");
    $stmt->bind_param("is", $nuevoStock, $cod);
    $stmt->execute();
    $stmt->close();

    // Eliminar compra
    $stmt = $conexion->prepare("DELETE FROM compras WHERE id=?");
    $stmt->bind_param("i", $idCompra);
    $stmt->execute();
    $stmt->close();

    // Actualizar contador de deshacer
    $result = $conexion->query("SELECT deshacerCompra FROM empresa WHERE id='1'");
    $row = $result->fetch_assoc();
    $deshacerCompra = max(0, $row['deshacerCompra'] - 1);

    $stmt = $conexion->prepare("UPDATE empresa SET deshacerCompra=? WHERE id='1'");
    $stmt->bind_param("i", $deshacerCompra);
    $stmt->execute();
    $stmt->close();

    $destino = (isset($_GET['origen']) && $_GET['origen'] == 'simple') ?
        '../publico/production/nuevaCompra.php?accion=mensajeDeshacer' :
        '../publico/production/nuevaCompraFacturas.php?accion=mensajeDeshacer';

    header("Location: $destino");
    exit;
}



// Fallback si no hay otra acción
header('Location: ../publico/production/nuevaCompraFacturas.php');
exit;
