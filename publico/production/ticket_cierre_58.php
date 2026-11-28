<?php
/**
 * Ticket de cierre de caja para impresora térmica de 58 mm.
 *
 * Se imprime desde cierres_caja.php cuando el usuario ya tiene un cierre.
 * Contenido: totales del sistema, entregado por el usuario, diferencias por
 * método de pago y conciliación final expresada en dólares.
 */
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    header('Location: ../../index.php');
    exit;
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$cierreId = (int) ($_GET['cierre_id'] ?? 0);
$usuarioId = (int) ($_GET['usuario_id'] ?? 0);
$sucursalId = (int) ($_GET['sucursal_id'] ?? 0);
$fecha = trim((string) ($_GET['fecha'] ?? ''));

$error = '';
$cierre = null;

if ($bssId <= 0 || ($cierreId <= 0 && ($usuarioId <= 0 || $fecha === ''))) {
    $error = 'No se indicó un cierre válido.';
} else {
    if ($cierreId > 0) {
        $stmt = $conexion->prepare(
            "SELECT c.*, u.nombre AS usuario_nombre, s.nombre AS sucursal_nombre,
                    n.nombre AS negocio_nombre
             FROM cortes_de_caja c
             LEFT JOIN usuarios u ON u.id = c.usuario_id AND u.bss_id = c.bss_id
             LEFT JOIN sucursales s ON s.id = c.sucursal_id AND s.bss_id = c.bss_id
             LEFT JOIN negocio n ON n.id = c.bss_id
             WHERE c.id = ? AND c.bss_id = ? AND c.tipo_corte = 'cierre'
             LIMIT 1"
        );
        if ($stmt) {
            $stmt->bind_param('ii', $cierreId, $bssId);
            $stmt->execute();
            $cierre = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    } else {
        $sql = "SELECT c.*, u.nombre AS usuario_nombre, s.nombre AS sucursal_nombre,
                       n.nombre AS negocio_nombre
                FROM cortes_de_caja c
                LEFT JOIN usuarios u ON u.id = c.usuario_id AND u.bss_id = c.bss_id
                LEFT JOIN sucursales s ON s.id = c.sucursal_id AND s.bss_id = c.bss_id
                LEFT JOIN negocio n ON n.id = c.bss_id
                WHERE c.bss_id = ? AND c.usuario_id = ? AND c.fecha = ?
                  AND c.tipo_corte = 'cierre'";
        $types = 'iis';
        $params = [$bssId, $usuarioId, $fecha];
        if ($sucursalId > 0) {
            $sql .= " AND c.sucursal_id = ?";
            $types .= 'i';
            $params[] = $sucursalId;
        }
        $sql .= " ORDER BY c.id DESC LIMIT 1";

        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $cierre = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    if (!$cierre) {
        $error = 'No se encontró el cierre de caja solicitado.';
    }
}

/* ── Tasas para llevar las diferencias a dólares ─────────────────────── */
$tasaBs = 0.0;
$tasaCop = 0.0;
if (!$error) {
    $stmt = $conexion->prepare("SELECT DolarBolivar, pesoDolar FROM cambio WHERE bss_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $bssId);
        $stmt->execute();
        $filaTasas = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($filaTasas) {
            $tasaBs = (float) ($filaTasas['DolarBolivar'] ?? 0);
            $tasaCop = (float) ($filaTasas['pesoDolar'] ?? 0);
        }
    }
    if ($tasaBs <= 0) {
        $resBcv = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC, id DESC LIMIT 1");
        if ($resBcv && $filaBcv = $resBcv->fetch_assoc()) {
            $tasaBs = (float) ($filaBcv['valor'] ?? 0);
        }
    }
}

$metodos = [
    'efectivo_bs' => ['label' => 'Efectivo Bs', 'moneda' => 'Bs', 'dec' => 2],
    'dolares' => ['label' => 'Efectivo USD', 'moneda' => '$', 'dec' => 2],
    'pesos' => ['label' => 'Efectivo COP', 'moneda' => 'COP', 'dec' => 0],
    'punto' => ['label' => 'Punto de venta', 'moneda' => 'Bs', 'dec' => 2],
    'pago_movil' => ['label' => 'Pago móvil', 'moneda' => 'Bs', 'dec' => 2],
    'transferencia' => ['label' => 'Transferencia', 'moneda' => 'Bs', 'dec' => 2],
    'biopago' => ['label' => 'Biopago', 'moneda' => 'Bs', 'dec' => 2],
];

$fmt = static function ($valor, int $dec = 2): string {
    return number_format((float) $valor, $dec, ',', '.');
};

$montoConMoneda = static function ($valor, string $moneda, int $dec = 2) use ($fmt): string {
    if ($moneda === '$') {
        return '$ ' . $fmt($valor, $dec);
    }
    return $fmt($valor, $dec) . ' ' . $moneda;
};

$sistema = [];
$contado = [];
$diferencia = [];
if (!$error) {
    $sistema = [
        'efectivo_bs' => (float) $cierre['efectivo_bs_sistema'],
        'dolares' => (float) $cierre['efectivo_usd_sistema'],
        'pesos' => (float) $cierre['pesos_sistema'],
        'punto' => (float) $cierre['punto_sistema'],
        'pago_movil' => (float) $cierre['pago_movil_sistema'],
        'transferencia' => (float) $cierre['transferencia_sistema'],
        'biopago' => (float) $cierre['biopago_sistema'],
    ];
    $contado = [
        'efectivo_bs' => (float) $cierre['efectivo_bs_contado'],
        'dolares' => (float) $cierre['efectivo_usd_contado'],
        'pesos' => (float) $cierre['pesos_contado'],
        'punto' => (float) $cierre['punto_contado'],
        'pago_movil' => (float) $cierre['pago_movil_contado'],
        'transferencia' => (float) $cierre['transferencia_contado'],
        'biopago' => (float) $cierre['biopago_contado'],
    ];
    foreach ($metodos as $clave => $meta) {
        $diferencia[$clave] = round($sistema[$clave] - $contado[$clave], 2);
    }
}

/* ── Conciliación final en USD ───────────────────────────────────────── */
$convertirAUsd = static function (float $monto, string $clave, float $tasaBs, float $tasaCop): float {
    if ($monto <= 0) {
        return 0.0;
    }
    if ($clave === 'dolares') {
        return $monto;
    }
    if ($clave === 'pesos') {
        return $tasaCop > 0 ? $monto / $tasaCop : 0.0;
    }
    return $tasaBs > 0 ? $monto / $tasaBs : 0.0;
};

$faltoUsd = 0.0;
$sobroUsd = 0.0;
$sinTasa = false;
if (!$error) {
    foreach ($metodos as $clave => $meta) {
        $dif = (float) $diferencia[$clave];
        if (abs($dif) < 0.005) {
            continue;
        }
        $usd = $convertirAUsd(abs($dif), $clave, $tasaBs, $tasaCop);
        if ($usd <= 0) {
            $sinTasa = true;
        }
        if ($dif > 0) {
            $faltoUsd += $usd;
        } else {
            $sobroUsd += $usd;
        }
    }
}
$faltoUsd = round($faltoUsd, 2);
$sobroUsd = round($sobroUsd, 2);
$netoUsd = round($faltoUsd - $sobroUsd, 2);

$fechaMostrar = $fecha;
$horaMostrar = '';
if ($cierre) {
    $fechaMostrar = (string) ($cierre['fecha'] ?? $fecha);
    if (!empty($cierre['creado_en'])) {
        $horaMostrar = date('H:i', strtotime((string) $cierre['creado_en']));
    }
}
$fechaMostrar = $fechaMostrar !== '' && strtotime($fechaMostrar) ? date('d/m/Y', strtotime($fechaMostrar)) : $fechaMostrar;

$usuarioNombre = trim((string) ($cierre['usuario_nombre'] ?? ''));
$sucursalNombre = trim((string) ($cierre['sucursal_nombre'] ?? ''));
$negocioNombre = trim((string) ($cierre['negocio_nombre'] ?? ''));
$adminNombre = trim((string) ($_SESSION['nombre'] ?? ''));
$rolOperador = $nivel === 1 ? 'Administrador' : 'Supervisor';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket de cierre de caja</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            background: #eef1f5;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .acciones {
            width: 320px;
            margin: 0 auto 12px;
            text-align: right;
        }

        .btn-imprimir {
            border: none;
            border-radius: 7px;
            background: #2dd4a0;
            color: #07130f;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .ticket {
            width: 320px;
            margin: 0 auto;
            padding: 16px 14px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .12);
            font-size: 11px;
        }

        .centrado {
            text-align: center;
        }

        .titulo {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .line-gruesa {
            border-top: 1px solid #000;
            margin: 7px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td {
            vertical-align: top;
            padding: 1.5px 0;
            font-size: 10px;
            word-wrap: break-word;
        }

        td.num {
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .seccion {
            margin: 8px 0 3px;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .dif-falto {
            color: #b91c1c;
            font-weight: 700;
        }

        .dif-sobro {
            color: #047857;
            font-weight: 700;
        }

        .cuadrado {
            color: #111827;
            font-weight: 700;
        }

        .resultado {
            margin-top: 5px;
            padding: 5px 6px;
            border: 1px solid #000;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
        }

        .nota {
            margin-top: 5px;
            font-size: 8.5px;
            line-height: 1.35;
        }

        .error {
            width: 320px;
            margin: 30px auto;
            padding: 18px;
            border-radius: 8px;
            background: #fff;
            color: #b91c1c;
            font-size: 13px;
            text-align: center;
        }

        @page {
            size: 58mm auto;
            margin: 0;
        }

        @media print {
            body {
                width: 58mm;
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .acciones {
                display: none;
            }

            .ticket {
                width: 48mm;
                margin: 0 auto;
                padding: 2mm 0;
                border-radius: 0;
                box-shadow: none;
                font-size: 9px;
            }

            td {
                font-size: 8.5px !important;
                padding: .7mm 0 !important;
            }

            .seccion {
                font-size: 8px !important;
            }

            .resultado {
                font-size: 9.5px !important;
                padding: 1mm !important;
            }

            .line {
                margin: 1.5mm 0;
            }

            .line-gruesa {
                margin: 1.8mm 0;
            }
        }
    </style>
</head>

<body>
    <div class="acciones">
        <button type="button" class="btn-imprimir" onclick="window.print()">Imprimir ticket 58 mm</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
        <div class="ticket">
            <p class="centrado" style="margin:0 0 3px;">
                <strong><?= htmlspecialchars($negocioNombre !== '' ? $negocioNombre : 'MI NEGOCIO', ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
            <p class="centrado titulo" style="margin:0;">CIERRE DE CAJA</p>
            <div class="line"></div>

            <table>
                <tr>
                    <td>Fecha:</td>
                    <td class="num"><?= htmlspecialchars($fechaMostrar, ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php if ($horaMostrar !== ''): ?>
                    <tr>
                        <td>Hora:</td>
                        <td class="num"><?= htmlspecialchars($horaMostrar, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td>Cajero:</td>
                    <td class="num"><?= htmlspecialchars($usuarioNombre !== '' ? $usuarioNombre : 'Usuario', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td><?= htmlspecialchars($rolOperador, ENT_QUOTES, 'UTF-8') ?>:</td>
                    <td class="num"><?= htmlspecialchars($adminNombre !== '' ? $adminNombre : 'No indicado', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php if ($sucursalNombre !== ''): ?>
                    <tr>
                        <td>Sucursal:</td>
                        <td class="num"><?= htmlspecialchars($sucursalNombre, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endif; ?>
            </table>

            <div class="line-gruesa"></div>

            <div class="seccion">1. Totales del sistema</div>
            <table>
                <?php foreach ($metodos as $clave => $meta): ?>
                    <tr>
                        <td><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="num"><?= htmlspecialchars($montoConMoneda($sistema[$clave], $meta['moneda'], $meta['dec']), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="line"></div>

            <div class="seccion">2. Entregado por el usuario</div>
            <table>
                <?php foreach ($metodos as $clave => $meta): ?>
                    <tr>
                        <td><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="num"><?= htmlspecialchars($montoConMoneda($contado[$clave], $meta['moneda'], $meta['dec']), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="line"></div>

            <div class="seccion">3. Diferencias por método de pago</div>
            <table>
                <?php foreach ($metodos as $clave => $meta):
                    $dif = (float) $diferencia[$clave];
                    $monto = abs($dif);
                    if (abs($dif) < 0.005) {
                        $texto = 'Cuadrado';
                        $clase = 'cuadrado';
                    } elseif ($dif > 0) {
                        $texto = 'Faltó ' . $montoConMoneda($monto, $meta['moneda'], $meta['dec']);
                        $clase = 'dif-falto';
                    } else {
                        $texto = 'Sobró ' . $montoConMoneda($monto, $meta['moneda'], $meta['dec']);
                        $clase = 'dif-sobro';
                    }
                ?>
                    <tr>
                        <td><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="num <?= $clase ?>"><?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="line-gruesa"></div>

            <div class="seccion">4. Conciliación final</div>
            <table>
                <tr>
                    <td>Total que faltó:</td>
                    <td class="num">$ <?= htmlspecialchars($fmt($faltoUsd), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td>Total que sobró:</td>
                    <td class="num">$ <?= htmlspecialchars($fmt($sobroUsd), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            </table>

            <?php if (abs($netoUsd) < 0.005): ?>
                <div class="resultado cuadrado">CUADRADO · Diferencia $ 0,00</div>
            <?php elseif ($netoUsd > 0): ?>
                <div class="resultado dif-falto">FALTÓ $ <?= htmlspecialchars($fmt($netoUsd), ENT_QUOTES, 'UTF-8') ?></div>
            <?php else: ?>
                <div class="resultado dif-sobro">SOBRÓ $ <?= htmlspecialchars($fmt(abs($netoUsd)), ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <?php if ($sinTasa): ?>
                <p class="nota">* Algunos montos no se pudieron convertir a USD porque no hay tasa configurada para su moneda.</p>
            <?php endif; ?>

            <div class="line"></div>
            <p class="nota">
                Tasa Bs/USD: <?= htmlspecialchars($fmt($tasaBs, 2), ENT_QUOTES, 'UTF-8') ?><br>
                Tasa COP/USD: <?= htmlspecialchars($fmt($tasaCop, 2), ENT_QUOTES, 'UTF-8') ?><br>
                La conciliación final suma lo que faltó y resta lo que sobró, expresado en dólares.
            </p>

            <?php if (!empty($cierre['observaciones'])): ?>
                <div class="line"></div>
                <div class="seccion">Observaciones</div>
                <p class="nota" style="margin-top:0;"><?= nl2br(htmlspecialchars((string) $cierre['observaciones'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>

            <div class="line"></div>
            <p class="centrado" style="font-size:9px;margin:3px 0 0;">*** FIN DEL TICKET ***</p>
        </div>
    <?php endif; ?>
</body>

</html>
