<?php
require_once __DIR__ . '/security.php';

// ── Guard de seguridad ────────────────────────────────────────────────────────
AdminSecurity::requireLogin();

// ── Conexión a BD ─────────────────────────────────────────────────────────────
$conexion = AdminSecurity::getConexion();
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? ($_SESSION['admin_user'] ?? 'Admin'));
$csrf_token = AdminSecurity::getCsrfToken();

$msg_success = '';
$msg_error = '';

// ── Procesar Acciones POST (Aprobar / Rechazar) ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!AdminSecurity::verifyCsrfToken($token)) {
        header('Location: pagos.php?error=csrf');
        exit;
    }

    $accion = $_POST['accion'] ?? '';
    $pago_id = (int)($_POST['pago_id'] ?? 0);

    if ($pago_id > 0) {
        if ($accion === 'aprobar') {
            $nota_admin = trim($_POST['nota_admin'] ?? '');
            if (empty($nota_admin)) {
                $nota_admin = 'Pago verificado y aprobado por administración.';
            }

            try {
                $conexion->begin_transaction();

                // 1. Obtener datos del pago
                $stmtP = $conexion->prepare("SELECT * FROM membresias_pagos WHERE id = ? FOR UPDATE");
                $stmtP->bind_param("i", $pago_id);
                $stmtP->execute();
                $resP = $stmtP->get_result();
                $pago = $resP->fetch_assoc();
                $stmtP->close();

                if (!$pago) {
                    throw new Exception("Pago no encontrado.");
                }

                $bss_id = (int)$pago['bss_id'];
                $plan = $pago['plan']; // 'mensual' o 'anual'

                $procesado_por = $_SESSION['admin_id'] ?? null;

                // 2. Actualizar estado del pago a aprobado
                $stmtUpdPago = $conexion->prepare("UPDATE membresias_pagos SET estado = 'aprobado', fecha_procesado = NOW(), nota_admin = ?, procesado_por = ? WHERE id = ?");
                $stmtUpdPago->bind_param("sii", $nota_admin, $procesado_por, $pago_id);
                $stmtUpdPago->execute();
                $stmtUpdPago->close();

                // 3. Consultar datos actuales del negocio (tipo e inicio son necesarios para decidir si se extiende o reinicia)
                $stmtNeg = $conexion->prepare("SELECT membresia_tipo, fecha_inicio_membresia, fecha_fin_membresia FROM negocio WHERE id = ? FOR UPDATE");
                $stmtNeg->bind_param("i", $bss_id);
                $stmtNeg->execute();
                $resNeg = $stmtNeg->get_result();
                $negocio = $resNeg->fetch_assoc();
                $stmtNeg->close();

                // Calcular nueva fecha de inicio y fin
                $ahora = time();
                $fecha_base = $ahora;
                $nueva_fecha_inicio = date('Y-m-d H:i:s');

                if (!empty($negocio['fecha_fin_membresia'])) {
                    $finActual = strtotime($negocio['fecha_fin_membresia']);
                    // Si ya tenía una membresía activa y vigente que NO sea prueba, extender a partir del vencimiento
                    if ($finActual > $ahora && ($negocio['membresia_tipo'] ?? '') !== 'prueba') {
                        $fecha_base = $finActual;
                        if (!empty($negocio['fecha_inicio_membresia'])) {
                            $nueva_fecha_inicio = $negocio['fecha_inicio_membresia'];
                        }
                    }
                }

                // Sumar según el plan
                if ($plan === 'anual') {
                    $nueva_fecha_fin = date('Y-m-d 23:59:59', strtotime('+1 year', $fecha_base));
                } else {
                    $nueva_fecha_fin = date('Y-m-d 23:59:59', strtotime('+1 month', $fecha_base));
                }

                $proxima_facturacion = date('Y-m-d', strtotime($nueva_fecha_fin));

                // 4. Actualizar negocio con fecha_inicio_membresia correcta
                $stmtUpdNeg = $conexion->prepare("UPDATE negocio SET membresia_tipo = ?, plan = ?, membresia_estado = 'activa', estado = 'activo', fecha_inicio_membresia = ?, fecha_fin_membresia = ?, proxima_facturacion = ? WHERE id = ?");
                $stmtUpdNeg->bind_param("sssssi", $plan, $plan, $nueva_fecha_inicio, $nueva_fecha_fin, $proxima_facturacion, $bss_id);
                $stmtUpdNeg->execute();
                $stmtUpdNeg->close();

                // Los demás comprobantes pendientes del mismo negocio quedan obsoletos al aprobarse uno.
                $stmtSup = $conexion->prepare("UPDATE membresias_pagos SET estado = 'rechazado', fecha_procesado = NOW(), nota_admin = 'Cerrado automáticamente: se aprobó otro comprobante del negocio.', procesado_por = ? WHERE bss_id = ? AND estado = 'pendiente' AND id <> ?");
                $stmtSup->bind_param("iii", $procesado_por, $bss_id, $pago_id);
                $stmtSup->execute();
                $stmtSup->close();

                $conexion->commit();
                header("Location: pagos.php?msg=aprobado&ref=" . urlencode($pago['numero_operacion']));
                exit;

            } catch (Exception $e) {
                $conexion->rollback();
                $msg_error = "Error al aprobar pago: " . $e->getMessage();
            }

        } elseif ($accion === 'rechazar') {
            $motivo = trim($_POST['motivo'] ?? '');
            if (empty($motivo)) {
                $motivo = "Comprobante no válido o fondos no confirmados. Por favor ingresa un comprobante válido.";
            }

            try {
                $conexion->begin_transaction();

                // 1. Obtener datos del pago
                $stmtP = $conexion->prepare("SELECT * FROM membresias_pagos WHERE id = ? FOR UPDATE");
                $stmtP->bind_param("i", $pago_id);
                $stmtP->execute();
                $resP = $stmtP->get_result();
                $pago = $resP->fetch_assoc();
                $stmtP->close();

                if (!$pago) {
                    throw new Exception("Pago no encontrado.");
                }

                $bss_id = (int)$pago['bss_id'];
                $procesado_por = $_SESSION['admin_id'] ?? null;

                // 2. Marcar pago como rechazado
                $stmtUpdPago = $conexion->prepare("UPDATE membresias_pagos SET estado = 'rechazado', fecha_procesado = NOW(), nota_admin = ?, procesado_por = ? WHERE id = ?");
                $stmtUpdPago->bind_param("sii", $motivo, $procesado_por, $pago_id);
                $stmtUpdPago->execute();
                $stmtUpdPago->close();

                // 3. Devolver el negocio a un estado coherente: 'vencida' solo si la vigencia ya expiró;
                //    si aún está vigente (p. ej. un comprobante adelantado) se conserva 'activa'.
                $stmtNeg = $conexion->prepare("SELECT fecha_fin_membresia FROM negocio WHERE id = ? FOR UPDATE");
                $stmtNeg->bind_param("i", $bss_id);
                $stmtNeg->execute();
                $resNeg = $stmtNeg->get_result();
                $negocio = $resNeg->fetch_assoc();
                $stmtNeg->close();

                $sigue_vigente = !empty($negocio['fecha_fin_membresia']) && strtotime($negocio['fecha_fin_membresia']) > time();
                $estado_negocio = $sigue_vigente ? 'activa' : 'vencida';

                $stmtUpdNeg = $conexion->prepare("UPDATE negocio SET membresia_estado = ? WHERE id = ?");
                $stmtUpdNeg->bind_param("si", $estado_negocio, $bss_id);
                $stmtUpdNeg->execute();
                $stmtUpdNeg->close();

                $conexion->commit();
                header("Location: pagos.php?msg=rechazado&ref=" . urlencode($pago['numero_operacion']));
                exit;

            } catch (Exception $e) {
                $conexion->rollback();
                $msg_error = "Error al rechazar pago: " . $e->getMessage();
            }

        } elseif ($accion === 'activar_prueba') {
            $bss_id_target = (int)($_POST['bss_id'] ?? 0);
            $dias = max(1, (int)($_POST['dias'] ?? 30));

            if ($bss_id_target <= 0) {
                $msg_error = "Selecciona un negocio válido para activar la membresía de prueba.";
            } else {
                $ahora = date('Y-m-d H:i:s');
                $nueva_fin = date('Y-m-d 23:59:59', strtotime("+{$dias} days"));
                $proxima = date('Y-m-d', strtotime("+{$dias} days"));

                $stmtPrueba = $conexion->prepare("UPDATE negocio SET membresia_tipo = 'prueba', plan = 'mensual', membresia_estado = 'activa', estado = 'activo', fecha_inicio_membresia = ?, fecha_fin_membresia = ?, proxima_facturacion = ? WHERE id = ?");
                if ($stmtPrueba) {
                    $stmtPrueba->bind_param("sssi", $ahora, $nueva_fin, $proxima, $bss_id_target);
                    if ($stmtPrueba->execute()) {
                        header("Location: pagos.php?msg=prueba_activada&dias={$dias}");
                        exit;
                    } else {
                        $msg_error = "Error al activar prueba: " . $stmtPrueba->error;
                    }
                    $stmtPrueba->close();
                } else {
                    $msg_error = "Error al preparar actualización: " . $conexion->error;
                }
            }
        }
    }
}

