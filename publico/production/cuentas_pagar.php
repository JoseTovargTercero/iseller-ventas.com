<?php
require_once('includes/requires.php');
require_once('../../configurar/_cuentas_pagar.php');

$nivel = (int) $_SESSION['nivel'];
if ($nivel !== 1 && $nivel !== 2) {
    header('Location: ../../index.php');
    exit;
}

$bss_id = (int) $_SESSION['bss_id'];

if (!cp_asegurar_esquema($conexion)) {
    http_response_code(500);
    echo 'No se pudieron preparar las tablas de cuentas por pagar.';
    exit;
}

// Las facturas de compras son opcionales en este módulo; si su tabla aún no
// existe, la sección de compras simplemente aparece vacía.
fc_asegurar_esquema($conexion);

// Igual que el módulo de compras: solo se muestran si la opción está activa.
$verCompras = false;
$stmtConfig = $conexion->prepare("SELECT factura FROM configuracion WHERE bss_id = ? ORDER BY id DESC LIMIT 1");
if ($stmtConfig) {
    $stmtConfig->bind_param("i", $bss_id);
    $stmtConfig->execute();
    $configFila = $stmtConfig->get_result()->fetch_assoc();
    $stmtConfig->close();
    $verCompras = $configFila && (int) $configFila['factura'] === 1;
}

if ($verCompras) {
    $tablaCompras = $conexion->query("SHOW TABLES LIKE 'compras_facturas'");
    $verCompras = $tablaCompras && $tablaCompras->num_rows > 0;
    if ($tablaCompras) {
        $tablaCompras->free();
    }
}

$origenes = ['concepto'];
if ($verCompras) {
    $origenes[] = 'compra';
}

$permisosSesion = $_SESSION['permisos'] ?? [];
$puedeCompras = $nivel === 1
    || in_array('nuevaCompra.php', $permisosSesion, true)
    || in_array('compras_facturas.php', $permisosSesion, true);

$topnav = topnav();
$sucursalSesion = (int) ($_SESSION['sucursal'] ?? 0);

$stmt = $conexion->prepare(
    $nivel === 2
        ? "SELECT id, nombre FROM sucursales WHERE bss_id = ? AND id = ? ORDER BY principal DESC"
        : "SELECT id, nombre FROM sucursales WHERE bss_id = ? ORDER BY principal DESC"
);
if ($nivel === 2) {
    $stmt->bind_param("ii", $bss_id, $sucursalSesion);
} else {
    $stmt->bind_param("i", $bss_id);
}
$stmt->execute();
$sucursales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$nombresSucursal = [];
foreach ($sucursales as $sucursal) {
    $nombresSucursal[(int) $sucursal['id']] = $sucursal['nombre'];
}

// Facturas propias (conceptos) + facturas de compras con factura.
$facturas = [];
foreach ($origenes as $origen) {
    $filas = cp_listar_facturas($conexion, $bss_id, $nivel === 2 ? $sucursalSesion : 0, $origen);
    foreach ($filas as $fila) {
        $fila['origen'] = $origen;
        $facturas[] = $fila;
    }
}

$stats = [
    'pendientes' => 0,
    'pendiente_saldo' => 0.0,
    'vencidas' => 0,
    'vencida_saldo' => 0.0,
    'pagadas' => 0,
    'pagada_total' => 0.0,
    'total' => 0.0,
    'total_facturas' => 0,
];

foreach ($origenes as $origen) {
    $fila = cp_estadisticas($conexion, $bss_id, $nivel === 2 ? $sucursalSesion : 0, $origen);
    $stats['pendientes'] += (int) ($fila['pendientes'] ?? 0);
    $stats['pendiente_saldo'] += (float) ($fila['pendiente_saldo'] ?? 0);
    $stats['vencidas'] += (int) ($fila['vencidas'] ?? 0);
    $stats['vencida_saldo'] += (float) ($fila['vencida_saldo'] ?? 0);
    $stats['pagadas'] += (int) ($fila['pagadas'] ?? 0);
    $stats['pagada_total'] += (float) ($fila['pagada_total'] ?? 0);
    $stats['total'] += (float) ($fila['total_comprado'] ?? 0);
    $stats['total_facturas'] += (int) ($fila['total_facturas'] ?? 0);
}

usort($facturas, function ($a, $b) {
    $pa = $a['estado'] === 'pendiente' ? 0 : 1;
    $pb = $b['estado'] === 'pendiente' ? 0 : 1;
    if ($pa !== $pb) {
        return $pa <=> $pb;
    }

    $va = !empty($a['fecha_vencimiento']);
    $vb = !empty($b['fecha_vencimiento']);
    if ($va !== $vb) {
        return $va ? -1 : 1;
    }
    if ($va && $a['fecha_vencimiento'] !== $b['fecha_vencimiento']) {
        return strcmp($a['fecha_vencimiento'], $b['fecha_vencimiento']);
    }

    return (int) $b['id'] <=> (int) $a['id'];
});

