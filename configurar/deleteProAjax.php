<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');
if ($_SESSION["nivel"] != 1) {
    header("Location:" . '../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $conexion->prepare("SELECT count(*) FROM sucursales WHERE bss_id='$bss_id'");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $sucursales = $row[0];



    if (isset($_POST['id']) && $_POST['id']) {
        $id = $_POST['id'];
        $modo = $_POST['modo'];
        $stock_id = $_POST['stock_id'];

        try {
            // Iniciar transacción
            $conexion->begin_transaction();

            if ($modo == 'p' || $sucursales <= 2) {
                // DELETE productos
                $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Error al preparar el DELETE: " . $conexion->error);
                }
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar el DELETE: " . $stmt->error);
                }
                $stmt->close();
            }

            // DELETE stock
            $stmt2 = $conexion->prepare("DELETE FROM stock WHERE id_producto = ?");
            if (!$stmt2) {
                throw new Exception("Error al preparar el DELETE: " . $conexion->error);
            }
            $stmt2->bind_param("i", $id);
            if (!$stmt2->execute()) {
                throw new Exception("Error al ejecutar el DELETE: " . $stmt2->error);
            }
            $stmt2->close();

            // Confirmar la transacción
            $conexion->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado correctamente.',
                'id' => $id
            ]);
        } catch (Exception $e) {
            // Revertir cambios si hay error
            $conexion->rollback();

            echo json_encode([
                'success' => false,
                'message' => 'Transacción fallida: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID no válido o no enviado.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método de solicitud no permitido.'
    ]);
}