// Mensajes flash
if (isset($_GET['msg'])) {
    $ref = htmlspecialchars($_GET['ref'] ?? '');
    if ($_GET['msg'] === 'aprobado') {
        $msg_success = "¡Pago con referencia <strong>{$ref}</strong> ha sido <strong>aprobado</strong> exitosamente! La membresía del negocio fue extendida.";
    } elseif ($_GET['msg'] === 'rechazado') {
        $msg_success = "Se ha solicitado un comprobante válido para la referencia <strong>{$ref}</strong>. El negocio ha sido notificado al ingresar al sistema.";
    } elseif ($_GET['msg'] === 'prueba_activada') {
        $d = (int)($_GET['dias'] ?? 30);
        $msg_success = "¡Membresía de prueba de <strong>{$d} días</strong> activada exitosamente con fecha de inicio actual!";
    }
}
if (isset($_GET['error']) && $_GET['error'] === 'csrf') {
    $msg_error = "Error de seguridad CSRF. Intenta nuevamente.";
}

// ── Consultas para la Vista ───────────────────────────────────────────────────

// 1. Contadores y métricas KPI
$stats = [
    'total'      => 0,
    'pendientes' => 0,
    'aprobados'  => 0,
    'rechazados' => 0,
    'monto_usd'  => 0.00
];

