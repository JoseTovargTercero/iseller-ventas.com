<?php
require_once('includes/requires.php');
require_once('../../configurar/_factura_compra.php');

$nivel = (int) $_SESSION['nivel'];
if ($nivel !== 1 && $nivel !== 2) {
    header('Location: ../../index.php');
    exit;
}

$bss_id = (int) $_SESSION['bss_id'];

if (!fc_asegurar_esquema($conexion)) {
    http_response_code(500);
    echo 'No se pudo preparar la tabla de facturas. Ejecute la migración 024_compras_facturas_vencimiento.sql.';
    exit;
}

// El módulo solo existe mientras la opción esté activa.
$stmt = $conexion->prepare("SELECT factura FROM configuracion WHERE bss_id = ? ORDER BY id DESC LIMIT 1");
if (!$stmt) {
    header('Location: nuevaCompra.php');
    exit;
}
$stmt->bind_param("i", $bss_id);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$config || (int) $config['factura'] !== 1) {
    header('Location: nuevaCompra.php');
    exit;
}

$topnav = topnav();
$sucursalSesion = (int) ($_SESSION['sucursal'] ?? 0);

$stmt = $conexion->prepare(
    $nivel === 2
        ? "SELECT id, nombre FROM sucursales WHERE bss_id = ? AND id = ? ORDER BY principal DESC"
        : "SELECT id, nombre FROM sucursales WHERE bss_id = ? ORDER BY principal DESC"
);
if ($nivel === 2) {
    $stmt->bind_param("ii", $bss_id, $sucursalSesion);
} else {
    $stmt->bind_param("i", $bss_id);
}
$stmt->execute();
$sucursales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$sqlFacturas = "SELECT f.*, COALESCE(p.abonado, 0) AS abonado
    FROM compras_facturas f
    LEFT JOIN (
        SELECT factura_id, SUM(monto) AS abonado
        FROM compras_factura_pagos
        WHERE bss_id = ?
        GROUP BY factura_id
    ) p ON p.factura_id = f.id
    WHERE f.bss_id = ?";

$ordenFacturas = " ORDER BY (f.estado = 'pendiente') DESC, (f.fecha_vencimiento IS NULL) ASC, f.fecha_vencimiento ASC, f.id DESC LIMIT 100";
if ($nivel === 2) {
    $sqlFacturas .= " AND f.id_sucursal = ?";
    $stmt = $conexion->prepare($sqlFacturas . $ordenFacturas);
    $stmt->bind_param("iii", $bss_id, $bss_id, $sucursalSesion);
} else {
    $stmt = $conexion->prepare($sqlFacturas . $ordenFacturas);
    $stmt->bind_param("ii", $bss_id, $bss_id);
}
$stmt->execute();
$facturas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$nombresSucursal = [];
foreach ($sucursales as $sucursal) {
    $nombresSucursal[(int) $sucursal['id']] = $sucursal['nombre'];
}

