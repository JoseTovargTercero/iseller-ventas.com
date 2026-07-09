<?php
$plan = '';
if (isset($_GET['plan'])) {
    $plan = $_GET['plan'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Registro — iSeller</title>
    <meta name="description" content="Regístrate en iSeller. Empieza gratis durante los primeros 3 meses.">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="publico/production/images/favicon.ico" type="image/ico">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Google Fonts — same as landing -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           DESIGN TOKENS — identical to landing
        ============================================================ */
        :root {
            --bg-base: #080A0C;
            --bg-surface: #101216;
            --bg-card: #15181C;
            --bg-card-hover: #1A1D24;
            --border:         rgba(255,255,255,0.08);
            --border-hover: rgba(255,255,255,0.15);
            --border-focus: rgba(255,255,255,0.3);

            --accent-1:       #3b82f6;
            --accent-2:       #06b6d4;
            --accent-grad: var(--accent-primary);
            --success:        #10b981;

            --text-primary: #FAFAFA;
            --text-secondary: #8898b4;
            --text-muted:     #4a5a76;

            --radius-sm:      8px;
            --radius-md:      14px;
            --radius-lg:      22px;
            --radius-xl:      32px;
            --transition:     all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ============================================================
           RESET & BASE
        ============================================================ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
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

        .panel-left .orb-a { display: none; }
        .panel-left .orb-b { display: none; }
        .panel-left .grid-pattern { display: none; }

        .panel-left > * { position: relative; z-index: 1; }

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
            justify-content: center;
            padding: 48px 0;
        }

        .trial-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.15);
            color: var(--success);
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
            width: 5px; height: 5px;
            background: var(--success);
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
            color: var(--text-primary);
        }

        .left-subtext {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.75;
            max-width: 360px;
            margin-bottom: 32px;
        }
        .left-subtext strong { color: var(--text-primary); }

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
            width: 22px; height: 22px;
            border-radius: 50%;
            background: rgba(16,185,129,0.12);
            border: 1px solid rgba(16,185,129,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .benefit-check svg { width: 11px; height: 11px; color: var(--success); }
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
        .left-footer a:hover { color: var(--text-secondary); }
        .left-footer .sep { color: var(--border); font-size: 0.75rem; }
        .left-footer .copy { margin-left: auto; font-size: 0.72rem; color: var(--text-muted); }

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
            animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
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
        .back-link:hover { color: var(--text-secondary); }
        .back-link svg { width: 14px; height: 14px; }

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
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(255,255,255,0.05);
            color: var(--text-muted);
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        .step-dot .dot.active {
            background: var(--accent-primary);
            color: var(--accent-on-primary);
            border-color: transparent;
        }
        .step-dot .dot.done {
            background: rgba(16,185,129,0.15);
            color: var(--success);
            border-color: rgba(16,185,129,0.3);
        }
        .step-dot .step-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: var(--transition);
        }
        .step-dot .step-label.active { color: var(--text-primary); }
        .step-line {
            flex: 1;
            height: 1px;
            background: var(--border);
            margin: 0 12px;
            border-radius: 2px;
            transition: var(--transition);
        }
        .step-line.done { background: var(--success); }

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
        .field-icon svg { width: 16px; height: 16px; }
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
        .field-group input::placeholder { color: var(--text-muted); }
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
        .toggle-pass svg { width: 16px; height: 16px; }
        .toggle-pass:hover { color: var(--text-secondary); }

        /* Otro tipo wrap */
        .otro-tipo-wrap {
            display: none;
            margin-bottom: 18px;
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
        .step-section { display: none; }
        .step-section.active {
            display: block;
            animation: stepIn 0.3s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes stepIn {
            from { opacity: 0; transform: translateX(12px); }
            to   { opacity: 1; transform: translateX(0); }
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
            background: var(--accent-primary);
            border: none;
            border-radius: var(--radius-sm);
            color: var(--accent-on-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: var(--transition);
            letter-spacing: 0.01em;
        }
        .btn-next:hover,
        .btn-submit:hover {
            transform: scale(0.98);
            background: #E4E4E7;
        }
        .btn-next:active, .btn-submit:active { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        /* Messages */
        #form-message-warning {
            color: #f87171;
            font-size: 0.8rem;
            margin-top: 12px;
            padding: 10px 14px;
            background: rgba(248,113,113,0.08);
            border: 1px solid rgba(248,113,113,0.15);
            border-radius: var(--radius-sm);
            display: none;
        }
        #form-message-warning:not(:empty) { display: block; }
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
        .login-note a:hover { color: var(--accent-1); }

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
        .security-note svg { width: 12px; height: 12px; }

        /* Plan badge */
        .plan-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(59,130,246,0.08);
            border: 1px solid var(--border);
            color: var(--accent-2);
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 100px;
            margin-bottom: 16px;
        }

        /* ── Responsive ── */
        @media (max-width: 960px) {
            .panel-left { display: none; }
            .panel-right {
                flex: 1;
                border-left: none;
                padding: 40px 24px;
            }
        }
        @media (max-width: 480px) {
            .panel-right { padding: 32px 16px; }
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
                    3 meses gratis — Sin tarjeta requerida
                </div>
                <h1 class="left-headline">
                    Empieza hoy,<br><em>sin pagar nada.</em>
                </h1>
                <p class="left-subtext">
                    Registra tu negocio y aprovecha todas las herramientas del sistema <strong>gratis durante los primeros 3 meses</strong>. Sin compromisos ni restricciones.
                </p>

                <!-- Benefits -->
                <div class="benefits-list">
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div class="benefit-text">
                            <strong>Ventas e inventario</strong>
                            Controla stock, productos y precios en tiempo real.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div class="benefit-text">
                            <strong>Múltiples sucursales</strong>
                            Gestiona todas tus sedes desde una sola cuenta.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <div class="benefit-text">
                            <strong>Reportes y estadísticas</strong>
                            Métricas de ventas diarias, semanales y mensuales.
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-check">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                    Volver al inicio
                </a>

                <?php if (!empty($plan)): ?>
                <div class="plan-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
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
                        <div class="field-group no-icon">
                            <select id="tipo_negocio" name="tipo_negocio" required>
                                <option value="" disabled selected>Selecciona un tipo</option>
                                <option value="minimarket">Minimarket</option>
                                <option value="farmacia">Farmacia</option>
                                <option value="restaurante">Restaurante</option>
                                <option value="ferreteria">Ferretería</option>
                                <option value="tienda_ropa">Tienda de Ropa</option>
                                <option value="tecnologia">Tecnología</option>
                                <option value="otro">Otro (Especificar)</option>
                            </select>
                        </div>

                        <!-- Campo condicional -->
                        <div class="otro-tipo-wrap" id="otro_tipo_container">
                            <label for="otro_tipo">Especifica el tipo de negocio</label>
                            <div class="field-group">
                                <span class="field-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                </span>
                                <input type="text" id="otro_tipo" name="otro_tipo" placeholder="Ej: Librería, Panadería...">
                            </div>
                        </div>

                        <label for="nombre_negocio">Nombre del negocio</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                            </span>
                            <input type="text" id="nombre_negocio" name="nombre_negocio" placeholder="Ej: Mi Tienda C.A." required>
                        </div>

                        <div class="btn-row">
                            <button type="button" class="btn-next" onclick="nextStep(2)">
                                Siguiente
                                <svg style="display:inline;margin-left:6px;vertical-align:middle" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input type="text" id="nombres" name="nombres" placeholder="Tu nombre completo" required>
                        </div>

                        <label for="email">Correo electrónico</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 7l10 7 10-7"/></svg>
                            </span>
                            <input type="email" id="email" name="email" placeholder="correo@negocio.com" required>
                        </div>

                        <label for="password">Contraseña</label>
                        <div class="field-group">
                            <span class="field-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            </span>
                            <input type="password" id="password" name="password" placeholder="Mínimo 5 caracteres" required>
                            <button type="button" class="toggle-pass" id="togglePass1" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <svg id="eye1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
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
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            </span>
                            <input type="password" id="confirmar_password" name="confirmar_password" placeholder="Repite la contraseña" required>
                            <button type="button" class="toggle-pass" id="togglePass2" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                                <svg id="eye2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>

                        <div class="btn-row">
                            <button type="button" class="btn-back" onclick="prevStep(1)">
                                <svg style="display:inline;margin-right:4px;vertical-align:middle" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                                Atrás
                            </button>
                            <button type="submit" class="btn-submit" id="btnSubmit">
                                Crear cuenta
                            </button>
                        </div>

                    </div><!-- /step-2 -->

                </form>

                <!-- Mensajes -->
                <div id="form-message-warning"></div>
                <div id="form-message-success">Registro exitoso. Redirigiendo...</div>

                <!-- Link a login -->
                <p class="login-note">
                    ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
                </p>

                <p class="security-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
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
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script>
        const plan = "<?php echo $plan; ?>";

        /* ── Step metadata ────────────────────────────── */
        const stepMeta = {
            1: { title: 'Datos del negocio',  sub: 'Cuéntanos sobre tu negocio para comenzar.' },
            2: { title: 'Crea tu cuenta',      sub: 'Completa tus datos de acceso al sistema.' }
        };

        function updateStepUI(step) {
            document.getElementById('form-title').textContent    = stepMeta[step].title;
            document.getElementById('form-subtitle').textContent = stepMeta[step].sub;

            [1, 2].forEach(n => {
                const dot   = document.getElementById('dot-' + n);
                const label = document.getElementById('label-' + n);
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
            });

            const line = document.getElementById('line-1');
            step > 1 ? line.classList.add('done') : line.classList.remove('done');
        }

        /* ── Navigation ───────────────────────────────── */
        function nextStep(step) {
            if (step === 2) {
                const tipo   = document.getElementById('tipo_negocio').value;
                const nombre = document.getElementById('nombre_negocio').value.trim();
                const otro   = document.getElementById('otro_tipo').value.trim();

                if (!tipo) { Swal.fire({ icon: 'warning', title: 'Falta información', text: 'Por favor selecciona un tipo de negocio.', background: '#101216', color: '#f0f4ff' }); return; }
                if (tipo === 'otro' && !otro) { Swal.fire({ icon: 'warning', title: 'Falta información', text: 'Por favor especifica el tipo de negocio.', background: '#101216', color: '#f0f4ff' }); return; }
                if (!nombre) { Swal.fire({ icon: 'warning', title: 'Falta información', text: 'Por favor ingresa el nombre del negocio.', background: '#101216', color: '#f0f4ff' }); return; }
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

        /* ── Otro tipo toggle ─────────────────────────── */
        document.getElementById('tipo_negocio').addEventListener('change', function () {
            document.getElementById('otro_tipo_container').style.display =
                this.value === 'otro' ? 'block' : 'none';
        });

        /* ── Toggle passwords ─────────────────────────── */
        function makeToggle(btnId, inputId, iconId) {
            document.getElementById(btnId).addEventListener('click', function () {
                const inp  = document.getElementById(inputId);
                const show = inp.type === 'password';
                inp.type = show ? 'text' : 'password';
                document.getElementById(iconId).innerHTML = show
                    ? '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
                    : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            });
        }
        makeToggle('togglePass1', 'password', 'eye1');
        makeToggle('togglePass2', 'confirmar_password', 'eye2');

        /* ── Strength bar ─────────────────────────────── */
        document.getElementById('password').addEventListener('input', function () {
            const val  = this.value;
            const fill  = document.getElementById('strengthFill');
            const label = document.getElementById('strengthLabel');
            let score = 0;
            if (val.length >= 5)          score++;
            if (val.length >= 8)          score++;
            if (/[A-Z]/.test(val))        score++;
            if (/[0-9]/.test(val))        score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { w: '0%',   bg: 'var(--border)',    text: 'Introduce una contraseña' },
                { w: '25%',  bg: '#f87171',           text: 'Muy débil' },
                { w: '50%',  bg: '#fb923c',           text: 'Débil' },
                { w: '75%',  bg: '#facc15',           text: 'Aceptable' },
                { w: '90%',  bg: '#34d399',           text: 'Buena' },
                { w: '100%', bg: 'var(--success)',    text: 'Excelente' }
            ];
            const lv = levels[Math.min(score, 5)];
            fill.style.width      = lv.w;
            fill.style.background = lv.bg;
            label.textContent     = lv.text;
            label.style.color     = score > 0 ? lv.bg : 'var(--text-muted)';
        });

        /* ── Form submit ──────────────────────────────── */
        document.getElementById('formRegistro').addEventListener('submit', function (e) {
            e.preventDefault();

            const warning   = document.getElementById('form-message-warning');
            const password  = document.getElementById('password').value;
            const confirmar = document.getElementById('confirmar_password').value;
            const btn       = document.getElementById('btnSubmit');

            warning.innerText = '';

            if (password.length < 5) {
                Swal.fire({ icon: 'warning', title: 'Contraseña débil', text: 'La contraseña debe tener al menos 5 caracteres.', background: '#101216', color: '#f0f4ff' });
                return;
            }
            if (password !== confirmar) {
                Swal.fire({ icon: 'warning', title: 'No coinciden', text: 'Las contraseñas no coinciden.', background: '#101216', color: '#f0f4ff' });
                return;
            }

            btn.disabled    = true;
            btn.textContent = 'Creando cuenta...';

            const formData = new FormData(this);
            formData.append('plan', plan);

            fetch('configurar/register_process.php', { method: 'POST', body: formData })
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
                        }).then(() => { window.location.href = 'login.php'; });
                    } else {
                        btn.disabled    = false;
                        btn.textContent = 'Crear cuenta';
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Error en el registro.', background: '#101216', color: '#f0f4ff' });
                    }
                })
                .catch(err => {
                    btn.disabled    = false;
                    btn.textContent = 'Crear cuenta';
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Sin conexión', text: 'Error al conectar con el servidor.', background: '#101216', color: '#f0f4ff' });
                });
        });
    </script>

</body>
</html>