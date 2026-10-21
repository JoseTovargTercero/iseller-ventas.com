<?php

class MembresiaHelper
{
    /**
     * Consulta el estado de la membresía del negocio actual.
     * Retorna un arreglo con información completa de vigencia y alertas.
     */
    public static function obtenerEstado($conexion, $bss_id)
    {
        $info = [
            'tipo'              => 'prueba',
            'plan'              => 'mensual',
            'estado'            => 'activa',
            'fecha_inicio'      => null,
            'fecha_fin'         => null,
            'dias_restantes'    => 0,
            'esta_vencida'      => false,
            'por_vencer'        => false,
            'pago_pendiente'    => false,
            'ultimo_comprobante' => null
        ];

        if (empty($bss_id)) {
            return $info;
        }

        $stmt = $conexion->prepare("SELECT plan, membresia_tipo, membresia_estado, fecha_inicio_membresia, fecha_fin_membresia, proxima_facturacion FROM negocio WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $bss_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $info['tipo']   = $row['membresia_tipo'] ?: 'prueba';
                $info['plan']   = in_array($row['plan'], ['mensual', 'anual']) ? $row['plan'] : 'mensual';
                $info['estado'] = $row['membresia_estado'] ?: 'activa';

                $fecha_inicio = $row['fecha_inicio_membresia'];
                $fecha_fin = $row['fecha_fin_membresia'];
                if (!$fecha_fin && $row['proxima_facturacion']) {
                    $fecha_fin = $row['proxima_facturacion'] . ' 23:59:59';
                }

                // Autocorrección: si la fecha de inicio está vacía, o si es una membresía de prueba activa
                // cuya fecha_inicio es de hace meses pero su fecha_fin es vigente, corregir fecha_inicio
                if (empty($fecha_inicio)) {
                    $fecha_inicio = date('Y-m-d H:i:s');
                    $stmtFix = $conexion->prepare("UPDATE negocio SET fecha_inicio_membresia = ? WHERE id = ?");
                    if ($stmtFix) {
                        $stmtFix->bind_param("si", $fecha_inicio, $bss_id);
                        $stmtFix->execute();
                        $stmtFix->close();
                    }
                }

                $info['fecha_inicio'] = $fecha_inicio;
                $info['fecha_fin'] = $fecha_fin;

                if ($fecha_fin) {
                    $ahora = time();
                    $fin   = strtotime($fecha_fin);
                    $diff_segundos = $fin - $ahora;
                    // Días naturales restantes (evita el off-by-one por vencer a las 23:59:59)
                    $dias = (int)round((strtotime(date('Y-m-d', $fin)) - strtotime(date('Y-m-d', $ahora))) / 86400);
                    if ($dias < 0) {
                        $dias = 0;
                    } elseif ($dias < 1 && $diff_segundos > 0) {
                        $dias = 1; // vence hoy: se informa al menos 1 día
                    }

                    $info['dias_restantes'] = $dias;

                    // Si ya pasó la fecha de fin y el estado no es pago verificado
                    if ($diff_segundos <= 0) {
                        $info['esta_vencida'] = true;
                        // Actualizar en base de datos si aún aparecía como 'activa'
                        if ($row['membresia_estado'] === 'activa') {
                            $stmtUpd = $conexion->prepare("UPDATE negocio SET membresia_estado = 'vencida' WHERE id = ?");
                            if ($stmtUpd) {
                                $stmtUpd->bind_param("i", $bss_id);
                                $stmtUpd->execute();
                                $stmtUpd->close();
                            }
                            $info['estado'] = 'vencida';
                        }
                    } elseif ($dias <= 7) {
                        $info['por_vencer'] = true;
                    }
                }
            }
            $stmt->close();
        }

        // Consultar si hay algún pago pendiente de revisión
        $stmtPago = $conexion->prepare("SELECT id, plan, monto_usd, numero_operacion, hora_operacion, fecha_reporte, estado FROM membresias_pagos WHERE bss_id = ? ORDER BY id DESC LIMIT 1");
        if ($stmtPago) {
            $stmtPago->bind_param("i", $bss_id);
            $stmtPago->execute();
            $resPago = $stmtPago->get_result();
            if ($rowPago = $resPago->fetch_assoc()) {
                $info['ultimo_comprobante'] = $rowPago;
                if ($rowPago['estado'] === 'pendiente') {
                    $info['pago_pendiente'] = true;
                }
            }
            $stmtPago->close();
        }

        return $info;
    }

    /**
     * Datos bancarios y de pago oficiales de la plataforma para pagar suscripciones
     */
    public static function getDatosPagoOficiales()
    {
        return [
            'pago_movil' => [
                'banco'     => '0134 - Banesco',
                'telefono'  => '0416-0679095',
                'cedula'    => 'V-27640176',
                'titular'   => 'ROMERO TOVAR JOSE RICARDO'
            ],
            'transferencia' => [
                'banco'     => 'Banesco',
                'cuenta'    => '0134-0444-51-4441211410',
                'titular'   => 'ROMERO TOVAR JOSE RICARDO',
                'cedula'       => 'V-27640176'
            ],
            'precios_usd' => [
                'mensual' => 8.00,
                'anual'   => 40.00
            ]
        ];
    }

    /**
     * Activa una membresía de prueba para un negocio asegurando que la fecha
     * de inicio sea SIEMPRE la fecha/hora actual y la fecha de fin sea calculada a partir de hoy.
     */
    public static function activarPrueba($conexion, $bss_id, $dias = 30)
    {
        $bss_id = (int)$bss_id;
        $dias = max(1, (int)$dias);
        $ahora = date('Y-m-d H:i:s');
        $fecha_fin = date('Y-m-d 23:59:59', strtotime("+{$dias} days"));
        $proxima = date('Y-m-d', strtotime("+{$dias} days"));

        $stmt = $conexion->prepare("UPDATE negocio SET membresia_tipo = 'prueba', plan = 'mensual', membresia_estado = 'activa', estado = 'activo', fecha_inicio_membresia = ?, fecha_fin_membresia = ?, proxima_facturacion = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssi", $ahora, $fecha_fin, $proxima, $bss_id);
            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }
        return false;
    }
}
