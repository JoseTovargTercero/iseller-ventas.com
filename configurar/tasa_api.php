<?php

function registrarHistorial($descripcion, $precioactual, $u_nombre, $conexion)
{

    $fecha_actual = date("d-m-Y");


    $sql = "INSERT INTO tasa_historico (u_nombre, precio, descripcion, fecha) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sdss", $u_nombre, $precioactual, $descripcion, $fecha_actual);
    $stmt->execute();
    $stmt->close();
}
function obtenerTasaDeApi()
{
    $api_key = "afa5859e067e3a9f96886ebc";
    $url = "https://v6.exchangerate-api.com/v6/{$api_key}/pair/USD/VES";

    try {
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        return $data['conversion_rate'] ?? 0; // Devuelve la tasa o 0 si no se puede obtener
    } catch (Exception $e) {
        return 0; // En caso de error, devuelve 0 como tasa predeterminada
    }
}

function tasaIsEmpty($conexion)
{
    $stmt_check = $conexion->prepare("SELECT * FROM tasa");
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    return $result_check->num_rows == 0;
}

function guardarTasa($conexion, $tasa)
{
    $descripcion = "Precio del Dólar Actual";
    $simbolo = "$";

    $sql = "INSERT INTO tasa (descripcion, simbolo, valor) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssd", $descripcion, $simbolo, $tasa);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarHistorial("Creacion automática", $tasa, "sigob", $conexion);
        return true; // Tasa guardada con éxito
    } else {
        return false; // Error al guardar la tasa
    }
}

function cambiarTasa($conexion)
{

    $tasa = obtenerTasaDeApi();


    // Actualizar solo el precio actual
    $sql = "UPDATE tasa SET valor = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("d", $tasa);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        registrarHistorial("actualizacion automática", $tasa, "sigob", $conexion);
        return true;
    } else {
        return false;
    }


    // $conexion->close();

}

function verificarUltimaActualizacionTasa($conexion)
{
    // Obtener la fecha del último registro de tasa en la tabla "tasa_historial"
    $sql = "SELECT fecha FROM tasa_historico ORDER BY id DESC LIMIT 1";
    $result = $conexion->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fechaUltimaActualizacion = new DateTime($row['fecha']);
        $fechaActual = new DateTime();

        // Comparar la fecha del último registro con la fecha actual
        if ($fechaUltimaActualizacion->format('d-m-Y') != $fechaActual->format('d-m-Y')) {
            return true; // La fecha del último registro es diferente a la fecha actual
        } else {
            return false; // La fecha del último registro es igual a la fecha actual
        }
    } else {
        return true; // No hay registros en la tabla, se asume que la fecha es diferente a la actual
    }
}
?>