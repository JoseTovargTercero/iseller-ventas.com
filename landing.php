<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>iSeller — Control de Inventario y Ventas Multisucursal en Tiempo Real</title>
    <meta name="description" content="Gestiona múltiples sucursales, inventario en tiempo real y ventas con iSeller. La plataforma más completa para el control total de tu negocio con soporte multidivisa.">
    <meta name="keywords" content="inventario, ventas, multisucursal, punto de venta, POS, gestión de negocio, iSeller, control de stock, reporte de ventas">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="canonical" href="https://iseller-tiendas.com/">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://iseller-tiendas.com/">
    <meta property="og:title" content="iSeller — Control de Inventario y Ventas Multisucursal">
    <meta property="og:description" content="Gestiona múltiples sucursales, inventario en tiempo real y ventas con iSeller.">
    <meta property="og:image" content="https://iseller-tiendas.com/publico/production/images/logo1-inv-compact.png">

    <link rel="icon" href="publico/production/images/favicon.ico" type="image/ico">

    <!-- Google Fonts -->
    <link href="publico/vendors/fonts/fonts.css" rel="stylesheet">

    <style>
        /* ============================================================
           DESIGN TOKENS - PREMIUM SAAS (Dark Mode Default)
        ============================================================ */
        :root {
            --bg-base: #080A0C;
            --bg-surface: #101216;
            --bg-card: #15181C;
            --bg-card-hover: #1A1D24;
            --border: rgba(255, 255, 255, 0.06);
            --border-hover: rgba(255, 255, 255, 0.15);
            --border-active: rgba(255, 255, 255, 0.3);

            --accent-primary: #FFFFFF;
            --accent-on-primary: #000000;
            --brand-blue: #3B82F6;
            --accent-mint: #49DEAD;

            --text-primary: #FAFAFA;
            --text-secondary: #A1A1AA;
            --text-muted: #71717A;

            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;

            --bezier: cubic-bezier(0.2, 0.8, 0.2, 1);
            --transition: all 0.35s var(--bezier);
            --transition-fast: all 0.2s var(--bezier);

            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.6);
        }

        /* ============================================================
           RESET & BASE
        ============================================================ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        /* Focus Accesibilidad */
        :focus-visible {
            outline: 2px solid var(--accent-primary);
            outline-offset: 2px;
        }

        /* ============================================================
           TYPOGRAPHY
        ============================================================ */
        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Sora', sans-serif;
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: var(--text-primary);
        }

        /* ============================================================
           LAYOUT UTILITIES
        ============================================================ */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        section {
            position: relative;
            z-index: 1;
        }

        /* ============================================================
           NAVBAR
        ============================================================ */
        #navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 20px 0;
            transition: var(--transition);
            background: transparent;
            border-bottom: 1px solid transparent;
        }

        #navbar.scrolled {
            background: rgba(8, 10, 12, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--border);
            padding: 14px 0;
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-logo img {
            height: 28px;
            width: auto;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            transition: var(--transition-fast);
        }

        .nav-links a:hover {
            color: var(--text-primary);
        }

        .nav-cta {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-ghost {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            transition: var(--transition-fast);
        }

        .btn-ghost:hover {
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent-primary);
            color: var(--accent-on-primary) !important;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            transition: var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
        }

        .btn-primary:hover {
            background: #E4E4E7;
            /* Light gray */
            transform: scale(0.98);
        }

        .btn-primary:active {
            transform: scale(0.96);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-primary);
            background: var(--bg-surface);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            transition: var(--transition-fast);
        }

        .btn-secondary:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-hover);
        }

        .btn-secondary:active {
            transform: scale(0.98);
        }

        /* Mobile hamburger */
        .nav-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 8px;
            border: none;
            background: none;
        }

        .nav-toggle span {
            display: block;
            width: 22px;
            height: 2px;
            background: var(--text-primary);
            border-radius: 2px;
            transition: var(--transition);
        }

        /* ============================================================
           HERO
        ============================================================ */
        #hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 160px 0 100px;
            position: relative;
            overflow: hidden;
        }

        #hero .hero-bg-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        /* Iluminación sutil (Storytelling: foco en la solución) */
        #hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(8, 10, 12, 0.88);
            pointer-events: none;
            z-index: 1;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .hero-content {
            max-width: 720px;
            text-align: left;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 100px;
            margin-bottom: 28px;
            transition: var(--transition-fast);
        }

        .hero-badge:hover {
            border-color: var(--border-hover);
            color: var(--text-primary);
        }

        .hero-badge .dot {
            width: 6px;
            height: 6px;
            background: var(--brand-blue);
            border-radius: 50%;
        }

        .hero-title {
            font-size: clamp(2.5rem, 4.5vw, 4rem);
            margin-bottom: 24px;
            color: var(--text-primary);
            letter-spacing: -0.03em;
        }

        .hero-sub {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 48px;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* Plataformas disponibles (web / windows / android) */
        .hero-platforms {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 36px;
        }

        .hero-platforms-label {
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-secondary);
            margin-right: 4px;
        }

        .hero-platform {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 22px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 100px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), var(--shadow-sm);
            color: var(--text-primary);
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: -0.01em;
            transition: var(--transition-fast);
        }

        .hero-platform svg {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
            color: var(--accent-mint);
        }

        .hero-platform:hover {
            border-color: rgba(73, 222, 173, 0.5);
            background: var(--bg-card-hover);
            color: var(--text-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(73, 222, 173, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        /* Hero Features Bar */
        .hero-features-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            margin-top: 48px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
        }

        .hero-feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0 24px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .hero-feature-item:first-child {
            padding-left: 0;
        }

        .hero-feature-item:last-child {
            border-right: none;
            padding-right: 0;
        }

        .hero-feature-icon {
            flex-shrink: 0;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1.5px solid #49dead;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #49dead;
        }

        .hero-feature-icon svg {
            width: 18px;
            height: 18px;
        }

        .hero-feature-name {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hero-feature-sub {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .nowrap {
            white-space: nowrap;
        }

        .hero-badge-new {
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #49dead;
            color: #000;
            padding: 2px 8px;
            border-radius: 99px;
            line-height: 1.4;
        }

        @media (max-width: 991px) {
            .hero-features-bar {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px 0;
            }

            .hero-feature-item:nth-child(2) {
                border-right: none;
            }

            /* En 2 columnas cada fila empieza pegada al borde, como la fila de 4 */
            .hero-feature-item:nth-child(odd) {
                padding-left: 0;
            }

            .hero-feature-item:nth-child(even) {
                padding-right: 0;
            }

            .hero-feature-item:nth-child(1),
            .hero-feature-item:nth-child(2) {
                padding-bottom: 20px;
                border-bottom: 1px solid var(--border);
            }
        }

        @media (max-width: 575px) {

            /* La etiqueta va en su propia línea para que los 3 chips entren juntos */
            .hero-platforms-label {
                width: 100%;
            }

            .hero-platforms {
                gap: 8px;
            }

            .hero-platform {
                padding: 10px 15px;
                font-size: 0.82rem;
                gap: 8px;
            }

            .hero-platform svg {
                width: 17px;
                height: 17px;
            }

            .hero-features-bar {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .hero-feature-item {
                padding: 16px 0;
                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .hero-feature-item:last-child {
                border-bottom: none;
            }
        }

        /* En pantallas de 360px los 3 chips deben seguir en una sola fila */
        @media (max-width: 400px) {
            .hero-platforms {
                gap: 6px;
            }

            .hero-platform {
                padding: 9px 12px;
                font-size: 0.78rem;
                gap: 6px;
            }

            .hero-platform svg {
                width: 15px;
                height: 15px;
            }
        }

        /* Hero visual - Parallax Dashboard */
        .hero-visual {
            position: relative;
            perspective: 1000px;
        }

        .hero-dashboard {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            /* Animación inicial sutil */
            transform: translateY(0);
            transition: transform 0.1s linear;
        }

        /* Dashboard UI interna limpia */
        .dash-topbar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-card);
        }

        .dash-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #333;
        }

        .dash-url {
            flex: 1;
            margin-left: 12px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.72rem;
            color: var(--text-muted);
            text-align: center;
        }

        .dash-body {
            padding: 24px;
        }

        .dash-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .dash-stat {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 16px;
        }

        .dash-stat-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }

        .dash-stat-value {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .dash-table-wrap {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .dash-table-header {
            display: flex;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.7rem;
            color: var(--text-muted);
            background: var(--bg-card);
        }

        .dash-table-row {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .dash-table-row:last-child {
            border-bottom: none;
        }

        .f1 {
            flex: 2;
        }

        .f2 {
            flex: 1;
        }

        .f3 {
            flex: 1;
            text-align: right;
        }

        .dash-pill {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Floating card overlay - minimalista */
        .hero-float-card {
            position: absolute;
            bottom: -30px;
            left: -30px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px 24px;
            box-shadow: var(--shadow-md);
            /* Parallax individual */
            transition: transform 0.1s linear;
        }

        .float-card-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .float-card-value {
            font-family: 'Sora';
            font-size: 1.75rem;
            font-weight: 600;
        }

        /* ============================================================
           SOCIAL PROOF BAR
        ============================================================ */
        #social-proof {
            padding: 64px 0;
            border-bottom: 1px solid var(--border);
        }

        .proof-inner {
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 48px;
        }

        .proof-stat {
            text-align: center;
        }

        .proof-num {
            font-family: 'Sora', sans-serif;
            font-size: 2rem;
            font-weight: 600;
            color: #fff;
            display: block;
        }

        .proof-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* ============================================================
           FEATURES SECTION
        ============================================================ */
        .section-wrap {
            padding: 140px 0;
            border-bottom: 1px solid var(--border);
        }

        .section-header {
            margin-bottom: 80px;
            max-width: 600px;
        }

        .section-header.center {
            text-align: center;
            margin: 0 auto 80px;
        }

        .section-eyebrow {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #49dead;
            margin-bottom: 16px;
            border: 1px solid rgba(73, 222, 173, 0.2);
            padding: 4px 12px;
            border-radius: 100px;
        }

        .section-title {
            font-size: clamp(2rem, 3.5vw, 2.5rem);
            margin-bottom: 24px;
            letter-spacing: -0.02em;
        }

        .section-sub {
            font-size: 1.125rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Feature cards */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .feature-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 40px 32px;
            transition: var(--transition);
        }

        .feature-card:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-active);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            background: rgba(73, 222, 173, 0.05);
            border: 1px solid rgba(73, 222, 173, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            color: #49dead;
            transition: var(--transition);
        }

        .feature-card:hover .feature-icon {
            border-color: rgba(73, 222, 173, 0.3);
            color: #49dead;
        }

        .feature-icon svg {
            width: 24px;
            height: 24px;
        }

        .feature-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .feature-desc {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Plataformas: web / windows / android */
        .platforms-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .platform-card {
            display: flex;
            flex-direction: column;
        }

        .platform-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 24px;
        }

        .platform-card .feature-icon {
            margin-bottom: 0;
        }

        .platform-tag {
            font-size: 0.68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 4px 12px;
            white-space: nowrap;
        }

        /* Large Feature Card */
        .feature-card-lg {
            grid-column: span 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }

        .feature-visual-code {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 24px;
            font-family: monospace;
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 2;
        }

        .code-key {
            color: var(--text-secondary);
        }

        .code-val {
            color: var(--text-primary);
        }

        /* ============================================================
           TIENDA ONLINE SPOTLIGHT
        ============================================================ */
        .tienda-online-spotlight {
            margin-top: 64px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            background: var(--bg-surface);
            border: 1px solid rgba(73, 222, 173, 0.12);
            border-radius: var(--radius-lg);
            padding: 56px 48px;
            position: relative;
            overflow: hidden;
        }

        .tienda-online-spotlight::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(73, 222, 173, 0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .spotlight-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            background: rgba(73, 222, 173, 0.1);
            border: 1px solid rgba(73, 222, 173, 0.2);
            color: #49dead;
            padding: 5px 14px;
            border-radius: 100px;
            margin-bottom: 20px;
        }

        .spotlight-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.6rem, 2.5vw, 2rem);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
            letter-spacing: -0.02em;
        }

        .spotlight-desc {
            font-size: 1rem;
            color: var(--text-secondary);
            line-height: 1.65;
            margin-bottom: 24px;
            max-width: 480px;
        }

        .spotlight-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 32px;
        }

        .spotlight-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.88rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .spotlight-list li svg {
            color: #49dead;
            flex-shrink: 0;
            margin-top: 3px;
        }

        .spotlight-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #49dead;
            color: #000;
            border-radius: var(--radius-sm);
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .spotlight-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(73, 222, 173, 0.25);
        }

        /* Mockup visual */
        .spotlight-visual {
            display: flex;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .spotlight-mockup {
            width: 100%;
            max-width: 380px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .mockup-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 14px;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid var(--border);
        }

        .mockup-bar span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .mockup-url {
            margin-left: auto;
            font-size: 0.65rem;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.04);
            padding: 3px 10px;
            border-radius: 6px;
        }

        .mockup-body {
            padding: 16px;
        }

        .mockup-hero-img {
            width: 100%;
            height: 100px;
            background: linear-gradient(135deg, rgba(73, 222, 173, 0.1), rgba(73, 222, 173, 0.03));
            border-radius: 8px;
            margin-bottom: 14px;
        }

        .mockup-products {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .mockup-product {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .mockup-product-img {
            width: 100%;
            aspect-ratio: 1;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        .mockup-product-name {
            width: 80%;
            height: 8px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 4px;
        }

        .mockup-product-price {
            width: 50%;
            height: 8px;
            background: rgba(73, 222, 173, 0.2);
            border-radius: 4px;
        }

        @media (max-width: 991px) {
            .tienda-online-spotlight {
                grid-template-columns: 1fr;
                padding: 40px 28px;
                gap: 40px;
            }

            .spotlight-visual {
                order: -1;
            }

            .spotlight-mockup {
                max-width: 320px;
            }
        }

        /* ============================================================
           TASAS DE CAMBIO
        ============================================================ */
        .currencies-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .currency-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
        }

        .currency-card:hover {
            border-color: var(--border-active);
            background: var(--bg-card-hover);
        }

        .currency-flag-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-card);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .currency-info {
            flex: 1;
        }

        .currency-name {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .currency-symbol {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .currency-rate {
            font-family: 'Sora', sans-serif;
            font-size: 1.1rem;
            font-weight: 500;
        }

        /* ============================================================
           COMO FUNCIONA
        ============================================================ */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 48px;
        }

        .step-item {
            position: relative;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 24px;
            right: -32px;
            width: 32px;
            height: 1px;
            background: var(--border);
        }

        .step-number {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #49dead;
            margin-bottom: 20px;
        }

        .step-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .step-desc {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        /* ============================================================
           PRICING SECTION
        ============================================================ */
        .pricing-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 64px;
        }

        .toggle-label {
            font-size: 0.95rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition-fast);
        }

        .toggle-label.active {
            color: var(--text-primary);
        }

        .toggle-switch {
            width: 52px;
            height: 28px;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 100px;
            position: relative;
            cursor: pointer;
            transition: var(--transition);
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            background: var(--text-secondary);
            border-radius: 50%;
            transition: var(--transition);
        }

        .toggle-switch.annual {
            border-color: var(--border-active);
        }

        .toggle-switch.annual::after {
            transform: translateX(24px);
            background: var(--text-primary);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
            max-width: 860px;
            margin: 0 auto;
        }

        .pricing-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 48px 40px;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }

        .pricing-card:hover {
            border-color: var(--border-active);
        }

        .pricing-card.featured {
            border-color: rgba(73, 222, 173, 0.25);
            background: #121519;
            box-shadow: var(--shadow-lg);
        }

        .pricing-plan {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 16px;
        }

        .pricing-price {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 12px;
        }

        .price-cur {
            font-size: 1.5rem;
            color: var(--text-secondary);
        }

        .price-amount {
            font-family: 'Sora', sans-serif;
            font-size: 3.5rem;
            font-weight: 600;
            line-height: 1;
        }

        .price-period {
            font-size: 1rem;
            color: var(--text-muted);
        }

        .pricing-trial {
            display: block;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 40px;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 48px;
            flex-grow: 1;
        }

        .pricing-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            color: var(--text-secondary);
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }

        .pricing-features li:last-child {
            border-bottom: none;
        }

        .pricing-features li svg {
            color: #49dead;
            flex-shrink: 0;
        }

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* ============================================================
           CTA SECTION
        ============================================================ */
        .cta-box {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 100px 64px;
            text-align: center;
        }

        .cta-title {
            font-size: clamp(2rem, 3.5vw, 2.5rem);
            margin-bottom: 24px;
        }

        .cta-title .primary-color {
            color: #49dead;
        }

        .cta-sub {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 48px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }

        /* ============================================================
           FOOTER
        ============================================================ */
        footer {
            padding: 64px 0 40px;
            border-top: 1px solid var(--border);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
        }

        .footer-copy {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .footer-links {
            display: flex;
            gap: 32px;
        }

        .footer-links a {
            font-size: 0.875rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition-fast);
        }

        .footer-links a:hover {
            color: var(--text-primary);
        }

        /* ============================================================
           ANIMATIONS (Elegantes y uniformes)
        ============================================================ */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.8s var(--bezier), transform 0.8s var(--bezier);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ============================================================
           RESPONSIVE
        ============================================================ */
        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 64px;
            }

            .hero-visual {
                display: none;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .platforms-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .feature-card-lg {
                grid-column: span 2;
            }

            .steps-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .step-item::after {
                display: none;
            }

            .currencies-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {

            .nav-links,
            .nav-cta {
                display: none;
            }

            .nav-toggle {
                display: flex;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .platforms-grid {
                grid-template-columns: 1fr;
            }

            .feature-card-lg {
                grid-column: span 1;
                display: block;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .proof-inner {
                flex-direction: column;
                gap: 40px;
            }

            .cta-box {
                padding: 64px 32px;
            }

            .section-wrap {
                padding: 80px 0;
            }

            .currencies-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile nav */
        .mobile-menu {
            display: none;
            position: fixed;
            inset: 0;
            background: var(--bg-base);
            z-index: 9999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 32px;
        }

        .mobile-menu.open {
            display: flex;
        }

        .mobile-menu a {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            text-decoration: none;
        }

        .mobile-close {
            position: absolute;
            top: 24px;
            right: 24px;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 2rem;
            cursor: pointer;
        }

        .primary-color {
            color: #49dead;
        }

        .primary-bg {
            background-color: #49dead;
        }

        .text-white {
            color: white !important;
        }

        /* ── Overlay de acceso (login / registro) ── */
        .auth-overlay {
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(8, 10, 12, 0.55);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }

        .auth-overlay.open {
            opacity: 1;
            pointer-events: auto;
        }

        .auth-overlay iframe {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            border: 0;
            background: #080A0C;
            transform: translateX(100%);
            transition: transform 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .auth-overlay.open iframe {
            transform: translateX(0);
        }

        .auth-close {
            position: absolute;
            top: 18px;
            right: 22px;
            z-index: 2;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(16, 18, 22, 0.85);
            color: #fff;
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .auth-close:hover {
            background: rgba(59, 130, 246, 0.35);
            transform: scale(1.05);
        }

        @media (prefers-reduced-motion: reduce) {

            .auth-overlay,
            .auth-overlay iframe {
                transition: none;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu" id="mobileMenu" role="dialog" aria-label="Menú de navegación">
        <button class="mobile-close" id="mobileClose" aria-label="Cerrar menú">&times;</button>
        <a href="#funcionalidades">Funcionalidades</a>
        <a href="#plataformas">Plataformas</a>
        <a href="#tasas">Tasas de cambio</a>
        <a href="#pricing">Planes</a>
        <a href="login.php" class="btn-secondary" style="font-size:1rem; border:none;">Iniciar sesión</a>
        <a href="registro.php" class="btn-primary">Comenzar gratis</a>
    </div>

    <!-- ============================================================
         NAVBAR
    ============================================================ -->
    <nav id="navbar" role="navigation" aria-label="Navegación principal">
        <div class="container">
            <div class="nav-inner">
                <a href="/" class="nav-logo" aria-label="iSeller — Inicio">
                    <img src="publico/production/images/logo1-inv-compact.png" alt="iSeller logotipo">
                </a>
                <ul class="nav-links" role="list">
                    <li><a href="#funcionalidades">Funcionalidades</a></li>
                    <li><a href="#plataformas">Plataformas</a></li>
                    <li><a href="#tasas">Tasas de cambio</a></li>
                    <li><a href="#pricing">Planes</a></li>
                </ul>
                <div class="nav-cta">
                    <a href="login.php" class="btn-ghost">Iniciar sesión</a>
                    <a href="registro.php" class="btn-primary">Comenzar gratis</a>
                </div>
                <button class="nav-toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </nav>

    <!-- ============================================================
         HERO
    ============================================================ -->
    <section id="hero">
        <video class="hero-bg-video" autoplay loop muted playsinline poster="publico/production/images/hero.webp">
            <source src="publico/production/images/hero.webm" type="video/webm">
        </video>
        <div class="container">
            <div class="hero-grid">
                <!-- Content -->
                <div class="hero-content">
                    <div class="hero-badge reveal">
                        Sistema operativo para tu tienda
                    </div>
                    <h1 class="hero-title reveal">
                        Gestiona tu inventario,<br>
                        ventas y ahora <br>
                        <span class="primary-color">tu tienda online</span>
                    </h1>
                    <p class="hero-sub reveal">
                        Todo en un solo lugar. Lleva el control de tu negocio y empieza a vender en linea en minutos, con tu propia tienda personalizada.
                    </p>

                    <div class="hero-platforms reveal" style="display: none;">
                        <span class="hero-platform" title="Úsalo desde cualquier navegador">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                                <path d="M2 9h20" />
                                <path d="M5 6.5h.01M8 6.5h.01" />
                            </svg>
                            Web
                        </span>
                        <span class="hero-platform" title="App de escritorio para Windows">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 6.2l7.2-1v6.3H3V6.2z" />
                                <path d="M12.6 4.9L21 3.5v8h-8.4V4.9z" />
                                <path d="M3 12.9h7.2v6.1L3 17.8v-4.9z" />
                                <path d="M12.6 12.9H21v7.6l-8.4-1.4v-6.2z" />
                            </svg>
                            Windows
                        </span>
                        <span class="hero-platform" title="App para Android">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6.4 12.6a5.6 5.6 0 0111.2 0z" />
                                <path d="M8.8 9.6L7.4 7" />
                                <path d="M15.2 9.6l1.4-2.6" />
                                <path d="M10 10.7h.01M14 10.7h.01" />
                            </svg>
                            Android
                        </span>
                    </div>
                </div>
                <div class="hero-features-bar reveal">
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div class="hero-feature-text">
                            <div class="hero-feature-name">Control de inventario</div>
                            <div class="hero-feature-sub">Stock en tiempo real</div>
                        </div>
                    </div>
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="2" y1="12" x2="22" y2="12" />
                                <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" />
                            </svg>
                        </div>
                        <div class="hero-feature-text">
                            <div class="hero-feature-name">Multi Moneda</div>
                            <div class="hero-feature-sub">Pesos, Dólares, Bolívares</div>
                        </div>
                    </div>
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <path d="M16 10a4 4 0 01-8 0" />
                            </svg>
                        </div>
                        <div class="hero-feature-text">
                            <div class="hero-feature-name"><span class="nowrap">Tienda Online <span class="hero-badge-new">Nuevo</span></span></div>
                            <div class="hero-feature-sub">Vende en internet en minutos</div>
                        </div>
                    </div>
                    <div class="hero-feature-item">
                        <div class="hero-feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="20" x2="18" y2="10" />
                                <line x1="12" y1="20" x2="12" y2="4" />
                                <line x1="6" y1="20" x2="6" y2="14" />
                            </svg>
                        </div>
                        <div class="hero-feature-text">
                            <div class="hero-feature-name">Reportes y estadísticas</div>
                            <div class="hero-feature-sub">Ganancias, ventas y métricas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>

    <!-- ============================================================
         SOCIAL PROOF
    ============================================================ -->
    <section id="social-proof" aria-label="Estadísticas de la plataforma">
        <div class="container">
            <div class="proof-inner">
                <div class="proof-stat reveal">
                    <span class="proof-num" id="counter-sucursales">34+</span>
                    <span class="proof-label">Sucursales gestionadas</span>
                </div>
                <div class="proof-stat reveal">
                    <span class="proof-num" id="counter-ventas">29k+</span>
                    <span class="proof-label">Transacciones procesadas</span>
                </div>
                <div class="proof-stat reveal">
                    <span class="proof-num">99.9%</span>
                    <span class="proof-label">Uptime del sistema</span>
                </div>
                <div class="proof-stat reveal">
                    <span class="proof-num">Un mes</span>
                    <span class="proof-label">Sin costo inicial</span>
                </div>
            </div>
        </div>
    </section>

    <main>
        <!-- ============================================================
             FEATURES
        ============================================================ -->
        <section id="funcionalidades" class="section-wrap">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-eyebrow">Funcionalidades</span>
                    <h2 class="section-title">El poder operativo para escalar</h2>
                    <p class="section-sub">Herramientas diseñadas para eliminar la fricción del día a día. Sincronización impecable y datos en tiempo real.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card reveal">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <polyline points="9 22 9 12 15 12 15 22" />
                            </svg>
                        </div>
                        <div class="feature-title">Gestión Multisucursal</div>
                        <p class="feature-desc">Supervisa inventario y ajusta precios globalmente desde un solo panel. Cada tienda siempre actualizada.</p>
                    </div>

                    <div class="feature-card reveal">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23" />
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
                            </svg>
                        </div>
                        <div class="feature-title">Motor Multidivisa</div>
                        <p class="feature-desc">Factura en bolívares, dólares o cualquier moneda. El sistema calcula los montos al instante en el punto de venta.</p>
                    </div>

                    <div class="feature-card reveal">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                            </svg>
                        </div>
                        <div class="feature-title">Analítica Profunda</div>
                        <p class="feature-desc">Mide márgenes de ganancia, productos más vendidos y rendimiento por cajero con precisión contable.</p>
                    </div>

                    <div class="feature-card reveal">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 00-4-4H5c-1.1 0-2 .9-2 2v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <line x1="20" y1="8" x2="20" y2="14" />
                                <line x1="23" y1="11" x2="17" y2="11" />
                            </svg>
                        </div>
                        <div class="feature-title">Control de Créditos (Fiado)</div>
                        <p class="feature-desc">Registra cuentas por cobrar de clientes de confianza y gestiona sus abonos de forma transparente.</p>
                    </div>

                    <div class="feature-card reveal">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="5" y="2" width="14" height="20" rx="2" />
                                <line x1="12" y1="18" x2="12.01" y2="18" />
                            </svg>
                        </div>
                        <div class="feature-title">Punto de Venta (POS) Ultra-rápido</div>
                        <p class="feature-desc">Una terminal optimizada para la velocidad. Soporta lectores de código de barras, búsqueda predictiva y gestión de clientes al momento del cobro.</p>
                    </div>

                    <div class="feature-card reveal">
                        <div class="feature-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 00-4-4H5c-1.1 0-2 .9-2 2v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <line x1="20" y1="8" x2="23" y2="8" />
                                <line x1="20" y1="12" x2="23" y2="12" />
                                <line x1="20" y1="16" x2="23" y2="16" />
                            </svg>
                        </div>
                        <div class="feature-title">Gastos y Empleados</div>
                        <p class="feature-desc">Controla gastos fijos y variables, gestiona nóminas recurrentes y analiza la rentabilidad de cada sucursal con reportes automatizados.</p>
                    </div>
                </div>

                <!-- Tienda Online — Feature destacada -->
                <div class="tienda-online-spotlight reveal">
                    <div class="spotlight-content">
                        <div class="spotlight-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                <line x1="3" y1="6" x2="21" y2="6" />
                                <path d="M16 10a4 4 0 01-8 0" />
                            </svg>
                            Nuevo
                        </div>
                        <h3 class="spotlight-title">Tienda Online</h3>
                        <p class="spotlight-desc">Lanza tu tienda virtual en minutos. Catálogo sincronizado con tu inventario, carrito de compras, pasarela de pagos y dominio propio — todo automático.</p>
                        <ul class="spotlight-list">
                            <li>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Sincronización en tiempo real con tu inventario físico
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Carrito de compras y checkout personalizable
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Dominio propio y diseño responsive incluido
                            </li>
                            <li>
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                Pedidos online directamente en tu panel de administración
                            </li>
                        </ul>
                        <a href="registro.php" class="spotlight-cta">
                            Crear mi tienda gratis
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                    <div class="spotlight-visual">
                        <div class="spotlight-mockup">
                            <div class="mockup-bar">
                                <span></span><span></span><span></span>
                                <div class="mockup-url">tu-tienda.com</div>
                            </div>
                            <div class="mockup-body">
                                <div class="mockup-hero-img"></div>
                                <div class="mockup-products">
                                    <div class="mockup-product">
                                        <div class="mockup-product-img"></div>
                                        <div class="mockup-product-name"></div>
                                        <div class="mockup-product-price"></div>
                                    </div>
                                    <div class="mockup-product">
                                        <div class="mockup-product-img"></div>
                                        <div class="mockup-product-name"></div>
                                        <div class="mockup-product-price"></div>
                                    </div>
                                    <div class="mockup-product">
                                        <div class="mockup-product-img"></div>
                                        <div class="mockup-product-name"></div>
                                        <div class="mockup-product-price"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section id="tasas" class="section-wrap">
            <div class="container">
                <div class="section-header center reveal">
                    <span class="section-eyebrow">Multidivisa</span>
                    <h2 class="section-title">Fluidez financiera total</h2>
                    <p class="section-sub">Mantén tus precios en la moneda fuerte de referencia. Cobra en la moneda local sin fricciones operativas.</p>
                </div>
                <div class="currencies-grid">
                    <div class="currency-card reveal">
                        <div class="currency-flag-placeholder">🇻🇪</div>
                        <div class="currency-info">
                            <div class="currency-name">Bolívar venezolano</div>
                            <div class="currency-symbol">VES (Bs.)</div>
                        </div>
                        <div class="currency-rate" id="rate-bcv">Bs. 36.06</div>
                    </div>
                    <div class="currency-card reveal">
                        <div class="currency-flag-placeholder">🇺🇸</div>
                        <div class="currency-info">
                            <div class="currency-name">Dólar estadounidense</div>
                            <div class="currency-symbol">USD ($)</div>
                        </div>
                        <div class="currency-rate">$ 1.00</div>
                    </div>
                    <div class="currency-card reveal">
                        <div class="currency-flag-placeholder">🇨🇴</div>
                        <div class="currency-info">
                            <div class="currency-name">Peso colombiano</div>
                            <div class="currency-symbol">COP ($)</div>
                        </div>
                        <div class="currency-rate" id="rate-cop">$ 4,158</div>
                    </div>
                    <div class="currency-card reveal" style="opacity: 0;">
                    </div>
                    <div class="currency-card reveal">
                        <div class="currency-flag-placeholder">/</div>
                        <div class="currency-info">
                            <div class="currency-name">Divisa personalizada</div>
                            <div class="currency-symbol">Configura tu tasa</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
             COMO FUNCIONA
        ============================================================ -->
        <section id="como-funciona" class="section-wrap">
            <div class="container">
                <div class="section-header reveal">
                    <span class="section-eyebrow">Onboarding</span>
                    <h2 class="section-title">Instalación Cero</h2>
                    <p class="section-sub">Basado en la nube. Tu negocio operativo en minutos, no en semanas.</p>
                </div>
                <div class="steps-grid">
                    <div class="step-item reveal">
                        <div class="step-number">01</div>
                        <div class="step-title">Crea tu cuenta</div>
                        <p class="step-desc">El registro toma segundos. Obtienes 1 mes gratuito para validar el sistema en entorno real sin requerir tarjeta.</p>
                    </div>
                    <div class="step-item reveal">
                        <div class="step-number">02</div>
                        <div class="step-title">Configura tu entorno</div>
                        <p class="step-desc">Agrega sucursales, roles de empleados e importa tu catálogo de inventario con un archivo CSV.</p>
                    </div>
                    <div class="step-item reveal">
                        <div class="step-number">03</div>
                        <div class="step-title">Despliega el POS</div>
                        <p class="step-desc">Tus cajeros inician sesión y comienzan a facturar. Todo sincronizado instantáneamente al panel central.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
             PLATAFORMAS
        ============================================================ -->
        <section id="plataformas" class="section-wrap">
            <div class="container">
                <div class="section-header center reveal">
                    <span class="section-eyebrow">Multiplataforma</span>
                    <h2 class="section-title">Tu negocio en todas partes</h2>
                    <p class="section-sub">Una sola cuenta y los mismos datos. Trabaja desde el navegador, la PC de caja o el teléfono, sin sincronizar nada a mano.</p>
                </div>
                <div class="platforms-grid">
                    <div class="feature-card platform-card reveal">
                        <div class="platform-card-head">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2" />
                                    <path d="M2 9h20" />
                                    <path d="M5 6.5h.01M8 6.5h.01" />
                                </svg>
                            </div>
                            <span class="platform-tag">Navegador</span>
                        </div>
                        <h3 class="feature-title">Web</h3>
                        <p class="feature-desc">El sistema completo desde cualquier navegador. Cobra, revisa inventario y administra tu tienda online sin instalar nada.</p>
                    </div>
                    <div class="feature-card platform-card reveal">
                        <div class="platform-card-head">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6.2l7.2-1v6.3H3V6.2z" />
                                    <path d="M12.6 4.9L21 3.5v8h-8.4V4.9z" />
                                    <path d="M3 12.9h7.2v6.1L3 17.8v-4.9z" />
                                    <path d="M12.6 12.9H21v7.6l-8.4-1.4v-6.2z" />
                                </svg>
                            </div>
                            <span class="platform-tag">Escritorio</span>
                        </div>
                        <h3 class="feature-title">Windows</h3>
                        <p class="feature-desc">App nativa para la PC de caja. Facturación ágil, pantalla completa y el panel siempre a mano, sin depender del navegador.</p>
                    </div>
                    <div class="feature-card platform-card reveal">
                        <div class="platform-card-head">
                            <div class="feature-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6.4 12.6a5.6 5.6 0 0111.2 0z" />
                                    <path d="M8.8 9.6L7.4 7" />
                                    <path d="M15.2 9.6l1.4-2.6" />
                                    <path d="M10 10.7h.01M14 10.7h.01" />
                                </svg>
                            </div>
                            <span class="platform-tag">Móvil</span>
                        </div>
                        <h3 class="feature-title">Android</h3>
                        <p class="feature-desc">Factura desde el teléfono, consulta stock y atiende pedidos fuera del mostrador con los mismos datos en tiempo real.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
             PRICING
        ============================================================ -->
        <section id="pricing" class="section-wrap">
            <div class="container">
                <div class="section-header center reveal">
                    <span class="section-eyebrow">Precios</span>
                    <h2 class="section-title">Transparencia absoluta</h2>
                    <p class="section-sub">Planes sin restricciones. Escala tu negocio con todas las herramientas desde el primer día.</p>
                </div>

                <div class="pricing-toggle reveal">
                    <span class="toggle-label active" id="label-mensual">Mensual</span>
                    <button class="toggle-switch annual" id="billingToggle" aria-label="Cambiar entre facturación mensual y anual"></button>
                    <span class="toggle-label" id="label-anual">Anual (-58%)</span>
                </div>

                <div class="pricing-grid">
                    <!-- Plan Mensual -->
                    <div class="pricing-card reveal">
                        <div class="pricing-plan">Mensual</div>
                        <div class="pricing-price">
                            <span class="price-cur">$</span>
                            <span class="price-amount" id="price-mensual">8</span>
                            <span class="price-period">/ mes</span>
                        </div>
                        <span class="pricing-trial">Inicia con un mes gratuito</span>
                        <ul class="pricing-features">
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Acceso multi-sucursal</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Múltiples divisas en tiempo real</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Usuarios y roles ilimitados</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Reportes analíticos de ganancias</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Soporte por correo electrónico</li>
                        </ul>
                        <a href="registro.php?plan=mensual" class="btn-secondary btn-block">Seleccionar plan</a>
                    </div>

                    <!-- Plan Anual -->
                    <div class="pricing-card featured reveal">
                        <div class="pricing-plan">Anual</div>
                        <div class="pricing-price">
                            <span class="price-cur">$</span>
                            <span class="price-amount" id="price-anual">40</span>
                            <span class="price-period">/ año</span>
                        </div>
                        <span class="pricing-trial">Facturación anual (Ahorras $56)</span>
                        <ul class="pricing-features">
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> 5 días de publicidad en Facebook Ads (media de ~4.000 vistas diarias) al configurar tu tienda virtual</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Todas las características del plan mensual</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Soporte prioritario 24/7</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Acceso anticipado a nuevas versiones</li>
                            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg> Respaldo de datos automático diario</li>
                        </ul>
                        <a href="registro.php?plan=anual" class="btn-primary btn-block">Seleccionar plan</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================
             CTA FINAL
        ============================================================ -->
        <section id="cta" class="section-wrap" style="border-bottom:none; padding-bottom:80px;">
            <div class="container">
                <div class="cta-box reveal">
                    <h2 class="cta-title">Tu negocio, <span class="primary-color">sincronizado</span></h2>
                    <p class="cta-sub">Da el salto a un control absoluto. Menos fricción administrativa, más tiempo para crecer.</p>
                    <div class="cta-actions">
                        <a href="registro.php" class="btn-primary">
                            Crear cuenta gratis
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ============================================================
         FOOTER
    ============================================================ -->
    <footer>
        <div class="container">
            <div class="footer-inner">
                <div class="nav-logo" style="opacity: 0.6;">
                    <img src="publico/production/images/logo1-inv-compact.png" alt="iSeller logotipo" style="height:24px;">
                </div>
                <nav class="footer-links" aria-label="Pie de página">
                    <a href="#funcionalidades">Funcionalidades</a>
                    <a href="#plataformas">Plataformas</a>
                    <a href="#tasas">Tasas</a>
                    <a href="login.php">Login</a>
                </nav>
                <p class="footer-copy">&copy; 2026 iSeller. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- ============================================================
         OVERLAY DE ACCESO (login / registro)
    ============================================================ -->
    <div class="auth-overlay" id="authOverlay" role="dialog" aria-modal="true" aria-label="Acceso a iSeller" aria-hidden="true">
        <button type="button" class="auth-close" id="authClose" aria-label="Cerrar">&times;</button>
        <iframe id="authFrame" title="Iniciar sesión o registrarse en iSeller"></iframe>
    </div>

    <!-- ============================================================
         SCRIPTS
    ============================================================ -->
    <script>
        (function() {
            'use strict';

            /* ── Navbar ───────────────────────────── */
            const navbar = document.getElementById('navbar');
            window.addEventListener('scroll', () => {
                navbar.classList.toggle('scrolled', window.scrollY > 20);
            }, {
                passive: true
            });

            /* ── Mobile menu ────────────────────────────────────── */
            const navToggle = document.getElementById('navToggle');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileClose = document.getElementById('mobileClose');

            navToggle.addEventListener('click', () => {
                mobileMenu.classList.add('open');
                document.body.style.overflow = 'hidden';
            });
            const closeMenu = () => {
                mobileMenu.classList.remove('open');
                document.body.style.overflow = '';
            };
            mobileClose.addEventListener('click', closeMenu);
            mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));

            /* ── Intersection Observer ──────────────────── */
            const revealObs = new IntersectionObserver((entries) => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        revealObs.unobserve(e.target);
                    }
                });
            }, {
                threshold: 0.1
            });
            document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

            /* ── Live stats ─────────────────────────────── */
            fetch('configurar/stats_landing.php')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('counter-sucursales').textContent = data.sucursales.toLocaleString('es-VE') + '+';
                        document.getElementById('counter-ventas').textContent = data.ventas.toLocaleString('es-VE') + '+';
                        const st = document.getElementById('stat-ventas');
                        let formatoEspanol = new Intl.NumberFormat('es-ES').format(data.ventas_hoy);
                        if (st) st.textContent = '$' + formatoEspanol;
                        const tr = document.getElementById('stat-trans');
                        if (tr) tr.textContent = formatoEspanol;

                        const rateBcv = document.getElementById('rate-bcv');
                        if (rateBcv && data.tasa_bcv) rateBcv.textContent = 'Bs. ' + data.tasa_bcv;
                        const rateCop = document.getElementById('rate-cop');
                        if (rateCop && data.tasa_peso) rateCop.textContent = '$ ' + data.tasa_peso;
                    }
                }).catch(() => {});

            /* ── Billing toggle ───────────────────── */
            const billingToggle = document.getElementById('billingToggle');
            let isAnnual = true;
            billingToggle.addEventListener('click', () => {
                isAnnual = !isAnnual;
                billingToggle.classList.toggle('annual', isAnnual);
                document.getElementById('label-mensual').classList.toggle('active', !isAnnual);
                document.getElementById('label-anual').classList.toggle('active', isAnnual);
            });

            /* ── Acceso en overlay (login / registro) ─────────────── */
            const authOverlay = document.getElementById('authOverlay');
            const authFrame = document.getElementById('authFrame');
            const authClose = document.getElementById('authClose');
            let authCloseTimer = null;

            const openAuth = (src) => {
                clearTimeout(authCloseTimer);
                authFrame.src = src;
                authOverlay.classList.add('open');
                authOverlay.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                mobileMenu.classList.remove('open');
                authClose.focus();
            };

            const closeAuth = () => {
                authOverlay.classList.remove('open');
                authOverlay.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                authCloseTimer = setTimeout(() => {
                    if (!authOverlay.classList.contains('open')) authFrame.src = 'about:blank';
                }, 450);
            };

            document.addEventListener('click', (e) => {
                const link = e.target.closest('a[href^="login.php"], a[href^="registro.php"]');
                if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button > 0) return;
                e.preventDefault();
                openAuth(link.getAttribute('href'));
            });
            authClose.addEventListener('click', closeAuth);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && authOverlay.classList.contains('open')) closeAuth();
            });
            window.addEventListener('pageshow', (e) => {
                if (e.persisted) closeAuth();
            });
            // Al autenticarse, el iframe navega al dashboard: se abre a pantalla completa.
            authFrame.addEventListener('load', () => {
                if (!authOverlay.classList.contains('open')) return;
                let url;
                try {
                    url = authFrame.contentWindow.location;
                } catch (err) {
                    return;
                }
                if (url.href === 'about:blank') return;
                if (/\/(login|registro)\.php$/.test(url.pathname)) return;
                if (url.pathname.indexOf('/publico/production/') !== -1) {
                    window.location.href = url.href;
                    return;
                }
                closeAuth();
            });

        })();
    </script>
</body>

</html>