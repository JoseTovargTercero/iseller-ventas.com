<?php
require_once('includes/requires.php');

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();
$bssId = (int) $bss_id;
$clienteId = (int) ($_GET['id'] ?? 0);
$cedula = trim((string) ($_GET['cedula'] ?? ''));
$nombreParam = trim((string) ($_GET['nombre'] ?? ''));

$cliente = null;
if ($clienteId > 0) {
    $stmt = $conexion->prepare(
        "SELECT id, cedula, nombre, telefono
         FROM clientes
         WHERE bss_id = ? AND id = ?
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('ii', $bssId, $clienteId);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
if (!$cliente && $cedula !== '') {
    $stmt = $conexion->prepare(
        "SELECT id, cedula, nombre, telefono
         FROM clientes
         WHERE bss_id = ? AND cedula = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('is', $bssId, $cedula);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}
if (!$cliente && $nombreParam !== '') {
    $stmt = $conexion->prepare(
        "SELECT id, cedula, nombre, telefono
         FROM clientes
         WHERE bss_id = ? AND nombre = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('is', $bssId, $nombreParam);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$cedulaCliente = trim((string) ($cliente['cedula'] ?? $cedula));
$nombreCliente = trim((string) ($cliente['nombre'] ?? $nombreParam));
$telefonoCliente = trim((string) ($cliente['telefono'] ?? ''));

$error = '';
if ($cedulaCliente === '' && $nombreCliente === '') {
    $error = 'No se indicó un cliente válido.';
}

$ordenes = [];
$totalOrdenes = 0;
$totalComprasUsd = 0.0;
$totalComprasBs = 0.0;
$totalComprasCop = 0.0;
$creditosActivos = 0;
$creditosActivosUsd = 0.0;
$creditosActivosBs = 0.0;
$creditosActivosCop = 0.0;
$limite = 1000;

if ($error === '') {
    $condiciones = [];
    $params = [$bssId];
    $types = 'i';

    if ($cedulaCliente !== '') {
        $condiciones[] = 'TRIM(o.cliente) = ?';
        $params[] = $cedulaCliente;
        $types .= 's';

        $condiciones[] = 'EXISTS (
            SELECT 1 FROM creditos c2
            WHERE c2.order_id = o.id
              AND c2.bss_id = o.bss_id
              AND c2.cedula = ?
        )';
        $params[] = $cedulaCliente;
        $types .= 's';
    }

    if ($nombreCliente !== '') {
        $condiciones[] = 'EXISTS (
            SELECT 1 FROM creditos c2
            WHERE c2.order_id = o.id
              AND c2.bss_id = o.bss_id
              AND (c2.cedula IS NULL OR c2.cedula = \'\' OR c2.cedula = \'0\')
              AND c2.negocio = ?
        )';
        $params[] = $nombreCliente;
        $types .= 's';
    }

    $where = "o.bss_id = ? AND o.status IN (1, 2, 4)";
    if ($condiciones) {
        $where .= ' AND (' . implode(' OR ', $condiciones) . ')';
    }

    $stmt = $conexion->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(o.total_price), 0) AS total_usd,
                COALESCE(SUM(o.total_price_bs), 0) AS total_bs,
                COALESCE(SUM(o.total_price_cop), 0) AS total_cop
         FROM orden o
         WHERE $where"
    );
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $resumen = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalOrdenes = (int) ($resumen['total'] ?? 0);
        $totalComprasUsd = (float) ($resumen['total_usd'] ?? 0);
        $totalComprasBs = (float) ($resumen['total_bs'] ?? 0);
        $totalComprasCop = (float) ($resumen['total_cop'] ?? 0);
    }

    $stmt = $conexion->prepare(
        "SELECT
            o.id,
            o.created,
            o.status,
            o.total_price,
            o.total_price_bs,
            o.total_price_cop,
            o.tipoPago,
            COALESCE(s.nombre, '—') AS sucursal,
            EXISTS(
                SELECT 1 FROM creditos c
                WHERE c.order_id = o.id
                  AND c.bss_id = o.bss_id
                  AND c.estado = '2'
            ) AS credito_activo
         FROM orden o
         LEFT JOIN sucursales s ON s.id = o.id_sucursal AND s.bss_id = o.bss_id
         WHERE $where
         ORDER BY o.created DESC, o.id DESC
         LIMIT $limite"
    );
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $ordenes[] = $fila;
        }
        $stmt->close();
    }

    $creditoCondiciones = [];
    $creditoParams = [$bssId];
    $creditoTypes = 'i';

    if ($cedulaCliente !== '') {
        $creditoCondiciones[] = 'cedula = ?';
        $creditoParams[] = $cedulaCliente;
        $creditoTypes .= 's';
    }
    if ($nombreCliente !== '') {
        $creditoCondiciones[] = '(cedula IS NULL OR cedula = \'\' OR cedula = \'0\') AND negocio = ?';
        $creditoParams[] = $nombreCliente;
        $creditoTypes .= 's';
    }

    if ($creditoCondiciones) {
        $stmt = $conexion->prepare(
            "SELECT COUNT(*) AS activos,
                    COALESCE(SUM(total_price), 0) AS total_usd,
                    COALESCE(SUM(total_price_bs), 0) AS total_bs,
                    COALESCE(SUM(total_price_cop), 0) AS total_cop
             FROM creditos
             WHERE bss_id = ? AND estado = '2'
               AND (" . implode(' OR ', $creditoCondiciones) . ")"
        );
        if ($stmt) {
            $stmt->bind_param($creditoTypes, ...$creditoParams);
            $stmt->execute();
            $creditoResumen = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $creditosActivos = (int) ($creditoResumen['activos'] ?? 0);
            $creditosActivosUsd = (float) ($creditoResumen['total_usd'] ?? 0);
            $creditosActivosBs = (float) ($creditoResumen['total_bs'] ?? 0);
            $creditosActivosCop = (float) ($creditoResumen['total_cop'] ?? 0);
        }
    }
}

