<?php
require_once('includes/requires.php');

$bss_id = $_SESSION['bss_id'] ?? 0;
$user_id = $_SESSION['id'] ?? 0;

$infoMembresia = MembresiaHelper::obtenerEstado($conexion, $bss_id);
$datosPago = MembresiaHelper::getDatosPagoOficiales();

$msg_success = '';
$msg_error = '';

// Token CSRF para el reporte de pago
if (empty($_SESSION['csrf_membresia'])) {
    $_SESSION['csrf_membresia'] = bin2hex(random_bytes(32));
}
$csrf_membresia = $_SESSION['csrf_membresia'];

// Procesar envío de comprobante de pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reportar_pago'])) {
    $plan_reportado   = in_array($_POST['plan'] ?? '', ['mensual', 'anual']) ? $_POST['plan'] : 'mensual';
    $numero_operacion = trim($_POST['numero_operacion'] ?? '');
    $hora_operacion   = trim($_POST['hora_operacion'] ?? '');
    $banco_origen     = trim($_POST['banco_origen'] ?? '');
    $monto_usd        = $datosPago['precios_usd'][$plan_reportado];

    // Evitar acumular comprobantes pendientes del mismo negocio
    $hay_pendiente = false;
    $stmtPend = $conexion->prepare("SELECT id FROM membresias_pagos WHERE bss_id = ? AND estado = 'pendiente' LIMIT 1");
    if ($stmtPend) {
        $stmtPend->bind_param("i", $bss_id);
        $stmtPend->execute();
        $hay_pendiente = (bool)$stmtPend->get_result()->fetch_assoc();
        $stmtPend->close();
    }

    if (!hash_equals($csrf_membresia, $_POST['csrf_token'] ?? '')) {
        $msg_error = "Sesión inválida. Recarga la página e inténtalo de nuevo.";
    } elseif ($hay_pendiente) {
        $msg_error = "Ya tienes un comprobante en revisión. Espera la verificación antes de enviar otro.";
    } elseif (empty($numero_operacion) || empty($hora_operacion)) {
        $msg_error = "Por favor completa el número de operación y la hora del pago.";
    } else {
        $stmtIns = $conexion->prepare("INSERT INTO membresias_pagos (bss_id, usuario_id, plan, monto_usd, numero_operacion, hora_operacion, banco_origen, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')");
        if ($stmtIns) {
            $stmtIns->bind_param("iisdsss", $bss_id, $user_id, $plan_reportado, $monto_usd, $numero_operacion, $hora_operacion, $banco_origen);
            if ($stmtIns->execute()) {
                // Actualizar negocio a pendiente_verificacion
                $stmtUpd = $conexion->prepare("UPDATE negocio SET membresia_estado = 'pendiente_verificacion' WHERE id = ?");
                if ($stmtUpd) {
                    $stmtUpd->bind_param("i", $bss_id);
                    $stmtUpd->execute();
                    $stmtUpd->close();
                }

                $msg_success = "¡Comprobante registrado con éxito! Tu pago está en proceso de verificación por el equipo de soporte. Te notificaremos pronto.";
                // Recargar info actualizada
                $infoMembresia = MembresiaHelper::obtenerEstado($conexion, $bss_id);
            } else {
                $msg_error = "Error al guardar el comprobante: " . $stmtIns->error;
            }
            $stmtIns->close();
        } else {
            $msg_error = "Error preparando la consulta: " . $conexion->error;
        }
    }
}

// Calcular monto en Bs según tasa BCV
$tasa_bcv = $bcv ?? 1;
$monto_mensual_bs = round(8.00 * $tasa_bcv, 2);
$monto_anual_bs = round(40.00 * $tasa_bcv, 2);

$plan_actual = $infoMembresia['plan'] ?? 'mensual';

