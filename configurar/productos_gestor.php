
<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');


if (isset($_POST['accion']) && $_POST["accion"] == 'editar') {

    $metodo = $_POST['metodo'];
    $id = $_POST['id'];
    $success = false;
    $message = '';

    /*
    Modifica el back:

    Sí $_POST['metodo'] es igual a:

    'generales':
        Se deben actualizar los datos de la tabla 'productos' para los campos:
        nombre, codigo_barras, proveedor

    'precio':
        Se deben actualizar los datos de la tabla 'productos' para los campos:
        precio_compra, cantidad_unidades, porcentaje, origen

        Adicionalmente se debe recorrer todos los productos de la tabla 'stock' cuyo 'id_producto' sea igual a $id y actualizar el campo: porcentaje

    'porcentaje':
        Se debe actualizar el campo 'porcentaje' de la tabla 'stock' en donde 'id_producto' sea igual a $id y 'id_sucursal' sea igual a $sucursal_a_editar


    */


    try {
        if ($metodo === 'generales') {
            // Actualizar solo nombre, codigo_barras y proveedor
            $nombre = $_POST['nombre'];
            $codigo_barra = $_POST['codigo_barra'];
            $proveedor = $_POST['proveedor'];

            $stmt = $conexion->prepare("UPDATE productos SET nombre = ?, codigo_barras = ?, proveedor = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nombre, $codigo_barra, $proveedor, $id);
            $stmt->execute();
            $success = $stmt->affected_rows > 0;
            $message = $success ? "Datos generales actualizados correctamente" : "No se modificaron los datos generales";
            $stmt->close();
        } elseif ($metodo === 'precio') {
            // Actualizar precio_compra, cantidad_unidades, porcentaje, origen en productos
            // Y también actualizar porcentaje en tabla stock para ese producto

            $precio_compra = $_POST['precio'];
            $cantidad_unidades = $_POST['cantidad'];
            $porcentaje = $_POST['porcentaje'];
            $origenProducto = $_POST['origenProducto'];

            $stmt = $conexion->prepare("UPDATE productos SET precio_compra = ?, cantidad_unidades = ?, porcentaje = ?, origen = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $precio_compra, $cantidad_unidades, $porcentaje, $origenProducto, $id);
            $stmt->execute();
            $stmt->close();

            // Actualizar porcentaje en stock para todos los registros del producto
            $stmt2 = $conexion->prepare("UPDATE stock SET porcentaje = ? WHERE id_producto = ?");
            $stmt2->bind_param("si", $porcentaje, $id);
            $stmt2->execute();
            $success = $stmt2->affected_rows > 0;
            $message = $success ? "Precio actualizado correctamente" : "No se modificó el precio ni el porcentaje en stock";
            $stmt2->close();
        } elseif ($metodo === 'porcentaje') {
            // Actualizar solo porcentaje en la tabla stock para un producto y sucursal específicos
            $porcentaje = $_POST['porcentaje'];
            if ($_SESSION["nivel"] == '1') {
                $sucursal_id = $_POST['sucursal_a_editar'];
            } else {
                $sucursal_id = $_SESSION['id_sucursal'];
            }

            $stmt = $conexion->prepare("UPDATE stock SET porcentaje = ? WHERE id_producto = ? AND id_sucursal = ?");
            $stmt->bind_param("sii", $porcentaje, $id, $sucursal_id);
            $stmt->execute();
            $success = $stmt->affected_rows > 0;
            $message = $success ? "Porcentaje por sucursal actualizado correctamente" : "No se modificó el porcentaje";
            $stmt->close();
        } else {
            $message = "Método no válido";
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Error en el servidor: " . $e->getMessage()
        ]);
        exit;
    }

    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);
    exit;
} else {
    echo json_encode([
        "success" => false,
        "message" => 'No se recibio ninguna accion'
    ]);
}
