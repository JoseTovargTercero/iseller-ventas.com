<?php
/**
 * Tutorial API — iSeller
 * GET  publico/production/db/tutorial_state.php  → devuelve JSON del estado del tutorial
 * POST publico/production/db/tutorial_state.php  → avanza de paso o marca completado
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../../configurar/configuracion.php';

// Solo admins (nivel 1) participan del tutorial
$user_id = (int)($_SESSION['id']    ?? 0);
$bss_id  = (int)($_SESSION['bss_id'] ?? 0);
$nivel   = (string)($_SESSION['nivel']  ?? '');

if (!$user_id || !$bss_id) {
    echo json_encode(['value' => false, 'message' => 'Sin sesión']);
    exit;
}

// Cargar por defecto la única sucursal en sesión para que ventas.php no redirija a seleccion_sucursal.php
if (empty($_SESSION['sucursal']) && $bss_id && isset($conexion)) {
    $stmtSuc = $conexion->prepare("SELECT id FROM sucursales WHERE bss_id = ?");
    if ($stmtSuc) {
        $stmtSuc->bind_param("i", $bss_id);
        $stmtSuc->execute();
        $resSuc = $stmtSuc->get_result();
        if ($resSuc && $resSuc->num_rows === 1) {
            $rowSuc = $resSuc->fetch_assoc();
            $_SESSION['sucursal'] = (int)$rowSuc['id'];
        }
        $stmtSuc->close();
    }
}

// ─── POST: actualizar paso o marcar tutorial completado ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'completar_paso') {
        $paso = (int)($_POST['paso'] ?? 1);
        
        // Si intenta completar el paso 3 (venta), verificar que realmente haya ventas en orden
        if ($paso >= 3) {
            $rVentas = $conexion->query("SELECT COUNT(*) AS c FROM orden WHERE bss_id = $bss_id");
            $cntVentas = $rVentas ? (int)$rVentas->fetch_assoc()['c'] : 0;
            if ($cntVentas <= 0) {
                echo json_encode([
                    'value' => false,
                    'message' => 'Aún no has registrado ninguna venta. Agrega un producto al carrito y completa la venta para finalizar el tutorial.',
                    'paso' => 2,
                    'completed' => false
                ]);
                exit;
            }
        }
        
        $stmt = $conexion->prepare("UPDATE usuarios SET tutorial_paso = GREATEST(tutorial_paso, ?) WHERE id = ? AND nivel = 1");
        if ($stmt) {
            $stmt->bind_param("ii", $paso, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        if ($paso >= 3) {
            $stmtFin = $conexion->prepare("UPDATE usuarios SET tutorial_completado = 1, tutorial_fecha = NOW() WHERE id = ? AND nivel = 1");
            if ($stmtFin) {
                $stmtFin->bind_param("i", $user_id);
                $stmtFin->execute();
                $stmtFin->close();
            }
        }
        
        echo json_encode(['value' => true, 'message' => 'Paso registrado con éxito', 'paso' => $paso]);
        exit;
    }

    // Acción por defecto: finalizar tutorial
    $stmt = $conexion->prepare("UPDATE usuarios SET tutorial_completado = 1, tutorial_paso = 3, tutorial_fecha = NOW() WHERE id = ? AND nivel = 1");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['value' => true, 'message' => 'Tutorial marcado como completado']);
    exit;
}

// ─── GET: calcular estado actual ───────────────────────────────────────────────

// Solo aplica a nivel 1
if ($nivel !== '1') {
    echo json_encode(['value' => true, 'message' => 'OK', 'data' => ['completed' => true, 'currentStep' => 3, 'totalSteps' => 3]]);
    exit;
}

// 1. Consultar estado en usuarios
$stmtCheck = $conexion->prepare("SELECT tutorial_completado, tutorial_paso FROM usuarios WHERE id = ? LIMIT 1");
$stmtCheck->bind_param("i", $user_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$rowCheck = $resCheck->fetch_assoc();
$stmtCheck->close();

if ($rowCheck && $rowCheck['tutorial_completado']) {
    echo json_encode([
        'value' => true,
        'message' => 'OK',
        'data' => ['completed' => true, 'currentStep' => 3, 'totalSteps' => 3]
    ]);
    exit;
}

$tutorial_paso = $rowCheck ? (int)$rowCheck['tutorial_paso'] : 0;

// 2. Definir los 3 pasos del tutorial iSeller
$steps = [
    [
        'key'       => 'tasas',
        'label'     => 'Configura tus tasas de cambio',
        'url'       => 'cambiar_tasas.php',
        'desc'      => 'Antes de comenzar a vender, define cómo calcularás los precios en Bolívares y otras monedas.',
        'details'   => 'Elige tu modo preferido: <b>Tasa BCV oficial</b>, <b>BCV + Margen</b> (para cubrir comisiones) o <b>Manual</b>. Al guardar tus cambios o presionar <b>Continuar</b>, quedará registrado tu primer paso.',
        'video_url' => 'https://www.youtube.com/embed/0G0YRhPiPnE?autoplay=1'
    ],
    [
        'key'       => 'producto',
        'label'     => 'Registra tu primer producto',
        'url'       => 'nuevoProducto.php',
        'desc'      => 'Agrega un producto a tu inventario con nombre, precio y cantidad para tenerlo disponible para la venta.',
        'details'   => 'Ingresa el <b>Nombre</b>, el <b>Precio del bulto</b> en moneda origen y las <b>Unidades que contiene</b>. Al hacer click en <b>Guardar producto</b> se registrará en tu catálogo.',
        'video_url' => 'https://www.youtube.com/embed/lvw7DftrvQ0?autoplay=1'
    ],
    [
        'key'       => 'venta',
        'label'     => 'Registra tu primera venta',
        'url'       => 'ventas.php',
        'desc'      => 'Aprende a buscar un producto, agregarlo al carrito y cerrar una venta en el punto de venta.',
        'details'   => 'Escribe en el <b>buscador</b> el nombre o código del producto. Haz click para añadirlo al carrito y luego presiona el botón <b>(V) VENDER</b> para registrar la venta.',
        'video_url' => 'https://www.youtube.com/embed/7dJqrOZTbuY?autoplay=1'
    ]
];

// 3. Determinar el paso actual
// Paso 0: Ajuste de tasas. SIEMPRE debe realizarse primero.
// Solo se considera superado cuando tutorial_paso >= 1.
$currentStep = 0;

if ($tutorial_paso < 1) {
    $currentStep = 0;
} else {
    // Paso 1 completado. Ahora verificamos productos en inventario
    $rStock = $conexion->query("SELECT COUNT(*) AS c FROM stock WHERE bss_id = $bss_id");
    $cntStock = $rStock ? (int)$rStock->fetch_assoc()['c'] : 0;

    if ($cntStock === 0) {
        $currentStep = 1; // Debe registrar producto
    } else {
        // Ya tiene productos registrados -> paso 2 completado
        // Si el usuario no tenía tutorial_paso >= 2, actualizarlo
        if ($tutorial_paso < 2) {
            $stmtPaso2 = $conexion->prepare("UPDATE usuarios SET tutorial_paso = 2 WHERE id = ? AND tutorial_paso < 2");
            if ($stmtPaso2) {
                $stmtPaso2->bind_param("i", $user_id);
                $stmtPaso2->execute();
                $stmtPaso2->close();
            }
        }

        // Verificamos si ya registró su primera venta en la tabla orden
        $rVentas = $conexion->query("SELECT COUNT(*) AS c FROM orden WHERE bss_id = $bss_id");
        $cntVentas = $rVentas ? (int)$rVentas->fetch_assoc()['c'] : 0;

        if ($cntVentas === 0) {
            $currentStep = 2; // Debe registrar venta
        } else {
            // Ya tiene ventas -> tutorial completado
            $currentStep = 3;
            $stmtFin = $conexion->prepare("UPDATE usuarios SET tutorial_completado = 1, tutorial_paso = 3, tutorial_fecha = NOW() WHERE id = ? AND nivel = 1 AND tutorial_completado = 0");
            if ($stmtFin) {
                $stmtFin->bind_param("i", $user_id);
                $stmtFin->execute();
                $stmtFin->close();
            }
        }
    }
}

$completed = ($currentStep >= count($steps));

echo json_encode([
    'value' => true,
    'message' => 'OK',
    'data' => [
        'steps'       => $steps,
        'currentStep' => $currentStep,
        'totalSteps'  => count($steps),
        'completed'   => $completed
    ]
]);
