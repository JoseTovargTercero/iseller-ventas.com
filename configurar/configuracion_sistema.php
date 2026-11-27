<?php
require_once("configuracion.php");
require_once('session.php');
require_once(__DIR__ . '/_stock_pendiente.php');

$response = [
  'status' => 'error',
  'message' => 'Parámetros inválidos'
];
http_response_code(400);

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
      'cortes_caja' => [
        'columna' => 'cortes_caja'
      ],
      'registro_clientes' => [
        'columna' => 'registro_clientes'
      ],
      'factura' => [
        'columna' => 'factura'
      ]
    ];

    $puedeGuardar = true;
    $query = '';
    $etiqueta = '';

    if ($name === 'seguimiento_estricto') {
      $query = "UPDATE negocio SET seguimiento_estricto = ? WHERE id = ?";
      $etiqueta = 'seguimiento_estricto';
    } elseif ($name === 'stock_pendiente') {
      if (!sp_asegurar_columna_negocio($conexion)) {
        $puedeGuardar = false;
        http_response_code(500);
        $response['message'] = 'No se pudo preparar la configuración de stock pendiente.';
      } elseif ($status === '1' && !sp_asegurar_todo($conexion)) {
        $puedeGuardar = false;
        http_response_code(500);
        $response['message'] = 'No se pudo preparar la tabla del stock pendiente. Verifica los permisos de la base de datos.';
      } else {
        $query = "UPDATE negocio SET stock_pendiente = ? WHERE id = ?";
        $etiqueta = 'stock pendiente';
      }
    } elseif (!isset($configs[$name])) {
      $puedeGuardar = false;
      $response['message'] = 'Configuración no reconocida.';
    } else {
      $columna = $configs[$name]['columna'];
      $query = "UPDATE configuracion SET $columna = ? WHERE bss_id = ?";
      $etiqueta = $columna;
    }

    if ($puedeGuardar) {
      $stmt = $conexion->prepare($query);
      if ($stmt) {
        $stmt->bind_param("ii", $status, $bss_id);
        if ($stmt->execute()) {
          http_response_code(200);
          $response = [
            'status' => 'success',
            'message' => 'Configuración actualizada correctamente: ' . $etiqueta
          ];
        } else {
          http_response_code(500);
          $response = [
            'status' => 'error',
            'message' => 'Error al actualizar la configuración'
          ];
        }
        $stmt->close();
      } else {
        http_response_code(500);
        $response = [
          'status' => 'error',
          'message' => 'No se pudo preparar la actualización de la configuración.'
        ];
      }
    }
  }
}

echo json_encode($response);