$resStats = $conexion->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) AS aprobados,
        SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) AS rechazados,
        SUM(CASE WHEN estado = 'aprobado' THEN monto_usd ELSE 0 END) AS total_recaudado
    FROM membresias_pagos
");
if ($resStats && $r = $resStats->fetch_assoc()) {
    $stats['total']      = (int)($r['total'] ?? 0);
    $stats['pendientes'] = (int)($r['pendientes'] ?? 0);
    $stats['aprobados']  = (int)($r['aprobados'] ?? 0);
    $stats['rechazados'] = (int)($r['rechazados'] ?? 0);
    $stats['monto_usd']  = (float)($r['total_recaudado'] ?? 0.00);
}

// Filtro de pestaña activo (todos, pendientes, aprobados, rechazados)
$filtro = $_GET['filtro'] ?? ($stats['pendientes'] > 0 ? 'pendientes' : 'todos');
$whereFiltro = "";
if ($filtro === 'pendientes') {
    $whereFiltro = "WHERE p.estado = 'pendiente'";
} elseif ($filtro === 'aprobados') {
    $whereFiltro = "WHERE p.estado = 'aprobado'";
} elseif ($filtro === 'rechazados') {
    $whereFiltro = "WHERE p.estado = 'rechazado'";
}

// 2. Consulta de pagos con datos de negocio y usuario
$pagos = [];
$sqlPagos = "
    SELECT 
        p.*,
        n.nombre AS negocio_nombre,
        n.membresia_estado,
        n.membresia_tipo,
        n.fecha_fin_membresia,
        n.whatsapp AS negocio_whatsapp,
        u.nombre AS usuario_nombre,
        u.usuario AS usuario_email
    FROM membresias_pagos p
    LEFT JOIN negocio n ON p.bss_id = n.id
    LEFT JOIN usuarios u ON p.usuario_id = u.id
    $whereFiltro
    ORDER BY 
        CASE WHEN p.estado = 'pendiente' THEN 0 ELSE 1 END,
        p.id DESC
";

$resPagos = $conexion->query($sqlPagos);
if ($resPagos) {
    while ($row = $resPagos->fetch_assoc()) {
        $pagos[] = $row;
    }
}

