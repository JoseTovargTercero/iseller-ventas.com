<?php
header('Content-Type: application/json');
require_once('configuracion.php');
require_once('session.php');
require_once('_nombres.php');


$response = [
    'status' => 'error',
    'message' => 'Solicitud inválida',
    'data' => null
];

if (isset($_POST['rep_codigo3'])) {
    $cedula = is_scalar($_POST['rep_codigo3']) ? trim((string) $_POST['rep_codigo3']) : '';
    $bss_id = (int) ($_SESSION['bss_id'] ?? 0);

    if (!empty($cedula)) {
        try {
            // base_nombres primero; si no está, clientes del negocio.
            $cliente = nb_buscar($conexion, $cedula, $bss_id);

            if ($cliente['nombre'] !== '') {
                $response = [
                    'status' => 'success',
                    'message' => 'Cliente encontrado',
                    'data' => [
                        'nombre' => $cliente['nombre'],
                        'telefono' => $cliente['telefono'],
                        'origen' => $cliente['origen']
                    ]
                ];
            } else {
                $response = [
                    'status' => 'not_found',
                    'message' => 'Cliente no registrado',
                    'data' => [
                        'nombre' => '',
                        'telefono' => '',
                        'origen' => ''
                    ]
                ];
            }
        } catch (Exception $e) {
            $response['message'] = 'Error del servidor';
            $response['error_detail'] = $e->getMessage();
        }
    } else {
        $response['status'] = 'empty';
        $response['message'] = 'Cédula no proporcionada';
        $response['data'] = [
            'nombre' => '',
            'telefono' => '',
            'origen' => ''
        ];
    }
}

echo json_encode($response);
