<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
  $topnav = topnav();

  if ($_SESSION["validate"] != "ok") {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
  }

  // Lista de tutoriales oficiales iSeller Academy
  $tutoriales = [
    [
      'id' => 'lvw7DftrvQ0',
      'title' => 'Nuevos productos y gestión de catálogo',
      'category' => 'productos',
      'category_name' => 'Catálogo & Productos',
      'category_icon' => 'cube-outline',
      'duration' => '03:40',
      'duration_sec' => 220,
      'badge' => 'Básico',
      'desc' => 'Aprende a registrar nuevos productos, cargar fotos, definir precios en multimoneda y organizar categorías para tu catálogo comercial.',
      'step' => 'Lección 1'
    ],
    [
      'id' => '0G0YRhPiPnE',
      'title' => 'Tasas de cambio y multimoneda en tiempo real',
      'category' => 'tasas',
      'category_name' => 'Tasas de Cambio',
      'category_icon' => 'cash-outline',
      'duration' => '02:15',
      'duration_sec' => 135,
      'badge' => 'Esencial',
      'desc' => 'Configuración de divisas activas, sincronización diaria con la tasa oficial BCV, dólar y peso colombiano con conversión automática.',
      'step' => 'Lección 2'
    ],
    [
      'id' => '7dJqrOZTbuY',
      'title' => 'Registro de ventas y facturación en caja',
      'category' => 'ventas',
      'category_name' => 'Ventas & Caja',
      'category_icon' => 'cart-outline',
      'duration' => '04:10',
      'duration_sec' => 250,
      'badge' => 'Diario',
      'desc' => 'Cómo cobrar ventas rápidamente, seleccionar múltiples métodos de pago (efectivo, pago móvil, tarjeta) e imprimir tickets de despacho.',
      'step' => 'Lección 3'
    ],
    [
      'id' => 'KQdipbZEEYw',
      'title' => 'Registro de compras y control de stock',
      'category' => 'compras',
      'category_name' => 'Compras & Stock',
      'category_icon' => 'file-tray-stacked-outline',
      'duration' => '03:25',
      'duration_sec' => 205,
      'badge' => 'Inventario',
      'desc' => 'Ingreso de compras a proveedores, actualización automática de existencias en almacén y cálculo de costos por unidad.',
      'step' => 'Lección 4'
    ],
    [
      'id' => 'SpNZEsTxUFE',
      'title' => 'Módulo de gastos operativos y caja chica',
      'category' => 'gastos',
      'category_name' => 'Gastos & Finanzas',
      'category_icon' => 'wallet-outline',
      'duration' => '02:50',
      'duration_sec' => 170,
      'badge' => 'Finanzas',
      'desc' => 'Registro de egresos operativos, clasificación por conceptos y control de flujo de efectivo para mantener un balance financiero exacto.',
      'step' => 'Lección 5'
    ]
  ];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <title>iSeller Academy — Tutoriales en Video</title>
  <?php require_once('includes/headers.php'); ?>
  
  <!-- Tipografía premium para diseño Awwwards -->
  <link href="../vendors/fonts/fonts.css" rel="stylesheet">

  <style>
    /* ==========================================================================
       YOUTUBE AWWWARDS DESIGN SYSTEM — iSeller Studio
       ========================================================================== */
    :root {
      --yt-bg: #0d0f12;
      --yt-surface: #14181f;
      --yt-card: #181d26;
      --yt-card-hover: #1f2530;
      --yt-border: rgba(255, 255, 255, 0.08);
      --yt-border-hover: rgba(45, 212, 160, 0.4);
      --yt-mint: #2dd4a0;
      --yt-mint-glow: rgba(45, 212, 160, 0.25);
      --yt-mint-dim: rgba(45, 212, 160, 0.1);
      --yt-red: #ff334b;
      --yt-text: #f1f5f9;
      --yt-text-muted: #94a3b8;
      --yt-text-subtle: #64748b;
      --yt-radius-sm: 8px;
      --yt-radius-md: 12px;
      --yt-radius-lg: 16px;
      --yt-radius-xl: 20px;
      --yt-font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
      --yt-mono: 'JetBrains Mono', monospace;
      --yt-ease: cubic-bezier(0.2, 0.9, 0.3, 1);
    }

    body {
      font-family: var(--yt-font) !important;
      background-color: var(--yt-bg) !important;
      color: var(--yt-text) !important;
      -webkit-font-smoothing: antialiased;
    }

    .right_col {
      background: var(--yt-bg) !important;
      min-height: 100vh;
      padding: 24px 32px 100px !important;
    }

    @media (max-width: 768px) {
      .right_col {
        padding: 16px 14px 80px !important;
      }
    }

    /* ── ACADEMY HERO HEADER ────────────────────────────────────────────────── */
    .academy-hero {
      position: relative;
      background: linear-gradient(135deg, rgba(24, 29, 38, 0.9) 0%, rgba(18, 22, 29, 0.95) 100%);
      border: 1px solid var(--yt-border);
      border-radius: var(--yt-radius-xl);
      padding: 28px 32px;
      margin-bottom: 24px;
      overflow: hidden;
      backdrop-filter: blur(12px);
    }

    .academy-hero::before {
      content: '';
      position: absolute;
      top: -80px;
      right: -80px;
      width: 280px;
      height: 280px;
      background: radial-gradient(circle, rgba(45, 212, 160, 0.15) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
      filter: blur(30px);
    }

    .academy-hero::after {
      content: '';
      position: absolute;
      bottom: -60px;
      left: 10%;
      width: 200px;
      height: 200px;
      background: radial-gradient(circle, rgba(255, 51, 75, 0.08) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
      filter: blur(40px);
    }

    .hero-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 18px;
    }

    .academy-badge-wrap {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(45, 212, 160, 0.08);
      border: 1px solid rgba(45, 212, 160, 0.25);
      border-radius: 99px;
      padding: 6px 14px;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      color: var(--yt-mint);
      text-transform: uppercase;
    }

    .pulse-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--yt-mint);
      box-shadow: 0 0 0 0 rgba(45, 212, 160, 0.7);
      animation: pulseDot 2s infinite;
    }

    @keyframes pulseDot {
      0% { box-shadow: 0 0 0 0 rgba(45, 212, 160, 0.7); }
      70% { box-shadow: 0 0 0 8px rgba(45, 212, 160, 0); }
      100% { box-shadow: 0 0 0 0 rgba(45, 212, 160, 0); }
    }

    .stats-pills {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .stat-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--yt-border);
      border-radius: 99px;
      padding: 5px 12px;
      font-size: 0.76rem;
      color: var(--yt-text-muted);
      font-weight: 600;
    }

    .stat-pill i {
      color: var(--yt-mint);
      font-size: 0.85rem;
    }

    .stat-pill.stat-watched {
      background: rgba(45, 212, 160, 0.06);
      border-color: rgba(45, 212, 160, 0.2);
      color: var(--yt-text);
    }

    .hero-content {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      flex-wrap: wrap;
      gap: 24px;
    }

    .hero-text h1 {
      font-size: 1.65rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin: 0 0 8px;
      color: #ffffff;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .hero-text h1 .yt-logo-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 27px;
      background: rgba(255, 51, 75, 0.06);
      border: 2px solid #ff334b;
      border-radius: 9px;
      color: #ff334b;
      font-size: 1.05rem;
      padding-left: 2px;
      box-shadow: 0 0 14px rgba(255, 51, 75, 0.15);
      transition: all 0.25s var(--yt-ease);
      flex-shrink: 0;
    }

    .hero-text h1 .yt-logo-icon:hover {
      background: rgba(255, 51, 75, 0.14);
      border-color: #ff4d63;
      color: #ff4d63;
      box-shadow: 0 0 20px rgba(255, 51, 75, 0.3);
      transform: scale(1.05);
    }

    .hero-text p {
      color: var(--yt-text-muted);
      font-size: 0.9rem;
      margin: 0;
      max-width: 600px;
      line-height: 1.5;
    }

    /* ── SEARCH BAR (YOUTUBE STYLE) ────────────────────────────────────────── */
    .yt-search-box {
      position: relative;
      width: 100%;
      max-width: 360px;
    }

    .yt-search-input-wrap {
      position: relative;
      display: flex;
      align-items: center;
      background: #0f1217;
      border: 1px solid var(--yt-border);
      border-radius: 99px;
      padding: 2px 8px 2px 14px;
      transition: all 0.25s var(--yt-ease);
    }

    .yt-search-input-wrap:focus-within {
      border-color: var(--yt-mint);
      box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.15);
      background: #141820;
    }

    .yt-search-icon {
      color: var(--yt-text-subtle);
      font-size: 1rem;
      margin-right: 8px;
      flex-shrink: 0;
    }

    .yt-search-input {
      background: transparent;
      border: none;
      outline: none;
      color: #fff;
      font-size: 0.85rem;
      width: 100%;
      padding: 8px 0;
      font-family: inherit;
    }

    .yt-search-input::placeholder {
      color: var(--yt-text-subtle);
      font-size: 0.82rem;
    }

    .yt-search-shortcut {
      background: rgba(255, 255, 255, 0.08);
      border-radius: 6px;
      padding: 2px 7px;
      font-size: 0.7rem;
      color: var(--yt-text-subtle);
      font-family: var(--yt-mono);
      pointer-events: none;
      flex-shrink: 0;
    }

    .yt-search-clear {
      background: transparent;
      border: none;
      color: var(--yt-text-muted);
      cursor: pointer;
      padding: 4px;
      font-size: 0.9rem;
      display: none;
    }

    .yt-search-clear:hover {
      color: #fff;
    }

    /* ── FILTER CHIPS (YOUTUBE CHIPS RIBBON) ───────────────────────────────── */
    .filter-ribbon-wrapper {
      position: relative;
      margin-bottom: 28px;
    }

    .filter-chips-scroll {
      display: flex;
      align-items: center;
      gap: 10px;
      overflow-x: auto;
      padding: 4px 2px 8px;
      scrollbar-width: none;
      -webkit-overflow-scrolling: touch;
      scroll-behavior: smooth;
    }

    .filter-chips-scroll::-webkit-scrollbar {
      display: none;
    }

    .chip-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: var(--yt-surface);
      border: 1px solid var(--yt-border);
      border-radius: 99px;
      padding: 8px 16px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--yt-text-muted);
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.2s var(--yt-ease);
      user-select: none;
      flex-shrink: 0;
    }

    .chip-btn ion-icon {
      font-size: 0.95rem;
      color: var(--yt-text-subtle);
      transition: color 0.2s;
    }

    .chip-btn:hover {
      background: var(--yt-card-hover);
      color: #fff;
      border-color: rgba(255, 255, 255, 0.16);
      transform: translateY(-1px);
    }

    .chip-btn.active {
      background: #ffffff;
      color: #0f1217;
      border-color: #ffffff;
      font-weight: 700;
      box-shadow: 0 4px 14px rgba(255, 255, 255, 0.15);
    }

    .chip-btn.active ion-icon {
      color: #0f1217;
    }

    .chip-count {
      font-size: 0.7rem;
      background: rgba(255, 255, 255, 0.1);
      padding: 1px 6px;
      border-radius: 99px;
      font-weight: 700;
    }

    .chip-btn.active .chip-count {
      background: rgba(15, 18, 23, 0.12);
      color: #0f1217;
    }

    /* ── YOUTUBE CINEMA / THEATER STAGE ────────────────────────────────────── */
    .cinema-stage {
      display: none;
      position: relative;
      margin-bottom: 34px;
      animation: cinemaEnter 0.4s var(--yt-ease);
    }

    .cinema-stage.active {
      display: block;
    }

    @keyframes cinemaEnter {
      from {
        opacity: 0;
        transform: translateY(-16px) scale(0.99);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .cinema-ambient-glow {
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 50% 35%, rgba(45, 212, 160, 0.22) 0%, rgba(24, 29, 38, 0) 70%);
      filter: blur(50px);
      border-radius: var(--yt-radius-xl);
      z-index: 0;
      pointer-events: none;
      transition: all 0.5s ease;
    }

    .cinema-card {
      position: relative;
      z-index: 1;
      background: var(--yt-surface);
      border: 1px solid rgba(45, 212, 160, 0.3);
      border-radius: var(--yt-radius-xl);
      overflow: hidden;
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
    }

    .cinema-player-wrap {
      position: relative;
      width: 100%;
      padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
      background: #000;
    }

    .cinema-player-wrap iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border: 0;
    }

    .cinema-details-bar {
      padding: 20px 26px 24px;
      background: linear-gradient(180deg, var(--yt-surface) 0%, #11141a 100%);
      border-top: 1px solid var(--yt-border);
    }

    .cinema-header-meta {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
      flex-wrap: wrap;
    }

    .cinema-tag {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 3px 10px;
      border-radius: 99px;
      background: rgba(45, 212, 160, 0.12);
      color: var(--yt-mint);
      border: 1px solid rgba(45, 212, 160, 0.25);
    }

    .cinema-duration {
      font-size: 0.75rem;
      color: var(--yt-text-muted);
      font-family: var(--yt-mono);
    }

    .cinema-title {
      font-size: 1.3rem;
      font-weight: 800;
      color: #ffffff;
      margin: 0 0 14px;
      letter-spacing: -0.01em;
    }

    .cinema-actions-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.06);
    }

    .cinema-channel-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .channel-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2dd4a0 0%, #10b981 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #0f1217;
      font-weight: 800;
      font-size: 1.1rem;
      box-shadow: 0 2px 8px rgba(45, 212, 160, 0.3);
      flex-shrink: 0;
    }

    .channel-text {
      line-height: 1.3;
    }

    .channel-name {
      font-size: 0.92rem;
      font-weight: 700;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .channel-name i {
      color: var(--yt-mint);
      font-size: 0.88rem;
    }

    .channel-desc {
      font-size: 0.78rem;
      color: var(--yt-text-muted);
    }

    .cinema-buttons {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .btn-cinema {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 16px;
      border-radius: 99px;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s var(--yt-ease);
      border: 1px solid var(--yt-border);
      background: rgba(255, 255, 255, 0.05);
      color: var(--yt-text);
      text-decoration: none;
    }

    .btn-cinema:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border-color: rgba(255, 255, 255, 0.2);
    }

    .btn-cinema.btn-watched.active {
      background: rgba(45, 212, 160, 0.15);
      border-color: rgba(45, 212, 160, 0.4);
      color: var(--yt-mint);
    }

    .btn-cinema.btn-close-cinema {
      background: transparent;
      color: var(--yt-text-muted);
    }

    .btn-cinema.btn-close-cinema:hover {
      background: rgba(239, 90, 111, 0.1);
      color: var(--yt-red);
      border-color: rgba(239, 90, 111, 0.3);
    }

    /* ── VIDEO GRID (YOUTUBE FEED STYLE) ───────────────────────────────────── */
    .yt-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: 22px;
    }

    @media (min-width: 1400px) {
      .yt-grid {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 26px;
      }
    }

    .yt-card {
      background: var(--yt-card);
      border: 1px solid var(--yt-border);
      border-radius: var(--yt-radius-lg);
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.25s var(--yt-ease), border-color 0.25s var(--yt-ease), box-shadow 0.25s var(--yt-ease);
      position: relative;
      display: flex;
      flex-direction: column;
    }

    .yt-card:hover {
      border-color: var(--yt-border-hover);
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
    }

    .yt-card.now-playing {
      border-color: var(--yt-mint);
      box-shadow: 0 0 0 1px var(--yt-mint), 0 8px 28px rgba(45, 212, 160, 0.2);
    }

    /* Thumbnail Box */
    .yt-thumb-box {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      background: #0f1217;
      overflow: hidden;
    }

    .yt-thumb-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.4s var(--yt-ease);
    }

    .yt-card:hover .yt-thumb-img {
      transform: scale(1.05);
    }

    /* Duration Tag */
    .yt-duration-badge {
      position: absolute;
      bottom: 8px;
      right: 8px;
      background: rgba(0, 0, 0, 0.82);
      backdrop-filter: blur(8px);
      color: #ffffff;
      font-size: 0.72rem;
      font-weight: 700;
      font-family: var(--yt-mono);
      padding: 3px 7px;
      border-radius: 6px;
      letter-spacing: 0.02em;
      border: 1px solid rgba(255, 255, 255, 0.1);
      z-index: 2;
    }

    /* Category Pill on top of thumb */
    .yt-cat-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      background: rgba(15, 18, 23, 0.85);
      backdrop-filter: blur(8px);
      color: var(--yt-mint);
      border: 1px solid rgba(45, 212, 160, 0.3);
      font-size: 0.7rem;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 99px;
      z-index: 2;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    /* Watched Status Indicator */
    .yt-watched-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(16, 185, 129, 0.9);
      color: #fff;
      font-size: 0.68rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 99px;
      z-index: 2;
      display: none;
      align-items: center;
      gap: 4px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .yt-card.is-watched .yt-watched-badge {
      display: inline-flex;
    }

    /* YouTube Watched Red/Mint Line */
    .yt-watched-progress {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 3px;
      width: 0%;
      background: var(--yt-mint);
      z-index: 3;
      transition: width 0.3s ease;
    }

    .yt-card.is-watched .yt-watched-progress {
      width: 100%;
    }

    /* Play Button Hover Overlay */
    .yt-play-overlay {
      position: absolute;
      inset: 0;
      background: rgba(10, 13, 18, 0.45);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.25s var(--yt-ease);
      z-index: 1;
    }

    .yt-play-btn-circle {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: rgba(45, 212, 160, 0.92);
      color: #0f1217;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      box-shadow: 0 4px 20px rgba(45, 212, 160, 0.5);
      transform: scale(0.8);
      transition: transform 0.25s var(--yt-ease);
      padding-left: 3px; /* visual center for play icon */
    }

    .yt-card:hover .yt-play-overlay {
      opacity: 1;
    }

    .yt-card:hover .yt-play-btn-circle {
      transform: scale(1);
    }

    /* Card Details Body */
    .yt-card-body {
      padding: 16px 16px 18px;
      display: flex;
      gap: 12px;
      flex: 1;
    }

    .yt-card-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(45, 212, 160, 0.12);
      border: 1px solid rgba(45, 212, 160, 0.25);
      color: var(--yt-mint);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
      margin-top: 2px;
    }

    .yt-card-text {
      flex: 1;
      min-width: 0;
    }

    .yt-card-title {
      font-size: 0.95rem;
      font-weight: 700;
      line-height: 1.35;
      color: var(--yt-text);
      margin: 0 0 6px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      transition: color 0.2s;
    }

    .yt-card:hover .yt-card-title {
      color: var(--yt-mint);
    }

    .yt-card-meta {
      font-size: 0.78rem;
      color: var(--yt-text-muted);
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .yt-card-meta .meta-dot {
      width: 3px;
      height: 3px;
      border-radius: 50%;
      background: var(--yt-text-subtle);
    }

    .yt-card-meta i.verified {
      color: var(--yt-mint);
      font-size: 0.78rem;
    }

    .yt-card-desc {
      font-size: 0.8rem;
      color: var(--yt-text-subtle);
      line-height: 1.4;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin: 0;
    }

    /* ── EMPTY STATE ───────────────────────────────────────────────────────── */
    .yt-empty-state {
      display: none;
      text-align: center;
      padding: 60px 20px;
      background: var(--yt-surface);
      border: 1px dashed var(--yt-border);
      border-radius: var(--yt-radius-xl);
      grid-column: 1 / -1;
    }

    .yt-empty-icon {
      width: 64px;
      height: 64px;
      margin: 0 auto 16px;
      background: rgba(255, 255, 255, 0.04);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      color: var(--yt-text-muted);
    }

    .yt-empty-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 6px;
    }

    .yt-empty-desc {
      font-size: 0.85rem;
      color: var(--yt-text-muted);
      max-width: 400px;
      margin: 0 auto 20px;
    }

    /* Toast Notification */
    .yt-toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #1e2430;
      border: 1px solid rgba(45, 212, 160, 0.4);
      color: #fff;
      padding: 12px 20px;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
      z-index: 9999;
      display: flex;
      align-items: center;
      gap: 10px;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s var(--yt-ease);
    }

    .yt-toast.show {
      transform: translateY(0);
      opacity: 1;
    }
  </style>
