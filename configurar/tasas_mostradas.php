
<?php
require_once 'configuracion.php'; // tu conexión a BD
require_once('session.php');
require_once '_tasas_cambio.php'; // la clase que creamos


$accion = $_GET['accion'] ?? $_POST['accion'] ?? null;
$bss_id = $_SESSION['bss_id'];

if (!$accion || !$bss_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parámetros insuficientes'
    ]);
    exit;
}

$tc = new TasasCambio($conexion);

switch ($accion) {
    case 'obtener':
        echo $tc->tasasMostradas($bss_id);
        break;

    case 'actualizar':
        $tasas = $_POST['tasas'] ?? [];
        echo $tc->actualizarTasasMostradas($bss_id, $tasas);
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Acción no válida'
        ]);
        break;
}
