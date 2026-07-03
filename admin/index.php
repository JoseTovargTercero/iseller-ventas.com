<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iSeller Admin — Inicio de sesión</title>
    <meta name="description" content="Panel de administración iSeller. Acceso exclusivo para administradores del sistema.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-dark:       #0a0b14;
            --bg-card:       rgba(255,255,255,0.04);
            --bg-card-hover: rgba(255,255,255,0.07);
            --border:        rgba(255,255,255,0.10);
            --border-focus:  rgba(99,102,241,0.70);
            --accent:        #6366f1;
            --accent-glow:   rgba(99,102,241,0.35);
            --accent-2:      #8b5cf6;
            --text-primary:  #f1f5f9;
            --text-muted:    #94a3b8;
            --danger:        #ef4444;
            --success:       #22c55e;
            --radius:        16px;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            overflow: hidden;
        }

        /* ── Animated gradient orbs ── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: radial-gradient(circle, #6366f1, transparent); top: -150px; left: -150px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, #8b5cf6, transparent); bottom: -100px; right: -100px; animation-delay: -4s; }
        .orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, #06b6d4, transparent); top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: -2s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%       { transform: translate(20px, -20px) scale(1.05); }
            66%       { transform: translate(-15px, 15px) scale(0.95); }
        }

        /* ── Grid overlay ── */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }

        /* ── Layout ── */
        .page {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* ── Card ── */
        .card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 44px 40px;
            box-shadow: 0 32px 64px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05) inset;
            animation: slideUp 0.6s cubic-bezier(0.16,1,0.3,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Logo / Header ── */
        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 8px 20px var(--accent-glow);
            flex-shrink: 0;
        }

        .logo-text { display: flex; flex-direction: column; }
        .logo-text .brand { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; }
        .logo-text .sub   { font-size: 11px; color: var(--text-muted); font-weight: 400; text-transform: uppercase; letter-spacing: 1.5px; }

        h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        /* ── Form fields ── */
        .field { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
            pointer-events: none;
            transition: color 0.2s;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--border-focus);
            background: rgba(99,102,241,0.08);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        input[type="text"]:focus ~ .input-icon,
        input[type="password"]:focus ~ .input-icon {
            color: var(--accent);
        }

        input::placeholder { color: rgba(148,163,184,0.5); }

        /* ── Toggle password ── */
        .toggle-pw {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); font-size: 15px;
            padding: 4px;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: var(--text-primary); }

        /* ── Error message ── */
        .error-msg {
            display: none;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            color: #fca5a5;
            margin-bottom: 20px;
            align-items: center;
            gap: 8px;
        }
        .error-msg.show { display: flex; animation: shake 0.4s cubic-bezier(0.36,0.07,0.19,0.97); }

        @keyframes shake {
            10%, 90% { transform: translateX(-2px); }
            20%, 80% { transform: translateX(4px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--accent), var(--accent-2));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
            box-shadow: 0 8px 24px var(--accent-glow);
        }

        .btn-submit::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .btn-submit:hover::after { opacity: 1; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 12px 32px var(--accent-glow); }
        .btn-submit:active { transform: translateY(1px); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn-submit .spinner {
            display: none;
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Footer note ── */
        .card-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 11px;
            color: var(--text-muted);
        }

        .badge-secure {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 10px;
            color: #86efac;
            margin-bottom: 8px;
        }

        @media (max-width: 480px) {
            .card { padding: 32px 24px; }
        }
    </style>
</head>
<body>
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    <div class="page">
        <div class="card">
            <div class="logo-wrap">
                <div class="logo-icon">⚡</div>
                <div class="logo-text">
                    <span class="brand">iSeller</span>
                    <span class="sub">Admin Panel</span>
                </div>
            </div>

            <h1>Bienvenido de vuelta</h1>
            <p class="subtitle">Ingresa tus credenciales para acceder al panel de administración.</p>

            <div class="error-msg" id="errorMsg">
                <span>⚠</span>
                <span id="errorText">Usuario o contraseña incorrectos.</span>
            </div>

            <form id="loginForm" method="POST" action="auth.php" novalidate>
                <div class="field">
                    <label for="admin_user">Usuario</label>
                    <div class="input-wrap">
                        <input
                            type="text"
                            id="admin_user"
                            name="admin_user"
                            placeholder="admin"
                            autocomplete="username"
                            required
                        >
                        <span class="input-icon">👤</span>
                    </div>
                </div>

                <div class="field">
                    <label for="admin_pass">Contraseña</label>
                    <div class="input-wrap">
                        <input
                            type="password"
                            id="admin_pass"
                            name="admin_pass"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <span class="input-icon">🔒</span>
                        <button type="button" class="toggle-pw" id="togglePw" title="Mostrar contraseña">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="btnText">Ingresar al panel</span>
                    <span class="spinner" id="spinner"></span>
                </button>
            </form>

            <div class="card-footer">
                <div class="badge-secure">🔐 Acceso protegido</div>
                <p>© 2025 iSeller · Uso exclusivo interno</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePw = document.getElementById('togglePw');
        const passInput = document.getElementById('admin_pass');
        togglePw.addEventListener('click', () => {
            const isPassword = passInput.type === 'password';
            passInput.type = isPassword ? 'text' : 'password';
            togglePw.textContent = isPassword ? '🙈' : '👁';
        });

        // Handle form submit with loading state
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const spinner = document.getElementById('spinner');
        const errorMsg = document.getElementById('errorMsg');
        const errorText = document.getElementById('errorText');

        // Show error if URL has error param
        const params = new URLSearchParams(window.location.search);
        if (params.get('error') === '1') {
            errorMsg.classList.add('show');
            errorText.textContent = 'Usuario o contraseña incorrectos. Intenta nuevamente.';
        }

        form.addEventListener('submit', (e) => {
            const user = document.getElementById('admin_user').value.trim();
            const pass = document.getElementById('admin_pass').value.trim();

            if (!user || !pass) {
                e.preventDefault();
                errorMsg.classList.add('show');
                errorText.textContent = 'Por favor completa todos los campos.';
                return;
            }

            errorMsg.classList.remove('show');
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            spinner.style.display = 'block';
        });
    </script>
</body>
</html>
