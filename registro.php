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
      overflow: hidden;
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
      flex: 1 1 45%;
      background-color: black;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 36px 48px 32px;
      position: relative;
      overflow: hidden;
    }

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

    /* Logo + nombre arriba a la izquierda */
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

    /* Bloque central */
    .left-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 40px 0;
    }

    .left-headline {
      font-size: clamp(26px, 3vw, 38px);
      font-weight: 700;
      line-height: 1.15;
      letter-spacing: -1px;
      color: #ffffff;
      margin-bottom: 16px;
      max-width: 420px;
    }

    .left-subtext {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.55);
      line-height: 1.6;
      max-width: 380px;
      margin-bottom: 32px;
    }

    .left-subtext strong {
      color: #28a745;
    }

    /* Beneficios list */
    .benefits-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-width: 360px;
    }

    .benefit-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .benefit-icon {
      width: 28px;
      height: 28px;
      background: rgba(40, 167, 69, 0.15);
      border: 1px solid rgba(40, 167, 69, 0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .benefit-text {
      font-size: 13px;
      color: rgba(255, 255, 255, 0.65);
      line-height: 1.4;
    }

    .benefit-text strong {
      color: #fff;
      font-weight: 600;
      display: block;
      font-size: 13px;
    }

    /* Promo badge */
    .promo-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(40, 167, 69, 0.12);
      border: 1px solid rgba(40, 167, 69, 0.3);
      border-radius: 24px;
      padding: 8px 16px;
      margin-bottom: 28px;
      width: fit-content;
    }

    .promo-badge span {
      font-size: 12px;
      color: #6ee384;
      font-weight: 600;
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

    /* ── PANEL DERECHO (blanco) ── */
    .panel-right {
      flex: 0 0 55%;
      background: #ffffff;
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
      max-width: 480px;
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
      font-size: 12px;
      font-weight: 700;
      background: #e9ecef;
      color: #adb5bd;
      border: 2px solid #e9ecef;
      transition: all 0.25s;
    }

    .step-dot .dot.active {
      background: #28a745;
      color: #fff;
      border-color: #28a745;
    }

    .step-dot .dot.done {
      background: #28a745;
      color: #fff;
      border-color: #28a745;
    }

    .step-dot .step-label {
      font-size: 12px;
      font-weight: 600;
      color: #adb5bd;
      transition: color 0.25s;
    }

    .step-dot .step-label.active {
      color: #212529;
    }

    .step-line {
      flex: 1;
      height: 2px;
      background: #e9ecef;
      margin: 0 12px;
      border-radius: 2px;
      transition: background 0.25s;
    }

    .step-line.done {
      background: #28a745;
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
      margin-bottom: 28px;
      line-height: 1.5;
    }

    /* Labels */
    label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #333;
      margin-bottom: 6px;
    }

    /* Input / Select wrapper */
    .field-group {
      position: relative;
      margin-bottom: 18px;
    }

    .field-group .field-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #adb5bd;
      font-size: 15px;
      pointer-events: none;
      z-index: 2;
    }

    .field-group input,
    .field-group select {
      width: 100%;
      padding: 11px 14px 11px 42px;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
      color: #212529;
      background: #fff;
      outline: none;
      transition: border-color 0.18s, box-shadow 0.18s;
      appearance: none;
      -webkit-appearance: none;
    }

    .field-group input:focus,
    .field-group select:focus {
      border-color: #28a745;
      box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.12);
    }

    .field-group input::placeholder {
      color: #adb5bd;
    }

    /* Select arrow */
    .field-group select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23adb5bd' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 36px;
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
      color: #adb5bd;
      font-size: 15px;
      padding: 2px;
      z-index: 2;
      transition: color 0.15s;
    }

    .toggle-pass:hover {
      color: #495057;
    }

    /* Campo "otro tipo" oculto */
    .otro-tipo-wrap {
      display: none;
      margin-bottom: 18px;
    }

    /* Botones de navegación */
    .btn-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-top: 8px;
    }

    .btn-back {
      padding: 11px 20px;
      background: none;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      font-family: inherit;
      font-size: 14px;
      font-weight: 500;
      color: #6c757d;
      cursor: pointer;
      transition: all 0.15s;
    }

    .btn-back:hover {
      background: #f8f9fa;
      border-color: #adb5bd;
      color: #333;
    }

    .btn-next,
    .btn-submit {
      flex: 1;
      padding: 12px;
      background-color: #28a745;
      border: none;
      border-radius: 8px;
      color: #fff;
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.18s, transform 0.12s;
      letter-spacing: 0.2px;
    }

    .btn-next:hover,
    .btn-submit:hover {
      background-color: #218838;
      transform: translateY(-1px);
    }

    .btn-next:active,
    .btn-submit:active {
      background-color: #1e7e34;
      transform: translateY(0);
    }

    .btn-submit:disabled {
      opacity: 0.65;
      cursor: not-allowed;
    }

    /* Steps */
    .step-section {
      display: none;
    }

    .step-section.active {
      display: block;
      animation: stepIn 0.3s ease both;
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

    /* Password strength bar */
    .strength-bar-wrap {
      margin-top: -10px;
      margin-bottom: 14px;
    }

    .strength-bar {
      height: 3px;
      border-radius: 3px;
      background: #e9ecef;
      overflow: hidden;
      margin-bottom: 4px;
    }

    .strength-fill {
      height: 100%;
      border-radius: 3px;
      width: 0;
      transition: width 0.3s, background 0.3s;
    }

    .strength-label {
      font-size: 11px;
      color: #adb5bd;
    }

    /* Login link */
    .login-note {
      text-align: center;
      font-size: 13px;
      color: #6c757d;
      margin-top: 20px;
    }

    .login-note a {
      color: #28a745;
      text-decoration: none;
      font-weight: 600;
    }

    .login-note a:hover {
      text-decoration: underline;
    }

    /* Mensajes */
    #form-message-warning {
      color: #dc3545;
      font-size: 13px;
      margin-top: 10px;
    }

    #form-message-success {
      color: #28a745;
      font-size: 13px;
      margin-top: 10px;
      display: none;
    }

    /* Nota seguridad pie */
    .security-note {
      text-align: center;
      font-size: 11px;
      color: #adb5bd;
      margin-top: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
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
        padding: 32px 16px;
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

      <!-- Logo arriba a la izquierda -->
      <div class="left-header">
        <img src="web/img/logo.png" alt="Logo iSeller" class="logo-img">
        <span class="brand-name">iSeller</span>
      </div>

      <!-- Cuerpo central -->
      <div class="left-body">

        <div class="promo-badge">
          <span>🎁 3 meses gratis · Sin tarjeta requerida</span>
        </div>

        <h1 class="left-headline">
          Empieza hoy,<br>sin pagar nada.
        </h1>
        <p class="left-subtext">
          Regístrate y aprovecha todas las herramientas del sistema <strong>gratis durante los primeros 3 meses</strong>. Sin compromisos.
        </p>

        <!-- Beneficios -->
        <div class="benefits-list">
          <div class="benefit-item">
            <div class="benefit-icon">✓</div>
            <div class="benefit-text">
              <strong>Ventas e inventario</strong>
              Controla stock, productos y precios en tiempo real.
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">✓</div>
            <div class="benefit-text">
              <strong>Múltiples sucursales</strong>
              Gestiona todas tus sedes desde una sola cuenta.
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">✓</div>
            <div class="benefit-text">
              <strong>Reportes y estadísticas</strong>
              Dashboards con métricas de ventas diarias, semanales y mensuales.
            </div>
          </div>
        </div>
      </div>

      <!-- Footer izquierdo -->
      <div class="left-footer">
        <a href="login.php">Iniciar sesión</a>
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

        <!-- Título dinámico por paso -->
        <h2 class="form-title" id="form-title">Datos del negocio</h2>
        <p class="form-subtitle" id="form-subtitle">Cuéntanos sobre tu negocio para comenzar.</p>

        <form id="formRegistro" method="post" novalidate>

          <!-- ═══════════════════════════ -->
          <!--  PASO 1 — Datos del negocio -->
          <!-- ═══════════════════════════ -->
          <div id="step-1" class="step-section active">

            <label for="tipo_negocio">Tipo de negocio</label>
            <div class="field-group">
              <span class="field-icon">🏪</span>
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

            <!-- Campo condicional "otro tipo" -->
            <div class="otro-tipo-wrap" id="otro_tipo_container">
              <label for="otro_tipo">Especificar tipo</label>
              <div class="field-group">
                <span class="field-icon">✏️</span>
                <input type="text" id="otro_tipo" name="otro_tipo" placeholder="Ej: Librería, Panadería...">
              </div>
            </div>

            <label for="nombre_negocio">Nombre del negocio</label>
            <div class="field-group">
              <span class="field-icon">🏢</span>
              <input type="text" id="nombre_negocio" name="nombre_negocio"
                placeholder="Ej: Mi Tienda C.A." required>
            </div>

            <div class="btn-row">
              <button type="button" class="btn-next" onclick="nextStep(2)">
                Siguiente →
              </button>
            </div>

          </div><!-- /step-1 -->

          <!-- ═══════════════════════════ -->
          <!--  PASO 2 — Datos del usuario -->
          <!-- ═══════════════════════════ -->
          <div id="step-2" class="step-section">

            <label for="nombres">Nombre completo</label>
            <div class="field-group">
              <span class="field-icon">👤</span>
              <input type="text" id="nombres" name="nombres"
                placeholder="Tu nombre completo" required>
            </div>

            <label for="email">Correo electrónico</label>
            <div class="field-group">
              <span class="field-icon">✉</span>
              <input type="email" id="email" name="email"
                placeholder="correo@negocio.com" required>
            </div>

            <label for="password">Contraseña</label>
            <div class="field-group">
              <span class="field-icon">🔒</span>
              <input type="password" id="password" name="password"
                placeholder="Mínimo 5 caracteres" required>
              <button type="button" class="toggle-pass" id="togglePass1" title="Mostrar">👁</button>
            </div>

            <!-- Barra de fuerza de contraseña -->
            <div class="strength-bar-wrap">
              <div class="strength-bar">
                <div class="strength-fill" id="strengthFill"></div>
              </div>
              <span class="strength-label" id="strengthLabel">Introduce una contraseña</span>
            </div>

            <label for="confirmar_password">Repetir contraseña</label>
            <div class="field-group">
              <span class="field-icon">🔒</span>
              <input type="password" id="confirmar_password" name="confirmar_password"
                placeholder="Repite la contraseña" required>
              <button type="button" class="toggle-pass" id="togglePass2" title="Mostrar">👁</button>
            </div>

            <div class="btn-row">
              <button type="button" class="btn-back" onclick="prevStep(1)">← Atrás</button>
              <button type="submit" class="btn-submit" id="btnSubmit">
                Crear cuenta
              </button>
            </div>

          </div><!-- /step-2 -->

        </form>

        <!-- Mensajes (compatibles con lógica JS original) -->
        <div id="form-message-warning"></div>
        <div id="form-message-success">Registro exitoso. Redirigiendo...</div>

        <!-- Link a login -->
        <p class="login-note">
          ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </p>

        <p class="security-note">
          🔐 Acceso seguro · Datos protegidos
        </p>

      </div><!-- /form-wrap -->
    </div><!-- /panel-right -->

  </div><!-- /split-layout -->

  <!-- Formulario fantasma para login.js (compatibilidad) -->
  <form name="data_form" style="display:none;">
    <input type="text" id="login" name="login">
    <input type="password" id="hidden_password" name="password">
  </form>

  <!-- External JS -->
  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="web/vendor/bootstrap/popper.min.js"></script>
  <script src="web/vendor/bootstrap/bootstrap.min.js"></script>

  <script>
    const plan = "<?php echo $plan; ?>";

    // ── Textos dinámicos por paso ──────────────────────────────────
    const stepMeta = {
      1: {
        title: 'Datos del negocio',
        sub: 'Cuéntanos sobre tu negocio para comenzar.'
      },
      2: {
        title: 'Crea tu cuenta',
        sub: 'Completa tus datos de acceso al sistema.'
      }
    };

    function updateStepUI(step) {
      // Título y subtítulo
      document.getElementById('form-title').textContent = stepMeta[step].title;
      document.getElementById('form-subtitle').textContent = stepMeta[step].sub;

      // Dots
      [1, 2].forEach(n => {
        const dot = document.getElementById('dot-' + n);
        const label = document.getElementById('label-' + n);
        dot.classList.remove('active', 'done');
        label.classList.remove('active');
        if (n < step) {
          dot.classList.add('done');
          dot.textContent = '✓';
        } else if (n === step) {
          dot.classList.add('active');
          dot.textContent = n;
        } else {
          dot.textContent = n;
        }
        if (n === step) label.classList.add('active');
      });

      // Line
      const line = document.getElementById('line-1');
      if (step > 1) line.classList.add('done');
      else line.classList.remove('done');
    }

    // ── Navegación de pasos ────────────────────────────────────────
    function nextStep(step) {
      if (step === 2) {
        const tipo = document.getElementById('tipo_negocio').value;
        const nombre = document.getElementById('nombre_negocio').value.trim();
        const otroTipo = document.getElementById('otro_tipo').value.trim();

        if (!tipo) {
          Swal.fire('Error', 'Por favor selecciona un tipo de negocio.', 'warning');
          return;
        }
        if (tipo === 'otro' && !otroTipo) {
          Swal.fire('Error', 'Por favor especifica el tipo de negocio.', 'warning');
          return;
        }
        if (!nombre) {
          Swal.fire('Error', 'Por favor ingresa el nombre del negocio.', 'warning');
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

    // ── Toggle tipo personalizado ──────────────────────────────────
    document.getElementById('tipo_negocio').addEventListener('change', function() {
      document.getElementById('otro_tipo_container').style.display =
        this.value === 'otro' ? 'block' : 'none';
    });

    // ── Toggle contraseñas ─────────────────────────────────────────
    function makeToggle(btnId, inputId) {
      document.getElementById(btnId).addEventListener('click', function() {
        const inp = document.getElementById(inputId);
        const show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        this.textContent = show ? '🙈' : '👁';
      });
    }
    makeToggle('togglePass1', 'password');
    makeToggle('togglePass2', 'confirmar_password');

    // ── Strength bar ───────────────────────────────────────────────
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
          bg: '#e9ecef',
          text: 'Introduce una contraseña'
        },
        {
          w: '25%',
          bg: '#dc3545',
          text: 'Muy débil'
        },
        {
          w: '50%',
          bg: '#fd7e14',
          text: 'Débil'
        },
        {
          w: '75%',
          bg: '#ffc107',
          text: 'Aceptable'
        },
        {
          w: '90%',
          bg: '#20c997',
          text: 'Buena'
        },
        {
          w: '100%',
          bg: '#28a745',
          text: 'Excelente'
        }
      ];
      const lv = levels[Math.min(score, 5)];
      fill.style.width = lv.w;
      fill.style.background = lv.bg;
      label.textContent = lv.text;
      label.style.color = score > 0 ? lv.bg : '#adb5bd';
    });

    // ── Envío del formulario ───────────────────────────────────────
    document.getElementById('formRegistro').addEventListener('submit', function(e) {
      e.preventDefault();

      const warning = document.getElementById('form-message-warning');
      const success = document.getElementById('form-message-success');
      const password = document.getElementById('password').value;
      const confirmar = document.getElementById('confirmar_password').value;
      const btn = document.getElementById('btnSubmit');

      warning.innerText = '';
      warning.style.display = 'none';

      if (password.length < 5) {
        Swal.fire('Error', 'La contraseña debe tener al menos 5 caracteres.', 'warning');
        return;
      }
      if (password !== confirmar) {
        Swal.fire('Error', 'Las contraseñas no coinciden.', 'warning');
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Creando cuenta...';

      const formData = new FormData(this);
      formData.append('plan', plan);

      fetch('configurar/register_process.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: '¡Cuenta creada!',
              text: 'Inicia sesión para continuar...',
              timer: 1500,
              showConfirmButton: true
            }).then(() => {
              window.location.href = 'login.php';
            });
          } else {
            btn.disabled = false;
            btn.textContent = 'Crear cuenta';
            Swal.fire('Error', data.message || 'Error en el registro.', 'error');
          }
        })
        .catch(err => {
          btn.disabled = false;
          btn.textContent = 'Crear cuenta';
          console.error(err);
          Swal.fire('Error', 'Error al conectar con el servidor.', 'error');
        });
    });
  </script>

</body>

</html>