</head>

<body class="nav-md">
  <div class="container body">
    <div class="main_container">
      <?php echo $menu ?>
      <?php echo $topnav ?>

      <div class="right_col" role="main">
        <div id="nombre_pagina" class="d-none"></div>

        <!-- ═══ ACADEMY HERO HEADER (YOUTUBE STUDIO) ═══ -->
        <div class="academy-hero">
          <div class="hero-top-row">
            <div class="academy-badge-wrap">
              <span class="pulse-dot"></span>
              <span>iSeller Academy • Video Guías HD</span>
            </div>

            <div class="stats-pills">
              <div class="stat-pill">
                <i class="bi bi-collection-play-fill"></i>
                <span><?= count($tutoriales) ?> Lecciones</span>
              </div>
              <div class="stat-pill">
                <i class="bi bi-clock-history"></i>
                <span>~16 min en total</span>
              </div>
              <div class="stat-pill stat-watched">
                <i class="bi bi-check-circle-fill"></i>
                <span id="watchedCountLabel">0 / <?= count($tutoriales) ?> completados</span>
              </div>
            </div>
          </div>

          <div class="hero-content">
            <div class="hero-text">
              <h1>
                <span class="yt-logo-icon"><i class="bi bi-play-fill"></i></span>
                Centro de Aprendizaje iSeller
              </h1>
              <p>Domina cada función del punto de venta con tutoriales concisos y prácticos. Haz clic en cualquier video para reproducir en el reproductor integrado.</p>
            </div>

            <!-- YouTube Search Bar -->
            <div class="yt-search-box">
              <div class="yt-search-input-wrap">
                <i class="bi bi-search yt-search-icon"></i>
                <input type="text" id="tutorialSearch" class="yt-search-input" placeholder="Buscar tutorial por tema..." autocomplete="off">
                <button type="button" id="searchClear" class="yt-search-clear" title="Limpiar"><i class="bi bi-x-lg"></i></button>
                <span class="yt-search-shortcut">/</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ YOUTUBE FILTER CHIPS RIBBON ═══ -->
        <div class="filter-ribbon-wrapper">
          <div class="filter-chips-scroll" id="filterChips">
            <button type="button" class="chip-btn active" data-filter="all">
              <ion-icon name="apps-outline"></ion-icon>
              <span>Todos</span>
              <span class="chip-count"><?= count($tutoriales) ?></span>
            </button>
            <button type="button" class="chip-btn" data-filter="productos">
              <ion-icon name="cube-outline"></ion-icon>
              <span>Catálogo & Productos</span>
            </button>
            <button type="button" class="chip-btn" data-filter="tasas">
              <ion-icon name="cash-outline"></ion-icon>
              <span>Tasas de Cambio</span>
            </button>
            <button type="button" class="chip-btn" data-filter="ventas">
              <ion-icon name="cart-outline"></ion-icon>
              <span>Ventas & Caja</span>
            </button>
            <button type="button" class="chip-btn" data-filter="compras">
              <ion-icon name="file-tray-stacked-outline"></ion-icon>
              <span>Compras & Stock</span>
            </button>
            <button type="button" class="chip-btn" data-filter="gastos">
              <ion-icon name="wallet-outline"></ion-icon>
              <span>Gastos & Finanzas</span>
            </button>
            <button type="button" class="chip-btn" data-filter="watched-only">
              <ion-icon name="checkmark-done-outline"></ion-icon>
              <span>Completados</span>
            </button>
          </div>
        </div>

        <!-- ═══ YOUTUBE CINEMA THEATER STAGE (EXPANDABLE) ═══ -->
        <div class="cinema-stage" id="cinemaStage">
          <div class="cinema-ambient-glow"></div>
          <div class="cinema-card">
            <div class="cinema-player-wrap">
              <iframe id="cinemaIframe" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <div class="cinema-details-bar">
              <div class="cinema-header-meta">
                <span class="cinema-tag" id="cinemaTag">Catálogo</span>
                <span class="cinema-duration"><i class="bi bi-clock me-1"></i><span id="cinemaDuration">03:40</span></span>
                <span class="badge rounded-pill bg-dark text-muted border border-secondary px-2 py-1" id="cinemaStep">Lección 1</span>
              </div>
              <h2 class="cinema-title" id="cinemaTitle">Título del tutorial</h2>

              <div class="cinema-actions-row">
                <div class="cinema-channel-info">
                  <div class="channel-avatar">
                    <i class="bi bi-play-circle-fill"></i>
                  </div>
                  <div class="channel-text">
                    <div class="channel-name">
                      iSeller Academy Oficial
                      <i class="bi bi-patch-check-fill" title="Verificado"></i>
                    </div>
                    <div class="channel-desc">Tutorial oficial en alta definición 1080p</div>
                  </div>
                </div>

                <div class="cinema-buttons">
                  <button type="button" class="btn-cinema btn-watched" id="btnToggleWatched">
                    <i class="bi bi-check2-circle"></i>
                    <span id="btnWatchedText">Marcar como visto</span>
                  </button>
                  <button type="button" class="btn-cinema" id="btnCopyLink">
                    <i class="bi bi-link-45deg"></i>
                    <span>Copiar enlace</span>
                  </button>
                  <a href="#" target="_blank" class="btn-cinema" id="btnOpenYoutube">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span>Ver en YouTube</span>
                  </a>
                  <button type="button" class="btn-cinema" id="btnNextVideo">
                    <span>Siguiente</span>
                    <i class="bi bi-chevron-right"></i>
                  </button>
                  <button type="button" class="btn-cinema btn-close-cinema" id="btnCloseCinema" title="Cerrar reproductor">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══ YOUTUBE VIDEO CARDS FEED ═══ -->
        <div class="yt-grid" id="tutoGrid">
          <?php foreach ($tutoriales as $index => $t): ?>
            <div class="yt-card" 
                 data-id="<?= htmlspecialchars($t['id']) ?>"
                 data-title="<?= htmlspecialchars($t['title']) ?>"
                 data-desc="<?= htmlspecialchars($t['desc']) ?>"
                 data-category="<?= htmlspecialchars($t['category']) ?>"
                 data-category-name="<?= htmlspecialchars($t['category_name']) ?>"
                 data-duration="<?= htmlspecialchars($t['duration']) ?>"
                 data-step="<?= htmlspecialchars($t['step']) ?>"
                 data-index="<?= $index ?>">
              
              <!-- Thumbnail -->
              <div class="yt-thumb-box">
                <img class="yt-thumb-img" 
                     src="https://img.youtube.com/vi/<?= htmlspecialchars($t['id']) ?>/maxresdefault.jpg" 
                     onerror="this.onerror=null; this.src='https://img.youtube.com/vi/<?= htmlspecialchars($t['id']) ?>/hqdefault.jpg';" 
                     alt="<?= htmlspecialchars($t['title']) ?>" 
                     loading="lazy">
                
                <span class="yt-cat-badge"><?= htmlspecialchars($t['category_name']) ?></span>
                <span class="yt-duration-badge"><?= htmlspecialchars($t['duration']) ?></span>
                
                <span class="yt-watched-badge">
                  <i class="bi bi-check-lg"></i> Visto
                </span>

                <div class="yt-watched-progress"></div>

                <div class="yt-play-overlay">
                  <div class="yt-play-btn-circle">
                    <i class="bi bi-play-fill"></i>
                  </div>
                </div>
              </div>

              <!-- Card Body -->
              <div class="yt-card-body">
                <div class="yt-card-avatar">
                  <ion-icon name="<?= htmlspecialchars($t['category_icon']) ?>"></ion-icon>
                </div>
                <div class="yt-card-text">
                  <h3 class="yt-card-title"><?= htmlspecialchars($t['title']) ?></h3>
                  <div class="yt-card-meta">
                    <span>iSeller Academy</span>
                    <i class="bi bi-patch-check-fill verified"></i>
                    <span class="meta-dot"></span>
                    <span><?= htmlspecialchars($t['step']) ?></span>
                  </div>
                  <p class="yt-card-desc"><?= htmlspecialchars($t['desc']) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- Empty State -->
          <div class="yt-empty-state" id="emptyState">
            <div class="yt-empty-icon"><i class="bi bi-search"></i></div>
            <div class="yt-empty-title">No se encontraron tutoriales</div>
            <p class="yt-empty-desc">Intenta buscando con otros términos o haz clic en el botón de abajo para ver la lista completa.</p>
            <button type="button" class="btn-cinema" id="btnResetFilters">
              <i class="bi bi-arrow-counterclockwise"></i> Ver todos los tutoriales
            </button>
          </div>
        </div>

      </div><!-- /right_col -->
    </div>
  </div>

  <!-- Toast Notification -->
  <div class="yt-toast" id="ytToast">
    <i class="bi bi-check-circle-fill text-success fs-5"></i>
    <span id="toastMsg">Enlace copiado al portapapeles</span>
  </div>

  <script src="../vendors/jquery/dist/jquery.min.js"></script>
  <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../build/js/custom.js"></script>
  <script src="js/nombre_pagina.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // ── Storage de videos vistos ──────────────────────────────────────────
      const STORAGE_KEY = 'iseller_academy_watched';
      let watchedList = [];
      try {
        watchedList = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
      } catch(e) {
        watchedList = [];
      }

      function saveWatched() {
        try {
          localStorage.setItem(STORAGE_KEY, JSON.stringify(watchedList));
        } catch(e) {}
        updateWatchedUI();
      }

      function isWatched(id) {
        return watchedList.includes(id);
      }

      function toggleWatched(id) {
        if (isWatched(id)) {
          watchedList = watchedList.filter(v => v !== id);
        } else {
          watchedList.push(id);
        }
        saveWatched();
      }

      // ── Actualizar UI de videos vistos ───────────────────────────────────
      const cards = Array.from(document.querySelectorAll('.yt-card'));
      const watchedCountLabel = document.getElementById('watchedCountLabel');

      function updateWatchedUI() {
        let count = 0;
        cards.forEach(card => {
          const id = card.dataset.id;
          if (isWatched(id)) {
            card.classList.add('is-watched');
            count++;
          } else {
            card.classList.remove('is-watched');
          }
        });
        if (watchedCountLabel) {
          watchedCountLabel.textContent = `${count} / ${cards.length} completados`;
        }
        updateCinemaWatchedButton();
      }

      // ── Cinema / Theater Player ──────────────────────────────────────────
      const cinemaStage = document.getElementById('cinemaStage');
      const cinemaIframe = document.getElementById('cinemaIframe');
      const cinemaTitle = document.getElementById('cinemaTitle');
      const cinemaTag = document.getElementById('cinemaTag');
      const cinemaDuration = document.getElementById('cinemaDuration');
      const cinemaStep = document.getElementById('cinemaStep');
      const btnToggleWatched = document.getElementById('btnToggleWatched');
      const btnWatchedText = document.getElementById('btnWatchedText');
      const btnCopyLink = document.getElementById('btnCopyLink');
      const btnOpenYoutube = document.getElementById('btnOpenYoutube');
      const btnNextVideo = document.getElementById('btnNextVideo');
      const btnCloseCinema = document.getElementById('btnCloseCinema');

      let currentVideoId = null;
      let currentIndex = -1;

      function openVideo(card) {
        if (!card) return;
        const id = card.dataset.id;
        const title = card.dataset.title;
        const catName = card.dataset.categoryName;
        const duration = card.dataset.duration;
        const step = card.dataset.step;
        const index = parseInt(card.dataset.index, 10);

        currentVideoId = id;
        currentIndex = index;

        // Marcar tarjeta activa
        cards.forEach(c => c.classList.remove('now-playing'));
        card.classList.add('now-playing');

        // Cargar datos en Cinema Stage
        cinemaTitle.textContent = title;
        cinemaTag.textContent = catName;
        cinemaDuration.textContent = duration;
        cinemaStep.textContent = step;
        cinemaIframe.src = `https://www.youtube.com/embed/${id}?autoplay=1&rel=0&modestbranding=1`;
        btnOpenYoutube.href = `https://www.youtube.com/watch?v=${id}`;

        // Mostrar Stage
        cinemaStage.classList.add('active');
        updateCinemaWatchedButton();

        // Smooth scroll al reproductor
        cinemaStage.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Marcar como visto automáticamente al abrir
        if (!isWatched(id)) {
          watchedList.push(id);
          saveWatched();
        }
      }

      function closeCinema() {
        cinemaStage.classList.remove('active');
        cinemaIframe.src = '';
        currentVideoId = null;
        currentIndex = -1;
        cards.forEach(c => c.classList.remove('now-playing'));
      }

      function updateCinemaWatchedButton() {
        if (!currentVideoId || !btnToggleWatched) return;
        if (isWatched(currentVideoId)) {
          btnToggleWatched.classList.add('active');
          btnWatchedText.textContent = 'Completado';
        } else {
          btnToggleWatched.classList.remove('active');
          btnWatchedText.textContent = 'Marcar como visto';
        }
      }

      if (btnToggleWatched) {
        btnToggleWatched.addEventListener('click', function() {
          if (currentVideoId) {
            toggleWatched(currentVideoId);
            showToast(isWatched(currentVideoId) ? '¡Marcado como visto!' : 'Marcado como pendiente');
          }
        });
      }

      if (btnCopyLink) {
        btnCopyLink.addEventListener('click', function() {
          if (currentVideoId) {
            const url = `https://youtu.be/${currentVideoId}`;
            if (navigator.clipboard) {
              navigator.clipboard.writeText(url).then(() => {
                showToast('Enlace de YouTube copiado al portapapeles');
              });
            } else {
              showToast('Enlace: ' + url);
            }
          }
        });
      }

      if (btnNextVideo) {
        btnNextVideo.addEventListener('click', function() {
          if (currentIndex !== -1) {
            const nextIndex = (currentIndex + 1) % cards.length;
            const nextCard = cards.find(c => parseInt(c.dataset.index, 10) === nextIndex);
            if (nextCard) openVideo(nextCard);
          }
        });
      }

      if (btnCloseCinema) {
        btnCloseCinema.addEventListener('click', closeCinema);
      }

      // Clic en tarjetas
      cards.forEach(card => {
        card.addEventListener('click', function() {
          openVideo(this);
        });
      });

      // ── Búsqueda y Filtros ───────────────────────────────────────────────
      const searchInput = document.getElementById('tutorialSearch');
      const searchClear = document.getElementById('searchClear');
      const filterChips = document.querySelectorAll('.chip-btn');
      const emptyState = document.getElementById('emptyState');
      const btnResetFilters = document.getElementById('btnResetFilters');

      let currentCategory = 'all';

      function applyFilters() {
        const query = (searchInput.value || '').trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach(card => {
          const title = (card.dataset.title || '').toLowerCase();
          const desc = (card.dataset.desc || '').toLowerCase();
          const cat = card.dataset.category || '';
          const id = card.dataset.id;

          const matchesQuery = !query || title.includes(query) || desc.includes(query) || cat.includes(query);
          
          let matchesCategory = true;
          if (currentCategory === 'watched-only') {
            matchesCategory = isWatched(id);
          } else if (currentCategory !== 'all') {
            matchesCategory = (cat === currentCategory);
          }

          if (matchesQuery && matchesCategory) {
            card.style.display = 'flex';
            visibleCount++;
          } else {
            card.style.display = 'none';
          }
        });

        if (visibleCount === 0) {
          emptyState.style.display = 'block';
        } else {
          emptyState.style.display = 'none';
        }

        if (searchClear) {
          searchClear.style.display = query.length > 0 ? 'inline-block' : 'none';
        }
      }

      searchInput.addEventListener('input', applyFilters);

      searchClear.addEventListener('click', function() {
        searchInput.value = '';
        applyFilters();
        searchInput.focus();
      });

      filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
          filterChips.forEach(c => c.classList.remove('active'));
          this.classList.add('active');
          currentCategory = this.dataset.filter;
          applyFilters();
        });
      });

      if (btnResetFilters) {
        btnResetFilters.addEventListener('click', function() {
          searchInput.value = '';
          currentCategory = 'all';
          filterChips.forEach(c => c.classList.remove('active'));
          document.querySelector('.chip-btn[data-filter="all"]')?.classList.add('active');
          applyFilters();
        });
      }

      // Atajo de teclado '/' para buscar y 'Esc' para cerrar cine
      document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement !== searchInput) {
          e.preventDefault();
          searchInput.focus();
          searchInput.select();
        } else if (e.key === 'Escape') {
          if (cinemaStage.classList.contains('active')) {
            closeCinema();
          }
        }
      });

      // ── Toast flotante ───────────────────────────────────────────────────
      const toast = document.getElementById('ytToast');
      const toastMsg = document.getElementById('toastMsg');
      let toastTimer = null;

      function showToast(msg) {
        if (!toast) return;
        toastMsg.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
          toast.classList.remove('show');
        }, 2800);
      }

      // ── Inicialización ───────────────────────────────────────────────────
      updateWatchedUI();

      // Si viene con parámetro en la URL (?v=ID o #ID)
      const urlParams = new URLSearchParams(window.location.search);
      const urlVideoId = urlParams.get('v') || window.location.hash.replace('#', '');
      if (urlVideoId) {
        const targetCard = cards.find(c => c.dataset.id === urlVideoId);
        if (targetCard) {
          openVideo(targetCard);
        }
      }
    });
  </script>
</body>

</html>
<?php
} else {
  define('PAGINA_INICIO', '../../index.php');
  header('Location: ' . PAGINA_INICIO);
}
?>