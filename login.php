<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Iniciar sesión — iSeller</title>
    <meta name="description" content="Gestiona tu negocio desde un solo lugar. Ingresa a tu cuenta de iSeller.">
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
            --border: rgba(255, 255, 255, 0.08);
            --border-hover: rgba(255,255,255,0.15);
            --border-focus: rgba(255,255,255,0.3);

            --accent-1: #3b82f6;
            --accent-2: #06b6d4;
            --accent-grad: var(--accent-primary);

            --text-primary: #FAFAFA;
            --text-secondary: #8898b4;
            --text-muted: #4a5a76;

            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 22px;
            --radius-xl: 32px;
            --transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-card: 0 8px 40px rgba(0, 0, 0, 0.55);
            --shadow-glow: 0 0 60px rgba(59, 130, 246, 0.12);
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
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        /* ── PANEL IZQUIERDO ── */
        .panel-left {
            flex: 1 1 55%;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 40px 52px 36px;
            overflow: hidden;
        }

        /* Gradient background */
        .panel-left::after {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--bg-surface);
            z-index: 0;
        }

        /* Orb decorations */
        .panel-left .orb-a {
            display: none;
        }

        .panel-left .orb-b {
            display: none;
        }

        /* Grid pattern */
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
            justify-content: center;
            padding: 48px 0;
        }

        .left-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            font-size: 0.72rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 100px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 22px;
            width: fit-content;
        }

        .left-eyebrow .dot {
            width: 5px;
            height: 5px;
            background: var(--brand-blue);
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }

        @keyframes pulse-dot {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: .4;
                transform: scale(0.6);
            }
        }

        .left-headline {
            font-family: 'Sora', sans-serif;
            font-size: clamp(1.9rem, 2.8vw, 2.8rem);
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: var(--text-primary);
            margin-bottom: 18px;
            max-width: 420px;
        }

        .left-headline em {
            font-style: normal;
            color: var(--text-primary);
        }

        .left-subtext {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.75;
            max-width: 380px;
            margin-bottom: 36px;
        }

        /* Stats card */
        .stats-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 22px 26px;
            max-width: 380px;

        }

        .stats-card-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 18px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .stat-item {}

        .stat-value {
            font-family: 'Sora', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .stats-divider {
            width: 100%;
            height: 1px;
            background: var(--border);
            grid-column: span 2;
            margin: 4px 0;
        }

        /* Status badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.04);
            border: 1px solid rgba(16, 185, 129, 0.15);
            border-radius: 100px;
            padding: 6px 14px 6px 8px;
            margin-top: 14px;
            width: fit-content;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
        }

        .status-text {
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.65);
        }

        .status-text strong {
            color: #10b981;
            font-weight: 600;
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
            flex: 0 0 44%;
            background: var(--bg-surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 52px 60px;
            overflow-y: auto;
        }

        .form-wrap {
            width: 100%;
            max-width: 360px;
        }

        /* Back to landing link */
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

        /* Form header */
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
            margin-bottom: 32px;
            line-height: 1.55;
        }

        /* Labels */
        .field-label-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 7px;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            letter-spacing: 0.01em;
        }

        .label-link {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }

        .label-link:hover {
            color: var(--accent-2);
        }

        /* Input group */
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
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

        .input-group-custom input {
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
        }

        .input-group-custom input::placeholder {
            color: var(--text-muted);
        }

        .input-group-custom input:focus {
            border-color: var(--border-focus);
            background: var(--bg-surface);
            box-shadow: none;
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

        /* Submit button */
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

        .btn-submit:hover {
            transform: scale(0.98);
            background: #E4E4E7;
        }

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
            margin-top: 12px;
            display: none;
        }

        /* Register note */
        .register-note {
            text-align: center;
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 24px;
        }

        .register-note a {
            color: var(--accent-2);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .register-note a:hover {
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
            margin-top: 24px;
        }

        .security-note svg {
            width: 12px;
            height: 12px;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider-text {
            font-size: 0.72rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        /* ── Responsive ── */
        @media (max-width: 900px) {
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
                padding: 32px 20px;
            }
        }

        /* Fade-in animation */
        .form-wrap {
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
                <div class="left-eyebrow">
                    <span class="dot"></span>
                    Sistema activo
                </div>
                <h1 class="left-headline">
                    Gestiona tu negocio<br>desde <em>un solo lugar</em>
                </h1>
                <p class="left-subtext">
                    Ventas, inventario, sucursales y reportes en tiempo real. Rápido, seguro y siempre disponible desde cualquier dispositivo.
                </p>

                <!-- Stats card -->
                <div class="stats-card">
                    <div class="stats-card-label">Resumen de la plataforma</div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value" id="stat-ventas">—</div>
                            <div class="stat-label">Ventas totales</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="stat-negocios">—</div>
                            <div class="stat-label">Negocios</div>
                        </div>
                        <div class="stats-divider"></div>
                        <div class="stat-item">
                            <div class="stat-value" id="stat-sucursales">—</div>
                            <div class="stat-label">Sucursales activas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="stat-usuarios">—</div>
                            <div class="stat-label">Usuarios</div>
                        </div>
                    </div>
                    <div class="status-badge">
                        <span class="status-dot"></span>
                        <span class="status-text"><strong>Sistema operativo</strong> — Todos los servicios funcionando</span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="left-footer">
                <a href="registro.php">Registrarse</a>
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

                <h2 class="form-title">Ingresa a tu cuenta</h2>
                <p class="form-subtitle">Bienvenido de nuevo. Introduce tus credenciales para continuar.</p>

                <form name="data_form" novalidate>

                    <!-- Correo -->
                    <div>
                        <label for="login">Correo o usuario</label>
                    </div>
                    <div class="input-group-custom">
                        <span class="field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2" />
                                <path d="M2 7l10 7 10-7" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            placeholder="correo@negocio.com o usuario"
                            autocomplete="username"
                            required>
                    </div>

                    <!-- Contraseña -->
                    <div class="field-label-row">
                        <label for="password">Contraseña</label>
                        <a href="#" class="label-link">¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="input-group-custom">
                        <span class="field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required>
                        <button type="button" class="toggle-pass" id="togglePass" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                            <svg id="iconEye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-submit" id="submitBtn">
                        Iniciar sesión
                    </button>

                </form>

                <!-- Mensajes (usados por login.js) -->
                <div id="form-message-warning"></div>
                <div id="form-message-success">Registro exitoso. Revisa tu correo.</div>

                <!-- Registro -->
                <p class="register-note">
                    ¿No tienes una cuenta? <a href="registro.php">Regístrate gratis</a>
                </p>

                <!-- Seguridad -->
                <p class="security-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0110 0v4" />
                    </svg>
                    Acceso seguro · Datos protegidos
                </p>

            </div>
        </div>

    </div>

    <!-- External JS -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="publico/build/js/login.js"></script>

    <script>
        /* ── Stats ─────────────────────────── */
        fetch('configurar/stats_landing.php')
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    document.getElementById('stat-ventas').textContent = d.ventas.toLocaleString('es-VE');
                    document.getElementById('stat-negocios').textContent = d.negocios.toLocaleString('es-VE');
                    document.getElementById('stat-sucursales').textContent = d.sucursales.toLocaleString('es-VE');
                    document.getElementById('stat-usuarios').textContent = d.usuarios.toLocaleString('es-VE');
                }
            })
            .catch(() => {});

        /* ── Toggle password ────────────────── */
        document.getElementById('togglePass').addEventListener('click', function() {
            const inp = document.getElementById('password');
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            this.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            document.getElementById('iconEye').innerHTML = show ?
                '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>' :
                '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        });
    </script>

</body>

</html>