$hoy = date('Y-m-d');
foreach ($facturas as &$factura) {
    $total = (float) $factura['total'];
    $abonado = (float) $factura['abonado'];
    $saldo = round($total - $abonado, 2);

    $factura['saldo_calc'] = max(0, $saldo);
    $factura['vencida_calc'] = false;
    $factura['dias_vencimiento'] = null;
    $factura['vence_texto'] = '';

    if ($factura['estado'] === 'pendiente' && $saldo > 0.005) {
        if (!empty($factura['fecha_vencimiento'])) {
            $dias = (int) floor((strtotime($factura['fecha_vencimiento']) - strtotime($hoy)) / 86400);
            $factura['dias_vencimiento'] = $dias;
            $factura['vencida_calc'] = $dias < 0;

            if ($dias < 0) {
                $factura['vence_texto'] = 'Vencida hace ' . abs($dias) . ' día' . (abs($dias) === 1 ? '' : 's');
            } elseif ($dias === 0) {
                $factura['vence_texto'] = 'Vence hoy';
            } else {
                $factura['vence_texto'] = 'Vence en ' . $dias . ' día' . ($dias === 1 ? '' : 's');
            }
        } else {
            $factura['vence_texto'] = 'Sin vencimiento';
        }
    }

    $factura['sucursal_nombre'] = $nombresSucursal[(int) $factura['id_sucursal']] ?? '';
}
unset($factura);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Cuentas por pagar | iSeller POS</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="css/cuentas_pagar.css?v=<?= @filemtime(__DIR__ . '/css/cuentas_pagar.css') ?>">
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class="right_col" role="main">
                <div class="fc-hero">
                    <div>
                        <h2>Cuentas por pagar</h2>
                        <p>Registra facturas por conceptos sin afectar el inventario y consulta las compras con factura.</p>
                    </div>
                    <div class="fc-hero-actions">
                        <?php if ($verCompras && $puedeCompras): ?>
                            <a href="compras_facturas.php" class="fc-ghost-btn">
                                <i class="bi bi-receipt"></i> Compras con factura
                            </a>
                        <?php endif; ?>
                        <button type="button" class="fc-primary-btn" data-toggle="modal" data-target="#modalNuevaFactura">
                            <i class="bi bi-plus-lg"></i> Nueva factura
                        </button>
                    </div>
                </div>

                <div class="fc-stats">
                    <div class="fc-stat pending">
                        <div class="fc-stat-label"><i class="bi bi-clock-history"></i> Por pagar</div>
                        <div class="fc-stat-value">$<?= number_format($stats['pendiente_saldo'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['pendientes'] ?> factura<?= $stats['pendientes'] === 1 ? '' : 's' ?> pendiente<?= $stats['pendientes'] === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="fc-stat overdue">
                        <div class="fc-stat-label"><i class="bi bi-exclamation-triangle"></i> Vencidas</div>
                        <div class="fc-stat-value">$<?= number_format($stats['vencida_saldo'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['vencidas'] ?> factura<?= $stats['vencidas'] === 1 ? '' : 's' ?> vencida<?= $stats['vencidas'] === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="fc-stat paid">
                        <div class="fc-stat-label"><i class="bi bi-check2-circle"></i> Pagadas</div>
                        <div class="fc-stat-value">$<?= number_format($stats['pagada_total'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['pagadas'] ?> factura<?= $stats['pagadas'] === 1 ? '' : 's' ?> pagada<?= $stats['pagadas'] === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="fc-stat total">
                        <div class="fc-stat-label"><i class="bi bi-journal-text"></i> Total registrado</div>
                        <div class="fc-stat-value">$<?= number_format($stats['total'], 2, '.', ',') ?></div>
                        <div class="fc-stat-meta"><?= (int) $stats['total_facturas'] ?> factura<?= $stats['total_facturas'] === 1 ? '' : 's' ?> registrada<?= $stats['total_facturas'] === 1 ? '' : 's' ?></div>
                    </div>
                </div>

                <div class="row">
                    <div class="modal fade" id="modalNuevaFactura" tabindex="-1" role="dialog" aria-labelledby="modalNuevaFacturaLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
                            <div class="modal-content">
                                <div class="modal-header fc-modal-head">
                                    <div class="fc-card-title">
                                        <span class="fc-title-icon"><i class="bi bi-journal-plus"></i></span>
                                        <div>
                                            <h3 id="modalNuevaFacturaLabel">Nueva factura por pagar</h3>
                                            <p>Registra la obligación y explica sus conceptos sin afectar el inventario</p>
                                        </div>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="cp-form" class="fc-form" autocomplete="off" novalidate>
                                    <div class="modal-body">
                                        <div class="fc-wizard">
                                            <div class="fc-wizard-step active" data-step="1">
                                                <span>1</span>
                                                <div><strong>Factura</strong><small>Proveedor y datos</small></div>
                                            </div>
                                            <div class="fc-wizard-line"></div>
                                            <div class="fc-wizard-step" data-step="2">
                                                <span>2</span>
                                                <div><strong>Conceptos</strong><small>Detalle a pagar</small></div>
                                            </div>
                                        </div>

                                        <div class="fc-step active" data-step="1">
                                            <div class="row">
                                                <div class="col-sm-6 fc-field">
                                                    <label for="cp-proveedor">Proveedor</label>
                                                    <input type="text" id="cp-proveedor" class="form-control" maxlength="255" required placeholder="Nombre del proveedor">
                                                </div>
                                                <div class="col-sm-6 fc-field">
                                                    <label for="cp-numero">N° de factura</label>
                                                    <input type="text" id="cp-numero" class="form-control" maxlength="100" required placeholder="Ej. F-000123">
                                                </div>
                                                <div class="col-sm-6 fc-field">
                                                    <label for="cp-fecha">Fecha de factura</label>
                                                    <input type="date" id="cp-fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                </div>
                                                <div class="col-sm-6 fc-field">
                                                    <label for="cp-sucursal">Sucursal</label>
                                                    <select id="cp-sucursal" class="form-control" required>
                                                        <?php if (count($sucursales) !== 1): ?>
                                                            <option value="">-- Seleccione --</option>
                                                        <?php endif; ?>
                                                        <?php foreach ($sucursales as $sucursalItem): ?>
                                                            <option value="<?= (int) $sucursalItem['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($sucursalItem['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-sm-6 fc-field">
                                                    <label for="cp-vencimiento">Vencimiento del crédito</label>
                                                    <input type="date" id="cp-vencimiento" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                                                    <small class="fc-help">Fecha límite para pagar el saldo pendiente.</small>
                                                </div>
                                                <div class="col-sm-6 fc-field">
                                                    <label for="cp-abono">Abono inicial ($)</label>
                                                    <input type="number" id="cp-abono" class="form-control" min="0" step="0.01" value="0">
                                                    <small class="fc-help">Déjalo en 0 para registrar la factura como pendiente.</small>
                                                </div>
                                            </div>
                                        </div><!-- /step 1 -->

                                        <div class="fc-step" data-step="2">
                                            <div class="fc-section-title"><span>2</span> Conceptos de la factura</div>
                                            <button type="button" id="cp-agregar-concepto" class="fc-add-btn" style="margin-bottom:12px;">
                                                <i class="bi bi-plus-lg"></i> Agregar concepto
                                            </button>
                                            <div class="cp-conceptos-wrap">
                                                <table class="table fc-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Concepto</th>
                                                            <th class="text-right">Monto</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="cp-conceptos-body"></tbody>
                                                </table>
                                            </div>

                                            <div class="fc-pay-summary" style="margin-top:14px;">
                                                <div>
                                                    <span class="fc-summary-label">Total factura</span>
                                                    <div class="fc-total">$<span id="cp-total">0.00</span></div>
                                                </div>
                                                <div>
                                                    <span class="fc-summary-label">Queda pendiente</span>
                                                    <div class="fc-pending" id="cp-saldo">$0.00</div>
                                                </div>
                                            </div>
                                        </div><!-- /step 2 -->
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="fc-ghost-btn" data-dismiss="modal">Cancelar</button>
                                        <div class="fc-wizard-nav">
                                            <button type="button" id="cp-atras" class="fc-ghost-btn d-none">
                                                <i class="bi bi-arrow-left"></i> Atrás
                                            </button>
                                            <button type="button" id="cp-siguiente" class="fc-primary-btn">
                                                Siguiente <i class="bi bi-arrow-right"></i>
                                            </button>
                                            <button type="submit" id="cp-guardar" class="fc-save-btn d-none">
                                                <i class="bi bi-check2-circle"></i> Guardar factura
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><!-- /modal nueva factura -->

                    <div class="modal fade" id="modalAbono" tabindex="-1" role="dialog" aria-labelledby="modalAbonoLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header fc-modal-head">
                                    <div class="fc-card-title">
                                        <span class="fc-title-icon"><i class="bi bi-cash-coin"></i></span>
                                        <div>
                                            <h3 id="modalAbonoLabel">Registrar abono</h3>
                                            <p id="abono-factura-sub">Abono sobre la factura seleccionada</p>
                                        </div>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="abono-form" class="fc-form" autocomplete="off">
                                    <div class="modal-body">
                                        <input type="hidden" id="abono_origen" value="">
                                        <input type="hidden" id="abono_factura_id" value="">
                                        <div class="fc-field">
                                            <label for="abono_monto">Monto del abono ($)</label>
                                            <input type="number" id="abono_monto" class="form-control" min="0.01" step="0.01" required>
                                            <small class="fc-help" id="abono-saldo-help"></small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="fc-ghost-btn" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" id="abono-guardar" class="fc-save-btn">
                                            <i class="bi bi-check2-circle"></i> Guardar abono
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><!-- /modal abono -->

                    <div class="modal fade" id="modalVencimiento" tabindex="-1" role="dialog" aria-labelledby="modalVencimientoLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header fc-modal-head">
                                    <div class="fc-card-title">
                                        <span class="fc-title-icon"><i class="bi bi-calendar-event"></i></span>
                                        <div>
                                            <h3 id="modalVencimientoLabel">Vencimiento del crédito</h3>
                                            <p id="vencimiento-factura-sub">Fecha límite para pagar el saldo pendiente</p>
                                        </div>
                                    </div>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="vencimiento-form" class="fc-form" autocomplete="off">
                                    <div class="modal-body">
                                        <input type="hidden" id="vencimiento_origen" value="">
                                        <input type="hidden" id="vencimiento_factura_id" value="">
                                        <div class="fc-field">
                                            <label for="vencimiento_fecha">Nueva fecha límite</label>
                                            <input type="date" id="vencimiento_fecha" class="form-control" required>
                                            <small class="fc-help">No puede ser anterior a la fecha de la factura.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="fc-ghost-btn" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" id="vencimiento-guardar" class="fc-save-btn">
                                            <i class="bi bi-check2-circle"></i> Guardar fecha
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div><!-- /modal vencimiento -->

                    <div class="col-12">
                        <div class="fc-card">
                            <div class="fc-card-head">
                                <div class="fc-card-title">
                                    <span class="fc-title-icon"><i class="bi bi-wallet2"></i></span>
                                    <div>
                                        <h3>Facturas por pagar</h3>
                                        <p><?= (int) $stats['total_facturas'] ?> factura<?= $stats['total_facturas'] === 1 ? '' : 's' ?> registrada<?= $stats['total_facturas'] === 1 ? '' : 's' ?><?= $verCompras ? ' entre conceptos y compras' : ' por conceptos' ?></p>
                                    </div>
                                </div>
                                <div class="fc-list-tools">
                                    <div class="fc-search">
                                        <i class="bi bi-search"></i>
                                        <input type="text" id="buscar-factura" placeholder="Buscar factura o proveedor">
                                    </div>
                                    <?php if ($verCompras): ?>
                                        <select id="filtro-origen" class="fc-select" aria-label="Origen de la factura">
                                            <option value="">Todos los orígenes</option>
                                            <option value="concepto">Solo conceptos</option>
                                            <option value="compra">Solo compras</option>
                                        </select>
                                    <?php else: ?>
                                        <input type="hidden" id="filtro-origen" value="">
                                    <?php endif; ?>
                                    <div class="fc-chips">
                                        <button type="button" class="fc-chip active" data-filtro="todas">Todas</button>
                                        <button type="button" class="fc-chip" data-filtro="pendientes">Pendientes</button>
                                        <button type="button" class="fc-chip" data-filtro="vencidas">Vencidas</button>
                                        <button type="button" class="fc-chip" data-filtro="pagadas">Pagadas</button>
                                    </div>
                                </div>
                            </div>
                            <div class="fc-card-body p-0">
                                <div class="fc-list-wrap">
                                    <table class="table fc-table fc-list">
                                        <thead>
                                            <tr>
                                                <th>Factura</th>
                                                <th>Proveedor</th>
                                                <th>Origen</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-right">Saldo</th>
                                                <th>Vencimiento</th>
                                                <th>Estado</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="fc-facturas-body">
                                            <?php if (count($facturas) === 0): ?>
                                                <tr>
                                                    <td colspan="8" class="fc-vacio">
                                                        <div>Aún no hay facturas registradas.</div>
                                                        <button type="button" class="fc-primary-btn" style="margin-top:12px;" data-toggle="modal" data-target="#modalNuevaFactura">
                                                            <i class="bi bi-plus-lg"></i> Registrar primera factura
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            <?php foreach ($facturas as $factura):
                                                $origen = $factura['origen'];
                                                $id = (int) $factura['id'];
                                                $total = (float) $factura['total'];
                                                $saldo = (float) $factura['saldo_calc'];
                                                $esCompra = $origen === 'compra';
                                                $esPagada = $factura['estado'] === 'pagada';
                                                $esVencida = $factura['vencida_calc'];
                                                $badgeClase = $esPagada ? 'pagada' : ($esVencida ? 'vencida' : 'pendiente');
                                                $badgeTexto = $esPagada ? 'Pagada' : ($esVencida ? 'Vencida' : 'Pendiente');
                                                $origenTexto = $esCompra ? 'Compra' : 'Concepto';
                                                $sucursalNombre = $factura['sucursal_nombre'];
                                                $busqueda = mb_strtolower($factura['numero_factura'] . ' ' . $factura['proveedor'] . ' ' . $sucursalNombre . ' ' . $origenTexto, 'UTF-8');
                                                $fechaVencimiento = !empty($factura['fecha_vencimiento']) ? date('d/m/Y', strtotime($factura['fecha_vencimiento'])) : '';
                                                $fechaFactura = date('d/m/Y', strtotime($factura['fecha_factura']));
                                            ?>
                                                <tr data-factura="<?= $origen . '-' . $id ?>"
                                                    data-origen="<?= $origen ?>"
                                                    data-estado="<?= $esPagada ? 'pagada' : 'pendiente' ?>"
                                                    data-vencida="<?= $esVencida ? '1' : '0' ?>"
                                                    data-search="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
                                                    <td>
                                                        <div class="fc-cell-main"><?= htmlspecialchars($factura['numero_factura']) ?></div>
                                                        <div class="fc-cell-sub"><?= $fechaFactura ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fc-cell-main"><?= htmlspecialchars($factura['proveedor']) ?></div>
                                                        <div class="fc-cell-sub"><?= htmlspecialchars($sucursalNombre) ?></div>
                                                    </td>
                                                    <td><span class="cp-origen <?= $origen ?>"><?= $origenTexto ?></span></td>
                                                    <td class="text-right fc-amount">$<?= number_format($total, 2, '.', ',') ?></td>
                                                    <td class="text-right fc-amount <?= $saldo > 0.005 ? 'due' : '' ?>">$<?= number_format($saldo, 2, '.', ',') ?></td>
                                                    <td>
                                                        <?php if ($esPagada): ?>
                                                            <span class="fc-due muted"><i class="bi bi-check2"></i> Sin crédito</span>
                                                        <?php elseif ($esVencida): ?>
                                                            <span class="fc-due late"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($factura['vence_texto']) ?></span>
                                                            <div class="fc-cell-sub"><?= $fechaVencimiento ?></div>
                                                        <?php elseif ($fechaVencimiento !== ''): ?>
                                                            <span class="fc-due <?= $factura['dias_vencimiento'] !== null && $factura['dias_vencimiento'] <= 5 ? 'soon' : 'ok' ?>"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($factura['vence_texto']) ?></span>
                                                            <div class="fc-cell-sub"><?= $fechaVencimiento ?></div>
                                                        <?php else: ?>
                                                            <span class="fc-due muted"><i class="bi bi-calendar-x"></i> Sin vencimiento</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="fc-badge <?= $badgeClase ?>"><?= $badgeTexto ?></span></td>
                                                    <td class="text-right fc-actions">
                                                        <button type="button" class="fc-icon-btn info" title="Ver detalle"
                                                            data-cp-detalle="1" data-origen="<?= $origen ?>" data-id="<?= $id ?>">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <?php if ($saldo > 0.005): ?>
                                                            <button type="button" class="fc-icon-btn success" title="Registrar abono"
                                                                data-cp-abonar="1" data-origen="<?= $origen ?>" data-id="<?= $id ?>"
                                                                data-saldo="<?= number_format($saldo, 2, '.', '') ?>"
                                                                data-numero="<?= htmlspecialchars((string) $factura['numero_factura'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="bi bi-cash-coin"></i>
                                                            </button>
                                                            <button type="button" class="fc-icon-btn warn" title="Cambiar vencimiento"
                                                                data-cp-vencimiento="1" data-origen="<?= $origen ?>" data-id="<?= $id ?>"
                                                                data-fecha="<?= htmlspecialchars((string) ($factura['fecha_vencimiento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                data-factura="<?= htmlspecialchars((string) $factura['fecha_factura'], ENT_QUOTES, 'UTF-8') ?>"
                                                                data-numero="<?= htmlspecialchars((string) $factura['numero_factura'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="bi bi-calendar-event"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="fc-empty-filter" class="fc-empty" style="display:none;">No hay facturas que coincidan con el filtro.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="js/nombre_pagina.js"></script>

    <script>
        (function() {
            const urlCp = '../../configurar/cuentas_pagar_acciones.php';
            const urlCompras = '../../configurar/factura_compra_acciones.php';
            const $ = (id) => document.getElementById(id);

            const buscarFactura = $('buscar-factura');
            const emptyFilter = $('fc-empty-filter');
            const filtroOrigen = $('filtro-origen');
            const facturasBody = $('fc-facturas-body');
            let filtroFacturas = 'todas';
            let cpPasoActual = 1;

            function dinero(valor) {
                return Number(valor || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function redondearMonto(valor) {
                const numero = Number(valor);
                if (!isFinite(numero)) return NaN;
                return Math.round((numero + Number.EPSILON) * 100) / 100;
            }

            function fechaCorta(fechaISO) {
                if (!fechaISO) return '';
                const partes = String(fechaISO).split('-');
                return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : fechaISO;
            }

            function sumarDias(fechaIso, dias) {
                if (!fechaIso) return '';
                const partes = fechaIso.split('-').map(Number);
                if (partes.length !== 3 || partes.some(Number.isNaN)) return '';
                const fecha = new Date(partes[0], partes[1] - 1, partes[2]);
                fecha.setDate(fecha.getDate() + dias);
                return fecha.getFullYear() + '-' +
                    String(fecha.getMonth() + 1).padStart(2, '0') + '-' +
                    String(fecha.getDate()).padStart(2, '0');
            }

            function endpoint(origen) {
                return origen === 'compra' ? urlCompras : urlCp;
            }

            async function enviarJson(url, payload) {
                const respuesta = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                return respuesta.json();
            }

            // ── Conceptos dinámicos ─────────────────────────────────────────
            const conceptosBody = $('cp-conceptos-body');
            const totalCp = $('cp-total');
            const saldoCp = $('cp-saldo');
            const abonoCp = $('cp-abono');

            function agregarFilaConcepto(descripcion, monto) {
                const tr = document.createElement('tr');

                const tdDesc = document.createElement('td');
                const inputDesc = document.createElement('input');
                inputDesc.type = 'text';
                inputDesc.className = 'form-control cp-concepto-desc';
                inputDesc.maxLength = 255;
                inputDesc.placeholder = 'Ej. Servicio de flete';
                inputDesc.value = descripcion || '';
                tdDesc.appendChild(inputDesc);

                const tdMonto = document.createElement('td');
                const inputMonto = document.createElement('input');
                inputMonto.type = 'number';
                inputMonto.className = 'form-control cp-concepto-monto';
                inputMonto.min = '0.01';
                inputMonto.step = '0.01';
                inputMonto.placeholder = '0.00';
                inputMonto.value = monto || '';
                tdMonto.appendChild(inputMonto);

                const tdQuitar = document.createElement('td');
                tdQuitar.className = 'text-right';
                const quitar = document.createElement('button');
                quitar.type = 'button';
                quitar.className = 'fc-icon-btn danger';
                quitar.title = 'Quitar concepto';
                quitar.innerHTML = '<i class="bi bi-trash3"></i>';
                quitar.addEventListener('click', function() {
                    tr.remove();
                    if (!conceptosBody.querySelector('tr')) {
                        agregarFilaConcepto();
                    }
                    actualizarTotalesCp();
                });
                tdQuitar.appendChild(quitar);

                tr.appendChild(tdDesc);
                tr.appendChild(tdMonto);
                tr.appendChild(tdQuitar);
                conceptosBody.appendChild(tr);
                actualizarTotalesCp();

                return tr;
            }

            function leerConceptos() {
                return Array.from(conceptosBody.querySelectorAll('tr')).map((tr) => ({
                    concepto: tr.querySelector('.cp-concepto-desc').value.trim(),
                    monto: tr.querySelector('.cp-concepto-monto').value
                }));
            }

            function actualizarTotalesCp() {
                const total = Math.round(leerConceptos().reduce((acumulado, fila) => {
                    const monto = redondearMonto(fila.monto || 0);
                    return acumulado + (isFinite(monto) && monto > 0 ? monto : 0);
                }, 0) * 100) / 100;

                const abono = parseFloat(abonoCp.value || 0) || 0;
                const saldo = Math.max(0, total - abono);

                totalCp.textContent = dinero(total);
                saldoCp.textContent = '$' + dinero(saldo);
                saldoCp.classList.toggle('zero', saldo <= 0.005);
            }

            function mostrarPasoCp(paso) {
                cpPasoActual = paso;

                document.querySelectorAll('#modalNuevaFactura .fc-step').forEach((panel) => {
                    panel.classList.toggle('active', Number(panel.dataset.step) === paso);
                });
                document.querySelectorAll('#modalNuevaFactura .fc-wizard-step').forEach((step) => {
                    const numero = Number(step.dataset.step);
                    step.classList.toggle('active', numero === paso);
                    step.classList.toggle('done', numero < paso);
                });

                $('cp-atras').classList.toggle('d-none', paso === 1);
                $('cp-siguiente').classList.toggle('d-none', paso === 2);
                $('cp-guardar').classList.toggle('d-none', paso !== 2);

                const cuerpoModal = document.querySelector('#modalNuevaFactura .modal-body');
                if (cuerpoModal) {
                    cuerpoModal.scrollTop = 0;
                }

                if (paso === 2) {
                    actualizarTotalesCp();
                    setTimeout(() => {
                        const primeraDesc = conceptosBody.querySelector('.cp-concepto-desc');
                        if (primeraDesc) primeraDesc.focus();
                    }, 120);
                }
            }

            function validarPaso1Cp() {
                const proveedor = $('cp-proveedor').value.trim();
                const numero = $('cp-numero').value.trim();
                const fecha = $('cp-fecha').value;
                const sucursal = $('cp-sucursal').value;
                const vencimiento = $('cp-vencimiento').value;

                if (!proveedor) {
                    Alerta.toast('error', 'Indique el proveedor de la factura');
                    $('cp-proveedor').focus();
                    return false;
                }
                if (!numero) {
                    Alerta.toast('error', 'Indique el número de factura');
                    $('cp-numero').focus();
                    return false;
                }
                if (!fecha) {
                    Alerta.toast('error', 'Indique la fecha de la factura');
                    $('cp-fecha').focus();
                    return false;
                }
                if (!sucursal) {
                    Alerta.toast('error', 'Seleccione la sucursal');
                    $('cp-sucursal').focus();
                    return false;
                }
                if (vencimiento && vencimiento < fecha) {
                    Alerta.toast('error', 'El vencimiento no puede ser anterior a la fecha de la factura');
                    $('cp-vencimiento').focus();
                    return false;
                }

                return true;
            }

            conceptosBody.addEventListener('input', actualizarTotalesCp);
            abonoCp.addEventListener('input', actualizarTotalesCp);

            $('cp-fecha').addEventListener('change', function() {
                const vencimiento = $('cp-vencimiento');
                vencimiento.min = this.value;
                if (vencimiento.value && vencimiento.value < this.value) {
                    vencimiento.value = this.value;
                }
            });

            $('cp-agregar-concepto').addEventListener('click', function() {
                const fila = agregarFilaConcepto();
                fila.querySelector('.cp-concepto-desc').focus();
            });

            $('cp-siguiente').addEventListener('click', function() {
                if (validarPaso1Cp()) {
                    mostrarPasoCp(2);
                }
            });

            $('cp-atras').addEventListener('click', function() {
                if (cpPasoActual > 1) {
                    mostrarPasoCp(1);
                }
            });

            document.querySelectorAll('#modalNuevaFactura .fc-wizard-step').forEach((step) => {
                step.addEventListener('click', function() {
                    const destino = Number(this.dataset.step);
                    if (destino < cpPasoActual) {
                        mostrarPasoCp(destino);
                    } else if (destino > cpPasoActual && validarPaso1Cp()) {
                        mostrarPasoCp(destino);
                    }
                });
            });

            function limpiarFormularioCuenta() {
                $('cp-form').reset();
                $('cp-vencimiento').min = $('cp-fecha').value;
                conceptosBody.innerHTML = '';
                agregarFilaConcepto();
                actualizarTotalesCp();
                mostrarPasoCp(1);
            }

            window.jQuery('#modalNuevaFactura').on('shown.bs.modal', function() {
                limpiarFormularioCuenta();
                setTimeout(() => $('cp-proveedor').focus(), 120);
            });

            $('cp-form').addEventListener('submit', async function(evento) {
                evento.preventDefault();

                if (cpPasoActual === 1) {
                    if (validarPaso1Cp()) {
                        mostrarPasoCp(2);
                    }
                    return;
                }

                if (!validarPaso1Cp()) {
                    mostrarPasoCp(1);
                    return;
                }

                const proveedor = $('cp-proveedor').value.trim();
                const numero = $('cp-numero').value.trim();
                const fecha = $('cp-fecha').value;
                const vencimiento = $('cp-vencimiento').value;
                const sucursal = $('cp-sucursal').value;
                const abono = parseFloat($('cp-abono').value || 0);

                if (!proveedor) {
                    Alerta.toast('error', 'Indique el proveedor de la factura');
                    $('cp-proveedor').focus();
                    return;
                }
                if (!numero) {
                    Alerta.toast('error', 'Indique el número de factura');
                    $('cp-numero').focus();
                    return;
                }
                if (!fecha) {
                    Alerta.toast('error', 'Indique la fecha de la factura');
                    $('cp-fecha').focus();
                    return;
                }
                if (!sucursal) {
                    Alerta.toast('error', 'Seleccione la sucursal');
                    $('cp-sucursal').focus();
                    return;
                }

                const conceptos = leerConceptos().filter((fila) => fila.concepto !== '' || fila.monto !== '');
                if (conceptos.length === 0) {
                    Alerta.toast('error', 'Agregue al menos un concepto a la factura');
                    return;
                }

                let total = 0;
                for (const fila of conceptos) {
                    const monto = redondearMonto(fila.monto);
                    if (!fila.concepto) {
                        Alerta.toast('error', 'Cada concepto debe tener una descripción');
                        return;
                    }
                    if (!isFinite(monto) || monto <= 0) {
                        Alerta.toast('error', 'El monto de cada concepto debe ser mayor que cero');
                        return;
                    }
                    total += monto;
                }
                total = Math.round(total * 100) / 100;

                if (!isFinite(abono) || abono < 0) {
                    Alerta.toast('error', 'El abono inicial no es válido');
                    return;
                }
                if (abono > total + 0.001) {
                    Alerta.toast('error', 'El abono inicial no puede superar el total');
                    return;
                }
                if (total - abono > 0.005 && !vencimiento) {
                    Alerta.toast('error', 'Indique la fecha de vencimiento del crédito');
                    $('cp-vencimiento').focus();
                    return;
                }
                if (total - abono > 0.005 && vencimiento < fecha) {
                    Alerta.toast('error', 'El vencimiento no puede ser anterior a la fecha de la factura');
                    $('cp-vencimiento').focus();
                    return;
                }

                const guardar = $('cp-guardar');
                guardar.disabled = true;

                try {
                    const data = await enviarJson(urlCp, {
                        accion: 'guardar',
                        proveedor: proveedor,
                        numero_factura: numero,
                        fecha_factura: fecha,
                        fecha_vencimiento: total - abono > 0.005 ? vencimiento : '',
                        sucursal: sucursal,
                        abono_inicial: abono,
                        conceptos: conceptos
                    });

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo guardar la factura');
                    }

                    window.jQuery('#modalNuevaFactura').modal('hide');
                    Alerta.toast('success', 'Factura registrada correctamente');
                    setTimeout(() => window.location.reload(), 800);
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                } finally {
                    guardar.disabled = false;
                }
            });

            // ── Filtros del listado ────────────────────────────────────────
            function aplicarFiltrosFacturas() {
                const texto = (buscarFactura.value || '').toLowerCase().trim();
                const origenFiltro = filtroOrigen.value;
                let visibles = 0;

                const filas = document.querySelectorAll('#fc-facturas-body tr[data-factura]');

                filas.forEach((tr) => {
                    const estado = tr.dataset.estado;
                    const vencida = tr.dataset.vencida === '1';
                    const origen = tr.dataset.origen;
                    const coincideFiltro =
                        filtroFacturas === 'todas' ||
                        (filtroFacturas === 'pendientes' && estado === 'pendiente') ||
                        (filtroFacturas === 'vencidas' && vencida) ||
                        (filtroFacturas === 'pagadas' && estado === 'pagada');
                    const coincideOrigen = !origenFiltro || origenFiltro === origen;
                    const coincideBusqueda = texto === '' || (tr.dataset.search || '').includes(texto);
                    const mostrar = coincideFiltro && coincideOrigen && coincideBusqueda;

                    tr.style.display = mostrar ? '' : 'none';
                    if (mostrar) visibles++;
                });

                emptyFilter.style.display = filas.length > 0 && visibles === 0 ? 'block' : 'none';
            }

            document.querySelectorAll('.fc-chip').forEach((boton) => {
                boton.addEventListener('click', function() {
                    document.querySelectorAll('.fc-chip').forEach((b) => b.classList.remove('active'));
                    this.classList.add('active');
                    filtroFacturas = this.dataset.filtro;
                    aplicarFiltrosFacturas();
                });
            });

            buscarFactura.addEventListener('input', aplicarFiltrosFacturas);
            filtroOrigen.addEventListener('change', aplicarFiltrosFacturas);

            // ── Detalle ────────────────────────────────────────────────────
            async function verCuenta(origen, id) {
                try {
                    const respuesta = await fetch(endpoint(origen) + '?accion=detalle&id=' + encodeURIComponent(id));
                    const data = await respuesta.json();

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo cargar la factura');
                    }

                    const esc = (valor) => {
                        const div = document.createElement('div');
                        div.textContent = valor == null ? '' : String(valor);
                        return div.innerHTML;
                    };

                    const esCompra = origen === 'compra';
                    const vence = data.factura.fecha_vencimiento ? fechaCorta(data.factura.fecha_vencimiento) : 'Sin definir';
                    const estadoTxt = data.factura.estado === 'pagada' ? 'Pagada' : (data.saldo <= 0.005 ? 'Pagada' : 'Pendiente');

                    let html = '<div style="text-align:left;font-size:13px;">';
                    html += '<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 12px;margin-bottom:12px;">';
                    html += '<div><strong>Proveedor:</strong><br>' + esc(data.factura.proveedor) + '</div>';
                    html += '<div><strong>N° factura:</strong><br>' + esc(data.factura.numero_factura) + '</div>';
                    html += '<div><strong>Fecha:</strong><br>' + esc(fechaCorta(data.factura.fecha_factura)) + '</div>';
                    html += '<div><strong>Vencimiento:</strong><br>' + esc(vence) + '</div>';
                    html += '<div><strong>Origen:</strong><br>' + (esCompra ? 'Compra con factura' : 'Factura por conceptos') + '</div>';
                    html += '<div><strong>Estado:</strong><br>' + esc(estadoTxt) + '</div>';
                    html += '</div>';

                    html += '<table style="width:100%;border-collapse:collapse;margin-bottom:12px;">';
                    if (esCompra) {
                        html += '<thead><tr><th style="text-align:left;border-bottom:1px solid #ccc;padding:5px;">Producto</th><th style="text-align:right;border-bottom:1px solid #ccc;padding:5px;">Cant.</th><th style="text-align:right;border-bottom:1px solid #ccc;padding:5px;">Subtotal</th></tr></thead><tbody>';
                        (data.lineas || []).forEach((linea) => {
                            html += '<tr><td style="padding:5px;border-bottom:1px solid #eee;">' + esc(linea.producto) + '</td><td style="text-align:right;padding:5px;border-bottom:1px solid #eee;">' + esc(linea.cantidad) + '</td><td style="text-align:right;padding:5px;border-bottom:1px solid #eee;">$' + dinero(linea.subtotal) + '</td></tr>';
                        });
                    } else {
                        html += '<thead><tr><th style="text-align:left;border-bottom:1px solid #ccc;padding:5px;">Concepto</th><th style="text-align:right;border-bottom:1px solid #ccc;padding:5px;">Monto</th></tr></thead><tbody>';
                        (data.conceptos || []).forEach((concepto) => {
                            html += '<tr><td style="padding:5px;border-bottom:1px solid #eee;">' + esc(concepto.concepto) + '</td><td style="text-align:right;padding:5px;border-bottom:1px solid #eee;">$' + dinero(concepto.monto) + '</td></tr>';
                        });
                    }
                    html += '</tbody></table>';

                    if (esCompra) {
                        const ajuste = Number(data.ajuste || 0);
                        if (Math.abs(ajuste) > 0.005) {
                            html += '<p style="margin:-2px 0 12px;font-size:12px;color:#8a8f98;text-align:right;">Ajuste al detalle: <b>' +
                                (ajuste > 0 ? '+' : '−') + '$' + dinero(Math.abs(ajuste)) + '</b></p>';
                        }
                    }

                    html += '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;margin-bottom:12px;">';
                    html += '<div style="padding:8px;border-radius:8px;background:rgba(45,212,160,.08);"><small>Total final</small><br><b>$' + dinero(data.factura.total) + '</b></div>';
                    html += '<div style="padding:8px;border-radius:8px;background:rgba(91,156,245,.08);"><small>Abonado</small><br><b>$' + dinero(data.abonado) + '</b></div>';
                    html += '<div style="padding:8px;border-radius:8px;background:rgba(245,180,91,.1);"><small>Saldo</small><br><b>$' + dinero(data.saldo) + '</b></div>';
                    html += '</div>';

                    if ((data.pagos || []).length > 0) {
                        html += '<strong>Abonos registrados:</strong><ul style="margin:6px 0 0 18px;padding:0;">';
                        data.pagos.forEach((pago) => {
                            html += '<li>$' + dinero(pago.monto) + ' — ' + esc(pago.fecha) + ' (' + esc(pago.usuario) + ')</li>';
                        });
                        html += '</ul>';
                    }

                    html += '</div>';

                    Swal.fire({
                        title: 'Factura ' + esc(data.factura.numero_factura),
                        html: html,
                        width: 620,
                        confirmButtonText: 'Cerrar'
                    });
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }

            // ── Abonos ─────────────────────────────────────────────────────
            let abonoSaldoActual = 0;

            function abrirAbono(origen, id, saldo, numero) {
                abonoSaldoActual = Number(saldo) || 0;

                $('abono_origen').value = origen;
                $('abono_factura_id').value = id;
                $('abono_monto').value = abonoSaldoActual.toFixed(2);
                $('abono_monto').max = String(abonoSaldoActual);
                $('abono-factura-sub').textContent = numero
                    ? 'Factura ' + numero + ' · Saldo pendiente: $' + dinero(abonoSaldoActual)
                    : 'Saldo pendiente: $' + dinero(abonoSaldoActual);
                $('abono-saldo-help').textContent = 'Puedes abonar hasta $' + dinero(abonoSaldoActual) + '.';

                window.jQuery('#modalAbono').modal('show');
            }

            window.jQuery('#modalAbono').on('shown.bs.modal', function() {
                setTimeout(() => {
                    $('abono_monto').focus();
                    $('abono_monto').select();
                }, 120);
            });

            $('abono-form').addEventListener('submit', async function(evento) {
                evento.preventDefault();

                const origen = $('abono_origen').value;
                const facturaId = parseInt($('abono_factura_id').value, 10);
                const monto = parseFloat($('abono_monto').value || 0);

                if (!facturaId) {
                    Alerta.toast('error', 'Factura inválida');
                    return;
                }
                if (!isFinite(monto) || monto <= 0) {
                    Alerta.toast('error', 'Indique un monto de abono válido');
                    $('abono_monto').focus();
                    return;
                }
                if (monto > abonoSaldoActual + 0.001) {
                    Alerta.toast('error', 'El abono no puede superar el saldo pendiente');
                    $('abono_monto').focus();
                    return;
                }

                const guardar = $('abono-guardar');
                guardar.disabled = true;

                try {
                    const data = await enviarJson(endpoint(origen), {
                        accion: 'abonar',
                        factura_id: facturaId,
                        monto: monto
                    });

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo registrar el abono');
                    }

                    window.jQuery('#modalAbono').modal('hide');
                    Alerta.toast('success', 'Abono registrado. Saldo: $' + dinero(data.saldo));
                    setTimeout(() => window.location.reload(), 800);
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                } finally {
                    guardar.disabled = false;
                }
            });

            // ── Vencimiento ────────────────────────────────────────────────
            function abrirVencimiento(origen, id, fechaActual, fechaFactura, numero) {
                const fechaValida = /^\d{4}-\d{2}-\d{2}$/.test(fechaActual || '');

                $('vencimiento_origen').value = origen;
                $('vencimiento_factura_id').value = id;
                $('vencimiento_fecha').min = fechaFactura || '';
                $('vencimiento_fecha').value = fechaValida
                    ? fechaActual
                    : (fechaFactura ? sumarDias(fechaFactura, 30) : '');
                $('vencimiento-factura-sub').textContent = numero
                    ? 'Factura ' + numero + ' · Fecha límite para pagar el saldo pendiente.'
                    : 'Fecha límite para pagar el saldo pendiente.';

                window.jQuery('#modalVencimiento').modal('show');
            }

            window.jQuery('#modalVencimiento').on('shown.bs.modal', function() {
                setTimeout(() => $('vencimiento_fecha').focus(), 120);
            });

            $('vencimiento-form').addEventListener('submit', async function(evento) {
                evento.preventDefault();

                const origen = $('vencimiento_origen').value;
                const facturaId = parseInt($('vencimiento_factura_id').value, 10);
                const fecha = $('vencimiento_fecha').value;

                if (!facturaId) {
                    Alerta.toast('error', 'Factura inválida');
                    return;
                }
                if (!fecha) {
                    Alerta.toast('error', 'Seleccione una fecha de vencimiento');
                    $('vencimiento_fecha').focus();
                    return;
                }
                if ($('vencimiento_fecha').min && fecha < $('vencimiento_fecha').min) {
                    Alerta.toast('error', 'La fecha límite no puede ser anterior a la fecha de la factura');
                    $('vencimiento_fecha').focus();
                    return;
                }

                const guardar = $('vencimiento-guardar');
                guardar.disabled = true;

                try {
                    const data = await enviarJson(endpoint(origen), {
                        accion: 'vencimiento',
                        factura_id: facturaId,
                        fecha_vencimiento: fecha
                    });

                    if (!data.success) {
                        throw new Error(data.message || 'No se pudo actualizar el vencimiento');
                    }

                    window.jQuery('#modalVencimiento').modal('hide');
                    Alerta.toast('success', data.message || 'Vencimiento actualizado correctamente');
                    setTimeout(() => window.location.reload(), 800);
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                } finally {
                    guardar.disabled = false;
                }
            });

            // ── Acciones del listado ──────────────────────────────────────
            if (facturasBody) {
                facturasBody.addEventListener('click', function(evento) {
                    const detalle = evento.target.closest('[data-cp-detalle]');
                    if (detalle) {
                        verCuenta(detalle.dataset.origen, detalle.dataset.id);
                        return;
                    }

                    const abonar = evento.target.closest('[data-cp-abonar]');
                    if (abonar) {
                        abrirAbono(
                            abonar.dataset.origen,
                            abonar.dataset.id,
                            abonar.dataset.saldo,
                            abonar.dataset.numero
                        );
                        return;
                    }

                    const vencimiento = evento.target.closest('[data-cp-vencimiento]');
                    if (vencimiento) {
                        abrirVencimiento(
                            vencimiento.dataset.origen,
                            vencimiento.dataset.id,
                            vencimiento.dataset.fecha,
                            vencimiento.dataset.factura,
                            vencimiento.dataset.numero
                        );
                    }
                });
            }

            aplicarFiltrosFacturas();
        })();
    </script>
</body>

</html>
