<?php
require_once("configuracion.php");
require_once('session.php');
if ($_SESSION["nivel"] != 1) {
    header("Location: ../index.php");
    exit;
}

$nombre = $_POST["nombre"];
$tipo = $_POST["tipo"];
$productos_accion = $_POST["productos_accion"];
$stockCritico = $_POST["stock_critico"];
$bss_id = (int) $_SESSION["bss_id"];

if (empty($nombre) || empty($tipo)) {
    echo json_encode(['error' => 'Datos', 'message' => 'Faltan datos']);
    exit;
}

$conexion->begin_transaction();


function cargarArchivo($archivo, $folder, $id)
{
    // Verifica si el archivo existe y no hubo error en la subida
    if (isset($_FILES[$archivo]) && $_FILES[$archivo]['error'] === UPLOAD_ERR_OK) {

        $source = $_FILES[$archivo]['tmp_name']; // Archivo temporal
        $extension = pathinfo($_FILES[$archivo]['name'], PATHINFO_EXTENSION); // Obtener extensión real

        // Validar extensión permitida
        $ext_permitidas = ['jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($extension), $ext_permitidas)) {
            echo "Extensión no permitida";
            return;
        }

        // Crear el directorio si no existe
        if (!file_exists($folder)) {
            if (!mkdir($folder, 0777, true)) {
                echo "No se pudo crear el directorio";
                return;
            }
        }

        $target_path = $folder . '/' . $id . '.png'; // Guardar como PNG
        if (move_uploaded_file($source, $target_path)) {
            return true;
        } else {
            return false;
        }
    }
}

try {
    // Insertar en sucursales
    $stmt = $conexion->prepare("INSERT INTO sucursales (bss_id, tipo, nombre, stockCritico) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $bss_id, $tipo, $nombre, $stockCritico);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar sucursal: " . $stmt->error);
    }

    $id_registro = $conexion->insert_id;
    $stmt->close();


    cargarArchivo("foto", '../publico/production/images/sucursal_logo/', $id_registro);

    // Si hay que copiar productos
    if ($productos_accion === 'copiar') {



        $query = "SELECT id, porcentaje, mayor, id_producto_relacionado FROM productos WHERE bss_id = ? AND activo = 0 ";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $bss_id);

        if (!$stmt->execute()) {
            throw new Exception("Error al obtener productos: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $detal = [];
        $pre_mayor = [];

        while ($row = $result->fetch_assoc()) {
            $id_producto = (int)$row['id'];
            $porcentaje = (float)$row['porcentaje'];
            $id_producto_relacionado = $row['id_producto_relacionado'];

            if ($row['mayor'] == '1') {
                $pre_mayor[] = [$id_producto, $porcentaje, $id_registro, $bss_id, $id_producto_relacionado];
            } else {
                $detal[] = "($id_producto, $porcentaje, $id_registro, $bss_id)";
            }
        }

        $stmt->close();


        // Registro de productos al detal
        if (!empty($detal)) {
            $sql_insert = "INSERT INTO stock (id_producto, porcentaje, id_sucursal, bss_id) VALUES " . implode(",", $detal);
            if (!$conexion->query($sql_insert)) {
                throw new Exception("Error al insertar en stock: " . $conexion->error);
            }
        }


        // Registro de productos al mayor
        if (!empty($pre_mayor)) {
            // $stmt2 = mysqli_prepare($conexion, "SELECT * FROM `stock` WHERE id = ? AND bss_id = ? AND id_sucursal = ? LIMIT 1");
            $stmt2 = $conexion->prepare("SELECT id FROM stock WHERE id_producto = ? AND bss_id = ? AND id_sucursal = ? LIMIT 1");


            $mayor = [];

            foreach ($pre_mayor as $key => $item) {

                $stmt2->bind_param('iii',  $item[4], $bss_id, $id_registro);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($result2->num_rows > 0) {
                    $row = $result2->fetch_assoc();
                    $id_stock = (int)$row['id'];

                    $mayor[] = "($item[0], $item[1], $item[2], $item[3], 1, $id_stock)";
                } else {
                    // Log o manejo de error si no se encuentra el producto relacionado
                    throw new Exception("Producto relacionado no encontrado en stock (ID: $item[4])");
                }
            }





            $sql_insert = "INSERT INTO stock (id_producto, porcentaje, id_sucursal, bss_id, mayor, id_stock) VALUES " . implode(",", $mayor);
            if (!$conexion->query($sql_insert)) {
                throw new Exception("Error al insertar en stock: " . $conexion->error);
            }
            $stmt2->close();
        }
    }




    // Si todo va bien
    $conexion->commit();
    echo json_encode(['success' => true, 'message' => 'Sucursal creada correctamente']);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['error' => true, 'message' => 'Ocurrió un error', 'detalle' => $e->getMessage()]);
}
