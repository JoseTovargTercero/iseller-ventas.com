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
    $idCompra = (int) $_GET['idDeshacer'];

    // Obtener detalles de la compra
    $query = $conexion->prepare("SELECT cod, cantidad, id_sucursal, factura_id FROM compras WHERE id=? AND bss_id=?");
    $query->bind_param("ii", $idCompra, $bss_id);
    $query->execute();
    $query->store_result();
    $query->bind_result($cod, $cantidadAgregada, $idSucursalCompra, $facturaId);
    $query->fetch();
    $query->close();

    // Las líneas de una factura no se deshacen desde el listado clásico.
    if ($cod === null) {
        header('Location: ../publico/production/nuevaCompra.php');
        exit;
    }
    if ($facturaId !== null) {
        header('Location: ../publico/production/compras_facturas.php');
        exit;
    }

    $productoId = (int) $cod;
    $cantidadAgregada = (int) $cantidadAgregada;

    $conexion->begin_transaction();
    try {
        // Comprobar que el producto tiene stock en la sucursal de la compra.
        $query = $conexion->prepare("SELECT id FROM stock WHERE id_producto=? AND id_sucursal=? AND bss_id=? LIMIT 1");
        if (!$query) throw new Exception('Error de base de datos.');
        $query->bind_param("iii", $productoId, $idSucursalCompra, $bss_id);
        $query->execute();
        $tieneStock = $query->get_result()->fetch_assoc();
        $query->close();
        if (!$tieneStock) throw new Exception('Stock no encontrado.');

        $stmt = $conexion->prepare("UPDATE stock SET stock = GREATEST(COALESCE(stock, 0) - ?, 0) WHERE id_producto=? AND id_sucursal=? AND bss_id=?");
        if (!$stmt) throw new Exception('Error de base de datos.');
        $stmt->bind_param("iiii", $cantidadAgregada, $productoId, $idSucursalCompra, $bss_id);
        if (!$stmt->execute()) throw new Exception('No se pudo actualizar el stock.');
        $stmt->close();

        $stmt = $conexion->prepare("DELETE FROM compras WHERE id=? AND bss_id=?");
        if (!$stmt) throw new Exception('Error de base de datos.');
        $stmt->bind_param("ii", $idCompra, $bss_id);
        if (!$stmt->execute()) throw new Exception('No se pudo eliminar la compra.');
        $stmt->close();

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
    }

    header('Location: ../publico/production/nuevaCompra.php?accion=mensajeDeshacer');
    exit;
}



// Fallback si no hay otra acción
header('Location: ../publico/production/nuevaCompra.php');
exit;
