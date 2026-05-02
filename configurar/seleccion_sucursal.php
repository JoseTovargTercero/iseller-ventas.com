<?php /*
require_once 'configuracion.php';
require_once 'session.php';
if ($_SESSION["nivel"] == 1) {
    require_once 'DatabaseHandler/DatabaseHandler.php';
    $db = new DatabaseHandler($conexion);
    header('Content-Type: application/json');


    $condicion = "bss_id = " . intval($bss_id);

    try {
        $resultado = $db->select(['id'], 'sucursales', $condicion);

        if (!empty($resultado)) {
            // Devolver el resultado directamente, ya que cada campo es dinámico
            $resultado = json_decode($resultado, true);

            $ids = array_map(function ($item) {
                return $item['id'];
            }, $resultado['success']);

            $sucursal_seleccionada = $_POST['sucursal'];
            // Paso 2: Verificar si un número existe en el array de IDs
            $existe = in_array($sucursal_seleccionada, $ids);

            if ($existe) {
                $_SESSION["sucursal"] = (int) $sucursal_seleccionada;
                echo json_encode(['success' => "Seleccionado correctamente"]);
            } else {
                echo json_encode(['error' => "No tiene permisos"]);
            }
        } else {
            echo json_encode(['error' => 'Registro no encontrado.']);
        }
    } catch (Exception $e) {
    echo json_encode(['error' => "Error: " . $e->getMessage()]);
    }
} */

require_once 'configuracion.php';
require_once 'session.php';

header('Content-Type: application/json');

if ($_SESSION["nivel"] == 1) {
    // Asumimos que $bss_id y $_POST['sucursal'] ya están definidos o validados
    $bss_id_val = intval($bss_id);
    $sucursal_seleccionada = isset($_POST['sucursal']) ? intval($_POST['sucursal']) : 0;

    try {
        // 1. Preparamos la consulta SQL estándar
        // Usamos "?" como marcador de posición (placeholder)
        $query = "SELECT id FROM sucursales WHERE bss_id = ?";
        
        $stmt = $conexion->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        // 2. Vinculamos el parámetro ("i" indica que es un integer)
        $stmt->bind_param("i", $bss_id_val);
        $stmt->execute();
        
        // 3. Obtenemos el resultado
        $resultado = $stmt->get_result();
        $ids_permitidos = [];

        while ($row = $resultado->fetch_assoc()) {
            $ids_permitidos[] = (int)$row['id'];
        }

        // 4. Verificación de lógica
        if (count($ids_permitidos) > 0) {
            if (in_array($sucursal_seleccionada, $ids_permitidos)) {
                $_SESSION["sucursal"] = $sucursal_seleccionada;
                echo json_encode(['success' => "Seleccionado correctamente"]);
            } else {
                echo json_encode(['error' => "No tiene permisos para esta sucursal"]);
            }
        } else {
            echo json_encode(['error' => 'No se encontraron sucursales para este bss_id.']);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(['error' => "Error en el servidor: " . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => "Acceso denegado: Nivel insuficiente"]);
}
