<?php
require_once('includes/requires.php');
require_once(__DIR__ . '/../../iseller_store/core/reputacion.php');

$topnav  = topnav();
$bss_id  = $_SESSION['bss_id'];

$stmtT = $conexion->prepare("SELECT slug, tiene_tienda_virtual, nombre, nombre_visible, reputacion_total, reputacion_recomiendan, reputacion_pct FROM negocio WHERE id = ?");
$stmtT->bind_param("i", $bss_id);
$stmtT->execute();
$tienda = $stmtT->get_result()->fetch_assoc();
$stmtT->close();

if (!$tienda || !$tienda['tiene_tienda_virtual']) {
    header("Location: tienda_onboarding.php");
    exit;
}

$slug = $tienda['slug'];

$repNivel      = reputacion_nivel($tienda['reputacion_total'], $tienda['reputacion_pct']);
$repTotal      = (int)$tienda['reputacion_total'];
$repRecomiendan = (int)$tienda['reputacion_recomiendan'];
$repReviews    = [];

$stmtRep = $conexion->prepare("
    SELECT v.recomienda, v.comentario, v.created_at, u.nombre
    FROM tienda_reputacion_votos v
    LEFT JOIN tienda_usuarios u ON u.id = v.usuario_id
    WHERE v.negocio_id = ? AND v.oculto = 0
      AND v.comentario IS NOT NULL AND v.comentario <> ''
    ORDER BY v.id DESC
    LIMIT 10
");
$stmtRep->bind_param("i", $bss_id);
$stmtRep->execute();
$repReviews = $stmtRep->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtRep->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Tienda Virtual — Pedidos Web</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <link href="../vendors/datatables/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css">
    <style>
        .pedidos-page {
            padding: 24px;
        }

        .pedidos-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .pedidos-header h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .pedidos-header h2 i {
            color: var(--dash-mint);
            font-size: 1.2rem;
        }

        /* Reputación */
        .rep-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-left: 4px solid var(--rep-color, #9e9e9e);
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }

        .rep-card-main {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .rep-score {
            min-width: 128px;
        }

        .rep-score strong {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: var(--rep-color, var(--dash-text));
        }

        .rep-score strong small {
            font-size: 1rem;
        }

        .rep-score span {
            display: block;
            font-size: 0.7rem;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 4px;
        }

        .rep-info {
            flex: 1;
            min-width: 220px;
        }

        .rep-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--dash-text);
        }

        .rep-level {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 700;
            border: 1px solid currentColor;
        }

        .rep-sub {
            font-size: 0.8rem;
            color: var(--dash-text-muted);
            margin-top: 3px;
        }

        .rep-progress {
            height: 8px;
            background: rgba(0, 0, 0, 0.06);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 10px;
            max-width: 320px;
        }

        .rep-progress span {
            display: block;
            height: 100%;
            background: var(--rep-color, #9e9e9e);
            border-radius: 999px;
        }

        .rep-hint {
            font-size: 0.75rem;
            color: var(--dash-text-muted);
            margin-top: 6px;
        }

        .rep-reviews {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
            border-top: 1px solid var(--dash-border);
            margin-top: 16px;
            padding-top: 14px;
        }

        .rep-review-item {
            background: rgba(0, 0, 0, 0.02);
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            padding: 10px 12px;
        }

        .rep-review-top {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--dash-text);
            margin-bottom: 4px;
        }

        .rep-review-top i.up {
            color: #00a650;
        }

        .rep-review-top i.down {
            color: #f23d4f;
        }

        .rep-review-item p {
            margin: 0 0 4px;
            font-size: 0.8rem;
            color: var(--dash-text-muted);
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .rep-review-item small {
            font-size: 0.7rem;
            color: var(--dash-text-muted);
            opacity: 0.8;
        }

        /* Stats row */
        .pedidos-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .stat-icon.pending {
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .stat-icon.prep {
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .stat-icon.shipped {
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .stat-icon.delivered {
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .stat-info .stat-num {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1;
        }

        .stat-info .stat-label {
            font-size: 0.72rem;
            color: var(--dash-text-muted);
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Table card */
        .table-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-card .x_title {
            background: transparent;
            border-bottom: 1px solid var(--dash-border);
            padding: 16px 20px;
            margin: 0;
        }

        .table-card .x_title h2 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dash-text);
        }

        .table-card .x_content {
            padding: 0;
        }

        /* DataTable overrides */
        .tabla-pedidos {
            width: 100% !important;
            border-collapse: collapse;
        }

        .tabla-pedidos thead th {
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            white-space: nowrap;
        }

        .tabla-pedidos tbody td {
            padding: 14px 16px;
            color: var(--dash-text);
            font-size: 0.85rem;
            border-bottom: 1px solid var(--dash-border);
            vertical-align: middle;
        }

        .tabla-pedidos tbody tr:hover {
            background: rgba(45, 212, 160, 0.03);
        }

        .tabla-pedidos tbody tr:last-child td {
            border-bottom: none;
        }

        .tabla-pedidos .odd {
            background: transparent;
        }

        .tabla-pedidos .even {
            background: transparent;
        }

        /* Pestañas de la página (pendientes / entregadas) */
        .pedidos-tabs {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            border-bottom: 1px solid var(--dash-border);
        }

        .pedidos-tab {
            padding: 14px 24px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--dash-text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .pedidos-tab:hover {
            color: var(--dash-text);
        }

        .pedidos-tab.active {
            color: var(--dash-mint);
            border-bottom-color: var(--dash-mint);
        }

        .pedidos-panel {
            display: none;
        }

        .pedidos-panel.active {
            display: block;
        }

        /* Botones de la columna Acciones en una sola línea */
        .acciones-pedido {
            display: flex;
            gap: 4px;
        }

        .acciones-pedido .btn {
            white-space: nowrap;
        }

        /* Pagination & search */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            padding: 12px 16px;
            margin: 0;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 6px;
            color: var(--dash-text);
            padding: 6px 10px;
            font-size: 0.82rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding: 12px 16px;
            color: var(--dash-text-muted);
            font-size: 0.78rem;
            border-top: 1px solid var(--dash-border);
        }

        .dataTables_wrapper .dataTables_paginate {
            padding: 8px 16px;
            border-top: 1px solid var(--dash-border);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--dash-text-muted) !important;
            border: 1px solid var(--dash-border) !important;
            border-radius: 6px !important;
            margin: 0 2px;
            padding: 5px 12px;
            font-size: 0.78rem;
            background: transparent !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--dash-mint) !important;
            border-color: var(--dash-mint) !important;
            color: #000 !important;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            border-color: var(--dash-mint) !important;
            color: var(--dash-mint) !important;
        }

        /* Badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .badge-status::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-1 {
            background: rgba(250, 204, 21, 0.1);
            color: #facc15;
        }

        .status-1::before {
            background: #facc15;
        }

        .status-2 {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .status-2::before {
            background: #3b82f6;
        }

        .status-3 {
            background: rgba(168, 85, 247, 0.1);
            color: #a855f7;
        }

        .status-3::before {
            background: #a855f7;
        }

        .status-4 {
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .status-4::before {
            background: var(--dash-mint);
        }

        .status-5 {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .status-5::before {
            background: #ef4444;
        }

        /* Status stepper */
        .status-steps {
            display: flex;
            align-items: flex-start;
        }

        .status-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            position: relative;
            text-align: center;
        }

        .status-step::before {
            content: '';
            position: absolute;
            top: 13px;
            left: -50%;
            width: 100%;
            height: 2px;
            background: var(--dash-border);
            z-index: 0;
        }

        .status-step:first-child::before {
            display: none;
        }

        .status-step.done::before,
        .status-step.active::before {
            background: var(--dash-mint);
        }

        .status-step .step-dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--dash-bg);
            border: 2px solid var(--dash-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--dash-text-muted);
            z-index: 1;
            transition: all 0.2s ease;
        }

        .status-step.done .step-dot {
            background: var(--dash-mint);
            border-color: var(--dash-mint);
            color: #000;
        }

        .status-step.active .step-dot {
            border-color: var(--dash-mint);
            color: var(--dash-mint);
            box-shadow: 0 0 0 4px rgba(45, 212, 160, 0.15);
        }

        .status-step .step-label {
            font-size: 0.68rem;
            color: var(--dash-text-muted);
            font-weight: 500;
            white-space: nowrap;
        }

        .status-step.active .step-label {
            color: var(--dash-mint);
            font-weight: 600;
        }

        .status-step.done .step-label {
            color: var(--dash-text);
        }

        .status-step.rejected .step-dot {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }

        .status-step.rejected .step-label {
            color: #ef4444;
            font-weight: 600;
        }

        .status-done-msg {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--dash-mint);
            font-weight: 500;
        }

        .status-done-msg.rejected {
            color: #ef4444;
        }

        /* Modal premium */
        .modal-content {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            color: var(--dash-text);
        }

        .modal-header {
            border-bottom: 1px solid var(--dash-border);
            padding: 20px 24px;
        }

        .modal-header .modal-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--dash-text);
        }

        .modal-order-meta {
            font-size: 0.75rem;
            color: var(--dash-text-muted);
            margin-top: 4px;
        }

        .meta-sep {
            margin: 0 6px;
            opacity: 0.4;
        }

        .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.5;
        }

        .modal-body {
            padding: 0;
        }

        /* Tabs */
        .modal-tabs {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            border-bottom: 1px solid var(--dash-border);
        }

        .modal-tab {
            padding: 14px 24px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--dash-text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .modal-tab:hover {
            color: var(--dash-text);
        }

        .modal-tab.active {
            color: var(--dash-mint);
            border-bottom-color: var(--dash-mint);
        }

        .modal-tab-content {
            display: none;
            padding: 24px;
        }

        .modal-tab-content.active {
            display: block;
        }

        /* Detail grid */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-label {
            font-size: 0.72rem;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        .detail-value {
            font-size: 0.88rem;
            color: var(--dash-text);
            font-weight: 500;
        }

        .detail-section h5 {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            margin-top: 0;
        }

        /* Payment tab */
        .pago-header-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px;
            background: rgba(45, 212, 160, 0.04);
            border: 1px solid rgba(45, 212, 160, 0.1);
            border-radius: 10px;
            margin-bottom: 16px;
        }

        .pago-method-badge {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .pago-method-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .pago-method-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--dash-text);
        }

        .pago-method-sub {
            font-size: 0.78rem;
            color: var(--dash-text-muted);
        }

        .pago-ref-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .pago-ref-card {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            padding: 14px;
        }

        .pago-ref-label {
            font-size: 0.68rem;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }

        .pago-ref-value {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--dash-text);
            word-break: break-all;
        }

        .pago-totals-section {
            border-top: 1px solid var(--dash-border);
            padding-top: 16px;
        }

        .pago-totals-title {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .payment-totals {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .payment-total-item {
            background: rgba(45, 212, 160, 0.04);
            border: 1px solid rgba(45, 212, 160, 0.1);
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }

        .payment-total-label {
            font-size: 0.7rem;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
        }

        .payment-total-amount {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--dash-mint);
        }

        /* Modal form elements */
        .modal-tab-content .input-group {
            display: flex;
            gap: 8px;
        }

        .modal-tab-content .form-select {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            font-size: 0.85rem;
            flex: 1;
        }

        .modal-tab-content .form-select:focus {
            border-color: var(--dash-mint);
            box-shadow: none;
        }

        .modal-tab-content .btn-primary {
            background: var(--dash-mint);
            border-color: var(--dash-mint);
            color: #000;
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 20px;
            white-space: nowrap;
        }

        .modal-tab-content .btn-primary:hover {
            opacity: 0.9;
        }

        .mt-3 {
            margin-top: 16px;
        }

        /* Map */
        .map-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 200px;
            border: 1px dashed var(--dash-border);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.02);
        }

        .articulos-list {
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            overflow: hidden;
        }

        .product-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--dash-border);
        }

        .product-list-item:last-child {
            border-bottom: none;
        }

        .product-list-item .prod-name {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--dash-text);
        }

        .product-list-item .prod-qty {
            font-size: 0.78rem;
            color: var(--dash-text-muted);
            margin-top: 2px;
        }

        .product-list-item .prod-sucursal {
            font-size: 0.72rem;
            color: var(--dash-mint);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .product-list-item .prod-sucursal i {
            font-size: 0.7rem;
        }

        .product-list-item .prod-total {
            font-weight: 600;
            font-size: 0.88rem;
            color: var(--dash-text);
        }

        .order-summary-box {
            background: rgba(45, 212, 160, 0.05);
            border: 1px solid rgba(45, 212, 160, 0.12);
            padding: 14px 18px;
            border-radius: 10px;
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .order-summary-box h6 {
            margin: 0;
            font-size: 0.85rem;
            color: var(--dash-text-muted);
            font-weight: 500;
        }

        .order-summary-box strong {
            color: var(--dash-mint);
            font-size: 1.2rem;
            margin-left: 8px;
        }

        /* Empty state */
        .dataTables_empty {
            color: var(--dash-text-muted) !important;
            padding: 40px 16px !important;
            font-size: 0.88rem !important;
        }

        @media (max-width: 768px) {
            .pedidos-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .pedidos-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .pago-ref-grid {
                grid-template-columns: 1fr;
            }

            .payment-totals {
                grid-template-columns: 1fr;
            }

            /* La tabla de 8 columnas no cabe en el teléfono: quedan # orden,
               cliente, total, estado y acciones. Fecha, teléfono y entrega
               se ven en el detalle ("Ver"). */
            .tabla-pedidos thead th:nth-child(2),
            .tabla-pedidos tbody td:nth-child(2),
            .tabla-pedidos thead th:nth-child(4),
            .tabla-pedidos tbody td:nth-child(4),
            .tabla-pedidos thead th:nth-child(5),
            .tabla-pedidos tbody td:nth-child(5) {
                display: none;
            }

            .tabla-pedidos thead th,
            .tabla-pedidos tbody td {
                padding: 10px 8px;
                font-size: .8rem;
                white-space: normal;
            }

            /* DataTables deja anchos fijos calculados: se recalculan para que entre */
            .tabla-pedidos thead th {
                width: auto !important;
            }

            .tabla-pedidos .badge-status {
                font-size: .65rem;
                padding: 3px 8px;
            }

            .acciones-pedido {
                gap: 6px;
                justify-content: flex-end;
            }

            .acciones-pedido .btn {
                width: 36px;
                padding: 6px;
                justify-content: center;
            }

            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                padding: 10px 12px;
            }
        }

        /* ---- Modal Asignar transportista (SweetAlert2) ---- */
        .swal-transp-popup {
            background: var(--dash-card, #181b22) !important;
            border: 1px solid var(--dash-border, #2e353e) !important;
            border-radius: 16px !important;
            color: var(--dash-text, #e8edf2) !important;
            padding: 24px !important;
        }

        .swal-transp-title {
            color: var(--dash-text, #e8edf2) !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
        }

        .swal-transp-html {
            margin: 14px 0 0 !important;
            padding: 0 !important;
        }

        .transp-order-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border: 1px solid var(--dash-border, #2e353e);
            background: rgba(45, 212, 160, 0.05);
            border-radius: 10px;
            margin-bottom: 16px;
            text-align: left;
        }

        .transp-order-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(45, 212, 160, 0.12);
            color: var(--dash-mint, #2dd4a0);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .transp-order-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--dash-text, #e8edf2);
        }

        .transp-order-sub {
            font-size: 0.75rem;
            color: var(--dash-text-muted, #8892a0);
            margin-top: 2px;
        }

        .transp-field-label {
            display: block;
            text-align: left;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--dash-text-muted, #8892a0);
            margin-bottom: 6px;
        }

        .swal-transp-select {
            width: 100% !important;
            display: flex !important;
            background: var(--dash-bg, #1a1e24) !important;
            border: 1px solid var(--dash-border, #2e353e) !important;
            color: var(--dash-text, #e8edf2) !important;
            border-radius: 8px !important;
            padding: 10px 12px !important;
            font-size: 0.85rem !important;
            margin: 0 !important;
            box-shadow: none !important;
        }

        .swal-transp-select:focus {
            border-color: var(--dash-mint, #2dd4a0) !important;
            box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.12) !important;
        }

        .transp-hint-modal {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            margin: 12px 0 0;
            text-align: left;
            font-size: 0.74rem;
            line-height: 1.45;
            color: var(--dash-text-muted, #8892a0);
        }

        .transp-hint-modal i {
            color: var(--dash-mint, #2dd4a0);
            margin-top: 1px;
        }

        .swal-transp-confirm {
            background: var(--dash-mint, #2dd4a0) !important;
            color: #000 !important;
            font-weight: 700 !important;
            border-radius: 8px !important;
            padding: 9px 20px !important;
        }

        .swal-transp-cancel {
            background: transparent !important;
            border: 1px solid var(--dash-border, #2e353e) !important;
            color: var(--dash-text-muted, #8892a0) !important;
            border-radius: 8px !important;
            padding: 9px 20px !important;
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class="right_col" role="main">
                <div class="pedidos-page">
                    <div class="pedidos-header">
                        <h2><i class="bi bi-box-seam"></i> Pedidos de Tienda Virtual</h2>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <a href="tienda_transportistas.php" class="btn btn-outline-secondary">
                                <i class="bi bi-person-badge"></i> Mis transportistas
                            </a>
                            <button class="btn btn-outline-secondary" onclick="cargarPedidos()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <div class="rep-card" style="--rep-color: <?= $repNivel['color'] ?>">
                        <div class="rep-card-main">
                            <div class="rep-score">
                                <?php if ($repNivel['visible']): ?>
                                    <strong><?= round((float)$tienda['reputacion_pct']) ?><small>%</small></strong>
                                    <span>recomendado</span>
                                <?php else: ?>
                                    <strong>—</strong>
                                    <span>Sin reputación</span>
                                <?php endif; ?>
                            </div>
                            <div class="rep-info">
                                <div class="rep-title">
                                    Mi reputación
                                    <span class="rep-level" style="color: <?= $repNivel['color'] ?>"><?= htmlspecialchars($repNivel['label']) ?></span>
                                </div>
                                <div class="rep-sub"><?= $repTotal ?> calificaciones · <?= $repRecomiendan ?> recomendaciones</div>
                                <?php if (!$repNivel['visible']): ?>
                                    <div class="rep-progress"><span style="width: <?= min(100, $repTotal / 5 * 100) ?>%"></span></div>
                                    <div class="rep-hint">Te faltan <?= max(0, 5 - $repTotal) ?> calificaciones para publicar tu reputación en la tienda.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (count($repReviews) > 0): ?>
                            <div class="rep-reviews">
                                <?php foreach ($repReviews as $rv): ?>
                                    <div class="rep-review-item">
                                        <div class="rep-review-top">
                                            <i class="bi <?= (int)$rv['recomienda'] === 1 ? 'bi-hand-thumbs-up-fill up' : 'bi-hand-thumbs-down-fill down' ?>"></i>
                                            <span><?= htmlspecialchars(reputacion_nombre_publico($rv['nombre'])) ?></span>
                                        </div>
                                        <p><?= nl2br(htmlspecialchars($rv['comentario'])) ?></p>
                                        <small><?= date('d/m/Y', strtotime($rv['created_at'])) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pedidos-stats">
                        <div class="stat-card">
                            <div class="stat-icon pending"><i class="bi bi-hourglass-split"></i></div>
                            <div class="stat-info">
                                <div class="stat-num" id="stat-pendientes">0</div>
                                <div class="stat-label">Pendientes</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon prep"><i class="bi bi-box-seam"></i></div>
                            <div class="stat-info">
                                <div class="stat-num" id="stat-preparacion">0</div>
                                <div class="stat-label">En preparación</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon shipped"><i class="bi bi-truck"></i></div>
                            <div class="stat-info">
                                <div class="stat-num" id="stat-enviados">0</div>
                                <div class="stat-label">Enviados</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon delivered"><i class="bi bi-check-circle"></i></div>
                            <div class="stat-info">
                                <div class="stat-num" id="stat-entregados">0</div>
                                <div class="stat-label">Entregados</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-card">
                        <?php $columnasPedido = ['# Orden', 'Fecha', 'Cliente', 'Teléfono', 'Entrega', 'Total', 'Estado', 'Acciones']; ?>

                        <ul class="pedidos-tabs" id="pedidosTabs">
                            <li class="pedidos-tab active" data-panel="pendientes">Pendientes</li>
                            <li class="pedidos-tab" data-panel="entregadas">Entregadas</li>
                        </ul>

                        <div class="pedidos-panel active" id="panel-pendientes">
                            <table id="tablaPendientes" class="display tabla-pedidos" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <?php foreach ($columnasPedido as $col): ?>
                                            <th><?php echo $col; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="pedidos-panel" id="panel-entregadas">
                            <table id="tablaEntregadas" class="display tabla-pedidos" cellspacing="0" width="100%">
                                <thead>
                                    <tr>
                                        <?php foreach ($columnasPedido as $col): ?>
                                            <th><?php echo $col; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetallePedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title">Orden #<span id="det-id"></span></h4>
                        <div class="modal-order-meta">
                            <span id="det-fecha"></span>
                            <span class="meta-sep">·</span>
                            <span id="det-status-header"></span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="modal-tabs" id="modalTabs">
                        <li class="modal-tab active" data-tab="cliente">Cliente</li>
                        <li class="modal-tab" data-tab="pago">Pago</li>
                        <li class="modal-tab" data-tab="articulos">Artículos</li>
                        <li class="modal-tab" data-tab="ubicacion">Ubicación</li>
                    </ul>

                    <div class="modal-tab-content active" id="tab-cliente">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Nombre</div>
                                <div class="detail-value" id="det-cliente"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Teléfono</div>
                                <div class="detail-value" id="det-telefono"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Email</div>
                                <div class="detail-value" id="det-email"></div>
                            </div>
                        </div>
                        <div class="detail-section text-center mt-3 pt-3 mb-3">
                            <h5>Estado del pedido</h5>
                            <div id="status-stepper"></div>
                            <div id="status-actions" class="mt-3"></div>
                        </div>

                        <div id="transportista-box" class="detail-section text-center mt-3 pt-3 mb-3" style="display:none;">
                            <h5>Transportista</h5>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:12px 14px;border:1px solid var(--dash-border);border-radius:10px;background:rgba(45,212,160,.04);text-align:left;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <i class="bi bi-truck" style="color:var(--dash-mint);font-size:1.1rem;"></i>
                                    <div>
                                        <div id="det-transportista" style="font-weight:600;font-size:.86rem;"></div>
                                        <div id="det-transportista-estado" style="font-size:.72rem;color:var(--dash-text-muted);"></div>
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <button id="btn-notificar-transportista" type="button" class="btn btn-outline-success btn-sm" onclick="reenviarNotificacion()" style="display:none;" title="Reenviar notificación a la app">
                                        <i class="bi bi-bell"></i> Notificar
                                    </button>
                                    <button id="btn-cambiar-transportista" type="button" class="btn btn-outline-success btn-sm" onclick="cambiarTransportista()" style="display:none;">
                                        <i class="bi bi-arrow-repeat"></i> Cambiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-tab-content" id="tab-pago">
                        <div class="pago-header-card">
                            <div class="pago-method-badge" id="det-pago-icon"><i class="bi bi-phone"></i></div>
                            <div class="pago-method-info">
                                <div class="pago-method-name" id="det-tipo-pago"></div>
                                <div class="pago-method-sub" id="det-tipo-entrega"></div>
                            </div>
                        </div>

                        <div class="pago-ref-grid">
                            <div class="pago-ref-card">
                                <div class="pago-ref-label">Referencia</div>
                                <div class="pago-ref-value" id="det-num-op">—</div>
                            </div>
                            <div class="pago-ref-card">
                                <div class="pago-ref-label">Fecha del pago</div>
                                <div class="pago-ref-value" id="det-hora-op">—</div>
                            </div>
                            <div class="pago-ref-card">
                                <div class="pago-ref-label">Fecha del pedido</div>
                                <div class="pago-ref-value" id="det-fecha-full">—</div>
                            </div>
                        </div>

                        <div class="pago-totals-section">
                            <h6 class="pago-totals-title">Totales de la orden</h6>
                            <div class="payment-totals">
                                <div class="payment-total-item">
                                    <div class="payment-total-label">Total (USD)</div>
                                    <div class="payment-total-amount" id="det-total-usd"></div>
                                </div>
                                <div class="payment-total-item">
                                    <div class="payment-total-label">Total (Bs.)</div>
                                    <div class="payment-total-amount" id="det-total-bs"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-tab-content" id="tab-articulos">
                        <div id="det-articulos" class="articulos-list"></div>
                        <div class="order-summary-box">
                            <h6>Total:</h6>
                            <strong id="det-total"></strong>
                        </div>
                    </div>

                    <div class="modal-tab-content" id="tab-ubicacion">
                        <div id="det-direccion-text" class="detail-grid" style="margin-bottom:16px;"></div>
                        <div id="det-map" style="height:300px;border-radius:10px;border:1px solid var(--dash-border);background:var(--dash-bg);"></div>
                        <div id="det-map-empty" class="map-empty-state" style="display:none;">
                            <i class="bi bi-geo-alt" style="font-size:2rem;color:var(--dash-text-muted);"></i>
                            <p style="color:var(--dash-text-muted);margin:8px 0 0;font-size:0.88rem;">Sin ubicación registrada para esta orden</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/jquery-3.7.1.min.js"></script>
    <script src="../vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/leaflet/leaflet.js"></script>
    <script src="../vendors/datatables/jquery.dataTables.min.js"></script>
    <script src="../vendors/datatables/dataTables.bootstrap5.min.js"></script>
    <script>
        var tablaPendientes, tablaEntregadas;
        var ordenActual = null;
        var ordenActualTipo = '';
        var ordenStatus = 0;
        var ordenTransportistaId = 0;
        var orderMap = null;
        var orderMarker = null;

        function trunc2(n) {
            return (Math.floor(Math.round(parseFloat(n) * 1e6) / 1e4) / 100).toFixed(2);
        }

        // Escapa datos del pedido antes de inyectarlos en el ticket impreso.
        function escTicket(value) {
            return String(value ?? '').replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        $(document).ready(function() {
            var dtConfig = {
                language: {
                    url: '../vendors/datatables/es-ES.json'
                },
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                columnDefs: [{
                    orderable: false,
                    targets: 7
                }]
            };
            tablaPendientes = $('#tablaPendientes').DataTable(dtConfig);
            tablaEntregadas = $('#tablaEntregadas').DataTable(dtConfig);

            // Pestañas de la página. La tabla de entregadas arranca oculta: al mostrarla
            // hay que recalcular los anchos de columna.
            $('#pedidosTabs').on('click', '.pedidos-tab', function() {
                var panel = $(this).data('panel');
                $('.pedidos-tab').removeClass('active');
                $(this).addClass('active');
                $('.pedidos-panel').removeClass('active');
                $('#panel-' + panel).addClass('active');
                (panel === 'entregadas' ? tablaEntregadas : tablaPendientes).columns.adjust();
            });

            // Tab switching
            $(document).on('click', '.modal-tab', function() {
                var tab = $(this).data('tab');
                $('.modal-tab').removeClass('active');
                $(this).addClass('active');
                $('.modal-tab-content').removeClass('active');
                $('#tab-' + tab).addClass('active');
                if (tab === 'ubicacion' && orderMap) {
                    setTimeout(function() {
                        orderMap.invalidateSize();
                    }, 100);
                }
            });

            // Reset tabs on modal open
            $('#modalDetallePedido').on('show.bs.modal', function() {
                $('.modal-tab').removeClass('active');
                $('.modal-tab[data-tab="cliente"]').addClass('active');
                $('.modal-tab-content').removeClass('active');
                $('#tab-cliente').addClass('active');
            });

            // Cleanup map on modal close
            $('#modalDetallePedido').on('hidden.bs.modal', function() {
                if (orderMap) {
                    orderMap.remove();
                    orderMap = null;
                }
            });

            cargarPedidos();
        });

        function getStatusText(status, tipoEntrega) {
            status = parseInt(status);
            var map = {
                1: 'Pendiente',
                2: 'En preparación',
                3: 'Enviado',
                4: 'Entregado',
                5: 'Rechazado'
            };
            // Solo vista: retiro en tienda muestra "Confirmado" en lugar de "Enviado"
            if (status === 3 && tipoEntrega === 'retiro_tienda') return 'Confirmado';
            return map[status] || 'Pendiente';
        }

        function getStatusBadge(status, tipoEntrega) {
            return '<span class="badge-status status-' + status + '">' + getStatusText(status, tipoEntrega) + '</span>';
        }

        function getTipoPago(id) {
            id = parseInt(id);
            var map = {
                2: 'Transferencia',
                6: 'Pago móvil',
                1: 'Efectivo',
                3: 'Transferencia',
                4: 'Tarjeta',
                5: 'Zelle'
            };
            return map[id] || 'Otro (' + id + ')';
        }

        function cargarPedidos() {
            fetch('db/tienda_pedidos_api.php?action=listar')
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) return;
                    var filasPendientes = [],
                        filasEntregadas = [];
                    var counts = {
                        1: 0,
                        2: 0,
                        3: 0,
                        4: 0
                    };
                    (data.data || []).forEach(function(o) {
                        var s = parseInt(o.status);
                        if (counts[s] !== undefined) counts[s]++;
                        var btns = '<div class="acciones-pedido">' +
                            '<button class="btn btn-outline-success btn-sm" onclick="verDetalle(' + o.id + ')" title="Ver pedido"><i class="bi bi-eye"></i><span class="d-none d-md-inline"> Ver</span></button>' +
                            '<button class="btn btn-outline-success btn-sm" onclick="imprimirTicketTienda(' + o.id + ')" title="Imprimir orden"><i class="bi bi-printer"></i><span class="d-none d-md-inline"> Imprimir orden</span></button>' +
                            '</div>';
                        var entrega = o.tipo_entrega === 'delivery' ?
                            '<span style="display:inline-flex;align-items:center;gap:5px;"><i class="bi bi-truck" style="color:var(--dash-mint);"></i> Delivery</span>' :
                            o.tipo_entrega === 'retiro_tienda' ?
                            '<span style="display:inline-flex;align-items:center;gap:5px;"><i class="bi bi-bag-check" style="color:var(--dash-info);"></i> Retiro</span>' :
                            '—';
                        var fila = [
                            '#' + o.id,
                            o.created,
                            o.cliente_nombre || o.cliente || 'N/A',
                            o.cliente_telefono || '—',
                            entrega,
                            '$' + trunc2(o.total_price),
                            getStatusBadge(o.status, o.tipo_entrega),
                            btns
                        ];
                        // Cerradas: entregadas (4) y rechazadas (5)
                        (s === 4 || s === 5 ? filasEntregadas : filasPendientes).push(fila);
                    });
                    tablaPendientes.clear().rows.add(filasPendientes).draw();
                    tablaEntregadas.clear().rows.add(filasEntregadas).draw();
                    $('#stat-pendientes').text(counts[1]);
                    $('#stat-preparacion').text(counts[2]);
                    $('#stat-enviados').text(counts[3]);
                    $('#stat-entregados').text(counts[4]);
                })
                .catch(function(err) {
                    console.error('Error:', err);
                });
        }

        function verDetalle(id) {
            ordenActual = id;
            fetch('db/tienda_pedidos_api.php?action=detalle&id=' + id)
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) {
                        alert(data.msg);
                        return;
                    }
                    var o = data.orden;
                    ordenActualTipo = o.tipo_entrega || '';

                    // Header
                    $('#det-id').text(o.id);
                    $('#det-fecha').text(o.created);
                    $('#det-status-header').html(getStatusBadge(o.status, o.tipo_entrega));

                    // Cliente tab
                    $('#det-cliente').text(o.cliente_nombre || o.cliente || 'N/A');
                    $('#det-telefono').text(o.cliente_telefono || 'N/A');
                    $('#det-email').text(o.cliente_email || 'N/A');
                    renderStatusStepper(o);

                    // Transportista (delivery enviado o entregado)
                    var esDelivery = (o.tipo_entrega === 'delivery');
                    var estadoNum = parseInt(o.status);
                    var tpBox = document.getElementById('transportista-box');
                    ordenTransportistaId = parseInt(o.transportista_id || 0);
                    if (esDelivery && (estadoNum === 3 || estadoNum === 4)) {
                        tpBox.style.display = '';
                        document.getElementById('det-transportista').textContent = o.transportista_nombre || 'Sin transportista asignado';
                        document.getElementById('det-transportista-estado').textContent = o.transportista_estado ? ('Servicio: ' + o.transportista_estado) : '';
                        var btnTp = document.getElementById('btn-cambiar-transportista');
                        btnTp.style.display = estadoNum === 3 ? 'inline-flex' : 'none';
                        btnTp.innerHTML = ordenTransportistaId
                            ? '<i class="bi bi-arrow-repeat"></i> Cambiar'
                            : '<i class="bi bi-truck"></i> Asignar';
                        document.getElementById('btn-notificar-transportista').style.display =
                            (estadoNum === 3 && ordenTransportistaId) ? 'inline-flex' : 'none';
                    } else {
                        tpBox.style.display = 'none';
                    }

                    // Pago tab
                    var tipoPagoId = parseInt(o.tipoPago);
                    var tipoPagoMap = {
                        1: 'Efectivo',
                        2: 'Transferencia',
                        3: 'Transferencia',
                        4: 'Tarjeta',
                        5: 'Zelle',
                        6: 'Pago móvil'
                    };
                    var tipoPagoNombre = tipoPagoMap[tipoPagoId] || 'Otro';
                    $('#det-tipo-pago').text(tipoPagoNombre);

                    var tipoEntrega = o.tipo_entrega || '';
                    var tipoEntregaMap = {
                        delivery: 'Delivery',
                        retiro_tienda: 'Retiro en tienda'
                    };
                    $('#det-tipo-entrega').text(tipoEntregaMap[tipoEntrega] || tipoEntrega || 'No especificado');

                    var iconMap = {
                        1: 'bi-cash-stack',
                        2: 'bi-bank',
                        3: 'bi-bank',
                        4: 'bi-credit-card',
                        5: 'bi-lightning',
                        6: 'bi-phone'
                    };
                    var iconClass = iconMap[tipoPagoId] || 'bi-wallet2';
                    $('#det-pago-icon').html('<i class="bi ' + iconClass + '"></i>');

                    $('#det-num-op').text(o.numero_operacion_bancaria || 'Sin referencia');
                    $('#det-hora-op').text(o.hora_operacion_bancaria || '—');
                    $('#det-fecha-full').text(o.created);
                    $('#det-total-usd').text('$' + trunc2(o.total_price));
                    $('#det-total-bs').text('Bs. ' + trunc2(o.total_price_bs || 0));

                    // Artículos tab
                    var arts = '';
                    data.articulos.forEach(function(a) {
                        var total = trunc2(a.quantity * parseFloat(a.precio));
                        arts += '<div class="product-list-item">' +
                            '<div><div class="prod-name">' + escTicket(a.producto_nombre || 'Producto') + '</div>' +
                            '<div class="prod-qty">' + a.quantity + ' x $' + trunc2(a.precio) + '</div>' +
                            (a.sucursal_nombre ? '<div class="prod-sucursal"><i class="bi bi-shop"></i> ' + a.sucursal_nombre + '</div>' : '') +
                            '</div>' +
                            '<div class="prod-total">$' + total + '</div></div>';
                    });
                    if (!arts) {
                        arts = '<div style="padding:28px 16px;text-align:center;color:var(--dash-text-muted);font-size:0.85rem;">Sin artículos registrados para esta orden</div>';
                    }
                    $('#det-articulos').html(arts);
                    $('#det-total').text('$' + trunc2(o.total_price));

                    // Ubicación tab
                    var hasLocation = o.lat && o.lng && parseFloat(o.lat) !== 0 && parseFloat(o.lng) !== 0;
                    var dirHtml = '<div class="detail-item"><div class="detail-label">Dirección</div><div class="detail-value">' + (o.direccion || 'N/A') + '</div></div>';
                    dirHtml += '<div class="detail-item"><div class="detail-label">Referencia</div><div class="detail-value">' + (o.referencia || '—') + '</div></div>';
                    dirHtml += '<div class="detail-item"><div class="detail-label">Comunidad</div><div class="detail-value">' + (o.comunidad || '—') + '</div></div>';
                    dirHtml += '<div class="detail-item"><div class="detail-label">Teléfono</div><div class="detail-value">' + (o.cliente_telefono || '—') + '</div></div>';
                    $('#det-direccion-text').html(dirHtml);

                    if (hasLocation) {
                        $('#det-map').show();
                        $('#det-map-empty').hide();
                        if (orderMap) {
                            orderMap.remove();
                            orderMap = null;
                        }
                        orderMap = L.map('det-map').setView([parseFloat(o.lat), parseFloat(o.lng)], 15);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(orderMap);
                        orderMarker = L.marker([parseFloat(o.lat), parseFloat(o.lng)]).addTo(orderMap)
                            .bindPopup(o.direccion || 'Ubicación del cliente').openPopup();
                        setTimeout(function() {
                            orderMap.invalidateSize();
                        }, 200);
                    } else {
                        $('#det-map').hide();
                        $('#det-map-empty').show();
                    }

                    // getOrCreateInstance: reutiliza la instancia. Crear una nueva en cada
                    // apertura/refresco acumula backdrops y al cerrar queda uno pegado.
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetallePedido')).show();
                })
                .catch(function(err) {
                    console.error('Error:', err);
                });
        }

        function imprimirTicketTienda(id) {
            fetch('db/tienda_pedidos_api.php?action=detalle&id=' + id)
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) {
                        alert(data.msg || 'Error al obtener datos');
                        return;
                    }
                    var o = data.orden;
                    var arts = data.articulos;

                    var fecha = new Date().toLocaleString('es-VE', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });

                    var tipoPagoMap = {
                        1: 'Efectivo',
                        2: 'Transferencia',
                        3: 'Transferencia',
                        4: 'Tarjeta',
                        5: 'Zelle',
                        6: 'Pago móvil'
                    };
                    var tipoPagoNombre = tipoPagoMap[parseInt(o.tipoPago)] || 'Otro';
                    var tipoEntregaMap = {
                        delivery: 'Delivery',
                        retiro_tienda: 'Retiro en tienda'
                    };
                    var tipoEntregaNombre = tipoEntregaMap[o.tipo_entrega] || o.tipo_entrega || '';

                    // Envío: el importe realmente cobrado en la orden (historial del cliente).
                    // Órdenes viejas sin historial: se recalcula desde la config, como antes.
                    var cfgDel = data.config_delivery || {};
                    var costoEnvio = 0;
                    if (o.tipo_entrega === 'delivery') {
                        costoEnvio = (o.importe_envio !== null && o.importe_envio !== undefined)
                            ? parseFloat(o.importe_envio || 0)
                            : (cfgDel.tipo === 'pago' ? parseFloat(cfgDel.costo || 0) : 0);
                    }

                    var filasArticulos = '';
                    arts.forEach(function(a) {
                        var subtotal = trunc2(a.quantity * parseFloat(a.precio));
                        filasArticulos += '<tr>' +
                            '<td style="font-size:11px;width:12%;word-wrap:break-word;">' + escTicket(a.quantity) + '</td>' +
                            '<td style="font-size:11px;width:60%;word-wrap:break-word;">' + escTicket(a.producto_nombre || 'Producto') + '</td>' +
                            '<td style="font-size:11px;width:28%;text-align:right;">$' + subtotal + '</td>' +
                            '</tr>';
                    });

                    var contenido = `
                <html>
                <head>
                    <title>Ticket Pedido #${o.id}</title>
                    <style>
                        * { box-sizing: border-box; }
                        body {
                            font-family: Arial, sans-serif;
                            font-size: 11px !important;
                            margin: 0; padding: 0;
                            max-width: 58mm;
                        }
                        .centrado { text-align: center; }
                        @media print {
                            @page { size: 58mm auto; margin: 0; }
                            body, html { margin: 0; padding: 0; }
                            #ticket { page-break-after: avoid; }
                        }
                        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                        th, td { text-align: left; padding: 2px 0; }
                        .line { border-top: 1px dashed #000; margin: 5px 0; }
                    </style>
                </head>
                <body>
                    <div class="ticket" id="ticket">
                        <p class="centrado">
                            <br>
                            <strong>PEDIDO WEB #${o.id}</strong><br>
                            <small>${fecha}</small><br>
                        </p>
                        <div class="line"></div>
                        <p style="margin:2px 0;font-size:11px;"><strong>Cliente:</strong> ${escTicket(o.cliente_nombre || o.cliente || 'N/A')}</p>
                        <p style="margin:2px 0;font-size:11px;"><strong>Tel:</strong> ${escTicket(o.cliente_telefono || 'N/A')}</p>
                        ${o.direccion ? '<p style="margin:2px 0;font-size:11px;"><strong>Dirección:</strong> ' + escTicket(o.direccion) + '</p>' : ''}
                        ${o.referencia ? '<p style="margin:2px 0;font-size:10px;"><strong>Ref:</strong> ' + escTicket(o.referencia) + '</p>' : ''}
                        ${tipoEntregaNombre ? '<p style="margin:2px 0;font-size:11px;"><strong>Entrega:</strong> ' + escTicket(tipoEntregaNombre) + '</p>' : ''}
                        <div class="line"></div>
                        <p style="margin:2px 0;font-size:11px;"><strong>Pago:</strong> ${tipoPagoNombre}</p>
                        ${o.numero_operacion_bancaria ? '<p style="margin:2px 0;font-size:10px;"><strong>Ref. pago:</strong> ' + escTicket(o.numero_operacion_bancaria) + '</p>' : ''}
                        <div class="line"></div>
                        <table>
                            <thead>
                                <tr>
                                    <th style="font-size:11px;width:12%;">Cant</th>
                                    <th style="font-size:11px;width:60%;">Producto</th>
                                    <th style="font-size:11px;width:28%;text-align:right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${filasArticulos}
                            </tbody>
                        </table>
                        <div class="line"></div>
                        ${costoEnvio > 0 ? '<p style="margin:2px 0;font-size:11px;text-align:right;"><strong>Delivery:</strong> $' + trunc2(costoEnvio) + '</p>' : ''}
                        <p class="centrado" style="font-size:13px;"><strong>TOTAL: $${trunc2(o.total_price)}</strong></p>
                        ${o.total_price_bs ? '<p class="centrado" style="font-size:11px;">Bs. ' + trunc2(o.total_price_bs) + '</p>' : ''}
                        <div class="line"></div>
                        <p class="centrado" style="font-size:10px;">¡GRACIAS POR SU COMPRA!</p>
                        <div class="line"></div>
                    </div>
                </body>
                </html>
            `;

                    var ventana = window.open('', '_blank', 'width=600,height=600');
                    ventana.document.open();
                    ventana.document.write(contenido);
                    ventana.document.close();
                    ventana.onload = function() {
                        ventana.print();
                        ventana.close();
                    };
                })
                .catch(function(err) {
                    console.error('Error al generar ticket:', err);
                    alert('Error al generar ticket.');
                });
        }

        function getStatusLabels(tipoEntrega) {
            return {
                1: 'Pendiente',
                2: 'En preparación',
                3: tipoEntrega === 'retiro_tienda' ? 'Confirmado' : 'Enviado',
                4: 'Entregado'
            };
        }

        function renderStatusStepper(o) {
            var labels = getStatusLabels(o.tipo_entrega);
            var actual = parseInt(o.status);
            ordenStatus = actual;
            var esRechazado = actual === 5;

            var steps = '';
            [1, 2, 3, 4].forEach(function(s) {
                var cls = '';
                if (!esRechazado) {
                    if (s < actual) cls = 'done';
                    else if (s === actual) cls = 'active';
                }
                var dot = (cls === 'done') ? '<i class="bi bi-check-lg"></i>' : s;
                steps += '<div class="status-step ' + cls + '">' +
                    '<div class="step-dot">' + dot + '</div>' +
                    '<div class="step-label">' + labels[s] + '</div>' +
                    '</div>';
            });
            document.getElementById('status-stepper').innerHTML = '<div class="status-steps">' + steps + '</div>';

            var actions = '';
            if (esRechazado) {
                actions = '<div class="status-done-msg rejected"><i class="bi bi-x-circle"></i> Pedido rechazado</div>';
            } else if (actual >= 4) {
                actions = '<div class="status-done-msg"><i class="bi bi-check-circle"></i> Pedido entregado</div>';
            } else {
                actions = '<div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">' +
                    '<button class="btn btn-success" onclick="avanzarEstado()">Marcar como ' + labels[actual + 1] + ' <i class="bi bi-arrow-right"></i></button>' +
                    '<button class="btn btn-outline-danger" onclick="rechazarEstado()"><i class="bi bi-x-lg"></i> Rechazar pedido</button>' +
                    '</div>';
            }
            document.getElementById('status-actions').innerHTML = actions;
        }

        // Avisa si el pedido quedó guardado pero el push al transportista falló.
        function avisarPush(data) {
            if (!data || !data.push || data.push.ok !== false) return;
            Swal.fire({
                icon: 'warning',
                title: 'Pedido guardado, notificación no enviada',
                text: data.push.msg || 'Revisa la configuración de notificaciones.',
                confirmButtonColor: '#2dd4a0'
            });
        }

        function enviarEstado(status, confirmMsg, transportistaId) {
            if (confirmMsg && !confirm(confirmMsg)) return;
            if (!ordenActual) return;
            var fd = new FormData();
            fd.append('action', 'cambiar_estado');
            fd.append('id', ordenActual);
            fd.append('status', status);
            if (transportistaId) fd.append('transportista_id', transportistaId);

            fetch('db/tienda_pedidos_api.php', {
                    method: 'POST',
                    body: fd
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (data.ok) {
                        cargarPedidos();
                        verDetalle(ordenActual);
                        avisarPush(data);
                    } else {
                        alert('Error: ' + data.msg);
                    }
                });
        }

        function avanzarEstado() {
            var siguiente = ordenStatus + 1;
            if (siguiente === 3 && ordenActualTipo === 'delivery') {
                elegirTransportista(function(id) {
                    enviarEstado(3, null, id);
                });
                return;
            }
            enviarEstado(siguiente);
        }

        function elegirTransportista(alConfirmar, opciones) {
            opciones = opciones || {};
            fetch('db/tienda_transportistas_api.php?action=listar')
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) {
                        Swal.fire('Error', data.msg || 'No se pudo cargar la lista', 'error');
                        return;
                    }
                    if (!data.data || !data.data.length) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Sin transportistas',
                            html: '<p style="margin:0;font-size:13px;color:#8892a0;">Agrega transportistas a tu tienda para poder asignar deliveries.</p>',
                            showCancelButton: true,
                            confirmButtonText: 'Ir a Mis transportistas',
                            cancelButtonText: 'Cerrar',
                            background: '#181b22',
                            color: '#e8edf2',
                            confirmButtonColor: '#2dd4a0'
                        }).then(function(res) {
                            if (res.isConfirmed) {
                                window.location.href = 'tienda_transportistas.php';
                            }
                        });
                        return;
                    }

                    var actual = parseInt(opciones.actual || 0);
                    var opcionesHtml = data.data.map(function(t) {
                        var extra = [];
                        if (t.cedula) extra.push('C.I. ' + escaparHtml(t.cedula));
                        if (t.telefono) extra.push(escaparHtml(t.telefono));
                        var selected = (parseInt(t.id) === actual) ? ' selected' : '';
                        return '<option value="' + parseInt(t.id) + '"' + selected + '>' + escaparHtml(t.nombre) +
                            (extra.length ? ' — ' + extra.join(' · ') : '') + '</option>';
                    }).join('');

                    Swal.fire({
                        title: opciones.titulo || 'Asignar transportista',
                        html: '<div class="transp-order-card">' +
                            '<div class="transp-order-icon"><i class="bi bi-truck"></i></div>' +
                            '<div><div class="transp-order-title">Pedido #' + parseInt(ordenActual) + '</div>' +
                            '<div class="transp-order-sub">' + (opciones.subtitulo || 'Delivery · se notificará al transportista') + '</div></div>' +
                            '</div>' +
                            '<label class="transp-field-label" for="swal-transportista">Transportista</label>' +
                            '<select id="swal-transportista" class="swal-transp-select">' + opcionesHtml + '</select>' +
                            '<p class="transp-hint-modal"><i class="bi bi-info-circle"></i> ' +
                            (opciones.hint || 'El transportista recibirá la dirección y el teléfono del cliente en su app.') + '</p>',
                        showCancelButton: true,
                        confirmButtonText: opciones.boton || '<i class="bi bi-send"></i> Asignar y enviar',
                        cancelButtonText: 'Cancelar',
                        focusConfirm: false,
                        customClass: {
                            popup: 'swal-transp-popup',
                            title: 'swal-transp-title',
                            htmlContainer: 'swal-transp-html',
                            confirmButton: 'swal-transp-confirm',
                            cancelButton: 'swal-transp-cancel'
                        },
                        preConfirm: function() {
                            var id = document.getElementById('swal-transportista').value;
                            if (!id) {
                                Swal.showValidationMessage('Selecciona un transportista');
                                return false;
                            }
                            return id;
                        }
                    }).then(function(res) {
                        if (res.isConfirmed && res.value) {
                            alConfirmar(res.value);
                        }
                    });
                })
                .catch(function() {
                    Swal.fire('Error', 'Error de conexión', 'error');
                });
        }

        function cambiarTransportista() {
            var asignando = !ordenTransportistaId;
            elegirTransportista(function(id) {
                var fd = new FormData();
                fd.append('action', 'cambiar_transportista');
                fd.append('id', ordenActual);
                fd.append('transportista_id', id);

                fetch('db/tienda_pedidos_api.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        if (!data.ok) {
                            alert('Error: ' + data.msg);
                            return;
                        }
                        cargarPedidos();
                        verDetalle(ordenActual);
                        avisarPush(data);
                    })
                    .catch(function() {
                        alert('Error de conexión');
                    });
            }, {
                titulo: asignando ? 'Asignar transportista' : 'Cambiar transportista',
                boton: asignando ? '<i class="bi bi-send"></i> Asignar' : '<i class="bi bi-check2"></i> Guardar cambio',
                actual: ordenTransportistaId,
                subtitulo: 'Delivery · el nuevo transportista recibirá el pedido',
                hint: 'El nuevo transportista recibirá la dirección y el teléfono del cliente en su app.'
            });
        }

        function reenviarNotificacion() {
            if (!ordenActual) return;
            var btn = document.getElementById('btn-notificar-transportista');
            var textoOriginal = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            var fd = new FormData();
            fd.append('action', 'notificar_transportista');
            fd.append('id', ordenActual);

            fetch('db/tienda_pedidos_api.php', {
                    method: 'POST',
                    body: fd
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                    if (!data.ok) {
                        Swal.fire('Error', data.msg || 'No se pudo notificar', 'error');
                        return;
                    }
                    if (data.push && data.push.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Notificación enviada',
                            text: 'El transportista recibió el aviso en su app.',
                            confirmButtonColor: '#2dd4a0'
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Notificación no enviada',
                            text: (data.push && data.push.msg) || 'Revisa la configuración de notificaciones.',
                            confirmButtonColor: '#2dd4a0'
                        });
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.innerHTML = textoOriginal;
                    Swal.fire('Error', 'Error de conexión', 'error');
                });
        }

        function escaparHtml(texto) {
            return String(texto == null ? '' : texto).replace(/[&<>"']/g, function(c) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c];
            });
        }

        function rechazarEstado() {
            enviarEstado(5, '¿Rechazar este pedido? El cliente será notificado.');
        }
    </script>
    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
</body>

</html>