// Los totales se calculan sobre todas las facturas, no solo sobre las últimas 100 del listado.
$sqlStats = "SELECT
        COUNT(*) AS total_facturas,
        COALESCE(SUM(f.total), 0) AS total_comprado,
        COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 THEN f.total - COALESCE(p.abonado, 0) ELSE 0 END), 0) AS pendiente_saldo,
        COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 THEN 1 ELSE 0 END), 0) AS pendientes,
        COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 AND f.fecha_vencimiento IS NOT NULL AND f.fecha_vencimiento < CURDATE() THEN f.total - COALESCE(p.abonado, 0) ELSE 0 END), 0) AS vencida_saldo,
        COALESCE(SUM(CASE WHEN f.estado = 'pendiente' AND (f.total - COALESCE(p.abonado, 0)) > 0.005 AND f.fecha_vencimiento IS NOT NULL AND f.fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) AS vencidas,
        COALESCE(SUM(CASE WHEN f.estado = 'pagada' THEN 1 ELSE 0 END), 0) AS pagadas,
        COALESCE(SUM(CASE WHEN f.estado = 'pagada' THEN f.total ELSE 0 END), 0) AS pagada_total
    FROM compras_facturas f
    LEFT JOIN (
        SELECT factura_id, SUM(monto) AS abonado
        FROM compras_factura_pagos
        WHERE bss_id = ?
        GROUP BY factura_id
    ) p ON p.factura_id = f.id
    WHERE f.bss_id = ?";

if ($nivel === 2) {
    $stmt = $conexion->prepare($sqlStats . " AND f.id_sucursal = ?");
    $stmt->bind_param("iii", $bss_id, $bss_id, $sucursalSesion);
} else {
    $stmt = $conexion->prepare($sqlStats);
    $stmt->bind_param("ii", $bss_id, $bss_id);
}
$stmt->execute();
$filaStats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stats = [
    'pendientes' => (int) ($filaStats['pendientes'] ?? 0),
    'pendiente_saldo' => (float) ($filaStats['pendiente_saldo'] ?? 0),
    'vencidas' => (int) ($filaStats['vencidas'] ?? 0),
    'vencida_saldo' => (float) ($filaStats['vencida_saldo'] ?? 0),
    'pagadas' => (int) ($filaStats['pagadas'] ?? 0),
    'pagada_total' => (float) ($filaStats['pagada_total'] ?? 0),
    'total' => (float) ($filaStats['total_comprado'] ?? 0),
    'total_facturas' => (int) ($filaStats['total_facturas'] ?? 0),
];

$hoy = date('Y-m-d');
foreach ($facturas as &$factura) {
    $total = (float) $factura['total'];
    $abonado = (float) $factura['abonado'];
    $saldo = round($total - $abonado, 2);

    $factura['saldo_calc'] = max(0, $saldo);
    $factura['vencida_calc'] = false;
    $factura['dias_vencimiento'] = null;
    $factura['vence_texto'] = '';

    if ($factura['estado'] === 'pendiente' && $saldo > 0.005) {
        if (!empty($factura['fecha_vencimiento'])) {
            $dias = (int) floor((strtotime($factura['fecha_vencimiento']) - strtotime($hoy)) / 86400);
            $factura['dias_vencimiento'] = $dias;
            $factura['vencida_calc'] = $dias < 0;

            if ($dias < 0) {
                $factura['vence_texto'] = 'Vencida hace ' . abs($dias) . ' día' . (abs($dias) === 1 ? '' : 's');
            } elseif ($dias === 0) {
                $factura['vence_texto'] = 'Vence hoy';
            } else {
                $factura['vence_texto'] = 'Vence en ' . $dias . ' día' . ($dias === 1 ? '' : 's');
            }
        } else {
            $factura['vence_texto'] = 'Sin vencimiento';
        }
    }
}
unset($factura);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Compras con factura | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .fc-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .fc-hero h2 {
            margin: 0 0 4px;
            font-size: 21px;
            font-weight: 800;
            color: var(--dash-text);
            letter-spacing: -.3px;
        }

        .fc-hero p {
            margin: 0;
            font-size: 13px;
            color: var(--dash-text-muted);
        }

        .fc-ghost-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 14px;
            border-radius: 10px;
            border: 1px solid var(--dash-border);
            background: rgba(255, 255, 255, .04);
            color: var(--dash-text-muted) !important;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none !important;
            transition: all .15s ease;
        }

        .fc-ghost-btn:hover {
            border-color: var(--dash-mint);
            color: var(--dash-mint) !important;
            background: rgba(45, 212, 160, .08);
        }

        .fc-hero-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .fc-primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 16px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .3px;
            box-shadow: 0 4px 14px rgba(45, 212, 160, .22);
            transition: all .15s ease;
        }

        .fc-primary-btn:hover {
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 7px 20px rgba(45, 212, 160, .35);
        }

        /* Modal de nueva factura */
        #modalNuevaFactura .modal-content {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 16px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, .5);
        }

        #modalNuevaFactura .modal-header.fc-modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 15px 20px;
            border-bottom: 1px solid var(--dash-border);
        }

        #modalNuevaFactura .modal-header .close {
            color: var(--dash-text);
            opacity: .7;
            text-shadow: none;
            font-size: 26px;
            line-height: 1;
            margin: 0;
            padding: 0;
        }

        #modalNuevaFactura .modal-header .close:hover {
            color: var(--dash-mint);
            opacity: 1;
        }

        #modalNuevaFactura .modal-body {
            padding: 18px 20px;
        }

        #modalNuevaFactura .modal-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 12px 20px;
            border-top: 1px solid var(--dash-border);
        }

        #modalNuevaFactura .modal-footer .fc-ghost-btn {
            padding: 8px 16px;
        }

        #modalNuevaFactura .modal-footer .fc-save-btn {
            width: auto;
            padding: 9px 20px;
        }

        .fc-wizard-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fc-wizard-nav .fc-primary-btn {
            padding: 9px 18px;
        }

        /* Wizard de la factura */
        .fc-wizard {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 14px;
            margin-bottom: 18px;
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            background: var(--dash-card);
            box-shadow: 0 6px 14px rgba(0, 0, 0, .16);
        }

        .fc-wizard-step {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            cursor: default;
        }

        .fc-wizard-step[data-step]:not(.active):not(.done) {
            cursor: default;
        }

        .fc-wizard-step.done {
            cursor: pointer;
        }

        .fc-wizard-step > span {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 1px solid var(--dash-border);
            background: var(--dash-bg);
            color: var(--dash-text-muted);
            font-size: 10px;
            font-weight: 800;
        }

        .fc-wizard-step strong {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: var(--dash-text-muted);
            line-height: 1.2;
        }

        .fc-wizard-step small {
            display: block;
            margin-top: 1px;
            font-size: 9.5px;
            color: var(--dash-text-muted);
            line-height: 1.2;
        }

        .fc-wizard-step.active strong {
            color: var(--dash-text);
        }

        .fc-wizard-step.active > span {
            background: var(--dash-mint);
            border-color: var(--dash-mint);
            color: #fff;
        }

        .fc-wizard-step.done > span {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
        }

        .fc-wizard-step.done strong {
            color: var(--dash-mint);
        }

        .fc-wizard-line {
            flex: 1 1 auto;
            height: 1px;
            min-width: 12px;
            background: var(--dash-border);
        }

        .fc-step {
            display: none;
        }

        .fc-step.active {
            display: block;
        }

        @media (max-width: 640px) {
            #modalNuevaFactura .modal-footer {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .fc-wizard-nav {
                justify-content: stretch;
            }

            .fc-wizard-nav .fc-primary-btn,
            .fc-wizard-nav .fc-ghost-btn,
            #modalNuevaFactura .modal-footer .fc-save-btn {
                flex: 1 1 auto;
                justify-content: center;
            }

            .fc-wizard {
                gap: 5px;
                padding: 10px;
            }

            .fc-wizard-step small {
                display: none;
            }

            .fc-wizard-step strong {
                font-size: 10px;
            }
        }

        @media (max-width: 640px) {
            #modalNuevaFactura .modal-body {
                padding: 14px;
            }

            #modalNuevaFactura .modal-header.fc-modal-head {
                padding: 13px 14px;
            }
        }

        .fc-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .fc-stat {
            position: relative;
            overflow: hidden;
            padding: 15px 17px;
            border-radius: 14px;
            border: 1px solid var(--dash-border);
            background: var(--dash-card);
        }

        .fc-stat::before {
            content: '';
            position: absolute;
            left: 0;
            top: 12px;
            bottom: 12px;
            width: 3px;
            border-radius: 0 4px 4px 0;
            background: var(--dash-text-muted);
        }

        .fc-stat.pending::before { background: #f5b45b; }
        .fc-stat.overdue::before { background: #ef5a6f; }
        .fc-stat.paid::before { background: var(--dash-mint); }
        .fc-stat.total::before { background: #5b9cf5; }

        .fc-stat-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: var(--dash-text-muted);
        }

        .fc-stat.pending .fc-stat-label i { color: #f5b45b; }
        .fc-stat.overdue .fc-stat-label i { color: #ef5a6f; }
        .fc-stat.paid .fc-stat-label i { color: var(--dash-mint); }
        .fc-stat.total .fc-stat-label i { color: #5b9cf5; }

        .fc-stat-value {
            margin: 7px 0 2px;
            font-size: 20px;
            font-weight: 800;
            color: var(--dash-text);
            font-variant-numeric: tabular-nums;
        }

        .fc-stat.pending .fc-stat-value { color: #f5b45b; }
        .fc-stat.overdue .fc-stat-value { color: #ef5a6f; }
        .fc-stat.paid .fc-stat-value { color: var(--dash-mint); }
        .fc-stat.total .fc-stat-value { color: #5b9cf5; }

        .fc-stat-meta {
            font-size: 11px;
            color: var(--dash-text-muted);
        }

        .fc-card {
            border: 1px solid var(--dash-border);
            border-radius: 16px;
            background: var(--dash-card);
            overflow: hidden;
            margin-bottom: 18px;
        }

        .fc-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            padding: 15px 18px;
            border-bottom: 1px solid var(--dash-border);
        }

        .fc-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .fc-title-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(45, 212, 160, .12);
            color: var(--dash-mint);
            font-size: 17px;
            flex-shrink: 0;
        }

        .fc-card-title h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            color: var(--dash-text);
        }

        .fc-card-title p {
            margin: 2px 0 0;
            font-size: 11.5px;
            color: var(--dash-text-muted);
        }

        .fc-card-body {
            padding: 18px;
        }

        .fc-card-body.p-0 {
            padding: 0;
        }

        .fc-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 12px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: var(--dash-text-muted);
        }

        .fc-section-title span {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(45, 212, 160, .14);
            color: var(--dash-mint);
            font-size: 10px;
            letter-spacing: 0;
        }

        .fc-form .fc-field {
            margin-bottom: 12px;
        }

        .fc-form label {
            display: block;
            margin: 0 0 5px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .35px;
            text-transform: uppercase;
            color: var(--dash-text-muted);
        }

        .fc-form .form-control {
            height: 38px;
            font-size: 13px !important;
            border-radius: 9px !important;
            background: var(--dash-bg) !important;
            border: 1px solid var(--dash-border) !important;
            color: var(--dash-text) !important;
            box-shadow: none !important;
        }

        .fc-form .form-control:focus {
            border-color: var(--dash-mint) !important;
            box-shadow: 0 0 0 3px rgba(45, 212, 160, .12) !important;
        }

        /* El selector nativo de fecha debe verse y abrirse también en tema oscuro. */
        .fc-form input[type="date"] {
            color-scheme: dark;
            cursor: pointer;
        }

        .fc-form input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: .8;
        }

        .fc-form select.form-control {
            padding-right: 30px;
        }

        .fc-help {
            display: block;
            margin-top: 4px;
            font-size: 10.5px;
            color: var(--dash-text-muted);
            line-height: 1.35;
        }

        .fc-adder {
            padding: 13px;
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            background: rgba(255, 255, 255, .02);
            margin-bottom: 12px;
        }

        .fc-add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            padding: 9px 12px;
            border-radius: 9px;
            border: 1px dashed rgba(45, 212, 160, .45);
            background: rgba(45, 212, 160, .07);
            color: var(--dash-mint);
            font-size: 12px;
            font-weight: 700;
            transition: all .15s ease;
        }

        .fc-add-btn:hover {
            background: rgba(45, 212, 160, .14);
            border-color: var(--dash-mint);
        }

        .fc-lines-wrap {
            max-height: 260px;
            overflow: auto;
            border: 1px solid var(--dash-border);
            border-radius: 11px;
            margin-bottom: 18px;
        }

        .fc-table {
            margin-bottom: 0;
            width: 100%;
        }

        .fc-table th {
            padding: 8px 9px !important;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--dash-text-muted);
            border-bottom: 1px solid var(--dash-border) !important;
            border-top: 0 !important;
            white-space: nowrap;
            background: rgba(255, 255, 255, .015);
        }

        .fc-table td {
            padding: 9px !important;
            vertical-align: middle !important;
            border-top: 1px solid var(--dash-border) !important;
            color: var(--dash-text);
            font-size: 12.5px;
        }

        .fc-lines tbody tr:hover,
        .fc-list tbody tr:hover {
            background: rgba(45, 212, 160, .04) !important;
        }

        .fc-lines .fc-line-name {
            font-weight: 600;
            max-width: 190px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .fc-vacio {
            padding: 24px 16px !important;
            text-align: center;
            color: var(--dash-text-muted) !important;
            font-size: 12.5px !important;
        }

        .fc-pay-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 13px;
            border: 1px solid rgba(45, 212, 160, .25);
            background: linear-gradient(135deg, rgba(45, 212, 160, .11), rgba(45, 212, 160, .03));
            margin-bottom: 14px;
        }

        .fc-summary-label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .55px;
            text-transform: uppercase;
            color: var(--dash-text-muted);
            margin-bottom: 3px;
        }

        .fc-total {
            font-size: 24px;
            font-weight: 800;
            color: var(--dash-mint);
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .fc-pending {
            font-size: 24px;
            font-weight: 800;
            color: #f5b45b;
            line-height: 1.1;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .fc-pending.zero {
            color: var(--dash-text-muted);
        }

        .fc-save-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px 16px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .3px;
            box-shadow: 0 4px 14px rgba(45, 212, 160, .22);
            transition: all .15s ease;
        }

        .fc-save-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 20px rgba(45, 212, 160, .35);
        }

        .fc-save-btn:disabled {
            opacity: .6;
            transform: none;
        }

        .fc-list-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .fc-search {
            position: relative;
            min-width: 210px;
        }

        .fc-search i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dash-text-muted);
            font-size: 13px;
            pointer-events: none;
        }

        .fc-search input {
            width: 100%;
            height: 34px;
            padding: 0 11px 0 31px;
            border-radius: 9px;
            border: 1px solid var(--dash-border);
            background: var(--dash-bg);
            color: var(--dash-text);
            font-size: 12px;
            outline: none;
        }

        .fc-search input:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, .1);
        }

        .fc-chips {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .fc-chip {
            padding: 5px 10px;
            border-radius: 99px;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .2px;
            transition: all .15s ease;
        }

        .fc-chip:hover {
            color: var(--dash-text);
            border-color: var(--dash-text-muted);
        }

        .fc-chip.active {
            background: rgba(45, 212, 160, .14);
            border-color: rgba(45, 212, 160, .45);
            color: var(--dash-mint);
        }

        .fc-list-wrap {
            max-height: 74vh;
            overflow: auto;
        }

        .fc-list td {
            padding: 10px 9px !important;
        }

        .fc-cell-main {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.25;
        }

        .fc-cell-sub {
            margin-top: 2px;
            font-size: 10.5px;
            color: var(--dash-text-muted);
            line-height: 1.25;
        }

        .fc-amount {
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .fc-amount.due {
            color: #f5b45b;
        }

        .fc-badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .2px;
            white-space: nowrap;
        }

        .fc-badge.pagada {
            background: rgba(45, 212, 160, .16);
            color: #2dd4a0;
        }

        .fc-badge.pendiente {
            background: rgba(245, 180, 91, .16);
            color: #f5b45b;
        }

        .fc-badge.vencida {
            background: rgba(239, 90, 111, .16);
            color: #ef5a6f;
        }

        .fc-due {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .fc-due i {
            font-size: 12px;
        }

        .fc-due.ok { color: var(--dash-text-muted); }
        .fc-due.soon { color: #f5b45b; }
        .fc-due.late { color: #ef5a6f; font-weight: 800; }
        .fc-due.muted { color: var(--dash-text-muted); }

        .fc-actions {
            white-space: nowrap;
        }

        .fc-icon-btn {
            width: 29px;
            height: 29px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--dash-border);
            background: rgba(255, 255, 255, .03);
            color: var(--dash-text-muted);
            font-size: 13px;
            transition: all .15s ease;
            margin-left: 3px;
        }

        .fc-icon-btn:hover {
            color: var(--dash-text);
            border-color: var(--dash-text-muted);
        }

        .fc-icon-btn.info:hover {
            color: #5b9cf5;
            border-color: rgba(91, 156, 245, .5);
            background: rgba(91, 156, 245, .1);
        }

        .fc-icon-btn.success:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, .5);
            background: rgba(45, 212, 160, .1);
        }

        .fc-icon-btn.warn:hover {
            color: #f5b45b;
            border-color: rgba(245, 180, 91, .5);
            background: rgba(245, 180, 91, .1);
        }

        .fc-icon-btn.danger:hover {
            color: #ef5a6f;
            border-color: rgba(239, 90, 111, .5);
            background: rgba(239, 90, 111, .1);
        }

        .fc-empty {
            padding: 28px 18px;
            text-align: center;
            color: var(--dash-text-muted);
            font-size: 12.5px;
        }

        @media (max-width: 1100px) {
            .fc-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .right_col {
                padding: 16px 12px !important;
            }

            .fc-stats {
                grid-template-columns: 1fr;
            }

            .fc-card-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .fc-list-tools {
                width: 100%;
                justify-content: stretch;
            }

            .fc-search {
                min-width: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class="right_col" role="main">
                <div class="fc-hero">
                    <div>
                        <h2>Compras con factura</h2>
                        <p>Registra facturas de proveedores, controla saldos y fechas de vencimiento.</p>
                    </div>
                    <div class="fc-hero-actions">
                        <a href="nuevaCompra.php" class="fc-ghost-btn">
                            <i class="bi bi-arrow-left"></i> Compras clásicas
                        </a>
                        <button type="button" class="fc-primary-btn" data-toggle="modal" data-target="#modalNuevaFactura">
                            <i class="bi bi-plus-lg"></i> Nueva factura
                        </button>
                    </div>
                </div>

                <div class="fc-stats">
                    <div class="fc-stat pending">
                        <div class="fc-stat-label"><i class="bi bi-clock-history"></i> Por pagar</div>
                        <div class="fc-stat-value">$<?= number_format($stats['pendiente_saldo'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['pendientes'] ?> factura<?= $stats['pendientes'] === 1 ? '' : 's' ?> pendiente<?= $stats['pendientes'] === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="fc-stat overdue">
                        <div class="fc-stat-label"><i class="bi bi-exclamation-triangle"></i> Vencidas</div>
                        <div class="fc-stat-value">$<?= number_format($stats['vencida_saldo'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['vencidas'] ?> factura<?= $stats['vencidas'] === 1 ? '' : 's' ?> vencida<?= $stats['vencidas'] === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="fc-stat paid">
                        <div class="fc-stat-label"><i class="bi bi-check2-circle"></i> Pagadas</div>
                        <div class="fc-stat-value">$<?= number_format($stats['pagada_total'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['pagadas'] ?> factura<?= $stats['pagadas'] === 1 ? '' : 's' ?> pagada<?= $stats['pagadas'] === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="fc-stat total">
                        <div class="fc-stat-label"><i class="bi bi-receipt"></i> Total comprado</div>
                        <div class="fc-stat-value">$<?= number_format($stats['total'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['total_facturas'] ?> factura<?= $stats['total_facturas'] === 1 ? '' : 's' ?> registrada<?= $stats['total_facturas'] === 1 ? '' : 's' ?></div>
                    </div>
                </div>

                <div class="row">
                    <div class="modal fade" id="modalNuevaFactura" tabindex="-1" role="dialog" aria-labelledby="modalNuevaFacturaLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                            <div class="modal-content">
                                <div class="modal-header fc-modal-head">
                                    <div class="fc-card-title">
                                        <span class="fc-title-icon"><i class="bi bi-receipt-cutoff"></i></span>
                                        <div>
                                            <h3 id="modalNuevaFacturaLabel">Nueva factura de compra</h3>
                                            <p>Proveedor, productos y condiciones del crédito</p>
                                        </div>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                <div class="fc-wizard">
                                    <div class="fc-wizard-step active" data-step="1">
                                        <span>1</span>
                                        <div><strong>Datos</strong><small>Proveedor y factura</small></div>
                                    </div>
                                    <div class="fc-wizard-line"></div>
                                    <div class="fc-wizard-step" data-step="2">
                                        <span>2</span>
                                        <div><strong>Productos</strong><small>Detalle de compra</small></div>
                                    </div>
                                    <div class="fc-wizard-line"></div>
                                    <div class="fc-wizard-step" data-step="3">
                                        <span>3</span>
                                        <div><strong>Pago</strong><small>Abono y vencimiento</small></div>
                                    </div>
                                </div>
                                <form id="factura-form" class="fc-form" autocomplete="off">
                                    <div class="fc-step active" data-step="1">
                                    <div class="row">
                                        <div class="col-sm-6 fc-field">
                                            <label for="proveedor">Proveedor</label>
                                            <input type="text" id="proveedor" class="form-control" maxlength="255" required placeholder="Nombre del proveedor">
                                        </div>
                                        <div class="col-sm-6 fc-field">
                                            <label for="numero_factura">N° de factura</label>
                                            <input type="text" id="numero_factura" class="form-control" maxlength="100" required placeholder="Ej. F-000123">
                                        </div>
                                        <div class="col-sm-6 fc-field">
                                            <label for="fecha_factura">Fecha de factura</label>
                                            <input type="date" id="fecha_factura" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-sm-6 fc-field">
                                            <label for="sucursal">Sucursal</label>
                                            <select id="sucursal" class="form-control" required>
                                                <?php if (count($sucursales) !== 1): ?>
                                                    <option value="">-- Seleccione --</option>
                                                <?php endif; ?>
                                                <?php foreach ($sucursales as $sucursalItem): ?>
                                                    <option value="<?= (int) $sucursalItem['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($sucursalItem['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    </div><!-- /step 1 -->
                                    <div class="fc-step" data-step="2">
                                    <div class="fc-adder">
                                        <div class="row">
                                            <div class="col-sm-5 fc-field">
                                                <label for="filtro">Buscar producto</label>
                                                <input type="text" id="filtro" class="form-control" placeholder="Escribe el nombre...">
                                            </div>
                                            <div class="col-sm-7 fc-field">
                                                <label for="producto">Producto</label>
                                                <select id="producto" class="form-control">
                                                    <option value="">-- Indique un filtro --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6 col-sm-3 fc-field">
                                                <label for="cantidad">Cantidad</label>
                                                <input type="number" id="cantidad" class="form-control" min="0.001" step="0.001" inputmode="decimal" placeholder="0.000">
                                            </div>
                                            <div class="col-6 col-sm-3 fc-field">
                                                <label for="precio">Precio bulto ($)</label>
                                                <input type="number" id="precio" class="form-control" min="0.01" step="0.01" placeholder="0.00">
                                            </div>
                                            <div class="col-6 col-sm-3 fc-field">
                                                <label for="unidades_bulto">Unid. por bulto</label>
                                                <input type="number" id="unidades_bulto" class="form-control" min="1" step="1" placeholder="0">
                                            </div>
                                            <div class="col-6 col-sm-3 fc-field">
                                                <label for="porcentaje">% ganancia</label>
                                                <input type="number" id="porcentaje" class="form-control" min="0" step="0.01" placeholder="0">
                                            </div>
                                        </div>
                                        <button type="button" id="agregar-linea" class="fc-add-btn">
                                            <i class="bi bi-plus-lg"></i> Agregar producto a la factura
                                        </button>
                                    </div>

                                    <div class="fc-lines-wrap">
                                        <table class="table fc-table fc-lines">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th class="text-right">Cant.</th>
                                                    <th class="text-right">P. bulto</th>
                                                    <th class="text-right">Unid/bulto</th>
                                                    <th class="text-right">Subtotal</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="lineas-body">
                                                <tr>
                                                    <td colspan="6" class="fc-vacio">Sin productos agregados</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    </div><!-- /step 2 -->
                                    <div class="fc-step" data-step="3">
                                    <div class="row">
                                        <div class="col-sm-6 fc-field">
                                            <label for="abono_inicial">Abono inicial ($)</label>
                                            <input type="number" id="abono_inicial" class="form-control" min="0" step="0.01" value="0">
                                            <small class="fc-help">Déjalo en 0 para registrar la factura como pendiente.</small>
                                        </div>
                                        <div class="col-sm-6 fc-field">
                                            <label for="fecha_vencimiento">Vencimiento del crédito</label>
                                            <input type="date" id="fecha_vencimiento" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                                            <small class="fc-help" id="vencimiento-help">Fecha límite para pagar el saldo pendiente.</small>
                                        </div>
                                    </div>

                                    <div class="fc-pay-summary">
                                        <div>
                                            <span class="fc-summary-label">Total factura</span>
                                            <div class="fc-total">$<span id="total-factura">0.00</span></div>
                                        </div>
                                        <div>
                                            <span class="fc-summary-label">Queda pendiente</span>
                                            <div class="fc-pending" id="saldo-preview">$0.00</div>
                                        </div>
                                    </div>
                                    </div><!-- /step 3 -->
                                </form>
                                </div><!-- /modal-body -->
                                <div class="modal-footer">
                                    <button type="button" class="fc-ghost-btn" data-dismiss="modal">Cancelar</button>
                                    <div class="fc-wizard-nav">
                                        <button type="button" id="wizard-atras" class="fc-ghost-btn d-none">
                                            <i class="bi bi-arrow-left"></i> Atrás
                                        </button>
                                        <button type="button" id="wizard-siguiente" class="fc-primary-btn">
                                            Siguiente <i class="bi bi-arrow-right"></i>
                                        </button>
                                        <button type="submit" id="guardar-factura" form="factura-form" class="fc-save-btn d-none">
                                            <i class="bi bi-check2-circle"></i> Guardar factura
                                        </button>
                                    </div>
                                </div>
                            </div><!-- /modal-content -->
                        </div><!-- /modal-dialog -->
                    </div><!-- /modal -->

                    <div class="col-12">
                        <div class="fc-card">
                            <div class="fc-card-head">
                                <div class="fc-card-title">
                                    <span class="fc-title-icon"><i class="bi bi-journal-text"></i></span>
                                    <div>
                                        <h3>Cuentas por pagar</h3>
                                        <p><?= (int) $stats['total_facturas'] ?> factura<?= $stats['total_facturas'] === 1 ? '' : 's' ?> registrada<?= $stats['total_facturas'] === 1 ? '' : 's' ?></p>
                                    </div>
                                </div>
                                <div class="fc-list-tools">
                                    <div class="fc-search">
                                        <i class="bi bi-search"></i>
                                        <input type="text" id="buscar-factura" placeholder="Buscar factura o proveedor">
                                    </div>
                                    <div class="fc-chips">
                                        <button type="button" class="fc-chip active" data-filtro="todas">Todas</button>
                                        <button type="button" class="fc-chip" data-filtro="pendientes">Pendientes</button>
                                        <button type="button" class="fc-chip" data-filtro="vencidas">Vencidas</button>
                                        <button type="button" class="fc-chip" data-filtro="pagadas">Pagadas</button>
                                    </div>
                                </div>
                            </div>
                            <div class="fc-card-body p-0">
                                <div class="fc-list-wrap">
                                    <table class="table fc-table fc-list">
                                        <thead>
                                            <tr>
                                                <th>Factura</th>
                                                <th>Proveedor</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-right">Saldo</th>
                                                <th>Vencimiento</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="fc-facturas-body">
                                            <?php if (count($facturas) === 0): ?>
                                                <tr>
                                                    <td colspan="7" class="fc-vacio">
                                                        <div>Aún no hay facturas registradas.</div>
                                                        <button type="button" class="fc-primary-btn" style="margin-top:12px;" data-toggle="modal" data-target="#modalNuevaFactura">
                                                            <i class="bi bi-plus-lg"></i> Registrar primera factura
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            <?php foreach ($facturas as $factura):
                                                $total = (float) $factura['total'];
                                                $saldo = (float) $factura['saldo_calc'];
                                                $sucursalNombre = $nombresSucursal[(int) $factura['id_sucursal']] ?? '';
                                                $esPagada = $factura['estado'] === 'pagada';
                                                $esVencida = $factura['vencida_calc'];
                                                $badgeClase = $esPagada ? 'pagada' : ($esVencida ? 'vencida' : 'pendiente');
                                                $badgeTexto = $esPagada ? 'Pagada' : ($esVencida ? 'Vencida' : 'Pendiente');
                                                $busqueda = mb_strtolower($factura['numero_factura'] . ' ' . $factura['proveedor'] . ' ' . $sucursalNombre, 'UTF-8');
                                                $fechaVencimiento = !empty($factura['fecha_vencimiento']) ? date('d/m/Y', strtotime($factura['fecha_vencimiento'])) : '';
                                            ?>
                                                <tr data-factura="<?= (int) $factura['id'] ?>"
                                                    data-estado="<?= $esPagada ? 'pagada' : 'pendiente' ?>"
                                                    data-vencida="<?= $esVencida ? '1' : '0' ?>"
                                                    data-search="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
                                                    <td>
                                                        <div class="fc-cell-main"><?= htmlspecialchars($factura['numero_factura']) ?></div>
                                                        <div class="fc-cell-sub"><?= date('d/m/Y', strtotime($factura['fecha_factura'])) ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fc-cell-main"><?= htmlspecialchars($factura['proveedor']) ?></div>
                                                        <div class="fc-cell-sub"><?= htmlspecialchars($sucursalNombre) ?></div>
                                                    </td>
                                                    <td class="text-right fc-amount">$<?= number_format($total, 2, '.', ',') ?></td>
                                                    <td class="text-right fc-amount <?= $saldo > 0.005 ? 'due' : '' ?>">$<?= number_format($saldo, 2, '.', ',') ?></td>
                                                    <td>
                                                        <?php if ($esPagada): ?>
                                                            <span class="fc-due muted"><i class="bi bi-check2"></i> Sin crédito</span>
                                                        <?php elseif ($esVencida): ?>
                                                            <span class="fc-due late"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($factura['vence_texto']) ?></span>
                                                            <div class="fc-cell-sub"><?= $fechaVencimiento ?></div>
                                                        <?php elseif ($fechaVencimiento !== ''): ?>
                                                            <span class="fc-due <?= $factura['dias_vencimiento'] !== null && $factura['dias_vencimiento'] <= 5 ? 'soon' : 'ok' ?>"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($factura['vence_texto']) ?></span>
                                                            <div class="fc-cell-sub"><?= $fechaVencimiento ?></div>
                                                        <?php else: ?>
                                                            <span class="fc-due muted"><i class="bi bi-calendar-x"></i> Sin vencimiento</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="fc-badge <?= $badgeClase ?>"><?= $badgeTexto ?></span></td>
                                                    <td class="text-right fc-actions">
                                                        <button type="button" class="fc-icon-btn info" title="Ver detalle" onclick="verFactura(<?= (int) $factura['id'] ?>)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php if ($saldo > 0.005): ?>
                                                            <button type="button" class="fc-icon-btn success" title="Registrar abono" onclick="abonarFactura(<?= (int) $factura['id'] ?>, <?= number_format($saldo, 2, '.', '') ?>)">
                                                                <i class="bi bi-cash-coin"></i>
                                                            </button>
                                                            <button type="button" class="fc-icon-btn warn" title="Cambiar vencimiento" onclick="editarVencimiento(<?= (int) $factura['id'] ?>, '<?= $factura['fecha_vencimiento'] ?>')">
                                                                <i class="bi bi-calendar-event"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="fc-empty-filter" class="fc-empty" style="display:none;">No hay facturas que coincidan con el filtro.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        (function() {
            const urlAcciones = '../../configurar/factura_compra_acciones.php';
            let lineas = [];

            const $ = (id) => document.getElementById(id);
            const filtro = $('filtro');
            const producto = $('producto');
            const sucursal = $('sucursal');
            const cantidad = $('cantidad');
            const precio = $('precio');
            const unidadesBulto = $('unidades_bulto');
            const porcentaje = $('porcentaje');
            const abonoInicial = $('abono_inicial');
            const fechaFactura = $('fecha_factura');
            const fechaVencimiento = $('fecha_vencimiento');
            const vencimientoHelp = $('vencimiento-help');
            const saldoPreview = $('saldo-preview');
            const lineasBody = $('lineas-body');
            const totalFactura = $('total-factura');
            const form = $('factura-form');
            const guardar = $('guardar-factura');
            const buscarFactura = $('buscar-factura');
            const emptyFilter = $('fc-empty-filter');
            const wizardSteps = document.querySelectorAll('.fc-wizard-step');
            const wizardPanels = document.querySelectorAll('.fc-step');
            const btnWizardAtras = $('wizard-atras');
            const btnWizardSiguiente = $('wizard-siguiente');
            let filtroFacturas = 'todas';
            let vencimientoManual = false;
            let pasoActual = 1;

            fechaVencimiento.min = fechaFactura.value;

            function sumarDias(fechaIso, dias) {
                if (!fechaIso) return '';
                const partes = fechaIso.split('-').map(Number);
                if (partes.length !== 3 || partes.some(Number.isNaN)) return '';
                const fecha = new Date(partes[0], partes[1] - 1, partes[2]);
                fecha.setDate(fecha.getDate() + dias);
                return fecha.getFullYear() + '-' +
                    String(fecha.getMonth() + 1).padStart(2, '0') + '-' +
                    String(fecha.getDate()).padStart(2, '0');
            }

            function dinero(valor) {
                return Number(valor || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function fechaCorta(fechaISO) {
                if (!fechaISO) return '';
                const partes = String(fechaISO).split('-');
                return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : fechaISO;
            }

            function totalLinea(linea) {
                const unidadesPorBulto = parseInt(linea.unidades_bulto, 10);
                if (!unidadesPorBulto || unidadesPorBulto <= 0) return 0;
                return (Number(linea.precio) / unidadesPorBulto) * Number(linea.cantidad);
            }

            // Las cantidades pueden ser fraccionarias (kg, litros, etc.).
            // Se guardan con hasta 3 decimales, igual que el stock.
            function redondearCantidad(valor) {
                const numero = Number(valor);
                if (!isFinite(numero)) return NaN;
                return Math.round((numero + Number.EPSILON) * 1000) / 1000;
            }

            function cantidadTexto(valor) {
                return Number(valor || 0).toLocaleString('en-US', {
                    maximumFractionDigits: 3
                });
            }

            function mostrarPaso(paso) {
                pasoActual = paso;

                wizardPanels.forEach((panel) => {
                    panel.classList.toggle('active', Number(panel.dataset.step) === paso);
                });
                wizardSteps.forEach((step) => {
                    const numero = Number(step.dataset.step);
                    step.classList.toggle('active', numero === paso);
                    step.classList.toggle('done', numero < paso);
                });

                btnWizardAtras.classList.toggle('d-none', paso === 1);
                btnWizardSiguiente.classList.toggle('d-none', paso === 3);
                guardar.classList.toggle('d-none', paso !== 3);

                if (paso === 2) {
                    setTimeout(() => filtro.focus(), 120);
                }
                if (paso === 3) {
                    actualizarResumenPago();
                }
            }

            function validarPaso1() {
                if (!$('proveedor').value.trim()) {
                    Alerta.toast('error', 'Indique el proveedor de la factura');
                    $('proveedor').focus();
                    return false;
                }
                if (!$('numero_factura').value.trim()) {
                    Alerta.toast('error', 'Indique el número de factura');
                    $('numero_factura').focus();
                    return false;
                }
                if (!$('fecha_factura').value) {
                    Alerta.toast('error', 'Indique la fecha de la factura');
                    $('fecha_factura').focus();
                    return false;
                }
                if (!sucursal.value) {
                    Alerta.toast('error', 'Seleccione la sucursal');
                    sucursal.focus();
                    return false;
                }
                return true;
            }

            function validarPaso2() {
                if (lineas.length === 0) {
                    Alerta.toast('error', 'Agregue al menos un producto a la factura');
                    return false;
                }
                return true;
            }

            function actualizarResumenPago() {
                const total = lineas.reduce((acumulado, linea) => acumulado + totalLinea(linea), 0);
                const abono = parseFloat(abonoInicial.value || 0) || 0;
                const saldo = Math.max(0, total - abono);
                const tieneLineas = total > 0.005;
                const pendiente = tieneLineas && saldo > 0.005;

                totalFactura.textContent = dinero(total);
                saldoPreview.textContent = '$' + dinero(saldo);
                saldoPreview.classList.toggle('zero', !pendiente || !tieneLineas);
                fechaVencimiento.disabled = tieneLineas && !pendiente;

                if (!tieneLineas) {
                    vencimientoHelp.textContent = 'Se sugiere 30 días desde la fecha de la factura.';
                } else if (pendiente) {
                    vencimientoHelp.textContent = 'Fecha límite para pagar el saldo pendiente.';
                } else {
                    vencimientoHelp.textContent = 'Factura pagada: no requiere vencimiento.';
                }
            }

            function renderLineas() {
                lineasBody.innerHTML = '';

                if (lineas.length === 0) {
                    const tr = document.createElement('tr');
                    const td = document.createElement('td');
                    td.colSpan = 6;
                    td.className = 'fc-vacio';
                    td.textContent = 'Sin productos agregados';
                    tr.appendChild(td);
                    lineasBody.appendChild(tr);
                    actualizarResumenPago();
                    return;
                }

                lineas.forEach((linea, indice) => {
                    const subtotal = totalLinea(linea);
                    const tr = document.createElement('tr');

                    const tdNombre = document.createElement('td');
                    const nombre = document.createElement('div');
                    nombre.className = 'fc-line-name';
                    nombre.textContent = linea.nombre;
                    nombre.title = linea.nombre;
                    tdNombre.appendChild(nombre);
                    tr.appendChild(tdNombre);

                    [
                        [cantidadTexto(linea.cantidad), 'text-right'],
                        ['$' + dinero(linea.precio), 'text-right'],
                        [linea.unidades_bulto, 'text-right'],
                        ['$' + dinero(subtotal), 'text-right fc-amount']
                    ].forEach(([valor, clase]) => {
                        const td = document.createElement('td');
                        td.textContent = valor;
                        td.className = clase;
                        tr.appendChild(td);
                    });

                    const tdQuitar = document.createElement('td');
                    tdQuitar.className = 'text-right';
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'fc-icon-btn danger';
                    boton.title = 'Quitar producto';
                    boton.innerHTML = '<i class="bi bi-trash3"></i>';
                    boton.addEventListener('click', function() {
                        lineas.splice(indice, 1);
                        renderLineas();
                    });
                    tdQuitar.appendChild(boton);
                    tr.appendChild(tdQuitar);

                    lineasBody.appendChild(tr);
                });

                actualizarResumenPago();
            }

            function cargarProductos() {
                const texto = filtro.value.trim();
                producto.innerHTML = '<option value="">-- Indique un filtro --</option>';

                if (texto.length < 2) return;
                if (!sucursal.value) {
                    Alerta.toast('error', 'Seleccione una sucursal antes de buscar el producto');
                    return;
                }

                const datos = new URLSearchParams({
                    producto: texto,
                    sucursal: sucursal.value
                });

                fetch('../../configurar/consulta_codigo_producto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: datos
                    })
                    .then((res) => res.json())
                    .then((lista) => {
                        producto.innerHTML = '<option value="">-- Seleccione --</option>';
                        (lista || []).forEach((item) => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.nombre;
                            producto.appendChild(option);
                        });
                    })
                    .catch(() => {
                        producto.innerHTML = '<option value="">-- Error al cargar --</option>';
                    });
            }

            function cargarPrecio() {
                if (!producto.value || !sucursal.value) return;

                const datos = new URLSearchParams({
                    producto: producto.value,
                    sucursal: sucursal.value
                });

                fetch('precio_producto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: datos
                    })
                    .then((res) => res.json())
                    .then((res) => {
                        if (!res.success) {
                            Alerta.toast('warning', 'No se pudo consultar el producto');
                            return;
                        }

                        precio.value = res.data.precio_compra || '';
                        unidadesBulto.value = res.data.cantidad_unidades || '';
                        porcentaje.value = res.data.porcentaje || '';
                        if (!$('proveedor').value.trim() && res.data.proveedor) {
                            $('proveedor').value = res.data.proveedor;
                        }
                    })
                    .catch(() => {
                        Alerta.toast('error', 'No se pudo consultar el producto');
                    });
            }

            function agregarLinea() {
                if (!producto.value) {
                    Alerta.toast('error', 'Seleccione un producto');
                    return;
                }

                const cantidadValor = redondearCantidad(cantidad.value);
                const precioValor = parseFloat(precio.value);
                const unidadesValor = parseInt(unidadesBulto.value, 10);
                const porcentajeValor = parseFloat(porcentaje.value || 0);

                if (!cantidadValor || !isFinite(cantidadValor) || cantidadValor <= 0 || !precioValor || precioValor <= 0 || !unidadesValor || unidadesValor <= 0) {
                    Alerta.toast('error', 'Cantidad, precio y unidades por bulto deben ser mayores que cero');
                    return;
                }

                lineas.push({
                    producto_id: parseInt(producto.value, 10),
                    nombre: producto.options[producto.selectedIndex].textContent,
                    cantidad: cantidadValor,
                    precio: precioValor,
                    unidades_bulto: unidadesValor,
                    porcentaje: isNaN(porcentajeValor) ? 0 : porcentajeValor
                });

                cantidad.value = '';
                precio.value = '';
                unidadesBulto.value = '';
                porcentaje.value = '';
                producto.value = '';
                renderLineas();
            }

            async function enviarJson(payload) {
                const respuesta = await fetch(urlAcciones, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                return respuesta.json();
            }

            function textoConciliacion(productos) {
                const n = (v) => Number(v || 0).toLocaleString('es-VE', {maximumFractionDigits: 3});
                return '<div style="text-align:left;font-size:13px;">' + productos.map((p) => `
                    <div style="border-bottom:1px solid #eee;padding:8px 0;">
                        <p style="margin:0 0 4px;"><b>${p.nombre || ''}</b></p>
                        <p style="margin:0;">Stock actual: <b>${n(p.stock_actual)} ${p.unidad || ''}</b></p>
                        <p style="margin:0;">Última compra: ${p.ultima_compra_at ? p.ultima_compra_at + ' (' + n(p.ultima_compra_kg) + ')' : 'sin registro'}</p>
                        <p style="margin:0;">Vendido desde entonces: <b>${n(p.vendido_desde)}</b></p>
                    </div>`).join('') +
                    '<p style="color:#888;margin-top:10px;">Confirme el monto actual si el stock es correcto, o vaya al módulo de ajuste de stock para registrar la diferencia.</p></div>';
            }

            async function mostrarConciliacion(productos) {
                const r = await Swal.fire({
                    title: 'Confirmar cantidad anterior',
                    html: textoConciliacion(productos || []),
                    width: 560,
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar monto actual',
                    denyButtonText: 'Ir a ajuste de stock',
                    cancelButtonText: 'Cancelar'
                });
                if (r.isConfirmed) return true;
                if (r.isDenied) window.open('ajustes_stock.php', '_blank');
                return false;
            }

            async function enviarFactura(payload) {
                const data = await enviarJson(payload);
                if (data && data.requiere_conciliacion) {
                    const confirmado = await mostrarConciliacion(data.productos);
                    if (!confirmado) return null;
                    payload.conciliaciones = {};
                    (data.productos || []).forEach((p) => {
                        payload.conciliaciones[p.id] = {
                            confirmado: 1,
                            stock_visto: p.stock_actual
                        };
                    });
                    return enviarFactura(payload);
                }
                return data;
            }

            window.verFactura = async function(id) {
                try {
                    const respuesta = await fetch(urlAcciones + '?accion=detalle&id=' + encodeURIComponent(id));
                    const data = await respuesta.json();

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo cargar la factura');
                    }

                    const esc = (valor) => {
                        const div = document.createElement('div');
                        div.textContent = valor == null ? '' : String(valor);
                        return div.innerHTML;
                    };

                    const vence = data.factura.fecha_vencimiento ? fechaCorta(data.factura.fecha_vencimiento) : 'Sin definir';
                    const estadoTxt = data.factura.estado === 'pagada' ? 'Pagada' : (data.saldo <= 0.005 ? 'Pagada' : 'Pendiente');

                    let html = '<div style="text-align:left;font-size:13px;">';
                    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 12px;margin-bottom:12px;">';
                    html += '<div><strong>Proveedor:</strong><br>' + esc(data.factura.proveedor) + '</div>';
                    html += '<div><strong>N° factura:</strong><br>' + esc(data.factura.numero_factura) + '</div>';
                    html += '<div><strong>Fecha:</strong><br>' + esc(fechaCorta(data.factura.fecha_factura)) + '</div>';
                    html += '<div><strong>Vencimiento:</strong><br>' + esc(vence) + '</div>';
                    html += '<div><strong>Estado:</strong><br>' + esc(estadoTxt) + '</div>';
                    html += '</div>';

                    html += '<table style="width:100%;border-collapse:collapse;margin-bottom:12px;">';
                    html += '<thead><tr><th style="text-align:left;border-bottom:1px solid #ccc;padding:5px;">Producto</th><th style="text-align:right;border-bottom:1px solid #ccc;padding:5px;">Cant.</th><th style="text-align:right;border-bottom:1px solid #ccc;padding:5px;">Subtotal</th></tr></thead><tbody>';
                    (data.lineas || []).forEach((linea) => {
                        html += '<tr><td style="padding:5px;border-bottom:1px solid #eee;">' + esc(linea.producto) + '</td><td style="text-align:right;padding:5px;border-bottom:1px solid #eee;">' + esc(linea.cantidad) + '</td><td style="text-align:right;padding:5px;border-bottom:1px solid #eee;">$' + dinero(linea.subtotal) + '</td></tr>';
                    });
                    html += '</tbody></table>';

                    html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;margin-bottom:12px;">';
                    html += '<div style="padding:8px;border-radius:8px;background:rgba(45,212,160,.08);"><small>Total</small><br><b>$' + dinero(data.factura.total) + '</b></div>';
                    html += '<div style="padding:8px;border-radius:8px;background:rgba(91,156,245,.08);"><small>Abonado</small><br><b>$' + dinero(data.abonado) + '</b></div>';
                    html += '<div style="padding:8px;border-radius:8px;background:rgba(245,180,91,.1);"><small>Saldo</small><br><b>$' + dinero(data.saldo) + '</b></div>';
                    html += '</div>';

                    if ((data.pagos || []).length > 0) {
                        html += '<strong>Abonos registrados:</strong><ul style="margin:6px 0 0 18px;padding:0;">';
                        data.pagos.forEach((pago) => {
                            html += '<li>$' + dinero(pago.monto) + ' — ' + esc(pago.fecha) + ' (' + esc(pago.usuario) + ')</li>';
                        });
                        html += '</ul>';
                    }

                    html += '</div>';

                    Swal.fire({
                        title: 'Factura ' + esc(data.factura.numero_factura),
                        html: html,
                        width: 600,
                        confirmButtonText: 'Cerrar'
                    });
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            };

            window.abonarFactura = async function(id, saldo) {
                const resultado = await Swal.fire({
                    title: 'Registrar abono',
                    input: 'number',
                    inputValue: Number(saldo).toFixed(2),
                    inputAttributes: {
                        min: '0.01',
                        max: String(saldo),
                        step: '0.01'
                    },
                    text: 'Saldo pendiente: $' + dinero(saldo),
                    showCancelButton: true,
                    confirmButtonText: 'Guardar abono',
                    cancelButtonText: 'Cancelar'
                });

                if (!resultado.isConfirmed) return;

                const monto = parseFloat(resultado.value);
                if (isNaN(monto) || monto <= 0) {
                    Alerta.toast('error', 'Indique un monto válido');
                    return;
                }

                try {
                    const data = await enviarJson({
                        accion: 'abonar',
                        factura_id: id,
                        monto: monto
                    });

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo registrar el abono');
                    }

                    Alerta.toast('success', 'Abono registrado. Saldo: $' + dinero(data.saldo));
                    setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            };

            window.editarVencimiento = async function(id, fechaActual) {
                const resultado = await Swal.fire({
                    title: 'Vencimiento del crédito',
                    input: 'date',
                    inputValue: fechaActual || '',
                    text: 'Fecha límite para pagar el saldo pendiente.',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar fecha',
                    cancelButtonText: 'Cancelar'
                });

                if (!resultado.isConfirmed) return;
                if (!resultado.value) {
                    Alerta.toast('error', 'Seleccione una fecha de vencimiento');
                    return;
                }

                try {
                    const data = await enviarJson({
                        accion: 'vencimiento',
                        factura_id: id,
                        fecha_vencimiento: resultado.value
                    });

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo actualizar el vencimiento');
                    }

                    Alerta.toast('success', data.message);
                    setTimeout(() => window.location.reload(), 600);
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            };

            function aplicarFiltrosFacturas() {
                const texto = (buscarFactura.value || '').toLowerCase().trim();
                let visibles = 0;

                document.querySelectorAll('#fc-facturas-body tr[data-factura]').forEach((tr) => {
                    const estado = tr.dataset.estado;
                    const vencida = tr.dataset.vencida === '1';
                    const coincideFiltro =
                        filtroFacturas === 'todas' ||
                        (filtroFacturas === 'pendientes' && estado === 'pendiente') ||
                        (filtroFacturas === 'vencidas' && vencida) ||
                        (filtroFacturas === 'pagadas' && estado === 'pagada');
                    const coincideBusqueda = texto === '' || (tr.dataset.search || '').includes(texto);
                    const mostrar = coincideFiltro && coincideBusqueda;

                    tr.style.display = mostrar ? '' : 'none';
                    if (mostrar) visibles++;
                });

                emptyFilter.style.display = visibles === 0 ? 'block' : 'none';
            }

            document.querySelectorAll('.fc-chip').forEach((boton) => {
                boton.addEventListener('click', function() {
                    document.querySelectorAll('.fc-chip').forEach((b) => b.classList.remove('active'));
                    this.classList.add('active');
                    filtroFacturas = this.dataset.filtro;
                    aplicarFiltrosFacturas();
                });
            });

            buscarFactura.addEventListener('input', aplicarFiltrosFacturas);

            btnWizardSiguiente.addEventListener('click', function() {
                if (pasoActual === 1 && !validarPaso1()) return;
                if (pasoActual === 2 && !validarPaso2()) return;
                if (pasoActual < 3) mostrarPaso(pasoActual + 1);
            });

            btnWizardAtras.addEventListener('click', function() {
                if (pasoActual > 1) mostrarPaso(pasoActual - 1);
            });

            wizardSteps.forEach((step) => {
                step.addEventListener('click', function() {
                    const destino = Number(this.dataset.step);
                    if (destino < pasoActual) mostrarPaso(destino);
                });
            });

            filtro.addEventListener('keyup', cargarProductos);
            sucursal.addEventListener('change', function() {
                filtro.value = '';
                producto.innerHTML = '<option value="">-- Indique un filtro --</option>';
                lineas = [];
                renderLineas();
            });
            producto.addEventListener('change', cargarPrecio);
            $('agregar-linea').addEventListener('click', agregarLinea);
            abonoInicial.addEventListener('input', actualizarResumenPago);
            fechaVencimiento.addEventListener('change', function() {
                vencimientoManual = true;
                actualizarResumenPago();
            });
            fechaVencimiento.addEventListener('click', function() {
                if (this.disabled || typeof this.showPicker !== 'function') return;
                try {
                    this.showPicker();
                } catch (error) {
                    // El navegador puede exigir un gesto de usuario; el selector nativo sigue disponible.
                }
            });
            fechaFactura.addEventListener('change', function() {
                fechaVencimiento.min = this.value;
                if (!vencimientoManual) {
                    fechaVencimiento.value = sumarDias(this.value, 30);
                }
                actualizarResumenPago();
            });

            form.addEventListener('submit', async function(evento) {
                evento.preventDefault();

                if (pasoActual !== 3) {
                    if (pasoActual === 1 && validarPaso1()) {
                        mostrarPaso(2);
                    } else if (pasoActual === 2 && validarPaso2()) {
                        mostrarPaso(3);
                    }
                    return;
                }

                if (lineas.length === 0) {
                    Alerta.toast('error', 'Agregue al menos un producto a la factura');
                    return;
                }

                const total = lineas.reduce((acumulado, linea) => acumulado + totalLinea(linea), 0);
                const abono = parseFloat(abonoInicial.value || 0);

                if (isNaN(abono) || abono < 0) {
                    Alerta.toast('error', 'El abono inicial no es válido');
                    return;
                }
                if (abono > total + 0.001) {
                    Alerta.toast('error', 'El abono inicial no puede superar el total');
                    return;
                }
                if (total - abono > 0.005 && !fechaVencimiento.value) {
                    Alerta.toast('error', 'Indique la fecha de vencimiento del crédito');
                    fechaVencimiento.focus();
                    return;
                }
                if (total - abono > 0.005 && fechaVencimiento.value < fechaFactura.value) {
                    Alerta.toast('error', 'El vencimiento no puede ser anterior a la fecha de la factura');
                    fechaVencimiento.focus();
                    return;
                }

                guardar.disabled = true;

                try {
                    const data = await enviarFactura({
                        accion: 'guardar',
                        proveedor: $('proveedor').value.trim(),
                        numero_factura: $('numero_factura').value.trim(),
                        fecha_factura: $('fecha_factura').value,
                        fecha_vencimiento: total - abono > 0.005 ? fechaVencimiento.value : '',
                        sucursal: sucursal.value,
                        abono_inicial: abono,
                        lineas: lineas
                    });

                    if (!data) return;
                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo guardar la factura');
                    }

                    Alerta.toast('success', 'Factura registrada correctamente');
                    setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                } finally {
                    guardar.disabled = false;
                }
            });

            const modalNuevaFactura = document.getElementById('modalNuevaFactura');
            if (modalNuevaFactura) {
                modalNuevaFactura.addEventListener('shown.bs.modal', function() {
                    mostrarPaso(1);
                    setTimeout(() => $('proveedor').focus(), 120);
                });
            }

            renderLineas();
            mostrarPaso(1);
            aplicarFiltrosFacturas();
        })();
    </script>
</body>

</html>
