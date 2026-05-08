<?php
header('Content-Type: application/json');
require_once('configuracion.php');
require_once('session.php');


$response = [
    'status' => 'error',
    'message' => 'Solicitud inválida',
    'data' => null
];

if (isset($_POST['rep_codigo3'])) {
    $cedula = trim($_POST['rep_codigo3']);
    $bss_id = $_SESSION['bss_id'] ?? null;

    if (!empty($cedula)) {
        try {
            // Sentencia preparada para evitar inyección SQL
            $sql = "SELECT nombre, telefono FROM clientes WHERE cedula = ? AND bss_id = ? ";

            // Filtrar por bss_id si está presente en la sesión (multi-tenant)
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("si", $cedula, $bss_id);

            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $cliente = $result->fetch_assoc();
                    $response = [
                        'status' => 'success',
                        'message' => 'Cliente encontrado',
                        'data' => [
                            'nombre' => $cliente['nombre'],
                            'telefono' => $cliente['telefono']
                        ]
                    ];
                } else {
                    $response = [
                        'status' => 'not_found',
                        'message' => 'Cliente no registrado',
                        'data' => [
                            'nombre' => '',
                            'telefono' => ''
                        ]
                    ];
                }
            } else {
                $response['message'] = 'Error en la base de datos';
            }
            $stmt->close();
        } catch (Exception $e) {
            $response['message'] = 'Error del servidor';
            $response['error_detail'] = $e->getMessage();
        }
    } else {
        $response['status'] = 'empty';
        $response['message'] = 'Cédula no proporcionada';
        $response['data'] = [
            'nombre' => '',
            'telefono' => ''
        ];
    }
}

echo json_encode($response);