// Negocios para activación manual de prueba
$negociosParaPrueba = [];
$resNegs = $conexion->query("SELECT id, nombre, membresia_tipo, membresia_estado FROM negocio ORDER BY nombre ASC");
if ($resNegs) {
    while ($rn = $resNegs->fetch_assoc()) {
        $negociosParaPrueba[] = $rn;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iSeller Admin — Pagos de Membresía</title>
    <link href="../publico/vendors/fonts/fonts.css" rel="stylesheet">
    <link href='../publico/vendors/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #080910;
            --bg-surface: #0f1117;
            --bg-card: rgba(255, 255, 255, 0.035);
            --bg-card-h: rgba(255, 255, 255, 0.06);
            --border: rgba(255, 255, 255, 0.08);
            --border-light: rgba(255, 255, 255, 0.12);
            --accent: #6366f1;
            --accent-2: #8b5cf6;
            --accent-glow: rgba(99, 102, 241, 0.3);
            --cyan: #06b6d4;
            --green: #22c55e;
            --amber: #f59e0b;
            --red: #ef4444;
            --text-1: #f1f5f9;
            --text-2: #94a3b8;
            --text-3: #475569;
            --radius-sm: 8px;
            --radius: 14px;
            --radius-lg: 20px;
            --sidebar-w: 240px;
            --header-h: 68px;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-1);
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.018) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.018) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .orb {
            position: fixed; border-radius: 50%;
            filter: blur(100px); opacity: 0.12; pointer-events: none; z-index: 0;
        }
        .orb-1 { width: 600px; height: 600px; background: var(--accent); top: -200px; left: -200px; }
        .orb-2 { width: 400px; height: 400px; background: var(--accent-2); bottom: -150px; right: -150px; }

        /* ── Sidebar ── */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: rgba(10, 11, 18, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 100;
        }
        .sidebar-logo {
            height: var(--header-h); padding: 0 20px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; box-shadow: 0 4px 14px var(--accent-glow);
        }
        .sidebar-logo-text .brand { font-size: 16px; font-weight: 700; letter-spacing: -0.3px; }
        .sidebar-logo-text .tag {
            font-size: 9px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--accent);
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 4px; padding: 1px 5px; margin-left: 6px;
        }
        .sidebar-nav { flex: 1; padding: 16px 12px; display: flex; flex-direction: column; gap: 4px; }
        .nav-section {
            font-size: 10px; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--text-3);
            padding: 12px 12px 4px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: var(--radius-sm);
            color: var(--text-2); text-decoration: none;
            font-size: 13px; font-weight: 500; transition: all 0.15s;
        }
        .nav-item:hover { color: var(--text-1); background: var(--bg-card); }
        .nav-item.active {
            color: #fff; background: linear-gradient(135deg, rgba(99, 102, 241, 0.25), rgba(139, 92, 246, 0.15));
            border: 1px solid rgba(99, 102, 241, 0.35); font-weight: 600;
        }
        .sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); }
        .user-card {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: var(--radius-sm); background: var(--bg-card);
        }
        .user-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 13px; font-weight: 700;
        }
        .user-info { flex: 1; min-width: 0; }
        .user-info .name { font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-info .role { font-size: 10px; color: var(--text-2); }
        .btn-logout { background: none; border: none; cursor: pointer; color: var(--text-2); font-size: 14px; transition: color 0.2s; }
        .btn-logout:hover { color: var(--red); }

        /* ── Main Layout ── */
        .main {
            margin-left: var(--sidebar-w); min-height: 100vh;
            display: flex; flex-direction: column; position: relative; z-index: 1;
        }
        .header {
            position: sticky; top: 0; z-index: 50; height: var(--header-h);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 32px; background: rgba(8, 9, 16, 0.85);
            backdrop-filter: blur(16px); border-bottom: 1px solid var(--border);
        }
        .header-left h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; }
        .header-left p { font-size: 12px; color: var(--text-2); margin-top: 1px; }
        .header-right { display: flex; align-items: center; gap: 12px; }

        .badge-live {
            display: flex; align-items: center; gap: 6px;
            background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.25);
            border-radius: 20px; padding: 5px 12px; font-size: 11px; font-weight: 600; color: #86efac;
        }
        .badge-live .dot {
            width: 6px; height: 6px; background: var(--green); border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        .btn-refresh {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-sm); color: var(--text-2);
            padding: 7px 11px; cursor: pointer; font-size: 13px; transition: all 0.15s;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .btn-refresh:hover { background: var(--bg-card-h); color: var(--text-1); }

        .content { padding: 28px 32px; flex: 1; }

        /* ── Alerts ── */
        .alert-box {
            padding: 14px 18px; border-radius: var(--radius-sm);
            margin-bottom: 22px; font-size: 13px; display: flex; align-items: center; gap: 10px;
        }
        .alert-box.success {
            background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.3); color: #86efac;
        }
        .alert-box.danger {
            background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5;
        }

        /* ── KPI Grid ── */
        .kpi-grid {
            display: grid; grid-template-columns: repeat(5, 1fr); gap: 14px; margin-bottom: 24px;
        }
        .kpi-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 18px 20px;
            transition: all 0.2s; position: relative; overflow: hidden;
        }
        .kpi-card:hover { border-color: var(--border-light); transform: translateY(-2px); }
        .kpi-card .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-2); }
        .kpi-card .kpi-val { font-size: 24px; font-weight: 800; margin-top: 6px; letter-spacing: -0.5px; }
        .kpi-card.amber .kpi-val { color: var(--amber); }
        .kpi-card.green .kpi-val { color: var(--green); }
        .kpi-card.red .kpi-val { color: var(--red); }
        .kpi-card.cyan .kpi-val { color: var(--cyan); }

        /* ── Filters & Search Toolbar ── */
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            gap: 16px; margin-bottom: 18px; flex-wrap: wrap;
        }
        .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .tab-btn {
            background: var(--bg-card); border: 1px solid var(--border);
            color: var(--text-2); padding: 7px 14px; border-radius: 8px;
            font-size: 12px; font-weight: 600; text-decoration: none;
            transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px;
        }
        .tab-btn:hover { background: var(--bg-card-h); color: var(--text-1); }
        .tab-btn.active {
            background: var(--accent); color: #fff; border-color: var(--accent);
            box-shadow: 0 2px 10px var(--accent-glow);
        }
        .tab-count {
            background: rgba(255, 255, 255, 0.15); border-radius: 10px;
            padding: 1px 6px; font-size: 10px;
        }

        .search-box {
            position: relative; max-width: 300px; width: 100%;
        }
        .search-box input {
            width: 100%; background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 8px; padding: 8px 12px 8px 34px; color: var(--text-1);
            font-size: 12.5px; outline: none; transition: border-color 0.15s;
        }
        .search-box input:focus { border-color: var(--accent); }
        .search-box .search-icon {
            position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
            color: var(--text-3); font-size: 15px; pointer-events: none;
        }

        /* ── Table Card ── */
        .table-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
        }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th {
            background: rgba(15, 17, 23, 0.95); padding: 12px 16px;
            text-align: left; font-size: 10.5px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2);
            border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        td {
            padding: 14px 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text-1); vertical-align: middle;
        }
        tbody tr:hover td { background: rgba(255, 255, 255, 0.025); }

        /* ── Badges ── */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 9px; border-radius: 20px; font-size: 10.5px;
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;
        }
        .badge-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-green { background: rgba(34, 197, 94, 0.15); color: #86efac; border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge-red { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .badge-cyan { background: rgba(6, 182, 212, 0.15); color: #67e8f9; border: 1px solid rgba(6, 182, 212, 0.3); }

        /* ── Action Buttons ── */
        .btn-action-group { display: flex; align-items: center; gap: 6px; }
        .btn-approve {
            background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.35);
            color: #86efac; border-radius: 6px; padding: 6px 11px;
            font-size: 11.5px; font-weight: 700; cursor: pointer; transition: all 0.15s;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-approve:hover { background: rgba(34, 197, 94, 0.3); transform: translateY(-1px); }

        .btn-reject {
            background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5; border-radius: 6px; padding: 6px 11px;
            font-size: 11.5px; font-weight: 700; cursor: pointer; transition: all 0.15s;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .btn-reject:hover { background: rgba(239, 68, 68, 0.25); transform: translateY(-1px); }

        .btn-wa-direct {
            background: rgba(37, 211, 102, 0.12); border: 1px solid rgba(37, 211, 102, 0.3);
            color: #25d366; border-radius: 6px; padding: 6px 9px;
            font-size: 14px; text-decoration: none; display: inline-flex; align-items: center;
            transition: all 0.15s;
        }
        .btn-wa-direct:hover { background: rgba(37, 211, 102, 0.25); }

        .copy-pill {
            background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border);
            border-radius: 4px; padding: 2px 6px; font-family: monospace; font-size: 12px;
            cursor: pointer; display: inline-flex; align-items: center; gap: 4px;
        }
        .copy-pill:hover { border-color: var(--accent); color: var(--cyan); }

        /* ── Modals ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(6px); display: none; align-items: center;
            justify-content: center; z-index: 1000; padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal-card {
            background: #10121a; border: 1px solid var(--border-light);
            border-radius: 14px; max-width: 500px; width: 100%; padding: 26px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8);
        }
        .modal-title { font-size: 16px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .modal-desc { font-size: 13px; color: var(--text-2); margin-bottom: 18px; line-height: 1.5; }
        .modal-form label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-2); margin-bottom: 6px; }
        .modal-form select, .modal-form textarea, .modal-form input {
            width: 100%; background: #080910; border: 1px solid var(--border);
            border-radius: 8px; color: var(--text-1); padding: 10px 12px;
            font-size: 13px; font-family: inherit; margin-bottom: 14px; outline: none;
        }
        .modal-form select:focus, .modal-form textarea:focus, .modal-form input:focus { border-color: var(--accent); }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }
        .btn-modal-cancel {
            background: var(--bg-card); border: 1px solid var(--border);
            color: var(--text-2); padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn-modal-submit-approve {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none; color: #fff; padding: 9px 18px; border-radius: 8px;
            font-size: 13px; font-weight: 700; cursor: pointer;
        }
        .btn-modal-submit-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none; color: #fff; padding: 9px 18px; border-radius: 8px;
            font-size: 13px; font-weight: 700; cursor: pointer;
        }

        @media (max-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            :root { --sidebar-w: 0px; }
            .sidebar { display: none; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- ── Sidebar ── -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">⚡</div>
            <div class="sidebar-logo-text">
                <span class="brand">iSeller</span>
                <span class="tag">Admin</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section">Menú</span>
            <a class="nav-item" href="dashboard.php">
                <i class="bx bx-home-alt"></i> Dashboard Negocios
            </a>
            <a class="nav-item active" href="pagos.php" style="display: flex; align-items: center; justify-content: space-between;">
                <span><i class="bx bx-credit-card"></i> Pagos Membresía</span>
                <?php if ($stats['pendientes'] > 0): ?>
                    <span class="badge badge-amber" style="font-size: 10px; padding: 2px 7px;"><?= $stats['pendientes'] ?></span>
                <?php endif; ?>
            </a>
            <a class="nav-item" href="reputacion.php">
                <i class="bx bx-star"></i> Reputación
            </a>
            <a class="nav-item" href="inactivos.php">
                <i class="bx bx-user-x"></i> Cuentas inactivas
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                <div class="user-info">
                    <div class="name"><?= $admin_name ?></div>
                    <div class="role">Super Admin</div>
                </div>
                <a href="logout.php" class="btn-logout" title="Cerrar sesión">⏻</a>
            </div>
        </div>
    </aside>

    <!-- ── Main ── -->
    <div class="main">
        <header class="header">
            <div class="header-left">
                <h1>Gestión de Pagos de Membresía</h1>
                <p>Verificación, aprobación y solicitud de comprobantes válidos · <?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="header-right">
                <div class="badge-live">
                    <span class="dot"></span> En línea
                </div>
                <button type="button" class="btn-refresh" style="background:rgba(45,212,160,0.12);border-color:rgba(45,212,160,0.35);color:#2dd4a0;font-weight:600;cursor:pointer;" onclick="abrirModalPrueba()">
                    <i class="bx bx-gift"></i> Activar Prueba
                </button>
                <a href="pagos.php" class="btn-refresh" title="Recargar"><i class="bx bx-refresh"></i> Actualizar</a>
                <a href="logout.php" class="btn-refresh" title="Cerrar sesión">⏻</a>
            </div>
        </header>

        <div class="content">

            <?php if (!empty($msg_success)): ?>
                <div class="alert-box success">
                    <i class="bx bx-check-circle" style="font-size: 18px;"></i>
                    <div><?= $msg_success ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($msg_error)): ?>
                <div class="alert-box danger">
                    <i class="bx bx-error-circle" style="font-size: 18px;"></i>
                    <div><?= htmlspecialchars($msg_error) ?></div>
                </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Total Reportados</div>
                    <div class="kpi-val"><?= number_format($stats['total']) ?></div>
                </div>
                <div class="kpi-card amber">
                    <div class="kpi-label">Pendientes de Revisión</div>
                    <div class="kpi-val"><?= number_format($stats['pendientes']) ?></div>
                </div>
                <div class="kpi-card green">
                    <div class="kpi-label">Pagos Aprobados</div>
                    <div class="kpi-val"><?= number_format($stats['aprobados']) ?></div>
                </div>
                <div class="kpi-card red">
                    <div class="kpi-label">Pagos Rechazados</div>
                    <div class="kpi-val"><?= number_format($stats['rechazados']) ?></div>
                </div>
                <div class="kpi-card cyan">
                    <div class="kpi-label">Recaudado Aprobado</div>
                    <div class="kpi-val">$<?= number_format($stats['monto_usd'], 2) ?></div>
                </div>
            </div>

            <!-- Toolbar: Filtros y Buscador -->
            <div class="toolbar">
                <div class="filter-tabs">
                    <a href="pagos.php?filtro=pendientes" class="tab-btn <?= ($filtro === 'pendientes') ? 'active' : '' ?>">
                        <i class="bx bx-time"></i> Pendientes <span class="tab-count"><?= $stats['pendientes'] ?></span>
                    </a>
                    <a href="pagos.php?filtro=todos" class="tab-btn <?= ($filtro === 'todos') ? 'active' : '' ?>">
                        <i class="bx bx-list-ul"></i> Todos <span class="tab-count"><?= $stats['total'] ?></span>
                    </a>
                    <a href="pagos.php?filtro=aprobados" class="tab-btn <?= ($filtro === 'aprobados') ? 'active' : '' ?>">
                        <i class="bx bx-check-circle"></i> Aprobados <span class="tab-count"><?= $stats['aprobados'] ?></span>
                    </a>
                    <a href="pagos.php?filtro=rechazados" class="tab-btn <?= ($filtro === 'rechazados') ? 'active' : '' ?>">
                        <i class="bx bx-x-circle"></i> Rechazados <span class="tab-count"><?= $stats['rechazados'] ?></span>
                    </a>
                </div>

                <div class="search-box">
                    <i class="bx bx-search search-icon"></i>
                    <input type="text" id="filtroTabla" placeholder="Buscar negocio, referencia o usuario..." onkeyup="filtrarPagos()">
                </div>
            </div>

            <!-- Tabla de Pagos -->
            <div class="table-card">
                <div class="table-responsive">
                    <table id="tablaPagos">
                        <thead>
                            <tr>
                                <th>ID / Fecha</th>
                                <th>Negocio</th>
                                <th>Usuario</th>
                                <th>Plan / Monto</th>
                                <th>Nro. Operación</th>
                                <th>Fecha Pago</th>
                                <th>Estado</th>
                                <th>Nota Admin</th>
                                <th style="text-align: right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pagos)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-2);">
                                        <i class="bx bx-inbox" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                                        No hay pagos reportados en esta sección.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pagos as $p): ?>
                                    <tr class="pago-row" data-search="<?= strtolower(htmlspecialchars(($p['negocio_nombre'] ?? '') . ' ' . $p['numero_operacion'] . ' ' . ($p['usuario_nombre'] ?? '') . ' ' . ($p['usuario_email'] ?? ''))) ?>">
                                        <td>
                                            <span style="font-weight: 700; color: var(--cyan);">#<?= $p['id'] ?></span>
                                            <div style="font-size: 11px; color: var(--text-3); margin-top: 2px;">
                                                <?= date('d/m/Y H:i', strtotime($p['fecha_reporte'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-1);">
                                                <?= htmlspecialchars($p['negocio_nombre'] ?? 'Negocio #' . $p['bss_id']) ?>
                                            </div>
                                            <div style="font-size: 11px; color: var(--text-2); margin-top: 2px; display: flex; align-items: center; gap: 6px;">
                                                <span>ID: <?= $p['bss_id'] ?></span>
                                                <?php if (!empty($p['negocio_whatsapp'])): ?>
                                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $p['negocio_whatsapp']) ?>?text=<?= urlencode('Hola, me comunico desde la administración de iSeller respecto a su pago de membresía.') ?>" target="_blank" class="btn-wa-direct" title="Contactar por WhatsApp">
                                                        <i class="bx bxl-whatsapp"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($p['usuario_nombre'] ?? 'Usuario #' . $p['usuario_id']) ?></div>
                                            <div style="font-size: 11px; color: var(--text-3);"><?= htmlspecialchars($p['usuario_email'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <?php if ($p['plan'] === 'anual'): ?>
                                                <span class="badge badge-green">Plan Anual</span>
                                            <?php else: ?>
                                                <span class="badge badge-cyan">Plan Mensual</span>
                                            <?php endif; ?>
                                            <div style="font-weight: 700; color: var(--text-1); margin-top: 4px;">
                                                $<?= number_format((float)$p['monto_usd'], 2) ?> USD
                                            </div>
                                        </td>
                                        <td>
                                            <span class="copy-pill" onclick="copiarTexto('<?= htmlspecialchars($p['numero_operacion']) ?>', this)" title="Click para copiar">
                                                <i class="bx bx-copy"></i> <?= htmlspecialchars($p['numero_operacion']) ?>
                                            </span>
                                            <?php if (!empty($p['banco_origen'])): ?>
                                                <div style="font-size: 11px; color: var(--text-2); margin-top: 3px;">
                                                    <?= htmlspecialchars($p['banco_origen']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-size: 12px;"><?= htmlspecialchars($p['hora_operacion']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($p['estado'] === 'pendiente'): ?>
                                                <span class="badge badge-amber"><i class="bx bx-time"></i> Pendiente</span>
                                            <?php elseif ($p['estado'] === 'aprobado'): ?>
                                                <span class="badge badge-green"><i class="bx bx-check"></i> Aprobado</span>
                                            <?php else: ?>
                                                <span class="badge badge-red"><i class="bx bx-x"></i> Rechazado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size: 11.5px; color: var(--text-2); max-width: 200px;">
                                            <?= htmlspecialchars($p['nota_admin'] ?? '—') ?>
                                            <?php if (!empty($p['fecha_procesado'])): ?>
                                                <div style="font-size: 10px; color: var(--text-3); margin-top: 2px;">
                                                    Proc: <?= date('d/m/Y H:i', strtotime($p['fecha_procesado'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php if ($p['estado'] === 'pendiente'): ?>
                                                <div class="btn-action-group" style="justify-content: flex-end;">
                                                    <button class="btn-approve" onclick="abrirModalAprobar(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['negocio_nombre'] ?? '')) ?>', '<?= htmlspecialchars($p['numero_operacion']) ?>', '<?= $p['plan'] ?>', '<?= $p['monto_usd'] ?>')">
                                                        <i class="bx bx-check"></i> Aprobar
                                                    </button>
                                                    <button class="btn-reject" onclick="abrirModalRechazar(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['negocio_nombre'] ?? '')) ?>', '<?= htmlspecialchars($p['numero_operacion']) ?>')">
                                                        <i class="bx bx-error-circle"></i> Solicitar Válido
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-size: 11px; color: var(--text-3);">Procesado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- ── Modal Aprobar Pago ── -->
    <div class="modal-overlay" id="modalAprobar">
        <div class="modal-card">
            <h3 class="modal-title" style="color: var(--green);">
                <i class="bx bx-check-shield"></i> Confirmar Aprobación de Pago
            </h3>
            <p class="modal-desc">
                Al aprobar este pago, la membresía del negocio se activará inmediatamente y se extenderá la fecha de vencimiento según el plan seleccionado.
            </p>

            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px;">
                <div>Negocio: <strong id="mApprNegocio" style="color: #fff;"></strong></div>
                <div style="margin-top: 4px;">Referencia: <strong id="mApprRef" style="color: var(--cyan); font-family: monospace;"></strong></div>
                <div style="margin-top: 4px;">Plan / Monto: <strong id="mApprPlan" style="color: var(--green);"></strong></div>
            </div>

            <form method="POST" action="pagos.php" class="modal-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="accion" value="aprobar">
                <input type="hidden" name="pago_id" id="mApprPagoId" value="">

                <label for="nota_admin">Nota administrativa (opcional)</label>
                <input type="text" name="nota_admin" id="nota_admin" placeholder="Ej: Verificado en Banesco Pago Móvil">

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="cerrarModal('modalAprobar')">Cancelar</button>
                    <button type="submit" class="btn-modal-submit-approve">
                        <i class="bx bx-check"></i> Sí, Aprobar y Activar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Modal Solicitar Comprobante Válido (Rechazar) ── -->
    <div class="modal-overlay" id="modalRechazar">
        <div class="modal-card">
            <h3 class="modal-title" style="color: var(--red);">
                <i class="bx bx-error-circle"></i> Solicitar Comprobante Válido
            </h3>
            <p class="modal-desc">
                Indica el motivo por el cual el comprobante no es válido. El negocio verá este motivo al entrar al sistema y se le solicitará cargar un nuevo comprobante.
            </p>

            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px;">
                <div>Negocio: <strong id="mRejNegocio" style="color: #fff;"></strong></div>
                <div style="margin-top: 4px;">Referencia reportada: <strong id="mRejRef" style="color: var(--cyan); font-family: monospace;"></strong></div>
            </div>

            <form method="POST" action="pagos.php" class="modal-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="accion" value="rechazar">
                <input type="hidden" name="pago_id" id="mRejPagoId" value="">

                <label for="motivo_select">Seleccionar motivo frecuente:</label>
                <select id="motivo_select" onchange="actualizarMotivo(this.value)">
                    <option value="La referencia indicada no aparece en los movimientos bancarios.">Referencia no encontrada en la cuenta bancaria</option>
                    <option value="El monto transferido es incorrecto o no coincide con el plan seleccionado.">Monto transferido no coincide con el plan</option>
                    <option value="El comprobante es ilegible o los datos están incompletos.">Comprobante ilegible o incompleto</option>
                    <option value="La transferencia fue realizada desde otro banco y aún no se hace efectiva.">Fondos no acreditados / liquidación diferida</option>
                    <option value="custom">Otro motivo personalizado...</option>
                </select>

                <label for="motivo_textarea">Mensaje que verá el cliente:</label>
                <textarea name="motivo" id="motivo_textarea" rows="3" required>La referencia indicada no aparece en los movimientos bancarios.</textarea>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="cerrarModal('modalRechazar')">Cancelar</button>
                    <button type="submit" class="btn-modal-submit-reject">
                        <i class="bx bx-send"></i> Solicitar Comprobante Válido
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Modal Activar Prueba Gratuita ── -->
    <div class="modal-overlay" id="modalPrueba">
        <div class="modal-card">
            <h3 class="modal-title" style="color: #2dd4a0;">
                <i class="bx bx-gift"></i> Activar Membresía de Prueba
            </h3>
            <p class="modal-desc">
                Asigna una membresía de prueba. La <strong>fecha de inicio</strong> se establecerá automáticamente en la <strong>fecha y hora actual</strong>, y la fecha de vencimiento se calculará a partir de hoy.
            </p>

            <form method="POST" action="pagos.php" class="modal-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="accion" value="activar_prueba">

                <label for="prueba_bss_id">Seleccionar Negocio:</label>
                <select name="bss_id" id="prueba_bss_id" required>
                    <option value="">-- Selecciona un negocio --</option>
                    <?php foreach ($negociosParaPrueba as $np): ?>
                        <option value="<?= $np['id'] ?>">
                            #<?= $np['id'] ?> — <?= htmlspecialchars($np['nombre']) ?> (<?= htmlspecialchars($np['membresia_tipo'] ?: 'sin tipo') ?> / <?= htmlspecialchars($np['membresia_estado'] ?: 'sin estado') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="prueba_dias">Días de prueba:</label>
                <input type="number" name="dias" id="prueba_dias" value="30" min="1" max="365" required>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="cerrarModal('modalPrueba')">Cancelar</button>
                    <button type="submit" class="btn-modal-submit-approve" style="background:#2dd4a0;color:#0f172a;border-color:#2dd4a0;font-weight:700;">
                        <i class="bx bx-check"></i> Activar Prueba Ahora
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalPrueba(preselectId) {
            if (preselectId) {
                document.getElementById('prueba_bss_id').value = preselectId;
            }
            document.getElementById('modalPrueba').classList.add('open');
        }

        // Filtrar tabla en tiempo real
        function filtrarPagos() {
            const input = document.getElementById('filtroTabla').value.toLowerCase();
            const filas = document.querySelectorAll('.pago-row');
            filas.forEach(f => {
                const search = f.getAttribute('data-search') || '';
                if (search.includes(input)) {
                    f.style.display = '';
                } else {
                    f.style.display = 'none';
                }
            });
        }

        // Copiar texto al portapapeles
        function copiarTexto(texto, el) {
            navigator.clipboard.writeText(texto).then(() => {
                const original = el.innerHTML;
                el.innerHTML = '<i class="bx bx-check"></i> Copiado';
                el.style.color = '#86efac';
                setTimeout(() => {
                    el.innerHTML = original;
                    el.style.color = '';
                }, 1500);
            });
        }

        // Modales
        function abrirModalAprobar(id, negocio, ref, plan, monto) {
            document.getElementById('mApprPagoId').value = id;
            document.getElementById('mApprNegocio').textContent = negocio;
            document.getElementById('mApprRef').textContent = ref;
            document.getElementById('mApprPlan').textContent = plan.toUpperCase() + ' ($' + parseFloat(monto).toFixed(2) + ' USD)';
            document.getElementById('modalAprobar').classList.add('open');
        }

        function abrirModalRechazar(id, negocio, ref) {
            document.getElementById('mRejPagoId').value = id;
            document.getElementById('mRejNegocio').textContent = negocio;
            document.getElementById('mRejRef').textContent = ref;
            document.getElementById('motivo_select').value = "La referencia indicada no aparece en los movimientos bancarios.";
            document.getElementById('motivo_textarea').value = "La referencia indicada no aparece en los movimientos bancarios.";
            document.getElementById('modalRechazar').classList.add('open');
        }

        function actualizarMotivo(val) {
            const textarea = document.getElementById('motivo_textarea');
            if (val === 'custom') {
                textarea.value = '';
                textarea.placeholder = 'Escribe aquí la razón detallada para el cliente...';
                textarea.focus();
            } else {
                textarea.value = val;
            }
        }

        function cerrarModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        // Cerrar modales con Escape o click fuera
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
            }
        });
        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.addEventListener('click', (e) => {
                if (e.target === m) m.classList.remove('open');
            });
        });
    </script>
</body>
</html>
