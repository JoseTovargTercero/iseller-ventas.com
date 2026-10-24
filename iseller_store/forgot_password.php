<?php
/**
 * Página de Recuperación de Contraseña (Solicitud)
 */

require_once('core/db.php');
require_once('core/session.php');

if (isLoggedIn()) {
    redirect('../');
}

$error = '';
$success = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error de validación CSRF.');
    }

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Por favor, ingrese su correo electrónico.';
    } else {
        // Verificar si el correo existe
        $stmt = $conexion_store->prepare("SELECT id FROM tienda_usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Guardar token en DB
            $update = $conexion_store->prepare("UPDATE tienda_usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expires, $user['id']);

            if ($update->execute()) {
                $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

                $asunto = 'Restablece tu contraseña - iSeller Store';
                $cuerpo = "Hola,\n\n"
                    . "Recibimos una solicitud para restablecer la contraseña de tu cuenta.\n\n"
                    . "Abre este enlace (vence en 1 hora):\n" . $reset_link . "\n\n"
                    . "Si no solicitaste el cambio, ignora este correo.\n";
                $headers = "From: iSeller Store <no-reply@iseller-tiendas.com>\r\n"
                    . "Reply-To: no-reply@iseller-tiendas.com\r\n"
                    . "MIME-Version: 1.0\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n";
                @mail($email, $asunto, $cuerpo, $headers);
            } else {
                $error = 'Hubo un error al procesar la solicitud.';
            }
            $update->close();
        }
        // Misma respuesta exista o no la cuenta: no se revela qué correos están registrados.
        if (!$error) {
            $success = 'Si el correo está registrado, te enviamos un enlace para restablecer la contraseña.';
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
    <title>Recuperar Contraseña - iSeller Store</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo-tienda.png">
    <link rel="apple-touch-icon" href="assets/img/logo-tienda.png">
    <link href="../publico/vendors/bootstrap5/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">
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
            box-shadow: 0 0 0 0.2rem rgba(111, 175, 122, 0.18);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-submit:hover,
        .btn-submit:focus {
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
                        <h2 class="login-title">Recuperar Contraseña</h2>
                        <p class="text-muted small mb-0">Ingresa tu email y te enviaremos un enlace para restablecer tu cuenta.</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger d-flex align-items-center small py-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?php echo $error; ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success small py-3">
                            <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-4">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" class="form-control login-input" id="email" name="email"
                                placeholder="nombre@ejemplo.com" autocomplete="email" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-submit text-white">Enviar enlace</button>
                        </div>
                    </form>

                    <div class="text-center border-top pt-3 mt-4">
                        <a href="login.php" class="login-link">
                            <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../publico/vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
</body>
</html>
