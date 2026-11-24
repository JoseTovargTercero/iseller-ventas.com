<?php

/**
 * Calcula los totales del sistema para el cierre de caja de un usuario.
 *
 * Se usa la misma lógica que registro_cierre.php: ventas en estado 1 y 4,
 * incluyendo el desglose de pagos fraccionados. El filtro por sucursal es
 * opcional para no alterar el cierre normal, que históricamente suma todas las
 * ventas del usuario en el negocio.
 *
 * @return array{efectivo_bs: float, dolares: float, pesos: float, punto: float, biopago: float, pago_movil: float, transferencia: float}
 */
function caja_totales_sistema($conexion, int $bssId, int $usuarioId, string $fecha, ?int $sucursalId = null): array
{
    $totales = [
        'efectivo_bs' => 0.0,
        'dolares' => 0.0,
        'pesos' => 0.0,
        'punto' => 0.0,
        'biopago' => 0.0,
        'pago_movil' => 0.0,
        'transferencia' => 0.0,
    ];

    $ts = strtotime($fecha);
    $fecha = $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    $filtroSucursal = ($sucursalId !== null && $sucursalId > 0)
        ? ' AND o.id_sucursal = ' . (int) $sucursalId
        : '';

    $sql = "SELECT o.tipoPago,
                   SUM(o.total_price) AS sum_usd,
                   SUM(o.total_price_bs) AS sum_bs,
                   SUM(o.total_price_cop) AS sum_cop
            FROM orden o
            WHERE DATE(o.created) = ?
              AND o.status IN ('1', '4')
              AND o.bss_id = ?
              AND o.usuario = ?
              $filtroSucursal
            GROUP BY o.tipoPago";

    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('sii', $fecha, $bssId, $usuarioId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $tipo = (int) $row['tipoPago'];
            switch ($tipo) {
                case 1:
                    $totales['punto'] += (float) $row['sum_bs'];
                    break;
                case 2:
                    $totales['pago_movil'] += (float) $row['sum_bs'];
                    break;
                case 3:
                    $totales['transferencia'] += (float) $row['sum_bs'];
                    break;
                case 4:
                    $totales['efectivo_bs'] += (float) $row['sum_bs'];
                    break;
                case 5:
                    $totales['dolares'] += (float) $row['sum_usd'];
                    break;
                case 6:
                    $totales['pesos'] += (float) $row['sum_cop'];
                    break;
                case 7:
                    $totales['biopago'] += (float) $row['sum_bs'];
                    break;
            }
        }
        $stmt->close();
    }

    $sqlFracciones = "SELECT SUM(f.punto) AS punto,
                             SUM(f.pagoMovil) AS pagoMovil,
                             SUM(f.transferencia) AS transferencia,
                             SUM(f.bioPago) AS bioPago,
                             SUM(f.efectivo) AS efectivo,
                             SUM(f.pesos) AS pesos,
                             SUM(f.dolares) AS dolares
                      FROM fracciones f
                      INNER JOIN orden o ON o.id = f.id_order
                      WHERE DATE(o.created) = ?
                        AND o.tipoPago = 8
                        AND o.status IN ('1', '4')
                        AND o.bss_id = ?
                        AND o.usuario = ?
                        $filtroSucursal";

    $stmt = $conexion->prepare($sqlFracciones);
    if ($stmt) {
        $stmt->bind_param('sii', $fecha, $bssId, $usuarioId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $totales['punto'] += (float) $row['punto'];
            $totales['pago_movil'] += (float) $row['pagoMovil'];
            $totales['transferencia'] += (float) $row['transferencia'];
            $totales['biopago'] += (float) $row['bioPago'];
            $totales['efectivo_bs'] += (float) $row['efectivo'];
            $totales['pesos'] += (float) $row['pesos'];
            $totales['dolares'] += (float) $row['dolares'];
        }
        $stmt->close();
    }

    foreach ($totales as $clave => $valor) {
        $totales[$clave] = round((float) $valor, 2);
    }

    return $totales;
}
