<?php
require_once('../../../configurar/configuracion.php');
require_once('../../../configurar/session.php');

header('Content-Type: application/json');

if (!isset($_SESSION['bss_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin sesión']);
    exit;
}

$bss_id = (int)$_SESSION['bss_id'];
$nivel = (int)($_SESSION['nivel'] ?? 2);

if ($nivel === 2) {
    $has_perm = false;
    if (isset($_SESSION['permisos']) && is_array($_SESSION['permisos'])) {
        foreach ($_SESSION['permisos'] as $p_item) {
            if (is_array($p_item) && in_array('rendimiento_tienda.php', $p_item)) {
                $has_perm = true;
                break;
            } elseif ($p_item === 'rendimiento_tienda.php') {
                $has_perm = true;
                break;
            }
        }
    }

    if (!$has_perm && isset($_SESSION['id'])) {
        $u_id = (int)$_SESSION['id'];
        $stmtP = $conexion->prepare("
            SELECT up.id 
            FROM users_permisos up
            INNER JOIN menu m ON m.id = up.id_item_menu
            WHERE up.id_user = ? AND m.dir = 'rendimiento_tienda.php'
            LIMIT 1
        ");
        if ($stmtP) {
            $stmtP->bind_param('i', $u_id);
            $stmtP->execute();
            if ($stmtP->get_result()->num_rows > 0) {
                $has_perm = true;
            }
            $stmtP->close();
        }
    }

    if (!$has_perm) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para acceder a este módulo.']);
        exit;
    }
}

$periodo = $_GET['periodo'] ?? 'mes';

$dateFilter = '';
switch ($periodo) {
    case 'dia':
        $dateFilter = "AND DATE(t.created) = CURDATE()";
        break;
    case 'semana':
        $dateFilter = "AND YEARWEEK(t.created, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    default:
        $dateFilter = "AND MONTH(t.created) = MONTH(CURDATE()) AND YEAR(t.created) = YEAR(CURDATE())";
        break;
}

$r = $conexion->query("SELECT COUNT(*) AS total_ordenes, COALESCE(SUM(CASE WHEN status=4 THEN CAST(total_price AS DECIMAL(10,2)) ELSE 0 END),0) AS total_ingreso, SUM(CASE WHEN tipo_entrega='delivery' THEN 1 ELSE 0 END) AS delivery, SUM(CASE WHEN tipo_entrega='retiro_tienda' THEN 1 ELSE 0 END) AS retiro FROM tienda_ordenes t WHERE bss_id=$bss_id $dateFilter");
$kpis = $r->fetch_assoc();

$r2 = $conexion->query("SELECT SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) AS pendientes, SUM(CASE WHEN status=4 THEN 1 ELSE 0 END) AS completadas FROM tienda_ordenes WHERE bss_id=$bss_id");
$est = $r2->fetch_assoc();
$kpis['pendientes'] = (int)($est['pendientes'] ?? 0);
$kpis['completadas'] = (int)($est['completadas'] ?? 0);

$r3 = $conexion->query("SELECT DATE(created) AS dia, COUNT(*) AS ordenes, COALESCE(SUM(CAST(total_price AS DECIMAL(10,2))),0) AS ingreso FROM tienda_ordenes WHERE bss_id=$bss_id AND created >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created) ORDER BY dia ASC");
$chartDias = $r3->fetch_all(MYSQLI_ASSOC);

$r4 = $conexion->query("SELECT p.nombre AS producto, SUM(CAST(ta.quantity AS UNSIGNED)) AS cantidad, COALESCE(SUM(CAST(ta.precio AS DECIMAL(10,2))*CAST(ta.quantity AS DECIMAL(10,2))),0) AS ingreso FROM tienda_orden_articulos ta INNER JOIN tienda_ordenes t ON t.id=ta.order_id INNER JOIN productos p ON p.id=ta.product_id WHERE t.bss_id=$bss_id $dateFilter GROUP BY ta.product_id, p.nombre ORDER BY cantidad DESC LIMIT 5");
$topProductos = $r4->fetch_all(MYSQLI_ASSOC);

$r5 = $conexion->query("SELECT tu.nombre, COUNT(*) AS ordenes, COALESCE(SUM(CAST(t.total_price AS DECIMAL(10,2))),0) AS total FROM tienda_ordenes t INNER JOIN tienda_usuarios tu ON tu.id=t.customer_id WHERE t.bss_id=$bss_id $dateFilter GROUP BY t.customer_id, tu.nombre ORDER BY total DESC LIMIT 5");
$topClientes = $r5->fetch_all(MYSQLI_ASSOC);

$r6 = $conexion->query("SELECT COUNT(*) AS total FROM negocio_productos WHERE negocio_id=$bss_id AND estado_publicacion=1");
$prodPub = (int)($r6->fetch_assoc()['total'] ?? 0);

$conexion->close();

echo json_encode([
    'kpis' => $kpis,
    'chartDias' => $chartDias,
    'topProductos' => $topProductos,
    'topClientes' => $topClientes,
    'productosPublicados' => $prodPub,
    'periodo' => $periodo
]);