// Estado de verificación pendiente
$en_verificacion = !empty($msg_success) || !empty($infoMembresia['pago_pendiente']);
if ($en_verificacion && empty($msg_success)) {
    $msg_success = "¡Comprobante registrado con éxito! Tu pago está en proceso de verificación por el equipo de soporte. Te notificaremos pronto.";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Renovación de Membresía — iSeller</title>
    <?php require_once('includes/headers.php'); ?>
    <style>
        :root {
            --dash-bg: #1a1e24;
            --dash-card: #181b22;
            --dash-card-hover: #1f242c;
            --dash-border: #2e353e;
            --dash-mint: #2dd4a0;
            --dash-mint-hover: #25b88a;
            --dash-mint-dim: rgba(45, 212, 160, 0.10);
            --dash-text: #e8edf2;
            --dash-text-muted: #8892a0;
            --dash-danger: #ef5a6f;
            --dash-warning: #f5b45b;
            --dash-info: #5b9cf5;
        }

        * {
            box-shadow: none !important;
        }

        body.paywall-body {
            background-color: var(--dash-bg);
            color: var(--dash-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
            margin: 0;
        }

        .paywall-container {
            max-width: 820px;
            width: 100%;
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            overflow: hidden;
        }

        .paywall-header {
            background: var(--dash-card);
            border-bottom: 1px solid var(--dash-border);
            padding: 24px 30px;
            text-align: center;
        }

        .paywall-brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--dash-mint);
            margin-bottom: 12px;
        }

        .paywall-header .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .badge-status.vencida {
            background: rgba(239, 90, 111, 0.12);
            color: var(--dash-danger);
            border: 1px solid rgba(239, 90, 111, 0.3);
        }

        .badge-status.pendiente {
            background: rgba(245, 180, 91, 0.12);
            color: var(--dash-warning);
            border: 1px solid rgba(245, 180, 91, 0.3);
        }

        .paywall-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 6px;
            color: var(--dash-text);
        }

        .paywall-subtitle {
            font-size: 0.85rem;
            color: var(--dash-text-muted);
            margin: 0 auto;
            max-width: 580px;
            line-height: 1.45;
        }

        .paywall-content {
            padding: 26px 30px;
        }

        .step-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dash-mint);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .plan-selector-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 22px;
        }

        .plan-card-option {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            padding: 18px 20px;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease, opacity 0.15s ease, filter 0.15s ease;
            position: relative;
        }

        .plan-card-option:hover {
            border-color: rgba(45, 212, 160, 0.4);
            background: var(--dash-card-hover);
        }

        /* El plan no seleccionado se atenúa para que la elección sea evidente */
        .plan-card-option:not(.active) {
            opacity: 0.45;
            filter: grayscale(0.4);
        }

        .plan-card-option:not(.active):hover {
            opacity: 0.75;
            filter: grayscale(0);
        }

        .plan-card-option.active {
            border: 2px solid var(--dash-mint);
            background: var(--dash-mint-dim);
            padding: 17px 19px;
            /* compensar 1px de border extra */
        }

        .plan-card-option input[type="radio"] {
            position: absolute;
            top: 18px;
            right: 18px;
            accent-color: var(--dash-mint);
            width: 17px;
            height: 17px;
            cursor: pointer;
        }

        .plan-tag {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .tag-mint {
            background: var(--dash-mint);
            color: #0c1a15;
        }

        .tag-muted {
            background: rgba(255, 255, 255, 0.08);
            color: var(--dash-text-muted);
            border: 1px solid var(--dash-border);
        }

        .plan-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--dash-text);
            margin-bottom: 4px;
        }

        .plan-price {
            font-size: 1.65rem;
            font-weight: 700;
            color: var(--dash-text);
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .plan-price span {
            font-size: 0.82rem;
            color: var(--dash-text-muted);
            font-weight: 500;
        }

        .plan-beneficio {
            display: flex;
            align-items: flex-start;
            gap: 5px;
            margin-top: 8px;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.35;
            color: var(--dash-mint);
        }

        .plan-beneficio i {
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .plan-price-bs {
            font-size: 0.82rem;
            color: var(--dash-mint);
            margin-top: 4px;
            font-weight: 600;
        }

        .bank-details-box {
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-left: 4px solid var(--dash-mint);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 22px;
        }

        .bank-details-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--dash-mint);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .bank-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            font-size: 13px;
        }

        .bank-col {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            padding: 12px 14px;
        }

        .bank-item-label {
            color: var(--dash-text-muted);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }

        .bank-item-val {
            color: var(--dash-text);
            font-size: 12.5px;
            line-height: 1.5;
        }

        .bank-item-val strong {
            color: var(--dash-text);
            font-weight: 600;
        }

        .form-label-custom {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--dash-text-muted);
            margin-bottom: 5px;
        }

        .form-label-custom .text-danger {
            color: var(--dash-danger) !important;
        }

        .form-control-custom {
            width: 100%;
            background: var(--dash-bg) !important;
            border: 1px solid var(--dash-border) !important;
            color: var(--dash-text) !important;
            border-radius: 8px !important;
            padding: 9px 14px;
            font-size: 13px !important;
            transition: border-color 0.2s ease;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--dash-mint) !important;
        }

        .form-control-custom::placeholder {
            color: #556070;
        }

        .btn-submit-payment {
            width: 100%;
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #0c1a15;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit-payment:hover {
            background: #25b88a;
            color: #ffffff;
        }

        .btn-submit-payment:active {
            transform: scale(0.99);
        }

        .logout-link-wrapper {
            text-align: center;
            margin-top: 18px;
        }

        .logout-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--dash-text-muted);
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .logout-link:hover {
            color: var(--dash-danger);
            text-decoration: none;
        }

        .btn-whatsapp {
            background: rgba(37, 211, 102, 0.15);
            border: 1px solid rgba(37, 211, 102, 0.4) !important;
            color: #25d366 !important;
            border-radius: 8px !important;
            padding: 12px 18px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-whatsapp:hover {
            background: rgba(37, 211, 102, 0.25);
            color: #25d366 !important;
        }

        .btn-whatsapp-sm {
            background: rgba(37, 211, 102, 0.12);
            border: 1px solid rgba(37, 211, 102, 0.3) !important;
            color: #25d366 !important;
            border-radius: 6px !important;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }

        .btn-whatsapp-sm:hover {
            background: rgba(37, 211, 102, 0.22);
            color: #25d366 !important;
        }

        .whatsapp-footer-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #25d366;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: color 0.15s ease;
        }

        .whatsapp-footer-link:hover {
            color: #4ade80;
            text-decoration: none;
        }

        .alert-custom-success {
            background: rgba(45, 212, 160, 0.1);
            border: 1px solid rgba(45, 212, 160, 0.3);
            color: var(--dash-mint);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
        }

        .alert-custom-danger {
            background: rgba(239, 90, 111, 0.1);
            border: 1px solid rgba(239, 90, 111, 0.3);
            color: var(--dash-danger);
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 13px;
        }

        @media (max-width: 680px) {

            .plan-selector-grid,
            .bank-grid {
                grid-template-columns: 1fr;
            }

            .paywall-header,
            .paywall-content {
                padding: 18px 20px;
            }
        }
    </style>
