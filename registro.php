<?php
$plan = in_array($_GET['plan'] ?? '', ['mensual', 'anual'], true) ? $_GET['plan'] : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php $auth_base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/'; ?>
    <base href="<?php echo htmlspecialchars($auth_base, ENT_QUOTES); ?>">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Registro — iSeller</title>
    <meta name="description" content="Regístrate en iSeller. Empieza gratis durante el primer mes.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="publico/production/images/favicon.ico" type="image/ico">

    <!-- SweetAlert2 -->
    <script src="publico/assets/sweetalert2.all.min.js"></script>

    <!-- Google Fonts — same as landing -->
    <link href="publico/vendors/fonts/fonts.css" rel="stylesheet">

    <style>
        /* ============================================================
           DESIGN TOKENS — identical to landing
        ============================================================ */
        :root {
            --bg-base: #080A0C;
            --bg-surface: #101216;
            --bg-card: #15181C;
            --bg-card-hover: #1A1D24;
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(255, 255, 255, 0.15);
            --border-focus: rgba(255, 255, 255, 0.3);

            --accent-1: #3b82f6;
            --accent-2: #06b6d4;
            --accent-grad: var(--accent-primary);
            --success: #10b981;

            --text-primary: #FAFAFA;
            --text-secondary: #8898b4;
            --text-muted: #4a5a76;

            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 22px;
            --radius-xl: 32px;
            --transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
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

        html,
        body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
        }

        /* Noise texture removed */

        /* ============================================================
           LAYOUT
        ============================================================ */
        .split-layout {
            display: flex;
            min-height: 100vh;
            width: 100vw;
            position: relative;
            z-index: 1;
        }

        /* ── PANEL IZQUIERDO ── */
        .panel-left {
            flex: 1 1 45%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px 52px 36px;
            overflow: hidden;
        }

        .panel-left::after {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--bg-surface);
            z-index: 0;
        }

        .panel-left .orb-a {
            display: none;
        }

        .panel-left .orb-b {
            display: none;
        }

        .panel-left .grid-pattern {
            display: none;
        }

        .panel-left>* {
            position: relative;
            z-index: 1;
        }

        /* Left header */
        .left-header a {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }

        .left-header img {
            height: 32px;
            width: auto;
            object-fit: contain;
        }

        /* Left body */
        .left-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 48px 0;
        }

        .trial-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(73, 222, 173, 0.08);
            border: 1px solid rgba(73, 222, 173, 0.18);
            color: #49dead;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 100px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 22px;
            width: fit-content;
        }

        .trial-badge .dot {
            width: 5px;
            height: 5px;
            background: #49dead;
            border-radius: 50%;
        }

        .left-headline {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.8rem, 2.6vw, 2.6rem);
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: var(--text-primary);
            margin-bottom: 16px;
            max-width: 380px;
        }

        .left-headline em {
            font-style: normal;
            color: #49dead;
        }

        .left-subtext {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.75;
            max-width: 360px;
            margin-bottom: 32px;
        }

        .left-subtext strong {
            color: var(--text-primary);
        }

        /* Benefits list */
        .benefits-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-width: 360px;
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .benefit-check {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(73, 222, 173, 0.12);
            border: 1px solid rgba(73, 222, 173, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .benefit-check svg {
            width: 11px;
            height: 11px;
            color: #49dead;
        }

        .benefit-text {
            font-size: 0.84rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .benefit-text strong {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.84rem;
            margin-bottom: 1px;
        }

        /* Left footer */
        .left-footer {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .left-footer a {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }

        .left-footer a:hover {
            color: var(--text-secondary);
        }

        .left-footer .sep {
            color: var(--border);
            font-size: 0.75rem;
        }

        .left-footer .copy {
            margin-left: auto;
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* ── PANEL DERECHO ── */
        .panel-right {
            flex: 0 0 55%;
            background: var(--bg-surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 52px 64px;
            overflow-y: auto;
        }

        .form-wrap {
            width: 100%;
            max-width: 480px;
            animation: fadeUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Back link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            color: var(--text-muted);
            text-decoration: none;
            margin-bottom: 32px;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--text-secondary);
        }

        .back-link svg {
            width: 14px;
            height: 14px;
        }

        /* Step indicator */
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 28px;
        }

        .step-dot {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-dot .dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            border: 1px solid var(--border);
            transition: var(--transition);
        }

        .step-dot .dot.active {
            background: #49dead;
            color: #000;
            border-color: transparent;
        }

        .step-dot .dot.done {
            background: rgba(73, 222, 173, 0.15);
            color: #49dead;
            border-color: rgba(73, 222, 173, 0.3);
        }

        .step-dot .step-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .step-dot .step-label.active {
            color: var(--text-primary);
        }

        .step-line {
            flex: 1;
            height: 1px;
            background: var(--border);
            margin: 0 12px;
            border-radius: 2px;
            transition: var(--transition);
        }

        .step-line.done {
            background: #49dead;
        }

        /* Form title */
        .form-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.03em;
            margin-bottom: 6px;
        }

        .form-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 28px;
            line-height: 1.55;
        }

        /* Labels */
        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 7px;
            letter-spacing: 0.01em;
        }

        /* Field groups */
        .field-group {
            position: relative;
            margin-bottom: 18px;
        }

        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            z-index: 2;
            display: flex;
        }

        .field-icon svg {
            width: 16px;
            height: 16px;
        }

        .field-icon-text {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            z-index: 2;
            font-size: 14px;
        }

        .field-group input,
        .field-group select {
            width: 100%;
            padding: 12px 44px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            color: var(--text-primary);
            outline: none;
            transition: var(--transition);
            appearance: none;
            -webkit-appearance: none;
        }

        .field-group input::placeholder {
            color: var(--text-muted);
        }

        .field-group input:focus,
        .field-group select:focus {
            border-color: var(--border-focus);
            background: var(--bg-surface);
            box-shadow: none;
        }

        /* Select arrow */
        .field-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234a5a76' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
        }

        .field-group select option {
            background: #0d1424;
            color: var(--text-primary);
        }

        /* Select no-icon padding */
        .field-group.no-icon input,
        .field-group.no-icon select {
            padding-left: 14px;
        }

        /* Combobox Tipo de negocio */
        .combobox {
            position: relative;
        }

        .combo-list {
            display: none;
            position: absolute;
            z-index: 30;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            max-height: 240px;
            overflow-y: auto;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
        }

        .combo-list.open {
            display: block;
        }

        .combo-item {
            padding: 10px 14px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
        }

        .combo-item:hover,
        .combo-item.active {
            background: rgba(59, 130, 246, 0.1);
            color: var(--text-primary);
        }

        .combo-empty {
            padding: 10px 14px;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* Toggle password */
        .toggle-pass {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 2px;
            z-index: 2;
            transition: var(--transition);
            display: flex;
        }

        .toggle-pass svg {
            width: 16px;
            height: 16px;
        }

        .toggle-pass:hover {
            color: var(--text-secondary);
        }

        /* Strength bar */
        .strength-bar-wrap {
            margin-top: -8px;
            margin-bottom: 16px;
        }

        .strength-bar {
            height: 3px;
            border-radius: 3px;
            background: var(--border);
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            border-radius: 3px;
            width: 0;
            transition: width 0.35s ease, background 0.35s ease;
        }

        .strength-label {
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        /* Step sections */
        .step-section {
            display: none;
        }

        .step-section.active {
            display: block;
            animation: stepIn 0.3s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes stepIn {
            from {
                opacity: 0;
                transform: translateX(12px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Buttons */
        .btn-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 10px;
        }

        .btn-back {
            padding: 12px 20px;
            background: none;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-back:hover {
            border-color: var(--border-hover);
            color: var(--text-primary);
            background: var(--bg-card);
        }

        .btn-next,
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: #49dead;
            border: none;
            border-radius: var(--radius-sm);
            color: #080A0C;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            letter-spacing: 0.01em;
        }

        .btn-next:hover,
        .btn-submit:hover {
            transform: scale(0.98);
            background: #3ec6a0;
            color: #080A0C;
        }

        .btn-next:active,
        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Messages */
        #form-message-warning {
            color: #f87171;
            font-size: 0.8rem;
            margin-top: 12px;
            padding: 10px 14px;
            background: rgba(248, 113, 113, 0.08);
            border: 1px solid rgba(248, 113, 113, 0.15);
            border-radius: var(--radius-sm);
            display: none;
        }

        #form-message-warning:not(:empty) {
            display: block;
        }

        #form-message-success {
            color: #34d399;
            font-size: 0.8rem;
            margin-top: 10px;
            display: none;
        }

        /* Login note */
        .login-note {
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 20px;
        }

        .login-note a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .login-note a:hover {
            color: var(--accent-1);
        }

        /* Security note */
        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-align: center;
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 18px;
        }

        .security-note svg {
            width: 12px;
            height: 12px;
        }

        /* Plan badge */
        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid var(--border);
            color: var(--accent-2);
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 100px;
            margin-bottom: 16px;
        }

        /* ── Estilos Selector de Planes (Paso 3) ── */
        .plan-cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .plan-choice-card {
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px;
            background: var(--bg-card);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            text-align: left;
        }

        .plan-choice-card:hover {
            border-color: var(--border-hover);
            background: var(--bg-card-hover);
        }

        .plan-choice-card.selected {
            border-color: var(--accent-1);
            background: rgba(59, 130, 246, 0.07);
        }

        .plan-choice-badge {
            position: absolute;
            top: -10px;
            right: 12px;
            background: #10b981;
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .plan-choice-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .plan-choice-price {
            display: flex;
            align-items: baseline;
            gap: 2px;
            margin-bottom: 6px;
        }

        .plan-choice-price .currency {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .plan-choice-price .amount {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .plan-choice-price .period {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .plan-choice-desc {
            font-size: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.35;
        }

        .plan-choice-beneficio {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-top: 10px;
            padding-top: 9px;
            border-top: 1px dashed rgba(16, 185, 129, 0.35);
            font-size: 0.72rem;
            font-weight: 600;
            color: #059669;
            line-height: 1.35;
        }

        .plan-choice-beneficio svg {
            flex-shrink: 0;
            margin-top: 2px;
        }

        .trial-info-box {
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-bottom: 18px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .trial-info-box svg {
            color: #10b981;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .trial-info-text {
            font-size: 0.8rem;
            color: #e2e8f0;
            line-height: 1.4;
        }

        .trial-info-text strong {
            color: #34d399;
        }

        /* ── Responsive ── */
        @media (max-width: 960px) {
            .panel-left {
                display: none;
            }

            .panel-right {
                flex: 1;
                border-left: none;
                padding: 40px 24px;
            }
        }

        @media (max-width: 480px) {
            .panel-right {
                padding: 32px 16px;
            }
        }
    </style>
</head>

<body>
    <div class="split-layout">

        <!-- ══════════════════════════════════
             PANEL IZQUIERDO
        ══════════════════════════════════ -->
        <div class="panel-left">
            <div class="orb-a"></div>
            <div class="orb-b"></div>
            <div class="grid-pattern"></div>

            <!-- Logo -->
            <div class="left-header">
                <a href="/" aria-label="Volver al inicio">
                    <img src="publico/production/images/logo1-inv-compact.png" alt="iSeller logotipo">
                </a>
            </div>

            <!-- Body -->
            <div class="left-body">
                <div class="trial-badge">
                    <span class="dot"></span>
                    1 mes gratis — Sin tarjeta requerida
                </div>
                <h1 class="left-headline">
                    Empieza hoy,<br><em>sin pagar nada.</em>
                </h1>
                <p class="left-subtext">
                    Registra tu negocio y aprovecha todas las herramientas del sistema <strong>gratis durante el primer mes</strong>. Sin compromisos ni restricciones.
                </p>

                <!-- Benefits -->
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <div class="benefit-text">
                            <strong>Ventas e inventario</strong>
                            Controla stock, productos y precios en tiempo real.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <div class="benefit-text">
                            <strong>Múltiples sucursales</strong>
                            Gestiona todas tus sedes desde una sola cuenta.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <div class="benefit-text">
                            <strong>Reportes y estadísticas</strong>
                            Métricas de ventas diarias, semanales y mensuales.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <div class="benefit-text">
                            <strong>Usuarios ilimitados</strong>
                            Agrega todo tu equipo sin coste adicional.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="left-footer">
                <a href="login.php">Iniciar sesión</a>
                <span class="sep">·</span>
                <a href="#">Términos</a>
                <span class="sep">·</span>
                <a href="#">Privacidad</a>
                <span class="copy">&copy; <?php echo date('Y'); ?> iSeller</span>
            </div>
        </div>

        <!-- ══════════════════════════════════
             PANEL DERECHO — FORMULARIO
        ══════════════════════════════════ -->
        <div class="panel-right">
            <div class="form-wrap">

                <a href="/" class="back-link" aria-label="Volver a la página principal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5M12 5l-7 7 7 7" />
                    </svg>
                    Volver al inicio
                </a>

                <?php if (!empty($plan)): ?>
                    <div class="plan-badge">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        Plan <?php echo ucfirst(htmlspecialchars($plan)); ?> seleccionado
                    </div>
                <?php endif; ?>

                <!-- Step indicator -->
                <div class="step-indicator" id="stepIndicator">
                    <div class="step-dot">
                        <div class="dot active" id="dot-1">1</div>
                        <span class="step-label active" id="label-1">Tu negocio</span>
                    </div>
                    <div class="step-line" id="line-1"></div>
                    <div class="step-dot">
                        <div class="dot" id="dot-2">2</div>
                        <span class="step-label" id="label-2">Tu cuenta</span>
                    </div>
                    <?php if (empty($plan)): ?>
                        <div class="step-line" id="line-2"></div>
                        <div class="step-dot">
                            <div class="dot" id="dot-3">3</div>
                            <span class="step-label" id="label-3">Tu plan</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Título dinámico -->
                <h2 class="form-title" id="form-title">Datos del negocio</h2>
                <p class="form-subtitle" id="form-subtitle">Cuéntanos sobre tu negocio para comenzar.</p>

                <form id="formRegistro" method="post" novalidate>

                    <!-- ════════════════════════ -->
                    <!--  PASO 1 — Datos negocio  -->
                    <!-- ════════════════════════ -->
                    <div id="step-1" class="step-section active">

                        <label for="tipo_negocio">Tipo de negocio</label>
                        <div class="field-group no-icon combobox" id="tipo-negocio-wrap">
                            <input type="text" id="tipo_negocio" name="tipo_negocio"
                                placeholder="Selecciona o escribe tu tipo" required autocomplete="off">
                            <div class="combo-list" id="tipo-negocio-list"></div>
                        </div>

                        <label for="nombre_negocio">Nombre del negocio</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                </svg>
                            </span>
                            <input type="text" id="nombre_negocio" name="nombre_negocio" placeholder="Ej: Mi Tienda C.A." required>
                        </div>

                        <div class="btn-row">
                            <button type="button" class="btn-next" onclick="nextStep(2)">
                                Siguiente
                                <svg style="display:inline;margin-left:6px;vertical-align:middle" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14M12 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                    </div><!-- /step-1 -->

                    <!-- ════════════════════════ -->
                    <!--  PASO 2 — Datos usuario  -->
                    <!-- ════════════════════════ -->
                    <div id="step-2" class="step-section">

                        <label for="nombres">Nombre completo</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <input type="text" id="nombres" name="nombres" placeholder="Tu nombre completo" required>
                        </div>

                        <label for="email">Correo electrónico</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="4" width="20" height="16" rx="2" />
                                    <path d="M2 7l10 7 10-7" />
                                </svg>
                            </span>
                            <input type="email" id="email" name="email" placeholder="correo@negocio.com" required>
                        </div>

                        <label for="password">Contraseña</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0110 0v4" />
                                </svg>
                            </span>
                            <input type="password" id="password" name="password" placeholder="Mínimo 5 caracteres" required>
                            <button type="button" class="toggle-pass" id="togglePass1" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <svg id="eye1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>

                        <!-- Strength bar -->
                        <div class="strength-bar-wrap">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <span class="strength-label" id="strengthLabel">Introduce una contraseña</span>
                        </div>

                        <label for="confirmar_password">Repetir contraseña</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0110 0v4" />
                                </svg>
                            </span>
                            <input type="password" id="confirmar_password" name="confirmar_password" placeholder="Repite la contraseña" required>
                            <button type="button" class="toggle-pass" id="togglePass2" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <svg id="eye2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>

                        <div class="btn-row">
                            <button type="button" class="btn-back" onclick="prevStep(1)">
                                <svg style="display:inline;margin-right:4px;vertical-align:middle" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 12H5M12 5l-7 7 7 7" />
                                </svg>
                                Atrás
                            </button>
                            <?php if (!empty($plan)): ?>
                                <button type="submit" class="btn-submit" id="btnSubmit">
                                    Crear cuenta
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-next" onclick="nextStep(3)">
                                    Siguiente
                                    <svg style="display:inline;margin-left:6px;vertical-align:middle" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M5 12h14M12 5l7 7-7 7" />
                                    </svg>
                                </button>
                            <?php endif; ?>
                        </div>

                    </div><!-- /step-2 -->

                    <?php if (empty($plan)): ?>
                        <!-- ════════════════════════ -->
                        <!--  PASO 3 — Selección Plan -->
                        <!-- ════════════════════════ -->
                        <div id="step-3" class="step-section">

                            <input type="hidden" id="selected_plan" name="plan" value="mensual">

                            <div class="trial-info-box">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                <div class="trial-info-text">
                                    <strong>¡1 mes de prueba gratis incluido!</strong><br>
                                    No pagarás nada hoy. Tu primer mes es 100% libre de costo. Elige el plan con el que deseas continuar cuando finalice tu tiempo de prueba.
                                </div>
                            </div>

                            <div class="plan-cards-grid">
                                <!-- Plan Mensual -->
                                <div class="plan-choice-card selected" id="card-mensual" onclick="selectPlan('mensual')">
                                    <div class="plan-choice-title">Plan Mensual</div>
                                    <div class="plan-choice-price">
                                        <span class="currency">$</span>
                                        <span class="amount">8</span>
                                        <span class="period">/ mes</span>
                                    </div>
                                    <div class="plan-choice-desc">
                                        Pago mes a mes después de tus 30 días gratis. Sin compromisos.
                                    </div>
                                </div>

                                <!-- Plan Anual -->
                                <div class="plan-choice-card" id="card-anual" onclick="selectPlan('anual')">
                                    <div class="plan-choice-badge">Ahorra 58%</div>
                                    <div class="plan-choice-title">Plan Anual</div>
                                    <div class="plan-choice-price">
                                        <span class="currency">$</span>
                                        <span class="amount">40</span>
                                        <span class="period">/ año</span>
                                    </div>
                                    <div class="plan-choice-desc">
                                        Equivale a solo $3.33/mes al terminar tu prueba gratuita.
                                    </div>
                                    <div class="plan-choice-beneficio">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        Incluye 5 días de publicidad en Facebook Ads (media de ~4.000 vistas diarias) al configurar tu tienda virtual
                                    </div>
                                </div>
                            </div>

                            <div class="btn-row">
                                <button type="button" class="btn-back" onclick="prevStep(2)">
                                    <svg style="display:inline;margin-right:4px;vertical-align:middle" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19 12H5M12 5l-7 7 7 7" />
                                    </svg>
                                    Atrás
                                </button>
                                <button type="submit" class="btn-submit" id="btnSubmit">
                                    Empezar mi mes gratis
                                </button>
                            </div>

                        </div><!-- /step-3 -->
                    <?php endif; ?>

                </form>

                <!-- Mensajes -->
                <div id="form-message-warning"></div>
                <div id="form-message-success">Registro exitoso. Redirigiendo...</div>

                <!-- Link a login -->
                <p class="login-note">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
                </p>

                <p class="security-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0110 0v4" />
                    </svg>
                    Acceso seguro · Datos protegidos
                </p>

            </div><!-- /form-wrap -->
        </div><!-- /panel-right -->

    </div><!-- /split-layout -->

    <!-- Formulario fantasma para compatibilidad con login.js -->
    <form name="data_form" style="display:none;">
        <input type="text" id="login" name="login">
        <input type="password" id="hidden_password" name="password">
    </form>

    <!-- External JS -->
    <script type="text/javascript" src="publico/vendors/jquery/jquery-3.7.1.min.js"></script>

    <script>
        const plan = <?php echo json_encode($plan); ?>;

        /* ── Step metadata ────────────────────────────── */
        const stepMeta = {
            1: {
                title: 'Datos del negocio',
                sub: 'Cuéntanos sobre tu negocio para comenzar.'
            },
            2: {
                title: 'Crea tu cuenta',
                sub: 'Completa tus datos de acceso al sistema.'
            },
            3: {
                title: 'Selecciona tu plan',
                sub: 'Empiezas con 1 mes gratis. Elige el plan para cuando termine tu prueba.'
            }
        };

        const totalSteps = plan ? 2 : 3;

        function updateStepUI(step) {
            if (stepMeta[step]) {
                document.getElementById('form-title').textContent = stepMeta[step].title;
                document.getElementById('form-subtitle').textContent = stepMeta[step].sub;
            }

            for (let n = 1; n <= totalSteps; n++) {
                const dot = document.getElementById('dot-' + n);
                const label = document.getElementById('label-' + n);
                if (!dot || !label) continue;
                dot.classList.remove('active', 'done');
                label.classList.remove('active');
                if (n < step) {
                    dot.classList.add('done');
                    dot.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
                } else if (n === step) {
                    dot.classList.add('active');
                    dot.textContent = n;
                } else {
                    dot.textContent = n;
                }
                if (n === step) label.classList.add('active');
            }

            const line1 = document.getElementById('line-1');
            if (line1) step > 1 ? line1.classList.add('done') : line1.classList.remove('done');

            const line2 = document.getElementById('line-2');
            if (line2) step > 2 ? line2.classList.add('done') : line2.classList.remove('done');
        }

        /* ── Selector de Plan (Paso 3) ────────────────── */
        function selectPlan(planVal) {
            const hidden = document.getElementById('selected_plan');
            if (hidden) hidden.value = planVal;

            document.querySelectorAll('.plan-choice-card').forEach(c => c.classList.remove('selected'));
            const selectedCard = document.getElementById('card-' + planVal);
            if (selectedCard) selectedCard.classList.add('selected');
        }

        /* ── Combobox Tipo de negocio ─────────────────── */
        const TIPOS_NEGOCIO = [
            'Minimarket', 'Supermercado', 'Bodega / Almacén', 'Farmacia', 'Óptica',
            'Restaurante', 'Cafetería', 'Panadería', 'Heladería', 'Ferretería',
            'Tienda de Ropa', 'Boutique', 'Calzado', 'Tecnología / Celulares',
            'Electrodomésticos', 'Librería / Papelería', 'Juguetes',
            'Perfumería / Cosméticos', 'Pet Shop / Veterinaria', 'Reparto automotriz',
            'Distribuidora / Mayorista', 'Floristería', 'Gimnasio / Deportes',
            'Salón de belleza', 'Farmacia veterinaria', 'Lavandería', 'Peluquería'
        ];
        const tipoInput = document.getElementById('tipo_negocio');
        const tipoList = document.getElementById('tipo-negocio-list');
        let comboActive = -1;

        const normCombo = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

        function renderCombo(filter) {
            const f = normCombo(filter.trim());
            const items = TIPOS_NEGOCIO.filter(t => !f || normCombo(t).includes(f));
            tipoList.innerHTML = items.length ?
                items.map(t => '<div class="combo-item" data-v="' + t + '">' + t + '</div>').join('') :
                '<div class="combo-empty">Sin coincidencias — puedes dejar escrito tu tipo personalizado</div>';
            comboActive = -1;
        }

        function openCombo() {
            renderCombo(tipoInput.value);
            tipoList.classList.add('open');
        }

        function closeCombo() {
            tipoList.classList.remove('open');
            comboActive = -1;
        }

        tipoInput.addEventListener('focus', openCombo);
        tipoInput.addEventListener('input', openCombo);
        tipoInput.addEventListener('keydown', function(e) {
            const its = tipoList.querySelectorAll('.combo-item');
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!tipoList.classList.contains('open')) openCombo();
                const list = tipoList.querySelectorAll('.combo-item');
                if (!list.length) return;
                comboActive = e.key === 'ArrowDown' ?
                    Math.min(comboActive + 1, list.length - 1) :
                    Math.max(comboActive - 1, 0);
                list.forEach(el => el.classList.remove('active'));
                list[comboActive].classList.add('active');
                list[comboActive].scrollIntoView({
                    block: 'nearest'
                });
            } else if (e.key === 'Enter') {
                if (tipoList.classList.contains('open') && comboActive > -1 && its[comboActive]) {
                    e.preventDefault();
                    tipoInput.value = its[comboActive].dataset.v;
                    closeCombo();
                }
            } else if (e.key === 'Escape') {
                closeCombo();
            }
        });
        tipoList.addEventListener('mousedown', function(e) {
            const item = e.target.closest('.combo-item');
            if (item) {
                e.preventDefault();
                tipoInput.value = item.dataset.v;
                closeCombo();
            }
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#tipo-negocio-wrap')) closeCombo();
        });

        /* ── Navigation ───────────────────────────────── */
        function nextStep(step) {
            if (step === 2) {
                const tipo = document.getElementById('tipo_negocio').value.trim();
                const nombre = document.getElementById('nombre_negocio').value.trim();

                if (!tipo) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta información',
                        text: 'Por favor selecciona un tipo de negocio.',
                        background: '#101216',
                        color: '#f0f4ff'
                    });
                    return;
                }
                if (!nombre) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta información',
                        text: 'Por favor ingresa el nombre del negocio.',
                        background: '#101216',
                        color: '#f0f4ff'
                    });
                    return;
                }
            } else if (step === 3) {
                const nombres = document.getElementById('nombres').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const confirmar = document.getElementById('confirmar_password').value;

                if (!nombres || !email) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Falta información',
                        text: 'Por favor completa tu nombre y correo electrónico.',
                        background: '#101216',
                        color: '#f0f4ff'
                    });
                    return;
                }
                if (password.length < 5) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Contraseña débil',
                        text: 'La contraseña debe tener al menos 5 caracteres.',
                        background: '#101216',
                        color: '#f0f4ff'
                    });
                    return;
                }
                if (password !== confirmar) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No coinciden',
                        text: 'Las contraseñas no coinciden.',
                        background: '#101216',
                        color: '#f0f4ff'
                    });
                    return;
                }
            }
            document.querySelectorAll('.step-section').forEach(el => el.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');
            updateStepUI(step);
        }

        function prevStep(step) {
            document.querySelectorAll('.step-section').forEach(el => el.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');
            updateStepUI(step);
        }

        /* ── Toggle passwords ─────────────────────────── */
        function makeToggle(btnId, inputId, iconId) {
            document.getElementById(btnId).addEventListener('click', function() {
                const inp = document.getElementById(inputId);
                const show = inp.type === 'password';
                inp.type = show ? 'text' : 'password';
                document.getElementById(iconId).innerHTML = show ?
                    '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>' :
                    '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            });
        }
        makeToggle('togglePass1', 'password', 'eye1');
        makeToggle('togglePass2', 'confirmar_password', 'eye2');

        /* ── Strength bar ─────────────────────────────── */
        document.getElementById('password').addEventListener('input', function() {
            const val = this.value;
            const fill = document.getElementById('strengthFill');
            const label = document.getElementById('strengthLabel');
            let score = 0;
            if (val.length >= 5) score++;
            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [{
                    w: '0%',
                    bg: 'var(--border)',
                    text: 'Introduce una contraseña'
                },
                {
                    w: '25%',
                    bg: '#f87171',
                    text: 'Muy débil'
                },
                {
                    w: '50%',
                    bg: '#fb923c',
                    text: 'Débil'
                },
                {
                    w: '75%',
                    bg: '#facc15',
                    text: 'Aceptable'
                },
                {
                    w: '90%',
                    bg: '#34d399',
                    text: 'Buena'
                },
                {
                    w: '100%',
                    bg: 'var(--success)',
                    text: 'Excelente'
                }
            ];
            const lv = levels[Math.min(score, 5)];
            fill.style.width = lv.w;
            fill.style.background = lv.bg;
            label.textContent = lv.text;
            label.style.color = score > 0 ? lv.bg : 'var(--text-muted)';
        });

        /* ── Form submit ──────────────────────────────── */
        document.getElementById('formRegistro').addEventListener('submit', function(e) {
            e.preventDefault();

            const warning = document.getElementById('form-message-warning');
            const password = document.getElementById('password').value;
            const confirmar = document.getElementById('confirmar_password').value;
            const btn = document.getElementById('btnSubmit');

            warning.innerText = '';

            if (password.length < 5) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña débil',
                    text: 'La contraseña debe tener al menos 5 caracteres.',
                    background: '#101216',
                    color: '#f0f4ff'
                });
                return;
            }
            if (password !== confirmar) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No coinciden',
                    text: 'Las contraseñas no coinciden.',
                    background: '#101216',
                    color: '#f0f4ff'
                });
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Creando cuenta...';

            const formData = new FormData(this);
            const chosenPlan = plan || (document.getElementById('selected_plan') ? document.getElementById('selected_plan').value : 'mensual');
            formData.set('plan', chosenPlan);

            fetch('configurar/register_process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Cuenta creada!',
                            text: 'Inicia sesión para continuar...',
                            timer: 1800,
                            showConfirmButton: true,
                            background: '#101216',
                            color: '#f0f4ff',
                            iconColor: '#10b981'
                        }).then(() => {
                            window.location.href = 'login.php';
                        });
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Crear cuenta';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error en el registro.',
                            background: '#101216',
                            color: '#f0f4ff'
                        });
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.textContent = 'Crear cuenta';
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Sin conexión',
                        text: 'Error al conectar con el servidor.',
                        background: '#101216',
                        color: '#f0f4ff'
                    });
                });
        });
    </script>

</body>

</html>