<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json');
if ($_SESSION["nivel"] != 1) {
    header("Location:" . '../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && $_POST['id']) {
        $codigo = $_POST['id'];

        try {
            // Iniciar transacción
            $conexion->begin_transaction();

            // UPDATE productos
            $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Error al preparar el UPDATE: " . $conexion->error);
            }
            $stmt->bind_param("i", $codigo);
            if (!$stmt->execute()) {
                throw new Exception("Error al ejecutar el UPDATE: " . $stmt->error);
            }
            $stmt->close();

            // DELETE stock
            $stmt2 = $conexion->prepare("DELETE FROM stock WHERE id_producto = ?");
            if (!$stmt2) {
                throw new Exception("Error al preparar el DELETE: " . $conexion->error);
            }
            $stmt2->bind_param("i", $codigo);
            if (!$stmt2->execute()) {
                throw new Exception("Error al ejecutar el DELETE: " . $stmt2->error);
            }
            $stmt2->close();

            // Confirmar la transacción
            $conexion->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado correctamente.',
                'id' => $codigo
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
