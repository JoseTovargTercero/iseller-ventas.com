<!DOCTYPE html>
<!--
	Moon by GetTemplates.co
	URL: https://gettemplates.co
-->
<html lang="es">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Inicio de sesión — iSeller</title>
  <meta name="description" content="Gestiona tu negocio desde un solo lugar. Ingresa a tu cuenta de iSeller.">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel='icon' href='publico/production/images/favicon.ico' type='image/ico' />

  <!-- External CSS -->
  <link rel="stylesheet" href="web/vendor/bootstrap/bootstrap.min.css">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&family=Lato:wght@300;400&display=swap" rel="stylesheet">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
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
      font-family: 'Work Sans', 'Lato', sans-serif;
      background-color: black;
      /* color original del proyecto */
      overflow: hidden;
    }

    /* ─── Snowflake (mantenido del original) ─── */
    .snowflake {
      position: absolute;
      width: 4px;
      height: 4px;
      background-color: white;
    }

    /* ─────────────────────────────────────────── *
     *  LAYOUT SIDE BY SIDE                         *
     * ─────────────────────────────────────────── */
    .split-layout {
      display: flex;
      height: 100vh;
      width: 100vw;
      overflow: hidden;
    }

    /* ── PANEL IZQUIERDO (oscuro) ── */
    .panel-left {
      flex: 1 1 55%;
      background-color: black;
      /* color original */
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 36px 48px 32px;
      position: relative;
      overflow: hidden;
    }

    /* Sutil textura / pattern sobre el fondo */
    .panel-left::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
      background-size: 50px 50px;
      pointer-events: none;
      z-index: 0;
    }

    .panel-left>* {
      position: relative;
      z-index: 1;
    }

    /* Logo + nombre en la parte superior izquierda */
    .left-header {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .left-header .logo-img {
      height: 36px;
      width: auto;
      object-fit: contain;
    }

    .left-header .brand-name {
      font-size: 18px;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: -0.3px;
    }

    /* Bloque central de texto */
    .left-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 40px 0;
    }

    .left-headline {
      font-size: clamp(28px, 3.5vw, 42px);
      font-weight: 700;
      line-height: 1.15;
      letter-spacing: -1px;
      color: #ffffff;
      margin-bottom: 18px;
      max-width: 480px;
    }

    .left-subtext {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.55);
      line-height: 1.6;
      max-width: 400px;
      margin-bottom: 36px;
    }

    /* Mini dashboard card */
    .stats-card {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid rgba(255, 255, 255, 0.10);
      border-radius: 14px;
      padding: 20px 24px;
      max-width: 380px;
      backdrop-filter: blur(8px);
    }

    .stats-card .card-label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: rgba(255, 255, 255, 0.40);
      margin-bottom: 16px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
    }

    .stat-item {}

    .stat-value {
      font-size: 22px;
      font-weight: 700;
      color: #ffffff;
      letter-spacing: -0.5px;
      line-height: 1;
    }

    .stat-label {
      font-size: 11px;
      color: rgba(255, 255, 255, 0.45);
      margin-top: 3px;
    }

    /* Divider entre stats */
    .stats-divider {
      width: 1px;
      background: rgba(255, 255, 255, 0.08);
      margin: 0;
    }

    /* Notification badge */
    .notif-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.10);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 24px;
      padding: 6px 14px 6px 6px;
      margin-top: 16px;
      width: fit-content;
    }

    .notif-dot {
      width: 28px;
      height: 28px;
      background: #28a745;
      /* btn-success color original */
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      flex-shrink: 0;
    }

    .notif-text {
      font-size: 11px;
      color: rgba(255, 255, 255, 0.75);
      line-height: 1.3;
    }

    .notif-text strong {
      display: block;
      color: #fff;
      font-size: 12px;
    }

    /* Footer izquierdo */
    .left-footer {
      display: flex;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .left-footer a {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.35);
      text-decoration: none;
      transition: color 0.15s;
    }

    .left-footer a:hover {
      color: rgba(255, 255, 255, 0.70);
    }

    .left-footer span {
      color: rgba(255, 255, 255, 0.15);
      font-size: 12px;
    }

    /* ── PANEL DERECHO (blanco/claro) ── */
    .panel-right {
      flex: 0 0 45%;
      background: #ffffff;
      /* bg-white del original */
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 56px;
      border-left: 1px solid rgba(0, 0, 0, 0.06);
      overflow-y: auto;
    }

    .form-wrap {
      width: 100%;
      max-width: 360px;
    }

    /* Título del formulario */
    .form-title {
      font-size: 22px;
      font-weight: 700;
      color: #111;
      letter-spacing: -0.4px;
      margin-bottom: 6px;
    }

    .form-subtitle {
      font-size: 13px;
      color: #6c757d;
      margin-bottom: 32px;
      line-height: 1.5;
    }

    /* Labels */
    .form-label-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 6px;
    }

    label {
      font-size: 13px;
      font-weight: 600;
      color: #333;
    }

    .label-link {
      font-size: 12px;
      color: #6c757d;
      text-decoration: none;
      transition: color 0.15s;
    }

    .label-link:hover {
      color: #28a745;
    }

    /* Input wrapper con icono */
    .input-group-custom {
      position: relative;
      margin-bottom: 20px;
    }

    .input-group-custom .field-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #adb5bd;
      font-size: 15px;
      pointer-events: none;
      z-index: 2;
    }

    .input-group-custom input {
      width: 100%;
      padding: 11px 42px;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
      color: #212529;
      background: #fff;
      outline: none;
      transition: border-color 0.18s, box-shadow 0.18s;
    }

    .input-group-custom input:focus {
      border-color: #28a745;
      /* color original btn-success */
      box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.12);
    }

    .input-group-custom input::placeholder {
      color: #adb5bd;
    }

    /* Toggle contraseña */
    .toggle-pass {
      position: absolute;
      right: 13px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #adb5bd;
      font-size: 15px;
      padding: 2px;
      z-index: 2;
      transition: color 0.15s;
    }

    .toggle-pass:hover {
      color: #495057;
    }

    /* Botón submit — btn-success original */
    .btn-ingresar {
      width: 100%;
      padding: 12px;
      background-color: #28a745;
      border: none;
      border-radius: 8px;
      color: #fff;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 8px;
      transition: background-color 0.18s, transform 0.12s;
      letter-spacing: 0.2px;
    }

    .btn-ingresar:hover {
      background-color: #218838;
      transform: translateY(-1px);
    }

    .btn-ingresar:active {
      background-color: #1e7e34;
      transform: translateY(0);
    }

    .btn-ingresar:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }

    /* Nota de registro */
    .register-note {
      text-align: center;
      font-size: 13px;
      color: #6c757d;
      margin-top: 24px;
    }

    .register-note a {
      color: #28a745;
      text-decoration: none;
      font-weight: 600;
    }

    .register-note a:hover {
      text-decoration: underline;
    }

    /* Nota seguridad pie */
    .security-note {
      text-align: center;
      font-size: 11px;
      color: #adb5bd;
      margin-top: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }

    /* Mensajes de error/éxito (ids usados por login.js) */
    #form-message-warning {
      color: #dc3545;
      font-size: 13px;
      margin-top: 12px;
    }

    #form-message-success {
      color: #28a745;
      font-size: 13px;
      margin-top: 12px;
      display: none;
    }

    /* ── Responsive ── */
    @media (max-width: 860px) {
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
  </style>

</head>

<body>

  <div class="split-layout">

    <!-- ════════════════════════════════════ -->
    <!--  PANEL IZQUIERDO                     -->
    <!-- ════════════════════════════════════ -->
    <div class="panel-left">

      <!-- Logo + nombre arriba a la izquierda -->
      <div class="left-header">
        <img src="web/img/logo.png" alt="Logo iSeller" class="logo-img">
        <span class="brand-name">iSeller</span>
      </div>

      <!-- Cuerpo central -->
      <div class="left-body">
        <h1 class="left-headline">
          Gestiona tu negocio<br>desde un solo lugar.
        </h1>
        <p class="left-subtext">
          Ventas, inventario, sucursales y reportes en una sola plataforma. Rápido, seguro y siempre disponible.
        </p>

        <!-- Mini stats card (decorativa) -->
        <div class="stats-card">
          <div class="card-label">Resumen de hoy</div>
          <div class="stats-grid">
            <div class="stat-item">
              <div class="stat-value" id="stat-ventas">—</div>
              <div class="stat-label">Ventas</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" id="stat-negocios">—</div>
              <div class="stat-label">Negocios activos</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" id="stat-sucursales">—</div>
              <div class="stat-label">Sucursales</div>
            </div>
            <div class="stat-item">
              <div class="stat-value" id="stat-usuarios">—</div>
              <div class="stat-label">Usuarios</div>
            </div>
          </div>

          <!-- Notificación ejemplo -->
          <div class="notif-badge">
            <div class="notif-dot">✓</div>
            <div class="notif-text">
              <strong>Sistema operativo</strong>
              Todos los servicios funcionando
            </div>
          </div>
        </div>
      </div>

      <!-- Footer izquierdo -->
      <div class="left-footer">
        <a href="#">Administrador</a>
        <span>·</span>
        <a href="registro.php">Registrarse</a>
        <span>·</span>
        <a href="#">Términos</a>
        <span>·</span>
        <a href="#">Privacidad</a>
        <span style="margin-left:auto; color:rgba(255,255,255,0.25); font-size:12px;">
          © <?php echo date('Y'); ?> iSeller
        </span>
      </div>

    </div><!-- /panel-left -->


    <!-- ════════════════════════════════════ -->
    <!--  PANEL DERECHO — FORMULARIO          -->
    <!-- ════════════════════════════════════ -->
    <div class="panel-right">
      <div class="form-wrap">

        <h2 class="form-title">Ingresa a tu cuenta</h2>
        <p class="form-subtitle">Inicia sesión para administrar tu negocio.</p>

        <form name="data_form" novalidate>

          <!-- Correo -->
          <div>
            <label for="login">Correo o usuario</label>
          </div>
          <div class="input-group-custom">
            <span class="field-icon">✉</span>
            <input
              type="text"
              id="login"
              name="login"
              placeholder="correo@negocio.com o usuario"
              autocomplete="username"
              required>
          </div>

          <!-- Contraseña -->
          <div class="form-label-row">
            <label for="password">Contraseña</label>
            <a href="#" class="label-link">¿Olvidaste tu contraseña?</a>
          </div>
          <div class="input-group-custom">
            <span class="field-icon">🔒</span>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="••••••••"
              autocomplete="current-password"
              required>
            <button type="button" class="toggle-pass" id="togglePass" title="Mostrar contraseña">👁</button>
          </div>

          <!-- Botón -->
          <button type="submit" class="btn-ingresar" id="submitBtn">
            Verificar
          </button>

        </form>

        <!-- Mensajes (usados por login.js) -->
        <div id="form-message-warning"></div>
        <div id="form-message-success">Registro exitoso. Revisa tu correo.</div>

        <!-- Registro -->
        <p class="register-note">
          ¿No tienes una cuenta? <a href="registro.php">Regístrate</a>
        </p>

        <!-- Nota seguridad -->
        <p class="security-note">
          🔐 Acceso seguro para negocios registrados
        </p>

      </div>
    </div><!-- /panel-right -->

  </div><!-- /split-layout -->


  <!-- External JS -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="publico/build/js/login.js"></script>
  <script src="web/vendor/bootstrap/popper.min.js"></script>
  <script src="web/vendor/bootstrap/bootstrap.min.js"></script>

  <script>
    // Toggle contraseña
    document.getElementById('togglePass').addEventListener('click', function() {
      const inp = document.getElementById('password');
      const isPass = inp.type === 'password';
      inp.type = isPass ? 'text' : 'password';
      this.textContent = isPass ? '🙈' : '👁';
    });
  </script>

</body>

</html>