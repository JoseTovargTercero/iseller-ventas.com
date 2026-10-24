<?php
/**
 * Página de Registro
 * Formulario de registro de nuevos usuarios
 */

require_once('core/db.php');
require_once('core/session.php');
require_once('core/venezuela_geo.php');

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    // Nota: Aquí no tenemos el valor de cart_exists del POST, por lo que redirigimos a checkout por defecto o a index
    redirect('checkout.php');
}

$error = '';
$success = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    function generateReferralCode($userId) {
        return strtoupper(substr(md5($userId . uniqid()), 0, 8));
    }

    // Validar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error de validación CSRF. Por favor, intente de nuevo.');
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $cart_exists = $_POST['cart_exists'] ?? '0';

    // Solo se aceptan combinaciones estado/municipio de la lista.
    $geo = venezuela_estados_municipios();
    $geo_estado = trim($_POST['geo_estado'] ?? '');
    $geo_municipio = trim($_POST['geo_municipio'] ?? '');
    if (!isset($geo[$geo_estado]) || !in_array($geo_municipio, $geo[$geo_estado], true)) {
        $geo_estado = $geo_municipio = '';
    }

    // Validaciones básicas
    if (empty($email) || empty($password) || empty($nombre) || $geo_estado === '' || $geo_municipio === '') {
        $error = 'Todos los campos son obligatorios.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingrese un email válido.';
    } else if (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else if ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Verificar si el email ya existe
    
        $stmt = $conexion_store->prepare("SELECT id FROM tienda_usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
     
        if ($result->num_rows > 0) {
            $error = 'Este email ya está registrado. Por favor, inicie sesión.';
        } else {

            $conexion_store->begin_transaction();
            try {
                // Hashear la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar nuevo usuario
                $stmt = $conexion_store->prepare("INSERT INTO tienda_usuarios (nombre, email, password, geo_estado, geo_municipio, estado) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("sssss", $nombre, $email, $password_hash, $geo_estado, $geo_municipio);
                
                if ($stmt->execute()) {
                
                    // Obtener el ID del usuario recién creado
                    $user_id = $conexion_store->insert_id;
                    // Generar código de referido
                    $referral_code = generateReferralCode($user_id);
                    // actualizar usuarios.referral_code 
                    $stmt = $conexion_store->prepare("UPDATE tienda_usuarios SET referral_code = ? WHERE id = ?");
                    $stmt->bind_param("ss", $referral_code, $user_id);
                    if(!$stmt->execute()){
                        throw new Exception("Error al actualizar el código de referido");
                    }

                    // Iniciar sesión automáticamente
                    $user = [
                        'id' => $user_id,
                        'nombre' => $nombre,
                        'email' => $email
                    ];
                    loginUser($user);

                    // Verificar si fue referido
                    $referralCode = $_COOKIE['referral_code'] ?? null;

                    if ($referralCode) {
                        $stmt = $conexion_store->prepare("SELECT id FROM tienda_usuarios WHERE referral_code = ? LIMIT 1");
                        $stmt->bind_param("s", $referralCode);
                        if(!$stmt->execute()){
                            throw new Exception("Error al buscar el código de referido");
                        }
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $referrer = $result->fetch_assoc();
                            $referrer_id = $referrer['id'];
                            // Insertar la referencia
                            $stmt = $conexion_store->prepare("INSERT INTO referrals (referrer_user_id, referred_user_id, referral_code, status) VALUES (?, ?, ?, 'pending')");
                            $stmt->bind_param("iis", $referrer_id, $user_id, $referralCode);
                            if(!$stmt->execute()){
                                throw new Exception("Error al insertar la referencia " . $stmt->error);
                            }
                        }

                        // eliminar cookie
                        setcookie('referral_code', '', time() - 3600, '/');

                    }
                    $conexion_store->commit();
                    // Volver a la tienda (o portada) desde donde se registró
                    $redirect_page = ($cart_exists === '1') ? 'checkout.php' : authReturnUrl('../');
                    redirect($redirect_page);
                } else {
                    $error = 'Error al crear la cuenta. Por favor, intente nuevamente.';
                    $conexion_store->rollback();
                }

            } catch (Exception $e) {
                $error = $e->getMessage();
                $conexion_store->rollback();
            }
        
        }

      
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - iSeller Store</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo-tienda.png">
    <link rel="apple-touch-icon" href="assets/img/logo-tienda.png">
    
    <link href="../publico/vendors/bootstrap5/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Global Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">
    
    <!-- Dexie.js for IndexedDB -->
    <script src="../publico/vendors/dexie/dexie.min.js"></script>
    
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(180deg, #f4faf6 0%, var(--bg-secondary) 60%);
        }

        /* offset for the fixed navbar */
        .login-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 108px 16px 48px;
        }

        .login-wrap {
            width: 100%;
            max-width: 460px;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 22px;
        }

        .login-brand-icon {
            display: block;
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin: 0 auto 14px;
            filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.14));
        }

        .login-brand h1 {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            margin: 0;
        }

        .login-brand h1 span {
            color: var(--primary-dark);
        }

        .login-card {
            border: 1px solid rgba(0, 0, 0, 0.04);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 24px 60px -30px rgba(4, 19, 12, 0.35), 0 2px 6px rgba(0, 0, 0, 0.03);
        }

        .login-card .card-body {
            padding: 32px 28px;
        }

        .login-title {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--text-primary);
            margin-bottom: 4px;
        }

        .login-card .form-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .login-input {
            height: 46px;
            font-size: 0.95rem;
            background: #f8faf9;
            border-color: #e9ecef;
            color: var(--text-primary);
        }

        .login-input:focus {
            background: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(111, 175, 122, 0.18);
        }

        /* keep Bootstrap's invalid state visible over the rules above */
        .login-input.is-invalid {
            border-color: #dc3545;
        }

        .login-input.is-invalid:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.18);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-register:hover,
        .btn-register:focus {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 22px -10px rgba(26, 127, 75, 0.75);
        }

        .login-link {
            color: var(--primary-dark);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }

        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin-top: 5px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #198754; width: 100%; }

        @media (max-width: 480px) {
            .login-main {
                /* navbar becomes two rows on very small screens */
                padding: 148px 14px 32px;
            }

            .login-card .card-body {
                padding: 26px 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <main class="login-main">
        <div class="login-wrap">
            <div class="login-brand">
                <a href="../" class="text-decoration-none">
                    <img src="assets/img/logo-tienda.png" alt="" class="login-brand-icon">
                    <h1>iSeller <span>Store</span></h1>
                </a>
            </div>

            <div class="card login-card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h2 class="login-title">Crear una cuenta</h2>
                        <p class="text-muted small mb-0">Únete para disfrutar de la experiencia completa</p>
                    </div>

                    <!-- Mensajes de error/éxito -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center small py-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center small py-2" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?php echo htmlspecialchars($success); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="registerForm">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="cart_exists" id="cart_exists" value="0">

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre y apellido</label>
                            <input type="text" class="form-control login-input" id="nombre" name="nombre"
                                placeholder="Tu nombre completo" autocomplete="name"
                                value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control login-input" id="email" name="email"
                                placeholder="nombre@ejemplo.com" autocomplete="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="geo_estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-select login-input" id="geo_estado" name="geo_estado" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach (venezuela_estados_municipios() as $estado => $municipios): ?>
                                        <option value="<?= htmlspecialchars($estado) ?>" <?= ($_POST['geo_estado'] ?? '') === $estado ? 'selected' : '' ?>><?= htmlspecialchars($estado) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="geo_municipio" class="form-label">Municipio <span class="text-danger">*</span></label>
                                <select class="form-select login-input" id="geo_municipio" name="geo_municipio" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach ((venezuela_estados_municipios()[$_POST['geo_estado'] ?? ''] ?? []) as $municipio): ?>
                                        <option value="<?= htmlspecialchars($municipio) ?>" <?= ($_POST['geo_municipio'] ?? '') === $municipio ? 'selected' : '' ?>><?= htmlspecialchars($municipio) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control login-input" id="password" name="password"
                                placeholder="Mínimo 6 caracteres" autocomplete="new-password" required>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <small class="text-muted d-block mt-1" id="strengthText"></small>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">Confirmar contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control login-input" id="password_confirm" name="password_confirm"
                                placeholder="Repite tu contraseña" autocomplete="new-password" required>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-register text-white">
                                Registrarse
                            </button>
                        </div>

                        <div class="text-center border-top pt-3">
                            <p class="small text-muted mb-2">¿Ya tienes cuenta?</p>
                            <a href="login.php" class="login-link">
                                Iniciar Sesión <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <script src="../publico/vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Débil';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Media';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Fuerte';
                strengthText.style.color = '#198754';
            }
        });
        
        // Estado / Municipio
        const GEO_VENEZUELA = <?= json_encode(venezuela_estados_municipios(), JSON_UNESCAPED_UNICODE) ?>;
        const geoEstado = document.getElementById('geo_estado');
        const geoMunicipio = document.getElementById('geo_municipio');
        geoEstado.addEventListener('change', function() {
            geoMunicipio.innerHTML = '<option value="">Seleccione...</option>';
            (GEO_VENEZUELA[geoEstado.value] || []).forEach(function(m) {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                geoMunicipio.appendChild(opt);
            });
        });

        // Validación de confirmación de contraseña
        const form = document.getElementById('registerForm');
        const confirmPassword = document.getElementById('password_confirm');

        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmPassword.value) {
                e.preventDefault();
                confirmPassword.classList.add('is-invalid');
                if (!document.querySelector('.invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'Las contraseñas no coinciden';
                    confirmPassword.parentNode.appendChild(feedback);
                }
            }
        });
        
        confirmPassword.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });

        // Verificación de carrito en IndexedDB
        const db = new Dexie("POS_DB");
        db.version(2).stores({
            carritoActivo: 'id',
            carritosVenta: 'id',
            carritosReservados: 'id',
            cart_meta: 'id'
        });
        // Mismo motivo que en index.php: un POS_DB en versión mayor rompe la consulta.
        const abrirDbCarrito = async () => {
            try {
                await db.open();
            } catch (err) {
                if (err.name !== 'VersionError') throw err;
                await Dexie.delete('POS_DB');
                await db.open();
            }
        };

        async function checkCartStatus() {
            try {
                await abrirDbCarrito();
                const count = await db.carritoActivo.count();
                document.getElementById('cart_exists').value = count > 0 ? '1' : '0';
                console.log('Cart status checked:', count > 0);
            } catch (e) {
                console.error("Error al verificar el carrito:", e);
            }
        }

        // Ejecutar al cargar y antes de enviar el formulario
        document.addEventListener('DOMContentLoaded', checkCartStatus);
        form.addEventListener('submit', checkCartStatus);
    </script>
</body>
</html>
