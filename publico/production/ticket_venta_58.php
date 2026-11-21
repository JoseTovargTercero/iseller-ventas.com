<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    header('Location: ../../index.php');
    exit;
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$ordenId = (int) ($_GET['id'] ?? 0);
$moneda = strtoupper(trim((string) ($_GET['moneda'] ?? 'default')));
if (!in_array($moneda, ['DEFAULT', 'USD', 'COP', 'BS'], true)) {
    $moneda = 'DEFAULT';
}
if ($moneda === 'DEFAULT') {
    $moneda = 'default';
}

$error = '';
$orden = null;
$sucursalNombre = '';
$sucursalLogoId = 0;
$contenidoTicket = '';

if ($ordenId <= 0 || $bssId <= 0) {
    $error = 'Venta no válida.';
} else {
    $stmt = $conexion->prepare(
        "SELECT id, id_sucursal, created, total_price, total_price_bs, total_price_cop, tipoPago, status
         FROM orden
         WHERE id = ? AND bss_id = ?
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('ii', $ordenId, $bssId);
        $stmt->execute();
        $orden = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$orden) {
        $error = 'La venta no existe o no pertenece a este negocio.';
    } else {
        $sucursalLogoId = (int) ($orden['id_sucursal'] ?? 0);
        if ($sucursalLogoId > 0) {
            $stmt = $conexion->prepare("SELECT nombre FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('ii', $sucursalLogoId, $bssId);
                $stmt->execute();
                $filaSucursal = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $sucursalNombre = trim((string) ($filaSucursal['nombre'] ?? ''));
            }
        }
        if ($sucursalNombre === '') {
            $sucursalNombre = 'MI TIENDA';
        }

        // Se reutiliza exactamente el mismo contenido del ticket del POS.
        $_POST['id'] = $ordenId;
        $_POST['moneda'] = $moneda;

        $cwd = getcwd();
        chdir(__DIR__ . '/../../configurar');
        try {
            ob_start();
            require 'contenido_ticket.php';
            $contenidoTicket = (string) ob_get_clean();
        } finally {
            chdir($cwd);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket de venta<?= $orden ? ' #' . (int) $orden['id'] : '' ?></title>
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

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            text-align: left;
            padding: 2px 0;
            font-size: 10px;
            word-wrap: break-word;
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

            th,
            td {
                font-size: 8.5px !important;
                padding: 1mm 0.4mm !important;
            }

            .line {
                margin: 1.5mm 0;
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
            <p class="centrado">
                <?php if ($sucursalLogoId > 0): ?>
                    <img src="images/sucursal_logo/<?= (int) $sucursalLogoId ?>.png" height="50" alt="" onerror="this.parentNode.removeChild(this)">
                <?php endif; ?>
                <br><br>
                <?= htmlspecialchars($sucursalNombre, ENT_QUOTES, 'UTF-8') ?>
                <br>
                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $orden['created'])), ENT_QUOTES, 'UTF-8') ?><br>
                <small>* Nota de entrega</small>
            </p>
            <?= $contenidoTicket ?>
            <div class="line"></div>
            <p class="centrado" style="font-size: 10px;">¡GRACIAS POR SU COMPRA!</p>
            <div class="line"></div>
        </div>
    <?php endif; ?>
</body>

</html>
