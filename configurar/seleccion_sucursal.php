<?php
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
}
