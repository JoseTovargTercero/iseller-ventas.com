<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] != 1) {
    header('Location: membresia_renovacion.php');
    exit;
}

$topnav = topnav();
$bss_id = $_SESSION['bss_id'] ?? 0;

$infoMembresia = MembresiaHelper::obtenerEstado($conexion, $bss_id);

// Obtener historial de pagos reportados
$historialPagos = [];
$stmtHist = $conexion->prepare("SELECT id, plan, monto_usd, numero_operacion, hora_operacion, banco_origen, fecha_reporte, estado, nota_admin FROM membresias_pagos WHERE bss_id = ? ORDER BY id DESC");
if ($stmtHist) {
    $stmtHist->bind_param("i", $bss_id);
    $stmtHist->execute();
    $resHist = $stmtHist->get_result();
    while ($row = $resHist->fetch_assoc()) {
        $historialPagos[] = $row;
    }
    $stmtHist->close();
}

// Calcular progreso del tiempo
$porcentajeTiempo = 0;
if ($infoMembresia['fecha_inicio'] && $infoMembresia['fecha_fin']) {
    $inicio = strtotime($infoMembresia['fecha_inicio']);
    $fin = strtotime($infoMembresia['fecha_fin']);
    $totalSegundos = max(1, $fin - $inicio);
    $transcurrido = max(0, time() - $inicio);
    $porcentajeTiempo = min(100, round(($transcurrido / $totalSegundos) * 100));
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Estado de Membresía — iSeller</title>
    <?php require_once('includes/headers.php'); ?>
    <style>
        .membership-card {
            background: var(--dash-card, #181b22);
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: none !important;
        }

        .plan-badge-lg {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-prueba {
            background: rgba(45, 212, 160, 0.15);
            color: #2dd4a0;
            border: 1px solid rgba(45, 212, 160, 0.3);
        }

        .badge-mensual {
            background: rgba(45, 212, 160, 0.15);
            color: #2dd4a0;
            border: 1px solid rgba(45, 212, 160, 0.3);
        }

        .badge-anual {
            background: rgba(168, 85, 247, 0.15);
            color: #c084fc;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-activa {
            background: rgba(45, 212, 160, 0.15);
            color: #2dd4a0;
        }

        .status-vencida {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        .status-pendiente {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .stat-box {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 16px;
            box-shadow: none !important;
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--dash-text-muted, #8892a0);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .stat-val {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--dash-text, #e8edf2);
        }

        .progress-custom {
            height: 8px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 14px;
            box-shadow: none !important;
        }

        .progress-bar-custom {
            height: 100%;
            background: linear-gradient(90deg, #2dd4a0, #25b88a);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .btn-renovar-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #2dd4a0;
            color: #0f172a !important;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: none !important;
        }

        .btn-renovar-action:hover {
            background: #25b88a;
            transform: translateY(-1px);
        }
    </style>
</head>

<body class='nav-md'>
    <div class='container body'>
        <div class='main_container'>

            <?php echo $menu ?>

            <!-- top navigation -->
            <?php echo $topnav ?>
            <!-- /top navigation -->

            <!-- page content -->
            <div class='right_col' role='main'>
                <div class="row">
                    <div class="col-md-12">

                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                            <div>
                                <h2 class="mb-0 text-white font-weight-bold" style="font-size: 1.6rem;">Estado de Membresía</h2>
                                <p class="text-muted mb-0">Detalles de tu plan, fecha de vencimiento y facturación.</p>
                            </div>
                            <div>
                                <a href="membresia_renovacion.php" class="btn-renovar-action">
                                    <i class="bx bx-refresh"></i> Renovar o Cambiar Plan
                                </a>
                            </div>
                        </div>

                        <!-- Tarjeta Principal de Estado -->
                        <div class="membership-card">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <?php
                                    $badgeClass = 'badge-prueba';
                                    if ($infoMembresia['tipo'] === 'mensual') $badgeClass = 'badge-mensual';
                                    if ($infoMembresia['tipo'] === 'anual') $badgeClass = 'badge-anual';
                                    ?>
                                    <span class="plan-badge-lg <?php echo $badgeClass; ?>">
                                        <i class="bx bx-crown"></i> Plan <?php echo ucfirst($infoMembresia['tipo']); ?>
                                    </span>

                                    <?php if ($infoMembresia['esta_vencida']): ?>
                                        <span class="status-pill status-vencida ms-2">Vencida</span>
                                    <?php elseif ($infoMembresia['pago_pendiente']): ?>
                                        <span class="status-pill status-pendiente ms-2">Verificación pendiente</span>
                                    <?php else: ?>
                                        <span class="status-pill status-activa ms-2">Activa</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted" style="font-size: 0.85rem;">Próxima renovación programada:</span>
                                    <div class="font-weight-bold text-white">Plan <?php echo ucfirst($infoMembresia['plan']); ?></div>
                                </div>
                            </div>

                            <!-- Métricas de Fecha -->
                            <div class="stat-grid">
                                <div class="stat-box">
                                    <div class="stat-label">Fecha de Inicio</div>
                                    <div class="stat-val">
                                        <?php echo $infoMembresia['fecha_inicio'] ? date('d/m/Y', strtotime($infoMembresia['fecha_inicio'])) : 'N/D'; ?>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Fecha de Vencimiento</div>
                                    <div class="stat-val text-white">
                                        <?php echo $infoMembresia['fecha_fin'] ? date('d/m/Y', strtotime($infoMembresia['fecha_fin'])) : 'N/D'; ?>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Tiempo Restante</div>
                                    <div class="stat-val <?php echo ($infoMembresia['dias_restantes'] <= 7) ? 'text-warning' : 'text-success'; ?>">
                                        <?php if ($infoMembresia['esta_vencida']): ?>
                                            0 días (Expirada)
                                        <?php else: ?>
                                            <?php echo $infoMembresia['dias_restantes']; ?> días
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Barra de progreso -->
                            <div class="progress-custom">
                                <div class="progress-bar-custom" style="width: <?php echo $porcentajeTiempo; ?>%;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-muted" style="font-size: 0.75rem;">
                                <span>Periodo transcurrido: <?php echo $porcentajeTiempo; ?>%</span>
                                <span><?php echo max(0, $infoMembresia['dias_restantes']); ?> días para vencer</span>
                            </div>
                        </div>

                        <!-- Historial de Pagos -->
                        <div class="membership-card">
                            <h4 class="text-white font-weight-bold mb-3" style="font-size: 1.15rem;">
                                <i class="bx bx-history me-2" style="color: #2dd4a0;"></i> Historial de Comprobantes Reportados
                            </h4>

                            <?php if (empty($historialPagos)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bx bx-receipt" style="font-size: 2.5rem; opacity: 0.4;"></i>
                                    <p class="mt-2 mb-0">No hay pagos reportados todavía.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" style="color: var(--dash-text, #e8edf2);">
                                        <thead>
                                            <tr style="border-bottom: 1px solid var(--dash-border, #2e353e); font-size: 0.8rem; color: #8892a0;">
                                                <th>Fecha</th>
                                                <th>Plan</th>
                                                <th>Monto</th>
                                                <th>Nº Operación</th>
                                                <th>Hora</th>
                                                <th>Banco</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historialPagos as $pago): ?>
                                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                    <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_reporte'])); ?></td>
                                                    <td><strong>Plan <?php echo ucfirst($pago['plan']); ?></strong></td>
                                                    <td>$<?php echo number_format($pago['monto_usd'], 2); ?></td>
                                                    <td><code><?php echo htmlspecialchars($pago['numero_operacion']); ?></code></td>
                                                    <td><?php echo htmlspecialchars($pago['hora_operacion']); ?></td>
                                                    <td><?php echo htmlspecialchars($pago['banco_origen'] ?: '-'); ?></td>
                                                    <td>
                                                        <?php if ($pago['estado'] === 'aprobado'): ?>
                                                            <span class="badge bg-success">Aprobado</span>
                                                        <?php elseif ($pago['estado'] === 'rechazado'): ?>
                                                            <span class="badge bg-danger">Rechazado</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src='../vendors/jquery/dist/jquery.min.js'></script>
    <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
    <script src='../vendors/fastclick/lib/fastclick.js'></script>
    <script src='../vendors/nprogress/nprogress.js'></script>
    <script src='../build/js/custom.js'></script>
</body>

</html>