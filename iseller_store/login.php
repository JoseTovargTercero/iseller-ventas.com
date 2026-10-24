<?php

/**
 * Página de Login
 * Formulario de inicio de sesión y procesamiento
 */

require_once('core/db.php');
require_once('core/session.php');

// Si ya está logueado, volver a la tienda desde donde entró
if (isLoggedIn()) {
    redirect(authReturnUrl('../'));
}

$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error de validación CSRF. Por favor, intente de nuevo.');
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        // Consultar usuario en la base de datos
        $stmt = $conexion_store->prepare("SELECT id, nombre, email, password, estado FROM tienda_usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verificar si el usuario está activo
            if ($user['estado'] != 1) {
                $error = 'Esta cuenta está desactivada. Contacte al administrador.';
            }
            // Verificar contraseña
            else if (password_verify($password, $user['password'])) {
                // Login exitoso
                loginUser($user);
                redirect(authReturnUrl('../'));
            } else {
                $error = 'Email o contraseña incorrectos.';
            }
        } else {
            $error = 'Email o contraseña incorrectos.';
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
    <title>Iniciar Sesión - iSeller Store</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo-tienda.png">
    <link rel="apple-touch-icon" href="assets/img/logo-tienda.png">

    <!-- Bootstrap CSS -->
    <link href="../publico/vendors/bootstrap5/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">
    <!-- Global Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">

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
            max-width: 420px;
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
            box-shadow: none;
        }

        .input-group-text {
            background: #f8faf9;
            border-color: #e9ecef;
            color: var(--text-secondary);
        }

        .input-group:focus-within {
            border-radius: 10px;
            box-shadow: 0 0 0 0.2rem rgba(111, 175, 122, 0.18);
        }

        .input-group:focus-within .input-group-text {
            background: #fff;
            border-color: var(--primary-color);
            color: var(--primary-dark);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-login:hover,
        .btn-login:focus {
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
                        <h2 class="login-title">Bienvenido de nuevo</h2>
                        <p class="text-muted small mb-0">Ingresa a tu cuenta para continuar</p>
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

                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control login-input border-start-0" id="email" name="email"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    placeholder="nombre@ejemplo.com" autocomplete="email" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control login-input border-start-0" id="password" name="password"
                                    placeholder="••••••••" autocomplete="current-password" required>
                            </div>
                            <div class="text-end mt-2">
                                <a href="forgot_password.php" class="login-link small">¿Olvidaste tu contraseña?</a>
                            </div>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-login text-white">
                                Iniciar Sesión
                            </button>
                        </div>

                        <div class="text-center border-top pt-3">
                            <p class="small text-muted mb-2">¿No tienes una cuenta?</p>
                            <a href="registro.php" class="login-link">
                                Crear cuenta nueva <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script src="../publico/vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
</body>

</html>