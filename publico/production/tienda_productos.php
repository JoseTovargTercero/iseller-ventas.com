<?php
require_once('includes/requires.php');

$topnav  = topnav();
$bss_id  = $_SESSION['bss_id'];

// Verificar que el negocio tiene tienda virtual activa
$stmtT = $conexion->prepare("SELECT slug, tiene_tienda_virtual FROM negocio WHERE id = ?");
$stmtT->bind_param("i", $bss_id);
$stmtT->execute();
$tienda = $stmtT->get_result()->fetch_assoc();
$stmtT->close();

if (!$tienda || !$tienda['tiene_tienda_virtual']) {
    header("Location: tienda_onboarding.php");
    exit;
}

$slug = $tienda['slug'];

// Obtener las sucursales del negocio
$stmtS = $conexion->prepare("SELECT id, nombre FROM sucursales WHERE bss_id = ?");
$stmtS->bind_param("i", $bss_id);
$stmtS->execute();
$sucursales = $stmtS->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtS->close();

// Contadores rápidos — subquery evita duplicados por múltiples sucursales en stock
$stmtC = $conexion->prepare(
    "SELECT
        (SELECT COUNT(DISTINCT id_producto) FROM stock WHERE bss_id = ?) AS total_inventario,
        (SELECT COUNT(*) FROM negocio_productos WHERE negocio_id = ? AND estado_publicacion = 1) AS total_publicados,
        (SELECT COUNT(*) FROM negocio_productos WHERE negocio_id = ? AND foto_url IS NOT NULL) AS con_foto"
);
$stmtC->bind_param("iii", $bss_id, $bss_id, $bss_id);
$stmtC->execute();
$stats = $stmtC->get_result()->fetch_assoc();
$stmtC->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Tienda Virtual - Productos</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .tp-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .tp-stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .stat-pill {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            padding: .7rem 1.2rem;
            font-size: .85rem;
            color: var(--dash-text-muted);
        }

        .stat-pill strong {
            display: block;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dash-text);
        }

        .stat-pill.green strong {
            color: var(--dash-mint);
        }

        .stat-pill.blue strong {
            color: var(--dash-info);
        }

        .search-bar {
            display: flex;
            gap: .5rem;
            margin-bottom: 1rem;
        }

        .search-bar input,
        .search-bar select {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
        }

        .search-bar input:focus,
        .search-bar select:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.12);
            outline: none;
        }

        .search-bar input {
            flex: 1;
        }

        .tp-table-wrap {
            overflow-x: auto;
        }

        .tp-table {
            width: 100%;
            min-width: 900px;
            border-collapse: separate;
            border-spacing: 0 .5rem;
            table-layout: fixed;
        }

        .tp-table th {
            text-align: left;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--dash-text-muted);
            padding: 0 .6rem .25rem;
        }

        .tp-table td {
            background: var(--dash-card);
            border-top: 1px solid var(--dash-border);
            border-bottom: 1px solid var(--dash-border);
            padding: .6rem;
            vertical-align: middle;
        }

        .tp-table td:first-child {
            border-left: 1px solid var(--dash-border);
            border-radius: 10px 0 0 10px;
        }

        .tp-table td:last-child {
            border-right: 1px solid var(--dash-border);
            border-radius: 0 10px 10px 0;
        }

        .tp-table tr.publicado td {
            border-color: var(--dash-mint);
        }

        /* Columnas de botones: mismo ancho fijo para que todos los botones midan igual */
        .tp-table .tp-col-act {
            width: 120px;
        }

        .tp-table .tp-col-foto {
            width: 56px;
        }

        .tp-table .tp-col-suc {
            width: 190px;
        }

        .tp-table .btn {
            width: 100%;
            margin: 0;
            padding: 5px 8px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .tp-table .tp-foto-check {
            margin: 0 auto;
        }

        .tp-table td.tp-empty {
            background: transparent;
            border: none !important;
            border-radius: 0 !important;
            text-align: center;
            color: var(--dash-text-muted);
            font-size: 14px;
            padding: 2rem;
        }

        .tp-foto-check {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            cursor: default;
        }

        .tp-foto-check.has-foto {
            background: rgba(45, 212, 160, 0.12);
            color: var(--dash-mint);
        }

        .tp-foto-check.no-foto {
            background: var(--dash-bg);
            color: var(--dash-border);
            border: 1px dashed var(--dash-border);
        }

        .tp-nombre {
            font-size: .9rem;
            font-weight: 600;
            color: var(--dash-text);
            line-height: 1.25;
        }

        .tp-cod {
            font-size: .75rem;
            color: var(--dash-text-muted);
            margin-top: 2px;
        }

        .sucursal-select {
            width: 100%;
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 6px 24px 6px 10px;
            font-size: 12px;
            min-width: 130px;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%236b7280'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
        }

        .sucursal-select:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.12);
            outline: none;
        }

        .sucursal-select:disabled {
            background-color: rgba(45, 212, 160, 0.08);
            border-color: rgba(45, 212, 160, 0.3);
            color: var(--dash-mint);
            cursor: default;
            font-weight: 600;
            background-image: none;
            padding-right: 10px;
        }

        .tp-sucursal-badge {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
            background: rgba(45, 212, 160, 0.08);
            border: 1px solid rgba(45, 212, 160, 0.3);
            color: var(--dash-mint);
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .tp-sucursal-badge::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--dash-mint);
            margin-right: 6px;
            vertical-align: middle;
        }

        .tp-pagination {
            display: flex;
            gap: .4rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .tp-pagination .btn-pg {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: .4rem .8rem;
            font-size: .85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tp-pagination .btn-pg:hover {
            border-color: var(--dash-mint);
        }

        .tp-pagination .btn-pg.active {
            background: var(--dash-mint);
            color: #1a1e24;
            border-color: var(--dash-mint);
            font-weight: bold;
        }

        .toast-tp {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            background: var(--dash-card);
            color: var(--dash-text);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-size: .85rem;
            border: 1px solid var(--dash-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transition: opacity .3s;
            pointer-events: none;
            transform: translateY(10px);
        }

        .toast-tp.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-tp.success {
            border-color: rgba(45, 212, 160, 0.4);
        }

        .toast-tp.success ion-icon {
            color: var(--dash-mint);
            font-size: 1.2rem;
        }

        .toast-tp.error {
            border-color: rgba(239, 90, 111, 0.4);
        }

        .toast-tp.error ion-icon {
            color: var(--dash-danger);
            font-size: 1.2rem;
        }

        .pro-tip-banner {
            background: rgba(91, 156, 245, 0.1);
            border: 1px solid rgba(91, 156, 245, 0.2);
            border-radius: 10px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.5rem;
            font-size: .85rem;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .pro-tip-banner ion-icon {
            font-size: 1.5rem;
            color: var(--dash-info);
            flex-shrink: 0;
        }

        /* ── Foto Modal ────────────────────────────────────────────── */
        .foto-modal {
            position: relative;
            z-index: 1051;
            pointer-events: auto;
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, .35), 0 0 0 1px rgba(255, 255, 255, .04) inset;
        }

        .fm-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 1.75rem 1rem;
        }

        .fm-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .fm-product-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1e24;
            font-size: 1.3rem;
            box-shadow: 0 4px 20px rgba(45, 212, 160, .3);
        }

        .fm-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--dash-text);
        }

        .fm-subtitle {
            font-size: .8rem;
            color: var(--dash-text-muted);
            margin-top: 2px;
        }

        .fm-close {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--dash-border);
            background: transparent;
            color: var(--dash-text-muted);
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s;
        }

        .fm-close:hover {
            background: rgba(239, 90, 111, .1);
            border-color: var(--dash-danger);
            color: var(--dash-danger);
        }

        /* Tabs */
        .fm-tabs {
            display: flex;
            gap: .25rem;
            padding: 0 1.75rem;
            border-bottom: 1px solid var(--dash-border);
        }

        .fm-tab {
            padding: .7rem 1.2rem;
            font-size: .8rem;
            font-weight: 600;
            color: var(--dash-text-muted);
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .2s;
            position: relative;
            bottom: -1px;
        }

        .fm-tab:hover {
            color: var(--dash-text);
        }

        .fm-tab.active {
            color: var(--dash-mint);
            border-bottom-color: var(--dash-mint);
        }

        .fm-tab ion-icon {
            font-size: 1rem;
        }

        /* Body */
        .fm-body {
            padding: 1.25rem 1.75rem 1.75rem;
            min-height: 380px;
        }

        .fm-panel {
            display: none;
        }

        .fm-panel.active {
            display: block;
            animation: fmFadeIn .25s ease;
        }

        @keyframes fmFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Search */
        .fm-search-wrap {
            position: relative;
            margin-bottom: 1rem;
        }

        .fm-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            color: var(--dash-text-muted);
            pointer-events: none;
        }

        .fm-search-input {
            width: 100%;
            padding: 12px 80px 12px 42px;
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            font-size: .85rem;
            color: var(--dash-text);
            transition: all .25s;
        }

        .fm-search-input:focus {
            outline: none;
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, .1);
        }

        .fm-search-count {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: .7rem;
            color: var(--dash-text-muted);
            font-weight: 600;
            background: var(--dash-card);
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid var(--dash-border);
        }

        /* Results grid */
        .fm-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
            gap: .6rem;
            max-height: 300px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--dash-border) transparent;
        }

        .fm-results::-webkit-scrollbar {
            width: 5px;
        }

        .fm-results::-webkit-scrollbar-track {
            background: transparent;
        }

        .fm-results::-webkit-scrollbar-thumb {
            background: var(--dash-border);
            border-radius: 10px;
        }

        .fm-result-card {
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all .25s cubic-bezier(.4, 0, .2, 1);
            position: relative;
            background: var(--dash-bg);
        }

        .fm-result-card:hover {
            border-color: var(--dash-mint);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(45, 212, 160, .15);
        }

        .fm-result-card img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            display: block;
        }

        .fm-result-card .fm-result-name {
            padding: 6px 8px;
            font-size: .65rem;
            font-weight: 600;
            color: var(--dash-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
        }

        .fm-result-card.selected {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, .2);
        }

        .fm-result-card.selected::after {
            content: '';
            position: absolute;
            top: 6px;
            right: 6px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--dash-mint);
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%231a1e24' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
        }

        .fm-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem 1rem;
            color: var(--dash-text-muted);
            display: flex;
            flex-direction: column;
        }

        .fm-empty ion-icon {
            font-size: 2.5rem;
            margin: 0.75rem auto;
            display: block;
            opacity: .4;
        }

        .fm-empty p {
            font-size: .85rem;
        }

        /* Upload zone */
        .fm-upload-zone {
            border: 2px dashed var(--dash-border);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all .3s;
        }

        .fm-upload-zone:hover,
        .fm-upload-zone.dragover {
            border-color: var(--dash-mint);
            background: rgba(45, 212, 160, .04);
        }

        .fm-upload-icon {
            font-size: 2.8rem;
            color: var(--dash-border);
            margin-bottom: .75rem;
            transition: color .3s;
        }

        .fm-upload-zone:hover .fm-upload-icon {
            color: var(--dash-mint);
        }

        .fm-upload-title {
            font-size: .95rem;
            font-weight: 700;
            color: var(--dash-text);
            margin-bottom: .25rem;
        }

        .fm-upload-hint {
            font-size: .8rem;
            color: var(--dash-text-muted);
            margin-bottom: 1rem;
        }

        .fm-upload-btn {
            padding: 10px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
            color: #1a1e24;
            transition: all .2s;
            box-shadow: 0 4px 15px rgba(45, 212, 160, .25);
        }

        .fm-upload-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        }

        .fm-upload-formats {
            font-size: .7rem;
            color: var(--dash-text-muted);
            margin-top: .75rem;
        }

        /* Upload preview */
        .fm-upload-preview {
            text-align: center;
        }

        .fm-upload-preview img {
            max-width: 100%;
            max-height: 260px;
            border-radius: 12px;
            border: 1px solid var(--dash-border);
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
        }

        .fm-preview-actions {
            display: flex;
            gap: .5rem;
            justify-content: center;
        }

        .fm-btn-primary {
            padding: 10px 24px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
            color: #1a1e24;
            transition: all .2s;
        }

        .fm-btn-primary:hover {
            box-shadow: 0 4px 15px rgba(45, 212, 160, .3);
        }

        .fm-btn-secondary {
            padding: 10px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 600;
            background: transparent;
            border: 1px solid var(--dash-border);
            color: var(--dash-text-muted);
            transition: all .2s;
        }

        .fm-btn-secondary:hover {
            border-color: var(--dash-text);
            color: var(--dash-text);
        }

        /* Web placeholder */
        .fm-web-placeholder {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--dash-text-muted);
        }

        .fm-web-placeholder ion-icon {
            font-size: 2.5rem;
            margin-bottom: .75rem;
            display: block;
            opacity: .3;
        }

        /* fm-selected-bar */
        .fm-selected-bar {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: .75rem 1.75rem;
            border-top: 1px solid var(--dash-border);
            background: rgba(45, 212, 160, .04);
        }

        .fm-selected-bar.show {
            display: flex;
        }

        .fm-selected-info {
            font-size: .8rem;
            color: var(--dash-text);
            font-weight: 600;
        }

        .fm-selected-info span {
            color: var(--dash-mint);
        }

        .fm-apply-btn {
            padding: 10px 28px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
            color: #1a1e24;
            transition: all .2s;
            box-shadow: 0 4px 15px rgba(45, 212, 160, .25);
        }

        .fm-apply-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        }

        /* Fix z-index above #navbar (9999) */
        #modalFoto {
            z-index: 10000;
        }

        #modalFoto .modal-backdrop {
            z-index: 9999;
        }

        /* Responsive centering — Bootstrap 4 only sets margin:auto at min-width:576px */
        #modalFoto .modal-dialog {
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Móvil (WebView de la app) ───────────────────────────────── */
        @media (max-width: 768px) {

            /* La tabla de 900px pasa a ser una tarjeta por producto */
            .tp-table {
                display: block;
                min-width: 0;
                table-layout: auto;
                border-spacing: 0;
            }

            .tp-table thead {
                display: none;
            }

            .tp-table tbody {
                display: block;
            }

            .tp-table tr {
                display: grid;
                /* La primera columna es sólo la miniatura; los botones usan las 4 siguientes */
                grid-template-columns: 34px repeat(4, 1fr);
                gap: 8px 10px;
                padding: 12px;
                margin-bottom: 10px;
                border: 1px solid var(--dash-border);
                border-radius: 12px;
                background: var(--dash-card);
            }

            .tp-table tr.publicado {
                border-color: rgba(45, 212, 160, .55);
            }

            .tp-table td {
                display: block;
                padding: 0;
                background: transparent;
                border: none !important;
            }

            /* Foto en la primera columna, nombre y código en el resto */
            .tp-table td.tp-col-foto {
                grid-column: 1;
                width: auto;
            }

            .tp-table td:nth-child(2) {
                grid-column: 2 / -1;
                min-width: 0;
            }

            /* Selector de sucursal, a lo ancho */
            .tp-table td:nth-child(3) {
                grid-column: 1 / -1;
            }

            /* Publicar / Detalles / Foto / Eliminar: uno por columna */
            .tp-table td:nth-child(4) {
                grid-column: 2;
            }

            .tp-table td:nth-child(5) {
                grid-column: 3;
            }

            .tp-table td:nth-child(6) {
                grid-column: 4;
            }

            .tp-table td:nth-child(7) {
                grid-column: 5;
            }

            /* Sólo el icono, en un cuadrado: el texto no cabe en un cuarto de pantalla */
            .tp-table .btn {
                font-size: 0;
                gap: 0;
                width: 38px;
                height: 38px;
                padding: 0;
                margin: 0 auto;
            }

            .tp-table .btn ion-icon {
                font-size: 17px;
            }

            /* La paginación larga (decenas de páginas) se cambia por
               anterior / página X de N / siguiente desde el JS */
            .tp-pagination {
                margin-top: 1rem;
                gap: .5rem;
                align-items: center;
            }

            .tp-pagination .btn-pg {
                min-width: 44px;
                height: 44px;
                font-size: 1.15rem;
                padding: 0;
            }

            .tp-pagination .btn-pg:disabled {
                opacity: .35;
                cursor: default;
            }

            .tp-pg-info {
                font-size: .82rem;
                color: var(--dash-text-muted);
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
                <div class="row">
                    <div class="col-lg-12">


                        <div class="dash-panel">
                            <div class="panel-body">
                                <!-- Stats -->
                                <div class="tp-header">
                                    <div class="tp-stats">
                                        <div class="stat-pill">
                                            <strong><?= (int)$stats['total_inventario'] ?></strong>
                                            En inventario
                                        </div>
                                        <div class="stat-pill green">
                                            <strong id="stat-pub"><?= (int)$stats['total_publicados'] ?></strong>
                                            Publicados
                                        </div>
                                        <div class="stat-pill blue">
                                            <strong id="stat-foto"><?= (int)$stats['con_foto'] ?></strong>
                                            Con foto
                                        </div>
                                    </div>
                                    <a href="tienda_configuracion.php" class="btn btn-outline-secondary">
                                        <ion-icon name="arrow-back-outline"></ion-icon> Volver a Configuración
                                    </a>
                                </div>

                                <!-- Pro tip -->
                                <div class="pro-tip-banner">
                                    <ion-icon name="bulb-outline"></ion-icon>
                                    <div><strong>Pro Tip:</strong> Los productos con fotografía propia aparecen <strong>primero en el Marketplace</strong>. ¡Sube fotos para destacar tu tienda!</div>
                                </div>

                                <!-- Búsqueda -->
                                <div class="search-bar">
                                    <input type="text" id="buscador" placeholder="Buscar producto por nombre...">
                                    <select id="filtro-pub" style="max-width:180px;">
                                        <option value="">Todos</option>
                                        <option value="1">Publicados</option>
                                        <option value="0">No publicados</option>
                                    </select>
                                </div>

                                <!-- Tabla -->
                                <div class="tp-table-wrap">
                                    <table class="tp-table">
                                        <thead>
                                            <tr>
                                                <th class="tp-col-foto"></th>
                                                <th>Producto</th>
                                                <th class="tp-col-suc">Sucursal</th>
                                                <th class="tp-col-act">Publicación</th>
                                                <th class="tp-col-act">Detalles</th>
                                                <th class="tp-col-act">Foto</th>
                                                <th class="tp-col-act">Eliminar</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tp-body">
                                            <tr>
                                                <td colspan="7" class="tp-empty">Cargando productos...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginación -->
                                <div class="tp-pagination" id="tp-paginacion"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /right_col -->
        </div>
    </div>

    <!-- Toast -->
    <div class="toast-tp" id="toast-tp"></div>

    <!-- Modal Detalle -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none; border-radius:14px; overflow:hidden; background:var(--dash-card);">
                <div class="modal-header" style="border-bottom:1px solid var(--dash-border); padding:1rem 1.25rem;">
                    <h6 style="margin:0; font-weight:700; color:var(--dash-text); display:flex; align-items:center; gap:6px;">
                        <ion-icon name="information-circle-outline" style="color:var(--dash-mint); font-size:1.2rem;"></ion-icon>
                        Detalle del Producto
                    </h6>
                    <button type="button" class="btn-close" data-dismiss="modal" style="filter:invert(.6);"></button>
                </div>
                <div class="modal-body" style="padding:1.25rem;">
                    <div style="display:flex; gap:1.25rem; align-items:flex-start;">
                        <div id="det-foto" style="width:120px; height:120px; border-radius:10px; background:var(--dash-bg); border:1px solid var(--dash-border); display:flex; align-items:center; justify-content:center; overflow:hidden; flex-shrink:0;">
                            <ion-icon name="image-outline" style="font-size:2.2rem; color:var(--dash-border);"></ion-icon>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div id="det-nombre" style="font-size:1rem; font-weight:700; color:var(--dash-text); margin-bottom:.25rem;"></div>
                            <div id="det-codigo" style="font-size:.75rem; color:var(--dash-text-muted); margin-bottom:.75rem;"></div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem;">
                                <div class="det-field"><span class="det-label">Sucursal</span><span id="det-sucursal" class="det-value">—</span></div>
                                <div class="det-field"><span class="det-label">Stock</span><span id="det-stock" class="det-value">—</span></div>
                                <div class="det-field"><span class="det-label">Precio compra</span><span id="det-precio" class="det-value">—</span></div>
                                <div class="det-field"><span class="det-label">Mayor</span><span id="det-mayor" class="det-value">—</span></div>
                                <div class="det-field"><span class="det-label">Unidades/paca</span><span id="det-unidades" class="det-value">—</span></div>
                                <div class="det-field"><span class="det-label">Estado</span><span id="det-estado" class="det-value">—</span></div>
                                <div class="det-field" style="grid-column:1/-1;"><span class="det-label">Proveedor</span><span id="det-proveedor" class="det-value">—</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .det-field {
            display: flex;
            flex-direction: column;
        }

        .det-label {
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--dash-text-muted);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .det-value {
            font-size: .85rem;
            color: var(--dash-text);
            font-weight: 500;
        }

        .det-estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: .75rem;
            font-weight: 600;
        }

        .det-estado-badge.pub {
            background: rgba(45, 212, 160, 0.12);
            color: var(--dash-mint);
        }

        .det-estado-badge.no-pub {
            background: rgba(239, 90, 111, 0.1);
            color: var(--dash-danger);
        }
    </style>

    <!-- Modal: Selector de Foto -->
    <div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="foto-modal">
                <!-- Header -->
                <div class="fm-header">
                    <div class="fm-header-left">
                        <div class="fm-product-icon">
                            <ion-icon name="camera"></ion-icon>
                        </div>
                        <div>
                            <div class="fm-title" id="fm-title">Seleccionar imagen</div>
                            <div class="fm-subtitle" id="fm-subtitle">Busca, sube o selecciona una foto para tu producto</div>
                        </div>
                    </div>
                    <button type="button" class="fm-close" data-dismiss="modal">
                        <ion-icon name="close"></ion-icon>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="fm-tabs">
                    <button class="fm-tab active" data-tab="buscar" onclick="fmSwitchTab('buscar', this)">
                        <ion-icon name="search"></ion-icon> Buscar imagen
                    </button>
                    <button class="fm-tab" data-tab="subir" onclick="fmSwitchTab('subir', this)">
                        <ion-icon name="cloud-upload"></ion-icon> Subir archivo
                    </button>
                </div>

                <!-- Body -->
                <div class="fm-body">
                    <!-- Tab: Buscar -->
                    <div class="fm-panel active" id="fm-panel-buscar">
                        <div class="fm-search-wrap">
                            <ion-icon name="search" class="fm-search-icon"></ion-icon>
                            <input type="text" id="fm-search" class="fm-search-input" placeholder="Escribe el nombre del producto...">
                            <span class="fm-search-count" id="fm-count"></span>
                        </div>
                        <div class="fm-results" id="fm-results">
                            <div class="fm-empty">
                                <ion-icon name="images-outline"></ion-icon>
                                <p>Escribe para buscar imágenes de productos</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Subir -->
                    <div class="fm-panel" id="fm-panel-subir">
                        <div class="fm-upload-zone" id="fm-dropzone">
                            <input type="file" id="fm-file-input" accept="image/jpeg,image/png,image/webp" hidden>
                            <div class="fm-upload-icon">
                                <ion-icon name="cloud-upload-outline"></ion-icon>
                            </div>
                            <p class="fm-upload-title">Arrastra una imagen aquí</p>
                            <p class="fm-upload-hint">o haz clic para seleccionar</p>
                            <button class="fm-upload-btn" onclick="document.getElementById('fm-file-input').click()">
                                Seleccionar archivo
                            </button>
                            <p class="fm-upload-formats">JPEG, PNG o WebP — Máx. 1.5MB</p>
                        </div>
                        <div class="fm-upload-preview" id="fm-upload-preview" style="display:none;">
                            <img id="fm-preview-img" src="" alt="Preview">
                            <div class="fm-preview-actions">
                                <button class="fm-btn-secondary" onclick="fmClearPreview()">Cambiar</button>
                                <button class="fm-btn-primary" onclick="fmUploadPhoto()">Usar esta foto</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Web -->
                    <div class="fm-panel" id="fm-panel-web">
                        <div class="fm-web-placeholder">
                            <ion-icon name="construct-outline"></ion-icon>
                            <p class="fm-upload-title">Próximamente</p>
                            <p class="fm-upload-hint">La búsqueda web estará disponible pronto</p>
                        </div>
                    </div>
                </div>

                <!-- Selected bar -->
                <div class="fm-selected-bar" id="fm-selected-bar">
                    <div class="fm-selected-info"></div>
                    <button class="fm-apply-btn" onclick="fmApplySelected()">Aplicar imagen</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/fuse/fuse.min.js"></script>
    <script src="../vendors/select2/dist/js/select2.full.min.js"></script>
    <script>
        const SUCURSALES = <?php echo json_encode($sucursales); ?>;
        const API = 'db/tienda_productos_api.php';
        let paginaActual = 1;

        /* ── Helpers ─────────────────────────────────────────────────────── */
        function toast(msg, type = 'success') {
            const el = document.getElementById('toast-tp');
            const iconName = type === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline';
            el.innerHTML = `<ion-icon name="${iconName}"></ion-icon> <span>${msg}</span>`;
            el.className = `toast-tp show ${type}`;
            setTimeout(() => el.classList.remove('show'), 3000);
        }

        function apiPost(data) {
            const fd = new FormData();
            Object.entries(data).forEach(([k, v]) => fd.append(k, v));
            return fetch(API, {
                method: 'POST',
                body: fd
            }).then(r => r.json());
        }

        /* ── Render ──────────────────────────────────────────────────────── */
        function renderGrid(items) {
            const tbody = document.getElementById('tp-body');
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="tp-empty">No se encontraron productos.</td></tr>';
                return;
            }
            tbody.innerHTML = items.map(p => {
                const pub = p.estado_publicacion == 1;
                const hasFoto = !!p.foto_url;
                const stock = p.stock_cantidad != null ? parseInt(p.stock_cantidad) : null;

                let controlHtml = '';
                if (pub) {
                    const sucName = SUCURSALES.find(s => s.id == p.sucursal_id)?.nombre || '—';
                    controlHtml = `<span class="tp-sucursal-badge">${sucName}</span>`;
                } else {
                    controlHtml = `<select id="suc-${p.id}" class="sucursal-select">`;
                    if (SUCURSALES.length > 0) {
                        SUCURSALES.forEach(s => {
                            const sel = (p.sucursal_id == s.id) ? 'selected' : '';
                            controlHtml += `<option value="${s.id}" ${sel}>${s.nombre}</option>`;
                        });
                    } else {
                        controlHtml += `<option value="">Sin sucursales</option>`;
                    }
                    controlHtml += `</select>`;
                }

                let stockHtml = '';
                if (pub) {
                    if (stock === 0) {
                        stockHtml = ` <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(239,90,111,0.12);color:#ef5a6f;border:1px solid rgba(239,90,111,0.25);border-radius:6px;padding:2px 8px;font-size:.7rem;font-weight:600;white-space:nowrap;"><ion-icon name="alert-circle-outline" style="font-size:.85rem;"></ion-icon> Agotado</span>`;
                    } else if (stock != null) {
                        stockHtml = ` <span style="font-size:.7rem;color:var(--dash-text-muted);">Stock: ${stock}</span>`;
                    }
                }

                return `<tr class="${pub ? 'publicado' : ''}" data-id="${p.id}">
            <td class="tp-col-foto">
                <div class="tp-foto-check ${hasFoto ? 'has-foto' : 'no-foto'}" title="${hasFoto ? 'Tiene foto' : 'Sin foto'}">
                    <ion-icon name="${hasFoto ? 'image-outline' : 'camera-outline'}"></ion-icon>
                </div>
            </td>
            <td>
                <div class="tp-nombre" title="${p.nombre}">${p.nombre}</div>
                <div class="tp-cod">COD: ${p.codigo_barras || 'N/A'}${stockHtml}</div>
            </td>
            <td>${controlHtml}</td>
            <td>
                <button class="btn btn-sm btn-pub ${pub ? 'btn-outline-success' : 'btn-outline-danger'}"
                        onclick="togglePub(${p.id}, ${pub ? 0 : 1}, this)"
                        title="${pub ? 'Quitar de la tienda' : 'Publicar en la tienda'}">
                    <ion-icon name="${pub ? 'eye-off-outline' : 'eye-outline'}"></ion-icon>
                    ${pub ? 'Quitar' : 'Publicar'}
                </button>
            </td>
            <td>
                <button class="btn btn-outline-secondary btn-sm" onclick="verDetalle(${p.id})" title="Ver detalle">
                    <ion-icon name="information-circle-outline"></ion-icon> Detalles
                </button>
            </td>
            <td>
                <button class="btn btn-outline-secondary btn-sm" onclick="abrirFoto(${p.id})" title="${hasFoto ? 'Cambiar foto' : 'Subir foto'}">
                    <ion-icon name="camera-outline"></ion-icon>
                    ${hasFoto ? 'Cambiar' : 'Subir'}
                </button>
            </td>
            <td>
                ${hasFoto ? `<button class="btn btn-outline-danger btn-sm" onclick="borrarFoto(${p.id}, this)" title="Eliminar foto">
                    <ion-icon name="trash-outline"></ion-icon>
                </button>` : ''}
            </td>
        </tr>`;
            }).join('');
        }

        function renderPaginacion(paginaActual, totalPaginas) {
            const el = document.getElementById('tp-paginacion');
            if (totalPaginas <= 1) {
                el.innerHTML = '';
                return;
            }
            // En el teléfono decenas de botones ocupan media pantalla:
            // anterior / página X de N / siguiente
            if (window.innerWidth <= 768) {
                el.innerHTML = `
                    <button class="btn-pg" onclick="cargar(${Math.max(1, paginaActual - 1)})" ${paginaActual === 1 ? 'disabled' : ''} aria-label="Anterior">‹</button>
                    <span class="tp-pg-info">Página ${paginaActual} de ${totalPaginas}</span>
                    <button class="btn-pg" onclick="cargar(${Math.min(totalPaginas, paginaActual + 1)})" ${paginaActual === totalPaginas ? 'disabled' : ''} aria-label="Siguiente">›</button>`;
                return;
            }
            let html = '';
            for (let i = 1; i <= totalPaginas; i++) {
                html += `<button class="btn-pg ${i === paginaActual ? 'active' : ''}"
                         onclick="cargar(${i})">${i}</button>`;
            }
            el.innerHTML = html;
        }

        /* ── Cargar productos ────────────────────────────────────────────── */
        function cargar(pagina = 1) {
            paginaActual = pagina;
            const q = encodeURIComponent(document.getElementById('buscador').value);
            const pub = document.getElementById('filtro-pub').value;
            let url = `${API}?action=listar&page=${pagina}&q=${q}`;
            if (pub !== '') url += `&pub=${pub}`;

            fetch(url)
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) return;
                    // Filtro client-side por publicado si viene el param
                    let items = data.data;
                    if (pub !== '') items = items.filter(p => String(p.estado_publicacion) === pub);
                    renderGrid(items);
                    renderPaginacion(data.page, data.pages);
                })
                .catch(err => {
                    console.error('Error fetching data:', err);
                    toast('Error al cargar productos', 'error');
                });
        }

        /* ── Toggle publicación ─────────────────────────────────────────── */
        function togglePub(productoId, nuevoEstado, btn) {
            let sucursalId = 0;
            if (nuevoEstado === 1) {
                const sel = document.getElementById(`suc-${productoId}`);
                sucursalId = sel ? sel.value : 0;
                if (!sucursalId) {
                    toast('Debes indicar la sucursal', 'error');
                    return;
                }
            }

            apiPost({
                    action: 'toggle',
                    producto_id: productoId,
                    publicado: nuevoEstado,
                    sucursal_id: sucursalId
                })
                .then(r => {
                    if (!r.ok) {
                        toast('Error: ' + r.msg, 'error');
                        return;
                    }
                    toast(nuevoEstado ? 'Publicado en tu tienda' : 'Quitado de la tienda', 'success');
                    cargar(paginaActual);
                });
        }

        /* ── Ver detalle ──────────────────────────────────────────────────── */
        function verDetalle(id) {
            fetch(`${API}?action=detalle&id=${id}`)
                .then(r => r.json())
                .then(r => {
                    console.log('detalle response:', r);
                    if (!r.ok) {
                        toast(r.msg || 'Error', 'error');
                        return;
                    }
                    const d = r.data;
                    if (!d) {
                        toast('Sin datos', 'error');
                        return;
                    }

                    // Foto
                    const fotoEl = document.getElementById('det-foto');
                    if (d.foto_url) {
                        fotoEl.innerHTML = `<img src="${d.foto_url}" alt="${d.nombre || ''}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;"><ion-icon name="image-outline" style="font-size:2.2rem;color:var(--dash-border);"></ion-icon></div>`;
                    } else {
                        fotoEl.innerHTML = `<ion-icon name="image-outline" style="font-size:2.2rem;color:var(--dash-border);"></ion-icon>`;
                    }

                    document.getElementById('det-nombre').textContent = d.nombre || '—';
                    document.getElementById('det-codigo').textContent = d.codigo ? `COD: ${d.codigo}` : 'Sin código';
                    document.getElementById('det-sucursal').textContent = d.sucursal || '—';
                    document.getElementById('det-stock').textContent = d.stock != null ? d.stock : '—';
                    document.getElementById('det-precio').textContent = d.precio_compra != null ? `$${Number(d.precio_compra).toFixed(2)}` : '—';
                    document.getElementById('det-mayor').textContent = d.mayor == 1 ? 'Sí' : 'No';
                    document.getElementById('det-unidades').textContent = d.unidades || '—';
                    document.getElementById('det-proveedor').textContent = d.proveedor || '—';

                    const estadoEl = document.getElementById('det-estado');
                    if (d.publicado) {
                        estadoEl.innerHTML = '<span class="det-estado-badge pub"><ion-icon name="eye-outline"></ion-icon> Publicado</span>';
                    } else {
                        estadoEl.innerHTML = '<span class="det-estado-badge no-pub"><ion-icon name="eye-off-outline"></ion-icon> No publicado</span>';
                    }

                    $('#modalDetalle').modal('show');
                })
                .catch(err => {
                    console.error('detalle error:', err);
                    toast('Error al cargar detalle', 'error');
                });
        }

        /* ── Subir foto ─────────────────────────────────────────────────── */
        let fmSelectedImage = null;
        let fmProductos = [];
        let fmFuse = null;
        let fmProductoActual = null;

        // Cargar JSON de productos para búsqueda
        fetch('../../iseller_store/img_sitema/productos.json')
            .then(r => r.json())
            .then(data => {
                fmProductos = data;
                fmFuse = new Fuse(data, {
                    threshold: 0.35,
                    distance: 50,
                    includeScore: true
                });
            })
            .catch(() => {});

        function abrirFoto(productoId) {
            fmProductoActual = productoId;
            fmSelectedImage = null;
            fmClearPreview();

            // Buscar nombre del producto en la tabla
            const card = document.querySelector(`tr[data-id="${productoId}"]`);
            const nombre = card ? card.querySelector('.tp-nombre')?.textContent || '' : '';

            document.getElementById('fm-title').textContent = nombre || 'Seleccionar imagen';
            document.getElementById('fm-subtitle').textContent = 'Busca, sube o selecciona una foto para tu producto';

            // Pre-llenar búsqueda
            const searchInput = document.getElementById('fm-search');
            searchInput.value = nombre;
            fmDoSearch(nombre);

            // Reset tabs
            fmSwitchTab('buscar', document.querySelector('.fm-tab[data-tab="buscar"]'));

            $('#modalFoto').modal('show');
        }

        function fmSwitchTab(tab, btn) {
            document.querySelectorAll('.fm-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.fm-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(`fm-panel-${tab}`).classList.add('active');
        }

        function fmDoSearch(query) {
            const container = document.getElementById('fm-results');
            const countEl = document.getElementById('fm-count');

            if (!query || !fmFuse) {
                container.innerHTML = `<div class="fm-empty text-center">
            <ion-icon name="images-outline"></ion-icon>
            <p>Escribe para buscar imágenes de productos</p>
        </div>`;
                countEl.textContent = '';
                return;
            }

            const results = fmFuse.search(query);
            countEl.textContent = `${results.length} resultado${results.length !== 1 ? 's' : ''}`;

            if (results.length === 0) {
                container.innerHTML = `<div class="fm-empty">
            <ion-icon name="search-outline"></ion-icon>
            <p>No se encontraron imágenes para "<strong>${query}</strong>"</p>
        </div>`;
                return;
            }

            container.innerHTML = results.map(r => {
                const nombre = r.item;
                const fileName = nombre.replace(/\s+/g, '-').toLowerCase();
                return `<div class="fm-result-card" onclick="fmSelectImage('${fileName}', this)" title="${nombre}">
            <img src="../../iseller_store/img_sitema/${fileName}.webp" alt="${nombre}"
                 onerror="this.parentElement.style.display='none'">
            <div class="fm-result-name">${nombre}</div>
        </div>`;
            }).join('');
        }

        function fmSelectImage(fileName, el) {
            document.querySelectorAll('.fm-result-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            fmSelectedImage = fileName;

            const bar = document.querySelector('.fm-selected-bar');
            bar.classList.add('show');
            bar.querySelector('.fm-selected-info').innerHTML = `Seleccionado: <span>${fileName.replace(/-/g, ' ')}</span>`;
        }

        function fmApplySelected() {
            if (!fmSelectedImage || !fmProductoActual) return;
            toast('Aplicando imagen…', 'success');

            fetch(`../../iseller_store/img_sitema/${fmSelectedImage}.webp`)
                .then(r => {
                    if (!r.ok) throw new Error('Imagen no encontrada');
                    return r.blob();
                })
                .then(blob => {
                    const file = new File([blob], `${fmSelectedImage}.webp`, {
                        type: 'image/webp'
                    });
                    const fd = new FormData();
                    fd.append('action', 'upload_foto');
                    fd.append('producto_id', fmProductoActual);
                    fd.append('foto', file);

                    return fetch(API, {
                        method: 'POST',
                        body: fd
                    });
                })
                .then(r => r.json())
                .then(r => {
                    if (!r.ok) {
                        toast('Error: ' + r.msg, 'error');
                        return;
                    }
                    toast('Foto aplicada con éxito', 'success');
                    $('#modalFoto').modal('hide');
                    cargar(paginaActual);
                })
                .catch(() => toast('Error al aplicar imagen', 'error'));
        }

        // Upload zone
        function fmClearPreview() {
            document.getElementById('fm-upload-preview').style.display = 'none';
            document.getElementById('fm-dropzone').style.display = '';
            document.getElementById('fm-file-input').value = '';
        }

        document.getElementById('fm-search')?.addEventListener('input', function() {
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => fmDoSearch(this.value), 200);
        });

        document.getElementById('fm-file-input')?.addEventListener('change', function() {
            if (!this.files[0]) return;
            const file = this.files[0];
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('fm-preview-img').src = e.target.result;
                document.getElementById('fm-dropzone').style.display = 'none';
                document.getElementById('fm-upload-preview').style.display = '';
            };
            reader.readAsDataURL(file);
        });

        function fmUploadPhoto() {
            const input = document.getElementById('fm-file-input');
            if (!input.files[0] || !fmProductoActual) return;

            const fd = new FormData();
            fd.append('action', 'upload_foto');
            fd.append('producto_id', fmProductoActual);
            fd.append('foto', input.files[0]);
            toast('Subiendo foto…', 'success');

            fetch(API, {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(r => {
                    if (!r.ok) {
                        toast('Error: ' + r.msg, 'error');
                        return;
                    }
                    toast('Foto guardada con éxito', 'success');
                    $('#modalFoto').modal('hide');
                    cargar(paginaActual);
                });
        }

        // Drag & drop
        const dropzone = document.getElementById('fm-dropzone');
        if (dropzone) {
            dropzone.addEventListener('dragover', e => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
            dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
            dropzone.addEventListener('drop', e => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    document.getElementById('fm-file-input').files = e.dataTransfer.files;
                    document.getElementById('fm-file-input').dispatchEvent(new Event('change'));
                }
            });
        }

        /* ── Borrar foto ─────────────────────────────────────────────────── */
        function borrarFoto(productoId) {
            if (!confirm('¿Eliminar la foto personalizada de este producto?')) return;
            apiPost({
                    action: 'delete_foto',
                    producto_id: productoId
                })
                .then(r => {
                    if (!r.ok) {
                        toast('Error', 'error');
                        return;
                    }
                    toast('Foto eliminada', 'success');
                    cargar(paginaActual);
                });
        }

        /* ── Búsqueda con debounce ──────────────────────────────────────── */
        let debounce;
        document.getElementById('buscador').addEventListener('input', () => {
            clearTimeout(debounce);
            debounce = setTimeout(() => cargar(1), 350);
        });
        document.getElementById('filtro-pub').addEventListener('change', () => cargar(1));

        // Carga inicial
        cargar(1);
    </script>
    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
</body>

</html>