$pagosTexto = [
    '1' => 'Punto',
    '2' => 'Pago Móvil',
    '3' => 'Transferencia',
    '4' => 'BS Efectivo',
    '5' => 'Dólares',
    '6' => 'Pesos',
    '7' => 'Biopago',
    '8' => 'Fraccionado',
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Compras del cliente</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .cc-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .cc-header h2 {
            margin: 0 0 4px;
            color: var(--dash-text);
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.3px;
        }

        .cc-header p {
            margin: 0;
            color: var(--dash-text-muted);
            font-size: 13px;
        }

        .cc-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 14px;
            border: 1px solid var(--dash-border);
            border-radius: 9px;
            background: rgba(255, 255, 255, .04);
            color: var(--dash-text-muted) !important;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none !important;
        }

        .cc-back:hover {
            border-color: var(--dash-mint);
            color: var(--dash-mint) !important;
            background: rgba(45, 212, 160, .08);
        }

        .cc-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .cc-stat {
            padding: 15px 17px;
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            background: var(--dash-card);
        }

        .cc-stat span {
            display: block;
            margin-bottom: 5px;
            color: var(--dash-text-muted);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .45px;
            text-transform: uppercase;
        }

        .cc-stat strong {
            color: var(--dash-text);
            font-size: 21px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }

        .cc-stat.credit strong {
            color: #f5b45b;
        }

        .cc-panel {
            border: 1px solid var(--dash-border);
            border-radius: 16px;
            background: var(--dash-card);
            overflow: hidden;
        }

        .cc-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 15px 18px;
            border-bottom: 1px solid var(--dash-border);
        }

        .cc-panel-head h3 {
            margin: 0;
            color: var(--dash-text);
            font-size: 14px;
            font-weight: 800;
        }

        .cc-panel-head small {
            color: var(--dash-text-muted);
            font-size: 11.5px;
        }

        .cc-table-wrap {
            overflow-x: auto;
        }

        .cc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .cc-table th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--dash-border);
            background: rgba(255, 255, 255, .015);
            color: var(--dash-text-muted);
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .4px;
            text-align: left;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .cc-table td {
            padding: 11px 12px;
            border-bottom: 1px solid rgba(46, 53, 62, .55);
            color: var(--dash-text);
            vertical-align: middle;
        }

        .cc-table tr:last-child td {
            border-bottom: 0;
        }

        .cc-table tbody tr:hover {
            background: rgba(45, 212, 160, .03);
        }

        .cc-right {
            text-align: right !important;
        }

        .cc-money {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .cc-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .2px;
            white-space: nowrap;
        }

        .cc-badge.sale {
            background: rgba(91, 156, 245, .14);
            color: #5b9cf5;
        }

        .cc-badge.mayor {
            background: rgba(167, 139, 250, .14);
            color: #a78bfa;
        }

        .cc-badge.credit {
            background: rgba(245, 180, 91, .15);
            color: #f5b45b;
        }

        .cc-badge.ok {
            background: rgba(45, 212, 160, .14);
            color: var(--dash-mint);
        }

        .cc-actions {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
        }

        .cc-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            background: transparent;
            color: var(--dash-text-muted);
            text-decoration: none !important;
            transition: all .15s ease;
        }

        .cc-icon-btn:hover {
            color: var(--dash-mint);
            border-color: rgba(45, 212, 160, .5);
            background: rgba(45, 212, 160, .06);
        }

        .cc-empty {
            padding: 42px 20px;
            color: var(--dash-text-muted);
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .cc-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .right_col {
                padding: 14px 12px !important;
            }

            .cc-stats {
                grid-template-columns: 1fr;
            }

            .cc-table th,
            .cc-table td {
                padding: 9px 8px;
            }
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu; ?>
            <?php echo $topnav; ?>

            <div class="right_col" role="main">
                <div class="cc-header">
                    <div>
                        <h2>Compras de <?= htmlspecialchars($nombreCliente ?: 'cliente', ENT_QUOTES, 'UTF-8') ?></h2>
                        <p>
                            <?php if ($cedulaCliente !== ''): ?>
                                Cédula: <?= htmlspecialchars($cedulaCliente, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                            <?php if ($telefonoCliente !== ''): ?>
                                · Teléfono: <?= htmlspecialchars($telefonoCliente, ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="clientes.php" class="cc-back">
                        <i class="fa fa-arrow-left"></i> Volver a clientes
                    </a>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="cc-panel">
                        <div class="cc-empty"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php else: ?>
                    <div class="cc-stats">
                        <div class="cc-stat">
                            <span>Compras registradas</span>
                            <strong><?= number_format($totalOrdenes, 0, ',', '.') ?></strong>
                        </div>
                        <div class="cc-stat">
                            <span>Total comprado</span>
                            <strong>$<?= number_format($totalComprasUsd, 2, '.', ',') ?></strong>
                        </div>
                        <div class="cc-stat credit">
                            <span>Créditos activos</span>
                            <strong><?= number_format($creditosActivos, 0, ',', '.') ?></strong>
                        </div>
                        <div class="cc-stat credit">
                            <span>Monto en créditos activos</span>
                            <strong>$<?= number_format($creditosActivosUsd, 2, '.', ',') ?></strong>
                        </div>
                    </div>

                    <div class="cc-panel">
                        <div class="cc-panel-head">
                            <div>
                                <h3>Historial de compras</h3>
                                <small>Ordenado de la compra más reciente a la más antigua.</small>
                            </div>
                            <small>
                                <?= number_format(count($ordenes), 0, ',', '.') ?> mostradas
                                <?= $totalOrdenes > $limite ? '(límite de ' . number_format($limite, 0, ',', '.') . ')' : '' ?>
                            </small>
                        </div>
                        <div class="cc-table-wrap">
                            <table class="cc-table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>#</th>
                                        <th>Tipo</th>
                                        <th>Sucursal</th>
                                        <th class="cc-right">USD</th>
                                        <th class="cc-right">Bs</th>
                                        <th class="cc-right">COP</th>
                                        <th>Pago</th>
                                        <th>Estado</th>
                                        <th class="cc-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$ordenes): ?>
                                        <tr>
                                            <td colspan="10" class="cc-empty">Este cliente no tiene compras registradas.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($ordenes as $orden): ?>
                                            <?php
                                            $esCreditoActivo = (int) ($orden['credito_activo'] ?? 0) === 1;
                                            $status = (int) ($orden['status'] ?? 0);
                                            $esCredito = $status === 2 || $esCreditoActivo;
                                            $tipoClase = $status === 4 ? 'mayor' : ($esCredito ? 'credit' : 'sale');
                                            $tipoTexto = $status === 4 ? 'Mayor' : ($esCredito ? 'Crédito' : 'Venta');
                                            $estadoClase = $esCreditoActivo ? 'credit' : 'ok';
                                            $estadoTexto = $esCreditoActivo ? 'Crédito activo' : ($esCredito ? 'Crédito pagado' : 'Pagada');
                                            $tipoPago = (string) ($orden['tipoPago'] ?? '');
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $orden['created'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><strong>#<?= (int) $orden['id'] ?></strong></td>
                                                <td><span class="cc-badge <?= $tipoClase ?>"><?= $tipoTexto ?></span></td>
                                                <td><?= htmlspecialchars($orden['sucursal'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="cc-right cc-money">$<?= number_format((float) $orden['total_price'], 2, '.', ',') ?></td>
                                                <td class="cc-right cc-money"><?= number_format((float) $orden['total_price_bs'], 2, ',', '.') ?></td>
                                                <td class="cc-right cc-money"><?= number_format((float) $orden['total_price_cop'], 0, ',', '.') ?></td>
                                                <td><?= htmlspecialchars($pagosTexto[$tipoPago] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><span class="cc-badge <?= $estadoClase ?>"><?= $estadoTexto ?></span></td>
                                                <td class="cc-right">
                                                    <div class="cc-actions">
                                                        <a class="cc-icon-btn" href="detallesVenta.php?id=<?= (int) $orden['id'] ?>" target="_blank" rel="noopener" title="Ver detalle">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <a class="cc-icon-btn" href="ticket_venta_58.php?id=<?= (int) $orden['id'] ?>" target="_blank" rel="noopener" title="Imprimir ticket 58 mm">
                                                            <i class="fa fa-print"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="js/nombre_pagina.js"></script>
</body>

</html>
