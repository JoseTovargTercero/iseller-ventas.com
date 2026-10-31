<?php
require_once('core/db.php');
require_once('core/session.php');

// Verificar que el usuario esté logueado
requireLogin();

$userName = getUserName();
?>
<!DOCTYPE html>
<html lang='es'>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - iSeller Store</title>
    <meta name="csrf-token" content="<?= htmlspecialchars(getCSRFToken()) ?>">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo-tienda.png">
    <link rel="apple-touch-icon" href="assets/img/logo-tienda.png">

    <!-- Bootstrap CSS -->
    <link href="../publico/vendors/bootstrap5/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">
    <!-- Chat System CSS -->
    <link rel="stylesheet" href="assets/css/chat.css">

    <style>
        :root {
            --pf-ink: #1f2933;
            --pf-muted: #6b7280;
            --pf-line: #e5e7eb;
            --pf-soft: #f5f7f6;
            --pf-green: #4e8f63;
            --pf-green-soft: #6faf7a;
            --pf-shadow: 0 20px 45px -28px rgba(31, 41, 51, .22);
        }

        body {
            background: var(--pf-soft);
            color: var(--pf-ink);
        }

        .hide {
            display: none !important;
        }

        /* ===== Navbar: se usa el topbar compartido (includes/navbar.php) ===== */

        /* ===== Espacio de página (offset navbar fijo) ===== */
        .pf-hero {
            padding: 104px 0 6px;
        }

        @media (max-width: 991px) {
            .pf-hero {
                padding-top: 96px;
            }
        }

        @media (max-width: 480px) {
            .pf-hero {
                padding-top: 140px;
            }
        }

        /* ===== Tarjeta de miembro ===== */
        .pf-card {
            background: #fff;
            border: 1px solid var(--pf-line);
            border-radius: 24px;
            padding: 30px 34px;
            box-shadow: var(--pf-shadow);
        }

        .pf-card-main {
            display: flex;
            align-items: center;
            gap: 26px;
            flex-wrap: wrap;
        }

        .pf-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }

        .pf-avatar {
            width: 116px;
            height: 116px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pf-green-soft), var(--pf-green));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            font-size: 2.6rem;
            font-weight: 600;
            color: #fff;
            overflow: hidden;
            box-shadow: 0 0 0 5px #fff, 0 0 0 6px rgba(78, 143, 99, .25);
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pf-avatar-btn {
            position: absolute;
            right: 2px;
            bottom: 2px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 3px solid #fff;
            background: var(--pf-green);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: transform .2s ease;
        }

        .pf-avatar-btn:hover {
            transform: scale(1.08);
        }

        .pf-info {
            flex: 1 1 320px;
            min-width: 0;
        }

        .pf-info-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .pf-name {
            font-family: inherit;
            font-weight: 600;
            font-size: clamp(1.5rem, 2.4vw, 2rem);
            letter-spacing: -.01em;
            margin: 0 0 10px;
            color: var(--pf-ink);
        }

        .pf-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .pf-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 6px 12px;
            border-radius: 999px;
        }

        .pf-badge-green {
            background: rgba(78, 143, 99, .1);
            color: var(--pf-green);
            border: 1px solid rgba(78, 143, 99, .22);
        }

        .pf-badge-line {
            background: #fff;
            color: var(--pf-muted);
            border: 1px solid var(--pf-line);
        }

        .pf-badge-line i {
            color: var(--pf-green);
        }

        .pf-bio {
            color: var(--pf-muted);
            font-size: .95rem;
            line-height: 1.6;
            max-width: 60ch;
            margin: 0 0 16px;
        }

        .pf-contacts {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
        }

        .pf-contact {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .85rem;
            color: var(--pf-muted);
            background: var(--pf-soft);
            border: 1px solid var(--pf-line);
            border-radius: 999px;
            padding: 7px 14px;
        }

        .pf-contact i {
            color: var(--pf-green);
            font-size: .95rem;
        }

        /* ===== Stats ===== */
        .pf-stat {
            background: #fff;
            border: 1px solid var(--pf-line);
            border-radius: 18px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            height: 100%;
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .pf-stat:hover {
            transform: translateY(-2px);
            box-shadow: var(--pf-shadow);
        }

        .pf-stat-ico {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .pf-stat-ico.pf-blue {
            background: rgba(78, 143, 99, .12);
            color: var(--pf-green);
        }

        .pf-stat-v {
            font-family: inherit;
            font-size: 1.6rem;
            font-weight: 600;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .pf-stat-k {
            font-size: .74rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--pf-muted);
            margin-top: 4px;
        }

        /* ===== Tabs segmentados ===== */
        .pf-tabs {
            display: inline-flex;
            gap: 4px;
            background: #fff;
            border: 1px solid var(--pf-line);
            border-radius: 999px;
            padding: 5px;
            margin: 22px 0 24px;
            flex-wrap: wrap;
            list-style: none;
        }

        .pf-tabs .nav-item {
            display: inline-flex;
        }

        .pf-tabs .nav-link {
            border: 0 !important;
            background: transparent;
            color: var(--pf-muted);
            font-weight: 600;
            font-size: .86rem;
            padding: 9px 18px;
            border-radius: 999px;
            transition: all .2s ease;
        }

        .pf-tabs .nav-link i {
            color: currentColor;
        }

        .pf-tabs .nav-link:hover {
            color: var(--pf-ink);
        }

        .pf-tabs .nav-link.active {
            background: var(--primary-dark);
            color: #fff;
        }

        /* ===== Paneles ===== */
        .pf-panel {
            background: #fff;
            border: 1px solid var(--pf-line);
            border-radius: 22px;
            padding: 28px 30px;
            box-shadow: 0 1px 2px rgba(16, 40, 28, .04);
        }

        .pf-panel-title {
            font-family: inherit;
            font-weight: 600;
            font-size: 1.35rem;
            margin: 0;
        }

        .pf-panel-sub {
            color: var(--pf-muted);
            font-size: .88rem;
            margin: 4px 0 0;
        }

        .pf-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            padding: 6px 12px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .pf-pill.ok {
            background: rgba(78, 143, 99, .12);
            color: var(--pf-green);
        }

        .pf-pill.muted {
            background: #eef2ef;
            color: var(--pf-muted);
        }

        /* ===================================================================
           HISTORIAL DE COMPRAS — zona premium
           =================================================================== */
        .oh-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .oh-head h2 {
            font-family: inherit;
            font-weight: 600;
            font-size: 1.6rem;
            margin: 0;
        }

        .oh-head .oh-k {
            display: block;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--pf-green);
            margin-bottom: 6px;
        }

        .oh-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .oh-filter {
            border: 1px solid var(--pf-line);
            background: #fff;
            color: var(--pf-muted);
            font-weight: 600;
            font-size: .82rem;
            padding: 8px 16px;
            border-radius: 999px;
            cursor: pointer;
            transition: all .2s ease;
        }

        .oh-filter:hover {
            border-color: var(--pf-green);
            color: var(--pf-green);
        }

        .oh-filter.is-active {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #fff;
        }

        .oh-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .oh-card {
            --st: var(--pf-green);
            background: #fff;
            border: 1px solid var(--pf-line);
            border-radius: 18px;
            overflow: hidden;
            transition: box-shadow .3s ease;
            animation: ohIn .5s both;
            animation-delay: calc(var(--i, 0) * 60ms);
        }

        @keyframes ohIn {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
        }

        .oh-card:hover {
            box-shadow: var(--pf-shadow);
        }

        .oh-card.is-pending { --st: #8a939c; }
        .oh-card.is-prep { --st: var(--primary-color); }
        .oh-card.is-ship { --st: var(--primary-dark); }
        .oh-card.is-done { --st: var(--primary-dark); }
        .oh-card.is-rej { --st: var(--danger-color); }

        /* Cabecera: fecha del pedido y número */
        .oh-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 20px;
            background: var(--pf-soft);
            border-bottom: 1px solid var(--pf-line);
        }

        .oh-date {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .84rem;
            color: var(--pf-muted);
        }

        .oh-num {
            font-size: .8rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--pf-muted);
        }

        .oh-num b {
            color: var(--pf-ink);
        }

        /* Cuerpo: miniatura, datos de la compra, tienda y acciones */
        .oh-card-body {
            display: grid;
            grid-template-columns: 96px minmax(0, 1fr) 180px 200px;
            gap: 22px;
            padding: 20px;
            align-items: start;
        }

        .oh-thumb {
            width: 96px;
            height: 96px;
            padding: 6px;
            border: 1px solid var(--pf-line);
            border-radius: 12px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
        }

        .oh-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .oh-thumb i {
            font-size: 1.5rem;
            color: #cbd5d1;
        }

        .oh-main {
            min-width: 0;
        }

        .oh-state {
            display: block;
            font-size: .8rem;
            font-weight: 700;
            color: var(--st);
            margin-bottom: 2px;
        }

        .oh-phrase {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.35;
            color: var(--pf-ink);
            margin: 0 0 8px;
        }

        .oh-product {
            display: block;
            font-size: .86rem;
            color: var(--pf-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .oh-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-size: .82rem;
            color: var(--pf-muted);
            margin-top: 4px;
        }

        .oh-side {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }

        .oh-store {
            font-size: .66rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--pf-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .oh-store-link {
            font-size: .84rem;
            font-weight: 600;
            color: var(--pf-green);
            text-decoration: none;
            width: fit-content;
        }

        .oh-store-link:hover {
            text-decoration: underline;
        }

        .oh-btns {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .oh-saved {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--pf-green);
            background: rgba(78, 143, 99, .1);
            border: 1px solid rgba(78, 143, 99, .25);
            border-radius: 999px;
            padding: 4px 10px;
        }

        /* Compra rechazada: la frase del estado va en rojo */
        .oh-card.is-rej .oh-phrase {
            color: var(--danger-color);
        }

        .oh-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            font-size: .82rem;
            font-weight: 600;
            padding: 9px 18px;
            border-radius: 999px;
            border: 1px solid var(--pf-line);
            background: #fff;
            color: var(--pf-ink);
            cursor: pointer;
            text-decoration: none;
            transition: all .2s ease;
        }

        .oh-btn i {
            transition: transform .2s ease;
        }

        .oh-btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
        }

        .oh-btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #fff;
        }

        .oh-btn-primary:hover i {
            transform: translateX(3px);
        }

        .oh-btn-rate {
            background: #fff8e1;
            border-color: #f0d37a;
            color: #8a6100;
        }

        .oh-btn-rate:hover {
            background: #ffefc2;
            border-color: #e0bd4f;
        }

        .oh-rated {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .78rem;
            font-weight: 600;
            color: var(--pf-green);
            padding: 9px 4px;
        }

        .rep-rate-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .rep-rate-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 18px 12px;
            border: 2px solid var(--pf-line);
            border-radius: 16px;
            background: #fff;
            color: var(--pf-ink);
            font-size: .86rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
        }

        .rep-rate-btn i {
            font-size: 1.35rem;
        }

        .rep-rate-btn.yes:hover {
            border-color: var(--pf-green);
            background: #f2fbf5;
            color: var(--pf-green);
        }

        .rep-rate-btn.no:hover {
            border-color: #e11d48;
            background: #fff1f3;
            color: #e11d48;
        }

        .rep-rate-btn:disabled {
            opacity: .55;
            cursor: wait;
        }

        .oh-btn-wa:hover {
            background: rgba(37, 211, 102, .12);
            border-color: rgba(37, 211, 102, .5);
            color: #128c4a;
        }

        .oh-empty {
            text-align: center;
            padding: 64px 20px;
            border: 1px dashed var(--pf-line);
            border-radius: 18px;
            color: var(--pf-muted);
        }

        .oh-empty .oh-mono {
            font-family: inherit;
            font-size: 2.4rem;
            font-weight: 600;
            color: var(--pf-green);
            opacity: .5;
        }

        .oh-empty p {
            margin: 12px 0 0;
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        /* ===== Info / direcciones ===== */
        .pf-field label {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--pf-muted);
            margin-bottom: 6px;
        }

        .pf-field .form-control {
            border: 1px solid var(--pf-line);
            background: var(--pf-soft);
            border-radius: 12px;
            padding: 11px 14px;
            font-size: .92rem;
            color: var(--pf-ink);
        }

        .pf-field .form-control[readonly] {
            background: var(--pf-soft);
        }

        .pf-addr {
            background: #fff;
            border: 1px solid var(--pf-line);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 12px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .pf-addr:hover {
            box-shadow: var(--pf-shadow);
        }

        .pf-addr.is-main {
            border-color: rgba(78, 143, 99, .45);
            background: linear-gradient(90deg, #f6fbf8, #fff);
        }

        .pf-addr-title {
            font-weight: 700;
            font-size: .95rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pf-addr-title i {
            color: var(--pf-green);
        }

        .pf-addr-txt {
            color: var(--pf-ink);
            font-size: .9rem;
            margin: 8px 0 0;
        }

        .pf-addr-ref {
            color: var(--pf-muted);
            font-size: .82rem;
            margin: 4px 0 0;
        }

        /* ===== Referidos ===== */
        .pf-referral {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 22px;
            padding: 26px 28px;
            color: #fff;
            margin-bottom: 24px;
        }

        .pf-referral h4 {
            font-family: inherit;
            font-weight: 600;
            margin: 0 0 6px;
        }

        .pf-referral p {
            color: rgba(255, 255, 255, .72);
            font-size: .9rem;
            margin: 0 0 16px;
        }

        .pf-ref-box {
            display: flex;
            gap: 10px;
            align-items: center;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 14px;
            padding: 10px 14px;
            flex-wrap: wrap;
        }

        .pf-ref-code {
            font-family: inherit;
            font-weight: 700;
            font-size: .88rem;
            flex: 1 1 200px;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ===== Modal detalle ===== */
        .pf-modal .modal-content {
            border: 0;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 30px 60px -30px rgba(16, 40, 28, .5);
        }

        .pf-modal .modal-header {
            border-bottom: 1px solid var(--pf-line);
            padding: 20px 24px;
        }

        .pf-modal .modal-title {
            font-family: inherit;
            font-weight: 600;
        }

        .pf-receipt {
            background: var(--pf-soft);
            border: 1px solid var(--pf-line);
            border-radius: 16px;
            padding: 18px 20px;
        }

        .pf-receipt h6 {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--pf-muted);
        }

        .pf-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 1px solid var(--pf-line);
        }

        .pf-total-row .pf-total-v {
            font-family: inherit;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--pf-green);
            font-variant-numeric: tabular-nums;
        }

        /* ===== Skeleton ===== */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e6e9e7 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 10px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* ===== Responsive ===== */
        @media (max-width: 991px) {
            /* En tablet/móvil: miniatura + datos arriba, tienda y botones debajo */
            .oh-card-body {
                grid-template-columns: 84px minmax(0, 1fr);
                gap: 16px;
            }

            .oh-thumb {
                width: 84px;
                height: 84px;
            }

            .oh-side,
            .oh-btns {
                grid-column: 1 / -1;
            }

            .oh-btns {
                flex-direction: row;
            }
        }

        @media (max-width: 767px) {
            .pf-card { padding: 24px 22px; }
            .pf-avatar { width: 96px; height: 96px; font-size: 2.1rem; }
            .pf-panel { padding: 22px 18px; }
            .oh-card-body { padding: 16px; }
            .oh-phrase { font-size: .94rem; }
        }

        @media (max-width: 575px) {
            .oh-card-body {
                grid-template-columns: 68px minmax(0, 1fr);
                gap: 14px;
            }

            .oh-thumb {
                width: 68px;
                height: 68px;
            }

            .oh-btns {
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .oh-card, .skeleton {
                animation: none !important;
            }
        }
    </style>
</head>

<body data-user-logged-in="true">
    <!-- Topbar compartido -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Cabecera de miembro -->
    <div class="pf-hero" id="profile-header-section">
        <div class="container">
            <div class="pf-card profile-card">
                <div class="pf-card-main">
                    <div class="pf-avatar-wrap">
                        <div class="pf-avatar" id="profile-avatar">
                            <div class="skeleton" style="width: 100%; height: 100%; border-radius: 50%;"></div>
                        </div>
                        <button class="pf-avatar-btn" onclick="document.getElementById('avatar-input').click()" title="Cambiar foto de perfil">
                            <i class="bi bi-camera-fill"></i>
                        </button>
                    </div>

                    <input type="file" id="avatar-input" accept="image/*" style="display: none;">

                    <div class="pf-info">
                        <div class="pf-info-top">
                            <div>
                                <h1 class="pf-name" id="profile-full-name">
                                    <span class="skeleton" style="width: 220px; height: 28px; display: inline-block;"></span>
                                </h1>
                                <div class="pf-badges">
                                    <span class="pf-badge pf-badge-line"><i class="bi bi-patch-check-fill"></i> Verificado</span>
                                </div>
                            </div>
                            <button class="hide pf-edit" onclick="Notiflix.Report.info('Editar Perfil', 'Próximamente: Podrás editar tus datos directamente desde aquí.', 'Entendido')">
                                <i class="bi bi-pencil"></i> Editar perfil
                            </button>
                        </div>

                        <p class="pf-bio" id="user-bio-placeholder">
                            Cliente de iSeller Store. Disfrutando de las ofertas y promociones de nuestra tienda.
                        </p>

                        <div class="pf-contacts">
                            <span class="pf-contact"><i class="bi bi-envelope"></i><span id="profile-email">Cargando...</span></span>
                            <span class="pf-contact"><i class="bi bi-geo-alt"></i><span id="profile-location">Venezuela</span></span>
                            <span class="pf-contact"><i class="bi bi-calendar3"></i>Se unió en <span id="member-since-date">···</span></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <main class="container pb-5">
        <div class="row">
            <div class="col-12">

                <!-- Banner de referidos -->
                <div class="pf-referral d-none" id="referral-section">
                    <h4><span id="profile-name"></span> ¡Invita a tus amigos!</h4>
                    <p>Comparte tu enlace con amigos y ayúdalos a descubrir iSeller Store.</p>
                    <div class="pf-ref-box">
                        <span class="pf-ref-code" id="ref-code-display">Cargando...</span>
                        <input type="text" id="ref-link-input" readonly style="position:absolute;opacity:0;height:0;width:0;">
                        <button class="btn btn-light btn-sm fw-bold" id="btn-copy-ref"><i class="bi bi-clipboard me-1"></i> Copiar</button>
                        <button class="btn btn-share btn-sm fw-bold text-white" id="btn-share-ref" style="background:var(--primary-color);"><i class="bi bi-share-fill"></i></button>
                    </div>
                </div>

                <!-- (tarjetas de estadísticas eliminadas: el dato "Compras" ya está en el resumen del historial) -->

                <!-- Tabs -->
                <ul class="nav pf-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                            <i class="bi bi-clock-history me-2"></i>Mis compras
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">
                            <i class="bi bi-person-fill me-2"></i>Mi información
                        </button>
                    </li>
                </ul>

                <!-- Contenido de tabs -->
                <div class="tab-content" id="profileTabContent">
                    <!-- Historial de compras -->
                    <div class="tab-pane fade show active" id="orders" role="tabpanel">
                        <div class="pf-panel">
                            <div class="oh-head">
                                <div>
                                    <span class="oh-k">Mi cuenta</span>
                                    <h2>Historial de Compras</h2>
                                </div>
                            </div>
                            <div id="orders-list">
                                <div class="skeleton mb-3" style="width: 100%; height: 96px;"></div>
                                <div class="skeleton mb-3" style="width: 100%; height: 96px;"></div>
                                <div class="skeleton mb-3" style="width: 100%; height: 96px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Información personal -->
                    <div class="tab-pane fade" id="info" role="tabpanel">
                        <div class="pf-panel">
                            <h3 class="pf-panel-title">Mis datos</h3>
                            <p class="pf-panel-sub">Información asociada a tu cuenta.</p>

                            <div class="row g-3 mt-1">
                                <div class="col-md-6 pf-field">
                                    <label for="info-nombre">Nombre completo</label>
                                    <input type="text" class="form-control" id="info-nombre" readonly>
                                </div>
                                <div class="col-md-6 pf-field">
                                    <label for="info-email">Correo electrónico</label>
                                    <input type="email" class="form-control" id="info-email" readonly>
                                </div>
                                <div class="col-md-6 pf-field">
                                    <label for="info-fecha">Miembro desde</label>
                                    <input type="text" class="form-control" id="info-fecha" readonly>
                                </div>
                            </div>

                            <h4 class="pf-panel-title mt-4 mb-3" style="font-size: 1.1rem;"><i class="bi bi-geo-alt-fill me-2"></i>Direcciones guardadas</h4>
                            <div id="addresses-list">
                                <div class="skeleton" style="width: 100%; height: 96px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Modal Detalle Orden -->
    <div class="modal fade pf-modal" id="modalDetalleOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleLabel">Detalle de la compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modal-loader" class="text-center py-5">
                        <div class="spinner-border text-success" role="status"><span class="visually-hidden">Cargando...</span></div>
                        <p class="mt-2 text-muted">Cargando detalles...</p>
                    </div>

                    <div id="modal-content" style="display: none;">
                        <div class="mb-4">
                            <h5 class="fw-bold mb-1">Pedido #<span id="detail-id"></span></h5>
                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <span id="detail-date"></span></span>
                        </div>

                        <div class="pf-receipt mb-4">
                            <h6 class="mb-3"><i class="bi bi-truck me-2"></i>Información de entrega</h6>
                            <p class="mb-1"><strong>Tipo:</strong> <span id="detail-delivery-type"></span></p>
                            <p class="mb-1"><strong>Dirección:</strong> <span id="detail-address"></span></p>
                            <p class="mb-0"><strong>Ref. de pago:</strong> <span id="detail-payment-ref"></span></p>
                        </div>

                        <h6 class="pf-panel-sub mb-3">Productos</h6>
                        <div class="table-responsive mb-4">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="detail-items-list"></tbody>
                            </table>
                        </div>

                        <div class="row justify-content-end">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span class="fw-semibold">$<span id="detail-subtotal">0.00</span></span></div>
                                <div class="d-flex justify-content-between"><span class="text-muted">Envío</span><span class="fw-semibold">$<span id="detail-shipping">0.00</span></span></div>
                                <div class="pf-total-row">
                                    <span class="fw-bold">Total</span>
                                    <span class="pf-total-v">$<span id="detail-total">0.00</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="modal-error" class="text-center py-5 d-none">
                        <i class="bi bi-exclamation-circle text-danger fs-1"></i>
                        <p class="mt-2 text-danger">Error al cargar la compra</p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Calificar Tienda -->
    <div class="modal fade pf-modal" id="modalCalificarTienda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Calificar tienda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-1">Tu opinión ayuda a otros compradores.</p>
                    <h6 class="fw-bold mb-3" id="calificar-tienda-nombre"></h6>

                    <div class="rep-rate-options mb-3">
                        <button type="button" class="rep-rate-btn yes calificar-btn" onclick="enviarCalificacion(1)">
                            <i class="bi bi-hand-thumbs-up"></i>
                            <span>Sí, la recomiendo</span>
                        </button>
                        <button type="button" class="rep-rate-btn no calificar-btn" onclick="enviarCalificacion(0)">
                            <i class="bi bi-hand-thumbs-down"></i>
                            <span>No la recomiendo</span>
                        </button>
                    </div>

                    <label class="form-label small text-muted" for="calificar-comentario">Comentario (opcional)</label>
                    <textarea id="calificar-comentario" class="form-control" rows="3" maxlength="500" placeholder="¿Cómo fue tu experiencia?"></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="../publico/vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-aio-3.2.8.min.js"></script>

    <script>
        // Fetch profile data
        async function loadProfileData() {
            try {
                const res = await fetch('api/perfil_data.php');
                const data = await res.json();

                if (!data.success) {
                    Notiflix.Notify.failure('Error al cargar datos: ' + data.message);
                    return;
                }

                // Avatar
                const avatarContainer = document.getElementById('profile-avatar');
                if (data.user.foto) {
                    avatarContainer.innerHTML = `<img src="assets/img/profiles/${data.user.foto}" class="avatar-img" id="avatar-preview">`;
                } else {
                    const initials = data.user.nombre.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                    avatarContainer.innerHTML = initials;
                }

                // Avatar del navbar
                const navUserBtn = document.getElementById('nav-user-btn');
                if (navUserBtn) {
                    if (data.user.foto) {
                        navUserBtn.innerHTML = `<img src="assets/img/profiles/${data.user.foto}" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%;">`;
                    } else {
                        navUserBtn.innerHTML = `<i class="bi bi-person"></i>`;
                    }
                }

                document.getElementById('profile-full-name').textContent = data.user.nombre;
                document.getElementById('profile-name').textContent = data.user.nombre;
                document.getElementById('profile-email').textContent = data.user.email;
                document.getElementById('member-since-date').textContent = new Date(data.user.created_at).toLocaleDateString('es-ES', {
                    month: 'long',
                    year: 'numeric'
                });

                // Medallón eliminado (puntos/niveles desactivados)

                if (data.addresses && data.addresses.length > 0) {
                    const primary = data.addresses.find(a => a.es_principal == 1) || data.addresses[0];
                    document.getElementById('profile-location').textContent = primary.direccion.split(',')[0];
                }

                // Referidos
                const refCode = data.user.referral_code;
                const refLink = `${window.location.origin}/?ref=${refCode}`;

                document.getElementById('ref-code-display').textContent = refLink;
                document.getElementById('ref-link-input').value = refLink;

                document.getElementById('btn-copy-ref').onclick = () => {
                    const whatsappText =
                        `Hola, te recomiendo comprar en iSeller Store 🛒 Encuentra productos de tiendas de tu localidad y recibe tu pedido en casa: 👉 ${refLink}`;

                    navigator.clipboard.writeText(whatsappText).then(() => {
                        const btn = document.getElementById('btn-copy-ref');
                        const originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                        setTimeout(() => { btn.innerHTML = originalHtml; }, 2000);
                    });
                };
                document.getElementById('btn-share-ref').onclick = () => {
                    if (navigator.share) {
                        navigator.share({
                            title: 'Únete a iSeller Store',
                            text: `Hola, te recomiendo comprar en iSeller Store 🛒 Encuentra productos de tiendas de tu localidad y recibe tu pedido en casa:`,
                            url: refLink
                        });
                    } else {
                        document.getElementById('btn-copy-ref').click();
                    }
                };

                renderOrders(data.orders);

                // Datos personales
                document.getElementById('info-nombre').value = data.user.nombre;
                document.getElementById('info-email').value = data.user.email;
                document.getElementById('info-fecha').value = new Date(data.user.created_at).toLocaleDateString('es-ES');

                renderAddresses(data.addresses);

                // Subida de avatar
                document.getElementById('avatar-input').onchange = async function(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('image', file);

                    try {
                        Notiflix.Loading.standard('Subiendo imagen...');
                        const res = await fetch('api/upload_profile_image.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await res.json();
                        Notiflix.Loading.remove();

                        if (result.success) {
                            Notiflix.Notify.success('Foto de perfil actualizada');
                            loadProfileData();
                        } else {
                            Notiflix.Notify.failure(result.message || 'Error al subir imagen');
                        }
                    } catch (err) {
                        Notiflix.Loading.remove();
                        Notiflix.Notify.failure('Error de red');
                    }
                };

            } catch (err) {
                console.error('ERROR COMPLETO:', err.message || err);
            }
        }

        /* ===================== Historial de Compras ===================== */
        let ALL_ORDERS = [];
        let ORDER_FILTER = 'all';

        const OH_STATUS = {
            pendiente: { label: 'Pendiente', cls: 'is-pending' },
            en_revision: { label: 'En preparación', cls: 'is-prep' },
            enviada: { label: 'Enviado', cls: 'is-ship' },
            entregada: { label: 'Entregado', cls: 'is-done' },
            rechazada: { label: 'Rechazado', cls: 'is-rej' }
        };
        const OH_GROUPS = {
            all: () => true,
            en_curso: o => ['pendiente', 'en_revision', 'enviada'].includes(o.estado),
            entregada: o => o.estado === 'entregada',
            rechazada: o => o.estado === 'rechazada'
        };

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : s;
            return d.innerHTML;
        }

        function renderOrders(orders) {
            ALL_ORDERS = Array.isArray(orders) ? orders : [];
            paintOrders();
        }

        function paintOrders() {
            const container = document.getElementById('orders-list');
            if (!container) return;

            if (ALL_ORDERS.length === 0) {
                container.innerHTML = `
                    <div class="oh-empty">
                        <div class="oh-mono">iS</div>
                        <p>Aún no hay compras registradas</p>
                    </div>
                `;
                return;
            }

            // valor_compra ya incluye el envío, por eso no se suma de nuevo
            const filters = [
                ['all', 'Todas'],
                ['en_curso', 'En curso'],
                ['entregada', 'Entregadas'],
                ['rechazada', 'Rechazadas']
            ];

            const list = ALL_ORDERS.filter(OH_GROUPS[ORDER_FILTER] || OH_GROUPS.all);

            let html = `
                <div class="oh-filters">
                    ${filters.map(f => `<button type="button" class="oh-filter ${ORDER_FILTER === f[0] ? 'is-active' : ''}" data-filter="${f[0]}">${f[1]}</button>`).join('')}
                </div>
            `;

            if (list.length === 0) {
                html += `<div class="oh-empty"><div class="oh-mono">iS</div><p>No hay compras en esta categoría</p></div>`;
            } else {
                html += `<div class="oh-list">${list.map((o, idx) => orderCard(o, idx)).join('')}</div>`;
            }

            container.innerHTML = html;
            container.querySelectorAll('.oh-filter').forEach(btn => {
                btn.addEventListener('click', () => {
                    ORDER_FILTER = btn.dataset.filter;
                    paintOrders();
                });
            });
        }

        /* Frase que resume el estado de la compra */
        const OH_PHRASE = {
            pendiente: 'Contacta con la tienda para concretar tu compra',
            en_revision: 'Tu pedido está en preparación',
            enviada: 'Tu pedido está en camino',
            entregada: 'Ya recibiste tu compra'
        };

        function fechaOrden(f) {
            const d = new Date(f);
            if (isNaN(d.getTime())) return '';
            const opciones = { day: 'numeric', month: 'long' };
            if (d.getFullYear() !== new Date().getFullYear()) opciones.year = 'numeric';
            return d.toLocaleDateString('es-ES', opciones);
        }

        function orderCard(o, idx) {
            const esRetiro = o.tipo_entrega !== 'delivery';
            // El estado 6 es interno del panel; el cliente ve "Transportista asignado".
            const asignado = Number(o.orden_status) === 6 && !esRetiro;
            const st = asignado
                ? { label: 'Transportista asignado', cls: 'is-ship' }
                : (OH_STATUS[o.estado] || { label: o.estado, cls: '' });
            const label = (!asignado && o.estado === 'enviada' && esRetiro) ? 'Listo para retirar' : st.label;

            const base = (parseFloat(o.valor_compra) || 0); // valor_compra ya incluye el envío
            const ahorro = parseFloat(o.ahorrado) || 0;
            const final = base - ahorro;

            const unidades = parseInt(o.total_unidades, 10) || 0;
            const productos = parseInt(o.total_items, 10) || 0;
            const unidadTxt = unidades > 0 ? `${unidades} unidad${unidades === 1 ? '' : 'es'}` : '';
            const productosTxt = productos > 1 ? `${productos} productos` : '';

            const frase = asignado
                ? 'Tu pedido ya tiene un transportista asignado'
                : (o.estado === 'rechazada'
                    ? (o.motivo_rechazo ? escapeHtml(o.motivo_rechazo) : 'Esta compra fue rechazada. Contáctanos para más información.')
                    : (o.estado === 'enviada' && esRetiro
                        ? 'Tu pedido está listo para retirar en tienda'
                        : (OH_PHRASE[o.estado] || 'Compra registrada')));

            const puedeCalificar = o.estado === 'entregada' && Number(o.orden_status) === 4 && o.orden_id && !Number(o.ya_calificada);
            const yaCalificada = o.estado === 'entregada' && Number(o.orden_status) === 4 && Number(o.ya_calificada);

            return `
                <article class="oh-card ${st.cls}" style="--i:${idx}">
                    <header class="oh-card-head">
                        <span class="oh-date"><i class="bi bi-calendar3"></i> ${fechaOrden(o.fecha)}</span>
                        <span class="oh-num">Pedido <b>#${o.id}</b></span>
                    </header>

                    <div class="oh-card-body">
                        <div class="oh-thumb" onclick="verDetalleOrden(${o.id})" title="Ver compra">
                            ${o.primer_producto_id
                                ? `<img src="assets/img/stock/${parseInt(o.primer_producto_id, 10)}.png" alt="" onerror="this.onerror=null;this.src='assets/img/no-images.png'">`
                                : `<i class="bi bi-box-seam"></i>`}
                        </div>

                        <div class="oh-main">
                            <span class="oh-state">${label}</span>
                            <p class="oh-phrase">${frase}</p>
                            ${o.primer_producto_nombre ? `<span class="oh-product">${escapeHtml(o.primer_producto_nombre)}</span>` : ''}
                            <span class="oh-meta">
                                <span>${[unidadTxt, productosTxt].filter(Boolean).join(' · ')}${unidadTxt || productosTxt ? ' · ' : ''}$${final.toFixed(2)}</span>
                                ${ahorro > 0 ? `<span class="oh-saved"><i class="bi bi-tag-fill"></i> Ahorraste $${ahorro.toFixed(2)}</span>` : ''}
                            </span>
                        </div>

                        <div class="oh-side">
                            ${o.negocio_nombre ? `<span class="oh-store">${escapeHtml(o.negocio_nombre)}</span>` : ''}
                            <a class="oh-store-link" href="chat.php${o.conversacion_id ? '?conv=' + parseInt(o.conversacion_id, 10) : ''}">Ver mensajes</a>
                        </div>

                        <div class="oh-btns">
                            <button type="button" class="oh-btn oh-btn-primary" onclick="verDetalleOrden(${o.id})">
                                Ver compra${puedeCalificar || yaCalificada ? '' : ' <i class="bi bi-arrow-right"></i>'}
                            </button>
                            ${puedeCalificar ? `<button type="button" class="oh-btn oh-btn-rate" onclick="abrirCalificar(${Number(o.orden_id)})"><i class="bi bi-stars"></i> Calificar tienda</button>` : ''}
                            ${yaCalificada ? `<span class="oh-rated"><i class="bi bi-patch-check-fill"></i> Ya calificaste</span>` : ''}
                            ${!['entregada', 'rechazada'].includes(o.estado) ? `
                            <a class="oh-btn oh-btn-wa" target="_blank" rel="noopener" href="https://wa.me/584160679095?text=Necesito%20ayuda%20con%20mi%20compra%20N%C2%BA%20${o.id}">
                                <i class="bi bi-whatsapp"></i> Soporte
                            </a>` : ''}
                        </div>
                    </div>
                </article>
            `;
        }

        /* ===================== Calificar tienda ===================== */
        let calificarOrdenId = 0;
        let calificarModal = null;
        let calificarEnviando = false;

        function abrirCalificar(ordenId) {
            const o = ALL_ORDERS.find(x => Number(x.orden_id) === Number(ordenId));
            if (!o) return;

            calificarOrdenId = Number(ordenId);
            document.getElementById('calificar-tienda-nombre').textContent = o.negocio_nombre || 'esta tienda';
            document.getElementById('calificar-comentario').value = '';
            document.querySelectorAll('.calificar-btn').forEach(b => b.disabled = false);

            if (!calificarModal) {
                calificarModal = new bootstrap.Modal(document.getElementById('modalCalificarTienda'));
            }
            calificarModal.show();
        }

        async function enviarCalificacion(recomienda) {
            if (calificarEnviando || !calificarOrdenId) return;
            calificarEnviando = true;
            document.querySelectorAll('.calificar-btn').forEach(b => b.disabled = true);

            try {
                const fd = new FormData();
                fd.append('orden_id', calificarOrdenId);
                fd.append('recomienda', recomienda ? '1' : '0');
                fd.append('comentario', document.getElementById('calificar-comentario').value.trim());

                const res = await fetch('api/calificar_tienda.php', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: fd
                });
                const data = await res.json();

                if (!data.success) throw new Error(data.message || 'No se pudo registrar');

                const o = ALL_ORDERS.find(x => Number(x.orden_id) === calificarOrdenId);
                if (o) o.ya_calificada = 1;
                if (calificarModal) calificarModal.hide();
                paintOrders();
                Notiflix.Notify.success('¡Gracias por calificar la tienda!');
            } catch (err) {
                Notiflix.Notify.failure(err.message || 'Error de red');
            } finally {
                calificarEnviando = false;
                document.querySelectorAll('.calificar-btn').forEach(b => b.disabled = false);
            }
        }

        function renderAddresses(addresses) {
            const container = document.getElementById('addresses-list');

            if (!addresses || addresses.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-geo-alt" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No tienes direcciones guardadas</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = addresses.map(a => {
                const isMain = a.es_principal == 1 || a.predeterminada == 1;
                const badge = isMain ? '<span class="pf-pill ok">Principal</span>' : '';
                const titulo = a.nombre_receptor ? escapeHtml(a.nombre_receptor) : ('Dirección ' + a.id);

                return `
                    <div class="pf-addr ${isMain ? 'is-main' : ''}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1 min-w-0">
                                <p class="pf-addr-title"><i class="bi bi-geo-alt-fill"></i>${titulo}</p>
                                <p class="pf-addr-txt">${escapeHtml(a.direccion)}</p>
                                ${a.referencia ? `<p class="pf-addr-ref"><i class="bi bi-info-circle me-1"></i>${escapeHtml(a.referencia)}</p>` : ''}
                            </div>
                            <div>${badge}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function verDetalleOrden(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleOrden'));
            modal.show();

            document.getElementById('modal-loader').classList.remove('d-none');
            document.getElementById('modal-content').style.display = 'none';
            document.getElementById('modal-error').classList.add('d-none');

            try {
                const res = await fetch(`api/orden_detalle.php?id=${id}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.message);

                const o = data.orden;

                document.getElementById('detail-id').textContent = o.id;
                document.getElementById('detail-date').textContent = new Date(o.fecha).toLocaleDateString();

                document.getElementById('detail-delivery-type').textContent = o.tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro en Tienda';
                document.getElementById('detail-address').textContent = o.direccion;
                document.getElementById('detail-payment-ref').textContent = o.pago_ref || 'N/A';

                const list = document.getElementById('detail-items-list');
                list.innerHTML = data.items.map(item => `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="assets/img/stock/${item.id}.png" class="rounded" width="50" height="50" style="object-fit: cover;" onerror="this.src='assets/img/no-images.png'">
                                <div><h6 class="mb-0 small fw-bold">${escapeHtml(item.nombre)}</h6></div>
                            </div>
                        </td>
                        <td class="text-center">${item.cantidad}</td>
                        <td class="text-end">$${item.precio.toFixed(2)}</td>
                        <td class="text-end fw-bold">$${item.subtotal.toFixed(2)}</td>
                    </tr>
                `).join('');

                document.getElementById('detail-subtotal').textContent = o.subtotal.toFixed(2);
                document.getElementById('detail-shipping').textContent = o.envio.toFixed(2);
                document.getElementById('detail-total').textContent = o.total.toFixed(2);

                document.getElementById('modal-loader').classList.add('d-none');
                document.getElementById('modal-content').style.display = 'block';

            } catch (error) {
                console.error(error);
                document.getElementById('modal-loader').classList.add('d-none');
                document.getElementById('modal-error').classList.remove('d-none');
            }
        }

        document.addEventListener('DOMContentLoaded', loadProfileData);

        // Activar tab y scroll según parámetro GET ?tab=
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const tabParam = params.get('tab');
            if (tabParam) {
                const tabEl = document.getElementById(tabParam + '-tab');
                if (tabEl) {
                    // Modo enfocado: ocultar cabecera de miembro, referidos, stats y tabs
                    const profileCard = document.querySelector('#profile-header-section .profile-card');
                    if (profileCard) profileCard.style.display = 'none';

                    ['referral-section', 'stats-section', 'profileTabs'].forEach(function(id) {
                        const el = document.getElementById(id);
                        if (el) el.style.display = 'none';
                    });

                    const tabContentBox = document.getElementById('profileTabContent');
                    if (tabContentBox) tabContentBox.style.paddingTop = '4rem';

                    const bsTab = new bootstrap.Tab(tabEl);
                    bsTab.show();

                    setTimeout(function() {
                        const tabContent = document.getElementById('profileTabContent');
                        if (tabContent) {
                            tabContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }, 200);
                }
            }
        });
    </script>

    <!-- Chat Component (Simplified) -->
    <?php include 'assets/components/chat-button.php'; ?>
    <script src="assets/js/chat-simple.js"></script>
</body>

</html>
