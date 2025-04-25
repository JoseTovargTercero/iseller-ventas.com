<?php
require_once('configuracion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['tasa_USD']) && isset($_POST['ultima_actualizacion'])) {
    $tasa = $_POST['tasa_USD'];
    $ultima_actualizacion = $_POST['ultima_actualizacion'];

    // Limpiar y convertir
    $tasa_float = floatval(str_replace(',', '.', $tasa));
    $fecha_formateada = date('Y-m-d H:i:s', is_numeric($ultima_actualizacion) ? (int)$ultima_actualizacion : strtotime($ultima_actualizacion));

    $sql = "UPDATE tu_tabla SET bcv = ?, last_u_bcv = ? WHERE id = 1";
    $stmt = $conexion->prepare($sql);

    if ($stmt) {
      $stmt->bind_param("ds", $tasa_float, $fecha_formateada);
      if ($stmt->execute()) {
        echo "Tasa actualizada correctamente.";
      } else {
        echo "Error al actualizar: " . $stmt->error;
      }
      $stmt->close();
    } else {
      echo "Error en la preparación: " . $conexion->error;
    }
  } else {
    echo "Faltan datos requeridos.";
  }
} else {
  echo "Método no permitido.";
}
