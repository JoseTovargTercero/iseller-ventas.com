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

try {
    // Insertar en sucursales
    $stmt = $conexion->prepare("INSERT INTO sucursales (bss_id, tipo, nombre, stockCritico) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $bss_id, $tipo, $nombre, $stockCritico);

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar sucursal: " . $stmt->error);
    }

    $id_registro = $conexion->insert_id;
    $stmt->close();

    // Si hay que copiar productos
    if ($productos_accion === 'copiar') {
        $query = "SELECT id, porcentaje FROM productos WHERE bss_id = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("i", $bss_id);

        if (!$stmt->execute()) {
            throw new Exception("Error al obtener productos: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $valores = [];

        while ($row = $result->fetch_assoc()) {
            $id_producto = (int)$row['id'];
            $porcentaje = (float)$row['porcentaje'];
            $valores[] = "($id_producto, $porcentaje, $id_registro, $bss_id)";
        }

        $stmt->close();

        if (!empty($valores)) {
            $sql_insert = "INSERT INTO stock (id_producto, porcentaje, id_sucursal, bss_id) VALUES " . implode(",", $valores);
            if (!$conexion->query($sql_insert)) {
                throw new Exception("Error al insertar en stock: " . $conexion->error);
            }
        }
    }

    // Si todo va bien
    $conexion->commit();
    echo json_encode(['success' => true, 'message' => 'Sucursal creada correctamente']);
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['error' => true, 'message' => 'Ocurrió un error', 'detalle' => $e->getMessage()]);
}