</head>

<body class="paywall-body">

    <div class="paywall-container">
        <div class="paywall-header">
            <div class="paywall-brand">
                <img src="images/logo1-inv-96.png" alt="logo iseller" height="25px"> iSeller Tiendas
            </div>
            <div>
                <?php if ($infoMembresia['pago_pendiente']): ?>
                    <span class="badge-status pendiente">
                        <i class="bx bx-time"></i> Verificación en Proceso
                    </span>
                    <h1 class="paywall-title">Comprobante en Revisión</h1>
                    <p class="paywall-subtitle">Hemos recibido tu comprobante de pago. El equipo administrativo está verificándolo para reactivar tu cuenta a la brevedad.</p>
                <?php else: ?>
                    <span class="badge-status vencida">
                        <i class="bx bx-lock-alt"></i> Suscripción Vencida
                    </span>
                    <h1 class="paywall-title">Tu plan ha finalizado</h1>
                    <p class="paywall-subtitle">Para continuar disfrutando de todas las herramientas de iSeller, selecciona tu plan y reporta tu comprobante de pago.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="paywall-content">
            <?php if (!empty($msg_success)): ?>
                <div class="alert-custom-success d-flex align-items-center mb-4" role="alert">
                    <i class="bx bx-check-circle me-3" style="font-size:1.6rem; flex-shrink: 0;"></i>
                    <div>
                        <div style="font-weight: 700; margin-bottom: 2px;">¡Comprobante registrado con éxito!</div>
                        <div>Tu pago está en proceso de verificación por el equipo de soporte. Te notificaremos pronto.</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($msg_error)): ?>
                <div class="alert-custom-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bx bx-error-circle me-2" style="font-size:1.3rem;"></i>
                    <div><?php echo htmlspecialchars($msg_error); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($infoMembresia['ultimo_comprobante']) && $infoMembresia['ultimo_comprobante']['estado'] === 'rechazado' && empty($msg_success)): ?>
                <div class="alert-custom-danger d-flex align-items-center mb-4" role="alert" style="background: rgba(239, 90, 111, 0.15); border-color: rgba(239, 90, 111, 0.4);">
                    <i class="bx bx-error-circle me-3" style="font-size:1.6rem; flex-shrink: 0;"></i>
                    <div>
                        <div style="font-weight: 700; margin-bottom: 3px;">Comprobante no válido (Ref: <?= htmlspecialchars($infoMembresia['ultimo_comprobante']['numero_operacion'] ?? '') ?>)</div>
                        <div style="font-size: 13px; line-height: 1.45;">
                            Motivo del administrador: <em>"<?= htmlspecialchars($infoMembresia['ultimo_comprobante']['nota_admin'] ?? 'No se pudo verificar la transferencia.') ?>"</em>.
                            Por favor revisa tus datos y reporta un comprobante válido a continuación.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($en_verificacion): ?>
                <!-- Estado en revisión: se ocultan datos bancarios y formulario -->
                <div class="bank-details-box" style="border-left-color: var(--dash-warning);">
                    <div class="bank-details-title" style="color: var(--dash-warning);">
                        <i class="bx bx-time-five"></i> Comprobante en proceso de validación
                    </div>
                    <p style="font-size: 13px; color: var(--dash-text-muted); margin-bottom: 14px; line-height: 1.5;">
                        Hemos recibido tu comprobante. Tan pronto como nuestro equipo confirme los fondos, tu suscripción y acceso a la plataforma serán reactivados inmediatamente.
                    </p>
                    <?php if (!empty($infoMembresia['ultimo_comprobante'])):
                        $comp = $infoMembresia['ultimo_comprobante'];
                    ?>
                        <div class="bank-grid">
                            <div class="bank-col">
                                <div class="bank-item-label">Nro. de Operación</div>
                                <div class="bank-item-val"><strong><?php echo htmlspecialchars($comp['numero_operacion'] ?? ''); ?></strong></div>
                                <div class="bank-item-label" style="margin-top: 8px;">Plan Solicitado</div>
                                <div class="bank-item-val"><?php echo ucfirst(htmlspecialchars($comp['plan'] ?? '')); ?> ($<?php echo number_format((float)($comp['monto_usd'] ?? 0), 2); ?> USD)</div>
                            </div>
                            <div class="bank-col">
                                <div class="bank-item-label">Fecha / Hora</div>
                                <div class="bank-item-val"><?php echo htmlspecialchars($comp['hora_operacion'] ?? ($comp['fecha_reporte'] ?? 'Reciente')); ?></div>
                                <div class="bank-item-label" style="margin-top: 8px;">Estado</div>
                                <div class="bank-item-val"><span style="color: var(--dash-warning); font-weight: 600;"><i class="bx bx-loader-alt bx-spin"></i> Pendiente de verificación</span></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap;">
                    <a href="membresia_renovacion.php" class="btn-submit-payment" style="text-decoration: none; margin-top: 0; flex: 1;">
                        <i class="bx bx-refresh"></i> Comprobar estado de activación
                    </a>
                    <a href="https://wa.me/584160679095?text=<?php echo urlencode('Hola, he reportado el pago de mi membresía en iSeller y deseo agilizar la verificación. Negocio ID: ' . $bss_id); ?>" target="_blank" class="btn-whatsapp" style="flex: 1;">
                        <i class="bx bxl-whatsapp" style="font-size: 1.25rem;"></i> Notificar por WhatsApp (+58 416 0679095)
                    </a>
                </div>

            <?php else: ?>

                <form method="POST" id="formRenovacion">
                    <input type="hidden" name="reportar_pago" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_membresia); ?>">

                    <!-- Selector de Planes -->
                    <div class="step-label">
                        <i class="bx bx-select-multiple"></i> 1. Selecciona tu plan de suscripción:
                    </div>
                    <div class="plan-selector-grid">
                        <!-- Tarjeta Mensual -->
                        <div class="plan-card-option <?php echo ($plan_actual === 'mensual') ? 'active' : ''; ?>" id="card-mensual" onclick="elegirPlan('mensual')">
                            <input type="radio" name="plan" value="mensual" <?php echo ($plan_actual === 'mensual') ? 'checked' : ''; ?>>
                            <span class="plan-tag tag-muted">Estándar</span>
                            <div class="plan-name">Plan Mensual</div>
                            <div class="plan-price">$8.00 <span>/ mes</span></div>
                            <div class="plan-price-bs">~ <?php echo number_format($monto_mensual_bs, 2, ',', '.'); ?> Bs (Tasa BCV)</div>
                        </div>

                        <!-- Tarjeta Anual -->
                        <div class="plan-card-option <?php echo ($plan_actual === 'anual') ? 'active' : ''; ?>" id="card-anual" onclick="elegirPlan('anual')">
                            <input type="radio" name="plan" value="anual" <?php echo ($plan_actual === 'anual') ? 'checked' : ''; ?>>
                            <span class="plan-tag tag-mint">Ahorro 58%</span>
                            <div class="plan-name">Plan Anual</div>
                            <div class="plan-price">$40.00 <span>/ año</span></div>
                            <div class="plan-price-bs">~ <?php echo number_format($monto_anual_bs, 2, ',', '.'); ?> Bs (Tasa BCV)</div>
                            <div class="plan-beneficio"><i class="bx bxl-facebook-circle"></i> Incluye 5 días de publicidad en Facebook Ads (media de ~4.000 vistas diarias) al configurar tu tienda virtual</div>
                        </div>
                    </div>

                    <!-- Datos bancarios de la plataforma -->
                    <div class="bank-details-box">
                        <div class="bank-details-title">
                            <i class="bx bx-credit-card"></i> Datos para realizar el pago:
                        </div>
                        <div class="bank-grid">
                            <div class="bank-col">
                                <div class="bank-item-label">Pago Móvil</div>
                                <div class="bank-item-val"><strong><?php echo $datosPago['pago_movil']['banco']; ?></strong></div>
                                <div class="bank-item-val">Teléfono: <strong><?php echo $datosPago['pago_movil']['telefono']; ?></strong></div>
                                <div class="bank-item-val">Cédula: <strong><?php echo $datosPago['pago_movil']['cedula']; ?></strong></div>
                            </div>
                            <div class="bank-col">
                                <div class="bank-item-label">Transferencia Bancaria</div>
                                <div class="bank-item-val"><strong><?php echo $datosPago['transferencia']['banco']; ?></strong></div>
                                <div class="bank-item-val">Cuenta: <strong><?php echo $datosPago['transferencia']['cuenta']; ?></strong></div>
                                <div class="bank-item-val">Cédula: <strong><?php echo $datosPago['transferencia']['cedula']; ?></strong></div>
                                <div class="bank-item-val">Titular: <strong><?php echo $datosPago['transferencia']['titular']; ?></strong></div>
                            </div>
                            <div class="bank-col" style="grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                                <div>
                                    <div class="bank-item-label">Contacto rápido / Soporte WhatsApp</div>
                                    <div class="bank-item-val" style="display: flex; align-items: center; gap: 6px;">
                                        <i class="bx bxl-whatsapp" style="color: #25d366; font-size: 1.15rem;"></i>
                                        <strong>+58 416 0679095</strong>
                                    </div>
                                </div>
                                <a href="https://wa.me/584160679095?text=<?php echo urlencode('Hola, tengo dudas sobre el pago de mi membresía en iSeller.'); ?>" target="_blank" class="btn-whatsapp-sm">
                                    <i class="bx bxl-whatsapp"></i> Escribir a soporte
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de comprobante -->
                    <div class="step-label">
                        <i class="bx bx-file"></i> 2. Carga los datos de tu comprobante:
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-custom" for="numero_operacion">Número de referencia / operación <span class="text-danger">*</span></label>
                            <input type="text" id="numero_operacion" name="numero_operacion" class="form-control-custom" placeholder="Ej: 12345678" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom" for="hora_operacion">Fecha de la operación <span class="text-danger">*</span></label>
                            <input type="date" id="hora_operacion" name="hora_operacion" class="form-control-custom" required>
                        </div>
                        <div class="col-md-12 d-none">
                            <label class="form-label-custom" for="banco_origen">Banco emisor (Opcional)</label>
                            <input type="text" id="banco_origen" name="banco_origen" class="form-control-custom" placeholder="Ej: Banesco, Mercantil, Provincial...">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-payment">
                        <i class="bx bx-send"></i> Enviar Comprobante para Verificación
                    </button>
                </form>

            <?php endif; ?>

            <div class="logout-link-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <a href="https://wa.me/584160679095?text=<?php echo urlencode('Hola, necesito asistencia con mi membresía de iSeller.'); ?>" target="_blank" class="whatsapp-footer-link">
                    <i class="bx bxl-whatsapp" style="font-size: 1.15rem;"></i> Soporte WhatsApp: +58 416 0679095
                </a>
                <span style="color: var(--dash-border);">|</span>
                <a href="../../login/salir.php" class="logout-link" style="margin-top: 0;">
                    <i class="bx bx-log-out"></i> Cerrar sesión
                </a>
            </div>
        </div>
    </div>

    <script>
        function elegirPlan(tipo) {
            document.querySelectorAll('.plan-card-option').forEach(c => c.classList.remove('active'));
            const card = document.getElementById('card-' + tipo);
            if (card) {
                card.classList.add('active');
                const radio = card.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            }
        }
    </script>

</body>

</html>