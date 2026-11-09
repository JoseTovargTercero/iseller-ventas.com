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
        $id = (int) $_POST['id'];
        $modo = $_POST['modo'];
        $stock_id = $_POST['stock_id'];

        // No borrar una res con cortes o lotes: dejaría datos huérfanos.
        $stmtGuard = $conexion->prepare(
            "SELECT
                (SELECT COUNT(*) FROM productos WHERE bss_id = ? AND id_producto_relacionado = ? AND (mayor IS NULL OR mayor = 0)) AS cortes,
                (SELECT COUNT(*) FROM lotes WHERE bss_id = ? AND padre_id = ?) AS lotes"
        );
        if ($stmtGuard) {
            $stmtGuard->bind_param('iiii', $bss_id, $id, $bss_id, $id);
            $stmtGuard->execute();
            $guard = $stmtGuard->get_result()->fetch_assoc();
            $stmtGuard->close();
            if ($guard && ((int) $guard['cortes'] > 0 || (int) $guard['lotes'] > 0)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'La res tiene cortes o lotes registrados. Desvincule los cortes antes de eliminarla.'
                ]);
                exit;
            }
        }

        try {
            // Iniciar transacción
            $conexion->begin_transaction();

            if ($modo == 'p' || $sucursales <= 2) {
                // DELETE productos — solo del negocio del usuario
                $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ? AND bss_id = ?");
                if (!$stmt) {
                    throw new Exception("Error al preparar el DELETE: " . $conexion->error);
                }
                $stmt->bind_param("ii", $id, $bss_id);
                if (!$stmt->execute()) {
                    throw new Exception("Error al ejecutar el DELETE: " . $stmt->error);
                }
                $stmt->close();
            }

            // DELETE stock — solo del negocio del usuario
            $stmt2 = $conexion->prepare("DELETE FROM stock WHERE id_producto = ? AND bss_id = ?");
            if (!$stmt2) {
                throw new Exception("Error al preparar el DELETE: " . $conexion->error);
            }
            $stmt2->bind_param("ii", $id, $bss_id);
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
