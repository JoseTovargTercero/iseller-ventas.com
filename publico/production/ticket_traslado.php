<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if ($nivel !== 1 && $nivel !== 2) {
    header('Location: ../../index.php');
    exit;
}

$bssId = (int) ($_SESSION['bss_id'] ?? 0);
$cargamentoId = (int) ($_GET['cargamento'] ?? 0);
$error = '';
$cargamento = null;
$items = [];
$totalUnidades = 0.0;

if ($cargamentoId <= 0 || $bssId <= 0) {
    $error = 'Cargamento no válido.';
} else {
    $stmt = $conexion->prepare(
        "SELECT C.*, so.nombre AS origen, sd.nombre AS destino
         FROM stock_cargamentos C
         LEFT JOIN sucursales so ON so.id = C.sucursal_origen AND so.bss_id = C.bss_id
         LEFT JOIN sucursales sd ON sd.id = C.sucursal_destino AND sd.bss_id = C.bss_id
         WHERE C.id = ? AND C.bss_id = ?
         LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('ii', $cargamentoId, $bssId);
        $stmt->execute();
        $cargamento = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$cargamento) {
        $error = 'El cargamento no existe o no pertenece a este negocio.';
    } else {
        $stmt = $conexion->prepare(
            "SELECT T.id_producto, T.producto_nombre, T.cantidad,
                    COALESCE(NULLIF(p.unidad, ''), 'UND') AS unidad
             FROM stock_traslados T
             LEFT JOIN productos p ON p.id = T.id_producto AND p.bss_id = T.bss_id
             WHERE T.cargamento_id = ? AND T.bss_id = ?
             ORDER BY T.id ASC"
        );
        if ($stmt) {
            $stmt->bind_param('ii', $cargamentoId, $bssId);
            $stmt->execute();
            $res = $stmt->get_result();
            $agrupados = [];
            while ($fila = $res->fetch_assoc()) {
                $productoId = (int) $fila['id_producto'];
                if (!isset($agrupados[$productoId])) {
                    $agrupados[$productoId] = [
                        'producto' => (string) $fila['producto_nombre'],
                        'unidad' => (string) $fila['unidad'],
                        'cantidad' => 0.0,
                    ];
                }
                $agrupados[$productoId]['cantidad'] += (float) $fila['cantidad'];
            }
            $stmt->close();

            $items = array_values($agrupados);
            usort($items, static function ($a, $b) {
                return strcmp((string) $a['producto'], (string) $b['producto']);
            });
            foreach ($items as &$item) {
                $item['cantidad'] = round((float) $item['cantidad'], 4);
                $totalUnidades += $item['cantidad'];
            }
            unset($item);
            $totalUnidades = round($totalUnidades, 4);
        }
    }
}

function ticket_h($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket de traslado<?= $cargamento ? ' #' . (int) $cargamento['id'] : '' ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #eef1f5;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .acciones {
            max-width: 760px;
            margin: 0 auto 14px;
            text-align: right;
        }

        .btn-imprimir,
        .btn-tabla {
            display: inline-block;
            border: none;
            border-radius: 7px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-imprimir {
            background: #2dd4a0;
            color: #07130f;
        }

        .btn-tabla {
            background: #e2e8f0;
            color: #334155;
            margin-right: 8px;
        }

        .btn-tabla:hover {
            color: #0f172a;
            text-decoration: none;
        }

        .ticket {
            max-width: 760px;
            margin: 0 auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .12);
        }

        .ticket-head {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
        }

        .ticket-head h1 {
            margin: 0 0 4px;
            font-size: 19px;
            letter-spacing: .4px;
        }

        .ticket-head p {
            margin: 0;
            color: #6b7280;
            font-size: 12px;
        }

        .ticket-id {
            text-align: right;
            font-size: 25px;
            font-weight: 800;
            white-space: nowrap;
        }

        .ticket-id small {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .8px;
            color: #6b7280;
        }

        .datos {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 24px;
            margin: 20px 0;
            font-size: 13px;
        }

        .dato strong {
            display: block;
            margin-bottom: 2px;
            color: #6b7280;
            font-size: 10px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .dato.completo {
            grid-column: 1 / -1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 8px 6px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background: #f8fafc;
            font-size: 10px;
            letter-spacing: .5px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .right {
            text-align: right;
        }

        tfoot th {
            border-top: 2px solid #111827;
            border-bottom: none;
            color: #111827;
            font-size: 12px;
        }

        .nota {
            margin-top: 18px;
            padding: 10px 12px;
            border-radius: 7px;
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            line-height: 1.5;
        }

        .firmas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 55px;
            font-size: 11px;
            text-align: center;
            color: #374151;
        }

        .firma {
            padding-top: 7px;
            border-top: 1px solid #111827;
        }

        .error {
            max-width: 760px;
            margin: 40px auto;
            padding: 22px;
            border-radius: 10px;
            background: #fff;
            color: #b91c1c;
            font-size: 14px;
            text-align: center;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .acciones {
                display: none;
            }

            .ticket {
                max-width: none;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="acciones">
        <?php if ($cargamento): ?>
            <a class="btn-tabla" href="transferir.php?cargamento=<?= (int) $cargamento['id'] ?>">Ver en la tabla</a>
        <?php endif; ?>
        <button type="button" class="btn-imprimir" onclick="window.print()">Imprimir ticket</button>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= ticket_h($error) ?></div>
    <?php else: ?>
        <div class="ticket">
            <div class="ticket-head">
                <div>
                    <h1>TRASLADO INTERNO ENTRE SUCURSALES</h1>
                    <p>Comprobante de cargamento</p>
                </div>
                <div class="ticket-id">
                    <small>ID DEL TRASPASO</small>
                    #<?= (int) $cargamento['id'] ?>
                </div>
            </div>

            <div class="datos">
                <div class="dato">
                    <strong>Fecha y hora</strong>
                    <?= ticket_h(date('d/m/Y H:i', strtotime((string) $cargamento['created_at']))) ?>
                </div>
                <div class="dato">
                    <strong>Usuario</strong>
                    <?= ticket_h($cargamento['usuario_nombre'] ?? '—') ?>
                </div>
                <div class="dato">
                    <strong>Sucursal origen</strong>
                    <?= ticket_h($cargamento['origen'] ?? '—') ?>
                </div>
                <div class="dato">
                    <strong>Sucursal destino</strong>
                    <?= ticket_h($cargamento['destino'] ?? '—') ?>
                </div>
                <div class="dato completo">
                    <strong>Motivo</strong>
                    <?= nl2br(ticket_h($cargamento['motivo'] ?? '—')) ?>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="right">Cantidad</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$items): ?>
                        <tr>
                            <td colspan="3" class="right" style="color:#6b7280;">Sin artículos registrados.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= ticket_h($item['producto']) ?></td>
                            <td class="right"><?= number_format((float) $item['cantidad'], 4, ',', '.') ?></td>
                            <td><?= ticket_h($item['unidad']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="right"><?= number_format($totalUnidades, 4, ',', '.') ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>

            <div class="nota">
                Guarde este ID para buscar el cargamento en el historial de traslados:
                <strong>#<?= (int) $cargamento['id'] ?></strong>.
                Artículos: <?= count($items) ?>. Unidades totales: <?= number_format($totalUnidades, 4, ',', '.') ?>.
            </div>

            <div class="firmas">
                <div class="firma">Despachado por</div>
                <div class="firma">Recibido por</div>
            </div>
        </div>
    <?php endif; ?>
</body>

</html>
