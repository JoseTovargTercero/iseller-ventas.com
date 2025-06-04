<?php
require_once("configuracion.php");
require_once('session.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION["nivel"] == 1) {
  $name = $_POST['name'] ?? null;
  $status = $_POST['status'] ?? null;

  if ($name !== null && ($status === '1' || $status === '0')) {

    $configs = [
      'tickets_imp' => [
        'columna' => 'tickets'
      ],
      'only_bs' => [
        'columna' => 'bs_ticket'
      ],
    ];

    $columna = $configs[$name]['columna'];

    $query = "UPDATE configuracion SET $columna = ? WHERE bss_id = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $status, $bss_id);
    if ($stmt->execute()) {
      http_response_code(200);
      $response = [
        'status' => 'success',
        'message' => 'Configuración actualizada correctamente' . $columna
      ];
    } else {
      http_response_code(500);
      $response = [
        'status' => 'error',
        'message' => 'Error al actualizar la configuración'
      ];
    }
  } else {
    http_response_code(400);
    $response = [
      'status' => 'error',
      'message' => 'Parámetros inválidos'
    ];
  }
}
echo json_encode($response);
