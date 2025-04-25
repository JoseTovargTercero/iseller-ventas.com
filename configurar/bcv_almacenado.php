<?php
require_once('configuracion.php');
$user = '1';

$stmt = mysqli_prepare($conexion, "SELECT * FROM `cambio` WHERE id = ?");
$stmt->bind_param('s', $user);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    echo json_encode([
      "tasa" => $row['DolarBolivar'],
      "ultimo_Cambio" => $row['last_u_bcv']
    ]);
  }
}
$stmt->close();
